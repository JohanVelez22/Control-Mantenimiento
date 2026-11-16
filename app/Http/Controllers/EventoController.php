<?php

namespace App\Http\Controllers;

use App\Models\CategoriaStock;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden ver la auditoría de eventos.');
        }

        $query = Evento::with(['user'])->orderByDesc('id');

        // B-04: historial de un registro concreto (por ejemplo, desde la ficha de una orden).
        if ($request->filled('modelo_tipo') && $request->filled('modelo_id') && ctype_digit((string) $request->modelo_id)) {
            $query->where('modelo_tipo', (string) $request->modelo_tipo)->where('modelo_id', (int) $request->modelo_id);
        }

        // Filtros
        if ($request->filled('accion') && $request->accion !== 'todas') {
            if ($request->accion === 'backup') {
                $query->whereIn('accion', ['backup', 'backup_restaurado']);
            } else {
                $query->where('accion', $request->accion);
            }
        }

        if ($request->filled('user_id') && $request->user_id !== 'todos') {
            $query->where('user_id', $request->user_id);
        }

        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        // D-04: rango sobre la columna (usa el índice). Con un registro concreto no se
        // limita por fecha: se quiere su historial completo.
        if (! $request->filled('modelo_id')) {
            $query->where('created_at', '>=', $fechaDesde.' 00:00:00')
                ->where('created_at', '<', Carbon::parse($fechaHasta)->addDay()->toDateString().' 00:00:00');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('descripcion', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('modelo_tipo', 'LIKE', '%'.$request->search.'%');
            });
        }

        $eventos = $query->paginate(30)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $lookups = $this->getEntityLookups($eventos->items());

        return view('eventos.index', compact('eventos', 'users', 'lookups', 'fechaDesde', 'fechaHasta'));
    }

    public function show(Evento $evento)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden ver la auditoría de eventos.');
        }

        $evento->load(['user']);
        // Forzar parseo como array si fuera necesario
        $viejos = is_string($evento->valores_antiguos) ? json_decode($evento->valores_antiguos, true) : $evento->valores_antiguos;
        $nuevos = is_string($evento->valores_nuevos) ? json_decode($evento->valores_nuevos, true) : $evento->valores_nuevos;
        $lookups = $this->getEntityLookups([$evento]);

        return view('eventos.show', compact('evento', 'viejos', 'nuevos', 'lookups'));
    }

    /** Llaves foráneas que se muestran con su nombre, por diccionario. */
    private const LLAVES = [
        'users' => ['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'],
        'clientes' => ['cliente_id'],
        'proveedores' => ['proveedor_id'],
        'tecnicos' => ['tecnico_id'],
        'categorias' => ['categoria_id', 'categoria_stock_id'],
        'conceptos' => ['concepto_id', 'concepto_caja_id'],
        'stocks' => ['stock_id', 'producto_id', 'repuesto_id'],
        'equipos' => ['equipo_id'],
    ];

    /**
     * Diccionario de resolución de llaves foráneas a nombres descriptivos.
     *
     * B-14: solo se consultan los ids que aparecen en los eventos mostrados (antes se
     * cargaban 8 tablas completas en cada página).
     *
     * @param  iterable<Evento>  $eventos
     */
    protected function getEntityLookups(iterable $eventos): array
    {
        $ids = array_fill_keys(array_keys(self::LLAVES), []);
        foreach ($eventos as $evento) {
            foreach ([$evento->valores_antiguos, $evento->valores_nuevos] as $valores) {
                $valores = is_string($valores) ? json_decode($valores, true) : $valores;
                if (! is_array($valores)) {
                    continue;
                }
                foreach ($valores as $clave => $valor) {
                    if (! is_scalar($valor) || ! ctype_digit((string) $valor)) {
                        continue;
                    }
                    foreach (self::LLAVES as $diccionario => $claves) {
                        if (in_array(strtolower((string) $clave), $claves, true)) {
                            $ids[$diccionario][] = (int) $valor;
                        }
                    }
                }
            }
        }
        $ids = array_map(fn ($lista) => array_values(array_unique($lista)), $ids);

        $buscar = fn (array $lista, \Closure $consulta) => $lista ? $consulta($lista) : [];

        return [
            'users' => $buscar($ids['users'], fn ($l) => User::whereIn('id', $l)->pluck('name', 'id')->toArray()),
            'clientes' => $buscar($ids['clientes'], fn ($l) => Cliente::whereIn('id', $l)->get(['id', 'nombres', 'apellidos'])
                ->mapWithKeys(fn ($c) => [$c->id => trim(($c->nombres ?? '').' '.($c->apellidos ?? ''))])
                ->filter(fn ($v) => $v !== '')->toArray()),
            'proveedores' => $buscar($ids['proveedores'], fn ($l) => Proveedor::whereIn('id', $l)->pluck('nombre_razon_social', 'id')->toArray()),
            'tecnicos' => $buscar($ids['tecnicos'], fn ($l) => Tecnico::whereIn('id', $l)->pluck('nombre', 'id')->toArray()),
            'categorias' => $buscar($ids['categorias'], fn ($l) => CategoriaStock::whereIn('id', $l)->pluck('nombre', 'id')->toArray()),
            'conceptos' => $buscar($ids['conceptos'], fn ($l) => ConceptoCaja::whereIn('id', $l)->pluck('nombre', 'id')->toArray()),
            'stocks' => $buscar($ids['stocks'], fn ($l) => Stock::whereIn('id', $l)->pluck('producto', 'id')->toArray()),
            'equipos' => $buscar($ids['equipos'], fn ($l) => Equipo::whereIn('id', $l)->get(['id', 'nombre', 'marca', 'modelo'])
                ->mapWithKeys(fn ($e) => [$e->id => trim(($e->nombre ?? '').' '.($e->marca ?? '').' '.($e->modelo ?? ''))])
                ->filter(fn ($v) => $v !== '')->toArray()),
        ];
    }

    public function backup(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden generar copias de seguridad.');
        }

        try {
            Artisan::call('app:backup-db');

            return redirect()->route('eventos.index')->with('success', 'Copia de seguridad del sistema generada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al generar respaldo desde eventos: '.$e->getMessage());

            return redirect()->route('eventos.index')->with('error', 'No se pudo generar la copia de seguridad. Revise los registros del sistema.');
        }
    }
}
