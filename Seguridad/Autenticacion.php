<?php
/**
 * Seguridad/Autenticacion.php
 * Middleware JWT. Incluir con require_once en endpoints protegidos.
 * Tras incluirlo, $GLOBALS['userId'] contiene el id_usuario autenticado.
 */

namespace Seguridad;

class Autenticacion
{
    public static function requerir(): void
    {
        $userId = JWT::getUserId();

        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado. Token inválido o expirado.']);
            exit;
        }

        /* Disponible globalmente para el endpoint */
        $GLOBALS['userId'] = $userId;
    }
}
