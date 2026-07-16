<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Electronica;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\CierreCaja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Usuarios base del sistema (admin + técnico) - definidos en .env
        $this->call(AdminUserSeeder::class);

        // Conceptos de Caja básicos para el sistema
        ConceptoCaja::firstOrCreate(['nombre' => 'Pago de mantenimiento']);
        ConceptoCaja::firstOrCreate(['nombre' => 'Pago de servicios']);
        ConceptoCaja::firstOrCreate(['nombre' => 'Apertura de caja']);
        ConceptoCaja::firstOrCreate(['nombre' => 'Otros ingresos']);
        ConceptoCaja::firstOrCreate(['nombre' => 'Otros egresos']);

        // NOTA: Para generar datos de prueba, utilice el comando:
        // php artisan app:seed-demo-data
    }
}
