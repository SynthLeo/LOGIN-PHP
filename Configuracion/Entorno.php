<?php
/**
 * Configuracion/Entorno.php
 * Fuente única de configuración. Carga .env si existe.
 * Uso: Entorno::get('JWT_SECRET')
 */

namespace Configuracion;

class Entorno
{
    private static bool $cargado = false;

    public static function cargar(): void
    {
        if (self::$cargado) return;

        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                $linea = trim($linea);
                if ($linea === '' || str_starts_with($linea, '#')) continue;
                if (!str_contains($linea, '=')) continue;

                [$clave, $valor] = explode('=', $linea, 2);
                $clave  = trim($clave);
                $valor  = trim($valor);

                if (!array_key_exists($clave, $_ENV)) {
                    $_ENV[$clave]    = $valor;
                    putenv("{$clave}={$valor}");
                }
            }
        }

        self::$cargado = true;
    }

    public static function get(string $clave, string $defecto = ''): string
    {
        self::cargar();
        return $_ENV[$clave] ?? getenv($clave) ?: $defecto;
    }

    public static function getInt(string $clave, int $defecto = 0): int
    {
        return (int) self::get($clave, (string) $defecto);
    }

    /* Devuelve la configuración de base de datos como array */
    public static function db(): array
    {
        self::cargar();
        return [
            'host' => self::get('DB_HOST', '127.0.0.1'),
            'port' => self::getInt('DB_PORT', 3306),
            'name' => self::get('DB_NAME', 'deporte'),
            'user' => self::get('DB_USER', 'root'),
            'pass' => self::get('DB_PASS', ''),
        ];
    }
}
