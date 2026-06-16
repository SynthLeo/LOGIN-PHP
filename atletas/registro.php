<?php


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
Autenticacion::requerir();          // → $GLOBALS['userId']


/* ── Solo POST ───────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

/* ── Leer body ───────────────────────────────────────────────────────────── */
$d = json_decode(file_get_contents('php://input'), true);
if (!$d) {
    http_response_code(400);
    echo json_encode(['error' => 'Body JSON inválido.']);
    exit;
}

/* ── Helper: obtener string o null ──────────────────────────────────────── */
$str  = fn(string $k, string $def = '') => trim((string)($d[$k] ?? $def));
$bool = fn(string $k) => !empty($d[$k]);
$int  = fn(string $k) => ($d[$k] !== '' && $d[$k] !== null) ? (int)$d[$k] : null;

/* ── Validaciones requeridas ─────────────────────────────────────────────── */
$requeridos = [
    'nombre'            => 'Nombre(s)',
    'apellido_paterno'  => 'Primer apellido',
    'curp'              => 'CURP',
    'genero'            => 'Género',
    'fecha_nacimiento'  => 'Fecha de nacimiento',
    'domicilio'         => 'Calle y número',
    'id_delegacion'     => 'Delegación',
    'celular'           => 'Teléfono celular',
    'disciplina'        => 'Disciplina',
    'em_nombre_completo'=> 'Contacto de emergencia',
    'em_parentesco'     => 'Parentesco',
    'em_tel_principal'  => 'Teléfono de emergencia',
];

foreach ($requeridos as $campo => $etiqueta) {
    if ($str($campo) === '') {
        http_response_code(422);
        echo json_encode(['error' => "Campo requerido vacío: {$etiqueta}."]);
        exit;
    }
}

/* Validar CURP */
$curp = strtoupper($str('curp'));
if (strlen($curp) !== 18) {
    http_response_code(422);
    echo json_encode(['error' => 'La CURP debe tener exactamente 18 caracteres.']);
    exit;
}

/* Validar fecha de nacimiento */
$fechaNac = $str('fecha_nacimiento');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNac)) {
    http_response_code(422);
    echo json_encode(['error' => 'Formato de fecha inválido (YYYY-MM-DD).']);
    exit;
}

/* Tutor obligatorio si es menor */
$esMenor   = $bool('menor_edad');
$datosTutor = $d['datos_tutor'] ?? [];
if ($esMenor) {
    foreach (['nombre', 'telefono', 'correo'] as $campo) {
        if (empty(trim($datosTutor[$campo] ?? ''))) {
            http_response_code(422);
            echo json_encode(['error' => "Dato de tutor requerido: {$campo}."]);
            exit;
        }
    }
}

/* ── Transacción ─────────────────────────────────────────────────────────── */
$pdo = getDB();

try {
    $pdo->beginTransaction();

    /* 1. informacion_medica ──────────────────────────────────────────────── */
    $stmtMedica = $pdo->prepare(
        'INSERT INTO informacion_medica
            (tipo_sangre, factor_rh, alergias, padecimientos)
         VALUES (?, ?, ?, ?)'
    );
    $stmtMedica->execute([
        $str('tipo_sangre') ?: null,
        $str('factor_rh')   ?: null,
        $str('alergias')    ?: null,
        $str('padecimientos') ?: null,
    ]);
    $idMedica = (int) $pdo->lastInsertId();

    /* 2. SEGURO ──────────────────────────────────────────────────────────── */
    $idSeguro = null;
    $tipoSeguro = $str('seguro_tipo');
    if ($tipoSeguro !== '') {
        $stmtSeguro = $pdo->prepare(
            'INSERT INTO SEGURO (tipo, estado) VALUES (?, ?)'
        );
        $stmtSeguro->execute([$tipoSeguro, 'activo']);
        $idSeguro = (int) $pdo->lastInsertId();
    }

    /* 3. contactos_emerg. ────────────────────────────────────────────────── */
    $stmtEmerg = $pdo->prepare(
        'INSERT INTO contactos_emerg
            (nombre_completo, parentesco, tel_principal)
         VALUES (?, ?, ?)'
    );
    $stmtEmerg->execute([
        $str('em_nombre_completo'),
        $str('em_parentesco'),
        $str('em_tel_principal'),
    ]);
    $idContacto = (int) $pdo->lastInsertId();

    /* 4. datos_personales  ────────────────────────────────────────────────── */
    /*
     * datos_tutor se guarda como JSON en la columna datos_tutor.
     * Si prefieres columnas separadas, ajusta el schema y este INSERT.
     */
    $datosTutorJson = $esMenor
        ? json_encode([
            'nombre'   => trim($datosTutor['nombre']   ?? ''),
            'telefono' => trim($datosTutor['telefono'] ?? ''),
            'correo'   => trim($datosTutor['correo']   ?? ''),
          ], JSON_UNESCAPED_UNICODE)
        : null;

    $stmtDatos = $pdo->prepare(
        'INSERT INTO datos_personales 
            (folio_unico, curp, nombre, apellido_paterno, apellido_materno,
             genero, fecha_nacimiento, edad, menor_edad, datos_tutor,
             domicilio, celular, correo,
             id_usuario, id_delegacion, id_informacion_medica, id_contacto, id_seguro)
         VALUES (?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?, ?)'
    );
    $stmtDatos->execute([
        $str('folio_unico'),
        $curp,
        $str('nombre'),
        $str('apellido_paterno'),
        $str('apellido_materno') ?: null,
        $str('genero'),
        $fechaNac,
        $int('edad'),
        $esMenor ? 1 : 0,
        $datosTutorJson,
        $str('domicilio'),
        $str('celular'),
        $str('correo') ?: null,
        $GLOBALS['userId'],                 // FK → usuarios.id_usuario
        (int) $str('id_delegacion'),        // FK → delegaciones .id_delegacion
        $idMedica,                          // FK → informacion_medica
        $idContacto,                        // FK → contactos_emerg.
        $idSeguro,                          // FK → SEGURO (nullable)
    ]);
    $idDatos = (int) $pdo->lastInsertId();

    /* 5. perfil_deportivo ────────────────────────────────────────────────── */
    $disciplina = $str('disciplina');
    $nivel      = $str('nivel')     ?: null;
    $modalidad  = $str('modalidad') ?: null;

    if ($disciplina !== '') {
        $stmtPerfil = $pdo->prepare(
            'INSERT INTO perfil_deportivo
                (disciplina, nivel, modalidad, id_datos_personales )
             VALUES (?, ?, ?, ?)'
        );
        $stmtPerfil->execute([$disciplina, $nivel, $modalidad, $idDatos]);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'message'    => 'Atleta registrado correctamente.',
        'folio'      => $str('folio_unico'),
        'id_atleta'  => $idDatos,
    ]);

} catch (\PDOException $e) {
    $pdo->rollBack();

    /* CURP duplicado (UNIQUE constraint) */
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe un atleta registrado con esa CURP.']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['error' => 'Error interno al registrar el atleta.']);
    // En desarrollo: echo json_encode(['error' => $e->getMessage()]);
}
