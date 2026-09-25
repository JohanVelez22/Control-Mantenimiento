<?php

namespace Database\Seeders;

use App\Models\CategoriaStock;
use App\Models\ConceptoCaja;
use App\Models\Configuracion;
use App\Models\Stock;
use Illuminate\Database\Seeder;

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

        // Configuración inicial de la Empresa
        Configuracion::firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Tecni Systemas',
                'nit' => '4.501.927',
                'telefono' => '3172697442 - 3165528637',
                'direccion' => 'Cra 4 # 20-81 Pereira',
                'correo' => 'tecnisystemaspereira@hotmail.com',
                'logo_path' => 'configuracion/logo_nuevo_tecnisystemas.png',
                'pie_pagina_factura' => 'Gracias por su confianza. Garantía de servicio sujeta a términos y condiciones.',
            ]
        );

        // Categorías base de Stock
        CategoriaStock::firstOrCreate(['nombre' => 'Tecnologia', 'tipo' => 'categoria']);
        CategoriaStock::firstOrCreate(['nombre' => 'Repuestos', 'tipo' => 'subcategoria']);
        CategoriaStock::firstOrCreate(['nombre' => 'Accesorios', 'tipo' => 'categoria']);
        CategoriaStock::firstOrCreate(['nombre' => 'Servicios', 'tipo' => 'categoria']);
    }
}
