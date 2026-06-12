<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">N° Orden (auto si vacío)</label>
        <input type="text" name="id_orden" value="{{ old('id_orden', $electronica->id_orden ?? $nextOrden ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('id_orden') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Buscar cliente o proveedor --}}
    <div class="md:col-span-2 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700/50 rounded-xl">
        <label class="block text-sm font-semibold text-purple-700 dark:text-purple-300 mb-2">🔍 Buscar Cliente (Opcional)</label>
        <div class="flex gap-2">
            <input type="text" id="cliente_busqueda" placeholder="Buscar por nombre o cédula..."
                   class="flex-1 bg-white dark:bg-gray-700 border border-purple-300 dark:border-purple-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-sm">
            <button type="button" onclick="buscarCliente()" class="px-4 py-2 bg-purple-500 text-white rounded-xl font-bold hover:bg-purple-600 transition-all text-sm">Buscar</button>
            <button type="button" onclick="limpiarCliente()" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 transition-all text-sm">✕</button>
        </div>
        <div id="cliente_resultados" class="mt-2 hidden space-y-1 max-h-40 overflow-y-auto"></div>
        <p class="text-xs text-purple-500 dark:text-purple-400 mt-1">Selecciona un cliente para autocompletar, o escribe el nombre directamente abajo.</p>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cliente *</label>
        <input type="text" name="cliente" id="electronica_cliente" required value="{{ old('cliente', $electronica->cliente ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('cliente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Dispositivo *</label>
        <input type="text" name="dispositivo" required value="{{ old('dispositivo', $electronica->dispositivo ?? '') }}" placeholder="Ej: Televisor, Celular, Tablet..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('dispositivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Marca</label>
        <input type="text" name="marca" value="{{ old('marca', $electronica->marca ?? '') }}" placeholder="Ej: Samsung, LG, Apple..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción del Problema *</label>
        <textarea name="descripcion_problema" required rows="3" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">{{ old('descripcion_problema', $electronica->descripcion_problema ?? '') }}</textarea>
        @error('descripcion_problema') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo *</label>
        <select name="tipo" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="correctivo" {{ old('tipo', $electronica->tipo ?? 'correctivo') === 'correctivo' ? 'selected' : '' }}>Correctivo</option>
            <option value="preventivo" {{ old('tipo', $electronica->tipo ?? '') === 'preventivo' ? 'selected' : '' }}>Preventivo</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Estado *</label>
        <select name="estado" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="pendiente" {{ old('estado', $electronica->estado ?? 'pendiente') === 'pendiente' ? 'selected' : '' }}>⏳ Pendiente</option>
            <option value="terminado" {{ old('estado', $electronica->estado ?? '') === 'terminado' ? 'selected' : '' }}>✅ Terminado</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Costo ($) *</label>
        <input type="text" id="costo_visual" value="{{ old('costo', isset($electronica) ? number_format($electronica->costo, 0, '', '') : 0) }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all" placeholder="0">
        <input type="hidden" name="costo" id="costo_real" value="{{ old('costo', isset($electronica) ? intval($electronica->costo) : 0) }}">
        @error('costo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Técnico *</label>
        <select name="tecnico_id" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="">-- Seleccionar técnico --</option>
            @foreach($tecnicos as $t)
                <option value="{{ $t->id }}" {{ old('tecnico_id', $electronica->tecnico_id ?? '') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
        @error('tecnico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha Entrada *</label>
        <input type="date" name="fecha_entrada" required value="{{ old('fecha_entrada', isset($electronica) ? $electronica->fecha_entrada->format('Y-m-d') : date('Y-m-d')) }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('fecha_entrada') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha Salida</label>
        <input type="date" name="fecha_salida" value="{{ old('fecha_salida', isset($electronica) && $electronica->fecha_salida ? $electronica->fecha_salida->format('Y-m-d') : '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('fecha_salida') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
</div>

<script>
    // Formateador de monto
    function formatInput(visualId, realId) {
        const inputVisual = document.getElementById(visualId);
        const inputReal = document.getElementById(realId);

        if(!inputVisual || !inputReal) return;

        // Init visual
        if (inputReal.value) {
            inputVisual.value = new Intl.NumberFormat('es-CO').format(inputReal.value);
        }

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
    formatInput('costo_visual', 'costo_real');

    // --- BÚSQUEDA DE CLIENTES ---
    @php
        $clientesData = \App\Models\Cliente::orderBy('nombre')->get(['id','nombre','identificacion','movil']);
    @endphp
    const todosClientes = @json($clientesData);

    function buscarCliente() {
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
                btn.className = 'w-full text-left px-3 py-2 text-sm bg-white dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-purple-900/30 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors';
                btn.innerHTML = `<span class="font-bold text-gray-800 dark:text-white">${c.nombre}</span> <span class="text-xs text-gray-500">| ${c.identificacion || ''} | ${c.movil || ''}</span>`;
                btn.onclick = () => seleccionarCliente(c);
                resultadosDiv.appendChild(btn);
            });
        }
        resultadosDiv.classList.remove('hidden');
    }

    function seleccionarCliente(cliente) {
        document.getElementById('electronica_cliente').value = cliente.nombre;
        document.getElementById('cliente_busqueda').value = cliente.nombre + ' (' + (cliente.identificacion || '') + ')';
        document.getElementById('cliente_resultados').classList.add('hidden');
    }

    function limpiarCliente() {
        document.getElementById('cliente_busqueda').value = '';
        document.getElementById('cliente_resultados').classList.add('hidden');
    }

    document.getElementById('cliente_busqueda').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') buscarCliente();
    });
</script>
