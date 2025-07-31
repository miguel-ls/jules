<?php
require_once __DIR__ . '/../../src/lib/database.php';
$db = Database::getConnection();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// --- Manejo de acciones POST (Actualizar rol) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $id = $_POST['id'];
    $rol = $_POST['rol'];

    // Validar que el rol sea uno de los permitidos
    if (in_array($rol, ['cliente', 'admin'])) {
        // Evitar que un admin se quite a sí mismo el rol de admin si es el único
        if ($id == $_SESSION['usuario_id']) {
             $stmt_count = $db->prepare("SELECT COUNT(*) as admin_count FROM usuarios WHERE rol = 'admin'");
             $stmt_count->execute();
             $admin_count = $stmt_count->get_result()->fetch_assoc()['admin_count'];
             if ($admin_count <= 1 && $rol !== 'admin') {
                 $_SESSION['message'] = 'Error: No puedes eliminar al único administrador.';
                 header('Location: admin_usuarios.php');
                 exit;
             }
        }

        $stmt = $db->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $stmt->bind_param('si', $rol, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Rol de usuario actualizado con éxito.';
        } else {
            $_SESSION['message'] = 'Error al actualizar el rol.';
        }
    } else {
        $_SESSION['message'] = 'Rol no válido.';
    }
    header('Location: admin_usuarios.php');
    exit;
}

// --- Manejo de la acción de eliminar (GET) ---
if ($action === 'delete' && $id) {
    // Un admin no puede eliminarse a sí mismo
    if ($id == $_SESSION['usuario_id']) {
        $_SESSION['message'] = 'Error: No puedes eliminar tu propia cuenta desde el panel.';
    } else {
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Usuario eliminado con éxito.';
        } else {
            $_SESSION['message'] = 'Error al eliminar el usuario.';
        }
    }
    header('Location: admin_usuarios.php');
    exit;
}

include __DIR__ . '/../../templates/partials/admin_header.php';
?>

<h2>Gestionar Usuarios</h2>

<?php
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}
?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Verificado</th>
            <th>Fecha Registro</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $usuarios = $db->query("SELECT * FROM usuarios ORDER BY id DESC");
        if ($usuarios->num_rows > 0):
            while ($user = $usuarios->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <form action="admin_usuarios.php" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <select name="rol" onchange="this.form.submit()">
                            <option value="cliente" <?php echo $user['rol'] === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                            <option value="admin" <?php echo $user['rol'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </form>
                </td>
                <td><?php echo $user['email_verificado'] ? 'Sí' : 'No'; ?></td>
                <td><?php echo $user['fecha_registro']; ?></td>
                <td>
                    <?php if ($user['id'] != $_SESSION['usuario_id']): // No permitir auto-eliminación ?>
                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción es irreversible.');">Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile;
        else: ?>
            <tr><td colspan="7" style="text-align:center;">No hay usuarios registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../../templates/partials/admin_footer.php'; ?>
