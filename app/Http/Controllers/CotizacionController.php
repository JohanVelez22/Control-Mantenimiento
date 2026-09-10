<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    public function index()
    {
        $cotizaciones = \App\Models\Cotizacion::with(['cliente', 'proveedor', 'user', 'items'])->orderBy('id', 'desc')->paginate(15);
        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        $clientes = \App\Models\Cliente::activos()->orderBy('nombres')->get();
        $proveedores = \App\Models\Proveedor::activos()->orderBy('nombre_razon_social')->get();
        $stocks = \App\Models\Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        return view('cotizaciones.create', compact('clientes', 'proveedores', 'stocks'));
    }

    public function store(\Illuminate\Http\Request $request)
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

        if (!$clienteId && !$proveedorId) {
            return back()->withErrors(['facturable_global' => 'Debe seleccionar un cliente o proveedor destinatario.'])->withInput();
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            // Generar siguiente código atómicamente usando OrdenService (previene condiciones de carrera)
            $codigo = app(\App\Services\OrdenService::class)->siguiente('COT-', \App\Models\Cotizacion::class, 'codigo');

            $cotizacion = \App\Models\Cotizacion::create([
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
                \App\Models\CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $item['tipo'],
                    'item_id' => $item['tipo'] === 'stock' ? $item['item_id'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada exitosamente.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error al crear la cotización: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(\App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return redirect()->route('cotizaciones.show', $cotizacion)->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $clientes = \App\Models\Cliente::where(function($q) use ($cotizacion) {
            $q->activos();
            if ($cotizacion->cliente_id) {
                $q->orWhere('id', $cotizacion->cliente_id);
            }
        })->orderBy('nombres')->get();

        $proveedores = \App\Models\Proveedor::where(function($q) use ($cotizacion) {
            $q->activos();
            if ($cotizacion->proveedor_id) {
                $q->orWhere('id', $cotizacion->proveedor_id);
            }
        })->orderBy('nombre_razon_social')->get();

        $stocks = \App\Models\Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        $cotizacion->load(['items', 'cliente', 'proveedor']);
        
        return view('cotizaciones.edit', [
            'cotizacion' => $cotizacion,
            'clientes' => $clientes,
            'proveedores' => $proveedores,
            'stocks' => $stocks,
        ]);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
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

        if (!$clienteId && !$proveedorId) {
            return back()->withErrors(['facturable_global' => 'Debe seleccionar un cliente o proveedor destinatario.'])->withInput();
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

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
                \App\Models\CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $item['tipo'],
                    'item_id' => $item['tipo'] === 'stock' ? $item['item_id'] : null,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('cotizaciones.index')->with('success', 'Cotización actualizada exitosamente.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    public function show(\App\Models\Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'proveedor', 'items.stock', 'user');
        return view('cotizaciones.show', ['cotizacion' => $cotizacion]);
    }

    public function pdf(\App\Models\Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'proveedor', 'items.stock', 'user');
        $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('cotizaciones.pdf', compact('cotizacion', 'empresa'));
        $pdf->setPaper('letter');
        return $pdf->stream('Cotizacion_' . $cotizacion->codigo . '.pdf');
    }

    public function anular(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
    {
        // El modal global usa 'password_confirm'; tecnico requiere contraseña de admin.
        $password = $request->input('admin_password') ?? $request->input('password_confirm');

        if (auth()->user()->isTecnico()) {
            $request->validate(['admin_password' => 'required_without:password_confirm']);
            if (!$password || !app(\App\Services\AnulacionService::class)->adminPasswordValida($password)) {
                return back()->with('error', 'Se requiere la contraseña de un administrador para anular.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required_without:admin_password']);
            if (!$password || !app(\App\Services\AnulacionService::class)->passwordValida($password)) {
                return back()->with('error', 'Contraseña incorrecta.');
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Toggle anulado (like Mantenimiento/Electronica)
            $esAnulacion = !$cotizacion->anulado;
            $cotizacion->update([
                'anulado' => $esAnulacion,
            ]);

            $mensaje = $esAnulacion
                ? 'Cotización anulada correctamente.'
                : 'Cotización reactivada correctamente.';

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', $mensaje);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error al cambiar estado: ' . $e->getMessage());
        }
    }

    public function rechazar(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Solo las cotizaciones pendientes pueden ser rechazadas.');
        }

        // Cambio de estado simple (sin contraseña) - se usa desde formulario con confirmación nativa
        $cotizacion->update(['estado' => 'rechazada']);

        return back()->with('success', 'Cotización rechazada correctamente.');
    }

    public function reactivar(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'rechazada') {
            return back()->with('error', 'Solo las cotizaciones rechazadas pueden ser reactivadas.');
        }

        // El modal global usa 'password_confirm'; tecnico requiere contraseña de admin.
        $password = $request->input('admin_password') ?? $request->input('password_confirm');

        if (auth()->user()->isTecnico()) {
            $request->validate(['admin_password' => 'required_without:password_confirm']);
            if (!$password || !app(\App\Services\AnulacionService::class)->adminPasswordValida($password)) {
                return back()->with('error', 'Se requiere la contraseña de un administrador para reactivar.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required_without:admin_password']);
            if (!$password || !app(\App\Services\AnulacionService::class)->passwordValida($password)) {
                return back()->with('error', 'Contraseña incorrecta.');
            }
        }

        $cotizacion->anulado = false;
        $cotizacion->estado = 'pendiente';
        $cotizacion->save();

        return back()->with('success', 'Cotización reactivada correctamente.');
    }

    public function convertir($cotizacion)
    {
        if (!$cotizacion instanceof \App\Models\Cotizacion || !$cotizacion->exists) {
            $cotizacion = \App\Models\Cotizacion::findOrFail($cotizacion);
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
                $stock = \App\Models\Stock::find($item->item_id);
                if (!$stock || !$stock->tieneDisponible($item->cantidad)) {
                    $prodNombre = $stock ? $stock->producto : $item->descripcion;
                    $disp = $stock ? $stock->cantidad : 0;
                    return back()->with('error', "Stock insuficiente para '{$prodNombre}'. Disponible: {$disp}, requerido: {$item->cantidad}.");
                }
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Marcar cotización como aprobada
            $cotizacion->update(['estado' => 'aprobada']);

            $facturableId = $cotizacion->proveedor_id ?: $cotizacion->cliente_id;
            $facturableType = $cotizacion->proveedor_id ? \App\Models\Proveedor::class : \App\Models\Cliente::class;
            $destinatarioNombre = $cotizacion->destinatario_nombre;

            // 2. Crear Factura de Venta
            $factura = \App\Models\Factura::create([
                'numero_factura'  => \App\Models\Factura::siguienteNumero('VT-'),
                'tipo_movimiento' => 'venta',
                'estado'          => 'pendiente_pago',
                'facturable_id'   => $facturableId,
                'facturable_type' => $facturableType,
                'total_documento' => $cotizacion->total,
                'total_pagado'    => 0,
                'observaciones'   => "Venta generada automáticamente desde Cotización #{$cotizacion->codigo}" . ($cotizacion->notas ? "\nNotas: {$cotizacion->notas}" : ''),
                'fecha'           => now()->toDateString(),
                'user_id'         => auth()->id(),
            ]);

            // 3. Crear FacturaItems y descontar stock cuando aplique
            foreach ($cotizacion->items as $item) {
                \App\Models\FacturaItem::create([
                    'factura_id'      => $factura->id,
                    'stock_id'        => $item->tipo === 'stock' ? $item->item_id : null,
                    'descripcion'     => $item->descripcion,
                    'cantidad'        => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                ]);

                if ($item->tipo === 'stock' && $item->item_id) {
                    $stock = \App\Models\Stock::find($item->item_id);
                    if ($stock) {
                        $stock->decrementarStock((int) $item->cantidad);
                    }
                }
            }

            // 4. Registrar movimiento raíz en Caja para seguimiento de saldos
            $conceptoVenta = \App\Models\ConceptoCaja::firstOrCreate(['nombre' => 'Venta de Inventario']);
            \App\Models\MovimientoCaja::create([
                'tipo_movimiento' => 'ingreso',
                'tipo_pago'       => 'efectivo',
                'monto'           => 0,
                'monto_total'     => (float) $cotizacion->total,
                'persona'         => $destinatarioNombre ?: 'Cliente / Proveedor',
                'concepto_id'     => $conceptoVenta->id,
                'descripcion'     => "Cobro venta #{$factura->numero_factura}",
                'fecha'           => now()->toDateString(),
                'estado'          => 'activo',
                'user_id'         => auth()->id(),
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', "Cotización {$cotizacion->codigo} aprobada y convertida en Venta #{$factura->numero_factura} exitosamente.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error al convertir cotización: ' . $e->getMessage());
            return back()->with('error', 'Error al procesar la conversión: ' . $e->getMessage());
        }
    }
}
