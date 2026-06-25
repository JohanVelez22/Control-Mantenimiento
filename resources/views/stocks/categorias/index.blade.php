@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('stocks.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    🏷️ Categorías de Inventario
                </h2>
                <p class="text-gray-500 font-medium mt-1">Gestiona las categorías y subcategorías de los productos del stock.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <form action="{{ route('stocks.categorias.store') }}" method="POST" class="glass-card p-6">
                @csrf
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Nueva Clasificación</h3>
                <div class="space-y-4">
                    <div>
                        <label class="field-label">Nombre</label>
                        <input type="text" name="nombre" required class="glass-input" placeholder="Ej: Pantallas, Accesorios...">
                    </div>
                    <div>
                        <label class="field-label">Tipo</label>
                        <select name="tipo" class="glass-input no-search" required>
                            <option value="categoria">Categoría Principal</option>
                            <option value="subcategoria">Subcategoría</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Crear Clasificación</button>
                </div>
            </form>
        </div>

        <div class="md:col-span-2">
            <div class="glass-card p-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Clasificaciones Existentes</h3>
                
                <ul class="space-y-3">
                    @forelse($categorias as $c)
                    <li class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 bg-white/50 dark:bg-slate-800/50 rounded-xl border border-gray-200/50 dark:border-white/5 gap-2">
                        <form action="{{ route('stocks.categorias.update', $c->id) }}" method="POST" class="flex-1 w-full flex flex-wrap sm:flex-nowrap gap-2 items-center">
                            @csrf @method('PUT')
                            <select name="tipo" class="glass-input py-1.5 px-2 text-xs w-auto no-search">
                                <option value="categoria" {{ $c->tipo == 'categoria' ? 'selected' : '' }}>Categoría</option>
                                <option value="subcategoria" {{ $c->tipo == 'subcategoria' ? 'selected' : '' }}>Subcat</option>
                            </select>
                            <input type="text" name="nombre" value="{{ $c->nombre }}" required class="glass-input flex-1 py-1.5 px-3 text-sm min-w-[120px]">
                            <button type="submit" class="btn-ghost px-3 py-1.5 text-xs text-blue-600 border-blue-500/20 hover:bg-blue-500/10">💾</button>
                        </form>
                        <form action="{{ route('stocks.categorias.destroy', $c->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta clasificación?');" class="ml-auto sm:ml-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger px-3 py-1.5 text-xs">🗑️</button>
                        </form>
                    </li>
                    @empty
                    <li class="p-4 text-center text-gray-500">No hay clasificaciones registradas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
