<?php
// public/login.php

// Incluir el archivo de configuración para la conexión a la BD y el inicio de sesión
require_once __DIR__ . '/../config/database.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["nombre_usuario"])) || empty(trim($_POST["password"]))) {
        $error_message = "Por favor, ingrese su nombre de usuario y contraseña.";
    } else {
        $nombre_usuario = trim($_POST["nombre_usuario"]);
        $password = trim($_POST["password"]);

        $conn = getDBConnection();

        if ($conn) {
            // Llamar al procedimiento almacenado para obtener los datos del usuario
            $stmt = $conn->prepare("CALL sp_usuario_leer_por_nombre_usuario(?)");
            $stmt->bind_param("s", $nombre_usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                // Verificar la contraseña
                if (password_verify($password, $user['password_hash'])) {
                    // Contraseña correcta, iniciar el flujo de 2FA
                    $auth_code = rand(100000, 999999);
                    $expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    // Guardar el código en la BD
                    $stmt_2fa = $conn->prepare("CALL sp_usuario_guardar_codigo_2fa(?, ?, ?)");
                    $stmt_2fa->bind_param("iss", $user['id_usuario'], $auth_code, $expiry_time);
                    $stmt_2fa->execute();
                    $stmt_2fa->close();

                    // Simulación de envío de email
                    // En un proyecto real, aquí iría la lógica para enviar el email.
                    // mail($user['email'], 'Tu código de verificación', 'Tu código es: ' . $auth_code);

                    // Guardar temporalmente el ID de usuario y el código para la demo
                    $_SESSION['2fa_user_id'] = $user['id_usuario'];
                    $_SESSION['2fa_code_demo'] = $auth_code; // SOLO PARA DEMO

                    // Redirigir a la página de verificación 2FA
                    header("Location: verificar_2fa.php");
                    exit();
                } else {
                    // Contraseña incorrecta
                    $error_message = "La contraseña ingresada no es válida.";
                }
            } else {
                // Usuario no encontrado
                $error_message = "No se encontró ninguna cuenta con ese nombre de usuario.";
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
    <title>Iniciar Sesión - Sistema de Matrícula</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 20px 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { background-color: #0056b3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn:hover { background-color: #004494; }
        .error-message { color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Iniciar Sesión</h2>
    <p>Acceso al panel de administración</p>

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="nombre_usuario">Nombre de Usuario</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Ingresar</button>
        </div>
    </form>
</div>

</body>
</html>
