@extends('layouts.app')
@section('content')
<style>
 /* Fila resaltada al llegar por ancla (#mantenimiento-id) */
 tr:target {
 background-color: rgba(59, 130, 246, 0.1) !important;
 outline: 2px solid rgba(59, 130, 246, 0.5);
 outline-offset: -2px;
 }
 .dark tr:target {
 background-color: rgba(59, 130, 246, 0.2) !important;
 }
</style>

{{-- Modal de contraseña para anular --}}
<div id="pwd-anular-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
 <div class="ts-modal-card scale-95 opacity-0" id="pwd-anular-card">
 <div class="p-6">
 <div class="w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-500 flex items-center justify-center text-3xl mx-auto mb-4">
 🚫
 </div>
 <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Confirmar Anulación</h3>
 <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
 Ingresa tu contraseña para anular esta orden. Se mantendrá el historial pero se marcará como anulada.
 </p>
 <form id="anular-pwd-form" method="POST" class="space-y-4">
 @csrf
 <div>
 <input type="password" name="password_confirm" id="pwd-anular-input" required placeholder="Contraseña..." class="glass-input text-center tracking-widest text-lg focus:ring-orange-500">
 </div>
 <div class="flex gap-3 pt-2">
 <button type="button" onclick="closeAnularModal()" class="flex-1 btn-ghost justify-center">Cancelar</button>
 <button type="submit" class="flex-1 text-center justify-center font-bold text-white py-2.5 rounded-xl transition-all">Anular Orden</button>
 </div>
 </form>
 </div>
 </div>
