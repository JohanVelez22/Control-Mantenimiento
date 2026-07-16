/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/Http/Controllers/**/*.php"
    ],
    theme: {
        extend: {
            colors: {
                ts: {
                    blue: '#2563EB',
                    bluedark: '#1D4ED8',
                    cyan: '#06B6D4',
                    slate: '#0F172A',
                }
            }
        }
    },
    plugins: [],
}
