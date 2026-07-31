<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Stock;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MovimientoInventarioController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    //  COMPRA: Alimentar stock desde un proveedor
    // ═══════════════════════════════════════════════════════════════

    public function createCompra(): View
    {
        $proveedores = Proveedor::activos()->orderBy('nombre_razon_social')->get();
        $clientes    = Cliente::activos()->orderBy('nombres')->orderBy('apellidos')->get();
        $stocks      = Stock::activos()->orderBy('producto')->get();

        return view('inventario.compra', compact('proveedores', 'clientes', 'stocks'));
    }

    public function registrarCompra(Request $request): RedirectResponse
    {
        if ($request->has('total_pagado')) {
            $request->merge(['total_pagado' => $this->cleanAmount($request->total_pagado)]);
        }
        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as &$item) {
                if (isset($item['precio_unitario'])) {
                    $item['precio_unitario'] = $this->cleanAmount($item['precio_unitario']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $request->validate([
            'facturable_global'       => ['required', 'string'],
            'fecha'                   => ['required', 'date'],
            'total_pagado'            => ['required', 'numeric', 'min:0'],
            'observaciones'           => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.stock_id'        => ['required', 'exists:stocks,id'],
            'items.*.cantidad'        => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            list($type, $id) = explode(':', $request->facturable_global);
            if ($type === 'Proveedor') {
                $entity = Proveedor::findOrFail($id);
                $facturableType = Proveedor::class;
                $entityName = $entity->nombre_razon_social;
            } else {
                $entity = Cliente::findOrFail($id);
                $facturableType = Cliente::class;
                $entityName = $entity->nombre;
            }

            $totalDocumento = $this->calcularTotal($request->items);
            $totalPagado    = (float) $request->total_pagado;

            // No permitir pagar más de lo debido (evita saldos negativos)
            if ($totalPagado > $totalDocumento + 0.001) {
                DB::rollBack();
                return back()->with('error', 'El valor pagado no puede superar el total del documento.')->withInput();
            }

            $saldo          = $totalDocumento - $totalPagado;
            $estado         = $saldo > 0.01 ? 'pendiente_pago' : 'emitida';

            // 1. Crear la factura
            $factura = Factura::create([
                'numero_factura'  => Factura::siguienteNumero('CP-'),
                'tipo_movimiento' => 'compra',
                'estado'          => $estado,
                'facturable_id'   => $entity->id,
                'facturable_type' => $facturableType,
                'total_documento' => $totalDocumento,
                'total_pagado'    => $totalPagado,
                'observaciones'   => $this->buildObservaciones($request->observaciones, $saldo),
                'fecha'           => $request->fecha,
                'user_id'         => Auth::id(),
            ]);

            // 2. Registrar ítems e incrementar stock
            foreach ($request->items as $item) {
                $stock = Stock::findOrFail($item['stock_id']);

                FacturaItem::create([
                    'factura_id'      => $factura->id,
                    'stock_id'        => $stock->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => (float) $item['precio_unitario'],
                ]);

                // Actualizar proveedor del artículo si es Proveedor
                if ($type === 'Proveedor') {
                    $stock->update(['proveedor_id' => $entity->id]);
                }
                // Incrementar stock de forma atómica
                $stock->incrementarStock((int) $item['cantidad']);
            }

            // 3. Si hay pago parcial, registrar egreso en Caja
            if ($totalPagado > 0) {
                $this->registrarMovimientoCaja(
                    tipo: 'egreso',
                    monto: $totalPagado,
                    persona: $entityName,
                    descripcion: "Pago compra #{$factura->numero_factura}",
                    fecha: $request->fecha
                );
            }

            // 4. Alerta interna si queda saldo pendiente
            if ($saldo > 0.01) {
                session()->flash('alert_compra_pendiente', [
                    'factura' => $factura->numero_factura,
                    'saldo'   => $saldo,
                    'proveedor' => $entityName,
                ]);
            }

            DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', "Compra #{$factura->numero_factura} registrada correctamente." .
                    ($saldo > 0.01 ? " ⚠️ Saldo pendiente con proveedor: $" . number_format($saldo, 2) : ''));
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando compra: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Error al procesar la compra. Intenta de nuevo.')->withInput();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  VENTA: Descontar stock hacia un cliente
    // ═══════════════════════════════════════════════════════════════

    public function createVenta(): View
    {
        $clientes    = Cliente::activos()->orderBy('nombres')->orderBy('apellidos')->get();
        $proveedores = Proveedor::activos()->orderBy('nombre_razon_social')->get();
        $stocks      = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        return view('inventario.venta', compact('clientes', 'proveedores', 'stocks'));
    }

    public function registrarVenta(Request $request): RedirectResponse
    {
        if ($request->has('total_pagado')) {
            $request->merge(['total_pagado' => $this->cleanAmount($request->total_pagado)]);
        }
        if ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
            foreach ($items as &$item) {
                if (isset($item['precio_unitario'])) {
                    $item['precio_unitario'] = $this->cleanAmount($item['precio_unitario']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $request->validate([
            'facturable_global'       => ['required', 'string'],
            'fecha'                   => ['required', 'date'],
            'total_pagado'            => ['required', 'numeric', 'min:0'],
            'observaciones'           => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.stock_id'        => ['required', 'exists:stocks,id'],
            'items.*.cantidad'        => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            list($type, $id) = explode(':', $request->facturable_global);
            if ($type === 'Proveedor') {
                $entity = Proveedor::findOrFail($id);
                $facturableType = Proveedor::class;
                $entityName = $entity->nombre_razon_social;
            } else {
                $entity = Cliente::findOrFail($id);
                $facturableType = Cliente::class;
                $entityName = $entity->nombre;
            }

            $totalDocumento = $this->calcularTotal($request->items);
            $totalPagado    = (float) $request->total_pagado;

            // No permitir cobrar más de lo debido (evita saldos negativos)
            if ($totalPagado > $totalDocumento + 0.001) {
                DB::rollBack();
                return back()->with('error', 'El valor cobrado no puede superar el total del documento.')->withInput();
            }

            $saldo          = $totalDocumento - $totalPagado;
            $estado         = $saldo > 0.01 ? 'pendiente_pago' : 'emitida';

            // 1. Pre-validar disponibilidad de TODOS los ítems antes de modificar BD
            foreach ($request->items as $item) {
                $stock = Stock::findOrFail($item['stock_id']);
                if (!$stock->tieneDisponible((int) $item['cantidad'])) {
                    throw new \DomainException(
                        "Stock insuficiente para '{$stock->producto}'. Disponible: {$stock->cantidad}."
                    );
                }
            }

            // 2. Crear la factura
            $factura = Factura::create([
                'numero_factura'  => Factura::siguienteNumero('VT-'),
                'tipo_movimiento' => 'venta',
                'estado'          => $estado,
                'facturable_id'   => $entity->id,
                'facturable_type' => $facturableType,
                'total_documento' => $totalDocumento,
                'total_pagado'    => $totalPagado,
                'observaciones'   => $this->buildObservaciones($request->observaciones, $saldo),
                'fecha'           => $request->fecha,
                'user_id'         => Auth::id(),
            ]);

            // 3. Registrar ítems y descontar stock
            foreach ($request->items as $item) {
                $stock = Stock::findOrFail($item['stock_id']);

                FacturaItem::create([
                    'factura_id'      => $factura->id,
                    'stock_id'        => $stock->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => (float) $item['precio_unitario'],
                ]);

                $stock->decrementarStock((int) $item['cantidad']);
            }

            // 4. Si hay pago parcial, registrar ingreso en Caja
            if ($totalPagado > 0) {
                $this->registrarMovimientoCaja(
                    tipo: 'ingreso',
                    monto: $totalPagado,
                    persona: $entityName,
                    descripcion: "Cobro venta #{$factura->numero_factura}",
                    fecha: $request->fecha
                );
            }

            // 5. Alerta interna si queda saldo por cobrar
            if ($saldo > 0.01) {
                session()->flash('alert_venta_pendiente', [
                    'factura' => $factura->numero_factura,
                    'saldo'   => $saldo,
                    'cliente' => $entityName,
                ]);
            }

            DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', "Venta #{$factura->numero_factura} registrada correctamente." .
                    ($saldo > 0.01 ? " ⚠️ Saldo pendiente por cobrar: $" . number_format($saldo, 2) : ''));
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando venta: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Error al procesar la venta. Intenta de nuevo.')->withInput();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  FACTURAS: Listado e impresión
    // ═══════════════════════════════════════════════════════════════

    public function facturas(Request $request): View
    {
        $fecha_desde = $request->input('fecha_desde', date('Y-m-01'));
        $fecha_hasta = $request->input('fecha_hasta', date('Y-m-d'));

        // Sincroniza con el request para que Blade coincida
        $request->merge([
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
        ]);

        $query = Factura::with(['facturable', 'user'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo_movimiento', $request->tipo);
        }
        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('valor_total')) {
            $valor_total = str_replace('.', '', $request->input('valor_total'));
            $query->where('total_documento', '=', $valor_total);
        }
        
        $query->whereDate('fecha', '>=', $fecha_desde);
        $query->whereDate('fecha', '<=', $fecha_hasta);

        $facturas = $query->paginate(10);

        return view('inventario.facturas.index', compact('facturas'));
    }

    public function showFactura(Factura $factura): View
    {
        $factura->load(['facturable', 'items.stock', 'user']);

        $movimientoPadre = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('descripcion', 'like', "%#{$factura->numero_factura}%")
            ->whereNull('parent_id')
            ->with(['childPayments' => fn($q) => $q->where('anulado', false)->with('user')])
            ->first();

        $abonos = $movimientoPadre ? $movimientoPadre->childPayments : collect();

        return view('inventario.facturas.show', compact('factura', 'movimientoPadre', 'abonos'));
    }

    public function printFactura(Factura $factura)
    {
        $factura->load(['facturable', 'items.stock', 'user']);

        $movimientoPadre = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->where('descripcion', 'like', "%#{$factura->numero_factura}%")
            ->whereNull('parent_id')
            ->with(['childPayments' => fn($q) => $q->where('anulado', false)->with('user')])
            ->first();

        $abonos = $movimientoPadre ? $movimientoPadre->childPayments : collect();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventario.facturas.print', compact('factura', 'abonos'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('factura_inventario_' . $factura->numero_factura . '.pdf');
    }

    public function anularFactura(Request $request, Factura $factura): RedirectResponse
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        // Aceptamos ambos nombres de campo para compatibilidad con el modal global.
        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        // Técnico requiere contraseña de admin; admin usa su propia o la de admin.
        if (Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para anular.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.');
            }
        }

        try {
            DB::beginTransaction();

            if ($factura->estado === 'anulada') {
                // REACTIVAR LA FACTURA
                // Verificar si hay stock disponible si es una venta
                if ($factura->tipo_movimiento === 'venta') {
                    foreach ($factura->items as $item) {
                        if (!$item->stock_id || !$item->stock) {
                            continue;
                        }
                        $stock = $item->stock;
                        if (!$stock->tieneDisponible($item->cantidad)) {
                            throw new \DomainException("No se puede reactivar. Stock insuficiente para '{$stock->producto}'. Requerido: {$item->cantidad}, disponible: {$stock->cantidad}.");
                        }
                    }
                }

                foreach ($factura->items as $item) {
                    if (!$item->stock_id || !$item->stock) {
                        continue;
                    }
                    $stock = $item->stock;
                    if ($factura->tipo_movimiento === 'compra') {
                        // Reactivando compra: vuelve a entrar el stock
                        $stock->incrementarStock($item->cantidad);
                    } else {
                        // Reactivando venta: vuelve a salir el stock
                        $stock->decrementarStock($item->cantidad);
                    }
                }

                $saldo = $factura->total_documento - $factura->total_pagado;
                $nuevoEstado = $saldo > 0.01 ? 'pendiente_pago' : 'emitida';

                \App\Models\MovimientoCaja::where('descripcion', 'like', "%#{$factura->numero_factura}%")
                    ->update(['estado' => 'activo', 'anulado' => false]);

                $cleanObs = implode("\n", array_filter(explode("\n", $factura->observaciones ?? ''), fn($l) => !preg_match('/^\[(ANULADA|REACTIVADA) el .* por .*\]$/u', trim($l))));
                $factura->observaciones = trim($cleanObs . "\n[REACTIVADA el " . now()->format('d/m/Y H:i') . ' por ' . Auth::user()->name . ']');
                $factura->estado = 'emitida'; // provisional, recalcularPagos() ajustará si hay saldo pendiente
                $factura->save();
                $factura->recalcularPagos();

                $action = 'reactivada';
            } else {
                // ANULAR LA FACTURA
                foreach ($factura->items as $item) {
                    if (!$item->stock_id || !$item->stock) {
                        continue;
                    }
                    $stock = $item->stock;
                    if ($factura->tipo_movimiento === 'compra') {
                        // Compra anulada: quitar lo que se agregó
                        $stock->decrementarStock($item->cantidad);
                    } else {
                        // Venta anulada: devolver lo que se quitó
                        $stock->incrementarStock($item->cantidad);
                    }
                }

                \App\Models\MovimientoCaja::where('descripcion', 'like', "%#{$factura->numero_factura}%")
                    ->update(['estado' => 'anulado', 'anulado' => true]);

                $cleanObs = implode("\n", array_filter(explode("\n", $factura->observaciones ?? ''), fn($l) => !preg_match('/^\[(ANULADA|REACTIVADA) el .* por .*\]$/u', trim($l))));
                $factura->update([
                    'estado'        => 'anulada',
                    'total_pagado'  => 0,
                    'observaciones' => trim($cleanObs . "\n[ANULADA el " . now()->format('d/m/Y H:i') . ' por ' . Auth::user()->name . ']'),
                ]);

                $action = 'anulada';
            }

            DB::commit();

            return redirect()->back()
                ->with('success', "Factura #{$factura->numero_factura} {$action}. El stock fue actualizado.");
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando/reactivando factura: ' . $e->getMessage());
            return back()->with('error', 'Error al cambiar el estado de la factura.');
        }
    }

    public function editFactura(Factura $factura): View
    {
        $proveedores = Proveedor::where(function($q) use ($factura) {
            $q->activos();
            if ($factura->facturable_type === Proveedor::class) {
                $q->orWhere('id', $factura->facturable_id);
            }
        })->orderBy('nombre_razon_social')->get();
        $clientes = Cliente::where(function($q) use ($factura) {
            $q->activos();
            if ($factura->facturable_type === Cliente::class) {
                $q->orWhere('id', $factura->facturable_id);
            }
        })->orderBy('nombres')->orderBy('apellidos')->get();
        $stocks      = Stock::activos()->orderBy('producto')->get();
        $factura->load('items.stock');
        return view('inventario.facturas.edit', compact('factura', 'proveedores', 'clientes', 'stocks'));
    }

    public function updateFactura(Request $request, Factura $factura): RedirectResponse
    {
        if ($request->has('total_pagado')) {
            $request->merge(['total_pagado' => $this->cleanAmount($request->total_pagado)]);
        }
        if ($request->has('existing_items') && is_array($request->existing_items)) {
            $items = $request->existing_items;
            foreach ($items as &$item) {
                if (array_key_exists('stock_id', $item) && empty($item['stock_id'])) {
                    $item['stock_id'] = null;
                }
                if (isset($item['precio_unitario'])) {
                    $item['precio_unitario'] = $this->cleanAmount($item['precio_unitario']);
                }
            }
            $request->merge(['existing_items' => $items]);
        }
        if ($request->has('new_items') && is_array($request->new_items)) {
            $items = $request->new_items;
            foreach ($items as &$item) {
                if (array_key_exists('stock_id', $item) && empty($item['stock_id'])) {
                    $item['stock_id'] = null;
                }
                if (isset($item['precio_unitario'])) {
                    $item['precio_unitario'] = $this->cleanAmount($item['precio_unitario']);
                }
            }
            $request->merge(['new_items' => $items]);
        }

        $request->validate([
            'fecha'                   => 'required|date',
            'total_pagado'            => 'required|numeric|min:0',
            'observaciones'           => 'nullable|string',
            'facturable_global'       => 'required|string',
            'existing_items'             => 'nullable|array',
            'existing_items.*.id'        => 'required|exists:factura_items,id',
            'existing_items.*.stock_id'  => 'nullable|exists:stocks,id',
            'existing_items.*.descripcion' => 'nullable|string',
            'existing_items.*.cantidad'  => 'required|integer|min:1',
            'existing_items.*.precio_unitario'=> 'required|numeric|min:0',
            'new_items'               => 'nullable|array',
            'new_items.*.stock_id'    => 'required|exists:stocks,id',
            'new_items.*.cantidad'    => 'required|integer|min:1',
            'new_items.*.precio_unitario'=> 'required|numeric|min:0',
        ]);

        list($type, $id) = explode(':', $request->facturable_global);
        if ($type === 'Proveedor') {
            $entity = Proveedor::findOrFail($id);
            $facturableType = Proveedor::class;
        } else {
            $entity = Cliente::findOrFail($id);
            $facturableType = Cliente::class;
        }

        $totalPagado = $this->cleanAmount($request->total_pagado);
        
        $wasAnulada = $factura->estado === 'anulada';
        $shouldBeAnulada = $wasAnulada;
        
        try {
            DB::beginTransaction();

            // 2. Ajustar stock por modificación de cantidad o artículo de los ítems existentes
            if (isset($request->existing_items) && is_array($request->existing_items) && !$shouldBeAnulada) {
                foreach ($request->existing_items as $itemData) {
                    $item = FacturaItem::findOrFail($itemData['id']);
                    $oldStock = $item->stock;
                    $oldQty = (int) $item->cantidad;
                    
                    $newStockId = !empty($itemData['stock_id']) ? (int) $itemData['stock_id'] : null;
                    $newQty = (int) $itemData['cantidad'];
                    $newPrice = $this->cleanAmount($itemData['precio_unitario']);
                    $newDesc = isset($itemData['descripcion']) ? $itemData['descripcion'] : $item->descripcion;



                    if ($oldStock || $newStockId) {
                        if ($oldStock && $newStockId && $oldStock->id !== $newStockId) {
                            $newStock = Stock::where('id', $newStockId)->lockForUpdate()->firstOrFail();
                            if ($factura->tipo_movimiento === 'compra') {
                                $oldStock->decrementarStock($oldQty);
                                $newStock->incrementarStock($newQty);
                            } else {
                                $oldStock->incrementarStock($oldQty);
                                if (!$newStock->tieneDisponible($newQty)) {
                                    throw new \DomainException("Stock insuficiente para '{$newStock->producto}'. Requerido: {$newQty}, disponible: {$newStock->cantidad}.");
                                }
                                $newStock->decrementarStock($newQty);
                            }
                        } elseif ($oldStock && $newStockId && $oldStock->id === $newStockId) {
                            // Es el mismo artículo, solo cambia cantidad
                            $oldStock = Stock::where('id', $oldStock->id)->lockForUpdate()->firstOrFail();
                            $diff = $newQty - $oldQty;
                            if ($diff !== 0) {
                                if ($factura->tipo_movimiento === 'compra') {
                                    if ($diff > 0) {
                                        $oldStock->incrementarStock($diff);
                                    } else {
                                        $oldStock->decrementarStock(abs($diff));
                                    }
                                } else {
                                    if ($diff > 0) {
                                        if (!$oldStock->tieneDisponible($diff)) {
                                            throw new \DomainException("Stock insuficiente para '{$oldStock->producto}'. Requerido adicional: {$diff}, disponible: {$oldStock->cantidad}.");
                                        }
                                        $oldStock->decrementarStock($diff);
                                    } else {
                                        $oldStock->incrementarStock(abs($diff));
                                    }
                                }
                            }
                        } elseif ($oldStock && !$newStockId) {
                            // Tenía stock y ahora cambió a sin stock
                            if ($factura->tipo_movimiento === 'compra') {
                                $oldStock->decrementarStock($oldQty);
                            } else {
                                $oldStock->incrementarStock($oldQty);
                            }
                        } elseif (!$oldStock && $newStockId) {
                            // No tenía stock y se asignó stock
                            $newStock = Stock::where('id', $newStockId)->lockForUpdate()->firstOrFail();
                            if ($factura->tipo_movimiento === 'compra') {
                                $newStock->incrementarStock($newQty);
                            } else {
                                if (!$newStock->tieneDisponible($newQty)) {
                                    throw new \DomainException("Stock insuficiente para '{$newStock->producto}'. Requerido: {$newQty}, disponible: {$newStock->cantidad}.");
                                }
                                $newStock->decrementarStock($newQty);
                            }
                        }
                    }
                    
                    $item->update([
                        'stock_id'        => $newStockId,
                        'descripcion'     => $newDesc,
                        'cantidad'        => $newQty,
                        'precio_unitario' => $newPrice,
                    ]);
                }
            }

            // 2.5. Añadir nuevos ítems a la factura
            if (isset($request->new_items) && is_array($request->new_items) && !$shouldBeAnulada) {
                foreach ($request->new_items as $itemData) {
                    $stock = Stock::where('id', $itemData['stock_id'])->lockForUpdate()->firstOrFail();
                    $newPrice = $this->cleanAmount($itemData['precio_unitario']);
                    
                    FacturaItem::create([
                        'factura_id'      => $factura->id,
                        'stock_id'        => $stock->id,
                        'cantidad'        => $itemData['cantidad'],
                        'precio_unitario' => $newPrice,
                    ]);

                    if ($factura->tipo_movimiento === 'compra') {
                        if ($type === 'Proveedor') {
                            $stock->update(['proveedor_id' => $entity->id]);
                        }
                        $stock->incrementarStock((int) $itemData['cantidad']);
                    } else {
                        if (!$stock->tieneDisponible((int) $itemData['cantidad'])) {
                            throw new \DomainException("Stock insuficiente para '{$stock->producto}'. Requerido: {$itemData['cantidad']}, disponible: {$stock->cantidad}.");
                        }
                        $stock->decrementarStock((int) $itemData['cantidad']);
                    }
                }
            }

            // 4. Calcular el nuevo total de la factura
            $totalDocumento = 0;
            foreach ($factura->items()->get() as $item) {
                $totalDocumento += (float) $item->cantidad * (float) $item->precio_unitario;
            }

            // No permitir que el pagado supere el nuevo total del documento
            if (!$shouldBeAnulada && $totalPagado > $totalDocumento + 0.001) {
                DB::rollBack();
                return back()->with('error', 'El valor pagado no puede superar el total del documento.')->withInput();
            }

            $saldo = $totalDocumento - $totalPagado;
            $estado = $shouldBeAnulada ? 'anulada' : ($saldo > 0.01 ? 'pendiente_pago' : 'emitida');

            // Extraer historial de anulaciones/reactivaciones de la observación actual
            $historial = collect(explode("\n", $factura->observaciones ?? ''))
                ->filter(fn($line) => str_starts_with($line, '[ANULADA') || str_starts_with($line, '[REACTIVADA'))
                ->implode("\n");

            $nuevaObservacion = $this->buildObservaciones($request->observaciones, $saldo, $historial ?: null);

            // Sincronizar movimiento de caja base
            $baseCaja = MovimientoCaja::where('descripcion', 'like', "%#{$factura->numero_factura}%")
                ->whereNull('parent_id')
                ->first();

            $entityName = $entity->nombre_razon_social ?? $entity->nombre;

            if ($totalPagado > 0) {
                if ($baseCaja) {
                    $baseCaja->update([
                        'monto'       => $totalPagado,
                        'monto_total' => $totalDocumento > $totalPagado ? $totalDocumento : null,
                        'persona'     => $entityName,
                        'fecha'       => $request->fecha,
                        'estado'      => $shouldBeAnulada ? 'anulado' : 'activo',
                    ]);
                } else if (!$shouldBeAnulada) {
                    $this->registrarMovimientoCaja(
                        tipo: $factura->tipo_movimiento === 'venta' ? 'ingreso' : 'egreso',
                        monto: $totalPagado,
                        persona: $entityName,
                        descripcion: ($factura->tipo_movimiento === 'venta' ? "Cobro venta #" : "Pago compra #") . $factura->numero_factura,
                        fecha: $request->fecha,
                        montoTotal: $totalDocumento > $totalPagado ? $totalDocumento : null
                    );
                }
            } else if ($baseCaja) {
                $baseCaja->update([
                    'monto'       => 0,
                    'monto_total' => $totalDocumento,
                    'estado'      => 'activo',
                ]);
            }

            $factura->update([
                'fecha'           => $request->fecha,
                'total_pagado'    => $totalPagado,
                'total_documento' => $totalDocumento,
                'estado'          => $estado,
                'observaciones'   => $nuevaObservacion,
                'facturable_id'   => $entity->id,
                'facturable_type' => $facturableType,
            ]);

            if (!$shouldBeAnulada) {
                $factura->recalcularPagos();
            }

            DB::commit();
            return redirect()->route('inventario.facturas')->with('success', 'Factura actualizada correctamente.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando factura: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar la factura.');
        }
    }


    // ─── Helpers Privados ─────────────────────────────────────────

    private function cleanAmount(mixed $val): float
    {
        if (is_null($val) || $val === '') return 0.0;
        if (is_int($val) || is_float($val)) return (float) $val;

        $str = trim((string) $val);

        // 1. Si es un número decimal estándar tipo "50000.00" o "50000.5" (un solo punto, 1 o 2 decimales al final, sin comas)
        if (preg_match('/^\d+\.\d{1,2}$/', $str)) {
            return (float) $str;
        }

        // 2. Si contiene tanto puntos como comas (ej. "5.000.000,00" o "5,000,000.00")
        if (str_contains($str, '.') && str_contains($str, ',')) {
            if (strrpos($str, ',') > strrpos($str, '.')) {
                // Formato español/colombiano: 5.000.000,00 -> quitar punto, reemplazar coma por punto
                $clean = str_replace('.', '', $str);
                $clean = str_replace(',', '.', $clean);
                return (float) $clean;
            } else {
                // Formato inglés: 5,000,000.00 -> quitar coma
                $clean = str_replace(',', '', $str);
                return (float) $clean;
            }
        }

        // 3. Si contiene solo comas (ej. "50000,00" o "50,000")
        if (str_contains($str, ',') && !str_contains($str, '.')) {
            if (preg_match('/^\d+,\d{1,2}$/', $str)) {
                return (float) str_replace(',', '.', $str);
            }
            return (float) str_replace(',', '', $str);
        }

        // 4. Si contiene solo puntos (ej. "50.000" o "5.000.000")
        if (str_contains($str, '.')) {
            if (substr_count($str, '.') > 1) {
                return (float) str_replace('.', '', $str);
            }
            $parts = explode('.', $str);
            $decimalPart = $parts[1] ?? '';
            if (strlen($decimalPart) === 3) {
                return (float) str_replace('.', '', $str);
            }
            return (float) $str;
        }

        return (float) $str;
    }

    private function calcularTotal(array $items): float
    {
        return collect($items)->sum(fn($i) => (float) $i['cantidad'] * $this->cleanAmount($i['precio_unitario']));
    }

    private function buildObservaciones(?string $obs, float $saldo, ?string $historial = null): ?string
    {
        // Limpiar líneas de saldo pendiente anteriores para no duplicarlas
        $lineas = array_filter(explode("\n", $obs ?? ''), fn($l) => !str_starts_with($l, '⚠️ SALDO PENDIENTE:'));
        $obsLimpia = implode("\n", $lineas);

        $parts = array_filter([$obsLimpia]);
        if ($saldo > 0.01) {
            $parts[] = "⚠️ SALDO PENDIENTE: $" . number_format($saldo, 0, ',', '.');
        }
        if ($historial) {
            $parts[] = $historial;
        }
        return implode("\n", $parts) ?: null;
    }

    private function registrarMovimientoCaja(
        string $tipo,
        float  $monto,
        string $persona,
        string $descripcion,
        string $fecha,
        ?float $montoTotal = null
    ): MovimientoCaja {
        $concepto = ConceptoCaja::firstOrCreate(
            ['nombre' => $tipo === 'egreso' ? 'Compra de Inventario' : 'Venta de Inventario']
        );

        return MovimientoCaja::create([
            'tipo_movimiento' => $tipo,
            'tipo_pago'       => 'efectivo',
            'monto'           => $monto,
            'monto_total'     => $montoTotal,
            'persona'         => $persona,
            'concepto_id'     => $concepto->id,
            'descripcion'     => $descripcion,
            'fecha'           => $fecha,
            'estado'          => 'activo',
            'user_id'         => Auth::id(),
        ]);
    }
}
