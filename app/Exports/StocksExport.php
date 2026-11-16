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

class StocksExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use FormatoHojaReporte;

    protected $stocks;

    public function __construct($stocks)
    {
        $this->stocks = $stocks;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // X-11: la combinación del título cubre las 11 columnas (antes A:J, sin Estado).
                $lastRow = $this->formatearHoja($sheet, 'K');
                $this->formatoColumnas($sheet, ['G', 'I', 'J'], self::FORMATO_MONEDA, $lastRow);
                $footerRow = $lastRow + 2;

                // Valor del inventario (cantidad × precio), no la suma de precios unitarios
                $this->total($sheet, $footerRow, 'A', 'Registros', 'B', count($this->stocks), '0');
                $this->total($sheet, $footerRow, 'E', 'Unidades', 'F', (int) $this->stocks->sum('cantidad'), '#,##0');
                $this->total($sheet, $footerRow + 1, 'F', 'Valor compra', 'G', (float) $this->stocks->sum(fn ($s) => (float) $s->cantidad * (float) $s->precio_compra));
                $this->total($sheet, $footerRow + 2, 'F', 'Valor venta', 'G', (float) $this->stocks->sum(fn ($s) => (float) $s->cantidad * (float) $s->precio_venta));
                $this->total($sheet, $footerRow + 3, 'F', 'Valor técnico', 'G', (float) $this->stocks->sum(fn ($s) => (float) $s->cantidad * (float) $s->precio_tecnico));
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
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4A5568'],
                ],
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
            ['Generado el: '.date('d/m/Y h:i A')],
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
                'Estado',
            ],
        ];
    }

    public function map($s): array
    {
        return [
            self::texto($s->codigo ?? '-'),
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
