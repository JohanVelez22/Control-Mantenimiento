@extends('layouts.app')
@section('content')
<div class="space-y-5">

 {{-- Header con tabs de navegación de reportes --}}
 <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-4">
 <div class="flex flex-wrap items-center gap-2">
 <span class="font-bold text-lg mr-2">📊 Reportes Financieros</span>
 <a href="{{ route('reportes.financiero.diario') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all {{ request()->routeIs('reportes.financiero.diario') ? 'bg-blue-500 text-white shadow-lg ' : 'bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20' }}">
 📅 Diario
 </a>
 <a href="{{ route('reportes.financiero.acumulado') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all {{ request()->routeIs('reportes.financiero.acumulado') ? 'bg-purple-500 text-white shadow-lg ' : 'bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20' }}">
 📈 Acumulado
 </a>
 <a href="{{ route('reportes.financiero.operaciones') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all {{ request()->routeIs('reportes.financiero.operaciones') ? 'bg-teal-500 text-white shadow-lg ' : 'bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20' }}">
 📋 Operaciones
 </a>
 </div>
 </div>

 @yield('reporte_content')

</div>
@endsection
