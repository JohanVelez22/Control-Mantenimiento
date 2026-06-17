import os
import re

views_dir = r"c:\ServBay\www\control-mantenimiento-equipos\resources\views"

files_to_fix = []

for root, dirs, files in os.walk(views_dir):
    for file in files:
        if file.endswith('.blade.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            if 'links()' in content:
                original = content
                
                # Replace p-6 md:p-8 with p-6 on glass-card
                content = re.sub(r'class="glass-card p-6 md:p-8"', 'class="glass-card p-6"', content)
                content = re.sub(r'class="glass-card p-6 md:p-8 mt-6"', 'class="glass-card p-6 mt-6"', content)
                content = re.sub(r'class="glass-card p-6 md:p-8 mb-6"', 'class="glass-card p-6 mb-6"', content)
                
                # Replace mt-4 wrapper with mt-6 flex justify-end wrapper around pagination
                content = re.sub(r'<div class="mt-4">(\s*\{\{.*?links\(\).*?\}\}\s*)</div>', r'<div class="mt-6 flex justify-end">\1</div>', content)

                if content != original:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    files_to_fix.append(path)

print("Modified files:")
for f in files_to_fix:
    print("- " + f)
