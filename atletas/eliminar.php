<?php
/**
 * atletas/eliminar.php
 * DELETE /api/atletas/eliminar.php?id=123
 * Requiere JWT válido en Authorization: Bearer <token>
 *
 * Elimina en cascada:
 *   1. perfil_deportivo      (FK → datos_personales )
 *   2. datos_personales       (principal)
 *   3. informacion_medica    (FK referenciada)
 *   4. contactos_emerg       (FK referenciada)
 *   5. SEGURO                (FK referenciada, nullable)
 */

declare(strict_types=1);

require_once __DIR__ . '/../Configuracion/Entorno.php';
require_once __DIR__ . '/../Configuracion/Database.php';
require_once __DIR__ . '/../Seguridad/Cors.php';
require_once __DIR__ . '/../Seguridad/JWT.php';
require_once __DIR__ . '/../Seguridad/Autenticacion.php';

use Configuracion\Entorno;
use Seguridad\Cors;
use Seguridad\Autenticacion;

use function Configuracion\getDB;

Cors::aplicar();
Autenticacion::requerir();

/* ── Solo DELETE ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

/* ── Validar ID ─────────────────────────────────────────────────────────── */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de atleta inválido.']);
    exit;
}

$pdo = getDB();

/* ── Verificar que el atleta existe ─────────────────────────────────────── */
$stmt = $pdo->prepare(
    'SELECT id_datos_personales , id_informacion_medica, id_contacto, id_seguro
     FROM datos_personales  WHERE id_datos_personales  = ?'
);
$stmt->execute([$id]);
$atleta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atleta) {
    http_response_code(404);
    echo json_encode(['error' => 'Atleta no encontrado.']);
    exit;
}

/* ── Eliminar en transacción ────────────────────────────────────────────── */
try {
    $pdo->beginTransaction();

    /* 1. perfil_deportivo (depende de datos_personales ) */
    $pdo->prepare('DELETE FROM perfil_deportivo WHERE id_datos_personales  = ?')
        ->execute([$id]);

    /* 2. datos_personales  */
    $pdo->prepare('DELETE FROM datos_personales  WHERE id_datos_personales  = ?')
        ->execute([$id]);

    /* 3. informacion_medica */
    if ($atleta['id_informacion_medica']) {
        $pdo->prepare('DELETE FROM informacion_medica WHERE id_informacion_medica = ?')
            ->execute([$atleta['id_informacion_medica']]);
    }

    /* 4. contactos_emerg */
    if ($atleta['id_contacto']) {
        $pdo->prepare('DELETE FROM contactos_emerg WHERE id_contacto = ?')
            ->execute([$atleta['id_contacto']]);
    }

    /* 5. SEGURO (nullable) */
    if ($atleta['id_seguro']) {
        $pdo->prepare('DELETE FROM seguro WHERE id_seguro = ?')
            ->execute([$atleta['id_seguro']]);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'message' => 'Atleta eliminado correctamente.',
        'id'      => $id,
    ]);

} catch (\PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar el atleta.']);
}