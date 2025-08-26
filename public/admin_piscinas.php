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
$piscina_data = ['id_piscina' => '', 'nombre' => '', 'id_tipo_piscina' => '', 'ubicacion' => ''];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_piscina_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Piscina eliminada correctamente.";
    } else {
        $error = "Error al eliminar la piscina: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_piscina']) ? (int)$_POST['id_piscina'] : null;
    $nombre = trim($_POST['nombre']);
    $id_tipo_piscina = (int)$_POST['id_tipo_piscina'];
    $ubicacion = trim($_POST['ubicacion']);

    if (empty($nombre) || empty($id_tipo_piscina)) {
        $error = "Nombre y Tipo de Piscina son campos obligatorios.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_piscina_actualizar(?, ?, ?, ?)");
            $stmt->bind_param("isis", $id, $nombre, $id_tipo_piscina, $ubicacion);
            if ($stmt->execute()) {
                $message = "Piscina actualizada correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_piscina_crear(?, ?, ?)");
            $stmt->bind_param("sis", $nombre, $id_tipo_piscina, $ubicacion);
            if ($stmt->execute()) {
                $message = "Piscina creada correctamente.";
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
    $stmt = $conn->prepare("CALL sp_piscina_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $piscina_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
// Cargar tipos de piscina para el dropdown
$tipos_piscina = [];
if ($result_tipos = $conn->query("CALL sp_tipo_piscina_leer_todos()")) {
    $tipos_piscina = $result_tipos->fetch_all(MYSQLI_ASSOC);
    while($conn->more_results() && $conn->next_result()) {}
}

// Cargar lista de piscinas
$piscinas = [];
if ($result_piscinas = $conn->query("CALL sp_piscina_leer_todas()")) {
    $piscinas = $result_piscinas->fetch_all(MYSQLI_ASSOC);
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de piscinas: " . $conn->error;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Piscinas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Piscinas</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Piscina' : 'Añadir Nueva Piscina' ?></div>
        <div class="card-body">
            <form action="admin_piscinas.php" method="post">
                <input type="hidden" name="id_piscina" value="<?= htmlspecialchars($piscina_data['id_piscina']) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nombre">Nombre de la Piscina</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($piscina_data['nombre']) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="id_tipo_piscina">Tipo de Piscina</label>
                        <select class="form-control" id="id_tipo_piscina" name="id_tipo_piscina" required>
                            <option value="">Seleccione un tipo</option>
                            <?php foreach ($tipos_piscina as $tipo): ?>
                                <option value="<?= $tipo['id_tipo_piscina'] ?>" <?= ($piscina_data['id_tipo_piscina'] == $tipo['id_tipo_piscina']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ubicacion">Ubicación / Sede</label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($piscina_data['ubicacion']) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_piscinas.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Piscinas</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Nombre</th><th>Tipo</th><th>Ubicación</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($piscinas)): ?>
                        <?php foreach ($piscinas as $piscina): ?>
                            <tr>
                                <td><?= htmlspecialchars($piscina['id_piscina']) ?></td>
                                <td><?= htmlspecialchars($piscina['nombre']) ?></td>
                                <td><?= htmlspecialchars($piscina['nombre_tipo_piscina']) ?></td>
                                <td><?= htmlspecialchars($piscina['ubicacion']) ?></td>
                                <td>
                                    <a href="admin_piscinas.php?action=edit&id=<?= $piscina['id_piscina'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_carriles.php?piscina_id=<?= $piscina['id_piscina'] ?>" class="btn btn-sm btn-info">Carriles</a>
                                    <a href="admin_piscinas.php?action=delete&id=<?= $piscina['id_piscina'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No hay piscinas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
