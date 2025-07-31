<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// --- Lógica de filtrado y búsqueda ---
$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";
$params = [];
$types = '';

// Filtro de búsqueda por texto
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $sql .= " AND p.nombre LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= 's';
}

// Filtro por categoría
$category_filter = $_GET['categoria'] ?? '';
if (!empty($category_filter)) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

// Filtro por precio
$min_price = $_GET['min_price'] ?? '';
if (is_numeric($min_price)) {
    $sql .= " AND p.precio >= ?";
    $params[] = $min_price;
    $types .= 'd';
}
$max_price = $_GET['max_price'] ?? '';
if (is_numeric($max_price)) {
    $sql .= " AND p.precio <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

// Filtro por disponibilidad
$available = isset($_GET['available']);
if ($available) {
    $sql .= " AND p.stock > 0";
}

$sql .= " ORDER BY p.fecha_creacion DESC";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result();

// Obtener todas las categorías para el filtro
$categorias = $db->query("SELECT * FROM categorias ORDER BY nombre ASC");

include __DIR__ . '/../templates/partials/header.php';
?>
<style>
.catalog-wrapper {
    display: flex;
    gap: 30px;
}
.filter-sidebar {
    width: 250px;
    flex-shrink: 0;
}
.filter-sidebar .filter-group {
    margin-bottom: 20px;
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.filter-sidebar h3 {
    margin-top: 0;
    font-size: 1.2rem;
    color: #005A9C;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    flex-grow: 1;
}
.product-card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
}
.product-card img { max-width: 100%; height: 180px; object-fit: cover; }
.product-card h4 { font-size: 1.1rem; margin: 10px 0; }
.product-card .price { color: #007BFF; font-weight: bold; }
.stock-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #fff;
}
.stock-status.available { background-color: #28a745; }
.stock-status.out-of-stock { background-color: #dc3545; }
</style>

<h2>Catálogo de Productos</h2>
<div class="catalog-wrapper">
    <aside class="filter-sidebar">
        <h3>Filtros</h3>
        <form action="catalogo.php" method="GET">
            <div class="filter-group">
                <label for="search">Buscar por nombre</label>
                <input type="text" name="search" id="search" class="form-group input" value="<?php echo htmlspecialchars($search); ?>" style="width: calc(100% - 20px);">
            </div>
            <div class="filter-group">
                <label for="categoria">Categoría</label>
                <select name="categoria" id="categoria" class="form-group input" style="width: 100%;">
                    <option value="">Todas</option>
                    <?php while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Rango de Precio</label>
                <input type="number" name="min_price" placeholder="Mín" value="<?php echo htmlspecialchars($min_price); ?>" style="width: 40%;">
                -
                <input type="number" name="max_price" placeholder="Máx" value="<?php echo htmlspecialchars($max_price); ?>" style="width: 40%;">
            </div>
            <div class="filter-group">
                <label>
                    <input type="checkbox" name="available" <?php echo $available ? 'checked' : ''; ?>>
                    Mostrar solo disponibles
                </label>
            </div>
            <button type="submit" class="btn">Filtrar</button>
            <a href="catalogo.php" style="margin-left: 10px;">Limpiar</a>
        </form>
    </aside>

    <main class="product-grid">
        <?php if ($productos->num_rows > 0):
            while ($p = $productos->fetch_assoc()): ?>
            <div class="product-card">
                <?php if ($p['stock'] > 0): ?>
                    <span class="stock-status available">Disponible</span>
                <?php else: ?>
                    <span class="stock-status out-of-stock">Agotado</span>
                <?php endif; ?>

                <a href="producto_detalle.php?id=<?php echo $p['id']; ?>">
                    <img src="uploads/products/<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                </a>
                <h4><?php echo htmlspecialchars($p['nombre']); ?></h4>
                <p class="price"><?php echo number_format($p['precio'], 2); ?> €</p>
                <?php if ($p['stock'] > 0): ?>
                    <a href="carrito.php?action=add&id=<?php echo $p['id']; ?>" class="btn">Añadir al Carrito</a>
                <?php else: ?>
                    <button class="btn" disabled>Agotado</button>
                <?php endif; ?>
            </div>
            <?php endwhile;
        else: ?>
            <p>No se encontraron productos que coincidan con los filtros seleccionados.</p>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
