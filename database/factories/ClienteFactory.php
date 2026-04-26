<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        
        $nombreCompleto = fake()->randomElement($nombres) . ' ' . fake()->randomElement($apellidos) . ' ' . fake()->randomElement($apellidos);
        
        $ciudades = ['Pereira', 'Dosquebradas', 'Santa Rosa', 'Cartago', 'Marsella'];
        $direcciones = [
            'Calle ' . fake()->numberBetween(1, 100) . ' # ' . fake()->numberBetween(1, 50) . '-' . fake()->numberBetween(1, 99),
            'Carrera ' . fake()->numberBetween(1, 80) . ' # ' . fake()->numberBetween(1, 120) . '-' . fake()->numberBetween(1, 60),
            'Diagonal ' . fake()->numberBetween(1, 40) . ' # ' . fake()->numberBetween(1, 30) . '-' . fake()->numberBetween(1, 20),
            'Avenida ' . fake()->numberBetween(10, 60) . ' con Calle ' . fake()->numberBetween(1, 90),
        ];

        return [
            'nombre' => $nombreCompleto,
            'identificacion' => fake()->unique()->numerify('10########'), // Cédula de 10 dígitos
            'movil' => '3' . fake()->numerify('#########'), // Celular colombiano
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->randomElement($direcciones) . ', ' . fake()->randomElement($ciudades),
        ];
    }
}