</div>

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🔧</span> Órdenes de Mantenimiento
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona los mantenimientos de equipos de los clientes</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
 <div class="relative">
 <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
 <input type="text" id="search-mantenimientos" placeholder="Buscar orden..." class="glass-input pl-9 w-48 sm:w-64 text-sm">
 </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('mantenimientos.create') }}" class="btn-primary text-sm">
 ➕ Nueva Orden
 </a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-mantenimientos" class="ts-table responsive-table w-full">
 <thead>
 <tr>
 <th class="w-20 text-center">Orden</th>
 <th class="text-center">Equipo</th>
 <th class="text-center">Cliente</th>
 <th class="text-center">Técnico</th>
 <th class="text-center">Tipo</th>
 <th class="text-center">Observación</th>
 <th class="text-center">Costo</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-24">Entrada</th>
 <th class="text-center w-24">Salida</th>
 <th class="text-center w-32">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($mantenimientos as $m)
 <tr id="mantenimiento-{{ $m->id }}" class="{{ $m->estado === 'anulado' ? 'row-anulado' : '' }}">
 <td class="text-center font-bold">
 <a href="#mantenimiento-{{ $m->id }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors">
 {{ $m->id_orden }}
 </a>
 </td>
 
 <td>
 <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="group block hover:bg-gray-50 dark:hover:bg-gray-800/50 p-1.5 -ml-1.5 rounded-lg transition-colors" title="Ver en tabla de equipos">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
 {{ $m->equipo->nombre ?? '-' }}
 </div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}
 </div>
 <div class="text-[10px] text-gray-400">
 {{ $m->equipo->serie ?? '' }}
 </div>
 </a>
 </td>
 
 <td>
 <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="group block hover:bg-gray-50 dark:hover:bg-gray-800/50 p-1.5 -ml-1.5 rounded-lg transition-colors" title="Ver en tabla de clientes">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
 {{ $m->equipo->cliente->nombre ?? '-' }}
 </div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ $m->equipo->cliente->identificacion ?? '-' }}
 </div>
 </a>
 </td>
 
 <td class="text-center font-medium text-sm">{{ $m->tecnico->nombre ?? '-' }}</td>
 
 <td class="text-center">
 <div class="font-bold text-slate-800 dark:text-white capitalize text-sm">{{ $m->tipo }}</div>
 <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ $m->reparacion }}</div>
 </td>
 
 <td class="max-w-[250px]">
 <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-normal break-words leading-relaxed font-medium">
 {{ $m->descripcion ?? '-' }}
 </p>
 </td>
 
 <td class="text-right font-black text-blue-600 dark:text-cyan-400">
 ${{ number_format($m->costo, 0, ',', '.') }}
 </td>
 
 <td class="text-center">
 @if($m->estado === 'anulado')
 <span class="pill pill-anulado" title="Anulado">🚫 Anulado</span>
 @else
 @php
 $estadoIcon = '⏳';
 if(in_array($m->estado, ['terminado', 'entregado'])) $estadoIcon = '✅';
 elseif($m->estado === 'en_proceso') $estadoIcon = '⚙️';
 elseif($m->estado === 'reparado') $estadoIcon = '🔧';
 @endphp
 <span class="pill {{ in_array($m->estado, ['terminado', 'entregado']) ? 'pill-done' : 'pill-pending' }}">
 {{ $estadoIcon }} {{ ucfirst($m->estado) }}
 </span>
 @endif
 </td>
 
 <td class="text-center font-medium text-sm text-slate-800 dark:text-slate-200">
 {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
 @php 
 $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
 $fechaFin = $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() : \Carbon\Carbon::now()->startOfDay();
 $dias = $fechaEntrada->diffInDays($fechaFin);
 @endphp
 <div class="mt-1 text-xs font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400') }}">
 ({{ $dias }} d)
 </div>
 </td>
 
 <td class="text-center font-medium text-sm {{ $m->fecha_salida ? 'text-slate-800 dark:text-white' : 'text-gray-400 italic' }}">
 {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '-' }}
 </td>
 
 <td class="text-center">
 <div class="flex justify-center gap-1.5 flex-wrap">
 <a href="{{ route('mantenimientos.show', $m->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Ver detalle">👁️</a>
 
 @if($m->estado === 'terminado' && $m->fecha_salida)
 <a href="{{ route('mantenimientos.factura', $m->id) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-700 hover:bg-green-50/50" title="Factura POS">🖨️</a>
 @elseif($m->estado === 'terminado')
 <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Requiere fecha de salida para facturar">🖨️</span>
 @endif

 @if(!auth()->user()->isInvitado())
 <a href="{{ route('mantenimientos.edit', $m->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">✏️</a>

 @if($m->estado !== 'anulado')
 <button type="button" onclick="openAnularModal('{{ route('mantenimientos.anular', $m->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs text-orange-600 border-orange-500/20 hover:bg-orange-500/10" title="Anular orden">🚫</button>
 @endif

 @if(auth()->user()->isAdmin())
 <form action="{{ route('mantenimientos.destroy', $m->id) }}" method="POST" class="inline" data-confirm-delete="¿Eliminar definitivamente la orden '{{ $m->id_orden }}'?">
 @csrf @method('DELETE')
 <button class="btn-danger px-2.5 py-1.5 text-xs" title="Eliminar">🗑️</button>
 </form>
 @endif
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="11" class="p-16 text-center">
 <div class="flex flex-col items-center justify-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🔧</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin mantenimientos registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra la primera orden de mantenimiento de un equipo.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('mantenimientos.create') }}" class="btn-primary">
 ➕ Crear Primera Orden
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
 {{ $mantenimientos->appends(request()->query())->links() }}
 </div>
</div>

<script>
 document.addEventListener('DOMContentLoaded', () => {
 if(typeof filterTable === 'function') {
 filterTable('search-mantenimientos', 'tabla-mantenimientos');
 }
 });

 function openAnularModal(actionUrl) {
 const modal = document.getElementById('pwd-anular-modal');
 const card = document.getElementById('pwd-anular-card');
 document.getElementById('anular-pwd-form').action = actionUrl;
 document.getElementById('pwd-anular-input').value = '';
 modal.classList.remove('hidden');
 setTimeout(() => {
 modal.classList.remove('opacity-0');
 card.classList.remove('scale-95', 'opacity-0');
 document.getElementById('pwd-anular-input').focus();
 }, 10);
 }
 
 function closeAnularModal() {
 const modal = document.getElementById('pwd-anular-modal');
 const card = document.getElementById('pwd-anular-card');
 modal.classList.add('opacity-0');
 card.classList.add('scale-95', 'opacity-0');
 setTimeout(() => modal.classList.add('hidden'), 300);
 }
 
 document.addEventListener('keydown', e => { 
 if (e.key === 'Escape') closeAnularModal(); 
 });
</script>
@endsection
