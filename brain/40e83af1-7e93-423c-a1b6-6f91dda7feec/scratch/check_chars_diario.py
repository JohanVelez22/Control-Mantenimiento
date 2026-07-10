with open(r'c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\diario.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()
for i in range(97, 107):
    print(f"{i+1}: {repr(lines[i])}")
