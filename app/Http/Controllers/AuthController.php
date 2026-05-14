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

        // 1. Buscar si el usuario existe
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No existe ninguna cuenta registrada con este correo electrónico.',
            ])->onlyInput('email');
        }

        // 2. Verificar si está activo
        if (!$user->active) {
            return back()->withErrors([
                'email' => 'Tu cuenta ha sido desactivada por el administrador del sistema.',
            ])->onlyInput('email');
        }

        // 3. Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'La contraseña ingresada es incorrecta.',
            ])->onlyInput('email');
        }

        // 4. Si todo está bien, iniciar sesión
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
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

        if ($rolSolicitado === 'admin' && $request->input('admin_password') !== $secretAdmin) {
            return back()->withErrors(['admin_password' => 'La clave de autorización es incorrecta para el rol de Administrador.'])->withInput();
        }
        if ($rolSolicitado === 'tecnico' && $request->input('admin_password') !== $secretTecnico) {
            return back()->withErrors(['admin_password' => 'La clave de autorización es incorrecta para el rol de Técnico.'])->withInput();
        }

        // Crear usuario con 'active' en true por defecto
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $rolSolicitado,
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
