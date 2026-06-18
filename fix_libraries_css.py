import re

# 1. Update app.blade.php to include the missing CSS for flatpickr and tom-select
with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

head_pattern = r"<!-- CSS Propio \(Liquid Glass\) -->\s*<link rel=\"stylesheet\" href=\"\{\{ asset\('css/glass\.css'\) \}\}\">"
head_replacement = """<!-- CSS Librerías -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    
    <!-- CSS Propio (Liquid Glass) -->
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">"""

if 'npm/flatpickr' not in c:
    c = re.sub(head_pattern, head_replacement, c)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)

# 2. Add glass styles for tom-select and flatpickr in glass.css
glass_css_additions = """

/* 🌟 GLASSMORPHISM FOR TOM-SELECT */
.ts-control {
    background: rgba(255,255,255,0.60) !important;
    border: 1px solid rgba(226,232,240,0.80) !important;
    border-radius: 0.75rem !important; /* rounded-xl */
    padding: 10px 14px !important;
    font-size: 14px !important;
    box-shadow: inset 0 2px 4px rgba(255,255,255,0.40) !important;
    color: #0F172A !important;
    transition: all 0.3s ease !important;
}
.ts-dropdown {
    background: rgba(255,255,255,0.85) !important;
    backdrop-filter: blur(16px) saturate(180%) !important;
    border: 1px solid rgba(226,232,240,0.80) !important;
    border-radius: 0.75rem !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    color: #0F172A !important;
    margin-top: 4px !important;
}
.ts-dropdown .active {
    background-color: rgba(37,99,235,0.1) !important;
    color: #2563EB !important;
    font-weight: 600 !important;
}
.ts-control > input {
    color: inherit !important;
}

html.dark .ts-control {
    background: rgba(15,23,42,0.60) !important;
    border-color: rgba(255,255,255,0.10) !important;
    color: #E2E8F0 !important;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.20) !important;
}
html.dark .ts-dropdown {
    background: rgba(15,23,42,0.85) !important;
    border-color: rgba(255,255,255,0.10) !important;
    color: #E2E8F0 !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
}
html.dark .ts-dropdown .active {
    background-color: rgba(96,165,250,0.15) !important;
    color: #60A5FA !important;
}

/* 🌟 GLASSMORPHISM FOR FLATPICKR */
.flatpickr-calendar {
    background: rgba(255,255,255,0.90) !important;
    backdrop-filter: blur(16px) saturate(180%) !important;
    border: 1px solid rgba(226,232,240,0.80) !important;
    border-radius: 1rem !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}
html.dark .flatpickr-calendar {
    background: rgba(15,23,42,0.90) !important;
    border-color: rgba(255,255,255,0.10) !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
    color: #E2E8F0 !important;
}
html.dark .flatpickr-day, html.dark .flatpickr-weekday, html.dark .flatpickr-current-month .flatpickr-monthDropdown-months {
    color: #E2E8F0 !important;
}
html.dark .flatpickr-day.selected {
    background: #3B82F6 !important;
    border-color: #3B82F6 !important;
    color: white !important;
}
html.dark .flatpickr-day:hover {
    background: rgba(255,255,255,0.1) !important;
}

/* Fix duplicated select hiding issue */
select.tomselected {
    display: none !important;
}

"""

with open('public/css/glass.css', 'a', encoding='utf-8') as f:
    f.write(glass_css_additions)

