<?php

namespace App\Exports;

use App\Exports\Concerns\FormatoHojaReporte;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reporte de órdenes (Mantenimiento y Electrónica comparten formato y columnas; antes eran
 * dos clases copiadas que ya habían divergido).
 */
abstract class ReporteOrdenesExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use FormatoHojaReporte;

    public function __construct(protected $ordenes) {}

    abstract protected function titulo(): string;

    public function collection()
    {
        return $this->ordenes;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaFila = $this->formatearHoja($sheet, 'O');
                $this->formatoColumnas($sheet, ['M'], self::FORMATO_MONEDA, $ultimaFila);
                $this->fechasEn($sheet, ['N', 'O'], $ultimaFila);

                // X-06: el total excluye las anuladas, igual que la pantalla y el PDF.
                $pie = $ultimaFila + 2;
                $this->total($sheet, $pie, 'A', 'Registros', 'B', count($this->ordenes), '0');
                $this->total($sheet, $pie, 'L', 'Total (sin anuladas)', 'M', (float) $this->ordenes->where('anulado', false)->sum('costo'));
                $anuladas = $this->ordenes->where('anulado', true);
                if ($anuladas->isNotEmpty()) {
                    $this->total($sheet, $pie + 1, 'L', 'Anuladas ('.$anuladas->count().')', 'M', (float) $anuladas->sum('costo'));
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true, 'size' => 12]],
            self::FILA_ENCABEZADO => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A5568']],
            ],
        ];
    }

    public function headings(): array
    {
        return [
            [$this->titulo()],
            ['Generado el: '.date('d/m/Y h:i A')],
            [''],
            ['Orden', 'Cliente', 'Identificación', 'Equipo', 'Marca', 'Modelo', 'Serie', 'Técnico', 'Tipo',
                'Reparación', 'Progreso', 'Estado', 'Costo', 'Fecha Entrada', 'Fecha Salida'],
        ];
    }

    public function map($orden): array
    {
        return [
            self::texto($orden->id_orden),
            $orden->equipo?->propietario_nombre ?: 'N/A',
            self::texto($orden->equipo?->propietario_identificacion ?: null),
            $orden->equipo->nombre ?? 'N/A',
            $orden->equipo->marca ?? 'N/A',
            $orden->equipo->modelo ?? 'N/A',
            self::texto($orden->equipo->serie ?? null),
            $orden->tecnico->nombre ?? 'N/A',
            ucfirst((string) $orden->tipo),
            ucfirst((string) ($orden->reparacion ?? '')),
            ucfirst((string) $orden->estado),
            $orden->anulado ? 'Anulado' : 'Activo',
            (float) $orden->costo,
            self::fechaExcel($orden->fecha_entrada),
            self::fechaExcel($orden->fecha_salida),
        ];
    }
}
