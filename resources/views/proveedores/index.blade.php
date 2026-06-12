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
            <form method="GET" class="flex flex-wrap gap-2">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">🔍</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar proveedor..." 
                           class="glass-input pl-9 w-48 sm:w-64">
                </div>
                <select name="tipo" class="glass-input w-auto font-semibold">
                    <option value="">Todos</option>
                    <option value="persona" {{ request('tipo') == 'persona' ? 'selected' : '' }}>👤 Persona</option>
                    <option value="empresa" {{ request('tipo') == 'empresa' ? 'selected' : '' }}>🏢 Empresa</option>
                </select>
                <button type="submit" class="btn-ghost">Filtrar</button>
            </form>
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('proveedores.create') }}" class="btn-primary ml-2 shadow-blue-500/30">
                    ➕ Nuevo Proveedor
                </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Identificación</th>
                    <th>Nombre / Razón Social</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th class="text-center">Stock Asociado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $p)
                <tr>
                    <td>
                        <span class="pill {{ $p->tipo_entidad === 'empresa' ? 'pill-done' : 'pill-pending' }}">
                            {{ $p->tipo_entidad === 'empresa' ? '🏢 Empresa' : '👤 Persona' }}
                        </span>
                    </td>
                    <td class="font-mono font-bold text-sm tracking-tight text-slate-700 dark:text-slate-300">{{ $p->identificacion }}</td>
                    <td class="font-bold text-slate-800 dark:text-white">{{ $p->nombre_razon_social }}</td>
                    <td class="font-medium">{{ $p->telefono ?? '—' }}</td>
                    <td class="text-sm font-medium">{{ $p->email ?? '—' }}</td>
                    <td class="text-center font-black text-blue-600 dark:text-cyan-400">
                        {{ $p->stocks_count ?? $p->stocks()->count() }}
                    </td>
                    <td>
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('proveedores.show', $p->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Ver Detalles">👁️</a>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('proveedores.edit', $p->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Editar">✏️</a>
                                @if(auth()->user()->isAdmin())
                                <form action="{{ route('proveedores.destroy', $p->id) }}" method="POST" class="inline"
                                      data-confirm-delete="¿Eliminar definitivamente al proveedor '{{ $p->nombre_razon_social }}'?">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger px-3 py-1.5 text-xs">🗑️</button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="text-6xl drop-shadow-md mb-2">🏭</div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin proveedores registrados</h3>
                            <p class="text-gray-500 font-medium max-w-sm mb-4">Agrega el primer proveedor para gestionar el abastecimiento de inventario.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('proveedores.create') }}" class="btn-primary">
                                    ➕ Agregar Proveedor
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
        {{ $proveedores->appends(request()->query())->links() }}
    </div>
</div>
@endsection
