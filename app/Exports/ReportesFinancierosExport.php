<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportesFinancierosExport implements FromCollection, WithHeadings, WithMapping
{
    protected $transacciones;

    public function __construct($transacciones)
    {
        $this->transacciones = $transacciones;
    }

    public function collection()
    {
        return $this->transacciones;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Descripción / Concepto',
            'Monto',
            'Estado'
        ];
    }

    public function map($tx): array
    {
        $isArr = is_array($tx);
        return [
            \Carbon\Carbon::parse($isArr ? $tx['fecha'] : $tx->fecha)->format('d/m/Y'),
            ucfirst($isArr ? $tx['tipo'] : $tx->tipo_movimiento ?? 'N/A'),
            $isArr ? $tx['descripcion'] : ($tx->concepto->nombre ?? $tx->persona ?? 'N/A'),
            $isArr ? $tx['monto'] : ($tx->monto ?? $tx->total_documento ?? 0),
            ucfirst($isArr ? ($tx['estado'] ?? '—') : ($tx->estado ?? '—'))
        ];
    }
}
