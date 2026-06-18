import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

old_logo = r'<!-- Centro: Logo Centrado -->.*?</a>\s*</div>'
new_logo = """<!-- Centro: Logo Centrado -->
                <div class="absolute left-1/2 transform -translate-x-1/2 hidden md:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[20px] font-black tracking-widest hover:scale-105 transition-transform duration-300 font-logo flex items-center gap-2">
                        <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
                        <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                    </a>
                </div>"""
c = re.sub(old_logo, new_logo, c, flags=re.DOTALL)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
