<?php

namespace Tests\Unit;

use App\Support\Monto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** M-16 / M-23: una sola regla para interpretar montos escritos (formato es-CO). */
class MontoTest extends TestCase
{
    public static function validos(): array
    {
        return [
            'vacío' => ['', 0.0],
            'entero' => ['50000', 50000.0],
            'número PHP' => [12345.678, 12345.68],
            'miles con punto' => ['5.000.000', 5000000.0],
            'miles con coma' => ['5,000,000', 5000000.0],
            'un grupo de miles con punto' => ['1.500', 1500.0],
            'un grupo de miles con coma (antes 1,5 en el cierre)' => ['1,500', 1500.0],
            'decimal con coma' => ['1,5', 1.5],
            'decimal con punto' => ['50000.00', 50000.0],
            'es-CO completo' => ['$ 5.000.000,50', 5000000.5],
            'en-US completo' => ['5,000,000.50', 5000000.5],
            'espacio duro' => ["1\u{00A0}234", 1234.0],
            'negativo' => ['-2.500', -2500.0],
        ];
    }

    #[DataProvider('validos')]
    public function test_interpreta_montos_validos(mixed $entrada, float $esperado): void
    {
        $this->assertSame($esperado, Monto::parse($entrada));
    }

    public static function ambiguos(): array
    {
        return [
            'tres decimales (antes 1)' => ['0.001'],
            'tres decimales mixto (antes 12345.678)' => ['12.345,678'],
            'grupos mal formados' => ['1.50.0'],
            'grupo de miles largo' => ['50000.000'],
            'texto' => ['abc'],
        ];
    }

    #[DataProvider('ambiguos')]
    public function test_lo_ambiguo_no_se_reinterpreta(string $entrada): void
    {
        // Se devuelve el texto tal cual para que la validación `numeric` lo rechace.
        $this->assertSame($entrada, Monto::parse($entrada));
    }
}
