<?php

namespace App\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Evita la inyección de fórmulas (CSV/Excel injection) en las exportaciones.
 *
 * Un texto que empieza por = + - @ se escribía como fórmula: un nombre de cliente como
 * =HYPERLINK("https://...",...) se ejecutaba al abrir el archivo en Excel. Los textos se
 * guardan siempre como texto literal; los números se siguen escribiendo como números.
 */
class SafeValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, $value)
    {
        // X-10: documentos y códigos como texto (sin perder ceros ni dígitos).
        if ($value instanceof TextoExcel) {
            $cell->setValueExplicit($value->valor, DataType::TYPE_STRING);

            return true;
        }

        if (is_string($value) && $value !== '' && ! is_numeric($value)
            && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
