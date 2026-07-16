@extends('layouts.consulta')

@section('content')
<div class="w-full max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 text-3xl mb-4">🔍</div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">Consulta de Electrónicas</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Busque por cédula, teléfono o número de orden (ej: ELC-001)</p>
        </div>

        @if(session('error'))
            <div class="mb-5 p-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('consulta.electronicas') }}" class="mb-8">
            <div class="flex gap-3">
                <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Cédula, teléfono o número de orden (ej: 123456789, 3001234567, ELC-001)" 
                       class="flex-1 glass-input py-3 text-lg" required minlength="5" maxlength="30"
                       pattern="[\d\s\-\.#]{5,30}" title="Solo números, espacios, guiones, puntos o #">
                <button type="submit" class="btn-primary px-6 py-3 whitespace-nowrap">Buscar</button>
            </div>
        </form>

        @if($query && $electronicas->isEmpty())
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-3">📭</div>
                <p class="font-medium">No se encontraron electrónicas para "<strong>{{ $query }}</strong>"</p>
            </div>
        @elseif($electronicas->isNotEmpty())
            <div class="space-y-3">
                @foreach($electronicas as $e)
                    <a href="{{ route('electronicas.show', $e->id) }}" class="block p-4 glass-card hover:bg-white/50 dark:hover:bg-gray-800/50 transition-colors border-l-4 border-cyan-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white">{{ $e->id_orden }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $e->equipo->cliente->nombre_completo ?? 'Cliente desconocido' }} · 
                                    {{ $e->equipo->nombre ?? 'Equipo' }} · 
                                    {{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}
                                </p>
                            </div>
                            <span class="pill {{ $e->estado === 'terminado' ? 'pill-done' : 'pill-pending' }}">
                                {{ ucfirst($e->estado) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection