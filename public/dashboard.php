<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// Proteger la página: si el usuario no está logueado, redirigir a login.php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener los pedidos del usuario
$stmt = $db->prepare("
    SELECT id, fecha_pedido, total, estado
    FROM pedidos
    WHERE usuario_id = ?
    ORDER BY fecha_pedido DESC
");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$pedidos = $stmt->get_result();

include __DIR__ . '/../templates/partials/header.php';
?>

<h2>Mi Cuenta</h2>

<div class="dashboard-wrapper" style="display: flex; gap: 30px;">
    <div class="user-info" style="flex: 1;">
        <h3>Información Personal</h3>
        <div class="form-container" style="margin:0; padding: 20px;">
             <p><strong>Nombre:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
             <?php
            // Obtener email del usuario
            $stmt_user = $db->prepare("SELECT email FROM usuarios WHERE id = ?");
            $stmt_user->bind_param('i', $_SESSION['usuario_id']);
            $stmt_user->execute();
            $user_email = $stmt_user->get_result()->fetch_assoc()['email'];
            ?>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
        </div>
    </div>
    <div class="order-history" style="flex: 3;">
        <h3>Mi Historial de Pedidos</h3>
        <?php if ($pedidos->num_rows > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $pedido['id']; ?></td>
                        <td><?php echo $pedido['fecha_pedido']; ?></td>
                        <td><?php echo number_format($pedido['total'], 2); ?> €</td>
                        <td><?php echo ucfirst($pedido['estado']); ?></td>
                        <td><a href="pedido_detalle.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm">Ver Detalles</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No has realizado ningún pedido todavía.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
