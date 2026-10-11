@extends('layouts.app')

@section('content')
<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 sm:mb-5">
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                🏢 Configuración de Empresa
            </h2>
            <p class="text-gray-500 font-medium mt-1.5">Gestiona la información comercial que aparecerá en los reportes y facturas de tus operaciones.</p>
        </div>
        @if(auth()->user()->role === 'admin')
        <div class="shrink-0">
            <button type="button" onclick="openBackupModal()" class="btn-backup flex items-center gap-2 px-4 py-2 font-bold text-sm shadow-lg shadow-teal-500/20 whitespace-nowrap">
                <span>🛡️</span> Generar Respaldo
            </button>
        </div>
        @endif
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
                        <div class="logo-camera-badge shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="text-white text-[11px] font-black uppercase tracking-wider">Cambiar Logo</span>
                    </div>
                </div>
                
                <input type="file" name="logo" id="logo-input" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="hidden" onchange="previewLogo(event)">
                <input type="hidden" name="eliminar_logo" id="eliminar-logo-input" value="0">

                <button type="button" 
                        onclick="confirmRemoveLogo()" 
                        id="btn-remove-logo" 
                        class="text-xs font-bold text-red-500 hover:text-red-600 dark:text-red-400 hover:bg-red-500/10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all {{ $configuracion->logo_path ? '' : 'hidden' }}">
                    <span>🗑️</span>
                    <span>Quitar logo</span>
                </button>

                <p class="text-[11px] text-gray-500 dark:text-gray-400 text-center max-w-[320px] w-full leading-relaxed">
                    <span>Formatos: <strong class="text-slate-700 dark:text-slate-300">SVG, PNG, JPG, WEBP</strong> <span class="whitespace-nowrap">• Máx. 5 MB</span></span>
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">(Fondo transparente sugerido)</span>
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

