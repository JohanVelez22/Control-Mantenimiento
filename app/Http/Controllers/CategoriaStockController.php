<?php

namespace App\Http\Controllers;

use App\Models\CategoriaStock;
use App\Models\Stock;
use App\Services\AnulacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'nombre' => ['required', 'string', 'max:50', Rule::unique('categoria_stocks', 'nombre')->where('tipo', $request->input('tipo'))],
            'tipo' => 'required|in:categoria,subcategoria',
        ], ['nombre.unique' => 'Ya existe una '.$request->input('tipo', 'categoría').' con ese nombre.']);

        CategoriaStock::create($validated);

        return redirect()->route('stocks.categorias.index')->with('success', ucfirst($validated['tipo']).' creada exitosamente.');
    }

    public function edit(CategoriaStock $categoria)
    {
        return view('stocks.categorias.edit', compact('categoria'));
    }

    public function update(Request $request, CategoriaStock $categoria)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', Rule::unique('categoria_stocks', 'nombre')->where('tipo', $request->input('tipo'))->ignore($categoria->id)],
            'tipo' => 'required|in:categoria,subcategoria',
        ], ['nombre.unique' => 'Ya existe una '.$request->input('tipo', 'categoría').' con ese nombre.']);

        // Los productos guardan el nombre como texto: propagar el cambio para no dejarlos huérfanos.
        DB::transaction(function () use ($categoria, $validated) {
            $nombreAnterior = $categoria->nombre;
            $columnaAnterior = $categoria->tipo;
            $categoria->update($validated);
            if ($nombreAnterior !== $validated['nombre'] && $columnaAnterior === $validated['tipo']) {
                Stock::where($columnaAnterior, $nombreAnterior)->update([$columnaAnterior => $validated['nombre']]);
            }
        });

        return redirect()->route('stocks.categorias.index')->with('success', 'Actualizado exitosamente.');
    }

    public function destroy(Request $request, CategoriaStock $categoria)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        if (Stock::where($categoria->tipo, $categoria->nombre)->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar: hay productos que usan esta '.$categoria->tipo.'. Reasígnelos primero.');
        }

        $categoria->delete();

        return redirect()->route('stocks.categorias.index')->with('success', 'Eliminado exitosamente.');
    }
}
