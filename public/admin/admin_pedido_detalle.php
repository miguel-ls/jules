<?php
require_once __DIR__ . '/../../src/lib/database.php';
$db = Database::getConnection();

$pedido_id = $_GET['id'] ?? null;
if (!$pedido_id) {
    header('Location: admin_pedidos.php');
    exit;
}

// Obtener información general del pedido y del cliente
$stmt = $db->prepare("
    SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    echo "Pedido no encontrado.";
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

include __DIR__ . '/../../templates/partials/admin_header.php';
?>

<h2>Detalles del Pedido #<?php echo $pedido['id']; ?></h2>

<div class="form-container" style="max-width: 900px;">
    <h3>Información del Pedido</h3>
    <p><strong>ID Pedido:</strong> <?php echo $pedido['id']; ?></p>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nombre']); ?> (<?php echo htmlspecialchars($pedido['cliente_email']); ?>)</p>
    <p><strong>Fecha:</strong> <?php echo $pedido['fecha_pedido']; ?></p>
    <p><strong>Total:</strong> <?php echo number_format($pedido['total'], 2); ?> €</p>
    <p><strong>Estado:</strong> <?php echo ucfirst($pedido['estado']); ?></p>

    <hr style="margin: 20px 0;">

    <h3>Artículos del Pedido</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Imagen</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $detalles->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                <td><img src="../uploads/products/<?php echo htmlspecialchars($item['producto_imagen']); ?>" alt="" width="50"></td>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo number_format($item['precio_unitario'], 2); ?> €</td>
                <td><?php echo number_format($item['cantidad'] * $item['precio_unitario'], 2); ?> €</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="admin_pedidos.php" class="btn" style="margin-top: 20px;">Volver a la lista de pedidos</a>
</div>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
