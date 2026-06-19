#!/usr/bin/env python3
"""Fix modal CSS to use display:flex !important so Tailwind's hidden class doesn't break centering."""

path = "public/css/glass.css"

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

old = """.ts-modal-overlay {
  position: fixed; inset: 0; z-index: 200;
  display: flex; align-items: center; justify-content: center; padding: 16px;
  background: rgba(0,0,0,0.40);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}"""

new = """.ts-modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  display: flex !important; align-items: center; justify-content: center; padding: 16px;
  background: rgba(0,0,0,0.50);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: opacity 0.3s ease, visibility 0.3s ease;
}
.ts-modal-overlay.hidden {
  opacity: 0 !important;
  pointer-events: none !important;
  visibility: hidden !important;
  display: flex !important;
}"""

if old in content:
    content = content.replace(old, new)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("[OK] Modal overlay CSS fixed.")
else:
    print("[!!] Pattern not found. Searching for partial match...")
    # Try to find what's actually there
    idx = content.find('.ts-modal-overlay {')
    if idx >= 0:
        print(f"Found at char {idx}:")
        print(repr(content[idx:idx+300]))
    else:
        print("Class not found at all!")
