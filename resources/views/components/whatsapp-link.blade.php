@props([
    'telefono' => null,
    'mensaje' => null,
    'showIcon' => true,
    'class' => '',
    'fallback' => '—'
])

@php
    $url = \App\Helpers\ColombiaHelper::whatsappUrl($telefono, $mensaje);
@endphp

@if($url)
    <a href="{{ $url }}" 
       target="_blank" 
       rel="noopener noreferrer" 
       class="inline-flex items-center align-middle gap-1 hover:text-emerald-500 dark:hover:text-emerald-400 group transition-colors cursor-pointer {{ $class }}" 
       style="gap: 3px;"
       title="Abrir chat de WhatsApp ({{ $telefono }})">
        @if($showIcon)
            <svg width="16" height="16" viewBox="0 0 24 24" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px; display: inline-block; vertical-align: middle;" class="shrink-0 group-hover:scale-110 transition-transform">
                <path fill="#25D366" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/>
                <path fill="#FFFFFF" d="M8.53 7.33c-.16 0-.35.06-.53.25-.19.19-.71.69-.71 1.69s.73 1.96.83 2.1c.1.13 1.41 2.16 3.43 3.03.48.21.86.33 1.15.42.48.15.92.13 1.27.08.39-.06 1.19-.49 1.36-.96.17-.47.17-.88.12-.96-.05-.08-.19-.13-.41-.24-.22-.11-1.3-.64-1.5-.72-.2-.07-.35-.11-.5.11-.15.22-.58.72-.71.87-.13.15-.26.17-.48.06-.22-.11-.93-.34-1.77-1.09-.65-.58-1.09-1.3-1.22-1.52-.13-.22-.01-.34.1-.45.1-.1.22-.26.33-.39.11-.13.15-.22.22-.37.07-.15.04-.28-.02-.39-.06-.11-.5-1.21-.69-1.65-.18-.44-.37-.38-.5-.39z"/>
            </svg>
        @endif
        <span class="inline-block align-middle">{{ $telefono }}</span>
    </a>
@else
    <span class="{{ $class }}">{{ $telefono ?: $fallback }}</span>
@endif
