<?php
// public/dashboard.php

// Incluir el script de verificación de autenticación.
// Si el usuario no está logueado, será redirigido automáticamente a login.php.
require_once __DIR__ . '/../src/Core/auth_check.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Matrícula</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f9f9f9; }
        .navbar { background-color: #333; overflow: hidden; }
        .navbar a { float: left; display: block; color: white; text-align: center; padding: 14px 20px; text-decoration: none; }
        .navbar a.logout { float: right; background-color: #d9534f; }
        .main-content { padding: 20px; }
        .welcome-banner { background-color: #0056b3; color: white; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="main-content">
    <div class="welcome-banner">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>!</h1>
        <p>Este es el panel de administración del sistema de matrícula de natación.</p>
        <p>Tu rol es: <strong><?php echo htmlspecialchars($_SESSION['rol']); ?></strong></p>
    </div>

    <h2>Acciones Rápidas</h2>
    <p>Próximamente aquí encontrarás enlaces directos a las tareas más comunes.</p>
    <!-- Ejemplo: <a href="matricular_alumno.php">Nueva Matrícula</a> -->
</div>

</body>
</html>
