@props([
    'id' => 'modal',
    'title' => null,
    'message' => null,
    'messageClass' => 'mb-8',   // espaciado inferior del mensaje (mb-6, mb-8, etc.)
    'icon' => null,
    'iconColor' => 'red',      // red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose, slate, gray, zinc, neutral, stone
    'size' => 'max-w-md',      // max-w-sm, max-w-md, max-w-lg, max-w-xl, max-w-2xl, max-w-3xl, max-w-4xl, max-w-5xl, max-w-6xl, max-w-7xl, max-w-full
    'showClose' => true,
    'closeLabel' => 'Cerrar',
    'closeColor' => 'ghost',   // ghost, danger, primary, secondary
    'actions' => [],           // array of ['label' => '', 'onclick' => '', 'class' => '', 'primary' => false]
    'formAction' => null,
    'formMethod' => 'POST',
    'formId' => null,
    'extraClass' => '',
    'showOverlay' => true,
    'centered' => true,
])

{{-- Modal Liquid Glass reutilizable.
     Uso básico:
     <x-modal id="mi-modal" title="¿Seguro?" message="¿Confirma la acción?" icon="⚠️" iconColor="orange" :actions="[
         ['label' => 'Cancelar', 'onclick' => 'closeMiModal()', 'class' => 'btn-ghost'],
         ['label' => 'Confirmar', 'onclick' => 'confirmar()', 'class' => 'btn-danger', 'primary' => true],
     ]" />
     
     Con formulario:
     <x-modal id="mi-modal" title="Confirmar" message="¿Seguro?" icon="⚠️" iconColor="red"
         :actions="[['label' => 'Cancelar', 'onclick' => 'closeModal()', 'class' => 'btn-ghost'],
                   ['label' => 'Eliminar', 'class' => 'btn-danger', 'primary' => true, 'type' => 'submit']]"
         formAction="{{ route('algo.destroy', $id) }}" formMethod="DELETE" formId="delete-form" />
     
     Con slots personalizados (para contenido complejo):
     <x-modal id="mi-modal" title="Título" icon="⚙️" size="max-w-2xl">
         <div class="custom-content">...</div>
         <x-slot name="footer">
             <button onclick="closeModal()" class="btn-ghost">Cancelar</button>
             <button onclick="guardar()" class="btn-primary">Guardar</button>
         </x-slot>
     </x-modal>

     El HTML resultante replica el patrón .ts-modal-overlay / .ts-modal-card existente.
--}}

@php
    // Determinar clases de color para el icono
    $iconColorClasses = [
        'red'    => 'text-red-500 bg-red-500/10 border-red-500/20',
        'orange' => 'text-orange-500 bg-orange-500/10 border-orange-500/20',
        'amber'  => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
        'yellow' => 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20',
        'lime'   => 'text-lime-500 bg-lime-500/10 border-lime-500/20',
        'green'  => 'text-green-500 bg-green-500/10 border-green-500/20',
        'emerald'=> 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
        'teal'   => 'text-teal-500 bg-teal-500/10 border-teal-500/20',
        'cyan'   => 'text-cyan-500 bg-cyan-500/10 border-cyan-500/20',
        'sky'    => 'text-sky-500 bg-sky-500/10 border-sky-500/20',
        'blue'   => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
        'indigo' => 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20',
        'violet' => 'text-violet-500 bg-violet-500/10 border-violet-500/20',
        'purple' => 'text-purple-500 bg-purple-500/10 border-purple-500/20',
        'fuchsia'=> 'text-fuchsia-500 bg-fuchsia-500/10 border-fuchsia-500/20',
        'pink'   => 'text-pink-500 bg-pink-500/10 border-pink-500/20',
        'rose'   => 'text-rose-500 bg-rose-500/10 border-rose-500/20',
        'slate'  => 'text-slate-500 bg-slate-500/10 border-slate-500/20',
        'gray'   => 'text-gray-500 bg-gray-500/10 border-gray-500/20',
        'zinc'   => 'text-zinc-500 bg-zinc-500/10 border-zinc-500/20',
        'neutral'=> 'text-neutral-500 bg-neutral-500/10 border-neutral-500/20',
        'stone'  => 'text-stone-500 bg-stone-500/10 border-stone-500/20',
    ];
    $iconClass = $iconColorClasses[$iconColor] ?? $iconColorClasses['red'];
    
    // Mapear colores de botón
    $btnClasses = [
        'ghost'    => 'btn-ghost',
        'danger'   => 'btn-danger',
        'primary'  => 'btn-primary',
        'secondary'=> 'btn-secondary',
    ];
    $closeBtnClass = $btnClasses[$closeColor] ?? 'btn-ghost';
