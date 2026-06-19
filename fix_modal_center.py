#!/usr/bin/env python3
# fix_modal_center.py - Fix modal centering WITHOUT corrupting UTF-8

import re

files = [
    "resources/views/mantenimientos/index.blade.php",
    "resources/views/electronicas/index.blade.php",
]

old = 'class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300"'
new = 'class="ts-modal-overlay hidden opacity-0"'

for path in files:
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    updated = content.replace(old, new)
    if updated != content:
        with open(path, 'w', encoding='utf-8', newline='\r\n') as f:
            f.write(updated)
        print(f"  [OK] Fixed: {path}")
    else:
        print(f"  [--] No change needed: {path}")

print("Done.")
