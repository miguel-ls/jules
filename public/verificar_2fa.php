<?php
require_once __DIR__ . '/../config/database.php';

// Si el usuario no ha pasado el primer paso de login, no debería estar aquí.
if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_code = trim($_POST['auth_code']);
    $user_id = $_SESSION['2fa_user_id'];

    if (empty($submitted_code)) {
        $error_message = "Por favor, ingrese el código de verificación.";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            // No tenemos un SP para leer solo el código, así que leemos todo el usuario
            $stmt = $conn->prepare("CALL sp_usuario_leer_por_id(?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                // Verificar que el código no haya expirado y que coincida
                if (strtotime($user['auth_code_2fa_expiry']) > time() && $user['auth_code_2fa'] == $submitted_code) {
                    // ¡Éxito! Limpiar variables 2FA y establecer sesión final
                    unset($_SESSION['2fa_user_id']);
                    unset($_SESSION['2fa_code_demo']); // SOLO PARA DEMO

                    $_SESSION['id_usuario'] = $user['id_usuario'];
                    $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                    $_SESSION['rol'] = $user['rol'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "El código es incorrecto o ha expirado.";
                }
            } else {
                $error_message = "Error al verificar la cuenta de usuario.";
            }
            $stmt->close();
            $conn->close();
        } else {
            $error_message = "Error de conexión con la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Dos Factores</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 20px 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        h2 { color: #333; }
        p { color: #555; }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-size: 1.2em; letter-spacing: 5px; }
        .btn { background-color: #0056b3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn:hover { background-color: #004494; }
        .error-message { color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .demo-code { background-color: #e9ecef; color: #495057; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Verificación Requerida</h2>
    <p>Hemos enviado un código de verificación a su correo. Por favor, ingréselo a continuación.</p>

    <!-- Bloque solo para demostración -->
    <div style="margin-bottom: 15px;">
        <small>Para fines de demostración, el código es:</small>
        <div class="demo-code"><?= htmlspecialchars($_SESSION['2fa_code_demo'] ?? 'Error') ?></div>
    </div>
    <!-- Fin del bloque de demostración -->

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="verificar_2fa.php" method="post">
        <div class="form-group">
            <input type="text" name="auth_code" maxlength="6" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Verificar</button>
        </div>
    </form>
    <a href="login.php">Volver a iniciar sesión</a>
</div>

</body>
</html>
