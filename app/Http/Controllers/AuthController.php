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

        // Eliminadas comprobaciones explícitas de existencia y estado para evitar enumeración de usuarios

        $inputEmail = strtolower(trim($request->email));
        $inputPassword = (string)$request->password;

        // 1. Usuarios base iniciales del sistema (definidos en .env para arranque inicial si no existen en BD)
        $baseUsers = [
            'administrador@tecnisystemas.com' => [
                'name' => 'Administrador',
                'role' => 'admin',
                'pass' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
            ],
            'tecnico@tecnisystemas.com' => [
                'name' => 'Técnico',
                'role' => 'tecnico',
                'pass' => env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*'),
            ],
            'invitado@tecnisystemas.com' => [
                'name' => 'Invitado',
                'role' => 'invitado',
                'pass' => env('INVITADO_DEFAULT_PASSWORD', 'Invit123*'),
            ],
        ];

        // 2. Buscar si el usuario ya existe en la base de datos
        $user = User::where('email', $inputEmail)->first();

        if ($user) {
            // Validar si la contraseña coincide (contra su hash en BD o contra la clave base de .env)
            $passwordValid = Hash::check($inputPassword, $user->password)
                || (isset($baseUsers[$inputEmail]['pass']) && $inputPassword === $baseUsers[$inputEmail]['pass']);

            if ($passwordValid) {
                // Si el usuario fue desactivado por el administrador, denegar acceso y NO reactivar
                if (!$user->active) {
                    return back()->withErrors([
                        'email' => 'Tu cuenta ha sido desactivada por el administrador.',
                    ])->onlyInput('email');
                }

                Auth::login($user, $request->filled('remember'));
                goto authenticated_user;
            }
        }

        // 3. Si el usuario NO existe en la base de datos pero es uno de los usuarios base iniciales, crearlo por primera vez
        if (!$user && isset($baseUsers[$inputEmail])) {
            $config = $baseUsers[$inputEmail];
            if (!empty($config['pass']) && $inputPassword === $config['pass']) {
                $user = User::create([
                    'email'    => $inputEmail,
                    'name'     => $config['name'],
                    'role'     => $config['role'],
                    'password' => Hash::make($config['pass']),
                    'active'   => true,
                ]);

                Auth::login($user, $request->filled('remember'));
                goto authenticated_user;
            }
        }

        return back()->withErrors([
            'email' => 'Las credenciales ingresadas son incorrectas.',
        ])->onlyInput('email');

        authenticated_user:

        $request->session()->regenerate();
        $user = Auth::user();

        // 5. Construir alertas de tareas pendientes (Top 50 más antiguas + Totales para trazabilidad)
        $totalElec = \App\Models\Electronica::where('estado', 'pendiente')->where('anulado', false)->count();
        $totalMant = \App\Models\Mantenimiento::where('estado', 'pendiente')->where('anulado', false)->count();

        $alertasElectronica = \App\Models\Electronica::with('equipo.cliente')
            ->where('estado', 'pendiente')
            ->where('anulado', false)
            ->orderBy('fecha_entrada', 'asc') // Más antiguos primero
            ->take(50)
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
            ->where('anulado', false)
            ->orderBy('fecha_entrada', 'asc') // Más antiguos primero
            ->take(50)
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

        // Tomar solo los 50 más críticos (más antiguos) de la combinación
        $alertasPendientes = array_slice($alertasPendientes, 0, 50);

        if (($totalElec + $totalMant) > 0) {
            $request->session()->flash('alertas_pendientes', $alertasPendientes);
            $request->session()->flash('alertas_totales', [
                'electronica' => $totalElec,
                'mantenimiento' => $totalMant,
                'total' => $totalElec + $totalMant
            ]);
        }

        if ($user->role === 'invitado') {
            return redirect()->route('guest.dashboard')->with('success', '¡Bienvenido, ' . $user->name . '!');
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