let _pendingBackupDeleteFilename = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('ts-modal-confirm')?.addEventListener('click', async () => {
        if (_pendingLogoDelete) {
            _pendingLogoDelete = false;
            if (typeof closeTsModal === 'function') closeTsModal();
            await ejecutarEliminacionLogo();
        } else if (_pendingBackupDeleteFilename) {
            const fname = _pendingBackupDeleteFilename;
            _pendingBackupDeleteFilename = null;
            if (typeof closeTsModal === 'function') closeTsModal();
            await ejecutarEliminacionBackup(fname);
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

@push('modals')
@if(auth()->user()->role === 'admin')
{{-- Modal de Gestión y Programación de Respaldos (Liquid Glass) --}}
<div id="backup-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300 z-[200]" onclick="if(event.target === this) closeBackupModal()">
    <div class="backup-modal-card scale-95 opacity-0 mx-auto shadow-2xl relative" id="backup-modal-card">
        
        {{-- Encabezado del Modal con Identidad Liquid Glass --}}
        <div class="backup-modal-header">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-xl bg-teal-500/15 dark:bg-teal-400/20 text-teal-600 dark:text-teal-400 flex items-center justify-center text-2xl shrink-0" style="border: none !important; box-shadow: none !important;">
                    <span>🛡️</span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-black text-slate-800 dark:text-white tracking-tight leading-snug truncate">
                        Respaldos y Copias de Seguridad
                    </h3>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Generación manual, programación automática y almacenamiento
                    </p>
                </div>
            </div>
        </div>

        {{-- Segmented Tabs Control --}}
        <div class="backup-modal-tabs">
            <button type="button" onclick="switchBackupTab('manual')" id="tab-btn-manual" class="backup-tab-btn backup-tab-manual is-active">
                <span>⚡</span> Respaldo Inmediato
            </button>
            <button type="button" onclick="switchBackupTab('schedule')" id="tab-btn-schedule" class="backup-tab-btn backup-tab-schedule">
                <span>⏰</span> Programación Automática
            </button>
            <button type="button" onclick="switchBackupTab('files')" id="tab-btn-files" class="backup-tab-btn backup-tab-files">
                <span>📂</span> Ubicación y Archivos <span id="backup-badge-count" class="backup-tab-badge">0</span>
            </button>
        </div>

        {{-- Cuerpo del Modal con Scroll Interno Independiente --}}
        <div class="backup-modal-body">
            
            {{-- TAB 1: Respaldo Inmediato (Manual) --}}
            <div id="tab-content-manual" class="space-y-3.5">
                <div>
                    <h4 class="text-sm font-bold text-slate-800 dark:text-white">Selecciona el alcance del respaldo:</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Elige los componentes que deseas incluir en esta copia de seguridad.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <label class="glass-card hover-glow glass-card-teal backup-option-card card-type-db is-selected flex flex-col justify-between" id="card-opt-db" onclick="selectManualType('db')">
                        <input type="radio" name="manual_backup_type" value="db" checked class="sr-only">
                        <div class="z-10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">🗄️</span>
                                <span class="pill pill-done text-[11px] py-0.5 px-2.5 font-bold">Recomendado</span>
                            </div>
                            <span class="text-[13.5px] font-black text-slate-800 dark:text-white block">Solo Base de Datos</span>
                            <span class="text-[12px] text-slate-500 dark:text-slate-400 mt-1 block leading-relaxed font-medium">
                                Archivo .sql completo con todas las tablas (usuarios, clientes, proveedores, ventas, compras, reparaciones, transacciones, etc).
                            </span>     
                        </div>
                        <span class="text-[11px] text-teal-600 dark:text-teal-400 font-bold mt-3 block z-10">Ultrarrápido y ligero</span>
                    </label>

                    <label class="glass-card hover-glow glass-card-indigo backup-option-card card-type-files flex flex-col justify-between" id="card-opt-files" onclick="selectManualType('files')">
                        <input type="radio" name="manual_backup_type" value="files" class="sr-only">
                        <div class="z-10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">🖼️</span>
                                <span class="text-[11px] text-indigo-500 dark:text-indigo-400 font-bold">Base + Archivos</span>
                            </div>
                            <span class="text-[13.5px] font-black text-slate-800 dark:text-white block">BD + Multimedia</span>
                            <span class="text-[12px] text-slate-500 dark:text-slate-400 mt-1 block leading-relaxed font-medium">
                                Base de datos .sql completa y carpeta de archivos públicos del sistema (logos, comprobantes y fotos).
                            </span>
                        </div>
                        <span class="text-[11px] text-indigo-500 dark:text-indigo-400 font-bold mt-3 block z-10">Genera archivo ZIP</span>
                    </label>

                    <label class="glass-card hover-glow glass-card-purple backup-option-card card-type-all flex flex-col justify-between" id="card-opt-all" onclick="selectManualType('all')">
                        <input type="radio" name="manual_backup_type" value="all" class="sr-only">
                        <div class="z-10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl">📦</span>
                                <span class="text-[11px] text-purple-500 dark:text-purple-400 font-bold">Snapshot Total</span>
                            </div>
                            <span class="text-[13.5px] font-black text-slate-800 dark:text-white block">Respaldo Integral</span>
                            <span class="text-[12px] text-slate-500 dark:text-slate-400 mt-1 block leading-relaxed font-medium">
                                Base de datos .sql completa, carpeta de archivos públicos del sistema, snapshot empaquetado del código fuente, etc.
                            </span>
                        </div>
                        <span class="text-[11px] text-purple-600 dark:text-purple-400 font-bold mt-3 block z-10">Para migraciones</span>
                    </label>
                </div>

                {{-- Banner informativo del último respaldo --}}
                <div class="glass-card backup-info-card flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-lg shrink-0">🕒</span>
                        <div class="min-w-0">
                            <span class="text-slate-500 dark:text-slate-400 block font-medium">Último respaldo registrado:</span>
                            <strong id="manual-latest-info" class="text-slate-800 dark:text-white truncate block">Consultando...</strong>
                        </div>
                    </div>
                    <span id="manual-latest-size" class="text-xs font-bold text-teal-600 dark:text-teal-400 shrink-0">--</span>
                </div>

                {{-- Botón Ejecutar --}}
                <div class="flex justify-end" style="margin-top: 1.5rem !important;">
                    <button type="button" onclick="executeManualBackup()" id="btn-execute-backup" class="btn-backup btn-backup-db px-6 py-2.5 text-sm font-bold shadow-lg shadow-teal-500/25 flex items-center gap-2">
                        <span id="btn-execute-backup-icon">⚡</span>
                        <span id="btn-execute-backup-text">Iniciar Respaldo Ahora</span>
                    </button>
                </div>
            </div>

            {{-- TAB 2: Programación Automática --}}
            <div id="tab-content-schedule" class="space-y-4 hidden">
                {{-- Switch de Activación --}}
                <div class="glass-card backup-info-card flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <span class="text-sm font-black text-slate-800 dark:text-white block">Respaldos Automáticos Programados</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">El sistema ejecutará las copias de seguridad de forma autónoma según tus criterios sin requerir intervención.</p>
                    </div>
                    <label class="ts-switch" title="Activar o desactivar respaldos automáticos">
                        <input type="checkbox" id="sched-auto-enabled" onchange="toggleScheduleFields()">
                        <span class="ts-switch-slider"></span>
                    </label>
                </div>

                <div id="schedule-fields-wrapper" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                        {{-- Columna Izquierda: Frecuencia y Alcance --}}
                        <div class="space-y-3.5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">Frecuencia de Ejecución:</label>
                                <select id="sched-frecuencia" class="glass-input w-full text-sm">
                                    <option value="daily">📅 Diario (Todos los días)</option>
                                    <option value="weekly">📆 Semanal (Tres días a la semana)</option>
                                    <option value="monthly">🗓️ Mensual (Una vez al mes)</option>
                                </select>
                            </div>

                            <div id="wrapper-sched-dia-semana" class="hidden">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">Días de Ejecución Semanal:</label>
                                <select id="sched-dia-semana" class="glass-input w-full text-sm">
                                    <option value="lmv">Lunes, Miércoles y Viernes</option>
                                    <option value="mjs">Martes, Jueves y Sábado</option>
                                </select>
                            </div>

                            <div id="wrapper-sched-dia-mes" class="hidden">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">Día del Mes:</label>
                                <select id="sched-dia-mes" class="glass-input w-full text-sm">
                                    @for($d = 1; $d <= 28; $d++)
                                        <option value="{{ $d }}">Día {{ $d }} de cada mes</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">Contenido a respaldar:</label>
                                <select id="sched-tipo-incluido" class="glass-input w-full text-sm">
                                    <option value="db">Solo Base de Datos (.sql) - Rápido</option>
                                    <option value="files">Base de Datos + Multimedia / Subidas</option>
                                    <option value="all">Respaldo Integral Completo (BD + Archivos + Código)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Columna Derecha: Hora Manual y Presets Rápidos --}}
                        <div class="space-y-3.5">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                        Hora de Ejecución:
                                    </label>
                                    <span class="text-[11px] font-semibold text-teal-600 dark:text-teal-400 font-mono" id="time-picker-24h-badge">
                                        24h: 02:00
                                    </span>
                                </div>
                                
                                {{-- Input base oculto para backend y tests (HH:mm) --}}
                                <input type="hidden" id="sched-hora" value="02:00">

                                {{-- Contenedor del Input Manual + Tag Esmeralda + AM/PM --}}
                                <div id="sched-hora-container" 
                                     class="glass-input time-picker-custom-input cursor-text"
                                     onclick="if (event.target.closest('#tp-btn-am, #tp-btn-pm') === null) { document.getElementById('sched-hora-manual')?.focus(); }">
                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                        {{-- Input manual de hora y minuto --}}
                                        <input type="text" 
                                               id="sched-hora-manual" 
                                               value="02:00" 
                                               maxlength="5" 
                                               placeholder="02:00"
                                               autocomplete="off"
                                               spellcheck="false"
                                               class="bg-transparent font-mono text-sm font-bold text-slate-800 dark:text-white w-14 tracking-wider placeholder:text-slate-400 select-all border-0 p-0 m-0 outline-none"
                                               style="border: none !important; outline: none !important; box-shadow: none !important;"
                                               onfocus="this.select()"
                                               oninput="handleManualTimeInput(this)"
                                               onkeydown="handleManualTimeKeydown(event, this)"
                                               onblur="handleManualTimeBlur(this)">
                                        
                                        {{-- Aviso / Tag de franja horaria con borde esmeralda no ovalado --}}
                                        <span id="sched-hora-tag" class="text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-wider bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/40 shrink-0">
                                            Madrugada
                                        </span>
                                    </div>

                                    {{-- Selector AM / PM --}}
                                    <div class="flex items-center gap-0.5 p-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/90 border border-slate-200/80 dark:border-white/10 shrink-0">
                                        <button type="button" 
                                                id="tp-btn-am" 
                                                onclick="setTimeAmPm('AM')" 
                                                class="tp-ampm-btn is-active">
                                            AM
                                        </button>
                                        <button type="button" 
                                                id="tp-btn-pm" 
                                                onclick="setTimeAmPm('PM')" 
                                                class="tp-ampm-btn">
                                            PM
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Horarios Sugeridos (Presets Rápidos) --}}
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">Horarios Sugeridos:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" onclick="setSchedTime('02:00')" id="preset-time-0200" class="btn-preset-time is-active">02:00 AM (Madrugada)</button>
                                    <button type="button" onclick="setSchedTime('06:00')" id="preset-time-0600" class="btn-preset-time">06:00 AM (Apertura)</button>
                                    <button type="button" onclick="setSchedTime('18:00')" id="preset-time-1800" class="btn-preset-time">18:00 (6 PM / Cierre)</button>
                                    <button type="button" onclick="setSchedTime('22:00')" id="preset-time-2200" class="btn-preset-time">22:00 (10 PM)</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Retención y Rotación de Copias --}}
                    <div class="glass-card hover-glow glass-card-emerald backup-quota-card space-y-2 relative overflow-hidden group">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 z-10 relative">
                            <div>
                                <span class="text-xs font-black uppercase tracking-wider text-teal-800 dark:text-teal-300 flex items-center gap-1.5">
                                    <span>🔄</span> Cupo Máximo de Copias (Rotación Automática)
                                </span>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                    ¿Después de cuántas copias acumuladas empezará a sobrescribir las más antiguas?
                                </p>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <button type="button" onclick="adjustMaxCopies(-1)" class="btn-stepper">-</button>
                                <input type="number" id="sched-max-copias" min="1" max="100" value="10" class="glass-input no-spinners w-16 px-1 text-center font-mono font-bold text-sm">
                                <button type="button" onclick="adjustMaxCopies(1)" class="btn-stepper">+</button>
                            </div>
                        </div>
                        <p class="text-[11px] text-teal-700 dark:text-teal-400 font-medium leading-relaxed z-10 relative">
                            💡 Ejemplo: Al definir <strong>10 copias</strong>, cuando se genere el respaldo número 11, el sistema eliminará automáticamente el archivo más antiguo para mantener exactamente 10 copias vigentes sin saturar el disco.
                        </p>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="button" onclick="saveBackupSchedule()" id="btn-save-schedule" class="btn-primary px-6 py-2.5 text-sm font-bold shadow-md flex items-center gap-2">
                            <span>💾</span> Guardar Programación
                        </button>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Ubicación y Archivos --}}
            <div id="tab-content-files" class="space-y-4 hidden">
                {{-- Banner de Ubicación física en Servidor / PC --}}
                <div class="glass-card backup-info-card space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <span>📁</span> Ubicación Física de Almacenamiento:
                        </span>
                        <div class="flex items-center gap-1.5 self-start sm:self-auto">
                            <button type="button" onclick="copyBackupPath()" class="btn-copy-path">
                                <span>📋</span> Copiar Ruta
                            </button>
                            <button type="button" onclick="openBackupFolder()" class="btn-open-folder">
                                <span>📂</span> Abrir Carpeta
                            </button>
                        </div>
                    </div>
                    <code id="display-storage-path" class="backup-path-code">
                        storage/app/backups
                    </code>
                </div>

                {{-- Métricas Rápidas en Grid 3 Columnas Estilo Liquid Glass --}}
                <div class="backup-metrics-grid">
                    <div class="glass-card hover-glow glass-card-blue backup-metric-card flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                        <p class="backup-metric-label text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5">
                            <span class="text-base">💾</span> Copias Guardadas
                        </p>
                        <p id="metric-total-files" class="backup-metric-val text-3xl font-black text-slate-800 dark:text-white z-10 leading-tight">0</p>
                    </div>
                    <div class="glass-card hover-glow glass-card-purple backup-metric-card flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                        <p class="backup-metric-label text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5">
                            <span class="text-base">📊</span> Espacio Ocupado
                        </p>
                        <p id="metric-total-size" class="backup-metric-val text-3xl font-black text-slate-800 dark:text-white z-10 leading-tight">0 KB</p>
                    </div>
                    <div class="glass-card hover-glow glass-card-teal backup-metric-card flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                        <p class="backup-metric-label text-xs font-bold text-teal-600 dark:text-teal-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5">
                            <span class="text-base">🔄</span> Límite de Rotación
                        </p>
                        <p id="metric-max-copies" class="backup-metric-val text-3xl font-black text-teal-600 dark:text-teal-400 z-10 leading-tight">10 copias</p>
                    </div>
                </div>

                {{-- Lista de Archivos con Scroll --}}
                <div class="glass-card backup-files-wrapper p-0">
                    <div class="max-h-64 overflow-y-auto p-0 m-0" id="backup-files-container">
                        <div class="p-8 text-center text-slate-400 text-xs">
                            Cargando archivos de respaldo...
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Pie del Modal --}}
        <div class="backup-modal-footer">
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                🛡️ Sistema de Auditoría y Respaldo Tecni-Systemas
            </span>
            <button type="button" onclick="closeBackupModal()" class="btn-close-modal">
                Cerrar
            </button>
        </div>

    </div>
