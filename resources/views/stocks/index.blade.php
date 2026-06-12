@extends('layouts.app')

@section('content')
<style>
    tr:target {
        background-color: rgba(59, 130, 246, 0.2) !important;
        outline: 2px solid #3b82f6;
    }
</style>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
        <h2 class="text-2xl font-bold">Inventario (Stock)</h2>
        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('stocks.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Buscar producto, cod..." class="search-input bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 text-sm font-semibold transition-all shadow-sm focus:outline-none w-48">
                <button type="submit" class="bg-gray-200 dark:bg-gray-700 px-3 py-2 rounded-xl text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Filtrar</button>
            </form>
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('stocks.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                    ➕ Nuevo Producto
                </a>
            @endif
        </div>
    </div>

    <!-- Tabla Adaptable -->
    <div class="w-full">
        <table class="w-full text-left border-collapse block md:table responsive-table">
            <thead class="hidden md:table-header-group">
                <tr class="bg-gray-200 dark:bg-gray-700 text-center block md:table-row">
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">Cód.</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">Producto</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">Cant.</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">P. Compra</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">Utilidad</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">P. Venta</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">P. Técnico</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 block md:table-cell">Acciones</th>
                </tr>
            </thead>
            <tbody class="block md:table-row-group">
                @forelse($stocks as $stock)
                <tr id="stock-{{ $stock->id }}" class="scroll-mt-[6.5rem] bg-white dark:bg-gray-800 md:bg-transparent md:dark:bg-transparent md:hover:bg-gray-100 md:dark:hover:bg-gray-700 text-sm md:text-base border border-gray-200 dark:border-gray-700 md:border-none rounded-xl md:rounded-none mb-4 md:mb-0 block md:table-row shadow-sm md:shadow-none transition-colors duration-500">
                    
                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">Código:</span>
                        <span class="font-medium">{{ $stock->codigo ?? '-' }}</span>
                    </td>
                    
                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">Producto:</span>
                        <span class="font-bold text-gray-800 dark:text-white">{{ $stock->producto }}</span>
                    </td>
                    
                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">Cantidad:</span>
                        <span class="inline-flex items-center justify-center px-2 py-1 rounded-lg {{ $stock->cantidad > 5 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }} font-bold">
                            {{ $stock->cantidad }}
                        </span>
                    </td>

                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">P. Compra:</span>
                        <span>${{ number_format($stock->precio_compra, 0, ',', '.') }}</span>
                    </td>

                    @php
                        $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
                        $utilidadPct   = $stock->utilidad ?? 0;
                    @endphp
                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">Utilidad:</span>
                        <div class="flex flex-col items-center gap-1" title="Margen sobre precio de compra: si compras a ${{ number_format($stock->precio_compra,0,',','.') }} y aplicas {{ number_format($utilidadPct,0) }}%, vendes a ${{ number_format($stock->precio_venta,0,',','.') }}">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 whitespace-nowrap">
                                💹 +{{ number_format($utilidadPct, 0) }}%
                            </span>
                            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                +${{ number_format($utilidadPesos, 0, ',', '.') }}/u
                            </span>
                            <span class="text-[10px] text-gray-400 dark:text-gray-500 whitespace-nowrap leading-tight">
                                margen s/compra
                            </span>
                        </div>
                    </td>

                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">P. Venta:</span>
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">${{ number_format($stock->precio_venta, 0, ',', '.') }}</span>
                    </td>

                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell flex justify-between items-center md:text-center border-b border-gray-100 dark:border-gray-700 md:border-b-0">
                        <span class="md:hidden font-bold text-gray-500 dark:text-gray-400">P. Técnico:</span>
                        <span class="text-purple-600 dark:text-purple-400 font-semibold">${{ number_format($stock->precio_tecnico, 0, ',', '.') }}</span>
                    </td>

                    <td class="p-3 md:border border-gray-300 dark:border-gray-500 block md:table-cell md:text-center bg-gray-50 dark:bg-gray-800/50 md:bg-transparent">
                        <div class="flex justify-end md:justify-center items-center gap-2 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('stocks.edit', $stock->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                    ✏️ Editar
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('stocks.destroy', $stock->id) }}" method="POST" class="inline-block" data-confirm-delete="¿Eliminar '{{ $stock->producto }}' del inventario?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-red-500/20 text-sm">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-gray-400 text-sm">👁️ Lectura</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="block md:table-row">
                    <td colspan="8" class="p-12 text-center block md:table-cell">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-6xl">📦</div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Inventario Vacío</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Registra tu primer repuesto o producto en el stock.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('stocks.create') }}" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">
                                    ➕ Agregar Producto
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $stocks->appends(request()->query())->links() }}
    </div>
</div>
@endsection



