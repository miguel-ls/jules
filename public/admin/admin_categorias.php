<?php
require_once __DIR__ . '/../../src/lib/database.php';
$db = Database::getConnection();

// Variables para mensajes y datos del formulario
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$nombre = '';
$descripcion = '';
$errors = [];
$message = '';

// Lógica para manejar las acciones POST (crear y actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $action = $_POST['action'];

    if (empty($nombre)) {
        $errors[] = 'El nombre de la categoría es obligatorio.';
    }

    if (empty($errors)) {
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
            $stmt->bind_param('ss', $nombre, $descripcion);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Categoría creada con éxito.';
            } else {
                $_SESSION['message'] = 'Error al crear la categoría.';
            }
        } elseif ($action === 'update') {
            $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
            $stmt->bind_param('ssi', $nombre, $descripcion, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Categoría actualizada con éxito.';
            } else {
                $_SESSION['message'] = 'Error al actualizar la categoría.';
            }
        }
        header('Location: admin_categorias.php');
        exit;
    } else {
        // Si hay errores, la acción se revierte a 'add' o 'edit' para mostrar el formulario con errores
        $action = empty($id) ? 'add' : 'edit';
    }
}

// Lógica para la acción de eliminar (GET)
if ($action === 'delete' && $id) {
    // Se podría añadir una comprobación de productos asociados antes de borrar
    $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Categoría eliminada con éxito.';
    } else {
        $_SESSION['message'] = 'Error al eliminar la categoría. Puede que tenga productos asociados.';
    }
    header('Location: admin_categorias.php');
    exit;
}

// Obtener datos para editar
if ($action === 'edit' && $id && empty($errors)) {
    $stmt = $db->prepare("SELECT nombre, descripcion FROM categorias WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    if ($category) {
        $nombre = $category['nombre'];
        $descripcion = $category['descripcion'];
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

include __DIR__ . '/../../templates/partials/admin_header.php';
?>

<h2>Gestionar Categorías</h2>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <a href="?action=add" class="btn btn-success" style="margin-bottom: 20px;">Añadir Nueva Categoría</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $db->query("SELECT * FROM categorias ORDER BY id DESC");
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                <td>
                    <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm">Editar</a>
                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?');">Eliminar</a>
                </td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="4" style="text-align:center;">No hay categorías registradas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <h3><?php echo $action === 'add' ? 'Añadir Nueva Categoría' : 'Editar Categoría'; ?></h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="form-container" action="admin_categorias.php" method="POST">
        <input type="hidden" name="action" value="<?php echo $id ? 'update' : 'create'; ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($descripcion); ?></textarea>
        </div>
        <button type="submit" class="btn"><?php echo $id ? 'Actualizar' : 'Crear'; ?></button>
        <a href="admin_categorias.php" style="margin-left: 10px;">Cancelar</a>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
