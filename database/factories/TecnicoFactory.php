<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tecnico>
 */
class TecnicoFactory extends Factory
{
    /**
     * Define un técnico con nombres y direcciones típicas de Colombia.
     */
    public function definition(): array
    {
        $apellidos = ['Zapata', 'Gallego', 'Suárez', 'Bedoya', 'Quintero', 'Rendón', 'Arboleda', 'Posada', 'Ramírez', 'Toro'];
        $nombres = ['Nelson', 'Héctor', 'Wilson', 'Giovanni', 'Alexander', 'Julián', 'Ricardo', 'Fernando', 'Yolanda', 'Margarita'];

        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . fake()->numberBetween(1, 100) . ' # ' . fake()->numberBetween(1, 50) . '-' . fake()->numberBetween(1, 99),
            'Carrera ' . fake()->numberBetween(1, 80) . ' # ' . fake()->numberBetween(1, 120) . '-' . fake()->numberBetween(1, 60),
        ];

        return [
            'nombre' => fake()->randomElement($nombres) . ' ' . fake()->randomElement($apellidos),
            'identificacion' => fake()->unique()->numerify('10########'),
            'especialidad' => fake()->randomElement(['Hardware', 'Software', 'Redes', 'General']),
            'movil' => '3' . fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->randomElement($direcciones) . ', ' . fake()->randomElement($ciudades),
        ];
    }
}
