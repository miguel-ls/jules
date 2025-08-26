<?php
// src/Core/auth_check.php
// Este script se encarga de verificar si un usuario ha iniciado sesión.
// Debe ser incluido al principio de cada página que requiera autenticación.

// Asegurarnos de que el archivo de configuración (que inicia la sesión) esté incluido.
// Usamos __DIR__ para construir una ruta absoluta y evitar problemas con la inclusión de archivos.
require_once __DIR__ . '/../../config/database.php';

// Verificar si la variable de sesión del ID de usuario no está establecida.
if (!isset($_SESSION['id_usuario'])) {
    // Si el usuario no está logueado, redirigirlo a la página de login.
    // Es importante usar una URL absoluta para la redirección.
    // Nota: SITE_URL debe estar definido en config/database.php
    if (defined('SITE_URL')) {
        header("Location: " . SITE_URL . "/login.php");
    } else {
        // Fallback por si SITE_URL no está definido
        header("Location: login.php");
    }
    // Detener la ejecución del script para asegurar que no se muestre contenido de la página protegida.
    exit();
}

// Si el script llega a este punto, significa que el usuario está autenticado correctamente.
// Las páginas que incluyan este archivo pueden ahora acceder a las variables de sesión como:
// $_SESSION['id_usuario']
// $_SESSION['nombre_usuario']
// $_SESSION['rol']
?>
