<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

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
        $this->assertEquals('sql', $response->json('latest.extension'));
    }

    public function test_admin_can_trigger_manual_backup_files_zip()
    {
        Configuracion::create([
            'nombre' => 'Tecni Test',
            'backup_max_copias' => 5,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('backups.manual'), [
            'tipo' => 'files',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('latest'));
        $this->assertEquals('zip', $response->json('latest.extension'));
        $this->assertStringStartsWith('backup_bd_multimedia_', $response->json('latest.name'));
    }

    public function test_admin_can_trigger_manual_backup_integral_zip()
    {
        Configuracion::create([
            'nombre' => 'Tecni Test',
            'backup_max_copias' => 5,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('backups.manual'), [
            'tipo' => 'all',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('latest'));
        $this->assertEquals('zip', $response->json('latest.extension'));
        $this->assertStringStartsWith('backup_integral_', $response->json('latest.name'));
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

    public function test_admin_can_restore_sql_backup_and_preserves_audit_events()
    {
        // 1. Crear evento histórico antes de la restauración
        Evento::create([
            'user_id' => $this->admin->id,
            'accion' => 'evento_historico_pre_restore',
            'descripcion' => 'Evento que debe sobrevivir a la restauración para trazabilidad',
            'created_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('eventos', [
            'accion' => 'evento_historico_pre_restore',
        ]);

        // 2. Crear archivo .sql de prueba
        $sqlFile = 'db_test_restore_sample.sql';
        $sqlPath = $this->backupDir.DIRECTORY_SEPARATOR.$sqlFile;
        File::put($sqlPath, "-- SQL test restore file\nSELECT 1;\n");

        // 3. Ejecutar restauración
        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $sqlFile]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($sqlFile, $response->json('restored_file'));

        // 4. Verificar que el evento previo sigue existiendo (trazabilidad preservada)
        $this->assertDatabaseHas('eventos', [
            'accion' => 'evento_historico_pre_restore',
        ]);

        // 5. Verificar que se registró el evento de auditoría de la restauración
        $this->assertDatabaseHas('eventos', [
            'accion' => 'backup_restaurado',
        ]);

        if (File::exists($sqlPath)) {
            File::delete($sqlPath);
        }
    }

    public function test_admin_can_restore_zip_multimedia_backup()
    {
        $zipFile = 'backup_bd_multimedia_test.zip';
        $zipPath = $this->backupDir.DIRECTORY_SEPARATOR.$zipFile;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('database.sql', "-- Test SQL in ZIP\nSELECT 1;\n");
        $zip->addFromString('archivos_publicos/test_media_file.txt', 'Contenido multimedia de prueba');
        $zip->close();

        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $zipFile]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verificar que el archivo multimedia fue copiado a storage/app/public
        $targetFile = storage_path('app/public/test_media_file.txt');
        $this->assertTrue(File::exists($targetFile));
        $this->assertEquals('Contenido multimedia de prueba', File::get($targetFile));

        // Limpiar
        if (File::exists($targetFile)) {
            File::delete($targetFile);
        }
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
    }

    public function test_admin_can_restore_integral_backup_safely()
    {
        $zipFile = 'backup_integral_test_full.zip';
        $zipPath = $this->backupDir.DIRECTORY_SEPARATOR.$zipFile;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('database.sql', "-- Test SQL in Integral\nSELECT 1;\n");
        $zip->addFromString('archivos_publicos/integral_logo.txt', 'Logo de empresa restaurado');
        $zip->addFromString('codigo_fuente/dummy_app.php', '<?php // Safe code');
        $zip->close();

        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $zipFile]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('código fuente en ejecución estable', $response->json('message'));

        $targetFile = storage_path('app/public/integral_logo.txt');
        $this->assertTrue(File::exists($targetFile));

        // Limpiar
        if (File::exists($targetFile)) {
            File::delete($targetFile);
        }
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
    }

    public function test_non_admin_cannot_restore_backup()
    {
        $dummy = 'test_unauthorized.sql';
        $dummyPath = $this->backupDir.DIRECTORY_SEPARATOR.$dummy;
        File::put($dummyPath, '-- Test');

        $response = $this->actingAs($this->tecnico)->postJson(route('backups.restore', ['filename' => $dummy]));
        $response->assertStatus(403);

        if (File::exists($dummyPath)) {
            File::delete($dummyPath);
        }
    }

    public function test_path_traversal_is_blocked_on_restore()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => '../.env']));
        $response->assertStatus(400);
    }

    public function test_zip_slip_inside_archive_is_blocked_on_restore(): void
    {
        $zipFile = 'malicious_zip_slip.zip';
        $zipPath = $this->backupDir.DIRECTORY_SEPARATOR.$zipFile;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('database.sql', "SELECT 1;\n");
        $zip->addFromString('../../evil.txt', 'evil content');
        $zip->close();

        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $zipFile]));

        $response->assertStatus(400);
        $this->assertStringContainsString('Zip Slip', $response->json('message'));

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
    }

    public function test_executable_files_in_zip_are_not_copied_to_public_storage(): void
    {
        $zipFile = 'test_executable_media.zip';
        $zipPath = $this->backupDir.DIRECTORY_SEPARATOR.$zipFile;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('database.sql', "SELECT 1;\n");
        $zip->addFromString('archivos_publicos/valid_logo.png', 'PNG DATA');
        $zip->addFromString('archivos_publicos/webshell.php', '<?php echo "pwned";');
        $zip->close();

        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $zipFile]));

        $response->assertStatus(200);

        // El archivo PNG permitido sí debe existir
        $pngPath = storage_path('app/public/valid_logo.png');
        $this->assertTrue(File::exists($pngPath));

        // El archivo PHP malicioso NO debe haber sido copiado
        $phpPath = storage_path('app/public/webshell.php');
        $this->assertFalse(File::exists($phpPath), 'Archivos ejecutables .php jamás deben copiarse al storage público.');

        // Limpiar
        if (File::exists($pngPath)) {
            File::delete($pngPath);
        }
        if (File::exists($phpPath)) {
            File::delete($phpPath);
        }
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
    }

    public function test_restore_fails_when_file_does_not_exist()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => 'no_existe.sql']));
        $response->assertStatus(404);
    }

    public function test_admin_can_trigger_git_update_when_updated()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.git-update'), [
            'fake_status' => 'updated',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('updated', $response->json('status'));
        $this->assertEquals('Se actualizó correctamente.', $response->json('message'));
    }

    public function test_admin_can_trigger_git_update_when_up_to_date()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.git-update'), [
            'fake_status' => 'up_to_date',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('up_to_date', $response->json('status'));
        $this->assertEquals('Nada por hacer.', $response->json('message'));
    }

    public function test_admin_handles_git_update_when_no_connection()
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.git-update'), [
            'fake_status' => 'no_connection',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('no_connection', $response->json('status'));
        $this->assertEquals('No se pudo establecer conexión con GitHub.', $response->json('message'));
    }

    public function test_non_admin_cannot_trigger_git_update()
    {
        $response = $this->actingAs($this->tecnico)->postJson(route('backups.git-update'));
        $response->assertStatus(403);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->backupDir)) {
            foreach (File::files($this->backupDir) as $file) {
                if (str_contains($file->getFilename(), 'test') || str_contains($file->getFilename(), 'memory')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }
}
