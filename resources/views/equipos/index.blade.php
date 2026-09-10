@extends('layouts.app')

@section('content')

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
 <th>Propietario</th>
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
 <td class="{{ $dim }}">
  @if($equipo->cliente)
  <a href="{{ route('clientes.index') }}#cliente-{{ $equipo->cliente_id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  👤 {{ $equipo->cliente->nombre }}
  </div>
  @if($equipo->cliente->identificacion)
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $equipo->cliente->identificacion }}
  </div>
  @endif
  </a>
  @elseif($equipo->proveedor)
  <a href="{{ route('proveedores.index') }}#proveedor-{{ $equipo->proveedor_id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de proveedores">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  🏢 {{ $equipo->proveedor->nombre_razon_social }}
  </div>
  @if($equipo->proveedor->identificacion)
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $equipo->proveedor->identificacion }}
  </div>
  @endif
  </a>
  @else
  <span class="font-bold text-slate-800 dark:text-white">-</span>
  @endif
  </td>
 <td class="{{ $dim }}"><p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" title="{{ $equipo->observacion }}">{{ $equipo->observacion ?? '-' }}</p></td>
 <td class="{{ $dim }}"><span class="font-medium text-slate-700 dark:text-slate-300">{{ $equipo->user->name ?? '-' }}</span></td>
  <td class="text-center">
   @if($equipo->estado === 'dado_de_baja')
   @php
       $detallePayload = [
           'nombre' => $equipo->nombre,
           'serie' => $equipo->serie ?: ($equipo->modelo ?: 'S/N'),
           'propietario' => $equipo->propietario_label,
           'motivo' => $equipo->motivo_baja_label,
           'observacion' => $equipo->observacion_baja ?: 'Sin observaciones registradas',
           'autor' => $equipo->bajaUser ? $equipo->bajaUser->name : ($equipo->user ? $equipo->user->name : 'Sistema'),
           'fecha' => $equipo->fecha_baja ? $equipo->fecha_baja->format('d/m/Y h:i A') : 'No registrada'
       ];
   @endphp
   <div class="inline-flex flex-col items-center">
        <span class="pill pill-pending" title="{{ $equipo->motivo_baja_label }}">
            ⚠️ Dado de Baja
        </span>
       <button type="button" 
           onclick='openDetalleBajaModal(@json($detallePayload))'
           class="mt-1 text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline flex items-center gap-1 transition cursor-pointer">
           <span>📋</span> Ver diagnóstico
       </button>
   </div>
   @else
   <span class="pill {{ $equipo->active ? 'pill-done' : 'pill-anulado' }}">
       {{ $equipo->active ? 'Activo' : 'Inactivo' }}
   </span>
   @endif
   </td>
 <td data-label="Acciones:" class="text-center w-36 {{ $dim }}">
    <div class="actions-grid">
    @if(!auth()->user()->isInvitado())
    <a href="{{ route('equipos.edit', $equipo->id) }}" class="btn-ghost btn-action-edit w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600 dark:text-yellow-400 hover:bg-yellow-500/10" title="Editar">✏️</a>
    
    @if($equipo->estado !== 'dado_de_baja')
    <button type="button" onclick="openBajaEquipoModal('{{ route('equipos.dar-de-baja', $equipo->id) }}', '{{ addslashes($equipo->nombre) }} ({{ addslashes($equipo->serie ?? $equipo->modelo ?? '') }})')" class="btn-ghost btn-action-baja w-8 h-8 flex items-center justify-center p-0 text-xs text-purple-600 dark:text-purple-400 hover:bg-purple-500/10" title="Dar de baja equipo (irreparable / desguace)">
        ⚠️
    </button>
    <button type="button" onclick="openAnularModal('{{ route('equipos.anular', $equipo->id) }}', {{ !$equipo->active ? 'true' : 'false' }})" class="btn-ghost btn-action-anular w-8 h-8 flex items-center justify-center p-0 text-xs {{ $equipo->active ? 'text-red-600 dark:text-red-400 hover:bg-red-500/10' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10' }}" title="{{ $equipo->active ? 'Anular Equipo' : 'Reactivar Equipo' }}">
        {{ $equipo->active ? '🚫' : '✅' }}
    </button>
    @else
     <button type="button" 
         onclick='openDetalleBajaModal(@json($detallePayload))'
         class="btn-ghost btn-action-view w-8 h-8 flex items-center justify-center p-0 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-500/10" title="Ver Diagnóstico y Motivo de Baja">
         📋
     </button>
    <button type="button" onclick="openAnularModal('{{ route('equipos.reactivar', $equipo->id) }}', true)" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10" title="Reactivar Equipo dado de baja">
        ✅
    </button>
    @endif
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
  @if($equipos->hasPages())
  <div class="mt-5 flex justify-end">
  {{ $equipos->appends(request()->query())->links() }}
  </div>
  @endif
