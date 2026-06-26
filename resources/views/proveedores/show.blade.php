@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">

 {{-- Header --}}
 <div class="glass-card p-6 md:p-8">
 <div class="flex flex-col md:flex-row justify-between items-start gap-6 mb-6 border-b border-gray-200/50 dark:border-white/10 pb-6 w-full">
 <div class="flex items-start gap-4">
 <a href="{{ route('proveedores.index') }}" class="btn-ghost px-3 py-2 text-xl mt-1 shrink-0" title="Volver">⬅️</a>
 <div>
 <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight leading-tight">
 <span class="text-indigo-500 mr-1 align-middle inline-block">{{ $proveedor->tipo_entidad === 'empresa' ? '🏢' : '👤' }}</span>
 <span class="align-middle">{{ $proveedor->nombre_razon_social }}</span>
 <span class="pill {{ $proveedor->tipo_entidad === 'empresa' ? 'pill-done' : 'pill-pending' }} text-xs py-1 px-2 ml-2 align-middle inline-flex whitespace-nowrap">
 {{ $proveedor->tipo_label }}
 </span>
 </h2>
 </div>
 </div>
 
 @if(!auth()->user()->isInvitado())
 <div class="flex items-center gap-3 shrink-0 mt-1 md:mt-2">
 <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn-ghost border-yellow-500/20 text-yellow-600">
 ✏️ Editar
 </a>
 <a href="{{ route('inventario.compra.create') }}?proveedor_id={{ $proveedor->id }}" class="btn-primary">
 📦 Nueva Compra
 </a>
 </div>
 @endif
 </div>

 <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-500/20">
 <div>
 <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-1">Identificación</span>
 <span class="font-mono font-bold text-slate-800 dark:text-white text-base">{{ $proveedor->identificacion }}</span>
 </div>
 <div>
 <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-1">Teléfono</span>
 <span class="font-bold text-slate-800 dark:text-white">{{ $proveedor->telefono ?? '—' }}</span>
 </div>
 <div>
 <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-1">Correo Electrónico</span>
 <span class="font-bold text-slate-800 dark:text-white">{{ $proveedor->email ?? '—' }}</span>
 </div>
 <div>
 <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-1">Dirección</span>
 <span class="font-bold text-slate-800 dark:text-white">{{ $proveedor->direccion ?? '—' }}</span>
 </div>
 @if($proveedor->notas)
 <div class="md:col-span-2 mt-2 p-3 bg-white/40 dark:bg-slate-800/40 rounded-xl">
 <span class="text-[10px] font-black text-indigo-500 uppercase tracking-widest block mb-1">Notas</span>
 <span class="font-medium text-slate-700 dark:text-slate-300">{{ $proveedor->notas }}</span>
 </div>
 @endif
 </div>
 </div>

 {{-- Resumen financiero --}}
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10">Total Comprado</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($comprasTotales, 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10">Total Pagado</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($comprasPagadas, 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 {{ $saldoProveedor > 0 ? 'bg-red-500/20 group-hover:bg-red-500/30' : 'bg-teal-500/20 group-hover:bg-teal-500/30' }} rounded-full blur-2xl transition-all"></div>
 <p class="text-[10px] font-black {{ $saldoProveedor > 0 ? 'text-red-600 dark:text-red-400' : 'text-teal-600 dark:text-teal-400' }} uppercase tracking-widest mb-1 z-10">Saldo Pendiente</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($saldoProveedor, 0, ',', '.') }}</p>
 </div>
 </div>

 {{-- Artículos de inventario del proveedor --}}
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-6">
 <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">📦 Artículos Suministrados</h3>
 <span class="px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300">{{ $proveedor->stocks->count() }}</span>
 </div>
 
 @if($proveedor->stocks->isEmpty())
 <div class="text-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
 <span class="text-4xl drop-shadow-md mb-2 inline-block opacity-50">📦</span>
 <p class="text-gray-500 font-medium">No hay artículos de inventario asociados a este proveedor.</p>
 </div>
 @else
 <div class="overflow-x-auto overflow-y-auto max-h-[400px] rounded-xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 relative mb-2">
 <table class="ts-table mb-0">
 <thead class="sticky top-0 z-20 shadow-sm">
 <tr>
 <th class="w-24">Código</th>
 <th>Producto</th>
 <th class="text-center w-24">Stock</th>
 <th class="text-right w-32">P. Compra</th>
 <th class="text-right w-32">P. Venta</th>
 </tr>
 </thead>
 <tbody>
 @foreach($proveedor->stocks as $s)
 <tr>
 <td class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400">{{ $s->codigo ?? '—' }}</td>
 <td class="font-bold text-slate-800 dark:text-white">{{ $s->producto }}</td>
 <td class="text-center">
 <span class="pill {{ $s->cantidad <= 0 ? 'pill-anulado' : ($s->cantidad < 5 ? 'pill-pending' : 'pill-done') }} py-0.5 px-2 text-[10px]">
 {{ $s->cantidad }}
 </span>
 </td>
 <td class="text-right font-medium">${{ number_format($s->precio_compra, 0, ',', '.') }}</td>
 <td class="text-right font-black text-blue-600 dark:text-cyan-400">${{ number_format($s->precio_venta, 0, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif
 </div>

 {{-- Historial de facturas/compras --}}
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-6">
 <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">🧾 Historial de Compras</h3>
 <span class="px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300">{{ $proveedor->facturas->count() }}</span>
 </div>
 
 @if($proveedor->facturas->isEmpty())
 <div class="text-center p-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
 <span class="text-4xl drop-shadow-md mb-2 inline-block opacity-50">🧾</span>
 <p class="text-gray-500 font-medium">No hay compras registradas con este proveedor.</p>
 </div>
 @else
 <div class="space-y-3 overflow-y-auto max-h-[500px] pr-2 custom-scrollbar">
 @foreach($proveedor->facturas->sortByDesc('fecha') as $f)
 <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 p-4 bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 rounded-xl hover:bg-white/60 dark:hover:bg-slate-700/40 transition-colors">
 <div class="flex items-start gap-4">
 <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl shrink-0">
 📦
 </div>
 <div>
 <div class="flex items-center gap-2">
 <p class="font-bold text-slate-800 dark:text-white">{{ $f->numero_factura }}</p>
 <span class="text-xs font-medium text-gray-400">{{ $f->fecha->format('d/m/Y') }}</span>
 </div>
 <p class="text-xs font-semibold text-gray-500 mt-1">{{ $f->items->count() }} ítems • Registrado por: {{ $f->user->name ?? '—' }}</p>
 </div>
 </div>
 
 <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto mt-2 sm:mt-0">
 <div class="text-left sm:text-right">
 <p class="font-black text-lg text-slate-800 dark:text-white">${{ number_format($f->total_documento, 0, ',', '.') }}</p>
 @if($f->saldo_pendiente > 0)
 <p class="text-[10px] text-red-500 font-bold uppercase tracking-wider mt-0.5">Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}</p>
 @endif
 </div>
 
 <div class="flex items-center gap-3">
 <span class="pill {{ $f->estado === 'anulada' ? 'pill-anulado' : ($f->estado === 'pendiente_pago' ? 'pill-pending' : 'pill-done') }} py-1 text-xs">
 {{ ucfirst(str_replace('_', ' ', $f->estado === 'pendiente_pago' ? 'Pendiente' : $f->estado)) }}
 </span>
 <a href="{{ route('inventario.facturas.show', $f->id) }}" class="btn-ghost px-2 py-1 text-xs" title="Ver Detalles">👁️</a>
 </div>
 </div>
 </div>
 @endforeach
 </div>
 @endif
 </div>

</div>
@endsection
