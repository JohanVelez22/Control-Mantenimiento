<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // 5. Construir alertas de tareas pendientes
        $alertasElectronica = \App\Models\Electronica::with('equipo.cliente')
            ->where('estado', 'pendiente')
            ->get()
            ->map(function ($e) {
                return [
                    'modulo'     => 'electrónica',
                    'id_orden'   => $e->id_orden,
                    'cliente'    => $e->equipo->cliente->nombre ?? 'N/A',
                    'dispositivo'=> $e->equipo->nombre ?? 'N/A',
                    'estado'     => $e->estado,
                    'dias'       => $e->dias_transcurridos ?? floor(abs(\Carbon\Carbon::parse($e->fecha_entrada)->diffInDays(now()))),
                ];
            })->toArray();

        $alertasMantenimiento = \App\Models\Mantenimiento::with('equipo.cliente')
            ->where('estado', 'pendiente')
            ->get()
            ->map(function ($m) {
                return [
                    'modulo'     => 'mantenimiento',
                    'id_orden'   => $m->id_orden,
                    'cliente'    => $m->equipo->cliente->nombre ?? 'N/A',
                    'dispositivo'=> $m->equipo->nombre ?? 'N/A',
                    'estado'     => $m->estado,
                    'dias'       => floor(abs(\Carbon\Carbon::parse($m->fecha_entrada)->diffInDays(now()))),
                ];
            })->toArray();

        $alertasPendientes = array_merge($alertasElectronica, $alertasMantenimiento);
        usort($alertasPendientes, function ($a, $b) {
            return $b['dias'] <=> $a['dias']; // Ordenar por días descendente (los más antiguos primero)
        });

        if (count($alertasPendientes) > 0) {
            $request->session()->flash('alertas_pendientes', $alertasPendientes);
        }

        return redirect()->intended('/dashboard')->with('success', '¡Bienvenido de nuevo, ' . $user->name . '!');
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