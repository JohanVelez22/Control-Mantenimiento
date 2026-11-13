<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** S-06: cambiar la contraseña desde Usuarios cierra las sesiones abiertas de ese usuario. */
class SesionesCambioPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_que_cambia_la_clave_de_otro_cierra_sus_sesiones(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        foreach (['ses-tec-1', 'ses-tec-2'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $tecnico->id, 'payload' => '', 'last_activity' => time()]);
        }
        DB::table('sessions')->insert(['id' => 'ses-admin', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->put(route('usuarios.update', $tecnico), [
            'name' => $tecnico->name, 'email' => $tecnico->email, 'role' => 'tecnico',
            'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $tecnico->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('id', 'ses-admin')->count());
    }

    public function test_editar_sin_cambiar_clave_no_cierra_sesiones(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        DB::table('sessions')->insert(['id' => 'ses-tec', 'user_id' => $tecnico->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($admin)->put(route('usuarios.update', $tecnico), [
            'name' => 'Nombre nuevo', 'email' => $tecnico->email, 'role' => 'tecnico',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('sessions')->where('user_id', $tecnico->id)->count());
    }
}
