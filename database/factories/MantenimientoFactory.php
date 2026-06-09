<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mantenimiento>
 */
class MantenimientoFactory extends Factory
{
    // Contador estático para generar números de orden secuenciales (ORD-1, ORD-2...)
    protected static int $orderNumber = 1;

    /**
     * Define un registro de mantenimiento con descripciones técnicas realistas.
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('es_ES');
        $reparacion = $faker->randomElement(['software', 'hardware']);
        
        $descripciones = [
            'software' => [
                'Formateo e instalación de sistema operativo y drivers.',
                'Limpieza de virus, malware y optimización de registro.',
                'Actualización de suite ofimática y software corporativo.',
                'Recuperación de arranque y corrección de errores de sistema.',
                'Configuración de copia de seguridad en la nube.'
            ],
            'hardware' => [
                'Limpieza física interna y cambio de pasta térmica.',
                'Ampliación de memoria RAM y cambio a disco SSD.',
                'Reparación de bisagras y mantenimiento de carcasa.',
                'Reemplazo de teclado y limpieza de ventiladores.',
                'Cambio de pasta termica.',
                'Diagnóstico de fuente de poder y cambio de componentes.'
            ]
        ];

        return [
            'id_orden' => 'ORD-' . self::$orderNumber++,
            'fecha_entrada' => $faker->dateTimeBetween('-1 month', 'now'),
            'fecha_salida' => $faker->optional()->dateTimeBetween('now', '+1 week'),
            'tipo' => $faker->randomElement(['preventivo', 'correctivo']),
            'reparacion' => $reparacion,
            'descripcion' => $faker->randomElement($descripciones[$reparacion]),
            'costo' => $faker->numberBetween(10, 90) * 5000, // Genera múltiplos de 5.000 entre 50k y 450k
            'estado' => $faker->randomElement(['pendiente', 'terminado']),
            'equipo_id' => Equipo::factory(),
            'tecnico_id' => Tecnico::factory(),
            'user_id' => User::factory(),
        ];
    }
}
