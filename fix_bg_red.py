import glob

files = [
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\dashboard.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\mantenimientos\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\electronicas\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\caja\index.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\diario.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\acumulado.blade.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php",
]

import re
for filepath in files:
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # Replace bg-red-100 text-red-800 with pill-anulado / pill-done
    content = re.sub(
        r"'bg-red-100[^']*'\s*:\s*'bg-emerald-100[^']*'",
        r"'pill-anulado' : 'pill-done'",
        content
    )

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

print("Fixed bg-red classes.")
