<?php

namespace App\Rules;

use App\Models\Cliente;
use App\Models\Proveedor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * B-05 / B-09: el destinatario de una venta, compra o cotización llega como
 * "Cliente:12" o "Proveedor:3". Antes se partía sin validar y un valor armado a mano
 * producía un error 500 o rompía la llave foránea.
 *
 * El destinatario debe existir y estar activo; `$actual` permite conservar el que ya
 * tenía el documento aunque después se haya desactivado.
 */
class Facturable implements ValidationRule
{
    public function __construct(private ?string $actual = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(Cliente|Proveedor):(\d+)$/', $value, $m)) {
            $fail('Seleccione un cliente o proveedor válido.');

            return;
        }

        $modelo = $m[1] === 'Proveedor' ? Proveedor::class : Cliente::class;
        $registro = $modelo::query()->find((int) $m[2]);
        if (! $registro) {
            $fail('El cliente o proveedor seleccionado no existe.');

            return;
        }

        if (array_key_exists('active', $registro->getAttributes()) && ! $registro->active && $value !== $this->actual) {
            $fail('El cliente o proveedor seleccionado está inactivo.');
        }
    }

    /** @return array{0: class-string, 1: int} */
    public static function partir(string $valor): array
    {
        [$tipo, $id] = explode(':', $valor, 2);

        return [$tipo === 'Proveedor' ? Proveedor::class : Cliente::class, (int) $id];
    }
}
