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
$profesor_data = [
    'id_profesor' => '', 'nombres' => '', 'apellidos' => '', 'documento_identidad' => '',
    'fecha_nacimiento' => '', 'telefono' => '', 'email' => '', 'fecha_contratacion' => '', 'estado' => 'Activo'
];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_profesor_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Profesor eliminado correctamente.";
    } else {
        $error = "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_profesor']) ? (int)$_POST['id_profesor'] : null;
    // Recoger todos los datos del formulario
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $documento_identidad = trim($_POST['documento_identidad']);
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $fecha_contratacion = !empty($_POST['fecha_contratacion']) ? $_POST['fecha_contratacion'] : null;
    $estado = $_POST['estado'];

    if (empty($nombres) || empty($apellidos) || empty($documento_identidad) || empty($email) || empty($fecha_contratacion)) {
        $error = "Los campos Nombres, Apellidos, Documento, Email y Fecha de Contratación son obligatorios.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_profesor_actualizar(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $id, $nombres, $apellidos, $documento_identidad, $fecha_nacimiento, $telefono, $email, $fecha_contratacion, $estado);
            if ($stmt->execute()) {
                $message = "Profesor actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_profesor_crear(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nombres, $apellidos, $documento_identidad, $fecha_nacimiento, $telefono, $email, $fecha_contratacion);
            if ($stmt->execute()) {
                $message = "Profesor creado correctamente.";
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
    $stmt = $conn->prepare("CALL sp_profesor_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profesor_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
$profesores = [];
if ($result = $conn->query("CALL sp_profesor_leer_todos()")) {
    if ($result->num_rows > 0) {
        $profesores = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de profesores: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Profesores</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Profesores</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Profesor' : 'Añadir Nuevo Profesor' ?></div>
        <div class="card-body">
            <form action="admin_profesores.php" method="post">
                <input type="hidden" name="id_profesor" value="<?= htmlspecialchars($profesor_data['id_profesor']) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nombres">Nombres</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" value="<?= htmlspecialchars($profesor_data['nombres']) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= htmlspecialchars($profesor_data['apellidos']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="documento_identidad">Documento de Identidad</label>
                        <input type="text" class="form-control" id="documento_identidad" name="documento_identidad" value="<?= htmlspecialchars($profesor_data['documento_identidad']) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($profesor_data['fecha_nacimiento']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="telefono">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($profesor_data['telefono']) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profesor_data['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                     <div class="form-group col-md-6">
                        <label for="fecha_contratacion">Fecha de Contratación</label>
                        <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" value="<?= htmlspecialchars($profesor_data['fecha_contratacion']) ?>" required>
                    </div>
                    <?php if ($edit_mode): ?>
                    <div class="form-group col-md-6">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control">
                            <option value="Activo" <?= $profesor_data['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $profesor_data['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_profesores.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Profesores</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($profesores)): ?>
                        <?php foreach ($profesores as $profesor): ?>
                            <tr>
                                <td><?= htmlspecialchars($profesor['id_profesor']) ?></td>
                                <td><?= htmlspecialchars($profesor['nombres'] . ' ' . $profesor['apellidos']) ?></td>
                                <td><?= htmlspecialchars($profesor['email']) ?></td>
                                <td><?= htmlspecialchars($profesor['telefono']) ?></td>
                                <td><span class="badge badge-<?= $profesor['estado'] == 'Activo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($profesor['estado']) ?></span></td>
                                <td>
                                    <a href="admin_profesores.php?action=edit&id=<?= $profesor['id_profesor'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_profesores.php?action=delete&id=<?= $profesor['id_profesor'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No hay profesores registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
