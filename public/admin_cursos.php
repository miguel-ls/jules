<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$message = '';
$error = '';
$edit_mode = false;
$curso_data = ['id_curso' => '', 'nombre_curso' => '', 'descripcion' => '', 'precio_base' => ''];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_curso_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Curso eliminado correctamente.";
    } else {
        $error = "Error al eliminar el curso: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_curso']) ? (int)$_POST['id_curso'] : null;
    $nombre_curso = trim($_POST['nombre_curso']);
    $descripcion = trim($_POST['descripcion']);
    $precio_base = trim($_POST['precio_base']);

    if (empty($nombre_curso) || !is_numeric($precio_base)) {
        $error = "El nombre del curso es obligatorio y el precio debe ser un número.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_curso_actualizar(?, ?, ?, ?)");
            $stmt->bind_param("issd", $id, $nombre_curso, $descripcion, $precio_base);
            if ($stmt->execute()) {
                $message = "Curso actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_curso_crear(?, ?, ?)");
            $stmt->bind_param("ssd", $nombre_curso, $descripcion, $precio_base);
            if ($stmt->execute()) {
                $message = "Curso creado correctamente.";
            } else {
                $error = "Error al crear: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Acción de Editar (cargar datos)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_curso_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $curso_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
$cursos = [];
if ($result = $conn->query("CALL sp_curso_leer_todos()")) {
    if ($result->num_rows > 0) {
        $cursos = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de cursos: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Cursos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Cursos</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Curso' : 'Añadir Nuevo Curso' ?></div>
        <div class="card-body">
            <form action="admin_cursos.php" method="post">
                <input type="hidden" name="id_curso" value="<?= htmlspecialchars($curso_data['id_curso']) ?>">
                <div class="form-group">
                    <label for="nombre_curso">Nombre del Curso</label>
                    <input type="text" class="form-control" id="nombre_curso" name="nombre_curso" value="<?= htmlspecialchars($curso_data['nombre_curso']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($curso_data['descripcion']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="precio_base">Precio Base (S/.)</label>
                    <input type="number" step="0.01" class="form-control" id="precio_base" name="precio_base" value="<?= htmlspecialchars($curso_data['precio_base']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_cursos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Cursos</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio Base</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cursos)): ?>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?= htmlspecialchars($curso['id_curso']) ?></td>
                                <td><?= htmlspecialchars($curso['nombre_curso']) ?></td>
                                <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                                <td>S/. <?= htmlspecialchars(number_format($curso['precio_base'], 2)) ?></td>
                                <td>
                                    <a href="admin_cursos.php?action=edit&id=<?= $curso['id_curso'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_cursos.php?action=delete&id=<?= $curso['id_curso'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No hay cursos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
