import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Match the height of the Sidebar brand area to the Topbar (h-16)
sidebar_header_pattern = r'<div class="h-20 flex items-center justify-center expanded:justify-start border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-300">'
new_sidebar_header = '<div class="h-16 flex items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-300">'
c = c.replace(sidebar_header_pattern, new_sidebar_header)

# 2. Make NAVEGACION font bolder and strictly centered
nav_span_pattern = r'<span class="text-\[13px\] font-normal tracking-widest text-\[#06B6D4\] uppercase opacity-0 group-\[\.expanded\]:opacity-100 transition-opacity duration-300 font-logo">NAVEGACIÓN</span>'
new_nav_span = '<span class="text-[14px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full">NAVEGACIÓN</span>'
c = c.replace(nav_span_pattern, new_nav_span)

# 3. Remove the NAV shorthand completely if they want it exactly like the image
nav_short_pattern = r'<span class="text-\[11px\] font-black text-\[#2563EB\] uppercase group-\[\.expanded\]:hidden font-logo">NAV</span>'
c = c.replace(nav_short_pattern, '')

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
