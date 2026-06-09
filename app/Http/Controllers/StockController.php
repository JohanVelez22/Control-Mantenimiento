<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Stock;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('producto', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('proveedor', 'like', "%{$search}%");
        }

        $stocks = $query->orderBy('id', 'desc')->paginate(10);

        return view('stocks.index', compact('stocks'));
    }

    public function create()
    {
        return view('stocks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|unique:stocks,codigo',
            'producto' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:0',
            'proveedor' => 'nullable|string|max:255',
            'precio_compra' => 'required|numeric|min:0',
            'utilidad' => 'required|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
            'precio_tecnico' => 'nullable|numeric|min:0',
        ]);

        Stock::create($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto agregado al inventario exitosamente.');
    }

    public function edit(Stock $stock)
    {
        return view('stocks.edit', compact('stock'));
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|unique:stocks,codigo,' . $stock->id,
            'producto' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:0',
            'proveedor' => 'nullable|string|max:255',
            'precio_compra' => 'required|numeric|min:0',
            'utilidad' => 'required|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0',
            'precio_tecnico' => 'nullable|numeric|min:0',
        ]);

        // Si se editó la compra o utilidad y se dejaron en blanco la venta, se resetearán a 0 para que el boot los recalcule.
        // Pero si vienen en el request, se mantendrán.
        if (!isset($validated['precio_venta'])) $validated['precio_venta'] = 0;
        if (!isset($validated['precio_tecnico'])) $validated['precio_tecnico'] = 0;

        $stock->update($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();
        return redirect()->route('stocks.index')->with('success', 'Producto eliminado del inventario.');
    }
}
