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
            'remove_photo' => 'nullable|in:0,1,true,false',
        ]);

        if ($request->hasFile('photo')) {
            if ($stock->photo && Storage::disk('public')->exists($stock->photo)) {
                Storage::disk('public')->delete($stock->photo);
            }
            $validated['photo'] = $request->file('photo')->store('stocks', 'public');
        } elseif ($request->boolean('remove_photo') || $request->input('remove_photo') === '1') {
            if ($stock->photo && Storage::disk('public')->exists($stock->photo)) {
                Storage::disk('public')->delete($stock->photo);
            }
            $validated['photo'] = null;
        }

        unset($validated['remove_photo']);

        if (!isset($validated['precio_venta'])) $validated['precio_venta'] = 0;
        if (!isset($validated['precio_tecnico'])) $validated['precio_tecnico'] = 0;

        $stock->update($validated);

        return redirect()->route('stocks.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function reportes(Request $request)
    {
        $query = Stock::with('proveedor')->where('active', true);

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
        $bajas = $stock->bajas()->with('user')->get();

        return view('stocks.show', compact('stock', 'historial', 'proveedor', 'bajas'));
    }

    public function print(Stock $stock)
    {
        $proveedor = $stock->proveedor()->first();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('stocks.print', compact('stock', 'proveedor'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha_stock_' . ($stock->codigo ?? $stock->id) . '.pdf');
    }

    public function anular(\Illuminate\Http\Request $request, Stock $stock)
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
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('stocks.index')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        // Alterna el estado activo
        $stock->active = !$stock->active;
        $stock->save();

        $action = $stock->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El producto ha sido {$action} exitosamente.");
    }

    public function darDeBaja(\Illuminate\Http\Request $request, Stock $stock, \App\Services\StockService $stockService)
    {
        if (!$stock->exists && $request->route('stock')) {
            $stock = Stock::findOrFail($request->route('stock'));
        }

        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para autorizar la baja de inventario.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.')->withInput();
            }
        }

        $validated = $request->validate([
            'cantidad'    => 'required|integer|min:1|max:' . max(1, $stock->cantidad),
            'motivo'      => 'required|string|in:defectuoso_fabrica,dano_taller,obsoleto,perdida_merma,otro',
            'observacion' => 'nullable|string|max:500',
        ], [
            'cantidad.max' => "No puedes dar de baja más de {$stock->cantidad} unidades disponibles.",
            'motivo.required' => 'Debes seleccionar el motivo de la baja.',
        ]);

        try {
            $baja = $stockService->darDeBaja(
                $stock,
                (int) $validated['cantidad'],
                $validated['motivo'],
                $validated['observacion'] ?? null,
                auth()->id()
            );

            $perdidaFmt = number_format($baja->costo_total_perdida, 0, ',', '.');
            return redirect()->back()->with('success', "Se dieron de baja {$baja->cantidad} unidad(es) de '{$stock->producto}'. Pérdida registrada: \${$perdidaFmt}.");
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al procesar la baja de stock: ' . $e->getMessage())->withInput();
        }
    }

    public function revertirBaja(\Illuminate\Http\Request $request, \App\Models\BajaStock $bajaStock, \App\Services\StockService $stockService)
    {
        if (!$bajaStock->exists && $request->route('bajaStock')) {
            $bajaStock = \App\Models\BajaStock::findOrFail($request->route('bajaStock'));
        }

        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para revertir la baja.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.')->withInput();
            }
        }

        $stock = $bajaStock->stock;
        $cantidad = $bajaStock->cantidad;
        $producto = $stock ? $stock->producto : 'producto';

        try {
            $stockService->revertirBaja($bajaStock);
            return redirect()->back()->with('success', "Se revirtió la baja exitosamente: se reintegraron {$cantidad} unidad(es) al inventario de '{$producto}'.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al revertir la baja: ' . $e->getMessage());
        }
    }
}
