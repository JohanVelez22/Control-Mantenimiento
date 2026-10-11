<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginFlexibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new \Database\Seeders\AdminUserSeeder)->run();
    }

    public function test_login_con_nombre_administrador_sin_correo(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'Administrador',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin', Auth::user()->role);
    }

    public function test_login_con_alias_admin(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_con_nombre_tecnico_sin_tildes(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'Tecnico',
            'password' => env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('tecnico', Auth::user()->role);
    }

    public function test_login_con_nombre_tecnico_con_tildes(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'Técnico',
            'password' => env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_con_nombre_invitado(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'Invitado',
            'password' => env('INVITADO_DEFAULT_PASSWORD', 'Invit123*'),
        ]);

        $response->assertRedirect(route('guest.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals('invitado', Auth::user()->role);
    }

    public function test_login_con_correo_completo(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'administrador@tecnisystemas.com',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_login_usuario_personalizado_por_nombre(): void
    {
        $customUser = User::create([
            'name' => 'Carlos Perez',
            'email' => 'cperez@empresa.com',
            'password' => Hash::make('ClaveSegura2026*'),
            'role' => 'tecnico',
            'active' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'Carlos Perez',
            'password' => 'ClaveSegura2026*',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($customUser->id, Auth::id());
    }

    public function test_login_credenciales_invalidas_retorna_error(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'Administrador',
            'password' => 'ClaveTotalmenteIncorrecta',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }

    public function test_cambio_de_contrasena_invalida_contrasena_por_defecto(): void
    {
        $admin = User::where('email', 'administrador@tecnisystemas.com')->first();
        $admin->password = Hash::make('NuevaClaveSuperSegura2026*');
        $admin->save();

        // Intento con clave default ya no debe funcionar (backdoor cerrado)
        $response = $this->post(route('login'), [
            'email' => 'administrador@tecnisystemas.com',
            'password' => 'Admin123*',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());

        // Intento con nueva clave debe tener éxito
        $response2 = $this->post(route('login'), [
            'email' => 'administrador@tecnisystemas.com',
            'password' => 'NuevaClaveSuperSegura2026*',
        ]);
        $response2->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
    }

    public function test_usuario_inexistente_no_se_crea_automaticamente_en_login(): void
    {
        // Si se elimina el administrador de la BD
        User::where('email', 'administrador@tecnisystemas.com')->delete();
        $this->assertNull(User::where('email', 'administrador@tecnisystemas.com')->first());

        // Un intento de login con credenciales por defecto debe fallar y NO recrear la cuenta
        $response = $this->post(route('login'), [
            'email' => 'administrador@tecnisystemas.com',
            'password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
        $this->assertNull(User::where('email', 'administrador@tecnisystemas.com')->first());
    }
}
