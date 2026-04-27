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
        
        $nombre = fake()->randomElement($nombres);
        $apellido1 = fake()->randomElement($apellidos);
        $apellido2 = fake()->randomElement($apellidos);
        $nombreCompleto = "$nombre $apellido1 $apellido2";
        
        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . fake()->numberBetween(1, 100) . ' # ' . fake()->numberBetween(1, 50) . '-' . fake()->numberBetween(1, 99),
            'Carrera ' . fake()->numberBetween(1, 80) . ' # ' . fake()->numberBetween(1, 120) . '-' . fake()->numberBetween(1, 60),
            'Diagonal ' . fake()->numberBetween(1, 40) . ' # ' . fake()->numberBetween(1, 30) . '-' . fake()->numberBetween(1, 20),
            'Avenida ' . fake()->numberBetween(10, 60) . ' con Calle ' . fake()->numberBetween(1, 90),
        ];

        // Crear un email coherente con el nombre (ej: juan.rodriguez@gmail.com)
        $email = Str::lower(Str::ascii($nombre)) . '.' . Str::lower(Str::ascii($apellido1)) . fake()->numberBetween(1, 99) . '@' . fake()->freeEmailDomain();

        return [
            'nombre' => $nombreCompleto,
            'identificacion' => fake()->unique()->numerify('10########'), // Cédula de 10 dígitos
            'movil' => '3' . fake()->numerify('#########'), // Celular colombiano
            'email' => $email,
            'direccion' => fake()->randomElement($direcciones) . ', ' . fake()->randomElement($ciudades),
        ];
    }
}
