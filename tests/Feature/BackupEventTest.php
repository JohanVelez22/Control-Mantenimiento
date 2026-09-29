<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackupEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_backup_command_registers_backup_event()
    {
        $exitCode = Artisan::call('app:backup-db');
        $this->assertEquals(0, $exitCode);

        $evento = Evento::where('accion', 'backup')->latest()->first();
        $this->assertNotNull($evento);
        $this->assertNull($evento->modelo_tipo);
        $this->assertNull($evento->modelo_id);

        $viejos = is_array($evento->valores_antiguos) ? $evento->valores_antiguos : json_decode($evento->valores_antiguos, true);
        $nuevos = is_array($evento->valores_nuevos) ? $evento->valores_nuevos : json_decode($evento->valores_nuevos, true);

        $this->assertArrayHasKey('Archivo Principal', $viejos);
        $this->assertArrayHasKey('Tipo de Respaldo', $viejos);
        $this->assertArrayHasKey('Resultado', $nuevos);
        $this->assertStringContainsString('exitosamente', $nuevos['Resultado']);
        $this->assertEquals('Sistema (Cron Nocturno)', $nuevos['Ejecutado Por']);
    }

    public function test_admin_can_trigger_backup_via_web_and_it_records_admin_user()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Super Administrador',
        ]);

        $response = $this->actingAs($admin)->post(route('eventos.backup'));
        $response->assertRedirect(route('eventos.index'));
        $response->assertSessionHas('success');

        $evento = Evento::where('accion', 'backup')->latest()->first();
        $this->assertNotNull($evento);

        $nuevos = is_array($evento->valores_nuevos) ? $evento->valores_nuevos : json_decode($evento->valores_nuevos, true);
        $this->assertEquals('Super Administrador', $nuevos['Ejecutado Por']);
    }

    public function test_non_admin_cannot_trigger_backup()
    {
        $tecnico = User::factory()->create([
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($tecnico)->post(route('eventos.backup'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_guest_cannot_trigger_backup()
    {
        $response = $this->post(route('eventos.backup'));
        $response->assertRedirect(route('login'));
    }

    public function test_eventos_index_renders_backup_event_and_filter_works()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Evento::create([
            'user_id' => $admin->id,
            'accion' => 'backup',
            'modelo_tipo' => null,
            'modelo_id' => null,
            'valores_antiguos' => ['Archivo Principal' => 'db_test_2026.sql', 'Tipo de Respaldo' => 'Base de Datos (.sql)'],
            'valores_nuevos' => ['Resultado' => '✅ Respaldo generado exitosamente', 'Ejecutado Por' => $admin->name],
            'descripcion' => 'Copia de seguridad del sistema realizada exitosamente (15.5 KB).',
            'ip_direccion' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get(route('eventos.index', [
            'accion' => 'backup',
            'fecha_desde' => now()->format('Y-m-d'),
            'fecha_hasta' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('💾 Backup');
        $response->assertSee('Copia de seguridad del sistema realizada exitosamente');

        $resEmpresa = $this->actingAs($admin)->get(route('configuracion.index'));
        $resEmpresa->assertStatus(200);
        $resEmpresa->assertSee('Generar Respaldo');
    }
}
