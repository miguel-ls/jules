<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// Proteger: usuario debe estar logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

$pedido_id = $_GET['id'] ?? null;
if (!$pedido_id) {
    header('Location: dashboard.php');
    exit;
}

// Obtener información del pedido, asegurándose de que pertenece al usuario logueado
$stmt = $db->prepare("
    SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?
");
$stmt->bind_param('ii', $pedido_id, $usuario_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si el pedido no se encuentra o no pertenece al usuario, redirigir
if (!$pedido) {
    header('Location: dashboard.php');
    exit;
}

// Obtener los detalles del pedido (productos)
$stmt_detalles = $db->prepare("
    SELECT dp.*, pr.nombre as producto_nombre, pr.imagen as producto_imagen
    FROM detalles_pedido dp
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = ?
");
$stmt_detalles->bind_param('i', $pedido_id);
$stmt_detalles->execute();
$detalles = $stmt_detalles->get_result();
$stmt_detalles->close();

include __DIR__ . '/../templates/partials/header.php';
?>
<style>
.cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.cart-table th, .cart-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
.cart-table th { background-color: #f2f2f2; }
.cart-table img { width: 80px; }
</style>

<h2>Detalles del Pedido #<?php echo $pedido['id']; ?></h2>

<div class="order-details-container form-container" style="max-width: 900px; margin: auto;">
    <h3>Información del Pedido</h3>
    <p><strong>Fecha:</strong> <?php echo $pedido['fecha_pedido']; ?></p>
    <p><strong>Total:</strong> <?php echo number_format($pedido['total'], 2); ?> €</p>
    <p><strong>Estado:</strong> <?php echo ucfirst($pedido['estado']); ?></p>

    <hr style="margin: 20px 0;">

    <h3>Artículos del Pedido</h3>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th></th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $detalles->fetch_assoc()): ?>
            <tr>
                <td><img src="uploads/products/<?php echo htmlspecialchars($item['producto_imagen']); ?>" alt=""></td>
                <td><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo number_format($item['precio_unitario'], 2); ?> €</td>
                <td><?php echo number_format($item['cantidad'] * $item['precio_unitario'], 2); ?> €</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn" style="margin-top: 20px;">Volver a Mis Pedidos</a>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
