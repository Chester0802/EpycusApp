<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Sin script-src explícito, el CSP cae a default-src 'self' — eso
        // bloquea CUALQUIER <script> inline sin darse cuenta, incluido el
        // que genera @routes de Ziggy (window.Ziggy nunca se define,
        // route() revienta en cada página). El nonce es la forma correcta
        // de permitir justo esos scripts sin abrir 'unsafe-inline'.
        $nonce = base64_encode(random_bytes(16));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // En desarrollo, Vite corre en 127.0.0.1:5173 (origen distinto al
        // de Laravel). Hay que permitir sus scripts, estilos y la conexión
        // WebSocket del HMR; sin esto el navegador los bloquea por CSP y
        // la app no arranca. En producción solo se permite 'self'.
        $viteOrigin = app()->isLocal() ? ' http://127.0.0.1:5173 http://127.0.0.1:5174' : '';
        $viteWs = app()->isLocal() ? ' ws://127.0.0.1:5173 ws://127.0.0.1:5174' : '';

        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; ".
            "script-src 'self' 'nonce-{$nonce}'{$viteOrigin}; ".
            "style-src 'self' 'unsafe-inline'{$viteOrigin}; ".
            "img-src 'self' data:; ".
            "font-src 'self'; ".
            "connect-src 'self' https://api.deepseek.com{$viteOrigin}{$viteWs}; ".
            // Pomodoro/Index.vue embebe un <iframe> de música opcional (botón,
            // nunca automático — ver docs/01-MODULOS.md §3 "Música opcional").
            // Sin frame-src explícito, CSP cae a default-src 'self' y bloquea el
            // iframe entero. youtube-nocookie.com (no youtube.com) es el modo
            // "sin cookies" de YouTube — reduce, no elimina, el tracking; ver la
            // nota de privacidad en docs/06-SEGURIDAD.md §7 antes de tocar esto.
            'frame-src https://www.youtube-nocookie.com; '.
            "frame-ancestors 'none';"
        );

        return $response;
    }
}
