<?php
// api/solicitudes/estatus.php
// ─────────────────────────────────────────────────────────────
//  GET /api/solicitudes/estatus.php?folio=ATL-2026-1234
//  GET /api/solicitudes/estatus.php?curp=XXXX180101HXXXXX01
//  PÚBLICO — no requiere JWT.
//  Devuelve el estatus y la info básica para mostrar el semáforo.
// ─────────────────────────────────────────────────────────────

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../Configuracion/Entorno.php';
require_once __DIR__ . '/../Configuracion/Database.php';

use function Configuracion\getDB;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit; }

$folio = trim($_GET['folio'] ?? '');
$curp  = strtoupper(trim($_GET['curp'] ?? ''));

if (!$folio && !$curp) {
    http_response_code(400);
    echo json_encode(['message' => 'Proporciona folio_unico o curp como parámetro.']);
    exit;
}

$pdo = getDB();

$sql = 'SELECT folio_unico, nombre, apellido_paterno, disciplina, categoria,
               estatus, observaciones, creado_en, aprobado_en
        FROM solicitudes
        WHERE ' . ($folio ? 'folio_unico = :valor' : 'curp = :valor') . '
        LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute([':valor' => $folio ?: $curp]);
$sol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sol) {
    http_response_code(404);
    echo json_encode(['message' => 'No se encontró ninguna solicitud con ese folio o CURP.']);
    exit;
}

// ── Mapear estatus → semáforo ────────────────────────────────
$semaforo = [
    'pendiente' => ['color' => 'rojo',    'icono' => '🔴', 'etiqueta' => 'Pendiente de revisión'],
    'revision'  => ['color' => 'amarillo','icono' => '🟡', 'etiqueta' => 'En revisión por el equipo IMDET'],
    'aprobada'  => ['color' => 'verde',   'icono' => '🟢', 'etiqueta' => 'Aprobada — Registrado oficialmente'],
    'rechazada' => ['color' => 'gris',    'icono' => '⚫', 'etiqueta' => 'Rechazada'],
];

$info = $semaforo[$sol['estatus']] ?? $semaforo['pendiente'];

echo json_encode([
    'folio_unico'    => $sol['folio_unico'],
    'nombre'         => $sol['nombre'] . ' ' . $sol['apellido_paterno'],
    'disciplina'     => $sol['disciplina'],
    'categoria'      => $sol['categoria'],
    'estatus'        => $sol['estatus'],
    'semaforo'       => $info,
    'observaciones'  => $sol['observaciones'],   // mensaje del admin si fue rechazada
    'creado_en'      => $sol['creado_en'],
    'aprobado_en'    => $sol['aprobado_en'],
]);
