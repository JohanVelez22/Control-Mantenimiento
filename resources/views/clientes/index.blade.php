@extends('layouts.app')

@section('content')
<style>
    /* Fila resaltada al llegar por ancla (#cliente-id) */
    tr:target {
        background-color: rgba(59, 130, 246, 0.2) !important;
        outline: 2px solid #3b82f6;
    }
</style>

<script>
    function centerAnchor() {
        const hash = window.location.hash;
        if (!hash) return;
        function scrollToRow() {
            const target = document.querySelector(hash);
            if (!target) return false;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return true;
        }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (!scrollToRow()) {
                    setTimeout(scrollToRow, 50);
                    setTimeout(scrollToRow, 200);
                }
            });
        });
    }
    window.addEventListener('load', centerAnchor);
</script>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Clientes</h2>
        @if(!auth()->user()->isInvitado())
            <a href="{{ route('clientes.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                ➕ Nuevo Cliente
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700 text-center">
                    <th class="p-3 border border-gray-300 dark:border-gray-500">ID</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Nombre</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Identificación</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Móvil</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Email</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Dirección</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $cliente)
                <tr id="cliente-{{ $cliente->id }}" class="scroll-mt-[6.5rem] hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-colors duration-500">
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->id }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->nombre }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->identificacion }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->movil }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->email ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $cliente->direccion ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('clientes.edit', $cliente->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                    ✏️ Editar
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-red-500/20 text-sm">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-1 text-sm cursor-default" title="Solo lectura">
                                    👁️ <span class="hidden md:inline">Lectura</span>
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $clientes->appends(request()->query())->links() }}
    </div>
</div>
@endsection
