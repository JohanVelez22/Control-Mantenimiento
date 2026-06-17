@extends('layouts.app')

@section('content')
<style>
 /* Fila resaltada al llegar por ancla (#cliente-id) */
 tr:target {
 background-color: rgba(59, 130, 246, 0.2) !important;
 outline: 2px solid #3b82f6;
 }
</style>

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">👤</span> Clientes
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona el directorio de tus clientes corporativos y personales</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-clientes" placeholder="Buscar cliente..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('clientes.create') }}" class="btn-primary">
 ➕ Nuevo Cliente
 </a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-clientes" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Nombre</th>
 <th>Identificación</th>
 <th>Móvil</th>
 <th>Email</th>
 <th>Dirección</th>
 <th class="text-center w-32">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($clientes as $cliente)
 <tr id="cliente-{{ $cliente->id }}" class="scroll-mt-[6.5rem]">
 <td class="text-center font-bold text-slate-800 dark:text-white">{{ $cliente->id }}</td>
 <td class="font-bold text-slate-800 dark:text-white">{{ $cliente->nombre }}</td>
 <td class="font-mono text-gray-600 dark:text-gray-300">{{ $cliente->identificacion }}</td>
 <td class="font-mono">{{ $cliente->movil }}</td>
 <td>{{ $cliente->email ?? '-' }}</td>
 <td>{{ $cliente->direccion ?? '-' }}</td>
 <td class="text-center">
 <div class="flex justify-center items-center gap-1 flex-wrap">
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">
 ✏️
 </a>
 @if(auth()->user()->isAdmin())
 <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline-block" data-confirm-delete="¿Eliminar al cliente '{{ $cliente->nombre }}'? Esta acción no se puede deshacer.">
 @csrf
 @method('DELETE')
 <button type="submit" class="btn-danger px-2.5 py-1.5 text-xs" title="Eliminar">
 🗑️
 </button>
 </form>
 @endif
 @else
 <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Solo lectura">
 👁️ Lectura
 </span>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="7" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">👤</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin clientes registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra a tu primer cliente para comenzar a gestionar sus equipos y mantenimientos.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('clientes.create') }}" class="btn-primary">
 ➕ Registrar Primer Cliente
 </a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 {{ $clientes->appends(request()->query())->links() }}
 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-clientes', 'tabla-clientes'));</script>
@endsection



