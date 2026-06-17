import os
import re

directory = r"C:\ServBay\www\control-mantenimiento-equipos\resources\views"

# Pattern 1: find glass cards that are flex flex-col justify-center
pattern1 = re.compile(r'(class="[^"]*glass-card[^"]*flex flex-col justify-center(?! items-center)[^"]*)')
# Pattern 2: find glass cards that are flex flex-col justify-center items-center but missing text-center
pattern2 = re.compile(r'(class="[^"]*glass-card[^"]*flex flex-col justify-center items-center(?! text-center)[^"]*)')
# Pattern 3: find card titles that have flex items-center gap-1.5 or gap-2 but missing justify-center
pattern3 = re.compile(r'(class="[^"]*uppercase tracking-widest[^"]*flex items-center(?! justify-center)[^"]*)')

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith(".blade.php"):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            orig_content = content
            
            # Add items-center text-center to pattern 1
            content = pattern1.sub(r'\1 items-center text-center', content)
            
            # Add text-center to pattern 2
            content = pattern2.sub(r'\1 text-center', content)
            
            # Add justify-center to pattern 3
            content = pattern3.sub(r'\1 justify-center', content)
            
            if content != orig_content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated {filepath}")

print("Done.")
