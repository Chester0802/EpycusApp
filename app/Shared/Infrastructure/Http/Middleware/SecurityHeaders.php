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

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $viteOrigin = app()->isLocal() ? ' http://127.0.0.1:5173 http://127.0.0.1:5174' : '';
        $viteWs = app()->isLocal() ? ' ws://127.0.0.1:5173 ws://127.0.0.1:5174' : '';

        if (app()->isLocal()) {
            $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://accounts.google.com{$viteOrigin}";
        } else {
            $scriptSrc = "'self' 'nonce-{$nonce}' https://accounts.google.com https://*.gstatic.com";
        }

        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; ".
            "script-src {$scriptSrc}; ".
            "style-src 'self' 'unsafe-inline' https://accounts.google.com{$viteOrigin}; ".
            "img-src 'self' data: https://*.googleusercontent.com https://*.gstatic.com; ".
            "font-src 'self' data:; ".
            "connect-src 'self' https://accounts.google.com https://api.deepseek.com{$viteOrigin}{$viteWs}; ".
            'frame-src https://accounts.google.com https://www.youtube-nocookie.com; '.
            "frame-ancestors 'self' https://epycus.es https://app.epycus.es;"
        );

        return $response;
    }
}

