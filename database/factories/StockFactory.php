<?php

namespace Database\Factories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * Define el estado por defecto para pruebas de Stock.
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('es_ES');

        return [
            'codigo' => 'STK-'.$faker->unique()->numerify('###-??'),
            'producto' => $faker->unique()->words(3, true),
            'categoria' => $faker->randomElement(['Componentes', 'Herramientas', 'Accesorios', 'Consumibles']),
            'subcategoria' => $faker->randomElement(['Eléctricos', 'Mecánicos', 'Electrónicos', 'Varios']),
            'cantidad' => $faker->numberBetween(0, 100),
            'precio_compra' => $faker->numberBetween(10, 500) * 1000,
            'utilidad' => $faker->numberBetween(10, 100),
            'precio_venta' => $faker->numberBetween(15, 600) * 1000,
            'precio_tecnico' => $faker->numberBetween(12, 550) * 1000,
            'active' => true,
        ];
    }
}