</div>

<script>
const BACKUP_CSRF = '{{ csrf_token() }}';
let _selectedManualType = 'db';
let _backupDataCache = null;

function getBackupSelectVal(id, fallback = '') {
    const el = document.getElementById(id);
    if (!el) return fallback;
    if (el.tomselect) {
        return el.tomselect.getValue() || fallback;
    }
    return el.value || fallback;
}

function setBackupSelectVal(id, val) {
    const el = document.getElementById(id);
    if (!el) return;
    if (el.tomselect) {
        el.tomselect.setValue(val, true);
    } else {
        el.value = val;
    }
}

function initBackupTomSelects() {
    ['sched-frecuencia', 'sched-dia-semana', 'sched-dia-mes', 'sched-tipo-incluido'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.classList.contains('tomselected') && typeof window.initGlassTomSelect === 'function') {
            window.initGlassTomSelect(el);
        }
    });

    const freqEl = document.getElementById('sched-frecuencia');
    if (freqEl && freqEl.tomselect) {
        freqEl.tomselect.off('change');
        freqEl.tomselect.on('change', function() {
            handleFrequencyChange();
        });
    }
}

function openBackupModal() {
    const modal = document.getElementById('backup-modal');
    const card = document.getElementById('backup-modal-card');
    if (!modal || !card) return;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    });

    initBackupTomSelects();
    setSchedTime(document.getElementById('sched-hora')?.value || '02:00');
    loadBackupData();
}

