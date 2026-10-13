<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    @php
        $valEmpresa = old('empresa', request('empresa', $movimiento->empresa ?? ''));
        $valPersona = old('persona', request('persona', $movimiento->persona ?? ''));
        $tieneEntidad = !empty(trim($valEmpresa)) || !empty(trim($valPersona));
    @endphp

    {{-- Buscar cliente o proveedor con desplegable interactivo --}}
    <div class="md:col-span-2">
        <label class="field-label flex items-center justify-between" for="select_entidad_caja">
            <span class="flex items-center gap-1.5 font-bold">Cliente / Proveedor</span>
        </label>
        <select id="select_entidad_caja" class="glass-input w-full mt-1" data-placeholder="Buscar cliente o proveedor...">
            <option value="">Buscar cliente o proveedor...</option>
            @if(isset($todasEntidades))
                @foreach($todasEntidades as $ent)
                    @php
                        $isCli = $ent->tipo_entidad === 'cliente';
                        $icon = $isCli ? '👤' : '🏢';
                        $tipoStr = $isCli ? 'Cliente' : 'Proveedor';
                        $idStr = !empty($ent->identificacion) ? " ({$ent->identificacion})" : '';
                        $valStr = ($isCli ? 'Cliente:' : 'Proveedor:') . $ent->id;
                        $isSelected = false;
                        if (!empty($valPersona) && $isCli && trim($valPersona) === trim($ent->nombre)) {
                            $isSelected = true;
                        } elseif (!empty($valEmpresa) && !$isCli && trim($valEmpresa) === trim($ent->nombre)) {
                            $isSelected = true;
                        }
                    @endphp
                    <option value="{{ $valStr }}" data-tipo="{{ $ent->tipo_entidad }}" data-nombre="{{ $ent->nombre }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $icon }} {{ $tipoStr }}: {{ $ent->nombre }}{{ $idStr }}
                    </option>
                @endforeach
            @endif
        </select>
        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-1.5">Escribe para filtrar o despliega la lista. Al seleccionar uno se completan automáticamente los campos de abajo.</p>
    </div>

    {{-- Empresa o Persona (uno de los dos) --}}
    <div class="md:col-span-2">
        <p id="aviso_empresa_persona" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-2 bg-amber-50 dark:bg-amber-900/20 inline-block px-3 py-1.5 rounded-xl border border-amber-200 dark:border-amber-700/50 transition-all duration-200 {{ $tieneEntidad ? 'hidden' : '' }}">
            ⚠️ Rellena al menos uno: <strong>Empresa</strong> o <strong>Persona</strong>
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="field-label !flex items-center gap-1.5 font-bold">Empresa <span class="text-xs font-normal text-gray-400 dark:text-slate-400 normal-case">(Opcional)</span></label>
                <input type="text" name="empresa" id="caja_empresa" value="{{ $valEmpresa }}" placeholder="Nombre de la empresa..." class="glass-input mt-1">
            </div>
            <div>
                <label class="field-label !flex items-center gap-1.5 font-bold">Persona <span class="text-xs font-normal text-gray-400 dark:text-slate-400 normal-case">(Opcional)</span></label>
                <input type="text" name="persona" id="caja_persona" value="{{ $valPersona }}" placeholder="Nombre de quien paga/recibe..." class="glass-input mt-1">
                @error('persona') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Fecha --}}
    <div>
        <label class="field-label flex items-center gap-1.5 font-bold">
           Fecha <span class="text-red-500 font-bold">*</span>
        </label>
        <input type="date" name="fecha" required value="{{ old('fecha', isset($movimiento) ? $movimiento->fecha->format('Y-m-d') : date('Y-m-d')) }}" class="glass-input">
        @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
    </div>

 @php
     $tipoMov = old('tipo_movimiento', request('tipo_movimiento', $movimiento->tipo_movimiento ?? 'ingreso'));
     $tipoPago = old('tipo_pago', request('tipo_pago', $movimiento->tipo_pago ?? 'efectivo'));
     
     $rawMontoTotal = old('monto_total', request('monto_total', $movimiento->monto_total ?? ''));

     // Si monto_total esta vacio pero hay una descripcion con factura (#VT-X / #CP-X), obtener el total de la factura
     if (($rawMontoTotal === '' || $rawMontoTotal === null || $rawMontoTotal == 0) && (isset($movimiento) || request('descripcion'))) {
         $descToSearch = $movimiento->descripcion ?? request('descripcion', '');
         if (preg_match('/#([A-Za-z0-9-]+)/', $descToSearch, $matches)) {
             $facturaRel = \App\Models\Factura::where('numero_factura', $matches[1])->first();
             if ($facturaRel) {
                 $rawMontoTotal = $facturaRel->total_documento;
             }
         }
     }

     $cleanMontoTotal = ($rawMontoTotal !== '' && $rawMontoTotal !== null) ? round((float)$rawMontoTotal) : '';
     $valMontoTotal = $cleanMontoTotal !== '' ? number_format($cleanMontoTotal, 0, ',', '.') : '';

     $rawMonto = old('monto', request('monto', $movimiento->monto ?? ''));
     $cleanMonto = ($rawMonto !== '' && $rawMonto !== null) ? round((float)$rawMonto) : '';
     $valMonto = $cleanMonto !== '' ? number_format($cleanMonto, 0, ',', '.') : '';

     $requestedConcepto = old('concepto_id', request('concepto_id', $movimiento->concepto_id ?? ''));
     if (!$requestedConcepto && isset($conceptos)) {
         $searchConcept = $tipoMov === 'egreso' ? 'compra' : 'venta';
         foreach ($conceptos as $cOpt) {
             if (str_contains(strtolower($cOpt->nombre), $searchConcept)) {
                 $requestedConcepto = $cOpt->id;
                 break;
             }
         }
     }
 @endphp

    {{-- Tipo de Movimiento --}}
    <div>
        <label class="field-label flex items-center gap-1.5 font-bold">
            Tipo de Movimiento <span class="text-red-500 font-bold">*</span>
        </label>
        <div class="flex gap-3 mt-1">
            <label id="label_ingreso" class="flex-1 ts-select-card hover-emerald {{ $tipoMov === 'ingreso' ? 'active-emerald' : '' }}">
                <input type="radio" name="tipo_movimiento" value="ingreso" required id="tipo_ingreso" {{ $tipoMov === 'ingreso' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_ingreso">📈 Ingreso</span>
            </label>
            <label id="label_egreso" class="flex-1 ts-select-card hover-red {{ $tipoMov === 'egreso' ? 'active-red' : '' }}">
                <input type="radio" name="tipo_movimiento" value="egreso" id="tipo_egreso" {{ $tipoMov === 'egreso' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_egreso">📉 Egreso</span>
            </label>
        </div>
        @error('tipo_movimiento') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
    </div>

    {{-- Tipo de Pago --}}
    <div>
        <label class="field-label flex items-center gap-1.5 font-bold">
            Tipo de Pago <span class="text-red-500 font-bold">*</span>
        </label>
        <div class="flex gap-3 mt-1">
            <label id="label_efectivo" class="flex-1 ts-select-card hover-blue {{ $tipoPago === 'efectivo' ? 'active-blue' : '' }}">
                <input type="radio" name="tipo_pago" value="efectivo" required id="tipo_efectivo" {{ $tipoPago === 'efectivo' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_efectivo">💵 Efectivo</span>
            </label>
            <label id="label_banco" class="flex-1 ts-select-card hover-purple {{ $tipoPago === 'consignacion' ? 'active-purple' : '' }}">
                <input type="radio" name="tipo_pago" value="consignacion" id="tipo_banco" {{ $tipoPago === 'consignacion' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_banco">🏦 Banco</span>
            </label>
        </div>
        @error('tipo_pago') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
    </div>

    {{-- Monto Total (Deuda Completa) --}}
    <div>
        <label class="field-label !flex items-center gap-1.5 font-bold" for="monto_total_visual">

            Monto Total <span class="text-xs font-normal text-gray-400 dark:text-slate-400 normal-case">(Opcional)</span>


        </label>
        <div>
            <input type="text" id="monto_total_visual" value="{{ $valMontoTotal }}" placeholder="0" class="glass-input font-bold text-left py-2.5">
            <input type="hidden" name="monto_total" id="monto_total_real" value="{{ $cleanMontoTotal }}">
        </div>
        @error('monto_total') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
        <p class="text-[11px] font-medium text-gray-400 mt-1">Usa esto solo si el pago actual es parcial. El sistema calculará el saldo pendiente.</p>
    </div>

    {{-- Monto Pagado y Estado --}}
    <div>
        <label class="field-label flex items-center justify-between" for="monto_visual">
            <span class="flex items-center gap-1.5 font-bold">
                Monto Pagado Hoy <span class="text-red-500 font-bold">*</span>
            </span>
        </label>
        <div>
            <input type="text" id="monto_visual" required value="{{ $valMonto }}" placeholder="0" class="glass-input font-bold text-left py-2.5">
            <input type="hidden" name="monto" id="monto_real" value="{{ $cleanMonto }}">
        </div>
        @error('monto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
    </div>

    {{-- Concepto --}}
    <div class="md:col-span-2">
        <label class="field-label flex items-center gap-1.5 font-bold" for="concepto_select">
            Concepto <span class="text-red-500 font-bold">*</span>
        </label>
        <div class="flex gap-2 min-w-0">
            <select name="concepto_id" id="concepto_select" class="glass-input flex-1">
                <option value="">Seleccionar concepto...</option>
                @foreach($conceptos as $c)
                <option value="{{ $c->id }}" {{ $requestedConcepto == $c->id ? 'selected' : '' }}>
                    {{ $c->nombre }}
                </option>
                @endforeach
                <option value="__nuevo__" class="font-bold text-blue-600">✏️ Crear nuevo concepto...</option>
            </select>
        </div>
        {{-- Campo oculto para nuevo concepto --}}
        <div id="nuevo-concepto-box" class="mt-3 hidden">
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5 block">Crear Nuevo Concepto</label>
            <div class="flex gap-2 items-center">
                <input type="text" id="nuevo_concepto_input" name="nuevo_concepto" placeholder="Nombre del nuevo concepto..." class="glass-input flex-1 h-[42px]">
                <button type="button" onclick="crearConcepto()" class="btn-primary h-[42px] flex items-center justify-center">Agregar</button>
                <button type="button" onclick="cancelarNuevoConcepto()" class="btn-ghost px-3 h-[42px] flex items-center justify-center">✕</button>
            </div>
            <p id="concepto-status" class="text-xs mt-2 font-medium"></p>
        </div>
        @error('concepto_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
    </div>

    {{-- Descripción --}}
    <div class="md:col-span-2">
        <label class="field-label !flex items-center gap-1.5 font-bold">
            <span class="flex items-center gap-1.5 font-bold">
                Descripción / Observaciones <span class="text-xs font-normal text-gray-400 dark:text-slate-400 normal-case">(Opcional)</span>
            </span>

        </label>
        <textarea name="descripcion" rows="2" placeholder="Detalles adicionales del movimiento..." class="glass-input resize-y">{{ old('descripcion', request('descripcion', $movimiento->descripcion ?? '')) }}</textarea>
    </div>
</div>

<script>
 // Mostrar/ocultar campo de nuevo concepto
 document.getElementById('concepto_select').addEventListener('change', function() {
 const box = document.getElementById('nuevo-concepto-box');
 if (this.value === '__nuevo__') {
 box.classList.remove('hidden');
 document.getElementById('nuevo_concepto_input').focus();
  this.value = ''; // Resetear select para que no envíe "__nuevo__"
 }
 });

 // Formateador de monto
 function formatInput(visualId, realId) {
 const inputVisual = document.getElementById(visualId);
 const inputReal = document.getElementById(realId);

 if(!inputVisual || !inputReal) return;

 inputVisual.addEventListener('input', function(e) {
 let value = e.target.value.replace(/\D/g, "");
 if (value.length > 12) {
 value = value.substring(0, 12);
 }
 if (value !== "") {
 inputReal.value = value;
 e.target.value = new Intl.NumberFormat('es-CO').format(value);
 } else {
 inputReal.value = "";
 }
 });
 
 // Colores dinámicos al cambiar tipo
 inputVisual.addEventListener('keyup', updateAmountColor);
 document.querySelectorAll('input[name="tipo_movimiento"]').forEach(r => {
 r.addEventListener('change', updateAmountColor);
 });
 
 document.querySelectorAll('input[name="tipo_pago"]').forEach(r => {
 r.addEventListener('change', updateAmountColor);
 });
 
 function updateAmountColor() {
 const isIngreso = document.getElementById('tipo_ingreso').checked;
 const isEgreso = document.getElementById('tipo_egreso').checked;
 
 if (inputVisual.value.trim() !== '' && inputVisual.value !== '0') {
 if (isIngreso) {
 inputVisual.classList.remove('text-red-600', 'dark:text-red-400');
 inputVisual.classList.add('text-emerald-600', 'dark:text-emerald-400');
 } else if (isEgreso) {
 inputVisual.classList.remove('text-emerald-600', 'dark:text-emerald-400');
 inputVisual.classList.add('text-red-600', 'dark:text-red-400');
 }
 } else {
 inputVisual.classList.remove('text-emerald-600', 'dark:text-emerald-400', 'text-red-600', 'dark:text-red-400');
 }
        const labelIngreso = document.getElementById('label_ingreso');
        const labelEgreso = document.getElementById('label_egreso');
        if (labelIngreso && labelEgreso) {
            labelIngreso.classList.remove('active-emerald');
            labelEgreso.classList.remove('active-red');
            if (isIngreso) {
                labelIngreso.classList.add('active-emerald');
            } else if (isEgreso) {
                labelEgreso.classList.add('active-red');
            }
        }

        const isEfectivo = document.getElementById('tipo_efectivo')?.checked;
        const isBanco = document.getElementById('tipo_banco')?.checked;
        const labelEfectivo = document.getElementById('label_efectivo');
        const labelBanco = document.getElementById('label_banco');

        if (labelEfectivo && labelBanco) {
            labelEfectivo.classList.remove('active-blue');
            labelBanco.classList.remove('active-purple');
            if (isEfectivo) {
                labelEfectivo.classList.add('active-blue');
            } else if (isBanco) {
                labelBanco.classList.add('active-purple');
            }
        }
    }
 
// Ejecuta inicial
  updateAmountColor();
  }
  formatInput('monto_visual', 'monto_real');
  formatInput('monto_total_visual', 'monto_total_real');

    function actualizarAvisoEntidad() {
        var aviso = document.getElementById('aviso_empresa_persona');
        var emp = document.getElementById('caja_empresa');
        var per = document.getElementById('caja_persona');
        if (!aviso || !emp || !per) return;
        var tieneValor = emp.value.trim() !== '' || per.value.trim() !== '';
        if (tieneValor) {
            aviso.classList.add('hidden');
        } else {
            aviso.classList.remove('hidden');
        }
    }

    var inputEmpresa = document.getElementById('caja_empresa');
    var inputPersona = document.getElementById('caja_persona');
    var avisoEntidad = document.getElementById('aviso_empresa_persona');
    var selectEntidadCaja = document.getElementById('select_entidad_caja');

    function bloquearCampo(el, placeholderTexto) {
        if (!el) return;
        el.readOnly = true;
        el.classList.add('opacity-50', 'cursor-not-allowed', 'bg-slate-100/50', 'dark:bg-slate-800/40');
        el.placeholder = placeholderTexto;
    }

    function desbloquearCampo(el, placeholderTexto) {
        if (!el) return;
        el.readOnly = false;
        el.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-100/50', 'dark:bg-slate-800/40');
        el.placeholder = placeholderTexto;
    }

    function actualizarAvisoEntidad() {
        var emp = (inputEmpresa?.value || '').trim();
        var per = (inputPersona?.value || '').trim();
        if (avisoEntidad) {
            if (emp || per) {
                avisoEntidad.classList.add('hidden');
            } else {
                avisoEntidad.classList.remove('hidden');
            }
        }
    }

    function alEscribirEmpresa() {
        if (inputEmpresa && inputEmpresa.value.trim().length > 0) {
            if (inputPersona) {
                inputPersona.value = '';
                bloquearCampo(inputPersona, 'Bloqueado (Empresa ingresada)');
            }
        } else {
            if (inputPersona) desbloquearCampo(inputPersona, 'Nombre de quien paga/recibe...');
        }
        actualizarAvisoEntidad();
    }

    function alEscribirPersona() {
        if (inputPersona && inputPersona.value.trim().length > 0) {
            if (inputEmpresa) {
                inputEmpresa.value = '';
                bloquearCampo(inputEmpresa, 'Bloqueado (Persona ingresada)');
            }
        } else {
            if (inputEmpresa) desbloquearCampo(inputEmpresa, 'Nombre de la empresa...');
        }
        actualizarAvisoEntidad();
    }

    function sincronizarEntidadCaja() {
        if (!selectEntidadCaja) return;
        var val = selectEntidadCaja.value;
        if (!val) {
            if (inputPersona && inputPersona.readOnly) {
                inputPersona.value = '';
                desbloquearCampo(inputPersona, 'Nombre de quien paga/recibe...');
            }
            if (inputEmpresa && inputEmpresa.readOnly) {
                inputEmpresa.value = '';
                desbloquearCampo(inputEmpresa, 'Nombre de la empresa...');
            }
            actualizarAvisoEntidad();
            return;
        }

        var opt = selectEntidadCaja.querySelector('option[value="' + CSS.escape(val) + '"]')
               || Array.from(selectEntidadCaja.options).find(function(o) { return o.value === val; });

        if (!opt) return;

        var tipo = opt.getAttribute('data-tipo');
        var nombre = opt.getAttribute('data-nombre') || '';

        if (tipo === 'cliente') {
            if (inputPersona) {
                desbloquearCampo(inputPersona, 'Nombre de quien paga/recibe...');
                inputPersona.value = nombre;
            }
            if (inputEmpresa) {
                inputEmpresa.value = '';
                bloquearCampo(inputEmpresa, 'Bloqueado (Cliente seleccionado)');
            }
        } else if (tipo === 'proveedor') {
            if (inputEmpresa) {
                desbloquearCampo(inputEmpresa, 'Nombre de la empresa...');
                inputEmpresa.value = nombre;
            }
            if (inputPersona) {
                inputPersona.value = '';
                bloquearCampo(inputPersona, 'Bloqueado (Proveedor seleccionado)');
            }
        }

        actualizarAvisoEntidad();
    }

    if (inputEmpresa) inputEmpresa.addEventListener('input', alEscribirEmpresa);
    if (inputPersona) inputPersona.addEventListener('input', alEscribirPersona);

    if (selectEntidadCaja) {
        selectEntidadCaja.addEventListener('change', sincronizarEntidadCaja);
        if (selectEntidadCaja.tomselect) {
            selectEntidadCaja.tomselect.on('change', sincronizarEntidadCaja);
        } else {
            document.addEventListener('tomselect:ready', function() {
                if (selectEntidadCaja.tomselect) {
                    selectEntidadCaja.tomselect.on('change', sincronizarEntidadCaja);
                }
            });
        }
    }

    // Inicializar estado de bloqueo según valores precargados
    if (inputEmpresa && inputEmpresa.value.trim().length > 0) {
        if (inputPersona) bloquearCampo(inputPersona, 'Bloqueado (Empresa ingresada)');
    } else if (inputPersona && inputPersona.value.trim().length > 0) {
        if (inputEmpresa) bloquearCampo(inputEmpresa, 'Bloqueado (Persona ingresada)');
    }
    actualizarAvisoEntidad();

 function cancelarNuevoConcepto() {
 document.getElementById('nuevo-concepto-box').classList.add('hidden');
 document.getElementById('nuevo_concepto_input').value = '';
 document.getElementById('concepto-status').textContent = '';
 }

 async function crearConcepto() {
 const input = document.getElementById('nuevo_concepto_input');
 const status = document.getElementById('concepto-status');
 const nombre = input.value.trim();
 if (!nombre) return;

 status.textContent = 'Creando concepto...';
 status.className = 'text-xs mt-2 font-bold text-blue-500 animate-pulse';

 try {
 const res = await fetch('{{ route('caja.concepto.store') }}', {
 method: 'POST',
 headers: {
 'Content-Type': 'application/json',
 'X-CSRF-TOKEN': '{{ csrf_token() }}'
 },
 body: JSON.stringify({ nombre })
 });

 if (!res.ok) {
 const err = await res.json();
 status.textContent = err.message || 'Error al crear concepto.';
 status.className = 'text-xs mt-2 font-bold text-red-500';
 return;
 }

 const data = await res.json();
 const select = document.getElementById('concepto_select');

 // Agregar la nueva opción al select y seleccionarla
 const option = new Option(data.nombre, data.id, true, true);
 // Insertar antes de la última opción "✏️ Crear nuevo..."
 select.insertBefore(option, select.lastElementChild);

 // Limpiar campo oculto (no enviar nombre, ya tiene ID)
 input.name = ''; // evitar que se envíe nuevo_concepto
 input.value = '';

 cancelarNuevoConcepto();
 
 // Mostrar toast de éxito global si existe
 if(typeof showToast === 'function') {
 showToast(`Concepto "${data.nombre}" creado exitosamente`, 'success');
 } else {
 status.textContent = `✅ Concepto "${data.nombre}" creado y seleccionado.`;
 status.className = 'text-xs mt-2 font-bold text-emerald-500';
 setTimeout(() => { status.textContent = ''; }, 3000);
 }

 } catch (e) {
 status.textContent = 'Error de conexión. Inténtalo de nuevo.';
 status.className = 'text-xs mt-2 font-bold text-red-500';
 }
 }
</script>
