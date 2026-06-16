<?php
/**
 * dashboard/stats.php
 * GET /api/dashboard/stats.php
 * Requiere JWT válido en Authorization: Bearer <token>
 *
 * Devuelve estadísticas generales del sistema deportivo:
 *   - total_atletas
 *   - menores
 *   - disciplinas (distintas)
 *   - delegaciones  (distintas)
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

try {
    /* Total de atletas registrados */
    $totalAtletas = (int) $pdo
        ->query('SELECT COUNT(*) FROM datos_personales ')
        ->fetchColumn();

    /* Atletas menores de edad */
    $menores = (int) $pdo
        ->query('SELECT COUNT(*) FROM datos_personales  WHERE menor_edad = 1')
        ->fetchColumn();

    /* Disciplinas distintas */
    $disciplinas = (int) $pdo
        ->query('SELECT COUNT(DISTINCT disciplina) FROM perfil_deportivo WHERE disciplina IS NOT NULL')
        ->fetchColumn();

    /* delegaciones  distintas */
    $delegaciones  = (int) $pdo
        ->query('SELECT COUNT(DISTINCT id_delegacion) FROM datos_personales  WHERE id_delegacion IS NOT NULL')
        ->fetchColumn();

    /* Atletas por disciplina */
    $porDisciplina = $pdo
        ->query(
            'SELECT pd.disciplina, COUNT(*) AS total
             FROM perfil_deportivo pd
             WHERE pd.disciplina IS NOT NULL
             GROUP BY pd.disciplina
             ORDER BY total DESC'
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    /* Atletas por categoría */
    $porCategoria = $pdo
        ->query(
            'SELECT categoria, COUNT(*) AS total
             FROM datos_personales 
             WHERE categoria IS NOT NULL AND categoria <> ""
             GROUP BY categoria
             ORDER BY total DESC'
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    /* Registros recientes (últimos 5) */
    $recientes = $pdo
        ->query(
            'SELECT dp.folio_unico, dp.nombre, dp.apellido_paterno,
                    dp.categoria, dg.nombre AS delegacion,
                    pd.disciplina
             FROM datos_personales  dp
             LEFT JOIN perfil_deportivo pd ON pd.id_datos_personales  = dp.id_datos_personales 
             LEFT JOIN delegaciones  dg ON dg.id_delegacion = dp.id_delegacion
             ORDER BY dp.id_datos_personales  DESC
             LIMIT 5'
        )
        ->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'total_atletas' => $totalAtletas,
        'menores'       => $menores,
        'disciplinas'   => $disciplinas,
        'delegaciones '  => $delegaciones ,
        'por_disciplina' => $porDisciplina,
        'por_categoria'  => $porCategoria,
        'recientes'      => $recientes,
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener estadísticas.']);
}