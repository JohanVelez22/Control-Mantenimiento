<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Test',
        ]);

        $this->tecnico = User::factory()->create([
            'role' => 'tecnico',
            'name' => 'Tecnico Test',
        ]);

        $this->backupDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/backups'));
        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function test_admin_can_get_backup_config_and_files()
    {
        Configuracion::create([
            'nombre' => 'Tecni Test',
            'backup_automatico' => true,
            'backup_frecuencia' => 'daily',
            'backup_hora' => '02:00',
            'backup_max_copias' => 10,
        ]);

        // Crear archivo simulado
        $dummy = $this->backupDir.DIRECTORY_SEPARATOR.'db_test_sample.sql';
        File::put($dummy, '-- Dummy SQL backup');

        $response = $this->actingAs($this->admin)->getJson(route('backups.config'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'config' => [
                'backup_automatico',
                'backup_frecuencia',
                'backup_hora',
                'backup_dia_semana',
                'backup_dia_mes',
                'backup_max_copias',
                'backup_tipo_incluido',
            ],
            'storage_path',
            'files',
            'total_files',
            'total_size',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(10, $response->json('config.backup_max_copias'));

        // Limpiar
        if (File::exists($dummy)) {
            File::delete($dummy);
        }
    }

    public function test_admin_can_save_backup_schedule()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.schedule'), [
            'backup_automatico' => 1,
            'backup_frecuencia' => 'weekly',
            'backup_hora' => '18:00',
            'backup_dia_semana' => 'monday',
            'backup_dia_mes' => 1,
            'backup_max_copias' => 15,
            'backup_tipo_incluido' => 'files',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $config = Configuracion::first();
        $this->assertNotNull($config);
        $this->assertEquals('weekly', $config->backup_frecuencia);
        $this->assertEquals('18:00', $config->backup_hora);
        $this->assertEquals('monday', $config->backup_dia_semana);
        $this->assertEquals(15, $config->backup_max_copias);
        $this->assertEquals('files', $config->backup_tipo_incluido);
    }

    public function test_admin_can_save_backup_schedule_with_multi_days()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.schedule'), [
            'backup_automatico' => 1,
            'backup_frecuencia' => 'weekly',
            'backup_hora' => '02:00',
            'backup_dia_semana' => 'lmv',
            'backup_dia_mes' => 1,
            'backup_max_copias' => 10,
            'backup_tipo_incluido' => 'all',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $config = Configuracion::first();
        $this->assertEquals('lmv', $config->backup_dia_semana);

        // Probar también opción mjs (Martes, Jueves, Sábado)
        $respMjs = $this->actingAs($this->admin)->postJson(route('backups.schedule'), [
            'backup_automatico' => 1,
            'backup_frecuencia' => 'weekly',
            'backup_hora' => '22:00',
            'backup_dia_semana' => 'mjs',
            'backup_dia_mes' => 1,
            'backup_max_copias' => 20,
            'backup_tipo_incluido' => 'db',
        ]);

        $respMjs->assertStatus(200);
        $config->refresh();
        $this->assertEquals('mjs', $config->backup_dia_semana);
    }

    public function test_admin_can_trigger_manual_backup_and_rotation_runs()
    {
        Configuracion::create([
            'nombre' => 'Tecni Test',
            'backup_max_copias' => 5,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('backups.manual'), [
            'tipo' => 'db',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('latest'));
    }

    public function test_admin_can_download_existing_backup_file()
    {
        $testFile = 'db_download_test_file.sql';
        $fullPath = $this->backupDir.DIRECTORY_SEPARATOR.$testFile;
        File::put($fullPath, '-- Test Download Content');

        $response = $this->actingAs($this->admin)->get(route('backups.download', ['filename' => $testFile]));

        $response->assertStatus(200);
        $this->assertEquals('attachment; filename='.$testFile, $response->headers->get('content-disposition'));

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    public function test_path_traversal_is_blocked_on_download()
    {
        $response = $this->actingAs($this->admin)->get(route('backups.download', ['filename' => '../.env']));

        $response->assertStatus(400);
    }

    public function test_admin_can_delete_backup_file()
    {
        $testFile = 'db_delete_test_file.sql';
        $fullPath = $this->backupDir.DIRECTORY_SEPARATOR.$testFile;
        File::put($fullPath, '-- Test Content to Delete');

        $this->assertTrue(File::exists($fullPath));

        $response = $this->actingAs($this->admin)->deleteJson(route('backups.destroy', ['filename' => $testFile]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertFalse(File::exists($fullPath));
    }

    public function test_non_admin_cannot_access_backup_endpoints()
    {
        $resConfig = $this->actingAs($this->tecnico)->getJson(route('backups.config'));
        $resConfig->assertStatus(403);

        $resManual = $this->actingAs($this->tecnico)->postJson(route('backups.manual'));
        $resManual->assertStatus(403);

        $resSchedule = $this->actingAs($this->tecnico)->postJson(route('backups.schedule'), [
            'backup_automatico' => 1,
            'backup_frecuencia' => 'daily',
            'backup_hora' => '02:00',
            'backup_dia_semana' => 'sunday',
            'backup_dia_mes' => 1,
            'backup_max_copias' => 10,
            'backup_tipo_incluido' => 'db',
        ]);
        $resSchedule->assertStatus(403);
    }

    public function test_open_folder_returns_storage_path()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.open-folder'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->backupDir, $response->json('path'));
    }
}
