<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Support\PasswordsConocidas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio de contraseña obligatorio (contraseña predeterminada o asignada por un administrador).
 */
class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route($request->user()->isInvitado() ? 'guest.dashboard' : 'dashboard');
        }

        return view('auth.cambiar-password');
    }

    public function update(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required', 'string', 'confirmed', 'different:current_password',
                Password::min(8)->mixedCase()->numbers(),
                function ($atributo, $valor, $fallar) {
                    if (PasswordsConocidas::es((string) $valor)) {
                        $fallar('Esa contraseña es pública o demasiado común. Elija otra.');
                    }
                },
            ],
        ], [
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        $usuario->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Cierra las sesiones abiertas con la contraseña anterior en otros equipos
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $usuario->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        try {
            Evento::registrar('password_cambiada', $usuario, null, null, 'El usuario definió una contraseña nueva (cambio obligatorio).');
        } catch (\Throwable) {
            // La auditoría no debe impedir el cambio de contraseña
        }

        return redirect()->route($usuario->isInvitado() ? 'guest.dashboard' : 'dashboard')
            ->with('success', 'Contraseña actualizada. Ya puede usar el sistema.');
    }
}
