@props([
    'name' => '',
    'class' => 'w-4 h-4',
    'strokeWidth' => 2,
])

@php
    $classAttr = $attributes->get('class', $class);
@endphp

@switch($name)
    @case('dashboard')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="7" height="9" x="3" y="3" rx="1"/>
            <rect width="7" height="5" x="14" y="3" rx="1"/>
            <rect width="7" height="9" x="14" y="12" rx="1"/>
            <rect width="7" height="5" x="3" y="16" rx="1"/>
        </svg>
        @break

    @case('user')
    @case('users')
    @case('clientes')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        @break

    @case('equipos')
    @case('computer')
    @case('monitor')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="20" height="14" x="2" y="3" rx="2"/>
            <line x1="8" x2="16" y1="21" y2="21"/>
            <line x1="12" x2="12" y1="17" y2="21"/>
        </svg>
        @break

    @case('proveedores')
    @case('truck')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
            <path d="M15 18H9"/>
            <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
            <circle cx="17" cy="18" r="2"/>
            <circle cx="7" cy="18" r="2"/>
        </svg>
        @break

    @case('tecnicos')
    @case('tools')
    @case('wrench')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
        @break

    @case('stock')
    @case('package')
    @case('caja-box')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m7.5 4.27 9 5.15"/>
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
            <path d="m3.3 7 8.7 5 8.7-5"/>
            <path d="M12 22V12"/>
        </svg>
        @break

    @case('cart')
    @case('operaciones')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="8" cy="21" r="1"/>
            <circle cx="19" cy="21" r="1"/>
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
        </svg>
        @break

    @case('cotizaciones')
    @case('clipboard')
    @case('file-text')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
            <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
            <path d="M10 9H8"/>
            <path d="M16 13H8"/>
            <path d="M16 17H8"/>
        </svg>
        @break

    @case('mantenimientos')
    @case('settings')
    @case('gear')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        @break

    @case('electronica')
    @case('zap')
    @case('lightning')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
        </svg>
        @break

    @case('caja')
    @case('banknotes')
    @case('cash')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="20" height="12" x="2" y="6" rx="2"/>
            <circle cx="12" cy="12" r="2"/>
            <path d="M6 12h.01M18 12h.01"/>
        </svg>
        @break

    @case('cierre')
    @case('lock')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        @break

    @case('reportes')
    @case('chart')
    @case('trending-up')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 3v18h18"/>
            <path d="m19 9-5 5-4-4-3 3"/>
        </svg>
        @break

    @case('configuracion')
    @case('empresa')
    @case('building')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/>
            <path d="M9 22v-4h6v4"/>
            <path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/>
        </svg>
        @break

    @case('eventos')
    @case('auditoria')
    @case('shield-check')
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>
        @break

    @default
        <svg class="{{ $classAttr }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" x2="12" y1="8" y2="12"/>
            <line x1="12" x2="12.01" y1="16" y2="16"/>
        </svg>
@endswitch
