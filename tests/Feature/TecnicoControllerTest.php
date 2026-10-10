<?php

namespace Tests\Feature;

use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TecnicoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnicoUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnicoUser = User::factory()->create(['role' => 'tecnico']);
    }

    public function test_admin_can_view_tecnicos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tecnicos.index'));
        $response->assertStatus(200);
        $response->assertSee('Técnicos');
    }

    public function test_tecnico_can_view_tecnicos_index(): void
    {
        $response = $this->actingAs($this->tecnicoUser)->get(route('tecnicos.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_tecnicos_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('tecnicos.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_tecnico(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Juan Técnico',
            'identificacion' => '12345678',
            'especialidad' => 'Laptops',
            'movil' => '3001234567',
            'email' => 'juan.tecnico@test.com',
        ]);

        $response->assertRedirect(route('tecnicos.index'));
        $this->assertDatabaseHas('tecnicos', [
            'nombre' => 'Juan Técnico',
            'identificacion' => '12345678',
        ]);
    }

    public function test_tecnico_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Sin Identificacion',
        ]);

        $response->assertSessionHasErrors(['identificacion', 'especialidad', 'movil']);
    }

    public function test_tecnico_creation_rejects_duplicate_identificacion(): void
    {
        Tecnico::create([
            'nombre' => 'Original',
            'identificacion' => '11111111',
            'especialidad' => 'General',
            'movil' => '3001111111',
        ]);

        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Duplicado',
            'identificacion' => '11111111',
            'especialidad' => 'Laptops',
            'movil' => '3002222222',
        ]);

        $response->assertSessionHasErrors(['identificacion']);
    }

    public function test_admin_can_edit_tecnico(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Original',
            'identificacion' => '22222222',
            'especialidad' => 'General',
            'movil' => '3003333333',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tecnicos.edit', $tecnico));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_tecnico(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Original',
            'identificacion' => '33333333',
            'especialidad' => 'General',
            'movil' => '3004444444',
        ]);

        $response = $this->actingAs($this->admin)->put(route('tecnicos.update', $tecnico), [
            'nombre' => 'Actualizado',
            'identificacion' => '33333333',
            'especialidad' => 'Laptops',
            'movil' => '3005555555',
            'email' => 'actualizado@test.com',
        ]);

        $response->assertRedirect(route('tecnicos.index'));
        $tecnico->refresh();
        $this->assertEquals('Actualizado', $tecnico->nombre);
        $this->assertEquals('Laptops', $tecnico->especialidad);
    }

    public function test_admin_can_anular_tecnico(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Para Anular',
            'identificacion' => '44444444',
            'especialidad' => 'General',
            'movil' => '3006666666',
        ]);

        $response = $this->actingAs($this->admin)->post(route('tecnicos.anular', $tecnico), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $tecnico->refresh();
        $this->assertFalse($tecnico->active);
    }

    public function test_admin_can_reactivar_tecnico(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Para Reactivar',
            'identificacion' => '55555555',
            'especialidad' => 'General',
            'movil' => '3007777777',
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tecnicos.anular', $tecnico), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $tecnico->refresh();
        $this->assertTrue($tecnico->active);
    }

    public function test_tecnico_requires_admin_password_to_anular_tecnico(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Tecnico Anular',
            'identificacion' => '66666666',
            'especialidad' => 'General',
            'movil' => '3008888888',
        ]);

        $response = $this->actingAs($this->tecnicoUser)->post(route('tecnicos.anular', $tecnico), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $tecnico->refresh();
        $this->assertFalse($tecnico->active);
    }

    public function test_tecnico_photo_upload_rejects_svg(): void
    {
        Storage::fake('public');

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('tecnico.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Con SVG',
            'identificacion' => '77777777',
            'especialidad' => 'General',
            'movil' => '3009999999',
            'photo' => $file,
        ]);

        $response->assertSessionHasErrors(['photo']);
        $this->assertNull(Tecnico::where('identificacion', '77777777')->first());
    }

    public function test_tecnico_photo_upload_accepts_png_under_5mb(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('tecnico.png')->size(4000);

        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Con PNG',
            'identificacion' => '88888888',
            'especialidad' => 'General',
            'movil' => '3001010101',
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $tecnico = Tecnico::where('identificacion', '88888888')->first();
        $this->assertNotNull($tecnico);
        $this->assertNotNull($tecnico->photo);
    }
}