# Guía de Arquitectura CSS Modular: Liquid Glass Design System
**Tecni-Systemas — Plantilla Pionera para Proyectos Laravel**

---

## 1. Filosofía de Diseño

El sistema visual **Liquid Glass** de Tecni-Systemas fue concebido para romper con interfaces corporativas rígidas o planas, ofreciendo una experiencia moderna, inmersiva y de alta gama mediante:
- **Efectos Glassmorphism Calibrados:** Uso de fondos translúcidos (`rgba`), bordes sutiles y filtros de desenfoque por hardware (`backdrop-filter: blur(28px) saturate(180%)`).
- **Paleta de Color Curada:** Azul eléctrico (`#2563EB`), cian tecnológico (`#06B6D4`), azul noche profundo (`#0F172A`) y blanco nítido (`#FFFFFF`), evitando tonos neón estridentes.
- **Soporte Nativo de Tema Claro y Oscuro:** Sin necesidad de duplicar clases ni recargar vistas, mediante el selector `.dark` y variables CSS en cascada.
- **Micro-animaciones Suaves:** Transiciones de 200ms calibradas con curvas `cubic-bezier(0.4, 0, 0.2, 1)`.

---

## 2. Estructura Modular de Archivos

Para facilitar el mantenimiento, la escalabilidad y permitir que este proyecto sea la **plantilla base o pionera** para futuros desarrollos, los estilos se dividieron en módulos temáticos desacoplados dentro de `resources/css/glass/`:

```
resources/css/glass/
├── _tokens.css       # Design Tokens: Paleta, fuentes (Inter/Michroma/Orbitron), scrollbars y variables
├── _cards.css        # Paneles .glass, contenedores .glass-card, estados hover y variantes de color
├── _sidebar.css      # Barra lateral de navegación: colapsada, expandida, estado móvil y marcas
├── _topbar.css       # Barra de encabezado superior, buscador, panel de usuario y notificaciones
├── _buttons.css      # Botones .btn-primary, .btn-danger, .btn-ghost-* y animaciones de brillo líquido
├── _forms.css        # Entradas .glass-input, spinners numéricos, selects y etiquetas de formulario
├── _tables.css       # Tablas de datos de alta densidad, scroll horizontal seguro y rayado zebra
├── _pills.css        # Insignias de estado (Pendiente, Entregado, Anulado, Cotizado)
├── _print.css        # Optimización para exportación e impresión térmica POS / hojas A4 (@media print)
├── _modals.css       # Overlays translúcidos, diálogos accesibles (ARIA) y lightbox de imágenes
├── _animations.css   # Keyframes globales, accesibilidad de foco visible y transiciones
├── _responsive.css   # Protección para zoom 150-200% y adaptación de toolbars en pantallas compactas
├── _tomselect.css    # Personalización Liquid Glass para el selector inteligente TomSelect
├── _flatpickr.css    # Personalización Liquid Glass para el calendario interactivo Flatpickr
├── _utilities.css    # Utilidades complementarias, reportes, cierres y componentes específicos
└── glass.css         # Manifiesto maestro compilado por Vite hacia producción
```

---

## 3. Descripción de los Módulos

### `_tokens.css`
Declara los tokens de diseño unificados en `:root`:
- `--ts-blue`: `#2563EB`
- `--ts-blue-dark`: `#1D4ED8`
- `--ts-cyan`: `#06B6D4`
- `--ts-slate`: `#0F172A`
- Variables de modo claro (`--glass-bg`, `--glass-border`, `--glass-shadow`).
- Variables de modo oscuro (`--glass-dark-bg`, `--glass-dark-border`, `--glass-dark-shadow`).

### `_base.css`
- Scrollbar global unificado con los menús TomSelect y Flatpickr.
- Reset de caja (`box-sizing: border-box`).
- Mesh de fondo con gradientes radiales elípticos (`.ts-bg`), adaptables a `.dark`.

### `_cards.css`
- Contenedores de tarjeta con esquinas redondeadas (`22px`).
- Reglas específicas para tarjetas anidadas sin sobre-saturación de brillo.
- Efectos de iluminación sutil al pasar el cursor (`hover-glow`).

### `_sidebar.css`
- Ancho colapsado (72px) y expandido (210px).
- Transición fluida con iconos centrados en modo desktop.
- Menú lateral deslizante en dispositivos móviles (`< 1024px`) con backdrop oscuro.

### `_topbar.css`
- Encabezado con efecto frosted glass sticky.
- Panel desplegable para notificaciones (`#notif-panel`) con scrollbar integrado.

### `_buttons.css`
- Botones de acción principales, secundarios y de advertencia.
- Estilos `.btn-ghost-*` con bordes sutiles y brillo especular líquido en hover.
- Cuadrados de acción compactos (`.btn-action-square`).

### `_forms.css`
- Inputs con fondo de vidrio (`.glass-input`) y bordes iluminados al enfocar (`focus:ring`).
- Personalización completa para librerías externas: **TomSelect** y **Flatpickr**.

### `_tables.css`
- Tablas adaptativas con encabezados semi-transparentes.
- Contenedores de tabla con desplazamiento horizontal fluido y protección contra cortes en móviles.
- Resaltado de filas en cotizaciones, ventas, mantenimientos y caja.

### `_modals.css`
- Ventanas modales centradas (`.ts-modal-overlay`, `.ts-modal-card`).
- Diálogos ARIA (`role="dialog"`, `aria-modal="true"`) con transiciones fade/scale.

### `_pills.css`
- Badges semánticos para estados: verde (completado/activo), ámbar (pendiente/proceso), rojo (anulado/baja).

### `_print.css`
- Reglas `@media print` depuradas: fondo blanco puro, ocultación de menús y barras laterales, formato de ticket para comprobantes y tamaño estándar para fichas A4.

---

## 4. Cómo Adoptar este Sistema en Futuros Proyectos

Para replicar esta arquitectura en otro proyecto Laravel:

1. **Copiar la carpeta modular:** Copiar `resources/css/glass/` a la nueva aplicación.
2. **Importar en el pipeline de Vite (Opcional):**
   ```css
   /* En resources/css/app.css */
   @tailwind base;
   @tailwind components;
   @tailwind utilities;

   @import './glass/glass.css';
   ```
3. **Servir como archivo estático unificado (Producción sin FOUC):**
   - El archivo consolidado `public/css/glass.css` se incluye directamente en `<head>`:
     ```blade
     <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ @filemtime(public_path('css/glass.css')) ?: '1.0' }}">
     ```
   - Esto previene cualquier problema de carga tardía de estilos (FOUC) o fallos de manifiesto Vite en despliegues estáticos.

---

*Tecni-Systemas — Ingeniería de Software y Diseño Visual de Excelencia.*
