<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $apellidos = ['Vargas', 'Ríos', 'Mendoza', 'Soto', 'Pineda', 'Giraldo', 'Holguín', 'Castaño', 'Henao', 'Agudelo'];
        $nombres = ['Esteban', 'Javier', 'Adriana', 'Liliana', 'Mauricio', 'Rodrigo', 'Camila', 'Ximena', 'Santiago', 'Nicolas'];
        
        $firstName = fake()->randomElement($nombres);
        $lastName = fake()->randomElement($apellidos);
        $fullName = $firstName . ' ' . $lastName;
        
        // Generar un email basado en el nombre sin acentos (ej: esteban.vargas@example.com)
        $emailName = Str::ascii($firstName . '.' . $lastName);
        $email = strtolower($emailName) . fake()->numberBetween(10, 99) . '@example.com';

        return [
            'name' => $fullName,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'tecnico', // Por defecto los usuarios creados son técnicos
            'active' => 1,       // Por defecto activos
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
