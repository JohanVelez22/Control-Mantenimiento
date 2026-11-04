{{-- resources/views/equipos/_form.blade.php --}}
@php
    $eq = $equipo ?? null;
    $defaultProp = '';
    if ($eq?->proveedor_id) {
        $defaultProp = 'Proveedor:' . $eq->proveedor_id;
    } elseif ($eq?->cliente_id) {
        $defaultProp = 'Cliente:' . $eq->cliente_id;
    }
    $selPropietario = old('propietario_global', $defaultProp);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="field-label flex items-center gap-2">Propietario <span class="text-red-500 font-bold">*</span></label>
            <select name="propietario_global" required class="glass-input mt-1 text-sm font-bold" data-placeholder="Buscar cliente o proveedor...">
                <option value="">Buscar cliente o proveedor...</option>
                @foreach($clientes as $cliente)
                    <option value="Cliente:{{ $cliente->id }}" {{ $selPropietario == 'Cliente:'.$cliente->id ? 'selected' : '' }}>
                        👤 Cliente: {{ $cliente->nombre }} ({{ $cliente->identificacion }}){{ $cliente->tipo_cliente === 'tecnico' ? ' 🛠️ Técnico' : '' }}
                    </option>
                @endforeach
                @if(isset($proveedores) && $proveedores->isNotEmpty())
                    @foreach($proveedores as $prov)
                        <option value="Proveedor:{{ $prov->id }}" {{ $selPropietario == 'Proveedor:'.$prov->id ? 'selected' : '' }}>
                            🏢 Proveedor: {{ $prov->nombre_razon_social }} ({{ $prov->identificacion }})
                        </option>
                    @endforeach
                @endif
            </select>
            @error('propietario_global') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
            @error('cliente_id') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
            @error('proveedor_id') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Nombre del Equipo <span class="text-red-500 font-bold">*</span></label>
            <input type="text" name="nombre" value="{{ old('nombre', $eq?->nombre) }}" required placeholder="Ej. PC Escritorio" class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
            @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Marca <span class="text-red-500 font-bold">*</span></label>
            <input type="text" name="marca" value="{{ old('marca', $eq?->marca) }}" required placeholder="Ej. HP, Dell" class="glass-input mt-1 @error('marca') border-red-500 @enderror">
            @error('marca') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Modelo <span class="text-red-500 font-bold">*</span></label>
            <input type="text" name="modelo" value="{{ old('modelo', $eq?->modelo) }}" required placeholder="Ej. ProDesk 400" class="glass-input mt-1 @error('modelo') border-red-500 @enderror">
            @error('modelo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Número de Serie <span class="text-red-500 font-bold">*</span></label>
            <input type="text" name="serie" value="{{ old('serie', $eq?->serie) }}" required placeholder="S/N..." oninput="this.value = this.value.toUpperCase()" class="glass-input mt-1 uppercase @error('serie') border-red-500 @enderror">
            @error('serie') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>
    <div class="md:col-span-2">
        <label class="field-label flex items-center gap-2">Observaciones / Detalles</label>
        <textarea name="observacion" rows="2" class="glass-input resize-y" placeholder="Cargador original, rayón en la tapa, etc...">{{ old('observacion', $eq?->observacion) }}</textarea>
    </div>

    <div class="md:col-span-2 flex flex-col md:flex-row items-center justify-end gap-3 mt-0 pt-0">
        <a href="{{ route('equipos.index') }}" class="btn-cancel"><span>✕</span><span>Cancelar</span></a>
        <button type="submit" class="btn-save">{{ $eq ? '🔄 Actualizar Equipo' : '💾 Guardar Equipo' }}</button>
    </div>
</div>
