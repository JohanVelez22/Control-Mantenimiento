import os
import re

def process_file(filepath, replacements):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        modified = content
        for pattern, replacement in replacements:
            modified = re.sub(pattern, replacement, modified)
            
        if modified != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(modified)
            print(f"Updated {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

# Paths to update
base_dir = r"c:\ServBay\www\control-mantenimiento-equipos"

controllers_to_update = [
    r"app\Http\Controllers\MantenimientoController.php",
    r"app\Http\Controllers\ElectronicaController.php",
    r"app\Http\Controllers\MovimientoCajaController.php",
    r"app\Http\Controllers\ReporteController.php",
    r"app\Http\Controllers\ReporteFinancieroController.php",
    r"app\Http\Controllers\MantenimientoStockController.php",
    r"app\Http\Controllers\ElectronicaStockController.php",
]

views_dirs = [
    r"resources\views\mantenimientos",
    r"resources\views\electronicas",
    r"resources\views\caja",
    r"resources\views\reportes_financieros",
    r"resources\views\reportes",
    r"resources\views\layouts",
]

for ctrl in controllers_to_update:
    path = os.path.join(base_dir, ctrl)
    process_file(path, [
        (r"->where\('estado',\s*'!=',\s*'anulado'\)", r"->where('anulado', false)"),
        (r"->where\('estado',\s*'anulado'\)", r"->where('anulado', true)"),
        (r"->whereIn\('estado',\s*\['anulado',\s*'anulada'\]\)", r"->where('anulado', true)"),
        (r"->update\(\['estado'\s*=>\s*'anulado'\]\)", r"->update(['anulado' => true])"),
        (r"->estado\s*===\s*'anulado'", r"->anulado"),
        (r"->estado\s*!==\s*'anulado'", r"!$0->anulado"),
    ])

# For views, we need to replace $var->estado === 'anulado' with $var->anulado
for view_dir in views_dirs:
    full_dir = os.path.join(base_dir, view_dir)
    if os.path.exists(full_dir):
        for root, dirs, files in os.walk(full_dir):
            for file in files:
                if file.endswith(".blade.php"):
                    filepath = os.path.join(root, file)
                    process_file(filepath, [
                        (r"\$([a-zA-Z0-9_]+)->estado\s*===\s*'anulado'", r"$\1->anulado"),
                        (r"\$([a-zA-Z0-9_]+)->estado\s*!==\s*'anulado'", r"!$\1->anulado"),
                        # In Arrays (like reportes_financieros where we iterate $mov)
                        (r"\$([a-zA-Z0-9_]+)\['estado'\]\s*===\s*'anulado'", r"$\1['anulado']"),
                        (r"\$([a-zA-Z0-9_]+)\['estado'\]\s*!==\s*'anulado'", r"!$\1['anulado']"),
                        (r"\$([a-zA-Z0-9_]+)\['estado'\]\s*===\s*'anulada'", r"$\1['anulado']"),
                        (r"\$([a-zA-Z0-9_]+)\['estado'\]\s*!==\s*'anulada'", r"!$\1['anulado']"),
                        (r"\|\|\s*\$([a-zA-Z0-9_]+)\['estado'\]\s*===\s*'anulada'", r""), # Remove the OR part
                        (r"\|\|\s*\$([a-zA-Z0-9_]+)\['anulado'\]", r""), # In case we already replaced both
                    ])

