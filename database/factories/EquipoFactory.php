<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * Define la estructura de un equipo con datos coherentes.
     * Genera marcas y modelos que coinciden (ej. Dell Latitude).
     */
    public function definition(): array
    {
        $equipos = [
            'Laptop' => ['Dell Latitude', 'HP EliteBook', 'Lenovo ThinkPad', 'MacBook Pro'],
            'PC de Escritorio' => ['Lenovo Ideapad 5', 'Dell OptiPlex 7080', 'HP ProDeskBook 440', 'Acer Aspire 100', 'Mac Mini M2'],
            'Servidor' => ['PowerEdge R740', 'ProLiant DL380'],
            'All in One' => ['Lenovo IdeaCentre', 'HP Laserjet Pro', 'Dell Optiplex', 'Acer Aspire'],
        ];

        $tipo = fake()->randomKey($equipos);
        $marcaModelo = fake()->randomElement($equipos[$tipo]);
        
        $partes = explode(' ', $marcaModelo, 2);
        $marca = $partes[0];
        $modelo = $partes[1] ?? $partes[0];

        return [
            'nombre' => $tipo,
            'marca' => $marca,
            'modelo' => $modelo,
            'serie' => fake()->unique()->bothify('??###???'),
            'observacion' => fake()->randomElement([
                'Equipo en buen estado general.',
                'Presenta desgaste en la carcasa.',
                'Uso corporativo intenso.',
                'Requiere formateo.',
                'Batería con ciclos de carga altos.',
                'Pantalla con leves rayones.',
                'Sistema operativo desactualizado.',
                'Disco duro dañado',
                'Cambio de Pasta Termica',
                'Cambio de Disco Duro SSD',
                'Instalación de Windows 11',
                'Instalación de Windows 10',
                'Instalacion Office 365',
                'Instalacion Antivirus',
                'Mantenimiento Correctivo'                      
            ]),
            'user_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
        ];
    }
}
