<?php
// Iniciar sesión para el CAPTCHA
require_once __DIR__ . '/../config/config.php';

// Generar CAPTCHA
$captcha_text = substr(bin2hex(random_bytes(3)), 0, 5);
$_SESSION['captcha'] = $captcha_text;

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $captcha_input = trim($_POST['captcha'] ?? '');

    if (empty($nombre)) $errors[] = 'El nombre es obligatorio.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo electrónico no es válido.';
    if (empty($asunto)) $errors[] = 'El asunto es obligatorio.';
    if (empty($mensaje)) $errors[] = 'El mensaje es obligatorio.';
    if (empty($captcha_input) || strtolower($captcha_input) !== strtolower($_SESSION['captcha'])) {
        $errors[] = 'El código CAPTCHA es incorrecto.';
    }

    if (empty($errors)) {
        // Simulación de envío de correo
        // En un proyecto real, aquí usarías una librería como PHPMailer
        // mail('admin@example.com', $asunto, $mensaje, "From: $email");

        $success_message = '¡Gracias por tu mensaje! Nos pondremos en contacto contigo pronto.';
    }
}

include __DIR__ . '/../templates/partials/header.php';
?>
<style>
.contact-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
}
.contact-form-container, .contact-info-map {
    flex: 1;
    min-width: 300px;
}
.map-container {
    position: relative;
    padding-bottom: 75%; /* Proporción 4:3 */
    height: 0;
    overflow: hidden;
    border: 1px solid #ddd;
}
.map-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.captcha-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}
.captcha-code {
    background-color: #f0f0f0;
    padding: 10px 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-weight: bold;
    letter-spacing: 2px;
    text-decoration: line-through;
    color: #005A9C;
}
</style>

<h2>Contáctanos</h2>

<div class="contact-wrapper">
    <div class="contact-form-container">
        <h3>Envíanos un Mensaje</h3>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="contacto.php" method="POST" class="form-container" style="margin: 0; padding:0; box-shadow: none;">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="asunto">Asunto</label>
                    <input type="text" name="asunto" id="asunto" value="<?php echo htmlspecialchars($asunto ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mensaje">Mensaje</label>
                    <textarea name="mensaje" id="mensaje" rows="5" required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="captcha">Introduce el código</label>
                    <div class="captcha-container">
                        <span class="captcha-code"><?php echo htmlspecialchars($_SESSION['captcha']); ?></span>
                        <input type="text" name="captcha" id="captcha" required>
                    </div>
                </div>
                <button type="submit" class="btn">Enviar Mensaje</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="contact-info-map">
        <h3>Nuestra Ubicación</h3>
        <div class="map-container">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3901.939484393963!2d-77.03012898518728!3d-12.04637489146903!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c8b5d8a2c2e9%3A0x4a0b3e6b5e1e1e1e!2sPlaza%20Mayor%20de%20Lima!5e0!3m2!1ses!2spe!4v1617909289123!5m2!1ses!2spe"
                width="600"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>
        <p style="margin-top: 20px;"><strong>Dirección:</strong> Plaza Mayor, Lima, Perú (Ubicación de ejemplo)</p>
        <p><strong>Email:</strong> contacto@impresion3d.com</p>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
