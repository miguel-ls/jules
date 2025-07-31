<?php include __DIR__ . '/../../templates/partials/admin_header.php'; ?>

<h2>Dashboard</h2>
<p>Bienvenido al panel de administración, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>.</p>
<p>Usa el menú de la izquierda para gestionar las diferentes secciones del sitio web.</p>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
