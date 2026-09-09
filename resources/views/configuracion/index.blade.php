@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4 sm:mb-5">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
            🏢 Configuración de Empresa
        </h2>
        <p class="text-gray-500 font-medium mt-1.5">Gestiona la información comercial que aparecerá en los reportes y facturas de tus operaciones.</p>
    </div>

    <form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data" class="glass-card p-6 md:p-8" id="form-configuracion" onsubmit="submitConfiguracion(event)">
        @csrf

        <div class="flex flex-col md:flex-row gap-8">
            {{-- Columna Izquierda: Logo --}}
            <div class="w-full md:w-1/3 flex flex-col items-center justify-start gap-4">
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
                <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center max-w-[210px] leading-snug">Formatos recomendados: PNG, JPG, WEBP. Fondo transparente sugerido.</p>
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
                    <textarea name="pie_pagina_factura" rows="3" class="glass-input" placeholder="Ej: Gracias por su compra. Los equipos reparados tienen garantía de 3 meses.">{{ old('pie_pagina_factura', $configuracion->pie_pagina_factura) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sección: Formato de Impresión Predeterminado --}}
        <div class="mt-6 pt-4 md:pt-5 border-t border-gray-200/50 dark:border-white/10">
            <div class="mb-4">
                <h3 class="text-base font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span>🖨️</span> Formato de Salida para Facturas y Recibos
                </h3>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                    Selecciona el tamaño y formato de salida predeterminado para tus ventas, compras y órdenes de servicio.
                </p>
            </div>

            @php
                $formatoActual = old('formato_factura', $configuracion->formato_factura ?? 'estandar');
                $isPos = $formatoActual === 'pos';
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Opción Estándar --}}
                <label for="radio-estandar" onclick="updateFormatSelection('estandar')" id="card-formato-estandar" class="format-card p-4 rounded-2xl flex items-start gap-4 text-left cursor-pointer transition-all {{ !$isPos ? 'is-active' : '' }}">
                    <input type="radio" name="formato_factura" value="estandar" id="radio-estandar" class="sr-only" {{ !$isPos ? 'checked' : '' }}>
                    
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-2xl shrink-0 border border-blue-500/20">
                        📄
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-black text-slate-800 dark:text-white truncate">Estándar Administrativo</span>
                            <span id="badge-estandar" class="pill {{ !$isPos ? 'pill-done' : 'pill-neutral' }} text-[10px] py-0.5 px-2.5 shrink-0">
                                {{ !$isPos ? '● Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <p class="text-xs text-indigo-500 dark:text-indigo-400 font-bold mt-0.5">Media Carta / A4</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                            Formato corporativo tradicional con diseño amplio, tablas completas y membrete. Recomendado para garantías y facturas formales.
                        </p>
                    </div>
                </label>

                {{-- Opción POS --}}
                <label for="radio-pos" onclick="updateFormatSelection('pos')" id="card-formato-pos" class="format-card p-4 rounded-2xl flex items-start gap-4 text-left cursor-pointer transition-all {{ $isPos ? 'is-active' : '' }}">
                    <input type="radio" name="formato_factura" value="pos" id="radio-pos" class="sr-only" {{ $isPos ? 'checked' : '' }}>
                    
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl shrink-0 border border-emerald-500/20">
                        🧾
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-black text-slate-800 dark:text-white truncate">Tirilla Ticket POS</span>
                            <span id="badge-pos" class="pill {{ $isPos ? 'pill-done' : 'pill-neutral' }} text-[10px] py-0.5 px-2.5 shrink-0">
                                {{ $isPos ? '● Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">Térmico (80mm)</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                            Formato compacto para impresoras de rollo continuo térmico. Ideal para despachos rápidos en mostrador y ahorro de papel.
                        </p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Botón Guardar --}}
        <div class="pt-6 mt-6 border-t border-gray-200/50 dark:border-white/10 flex justify-end">
            <button type="submit" class="btn-save flex items-center gap-2">
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

function updateFormatSelection(formato) {
    const cardEstandar = document.getElementById('card-formato-estandar');
    const cardPos = document.getElementById('card-formato-pos');
    const badgeEstandar = document.getElementById('badge-estandar');
    const badgePos = document.getElementById('badge-pos');
    const radioEstandar = document.getElementById('radio-estandar');
    const radioPos = document.getElementById('radio-pos');

    if (formato === 'pos') {
        if (radioPos) radioPos.checked = true;
        if (radioEstandar) radioEstandar.checked = false;
        if (cardPos) cardPos.classList.add('is-active');
        if (cardEstandar) cardEstandar.classList.remove('is-active');

        if (badgePos) {
            badgePos.className = 'pill pill-done text-[10px] py-0.5 px-2.5 shrink-0';
            badgePos.textContent = '● Activo';
        }
        if (badgeEstandar) {
            badgeEstandar.className = 'pill pill-neutral text-[10px] py-0.5 px-2.5 shrink-0';
            badgeEstandar.textContent = 'Inactivo';
        }
    } else {
        if (radioEstandar) radioEstandar.checked = true;
        if (radioPos) radioPos.checked = false;
        if (cardEstandar) cardEstandar.classList.add('is-active');
        if (cardPos) cardPos.classList.remove('is-active');

        if (badgeEstandar) {
            badgeEstandar.className = 'pill pill-done text-[10px] py-0.5 px-2.5 shrink-0';
            badgeEstandar.textContent = '● Activo';
        }
        if (badgePos) {
            badgePos.className = 'pill pill-neutral text-[10px] py-0.5 px-2.5 shrink-0';
            badgePos.textContent = 'Inactivo';
        }
    }
}

async function submitConfiguracion(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span>⏳</span><span>Guardando...</span>`;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Configuración guardada correctamente.', 'success');
            }
            if (data.logo_url) {
                const preview = document.getElementById('logo-preview');
                if (preview) {
                    preview.src = data.logo_url;
                    preview.classList.remove('hidden');
                }
            }
        } else {
            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Ocurrió un error al guardar.');
            if (typeof showToast === 'function') {
                showToast(errorMsg, 'error');
            }
        }
    } catch (error) {
        console.error('Error al guardar configuración:', error);
        if (typeof showToast === 'function') {
            showToast('Error de conexión al intentar guardar.', 'error');
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
    }
}
</script>
@endsection
