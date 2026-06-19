import os
import re

def add_fillable(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # If 'anulado' is already in fillable, skip
        if "'anulado'" in content or '"anulado"' in content:
            return

        # Find the end of the fillable array
        # This regex looks for 'estado', and we add 'anulado', right after it.
        modified = re.sub(r"('estado',\s*)", r"\1'anulado',\n        ", content)
        
        if modified != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(modified)
            print(f"Updated {filepath}")
        else:
            print(f"Could not find where to insert in {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

models = [
    r"c:\ServBay\www\control-mantenimiento-equipos\app\Models\Mantenimiento.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\app\Models\Electronica.php",
    r"c:\ServBay\www\control-mantenimiento-equipos\app\Models\MovimientoCaja.php",
]

for m in models:
    add_fillable(m)
