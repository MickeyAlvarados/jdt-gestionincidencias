<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Crear usuario, empleado y asignar rol en una transacción
        DB::transaction(function () use ($request, &$user) {
            // Crear usuario
            $user = User::create([
                'nombres' => $request->name,
                'apellidos' => '',
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tipo_usuario' => 'docente',
                'role_id' => 5, // DOCENTE
            ]);

            // Asignar rol DOCENTE
            $user->assignRole('DOCENTE');

            // Crear empleado asociado (requerido para el chat)
            Empleado::create([
                'id' => $user->id,
                'idusuarios' => $user->id,
                'idcargos' => 1, // Cargo por defecto: Administrador
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}