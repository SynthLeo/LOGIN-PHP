<?php
/**
 * Seguridad/Cors.php
 * Aplica cabeceras CORS contra el origen permitido en .env.
 * Incluir con require_once al inicio de cada endpoint.
 */

namespace Seguridad;

use Configuracion\Entorno;

class Cors
{
    public static function aplicar(): void
    {
        $permitido = Entorno::get('ALLOWED_ORIGIN', 'http://localhost:5173');
        $origen    = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origen === $permitido) {
            header("Access-Control-Allow-Origin: {$origen}");
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json; charset=utf-8');

        /* Pre-flight OPTIONS → responder y salir */
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
