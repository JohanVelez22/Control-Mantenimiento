<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcumuladoExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    protected $acumulado;

    public function __construct($acumulado)
    {
        $this->acumulado = $acumulado;
    }

    public function array(): array
    {
        return [
            ['Mantenimientos', $this->acumulado['total_mantenimientos'] ?? 0, $this->acumulado['facturado_mant'] ?? 0],
            ['Electrónica', $this->acumulado['total_electronicas'] ?? 0, $this->acumulado['facturado_elec'] ?? 0],
            ['Compras de Inventario', $this->acumulado['total_compras'] ?? 0, $this->acumulado['compras_inventario'] ?? 0],
            ['Ventas de Inventario', $this->acumulado['total_ventas'] ?? 0, $this->acumulado['ventas_inventario'] ?? 0],
            ['Ingresos Reales (Caja)', $this->acumulado['total_ingresos'] ?? 0, $this->acumulado['ingresos_caja'] ?? 0],
            ['Egresos Reales (Caja)', $this->acumulado['total_egresos'] ?? 0, $this->acumulado['egresos_caja'] ?? 0],
            ['Movimientos Anulados', $this->acumulado['total_anulados'] ?? 0, 0],
        ];
    }

    public function headings(): array
    {
        return [
            ['RESUMEN CONSOLIDADO - ACUMULADO'],
            ['Generado el: ' . date('d/m/Y h:i A')],
            [''],
            ['Categoría', 'Cantidad de Movimientos', 'Costo Total'],
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:C1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:C2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Format amount column (C)
                $sheet->getStyle('C5:C11')->getNumberFormat()->setFormatCode('"$"#,##0.00');

                $lastRow = 11;
                $footerRow = $lastRow + 2;

                $sheet->setCellValue("B{$footerRow}", 'Balance Neto del Período:');
                $sheet->setCellValue("C{$footerRow}", $this->acumulado['balance_neto'] ?? 0);
                $sheet->getStyle("C{$footerRow}")->getNumberFormat()->setFormatCode('"$"#,##0.00');

                $sheet->getStyle("B{$footerRow}:C{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                ]);
                
                $sheet->getStyle("B{$footerRow}:C{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