@endphp

<div id="{{ $id }}" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300 {{ $showOverlay ? '' : 'bg-transparent' }} {{ $extraClass }}" data-xmodal {{ $showOverlay ? '' : 'style="background: transparent;"' }}>
    <div class="ts-modal-card scale-95 opacity-0 {{ $centered ? 'mx-auto' : '' }} {{ $size }}" id="{{ $id }}-card" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-msg">
        <div class="p-6">
            @if($icon)
                <div class="w-16 h-16 rounded-2xl {{ $iconClass }} flex items-center justify-center text-3xl mx-auto mb-4" aria-hidden="true">
                    {{ $icon }}
                </div>
            @endif
            
            @if($title)
                <h3 id="{{ $id }}-title" class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">{{ $title }}</h3>
            @endif
            
            @if($message)
                <p id="{{ $id }}-msg" class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium {{ $messageClass }}">{{ $message }}</p>
            @endif

            @if($formAction)
                <form id="{{ $formId ?? $id . '-form' }}" method="{{ $formMethod }}" action="{{ $formAction }}" class="space-y-4">
                    @csrf
                    @if($formMethod !== 'POST')
                        @method($formMethod)
                    @endif
                    {{ $slot }}
            @else
                {{ $slot }}
            @endif

            @if(!empty($actions) || $formAction)
                <div class="mt-6 flex gap-3 {{ $formAction ? 'justify-end' : 'justify-center' }} flex-wrap">
                    @if($formAction)
                        {{-- Botones de formulario --}}
                        @foreach($actions as $action)
                            @php
                                $btnClass = $action['class'] ?? 'btn-ghost';
                                if (isset($action['primary']) && $action['primary']) {
                                    $btnClass = 'btn-danger'; // Botón primario en formularios de confirmación = danger
                                }
                                $type = $action['type'] ?? 'button';
                                $onclick = isset($action['onclick']) ? 'onclick="' . $action['onclick'] . '"' : '';
                            @endphp
                            @if($type === 'submit')
                                <button type="submit" class="{{ $btnClass }} justify-center font-bold {{ isset($action['class']) ? $action['class'] : '' }}" {{ $onclick }}>
                                    {{ $action['label'] }}
                                </button>
                            @else
                                <button type="button" class="{{ $btnClass }} justify-center font-bold {{ isset($action['class']) ? $action['class'] : '' }}" {{ $onclick }}>
                                    {{ $action['label'] }}
                                </button>
                            @endif
                        @endforeach
                    @else
                        {{-- Botones simples --}}
                        @foreach($actions as $action)
                            @php
                                $btnClass = $action['class'] ?? 'btn-ghost';
                                $onclick = isset($action['onclick']) ? 'onclick="' . $action['onclick'] . '"' : '';
                            @endphp
                            <button type="button" class="{{ $btnClass }} justify-center font-bold {{ isset($action['class']) ? $action['class'] : '' }}" {{ $onclick }}>
                                {{ $action['label'] }}
                            </button>
                        @endforeach
                    @endif
                </div>
            @endif

            @if(!$formAction && $showClose && empty($actions))
                <div class="mt-6 flex justify-center">
                    <button type="button" onclick="closeModal('{{ $id }}')" class="{{ $closeBtnClass }} justify-center font-bold">
                        {{ $closeLabel }}
                    </button>
                </div>
            @endif

            @if($formAction)
                </form>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Funciones genéricas para abrir/cerrar modales
    window.openModal = function(id) {
        const modal = document.getElementById(id);
        const card = document.getElementById(id + '-card');
        if (!modal || !card) return;
        
        modal._trigger = document.activeElement;
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
                const focusable = card.querySelector('input, button, select, textarea, [tabindex]');
                if (focusable) focusable.focus();
            });
        });
    };
    
    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        const card = document.getElementById(id + '-card');
        if (!modal || !card) return;
        
        const trigger = modal._trigger;
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            if (trigger && typeof trigger.focus === 'function') trigger.focus();
        }, 300);
    };
    
    // Cerrar con Escape (solo modales x-modal)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.ts-modal-overlay[data-xmodal]:not(.hidden)').forEach(modal => {
                const id = modal.id;
                if (id) window.closeModal(id);
            });
        }
    });
    
    // Cerrar al hacer click en el overlay (fondo) — solo modales x-modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('ts-modal-overlay') && e.target.hasAttribute('data-xmodal')) {
            const id = e.target.id;
            if (id) window.closeModal(id);
        }
    });
</script>
@endpush