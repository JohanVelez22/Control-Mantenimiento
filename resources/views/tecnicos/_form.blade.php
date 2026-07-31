{{-- resources/views/tecnicos/_form.blade.php --}}
@php
    $t = $tecnico ?? null;
    $selEsp = old('especialidad', $t?->especialidad ?? '');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="field-label">Nombre Completo *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $t?->nombre) }}" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
            @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Identificación (DNI/NIT) *</label>
            <input type="text" name="identificacion" value="{{ old('identificacion', $t?->identificacion) }}" required oninput="this.value = this.value.replace(/[^0-9-]/g, '')" class="glass-input mt-1 @error('identificacion') border-red-500 @enderror">
            @error('identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Especialidad *</label>
            <select name="especialidad" required class="glass-input no-search mt-1 text-sm font-bold">
                <option value="">— Seleccionar especialidad —</option>
                <option value="Hardware" {{ $selEsp == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                <option value="Software" {{ $selEsp == 'Software' ? 'selected' : '' }}>Software</option>
                <option value="Electrónica" {{ $selEsp == 'Electrónica' ? 'selected' : '' }}>Electrónica</option>
                <option value="Redes" {{ $selEsp == 'Redes' ? 'selected' : '' }}>Redes</option>
                <option value="General" {{ $selEsp == 'General' ? 'selected' : '' }}>General</option>
            </select>
            @error('especialidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Teléfono Móvil *</label>
            <input type="tel" name="movil" value="{{ old('movil', $t?->movil) }}" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="glass-input mt-1 @error('movil') border-red-500 @enderror">
            @error('movil') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="field-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $t?->email) }}" class="glass-input mt-1 @error('email') border-red-500 @enderror">
            @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="3" class="glass-input mt-1 resize-y">{{ old('direccion', $t?->direccion) }}</textarea>
    </div>

    <div>
        <label class="field-label">Foto del Técnico (Opcional)</label>
        @if($t?->photo)
            <div class="mb-3">
                <img src="{{ asset('storage/' . $t->photo) }}" width="100" height="100" class="rounded-xl object-cover border-2 border-gray-300 dark:border-gray-600 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('{{ asset('storage/' . $t->photo) }}', '{{ addslashes($t->nombre) }}', this)">
            </div>
        @endif
        <input type="file" name="photo" accept="image/*" class="glass-input mt-1 @error('photo') border-red-500 @enderror">
        @if($t?->photo)
            <p class="text-xs text-gray-500 mt-1">Sube una nueva imagen para actualizar la foto actual.</p>
        @endif
        @error('photo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
        <a href="{{ route('tecnicos.index') }}" class="btn-cancel">↩️ Cancelar</a>
        <button type="submit" class="btn-save">
            {{ $t ? '🔄 Actualizar Técnico' : '💾 Guardar Técnico' }}
        </button>
    </div>
</div>
