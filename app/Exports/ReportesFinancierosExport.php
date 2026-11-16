<?php

namespace App\Exports;

use App\Exports\Concerns\FormatoHojaReporte;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportesFinancierosExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use FormatoHojaReporte;

    protected $transacciones;

    protected ?array $resumen;

    public function __construct($transacciones, ?array $resumen = null)
    {
        // Ensure it's a Laravel Collection
        $this->transacciones = collect($transacciones);
        $this->resumen = $resumen;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->formatearHoja($sheet, 'G', horizontal: false);
                $footerRow = $lastRow + 2;
                $this->fechasEn($sheet, ['B'], $lastRow);

                if ($this->resumen && isset($this->resumen['balance_neto'])) {
                    $total = (float) $this->resumen['balance_neto'];
                } else {
                    $ingresos = $this->transacciones->filter(function ($tx) {
                        $tipo = is_array($tx) ? ($tx['tipo'] ?? '') : ($tx->tipo_movimiento ?? '');
                        $anulado = is_array($tx) ? ! empty($tx['anulado']) : $tx->anulado;

                        return ! $anulado && $tipo === 'ingreso';
                    })->sum(fn ($tx) => is_array($tx) ? ($tx['monto'] ?? 0) : ($tx->monto ?? 0));

                    $egresos = $this->transacciones->filter(function ($tx) {
                        $tipo = is_array($tx) ? ($tx['tipo'] ?? '') : ($tx->tipo_movimiento ?? '');
                        $anulado = is_array($tx) ? ! empty($tx['anulado']) : $tx->anulado;

                        return ! $anulado && $tipo === 'egreso';
                    })->sum(fn ($tx) => is_array($tx) ? ($tx['monto'] ?? 0) : ($tx->monto ?? 0));

                    $total = $ingresos - $egresos;
                }

                // Aplicar formato de miles a la columna E (Costo)
                $sheet->getStyle("E5:E{$lastRow}")->getNumberFormat()->setFormatCode('"$ "#,##0');

                // Alinear columna A (Código) a la izquierda para consistencia
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $this->total($sheet, $footerRow, 'A', 'Registros', 'B', $this->transacciones->count(), '0');
                $this->total($sheet, $footerRow, 'D', 'Balance neto', 'E', (float) $total);
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
        return $this->transacciones;
    }

    public function headings(): array
    {
        return [
            ['INFORME FINANCIERO'],
            ['Generado el: '.date('d/m/Y h:i A')],
            [''],
            ['Código', 'Fecha', 'Tipo', 'Descripción / Concepto', 'Costo', 'Estado', 'Anulado'],
        ];
    }

    public function map($tx): array
    {
        $isArr = is_array($tx);

        $fecha = $isArr ? ($tx['fecha'] ?? '') : ($tx->fecha ?? $tx->fecha_entrada ?? '');
        $tipo = $isArr ? ($tx['tipo'] ?? 'N/A') : ($tx->tipo_movimiento ?? 'N/A');

        $codigo = '—';
        if ($isArr) {
            $codigo = $tx['codigo'] ?? '—';
            $desc = $tx['descripcion'] ?? '—';
        } else {
            if ($tx instanceof Mantenimiento) {
                $codigo = $tx->id_orden;
                $equipo = $tx->equipo->nombre ?? '—';
                $cliente = $tx->equipo?->propietario_nombre ?? '—';
                $desc = "{$equipo} ({$cliente})";
            } elseif ($tx instanceof Electronica) {
                $codigo = $tx->id_orden;
                $equipo = $tx->equipo->nombre ?? '—';
                $cliente = $tx->equipo?->propietario_nombre ?? '—';
                $desc = "{$equipo} ({$cliente})";
            } elseif ($tx instanceof Factura) {
                $codigo = $tx->numero_factura;
                $desc = $tx->facturable->nombre ?? $tx->facturable->nombre_razon_social ?? '—';
            } elseif ($tx instanceof MovimientoCaja) {
                $codigo = $tx->id;
                $quien = $tx->persona ?? $tx->empresa ?? 'Anónimo';
                $concepto = $tx->concepto->nombre ?? '—';
                $desc = "{$quien} — {$concepto}";
            } else {
                $desc = '—';
            }
        }

        $monto = $isArr ? ($tx['monto'] ?? 0) : ($tx->monto ?? $tx->total_documento ?? 0);
        $estado = $isArr ? ($tx['estado'] ?? '—') : ($tx->estado ?? '—');
        $anulado = $isArr ? (! empty($tx['anulado']) ? 'Sí' : 'No') : (($tx->anulado ?? (($tx->estado ?? null) === 'anulada')) ? 'Sí' : 'No');

        return [
            self::texto((string) $codigo),
            self::fechaExcel($fecha ?: null),
            ucfirst($tipo),
            $desc,
            (float) $monto,
            ucfirst($estado),
            $anulado,
        ];
    }
}
