<?php

// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_contraseña_db');
define('DB_NAME', 'impresion3d_db');

// Configuración del Sitio
define('SITE_URL', 'http://localhost/impresion3d'); // Cambia esto a la URL de tu sitio

// Configuración de Email (para verificación y notificaciones)
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'tu_email@example.com');
define('MAIL_PASSWORD', 'tu_contraseña_email');
define('MAIL_FROM_ADDRESS', 'no-reply@example.com');
define('MAIL_FROM_NAME', 'Soporte Impresión 3D');

// Habilitar/deshabilitar errores de PHP para depuración
// En producción, esto debería estar en 0
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar la sesión en todas las páginas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
