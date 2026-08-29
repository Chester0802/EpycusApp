<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_without_client_id_shows_warning(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('warning');
    }

    public function test_google_redirect_with_client_id_redirects_to_google(): void
    {
        config(['services.google.client_id' => 'dummy_google_client_id']);
        config(['services.google.redirect' => 'http://localhost/auth/google/callback']);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }
}
