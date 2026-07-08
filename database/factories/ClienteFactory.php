<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define la estructura de un cliente con datos coherentes colombianos.
     */
    public function definition(): array
    {
        $apellidos = ['Rodríguez', 'Martínez', 'García', 'Gómez', 'López', 'González', 'Hernández', 'Sánchez', 'Pérez', 'Vásquez', 'Vélez', 'Restrepo', 'Jaramillo', 'Osorio', 'Montoya', 'Castrillón', 'Gaviria', 'Uribe', 'Zuluaga', 'Echeverri'];
        $nombres = ['Juan', 'María', 'José', 'Ana', 'Carlos', 'Sandra', 'Luis', 'Claudia', 'Diego', 'Martha', 'Andrés', 'Gloria', 'Jorge', 'Diana', 'Sergio', 'Paula', 'Mateo', 'Valentina', 'Alejandro', 'Daniela'];
        
        $faker = \Faker\Factory::create('es_ES');
        $nombre = $faker->randomElement($nombres);
        $apellido1 = $faker->randomElement($apellidos);
        $apellido2 = $faker->randomElement($apellidos);
        $nombreCompleto = $nombre . ' ' . $apellido1 . ' ' . $apellido2;
        
        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . $faker->numberBetween(1, 100) . ' # ' . $faker->numberBetween(1, 50) . '-' . $faker->numberBetween(1, 99),
            'Carrera ' . $faker->numberBetween(1, 80) . ' # ' . $faker->numberBetween(1, 120) . '-' . $faker->numberBetween(1, 60),
            'Diagonal ' . $faker->numberBetween(1, 40) . ' # ' . $faker->numberBetween(1, 30) . '-' . $faker->numberBetween(1, 20),
            'Avenida ' . $faker->numberBetween(10, 60) . ' con Calle ' . $faker->numberBetween(1, 90),
        ];

        // Generar email más realista
        $email = Str::lower(Str::ascii($nombre)) . '.' . Str::lower(Str::ascii($apellido1)) . $faker->numberBetween(1, 99) . '@' . $faker->freeEmailDomain();

        return [
            'nombres' => $nombre,
            'apellidos' => $apellido1 . ' ' . $apellido2,
            'tipo_identificacion' => 'cedula_ciudadania',
            'genero' => $faker->randomElement(['masculino', 'femenino', 'indefinido']),
            'tipo_cliente' => $faker->randomElement(['cliente', 'tecnico']),
            'identificacion' => $faker->unique()->numerify('10########'), // Cédula de 10 dígitos
            'movil' => '3' . $faker->numerify('#########'), // Celular colombiano
            'email' => $email,
            'direccion' => $faker->randomElement($direcciones),
            'departamento' => 'Risaralda',
            'municipio' => $faker->randomElement($ciudades),
            'active' => true,
        ];
    }
}
