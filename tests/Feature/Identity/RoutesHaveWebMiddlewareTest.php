<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * app/Modules/Identity/Presentation/routes.php se carga vía
 * IdentityServiceProvider::loadRoutesFrom(), que NO hereda el grupo 'web'
 * que bootstrap/app.php aplica automáticamente a routes/web.php. Sin 'web'
 * explícito, no hay sesión/CSRF y auth() falla en silencio con 401 pese a
 * que el usuario esté logueado de verdad — un bug real de la Fase 0 que
 * ningún test con actingAs() detecta, porque actingAs() nunca pasa por el
 * middleware de sesión basado en cookies. Esta prueba sí lo detecta:
 * revisa el middleware registrado, no el comportamiento simulado.
 */
class RoutesHaveWebMiddlewareTest extends TestCase
{
    public function test_identity_module_routes_include_the_web_middleware_group(): void
    {
        $routeNames = ['profile.complete', 'consent.store', 'preferences.update'];

        foreach ($routeNames as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "La ruta '{$name}' no está registrada.");
            $this->assertContains(
                'web',
                $route->gatherMiddleware(),
                "La ruta '{$name}' no tiene el middleware 'web' — sin él no hay sesión ni CSRF reales.",
            );
        }
    }
}
