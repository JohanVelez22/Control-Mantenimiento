{{--
    Bloque de cierre / reapertura de una orden (B-01). Se incluye en los formularios de
    Mantenimiento y Electrónica, después de las fechas.
    Parámetro: $orden (modelo o null al crear).
--}}
@php
    $orden = $orden ?? null;
    $estadoOriginal = $orden?->estado;
    $abonado = $orden ? (float) $orden->total_abonado : 0.0;
    $esTecnico = auth()->user()?->isTecnico();
@endphp

<div class="md:col-span-2 space-y-3" id="orden-cierre"
     data-estado-original="{{ $estadoOriginal ?? '' }}"
     data-abonado="{{ $abonado }}">

    @if($estadoOriginal === 'terminado')
    <div class="rounded-xl border border-emerald-300/50 dark:border-emerald-500/30 bg-emerald-50/60 dark:bg-emerald-900/15 p-3 text-xs text-emerald-800 dark:text-emerald-300">
        ✅ Esta orden está <strong>terminada</strong>. El costo, el equipo, el técnico y los repuestos quedan bloqueados.
        Para cambiarlos, cambie el estado a <strong>Pendiente</strong> (reabrir) indicando el motivo. Los abonos sí se pueden seguir registrando.
    </div>

    <div id="bloque-reapertura" class="hidden rounded-xl border border-amber-300/60 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-900/15 p-3">
        <label class="field-label" for="motivo_reapertura">Motivo para reabrir la orden <span class="text-red-500 font-bold">*</span></label>
        <textarea name="motivo_reapertura" id="motivo_reapertura" rows="2" maxlength="500" class="glass-input mt-1 resize-y"
            placeholder="Ej.: el cliente regresó con la misma falla">{{ old('motivo_reapertura') }}</textarea>
        <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-1">Al reabrir se borra la fecha de salida y queda registro en el historial.</p>
        @error('motivo_reapertura') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>
    @else
    <div id="bloque-entrega-saldo" class="hidden rounded-xl border border-red-300/60 dark:border-red-500/30 bg-red-50/60 dark:bg-red-900/15 p-3">
        <p class="text-sm font-bold text-red-700 dark:text-red-300">
            ⚠️ Saldo pendiente al terminar: <span id="saldo-al-cerrar">$ 0</span>
        </p>
        <p class="text-[11px] text-red-700/80 dark:text-red-300/80 mt-0.5">
            Lo normal es registrar el pago (abono) antes de terminar. Si el equipo se entrega sin pagar todo, márquelo y explique por qué.
        </p>
        <label class="flex items-center gap-2 mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="entrega_con_saldo" value="1" id="entrega_con_saldo" {{ old('entrega_con_saldo') ? 'checked' : '' }}>
            Entregar con saldo pendiente (crédito)
        </label>
        @error('entrega_con_saldo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        <label class="field-label mt-2" for="motivo_entrega">Motivo <span class="text-red-500 font-bold">*</span></label>
        <textarea name="motivo_entrega" id="motivo_entrega" rows="2" maxlength="500" class="glass-input mt-1 resize-y"
            placeholder="Ej.: cliente frecuente, paga el viernes">{{ old('motivo_entrega') }}</textarea>
        @error('motivo_entrega') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        @if(!$orden && $esTecnico)
        <label class="field-label mt-2" for="admin_password_cierre">🔐 Contraseña de administrador <span class="text-red-500 font-bold">*</span></label>
        <input type="password" name="admin_password" id="admin_password_cierre" class="glass-input mt-1" autocomplete="off"
            placeholder="Requerida para entregar con saldo">
        @endif
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cont = document.getElementById('orden-cierre');
    if (!cont) return;
    const form = cont.closest('form');
    if (!form) return;

    const estadoOriginal = cont.dataset.estadoOriginal;
    const abonado = parseFloat(cont.dataset.abonado || '0') || 0;
    const estado = form.querySelector('select[name="estado"]');
    const costo = form.querySelector('input[name="costo"]');
    const costoVisual = form.querySelector('#costo_visual');
    const salida = form.querySelector('input[name="fecha_salida"]');
    const reapertura = document.getElementById('bloque-reapertura');
    const entrega = document.getElementById('bloque-entrega-saldo');
    const saldoTxt = document.getElementById('saldo-al-cerrar');
    const hoy = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);

    function actualizar() {
        const terminado = estado && estado.value === 'terminado';

        if (salida) {
            salida.disabled = !terminado;
            salida.required = terminado;
            salida.max = hoy;
            if (terminado && !salida.value) salida.value = hoy;
            if (!terminado) salida.value = '';
        }

        if (reapertura) {
            reapertura.classList.toggle('hidden', !(estadoOriginal === 'terminado' && !terminado));
        }

        if (entrega) {
            // Se lee el campo visible: el oculto se actualiza en otro manejador que puede correr después.
            const visible = costoVisual ? costoVisual.value.replace(/\D/g, '') : '';
            const crudo = visible !== '' ? visible : (costo ? costo.value : '');
            const saldo = Math.max(0, (parseFloat(crudo || '0') || 0) - abonado);
            const mostrar = terminado && estadoOriginal !== 'terminado' && saldo > 0.01;
            entrega.classList.toggle('hidden', !mostrar);
            if (saldoTxt) saldoTxt.textContent = '$ ' + new Intl.NumberFormat('es-CO').format(Math.round(saldo));
        }
    }

    if (estado) estado.addEventListener('change', actualizar);
    if (costoVisual) costoVisual.addEventListener('input', actualizar);
    actualizar();
});
</script>
