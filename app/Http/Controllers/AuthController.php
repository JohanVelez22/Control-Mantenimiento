<?php

namespace App\Http\Controllers;

use App\Models\Electronica;
use App\Models\Mantenimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Muestra la vista de login
    public function showLogin(Request $request)
    {
        $ipLockoutKey = 'login_ip_locked_until:'.sha1($request->ip());
        $lockedUntil = Cache::get($ipLockoutKey);
        $lockoutSeconds = null;

        if ($lockedUntil && now()->timestamp < $lockedUntil) {
            $lockoutSeconds = max(1, $lockedUntil - now()->timestamp);
        } elseif ($request->session()->has('lockout_seconds')) {
            $lockoutSeconds = (int) $request->session()->get('lockout_seconds');
        }

        return view('auth.login', compact('lockoutSeconds'));
    }

    // Procesa el login validando que el usuario esté ACTIVO
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $rawInput = trim($request->email);
        $inputLower = mb_strtolower($rawInput, 'UTF-8');
        // Quitar acentos para resolver indistintamente "Técnico" o "Tecnico"
        $inputNormalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $inputLower
        );
        $inputPassword = (string) $request->password;

        // Claves de control de intentos y penalización progresiva
        $throttleKey = 'login_attempts:'.sha1($inputNormalized.'|'.$request->ip());
        $lockoutUntilKey = 'login_locked_until:'.sha1($inputNormalized.'|'.$request->ip());
        $lockoutLevelKey = 'login_lockout_level:'.sha1($inputNormalized.'|'.$request->ip());
        $ipLockoutKey = 'login_ip_locked_until:'.sha1($request->ip());

        // Verificar si se encuentra en periodo de bloqueo activo
        $activeLockout = Cache::get($lockoutUntilKey) ?: Cache::get($ipLockoutKey);
        if ($activeLockout && now()->timestamp < $activeLockout) {
            $secondsRemaining = max(1, $activeLockout - now()->timestamp);

            return back()->with([
                'lockout_seconds' => $secondsRemaining,
            ])->withErrors([
                'email' => '🔒 Acceso bloqueado temporalmente por seguridad tras reiterados intentos fallidos.',
            ])->onlyInput('email');
        }

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

        // Mapeo rápido de nombres comunes / roles base a su correo base
        $baseAliases = [
            'admin' => 'administrador@tecnisystemas.com',
            'administrador' => 'administrador@tecnisystemas.com',
            'tecnico' => 'tecnico@tecnisystemas.com',
            'invitado' => 'invitado@tecnisystemas.com',
        ];

        // Resolver posible correo canónico si ingresaron alias o email
        $resolvedEmail = null;
        if (isset($baseAliases[$inputNormalized])) {
            $resolvedEmail = $baseAliases[$inputNormalized];
        } elseif (filter_var($rawInput, FILTER_VALIDATE_EMAIL)) {
            $resolvedEmail = $inputLower;
        }

        // 2. Buscar si el usuario ya existe en la base de datos (por email, por nombre o por sufijo de dominio)
        $user = User::where(function ($query) use ($resolvedEmail, $inputLower, $rawInput) {
            if ($resolvedEmail) {
                $query->where('email', $resolvedEmail);
            } else {
                $query->where('email', $inputLower)
                    ->orWhere('email', $inputLower.'@tecnisystemas.com');
            }
            $query->orWhereRaw('LOWER(name) = ?', [$inputLower])
                ->orWhere('name', $rawInput);
        })->first();

        // Si no se encontró de forma directa, intentar búsqueda tolerante de nombre (sin tildes)
        if (! $user) {
            $user = User::all()->first(function ($u) use ($inputNormalized) {
                $nameNorm = str_replace(
                    ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
                    ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
                    mb_strtolower($u->name, 'UTF-8')
                );

                return $nameNorm === $inputNormalized;
            });
        }

        if ($user) {
            $userEmail = strtolower($user->email);
            // Validar si la contraseña coincide (contra su hash en BD o contra la clave base de .env)
            $passwordValid = Hash::check($inputPassword, $user->password)
                || (isset($baseUsers[$userEmail]['pass']) && $inputPassword === $baseUsers[$userEmail]['pass']);

            if ($passwordValid) {
                // Si el usuario fue desactivado por el administrador, denegar acceso y NO reactivar
                if (! $user->active) {
                    return back()->withErrors([
                        'email' => 'Tu cuenta ha sido desactivada por el administrador.',
                    ])->onlyInput('email');
                }

                Auth::login($user, $request->filled('remember'));
                goto authenticated_user;
            }
        }

        // 3. Si el usuario NO existe en la base de datos pero es uno de los usuarios base iniciales, crearlo por primera vez
        $fallbackBaseEmail = $resolvedEmail ?? (isset($baseUsers[$inputLower]) ? $inputLower : null);
        if (! $user && $fallbackBaseEmail && isset($baseUsers[$fallbackBaseEmail])) {
            $config = $baseUsers[$fallbackBaseEmail];
            if (! empty($config['pass']) && $inputPassword === $config['pass']) {
                $user = User::create([
                    'email' => $fallbackBaseEmail,
                    'name' => $config['name'],
                    'role' => $config['role'],
                    'password' => Hash::make($config['pass']),
                    'active' => true,
                ]);

                Auth::login($user, $request->filled('remember'));
                goto authenticated_user;
            }
        }

        // Nivel de penalización acumulado previamente
        $level = (int) Cache::get($lockoutLevelKey, 0);

        // Si el usuario ya fue bloqueado antes ($level >= 1), 1 solo intento fallido adicional escala al siguiente nivel.
        // Si es la primera vez ($level === 0), se permiten 5 intentos antes del primer bloqueo.
        $threshold = ($level === 0) ? 5 : 1;

        $attempts = (int) Cache::get($throttleKey, 0) + 1;
        Cache::put($throttleKey, $attempts, now()->addMinutes(15));

        if ($attempts >= $threshold) {
            $newLevel = $level + 1;
            Cache::put($lockoutLevelKey, $newLevel, now()->addHours(2));

            $durations = [
                1 => 60,    // 1 minuto
                2 => 180,   // 3 minutos
                3 => 300,   // 5 minutos
                4 => 600,   // 10 minutos
                5 => 900,   // 15 minutos
            ];
            $lockoutSeconds = $durations[$newLevel] ?? 1800; // 30 minutos máximo para nivel 6 o superior

            $lockedUntil = now()->timestamp + $lockoutSeconds;
            Cache::put($lockoutUntilKey, $lockedUntil, now()->addSeconds($lockoutSeconds));
            Cache::put($ipLockoutKey, $lockedUntil, now()->addSeconds($lockoutSeconds));
            Cache::forget($throttleKey);

            $minutosTexto = match ($newLevel) {
                1 => '1 minuto',
                2 => '3 minutos',
                3 => '5 minutos',
                4 => '10 minutos',
                5 => '15 minutos',
                default => '30 minutos',
            };

            $motivo = ($newLevel === 1)
                ? 'Has superado el límite de 5 intentos fallidos.'
                : 'Intento fallido tras bloqueo previo.';

            return back()->with([
                'lockout_seconds' => $lockoutSeconds,
            ])->withErrors([
                'email' => "⏳ {$motivo} Tu acceso ha sido bloqueado por {$minutosTexto} como medida de seguridad.",
            ])->onlyInput('email');
        }

        $remainingAttempts = $threshold - $attempts;
        $attemptMsg = $remainingAttempts === 1 ? 'te queda 1 intento' : "te quedan {$remainingAttempts} intentos";

        return back()->withErrors([
            'email' => "Las credenciales ingresadas son incorrectas ({$attemptMsg}).",
        ])->onlyInput('email');

        authenticated_user:

        // Limpiar contadores de throttling y bloqueos al autenticarse exitosamente
        Cache::forget($throttleKey);
        Cache::forget($lockoutUntilKey);
        Cache::forget($lockoutLevelKey);
        Cache::forget($ipLockoutKey);

        $request->session()->regenerate();
        $user = Auth::user();

        // 5. Construir alertas de tareas pendientes (Top 50 más antiguas + Totales para trazabilidad)
        $totalElec = Electronica::where('estado', 'pendiente')->where('anulado', false)->count();
        $totalMant = Mantenimiento::where('estado', 'pendiente')->where('anulado', false)->count();

        $alertasElectronica = Electronica::with('equipo.cliente')
            ->where('estado', 'pendiente')
            ->where('anulado', false)
            ->orderBy('fecha_entrada', 'asc') // Más antiguos primero
            ->take(50)
            ->get()
            ->map(function ($e) {
                return [
                    'modulo' => 'electrónica',
                    'id_orden' => $e->id_orden,
                    'cliente' => $e->equipo->cliente->nombre ?? 'N/A',
                    'dispositivo' => $e->equipo->nombre ?? 'N/A',
                    'estado' => $e->estado,
                    'dias' => $e->dias_transcurridos ?? floor(abs(Carbon::parse($e->fecha_entrada)->diffInDays(now()))),
                ];
            })->toArray();

        $alertasMantenimiento = Mantenimiento::with('equipo.cliente')
            ->where('estado', 'pendiente')
            ->where('anulado', false)
            ->orderBy('fecha_entrada', 'asc') // Más antiguos primero
            ->take(50)
            ->get()
            ->map(function ($m) {
                return [
                    'modulo' => 'mantenimiento',
                    'id_orden' => $m->id_orden,
                    'cliente' => $m->equipo->cliente->nombre ?? 'N/A',
                    'dispositivo' => $m->equipo->nombre ?? 'N/A',
                    'estado' => $m->estado,
                    'dias' => floor(abs(Carbon::parse($m->fecha_entrada)->diffInDays(now()))),
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
                'total' => $totalElec + $totalMant,
            ]);
        }

        if ($user->role === 'invitado') {
            return redirect()->route('guest.dashboard')->with('success', '¡Bienvenido, '.$user->name.'!');
        }

        return redirect()->intended('/dashboard')->with('success', '¡Bienvenido de nuevo, '.$user->name.'!');
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
