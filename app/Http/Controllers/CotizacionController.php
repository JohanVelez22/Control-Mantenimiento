<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Services\AnulacionService;
use App\Services\OrdenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    public function index()
    {
        $cotizaciones = Cotizacion::with(['cliente', 'proveedor', 'user', 'items'])->orderBy('id', 'desc')->paginate(15);

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        $clientes = Cliente::activos()->orderBy('nombres')->get();
        $proveedores = Proveedor::activos()->orderBy('nombre_razon_social')->get();
        $stocks = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();

        return view('cotizaciones.create', compact('clientes', 'proveedores', 'stocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'facturable_global' => 'nullable|string',
            'cliente_id' => 'nullable|exists:clientes,id',
            'fecha' => 'required|date',
            'validez_dias' => 'required|integer|min:1',
            'notas' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.tipo' => 'required|in:stock,mantenimiento,electronica,libre',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.item_id' => 'nullable|exists:stocks,id',
        ]);

        $clienteId = null;
        $proveedorId = null;

        if ($request->filled('facturable_global')) {
            $parts = explode(':', $request->facturable_global);
            if (count($parts) === 2) {
                if ($parts[0] === 'Proveedor') {
                    $proveedorId = (int) $parts[1];
                } else {
                    $clienteId = (int) $parts[1];
                }
            }
        } elseif ($request->filled('cliente_id')) {
            $clienteId = (int) $request->cliente_id;
        }

        if (! $clienteId && ! $proveedorId) {
            return back()->withErrors(['facturable_global' => 'Debe seleccionar un cliente o proveedor destinatario.'])->withInput();
        }

        try {
            DB::beginTransaction();

            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            // Generar siguiente código atómicamente usando OrdenService (previene condiciones de carrera)
            $codigo = app(OrdenService::class)->siguiente('COT-', Cotizacion::class, 'codigo');

            $cotizacion = Cotizacion::create([
                'codigo' => $codigo,
                'cliente_id' => $clienteId,
                'proveedor_id' => $proveedorId,
                'fecha' => $request->fecha,
                'validez_dias' => $request->validez_dias,
                'total' => $total,
                'estado' => 'pendiente',
                'notas' => $request->notas,
                'user_id' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $item['tipo'],
                    'item_id' => $item['tipo'] === 'stock' ? $item['item_id'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            DB::commit();

            return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al crear la cotización: '.$e->getMessage())->withInput();
        }
    }

    public function edit(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return redirect()->route('cotizaciones.show', $cotizacion)->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $clientes = Cliente::where(function ($q) use ($cotizacion) {
            $q->activos();
            if ($cotizacion->cliente_id) {
                $q->orWhere('id', $cotizacion->cliente_id);
            }
        })->orderBy('nombres')->get();

        $proveedores = Proveedor::where(function ($q) use ($cotizacion) {
            $q->activos();
            if ($cotizacion->proveedor_id) {
                $q->orWhere('id', $cotizacion->proveedor_id);
            }
        })->orderBy('nombre_razon_social')->get();

        $stocks = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        $cotizacion->load(['items', 'cliente', 'proveedor']);

        return view('cotizaciones.edit', [
            'cotizacion' => $cotizacion,
            'clientes' => $clientes,
            'proveedores' => $proveedores,
            'stocks' => $stocks,
        ]);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $request->validate([
            'facturable_global' => 'nullable|string',
            'cliente_id' => 'nullable|exists:clientes,id',
            'fecha' => 'required|date',
            'validez_dias' => 'required|integer|min:1',
            'notas' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.tipo' => 'required|in:stock,mantenimiento,electronica,libre',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.item_id' => 'nullable|exists:stocks,id',
        ]);

        $clienteId = null;
        $proveedorId = null;

        if ($request->filled('facturable_global')) {
            $parts = explode(':', $request->facturable_global);
            if (count($parts) === 2) {
                if ($parts[0] === 'Proveedor') {
                    $proveedorId = (int) $parts[1];
                } else {
                    $clienteId = (int) $parts[1];
                }
            }
        } elseif ($request->filled('cliente_id')) {
            $clienteId = (int) $request->cliente_id;
        }

        if (! $clienteId && ! $proveedorId) {
            return back()->withErrors(['facturable_global' => 'Debe seleccionar un cliente o proveedor destinatario.'])->withInput();
        }

        try {
            DB::beginTransaction();

            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            $cotizacion->update([
                'cliente_id' => $clienteId,
                'proveedor_id' => $proveedorId,
                'fecha' => $request->fecha,
                'validez_dias' => $request->validez_dias,
                'total' => $total,
                'notas' => $request->notas,
            ]);

            // Eliminar ítems anteriores y crear nuevos
            $cotizacion->items()->delete();

            foreach ($request->items as $item) {
                CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $item['tipo'],
                    'item_id' => $item['tipo'] === 'stock' ? $item['item_id'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            DB::commit();

            return redirect()->route('cotizaciones.index')->with('success', 'Cotización actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al actualizar: '.$e->getMessage())->withInput();
        }
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'proveedor', 'items.stock', 'user');

        return view('cotizaciones.show', ['cotizacion' => $cotizacion]);
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'proveedor', 'items.stock', 'user');
        $empresa = Configuracion::first() ?? new Configuracion;
        $pdf = Pdf::loadView('cotizaciones.pdf', compact('cotizacion', 'empresa'));
        $pdf->setPaper('letter');

        return $pdf->stream('Cotizacion_'.$cotizacion->codigo.'.pdf');
    }

    public function anular(Request $request, Cotizacion $cotizacion)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        try {
            DB::beginTransaction();

            // Toggle anulado (like Mantenimiento/Electronica)
            $esAnulacion = ! $cotizacion->anulado;
            $cotizacion->update([
                'anulado' => $esAnulacion,
            ]);

            $mensaje = $esAnulacion
                ? 'Cotización anulada correctamente.'
                : 'Cotización reactivada correctamente.';

            DB::commit();

            return back()->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al cambiar estado: '.$e->getMessage());
        }
    }

    public function rechazar(Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Solo las cotizaciones pendientes pueden ser rechazadas.');
        }

        // Cambio de estado simple (sin contraseña) - se usa desde formulario con confirmación nativa
        $cotizacion->update(['estado' => 'rechazada']);

        return back()->with('success', 'Cotización rechazada correctamente.');
    }

    public function reactivar(Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'rechazada') {
            return back()->with('error', 'Solo las cotizaciones rechazadas pueden ser reactivadas.');
        }

        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        $cotizacion->anulado = false;
        $cotizacion->estado = 'pendiente';
        $cotizacion->save();

        return back()->with('success', 'Cotización reactivada correctamente.');
    }

    public function convertir($cotizacion)
    {
        if (! $cotizacion instanceof Cotizacion || ! $cotizacion->exists) {
            $cotizacion = Cotizacion::findOrFail($cotizacion);
        }

        if ($cotizacion->anulado) {
            return back()->with('error', 'No se puede convertir una cotización anulada.');
        }

        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Esta cotización ya fue procesada o rechazada.');
        }

        $cotizacion->load('items');

        // Validar stock disponible para todos los ítems de tipo 'stock'
        foreach ($cotizacion->items as $item) {
            if ($item->tipo === 'stock' && $item->item_id) {
                $stock = Stock::find($item->item_id);
                if (! $stock || ! $stock->tieneDisponible($item->cantidad)) {
                    $prodNombre = $stock ? $stock->producto : $item->descripcion;
                    $disp = $stock ? $stock->cantidad : 0;

                    return back()->with('error', "Stock insuficiente para '{$prodNombre}'. Disponible: {$disp}, requerido: {$item->cantidad}.");
                }
            }
        }

        try {
            DB::beginTransaction();

            // 1. Marcar cotización como aprobada
            $cotizacion->update(['estado' => 'aprobada']);

            $facturableId = $cotizacion->proveedor_id ?: $cotizacion->cliente_id;
            $facturableType = $cotizacion->proveedor_id ? Proveedor::class : Cliente::class;
            $destinatarioNombre = $cotizacion->destinatario_nombre;

            // 2. Crear Factura de Venta
            $factura = Factura::create([
                'numero_factura' => Factura::siguienteNumero('VT-'),
                'tipo_movimiento' => 'venta',
                'estado' => 'pendiente_pago',
                'facturable_id' => $facturableId,
                'facturable_type' => $facturableType,
                'total_documento' => $cotizacion->total,
                'total_pagado' => 0,
                'observaciones' => "Venta generada automáticamente desde Cotización #{$cotizacion->codigo}".($cotizacion->notas ? "\nNotas: {$cotizacion->notas}" : ''),
                'fecha' => now()->toDateString(),
                'user_id' => auth()->id(),
            ]);

            // 3. Crear FacturaItems y descontar stock cuando aplique
            foreach ($cotizacion->items as $item) {
                FacturaItem::create([
                    'factura_id' => $factura->id,
                    'stock_id' => $item->tipo === 'stock' ? $item->item_id : null,
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                ]);

                if ($item->tipo === 'stock' && $item->item_id) {
                    $stock = Stock::find($item->item_id);
                    if ($stock) {
                        $stock->decrementarStock((int) $item->cantidad);
                    }
                }
            }

            // 4. Registrar movimiento raíz en Caja para seguimiento de saldos
            $conceptoVenta = ConceptoCaja::firstOrCreate(['nombre' => 'Venta de Inventario']);
            MovimientoCaja::create([
                'tipo_movimiento' => 'ingreso',
                'tipo_pago' => 'efectivo',
                'monto' => 0,
                'monto_total' => (float) $cotizacion->total,
                'persona' => $destinatarioNombre ?: 'Cliente / Proveedor',
                'concepto_id' => $conceptoVenta->id,
                'descripcion' => "Cobro venta #{$factura->numero_factura}",
                'fecha' => now()->toDateString(),
                'estado' => 'activo',
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', "Cotización {$cotizacion->codigo} aprobada y convertida en Venta #{$factura->numero_factura} exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al convertir cotización: '.$e->getMessage());

            return back()->with('error', 'Error al procesar la conversión: '.$e->getMessage());
        }
    }
}
