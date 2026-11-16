<?php

namespace App\Services;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de cierre y reapertura de órdenes (Mantenimiento y Electrónica), B-01/B-03.
 *
 * - Terminar exige fecha de salida (no futura).
 * - Terminar con saldo pendiente solo se permite como "entrega con saldo": casilla
 *   explícita y motivo. Si quien la registra al crear es un técnico, además la
 *   clave de un administrador (al editar, el técnico ya la da para cualquier cambio).
 * - Una orden pendiente no tiene fecha de salida.
 * - Una orden terminada no cambia costo, equipo ni técnico ni recibe o pierde
 *   repuestos: hay que reabrirla, con motivo. Sí puede recibir abonos (cobrar el saldo).
 * - La factura solo se genera para órdenes terminadas.
 */
class OrdenEstadoService
{
    public const EPSILON = 0.01;

    public const MOTIVO_MIN = 5;

    /** Reglas de validación de fecha de salida y campos de cierre, para store y update. */
    public static function reglas(): array
    {
        return [
            'fecha_salida' => ['nullable', 'date', 'after_or_equal:fecha_entrada', 'before_or_equal:today', 'required_if:estado,terminado'],
            'entrega_con_saldo' => ['nullable', 'boolean'],
            'motivo_entrega' => ['nullable', 'string', 'max:500'],
            'motivo_reapertura' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * B-07: reglas comunes de Mantenimiento y Electrónica. El equipo y el técnico deben
     * estar activos (al editar se conserva el que ya tenía la orden) y la fecha de entrada
     * no puede ser futura.
     */
    public static function reglasComunes(?Model $orden): array
    {
        $equipoActual = $orden?->equipo_id;
        $tecnicoActual = $orden?->tecnico_id;

        return [
            'fecha_entrada' => ['required', 'date', 'before_or_equal:today'],
            'equipo_id' => ['required', 'integer', Rule::exists('equipos', 'id')->where(function ($q) use ($equipoActual) {
                $q->where(function ($w) use ($equipoActual) {
                    $w->where(fn ($a) => $a->where('active', true)->where('estado', '!=', 'dado_de_baja'));
                    if ($equipoActual) {
                        $w->orWhere('id', $equipoActual);
                    }
                });
            })],
            'tecnico_id' => ['required', 'integer', Rule::exists('tecnicos', 'id')->where(function ($q) use ($tecnicoActual) {
                $q->where(function ($w) use ($tecnicoActual) {
                    $w->where('active', true);
                    if ($tecnicoActual) {
                        $w->orWhere('id', $tecnicoActual);
                    }
                });
            })],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'fecha_entrada.before_or_equal' => 'La fecha de entrada no puede ser futura.',
            'equipo_id.exists' => 'El equipo seleccionado no existe, está anulado o fue dado de baja.',
            'tecnico_id.exists' => 'El técnico seleccionado no existe o está inactivo.',
            'fecha_salida.required_if' => 'Para marcar la orden como terminada, indique la fecha de salida (entrega).',
            'fecha_salida.before_or_equal' => 'La fecha de salida no puede ser futura.',
            'fecha_salida.after_or_equal' => 'La fecha de salida no puede ser anterior a la de entrada.',
        ];
    }

    /**
     * Aplica las reglas de transición sobre los datos ya validados.
     *
     * Devuelve los datos listos para guardar (sin los campos de cierre) y la lista de
     * eventos a registrar después de guardar. Lanza ValidationException si la
     * transición no está permitida.
     *
     * @return array{0: array, 1: array<int, array{0: string, 1: string}>}
     */
    public function preparar(array $datos, ?Model $orden): array
    {
        $entregaConSaldo = (bool) ($datos['entrega_con_saldo'] ?? false);
        $motivoEntrega = trim((string) ($datos['motivo_entrega'] ?? ''));
        $motivoReapertura = trim((string) ($datos['motivo_reapertura'] ?? ''));
        unset($datos['entrega_con_saldo'], $datos['motivo_entrega'], $datos['motivo_reapertura']);

        $estadoNuevo = $datos['estado'];
        $estadoAnterior = $orden?->estado;
        $eventos = [];

        if ($estadoNuevo === 'pendiente') {
            // Una orden pendiente no se ha entregado.
            $datos['fecha_salida'] = null;
        }

        if ($orden) {
            // B-21: el costo mínimo se valida de nuevo sobre la fila bloqueada; un abono
            // simultáneo pudo subir lo abonado después de la validación del formulario.
            $minimo = (float) $orden->costoMinimoPermitido();
            if (isset($datos['costo']) && (int) round($minimo * 100) > (int) round((float) $datos['costo'] * 100)) {
                throw ValidationException::withMessages([
                    'costo' => 'El costo no puede ser menor que lo ya abonado o que el valor de los repuestos asignados ($ '
                        .number_format($minimo, 0, ',', '.').').',
                ]);
            }

            // B-06: con abonos registrados, la orden pertenece a ese equipo y a su dueño
            // (la caja ya tiene su nombre); cambiar el equipo reescribiría el historial.
            if (isset($datos['equipo_id']) && (int) $datos['equipo_id'] !== (int) $orden->equipo_id
                && $orden->abonos()->where('anulado', false)->exists()) {
                throw ValidationException::withMessages([
                    'equipo_id' => 'La orden ya tiene abonos: no se puede cambiar el equipo. Anule los abonos o cree una orden nueva.',
                ]);
            }
        }

        if ($estadoAnterior === 'terminado' && $estadoNuevo === 'terminado') {
            $cambios = [];
            if (abs((float) $datos['costo'] - (float) $orden->costo) > self::EPSILON) {
                $cambios[] = 'el costo';
            }
            if ((int) $datos['equipo_id'] !== (int) $orden->equipo_id) {
                $cambios[] = 'el equipo';
            }
            if ((int) $datos['tecnico_id'] !== (int) $orden->tecnico_id) {
                $cambios[] = 'el técnico';
            }
            if ($cambios) {
                throw ValidationException::withMessages([
                    'estado' => 'La orden ya está terminada: no se puede cambiar '.implode(', ', $cambios)
                        .'. Para hacerlo, reábrala (estado Pendiente, con el motivo) y vuelva a cerrarla.',
                ]);
            }
        }

        if ($estadoAnterior === 'terminado' && $estadoNuevo === 'pendiente') {
            if (mb_strlen($motivoReapertura) < self::MOTIVO_MIN) {
                throw ValidationException::withMessages([
                    'motivo_reapertura' => 'Para reabrir una orden terminada, escriba el motivo (mínimo '.self::MOTIVO_MIN.' caracteres).',
                ]);
            }
            $eventos[] = ['orden_reabierta', 'Orden reabierta. Motivo: '.$motivoReapertura];
        }

        if ($estadoNuevo === 'terminado' && $estadoAnterior !== 'terminado') {
            $abonado = $orden ? (float) $orden->abonos()->where('anulado', false)->sum('monto') : 0.0;
            $saldo = round((float) $datos['costo'] - $abonado, 2);

            if ($saldo > self::EPSILON) {
                $saldoTxt = '$'.number_format($saldo, 0, ',', '.');
                if (! $entregaConSaldo) {
                    throw ValidationException::withMessages([
                        'entrega_con_saldo' => "La orden tiene un saldo pendiente de {$saldoTxt}. Registre el pago antes de terminarla, "
                            .'o marque "Entregar con saldo pendiente" e indique el motivo.',
                    ]);
                }
                if (mb_strlen($motivoEntrega) < self::MOTIVO_MIN) {
                    throw ValidationException::withMessages([
                        'motivo_entrega' => 'Indique el motivo para entregar con saldo pendiente (mínimo '.self::MOTIVO_MIN.' caracteres).',
                    ]);
                }
                $eventos[] = ['orden_entregada_con_saldo', "Orden terminada con saldo pendiente de {$saldoTxt}. Motivo: {$motivoEntrega}"];
            }
        }

        return [$datos, $eventos];
    }

    /** ¿La creación necesita clave de administrador? (técnico que entrega con saldo). */
    public function requiereClaveAlCrear(array $datos): bool
    {
        return Auth::user()?->isTecnico()
            && ($datos['estado'] ?? null) === 'terminado'
            && (float) ($datos['costo'] ?? 0) > self::EPSILON;
    }

    /** @param  array<int, array{0: string, 1: string}>  $eventos */
    public function registrarEventos(Model $orden, array $eventos): void
    {
        foreach ($eventos as [$accion, $descripcion]) {
            Evento::registrar($accion, $orden, null, null, $descripcion);
        }
    }

    /** Mensaje si la orden no admite cambios de repuestos, o null si los admite. */
    public static function bloqueoRepuestos(Model $orden): ?string
    {
        if ($orden->estado === 'terminado') {
            return 'La orden está terminada: para agregar o quitar repuestos, reábrala primero (editar → estado Pendiente, con el motivo).';
        }

        return null;
    }

    /** Mensaje si la orden no se puede facturar, o null si se puede. */
    public static function bloqueoFactura(Model $orden): ?string
    {
        if ($orden->estado !== 'terminado') {
            return 'Solo se puede generar la factura de una orden terminada.';
        }
        if (! $orden->fecha_salida) {
            return 'No se puede generar la factura sin fecha de salida. Registre la salida de la orden e inténtelo de nuevo.';
        }

        return null;
    }
}
