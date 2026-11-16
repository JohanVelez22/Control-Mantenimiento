<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Services\AnulacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    public function index(Request $request)
    {
        // Cargamos 'cliente', 'proveedor', 'user' y 'bajaUser' para mostrar quién registró el equipo y la baja
        $query = Equipo::with(['cliente', 'proveedor', 'user', 'bajaUser']);

        // F-04: la búsqueda recorre todos los registros, no solo la página visible.
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                    ->orWhere('marca', 'like', "%{$s}%")
                    ->orWhere('modelo', 'like', "%{$s}%")
                    ->orWhere('serie', 'like', "%{$s}%")
                    ->orWhereHas('cliente', function ($q3) use ($s) {
                        $q3->where('nombres', 'like', "%{$s}%")
                            ->orWhere('apellidos', 'like', "%{$s}%")
                            ->orWhere('identificacion', 'like', "%{$s}%");
                    })
                    ->orWhereHas('proveedor', fn ($q3) => $q3->where('nombre_razon_social', 'like', "%{$s}%"));
            });
        }

        $equipos = $query->orderBy('id', 'desc')->paginate(10);

        return view('equipos.index', compact('equipos'));
    }

    public function show(Equipo $equipo)
    {
        return redirect()->route('equipos.edit', $equipo);
    }

    public function create()
    {
        $clientes = Cliente::activos()->orderBy('nombres')->orderBy('apellidos')->get();
        $proveedores = Proveedor::activos()->orderBy('nombre_razon_social')->get();

        return view('equipos.create', compact('clientes', 'proveedores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:80',
            'marca' => 'required|string|max:80',
            'modelo' => 'required|string|max:80',
            'serie' => 'required|string|max:80|unique:equipos',
            'propietario_global' => ['nullable', 'string', 'regex:/^(Cliente|Proveedor):\d+$/'],
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'proveedor_id' => 'nullable|integer|exists:proveedores,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        $clienteId = null;
        $proveedorId = null;

        if ($request->filled('propietario_global')) {
            $parts = explode(':', $request->propietario_global);
            if (count($parts) === 2) {
                if ($parts[0] === 'Proveedor') {
                    $proveedorId = (int) $parts[1];
                } else {
                    $clienteId = (int) $parts[1];
                }
            }
        } elseif ($request->filled('cliente_id')) {
            $clienteId = (int) $request->cliente_id;
        } elseif ($request->filled('proveedor_id')) {
            $proveedorId = (int) $request->proveedor_id;
        }

        if (! $clienteId && ! $proveedorId) {
            return back()->withErrors(['propietario_global' => 'Debe seleccionar un propietario (cliente o proveedor).'])->withInput();
        }

        // El propietario debe existir (un id inexistente producía un error 500 por la FK)
        if (($clienteId && ! Cliente::whereKey($clienteId)->exists()) || ($proveedorId && ! Proveedor::whereKey($proveedorId)->exists())) {
            return back()->withErrors(['propietario_global' => 'El propietario seleccionado no existe.'])->withInput();
        }

        $validated['cliente_id'] = $clienteId;
        $validated['proveedor_id'] = $proveedorId;
        unset($validated['propietario_global']);
        $validated['user_id'] = Auth::id();

        Equipo::create($validated);

        return redirect()->route('equipos.index')->with('success', 'Equipo registrado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        if ($equipo->estado === 'dado_de_baja' || ! $equipo->active) {
            $motivo = $equipo->estado === 'dado_de_baja' ? 'ha sido dado de baja' : 'se encuentra anulado (inactivo)';

            return redirect()->route('equipos.index')->with('error', "Por seguridad, no se puede editar un equipo que {$motivo}.");
        }

        $clientes = Cliente::where(function ($q) use ($equipo) {
            $q->activos();
            if ($equipo->cliente_id) {
                $q->orWhere('id', $equipo->cliente_id);
            }
        })->orderBy('nombres')->orderBy('apellidos')->get();

        $proveedores = Proveedor::where(function ($q) use ($equipo) {
            $q->activos();
            if ($equipo->proveedor_id) {
                $q->orWhere('id', $equipo->proveedor_id);
            }
        })->orderBy('nombre_razon_social')->get();

        return view('equipos.edit', compact('equipo', 'clientes', 'proveedores'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        if ($equipo->estado === 'dado_de_baja' || ! $equipo->active) {
            $motivo = $equipo->estado === 'dado_de_baja' ? 'ha sido dado de baja' : 'se encuentra anulado (inactivo)';

            return redirect()->route('equipos.index')->with('error', "Por seguridad, no se puede editar un equipo que {$motivo}.");
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:80',
            'marca' => 'required|string|max:80',
            'modelo' => 'required|string|max:80',
            'serie' => 'required|string|max:80|unique:equipos,serie,'.$equipo->id,
            'propietario_global' => ['nullable', 'string', 'regex:/^(Cliente|Proveedor):\d+$/'],
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'proveedor_id' => 'nullable|integer|exists:proveedores,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        $clienteId = null;
        $proveedorId = null;

        if ($request->filled('propietario_global')) {
            $parts = explode(':', $request->propietario_global);
            if (count($parts) === 2) {
                if ($parts[0] === 'Proveedor') {
                    $proveedorId = (int) $parts[1];
                } else {
                    $clienteId = (int) $parts[1];
                }
            }
        } elseif ($request->filled('cliente_id')) {
            $clienteId = (int) $request->cliente_id;
        } elseif ($request->filled('proveedor_id')) {
            $proveedorId = (int) $request->proveedor_id;
        }

        if (! $clienteId && ! $proveedorId) {
            return back()->withErrors(['propietario_global' => 'Debe seleccionar un propietario (cliente o proveedor).'])->withInput();
        }

        // El propietario debe existir (un id inexistente producía un error 500 por la FK)
        if (($clienteId && ! Cliente::whereKey($clienteId)->exists()) || ($proveedorId && ! Proveedor::whereKey($proveedorId)->exists())) {
            return back()->withErrors(['propietario_global' => 'El propietario seleccionado no existe.'])->withInput();
        }

        $validated['cliente_id'] = $clienteId;
        $validated['proveedor_id'] = $proveedorId;
        unset($validated['propietario_global']);

        // B-06: las órdenes muestran al dueño actual del equipo. Si ya hay órdenes cobradas
        // o entregadas, cambiar el dueño reescribiría sus facturas y la consulta del cliente.
        $cambiaDueno = (int) $equipo->cliente_id !== (int) $clienteId || (int) $equipo->proveedor_id !== (int) $proveedorId;
        if ($cambiaDueno) {
            $conHistorial = collect([Mantenimiento::class, Electronica::class])->contains(
                fn ($modelo) => $modelo::where('equipo_id', $equipo->id)->where('anulado', false)
                    ->where(fn ($q) => $q->where('estado', 'terminado')->orWhereHas('abonos', fn ($a) => $a->where('anulado', false)))
                    ->exists()
            );
            if ($conHistorial) {
                return back()->withErrors(['propietario_global' => 'Este equipo tiene órdenes cobradas o entregadas a nombre del dueño actual: no se puede cambiar el propietario. Si cambió de dueño, registre el equipo de nuevo para el nuevo propietario.'])->withInput();
            }
        }

        $equipo->update($validated);
        if ($cambiaDueno) {
            Evento::registrar('propietario_cambiado', $equipo, null, ['cliente_id' => $clienteId, 'proveedor_id' => $proveedorId],
                'Cambió el propietario del equipo '.$equipo->nombre.' (serie '.$equipo->serie.')');
        }

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function anular(Request $request, Equipo $equipo)
    {
        if ($equipo->estado === 'dado_de_baja') {
            return redirect()->back()->with('error', 'No se puede anular un equipo que ya ha sido dado de baja.');
        }

        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        $servicio = app(AnulacionService::class);
        if ($error = $servicio->verificarIntencion($request, (bool) $equipo->active)) {
            return redirect()->back()->with('error', $error);
        }
        if ($equipo->active && ($bloqueo = $servicio->bloqueoPorOrdenesPendientes('equipo_id', [$equipo->id], 'el equipo'))) {
            return redirect()->back()->with('error', $bloqueo);
        }

        $equipo->active = ! $equipo->active;
        $equipo->save();

        $action = $equipo->active ? 'reactivado' : 'desactivado (anulado)';

        return redirect()->back()->with('success', "El equipo ha sido {$action} exitosamente.");
    }

    public function darDeBaja(Request $request, Equipo $equipo)
    {
        if (! $equipo->exists && $request->route('equipo')) {
            $equipo = Equipo::findOrFail($request->route('equipo'));
        }

        if ($equipo->estado === 'dado_de_baja') {
            return redirect()->back()->with('error', 'Este equipo ya ha sido dado de baja.');
        }

        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        if ($bloqueo = app(AnulacionService::class)->bloqueoPorOrdenesPendientes('equipo_id', [$equipo->id], 'el equipo')) {
            return redirect()->back()->with('error', $bloqueo);
        }

        $validated = $request->validate([
            'motivo_baja' => 'required|string|in:irreparable,desguace_repuestos,chatarrizacion,siniestro,abandonado,otro',
            'observacion_baja' => 'nullable|string|max:500',
        ], [
            'motivo_baja.required' => 'Debes indicar el motivo de la baja del equipo.',
        ]);

        $equipo->update([
            'estado' => 'dado_de_baja',
            'active' => false,
            'motivo_baja' => $validated['motivo_baja'],
            'observacion_baja' => $validated['observacion_baja'] ?? null,
            'fecha_baja' => now(),
            'baja_user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "El equipo '{$equipo->nombre}' ha sido dado de baja exitosamente. Motivo: {$equipo->motivo_baja_label}.");
    }

    public function reactivar(Request $request, Equipo $equipo)
    {
        if (! $equipo->exists && $request->route('equipo')) {
            $equipo = Equipo::findOrFail($request->route('equipo'));
        }

        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        $equipo->update([
            'estado' => 'operativo',
            'active' => true,
            'motivo_baja' => null,
            'observacion_baja' => null,
            'fecha_baja' => null,
            'baja_user_id' => null,
        ]);

        return redirect()->back()->with('success', "El equipo '{$equipo->nombre}' ha sido reactivado y vuelve a estar disponible para mantenimiento.");
    }
}
