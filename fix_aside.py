import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

pattern = r'<aside id="ts-sidebar"[^>]+>'
c = re.sub(pattern, '<aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col" style="background-color: #0B1121;">', c)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
