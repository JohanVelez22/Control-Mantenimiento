import re
import glob

def process_caja():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\caja\index.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    # Add line-through opacity-70 to pills in caja if anulado
    # Tipo
    content = re.sub(
        r"(<span class=\"pill \{\{ \$m->tipo_movimiento === 'ingreso' \? 'pill-done' : 'pill-egreso' \}\})\">",
        r"\1 {{ $m->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    # Pago
    content = re.sub(
        r"(<span class=\"pill \{\{ \$m->tipo_pago === 'efectivo' \? 'pill-efectivo' : 'pill-banco' \}\})\">",
        r"\1 {{ $m->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    # Estado (already has it? no, wait. We didn't add it in caja/index.blade.php yet for the state pill)
    content = re.sub(
        r"(<span class=\"pill \{\{ \$m->anulado \? 'pill-anulado' : 'pill-done' \}\})\">",
        r"\1 {{ $m->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_diario_acumulado():
    for name in ['diario.blade.php', 'acumulado.blade.php']:
        filepath = rf"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\{name}"
        with open(filepath, "r", encoding="utf-8") as f: content = f.read()

        # Update the php logic to differentiate emitida (blue) from procesado (purple)
        content = re.sub(
            r"elseif\(in_array\(\$progreso, \['emitida', 'procesado'\]\)\) \$pillClass = 'pill-especialidad';",
            r"elseif($progreso === 'emitida') $pillClass = 'pill-preventivo';\n       elseif($progreso === 'procesado') $pillClass = 'pill-especialidad';",
            content
        )
        with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_operaciones():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    # Facturas was using pill-especialidad. Let's change it to pill-preventivo for Emitida.
    content = re.sub(
        r"pill-especialidad \{\{ \$f->estado === 'anulada'",
        r"pill-preventivo {{ $f->estado === 'anulada'",
        content
    )

    with open(filepath, "w", encoding="utf-8") as f: f.write(content)


process_caja()
process_diario_acumulado()
process_operaciones()

print("Pills updated in caja and reportes financieros!")
