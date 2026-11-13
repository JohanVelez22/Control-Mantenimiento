<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PasswordsConocidas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

    /** S-05: sin cambio pendiente no se puede fijar otra contraseña (cuenta invitado compartida). */
    public function test_sin_cambio_pendiente_no_se_puede_actualizar_la_password(): void
    {
        $invitado = User::factory()->create(['role' => 'invitado', 'active' => true, 'must_change_password' => false, 'password' => Hash::make('Compartida-2026')]);

        $this->actingAs($invitado)->put(route('password.actualizar'), [
            'current_password' => 'Compartida-2026', 'password' => 'Secuestro2026X', 'password_confirmation' => 'Secuestro2026X',
        ])->assertForbidden();

        $this->assertTrue(Hash::check('Compartida-2026', $invitado->fresh()->password));
    }

    /** S-01: el dueño fija su contraseña desde la consola, sin pasar por la web. */
    public function test_restablecer_password_desde_consola(): void
    {
        $admin = $this->adminConPasswordPredeterminada();

        $this->artisan('app:restablecer-password', ['email' => $admin->email])
            ->expectsQuestion("Nueva contraseña para {$admin->email} (no se mostrará)", 'Admin123*')
            ->expectsQuestion('Repita la contraseña', 'Admin123*')
            ->assertExitCode(1);
        $this->assertTrue(Hash::check('Admin123*', $admin->fresh()->password), 'Una contraseña publicada no debe aceptarse');

        $this->artisan('app:restablecer-password', ['email' => $admin->email])
            ->expectsQuestion("Nueva contraseña para {$admin->email} (no se mostrará)", 'Propia-Clave-2026')
            ->expectsQuestion('Repita la contraseña', 'Propia-Clave-2026')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('Propia-Clave-2026', $admin->fresh()->password));
        $this->assertFalse((bool) $admin->fresh()->must_change_password);
    }

    /** F-01: el invitado no recibe notificaciones con datos de clientes ni en la página 404. */
    public function test_invitado_no_ve_notificaciones_en_pagina_de_error(): void
    {
        $this->artisan('db:seed', ['--force' => true]);
        Artisan::call('app:seed-demo-data', ['--no-interaction' => true]);
        $invitado = User::where('role', 'invitado')->first();
        $invitado->update(['must_change_password' => false]);

        $html = $this->actingAs($invitado)->get('/ruta-que-no-existe-xyz')->assertNotFound()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\$\s?\d{1,3}(\.\d{3})+/', $html, 'La 404 expone saldos y montos al invitado');
    }
}
