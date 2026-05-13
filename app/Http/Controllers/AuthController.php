<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class AuthController extends Controller
{
    // Muestra la vista de login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Procesa el login validando que el usuario esté ACTIVO
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar login: las credenciales deben coincidir y 'active' debe ser true (1)
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'active' => 1])) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        // Si falla, verificamos si es porque el usuario existe pero está desactivado
        $user = User::where('email', $request->email)->first();
        if ($user && !$user->active) {
            return back()->withErrors([
                'email' => 'Tu cuenta ha sido desactivada por el administrador.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Muestra la vista de registro
    public function showRegister()
    {
        return view('auth.register');
    }

    // Procesa el registro
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'admin_password' => 'nullable|string',
            'role' => 'required|in:admin,tecnico,invitado',
        ]);

        $rolSolicitado = $request->input('role');
        $secretAdmin = env('ROLE_PROMOTE_ADMIN_SECRET', 'Admin2026*');
        $secretTecnico = env('ROLE_PROMOTE_TECNICO_SECRET', 'Tecny2026*');

        $asignado = $rolSolicitado;
        if ($rolSolicitado === 'admin' && $request->input('admin_password') !== $secretAdmin) {
            $asignado = 'invitado';
        }
        if ($rolSolicitado === 'tecnico' && $request->input('admin_password') !== $secretTecnico) {
            $asignado = 'invitado';
        }

        // Crear usuario con 'active' en true por defecto
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $asignado,
            'active' => true, 
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // Cierra la sesión
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
