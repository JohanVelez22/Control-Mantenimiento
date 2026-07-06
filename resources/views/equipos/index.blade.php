@extends('layouts.app')

@section('content')
<style>
  /* Fila resaltada al llegar por ancla (#equipo-id) */
  tr:target {
  background-color: rgba(59, 130, 246, 0.2) !important;
  outline: 2px solid #3b82f6;
  }
</style>

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🖥️</span> Equipos
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Administra y vincula los equipos a tus clientes</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-equipos" placeholder="Buscar equipo..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('equipos.create') }}" class="btn-primary">
 ➕ Nuevo Equipo
 </a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-equipos" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Equipo</th>
 <th>Serie</th>
 <th>Cliente</th>
 <th>Observación</th>
 <th>Registrado por</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($equipos as $equipo)
 @php $dim = !$equipo->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr id="equipo-{{ $equipo->id }}" class="scroll-mt-[6.5rem]">
 <td class="text-center font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $equipo->id }}</td>
 <td class="{{ $dim }}">
 <div class="font-bold text-slate-800 dark:text-white leading-tight">{{ $equipo->nombre }}</div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">{{ $equipo->marca }} {{ $equipo->modelo }}</div>
 </td>
 <td class="uppercase text-gray-600 dark:text-gray-300 {{ $dim }}">{{ $equipo->serie }}</td>
 <td class="font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $equipo->cliente->nombre ?? '-' }}</td>
 <td class="{{ $dim }}"><p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" title="{{ $equipo->observacion }}">{{ $equipo->observacion ?? '-' }}</p></td>
 <td class="{{ $dim }}"><span class="font-medium text-slate-700 dark:text-slate-300">{{ $equipo->user->name ?? '-' }}</span></td>
 <td class="text-center">
 <span class="pill {{ $equipo->active ? 'pill-done' : 'pill-anulado' }}">
 {{ $equipo->active ? 'Activo' : 'Inactivo' }}
 </span>
 </td>
 <td class="text-center {{ $dim }}">
 <div class="flex justify-center items-center gap-1">
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('equipos.edit', $equipo->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
 <button type="button" onclick="openAnularModal('{{ route('equipos.anular', $equipo->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs {{ $equipo->active ? 'text-red-600' : 'text-emerald-600' }}" title="{{ $equipo->active ? 'Anular Equipo' : 'Reactivar Equipo' }}">
 {{ $equipo->active ? '🚫' : '✅' }}
 </button>
 @else
 <span class="text-gray-400 text-sm">👁️ Lectura</span>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🖥️</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin equipos registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Comienza vinculando un equipo a un cliente para iniciar el seguimiento.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('equipos.create') }}" class="btn-primary">➕ Registrar Primer Equipo</a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 {{ $equipos->appends(request()->query())->links() }}
 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-equipos', 'tabla-equipos'));</script>
@endsection
