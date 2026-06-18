import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Update NAVEGACION to make it smaller
old_nav = '<span class="text-[14px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full">NAVEGACIÓN</span>'
new_nav = '<span class="text-[11px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full">NAVEGACIÓN</span>'
c = c.replace(old_nav, new_nav)

# 2. Add id="main-wrapper" and remove inline style to allow CSS to push the layout
old_main = '<div class="flex-1 flex flex-col min-w-0 transition-all duration-300" style="margin-left: var(--sidebar-w);">'
new_main = '<div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-300">'
c = c.replace(old_main, new_main)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
