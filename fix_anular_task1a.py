import re

def modify_file(filepath, replacements, use_regex=False):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    for target, replacement in replacements:
        if use_regex:
            content = re.sub(target, replacement, content, flags=re.DOTALL)
        else:
            content = content.replace(target, replacement)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

# 1. MantenimientoController
modify_file("app/Http/Controllers/MantenimientoController.php", [
    (r"    public function destroy.*?    }\n\n", "")
], use_regex=True)

modify_file("app/Http/Controllers/MantenimientoController.php", [
    ("            $mantenimiento->update(['estado' => 'anulado']);\n\n            // Revertir stock si se implementa relación pivot\n            foreach ($mantenimiento->stocks as $stock) {\n                \App\Models\Stock::where('id', $stock->id)->increment('cantidad', $stock->pivot->cantidad);\n            }\n            DB::commit();",
     "            $mantenimiento->update(['estado' => 'anulado']);\n\n            // Revertir stock si se implementa relación pivot\n            foreach ($mantenimiento->stocks as $stock) {\n                \App\Models\Stock::where('id', $stock->id)->increment('cantidad', $stock->pivot->cantidad);\n            }\n\n            // Revertir abonos en Caja\n            $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Mantenimiento')->first();\n            if ($concepto && $mantenimiento->abonos->count() > 0) {\n                foreach ($mantenimiento->abonos as $abono) {\n                    \App\Models\MovimientoCaja::where('concepto_id', $concepto->id)\n                        ->where('monto', $abono->monto)\n                        ->where('fecha', $abono->fecha->toDateString())\n                        ->where('descripcion', 'like', \"%Orden \" . $mantenimiento->id_orden . \"%\")\n                        ->where('estado', 'activo')\n                        ->update(['estado' => 'anulado']);\n                }\n            }\n\n            DB::commit();")
], use_regex=False)

# 2. ElectronicaController
modify_file("app/Http/Controllers/ElectronicaController.php", [
    (r"    public function destroy.*?    }\n\n", "")
], use_regex=True)

modify_file("app/Http/Controllers/ElectronicaController.php", [
    ("            $electronica->update(['estado' => 'anulado']);\n            \Illuminate\Support\Facades\DB::commit();",
     "            $electronica->update(['estado' => 'anulado']);\n\n            // Revertir stock\n            foreach ($electronica->stocks as $stock) {\n                \App\Models\Stock::where('id', $stock->id)->increment('cantidad', $stock->pivot->cantidad);\n            }\n\n            // Revertir abonos en Caja\n            $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Electrónica')->first();\n            if ($concepto && $electronica->abonos->count() > 0) {\n                foreach ($electronica->abonos as $abono) {\n                    \App\Models\MovimientoCaja::where('concepto_id', $concepto->id)\n                        ->where('monto', $abono->monto)\n                        ->where('fecha', $abono->fecha->toDateString())\n                        ->where('descripcion', 'like', \"%Orden \" . $electronica->id_orden . \"%\")\n                        ->where('estado', 'activo')\n                        ->update(['estado' => 'anulado']);\n                }\n            }\n\n            \Illuminate\Support\Facades\DB::commit();")
], use_regex=False)

# 3. MovimientoCajaController
modify_file("app/Http/Controllers/MovimientoCajaController.php", [
    (r"    /\*\*.*?     \* Eliminar.*?\*/\n    public function destroy.*?    }\n\n", "")
], use_regex=True)

# 4. Mantenimiento Index Blade
modify_file("resources/views/mantenimientos/index.blade.php", [
    (r" <form action=\"\{\{ route\('mantenimientos\.destroy'.*?</form>\n?", "")
], use_regex=True)

# 5. Electronica Index Blade
modify_file("resources/views/electronicas/index.blade.php", [
    (r" <form action=\"\{\{ route\('electronicas\.destroy'.*?</form>\n?", "")
], use_regex=True)

# 6. Caja Index Blade
modify_file("resources/views/caja/index.blade.php", [
    (r"\{\{-- Modal de contraseña para eliminar \(Liquid Glass\) --\}\}\n<div id=\"pwd-modal\".*?</div>\n</div>\n\n", ""),
    (r"\s*<button type=\"button\" onclick=\"openPwdModal\('\{\{ route\('caja\.destroy', \$m->id\) \}'\)\" class=\"btn-danger px-3 py-1\.5 text-xs\" title=\"Eliminar definitivamente\">🗑️</button>", ""),
    (r" function openPwdModal\(actionUrl\) \{.*?\}, 10\);\n \}\n \n function closePwdModal\(\) \{.*?\n \}\n\n", ""),
    (r" closePwdModal\(\);\n", "")
], use_regex=True)

print("Python script executed successfully.")