</div>

<!-- MODAL DAR DE BAJA EQUIPO (Simétrico al Modal de Notificaciones) -->
<div id="baja-equipo-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="baja-equipo-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">
        
        {{-- Header simétrico a notificaciones --}}
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl shrink-0 select-none">⚠️</span>
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Dar de Baja Equipo</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Declarar equipo irreparable o desecho técnico</p>
            </div>
        </div>

        <form id="baja-equipo-form" method="POST" action="">
            @csrf
            {{-- Contenedor Central al mismo ancho que el modal de notificaciones --}}
            <div class="w-full max-w-[450px] mx-auto flex flex-col flex-1 pb-2">
                
                {{-- Bloque resumen del equipo con barra de acento --}}
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 relative overflow-hidden mb-4">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500 rounded-l-xl"></div>
                    <div class="pl-2.5 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Equipo en Custodia</span>
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-md">Retiro de servicio</span>
                        </div>
                        <p id="baja-equipo-nombre" class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate"></p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">
                            ⚠️ El equipo no podrá recibir nuevos mantenimientos. El historial previo permanece intacto.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivo de la baja:</label>
                        <select name="motivo_baja" id="baja-equipo-motivo" required class="glass-input no-search text-sm w-full" data-placeholder="Seleccione el motivo de la baja...">
                            <option value="">Seleccione el motivo de la baja...</option>
                            <option value="irreparable" selected>❌ Daño irreparable / Falla crítica en placa</option>
                            <option value="desguace_repuestos">⚙️ Desguace / Canibalización para repuestos</option>
                            <option value="chatarrizacion">🗑️ Chatarrización / Desecho definitivo</option>
                            <option value="siniestro">💥 Siniestro / Pérdida total</option>
                            <option value="abandonado">📦 Equipo abandonado por cliente/propietario</option>
                            <option value="otro">📝 Otro motivo técnico</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Diagnóstico u observación técnica:</label>
                        <textarea name="observacion_baja" id="baja-equipo-observacion" rows="2" placeholder="Describe la causa técnica del descarte..." class="glass-input text-xs w-full"></textarea>
                    </div>

                    @if(auth()->user()->isTecnico())
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Contraseña de Administrador:
                        </label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" class="glass-input text-center tracking-widest text-sm w-full">
                    </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 w-full modal-divider">
                <button type="button" onclick="closeBajaEquipoModal()" class="flex-1 btn-ghost border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-ghost border-blue-500/30 text-blue-600 dark:text-blue-400 hover:bg-blue-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    ⚠️ Confirmar Baja
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALLE DE BAJA Y DIAGNÓSTICO (Simétrico al de notificaciones) -->
<div id="detalle-baja-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="detalle-baja-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">
        
        {{-- Header simétrico a notificaciones --}}
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl shrink-0 select-none">📋</span>
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Detalle de Baja y Diagnóstico</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Motivo técnico y estado del equipo descartado</p>
            </div>
        </div>

        {{-- Contenedor Central al mismo ancho que el modal de notificaciones --}}
        <div class="w-full max-w-[450px] mx-auto flex flex-col flex-1 pb-2 space-y-3.5">
            
            {{-- Bloque resumen del equipo con barra de acento --}}
            <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500 rounded-l-xl"></div>
                <div class="pl-2.5 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Dispositivo</span>
                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-md">Dado de Baja</span>
                    </div>
                    <p id="detalle-baja-nombre" class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate"></p>
                    <p id="detalle-baja-serie" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono"></p>
                    <p id="detalle-baja-propietario" class="text-xs text-slate-700 dark:text-slate-300 mt-1 font-semibold truncate"></p>
                </div>
            </div>

            {{-- Motivo de Daño / Descarte --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivo de Daño / Baja:</label>
                <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-slate-200 dark:border-slate-800">
                    <span id="detalle-baja-motivo" class="text-sm font-black text-amber-600 dark:text-amber-400"></span>
                </div>
            </div>

            {{-- Diagnóstico u Observación Técnica --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Diagnóstico u Observación Técnica:</label>
                <div class="p-3 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-slate-200 dark:border-slate-800 min-h-[60px]">
                    <p id="detalle-baja-observacion" class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed"></p>
                </div>
            </div>

            {{-- Auditoría y Fecha --}}
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-slate-200 dark:border-slate-800">
                    <span class="text-gray-500 text-[11px] block mb-0.5 font-medium">Registrado por:</span>
                    <span id="detalle-baja-autor" class="font-bold text-slate-800 dark:text-white truncate block"></span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-slate-200 dark:border-slate-800">
                    <span class="text-gray-500 text-[11px] block mb-0.5 font-medium">Fecha y hora:</span>
                    <span id="detalle-baja-fecha" class="font-bold text-slate-800 dark:text-white block"></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 w-full modal-divider">
            <button type="button" onclick="closeDetalleBajaModal()" class="w-full btn-ghost border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500/10 justify-center py-2.5 rounded-xl font-bold text-sm transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => filterTable('search-equipos', 'tabla-equipos'));

function openBajaEquipoModal(actionUrl, nombreEquipo) {
    const modal = document.getElementById('baja-equipo-modal');
    const card  = document.getElementById('baja-equipo-card');
    const form  = document.getElementById('baja-equipo-form');
    const nomEl = document.getElementById('baja-equipo-nombre');
    const motivoSel = document.getElementById('baja-equipo-motivo');

    form.action = actionUrl;
    nomEl.textContent = nombreEquipo;

    if (motivoSel && motivoSel.tomselect) {
        motivoSel.tomselect.setValue('irreparable');
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeBajaEquipoModal() {
    const modal = document.getElementById('baja-equipo-modal');
    const card  = document.getElementById('baja-equipo-card');
    const motivoSel = document.getElementById('baja-equipo-motivo');
    if (motivoSel && motivoSel.tomselect && motivoSel.tomselect.isOpen) {
        motivoSel.tomselect.close();
    }
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function openDetalleBajaModal(data) {
    const modal = document.getElementById('detalle-baja-modal');
    const card  = document.getElementById('detalle-baja-card');

    document.getElementById('detalle-baja-nombre').textContent = data.nombre || 'Equipo';
    document.getElementById('detalle-baja-serie').textContent = 'Serie: ' + (data.serie || 'S/N');
    document.getElementById('detalle-baja-propietario').textContent = data.propietario || '';
    document.getElementById('detalle-baja-motivo').textContent = data.motivo || 'Dado de baja';
    document.getElementById('detalle-baja-observacion').textContent = data.observacion || 'Sin observaciones registradas';
    document.getElementById('detalle-baja-autor').textContent = data.autor || 'Sistema';
    document.getElementById('detalle-baja-fecha').textContent = data.fecha || '-';

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeDetalleBajaModal() {
    const modal = document.getElementById('detalle-baja-modal');
    const card  = document.getElementById('detalle-baja-card');
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
@endsection
