<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class SecurityHardeningAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected User $invitado;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Security',
            'email' => 'admin_sec@test.com',
            'active' => true,
        ]);

        $this->tecnico = User::factory()->create([
            'role' => 'tecnico',
            'name' => 'Tecnico Security',
            'email' => 'tecnico_sec@test.com',
            'active' => true,
        ]);

        $this->invitado = User::factory()->create([
            'role' => 'invitado',
            'name' => 'Invitado Security',
            'email' => 'invitado_sec@test.com',
            'active' => true,
        ]);

        $this->backupDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/backups'));
        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar archivos de prueba en backups
        if (File::exists($this->backupDir)) {
            $testFiles = File::glob($this->backupDir.DIRECTORY_SEPARATOR.'sec_test_*');
            foreach ($testFiles as $f) {
                if (File::isFile($f)) {
                    File::delete($f);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Verifica que el rol invitado esté completamente aislado de rutas mutantes y administrativas.
     */
    public function test_invitado_role_blocked_from_all_mutations_and_administrative_routes(): void
    {
        // 1. Peticiones HTTP estándar deben ser redirigidas al dashboard con mensaje de error
        $respClientes = $this->actingAs($this->invitado)->post(route('clientes.store'), [
            'nombre' => 'Cliente No Permitido',
            'telefono' => '1234567',
        ]);
        $respClientes->assertRedirect(route('dashboard'));

        $respEquipos = $this->actingAs($this->invitado)->post(route('equipos.store'), [
            'nombre' => 'Equipo No Permitido',
        ]);
        $respEquipos->assertRedirect(route('dashboard'));

        $respStocks = $this->actingAs($this->invitado)->post(route('stocks.store'), [
            'nombre' => 'Stock No Permitido',
        ]);
        $respStocks->assertRedirect(route('dashboard'));

        $respCierre = $this->actingAs($this->invitado)->post(route('cierre.store'), [
            'fecha' => now()->toDateString(),
        ]);
        $respCierre->assertRedirect(route('dashboard'));

        // 2. Peticiones JSON / AJAX deben recibir estrictamente HTTP 403 Forbidden
        $respClientesJson = $this->actingAs($this->invitado)->postJson(route('clientes.store'), [
            'nombre' => 'Cliente Hack Json',
        ]);
        $respClientesJson->assertForbidden();

        $respBackupManual = $this->actingAs($this->invitado)->postJson(route('backups.manual'));
        $respBackupManual->assertForbidden();
    }

    /**
     * Verifica que el rol técnico no pueda acceder a ninguna acción de gestión de respaldos.
     */
    public function test_tecnico_role_strictly_forbidden_from_backup_actions(): void
    {
        $this->actingAs($this->tecnico)->getJson(route('backups.config'))->assertForbidden();
        $this->actingAs($this->tecnico)->postJson(route('backups.manual'))->assertForbidden();
        $this->actingAs($this->tecnico)->postJson(route('backups.schedule'), [])->assertForbidden();
        $this->actingAs($this->tecnico)->getJson(route('backups.download', ['filename' => 'sec_sample.sql']))->assertForbidden();
        $this->actingAs($this->tecnico)->deleteJson(route('backups.destroy', ['filename' => 'sec_sample.sql']))->assertForbidden();
        $this->actingAs($this->tecnico)->postJson(route('backups.restore', ['filename' => 'sec_sample.sql']))->assertForbidden();
        $this->actingAs($this->tecnico)->postJson(route('backups.git-update'))->assertForbidden();
        $this->actingAs($this->tecnico)->postJson(route('backups.open-folder'))->assertForbidden();
    }

    /**
     * Verifica que intentos de Path Traversal en descargas y eliminación de backups sean rechazados con 400.
     */
    public function test_backup_path_traversal_attempts_are_strictly_rejected(): void
    {
        // Intento con '..'
        $resDownload = $this->actingAs($this->admin)->get(route('backups.download', ['filename' => '..%2F..%2F..%2Fetc%2Fpasswd']));
        $this->assertTrue(in_array($resDownload->status(), [400, 404]), 'Debe rechazar path traversal');

        $resDestroy = $this->actingAs($this->admin)->deleteJson(route('backups.destroy', ['filename' => '..%2F..%2Fconfig.php']));
        $this->assertEquals(400, $resDestroy->status());
        $this->assertFalse($resDestroy->json('success'));

        $resRestore = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => '..%2F..%2Fhack.sql']));
        $this->assertEquals(400, $resRestore->status());
        $this->assertFalse($resRestore->json('success'));
    }

    /**
     * Verifica la mitigación contra Zip Slip durante la restauración de respaldos empaquetados.
     */
    public function test_backup_zip_slip_is_blocked_safely(): void
    {
        $zipFileName = 'sec_test_zipslip.zip';
        $zipFilePath = $this->backupDir.DIRECTORY_SEPARATOR.$zipFileName;

        // Construir un ZIP con entrada que contiene '..'
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('../evil_script.php', '<?php phpinfo(); ?>');
            $zip->addFromString('database.sql', '-- Safe DB SQL');
            $zip->close();
        }

        $this->assertFileExists($zipFilePath);

        $response = $this->actingAs($this->admin)->postJson(route('backups.restore', ['filename' => $zipFileName]));

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('Zip Slip detectado', $response->json('message'));
    }

    /**
     * Verifica que open-folder retorne respuesta JSON segura sin generar llamadas Shell en tests.
     */
    public function test_open_folder_returns_safe_json_without_host_execution_in_testing(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('backups.open-folder'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('opened'));
        $this->assertEquals($this->backupDir, $response->json('path'));
        $this->assertNotNull($response->json('message'));
    }

    /**
     * Verifica que al cambiar la contraseña de un usuario se rote su remember_token para invalidar sesiones previas.
     */
    public function test_user_password_change_rotates_remember_token(): void
    {
        $user = User::factory()->create([
            'role' => 'tecnico',
            'password' => Hash::make('OldPass1234!'),
            'remember_token' => 'original_token_to_be_rotated_123',
        ]);

        $response = $this->actingAs($this->admin)->put(route('usuarios.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'tecnico',
            'password' => 'NewPass5678!',
            'password_confirmation' => 'NewPass5678!',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $user->refresh();

        $this->assertTrue(Hash::check('NewPass5678!', $user->password));
        $this->assertNotEquals('original_token_to_be_rotated_123', $user->remember_token);
        $this->assertNotEmpty($user->remember_token);
        $this->assertEquals(60, strlen($user->remember_token));
    }

    /**
     * Verifica que el mecanismo de defensa contra ataques de fuerza bruta en login escale el tiempo de bloqueo.
     */
    public function test_brute_force_login_escalation_locks_user_temporarily(): void
    {
        Cache::flush();

        $email = 'brute_force_victim@tecnisystemas.com';
        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('SuperSecret123!'),
            'active' => true,
        ]);

        // Simular 5 intentos fallidos
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->post(route('login'), [
                'email' => $email,
                'password' => 'WrongPassword'.$i,
            ]);
            $resp->assertSessionHasErrors('email');
        }

        // El 5to intento (o siguiente) debe activar el bloqueo
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            mb_strtolower($email, 'UTF-8')
        );
        $lockoutUntilKey = 'login_locked_until:'.sha1($normalized.'|127.0.0.1');

        $this->assertNotNull(Cache::get($lockoutUntilKey), 'El usuario debe estar bloqueado tras 5 intentos fallidos.');

        // Un nuevo intento debe devolver mensaje de bloqueo temporal
        $lockedResp = $this->post(route('login'), [
            'email' => $email,
            'password' => 'WrongPasswordAgain',
        ]);
        $lockedResp->assertSessionHas('lockout_seconds');
    }
}
