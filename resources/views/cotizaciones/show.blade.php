@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8 relative overflow-hidden">
        {{-- Sello de estado --}}
        @if($cotizacion->estado === 'aprobada')
            <div class="absolute top-8 right-[-40px] bg-emerald-500 text-white text-sm font-bold px-12 py-1 rotate-45 shadow-lg">
                APROBADA
            </div>
        @elseif($cotizacion->estado === 'vencida')
            <div class="absolute top-8 right-[-40px] bg-red-500 text-white text-sm font-bold px-12 py-1 rotate-45 shadow-lg">
                VENCIDA
            </div>
        @endif

        <div class="flex items-center gap-3 mb-8 relative z-10">
            <a href="{{ route('cotizaciones.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div class="flex-1">
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📝 {{ $cotizacion->codigo }}</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Cotización formal - Creada el {{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}</p>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" target="_blank" class="btn-clean px-4">
                    📄 Ver PDF
                </a>
                @if($cotizacion->estado === 'pendiente')
                <form action="{{ route('cotizaciones.convertir', $cotizacion) }}" method="POST" onsubmit="return confirm('Al confirmar, se creará una Nueva Venta (Factura) basada en esta cotización. ¿Continuar?')">
                    @csrf
                    <button type="submit" class="btn-primary">
                        ✅ Aprobar y Facturar
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 relative z-10">
            <div class="bg-slate-50/50 dark:bg-slate-800/30 p-5 rounded-xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-xs font-bold uppercase text-slate-400 tracking-widest mb-3">Datos del Cliente</h3>
                <p class="font-bold text-lg text-slate-700 dark:text-slate-200">{{ $cotizacion->cliente->nombre }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">ID/NIT: {{ $cotizacion->cliente->identificacion }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Tel: {{ $cotizacion->cliente->movil ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50/50 dark:bg-slate-800/30 p-5 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase text-slate-400 tracking-widest mb-3">Resumen de Cotización</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400"><span class="font-semibold">Válida por:</span> {{ $cotizacion->validez_dias }} días</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1"><span class="font-semibold">Vendedor:</span> {{ $cotizacion->user->name }}</p>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400 text-right">
                        Total: ${{ number_format($cotizacion->total, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2 mb-4">
            <span>🛍️</span> Detalle Cotizado
        </h3>
        
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 mb-6 relative z-10">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3 text-center">Cant.</th>
                        <th class="px-4 py-3 text-right">Precio Un.</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white/40 dark:bg-transparent">
                    @foreach($cotizacion->items as $item)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-4 py-3 font-medium">
                            @if($item->tipo === 'stock')
                                <span class="text-emerald-600 dark:text-emerald-400 text-xs px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-md">📦 Producto</span>
                            @else
                                <span class="text-blue-600 dark:text-blue-400 text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-md">🛠️ Servicio</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                            {{ $item->descripcion }}
                            @if($item->stock)
                                <div class="text-[10px] text-slate-400">Ref Stock: {{ $item->stock->codigo }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-bold">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-white">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($cotizacion->notas)
        <div class="bg-yellow-50/50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-700/50 rounded-lg p-4 relative z-10">
            <h4 class="text-xs font-bold uppercase text-yellow-700 dark:text-yellow-500 mb-1">Notas</h4>
            <p class="text-sm text-yellow-800 dark:text-yellow-600">{{ $cotizacion->notas }}</p>
        </div>
        @endif

    </div>
</div>
@endsection
