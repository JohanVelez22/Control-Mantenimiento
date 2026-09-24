<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginFlexibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_con_nombre_administrador_sin_correo(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'Administrador',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin', Auth::user()->role);
    }

    public function test_login_con_alias_admin(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'admin',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_con_nombre_tecnico_sin_tildes(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'Tecnico',
            'password' => env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('tecnico', Auth::user()->role);
    }

    public function test_login_con_nombre_tecnico_con_tildes(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'Técnico',
            'password' => env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_con_nombre_invitado(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'Invitado',
            'password' => env('INVITADO_DEFAULT_PASSWORD', 'Invit123*'),
        ]);

        $response->assertRedirect(route('guest.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('invitado', Auth::user()->role);
    }

    public function test_login_con_correo_completo(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'administrador@tecnisystemas.com',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_usuario_personalizado_por_nombre(): void
    {
        $customUser = User::create([
            'name'     => 'Carlos Perez',
            'email'    => 'cperez@empresa.com',
            'password' => Hash::make('ClaveSegura2026*'),
            'role'     => 'tecnico',
            'active'   => true,
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'Carlos Perez',
            'password' => 'ClaveSegura2026*',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($customUser->id, Auth::id());
    }

    public function test_login_credenciales_invalidas_retorna_error(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'Administrador',
            'password' => 'ClaveTotalmenteIncorrecta',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }
}
