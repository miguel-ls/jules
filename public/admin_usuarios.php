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
$user_data = ['id_usuario' => '', 'nombre_usuario' => '', 'email' => '', 'nombre_completo' => '', 'rol' => 'Asistente'];

// --- LÓGICA DE CONTROLADOR ---

// Acción de Eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    if ($id_to_delete == $_SESSION['id_usuario']) {
        $error = "No puedes eliminar tu propio usuario.";
    } else {
        $stmt = $conn->prepare("CALL sp_usuario_eliminar(?)");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $message = "Usuario eliminado correctamente.";
        } else {
            $error = "Error al eliminar el usuario: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Acción de Crear o Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $rol = $_POST['rol'];
    $password = $_POST['password'];

    if (empty($nombre_usuario) || empty($email) || empty($rol)) {
        $error = "Nombre de usuario, email y rol son obligatorios.";
    } elseif (!$id && empty($password)) {
        $error = "La contraseña es obligatoria para nuevos usuarios.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_usuario_actualizar(?, ?, ?, ?)");
            $stmt->bind_param("isss", $id, $nombre_completo, $email, $rol);
            if ($stmt->execute()) {
                $message = "Usuario actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
            $stmt->close();

            // Si se proporcionó una nueva contraseña, actualizarla
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("CALL sp_usuario_actualizar_password(?, ?)");
                $stmt_pass->bind_param("is", $id, $password_hash);
                $stmt_pass->execute();
                $stmt_pass->close();
                $message .= " Contraseña actualizada.";
            }
        } else { // Crear
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("CALL sp_usuario_crear(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre_usuario, $password_hash, $email, $nombre_completo, $rol);
            if ($stmt->execute()) {
                $message = "Usuario creado correctamente.";
            } else {
                $error = "Error al crear usuario: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Acción de Editar (cargar datos)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_usuario_leer_por_id(?)");
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- LÓGICA DE VISTA ---
$users = [];
if ($result = $conn->query("CALL sp_usuario_leer_todos()")) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
while($conn->more_results() && $conn->next_result()) {}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Usuarios</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Mantenimiento de Usuarios</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Usuario' : 'Añadir Nuevo Usuario' ?></div>
        <div class="card-body">
            <form action="admin_usuarios.php" method="post">
                <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($user_data['id_usuario']) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Nombre de Usuario</label><input type="text" class="form-control" name="nombre_usuario" value="<?= htmlspecialchars($user_data['nombre_usuario']) ?>" <?= $edit_mode ? 'readonly' : 'required' ?>></div>
                    <div class="form-group col-md-6"><label>Contraseña</label><input type="password" class="form-control" name="password" placeholder="<?= $edit_mode ? 'Dejar en blanco para no cambiar' : '' ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required></div>
                    <div class="form-group col-md-6"><label>Nombre Completo</label><input type="text" class="form-control" name="nombre_completo" value="<?= htmlspecialchars($user_data['nombre_completo']) ?>"></div>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" class="form-control">
                        <option value="Administrador" <?= $user_data['rol'] == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="Asistente" <?= $user_data['rol'] == 'Asistente' ? 'selected' : '' ?>>Asistente</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Crear Usuario' ?></button>
                <?php if ($edit_mode): ?><a href="admin_usuarios.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Usuarios</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Usuario</th><th>Nombre Completo</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id_usuario']) ?></td>
                            <td><?= htmlspecialchars($user['nombre_usuario']) ?></td>
                            <td><?= htmlspecialchars($user['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['rol']) ?></td>
                            <td>
                                <a href="?action=edit&id=<?= $user['id_usuario'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php if ($user['id_usuario'] != $_SESSION['id_usuario']): // No permitir auto-eliminación ?>
                                <a href="?action=delete&id=<?= $user['id_usuario'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
