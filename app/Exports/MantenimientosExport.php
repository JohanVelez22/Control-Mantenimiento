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

class MantenimientosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $mantenimientos;

    public function __construct($mantenimientos)
    {
        $this->mantenimientos = $mantenimientos;
    }

    /**
     * Define los eventos para manipular la hoja después de generada.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Centrar y combinar título (Fila 1)
                $sheet->mergeCells('A1:L1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Centrar y combinar fecha (Fila 2)
                $sheet->mergeCells('A2:L2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $footerRow = $lastRow + 2; // Dejamos una fila de espacio

                $totalRegistros = count($this->mantenimientos);
                $costoTotal = $this->mantenimientos->sum('costo');

                // Escribir el total de registros bajo la columna "Orden" (Columna A)
                $sheet->setCellValue("A{$footerRow}", "Total: {$totalRegistros}");
                
                // Escribir el costo total bajo la columna "Costo" (Columna J)
                $sheet->setCellValue("J{$footerRow}", "Total: $" . number_format($costoTotal, 2));

                // Estilo para los totales
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11
                    ],
                ]);

                // Alineación a la derecha para el costo total
                $sheet->getStyle("J{$footerRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
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
        return $this->mantenimientos;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE MANTENIMIENTOS'],
            ['Generado el: ' . date('d/m/Y h:i A')],
            [''], // Fila en blanco para separación
            [
                'Orden', 
                'Cliente', 
                'Equipo', 
                'Marca',  
                'Modelo', 
                'Serie',
                'Técnico', 
                'Tipo', 
                'Reparación', 
                'Costo', 
                'Fecha Entrada', 
                'Fecha Salida'
            ]
        ];
    }

    public function map($m): array
    {
        return [
            $m->id_orden,
            $m->equipo->cliente->nombre ?? 'N/A',
            $m->equipo->nombre ?? 'N/A',
            $m->equipo->marca ?? 'N/A',  
            $m->equipo->modelo ?? 'N/A', 
            $m->equipo->serie ?? 'N/A',
            $m->tecnico->nombre ?? 'N/A',
            ucfirst($m->tipo),
            ucfirst($m->reparacion),
            $m->costo,
            \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y'),
            $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente',
        ];
    }
}
