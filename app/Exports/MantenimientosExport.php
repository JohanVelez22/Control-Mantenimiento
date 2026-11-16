<?php

namespace App\Exports;

class MantenimientosExport extends ReporteOrdenesExport
{
    protected function titulo(): string
    {
        return 'REPORTE DE MANTENIMIENTOS';
    }
}
