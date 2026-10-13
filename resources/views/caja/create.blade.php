@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto">
 <div class="glass-card p-6 md:p-6">
 <div class="flex items-center gap-3 mb-6">
 <a href="{{ route('caja.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">💰 Nuevo Movimiento de Caja</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Registra un ingreso o egreso de efectivo o banco</p>
 </div>
 </div>
 <form action="{{ route('caja.store') }}" method="POST">
 @csrf
 @include('caja._form')
 
    <div class="flex flex-col md:flex-row items-center justify-end gap-3 mt-0 pt-0">
 <a href="{{ route('caja.index') }}" class="btn-cancel">✕ Cancelar</a>
 <button type="submit" name="print_after" value="1" class="btn-save-print">
 🖨️ Guardar e Imprimir
 </button>
 <button type="submit" class="btn-save">
 💾 Guardar
 </button>
 </div>
 </form>
 </div>
</div>
@endsection
