with open(r'c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\operaciones.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()
for i in range(127, 185):
    print(f"{i+1}: {repr(lines[i])}")
