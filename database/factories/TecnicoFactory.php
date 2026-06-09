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

        $faker = \Faker\Factory::create('es_ES');
        $nombre = $faker->randomElement($nombres);
        $apellido = $faker->randomElement($apellidos);
        $nombreCompleto = $nombre . ' ' . $apellido;

        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . $faker->numberBetween(1, 100) . ' # ' . $faker->numberBetween(1, 50) . '-' . $faker->numberBetween(1, 99),
            'Carrera ' . $faker->numberBetween(1, 80) . ' # ' . $faker->numberBetween(1, 120) . '-' . $faker->numberBetween(1, 60),
        ];

        // Crear un email coherente (ej: nelson.zapata@gmail.com)
        $email = Str::lower(Str::ascii($nombre)) . '.' . Str::lower(Str::ascii($apellido)) . $faker->numberBetween(1, 99) . '@' . $faker->freeEmailDomain();

        return [
            'nombre' => $nombreCompleto,
            'identificacion' => $faker->unique()->numerify('10########'),
            'especialidad' => $faker->randomElement(['Hardware', 'Software', 'Redes', 'General']),
            'movil' => '3' . $faker->numerify('#########'),
            'email' => $email,
            'direccion' => $faker->randomElement($direcciones) . ', ' . $faker->randomElement($ciudades),
        ];
    }
}
