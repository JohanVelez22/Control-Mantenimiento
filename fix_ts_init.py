import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Update TomSelect initialization to include no-search (but without search bar)
old_ts_init = """              // 2. Tom Select para selects con clase glass-input (con buscador habilitado)
              document.querySelectorAll("select.glass-input").forEach((el) => {
                  if (!el.classList.contains('tomselected') && !el.classList.contains('no-search')) {"""
new_ts_init = """              // 2. Tom Select para selects con clase glass-input
              document.querySelectorAll("select.glass-input").forEach((el) => {
                  if (!el.classList.contains('tomselected')) {
                      let isNoSearch = el.classList.contains('no-search');"""
c = c.replace(old_ts_init, new_ts_init)

old_ts_config = """                          allowEmptyOption: true,
                          render: {"""
new_ts_config = """                          allowEmptyOption: true,
                          controlInput: isNoSearch ? null : undefined,
                          render: {"""
c = c.replace(old_ts_config, new_ts_config)

# 2. Add cache busting to glass.css
old_css_link = """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">"""
new_css_link = """<link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ time() }}">"""
c = c.replace(old_css_link, new_css_link)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)

