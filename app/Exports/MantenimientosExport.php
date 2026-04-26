<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MantenimientosExport implements FromCollection, WithHeadings, WithMapping
{
    protected $mantenimientos;

    public function __construct($mantenimientos)
    {
        $this->mantenimientos = $mantenimientos;
    }

    public function collection()
    {
        return $this->mantenimientos;
    }

    public function headings(): array
    {
        return [
            'Orden', 
            'Cliente', 
            'Equipo', 
            'Marca',  
            'Modelo', 
            'Técnico', 
            'Tipo', 
            'Reparación', 
            'Costo', 
            'Fecha Entrada', 
            'Fecha Salida'
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
            $m->tecnico->nombre ?? 'N/A',
            ucfirst($m->tipo),
            ucfirst($m->reparacion),
            $m->costo,
            \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y'),
            $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente',
        ];
    }
}
