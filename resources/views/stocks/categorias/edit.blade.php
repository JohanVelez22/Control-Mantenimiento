@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('stocks.categorias.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    🏷️ Editar Clasificación
                </h2>
                <p class="text-gray-500 font-medium mt-1">Modifica la categoría o subcategoría.</p>
            </div>
        </div>
    </div>

    <div class="glass-card p-6 max-w-xl">
        <form action="{{ route('stocks.categorias.update', $categoria->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="field-label">Tipo de Clasificación</label>
                    <select name="tipo" class="glass-input font-bold" required>
                        <option value="categoria" {{ $categoria->tipo == 'categoria' ? 'selected' : '' }}>🗂️ Categoría Principal</option>
                        <option value="subcategoria" {{ $categoria->tipo == 'subcategoria' ? 'selected' : '' }}>📂 Subcategoría</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Nombre</label>
                    <input type="text" name="nombre" value="{{ $categoria->nombre }}" required class="glass-input" placeholder="Ej: Pantallas, Accesorios...">
                </div>
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('stocks.categorias.index') }}" class="btn-ghost flex-1 justify-center">Cancelar</a>
                    <button type="submit" class="btn-primary flex-1 justify-center">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection