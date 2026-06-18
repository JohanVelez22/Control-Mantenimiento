import os

filepath = 'public/css/glass.css'
with open(filepath, 'a', encoding='utf-8') as f:
    f.write("""

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
""")
