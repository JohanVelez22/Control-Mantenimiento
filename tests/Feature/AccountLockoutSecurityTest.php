<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AccountLockoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        (new AdminUserSeeder)->run();
    }

    public function test_rotating_ips_cannot_bypass_account_lockout(): void
    {
        $targetEmail = 'administrador@tecnisystemas.com';

        // Realizar 10 intentos fallidos desde 10 IPs distintas
        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "192.168.1.{$i}"])
                ->post(route('login'), [
                    'email' => $targetEmail,
                    'password' => 'wrong_password',
                ]);
        }

        // Intento 11 desde una IP completamente nueva (192.168.1.99)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.99'])
            ->post(route('login'), [
                'email' => $targetEmail,
                'password' => 'wrong_password',
            ]);

        // Debe ser bloqueado por protección a nivel de cuenta
        $response->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Acceso bloqueado', $errorMessage);
    }

    public function test_non_existent_user_login_runs_dummy_hash_without_500_error(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'usuario_inexistente_12345@tecnisystemas.com',
            'password' => 'clave_cualquiera',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('incorrectas', session('errors')->first('email'));
    }
}
