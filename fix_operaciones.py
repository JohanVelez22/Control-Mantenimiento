import re

filepath = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# 1. solo_mantenimientos
# Header
content = re.sub(
    r"(<th class=\"p-2 text-center\">Costo</th>)<th class=\"p-2 text-center\">Estado</th>",
    r"\1<th class=\"p-2 text-center\">Progreso</th><th class=\"p-2 text-center\">Estado</th>",
    content
)
# Row
content = re.sub(
    r"(<tr class=\")hover:bg-gray-50 dark:hover:bg-gray-700/30(\">)",
    r"\1hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($m->anulado) ? 'opacity-50 line-through' : '' }}\2",
    content
)
# Cell
content = re.sub(
    r"(<td class=\"p-2\"><span class=\"pill pill-efectivo\">\{\{ ucfirst\(\$m->estado\) \}\}</span></td>)",
    r"\1\n <td class=\"p-2\">\n <span class=\"px-2 py-0.5 rounded-lg text-xs font-bold {{ !empty($m->anulado) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}\">\n {{ !empty($m->anulado) ? 'Anulado' : 'Activo' }}\n </span>\n </td>",
    content
)

# 2. solo_electronica
# Header
content = re.sub(
    r"(<th class=\"p-2 text-center\">Costo</th>)<th class=\"p-2 text-center\">Estado</th>",
    r"\1<th class=\"p-2 text-center\">Progreso</th><th class=\"p-2 text-center\">Estado</th>",
    content
)
# Row
content = re.sub(
    r"(<tr class=\")hover:bg-gray-50 dark:hover:bg-gray-700/30(\">)",
    r"\1hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($e->anulado) ? 'opacity-50 line-through' : '' }}\2",
    content
)
# Cell
content = re.sub(
    r"(<td class=\"p-2\"><span class=\"px-2 py-0.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-800\">\{\{ ucfirst\(\$e->estado\) \}\}</span></td>)",
    r"\1\n <td class=\"p-2\">\n <span class=\"px-2 py-0.5 rounded-lg text-xs font-bold {{ !empty($e->anulado) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}\">\n {{ !empty($e->anulado) ? 'Anulado' : 'Activo' }}\n </span>\n </td>",
    content
)

# 3. solo_ingresos, solo_egresos
# Header
content = re.sub(
    r"(<th class=\"p-2 text-center\">Monto</th>)<th class=\"p-2 text-center\">Estado</th>",
    r"\1<th class=\"p-2 text-center\">Progreso</th><th class=\"p-2 text-center\">Estado</th>",
    content
)
# Row
content = re.sub(
    r"(<tr class=\")hover:bg-gray-50 dark:hover:bg-gray-700/30(\">)",
    r"\1hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($c->anulado) ? 'opacity-50 line-through' : '' }}\2",
    content
)
# Cell
content = re.sub(
    r"(<td class=\"p-2\"><span class=\"text-xs font-semibold capitalize\">\{\{ \$c->estado \}\}</span></td>)",
    r"\1\n <td class=\"p-2\">\n <span class=\"px-2 py-0.5 rounded-lg text-xs font-bold {{ !empty($c->anulado) ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}\">\n {{ !empty($c->anulado) ? 'Anulado' : 'Activo' }}\n </span>\n </td>",
    content
)

# 4. compras, ventas
# Facturas just get opacity class
content = re.sub(
    r"(<tr class=\")hover:bg-gray-50 dark:hover:bg-gray-700/30(\">)",
    r"\1hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($f) && $f->estado === 'anulada' ? 'opacity-50 line-through' : '' }}\2",
    content
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("Updated operaciones.blade.php")
