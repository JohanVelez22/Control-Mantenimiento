import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace the classes for Notification and Theme Toggle buttons
old_button_class = 'w-10 h-10 flex items-center justify-center rounded-xl bg-[#1e293b]/30 border border-gray-600/30 hover:bg-gray-700/50 transition-colors group text-lg'
new_button_class = 'w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg'

c = c.replace(old_button_class, new_button_class)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
