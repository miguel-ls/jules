<?php

// Incluir el archivo de configuración una sola vez
require_once __DIR__ . '/../../config/config.php';

/**
 * Clase para manejar la conexión a la base de datos.
 * Utiliza el patrón Singleton para asegurar una única instancia de la conexión.
 */
class Database {

    /**
     * @var mysqli|null La instancia única de la conexión a la base de datos.
     */
    private static $connection = null;

    /**
     * Constructor privado para prevenir la instanciación directa.
     */
    private function __construct() {
    }

    /**
     * Método estático para obtener la instancia de la conexión a la base de datos.
     * Si la conexión no existe, la crea.
     *
     * @return mysqli La conexión a la base de datos.
     */
    public static function getConnection() {
        if (self::$connection === null) {
            // Crear una nueva conexión si no existe
            self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // Comprobar si hay errores de conexión
            if (self::$connection->connect_error) {
                // Terminar el script y mostrar el error si la conexión falla
                die("Error de conexión a la base de datos: " . self::$connection->connect_error);
            }

            // Establecer el juego de caracteres a UTF-8
            if (!self::$connection->set_charset("utf8mb4")) {
                // Opcional: registrar este error en un log en un entorno de producción
                // printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", self::$connection->error);
            }
        }

        return self::$connection;
    }

    /**
     * Prevenir la clonación de la instancia.
     */
    private function __clone() {
    }

    /**
     * Prevenir la deserialización de la instancia.
     */
    public function __wakeup() {
    }
}
