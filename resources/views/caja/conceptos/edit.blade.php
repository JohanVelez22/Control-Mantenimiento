@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('conceptos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    🏷️ Editar Concepto
                </h2>
                <p class="text-gray-500 font-medium mt-1">Modifica el nombre del concepto de caja.</p>
            </div>
        </div>
    </div>

    <div class="glass-card p-6 max-w-xl">
        <form action="{{ route('conceptos.update', $concepto->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="field-label">Nombre del Concepto</label>
                    <input type="text" name="nombre" value="{{ $concepto->nombre }}" required class="glass-input" placeholder="Ej: Pago de Internet">
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('conceptos.index') }}" class="btn-ghost flex-1 justify-center">Cancelar</a>
                    <button type="submit" class="btn-save flex-1 justify-center">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection