<?php
// Incluir configuración y iniciar sesión.
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión 3D Services</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
</head>
<body>

<header>
    <div class="container">
        <h1><a href="<?php echo SITE_URL; ?>/public/index.php">Impresión 3D</a></h1>
        <nav>
            <ul>
                <li><a href="<?php echo SITE_URL; ?>/public/index.php">Inicio</a></li>
                <li><a href="<?php echo SITE_URL; ?>/public/catalogo.php">Catálogo</a></li>
                <li><a href="<?php echo SITE_URL; ?>/public/servicios.php">Servicios</a></li>
                <li><a href="<?php echo SITE_URL; ?>/public/contacto.php">Contacto</a></li>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="<?php echo SITE_URL; ?>/public/dashboard.php">Mi Cuenta</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/public/logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/public/login.php">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/public/registro.php">Registro</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
