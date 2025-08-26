<?php
// public/admin_formas_pago.php

require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$message = '';
$error = '';
$edit_mode = false;
$forma_pago_data = ['id_forma_pago' => '', 'nombre' => ''];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_forma_pago_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Forma de pago eliminada correctamente.";
    } else {
        $error = "Error al eliminar la forma de pago: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_forma_pago']) ? (int)$_POST['id_forma_pago'] : null;
    $nombre = trim($_POST['nombre']);

    if (empty($nombre)) {
        $error = "El nombre es un campo obligatorio.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_forma_pago_actualizar(?, ?)");
            $stmt->bind_param("is", $id, $nombre);
            if ($stmt->execute()) {
                $message = "Forma de pago actualizada correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_forma_pago_crear(?)");
            $stmt->bind_param("s", $nombre);
            if ($stmt->execute()) {
                $message = "Forma de pago creada correctamente.";
            } else {
                $error = "Error al crear: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Acción de Editar (cargar datos en el formulario)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_forma_pago_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $forma_pago_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA (OBTENER DATOS PARA MOSTRAR) ---
$formas_pago = [];
if ($result = $conn->query("CALL sp_forma_pago_leer_todas()")) {
    if ($result->num_rows > 0) {
        $formas_pago = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de formas de pago: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Formas de Pago</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .navbar { background-color: #333 !important; }
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,.75); }
        .navbar-dark .navbar-nav .nav-link:hover { color: #fff; }
        .navbar .logout { background-color: #d9534f; border-radius: 5px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Formas de Pago</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Forma de Pago' : 'Añadir Nueva Forma de Pago' ?></div>
        <div class="card-body">
            <form action="admin_formas_pago.php" method="post">
                <input type="hidden" name="id_forma_pago" value="<?= htmlspecialchars($forma_pago_data['id_forma_pago']) ?>">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($forma_pago_data['nombre']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?>
                    <a href="admin_formas_pago.php" class="btn btn-secondary">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de Registros -->
    <div class="card">
        <div class="card-header">Listado de Formas de Pago</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($formas_pago)): ?>
                        <?php foreach ($formas_pago as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['id_forma_pago']) ?></td>
                                <td><?= htmlspecialchars($item['nombre']) ?></td>
                                <td>
                                    <a href="admin_formas_pago.php?action=edit&id=<?= $item['id_forma_pago'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_formas_pago.php?action=delete&id=<?= $item['id_forma_pago'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No hay formas de pago registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
