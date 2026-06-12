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
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        $stocks      = Stock::orderBy('producto')->get();
        $nextNumero  = Factura::siguienteNumero('C');

        return view('inventario.compra', compact('proveedores', 'stocks', 'nextNumero'));
    }

    public function registrarCompra(Request $request): RedirectResponse
    {
        $request->validate([
            'proveedor_id'            => ['required', 'exists:proveedores,id'],
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

            $proveedor     = Proveedor::findOrFail($request->proveedor_id);
            $totalDocumento = $this->calcularTotal($request->items);
            $totalPagado    = (float) $request->total_pagado;
            $saldo          = $totalDocumento - $totalPagado;
            $estado         = $saldo > 0.01 ? 'pendiente_pago' : 'emitida';

            // 1. Crear la factura
            $factura = Factura::create([
                'numero_factura'  => Factura::siguienteNumero('C'),
                'tipo_movimiento' => 'compra',
                'estado'          => $estado,
                'facturable_id'   => $proveedor->id,
                'facturable_type' => Proveedor::class,
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
                    'precio_unitario' => $item['precio_unitario'],
                ]);

                // Actualizar también el proveedor del artículo
                $stock->update(['proveedor_id' => $proveedor->id]);
                // Incrementar stock de forma atómica
                $stock->incrementarStock((int) $item['cantidad']);
            }

            // 3. Si hay pago parcial, registrar egreso en Caja
            if ($totalPagado > 0) {
                $this->registrarMovimientoCaja(
                    tipo: 'egreso',
                    monto: $totalPagado,
                    persona: $proveedor->nombre_razon_social,
                    descripcion: "Pago compra #{$factura->numero_factura}",
                    fecha: $request->fecha
                );
            }

            // 4. Alerta interna si queda saldo pendiente
            if ($saldo > 0.01) {
                session()->flash('alert_compra_pendiente', [
                    'factura' => $factura->numero_factura,
                    'saldo'   => $saldo,
                    'proveedor' => $proveedor->nombre_razon_social,
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
        $clientes   = Cliente::orderBy('nombre')->get();
        $stocks     = Stock::where('cantidad', '>', 0)->orderBy('producto')->get();
        $nextNumero = Factura::siguienteNumero('V');

        return view('inventario.venta', compact('clientes', 'stocks', 'nextNumero'));
    }

    public function registrarVenta(Request $request): RedirectResponse
    {
        $request->validate([
            'cliente_id'              => ['required', 'exists:clientes,id'],
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

            $cliente        = Cliente::findOrFail($request->cliente_id);
            $totalDocumento = $this->calcularTotal($request->items);
            $totalPagado    = (float) $request->total_pagado;
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
                'numero_factura'  => Factura::siguienteNumero('V'),
                'tipo_movimiento' => 'venta',
                'estado'          => $estado,
                'facturable_id'   => $cliente->id,
                'facturable_type' => Cliente::class,
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
                    'precio_unitario' => $item['precio_unitario'],
                ]);

                $stock->decrementarStock((int) $item['cantidad']);
            }

            // 4. Si hay pago parcial, registrar ingreso en Caja
            if ($totalPagado > 0) {
                $this->registrarMovimientoCaja(
                    tipo: 'ingreso',
                    monto: $totalPagado,
                    persona: $cliente->nombre,
                    descripcion: "Cobro venta #{$factura->numero_factura}",
                    fecha: $request->fecha
                );
            }

            // 5. Alerta interna si queda saldo por cobrar
            if ($saldo > 0.01) {
                session()->flash('alert_venta_pendiente', [
                    'factura' => $factura->numero_factura,
                    'saldo'   => $saldo,
                    'cliente' => $cliente->nombre,
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
        $query = Factura::with(['facturable', 'user'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('tipo')) {
            $query->where('tipo_movimiento', $request->tipo);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $facturas = $query->paginate(15);

        return view('inventario.facturas.index', compact('facturas'));
    }

    public function showFactura(Factura $factura): View
    {
        $factura->load(['facturable', 'items.stock', 'user']);
        return view('inventario.facturas.show', compact('factura'));
    }

    public function printFactura(Factura $factura): View
    {
        $factura->load(['facturable', 'items.stock', 'user']);
        return view('inventario.facturas.print', compact('factura'));
    }

    public function anularFactura(Request $request, Factura $factura): RedirectResponse
    {
        if ($factura->estado === 'anulada') {
            return back()->with('error', 'Esta factura ya estaba anulada.');
        }

        try {
            DB::beginTransaction();

            // Revertir el movimiento de stock
            foreach ($factura->items as $item) {
                $stock = $item->stock;
                if ($factura->tipo_movimiento === 'compra') {
                    // Compra anulada: quitar lo que se agregó
                    $stock->decrementarStock($item->cantidad);
                } else {
                    // Venta anulada: devolver lo que se quitó
                    $stock->incrementarStock($item->cantidad);
                }
            }

            $factura->update([
                'estado'        => 'anulada',
                'observaciones' => ($factura->observaciones ?? '') . "\n[ANULADA el " . now()->format('d/m/Y H:i') . ' por ' . Auth::user()->name . ']',
            ]);

            DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', "Factura #{$factura->numero_factura} anulada. El stock fue revertido.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando factura: ' . $e->getMessage());
            return back()->with('error', 'Error al anular la factura.');
        }
    }

    // ─── Helpers Privados ─────────────────────────────────────────

    private function calcularTotal(array $items): float
    {
        return collect($items)->sum(fn($i) => (float) $i['cantidad'] * (float) $i['precio_unitario']);
    }

    private function buildObservaciones(?string $obs, float $saldo): ?string
    {
        $parts = array_filter([$obs]);
        if ($saldo > 0.01) {
            $parts[] = "⚠️ SALDO PENDIENTE: $" . number_format($saldo, 2, '.', ',');
        }
        return implode("\n", $parts) ?: null;
    }

    private function registrarMovimientoCaja(
        string $tipo,
        float  $monto,
        string $persona,
        string $descripcion,
        string $fecha
    ): void {
        $concepto = ConceptoCaja::firstOrCreate(
            ['nombre' => $tipo === 'egreso' ? 'Compra de Inventario' : 'Venta de Inventario']
        );

        MovimientoCaja::create([
            'tipo_movimiento' => $tipo,
            'tipo_pago'       => 'efectivo',
            'monto'           => $monto,
            'persona'         => $persona,
            'concepto_id'     => $concepto->id,
            'descripcion'     => $descripcion,
            'fecha'           => $fecha,
            'estado'          => 'activo',
            'user_id'         => Auth::id(),
        ]);
    }
}
