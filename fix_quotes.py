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

for filepath in files:
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # Replace literal \" with "
    content = content.replace(r'\"', '"')

    # Now let's fix the pill classes properly.
    # We want <span class="pill pill-anulado"> for annulled and <span class="pill pill-done"> for active
    # Let's check what I put in diario.blade.php
    # <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ !empty($mov['anulado']) ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
    # Let's replace ALL of these with the correct pills.

    import re
    content = re.sub(
        r"class=\"px-2 py-0\.5 rounded-lg text-xs font-bold \{\{ (.*?) \? 'bg-red-100[^']*' : 'bg-emerald-100[^']*' \}\}\"",
        r'class="pill {{ \1 ? \'pill-anulado\' : \'pill-done\' }}"',
        content
    )

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

print("Fixed quotes and classes.")
