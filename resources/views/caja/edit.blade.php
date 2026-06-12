@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('caja.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Movimiento: #{{ $movimiento->id }}</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los datos del registro de caja</p>
            </div>
        </div>
        <form action="{{ route('caja.update', $movimiento->id) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            @include('caja._form', ['movimiento' => $movimiento])
            
            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('caja.index') }}" class="btn-ghost text-center py-3">Cancelar</a>
                <button type="submit" class="btn-primary shadow-blue-500/30 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 border-none justify-center py-3">
                    🔄 Actualizar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
