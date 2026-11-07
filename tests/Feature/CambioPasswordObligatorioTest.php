<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PasswordsConocidas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cuentas con contraseña predeterminada o asignada por un administrador deben definir
 * una propia antes de usar el sistema.
 */
class CambioPasswordObligatorioTest extends TestCase
{
    use RefreshDatabase;

    private function adminConPasswordPredeterminada(): User
    {
        return User::factory()->create([
            'email' => 'administrador@tecnisystemas.com', 'name' => 'Administrador',
            'role' => 'admin', 'active' => true, 'password' => Hash::make('Admin123*'),
        ]);
    }

    public function test_login_con_password_predeterminada_obliga_a_cambiarla(): void
    {
        $admin = $this->adminConPasswordPredeterminada();

        $this->post('/login', ['email' => $admin->email, 'password' => 'Admin123*']);
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->must_change_password);

        $this->get(route('dashboard'))->assertRedirect(route('password.cambiar'));
        $this->get(route('caja.index'))->assertRedirect(route('password.cambiar'));
        $this->getJson(route('backups.config'))->assertForbidden();
        $this->get(route('password.cambiar'))->assertOk()->assertSee('Cambie su contraseña');
    }

    public function test_el_cambio_valida_y_luego_libera_el_acceso(): void
    {
        $admin = $this->adminConPasswordPredeterminada();
        $admin->forceFill(['must_change_password' => true])->save();
        $this->actingAs($admin);

        // Contraseña actual incorrecta
        $this->put(route('password.actualizar'), ['current_password' => 'otra', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertSessionHasErrors('current_password');
        // Contraseña pública
        $this->put(route('password.actualizar'), ['current_password' => 'Admin123*', 'password' => 'Tecni123*', 'password_confirmation' => 'Tecni123*'])
            ->assertSessionHasErrors('password');
        // Contraseña débil
        $this->put(route('password.actualizar'), ['current_password' => 'Admin123*', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($admin->fresh()->must_change_password);

        // Cambio válido
        $this->put(route('password.actualizar'), ['current_password' => 'Admin123*', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertRedirect(route('dashboard'));

        $admin->refresh();
        $this->assertFalse($admin->must_change_password);
        $this->assertTrue(Hash::check('NuevaClave2026', $admin->password));
        $this->assertFalse(Hash::check('Admin123*', $admin->password));
        $this->get(route('dashboard'))->assertOk();
        $this->assertDatabaseHas('eventos', ['accion' => 'password_cambiada', 'modelo_id' => $admin->id]);
    }

    public function test_usuario_creado_por_admin_debe_cambiar_su_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Tecnico', 'email' => 'nuevo@x.com', 'role' => 'tecnico',
            'password' => 'Asignada2026', 'password_confirmation' => 'Asignada2026',
        ])->assertRedirect();

        $this->assertTrue(User::where('email', 'nuevo@x.com')->value('must_change_password'));
    }

    public function test_usuario_sin_cambio_pendiente_no_ve_la_pantalla(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);

        $this->actingAs($admin)->get(route('password.cambiar'))->assertRedirect(route('dashboard'));
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_detecta_password_conocida_en_hash(): void
    {
        $this->assertTrue(PasswordsConocidas::coincideConHash(Hash::make('Invit123*')));
        $this->assertFalse(PasswordsConocidas::coincideConHash(Hash::make('Una-Clave-Propia-2026')));
    }
}
