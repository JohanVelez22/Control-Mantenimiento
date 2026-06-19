#!/usr/bin/env python3
"""Remove duplicate openAnularModal/closeAnularModal JS from view files since they're now global in layout."""
import re

files = [
    "resources/views/caja/index.blade.php",
    "resources/views/mantenimientos/index.blade.php",
    "resources/views/electronicas/index.blade.php",
]

# Remove the local JS block that defines these functions
pattern = r"\s*function openAnularModal\(actionUrl\) \{.*?function closeAnularModal\(\) \{.*?\}\s*"

for path in files:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        new_content = re.sub(pattern, '\n', content, flags=re.DOTALL)
        
        if new_content != content:
            with open(path, 'w', encoding='utf-8', newline='\r\n') as f:
                f.write(new_content)
            print(f"  [OK] Removed local JS from: {path}")
        else:
            print(f"  [--] No local JS found in: {path}")
    except Exception as e:
        print(f"  [ERROR] {path}: {e}")

print("Done.")
