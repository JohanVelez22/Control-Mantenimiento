@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('electronicas.index') }}" class="text-gray-500 hover:text-purple-500 transition-colors">⬅️ Volver</a>
        <h2 class="text-2xl font-bold">⚡ Editar Registro — {{ $electronica->id_orden }}</h2>
    </div>
    <form action="{{ route('electronicas.update', $electronica->id) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        @include('electronicas._form', ['electronica' => $electronica])
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('electronicas.index') }}" class="px-6 py-2 rounded-xl text-gray-600 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 font-semibold transition-all">Cancelar</a>
            <button type="submit" class="px-6 py-2 rounded-xl bg-purple-500 text-white font-bold hover:bg-purple-600 shadow-lg shadow-purple-500/30 transition-all">Actualizar Registro</button>
        </div>
    </form>
</div>
@endsection