function closeBackupModal() {
    const modal = document.getElementById('backup-modal');
    const card = document.getElementById('backup-modal-card');
    if (!modal || !card) return;

    ['sched-frecuencia', 'sched-dia-semana', 'sched-dia-mes', 'sched-tipo-incluido'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.tomselect && el.tomselect.isOpen) {
            el.tomselect.close();
        }
    });

    modal.classList.remove('opacity-100');
    modal.classList.add('opacity-0');
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('backup-modal');
        if (modal && !modal.classList.contains('hidden')) {
            closeBackupModal();
        }
    }
});

function switchBackupTab(tab) {
    const tabs = ['manual', 'schedule', 'files'];
    tabs.forEach(t => {
        const content = document.getElementById(`tab-content-${t}`);
        const btn = document.getElementById(`tab-btn-${t}`);
        if (content) {
            if (t === tab) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        }
        if (btn) {
            if (t === tab) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        }
    });

    if (tab === 'schedule') {
        initBackupTomSelects();
    }
}

function selectManualType(type) {
    _selectedManualType = type;
    ['db', 'files', 'all'].forEach(t => {
        const card = document.getElementById(`card-opt-${t}`);
        if (card) {
            if (t === type) {
                card.classList.add('is-selected');
                const radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            } else {
                card.classList.remove('is-selected');
                const radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            }
        }
    });

    // Cambiar color del botón de ejecución según la opción elegida
    const executeBtn = document.getElementById('btn-execute-backup');
    if (executeBtn) {
        executeBtn.classList.remove(
            'btn-backup-db', 'btn-backup-files', 'btn-backup-all',
            'shadow-teal-500/25', 'shadow-indigo-500/25', 'shadow-purple-500/25'
        );
        executeBtn.classList.add(`btn-backup-${type}`);
        if (type === 'db') {
            executeBtn.classList.add('shadow-teal-500/25');
        } else if (type === 'files') {
            executeBtn.classList.add('shadow-indigo-500/25');
        } else if (type === 'all') {
            executeBtn.classList.add('shadow-purple-500/25');
        }
    }
}

