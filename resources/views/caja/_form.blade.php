<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Buscar cliente o proveedor --}}
    <div class="md:col-span-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 rounded-xl">
        <label class="block text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">🔍 Buscar Cliente / Proveedor (Opcional)</label>
        <div class="flex gap-2">
            <input type="text" id="cliente_busqueda" placeholder="Buscar por nombre o cédula..."
                   class="flex-1 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm">
            <button type="button" onclick="buscarClienteCaja()" class="px-4 py-2 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-600 transition-all text-sm">Buscar</button>
            <button type="button" onclick="limpiarClienteCaja()" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition-all text-sm">✕</button>
        </div>
        <div id="cliente_resultados" class="mt-2 hidden space-y-1 max-h-40 overflow-y-auto"></div>
        <p class="text-xs text-blue-500 dark:text-blue-400 mt-1">Selecciona un cliente para autocompletar los campos. También puedes escribir directamente abajo.</p>
    </div>

    {{-- Empresa o Persona (uno de los dos) --}}
    <div class="md:col-span-2">
        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-2">⚠️ Rellena al menos uno: <strong>Empresa</strong> o <strong>Persona</strong>.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">🏢 Empresa (Opcional)</label>
                <input type="text" name="empresa" id="caja_empresa" value="{{ old('empresa', $movimiento->empresa ?? '') }}"
                       placeholder="Nombre de la empresa..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">👤 Persona (Opcional)</label>
                <input type="text" name="persona" id="caja_persona" value="{{ old('persona', $movimiento->persona ?? '') }}"
                       placeholder="Nombre de quien paga/recibe..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                @error('persona') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Fecha --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha *</label>
        <input type="date" name="fecha" required
               value="{{ old('fecha', isset($movimiento) ? $movimiento->fecha->format('Y-m-d') : date('Y-m-d')) }}"
               class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Tipo de Movimiento --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Movimiento *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'ingreso'
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-green-300' }}">
                <input type="radio" name="tipo_movimiento" value="ingreso" required id="tipo_ingreso"
                       {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'ingreso' ? 'checked' : '' }}
                       class="accent-green-500">
                <span class="font-bold text-green-700 dark:text-green-400">📈 Ingreso</span>
            </label>
            <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'egreso'
                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-red-300' }}">
                <input type="radio" name="tipo_movimiento" value="egreso" id="tipo_egreso"
                       {{ old('tipo_movimiento', $movimiento->tipo_movimiento ?? '') === 'egreso' ? 'checked' : '' }}
                       class="accent-red-500">
                <span class="font-bold text-red-700 dark:text-red-400">📉 Egreso</span>
            </label>
        </div>
        @error('tipo_movimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Tipo de Pago --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Pago *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'efectivo'
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-blue-300' }}">
                <input type="radio" name="tipo_pago" value="efectivo" required
                       {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'efectivo' ? 'checked' : '' }}
                       class="accent-blue-500">
                <span class="font-bold text-blue-700 dark:text-blue-400">💵 Efectivo</span>
            </label>
            <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'consignacion'
                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300' }}">
                <input type="radio" name="tipo_pago" value="consignacion"
                       {{ old('tipo_pago', $movimiento->tipo_pago ?? '') === 'consignacion' ? 'checked' : '' }}
                       class="accent-purple-500">
                <span class="font-bold text-purple-700 dark:text-purple-400">🏦 Consignación</span>
            </label>
        </div>
        @error('tipo_pago') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Monto --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Monto ($) *</label>
        <input type="text" id="monto_visual" required
               value="{{ old('monto', isset($movimiento) ? number_format($movimiento->monto, 0, ',', '.') : '') }}"
               placeholder="0" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-bold">
        <input type="hidden" name="monto" id="monto_real" value="{{ old('monto', $movimiento->monto ?? '') }}">
        @error('monto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Concepto --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Concepto *</label>
        <div class="flex gap-2">
            <select name="concepto_id" id="concepto_select" class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <option value="">-- Seleccionar concepto --</option>
                @foreach($conceptos as $c)
                    <option value="{{ $c->id }}"
                        {{ old('concepto_id', $movimiento->concepto_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->nombre }}
                    </option>
                @endforeach
                <option value="__nuevo__">✏️ Crear nuevo concepto...</option>
            </select>
        </div>
        {{-- Campo oculto para nuevo concepto --}}
        <div id="nuevo-concepto-box" class="mt-2 hidden">
            <div class="flex gap-2">
                <input type="text" id="nuevo_concepto_input" name="nuevo_concepto"
                       placeholder="Nombre del nuevo concepto..."
                       class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <button type="button" onclick="crearConcepto()"
                        class="px-4 py-2 bg-green-500 text-white rounded-xl font-bold hover:bg-green-600 transition-all text-sm">
                    Agregar
                </button>
                <button type="button" onclick="cancelarNuevoConcepto()"
                        class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition-all text-sm">
                    ✕
                </button>
            </div>
            <p id="concepto-status" class="text-xs mt-1 text-gray-500"></p>
        </div>
        @error('concepto_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Descripción --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción (Opcional)</label>
        <textarea name="descripcion" rows="2" placeholder="Detalles adicionales del movimiento..."
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">{{ old('descripcion', $movimiento->descripcion ?? '') }}</textarea>
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
    }
    formatInput('monto_visual', 'monto_real');

    // --- BÚSQUEDA DE CLIENTES ---
    @php
        $clientesData = \App\Models\Cliente::orderBy('nombre')->get(['id','nombre','identificacion','movil']);
    @endphp
    const todosClientes = @json($clientesData);

    function buscarClienteCaja() {
        const termino = document.getElementById('cliente_busqueda').value.trim().toLowerCase();
        const resultadosDiv = document.getElementById('cliente_resultados');
        resultadosDiv.innerHTML = '';

        if (!termino) {
            resultadosDiv.classList.add('hidden');
            return;
        }

        const encontrados = todosClientes.filter(c =>
            c.nombre.toLowerCase().includes(termino) ||
            (c.identificacion && c.identificacion.toLowerCase().includes(termino))
        );

        if (encontrados.length === 0) {
            resultadosDiv.innerHTML = '<p class="text-xs text-gray-500 py-2">Sin resultados.</p>';
        } else {
            encontrados.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 text-sm bg-white dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/30 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors';
                btn.innerHTML = `<span class="font-bold text-gray-800 dark:text-white">${c.nombre}</span> <span class="text-xs text-gray-500">| ${c.identificacion || ''} | ${c.movil || ''}</span>`;
                btn.onclick = () => seleccionarClienteCaja(c);
                resultadosDiv.appendChild(btn);
            });
        }
        resultadosDiv.classList.remove('hidden');
    }

    function seleccionarClienteCaja(cliente) {
        document.getElementById('caja_persona').value = cliente.nombre;
        document.getElementById('caja_empresa').value = '';
        document.getElementById('cliente_busqueda').value = cliente.nombre + ' (' + (cliente.identificacion || '') + ')';
        document.getElementById('cliente_resultados').classList.add('hidden');
    }

    function limpiarClienteCaja() {
        document.getElementById('cliente_busqueda').value = '';
        document.getElementById('cliente_resultados').classList.add('hidden');
    }

    // Búsqueda al presionar Enter
    document.getElementById('cliente_busqueda').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') buscarClienteCaja();
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

        status.textContent = 'Creando...';
        status.className = 'text-xs mt-1 text-gray-500';

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
                status.className = 'text-xs mt-1 text-red-500';
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
            status.textContent = `✅ Concepto "${data.nombre}" creado y seleccionado.`;
            status.className = 'text-xs mt-1 text-green-600';
            setTimeout(() => { status.textContent = ''; }, 3000);

        } catch (e) {
            status.textContent = 'Error de conexión.';
            status.className = 'text-xs mt-1 text-red-500';
        }
    }
</script>

