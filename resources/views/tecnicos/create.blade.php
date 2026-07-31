@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('tecnicos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">👨🏻‍🔧 Registrar Nuevo Técnico</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Añade un nuevo técnico al sistema</p>
            </div>
        </div>

        <form method="POST" action="{{ route('tecnicos.store') }}" enctype="multipart/form-data">
            @csrf
            @include('tecnicos._form')
        </form>
    </div>
</div>
@endsection
