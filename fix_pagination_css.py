import re

css_fix = """
/* 🌟 GLASSMORPHISM FOR PAGINATION AND OVERFLOW FIXES */
nav[role="navigation"] {
    display: flex !important;
    justify-content: center !important;
    width: 100% !important;
    overflow-x: auto !important;
    padding-bottom: 0.5rem !important;
}
nav[role="navigation"] svg {
    width: 1.25rem !important;
    height: 1.25rem !important;
}
nav[role="navigation"] > div {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}
nav[role="navigation"] p {
    margin-bottom: 0 !important;
    color: #64748b !important;
}
html.dark nav[role="navigation"] p {
    color: #94a3b8 !important;
}
nav[role="navigation"] span, 
nav[role="navigation"] a {
    background: rgba(255,255,255,0.60) !important;
    backdrop-filter: blur(8px) !important;
    border: 1px solid rgba(226,232,240,0.80) !important;
    color: #0F172A !important;
    border-radius: 0.5rem !important;
    padding: 0.5rem 0.75rem !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
}
nav[role="navigation"] a:hover {
    background: rgba(255,255,255,0.90) !important;
    border-color: rgba(37,99,235,0.5) !important;
    color: #2563EB !important;
}
nav[role="navigation"] span[aria-current="page"] > span {
    background: #2563EB !important;
    color: white !important;
    border-color: #2563EB !important;
    font-weight: bold !important;
}
html.dark nav[role="navigation"] span, 
html.dark nav[role="navigation"] a {
    background: rgba(15,23,42,0.60) !important;
    border-color: rgba(255,255,255,0.10) !important;
    color: #E2E8F0 !important;
}
html.dark nav[role="navigation"] a:hover {
    background: rgba(30,41,59,0.80) !important;
    border-color: rgba(96,165,250,0.5) !important;
    color: #60A5FA !important;
}
"""

with open('public/css/glass.css', 'a', encoding='utf-8') as f:
    f.write(css_fix)
