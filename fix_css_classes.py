import re
import glob

def fix_blade_files(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Fix row classes
    # Look for: <tr class="{{ $var ? 'opacity-50 line-through' : '' }}...
    # Replace with: <tr class="{{ $var ? 'row-anulado' : '' }}...
    content = re.sub(
        r"\{\{\s*(!?empty\(\$[^)]+\)|\$[^ ]+)\s*\?\s*'opacity-50 line-through'\s*:\s*''\s*\}\}",
        r"{{ \1 ? 'row-anulado' : '' }}",
        content
    )

    # Facturas specific (has $f->estado === 'anulada')
    content = re.sub(
        r"\{\{\s*(!empty\(\$f\) && \$f->estado === 'anulada')\s*\?\s*'opacity-50 line-through'\s*:\s*''\s*\}\}",
        r"{{ \1 ? 'row-anulado' : '' }}",
        content
    )

    # 2. Fix pills (where it uses bg-red-100 etc)
    # <span class="... {{ $var ? 'bg-red-100...' : 'bg-emerald-100...' }}">
    content = re.sub(
        r"class=\"[^\"]*\{\{\s*(!?empty\(\$[^)]+\)|\$[^ ]+)\s*\?\s*'bg-red-100[^']*'\s*:\s*'bg-emerald-100[^']*'\s*\}\}[^\"]*\"",
        r"class=\"pill {{ \1 ? 'pill-anulado' : 'pill-done' }}\"",
        content
    )

    # Also fix in dashboard where I left 'pill ' before the dynamic part:
    # class="pill {{ $m->anulado ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }}"
    content = re.sub(
        r"class=\"pill\s*\{\{\s*(!?empty\(\$[^)]+\)|\$[^ ]+)\s*\?\s*'bg-red-100[^']*'\s*:\s*'bg-emerald-100[^']*'\s*\}\}\"",
        r"class=\"pill {{ \1 ? 'pill-anulado' : 'pill-done' }}\"",
        content
    )

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

# Process all files
files = [
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\dashboard.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\mantenimientos\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\electronicas\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\caja\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\diario.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\acumulado.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php",
]

for f in files:
    fix_blade_files(f)

print("All blade files fixed for row-anulado and pill-anulado/pill-done.")
