#!/usr/bin/env python3
"""
Fix 1: Remove destroy forms from mantenimientos and electronicas views (route was removed).
Fix 2: Remove local anular modal from caja, mantenimientos, electronicas (will use global modal from layout).
"""

import re

# ─── FIX 1: Remove destroy form blocks from views ────────────────────────────

def remove_destroy_form(filepath, route_name):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Match: @if(auth()->user()->isAdmin()) ... form with destroy ... @endif
    pattern = r"\s*@if\(auth\(\)->user\(\)->isAdmin\(\)\)\s*<form action=\"\{\{ route\('" + re.escape(route_name) + r"'.*?</form>\s*@endif"
    new_content = re.sub(pattern, '', content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8', newline='\r\n') as f:
            f.write(new_content)
        print(f"  [OK] Removed destroy form from: {filepath}")
    else:
        print(f"  [--] Pattern not found in: {filepath}")

remove_destroy_form("resources/views/mantenimientos/index.blade.php", "mantenimientos.destroy")
remove_destroy_form("resources/views/electronicas/index.blade.php", "electronicas.destroy")

# ─── FIX 2: Remove local anular modals from caja, mantenimientos, electronicas ──────

def remove_local_modal(filepath, modal_id):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove the modal div block
    pattern = r"\{\{--.*?--\}\}\s*<div id=\"" + re.escape(modal_id) + r"\".*?</div>\s*</div>\s*</div>\s*"
    new_content = re.sub(pattern, '', content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8', newline='\r\n') as f:
            f.write(new_content)
        print(f"  [OK] Removed local modal from: {filepath}")
    else:
        print(f"  [--] Modal pattern not found in: {filepath}")

remove_local_modal("resources/views/caja/index.blade.php", "pwd-anular-modal")
remove_local_modal("resources/views/mantenimientos/index.blade.php", "pwd-anular-modal")
remove_local_modal("resources/views/electronicas/index.blade.php", "pwd-anular-modal")

# ─── FIX 3: Update JS in those views to use global modal functions ────────────
# The global layout will provide openAnularModal() / closeAnularModal() via @push('scripts')

print("\nDone.")
