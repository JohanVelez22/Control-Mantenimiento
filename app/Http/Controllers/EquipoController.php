<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Cliente;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    public function index()
    {
        // Cargamos 'cliente', 'proveedor' y 'user' para mostrar quién registró el equipo
        $equipos = Equipo::with(['cliente', 'proveedor', 'user'])->orderBy('id', 'desc')->paginate(10);
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
            'propietario_global' => 'nullable|string',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'proveedor_id' => 'nullable|integer|exists:proveedores,id',
            'observacion' => 'nullable|string|max:500'
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

        if (!$clienteId && !$proveedorId) {
            return back()->withErrors(['propietario_global' => 'Debe seleccionar un propietario (cliente o proveedor).'])->withInput();
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
        $clientes = Cliente::where(function($q) use ($equipo) {
            $q->activos();
            if ($equipo->cliente_id) {
                $q->orWhere('id', $equipo->cliente_id);
            }
        })->orderBy('nombres')->orderBy('apellidos')->get();

        $proveedores = Proveedor::where(function($q) use ($equipo) {
            $q->activos();
            if ($equipo->proveedor_id) {
                $q->orWhere('id', $equipo->proveedor_id);
            }
        })->orderBy('nombre_razon_social')->get();

        return view('equipos.edit', compact('equipo', 'clientes', 'proveedores'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:80',
            'marca' => 'required|string|max:80',
            'modelo' => 'required|string|max:80',
            'serie' => 'required|string|max:80|unique:equipos,serie,' . $equipo->id,
            'propietario_global' => 'nullable|string',
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

        if (!$clienteId && !$proveedorId) {
            return back()->withErrors(['propietario_global' => 'Debe seleccionar un propietario (cliente o proveedor).'])->withInput();
        }

        $validated['cliente_id'] = $clienteId;
        $validated['proveedor_id'] = $proveedorId;
        unset($validated['propietario_global']);

        $equipo->update($validated);

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function anular(\Illuminate\Http\Request $request, Equipo $equipo)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.');
            }
        }

        $equipo->active = !$equipo->active;
        $equipo->save();

        $action = $equipo->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El equipo ha sido {$action} exitosamente.");
    }

    public function darDeBaja(\Illuminate\Http\Request $request, Equipo $equipo)
    {
        if (!$equipo->exists && $request->route('equipo')) {
            $equipo = Equipo::findOrFail($request->route('equipo'));
        }

        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para dar de baja un equipo.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.')->withInput();
            }
        }

        $validated = $request->validate([
            'motivo_baja'      => 'required|string|in:irreparable,desguace_repuestos,chatarrizacion,siniestro,abandonado,otro',
            'observacion_baja' => 'nullable|string|max:500',
        ], [
            'motivo_baja.required' => 'Debes indicar el motivo de la baja del equipo.',
        ]);

        $equipo->update([
            'estado'           => 'dado_de_baja',
            'active'           => false,
            'motivo_baja'      => $validated['motivo_baja'],
            'observacion_baja' => $validated['observacion_baja'] ?? null,
            'fecha_baja'       => now(),
            'baja_user_id'     => auth()->id(),
        ]);

        return redirect()->back()->with('success', "El equipo '{$equipo->nombre}' ha sido dado de baja exitosamente. Motivo: {$equipo->motivo_baja_label}.");
    }

    public function reactivar(\Illuminate\Http\Request $request, Equipo $equipo)
    {
        if (!$equipo->exists && $request->route('equipo')) {
            $equipo = Equipo::findOrFail($request->route('equipo'));
        }

        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para reactivar el equipo.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.')->withInput();
            }
        }

        $equipo->update([
            'estado'           => 'operativo',
            'active'           => true,
            'motivo_baja'      => null,
            'observacion_baja' => null,
            'fecha_baja'       => null,
            'baja_user_id'     => null,
        ]);

        return redirect()->back()->with('success', "El equipo '{$equipo->nombre}' ha sido reactivado y vuelve a estar disponible para mantenimiento.");
    }

    public function destroy(Equipo $equipo)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para eliminar.');
        }

        $equipo->delete();

        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado correctamente.');
    }
}
