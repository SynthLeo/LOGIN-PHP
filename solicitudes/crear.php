<?php
// api/solicitudes/crear.php
// ─────────────────────────────────────────────────────────────
//  POST /api/solicitudes/crear.php
//  PÚBLICO — no requiere JWT.
//  Recibe el JSON del formulario del atleta y lo guarda en
//  la tabla `solicitudes` con estatus = 'pendiente'.
// ─────────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../Configuracion/Entorno.php';
require_once __DIR__ . '/../Configuracion/Database.php';

use function Configuracion\getDB;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit; }

// ── Leer body ───────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['message' => 'Body JSON inválido o vacío.']);
    exit;
}

// ── Campos requeridos ────────────────────────────────────────
$requeridos = [
    'folio_unico', 'nombre', 'apellido_paterno', 'curp', 'genero',
    'fecha_nacimiento', 'edad', 'categoria', 'domicilio', 'celular',
    'id_delegacion', 'disciplina',
    'em_nombre_completo', 'em_parentesco', 'em_tel_principal',
];

foreach ($requeridos as $campo) {
    if (empty(trim((string)($body[$campo] ?? '')))) {
        http_response_code(422);
        echo json_encode(['message' => "Campo requerido vacío: $campo"]);
        exit;
    }
}

// ── Validar CURP ─────────────────────────────────────────────
$curp = strtoupper(trim($body['curp']));
if (strlen($curp) !== 18) {
    http_response_code(422);
    echo json_encode(['message' => 'La CURP debe tener exactamente 18 caracteres.']);
    exit;
}

// ── Validar datos del tutor si es menor ─────────────────────
$esMenor    = !empty($body['menor_edad']);
$datosTutor = null;

if ($esMenor) {
    $tutor = $body['datos_tutor'] ?? [];
    if (empty(trim($tutor['nombre'] ?? '')) ||
        empty(trim($tutor['telefono'] ?? '')) ||
        empty(trim($tutor['correo'] ?? ''))) {
        http_response_code(422);
        echo json_encode(['message' => 'Datos del tutor incompletos (atleta menor de edad).']);
        exit;
    }
    $datosTutor = json_encode([
        'nombre'   => trim($tutor['nombre']),
        'telefono' => trim($tutor['telefono']),
        'correo'   => trim($tutor['correo']),
    ]);
}

// ── Verificar CURP/folio duplicado ───────────────────────────
$pdo = getDB();   // ← tu función de conexión

$stmtCheck = $pdo->prepare(
    'SELECT id, estatus FROM solicitudes WHERE curp = ? OR folio_unico = ? LIMIT 1'
);
$stmtCheck->execute([$curp, trim($body['folio_unico'])]);
$existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($existente) {
    http_response_code(409);
    echo json_encode([
        'message' => 'Ya existe una solicitud con esa CURP o folio.',
        'estatus' => $existente['estatus'],
    ]);
    exit;
}

// ── Insertar ─────────────────────────────────────────────────
$sql = <<<SQL
INSERT INTO solicitudes (
  folio_unico, nombre, apellido_paterno, apellido_materno,
  curp, genero, fecha_nacimiento, edad, categoria,
  domicilio, celular, telefono_fijo, correo, id_delegacion,
  menor_edad, datos_tutor,
  disciplina, nivel, modalidad,
  tipo_sangre, factor_rh, alergias, padecimientos, seguro_tipo,
  em_nombre_completo, em_parentesco, em_tel_principal
) VALUES (
  :folio_unico, :nombre, :apellido_paterno, :apellido_materno,
  :curp, :genero, :fecha_nacimiento, :edad, :categoria,
  :domicilio, :celular, :telefono_fijo, :correo, :id_delegacion,
  :menor_edad, :datos_tutor,
  :disciplina, :nivel, :modalidad,
  :tipo_sangre, :factor_rh, :alergias, :padecimientos, :seguro_tipo,
  :em_nombre_completo, :em_parentesco, :em_tel_principal
)
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':folio_unico'        => trim($body['folio_unico']),
    ':nombre'             => trim($body['nombre']),
    ':apellido_paterno'   => trim($body['apellido_paterno']),
    ':apellido_materno'   => trim($body['apellido_materno'] ?? '') ?: null,
    ':curp'               => $curp,
    ':genero'             => trim($body['genero']),
    ':fecha_nacimiento'   => $body['fecha_nacimiento'],
    ':edad'               => (int)$body['edad'],
    ':categoria'          => trim($body['categoria']),
    ':domicilio'          => trim($body['domicilio']),
    ':celular'            => trim($body['celular']),
    ':telefono_fijo'      => trim($body['telefono_fijo'] ?? '') ?: null,
    ':correo'             => trim($body['correo'] ?? '') ?: null,
    ':id_delegacion'      => (int)$body['id_delegacion'],
    ':menor_edad'         => $esMenor ? 1 : 0,
    ':datos_tutor'        => $datosTutor,
    ':disciplina'         => trim($body['disciplina']),
    ':nivel'              => trim($body['nivel'] ?? '') ?: null,
    ':modalidad'          => trim($body['modalidad'] ?? '') ?: null,
    ':tipo_sangre'        => trim($body['tipo_sangre'] ?? '') ?: null,
    ':factor_rh'          => trim($body['factor_rh'] ?? '') ?: null,
    ':alergias'           => trim($body['alergias'] ?? '') ?: null,
    ':padecimientos'      => trim($body['padecimientos'] ?? '') ?: null,
    ':seguro_tipo'        => trim($body['seguro_tipo'] ?? '') ?: null,
    ':em_nombre_completo' => trim($body['em_nombre_completo']),
    ':em_parentesco'      => trim($body['em_parentesco']),
    ':em_tel_principal'   => trim($body['em_tel_principal']),
]);

http_response_code(201);
echo json_encode([
    'message'     => 'Solicitud enviada correctamente.',
    'folio_unico' => trim($body['folio_unico']),
    'estatus'     => 'pendiente',
]);
