@extends('layouts.app')

@section('content')
<style>
@media print {
    .no-print, nav, aside, header, footer, form, button { display: none !important; }
    a:not(.no-print-link) { display: none !important; }
    .no-print-link { color: black !important; text-decoration: none !important; cursor: default !important; }
    body { background: white !important; color: black !important; margin: 1cm !important; padding: 0 !important; }
    .shadow, .rounded-lg { box-shadow: none !important; border: none !important; }
    table { width: 100% !important; border: 1px solid #000 !important; font-size: 8pt !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #000 !important; padding: 4px !important; }
    h2 { text-align: center !important; font-size: 18pt !important; margin-bottom: 20px !important; }
}
</style>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-sm text-gray-500 font-bold uppercase">💻 Total de Equipos</div>
        <div class="text-2xl font-bold">{{ $totalEquipos ?? 0 }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="text-sm text-gray-500 font-bold uppercase">🔧 Mantenimientos</div>
        <div class="text-2xl font-bold">{{ $totalMantenimientos ?? 0 }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <div class="text-sm text-gray-500 font-bold uppercase">⏳ Pendientes</div>
        <div class="text-2xl font-bold">{{ $stats['pendientes'] ?? 0 }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-purple-500">
        <div class="text-sm text-gray-500 font-bold uppercase">✅ Terminados</div>
        <div class="text-2xl font-bold">{{ $stats['terminados'] ?? 0 }}</div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
    <div class="text-sm text-gray-500 font-bold uppercase">Costo Total Acumulado</div>
    <div class="text-2xl font-bold text-green-600">💰${{ $totalCostoFormateado ?? '0.00' }}</div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
    <h3 class="text-xl font-bold mb-4">Mantenimientos Recientes</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700 text-xs uppercase">
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Costo</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Entrada</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Días</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Salida</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($recentMant ?? [] as $m)
                @php
                    $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
                    $fechaFin = $m->fecha_salida 
                        ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() 
                        : \Carbon\Carbon::now()->startOfDay();
                    $diasTranscurridos = $fechaEntrada->diffInDays($fechaFin);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="p-3 text-center font-bold whitespace-nowrap border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('mantenimientos.index') }}#mantenimiento-{{ $m->id }}" class="text-blue-600 hover:text-blue-800 hover:underline no-print-link">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    
                    {{-- Celda de Equipo: Nombre al lado de Marca/Modelo --}}
                    <td class="p-3 text-center border border-gray-300 dark:border-gray-500">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $m->equipo->nombre ?? '-' }}
                            </span>
                            <span class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
                                ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }})
                            </span>
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $m->equipo->serie ?? '' }}
                            </span>
                        </div>
                    </td>

                    <td class="p-3 text-center font-bold text-green-600 border border-gray-300 dark:border-gray-500">
                        ${{ number_format($m->costo, 2) }}
                    </td>

                    <td class="p-3 text-center border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded text-[11px] font-bold text-white {{ $m->estado == 'terminado' ? 'bg-green-500' : 'bg-yellow-500' }}">
                            {{ strtoupper($m->estado) }}
                        </span>
                    </td>

                    <td class="p-3 text-center whitespace-nowrap border border-gray-300 dark:border-gray-500">{{ $fechaEntrada->format('d/m/Y') }}</td>
                    
                    <td class="p-3 text-center font-bold border border-gray-300 dark:border-gray-500">
                        <span class="{{ !$m->fecha_salida && $diasTranscurridos > 3 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $diasTranscurridos }}
                        </span>
                    </td>

                    <td class="p-3 text-center whitespace-nowrap border border-gray-300 dark:border-gray-500 {{ $m->fecha_salida ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-gray-400 italic' }}">
                        {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'En proceso' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4 text-right">
        <a href="{{ route('mantenimientos.reportes') }}" class="text-blue-600 font-bold hover:underline">📈 Ver reporte detallado →</a>
    </div>
</div>
@endsection
