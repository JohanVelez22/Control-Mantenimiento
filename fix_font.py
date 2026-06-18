import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Add Outfit font
font_link = """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif !important; background-color: #0A0F1C !important; }
        .nav-item.active { background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(37,99,235,0.05) 100%); color: #60A5FA; border-left: 3px solid #3B82F6; font-weight: 700; }
        .nav-label { font-family: 'Outfit', sans-serif !important; }
    </style>"""
c = re.sub(r'<link rel="stylesheet" href="{{ asset\(\'css/glass\.css\'\) }}">', font_link, c)

# Make "NAVEGACIÓN" cyan
nav_pattern = r'<span class="text-\[11px\] font-black tracking-\[0\.2em\] text-\[\#2563EB\] uppercase opacity-0 group-\[\.expanded\]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>'
nav_cyan = '<span class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>'
c = c.replace('<span class="text-[11px] font-black tracking-[0.2em] text-[#2563EB] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>', nav_cyan)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
