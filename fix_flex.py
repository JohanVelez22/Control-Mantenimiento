import os, re
for r, d, files in os.walk('resources/views'):
    for f in files:
        if f.endswith('.blade.php'):
            path = os.path.join(r, f)
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
            # Replace the flex actions div with grid
            new_content = re.sub(r'class="flex (?:flex-wrap )?justify-center gap-[0-9\.]+ max-w-\[[0-9]+px\] mx-auto"', 'class="grid grid-cols-2 gap-1.5 justify-center mx-auto w-fit"', content)
            if new_content != content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                print(f"Updated {path}")
