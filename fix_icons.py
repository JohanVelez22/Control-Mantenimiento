import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Replace Theme Toggle button
theme_pattern = r'<!-- Theme Toggle -->\s*<button id="theme-toggle".*?</button>'
new_theme = """<!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#1e293b]/30 border border-gray-600/30 hover:bg-gray-700/50 transition-colors group text-lg">
                        <span class="dark:hidden">🌞</span>
                        <span class="hidden dark:inline">🌙</span>
                    </button>"""
c = re.sub(theme_pattern, new_theme, c, flags=re.DOTALL)


# Replace Logout button inner text to SVG
logout_pattern = r'<!-- Logout -->\s*<form action="\{\{ route\(\'logout\'\) \}\}" method="POST" class="m-0 pl-1">\s*@csrf\s*<button type="submit" class="([^"]+)" title="Cerrar Sesión">\s*🚪\s*</button>\s*</form>'
new_logout = """<!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="\\1" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>"""
c = re.sub(logout_pattern, new_logout, c)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
