<?php

namespace App\Exports;

/**
 * X-10: valor que se escribe en Excel como texto aunque parezca número (cédulas, NIT,
 * códigos): así no pierde ceros a la izquierda ni dígitos en documentos largos.
 */
final class TextoExcel implements \Stringable
{
    public function __construct(public readonly string $valor) {}

    public function __toString(): string
    {
        return $this->valor;
    }
}
