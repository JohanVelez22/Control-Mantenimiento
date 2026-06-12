@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('proveedores.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Proveedor</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza la información de {{ $proveedor->nombre_razon_social }}</p>
            </div>
        </div>
        <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            @include('proveedores._form')
            
            <div class="flex gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <button type="submit" class="flex-1 btn-primary shadow-yellow-500/30 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 border-none justify-center py-3">
                    🔄 Actualizar Proveedor
                </button>
                <a href="{{ route('proveedores.index') }}" class="btn-ghost px-6 py-3 justify-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
