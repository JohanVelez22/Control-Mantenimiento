@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="glass-card p-6 md:p-6">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('clientes.index') }}" class="btn-ghost text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">👤 Registrar Nuevo Cliente</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Añade un nuevo cliente al sistema</p>
            </div>
        </div>

        <form method="POST" action="{{ route('clientes.store') }}">
            @csrf
            @include('clientes._form')
        </form>
    </div>
</div>
@endsection
