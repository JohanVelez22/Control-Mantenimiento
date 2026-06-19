import re

filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\dashboard.blade.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# For Mantenimientos table
# Header
content = re.sub(
    r"(<th class=\"text-center\">Costo</th>\s*<th class=\"text-center\">)Estado(</th>)",
    r"\1Progreso\2\n <th class=\"text-center\">Estado</th>",
    content
)

# Row class to add opacity if anulado
content = re.sub(
    r"<tr>(\s*<td class=\"text-center font-bold whitespace-nowrap\">\s*<a href=\"\{\{ route\('mantenimientos\.index')",
    r"<tr class=\"{{ $m->anulado ? 'opacity-50 line-through' : '' }}\">\1",
    content
)

# Body cell
content = re.sub(
    r"(<span class=\"pill \{\{ \$m->estado == 'terminado' \? 'pill-done' : 'pill-pending' \}\}\">\s*\{\{ strtoupper\(\$m->estado\) \}\}\s*</span>\s*</td>)",
    r"\1\n <td class=\"text-center\">\n <span class=\"pill {{ $m->anulado ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}\">\n {{ $m->anulado ? 'ANULADO' : 'ACTIVO' }}\n </span>\n </td>",
    content
)

# For Electronica table
# Header
content = re.sub(
    r"(<th class=\"text-center\">Costo</th>\s*<th class=\"text-center\">)Estado(</th>)",
    r"\1Progreso\2\n <th class=\"text-center\">Estado</th>",
    content
)

# Row class to add opacity if anulado
content = re.sub(
    r"<tr>(\s*<td class=\"text-center font-bold whitespace-nowrap\">\s*<a href=\"\{\{ route\('electronicas\.index')",
    r"<tr class=\"{{ $e->anulado ? 'opacity-50 line-through' : '' }}\">\1",
    content
)

# Body cell
content = re.sub(
    r"(<span class=\"pill \{\{ \$e->estado == 'terminado' \? 'pill-done' : 'pill-pending' \}\}\">\s*\{\{ strtoupper\(\$e->estado\) \}\}\s*</span>\s*</td>)",
    r"\1\n <td class=\"text-center\">\n <span class=\"pill {{ $e->anulado ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}\">\n {{ $e->anulado ? 'ANULADO' : 'ACTIVO' }}\n </span>\n </td>",
    content
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("Updated dashboard.blade.php")
