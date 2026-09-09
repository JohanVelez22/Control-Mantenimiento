@props([
    'url',
    'label' => 'Imprimir',
    'colorClass' => 'border-blue-500/20 text-blue-600 dark:text-blue-400',
    'id' => 'print-dd-' . uniqid()
])

@php
    $empresa = \App\Models\Configuracion::first();
    $formatoActivo = $empresa->formato_factura ?? 'estandar';
    $separator = str_contains($url, '?') ? '&' : '?';
    $posUrl = $url . $separator . 'formato=pos';
    $estandarUrl = $url . $separator . 'formato=estandar';
@endphp

<div class="relative inline-flex items-center btn-ghost {{ $colorClass }} print-dropdown-box" 
     id="{{ $id }}-container" 
     style="border-radius: 14px; overflow: visible;">

    {{-- Botón Principal: Formato predeterminado (idéntico en tamaño y padding a Editar/Anular) --}}
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" 
       class="print-main-btn no-underline hover:opacity-80 transition-opacity"
       title="Imprimir en formato predeterminado ({{ $formatoActivo === 'pos' ? 'Ticket POS 80mm' : 'Estándar A4' }})">
        <span>🖨️ {{ $label }}</span>
    </a>

    {{-- Divisor sutil compacto --}}
    <span class="print-divider"></span>

    {{-- Flecha desplegable compacta --}}
    <button type="button" 
            onclick="togglePrintMenu('{{ $id }}', event)" 
            class="print-arrow-btn hover:opacity-80 transition-opacity"
            title="Elegir formato de impresión">
        <svg id="{{ $id }}-arrow" style="width: 9.5px; height: 9.5px; opacity: 0.7; pointer-events: none; transition: transform 0.2s ease;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Desplegable con diseño y tonalidad homogénea al sistema (.ts-dropdown .ts-dropdown-solid) --}}
    <div id="{{ $id }}-menu" class="ts-dropdown ts-dropdown-solid hidden" 
         style="position: absolute; left: 50%; transform: translateX(-50%); top: calc(100% + 4px); min-width: 220px; width: max-content; text-align: left; box-sizing: border-box; z-index: 99999;">
        
        <a href="{{ $posUrl }}" target="_blank" rel="noopener noreferrer" 
           class="option" 
           onclick="closePrintMenuDelayed('{{ $id }}')">
            <span class="flex items-center gap-2">
                <span style="font-size: 15px;">🧾</span> 
                <span>Tirilla POS (80mm)</span>
            </span>
            @if($formatoActivo === 'pos')
                <span class="print-active-badge">• Activo</span>
            @endif
        </a>

        <a href="{{ $estandarUrl }}" target="_blank" rel="noopener noreferrer" 
           class="option" 
           onclick="closePrintMenuDelayed('{{ $id }}')">
            <span class="flex items-center gap-2">
                <span style="font-size: 15px;">📄</span> 
                <span>Estándar (A4 / Carta)</span>
            </span>
            @if($formatoActivo !== 'pos')
                <span class="print-active-badge">• Activo</span>
            @endif
        </a>
    </div>
</div>

@once
<script>
function togglePrintMenu(id, e) {
    if (e) {
        e.stopPropagation();
        e.preventDefault();
    }
    const menu = document.getElementById(id + '-menu');
    const arrow = document.getElementById(id + '-arrow');
    const container = document.getElementById(id + '-container');
    if (!menu) return;

    const willOpen = menu.classList.contains('hidden');

    // Cerrar otros desplegables de impresión y restaurar su z-index
    document.querySelectorAll('.print-dropdown-box').forEach(c => {
        c.style.zIndex = '';
        const hRow = c.closest('.border-b') || c.closest('header') || c.parentElement;
        if (hRow) hRow.style.zIndex = '';
    });
    document.querySelectorAll('.ts-dropdown').forEach(m => {
        if (m.id && m.id.endsWith('-menu')) m.classList.add('hidden');
    });
    document.querySelectorAll('[id$="-arrow"]').forEach(a => a.style.transform = '');

    if (willOpen) {
        menu.classList.remove('hidden');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
        if (container) {
            container.style.zIndex = '9999';
            const headerRow = container.closest('.border-b') || container.closest('header') || container.parentElement;
            if (headerRow) headerRow.style.zIndex = '9999';
        }
    }
}

function closePrintMenuDelayed(id) {
    setTimeout(function() {
        const menu = document.getElementById(id + '-menu');
        const arrow = document.getElementById(id + '-arrow');
        const container = document.getElementById(id + '-container');
        if (menu) menu.classList.add('hidden');
        if (arrow) arrow.style.transform = '';
        if (container) {
            container.style.zIndex = '';
            const headerRow = container.closest('.border-b') || container.closest('header') || container.parentElement;
            if (headerRow) headerRow.style.zIndex = '';
        }
    }, 120);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.print-dropdown-box')) {
        document.querySelectorAll('.print-dropdown-box').forEach(c => {
            c.style.zIndex = '';
            const headerRow = c.closest('.border-b') || c.closest('header') || c.parentElement;
            if (headerRow) headerRow.style.zIndex = '';
        });
        document.querySelectorAll('.ts-dropdown').forEach(m => {
            if (m.id && m.id.endsWith('-menu')) m.classList.add('hidden');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(a => a.style.transform = '');
    }
});
</script>
@endonce
