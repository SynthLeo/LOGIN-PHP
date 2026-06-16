<?php
// api/solicitudes/actualizar_estatus.php
// ─────────────────────────────────────────────────────────────
//  PATCH /api/solicitudes/actualizar_estatus.php
//  Body: { "id": 42, "estatus": "rechazada", "observaciones": "Falta CURP..." }
//  Body: { "id": 42, "estatus": "revision" }
//  PROTEGIDO — requiere JWT de admin.
//
//  Sirve para:
//    · Poner en revisión  → estatus = "revision"
//    · Rechazar           → estatus = "rechazada" + observaciones (obligatorio)
//    · Regresar a pendiente si hubo un error → estatus = "pendiente"
//  No usar para aprobar (usa aprobar.php).
// ─────────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../Configuracion/Entorno.php';
require_once __DIR__ . '/../Configuracion/Database.php';
require_once __DIR__ . '/../Seguridad/JWT.php';
require_once __DIR__ . '/../Seguridad/Autenticacion.php';

use Seguridad\Autenticacion;
use function Configuracion\getDB;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH')   { http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit; }

// ── JWT ──────────────────────────────────────────────────────
Autenticacion::requerir();

// ── Body ─────────────────────────────────────────────────────
$body   = json_decode(file_get_contents('php://input'), true);
$id     = (int)($body['id'] ?? 0);
$nuevo  = trim($body['estatus'] ?? '');
$obs    = trim($body['observaciones'] ?? '');

$permitidos = ['pendiente', 'revision', 'rechazada'];

if ($id <= 0 || !in_array($nuevo, $permitidos)) {
    http_response_code(400);
    echo json_encode(['message' => 'id o estatus inválido. Usa: ' . implode(', ', $permitidos)]);
    exit;
}

if ($nuevo === 'rechazada' && !$obs) {
    http_response_code(422);
    echo json_encode(['message' => 'Al rechazar una solicitud debes indicar el motivo en "observaciones".']);
    exit;
}

$pdo = getDB();

// Verificar que existe y no está ya aprobada
$stmtCheck = $pdo->prepare('SELECT estatus FROM solicitudes WHERE id = ? LIMIT 1');
$stmtCheck->execute([$id]);
$sol = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$sol) {
    http_response_code(404);
    echo json_encode(['message' => 'Solicitud no encontrada.']);
    exit;
}

if ($sol['estatus'] === 'aprobada') {
    http_response_code(409);
    echo json_encode(['message' => 'No se puede modificar una solicitud ya aprobada.']);
    exit;
}

// ── Actualizar ────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'UPDATE solicitudes SET estatus = :estatus, observaciones = :obs WHERE id = :id'
);
$stmt->execute([
    ':estatus' => $nuevo,
    ':obs'     => $obs ?: null,
    ':id'      => $id,
]);

$etiquetas = [
    'pendiente' => 'Regresada a pendiente',
    'revision'  => 'Marcada en revisión',
    'rechazada' => 'Solicitud rechazada',
];

echo json_encode([
    'message' => $etiquetas[$nuevo] . ' correctamente.',
    'id'      => $id,
    'estatus' => $nuevo,
]);
