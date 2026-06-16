<?php
/**
 * atletas/listar.php
 * GET /api/atletas/listar.php
 * Requiere JWT válido en Authorization: Bearer <token>
 *
 * Devuelve la lista completa de atletas con sus datos:
 *   datos_personales  + perfil_deportivo + informacion_medica + contactos_emerg
 *
 * Parámetros opcionales (GET):
 *   ?disciplina=Fútbol
 *   ?categoria=Juvenil
 *   ?busqueda=texto
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

/* ── Solo GET ────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$pdo = getDB();

/* ── Filtros opcionales ──────────────────────────────────────────────────── */
$disciplina = trim($_GET['disciplina'] ?? '');
$categoria  = trim($_GET['categoria']  ?? '');
$busqueda   = trim($_GET['busqueda']   ?? '');

/* ── Construcción dinámica del WHERE ────────────────────────────────────── */
$condiciones = [];
$params      = [];

if ($disciplina !== '') {
    $condiciones[] = 'pd.disciplina = ?';
    $params[]      = $disciplina;
}

if ($categoria !== '') {
    $condiciones[] = 'dp.categoria = ?';
    $params[]      = $categoria;
}

if ($busqueda !== '') {
    $condiciones[] = '(dp.nombre LIKE ? OR dp.apellido_paterno LIKE ? OR dp.curp LIKE ? OR dp.folio_unico LIKE ?)';
    $like = "%{$busqueda}%";
    array_push($params, $like, $like, $like, $like);
}

$where = count($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

/* ── Query principal ────────────────────────────────────────────────────── */
try {
    $sql = "
        SELECT
            dp.id_datos_personales ,
            dp.folio_unico,
            dp.nombre,
            dp.apellido_paterno,
            dp.apellido_materno,
            dp.curp,
            dp.genero,
            dp.fecha_nacimiento,
            dp.edad,
            dp.categoria,
            dp.menor_edad,
            dp.datos_tutor,
            dp.domicilio,
            dp.celular,
            dp.correo,
            dp.id_delegacion,
            dg.nombre AS delegacion,
            pd.disciplina,
            pd.nivel,
            pd.modalidad,

            im.tipo_sangre,
            im.factor_rh,
            im.alergias,
            im.padecimientos,

            ce.nombre_completo  AS em_nombre_completo,
            ce.parentesco       AS em_parentesco,
            ce.tel_principal    AS em_tel_principal,

            s.tipo              AS seguro_tipo

        FROM datos_personales  dp
        LEFT JOIN perfil_deportivo   pd ON pd.id_datos_personales   = dp.id_datos_personales 
        LEFT JOIN informacion_medica im ON im.id_informacion_medica = dp.id_informacion_medica
        LEFT JOIN contactos_emerg    ce ON ce.id_contacto           = dp.id_contacto
        LEFT JOIN seguro             s ON s.id_seguro               = dp.id_seguro
        LEFT JOIN delegaciones  dg ON dg.id_delegacion = dp.id_delegacion
        {$where}
        ORDER BY dp.id_datos_personales  DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Convertir tipos */
    foreach ($atletas as &$a) {
        $a['menor_edad'] = (bool) $a['menor_edad'];
        $a['edad']       = $a['edad'] !== null ? (int) $a['edad'] : null;

        /* datos_tutor viene como JSON string → decodificar */
        if ($a['datos_tutor'] !== null) {
            $decoded = json_decode($a['datos_tutor'], true);
            $a['datos_tutor'] = $decoded ?: null;
        }
    }
    unset($a);

    http_response_code(200);
    echo json_encode([
        'total'   => count($atletas),
        'atletas' => $atletas,
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]); // ← cambia esto temporalmente
}