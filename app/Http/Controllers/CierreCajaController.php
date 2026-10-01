<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\MovimientoCaja;
use App\Services\AnulacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CierreCajaController extends Controller
{
    public function index()
    {
        $cierres = CierreCaja::with('user')->orderBy('fecha', 'desc')->paginate(10);

        // Pre-calcular datos del día actual (aún no cerrado)
        $hoy = now()->toDateString();
        $yaExiste = CierreCaja::whereDate('fecha', $hoy)->exists();

        $preview = null;
        if (! $yaExiste) {
            $preview = $this->calcularDia($hoy);
        }

        return view('cierre.index', compact('cierres', 'preview', 'hoy', 'yaExiste'));
    }

    /** Realiza el cierre del día indicado con opción de conciliación física */
    public function store(Request $request)
    {
        $fecha = Carbon::parse($request->fecha)->toDateString();
        $request->merge(['fecha' => $fecha]);

        // Sanitizar efectivo_real_contado si viene con formato monetario
        if ($request->filled('efectivo_real_contado')) {
            $rawContado = $request->efectivo_real_contado;
            if (is_string($rawContado)) {
                $rawContado = str_replace(['$', ' '], '', $rawContado);
                if (str_contains($rawContado, '.') && str_contains($rawContado, ',')) {
                    $rawContado = str_replace('.', '', $rawContado);
                    $rawContado = str_replace(',', '.', $rawContado);
                } elseif (str_contains($rawContado, '.') && !str_contains($rawContado, ',')) {
                    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $rawContado)) {
                        $rawContado = str_replace('.', '', $rawContado);
                    }
                } elseif (str_contains($rawContado, ',')) {
                    $rawContado = str_replace(',', '.', $rawContado);
                }
            }
            $request->merge(['efectivo_real_contado' => $rawContado]);
        }

        $request->validate([
            'fecha' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($fecha) {
                    if (CierreCaja::whereDate('fecha', $fecha)->exists()) {
                        $fail('Ya existe un cierre registrado para esta fecha.');
                    }
                },
            ],
            'efectivo_real_contado' => 'nullable|numeric|min:0',
            'motivo_diferencia' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($request, $fecha) {
            // Protección contra race condition con bloqueo pesimista
            $yaExiste = CierreCaja::whereDate('fecha', $fecha)->lockForUpdate()->exists();
            if ($yaExiste) {
                return back()->with('error', 'Ya existe un cierre registrado para la fecha seleccionada.')->withInput();
            }

            $datos = $this->calcularDia($fecha);

            // El conteo físico es opcional. Si el cajero no registra un valor,
            // `efectivo_real_contado` queda NULL y el modelo lo reporta como
            // 'sin_conciliar'. Nunca se infiere el contado desde el valor
            // teórico del sistema: eso afirmaría un cuadre no verificado.
            $efectivoSistema = (float) $datos['efectivo'];
            $conteoRegistrado = $request->filled('efectivo_real_contado')
                && is_numeric($request->efectivo_real_contado);

            if ($conteoRegistrado) {
                $efectivoReal = round((float) $request->efectivo_real_contado, 2);
                $diferencia = round($efectivoReal - $efectivoSistema, 2);
            } else {
                $efectivoReal = null;
                $diferencia = 0;
            }

            CierreCaja::create([
                'fecha' => $fecha,
                'total_ingresos' => $datos['total_ingresos'],
                'total_egresos' => $datos['total_egresos'],
                'efectivo' => $datos['efectivo'],
                'efectivo_real_contado' => $efectivoReal,
                'diferencia' => $diferencia,
                'consignacion' => $datos['consignacion'],
                'saldo_final' => $datos['saldo_final'],
                'num_movimientos' => $datos['num_movimientos'],
                'bloqueado' => true,
                'observaciones' => $request->observaciones,
                'motivo_diferencia' => $request->motivo_diferencia,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('cierre.index')
                ->with('success', 'Cierre del '.Carbon::parse($fecha)->format('d/m/Y').' guardado y bloqueado.');
        });
    }

    /** Eliminar cierre — requiere autorización sensible (admin directo, técnico con clave admin) */
    public function destroy(Request $request, CierreCaja $cierre)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        $cierre->delete();

        return redirect()->route('cierre.index')->with('success', 'Cierre eliminado y día desbloqueado.');
    }

    /** Ver detalle del cierre de caja */
    public function show(CierreCaja $cierre)
    {
        $cierre->load('user');
        $fecha = $cierre->fecha->toDateString();

        $movimientos = MovimientoCaja::with(['user', 'concepto'])
            ->whereDate('fecha', $fecha)
            ->orderBy('id', 'desc')
            ->get();

        return view('cierre.show', compact('cierre', 'movimientos'));
    }

    /** Formulario para editar observaciones del cierre */
    public function edit(CierreCaja $cierre)
    {
        return view('cierre.edit', compact('cierre'));
    }

    /** Actualizar observaciones y motivo de diferencia del cierre */
    public function update(Request $request, CierreCaja $cierre)
    {
        $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'motivo_diferencia' => 'nullable|string|max:1000',
        ]);

        $cierre->update([
            'observaciones' => $request->observaciones,
            'motivo_diferencia' => $request->motivo_diferencia,
        ]);

        return redirect()->route('cierre.show', $cierre->id)
            ->with('success', 'Observaciones del cierre actualizadas correctamente.');
    }

    // ──────────────────────────────────────────────────────────
    /** Helper: calcula totales de movimientos de un día dado excluyendo los anulados */
    private function calcularDia(string $fecha): array
    {
        $movs = MovimientoCaja::whereDate('fecha', $fecha)->where('estado', 'activo')->where('anulado', false)->get();

        $ingresos = (float) $movs->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos = (float) $movs->where('tipo_movimiento', 'egreso')->sum('monto');

        $efectivoIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoEgresos = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoNeto = $efectivoIngresos - $efectivoEgresos;

        $consignacionIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionEgresos = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionNeto = $consignacionIngresos - $consignacionEgresos;

        return [
            'total_ingresos' => $ingresos,
            'total_egresos' => $egresos,
            'efectivo' => $efectivoNeto,
            'consignacion' => $consignacionNeto,
            'saldo_final' => $ingresos - $egresos,
            'num_movimientos' => $movs->count(),
        ];
    }
}
