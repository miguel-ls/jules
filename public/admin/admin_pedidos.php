<?php
require_once __DIR__ . '/../../src/lib/database.php';
$db = Database::getConnection();

// --- Manejo de acciones POST (Actualizar estado) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $pedido_id = $_POST['pedido_id'];
    $estado = $_POST['estado'];
    $allowed_statuses = ['pendiente', 'procesando', 'enviado', 'completado', 'cancelado'];

    if (in_array($estado, $allowed_statuses)) {
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param('si', $estado, $pedido_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Estado del pedido actualizado con éxito.';
        } else {
            $_SESSION['message'] = 'Error al actualizar el estado del pedido.';
        }
    } else {
        $_SESSION['message'] = 'Estado no válido.';
    }
    header('Location: admin_pedidos.php');
    exit;
}


include __DIR__ . '/../../templates/partials/admin_header.php';
?>

<h2>Gestionar Pedidos</h2>

<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}
?>

<table>
    <thead>
        <tr>
            <th>ID Pedido</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $pedidos = $db->query("
            SELECT p.id, u.nombre as cliente_nombre, p.fecha_pedido, p.total, p.estado
            FROM pedidos p
            JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.fecha_pedido DESC
        ");
        $statuses = ['pendiente', 'procesando', 'enviado', 'completado', 'cancelado'];

        if ($pedidos->num_rows > 0):
            while ($pedido = $pedidos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $pedido['id']; ?></td>
                <td><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></td>
                <td><?php echo $pedido['fecha_pedido']; ?></td>
                <td><?php echo number_format($pedido['total'], 2); ?> €</td>
                <td>
                    <form action="admin_pedidos.php" method="POST" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                        <select name="estado" class="form-group" style="margin: 0; padding: 5px;">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $pedido['estado'] === $status ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm">Actualizar</button>
                    </form>
                </td>
                <td>
                    <a href="admin_pedido_detalle.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm">Ver Detalles</a>
                </td>
            </tr>
            <?php endwhile;
        else: ?>
            <tr><td colspan="6" style="text-align:center;">No hay pedidos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
