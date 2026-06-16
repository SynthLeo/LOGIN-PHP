<?php
/**
 * auth/login.php
 * POST /api/auth/login.php
 *
 * Body JSON:
 *   { "email": "...", "password": "..." }
 *
 * Respuesta exitosa:
 *   { "token": "...", "rol": "...", "nombre": "..." }
 *
 * Tablas involucradas:
 *   usuarios  (email, password_hash, rol, activo, intentos_fallidos, bloqueado_hasta)
 *   SESIONES  (id_usuario, token_sesion, inicio_sesion, expira_en, activa)
 */

declare(strict_types=1);

require_once __DIR__ . '/../Configuracion/Entorno.php';
require_once __DIR__ . '/../Configuracion/Database.php';
require_once __DIR__ . '/../Seguridad/Cors.php';
require_once __DIR__ . '/../Seguridad/JWT.php';

use Configuracion\Entorno;
use Seguridad\Cors;
use Seguridad\JWT;

use function Configuracion\getDB;

Cors::aplicar();

/* ── Solo POST ───────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

/* ── Leer y validar body ─────────────────────────────────────────────────── */
$body = json_decode(file_get_contents('php://input'), true);

$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña son requeridos.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de correo inválido.']);
    exit;
}

/* ── Constantes de bloqueo ───────────────────────────────────────────────── */
$maxIntentos     = Entorno::getInt('LOGIN_MAX_INTENTOS', 5);
$bloqueoSegundos = Entorno::getInt('LOGIN_BLOQUEO_SEGUNDOS', 900);

$pdo = getDB();

/* ── Buscar usuario ──────────────────────────────────────────────────────── */
$stmt = $pdo->prepare(
    'SELECT id_usuario, email, password_hash, rol, activo,
            intentos_fallidos, bloqueado_hasta
     FROM usuarios   
     WHERE email = ?
     LIMIT 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch();

/* Respuesta genérica para no revelar si el email existe */
$errorGenerico = ['error' => 'Credenciales incorrectas o cuenta inactiva.'];

if (!$usuario) {
    http_response_code(401);
    echo json_encode($errorGenerico);
    exit;
}

/* ── Cuenta inactiva ─────────────────────────────────────────────────────── */
if (!(bool) $usuario['activo']) {
    http_response_code(401);
    echo json_encode(['error' => 'Cuenta inactiva. Contacta al administrador.']);
    exit;
}

/* ── Cuenta bloqueada temporalmente ─────────────────────────────────────── */
if ($usuario['bloqueado_hasta'] !== null) {
    $bloqueadoHasta = strtotime($usuario['bloqueado_hasta']);
    if ($bloqueadoHasta > time()) {
        $minutos = ceil(($bloqueadoHasta - time()) / 60);
        http_response_code(429);
        echo json_encode([
            'error' => "Cuenta bloqueada. Intenta de nuevo en {$minutos} minuto(s).",
        ]);
        exit;
    }
    /* Bloqueo expirado → resetear */
    $pdo->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?')
        ->execute([$usuario['id_usuario']]);
    $usuario['intentos_fallidos'] = 0;
}

/* ── Verificar contraseña ────────────────────────────────────────────────── */
if (!password_verify($password, $usuario['password_hash'])) {

    $nuevosIntentos = (int) $usuario['intentos_fallidos'] + 1;

    if ($nuevosIntentos >= $maxIntentos) {
        /* Bloquear cuenta */
        $hasta = date('Y-m-d H:i:s', time() + $bloqueoSegundos);
        $pdo->prepare(
            'UPDATE usuarios 
             SET intentos_fallidos = ?, bloqueado_hasta = ?
             WHERE id_usuario = ?'
        )->execute([$nuevosIntentos, $hasta, $usuario['id_usuario']]);

        http_response_code(429);
        echo json_encode([
            'error' => "Demasiados intentos fallidos. Cuenta bloqueada por " . ($bloqueoSegundos / 60) . " minutos.",
        ]);
    } else {
        $pdo->prepare(
            'UPDATE usuarios SET intentos_fallidos = ? WHERE id_usuario = ?'
        )->execute([$nuevosIntentos, $usuario['id_usuario']]);

        http_response_code(401);
        echo json_encode($errorGenerico);
    }
    exit;
}

/* ── Login exitoso: resetear intentos ───────────────────────────────────── */
$pdo->prepare(
    'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?'
)->execute([$usuario['id_usuario']]);

/* ── Generar JWT ─────────────────────────────────────────────────────────── */
$expiry = Entorno::getInt('JWT_EXPIRY', 3600);
$token  = JWT::generar([
    'sub' => $usuario['id_usuario'],
    'rol' => $usuario['rol'],
]);

/* ── Registrar sesión en SESIONES ────────────────────────────────────────── */
$pdo->prepare(
    'INSERT INTO sesiones (id_usuario, token_sesion, inicio_sesion, expira_en, activa)
     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), 1)'
)->execute([$usuario['id_usuario'], $token, $expiry]);

/* ── Respuesta ───────────────────────────────────────────────────────────── */
http_response_code(200);
echo json_encode([
    'token' => $token,
    'rol'   => $usuario['rol'],
]);
