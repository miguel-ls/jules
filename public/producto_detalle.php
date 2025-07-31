<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: catalogo.php');
    exit;
}

$stmt = $db->prepare("
    SELECT p.*, c.nombre as categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

if (!$producto) {
    // Si no se encuentra el producto, redirigir al catálogo
    header('Location: catalogo.php');
    exit;
}

include __DIR__ . '/../templates/partials/header.php';
?>
<style>
.product-detail-wrapper {
    display: flex;
    gap: 40px;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.product-image-gallery {
    flex: 1;
    max-width: 50%;
}
.product-image-gallery img {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #ddd;
}
.product-info {
    flex: 1;
}
.product-info h2 {
    margin-top: 0;
    font-size: 2rem;
    color: #005A9C;
}
.product-info .category {
    font-style: italic;
    color: #666;
    margin-bottom: 20px;
}
.product-info .price {
    font-size: 1.8rem;
    color: #007BFF;
    font-weight: bold;
    margin-bottom: 20px;
}
.product-info .stock {
    font-size: 1.1rem;
    margin-bottom: 20px;
}
.stock.available { color: #28a745; }
.stock.out-of-stock { color: #dc3545; }

.add-to-cart-form {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 20px;
}
.add-to-cart-form input[type="number"] {
    width: 70px;
    padding: 10px;
    text-align: center;
}
</style>

<div class="product-detail-wrapper">
    <div class="product-image-gallery">
        <img src="uploads/products/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
    </div>
    <div class="product-info">
        <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
        <p class="category">Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>

        <p class="price"><?php echo number_format($producto['precio'], 2); ?> €</p>

        <div class="stock <?php echo $producto['stock'] > 0 ? 'available' : 'out-of-stock'; ?>">
            <strong>Disponibilidad:</strong> <?php echo $producto['stock'] > 0 ? 'En Stock ('.$producto['stock'].' unidades)' : 'Agotado'; ?>
        </div>

        <div class="description">
            <h3>Descripción del Producto</h3>
            <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
        </div>

        <?php if ($producto['stock'] > 0): ?>
            <form class="add-to-cart-form" action="carrito.php" method="GET">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                <div class="form-group">
                    <label for="quantity">Cantidad:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $producto['stock']; ?>" class="form-group input">
                </div>
                <button type="submit" class="btn">Añadir al Carrito</button>
            </form>
        <?php else: ?>
            <button class="btn" disabled>Producto Agotado</button>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
