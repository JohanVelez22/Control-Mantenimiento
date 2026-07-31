@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('clientes.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">
                    ✏️ Editar Cliente: {{ $cliente->nombre_completo }}
                </h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del cliente registrado</p>
            </div>
        </div>

        <form method="POST" action="{{ route('clientes.update', $cliente->id) }}" class="space-y-6">
            @csrf @method('PUT')
            @include('clientes._form', ['cliente' => $cliente])
        </form>
    </div>
</div>
@endsection
