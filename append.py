with open('resources/views/layouts/app.blade.php', 'a', encoding='utf-8') as f:
    f.write('''
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if(window.location.hash) {
            const target = document.querySelector(window.location.hash);
            if(target) {
                setTimeout(() => {
                    target.scrollIntoView({behavior: "smooth", block: "center"});
                    target.classList.add("ring-4", "ring-blue-500", "transition-all", "duration-1000");
                    setTimeout(() => target.classList.remove("ring-4", "ring-blue-500"), 2500);
                }, 500);
            }
        }
    });
</script>
''')
