@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
    <div>
        <h1 class="text-3xl font-black text-gray-800 dark:text-white tracking-tight flex items-center gap-3">
            <span class="text-4xl drop-shadow-md">📝</span>
            Cotizaciones
        </h1>
        <p class="text-gray-500 dark:text-gray-400 font-medium mt-1">
            Gestiona presupuestos para clientes sin afectar caja ni stock.
        </p>
    </div>

    @if(auth()->user() && auth()->user()->role !== 'invitado')
    <a href="{{ route('cotizaciones.create') }}" class="btn-primary group">
        <span class="text-lg group-hover:scale-110 transition-transform">➕</span>
        Nueva Cotización
    </a>
    @endif
</div>

<div class="glass-card">
    <div class="overflow-x-auto pb-2">
        <table class="ts-table responsive-table w-full">
            <thead>
                <tr>
                    <th class="w-24 text-left">Código</th>
                    <th class="text-left">Cliente</th>
                    <th class="w-32 text-center">Fecha</th>
                    <th class="w-32 text-center">Validez</th>
                    <th class="w-32 text-right">Total</th>
                    <th class="w-32 text-center">Estado</th>
                    <th class="w-24 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $cot)
                <tr>
                    <td class="font-bold text-slate-600 dark:text-slate-300">{{ $cot->codigo }}</td>
                    <td class="font-bold text-slate-800 dark:text-white">{{ $cot->cliente->nombre ?? 'N/A' }}</td>
                    <td class="text-center font-medium">{{ \Carbon\Carbon::parse($cot->fecha)->format('d/m/Y') }}</td>
                    <td class="text-center font-medium">{{ $cot->validez_dias }} días</td>
                    <td class="text-right font-bold text-slate-800 dark:text-white">${{ number_format($cot->total, 0, '', '.') }}</td>
                    <td class="text-center">
                        <span class="pill {{ $cot->estado === 'aprobada' ? 'pill-done' : ($cot->estado === 'rechazada' ? 'pill-anulado' : 'pill-pending') }}">
                            {{ ucfirst($cot->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="flex justify-center gap-1.5 flex-wrap">
                            <a href="{{ route('cotizaciones.show', $cot) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-indigo-600" title="Ver detalle">👁️</a>
                            
                            <a href="{{ route('cotizaciones.pdf', $cot) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-gray-600 hover:text-gray-800" title="Ver PDF">📄</a>

                            @if($cot->estado === 'pendiente' && (!auth()->user() || auth()->user()->role !== 'invitado'))
                                <a href="{{ route('cotizaciones.edit', $cot) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>

                                <button type="button" onclick="openAnularModal('{{ route('cotizaciones.rechazar', $cot) }}', false)" class="btn-ghost px-2.5 py-1.5 text-xs text-red-600 border-red-500/20 hover:bg-red-500/10" title="Rechazar cotización">
                                    ❌
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center p-12 bg-white/30 dark:bg-slate-800/30">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="text-5xl opacity-80">📝</div>
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No hay cotizaciones aún</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Las cotizaciones creadas aparecerán aquí.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 px-4 pb-4">
        {{ $cotizaciones->links() }}
    </div>
</div>
@endsection
