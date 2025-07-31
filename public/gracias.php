<?php
require_once __DIR__ . '/../config/config.php';

// Proteger la página: solo se puede acceder si se acaba de completar un pedido
if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['order_id'];
// Limpiar el ID del pedido de la sesión para que el mensaje no se muestre de nuevo
unset($_SESSION['order_id']);

include __DIR__ . '/../templates/partials/header.php';
?>

<div class="container" style="text-align: center; padding: 50px 0;">
    <div class="alert alert-success">
        <h2>¡Gracias por tu compra!</h2>
    </div>

    <p>Tu pedido ha sido procesado con éxito.</p>
    <p><strong>Tu número de pedido es: <?php echo htmlspecialchars($order_id); ?></strong></p>
    <p>Hemos enviado una confirmación a tu correo electrónico (simulado).</p>

    <div style="margin-top: 30px;">
        <a href="dashboard.php" class="btn">Ver Mis Pedidos</a>
        <a href="catalogo.php" class="btn" style="margin-left: 10px;">Seguir Comprando</a>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
