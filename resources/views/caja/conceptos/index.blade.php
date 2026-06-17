@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                🏷️ Conceptos de Caja
            </h2>
            <p class="text-gray-500 font-medium mt-2">Gestiona las categorías o conceptos para ingresos y egresos.</p>
        </div>
        <a href="{{ route('caja.index') }}" class="btn-ghost">⬅️ Volver a Caja</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <form action="{{ route('conceptos.store') }}" method="POST" class="glass-card p-6">
                @csrf
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Nuevo Concepto</h3>
                <div class="space-y-4">
                    <div>
                        <label class="field-label">Nombre del Concepto</label>
                        <input type="text" name="nombre" required class="glass-input" placeholder="Ej: Pago de Internet">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Crear Concepto</button>
                </div>
            </form>
        </div>

        <div class="md:col-span-2">
            <div class="glass-card p-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Conceptos Existentes</h3>
                
                <ul class="space-y-3">
                    @forelse($conceptos as $c)
                    <li class="flex items-center justify-between p-3 bg-white/50 dark:bg-slate-800/50 rounded-xl border border-gray-200/50 dark:border-white/5">
                        <form action="{{ route('conceptos.update', $c->id) }}" method="POST" class="flex-1 flex gap-2 mr-4">
                            @csrf @method('PUT')
                            <input type="text" name="nombre" value="{{ $c->nombre }}" required class="glass-input flex-1 py-1.5 px-3 text-sm">
                            <button type="submit" class="btn-ghost px-3 py-1.5 text-xs text-blue-600 border-blue-500/20 hover:bg-blue-500/10">💾 Guardar</button>
                        </form>
                        <form action="{{ route('conceptos.destroy', $c->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este concepto?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger px-3 py-1.5 text-xs">🗑️</button>
                        </form>
                    </li>
                    @empty
                    <li class="p-4 text-center text-gray-500">No hay conceptos registrados.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
