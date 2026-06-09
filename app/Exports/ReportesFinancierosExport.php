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
        // ID, Producto, Proveedor, Precio Compra, Utilidad, Precio Venta, Tipo de Pago y Estado (solicitado en el prompt, aunque parece una mezcla de Caja y Stock, lo adaptaremos a la tabla caja y mantenimientos)
        return [
            'ID',
            'Fecha',
            'Concepto / Producto',
            'Persona / Proveedor',
            'Tipo / Utilidad',
            'Monto / Precio',
            'Tipo de Pago',
            'Estado'
        ];
    }

    public function map($tx): array
    {
        return [
            $tx->id,
            \Carbon\Carbon::parse($tx->fecha)->format('d/m/Y'),
            $tx->concepto->nombre ?? 'N/A',
            $tx->persona ?? 'N/A',
            $tx->tipo_movimiento,
            $tx->monto,
            $tx->tipo_pago,
            $tx->estado
        ];
    }
}
