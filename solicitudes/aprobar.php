<?php
// api/solicitudes/aprobar.php
// ─────────────────────────────────────────────────────────────
//  PATCH /api/solicitudes/aprobar.php
//  Body: { "id": 42 }
//  PROTEGIDO — requiere JWT de admin.
//
//  Mueve los datos de `solicitudes` a las tablas principales:
//  usuarios, informacion_medica, seguro, contactos_emerg,
//  datos_personales, perfil_deportivo. Transacción completa.
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

// ── Verificar JWT ────────────────────────────────────────────
Autenticacion::requerir();

// ── Leer body ────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
$id   = (int)($body['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de solicitud inválido.']);
    exit;
}

$pdo = getDB();

// ── Obtener solicitud ────────────────────────────────────────
$stmtSol = $pdo->prepare('SELECT * FROM solicitudes WHERE id = ? LIMIT 1');
$stmtSol->execute([$id]);
$s = $stmtSol->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    http_response_code(404);
    echo json_encode(['message' => 'Solicitud no encontrada.']);
    exit;
}

if ($s['estatus'] === 'aprobada') {
    http_response_code(409);
    echo json_encode(['message' => 'Esta solicitud ya fue aprobada anteriormente.']);
    exit;
}

// ── Transacción ──────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // 1. usuarios (crear cuenta para el atleta)
    $email = trim($s['correo']) ?: (strtolower($s['curp']) . '@sinregistro.local');

    $stmtCheckEmail = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1');
    $stmtCheckEmail->execute([$email]);
    if ($stmtCheckEmail->fetch()) {
        // evitar choque con UNIQUE email si ya existe
        $email = strtolower($s['curp']) . '+' . $s['folio_unico'] . '@sinregistro.local';
    }

    $passwordTemporal = bin2hex(random_bytes(6)); // 12 caracteres
    $passwordHash     = password_hash($passwordTemporal, PASSWORD_DEFAULT);

    $pdo->prepare(
        'INSERT INTO usuarios (email, password_hash, rol)
         VALUES (:email, :pass, :rol)'
    )->execute([
        ':email' => $email,
        ':pass'  => $passwordHash,
        ':rol'   => 'atleta',
    ]);
    $idUsuario = (int)$pdo->lastInsertId();

    // 2. informacion_medica
    $pdo->prepare(
        'INSERT INTO informacion_medica (tipo_sangre, factor_rh, alergias, padecimientos)
         VALUES (:tipo_sangre, :factor_rh, :alergias, :padecimientos)'
    )->execute([
        ':tipo_sangre'   => $s['tipo_sangre'],
        ':factor_rh'     => $s['factor_rh'],
        ':alergias'      => $s['alergias'],
        ':padecimientos' => $s['padecimientos'],
    ]);
    $idMedica = (int)$pdo->lastInsertId();

    // 3. seguro (solo si tiene tipo)
    $idseguro = null;
    if (!empty($s['seguro_tipo'])) {
        $pdo->prepare(
            'INSERT INTO seguro (tipo, estado) VALUES (:tipo, :estado)'
        )->execute([
            ':tipo'   => $s['seguro_tipo'],
            ':estado' => 'activo',
        ]);
        $idseguro = (int)$pdo->lastInsertId();
    }

    // 4. contactos_emerg
    $pdo->prepare(
        'INSERT INTO contactos_emerg (nombre_completo, parentesco, tel_principal)
         VALUES (:nombre, :parentesco, :tel)'
    )->execute([
        ':nombre'     => $s['em_nombre_completo'],
        ':parentesco' => $s['em_parentesco'],
        ':tel'        => $s['em_tel_principal'],
    ]);
    $idContacto = (int)$pdo->lastInsertId();

    // 5. datos_personales
    $pdo->prepare(
        'INSERT INTO datos_personales (
            folio_unico, curp, nombre, apellido_paterno, apellido_materno,
            genero, fecha_nacimiento, edad, menor_edad, datos_tutor,
            domicilio, celular, correo,
            id_usuario, id_delegacion, id_informacion_medica, id_contacto, id_seguro
         ) VALUES (
            :folio_unico, :curp, :nombre, :apellido_paterno, :apellido_materno,
            :genero, :fecha_nacimiento, :edad, :menor_edad, :datos_tutor,
            :domicilio, :celular, :correo,
            :id_usuario, :id_delegacion, :id_medica, :id_contacto, :id_seguro
         )'
    )->execute([
        ':folio_unico'      => $s['folio_unico'],
        ':curp'             => $s['curp'],
        ':nombre'           => $s['nombre'],
        ':apellido_paterno' => $s['apellido_paterno'],
        ':apellido_materno' => $s['apellido_materno'],
        ':genero'           => $s['genero'],
        ':fecha_nacimiento' => $s['fecha_nacimiento'],
        ':edad'             => $s['edad'],
        ':menor_edad'       => $s['menor_edad'],
        ':datos_tutor'      => $s['datos_tutor'],
        ':domicilio'        => $s['domicilio'],
        ':celular'          => $s['celular'],
        ':correo'           => $s['correo'],
        ':id_usuario'       => $idUsuario,
        ':id_delegacion'    => $s['id_delegacion'],
        ':id_medica'        => $idMedica,
        ':id_contacto'      => $idContacto,
        ':id_seguro'        => $idseguro,
    ]);
    $idPersonal = (int)$pdo->lastInsertId();

    // 6. perfil_deportivo
    $pdo->prepare(
        'INSERT INTO perfil_deportivo (disciplina, nivel, modalidad, id_datos_personales)
         VALUES (:disciplina, :nivel, :modalidad, :id)'
    )->execute([
        ':disciplina' => $s['disciplina'],
        ':nivel'      => $s['nivel'],
        ':modalidad'  => $s['modalidad'],
        ':id'         => $idPersonal,
    ]);

    // 7. Marcar solicitud como aprobada
    $pdo->prepare(
        'UPDATE solicitudes
         SET estatus = "aprobada", aprobado_en = NOW(), aprobado_por = :admin
         WHERE id = :id'
    )->execute([
        ':admin' => $GLOBALS['userId'],
        ':id'    => $id,
    ]);

    $pdo->commit();

    echo json_encode([
        'message'             => 'Solicitud aprobada y atleta registrado correctamente.',
        'folio_unico'         => $s['folio_unico'],
        'id_datos_personales' => $idPersonal,
        'id_usuario'          => $idUsuario,
        'email_generado'      => $email,
        'password_temporal'   => $passwordTemporal,
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'message' => 'Error al aprobar la solicitud. No se realizaron cambios.',
        'detalle' => $e->getMessage(),
    ]);
}