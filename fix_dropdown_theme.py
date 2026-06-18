import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Make notification dropdown responsive to light/dark mode
old_dropdown_container = 'id="notif-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-[#111827] border border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden"'
new_dropdown_container = 'id="notif-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-white dark:bg-[#111827] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden"'
c = c.replace(old_dropdown_container, new_dropdown_container)

old_dropdown_header = 'class="p-4 border-b border-gray-800 bg-[#0B1121]"'
new_dropdown_header = 'class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#0B1121]"'
c = c.replace(old_dropdown_header, new_dropdown_header)

old_dropdown_header_text = 'class="font-bold text-sm text-white"'
new_dropdown_header_text = 'class="font-bold text-sm text-slate-800 dark:text-white"'
c = c.replace(old_dropdown_header_text, new_dropdown_header_text)

old_dropdown_item = 'class="text-sm p-3 hover:bg-[#1F2937] rounded-xl cursor-pointer flex justify-between items-center mb-1 transition-colors"'
new_dropdown_item = 'class="text-sm p-3 hover:bg-gray-100 dark:hover:bg-[#1F2937] rounded-xl cursor-pointer flex justify-between items-center mb-1 transition-colors"'
c = c.replace(old_dropdown_item, new_dropdown_item)

old_dropdown_item_text = 'class="text-gray-300"'
new_dropdown_item_text = 'class="text-gray-600 dark:text-gray-300"'
c = c.replace(old_dropdown_item_text, new_dropdown_item_text)

old_dropdown_item_bold = 'class="text-white"'
new_dropdown_item_bold = 'class="text-slate-800 dark:text-white"'
c = c.replace(old_dropdown_item_bold, new_dropdown_item_bold)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)

