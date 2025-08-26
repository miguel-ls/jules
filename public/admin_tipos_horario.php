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
$tipo_horario_data = ['id_tipo_horario' => '', 'nombre' => '', 'descripcion' => ''];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_tipo_horario_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Tipo de horario eliminado correctamente.";
    } else {
        $error = "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_tipo_horario']) ? (int)$_POST['id_tipo_horario'] : null;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre)) {
        $error = "El nombre es un campo obligatorio.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_tipo_horario_actualizar(?, ?, ?)");
            $stmt->bind_param("iss", $id, $nombre, $descripcion);
            if ($stmt->execute()) {
                $message = "Tipo de horario actualizado.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_tipo_horario_crear(?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);
            if ($stmt->execute()) {
                $message = "Tipo de horario creado.";
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
    $stmt = $conn->prepare("CALL sp_tipo_horario_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $tipo_horario_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
$tipos_horario = [];
if ($result = $conn->query("CALL sp_tipo_horario_leer_todos()")) {
    if ($result->num_rows > 0) {
        $tipos_horario = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Tipos de Horario</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Tipos de Horario</h2>
    <p>Define patrones de días para las clases (ej. Lunes, Miércoles y Viernes).</p>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Tipo de Horario' : 'Añadir Nuevo Tipo' ?></div>
        <div class="card-body">
            <form action="admin_tipos_horario.php" method="post">
                <input type="hidden" name="id_tipo_horario" value="<?= htmlspecialchars($tipo_horario_data['id_tipo_horario']) ?>">
                <div class="form-group">
                    <label for="nombre">Nombre (ej. Lunes, Miércoles y Viernes)</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($tipo_horario_data['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción Corta (ej. L-M-V)</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" value="<?= htmlspecialchars($tipo_horario_data['descripcion']) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_tipos_horario.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Tipos de Horario</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (!empty($tipos_horario)): ?>
                        <?php foreach ($tipos_horario as $tipo): ?>
                            <tr>
                                <td><?= htmlspecialchars($tipo['id_tipo_horario']) ?></td>
                                <td><?= htmlspecialchars($tipo['nombre']) ?></td>
                                <td><?= htmlspecialchars($tipo['descripcion']) ?></td>
                                <td>
                                    <a href="admin_tipos_horario.php?action=edit&id=<?= $tipo['id_tipo_horario'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_tipos_horario.php?action=delete&id=<?= $tipo['id_tipo_horario'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No hay tipos de horario registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
