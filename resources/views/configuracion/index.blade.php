@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
            🏢 Configuración de Empresa
        </h2>
        <p class="text-gray-500 font-medium mt-2">Gestiona la información comercial que aparecerá en los reportes y facturas de tus operaciones.</p>
    </div>

    <form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data" class="glass-card p-6 md:p-8 space-y-6">
        @csrf

        <div class="flex flex-col md:flex-row gap-8">
            {{-- Columna Izquierda: Logo --}}
            <div class="w-full md:w-1/3 flex flex-col items-center gap-4">
                <div class="w-48 h-48 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-white/50 dark:bg-slate-800/50 flex flex-col items-center justify-center overflow-hidden relative group cursor-pointer" onclick="document.getElementById('logo-input').click()">
                    @if($configuracion->logo_path)
                        <img src="{{ Storage::url($configuracion->logo_path) }}" id="logo-preview" alt="Logo Empresa" class="w-full h-full object-contain p-2 z-10 bg-white dark:bg-transparent">
                        <div id="logo-placeholder" class="hidden flex-col items-center z-10">
                            <div class="text-6xl mb-2 opacity-50">🖼️</div>
                            <span class="text-xs font-bold text-gray-400">Sin Logo</span>
                        </div>
                    @else
                        <img src="" id="logo-preview" alt="Logo Empresa" class="hidden w-full h-full object-contain p-2 z-10 bg-white dark:bg-transparent">
                        <div id="logo-placeholder" class="flex flex-col items-center z-10">
                            <div class="text-6xl mb-2 opacity-50">🖼️</div>
                            <span class="text-xs font-bold text-gray-400">Sin Logo</span>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center z-20 transition-all">
                        <span class="text-white text-sm font-bold">Cambiar Logo</span>
                    </div>
                </div>
                <input type="file" name="logo" id="logo-input" accept="image/*" class="hidden" onchange="previewLogo(event)">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 text-center">Formatos recomendados: PNG, JPG, WEBP. Fondo transparente sugerido.</p>
            </div>

            {{-- Columna Derecha: Datos --}}
            <div class="w-full md:w-2/3 space-y-4">
                <div>
                    <label class="field-label">Nombre de la Empresa o Negocio *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $configuracion->nombre) }}" required class="glass-input font-bold">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">NIT / Documento</label>
                        <input type="text" name="nit" value="{{ old('nit', $configuracion->nit) }}" class="glass-input">
                    </div>
                    <div>
                        <label class="field-label">Teléfono de Contacto</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $configuracion->telefono) }}" class="glass-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Correo Electrónico</label>
                        <input type="email" name="correo" value="{{ old('correo', $configuracion->correo) }}" class="glass-input">
                    </div>
                    <div>
                        <label class="field-label">Dirección / Sucursal</label>
                        <input type="text" name="direccion" value="{{ old('direccion', $configuracion->direccion) }}" class="glass-input">
                    </div>
                </div>

                <div>
                    <label class="field-label flex items-center justify-between">
                        <span>Texto para Pie de Página en Reportes / Facturas</span>
                        <span class="text-[10px] font-normal text-gray-500 dark:text-gray-400">Términos, garantías, etc.</span>
                    </label>
                    <textarea name="pie_pagina_factura" rows="4" class="glass-input" placeholder="Ej: Gracias por su compra. Los equipos reparados tienen garantía de 3 meses.">{{ old('pie_pagina_factura', $configuracion->pie_pagina_factura) }}</textarea>
                </div>
            </div>
        </div>

        <div class="pt-6 mt-6 border-t border-gray-200/50 dark:border-white/10 flex justify-end">
            <button type="submit" class="btn-primary flex items-center gap-2">
                <span>💾</span>
                <span>Guardar Configuración</span>
            </button>
        </div>
    </form>
</div>

<script>
function previewLogo(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            
            const placeholder = document.getElementById('logo-placeholder');
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}
</script>
@endsection
