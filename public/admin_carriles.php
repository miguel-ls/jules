<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

// Validar que se ha proporcionado un ID de piscina
if (!isset($_GET['piscina_id']) || !is_numeric($_GET['piscina_id'])) {
    header("Location: admin_piscinas.php");
    exit('ID de piscina no válido.');
}
$piscina_id = (int)$_GET['piscina_id'];

$conn = getDBConnection();
$message = '';
$error = '';
$edit_mode = false;
$carril_data = ['id_carril' => '', 'numero_carril' => '', 'capacidad_maxima' => ''];

// Cargar datos de la piscina para mostrar su nombre
$piscina_nombre = '';
$stmt_piscina = $conn->prepare("CALL sp_piscina_leer_por_id(?)");
$stmt_piscina->bind_param("i", $piscina_id);
if ($stmt_piscina->execute()) {
    $result = $stmt_piscina->get_result();
    if ($result->num_rows > 0) {
        $piscina = $result->fetch_assoc();
        $piscina_nombre = $piscina['nombre'];
    } else {
        header("Location: admin_piscinas.php");
        exit('Piscina no encontrada.');
    }
}
$stmt_piscina->close();
while($conn->more_results() && $conn->next_result()) {}

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_carril_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Carril eliminado correctamente.";
    } else {
        $error = "Error al eliminar el carril: " . $stmt->error;
    }
    $stmt->close();
    while($conn->more_results() && $conn->next_result()) {}
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_piscina_post = (int)$_POST['id_piscina'];
    // Asegurarse de que el POST corresponde a la piscina actual
    if ($id_piscina_post === $piscina_id) {
        $id_carril = !empty($_POST['id_carril']) ? (int)$_POST['id_carril'] : null;
        $numero_carril = trim($_POST['numero_carril']);
        $capacidad_maxima = trim($_POST['capacidad_maxima']);

        if (!is_numeric($numero_carril) || !is_numeric($capacidad_maxima)) {
            $error = "Número de carril y capacidad deben ser números.";
        } else {
            if ($id_carril) { // Actualizar
                $stmt = $conn->prepare("CALL sp_carril_actualizar(?, ?, ?)");
                $stmt->bind_param("iii", $id_carril, $numero_carril, $capacidad_maxima);
                if ($stmt->execute()) {
                    $message = "Carril actualizado.";
                } else { $error = "Error: " . $stmt->error; }
            } else { // Crear
                $stmt = $conn->prepare("CALL sp_carril_crear(?, ?, ?)");
                $stmt->bind_param("iii", $piscina_id, $numero_carril, $capacidad_maxima);
                if ($stmt->execute()) {
                    $message = "Carril creado.";
                } else { $error = "Error: " . $stmt->error; }
            }
            $stmt->close();
            while($conn->more_results() && $conn->next_result()) {}
        }
    }
}

// Acción de Editar (cargar datos)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_carril_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $carril_data = $result->fetch_assoc();
    }
    $stmt->close();
    while($conn->more_results() && $conn->next_result()) {}
}

// --- LÓGICA DE VISTA ---
$carriles = [];
$stmt_carriles = $conn->prepare("CALL sp_carril_leer_por_piscina(?)");
$stmt_carriles->bind_param("i", $piscina_id);
if ($stmt_carriles->execute()) {
    $result_carriles = $stmt_carriles->get_result();
    if ($result_carriles->num_rows > 0) {
        $carriles = $result_carriles->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $error = "Error al cargar la lista de carriles: " . $conn->error;
}
$stmt_carriles->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Carriles</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Carriles de la Piscina: "<?= htmlspecialchars($piscina_nombre) ?>"</h2>
        <a href="admin_piscinas.php" class="btn btn-secondary">Volver a Piscinas</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success mt-3"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card my-4">
                <div class="card-header"><?= $edit_mode ? 'Editar Carril' : 'Añadir Nuevo Carril' ?></div>
                <div class="card-body">
                    <form action="admin_carriles.php?piscina_id=<?= $piscina_id ?>" method="post">
                        <input type="hidden" name="id_piscina" value="<?= $piscina_id ?>">
                        <input type="hidden" name="id_carril" value="<?= htmlspecialchars($carril_data['id_carril']) ?>">
                        <div class="form-group">
                            <label for="numero_carril">Número de Carril</label>
                            <input type="number" class="form-control" name="numero_carril" value="<?= htmlspecialchars($carril_data['numero_carril']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="capacidad_maxima">Capacidad Máxima</label>
                            <input type="number" class="form-control" name="capacidad_maxima" value="<?= htmlspecialchars($carril_data['capacidad_maxima']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                        <?php if ($edit_mode): ?><a href="admin_carriles.php?piscina_id=<?= $piscina_id ?>" class="btn btn-secondary">Cancelar</a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card my-4">
                <div class="card-header">Listado de Carriles</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Número</th><th>Capacidad</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php if (!empty($carriles)): ?>
                                <?php foreach ($carriles as $carril): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($carril['id_carril']) ?></td>
                                        <td><?= htmlspecialchars($carril['numero_carril']) ?></td>
                                        <td><?= htmlspecialchars($carril['capacidad_maxima']) ?></td>
                                        <td>
                                            <a href="admin_carriles.php?piscina_id=<?= $piscina_id ?>&action=edit&id=<?= $carril['id_carril'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <a href="admin_carriles.php?piscina_id=<?= $piscina_id ?>&action=delete&id=<?= $carril['id_carril'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No hay carriles registrados para esta piscina.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
