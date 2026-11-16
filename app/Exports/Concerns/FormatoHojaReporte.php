<?php

namespace App\Exports\Concerns;

use App\Exports\TextoExcel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * X-10 / X-11: formato común de las hojas de reporte.
 *
 * - Fechas como fechas de Excel (se pueden ordenar y filtrar), documentos como texto (no
 *   pierden ceros a la izquierda ni dígitos).
 * - Totales como números con formato de moneda, con la etiqueta en otra celda.
 * - Autofiltro, encabezado fijo al desplazarse y repetido al imprimir, hoja horizontal
 *   ajustada al ancho y numeración de páginas.
 */
trait FormatoHojaReporte
{
    protected const FILA_ENCABEZADO = 4;

    protected const FORMATO_MONEDA = '"$ "#,##0';

    protected static function fechaExcel($valor): ?float
    {
        if (! $valor) {
            return null;
        }

        return Date::dateTimeToExcel(Carbon::parse($valor)->startOfDay());
    }

    protected static function texto($valor): ?TextoExcel
    {
        return ($valor === null || $valor === '') ? null : new TextoExcel((string) $valor);
    }

    protected function formatearHoja(Worksheet $sheet, string $ultimaColumna, bool $horizontal = true): int
    {
        $fila = self::FILA_ENCABEZADO;
        $ultimaFila = max($fila, $sheet->getHighestRow());

        foreach ([1, 2] as $f) {
            $sheet->mergeCells("A{$f}:{$ultimaColumna}{$f}");
            $sheet->getStyle("A{$f}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->setAutoFilter("A{$fila}:{$ultimaColumna}{$ultimaFila}");
        $sheet->freezePane('A'.($fila + 1));
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($fila, $fila);
        $sheet->getPageSetup()->setOrientation($horizontal ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');

        return $ultimaFila;
    }

    protected function formatoColumnas(Worksheet $sheet, array $columnas, string $formato, int $ultimaFila): void
    {
        $desde = self::FILA_ENCABEZADO + 1;
        foreach ($columnas as $col) {
            $sheet->getStyle("{$col}{$desde}:{$col}{$ultimaFila}")->getNumberFormat()->setFormatCode($formato);
        }
    }

    protected function fechasEn(Worksheet $sheet, array $columnas, int $ultimaFila): void
    {
        $this->formatoColumnas($sheet, $columnas, NumberFormat::FORMAT_DATE_DDMMYYYY, $ultimaFila);
    }

    /** Etiqueta en una celda y valor numérico (con su formato) en otra. */
    protected function total(Worksheet $sheet, int $fila, string $colEtiqueta, string $etiqueta, string $colValor, float|int $valor, ?string $formato = self::FORMATO_MONEDA): void
    {
        $sheet->setCellValue("{$colEtiqueta}{$fila}", $etiqueta);
        $sheet->setCellValue("{$colValor}{$fila}", $valor);
        if ($formato) {
            $sheet->getStyle("{$colValor}{$fila}")->getNumberFormat()->setFormatCode($formato);
        }
        $sheet->getStyle("{$colEtiqueta}{$fila}:{$colValor}{$fila}")->getFont()->setBold(true);
        $sheet->getStyle("{$colEtiqueta}{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}
