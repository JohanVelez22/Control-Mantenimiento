// Copia las librerías de interfaz desde node_modules a public/vendor para servirlas
// localmente (la app funciona sin internet). Ejecutar con: npm run vendor:sync
// Las versiones están fijadas en package.json; si cambian, actualizar los hashes SRI
// de resources/views/layouts/{app,consulta}.blade.php y dashboard.blade.php.
import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';

const archivos = [
    ['flatpickr/dist/flatpickr.min.js', 'flatpickr/flatpickr.min.js'],
    ['flatpickr/dist/flatpickr.min.css', 'flatpickr/flatpickr.min.css'],
    ['flatpickr/dist/l10n/es.js', 'flatpickr/l10n/es.js'],
    ['tom-select/dist/js/tom-select.complete.min.js', 'tom-select/tom-select.complete.min.js'],
    ['tom-select/dist/css/tom-select.css', 'tom-select/tom-select.css'],
    ['chart.js/dist/chart.umd.min.js', 'chart.js/chart.umd.min.js'],
];

for (const [origen, destino] of archivos) {
    const ruta = join('public', 'vendor', destino);
    mkdirSync(dirname(ruta), { recursive: true });
    copyFileSync(join('node_modules', origen), ruta);
    console.log(`✓ ${ruta}`);
}
