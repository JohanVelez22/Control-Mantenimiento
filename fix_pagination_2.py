with open('resources/views/vendor/pagination/tailwind.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace('<nav role="navigation"', '<nav role="navigation" aria-label="{{ __(\'Pagination Navigation\') }}" class="-mt-2 relative z-10"')

with open('resources/views/vendor/pagination/tailwind.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
