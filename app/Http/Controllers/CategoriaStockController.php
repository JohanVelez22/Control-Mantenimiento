<?php

namespace App\Http\Controllers;

use App\Models\CategoriaStock;
use Illuminate\Http\Request;

class CategoriaStockController extends Controller
{
    public function index()
    {
        $categorias = CategoriaStock::orderBy('tipo')->orderBy('nombre')->get();
        return view('stocks.categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'tipo' => 'required|in:categoria,subcategoria',
        ]);

        CategoriaStock::create($validated);

        return redirect()->route('stocks.categorias.index')->with('success', ucfirst($validated['tipo']) . ' creada exitosamente.');
    }

    public function update(Request $request, CategoriaStock $categoria)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'tipo' => 'required|in:categoria,subcategoria',
        ]);

        $categoria->update($validated);

        return redirect()->route('stocks.categorias.index')->with('success', 'Actualizado exitosamente.');
    }

    public function destroy(Request $request, CategoriaStock $categoria)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos.');
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->password_confirm, auth()->user()->password) && !\Illuminate\Support\Facades\Hash::check($request->password_confirm, \App\Models\User::where('role', 'admin')->first()->password ?? '')) {
            return redirect()->back()->with('error', 'Contraseña incorrecta.');
        }

        $categoria->delete();

        return redirect()->route('stocks.categorias.index')->with('success', 'Eliminado exitosamente.');
    }
}
