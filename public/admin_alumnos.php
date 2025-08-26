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
// Array para inicializar los datos del alumno
$alumno_data = [
    'id_alumno' => '', 'nombres' => '', 'apellidos' => '', 'documento_identidad' => '',
    'fecha_nacimiento' => '', 'grupo_sanguineo' => '', 'direccion' => '', 'email' => '',
    'telefono' => '', 'nombre_padre_madre' => '', 'contacto_emergencia_nombre' => '',
    'contacto_emergencia_telefono' => ''
];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_alumno_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Alumno eliminado correctamente.";
    } else {
        $error = "Error al eliminar el alumno. Es posible que tenga matrículas asociadas.";
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_alumno']) ? (int)$_POST['id_alumno'] : null;

    // Recopilar todos los datos del formulario
    $params = [
        'nombres' => $_POST['nombres'],
        'apellidos' => $_POST['apellidos'],
        'documento_identidad' => $_POST['documento_identidad'],
        'fecha_nacimiento' => $_POST['fecha_nacimiento'],
        'grupo_sanguineo' => $_POST['grupo_sanguineo'],
        'direccion' => $_POST['direccion'],
        'email' => $_POST['email'],
        'telefono' => $_POST['telefono'],
        'nombre_padre_madre' => $_POST['nombre_padre_madre'],
        'contacto_emergencia_nombre' => $_POST['contacto_emergencia_nombre'],
        'contacto_emergencia_telefono' => $_POST['contacto_emergencia_telefono']
    ];

    if (empty($params['nombres']) || empty($params['apellidos']) || empty($params['fecha_nacimiento'])) {
        $error = "Nombres, Apellidos y Fecha de Nacimiento son campos obligatorios.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_alumno_actualizar(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssss", $id, ...array_values($params));
            if ($stmt->execute()) {
                $message = "Alumno actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_alumno_crear(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", ...array_values($params));
            if ($stmt->execute()) {
                $message = "Alumno creado correctamente.";
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
    $stmt = $conn->prepare("CALL sp_alumno_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $alumno_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
$alumnos = [];
if ($result = $conn->query("CALL sp_alumno_leer_todos()")) {
    if ($result->num_rows > 0) {
        $alumnos = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de alumnos: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Alumnos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Alumnos</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Alumno' : 'Añadir Nuevo Alumno' ?></div>
        <div class="card-body">
            <form action="admin_alumnos.php" method="post">
                <input type="hidden" name="id_alumno" value="<?= htmlspecialchars($alumno_data['id_alumno']) ?>">

                <h5>Datos Personales</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="nombres">Nombres</label><input type="text" class="form-control" name="nombres" value="<?= htmlspecialchars($alumno_data['nombres']) ?>" required></div>
                    <div class="form-group col-md-6"><label for="apellidos">Apellidos</label><input type="text" class="form-control" name="apellidos" value="<?= htmlspecialchars($alumno_data['apellidos']) ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label for="documento_identidad">Documento Identidad</label><input type="text" class="form-control" name="documento_identidad" value="<?= htmlspecialchars($alumno_data['documento_identidad']) ?>"></div>
                    <div class="form-group col-md-4"><label for="fecha_nacimiento">Fecha de Nacimiento</label><input type="date" class="form-control" name="fecha_nacimiento" value="<?= htmlspecialchars($alumno_data['fecha_nacimiento']) ?>" required></div>
                    <div class="form-group col-md-4"><label for="grupo_sanguineo">Grupo Sanguíneo</label><input type="text" class="form-control" name="grupo_sanguineo" value="<?= htmlspecialchars($alumno_data['grupo_sanguineo']) ?>"></div>
                </div>

                <h5>Datos de Contacto</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-8"><label for="direccion">Dirección</label><input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($alumno_data['direccion']) ?>"></div>
                    <div class="form-group col-md-4"><label for="telefono">Teléfono</label><input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($alumno_data['telefono']) ?>"></div>
                </div>
                <div class="form-group"><label for="email">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($alumno_data['email']) ?>"></div>

                <h5>Datos de Apoderado y Emergencia</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="nombre_padre_madre">Nombre del Padre/Madre</label><input type="text" class="form-control" name="nombre_padre_madre" value="<?= htmlspecialchars($alumno_data['nombre_padre_madre']) ?>"></div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-6"><label for="contacto_emergencia_nombre">Contacto de Emergencia</label><input type="text" class="form-control" name="contacto_emergencia_nombre" value="<?= htmlspecialchars($alumno_data['contacto_emergencia_nombre']) ?>"></div>
                    <div class="form-group col-md-6"><label for="contacto_emergencia_telefono">Teléfono de Emergencia</label><input type="text" class="form-control" name="contacto_emergencia_telefono" value="<?= htmlspecialchars($alumno_data['contacto_emergencia_telefono']) ?>"></div>
                </div>

                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_alumnos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Alumnos</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nombre Completo</th><th>Documento</th><th>Teléfono</th><th>Email</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alumnos)): ?>
                            <?php foreach ($alumnos as $alumno): ?>
                                <tr>
                                    <td><?= htmlspecialchars($alumno['id_alumno']) ?></td>
                                    <td><?= htmlspecialchars($alumno['nombres'] . ' ' . $alumno['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($alumno['documento_identidad']) ?></td>
                                    <td><?= htmlspecialchars($alumno['telefono']) ?></td>
                                    <td><?= htmlspecialchars($alumno['email']) ?></td>
                                    <td>
                                        <a href="admin_alumnos.php?action=edit&id=<?= $alumno['id_alumno'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <a href="admin_alumnos.php?action=delete&id=<?= $alumno['id_alumno'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No hay alumnos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
