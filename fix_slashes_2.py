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
    with open(filepath, "r", encoding="utf-8") as f: content = f.read()
    content = content.replace(r'\"', '"')
    with open(filepath, "w", encoding="utf-8") as f: f.write(content)

print("Fixed backslash errors.")
