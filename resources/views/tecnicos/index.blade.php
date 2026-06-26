@extends('layouts.app')

@section('content')
<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🛠️</span> Técnicos
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Controla el personal encargado de realizar los mantenimientos</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-tecnicos" placeholder="Buscar técnico..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('tecnicos.create') }}" class="btn-primary">➕ Nuevo Técnico</a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-tecnicos" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th class="w-16 text-center">Foto</th>
 <th>Nombre</th>
 <th>Identificación</th>
 <th>Especialidad</th>
 <th>Móvil</th>
 <th>Email</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($tecnicos as $tecnico)
 @php $dim = !$tecnico->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr>
 <td class="text-center font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $tecnico->id }}</td>
 <td class="text-center {{ $dim }}">
 @if($tecnico->photo)
 <img src="{{ asset('storage/' . $tecnico->photo) }}" width="40" height="40" class="rounded-xl object-cover mx-auto shadow-sm">
 @else
 <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 mx-auto text-xs font-bold shadow-sm">N/A</div>
 @endif
 </td>
 <td class="font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $tecnico->nombre }}</td>
 <td class="text-gray-600 dark:text-gray-300 {{ $dim }}">{{ $tecnico->identificacion }}</td>
 @php
     $espClass = 'pill-preventivo';
     $espStr = strtolower($tecnico->especialidad);
     if (str_contains($espStr, 'software')) $espClass = 'pill-especialidad';
     elseif (str_contains($espStr, 'hardware')) $espClass = 'pill-correctivo';
     elseif (str_contains($espStr, 'electrónic') || str_contains($espStr, 'electronic')) $espClass = 'pill-done';
     elseif (str_contains($espStr, 'redes')) $espClass = 'pill-banco';
 @endphp
 <td class="{{ $dim }}"><span class="pill {{ $espClass }}">{{ $tecnico->especialidad }}</span></td>
 <td class="{{ $dim }}">{{ $tecnico->movil }}</td>
 <td class="{{ $dim }}">{{ $tecnico->email ?? '-' }}</td>
 <td class="text-center">
 <span class="pill {{ $tecnico->active ? 'pill-done' : 'pill-anulado' }}">
 {{ $tecnico->active ? 'Activo' : 'Inactivo' }}
 </span>
 </td>
 <td class="text-center {{ $dim }}">
 <div class="flex justify-center items-center gap-1">
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('tecnicos.edit', $tecnico->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
 <button type="button" onclick="openAnularModal('{{ route('tecnicos.anular', $tecnico->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs {{ $tecnico->active ? 'text-red-600' : 'text-emerald-600' }}" title="{{ $tecnico->active ? 'Anular Técnico' : 'Reactivar Técnico' }}">
 {{ $tecnico->active ? '🚫' : '✅' }}
 </button>
 @else
 <span class="text-gray-400 text-sm">👁️ Lectura</span>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="9" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🛠️</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin técnicos registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra al personal técnico para asignar órdenes de mantenimiento.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('tecnicos.create') }}" class="btn-primary">➕ Registrar Primer Técnico</a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 {{ $tecnicos->appends(request()->query())->links() }}
 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-tecnicos', 'tabla-tecnicos'));</script>
@endsection
