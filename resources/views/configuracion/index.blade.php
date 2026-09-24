@extends('layouts.app')

@section('content')
<div>
    <div class="mb-4 sm:mb-5">
        <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
            🏢 Configuración de Empresa
        </h2>
        <p class="text-gray-500 font-medium mt-1.5">Gestiona la información comercial que aparecerá en los reportes y facturas de tus operaciones.</p>
    </div>

    <form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data" class="glass-card p-6" id="form-configuracion" onsubmit="submitConfiguracion(event)">
        @csrf

        <div class="flex flex-col md:flex-row gap-8">
            {{-- Columna Izquierda: Identidad de Marca / Logo --}}
            <div class="w-full md:w-1/3 flex flex-col items-center justify-center gap-3 self-center">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Logo de la Empresa</span>
                <div class="logo-upload-container group" onclick="document.getElementById('logo-input').click()">
                    @if($configuracion->logo_path)
                        <img src="{{ Storage::url($configuracion->logo_path) }}" id="logo-preview" alt="Logo Empresa" class="w-full h-full object-contain p-3.5 z-10 bg-white/40 dark:bg-transparent transition-transform duration-300 group-hover:scale-105">
                        <div id="logo-placeholder" class="hidden flex-col items-center justify-center z-10 text-center p-4">
                            <div class="text-5xl mb-2 opacity-40">🖼️</div>
                            <span class="text-xs font-bold text-gray-400">Sin Logo</span>
                            <span class="text-[10px] text-blue-500 font-semibold mt-1">Clic para subir</span>
                        </div>
                    @else
                        <img src="" id="logo-preview" alt="Logo Empresa" class="hidden w-full h-full object-contain p-3.5 z-10 bg-white/40 dark:bg-transparent transition-transform duration-300 group-hover:scale-105">
                        <div id="logo-placeholder" class="flex flex-col items-center justify-center z-10 text-center p-4">
                            <div class="text-5xl mb-2 opacity-40">🖼️</div>
                            <span class="text-xs font-bold text-gray-400">Sin Logo</span>
                            <span class="text-[10px] text-blue-500 font-semibold mt-1">Clic para subir</span>
                        </div>
                    @endif
                    
                    {{-- Overlay animado al hacer hover --}}
                    <div class="absolute inset-0 bg-slate-900/65 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center gap-1.5 transition-opacity duration-200 backdrop-blur-[2px] z-20 pointer-events-none">
                        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-base text-white shadow-sm">
                            📷
                        </div>
                        <span class="text-white text-[11px] font-black uppercase tracking-wider">Cambiar Logo</span>
                    </div>
                </div>
                
                <input type="file" name="logo" id="logo-input" accept=".svg,.png,.jpg,.jpeg,.webp,image/svg+xml,image/png,image/jpeg,image/webp" class="hidden" onchange="previewLogo(event)">
                <input type="hidden" name="eliminar_logo" id="eliminar-logo-input" value="0">

                <button type="button" 
                        onclick="confirmRemoveLogo()" 
                        id="btn-remove-logo" 
                        class="text-xs font-bold text-red-500 hover:text-red-600 dark:text-red-400 hover:bg-red-500/10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all {{ $configuracion->logo_path ? '' : 'hidden' }}">
                    <span>🗑️</span>
                    <span>Quitar logo</span>
                </button>

                <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center max-w-[240px] leading-snug">
                    <span>Formatos: <strong class="text-slate-700 dark:text-slate-300">SVG, PNG, JPG, WEBP</strong> • Máx. 5 MB</span>
                    <span class="block text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">(Fondo transparente sugerido)</span>
                </p>
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
        <div class="table-footer-btn border-t border-gray-200/50 dark:border-white/10 flex justify-end">
            <button type="submit" class="btn-save flex items-center gap-2">
                <span>💾</span>
                <span>Guardar Configuración</span>
            </button>
        </div>
    </form>
</div>

<script>
let hasSavedLogo = {{ $configuracion->logo_path ? 'true' : 'false' }};

