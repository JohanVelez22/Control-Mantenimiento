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
        $clientes = \App\Models\Cliente::activos()->orderBy('nombres')->get();
        $stocks = \App\Models\Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
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

            // Generar siguiente código atómicamente usando OrdenService (previene condiciones de carrera)
            $codigo = app(\App\Services\OrdenService::class)->siguiente('COT-', \App\Models\Cotizacion::class, 'codigo');

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

    public function edit(\App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
            return redirect()->route('cotizaciones.show', $cotizacion)->with('error', 'Solo se pueden editar cotizaciones pendientes.');
        }

        $clientes = \App\Models\Cliente::where(function($q) use ($cotizacion) {
            $q->activos()->orWhere('id', $cotizacion->cliente_id);
        })->orderBy('nombres')->get();
        $stocks = \App\Models\Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        $cotizacion->load('items');
        
        return view('cotizaciones.edit', [
            'cotizacion' => $cotizacion,
            'clientes' => $clientes,
            'stocks' => $stocks,
        ]);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Cotizacion $cotizacion)
    {
        if ($cotizacion->estado !== 'pendiente') {
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

            $cotizacion->update([
                'cliente_id' => $request->cliente_id,
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
        $cotizacion->load('cliente', 'items.stock', 'user');
        return view('cotizaciones.show', ['cotizacion' => $cotizacion]);
    }

public function pdf(\App\Models\Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente', 'items.stock', 'user');
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
}
