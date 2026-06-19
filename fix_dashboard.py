import os
import re

def add_anulado_false(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replace common model queries to exclude annulled
        # For Mantenimiento
        content = re.sub(r'Mantenimiento::count\(\)', r"Mantenimiento::where('anulado', false)->count()", content)
        content = re.sub(r"Mantenimiento::where\('estado',", r"Mantenimiento::where('anulado', false)->where('estado',", content)
        content = re.sub(r"Mantenimiento::whereIn\('estado',", r"Mantenimiento::where('anulado', false)->whereIn('estado',", content)
        content = re.sub(r"Mantenimiento::whereBetween\('fecha_entrada',", r"Mantenimiento::where('anulado', false)->whereBetween('fecha_entrada',", content)
        
        # For Electronica
        content = re.sub(r"Electronica::where\('estado',", r"Electronica::where('anulado', false)->where('estado',", content)
        content = re.sub(r"Electronica::where\('tipo',", r"Electronica::where('anulado', false)->where('tipo',", content)
        
        # For MovimientoCaja
        # Already has where('estado', 'activo'), just add where('anulado', false)
        content = re.sub(r"\\App\\Models\\MovimientoCaja::where\('estado', 'activo'\)", r"\\App\\Models\\MovimientoCaja::where('estado', 'activo')->where('anulado', false)", content)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
    except Exception as e:
        print(f"Error: {e}")

add_anulado_false(r"c:\ServBay\www\control-mantenimiento-equipos\app\Http\Controllers\DashboardController.php")
