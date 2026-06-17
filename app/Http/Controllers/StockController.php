<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Stock;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with('proveedor');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('producto', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhereHas('proveedor', function($q2) use ($search) {
                      $q2->where('nombre_razon_social', 'like', "%{$search}%");
                  });
        }

        $stocks = $query->orderBy('id', 'desc')->paginate(10);

        return view('stocks.index', compact('stocks'));
    }

    public function create()
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para crear.');
        }
        $proveedores = \App\Models\Proveedor::all();
        return view('stocks.create', compact('proveedores'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para crear.');
        }

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:stocks,codigo',
            'producto' => 'required|string|max:80',
            'cantidad' => 'required|integer|min:0',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0|decimal:0,2',
            'utilidad' => 'required|numeric|min:0|max:100',
            'precio_venta' => 'nullable|numeric|min:0|decimal:0,2',
            'precio_tecnico' => 'nullable|numeric|min:0|decimal:0,2',
        ]);

        Stock::create($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto agregado al inventario exitosamente.');
    }

    public function edit(Stock $stock)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para editar.');
        }
        $proveedores = \App\Models\Proveedor::all();
        return view('stocks.edit', compact('stock', 'proveedores'));
    }

    public function update(Request $request, Stock $stock)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para actualizar.');
        }

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:stocks,codigo,' . $stock->id,
            'producto' => 'required|string|max:80',
            'cantidad' => 'required|integer|min:0',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0|decimal:0,2',
            'utilidad' => 'required|numeric|min:0|max:100',
            'precio_venta' => 'nullable|numeric|min:0|decimal:0,2',
            'precio_tecnico' => 'nullable|numeric|min:0|decimal:0,2',
        ]);

        // Si se editó la compra o utilidad y se dejaron en blanco la venta, se resetearán a 0 para que el boot los recalcule.
        if (!isset($validated['precio_venta'])) $validated['precio_venta'] = 0;
        if (!isset($validated['precio_tecnico'])) $validated['precio_tecnico'] = 0;

        $stock->update($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Stock $stock)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('stocks.index')->with('error', 'Solo el administrador puede eliminar.');
        }
        
        try {
            $stock->delete();
            return redirect()->route('stocks.index')->with('success', 'Producto eliminado del inventario.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return redirect()->route('stocks.index')->with('error', 'No se puede eliminar este producto porque está asociado a facturas o reparaciones existentes.');
            }
            return redirect()->route('stocks.index')->with('error', 'Error al eliminar el producto.');
        }
    }
}
