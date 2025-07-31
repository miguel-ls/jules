<?php
require_once __DIR__ . '/../../../config/config.php';

// Protección de la sección de administración
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/admin_style.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="admin_categorias.php">Gestionar Categorías</a></li>
            <li><a href="admin_productos.php">Gestionar Productos</a></li>
            <li><a href="admin_usuarios.php">Gestionar Usuarios</a></li>
            <li><a href="admin_pedidos.php">Gestionar Pedidos</a></li>
            <li><a href="../index.php" target="_blank">Ver Sitio</a></li>
            <li><a href="../logout.php">Cerrar Sesión</a></li>
        </ul>
    </aside>
    <main class="admin-content">
