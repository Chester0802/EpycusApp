<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'alias' => 'test_user',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms_accepted' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('profile.complete', absolute: false));
    }

    public function test_registration_validation_errors_return_spanish_messages(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'alias' => '',
            'password' => '',
            'password_confirmation' => '',
            'terms_accepted' => false,
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Por favor, ingresa tu nombre completo.',
            'email' => 'El correo electrónico es obligatorio.',
            'alias' => 'El alias público es obligatorio para el ranking.',
            'password' => 'Debes ingresar una contraseña.',
            'terms_accepted' => 'Debes aceptar los Términos y Condiciones para continuar.',
        ]);
    }
}
