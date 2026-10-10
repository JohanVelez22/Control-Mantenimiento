<?php

namespace Tests\Unit;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\OrdenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Equipo $equipo;
    protected Tecnico $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->equipo = Equipo::factory()->create(['user_id' => $this->admin->id]);
        $this->tecnico = Tecnico::factory()->create();
    }

    public function test_siguiente_generates_first_number_when_empty(): void
    {
        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class);

        $this->assertEquals('ORD-1', $result);
    }

    public function test_siguiente_generates_next_number_when_existing(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-5',
            'equipo_id' => $this->equipo->id, 'tecnico_id' => $this->tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now(), 'descripcion' => 'Test',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 100000, 'estado' => 'pendiente',
        ]);

        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class);

        $this->assertEquals('ORD-6', $result);
    }

    public function test_siguiente_ignores_different_prefix(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ELC-10',
            'equipo_id' => $this->equipo->id, 'tecnico_id' => $this->tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now(), 'descripcion' => 'Test',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 100000, 'estado' => 'pendiente',
        ]);

        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class);

        $this->assertEquals('ORD-1', $result);
    }

    public function test_siguiente_with_pad_zeros(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-0005',
            'equipo_id' => $this->equipo->id, 'tecnico_id' => $this->tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now(), 'descripcion' => 'Test',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 100000, 'estado' => 'pendiente',
        ]);

        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class, 'id_orden', 4);

        $this->assertEquals('ORD-0006', $result);
    }

    public function test_siguiente_without_lock_preview_only(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-10',
            'equipo_id' => $this->equipo->id, 'tecnico_id' => $this->tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now(), 'descripcion' => 'Test',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 100000, 'estado' => 'pendiente',
        ]);

        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class, 'id_orden', null, false);

        $this->assertEquals('ORD-11', $result);
    }

    public function test_siguiente_handles_gaps_in_sequence(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-100',
            'equipo_id' => $this->equipo->id, 'tecnico_id' => $this->tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now(), 'descripcion' => 'Test',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 100000, 'estado' => 'pendiente',
        ]);

        $service = new OrdenService;
        $result = $service->siguiente('ORD-', Mantenimiento::class);

        $this->assertEquals('ORD-101', $result);
    }
}