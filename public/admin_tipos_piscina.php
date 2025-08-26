<?php
// public/admin_tipos_piscina.php

require_once __DIR__ . '/../src/Core/auth_check.php';
// Opcional: Restringir solo a administradores
if ($_SESSION['rol'] !== 'Administrador') {
    // Podríamos redirigir a una página de "acceso denegado" o simplemente al dashboard.
    header("Location: dashboard.php");
    exit('Acceso denegado. Se requiere rol de Administrador.');
}

$conn = getDBConnection();
$message = '';
$error = '';
$edit_mode = false;
$tipo_piscina_data = ['id_tipo_piscina' => '', 'nombre' => '', 'descripcion' => ''];

// --- LÓGICA DE CONTROLADOR (MANEJO DE ACCIONES) ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_tipo_piscina_eliminar(?)");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Tipo de piscina eliminado correctamente.";
    } else {
        $error = "Error al eliminar el tipo de piscina: " . $stmt->error;
    }
    $stmt->close();
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_tipo_piscina']) ? (int)$_POST['id_tipo_piscina'] : null;
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre)) {
        $error = "El nombre es un campo obligatorio.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_tipo_piscina_actualizar(?, ?, ?)");
            $stmt->bind_param("iss", $id, $nombre, $descripcion);
            if ($stmt->execute()) {
                $message = "Tipo de piscina actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_tipo_piscina_crear(?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);
            if ($stmt->execute()) {
                $message = "Tipo de piscina creado correctamente.";
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
    $stmt = $conn->prepare("CALL sp_tipo_piscina_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $tipo_piscina_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA (OBTENER DATOS PARA MOSTRAR) ---
$tipos_piscina = [];
if ($result = $conn->query("CALL sp_tipo_piscina_leer_todos()")) {
    if ($result->num_rows > 0) {
        $tipos_piscina = $result->fetch_all(MYSQLI_ASSOC);
    }
    // Liberar el conjunto de resultados
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de tipos de piscina: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Tipos de Piscina</title>
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
    <h2>Mantenimiento de Tipos de Piscina</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulario para Añadir/Editar -->
    <div class="card mb-4">
        <div class="card-header">
            <?= $edit_mode ? 'Editar Tipo de Piscina' : 'Añadir Nuevo Tipo de Piscina' ?>
        </div>
        <div class="card-body">
            <form action="admin_tipos_piscina.php" method="post">
                <input type="hidden" name="id_tipo_piscina" value="<?= htmlspecialchars($tipo_piscina_data['id_tipo_piscina']) ?>">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($tipo_piscina_data['nombre']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($tipo_piscina_data['descripcion']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?>
                    <a href="admin_tipos_piscina.php" class="btn btn-secondary">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de Tipos de Piscina Existentes -->
    <div class="card">
        <div class="card-header">
            Listado de Tipos de Piscina
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tipos_piscina)): ?>
                        <?php foreach ($tipos_piscina as $tipo): ?>
                            <tr>
                                <td><?= htmlspecialchars($tipo['id_tipo_piscina']) ?></td>
                                <td><?= htmlspecialchars($tipo['nombre']) ?></td>
                                <td><?= htmlspecialchars($tipo['descripcion']) ?></td>
                                <td>
                                    <a href="admin_tipos_piscina.php?action=edit&id=<?= $tipo['id_tipo_piscina'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="admin_tipos_piscina.php?action=delete&id=<?= $tipo['id_tipo_piscina'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay tipos de piscina registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
