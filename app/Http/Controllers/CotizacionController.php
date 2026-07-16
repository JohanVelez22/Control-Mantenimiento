<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CotizacionController extends Controller
{
    public function index()
    {
        $cotizaciones = \App\Models\Cotizacion::with('cliente', 'user')->orderBy('id', 'desc')->paginate(15);
        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        $clientes = \App\Models\Cliente::orderBy('nombres')->get();
        $stocks = \App\Models\Stock::where('cantidad', '>', 0)->orderBy('producto')->get();
        return view('cotizaciones.create', compact('clientes', 'stocks'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
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

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            // Generate next code (short version as requested)
            $lastCot = \App\Models\Cotizacion::orderBy('id', 'desc')->first();
            $nextId = $lastCot ? $lastCot->id + 1 : 1;
            $codigo = 'COT-' . $nextId;

            $cotizacion = \App\Models\Cotizacion::create([
                'codigo' => $codigo,
                'cliente_id' => $request->cliente_id,
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

    public function edit(\App\Models\Cotizacion $cotizacione)
    {
        if ($cotizacione->estado !== 'pendiente') {
            return redirect()->route('cotizaciones.show', $cotizacione)->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $clientes = \App\Models\Cliente::orderBy('nombres')->get();
        $stocks = \App\Models\Stock::where('cantidad', '>', 0)->orderBy('producto')->get();
        $cotizacione->load('items');
        
        return view('cotizaciones.edit', [
            'cotizacion' => $cotizacione,
            'clientes' => $clientes,
            'stocks' => $stocks,
        ]);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacione)
    {
        if ($cotizacione->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
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

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            $cotizacione->update([
                'cliente_id' => $request->cliente_id,
                'fecha' => $request->fecha,
                'validez_dias' => $request->validez_dias,
                'total' => $total,
                'notas' => $request->notas,
            ]);

            // Eliminar ítems anteriores y crear nuevos
            $cotizacione->items()->delete();

            foreach ($request->items as $item) {
                \App\Models\CotizacionItem::create([
                    'cotizacion_id' => $cotizacione->id,
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

    public function show(\App\Models\Cotizacion $cotizacione)
    {
        // Parameter is named $cotizacione by default because resource generator is weird with Spanish names
        $cotizacione->load('cliente', 'items.stock', 'user');
        return view('cotizaciones.show', ['cotizacion' => $cotizacione]);
    }

    public function pdf(\App\Models\Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'items.stock', 'user');
        $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('cotizaciones.pdf', compact('cotizacion', 'empresa'));
        $pdf->setPaper('letter');
        return $pdf->stream('Cotizacion_' . $cotizacion->codigo . '.pdf');
    }

    public function convertir(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Solo las cotizaciones pendientes pueden ser convertidas.');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Crear la Factura
            $factura = \App\Models\Factura::create([
                'numero_factura' => \App\Models\Factura::siguienteNumero('VT-'),
                'tipo_movimiento' => 'venta',
                'estado' => 'pendiente_pago',
                'facturable_id' => $cotizacion->cliente_id,
                'facturable_type' => \App\Models\Cliente::class,
                'total_documento' => $cotizacion->total,
                'total_pagado' => 0,
                'observaciones' => "Venta generada a partir de Cotización: " . $cotizacion->codigo,
                'fecha' => now()->toDateString(),
                'user_id' => auth()->id(),
            ]);

            // 2. Procesar ítems y afectar stock
            foreach ($cotizacion->items as $item) {
                if ($item->tipo === 'stock' && $item->item_id) {
                    $stock = \App\Models\Stock::findOrFail($item->item_id);
                    if (!$stock->tieneDisponible($item->cantidad)) {
                        throw new \DomainException("Stock insuficiente para el producto: " . $stock->producto);
                    }
                    
                    \App\Models\FacturaItem::create([
                        'factura_id' => $factura->id,
                        'stock_id' => $stock->id,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario,
                    ]);

                    $stock->decrementarStock($item->cantidad);
                } else {
                    // Si es servicio libre, lo metemos a la factura sin afectar stock
                    \App\Models\FacturaItem::create([
                        'factura_id' => $factura->id,
                        'stock_id' => null, 
                        'descripcion' => $item->descripcion,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario,
                    ]);
                }
            }

            // 3. Marcar cotización como aprobada
            $cotizacion->update(['estado' => 'aprobada']);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('inventario.facturas.show', $factura->id)
                ->with('success', 'Cotización aprobada y convertida en Venta. Registre el pago cuando el cliente abone.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error al convertir: ' . $e->getMessage());
        }
    }

public function rechazar(\Illuminate\Http\Request $request, Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return back()->with('error', 'Solo las cotizaciones pendientes pueden ser rechazadas.');
        }

        // El modal global usa 'password_confirm'; tecnico requiere contraseña de admin.
        // Aceptamos ambos nombres de campo para compatibilidad.
        $password = $request->input('admin_password') ?? $request->input('password_confirm');

        if (auth()->user()->isTecnico()) {
            $request->validate(['admin_password' => 'required_without:password_confirm']);
            if (!$password || !app(\App\Services\AnulacionService::class)->adminPasswordValida($password)) {
                return back()->with('error', 'Se requiere la contraseña de un administrador para rechazar.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required_without:admin_password']);
            if (!$password || !app(\App\Services\AnulacionService::class)->passwordValida($password)) {
                return back()->with('error', 'Contraseña incorrecta.');
            }
        }

        $cotizacion->update(['estado' => 'rechazada']);

        return back()->with('success', 'Cotización marcada como rechazada.');
    }

    public function reactivar(\Illuminate\Http\Request $request, Cotizacion $cotizacion)
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

        $cotizacion->update(['estado' => 'pendiente']);

        return back()->with('success', 'Cotización reactivada correctamente.');
    }
}
