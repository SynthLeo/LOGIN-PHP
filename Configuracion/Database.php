<?php
/**
 * Configuracion/Database.php
 * Singleton PDO. Uso: $pdo = getDB();
 */

namespace Configuracion;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instancia = null;

    public static function conexion(): PDO
    {
        if (self::$instancia !== null) return self::$instancia;

        $cfg = Entorno::db();
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";

        try {
            self::$instancia = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a la base de datos.']);
            exit;
        }

        return self::$instancia;
    }
}

/* Función global para compatibilidad con el resto del proyecto */
function getDB(): PDO
{
    return \Configuracion\Database::conexion();
}
