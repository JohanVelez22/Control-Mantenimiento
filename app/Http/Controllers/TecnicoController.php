<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TecnicoController extends Controller
{
    public function index()
    {
        $tecnicos = Tecnico::orderBy('id', 'desc')->paginate(10);
        return view('tecnicos.index', compact('tecnicos'));
    }

    public function show(Tecnico $tecnico)
    {
        return redirect()->route('tecnicos.edit', $tecnico);
    }

    public function create()
    {
        return view('tecnicos.create');
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'nombre' => 'required|string|regex:/^[\pL\s\.\-]+$/u|max:80',
            'identificacion' => 'required|string|max:30|unique:tecnicos',
            'especialidad' => 'required|string|max:80',
            'movil' => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('tecnicos', 'public');
        }

        Tecnico::create($validated);

        return redirect()->route('tecnicos.index')->with('success', 'Técnico registrado correctamente.');
    }

    public function edit(Tecnico $tecnico)
    {
        
        return view('tecnicos.edit', compact('tecnico'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {

        $validated = $request->validate([
            'nombre' => 'required|string|regex:/^[\pL\s\.\-]+$/u|max:80',
            'identificacion' => 'required|string|max:30|unique:tecnicos,identificacion,' . $tecnico->id,
            'especialidad' => 'required|string|max:80',
            'movil' => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($tecnico->photo && Storage::disk('public')->exists($tecnico->photo)) {
                Storage::disk('public')->delete($tecnico->photo);
            }
            $validated['photo'] = $request->file('photo')->store('tecnicos', 'public');
        }

        $tecnico->update($validated);

        return redirect()->route('tecnicos.index')->with('success', 'Técnico actualizado correctamente.');
    }

    public function anular(\Illuminate\Http\Request $request, Tecnico $tecnico)
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

        $tecnico->active = !$tecnico->active;
        $tecnico->save();

        $action = $tecnico->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El técnico ha sido {$action} exitosamente.");
    }
}