<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

 {{-- Buscar cliente o proveedor --}}
 <div class="md:col-span-2 p-4 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-500/20 rounded-2xl">
 <label class="field-label flex items-center gap-2"><span>🔍</span> Buscar Cliente / Proveedor (Opcional)</label>
 <div class="flex gap-2 items-center">
 <input type="text" id="cliente_busqueda" placeholder="Buscar por nombre o cédula..." class="glass-input flex-1 h-[42px]">
 <button type="button" onclick="buscarClienteCaja()" class="btn-primary h-[42px] flex items-center justify-center">Buscar</button>
 <button type="button" onclick="limpiarClienteCaja()" class="btn-ghost px-3 h-[42px] flex items-center justify-center">✕</button>
 </div>
 <div id="cliente_resultados" class="mt-2 hidden space-y-1 max-h-40 overflow-y-auto glass-card p-2 rounded-xl border border-gray-200/50 dark:border-white/10 shadow-lg"></div>
 <p class="text-[11px] font-medium text-indigo-500/80 dark:text-indigo-400/80 mt-2">Selecciona un cliente para autocompletar los campos. También puedes escribir directamente abajo.</p>
 </div>

 {{-- Empresa o Persona (uno de los dos) --}}
 <div class="md:col-span-2">
 <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-2 bg-amber-50 dark:bg-amber-900/20 inline-block px-3 py-1 rounded-full border border-amber-200 dark:border-amber-700/50">⚠️ Rellena al menos uno: <strong>Empresa</strong> o <strong>Persona</strong></p>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label class="field-label flex items-center gap-2"><span>🏢</span> Empresa (Opcional)</label>
 <input type="text" name="empresa" id="caja_empresa" value="{{ old('empresa', $movimiento->empresa ?? '') }}" placeholder="Nombre de la empresa..." class="glass-input">
 </div>
 <div>
 <label class="field-label flex items-center gap-2"><span>👤</span> Persona (Opcional)</label>
 <input type="text" name="persona" id="caja_persona" value="{{ old('persona', $movimiento->persona ?? '') }}" placeholder="Nombre de quien paga/recibe..." class="glass-input">
 @error('persona') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>
 </div>

 {{-- Fecha --}}
 <div>
 <label class="field-label">Fecha *</label>
 <input type="date" name="fecha" required value="{{ old('fecha', isset($movimiento) ? $movimiento->fecha->format('Y-m-d') : date('Y-m-d')) }}" class="glass-input">
 @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 {{-- Tipo de Movimiento --}}
 <div>
 <label class="field-label">Tipo de Movimiento *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'ingreso' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20' : 'border-gray-200/50 dark:border-white/10 hover:border-emerald-300 dark:hover:border-emerald-700 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}">
 <input type="radio" name="tipo_movimiento" value="ingreso" required id="tipo_ingreso" {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'ingreso' ? 'checked' : '' }} class="accent-emerald-500 w-4 h-4">
 <span class="font-bold {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'ingreso' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">📈 Ingreso</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'egreso' ? 'border-red-500 bg-red-50/50 dark:bg-red-900/20' : 'border-gray-200/50 dark:border-white/10 hover:border-red-300 dark:hover:border-red-700 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}">
 <input type="radio" name="tipo_movimiento" value="egreso" id="tipo_egreso" {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'egreso' ? 'checked' : '' }} class="accent-red-500 w-4 h-4">
 <span class="font-bold {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'egreso' ? 'text-red-700 dark:text-red-400' : 'text-slate-600 dark:text-slate-400' }}">📉 Egreso</span>
 </label>
 </div>
 @error('tipo_movimiento') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 {{-- Tipo de Pago --}}
 <div>
 <label class="field-label">Tipo de Pago *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'efectivo' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200/50 dark:border-white/10 hover:border-blue-300 dark:hover:border-blue-700 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}">
 <input type="radio" name="tipo_pago" value="efectivo" required {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'efectivo' ? 'checked' : '' }} class="accent-blue-500 w-4 h-4">
 <span class="font-bold {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'efectivo' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400' }}">💵 Efectivo</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'consignacion' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-900/20' : 'border-gray-200/50 dark:border-white/10 hover:border-purple-300 dark:hover:border-purple-700 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}">
 <input type="radio" name="tipo_pago" value="consignacion" {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'consignacion' ? 'checked' : '' }} class="accent-purple-500 w-4 h-4">
 <span class="font-bold {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'consignacion' ? 'text-purple-700 dark:text-purple-400' : 'text-slate-600 dark:text-slate-400' }}">🏦 Banco</span>
 </label>
 </div>
 @error('tipo_pago') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 {{-- Monto Total (Deuda Completa) --}}
 <div>
 <label class="field-label flex items-center justify-between">
 <span>Monto Total ($)</span>
 <span class="text-[10px] font-normal text-gray-400">(Opcional)</span>
 </label>
 <input type="text" id="monto_total_visual" value="{{ old('monto_total', isset($movimiento) && $movimiento->monto_total ? number_format($movimiento->monto_total, 0, ',', '.') : '') }}" placeholder="Monto total a pagar/cobrar..." class="glass-input font-bold text-right py-2">
 <input type="hidden" name="monto_total" id="monto_total_real" value="{{ old('monto_total', $movimiento->monto_total ?? '') }}">
 @error('monto_total') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 <p class="text-[11px] font-medium text-gray-400 mt-1">Usa esto solo si el pago actual es parcial. El sistema calculará el saldo pendiente.</p>
 </div>

 {{-- Monto Pagado y Estado --}}
 <div class="{{ isset($movimiento) ? 'md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5' : '' }}">
 <div>
 <label class="field-label">Monto Pagado Hoy ($) *</label>
 <input type="text" id="monto_visual" required value="{{ old('monto', isset($movimiento) ? number_format($movimiento->monto, 0, ',', '.') : '') }}" placeholder="Monto pagado..." class="glass-input font-bold text-right py-2">
 <input type="hidden" name="monto" id="monto_real" value="{{ old('monto', $movimiento->monto ?? '') }}">
 @error('monto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 
 @if(isset($movimiento))
 <div>
 <label class="field-label flex items-center gap-2"><span>🛡️</span> Estado del Movimiento</label>
 <select name="anulado" class="glass-input no-search font-bold h-[42px]">
 <option value="0" {{ old('anulado', $movimiento->anulado ?? 0) == '0' ? 'selected' : '' }}>🟢 Activo</option>
 <option value="1" {{ old('anulado', $movimiento->anulado ?? 0) == '1' ? 'selected' : '' }}>🔴 Anulado</option>
 </select>
 </div>
 @endif
 </div>

 {{-- Concepto --}}
 <div class="md:col-span-2">
 <label class="field-label">Concepto *</label>
 <div class="flex gap-2 min-w-0">
 <select name="concepto_id" id="concepto_select" class="glass-input flex-1">
 <option value=\"\">Seleccionar concepto...</option>
 @foreach($conceptos as $c)
 <option value="{{ $c->id }}" {{ old('concepto_id', $movimiento->concepto_id ?? '') == $c->id ? 'selected' : '' }}>
 {{ $c->nombre }}
 </option>
 @endforeach
 <option value="__nuevo__" class="font-bold text-blue-600">✏️ Crear nuevo concepto...</option>
 </select>
 </div>
 {{-- Campo oculto para nuevo concepto --}}
 <div id="nuevo-concepto-box" class="mt-3 hidden p-4 rounded-xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-700/30">
 <label class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2 block">Crear Nuevo Concepto</label>
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
 <label class="field-label">Descripción (Opcional)</label>
 <textarea name="descripcion" rows="2" placeholder="Detalles adicionales del movimiento..." class="glass-input resize-y">{{ old('descripcion', $movimiento->descripcion ?? '') }}</textarea>
 </div>
</div>

<script>
 // Mostrar/ocultar campo de nuevo concepto
 document.getElementById('concepto_select').addEventListener('change', function() {
 const box = document.getElementById('nuevo-concepto-box');
 if (this.value === '__nuevo__') {
 box.classList.remove('hidden');
 document.getElementById('nuevo_concepto_input').focus();
 this.value = ''; // Reset select so it doesn't send "__nuevo__"
 }
 });

 // Formateador de monto
 function formatInput(visualId, realId) {
 const inputVisual = document.getElementById(visualId);
 const inputReal = document.getElementById(realId);

 if(!inputVisual || !inputReal) return;

 inputVisual.addEventListener('input', function(e) {
 let value = e.target.value.replace(/\D/g, "");
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
 }
 
 // Trigger initial
 updateAmountColor();
 }
 formatInput('monto_visual', 'monto_real');
 formatInput('monto_total_visual', 'monto_total_real');

 // --- BÚSQUEDA DE CLIENTES Y PROVEEDORES ---
 @php
 $clientesData = \App\Models\Cliente::orderBy('nombre')->get(['id','nombre','identificacion','movil'])->map(function($c) {
     $c->tipo_entidad = 'cliente';
     return $c;
 });
 $proveedoresData = \App\Models\Proveedor::orderBy('nombre_razon_social')->get(['id','nombre_razon_social as nombre','identificacion','telefono as movil'])->map(function($p) {
     $p->tipo_entidad = 'proveedor';
     return $p;
 });
 $entidadesData = $clientesData->concat($proveedoresData);
 @endphp
 const todasEntidades = @json($entidadesData);

 function buscarClienteCaja() {
 const termino = document.getElementById('cliente_busqueda').value.trim().toLowerCase();
 const resultadosDiv = document.getElementById('cliente_resultados');
 resultadosDiv.innerHTML = '';

 if (!termino) {
 resultadosDiv.classList.add('hidden');
 return;
 }

 const encontrados = todasEntidades.filter(c =>
 c.nombre.toLowerCase().includes(termino) ||
 (c.identificacion && c.identificacion.toLowerCase().includes(termino))
 );

 if (encontrados.length === 0) {
 resultadosDiv.innerHTML = '<p class="text-xs font-semibold text-gray-500 py-2 text-center">No se encontraron resultados.</p>';
 } else {
 encontrados.forEach(c => {
 const btn = document.createElement('button');
 btn.type = 'button';
 btn.className = 'w-full text-left px-3 py-2 text-sm bg-transparent hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-lg transition-colors border-b border-gray-100 dark:border-white/5 last:border-0';
 const icon = c.tipo_entidad === 'cliente' ? '👤' : '🏢';
 const typeLabel = c.tipo_entidad === 'cliente' ? 'Cliente' : 'Proveedor';
 btn.innerHTML = `<div class="font-bold text-slate-800 dark:text-white">${icon} ${c.nombre}</div> <div class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">${typeLabel} • ${c.identificacion || 'N/A'}</div>`;
 btn.onclick = () => seleccionarEntidadCaja(c);
 resultadosDiv.appendChild(btn);
 });
 }
 resultadosDiv.classList.remove('hidden');
 }

 function seleccionarEntidadCaja(entidad) {
 if (entidad.tipo_entidad === 'cliente') {
     document.getElementById('caja_persona').value = entidad.nombre;
     document.getElementById('caja_empresa').value = '';
 } else {
     document.getElementById('caja_empresa').value = entidad.nombre;
     document.getElementById('caja_persona').value = '';
 }
 document.getElementById('cliente_busqueda').value = entidad.nombre + ' (' + (entidad.identificacion || '') + ')';
 document.getElementById('cliente_resultados').classList.add('hidden');
 }

 function limpiarClienteCaja() {
 document.getElementById('cliente_busqueda').value = '';
 document.getElementById('cliente_resultados').classList.add('hidden');
 }

 // Búsqueda al presionar Enter (evitando el envío del formulario)
 document.getElementById('cliente_busqueda').addEventListener('keydown', function(e) {
 if (e.key === 'Enter') {
 e.preventDefault();
 buscarClienteCaja();
 }
 });

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
