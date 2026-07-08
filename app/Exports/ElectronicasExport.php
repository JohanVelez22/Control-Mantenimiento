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

class ElectronicasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $electronicas;

    public function __construct($electronicas)
    {
        $this->electronicas = $electronicas;
    }

    /**
     * Define los eventos para manipular la hoja después de generada.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Configurar pie de página para impresión
                $sheet->getHeaderFooter()->setOddFooter('&RPágina &P de &N');

                // Centrar y combinar título (Fila 1)
                $sheet->mergeCells('A1:N1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Centrar y combinar fecha (Fila 2)
                $sheet->mergeCells('A2:N2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $footerRow = $lastRow + 2; // Dejamos una fila de espacio

                $totalRegistros = count($this->electronicas);
                $costoTotal = $this->electronicas->sum('costo');

                // Aplicar formato de miles a la columna L (Costo)
                $sheet->getStyle("L5:L{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0');

                // Escribir el total de registros bajo la columna "Orden" (Columna A)
                $sheet->setCellValue("A{$footerRow}", "Total: {$totalRegistros}");
                
                // Escribir el costo total bajo la columna "Costo" (Columna L)
                $sheet->setCellValue("L{$footerRow}", "Total: $" . number_format($costoTotal, 0, ',', '.'));

                // Estilo para los totales
                $sheet->getStyle("A{$footerRow}:L{$footerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11
                    ],
                ]);

                // Alineación a la derecha para el costo total
                $sheet->getStyle("L{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el título principal
            1    => ['font' => ['bold' => true, 'size' => 16]],
            // Estilo para la fecha
            2    => ['font' => ['italic' => true, 'size' => 12]],
            // Estilo para los encabezados de la tabla (Fila 4)
            4    => [
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
        return $this->electronicas;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE ELECTRÓNICA'],
            ['Generado el: ' . date('d/m/Y h:i A')],
            [''], // Fila en blanco para separación
            [
                'Orden', 
                'Cliente', 
                'Identificación',
                'Equipo', 
                'Marca',  
                'Modelo', 
                'Serie',
                'Técnico', 
                'Tipo', 
                'Progreso',
                'Estado',
                'Costo', 
                'Fecha Entrada', 
                'Fecha Salida'
            ]
        ];
    }

    public function map($e): array
    {
        return [
            $e->id_orden,
            $e->equipo->cliente->nombre ?? 'N/A',
            $e->equipo->cliente->identificacion ?? '-',
            $e->equipo->nombre ?? 'N/A',
            $e->equipo->marca ?? 'N/A',  
            $e->equipo->modelo ?? 'N/A', 
            $e->equipo->serie ?? 'N/A',
            $e->tecnico->nombre ?? 'N/A',
            ucfirst($e->tipo),
            ucfirst($e->estado),
            $e->anulado ? 'Anulado' : 'Activo',
            (float) $e->costo,
            \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y'),
            $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : 'Pendiente',
        ];
    }
}
