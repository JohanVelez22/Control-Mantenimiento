<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportesFinancierosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $transacciones;

    public function __construct($transacciones)
    {
        // Ensure it's a Laravel Collection
        $this->transacciones = collect($transacciones);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Configurar pie de página para impresión
                $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');

                // Centrar y combinar título (Fila 1)
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Centrar y combinar fecha (Fila 2)
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $lastRow   = $sheet->getHighestRow();
                $footerRow = $lastRow + 2;

                                $ingresos = $this->transacciones->filter(function($tx) {
                    $tipo = is_array($tx) ? ($tx['tipo'] ?? '') : ($tx->tipo_movimiento ?? '');
                    $anulado = is_array($tx) ? !empty($tx['anulado']) : $tx->anulado;
                    return !$anulado && in_array($tipo, ['ingreso', 'venta', 'mantenimiento', 'electronica']);
                })->sum(fn($tx) => is_array($tx) ? ($tx['monto'] ?? 0) : ($tx->monto ?? $tx->total_documento ?? 0));

                $egresos = $this->transacciones->filter(function($tx) {
                    $tipo = is_array($tx) ? ($tx['tipo'] ?? '') : ($tx->tipo_movimiento ?? '');
                    $anulado = is_array($tx) ? !empty($tx['anulado']) : $tx->anulado;
                    return !$anulado && in_array($tipo, ['egreso', 'compra']);
                })->sum(fn($tx) => is_array($tx) ? ($tx['monto'] ?? 0) : ($tx->monto ?? $tx->total_documento ?? 0));

                $total = $ingresos - $egresos;

                // Aplicar formato de miles a la columna D (Costo)
                $sheet->getStyle("D5:D{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0');

                $sheet->setCellValue("A{$footerRow}", 'Total registros: ' . $this->transacciones->count());
                $sheet->setCellValue("D{$footerRow}", 'Balance Neto: $' . number_format($total, 0, ',', '.'));

                $sheet->getStyle("A{$footerRow}:F{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                $sheet->getStyle("D{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true, 'size' => 12]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A5568'],
                ],
            ],
        ];
    }

    public function collection()
    {
        return $this->transacciones;
    }

    public function headings(): array
    {
        return [
            ['INFORME FINANCIERO'],
            ['Generado el: ' . date('d/m/Y h:i A')],
            [''],
            ['Fecha', 'Tipo', 'Descripción / Concepto', 'Costo', 'Estado', 'Anulado'],
        ];
    }

    public function map($tx): array
    {
        $isArr = is_array($tx);

        $fecha = $isArr ? ($tx['fecha'] ?? '') : ($tx->fecha ?? $tx->fecha_entrada ?? '');
        $tipo  = $isArr ? ($tx['tipo'] ?? 'N/A') : ($tx->tipo_movimiento ?? 'N/A');
        $desc  = $isArr
            ? ($tx['descripcion'] ?? '—')
            : ($tx->concepto->nombre ?? $tx->persona ?? $tx->empresa ?? 'N/A');
        $monto   = $isArr ? ($tx['monto'] ?? 0) : ($tx->monto ?? $tx->total_documento ?? 0);
        $estado  = $isArr ? ($tx['estado'] ?? '—') : ($tx->estado ?? '—');
        $anulado = $isArr ? (!empty($tx['anulado']) ? 'Sí' : 'No') : ($tx->anulado ? 'Sí' : 'No');

        return [
            $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : '—',
            ucfirst($tipo),
            $desc,
            (float) $monto,
            ucfirst($estado),
            $anulado,
        ];
    }
}
