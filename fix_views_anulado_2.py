import os
import re

def process_file(filepath, replacements):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        modified = content
        for pattern, replacement in replacements:
            modified = re.sub(pattern, replacement, modified, flags=re.DOTALL)
            
        if modified != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(modified)
            print(f"Updated {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

# Paths to update
base_dir = r"c:\ServBay\www\control-mantenimiento-equipos"

views_dirs = [
    r"resources\views\mantenimientos",
    r"resources\views\electronicas",
]

for view_dir in views_dirs:
    full_dir = os.path.join(base_dir, view_dir)
    if os.path.exists(full_dir):
        for root, dirs, files in os.walk(full_dir):
            for file in files:
                if file.endswith(".blade.php"):
                    filepath = os.path.join(root, file)
                    process_file(filepath, [
                        (r"@if\(\$([a-z])->anulado\)\s*<span class=\"pill pill-anulado\"[^\>]*>.*?</span>\s*@else\s*(.*?)\s*@endif", r"\2"),
                    ])

# Caja
caja_path = os.path.join(base_dir, r"resources\views\caja\index.blade.php")
process_file(caja_path, [
    (r"@if\(\$m->anulado\)\s*<span class=\"pill pill-anulado\"[^\>]*>.*?</span>\s*@elseif\(\$m->estado === 'activo'\)\s*(.*?)\s*@endif", r"@if($m->estado === 'activo')\n\1\n@endif"),
])

