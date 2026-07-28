<?php

namespace Database\Factories;

use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Electronica>
 */
class ElectronicaFactory extends Factory
{
    protected static int $orderNumber = 1;

    /**
     * Define el estado por defecto para pruebas de Electrónica.
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('es_ES');
        
        $descripciones = [
            'Reparación de tarjeta madre, reballing de chip de video.',
            'Cambio de pines de carga y micro-soldadura SMD.',
            'Reparación de circuito de alimentación (MOSFETs y capacitores).',
            'Diagnóstico y cambio de integrado de carga de batería.',
            'Reemplazo de display LCD y calibración de panel táctil.',
            'Limpieza ultrasónica por sulfatación / derrames de líquido.'
        ];

        return [
            'id_orden' => 'ELE-' . self::$orderNumber++,
            'fecha_entrada' => $faker->dateTimeBetween('-1 month', 'now'),
            'fecha_salida' => $faker->optional()->dateTimeBetween('now', '+1 week'),
            'tipo' => $faker->randomElement(['preventivo', 'correctivo']),
            'descripcion' => $faker->randomElement($descripciones),
            'costo' => $faker->numberBetween(15, 120) * 5000,
            'estado' => $faker->randomElement(['pendiente', 'terminado']),
            'equipo_id' => Equipo::factory(),
            'tecnico_id' => Tecnico::factory(),
            'user_id' => User::factory(),
        ];
    }
}
