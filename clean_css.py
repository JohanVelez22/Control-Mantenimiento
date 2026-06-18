import os

filepath = 'public/css/glass.css'
with open(filepath, 'rb') as f:
    content = f.read()

# Replace UTF-16 spaces/null bytes if any and remove the bad line completely
# The bad line started with "i n p u t"
# We can just decode with utf-8, ignoring errors, or just clean it up.
text = content.decode('utf-8', errors='ignore')
lines = text.split('\n')
clean_lines = []
for line in lines:
    if 'i n p u t' in line or '- w e b k i t' in line:
        continue
    clean_lines.append(line)

final_text = '\n'.join(clean_lines)

final_text += """
/* ─── Correccion de Autocompletado del Navegador (Chrome/Edge) ─── */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active {
    transition: background-color 5000s ease-in-out 0s !important;
    -webkit-text-fill-color: inherit !important;
}

html.dark input:-webkit-autofill,
html.dark input:-webkit-autofill:hover, 
html.dark input:-webkit-autofill:focus, 
html.dark input:-webkit-autofill:active {
    -webkit-text-fill-color: #f8fafc !important;
}
"""

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(final_text)

print("glass.css cleaned and updated.")
