@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('equipos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Equipo: {{ $equipo->nombre }}</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del dispositivo registrado</p>
            </div>
        </div>

        @if($equipo->estado === 'dado_de_baja')
        <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-amber-500 rounded-l-2xl"></div>
            <div class="pl-2">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xl">⚠️</span>
                    <h4 class="text-sm font-black text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                        Este equipo se encuentra Dado de Baja
                    </h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs mt-3">
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-amber-500/20">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold block mb-0.5">Motivo de Daño / Descarte:</span>
                        <span class="font-bold text-amber-700 dark:text-amber-400 text-sm">{{ $equipo->motivo_baja_label }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-amber-500/20">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold block mb-0.5">Autorizado Por:</span>
                        <span class="font-bold text-slate-800 dark:text-white">{{ $equipo->bajaUser->name ?? 'Sistema' }}</span>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400 block">{{ $equipo->fecha_baja ? $equipo->fecha_baja->format('d/m/Y h:i A') : '-' }}</span>
                    </div>
                </div>
                @if($equipo->observacion_baja)
                <div class="mt-3 p-3 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-amber-500/20 text-xs">
                    <span class="text-gray-500 dark:text-gray-400 font-semibold block mb-1">Diagnóstico u Observación Técnica:</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">{{ $equipo->observacion_baja }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('equipos.update', $equipo->id) }}">
            @csrf
            @method('PUT')
            @include('equipos._form')
        </form>
    </div>
</div>
@endsection
