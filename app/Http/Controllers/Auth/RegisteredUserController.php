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
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.UserModel::class,
            'alias' => 'required|string|max:40|unique:'.UserModel::class.',alias',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
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

        return redirect(route('profile.complete'));
    }
}
