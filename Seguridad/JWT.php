<?php
namespace Seguridad;

use Configuracion\Entorno;

class JWT
{
    /* ── Generar ─────────────────────────────────────────────────────── */
    public static function generar(array $payload): string
    {
        $expiry  = Entorno::getInt('JWT_EXPIRY', 3600);
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + $expiry,
        ]);

        $header = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body   = self::base64url(json_encode($payload));
        $firma  = self::base64url(self::firmar("{$header}.{$body}"));

        return "{$header}.{$body}.{$firma}";
    }

    /* ── Verificar ───────────────────────────────────────────────────── */
    public static function verificar(string $token): ?array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) return null;

        [$header, $body, $firma] = $partes;

        $firmaEsperada = self::base64url(self::firmar("{$header}.{$body}"));
        if (!hash_equals($firmaEsperada, $firma)) return null;

        $payload = json_decode(self::base64urlDecode($body), true);
        if (!$payload) return null;

        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }

    /* ── Extraer userId del header Authorization ─────────────────────── */
    public static function getUserId(): ?int
    {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION']
                 ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                 ?? '';

        if ($cabecera === '') {
            $headers  = function_exists('getallheaders') ? getallheaders() : [];
            $cabecera = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!str_starts_with($cabecera, 'Bearer ')) return null;

        $token   = substr($cabecera, 7);
        $payload = self::verificar($token);

        return isset($payload['sub']) ? (int) $payload['sub'] : null;
    }

    /* ── Helpers privados ────────────────────────────────────────────── */
    private static function firmar(string $datos): string
    {
        return hash_hmac('sha256', $datos, Entorno::get('JWT_SECRET'), true);
    }

    private static function base64url(string $datos): string
    {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $datos): string
    {
        return base64_decode(
            strtr($datos, '-_', '+/') . str_repeat('=', (4 - strlen($datos) % 4) % 4)
        );
    }
}