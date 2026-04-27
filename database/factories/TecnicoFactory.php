<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

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

        $nombre = fake()->randomElement($nombres);
        $apellido = fake()->randomElement($apellidos);
        $nombreCompleto = "$nombre $apellido";

        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . fake()->numberBetween(1, 100) . ' # ' . fake()->numberBetween(1, 50) . '-' . fake()->numberBetween(1, 99),
            'Carrera ' . fake()->numberBetween(1, 80) . ' # ' . fake()->numberBetween(1, 120) . '-' . fake()->numberBetween(1, 60),
        ];

        // Crear un email coherente (ej: nelson.zapata@gmail.com)
        $email = Str::lower(Str::ascii($nombre)) . '.' . Str::lower(Str::ascii($apellido)) . fake()->numberBetween(1, 99) . '@' . fake()->freeEmailDomain();

        return [
            'nombre' => $nombreCompleto,
            'identificacion' => fake()->unique()->numerify('10########'),
            'especialidad' => fake()->randomElement(['Hardware', 'Software', 'Redes', 'General']),
            'movil' => '3' . fake()->numerify('#########'),
            'email' => $email,
            'direccion' => fake()->randomElement($direcciones) . ', ' . fake()->randomElement($ciudades),
        ];
    }
}
