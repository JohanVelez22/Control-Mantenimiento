<?php

namespace App\Exports;

class ElectronicasExport extends ReporteOrdenesExport
{
    protected function titulo(): string
    {
        return 'REPORTE DE ELECTRÓNICA';
    }
}
