<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'active' => true,
        ]);
        $this->tecnico = User::create([
            'name' => 'Tecnico Test',
            'email' => 'tecnico@test.com',
            'password' => Hash::make('password'),
            'role' => 'tecnico',
            'active' => true,
        ]);
    }

    public function test_admin_can_view_usuarios_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('usuarios.index'));
        $response->assertStatus(200);
        $response->assertSee('Usuarios');
    }

    public function test_tecnico_cannot_view_usuarios_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('usuarios.index'));
        $response->assertStatus(403);
    }

    public function test_invitado_cannot_view_usuarios_index(): void
    {
        $invitado = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($invitado)->get(route('usuarios.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_user(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg')->size(1000);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tecnico',
            'photo' => $file,
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $user = User::where('email', 'nuevo@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('tecnico', $user->role);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertNotNull($user->photo);
    }

    public function test_user_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Sin Email',
        ]);

        $response->assertSessionHasErrors(['email', 'password', 'role']);
    }

    public function test_user_creation_rejects_duplicate_email(): void
    {
        User::create([
            'name' => 'Original',
            'email' => 'duplicado@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Duplicado',
            'email' => 'duplicado@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tecnico',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_creation_rejects_password_mismatch(): void
    {
        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password456!',
            'role' => 'tecnico',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_creation_rejects_short_password(): void
    {
        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '123',
            'password_confirmation' => '123',
            'role' => 'tecnico',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_can_edit_user(): void
    {
        $user = User::create([
            'name' => 'Original',
            'email' => 'original@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($this->admin)->get(route('usuarios.edit', $user));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::create([
            'name' => 'Original',
            'email' => 'original@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($this->admin)->put(route('usuarios.update', $user), [
            'name' => 'Actualizado',
            'email' => 'actualizado@test.com',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $user->refresh();
        $this->assertEquals('Actualizado', $user->name);
        $this->assertEquals('actualizado@test.com', $user->email);
        $this->assertEquals('admin', $user->role);
    }

    public function test_admin_can_update_user_password(): void
    {
        $user = User::create([
            'name' => 'Test Password',
            'email' => 'password@test.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($this->admin)->put(route('usuarios.update', $user), [
            'name' => 'Test Password',
            'email' => 'password@test.com',
            'role' => 'tecnico',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_admin_can_anular_user(): void
    {
        $user = User::create([
            'name' => 'Para Anular',
            'email' => 'anular@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'tecnico',
        ]);

        $response = $this->actingAs($this->admin)->post(route('usuarios.anular', $user), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_admin_can_reactivar_user(): void
    {
        $user = User::create([
            'name' => 'Para Reactivar',
            'email' => 'reactivar@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'tecnico',
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('usuarios.anular', $user), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->active);
    }

    public function test_user_photo_upload_rejects_svg(): void
    {
        Storage::fake('public');

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('avatar.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Con SVG',
            'email' => 'svg@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tecnico',
            'photo' => $file,
        ]);

        $response->assertSessionHasErrors(['photo']);
        $this->assertNull(User::where('email', 'svg@test.com')->first());
    }

    public function test_user_photo_upload_accepts_png_under_5mb(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.png')->size(4000);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Con PNG',
            'email' => 'png@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tecnico',
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $user = User::where('email', 'png@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->photo);
    }
}
