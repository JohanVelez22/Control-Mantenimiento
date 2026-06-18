import os
import re

filepath = 'public/css/glass.css'
with open(filepath, 'rb') as f:
    content = f.read()

# Replace null bytes
content = content.replace(b'\x00', b'')

# Decode to string
text = content.decode('utf-8', errors='ignore')

# Find the last valid block before the garbage
# We know the last valid block ends with:
# html.dark .flatpickr-day.flatpickr-disabled {
#     color: #475569 !important;
# }
marker = "html.dark .flatpickr-day.flatpickr-disabled {\r\n    color: #475569 !important;\r\n}"
if marker not in text:
    marker = "html.dark .flatpickr-day.flatpickr-disabled {\n    color: #475569 !important;\n}"

if marker in text:
    text = text[:text.find(marker) + len(marker)]
else:
    print("Marker not found, leaving text as is")

# Add the autofill fix
autofill_css = """

/* ─── Correccion de Autocompletado del Navegador ─── */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px rgba(255, 255, 255, 0.9) inset !important;
    -webkit-text-fill-color: #1e293b !important; /* text-slate-800 */
    transition: background-color 5000s ease-in-out 0s !important;
}

html.dark input:-webkit-autofill,
html.dark input:-webkit-autofill:hover, 
html.dark input:-webkit-autofill:focus, 
html.dark input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px rgba(15, 23, 42, 0.9) inset !important;
    -webkit-text-fill-color: #f8fafc !important; /* text-slate-50 */
    transition: background-color 5000s ease-in-out 0s !important;
}
"""

text += autofill_css

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(text)

print("CSS cleaned and fixed.")