function handleFrequencyChange() {
    const freq = getBackupSelectVal('sched-frecuencia', 'daily');
    const wrapSemana = document.getElementById('wrapper-sched-dia-semana');
    const wrapMes = document.getElementById('wrapper-sched-dia-mes');

    if (wrapSemana) {
        if (freq === 'weekly') {
            wrapSemana.classList.remove('hidden');
        } else {
            wrapSemana.classList.add('hidden');
        }
    }

    if (wrapMes) {
        if (freq === 'monthly') {
            wrapMes.classList.remove('hidden');
        } else {
            wrapMes.classList.add('hidden');
        }
    }
}

function toggleScheduleFields() {
    const enabled = document.getElementById('sched-auto-enabled')?.checked;
    const wrapper = document.getElementById('schedule-fields-wrapper');
    if (wrapper) {
        wrapper.style.opacity = enabled ? '1' : '0.45';
        wrapper.style.pointerEvents = enabled ? 'auto' : 'none';
    }
}

let _currentAmPm = 'AM';

function getTagForHour24(h24) {
    if (h24 >= 0 && h24 < 6) return 'Madrugada';
    if (h24 >= 6 && h24 < 12) return 'Mañana (Apertura)';
    if (h24 >= 12 && h24 < 18) return 'Tarde';
    if (h24 >= 18 && h24 < 22) return 'Cierre / Tarde';
    return 'Noche';
}

function handleManualTimeKeydown(e, input) {
    input._isBackspace = (e.key === 'Backspace' || e.key === 'Delete');
}

function handleManualTimeInput(input) {
    let val = input.value.replace(/[^0-9:]/g, '');

    // Si el usuario escribe números sin dos puntos
    if (!val.includes(':') && !input._isBackspace) {
        if (val.length === 2) {
            const d1 = parseInt(val[0], 10);
            const d2 = parseInt(val[1], 10);
            if (d1 >= 3 || (d1 === 2 && d2 >= 4)) {
                // E.g. '83' -> '08:3' o '25' -> '02:5'
                val = '0' + val[0] + ':' + val[1];
            } else {
                // E.g. '02' -> '02:', '11' -> '11:'
                val = val + ':';
            }
        }
    }

    // Limitar longitud de minutos a 2 dígitos
    const parts = val.split(':');
    if (parts.length > 1 && parts[1].length > 2) {
        parts[1] = parts[1].slice(0, 2);
        val = parts[0] + ':' + parts[1];
    }

    input.value = val;

    let h12 = parseInt(parts[0], 10);
    let m = parts.length > 1 && parts[1] !== '' ? parseInt(parts[1], 10) : 0;

    if (isNaN(h12)) return;

    // Si el usuario escribe una hora en formato 24h directamente (ej: 18 o 22)
    if (h12 >= 13 && h12 <= 23) {
        _currentAmPm = 'PM';
        h12 = h12 - 12;
    } else if (h12 === 0) {
        h12 = 12;
        _currentAmPm = 'AM';
    } else if (h12 > 23) {
        h12 = 12;
    }

    if (m > 59) m = 59;

    syncTimeState(h12, m, _currentAmPm, false);
}

