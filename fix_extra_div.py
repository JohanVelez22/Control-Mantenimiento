import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace "</div>\n            </div>\n\n            <!-- Navegación -->"
# with "</div>\n\n            <!-- Navegación -->"
pattern = r'</div>\s*</div>\s*<!-- Navegación -->'
c = re.sub(pattern, '</div>\n\n            <!-- Navegación -->', c)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
