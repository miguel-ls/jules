<?php
$errors = [];

// Si el usuario ya está logueado, redirigir al dashboard
require_once __DIR__ . '/../config/config.php';
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once __DIR__ . '/../src/lib/database.php';
    $db = Database::getConnection();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'El correo y la contraseña son obligatorios.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id, nombre, password, rol, email_verificado FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Contraseña correcta

            if (!$user['email_verificado']) {
                $errors[] = 'Tu cuenta no ha sido verificada. Por favor, revisa tu correo electrónico.';
            } else {
                // Regenerar ID de sesión para seguridad
                session_regenerate_id(true);

                // Guardar datos del usuario en la sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_rol'] = $user['rol'];

                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    // Iniciar proceso de 2FA para administradores
                    $user_id = $user['id'];
                    $codigo_2fa = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 600); // Válido por 10 minutos

                    // Guardar el código en la base de datos
                    $stmt_2fa = $db->prepare("INSERT INTO two_factor_codes (usuario_id, codigo, fecha_expiracion) VALUES (?, ?, ?)");
                    $stmt_2fa->bind_param("iss", $user_id, $codigo_2fa, $expires);
                    $stmt_2fa->execute();
                    $stmt_2fa->close();

                    // Guardar ID de usuario temporalmente en la sesión para verificar en la siguiente página
                    $_SESSION['2fa_user_id'] = $user_id;

                    // Simular envío de email mostrando el código
                    $_SESSION['2fa_code_flash'] = $codigo_2fa;

                    header('Location: verificar_2fa.php');
                } else {
                    // Login normal para clientes
                    header('Location: dashboard.php');
                }
                exit;
            }

        } else {
            // Usuario no encontrado o contraseña incorrecta
            $errors[] = 'Correo electrónico o contraseña incorrectos.';
        }
    }
}

include __DIR__ . '/../templates/partials/header.php';
?>

<div class="form-container">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Acceder</button>
    </form>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