function previewLogo(event) {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            if (typeof showToast === 'function') {
                showToast('El archivo seleccionado supera el límite de 5 MB.', 'error');
            } else {
                alert('El archivo seleccionado supera el límite de 5 MB.');
            }
            event.target.value = '';
            return;
        }

        const delInput = document.getElementById('eliminar-logo-input');
        if (delInput) delInput.value = '0';

        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            
            const placeholder = document.getElementById('logo-placeholder');
            if (placeholder) {
                placeholder.classList.add('hidden');
                placeholder.style.display = 'none';
            }

            const btnRemove = document.getElementById('btn-remove-logo');
            if (btnRemove) {
                btnRemove.classList.remove('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}

let _pendingLogoDelete = false;

function confirmRemoveLogo() {
    const fileInput = document.getElementById('logo-input');
    const preview = document.getElementById('logo-preview');
    const placeholder = document.getElementById('logo-placeholder');
    const btnRemove = document.getElementById('btn-remove-logo');
    const delInput = document.getElementById('eliminar-logo-input');

    // Caso 1: El usuario seleccionó un archivo nuevo desde su PC pero aún no lo guarda
    if (fileInput && fileInput.files.length > 0 && !hasSavedLogo) {
        fileInput.value = '';
        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
            placeholder.style.display = 'flex';
        }
        if (btnRemove) btnRemove.classList.add('hidden');
        if (delInput) delInput.value = '0';
        return;
    }

    // Caso 2: Hay un logo guardado en el servidor -> Abrir modal Liquid Glass del sistema
    if (hasSavedLogo) {
        const modal = document.getElementById('ts-modal');
        const card = document.getElementById('ts-modal-card');
        const titleEl = document.getElementById('ts-modal-title');
        const msgEl = document.getElementById('ts-modal-msg');

        if (titleEl) titleEl.innerText = '¿Eliminar logo de la empresa?';
        if (msgEl) msgEl.innerText = 'Esta acción removerá el logo guardado. Los reportes, facturas y tirillas volverán al formato de membrete textual.';

        _pendingLogoDelete = true;

        if (modal) {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                if (card) card.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }
    } else {
        if (fileInput) fileInput.value = '';
        if (preview) {
            preview.src = '';
            preview.classList.add('hidden');
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
            placeholder.style.display = 'flex';
        }
        if (btnRemove) btnRemove.classList.add('hidden');
    }
}

async function ejecutarEliminacionLogo() {
    const btnRemove = document.getElementById('btn-remove-logo');
    const fileInput = document.getElementById('logo-input');
    const preview = document.getElementById('logo-preview');
    const placeholder = document.getElementById('logo-placeholder');
    const delInput = document.getElementById('eliminar-logo-input');

    if (btnRemove) {
        btnRemove.disabled = true;
        btnRemove.innerHTML = '<span>⏳</span><span>Eliminando...</span>';
    }

    const form = document.getElementById('form-configuracion');
    const formData = new FormData(form);
    formData.set('eliminar_logo', '1');

    try {
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
            hasSavedLogo = false;
            if (fileInput) fileInput.value = '';
            if (preview) {
                preview.src = '';
                preview.classList.add('hidden');
            }
            if (placeholder) {
                placeholder.classList.remove('hidden');
                placeholder.style.display = 'flex';
            }
            if (btnRemove) btnRemove.classList.add('hidden');
            if (delInput) delInput.value = '0';

            if (typeof showToast === 'function') {
                showToast('Logo eliminado correctamente.', 'success');
            }
        } else {
            const errorMsg = data.message || 'Error al eliminar el logo.';
            if (typeof showToast === 'function') {
                showToast(errorMsg, 'error');
            }
        }
    } catch (error) {
        console.error('Error al eliminar logo:', error);
        if (typeof showToast === 'function') {
            showToast('Error de conexión al eliminar el logo.', 'error');
        }
    } finally {
        if (btnRemove) {
            btnRemove.disabled = false;
            btnRemove.innerHTML = '<span>🗑️</span><span>Quitar logo</span>';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('ts-modal-confirm')?.addEventListener('click', async () => {
        if (_pendingLogoDelete) {
            _pendingLogoDelete = false;
            if (typeof closeTsModal === 'function') closeTsModal();
            await ejecutarEliminacionLogo();
        }
    });
});

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
                hasSavedLogo = true;
                const preview = document.getElementById('logo-preview');
                if (preview) {
                    preview.src = data.logo_url;
                    preview.classList.remove('hidden');
                }
                const placeholder = document.getElementById('logo-placeholder');
                if (placeholder) {
                    placeholder.classList.add('hidden');
                    placeholder.style.display = 'none';
                }
                const btnRemove = document.getElementById('btn-remove-logo');
                if (btnRemove) {
                    btnRemove.classList.remove('hidden');
                }
            } else if (document.getElementById('eliminar-logo-input')?.value === '1') {
                hasSavedLogo = false;
                const preview = document.getElementById('logo-preview');
                if (preview) {
                    preview.src = '';
                    preview.classList.add('hidden');
                }
                const placeholder = document.getElementById('logo-placeholder');
                if (placeholder) {
                    placeholder.classList.remove('hidden');
                    placeholder.style.display = 'flex';
                }
                const btnRemove = document.getElementById('btn-remove-logo');
                if (btnRemove) {
                    btnRemove.classList.add('hidden');
                }
                document.getElementById('eliminar-logo-input').value = '0';
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
