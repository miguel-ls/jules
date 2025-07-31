<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// Proteger la página: el usuario debe haber pasado la primera fase de login
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$user_id = $_SESSION['2fa_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $errors[] = 'El código de verificación es obligatorio.';
    } else {
        $current_time = date('Y-m-d H:i:s');

        // Buscar el código en la base de datos
        $stmt = $db->prepare("SELECT id, fecha_expiracion FROM two_factor_codes WHERE usuario_id = ? AND codigo = ?");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $code_data = $result->fetch_assoc();
        $stmt->close();

        if (!$code_data) {
            $errors[] = 'El código de verificación es incorrecto.';
        } elseif ($code_data['fecha_expiracion'] < $current_time) {
            $errors[] = 'El código de verificación ha expirado.';
        } else {
            // Código correcto y válido

            // 1. Limpiar códigos de 2FA para este usuario
            $stmt_delete = $db->prepare("DELETE FROM two_factor_codes WHERE usuario_id = ?");
            $stmt_delete->bind_param("i", $user_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // 2. Obtener datos del usuario para la sesión final
            $stmt_user = $db->prepare("SELECT id, nombre, rol FROM usuarios WHERE id = ?");
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $user = $stmt_user->get_result()->fetch_assoc();
            $stmt_user->close();

            // 3. Establecer la sesión final
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol'] = $user['rol'];
            unset($_SESSION['2fa_user_id']); // Limpiar la sesión temporal

            header('Location: admin/index.php');
            exit;
        }
    }
}

// Para la demostración, mostrar el código que se "envió por correo"
$flash_code = $_SESSION['2fa_code_flash'] ?? null;
unset($_SESSION['2fa_code_flash']); // Borrar después de mostrar

include __DIR__ . '/../templates/partials/header.php';
?>

<div class="form-container">
    <h2>Verificación de Dos Pasos</h2>
    <p>Se ha enviado un código de 6 dígitos a tu correo electrónico. Introdúcelo a continuación.</p>

    <?php if ($flash_code): ?>
    <div class="alert alert-success">
        <strong>Para fines de demostración:</strong> Tu código es <?php echo htmlspecialchars($flash_code); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="verificar_2fa.php" method="POST">
        <div class="form-group">
            <label for="code">Código de 6 dígitos</label>
            <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}" title="Debe ser un código de 6 dígitos.">
        </div>
        <button type="submit" class="btn">Verificar y Acceder</button>
    </form>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
