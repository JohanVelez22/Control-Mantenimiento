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

class StocksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $stocks;

    public function __construct($stocks)
    {
        $this->stocks = $stocks;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Configurar pie de página para impresión
                $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');
                
                // Centrar y combinar título (Fila 1)
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Centrar y combinar fecha (Fila 2)
                $sheet->mergeCells('A2:J2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $footerRow = $lastRow + 2; // Dejamos una fila de espacio

                $totalRegistros = count($this->stocks);
                $cantidadTotal = $this->stocks->sum('cantidad');
                $compraTotal = $this->stocks->sum('precio_compra');
                $ventaTotal = $this->stocks->sum('precio_venta');
                $tecnicoTotal = $this->stocks->sum('precio_tecnico');

                // Aplicar formato de miles a las columnas G, I y J
                $sheet->getStyle("G5:G{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0');
                $sheet->getStyle("I5:I{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0');
                $sheet->getStyle("J5:J{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0');

                // Escribir totales
                $sheet->setCellValue("A{$footerRow}", "Total Registros: {$totalRegistros}");
                $sheet->setCellValue("F{$footerRow}", "Cant. Total: {$cantidadTotal}");
                $sheet->setCellValue("G{$footerRow}", "T. Compra: $" . number_format($compraTotal, 0, ',', '.'));
                $sheet->setCellValue("I{$footerRow}", "T. Venta: $" . number_format($ventaTotal, 0, ',', '.'));
                $sheet->setCellValue("J{$footerRow}", "T. Técnico: $" . number_format($tecnicoTotal, 0, ',', '.'));

                // Estilo para los totales
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11
                    ],
                ]);

                $sheet->getStyle("G{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("I{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("J{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
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
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A5568']
                ]
            ],
        ];
    }

    public function collection()
    {
        return $this->stocks;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE INVENTARIO (STOCK)'],
            ['Generado el: ' . date('d/m/Y h:i A')],
            [''],
            [
                'Código', 
                'Producto', 
                'Categoría',
                'Subcategoría',
                'Proveedor',
                'Cantidad',
                'Precio Compra',
                'Utilidad (%)',
                'Precio Venta',
                'Precio Técnico',
                'Estado'
            ]
        ];
    }

    public function map($s): array
    {
        return [
            $s->codigo ?? '-',
            $s->producto,
            $s->categoria ?? '-',
            $s->subcategoria ?? '-',
            $s->proveedor_id ? ($s->getRelationValue('proveedor')->nombre_razon_social ?? 'N/A') : ($s->getRawOriginal('proveedor') ?: 'N/A'),
            $s->cantidad,
            (float) $s->precio_compra,
            $s->utilidad,
            (float) $s->precio_venta,
            (float) $s->precio_tecnico,
            $s->active ? 'Activo' : 'Inactivo',
        ];
    }
}
