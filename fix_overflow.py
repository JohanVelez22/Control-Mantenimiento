import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Remove the bad body styles that are destroying the layout
c = c.replace("body { font-family: 'Outfit', sans-serif !important; background-color: #0A0F1C !important; }", "")

# We still want Outfit to be available for the logo and NAVEGACION
# Let's add it to the logo and NAVEGACION explicitly using inline styles or Tailwind classes if we had defined them.
# The font Outfit is loaded, so we can just use inline style for the logo.

logo_pattern = r'<span class="text-\[\#2563EB\]">TECNI</span><span class="text-white">SYSTEMAS</span>'
logo_outfit = '<span class="text-[#2563EB]" style="font-family: \'Outfit\', sans-serif;">TECNI</span><span class="text-white" style="font-family: \'Outfit\', sans-serif;">SYSTEMAS</span>'
c = c.replace(logo_pattern, logo_outfit)

nav_pattern = r'<span class="text-\[12px\] font-black tracking-\[0\.2em\] text-\[\#06B6D4\] uppercase opacity-0 group-\[\.expanded\]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>'
nav_outfit = '<span class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300" style="font-family: \'Outfit\', sans-serif;">NAVEGACIÓN</span>'
c = c.replace(nav_pattern, nav_outfit)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
