@extends('layouts.app')
@section('content')
<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🏭</span> Proveedores
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona personas y empresas que te suministran productos</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-proveedores" placeholder="Buscar proveedor..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('proveedores.create') }}" class="btn-primary ml-2 ">
 ➕ Nuevo Proveedor
 </a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-proveedores" class="ts-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Tipo</th>
 <th>Identificación</th>
 <th>Nombre / Razón Social</th>
 <th>Teléfono</th>
 <th>Email</th>
 <th class="text-center">Stock Asociado</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($proveedores as $p)
 @php $dim = !$p->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr>
 <td class="text-center font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $p->id }}</td>
 <td class="{{ $dim }}">
 <span class="pill {{ $p->tipo_entidad === 'empresa' ? 'pill-done' : 'pill-pending' }}">
 {{ $p->tipo_entidad === 'empresa' ? '🏢 Empresa' : '👤 Persona' }}
 </span>
 </td>
 <td class="font-bold text-sm tracking-tight text-slate-700 dark:text-slate-300 {{ $dim }}">{{ $p->identificacion }}</td>
 <td class="font-bold text-slate-800 dark:text-white {{ $dim }}">{{ $p->nombre_razon_social }}</td>
 <td class="font-medium {{ $dim }}">{{ $p->telefono ?? '—' }}</td>
 <td class="text-sm font-medium {{ $dim }}">{{ $p->email ?? '—' }}</td>
 <td class="text-center font-black text-blue-600 dark:text-cyan-400 {{ $dim }}">
 {{ $p->stocks_count ?? $p->stocks()->count() }}
 </td>
 <td class="text-center">
 <span class="pill {{ $p->active ? 'pill-done' : 'pill-anulado' }}">
 {{ $p->active ? 'Activo' : 'Inactivo' }}
 </span>
 </td>
 <td class="{{ $dim }}">
 <div class="flex justify-center gap-2">
 <a href="{{ route('proveedores.show', $p->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Ver Detalles">👁️</a>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('proveedores.edit', $p->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Editar">✏️</a>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="9" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🏭</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin proveedores registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Agrega el primer proveedor para gestionar el abastecimiento de inventario.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('proveedores.create') }}" class="btn-primary">➕ Agregar Proveedor</a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 {{ $proveedores->appends(request()->query())->links() }}
 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-proveedores', 'tabla-proveedores'));</script>
@endsection
