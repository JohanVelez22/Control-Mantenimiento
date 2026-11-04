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

        $problemas = [
            'Falla en tarjeta madre, posible reballing de chip de video.',
            'Pines de carga dañados, requiere micro-soldadura SMD.',
            'Circuito de alimentación con falla (MOSFETs y capacitores).',
            'Integrado de carga de batería no funciona.',
            'Display LCD roto, requiere calibración de panel táctil.',
            'Sulfatación / derrames de líquido, requiere limpieza ultrasónica.',
        ];

        return [
            'id_orden' => 'ELE-'.self::$orderNumber++,
            'fecha_entrada' => $faker->dateTimeBetween('-1 month', 'now'),
            'fecha_salida' => $faker->optional()->dateTimeBetween('now', '+1 week'),
            'tipo' => $faker->randomElement(['preventivo', 'correctivo']),
            'descripcion_problema' => $faker->randomElement($problemas),
            'reparacion' => $faker->randomElement(['software', 'hardware']),
            'costo' => $faker->numberBetween(15, 120) * 5000,
            'estado' => $faker->randomElement(['pendiente', 'terminado']),
            'anulado' => false,
            'equipo_id' => Equipo::factory(),
            'tecnico_id' => Tecnico::factory(),
            'user_id' => User::factory(),
        ];
    }
}
