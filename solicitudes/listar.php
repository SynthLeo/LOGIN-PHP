<?php
// api/solicitudes/listar.php
// ─────────────────────────────────────────────────────────────
//  GET /api/solicitudes/listar.php
//  GET /api/solicitudes/listar.php?estatus=pendiente
//  GET /api/solicitudes/listar.php?estatus=pendiente&pagina=2
//  PROTEGIDO — requiere JWT de admin.
//  Devuelve la lista paginada de solicitudes + conteos por estatus.
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit; }

Autenticacion::requerir();

$ESTATUSES_VALIDOS = ['pendiente', 'revision', 'aprobada', 'rechazada'];
$estatus  = in_array($_GET['estatus'] ?? '', $ESTATUSES_VALIDOS) ? $_GET['estatus'] : null;
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));
$porPag   = 20;
$offset   = ($pagina - 1) * $porPag;
$busqueda = trim($_GET['q'] ?? '');

$pdo = getDB();

// ── Conteos por estatus (siempre, para las tabs del dashboard) ──
$conteos = [];
$stmtC = $pdo->query(
    "SELECT estatus, COUNT(*) AS total FROM solicitudes GROUP BY estatus"
);
foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $conteos[$row['estatus']] = (int)$row['total'];
}
$conteos['todos'] = array_sum($conteos);

// ── Query principal ──────────────────────────────────────────
$where  = [];
$params = [];

if ($estatus) {
    $where[]  = 'estatus = :estatus';
    $params[':estatus'] = $estatus;
}

if ($busqueda) {
    $where[]       = '(nombre LIKE :q OR apellido_paterno LIKE :q OR curp LIKE :q OR folio_unico LIKE :q)';
    $params[':q']  = "%$busqueda%";
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Total para paginación
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM solicitudes $whereSQL");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

// Datos
$sql = "SELECT id, folio_unico, estatus,
               nombre, apellido_paterno, apellido_materno,
               curp, genero, edad, categoria,
               disciplina, id_delegacion,
               celular, correo, menor_edad,
               creado_en, actualizado_en, observaciones
        FROM solicitudes
        $whereSQL
        ORDER BY
          FIELD(estatus, 'pendiente', 'revision', 'aprobada', 'rechazada'),
          creado_en DESC
        LIMIT :limite OFFSET :offset";

$stmtD = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmtD->bindValue($k, $v); }
$stmtD->bindValue(':limite', $porPag, PDO::PARAM_INT);
$stmtD->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtD->execute();
$solicitudes = $stmtD->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'solicitudes'  => $solicitudes,
    'total'        => $total,
    'pagina'       => $pagina,
    'total_paginas'=> (int)ceil($total / $porPag),
    'conteos'      => $conteos,
]);
