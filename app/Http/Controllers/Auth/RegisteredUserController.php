<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\DTOs\RegisterUserDTO;
use App\Modules\Identity\Application\UseCases\RegisterUserUseCase;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

final class RegisteredUserController extends Controller
{
    public function __construct(private RegisterUserUseCase $registerUser) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.UserModel::class],
            'alias' => ['required', 'string', 'max:40', 'unique:'.UserModel::class.',alias'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms_accepted' => ['sometimes', 'accepted'],
        ], [
            'name.required' => 'Por favor, ingresa tu nombre completo.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico con formato válido (ej. usuario@universidad.edu.pe).',
            'email.unique' => 'Este correo electrónico ya está registrado en Epycus.',
            'email.max' => 'El correo electrónico es demasiado largo.',
            'alias.required' => 'El alias público es obligatorio para el ranking.',
            'alias.unique' => 'Este alias ya está en uso. Prueba generar uno nuevo con el botón.',
            'alias.max' => 'El alias no debe exceder los 40 caracteres.',
            'password.required' => 'Debes ingresar una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'terms_accepted.required' => 'Debes aceptar los Términos y Condiciones para continuar.',
            'terms_accepted.accepted' => 'Debes aceptar los Términos y Condiciones para continuar.',
        ]);

        $dto = new RegisterUserDTO(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            alias: $request->alias,
        );

        $userDto = $this->registerUser->execute($dto);

        $user = UserModel::find($userDto->id);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('profile.complete'));

    }
}
