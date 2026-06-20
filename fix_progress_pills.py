import re
import glob

def process_dashboard():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\dashboard.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    # Add line-through opacity-70 to progress pills if anulado
    # Mantenimiento
    content = re.sub(
        r"(<span class=\"pill \{\{ \$m->estado == 'terminado' \? 'pill-done' : 'pill-pending' \}\})\">",
        r"\1 {{ $m->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    # Electronica
    content = re.sub(
        r"(<span class=\"pill \{\{ \$e->estado == 'terminado' \? 'pill-done' : 'pill-pending' \}\})\">",
        r"\1 {{ $e->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_mantenimientos():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\mantenimientos\index.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    content = re.sub(
        r"(<span class=\"pill \{\{ in_array\(\$m->estado, \['terminado', 'entregado'\]\) \? 'pill-done' : 'pill-pending' \}\})\">",
        r"\1 {{ $m->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_electronicas():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\electronicas\index.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    content = re.sub(
        r"(<span class=\"pill \{\{ \$e->estado === 'terminado' \? 'pill-done' : 'pill-pending' \}\})\">",
        r"\1 {{ $e->anulado ? 'line-through opacity-70' : '' }}\">",
        content
    )
    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_diario_acumulado():
    for name in ['diario.blade.php', 'acumulado.blade.php']:
        filepath = rf"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\{name}"
        with open(filepath, "r", encoding="utf-8") as f: content = f.read()

        # Update the php logic to use pill-especialidad for emitida/procesado
        content = re.sub(
            r"if\(in_array\(\$progreso, \['terminado', 'entregado', 'emitida', 'procesado'\]\)\) \$pillClass = 'pill-done';",
            r"if(in_array($progreso, ['terminado', 'entregado'])) $pillClass = 'pill-done';\n       elseif(in_array($progreso, ['emitida', 'procesado'])) $pillClass = 'pill-especialidad';",
            content
        )

        # Add strike-through to the span
        content = re.sub(
            r"(<span class=\"pill \{\{ \$pillClass \}\})([^>]*>\{\{ ucfirst\(\$progreso\) \?\: '—' \}\})",
            r"\1 {{ !empty($mov['anulado']) ? 'line-through opacity-70' : '' }}\2",
            content
        )
        with open(filepath, "w", encoding="utf-8") as f: f.write(content)

def process_operaciones():
    filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php"
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()

    # Mantenimiento
    content = re.sub(
        r"(<td class=\"p-2\"><span class=\"pill pill-efectivo)\">(\{\{ ucfirst\(\$m->estado\) \}\})",
        r"\1 {{ !empty($m->anulado) ? 'line-through opacity-70' : '' }}\">\2",
        content
    )
    # Electronica
    content = re.sub(
        r"(<td class=\"p-2\"><span class=\")px-2 py-0\.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-800(\">\{\{ ucfirst\(\$e->estado\) \}\})",
        r"\1pill pill-pending {{ !empty($e->anulado) ? 'line-through opacity-70' : '' }}\2",
        content
    )
    # Ingresos/Egresos (Caja) -> Change {{ $c->estado }} (which was activo/anulado) to hardcoded 'Procesado' with pill-especialidad
    content = re.sub(
        r"(<td class=\"p-2\"><span class=\")text-xs font-semibold capitalize(\">)\{\{ \$c->estado \}\}(</span></td>\s*<td class=\"p-2\">\s*<span class=\"pill \{\{ !empty\(\$c->anulado\))",
        r"\1pill pill-especialidad {{ !empty($c->anulado) ? 'line-through opacity-70' : '' }}\2Procesado\3",
        content
    )
    # Facturas
    content = re.sub(
        r"(<td class=\"p-2\"><span class=\")text-xs font-semibold capitalize(\">)\{\{ \$f->estado \}\}(</span></td>\s*</tr>)",
        r"\1pill pill-especialidad {{ $f->estado === 'anulada' ? 'line-through opacity-70' : '' }}\2Emitida\3",
        content
    )
    # Wait, the facturas in operaciones.blade.php only have ONE state column! Let me double check operations facturas.
    # Facturas header has: <th class="p-2 text-center">Estado</th>
    # And row has: <td class="p-2"><span class="text-xs font-semibold capitalize">{{ $f->estado }}</span></td>
    # So for facturas, if the user wants it to look like the rest, wait, it didn't have "Progreso" vs "Estado".
    # I'll change the facturas text to pill-done if activa, pill-anulado if anulada.
    # Ah wait, let's keep it simple: <span class="pill {{ $f->estado === 'anulada' ? 'pill-anulado line-through opacity-70' : 'pill-done' }}">{{ $f->estado }}</span>

    # For facturas fix:
    content = re.sub(
        r"<td class=\"p-2\"><span class=\"text-xs font-semibold capitalize\">\{\{ \$f->estado \}\}</span></td>\s*</tr>",
        r"<td class=\"p-2\"><span class=\"pill {{ $f->estado === 'anulada' ? 'pill-anulado line-through opacity-70' : 'pill-done' }}\">{{ ucfirst($f->estado) }}</span></td>\n   </tr>",
        content
    )

    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

process_dashboard()
process_mantenimientos()
process_electronicas()
process_diario_acumulado()
process_operaciones()

print("Progress pills updated!")
