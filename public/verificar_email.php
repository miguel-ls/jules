<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

$message = '';
$message_type = 'danger'; // 'danger' or 'success'

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'No se proporcionó un token de verificación.';
} else {
    $current_time = date('Y-m-d H:i:s');

    // Buscar el token en la base de datos
    $stmt = $db->prepare("SELECT usuario_id, fecha_expiracion FROM email_verifications WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $verification_data = $result->fetch_assoc();
    $stmt->close();

    if (!$verification_data) {
        $message = 'El token de verificación no es válido.';
    } elseif ($verification_data['fecha_expiracion'] < $current_time) {
        $message = 'El token de verificación ha expirado. Por favor, solicita uno nuevo.';
        // Aquí podría ir la lógica para reenviar un token
    } else {
        // El token es válido y no ha expirado
        $usuario_id = $verification_data['usuario_id'];

        // Iniciar una transacción para asegurar la atomicidad
        $db->begin_transaction();

        try {
            // 1. Actualizar el estado del usuario a verificado
            $stmt_update = $db->prepare("UPDATE usuarios SET email_verificado = 1 WHERE id = ?");
            $stmt_update->bind_param("i", $usuario_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Eliminar el token para que no se pueda reutilizar
            $stmt_delete = $db->prepare("DELETE FROM email_verifications WHERE usuario_id = ?");
            $stmt_delete->bind_param("i", $usuario_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Si todo fue bien, confirmar la transacción
            $db->commit();

            $message = '¡Tu correo electrónico ha sido verificado con éxito! Ahora puedes iniciar sesión.';
            $message_type = 'success';

        } catch (Exception $e) {
            // Si algo falla, revertir la transacción
            $db->rollback();
            $message = 'Ocurrió un error al verificar tu cuenta. Por favor, inténtalo de nuevo.';
        }
    }
}

include __DIR__ . '/../templates/partials/header.php';
?>

<div class="container" style="text-align: center;">
    <div class="alert alert-<?php echo $message_type; ?>">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
    <?php if ($message_type === 'success'): ?>
        <a href="login.php" class="btn">Ir a Iniciar Sesión</a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
