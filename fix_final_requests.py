import re

# 1. Update mantenimientos/index.blade.php to match electronicas "Tipo" column format
file_mant = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\mantenimientos\index.blade.php"
with open(file_mant, "r", encoding="utf-8") as f:
    content_mant = f.read()

# Replace:
# <td class="text-center">
# <div class="font-bold text-slate-800 dark:text-white capitalize text-sm">{{ $m->tipo }}</div>
# <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ $m->reparacion }}</div>
# </td>
replacement_mant = """   <td class="text-center">
   <span class="pill {{ $m->tipo === 'correctivo' ? 'pill-correctivo' : 'pill-preventivo' }} {{ $m->anulado ? 'line-through opacity-70' : '' }}">
   {{ ucfirst($m->tipo) }}
   </span>
   <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mt-1">{{ $m->reparacion }}</div>
   </td>"""
content_mant = re.sub(
    r"<td class=\"text-center\">\s*<div class=\"font-bold text-slate-800 dark:text-white capitalize text-sm\">\{\{ \$m->tipo \}\}</div>\s*<div class=\"text-\[10px\] font-semibold text-gray-500 uppercase tracking-widest\">\{\{ \$m->reparacion \}\}</div>\s*</td>",
    replacement_mant,
    content_mant
)
with open(file_mant, "w", encoding="utf-8") as f:
    f.write(content_mant)


# 2. Update electronicas/index.blade.php to add strike-through if anulado
file_elec = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\electronicas\index.blade.php"
with open(file_elec, "r", encoding="utf-8") as f:
    content_elec = f.read()

content_elec = re.sub(
    r"(<span class=\"pill \{\{ \$e->tipo === 'correctivo' \? 'pill-correctivo' : 'pill-preventivo' \}\})\">",
    r"\1 {{ $e->anulado ? 'line-through opacity-70' : '' }}\">",
    content_elec
)
with open(file_elec, "w", encoding="utf-8") as f:
    f.write(content_elec)


# 3. Update layouts/app.blade.php to link Info Operativos to diario instead of reportes.index
file_app = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\layouts\app.blade.php"
with open(file_app, "r", encoding="utf-8") as f:
    content_app = f.read()

content_app = re.sub(
    r"<a href=\"\{\{ route\('reportes.index'\) \}\}\" class=\"nav-item \{\{ request\(\)->routeIs\('reportes.\*'\) \? 'active' : '' \}\}\" title=\"Info Operativos\">",
    r"<a href=\"{{ route('reportes.financiero.diario') }}\" class=\"nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}\" title=\"Info Operativos\">",
    content_app
)
with open(file_app, "w", encoding="utf-8") as f:
    f.write(content_app)

print("Updates applied successfully.")
