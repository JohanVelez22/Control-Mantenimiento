<?php

namespace App\Support;

/**
 * Único normalizador de montos escritos por el usuario (M-16 / M-23).
 *
 * Antes había dos reglas contradictorias: en ventas y compras "1,500" valía 1500 y
 * "0.001" valía 1; en el cierre de caja "1,500" valía 1,5. Reglas (formato es-CO,
 * pesos sin centavos en la práctica):
 *
 * - Se ignoran "$", espacios y espacios duros.
 * - Con punto y coma a la vez, el último separador es el decimal: "5.000.000,50" y
 *   "5,000,000.50" valen 5000000.5.
 * - Con un solo tipo de separador:
 *   - si aparece varias veces son miles ("5.000.000", "5,000,000");
 *   - si aparece una vez seguido de 3 dígitos son miles ("1.500", "1,500" = 1500);
 *   - si aparece una vez seguido de 1 o 2 dígitos es decimal ("1,5", "50000.00").
 * - Los grupos de miles deben ser de 3 dígitos y los decimales de 1 o 2. Lo que no
 *   encaja ("0.001", "12.345,678", "1.50.0") no se reinterpreta: se devuelve el texto
 *   original para que la validación `numeric` lo rechace con un mensaje.
 *
 * El resultado se redondea a 2 decimales, igual que las columnas DECIMAL(…,2).
 */
final class Monto
{
    public static function parse(mixed $valor): float|string
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }
        if (is_int($valor) || is_float($valor)) {
            return round((float) $valor, 2);
        }

        $original = (string) $valor;
        $str = preg_replace('/[\s\x{00A0}$]+/u', '', $original);
        if ($str === '' || ! preg_match('/^-?[\d.,]+$/', $str)) {
            return $original;
        }

        $negativo = str_starts_with($str, '-');
        $str = ltrim($str, '-');

        $tienePunto = str_contains($str, '.');
        $tieneComa = str_contains($str, ',');

        if ($tienePunto && $tieneComa) {
            $decimal = strrpos($str, ',') > strrpos($str, '.') ? ',' : '.';
            $miles = $decimal === ',' ? '.' : ',';
            [$entero, $fraccion] = self::partir($str, $decimal);
            if ($fraccion === null || ! self::gruposValidos($entero, $miles)) {
                return $original;
            }
            $numero = str_replace($miles, '', $entero).'.'.$fraccion;
        } elseif ($tienePunto || $tieneComa) {
            $sep = $tienePunto ? '.' : ',';
            $veces = substr_count($str, $sep);
            $despues = substr($str, strrpos($str, $sep) + 1);

            if ($veces === 1 && preg_match('/^\d{1,2}$/', $despues)) {
                $numero = str_replace($sep, '.', $str);
            } elseif (self::gruposValidos($str, $sep)) {
                $numero = str_replace($sep, '', $str);
            } else {
                return $original;
            }
        } else {
            $numero = $str;
        }

        if (! is_numeric($numero)) {
            return $original;
        }

        return round(($negativo ? -1 : 1) * (float) $numero, 2);
    }

    /** @return array{0: string, 1: ?string} parte entera y fracción (1-2 dígitos) o null si no es válida */
    private static function partir(string $str, string $decimal): array
    {
        $pos = strrpos($str, $decimal);
        $fraccion = substr($str, $pos + 1);

        return [substr($str, 0, $pos), preg_match('/^\d{1,2}$/', $fraccion) ? $fraccion : null];
    }

    /** Grupos de miles bien formados: "1", "12", "1.234", "12.345.678" (con el separador dado). */
    private static function gruposValidos(string $entero, string $sep): bool
    {
        if (! str_contains($entero, $sep)) {
            return preg_match('/^\d+$/', $entero) === 1;
        }

        // El primer grupo no empieza en 0: "0.001" no es mil, es un monto inválido.
        return preg_match('/^[1-9]\d{0,2}('.preg_quote($sep, '/').'\d{3})+$/', $entero) === 1;
    }
}
