{{-- B-04: historial de la orden (cambios, repuestos, abonos, cierres y anulaciones). Solo administradores. --}}
@if(isset($historial) && auth()->user()?->isAdmin())
<section class="glass-card p-6 md:p-8 mt-6" aria-labelledby="historial-orden-titulo">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h2 id="historial-orden-titulo" class="text-lg font-bold text-slate-800 dark:text-white">Historial de la orden</h2>
        @if($historialUrl ?? null)
            <a href="{{ $historialUrl }}" class="btn-ghost text-xs font-semibold">Ver en Auditoría</a>
        @endif
    </div>

    @if($historial->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">Sin eventos registrados para esta orden.</p>
    @else
        <ol class="space-y-2 text-sm">
            @foreach($historial as $evento)
                <li class="flex flex-wrap gap-x-3 gap-y-1 border-b border-slate-200/60 dark:border-slate-700/60 pb-2">
                    <time datetime="{{ $evento->created_at?->toIso8601String() }}" class="text-xs text-slate-500 dark:text-slate-400 w-32 shrink-0">
                        {{ $evento->created_at?->format('d/m/Y H:i') }}
                    </time>
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 w-28 shrink-0">{{ $evento->user->name ?? 'Sistema' }}</span>
                    <span class="flex-1 min-w-[12rem] text-slate-700 dark:text-slate-200">{{ $evento->descripcion ?? $evento->accion }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
@endif