function handleManualTimeBlur(input) {
    let val = input.value.trim().replace(/[^0-9:]/g, '');
    if (!val) {
        setSchedTime('02:00');
        return;
    }
    const parts = val.split(':');
    let h = parseInt(parts[0] || '2', 10);
    let m = parseInt(parts[1] || '0', 10);

    if (isNaN(h)) h = 2;
    if (isNaN(m)) m = 0;

    if (h >= 13 && h <= 23) {
        _currentAmPm = 'PM';
        h = h - 12;
    } else if (h === 0) {
        h = 12;
    } else if (h > 12) {
        h = 12;
    }
    if (m > 59) m = 59;

    syncTimeState(h, m, _currentAmPm, true);
}

function setTimeAmPm(ampm) {
    _currentAmPm = ampm;
    const input = document.getElementById('sched-hora-manual');
    const val = input ? input.value : '02:00';
    const parts = val.split(':');
    let h = parseInt(parts[0] || '2', 10);
    let m = parseInt(parts[1] || '0', 10);
    if (isNaN(h) || h < 1 || h > 12) h = 2;
    if (isNaN(m) || m < 0 || m > 59) m = 0;

    syncTimeState(h, m, ampm, true);
}

function syncTimeState(h12, m, ampm, updateInput = true) {
    _currentAmPm = ampm;

    // Calcular h24
    let h24 = h12;
    if (ampm === 'PM') {
        if (h12 < 12) h24 = h12 + 12;
    } else {
        if (h12 === 12) h24 = 0;
    }

    const h24Str = String(h24).padStart(2, '0');
    const mStr = String(m).padStart(2, '0');
    const t24 = `${h24Str}:${mStr}`;

    const h12Str = String(h12).padStart(2, '0');

    // Sincronizar input oculto para backend y tests
    const hiddenInput = document.getElementById('sched-hora');
    if (hiddenInput) hiddenInput.value = t24;

    // Sincronizar input manual visible si es requerido
    if (updateInput) {
        const manualInput = document.getElementById('sched-hora-manual');
        if (manualInput) manualInput.value = `${h12Str}:${mStr}`;
    }

    // Actualizar botones AM/PM
    const btnAm = document.getElementById('tp-btn-am');
    const btnPm = document.getElementById('tp-btn-pm');
    if (btnAm) btnAm.classList.toggle('is-active', ampm === 'AM');
    if (btnPm) btnPm.classList.toggle('is-active', ampm === 'PM');

    // Actualizar badge 24h
    const badge24 = document.getElementById('time-picker-24h-badge');
    if (badge24) badge24.textContent = `24h: ${t24}`;

    // Actualizar tag descriptivo con borde esmeralda
    const tagEl = document.getElementById('sched-hora-tag');
    if (tagEl) tagEl.textContent = getTagForHour24(h24);

    // Actualizar presets sugeridos
    const presets = [
        { id: 'preset-time-0200', t: '02:00' },
        { id: 'preset-time-0600', t: '06:00' },
        { id: 'preset-time-1800', t: '18:00' },
        { id: 'preset-time-2200', t: '22:00' }
    ];
    presets.forEach(p => {
        const pBtn = document.getElementById(p.id);
        if (pBtn) pBtn.classList.toggle('is-active', t24 === p.t);
    });
}

function setSchedTime(timeStr) {
    if (!timeStr || typeof timeStr !== 'string') timeStr = '02:00';
    const parts = timeStr.trim().split(':');
    let h24 = parseInt(parts[0], 10);
    let m = parseInt(parts[1], 10);
    if (isNaN(h24) || h24 < 0 || h24 > 23) h24 = 2;
    if (isNaN(m) || m < 0 || m > 59) m = 0;

    let ampm = h24 >= 12 ? 'PM' : 'AM';
    let h12 = h24 % 12;
    if (h12 === 0) h12 = 12;

    syncTimeState(h12, m, ampm, true);
}

function adjustMaxCopies(delta) {
    const input = document.getElementById('sched-max-copias');
    if (!input) return;
    let val = parseInt(input.value || '10', 10) + delta;
    if (val < 1) val = 1;
    if (val > 100) val = 100;
    input.value = val;
}

