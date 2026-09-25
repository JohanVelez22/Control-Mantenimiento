<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_intentos_fallidos_muestran_contador_de_intentos_restantes(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('te quedan 4 intentos', session('errors')->first('email'));
        $this->assertFalse(Auth::check());
    }

    public function test_bloqueo_a_los_5_intentos_por_1_minuto(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->post(route('login'), [
                'email' => 'admin@tecnisystemas.com',
                'password' => 'clave_erronea',
            ]);
        }

        // 5to intento
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('1 minuto', session('errors')->first('email'));
        $this->assertEquals(60, session('lockout_seconds'));
    }

    public function test_intento_durante_bloqueo_es_rechazado_con_tiempo_restante(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login'), [
                'email' => 'admin@tecnisystemas.com',
                'password' => 'clave_erronea',
            ]);
        }

        // Intento número 6 dentro del minuto de bloqueo
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Acceso bloqueado', session('errors')->first('email'));
        $this->assertNotNull(session('lockout_seconds'));
    }

    public function test_reincidencia_escala_a_3_minutos_y_luego_a_5_minutos(): void
    {
        // 1er ciclo: 5 intentos -> 1 minuto
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login'), [
                'email' => 'admin@tecnisystemas.com',
                'password' => 'clave_erronea',
            ]);
        }
        $this->assertEquals(60, session('lockout_seconds'));

        // Simular que expira el primer bloqueo
        $lockoutUntilKey = 'login_locked_until:'.sha1('admin@tecnisystemas.com|127.0.0.1');
        $ipLockoutKey = 'login_ip_locked_until:'.sha1('127.0.0.1');
        Cache::forget($lockoutUntilKey);
        Cache::forget($ipLockoutKey);

        // 2do ciclo: tras desbloqueo, 1 solo intento fallido escala a 3 minutos (180s)
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertEquals(180, session('lockout_seconds'));

        // Simular expiración del segundo bloqueo
        Cache::forget($lockoutUntilKey);
        Cache::forget($ipLockoutKey);

        // 3er ciclo: 1 intento fallido -> escala a 5 minutos (300s)
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertEquals(300, session('lockout_seconds'));

        // Simular expiración del tercer bloqueo
        Cache::forget($lockoutUntilKey);
        Cache::forget($ipLockoutKey);

        // 4to ciclo: 1 intento fallido -> escala a 10 minutos (600s)
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertEquals(600, session('lockout_seconds'));

        // Simular expiración del cuarto bloqueo
        Cache::forget($lockoutUntilKey);
        Cache::forget($ipLockoutKey);

        // 5to ciclo: 1 intento fallido -> escala a 15 minutos (900s)
        $response = $this->post(route('login'), [
            'email' => 'admin@tecnisystemas.com',
            'password' => 'clave_erronea',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertEquals(900, session('lockout_seconds'));
    }

    public function test_login_correcto_resetea_todos_los_intentos_y_penalizaciones(): void
    {
        // 3 intentos fallidos
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('login'), [
                'email' => 'Administrador',
                'password' => 'clave_erronea',
            ]);
        }

        // Intento correcto con credenciales de admin por defecto
        $response = $this->post(route('login'), [
            'email' => 'Administrador',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());

        // Verificar que el throttle key está limpio en cache
        $inputNormalized = 'administrador';
        $throttleKey = 'login_attempts:'.sha1($inputNormalized.'|127.0.0.1');
        $this->assertNull(Cache::get($throttleKey));
    }
}
