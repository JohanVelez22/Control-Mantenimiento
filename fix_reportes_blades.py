import re

def update_report_blade(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # Table Header
    content = re.sub(
        r"(<th class=\"p-3 text-center\">)Estado(</th>)",
        r"\1Progreso\2\n <th class=\"p-3 text-center\">Estado</th>",
        content
    )

    # Add line-through to the tr
    content = re.sub(
        r"'opacity-50'",
        r"'opacity-50 line-through'",
        content
    )

    # Add Estado cell
    content = re.sub(
        r"(<span class=\"text-xs font-semibold text-gray-500\">\{\{ ucfirst\(\$mov\['estado'\] \?\? '—'\) \}\}</span>\s*</td>)",
        r"\1\n <td class=\"p-3\">\n <span class=\"px-2 py-0.5 rounded-lg text-xs font-bold {{ !empty($mov['anulado']) ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}\">\n {{ !empty($mov['anulado']) ? 'Anulado' : 'Activo' }}\n </span>\n </td>",
        content
    )

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

update_report_blade(r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\diario.blade.php")
update_report_blade(r"c:\ServBay\www\control-mantenimiento-equipos\resources\views\reportes_financieros\acumulado.blade.php")

print("Updated diario.blade.php and acumulado.blade.php")
