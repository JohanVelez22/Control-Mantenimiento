#!/usr/bin/env python3
"""Apply display:flex fix to modal JS in mantenimientos and electronicas."""

files = [
    "resources/views/mantenimientos/index.blade.php",
    "resources/views/electronicas/index.blade.php",
]

# The exact old pattern (single space indent as seen in the file)
old_open_end = " modal.classList.remove('hidden');"
new_open_end = " modal.style.display = 'flex';\n modal.classList.remove('hidden');"

old_close = " setTimeout(() => modal.classList.add('hidden'), 300);"
new_close = " setTimeout(() => { modal.classList.add('hidden'); modal.style.display = ''; }, 300);"

for path in files:
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        modified = content
        count = 0
        
        if old_open_end in modified:
            modified = modified.replace(old_open_end, new_open_end, 1)
            count += 1
            print(f"  [OK] open fix applied in: {path}")
        else:
            print(f"  [--] open pattern not found in: {path}")
        
        if old_close in modified:
            modified = modified.replace(old_close, new_close)
            count += 1
            print(f"  [OK] close fix applied in: {path}")
        else:
            print(f"  [--] close pattern not found in: {path}")
        
        if count > 0:
            with open(path, 'w', encoding='utf-8', newline='\r\n') as f:
                f.write(modified)
            print(f"  [SAVED] {path}")
    except Exception as e:
        print(f"  [ERROR] {path}: {e}")

print("\nDone.")
