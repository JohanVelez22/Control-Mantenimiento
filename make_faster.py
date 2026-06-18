import re

# 1. Update app.blade.php
with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace('<div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-300">', 
              '<div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-150">')

# Also speed up the sidebar brand expansion if it has duration-300
c = c.replace('duration-300 font-logo">NAVEGACIÓN</span>', 'duration-150 font-logo">NAVEGACIÓN</span>')
c = c.replace('transition-all duration-300', 'transition-all duration-150')

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)


# 2. Update glass.css
with open('public/css/glass.css', 'r', encoding='utf-8') as f:
    css = f.read()

css = re.sub(r'transition: width 0\.32s cubic-bezier\(0\.4, 0, 0\.2, 1\)(.*?;)', r'transition: width 0.15s ease-out\1', css)
css = re.sub(r'transition: width 0\.35s ease;(.*?;)', r'transition: width 0.15s ease;\1', css)

# Make sure main wrapper margin-left is transitioned properly in css if missing, but it's handled by Tailwind class transition-all in blade.

with open('public/css/glass.css', 'w', encoding='utf-8') as f:
    f.write(css)

