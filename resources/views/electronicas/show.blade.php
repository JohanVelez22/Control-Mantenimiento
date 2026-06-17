@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="glass-card p-6 md:p-8">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-6 border-b border-gray-200/50 dark:border-white/10 pb-6">
            <div>
                <a href="{{ route('electronicas.index') }}" class="btn-ghost px-3 py-1.5 text-xs mb-3 inline-flex">⬅️ Volver a electrónica</a>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    <span class="text-purple-500">⚡</span>
                    {{ $electronica->id_orden }}
                    @if($electronica->estado === 'anulado')
                        <span class="pill pill-anulado text-sm py-1 px-3 ml-2">🚫 Anulado</span>
                    @else
                        <span class="pill {{ $electronica->estado === 'terminado' ? 'pill-done' : 'pill-pending' }} text-sm py-1 px-3 ml-2">
                            {{ $electronica->estado === 'terminado' ? '✅ Terminado' : '⏳ Pendiente' }}
                        </span>
                    @endif
                </h2>
            </div>
            
            @if(!auth()->user()->isInvitado())
            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <a href="{{ route('electronicas.edit', $electronica) }}" class="btn-ghost flex-1 md:flex-none justify-center border-yellow-500/20 text-yellow-600">
                    ✏️ Editar
                </a>
                @if($electronica->fecha_salida && $electronica->estado === 'terminado')
                <a href="{{ route('electronicas.factura', $electronica) }}" target="_blank" class="btn-primary flex-1 md:flex-none justify-center">
                    🖨️ Factura
                </a>
                @endif
            </div>
            @endif
        </div>

        {{-- Datos del mantenimiento --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm p-5 rounded-2xl bg-purple-50/50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-500/20">
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Cliente</span>
                <span class="font-bold text-slate-800 dark:text-white">{{ $electronica->equipo->cliente->nombre ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Equipo</span>
                <span class="font-bold text-slate-800 dark:text-white">{{ $electronica->equipo->marca }} {{ $electronica->equipo->modelo }} <span class="text-xs text-gray-500">({{ $electronica->equipo->nombre }})</span></span>
            </div>
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Técnico</span>
                <span class="font-bold text-slate-800 dark:text-white">{{ $electronica->tecnico->nombre }}</span>
            </div>
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Tipo</span>
                <span class="font-bold text-slate-800 dark:text-white capitalize">{{ $electronica->tipo }}</span>
            </div>
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Entrada</span>
                <span class="font-bold text-slate-800 dark:text-white">{{ $electronica->fecha_entrada->format('d/m/Y') }}</span>
            </div>
            <div>
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Salida</span>
                <span class="font-bold text-slate-800 dark:text-white">{{ $electronica->fecha_salida?->format('d/m/Y') ?? '—' }}</span>
            </div>
            @if($electronica->descripcion_problema)
            <div class="md:col-span-2 mt-2 p-3 bg-white/40 dark:bg-slate-800/40 rounded-xl">
                <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block mb-1">Descripción del problema</span>
                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $electronica->descripcion_problema }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Resumen financiero --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
            <p class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10">Costo Estimado</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($electronica->costo, 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
            <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10">Total Abonado</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($electronica->total_abonado, 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
            <div class="absolute -right-6 -top-6 w-24 h-24 {{ $electronica->saldo_pendiente > 0 ? 'bg-red-500/20 group-hover:bg-red-500/30' : 'bg-teal-500/20 group-hover:bg-teal-500/30' }} rounded-full blur-2xl transition-all"></div>
            <p class="text-[10px] font-black {{ $electronica->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-teal-600 dark:text-teal-400' }} uppercase tracking-widest mb-1 z-10">Saldo Pendiente</p>
            <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($electronica->saldo_pendiente, 0, ',', '.') }}</p>
        </div>
    </div>



{{-- Panel de Repuestos Utilizados (Stock) --}}
 <div class="glass-card p-6 md:p-8">
 <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2 mb-6">📦 Repuestos / Insumos Utilizados</h3>

 @if(!auth()->user()->isInvitado() && $electronica->estado !== 'anulado')
 <form action="{{ route('electronicas.stocks.store', $electronica) }}" method="POST" class="p-4 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-500/20 rounded-2xl mb-6">
 @csrf
 <h4 class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-3">Añadir repuesto al registro</h4>
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="md:col-span-2">
 <label class="field-label">Buscar repuesto en stock *</label>
 <select name="stock_id" required class="glass-input">
 <option value="">-- Seleccione un repuesto --</option>
 @foreach($stocks_disponibles as $stock)
 <option value="{{ $stock->id }}">{{ $stock->producto }} (Disp: {{ $stock->cantidad }} | Venta: ${{ number_format($stock->precio_venta, 0, ',', '.') }})</option>
 @endforeach
 </select>
 </div>
 <div>
 <label class="field-label">Cantidad *</label>
 <div class="flex gap-2">
 <input type="number" name="cantidad" required min="1" value="1" class="glass-input w-24">
 <button type="submit" class="btn-primary flex-1 justify-center">
 ➕ Añadir
 </button>
 </div>
 </div>
 </div>
 </form>
 @elseif($electronica->estado === 'anulado')
 <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-500/30 rounded-xl p-4 mb-6">
 <p class="text-sm font-bold text-red-600 dark:text-red-400 flex items-center justify-center gap-2"><span>🚫</span> No se pueden agregar repuestos a una orden anulada.</p>
 </div>
 @endif

 {{-- Listado de repuestos --}}
 @if($electronica->stocks->isEmpty())
 <div class="text-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
 <span class="text-4xl drop-shadow-md mb-2 inline-block opacity-50">📦</span>
 <p class="text-gray-500 font-medium">No hay repuestos registrados en este registro.</p>
 </div>
 @else
 <div class="space-y-3">
 @foreach($electronica->stocks as $repuesto)
 <div class="flex flex-col sm:flex-row justify-between sm:items-center p-4 bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 rounded-xl hover:bg-white/60 dark:hover:bg-slate-700/40 transition-colors gap-3">
 <div class="flex items-center gap-4">
 <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl shrink-0">
 ⚙️
 </div>
 <div>
 <p class="font-bold text-slate-800 dark:text-white text-lg leading-tight">{{ $repuesto->producto }}</p>
 <p class="text-xs font-semibold text-gray-500 mt-0.5">
 Cant: {{ $repuesto->pivot->cantidad }} <span class="text-gray-300 dark:text-gray-600 mx-1">|</span> Unit: ${{ number_format($repuesto->pivot->precio_unitario, 0, ',', '.') }}
 </p>
 </div>
 </div>
 
 <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto">
 <div class="text-right">
 <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-0.5">Subtotal</p>
 <p class="font-black text-blue-600 dark:text-cyan-400">${{ number_format($repuesto->pivot->cantidad * $repuesto->pivot->precio_unitario, 0, ',', '.') }}</p>
 </div>
 
 @if(auth()->user()->role === 'admin' && $electronica->estado !== 'anulado')
 <form action="{{ route('electronicas.stocks.destroy', [$electronica, $repuesto->id]) }}" method="POST" class="inline-block"
 onsubmit="return confirm('¿Seguro que deseas eliminar este repuesto? Se descontará del costo de la orden y volverá al inventario.');">
 @csrf @method('DELETE')
 <button type="submit" class="btn-ghost px-2 py-1.5 text-xs text-red-600 hover:text-red-700 border-red-500/20 hover:bg-red-50/50" title="Eliminar y devolver al stock">
 🗑️
 </button>
 </form>
 @endif
 </div>
 </div>
 @endforeach
 </div>
 @endif
 </div>

 {{-- Panel de Abonos --}}
 <div class="glass-card p-6 md:p-8">
 <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2 mb-6">💳 Abonos / Pagos Parciales</h3>

 {{-- Formulario nuevo abono --}}
 @if(!auth()->user()->isInvitado() && $electronica->estado !== 'anulado')
 <form action="{{ route('electronicas.abonos.store', $electronica) }}" method="POST" class="p-4 bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl mb-6 space-y-4">
 @csrf
 <h4 class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Registrar nuevo abono</h4>
 <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
 <div>
 <label class="field-label">Monto ($) *</label>
 <input type="text" id="abono_monto_visual" required placeholder="0" class="glass-input font-bold text-emerald-600 dark:text-emerald-400">
 <input type="hidden" name="monto" id="abono_monto_real">
 </div>
 <div>
 <label class="field-label">Fecha *</label>
 <input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="glass-input">
 </div>
 <div>
 <label class="field-label">Tipo de pago *</label>
 <select name="tipo_pago" class="glass-input">
 <option value="efectivo">💵 Efectivo</option>
 <option value="consignacion">🏦 Consignación</option>
 </select>
 </div>
 <div>
 <label class="field-label">Descripción</label>
 <input type="text" name="descripcion" placeholder="Opcional..." class="glass-input">
 </div>
 </div>
 <button type="submit" class="btn-primary w-full justify-center py-2.5">
 ➕ Registrar Abono
 </button>
 </form>
 @elseif($electronica->estado === 'anulado')
 <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-500/30 rounded-xl p-4 mb-6">
 <p class="text-sm font-bold text-red-600 dark:text-red-400 flex items-center justify-center gap-2"><span>🚫</span> No se pueden agregar abonos a una orden anulada.</p>
 </div>
 @endif

 {{-- Listado de abonos --}}
 @if($electronica->abonos->isEmpty())
 <div class="text-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
 <span class="text-4xl drop-shadow-md mb-2 inline-block opacity-50">💳</span>
 <p class="text-gray-500 font-medium">Sin abonos registrados aún.</p>
 </div>
 @else
 <div class="space-y-3">
 @foreach($electronica->abonos->sortByDesc('fecha') as $abono)
 <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 rounded-xl hover:bg-white/60 dark:hover:bg-slate-700/40 transition-colors">
 <div class="flex items-center gap-4">
 <div class="w-10 h-10 rounded-lg {{ $abono->tipo_pago === 'efectivo' ? 'bg-blue-500/10 text-blue-500' : 'bg-purple-500/10 text-purple-500' }} flex items-center justify-center text-xl shrink-0">
 {{ $abono->tipo_pago === 'efectivo' ? '💵' : '🏦' }}
 </div>
 <div>
 <p class="font-black text-lg text-emerald-600 dark:text-emerald-400 leading-tight">${{ number_format($abono->monto, 0, ',', '.') }}</p>
 <p class="text-[11px] font-semibold text-gray-500 mt-0.5">
 {{ $abono->fecha->format('d/m/Y') }} <span class="text-gray-300 dark:text-gray-600 mx-1">•</span> {{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Consignación' }} <span class="text-gray-300 dark:text-gray-600 mx-1">•</span> Reg: {{ $abono->user->name }}
 </p>
 @if($abono->descripcion) 
 <p class="text-[11px] text-gray-400 mt-1 italic block max-w-md truncate">"{{ $abono->descripcion }}"</p> 
 @endif
 </div>
 </div>
 
 @if(!auth()->user()->isInvitado())
 <form action="{{ route('electronicas.abonos.destroy', $abono) }}" method="POST" data-confirm-delete="¿Eliminar este abono de ${{ number_format($abono->monto, 0, ',', '.') }}?">
 @csrf @method('DELETE')
 <button type="submit" class="btn-ghost px-2 py-1.5 text-xs text-red-600 hover:text-red-700 border-red-500/20 hover:bg-red-50/50" title="Eliminar abono">
 🗑️
 </button>
 </form>
 @endif
 </div>
 @endforeach
 </div>
 @endif
 </div>
</div>

<script>
 const abonoVisual = document.getElementById('abono_monto_visual');
 const abonoReal = document.getElementById('abono_monto_real');

 if (abonoVisual && abonoReal) {
 abonoVisual.addEventListener('input', function(e) {
 let value = e.target.value.replace(/\D/g, "");
 if (value !== "") {
 abonoReal.value = value;
 e.target.value = new Intl.NumberFormat('es-CO').format(value);
 } else {
 abonoReal.value = "";
 }
 });
 
 // Validation before submit for the Abono form
 const formAbono = abonoVisual.closest('form');
 if (formAbono) {
 formAbono.addEventListener('submit', function() {
 if(abonoReal.value === "") abonoReal.value = 0;
 });
 }
 }
</script>
@endsection
