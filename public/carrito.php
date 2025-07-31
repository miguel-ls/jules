<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// Inicializar el carrito en la sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$quantity = $_GET['quantity'] ?? 1;

// --- Lógica del Carrito ---

// Añadir producto
if ($action === 'add' && $id) {
    $stmt = $db->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $current_quantity = $_SESSION['carrito'][$id] ?? 0;
        if (($current_quantity + $quantity) <= $product['stock']) {
            $_SESSION['carrito'][$id] = $current_quantity + $quantity;
            $_SESSION['message'] = 'Producto añadido al carrito.';
        } else {
            $_SESSION['message'] = 'No hay suficiente stock para la cantidad solicitada.';
        }
    }
    header('Location: carrito.php');
    exit;
}

// Actualizar cantidad
if ($action === 'update' && $id) {
    $quantity = $_POST['quantity'];
    if (isset($_SESSION['carrito'][$id]) && $quantity > 0) {
        $stmt = $db->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($quantity <= $product['stock']) {
            $_SESSION['carrito'][$id] = $quantity;
        } else {
            $_SESSION['message'] = 'La cantidad solicitada excede el stock disponible.';
        }
    }
    header('Location: carrito.php');
    exit;
}

// Eliminar producto
if ($action === 'remove' && $id) {
    unset($_SESSION['carrito'][$id]);
    $_SESSION['message'] = 'Producto eliminado del carrito.';
    header('Location: carrito.php');
    exit;
}

// Vaciar carrito
if ($action === 'clear') {
    $_SESSION['carrito'] = [];
    $_SESSION['message'] = 'El carrito ha sido vaciado.';
    header('Location: carrito.php');
    exit;
}

// --- Obtener datos de los productos en el carrito para mostrarlos ---
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
        $subtotal = $product['precio'] * $quantity;
        $total_price += $subtotal;
        $cart_items[] = [
            'id' => $product['id'],
            'nombre' => $product['nombre'],
            'precio' => $product['precio'],
            'imagen' => $product['imagen'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}


include __DIR__ . '/../templates/partials/header.php';
?>
<style>
.cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.cart-table th, .cart-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
.cart-table th { background-color: #f2f2f2; }
.cart-table img { width: 80px; }
.cart-total { text-align: right; margin-top: 20px; font-size: 1.5rem; }
.cart-actions { display: flex; justify-content: space-between; margin-top: 20px; }
</style>

<h2>Tu Carrito de Compras</h2>

<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}
?>

<?php if (empty($cart_items)): ?>
    <p>Tu carrito está vacío. <a href="catalogo.php">¡Empieza a comprar!</a></p>
<?php else: ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th></th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><img src="uploads/products/<?php echo htmlspecialchars($item['imagen']); ?>" alt=""></td>
                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                <td><?php echo number_format($item['precio'], 2); ?> €</td>
                <td>
                    <form action="carrito.php?action=update&id=<?php echo $item['id']; ?>" method="POST" style="display:inline-flex; align-items:center;">
                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; text-align:center;">
                        <button type="submit" class="btn btn-sm" style="margin-left: 5px;">Actualizar</button>
                    </form>
                </td>
                <td><?php echo number_format($item['subtotal'], 2); ?> €</td>
                <td><a href="carrito.php?action=remove&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">Quitar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="cart-total">
        <strong>Total: <?php echo number_format($total_price, 2); ?> €</strong>
    </div>

    <div class="cart-actions">
        <a href="carrito.php?action=clear" class="btn btn-danger">Vaciar Carrito</a>
        <a href="checkout.php" class="btn btn-success">Proceder al Pago</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
