<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Evento;
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
        $yaExiste = CierreCaja::where('fecha', $hoy)->exists();

        $preview = null;
        if (! $yaExiste) {
            $preview = $this->calcularDia($hoy);
        }

        return view('cierre.index', compact('cierres', 'preview', 'hoy', 'yaExiste'));
    }

    /** Realiza el cierre del día indicado con opción de conciliación física */
    public function store(Request $request)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        $request->validate(['fecha' => 'required|date']);
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
                } elseif (str_contains($rawContado, '.') && ! str_contains($rawContado, ',')) {
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
                    if (CierreCaja::where('fecha', $fecha)->exists()) {
                        $fail('Ya existe un cierre registrado para esta fecha.');
                    } elseif ($fecha > now()->toDateString()) {
                        $fail('No se puede cerrar un día futuro.');
                    } elseif (CierreCaja::where('fecha', '>', $fecha)->exists()) {
                        // M-08: los cierres son cronológicos; uno intermedio cambiaría el saldo inicial de los posteriores
                        $fail('Ya existe un cierre posterior a esta fecha; los cierres deben hacerse en orden cronológico.');
                    }
                },
            ],
            'efectivo_real_contado' => 'nullable|numeric|min:0',
            'motivo_diferencia' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $fecha) {
                // Protección contra race condition con bloqueo pesimista en índice único directo (F-24)
                if (CierreCaja::where('fecha', $fecha)->lockForUpdate()->exists()) {
                    throw new \DomainException('Ya existe un cierre registrado para la fecha seleccionada.');
                }

                // D-08: primero se reserva el día insertando el cierre y después se leen los
                // movimientos con lectura bloqueante. Un movimiento que se esté guardando en ese
                // mismo instante, o queda incluido en el arqueo (el cierre espera a que termine),
                // o es rechazado por el guardián (que espera a este cierre y lo ve). Antes podía
                // quedar fuera del arqueo de un día ya bloqueado.
                $cierre = CierreCaja::withoutEvents(fn () => CierreCaja::create([
                    'fecha' => $fecha, 'total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0,
                    'consignacion' => 0, 'saldo_final' => 0, 'num_movimientos' => 0,
                    'bloqueado' => true, 'user_id' => auth()->id(),
                ]));

                $datos = $this->calcularDia($fecha, bloquear: true);

                // El conteo físico es opcional. Si el cajero no registra un valor,
                // `efectivo_real_contado` queda NULL y el modelo lo reporta como
                // 'sin_conciliar'. Nunca se infiere el contado desde el valor
                // teórico del sistema: eso afirmaría un cuadre no verificado.
                $efectivoEsperado = (float) ($datos['efectivo_esperado'] ?? $datos['efectivo']);
                $conteoRegistrado = $request->filled('efectivo_real_contado')
                    && is_numeric($request->efectivo_real_contado);

                if ($conteoRegistrado) {
                    $efectivoReal = round((float) $request->efectivo_real_contado, 2);
                    $diferencia = round($efectivoReal - $efectivoEsperado, 2);
                } else {
                    $efectivoReal = null;
                    $diferencia = 0;
                }

                if (abs($diferencia) >= 0.01 && empty(trim((string) $request->motivo_diferencia))) {
                    $tipoDescuadre = $diferencia > 0 ? 'sobrante' : 'faltante';
                    // La excepción deshace también el cierre provisional.
                    throw new \DomainException("Existe un descuadre en caja ({$tipoDescuadre} de $".number_format(abs($diferencia), 0, ',', '.').'). Debes ingresar el motivo o justificación de la diferencia para poder cerrar y bloquear el día.');
                }

                $cierre->fill([
                    'total_ingresos' => $datos['total_ingresos'],
                    'total_egresos' => $datos['total_egresos'],
                    'efectivo' => $datos['efectivo'],
                    'efectivo_real_contado' => $efectivoReal,
                    'diferencia' => $diferencia,
                    'consignacion' => $datos['consignacion'],
                    'saldo_final' => $datos['saldo_final'],
                    'num_movimientos' => $datos['num_movimientos'],
                    'observaciones' => $request->observaciones,
                    'motivo_diferencia' => $request->motivo_diferencia,
                ])->saveQuietly();

                Evento::registrar('creado', $cierre, null, $cierre->getAttributes(), 'Creó un nuevo registro en CierreCaja');

                return redirect()->route('cierre.index')
                    ->with('success', 'Cierre del '.Carbon::parse($fecha)->format('d/m/Y').' guardado y bloqueado.');
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /** Eliminar cierre — requiere autorización sensible (admin directo, técnico con clave admin) */
    public function destroy(Request $request, CierreCaja $cierre)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        // Solo se reabre el último día cerrado: borrar un cierre intermedio cambiaría el saldo
        // inicial de los días posteriores, cuyos arqueos ya están registrados.
        $posterior = CierreCaja::where('fecha', '>', $cierre->fecha)->orderBy('fecha')->first();
        if ($posterior) {
            return back()->with('error', 'No se puede eliminar este cierre: existe un cierre posterior ('.$posterior->fecha->format('d/m/Y').'). Elimine primero los cierres más recientes.');
        }

        $cierre->delete();

        return redirect()->route('cierre.index')->with('success', 'Cierre eliminado y día desbloqueado.');
    }

    /** Ver detalle del cierre de caja */
    public function show(CierreCaja $cierre)
    {
        $cierre->load('user');
        $fecha = $cierre->fecha->toDateString();

        $movQuery = MovimientoCaja::with(['user', 'concepto']);
        if (DB::connection()->getDriverName() === 'mysql') {
            $movQuery->where('fecha', $fecha);
        } else {
            $movQuery->where(function ($q) use ($fecha) {
                $q->where('fecha', $fecha)->orWhereDate('fecha', $fecha);
            });
        }
        $movimientos = $movQuery->orderBy('id', 'desc')->get();

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
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return back()->with('error', $error)->withInput();
        }

        $request->validate([
            'observaciones' => 'nullable|string|max:1000',
            'motivo_diferencia' => 'nullable|string|max:1000',
        ]);

        // M-14: si el cierre tiene descuadre, su motivo es obligatorio al cerrar y no se
        // puede vaciar después. Se puede corregir el texto; el anterior queda en eventos.
        if (abs((float) $cierre->diferencia) >= 0.01 && trim((string) $request->motivo_diferencia) === '') {
            return back()->withInput()->withErrors([
                'motivo_diferencia' => 'Este cierre tiene un descuadre de $'.number_format(abs((float) $cierre->diferencia), 0, ',', '.')
                    .': el motivo de la diferencia no se puede dejar vacío.',
            ]);
        }

        $cierre->update([
            'observaciones' => $request->observaciones,
            'motivo_diferencia' => $request->motivo_diferencia,
        ]);

        return redirect()->route('cierre.show', $cierre->id)
            ->with('success', 'Observaciones del cierre actualizadas correctamente.');
    }

    // ──────────────────────────────────────────────────────────
    /** Helper: calcula totales de movimientos de un día dado excluyendo los anulados */
    private function calcularDia(string $fecha, bool $bloquear = false): array
    {
        $movQuery = MovimientoCaja::query();
        if (DB::connection()->getDriverName() === 'mysql') {
            $movQuery->where('fecha', $fecha);
        } else {
            $movQuery->where(function ($q) use ($fecha) {
                $q->where('fecha', $fecha)->orWhereDate('fecha', $fecha);
            });
        }
        if ($bloquear) {
            // Lectura bloqueante: espera a los movimientos de esa fecha que otra transacción
            // esté guardando y lee su versión confirmada (D-08).
            $movQuery->sharedLock();
        }
        $movs = $movQuery->where('estado', 'activo')->where('anulado', false)->get();

        $ingresos = (float) $movs->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos = (float) $movs->where('tipo_movimiento', 'egreso')->sum('monto');

        $efectivoIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoEgresos = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoNeto = $efectivoIngresos - $efectivoEgresos;

        $consignacionIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionEgresos = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionNeto = $consignacionIngresos - $consignacionEgresos;

        // Saldo anterior acumulado en efectivo previo a esta fecha
        $saldoAnterior = CierreCaja::saldoInicialEfectivo($fecha);

        $efectivoEsperado = $saldoAnterior + $efectivoNeto;

        return [
            'total_ingresos' => $ingresos,
            'total_egresos' => $egresos,
            'saldo_anterior' => $saldoAnterior,
            'flujo_dia' => $efectivoNeto,
            'efectivo_esperado' => $efectivoEsperado,
            'efectivo' => $efectivoNeto,
            'consignacion' => $consignacionNeto,
            'saldo_final' => $ingresos - $egresos,
            'num_movimientos' => $movs->count(),
        ];
    }
}