async function loadBackupData() {
    try {
        const res = await fetch('{{ route('backups.config') }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json();
        if (!data.success) return;

        _backupDataCache = data;

        // Configuración Programada
        const autoCheck = document.getElementById('sched-auto-enabled');
        if (autoCheck) autoCheck.checked = !!data.config.backup_automatico;

        setBackupSelectVal('sched-frecuencia', data.config.backup_frecuencia || 'daily');

        setSchedTime(data.config.backup_hora || '02:00');

        let diaSemVal = (data.config.backup_dia_semana || 'lmv').toLowerCase();
        if (diaSemVal !== 'lmv' && diaSemVal !== 'mjs') {
            if (['tuesday', 'thursday', 'saturday', 'martes', 'jueves', 'sabado', 'martes_jueves_sabado'].includes(diaSemVal)) {
                diaSemVal = 'mjs';
            } else {
                diaSemVal = 'lmv';
            }
        }
        setBackupSelectVal('sched-dia-semana', diaSemVal);

        setBackupSelectVal('sched-dia-mes', data.config.backup_dia_mes || 1);

        const maxCopiasInput = document.getElementById('sched-max-copias');
        if (maxCopiasInput) maxCopiasInput.value = data.config.backup_max_copias || 10;

        setBackupSelectVal('sched-tipo-incluido', data.config.backup_tipo_incluido || 'db');

        handleFrequencyChange();
        toggleScheduleFields();

        // Ruta de Almacenamiento
        const pathEl = document.getElementById('display-storage-path');
        if (pathEl) pathEl.textContent = data.storage_path || 'storage/app/backups';

        // Métricas
        renderBackupFiles(data.files || [], data.total_files || 0, data.total_size || '0 KB', data.config.backup_max_copias || 10, data.latest);

    } catch (err) {
        console.error('Error al cargar datos de respaldo:', err);
    }
}

function renderBackupFiles(files, totalFiles, totalSize, maxCopies, latest) {
    // Badges y Métricas
    const badgeCount = document.getElementById('backup-badge-count');
    if (badgeCount) badgeCount.textContent = totalFiles;

    const metricFiles = document.getElementById('metric-total-files');
    if (metricFiles) metricFiles.textContent = totalFiles;

    const metricSize = document.getElementById('metric-total-size');
    if (metricSize) metricSize.textContent = totalSize;

    const metricMax = document.getElementById('metric-max-copies');
    if (metricMax) metricMax.textContent = `${maxCopies} copias`;

    // Último respaldo en Tab 1
    const latestInfo = document.getElementById('manual-latest-info');
    const latestSize = document.getElementById('manual-latest-size');
    if (latestInfo) {
        if (latest) {
            latestInfo.textContent = `${latest.name} (${latest.fecha_relativa})`;
        } else {
            latestInfo.textContent = 'Ninguno registrado aún';
        }
    }
    if (latestSize) {
        latestSize.textContent = latest ? latest.size_formatted : '--';
    }

    // Tabla de Archivos
    const container = document.getElementById('backup-files-container');
    if (!container) return;

    if (!files || files.length === 0) {
        container.innerHTML = `
            <div class="p-8 text-center text-gray-400">
                <span class="text-3xl block mb-2 opacity-40">📭</span>
                <span class="text-xs font-bold block">No hay copias de seguridad generadas todavía.</span>
                <span class="text-[11px] block mt-1 text-slate-400">Genera una ahora en la pestaña "Respaldo Inmediato".</span>
            </div>
        `;
        return;
    }

    let html = `
        <table class="backup-table">
            <thead>
                <tr>
                    <th class="text-center">Archivo</th>
                    <th class="text-center">Fecha y Hora</th>
                    <th class="text-center">Tamaño</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    files.forEach(f => {
        let icon = '📄';
        if (f.name.startsWith('backup_integral')) {
            icon = '📦';
        } else if (f.name.startsWith('backup_bd_multimedia') || f.extension === 'zip') {
            icon = '🗜️';
        }
        const downloadUrl = '{{ url('/backups/download') }}/' + encodeURIComponent(f.name);
        html += `
            <tr class="backup-file-row">
                <td class="pl-4">
                    <div class="flex items-center gap-2 truncate max-w-[220px] sm:max-w-xs">
                        <span class="text-base shrink-0">${icon}</span>
                        <span class="backup-file-name truncate" title="${f.name}">${f.name}</span>
                    </div>
                </td>
                <td class="text-center">
                    <div class="backup-file-date">${f.fecha}</div>
                    <div class="backup-file-rel">${f.fecha_relativa}</div>
                </td>
                <td class="backup-file-size whitespace-nowrap text-center">
                    ${f.size_formatted}
                </td>
                <td class="text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                        <a href="${downloadUrl}" class="btn-backup-download" title="Descargar al equipo">
                            <span>⬇️</span> Descargar
                        </a>
                        <button type="button" onclick="deleteBackupFile('${f.name}')" class="btn-backup-delete" title="Eliminar respaldo">
                            <span>🗑️</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    container.innerHTML = html;
}

async function executeManualBackup() {
    const btn = document.getElementById('btn-execute-backup');
    const icon = document.getElementById('btn-execute-backup-icon');
    const text = document.getElementById('btn-execute-backup-text');
    if (!btn) return;

    btn.disabled = true;
    if (icon) icon.textContent = '⏳';
    if (text) text.textContent = 'Generando copia de seguridad...';

    try {
        const res = await fetch('{{ route('backups.manual') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': BACKUP_CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ tipo: _selectedManualType })
        });

        const data = await res.json();
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Copia de seguridad completada con éxito.', 'success');
            }
            renderBackupFiles(data.files || [], data.total_files || 0, data.total_size || '0 KB', document.getElementById('sched-max-copias')?.value || 10, data.latest);
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error al generar la copia de seguridad.', 'error');
            }
        }
    } catch (err) {
        console.error('Error al ejecutar respaldo:', err);
        if (typeof showToast === 'function') {
            showToast('Error de conexión al procesar el respaldo.', 'error');
        }
    } finally {
        btn.disabled = false;
        if (icon) icon.textContent = '⚡';
        if (text) text.textContent = 'Iniciar Respaldo Ahora';
    }
}

