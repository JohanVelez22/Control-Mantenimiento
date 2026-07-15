{{-- Tabla glass reutilizable. Envuelve en el contenedor scroll estándar.
     Uso: <x-table id="tabla-x" class="ts-table responsive-table"> <thead>...</thead> <tbody>...</tbody> </x-table>
     El HTML resultante es idéntico al patrón manual: <div class="overflow-x-auto pb-2"><table...> --}}
<div class="overflow-x-auto pb-2">
    <table {{ $attributes->merge(['class' => 'ts-table responsive-table']) }}>
        {{ $slot }}
    </table>
</div>

