<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Stock;

class StockController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('locate')) {
            $id = $request->locate;
            $position = Stock::where('id', '>=', $id)->count();
            $page = ceil($position / 10) ?: 1;
            return redirect()->route('stocks.index', ['page' => $page])->withFragment('stock-' . $id);
        }

        $query = Stock::with('proveedor')->activos();

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
            'categoria' => 'required|string|max:50',
            'subcategoria' => 'required|string|max:50',
            'cantidad' => 'required|integer|min:0',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0|decimal:0,2',
            'utilidad' => 'required|numeric|min:0|max:100',
            'precio_venta' => 'nullable|numeric|min:0|decimal:0,2',
            'precio_tecnico' => 'nullable|numeric|min:0|decimal:0,2',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('stocks', 'public');
        }

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
            'categoria' => 'required|string|max:50',
            'subcategoria' => 'required|string|max:50',
            'cantidad' => 'required|integer|min:0',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'precio_compra' => 'required|numeric|min:0|decimal:0,2',
            'utilidad' => 'required|numeric|min:0|max:100',
            'precio_venta' => 'nullable|numeric|min:0|decimal:0,2',
            'precio_tecnico' => 'nullable|numeric|min:0|decimal:0,2',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($stock->photo && Storage::disk('public')->exists($stock->photo)) {
                Storage::disk('public')->delete($stock->photo);
            }
            $validated['photo'] = $request->file('photo')->store('stocks', 'public');
        }

        if (!isset($validated['precio_venta'])) $validated['precio_venta'] = 0;
        if (!isset($validated['precio_tecnico'])) $validated['precio_tecnico'] = 0;

        $stock->update($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function reportes(Request $request)
    {
        $query = Stock::with('proveedor');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('producto', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhereHas('proveedor', function($q2) use ($search) {
                      $q2->where('nombre_razon_social', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('proveedor_id') && $request->proveedor_id !== 'todos') {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('categoria') && $request->categoria !== 'todos') {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('subcategoria') && $request->subcategoria !== 'todos') {
            $query->where('subcategoria', $request->subcategoria);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        if ($request->filled('min_costo')) {
            $col = in_array($request->price_type, ['precio_venta', 'precio_tecnico']) ? $request->price_type : 'precio_compra';
            $query->where($col, '>=', $request->min_costo);
        }
        
        if ($request->filled('max_costo')) {
            $col = in_array($request->price_type, ['precio_venta', 'precio_tecnico']) ? $request->price_type : 'precio_compra';
            $query->where($col, '<=', $request->max_costo);
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('active', $request->estado === 'activo' ? 1 : 0);
        }

        // Exportar PDF
        if ($request->has('export') && $request->export === 'pdf') {
            $stocks = $query->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('stocks.pdf_reportes', compact('stocks'))
                                             ->setPaper('a4', 'portrait');
            return $pdf->download('Reporte_Inventario_' . date('Ymd_Hi') . '.pdf');
        }

        // Exportar Excel
        if ($request->has('export') && $request->export === 'excel') {
            $stocks = $query->get();
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\StocksExport($stocks),
                'Reporte_Inventario_' . date('Ymd_Hi') . '.xlsx'
            );
        }

        $stocks = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $categorias = \App\Models\CategoriaStock::where('tipo', 'categoria')->pluck('nombre')->merge(Stock::select('categoria')->whereNotNull('categoria')->where('categoria', '!=', '')->distinct()->pluck('categoria'))->unique();
        $subcategorias = \App\Models\CategoriaStock::where('tipo', 'subcategoria')->pluck('nombre')->merge(Stock::select('subcategoria')->whereNotNull('subcategoria')->where('subcategoria', '!=', '')->distinct()->pluck('subcategoria'))->unique();
        $proveedores = \App\Models\Proveedor::where('active', 1)->orderBy('nombre_razon_social')->get();

        return view('stocks.reportes', compact('stocks', 'categorias', 'subcategorias', 'proveedores'));
    }

    public function show(Stock $stock)
    {
        $historial = \App\Models\FacturaItem::with('factura.facturable', 'factura.user')
            ->where('stock_id', $stock->id)
            ->get()
            ->sortByDesc(function($item) {
                return $item->factura->fecha ?? $item->created_at;
            });
            
        // Obtiene explícitamente la relación proveedor para evitar que la columna 'proveedor' la enmascare
        $proveedor = $stock->proveedor()->first();
            
        return view('stocks.show', compact('stock', 'historial', 'proveedor'));
    }

    public function print(Stock $stock)
    {
        $proveedor = $stock->proveedor()->first();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('stocks.print', compact('stock', 'proveedor'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha_stock_' . ($stock->codigo ?? $stock->id) . '.pdf');
    }

    public function anular(Stock $stock)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        // Alterna el estado activo
        $stock->active = !$stock->active;
        $stock->save();

        $action = $stock->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El producto ha sido {$action} exitosamente.");
    }
}
