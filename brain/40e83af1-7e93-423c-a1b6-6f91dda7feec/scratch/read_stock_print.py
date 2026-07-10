with open(r'c:\ServBay\www\control-mantenimiento-equipos\stock_print.html', 'r', encoding='utf-16') as f:
    text = f.read()
print(text[:2000])
