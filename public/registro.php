<?php
// Este bloque de PHP manejará la lógica de registro
$errors = [];
$success_message = '';

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Incluir la clase de la base de datos
    require_once __DIR__ . '/../src/lib/database.php';

    $db = Database::getConnection();

    // Recoger y sanear los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // --- Validación de datos ---
    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }
    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido.';
    }
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    // Si no hay errores de validación, proceder a verificar el email en la DB
    if (empty($errors)) {
        // Comprobar si el email ya existe
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'El correo electrónico ya está registrado.';
        }
        $stmt->close();
    }

    // Si después de todas las validaciones no hay errores, registrar al usuario
    if (empty($errors)) {
        // Hashear la contraseña por seguridad
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insertar usuario en la base de datos (con email_verificado = 0 por defecto)
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $password_hashed);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();

            // Generar token de verificación
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 86400); // Token válido por 24 horas

            $stmt_token = $db->prepare("INSERT INTO email_verifications (usuario_id, token, fecha_expiracion) VALUES (?, ?, ?)");
            $stmt_token->bind_param("iss", $user_id, $token, $expires);

            if ($stmt_token->execute()) {
                // Simulación de envío de correo electrónico
                $verification_link = SITE_URL . '/public/verificar_email.php?token=' . $token;

                $success_message = '¡Registro exitoso! Se ha enviado un correo de verificación. ';
                $success_message .= '<br><strong>Para fines de demostración, aquí está tu enlace de verificación:</strong>';
                $success_message .= '<br><a href="' . htmlspecialchars($verification_link) . '">' . htmlspecialchars($verification_link) . '</a>';

            } else {
                $errors[] = 'Ocurrió un error al generar el token de verificación.';
            }
            $stmt_token->close();

        } else {
            $errors[] = 'Ocurrió un error durante el registro. Por favor, inténtalo de nuevo.';
            $stmt->close();
        }
    }
}
?>

<?php include __DIR__ . '/../templates/partials/header.php'; ?>

<div class="form-container">
    <h2>Registro de Usuario</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php else: ?>
        <form action="registro.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar Contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn">Registrarse</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
