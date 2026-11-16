<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * S-04: código aleatorio para que el cliente consulte su orden sin que se pueda adivinar.
 *
 * 8 caracteres de un alfabeto sin letras ni números que se confunden (0/O, 1/I/L):
 * 31^8 ≈ 8,5 × 10^11 combinaciones. Se muestra como "ABCD-EFGH" y se acepta con o sin
 * guion, espacios o minúsculas.
 */
final class CodigoConsulta
{
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const LARGO = 8;

    public static function nuevo(string $tabla): string
    {
        do {
            $codigo = '';
            for ($i = 0; $i < self::LARGO; $i++) {
                $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }
        } while (DB::table($tabla)->where('codigo_consulta', $codigo)->exists());

        return $codigo;
    }

    /** "abcd efgh", "ABCD-EFGH" → "ABCDEFGH". */
    public static function normalizar(?string $texto): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $texto));
    }

    public static function formatear(?string $codigo): string
    {
        $codigo = (string) $codigo;

        return strlen($codigo) === self::LARGO ? substr($codigo, 0, 4).'-'.substr($codigo, 4) : $codigo;
    }
}
