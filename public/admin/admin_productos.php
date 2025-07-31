<?php
require_once __DIR__ . '/../../src/lib/database.php';
$db = Database::getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// --- Manejo de acciones POST (Crear y Actualizar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    $categoria_id = $_POST['categoria_id'];
    $action = $_POST['action'];
    $errors = [];

    // Validación
    if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
    if (!is_numeric($precio) || $precio < 0) $errors[] = 'El precio no es válido.';
    if (!is_numeric($stock) || $stock < 0) $errors[] = 'El stock no es válido.';
    if (empty($categoria_id)) $errors[] = 'La categoría es obligatoria.';

    // Manejo de la subida de imagen
    $imagen_path = $_POST['current_imagen'] ?? ''; // Mantener imagen actual si no se sube una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/products/';
        $imagen_name = uniqid() . '-' . basename($_FILES['imagen']['name']);
        $target_file = $upload_dir . $imagen_name;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_file)) {
            // Si se sube una nueva imagen y existía una anterior, borrar la anterior
            if (!empty($imagen_path)) {
                unlink($upload_dir . $imagen_path);
            }
            $imagen_path = $imagen_name;
        } else {
            $errors[] = 'Error al subir la imagen.';
        }
    }

    if (empty($errors)) {
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssdiis', $nombre, $descripcion, $precio, $stock, $categoria_id, $imagen_path);
        } else { // update
            $stmt = $db->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, imagen=? WHERE id=?");
            $stmt->bind_param('ssdiisi', $nombre, $descripcion, $precio, $stock, $categoria_id, $imagen_path, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = 'Producto guardado con éxito.';
        } else {
            $_SESSION['message'] = 'Error al guardar el producto.';
        }
        header('Location: admin_productos.php');
        exit;
    } else {
        // Si hay errores, volver a mostrar el formulario
        $action = $id ? 'edit' : 'add';
    }
}

// --- Manejo de la acción de eliminar (GET) ---
if ($action === 'delete' && $id) {
    // Primero, obtener el nombre de la imagen para borrar el archivo
    $stmt = $db->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    // Ahora, eliminar el producto de la DB
    $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Si se borró de la DB, borrar el archivo de imagen
        if ($product && !empty($product['imagen'])) {
            $image_file = __DIR__ . '/../uploads/products/' . $product['imagen'];
            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }
        $_SESSION['message'] = 'Producto eliminado con éxito.';
    } else {
        $_SESSION['message'] = 'Error al eliminar el producto.';
    }
    header('Location: admin_productos.php');
    exit;
}

include __DIR__ . '/../../templates/partials/admin_header.php';
?>

<h2>Gestionar Productos</h2>

<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}
?>

<?php if ($action === 'list'): ?>
    <a href="?action=add" class="btn btn-success" style="margin-bottom: 20px;">Añadir Nuevo Producto</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $productos = $db->query("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
            if ($productos->num_rows > 0):
                while ($p = $productos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><img src="../uploads/products/<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" width="50"></td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($p['categoria_nombre']); ?></td>
                    <td><?php echo number_format($p['precio'], 2); ?> €</td>
                    <td><?php echo $p['stock']; ?></td>
                    <td>
                        <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm">Editar</a>
                        <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?');">Eliminar</a>
                    </td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="7" style="text-align:center;">No hay productos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <?php
    $product = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
    }
    ?>
    <h3><?php echo $action === 'add' ? 'Añadir Producto' : 'Editar Producto'; ?></h3>
    <form class="form-container" action="admin_productos.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $id ? 'update' : 'create'; ?>">
        <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>

        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($product['nombre'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion"><?php echo htmlspecialchars($product['descripcion'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="precio">Precio</label>
            <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($product['precio'] ?? '0.00'); ?>">
        </div>
        <div class="form-group">
            <label for="stock">Stock</label>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock'] ?? '0'); ?>">
        </div>
        <div class="form-group">
            <label for="categoria_id">Categoría</label>
            <select name="categoria_id">
                <option value="">Selecciona una categoría</option>
                <?php
                $categorias = $db->query("SELECT id, nombre FROM categorias");
                while ($cat = $categorias->fetch_assoc()) {
                    $selected = ($product && $product['categoria_id'] == $cat['id']) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' {$selected}>" . htmlspecialchars($cat['nombre']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="imagen">Imagen</label>
            <input type="file" name="imagen">
            <?php if ($action === 'edit' && !empty($product['imagen'])): ?>
                <p>Imagen actual: <img src="../uploads/products/<?php echo htmlspecialchars($product['imagen']); ?>" width="100"></p>
                <input type="hidden" name="current_imagen" value="<?php echo htmlspecialchars($product['imagen']); ?>">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn"><?php echo $id ? 'Actualizar' : 'Crear'; ?></button>
        <a href="admin_productos.php" style="margin-left: 10px;">Cancelar</a>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
