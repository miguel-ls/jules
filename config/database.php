<?php
// config/database.php

// --- Configuración de la Base de Datos ---
// Reemplaza estos valores con los de tu entorno de desarrollo/producción.
define('DB_HOST', '127.0.0.1'); // Usar 127.0.0.1 en lugar de localhost para evitar problemas de resolución de DNS
define('DB_USER', 'root'); // Usuario de la base de datos
define('DB_PASS', 'password'); // Contraseña de la base de datos (cámbiala si tienes una)
define('DB_NAME', 'natacion_db'); // Nombre de la base de datos para este proyecto

// --- Configuración del Sitio ---
define('SITE_URL', 'http://localhost/natacion'); // URL base del sitio web

/**
 * Crea y retorna una conexión a la base de datos usando MySQLi.
 *
 * @return mysqli|false El objeto de conexión mysqli en caso de éxito, o false en caso de error.
 */
function getDBConnection() {
    // Desactivar la notificación de errores de mysqli para manejarlo manualmente
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verificar si hubo un error en la conexión
    if ($conn->connect_error) {
        // En un entorno de producción, registrarías este error en un archivo de log
        // y mostrarías un mensaje de error genérico al usuario.
        error_log("Error de conexión a la base de datos: " . $conn->connect_error);
        // Para depuración, podemos mostrar el error. No hacer esto en producción.
        // die("Error de conexión: " . $conn->connect_error);
        return false;
    }

    // Establecer el juego de caracteres a UTF-8 para soportar tildes y caracteres especiales
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error al establecer el charset a utf8mb4: " . $conn->error);
        // die("Error al cargar el charset utf8mb4: " . $conn->error);
    }

    return $conn;
}

// Iniciar la sesión si no está ya iniciada.
// Esto es crucial para que la autenticación funcione en todas las páginas.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