async function saveBackupSchedule() {
    const btn = document.getElementById('btn-save-schedule');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Guardando...';
    }

    const payload = {
        backup_automatico: document.getElementById('sched-auto-enabled')?.checked ? 1 : 0,
        backup_frecuencia: getBackupSelectVal('sched-frecuencia', 'daily'),
        backup_hora: document.getElementById('sched-hora')?.value || '02:00',
        backup_dia_semana: getBackupSelectVal('sched-dia-semana', 'lmv'),
        backup_dia_mes: parseInt(getBackupSelectVal('sched-dia-mes', '1'), 10),
        backup_max_copias: parseInt(document.getElementById('sched-max-copias')?.value || '10', 10),
        backup_tipo_incluido: getBackupSelectVal('sched-tipo-incluido', 'db'),
    };

    try {
        const res = await fetch('{{ route('backups.schedule') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': BACKUP_CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Programación guardada correctamente.', 'success');
            }
            const metricMax = document.getElementById('metric-max-copies');
            if (metricMax) metricMax.textContent = `${payload.backup_max_copias} copias`;
        } else {
            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Error al guardar la programación.');
            if (typeof showToast === 'function') {
                showToast(errorMsg, 'error');
            }
        }
    } catch (err) {
        console.error('Error al guardar programación:', err);
        if (typeof showToast === 'function') {
            showToast('Error de conexión al guardar.', 'error');
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

function deleteBackupFile(filename) {
    _pendingBackupDeleteFilename = filename;
    const modal = document.getElementById('ts-modal');
    const card = document.getElementById('ts-modal-card');
    const titleEl = document.getElementById('ts-modal-title');
    const msgEl = document.getElementById('ts-modal-msg');
    const confirmBtn = document.getElementById('ts-modal-confirm');
    const cancelBtn = document.getElementById('ts-modal-cancel');
    const iconBox = document.getElementById('ts-modal-icon-box');
    const icon = document.getElementById('ts-modal-icon');

    if (titleEl) titleEl.innerText = '¿Eliminar archivo de respaldo?';
    if (msgEl) msgEl.innerText = `Esta acción eliminará permanentemente la copia de seguridad "${filename}". No se puede deshacer.`;
    if (icon) icon.innerText = '🗑️';
    if (iconBox) iconBox.className = 'w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4';
    if (confirmBtn) {
        confirmBtn.className = 'flex-1 btn-danger justify-center font-bold';
        confirmBtn.textContent = 'Eliminar';
    }
    if (cancelBtn) {
        cancelBtn.className = 'flex-1 btn-ghost-amber';
        cancelBtn.textContent = 'Cancelar';
    }

    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            if (card) card.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }
}

async function ejecutarEliminacionBackup(filename) {
    try {
        const res = await fetch('{{ url('/backups/destroy') }}/' + encodeURIComponent(filename), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': BACKUP_CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await res.json();
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            }
            renderBackupFiles(data.files || [], data.total_files || 0, data.total_size || '0 KB', document.getElementById('sched-max-copias')?.value || 10, data.files?.[0]);
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'No se pudo eliminar el archivo.', 'error');
            }
        }
    } catch (err) {
        console.error('Error al eliminar respaldo:', err);
        if (typeof showToast === 'function') {
            showToast('Error de conexión al eliminar el archivo.', 'error');
        }
    }
}

function copyBackupPath(notify = true) {
    const pathText = document.getElementById('display-storage-path')?.textContent?.trim() || '';
    if (navigator.clipboard && pathText) {
        navigator.clipboard.writeText(pathText).then(() => {
            if (notify && typeof showToast === 'function') {
                showToast('¡Ruta física copiada al portapapeles!', 'info');
            }
        }).catch(() => {
            if (notify) prompt('Copia manualmente la ruta:', pathText);
        });
    } else if (notify && pathText) {
        prompt('Copia manualmente la ruta:', pathText);
    }
}

async function openBackupFolder() {
    try {
        const res = await fetch('{{ route('backups.open-folder') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': BACKUP_CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json();
        copyBackupPath(false);
        if (typeof showToast === 'function') {
            showToast(data.message || 'Carpeta de respaldos abierta en el Explorador.', data.opened ? 'success' : 'info');
        }
    } catch (err) {
        console.error('Error al abrir carpeta:', err);
        copyBackupPath(true);
    }
}
</script>
@endif
@endpush

