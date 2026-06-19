import re

filepath = r"c:\ServBay\www\control-mantenimiento-equipos\app\Http\Controllers\ReporteFinancieroController.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Remove where('anulado', false) ONLY from the specific list queries
# We'll use regex to target the queries and their maps
# 1. Mantenimientos Diario
content = re.sub(
    r"(\$mantenimientos = Mantenimiento::with\(\['equipo\.cliente', 'tecnico'\]\)\s*)->where\('anulado',\s*false\)\s*(->whereDate\('fecha_entrada', \$fecha\))",
    r"\1\2",
    content
)
# Add anulado mapping for Mantenimiento
content = re.sub(
    r"('estado'\s*=> \$m->estado,)",
    r"\1\n                    'anulado'     => $m->anulado,",
    content
)

# 2. Electronica Diario
content = re.sub(
    r"(\$electronicas = Electronica::with\(\['tecnico', 'equipo\.cliente'\]\)\s*)->where\('anulado',\s*false\)\s*(->whereDate\('fecha_entrada', \$fecha\))",
    r"\1\2",
    content
)
# Add anulado mapping for Electronica
content = re.sub(
    r"('estado'\s*=> \$e->estado,)",
    r"\1\n                    'anulado'     => $e->anulado,",
    content
)

# 3. Facturas Diario
# Add anulado mapping for Facturas
content = re.sub(
    r"('estado'\s*=> \$f->estado,)",
    r"\1\n                    'anulado'     => $f->estado === 'anulada',",
    content
)

# 4. Caja Diario
content = re.sub(
    r"(\$caja = MovimientoCaja::with\('concepto'\)\s*)->where\('anulado',\s*false\)\s*(->whereDate\('fecha', \$fecha\))",
    r"\1\2",
    content
)
# Add anulado mapping for Caja
content = re.sub(
    r"('estado'\s*=> \$c->estado,)",
    r"\1\n                    'anulado'     => $c->anulado,",
    content
)

# Fix Diario Resumen
resumen_old = r"""        \$resumen = \[
            'total_ingresos'       => \$movimientos->whereIn\('tipo', \['ingreso', 'venta'\]\)->sum\('monto'\),
            'total_egresos'        => \$movimientos->whereIn\('tipo', \['egreso', 'compra'\]\)->sum\('monto'\),
            'total_mantenimientos' => \$mantenimientos->sum\('monto'\),
            'total_anulados'       => 0,
        \];"""
resumen_new = """        $resumen = [
            'total_ingresos'       => $movimientos->where('anulado', false)->whereIn('tipo', ['ingreso', 'venta'])->sum('monto'),
            'total_egresos'        => $movimientos->where('anulado', false)->whereIn('tipo', ['egreso', 'compra'])->sum('monto'),
            'total_mantenimientos' => $mantenimientos->where('anulado', false)->sum('costo'),
            'total_anulados'       => $movimientos->where('anulado', true)->count(),
        ];"""
content = re.sub(resumen_old, resumen_new, content)

# 5. Mantenimientos Acumulado
content = re.sub(
    r"(\$mantenimientosList = Mantenimiento::with\(\['equipo\.cliente', 'tecnico'\]\)\s*)->where\('anulado',\s*false\)\s*(->whereBetween\('fecha_entrada', \[\$desde, \$hasta\]\))",
    r"\1\2",
    content
)

# 6. Electronica Acumulado
content = re.sub(
    r"(\$electronicasList = Electronica::with\(\['tecnico', 'equipo\.cliente'\]\)\s*)->where\('anulado',\s*false\)\s*(->whereBetween\('fecha_entrada', \[\$desde, \$hasta\]\))",
    r"\1\2",
    content
)

# 7. Caja Acumulado
content = re.sub(
    r"(\$cajaList = MovimientoCaja::with\('concepto'\)\s*)->where\('anulado',\s*false\)\s*(->whereBetween\('fecha', \[\$desde, \$hasta\]\))",
    r"\1\2",
    content
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)

print("Updated ReporteFinancieroController")
