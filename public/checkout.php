<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// 1. Proteger: El usuario debe estar logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['message'] = 'Debes iniciar sesión para realizar un pedido.';
    header('Location: login.php');
    exit;
}

// 2. Proteger: El carrito no puede estar vacío
if (empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit;
}

// Lógica para procesar el pedido cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->begin_transaction();
    try {
        // Recalcular total y obtener productos del carrito para asegurar datos frescos
        $cart_items_data = [];
        $total_price = 0;
        if (!empty($_SESSION['carrito'])) {
            $product_ids = array_keys($_SESSION['carrito']);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $types = str_repeat('i', count($product_ids));
            $stmt = $db->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($product = $result->fetch_assoc()) {
                $quantity = $_SESSION['carrito'][$product['id']];
                // Comprobación final de stock
                if ($quantity > $product['stock']) {
                    throw new Exception("Lo sentimos, el producto '{$product['nombre']}' no tiene suficiente stock.");
                }
                $total_price += $product['precio'] * $quantity;
                $cart_items_data[$product['id']] = ['product' => $product, 'quantity' => $quantity];
            }
        }

        // 1. Crear el pedido en la tabla `pedidos`
        $stmt_pedido = $db->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
        $stmt_pedido->bind_param('id', $_SESSION['usuario_id'], $total_price);
        $stmt_pedido->execute();
        $pedido_id = $stmt_pedido->insert_id;
        $stmt_pedido->close();

        // 2. Insertar detalles del pedido y actualizar stock
        $stmt_detalle = $db->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmt_stock = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($cart_items_data as $item_data) {
            $product = $item_data['product'];
            $quantity = $item_data['quantity'];
            // Insertar detalle
            $stmt_detalle->bind_param('iiid', $pedido_id, $product['id'], $quantity, $product['precio']);
            $stmt_detalle->execute();
            // Actualizar stock
            $stmt_stock->bind_param('ii', $quantity, $product['id']);
            $stmt_stock->execute();
        }
        $stmt_detalle->close();
        $stmt_stock->close();

        // 3. Si todo va bien, confirmar la transacción
        $db->commit();

        // 4. Limpiar el carrito y redirigir
        unset($_SESSION['carrito']);
        $_SESSION['order_id'] = $pedido_id; // Guardar ID para la página de gracias
        header('Location: gracias.php');
        exit;

    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
}

// --- Lógica para mostrar la página (GET) ---
// Obtener datos del carrito para mostrar el resumen
$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['carrito'])) {
    $product_ids = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    $stmt = $db->prepare("SELECT id, nombre, precio, imagen FROM productos WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($product = $result->fetch_assoc()) {
        $quantity = $_SESSION['carrito'][$product['id']];
        $cart_items[] = ['product' => $product, 'quantity' => $quantity];
        $total_price += $product['precio'] * $quantity;
    }
}

include __DIR__ . '/../templates/partials/header.php';
?>

<h2>Finalizar Compra</h2>

<div class="checkout-wrapper" style="display: flex; gap: 30px;">
    <div class="order-summary" style="flex: 2;">
        <h3>Resumen del Pedido</h3>
        <table class="cart-table">
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><img src="uploads/products/<?php echo htmlspecialchars($item['product']['imagen']); ?>" alt=""></td>
                    <td><?php echo htmlspecialchars($item['product']['nombre']); ?></td>
                    <td><?php echo $item['quantity']; ?> x <?php echo number_format($item['product']['precio'], 2); ?> €</td>
                    <td><?php echo number_format($item['product']['precio'] * $item['quantity'], 2); ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="cart-total">
            <strong>Total a Pagar: <?php echo number_format($total_price, 2); ?> €</strong>
        </div>
    </div>
    <div class="customer-info" style="flex: 1;">
        <h3>Información de Envío</h3>
        <div class="form-container" style="margin:0; padding:20px;">
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
            <?php
            // Obtener email del usuario
            $stmt_user = $db->prepare("SELECT email FROM usuarios WHERE id = ?");
            $stmt_user->bind_param('i', $_SESSION['usuario_id']);
            $stmt_user->execute();
            $user_email = $stmt_user->get_result()->fetch_assoc()['email'];
            ?>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            <p><em>(La dirección de envío se gestionaría en un sistema real)</em></p>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <form action="checkout.php" method="POST">
                <button type="submit" class="btn btn-success" style="width: 100%; font-size: 1.2rem;">Realizar Pedido</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
