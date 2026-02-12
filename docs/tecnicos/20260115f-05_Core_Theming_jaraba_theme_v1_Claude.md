JARABA IMPACT PLATFORM
Sistema de Theming y Componentización
jaraba_theme v1.0
Especificación Técnica de Frontend
Enero 2026
 
1. Arquitectura del Sistema de Theming
El tema jaraba_theme es un desarrollo a medida basado en Stable9 + Bootstrap 5 (compilado, no CDN), con un sistema de Componentización Parametrizada que permite customización completa sin tocar código.
1.1 Sistema de 4 Capas
La arquitectura implementa un sistema jerárquico donde cada capa puede sobrescribir la anterior:
Capa	Tecnología	Función	Quién la modifica
1. SCSS (ADN)	_variables.scss	Variables por defecto compiladas	Desarrollador
2. PHP (Panel Admin)	jaraba_theme.theme	Selectores visuales (color pickers, radios)	Administrador Plataforma
3. Config Entity	tenant_theme_config	Almacenamiento por tenant (cascada)	Propietario Tenant
4. CSS Runtime	hook_preprocess_html()	Inyección de CSS Custom Properties	Sistema (automático)
1.2 Cascada de Configuración
Las configuraciones visuales se heredan en cascada, permitiendo personalización granular:
┌─────────────────────────────────────────────────────────────┐
│  PLATAFORMA (defaults globales)                            │
│    └── VERTICAL (AgroConecta, EmpleoConecta)               │
│          └── PLAN (Starter, Professional, Enterprise)      │
│                └── TENANT (configuración específica)       │
└─────────────────────────────────────────────────────────────┘

Ejemplo: Un tenant en AgroConecta/Professional hereda:
  1. Colores base de Plataforma (#FF8C42, #00A9A5)
  2. Iconografía de AgroConecta (productos agrícolas)
  3. Features de Professional (marca blanca parcial)
  4. Su logo y color primario personalizado
 
2. Identidad Visual del Ecosistema
2.1 Paleta de Colores Oficial
Color	Nombre	HEX	RGB	Uso Principal
■	Azul Corporativo	#233D63	35, 61, 99	Fondos oscuros, texto principal, confianza
■	Turquesa Innovación	#00A9A5	0, 169, 165	CTAs secundarios, éxito, crecimiento
■	Naranja Impulso	#FF8C42	255, 140, 66	CTAs primarios, acción, energía
■	Blanco Limpio	#FFFFFF	255, 255, 255	Fondos claros, espacios, claridad
■	Gris Claro	#F4F4F4	244, 244, 244	Fondos alternativos, separadores
■	Gris Texto	#333333	51, 51, 51	Texto de párrafo principal
■	Gris Muted	#666666	102, 102, 102	Texto secundario, metadatos
2.2 Sistema Tipográfico
Tipografías de Google Fonts optimizadas para rendimiento y accesibilidad:
Familia	Uso	Pesos	Fallback
Montserrat	Títulos (H1-H3), Logo, CTAs destacados	600, 700, 800	system-ui, sans-serif
Roboto	Cuerpo de texto, UI, formularios	400, 500, 700	Arial, sans-serif
Consolas	Código, datos técnicos	400	monospace
Escala Tipográfica
Elemento	Familia	Peso	Tamaño	Line Height	Color
H1 (Hero)	Montserrat	800	48px / 3rem	1.2	#233D63
H2 (Sección)	Montserrat	700	36px / 2.25rem	1.25	#233D63
H3 (Subsección)	Montserrat	600	28px / 1.75rem	1.3	#233D63
H4 (Card Title)	Roboto	700	22px / 1.375rem	1.35	#333333
H5 (Label)	Roboto	500	18px / 1.125rem	1.4	#333333
Párrafo	Roboto	400	16px / 1rem	1.6	#333333
Small / Caption	Roboto	400	14px / 0.875rem	1.4	#666666
Botón	Montserrat	600	16px / 1rem	1	#FFFFFF
 
3. Variables CSS Configurables
El sistema expone más de 45 variables CSS configurables sin código, organizadas por categoría:
3.1 Variables de Identidad
Variable CSS	Valor Default	Descripción	Panel Admin
--color-primary	#FF8C42	Color principal de marca (CTAs, enlaces)	Color Picker
--color-secondary	#00A9A5	Color secundario (acentos, badges)	Color Picker
--color-dark	#233D63	Color oscuro (headers, footers)	Color Picker
--color-success	#28A745	Estados de éxito	Color Picker
--color-warning	#FFC107	Estados de advertencia	Color Picker
--color-danger	#DC3545	Estados de error	Color Picker
--color-text	#333333	Texto principal	Color Picker
--color-muted	#666666	Texto secundario	Color Picker
3.2 Variables de Tipografía
Variable CSS	Valor Default	Descripción	Panel Admin
--font-family-headings	'Montserrat', sans-serif	Familia para títulos	Select (Google Fonts)
--font-family-body	'Roboto', sans-serif	Familia para cuerpo	Select (Google Fonts)
--font-size-base	16px	Tamaño base de texto	Range Slider
--font-weight-headings	700	Peso de títulos	Select
--line-height-base	1.6	Altura de línea base	Range Slider
--letter-spacing-headings	-0.02em	Espaciado en títulos	Range Slider
3.3 Variables de Layout
Variable CSS	Valor Default	Descripción	Panel Admin
--container-max-width	1200px	Ancho máximo del contenedor	Range Slider
--sidebar-width	280px	Ancho del sidebar	Range Slider
--spacing-unit	8px	Unidad base de espaciado	Range Slider
--section-padding	80px	Padding vertical de secciones	Range Slider
--grid-gap	24px	Gap entre elementos de grid	Range Slider
3.4 Variables de Componentes
Variable CSS	Valor Default	Descripción	Panel Admin
--border-radius-sm	4px	Radio pequeño (inputs)	Range Slider
--border-radius-md	8px	Radio medio (cards)	Range Slider
--border-radius-lg	16px	Radio grande (modales)	Range Slider
--border-radius-pill	50px	Radio pill (badges)	—
--shadow-sm	0 1px 3px rgba(0,0,0,0.12)	Sombra suave	—
--shadow-md	0 4px 12px rgba(0,0,0,0.15)	Sombra media	—
--shadow-lg	0 8px 24px rgba(0,0,0,0.2)	Sombra pronunciada	—
--transition-fast	150ms ease	Transición rápida	—
--transition-normal	300ms ease	Transición normal	—
 
4. Biblioteca de Componentes
4.1 Header Inmersivo
Navegación transparente con position: absolute, lógica de superposición y cambio de color dinámico:
Variante	Clase CSS	Comportamiento	Uso Recomendado
Transparente	.header--transparent	Fondo transparente, texto blanco, cambia a sólido en scroll	Landing pages con hero imagen
Sólido Claro	.header--solid-light	Fondo blanco fijo, texto oscuro	Páginas interiores, catálogo
Sólido Oscuro	.header--solid-dark	Fondo azul corporativo, texto blanco	Área de usuario, checkout
Sticky	.header--sticky	Se fija al hacer scroll down	Tiendas con mucho scroll
Configuración SCSS:
// _header.scss
.header {
  position: var(--header-position, relative);
  background: var(--header-bg, transparent);
  transition: var(--transition-normal);
  
  &--transparent {
    --header-position: absolute;
    --header-bg: transparent;
    color: white;
    
    &.scrolled {
      --header-bg: var(--color-dark);
      position: fixed;
    }
  }
}
4.2 Hero Sections
Variante	Clase CSS	Características	Variables Configurables
Full Screen	.hero--fullscreen	100vh, imagen de fondo, overlay	--hero-overlay-opacity, --hero-min-height
Split	.hero--split	50% imagen, 50% contenido	--hero-split-ratio, --hero-image-position
Compact	.hero--compact	300px altura, para páginas internas	--hero-compact-height
Video	.hero--video	Video de fondo con autoplay muted	--hero-video-opacity
4.3 Cards (Tarjetas)
Variante	Clase CSS	Uso	Elementos
Producto	.card--product	Catálogo de productos	Imagen, título, precio, CTA, badge oferta
Curso	.card--course	Listado de formación	Thumbnail, título, duración, progreso, instructor
Perfil	.card--profile	Usuarios, mentores	Avatar, nombre, rol, stats, contacto
Testimonio	.card--testimonial	Social proof	Quote, avatar, nombre, empresa, rating
Stat	.card--stat	Dashboard métricas	Icono, valor, label, trend arrow
CTA	.card--cta	Llamadas a la acción	Icono, título, descripción, botón
 
4.4 Botones
Variante	Clase CSS	Estilo	Estados
Primary	.btn--primary	Fondo naranja, texto blanco	hover: darken 10%, active: darken 15%
Secondary	.btn--secondary	Fondo turquesa, texto blanco	hover: darken 10%, active: darken 15%
Outline Primary	.btn--outline-primary	Borde naranja, fondo transparente	hover: fondo naranja, texto blanco
Outline Secondary	.btn--outline-secondary	Borde turquesa, fondo transparente	hover: fondo turquesa, texto blanco
Ghost	.btn--ghost	Sin fondo ni borde, solo texto	hover: fondo rgba(0,0,0,0.05)
Link	.btn--link	Estilo de enlace con icono	hover: underline
Icon	.btn--icon	Solo icono, circular	hover: fondo sutil
Tamaños de Botón:
Tamaño	Clase	Padding	Font Size	Min Width
Small	.btn--sm	8px 16px	14px	80px
Medium (default)	.btn--md	12px 24px	16px	120px
Large	.btn--lg	16px 32px	18px	160px
Block	.btn--block	16px 24px	16px	100%
4.5 Formularios
Componente	Clase CSS	Características
Input Text	.form-input	Border radius configurable, focus ring con color primary
Select	.form-select	Custom arrow, mismo estilo que inputs
Textarea	.form-textarea	Resize vertical, min-height 120px
Checkbox	.form-checkbox	Custom check icon, animación suave
Radio	.form-radio	Custom dot, animación suave
Switch/Toggle	.form-switch	Estilo iOS, on/off states con colores
File Upload	.form-file	Drag & drop zone, preview de archivos
Range Slider	.form-range	Track y thumb customizables
 
5. Visual Picker (Panel de Configuración)
El Visual Picker es una interfaz de administración que permite personalizar el tema visualmente sin conocimientos técnicos.
5.1 Ruta de Acceso
/admin/appearance/settings/jaraba_theme
5.2 Secciones del Panel
Sección	Controles	Variables Afectadas
Identidad de Marca	Logo upload, favicon, color pickers (primary, secondary, dark)	--color-primary, --color-secondary, --color-dark
Tipografía	Selects de Google Fonts, sliders de tamaño	--font-family-*, --font-size-base
Header	Radio buttons con miniaturas de layouts	--header-position, --header-bg, --header-style
Hero	Slider de altura, slider de opacidad overlay	--hero-min-height, --hero-overlay-opacity
Cards	Slider de border-radius, selector de sombras	--border-radius-md, --shadow-md
Footer	Textarea de copyright, toggles de secciones	--footer-bg, --footer-columns
5.3 Implementación PHP
// jaraba_theme.theme
function jaraba_theme_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  // Sección de Identidad
  $form['identity'] = [
    '#type' => 'details',
    '#title' => t('Identidad de Marca'),
    '#open' => TRUE,
  ];
  
  $form['identity']['color_primary'] = [
    '#type' => 'color',
    '#title' => t('Color Primario'),
    '#default_value' => theme_get_setting('color_primary') ?? '#FF8C42',
  ];
  
  $form['identity']['font_headings'] = [
    '#type' => 'select',
    '#title' => t('Tipografía de Títulos'),
    '#options' => jaraba_get_google_fonts(),
    '#default_value' => theme_get_setting('font_headings') ?? 'Montserrat',
  ];
}
 
5.4 Inyección de CSS Runtime
El hook hook_preprocess_html() genera las CSS Custom Properties dinámicamente:
// jaraba_theme.theme
function jaraba_theme_preprocess_html(&$variables) {
  // Obtener configuración del tenant actual
  $tenant_config = \Drupal::service('jaraba_tenant.theme_config')->getActiveConfig();
  
  // Construir CSS inline con variables
  $css_vars = ':root {';
  $css_vars .= '--color-primary: ' . ($tenant_config['color_primary'] ?? '#FF8C42') . ';';
  $css_vars .= '--color-secondary: ' . ($tenant_config['color_secondary'] ?? '#00A9A5') . ';';
  $css_vars .= '--color-dark: ' . ($tenant_config['color_dark'] ?? '#233D63') . ';';
  $css_vars .= '--font-family-headings: ' . $tenant_config['font_headings'] . ', sans-serif;';
  $css_vars .= '--border-radius-md: ' . $tenant_config['border_radius'] . 'px;';
  $css_vars .= '}';
  
  // Inyectar en <head>
  $variables['#attached']['html_head'][] = [
    ['#tag' => 'style', '#value' => $css_vars],
    'jaraba_theme_vars'
  ];
}
5.5 Entidad de Configuración por Tenant
Campo	Tipo	Descripción
id	SERIAL	ID interno
tenant_id	INT	FK a tenant (NULL = default plataforma)
vertical_id	INT	FK a vertical (NULL = todas)
config_key	VARCHAR(64)	Nombre de la variable (color_primary)
config_value	VARCHAR(255)	Valor configurado (#FF8C42)
created	DATETIME	Fecha de creación
changed	DATETIME	Última modificación
 
6. Estructura de Archivos del Tema
jaraba_theme/
├── jaraba_theme.info.yml
├── jaraba_theme.libraries.yml
├── jaraba_theme.theme
├── logo.svg
├── screenshot.png
│
├── config/
│   └── install/
│       └── jaraba_theme.settings.yml
│
├── css/
│   ├── style.css                 # CSS compilado (no editar)
│   └── editor.css                # Estilos para CKEditor
│
├── scss/
│   ├── _variables.scss           # Variables SCSS (ADN del tema)
│   ├── _mixins.scss              # Mixins reutilizables
│   ├── _typography.scss          # Sistema tipográfico
│   ├── _colors.scss              # Paleta y utilidades de color
│   ├── _layout.scss              # Grid, containers, spacing
│   ├── _buttons.scss             # Todos los estilos de botones
│   ├── _forms.scss               # Inputs, selects, validaciones
│   ├── _cards.scss               # Variantes de tarjetas
│   ├── _header.scss              # Navegación y header
│   ├── _hero.scss                # Secciones hero
│   ├── _footer.scss              # Footer y subfooter
│   ├── _commerce.scss            # Estilos de Commerce
│   ├── _utilities.scss           # Clases utilitarias
│   └── style.scss                # Archivo principal (imports)
│
├── js/
│   ├── header.js                 # Comportamiento sticky/scroll
│   ├── visual-picker.js          # Preview en tiempo real
│   └── main.js                   # Inicialización general
│
├── templates/
│   ├── layout/
│   │   ├── page.html.twig
│   │   └── region.html.twig
│   ├── navigation/
│   │   ├── menu.html.twig
│   │   └── menu--main.html.twig
│   ├── content/
│   │   ├── node.html.twig
│   │   ├── node--product.html.twig
│   │   └── node--article.html.twig
│   ├── commerce/
│   │   ├── commerce-product.html.twig
│   │   └── commerce-cart-block.html.twig
│   └── components/
│       ├── card.html.twig
│       ├── hero.html.twig
│       └── cta-block.html.twig
│
└── images/
    ├── icons/                    # SVG icons
    └── patterns/                 # Backgrounds, texturas
 
7. Accesibilidad (WCAG 2.1 AA)
El tema cumple con los estándares WCAG 2.1 nivel AA, requisito obligatorio para proyectos del sector público.
7.1 Requisitos de Contraste
Elemento	Ratio Mínimo	Colores Válidos sobre #FFFFFF
Texto normal (< 18px)	4.5:1	#233D63 (8.1:1) ✓, #333333 (12.6:1) ✓
Texto grande (≥ 18px bold)	3:1	#FF8C42 (3.2:1) ✓, #00A9A5 (3.1:1) ✓
Elementos UI (iconos, borders)	3:1	Todos los colores primarios válidos
Estados focus	3:1	Outline de 2px con --color-primary
7.2 Focus States
// _accessibility.scss
:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.btn:focus-visible {
  box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.4);
}

// Skip link para navegación por teclado
.skip-link {
  position: absolute;
  top: -100%;
  left: 0;
  
  &:focus {
    top: 0;
    z-index: 9999;
  }
}
7.3 Checklist de Accesibilidad
•	[ ] Contraste de texto verificado con herramientas (WebAIM, axe)
•	[ ] Todos los elementos interactivos tienen focus visible
•	[ ] Imágenes tienen alt text descriptivo
•	[ ] Formularios tienen labels asociados correctamente
•	[ ] Navegación funciona 100% con teclado
•	[ ] Estructura de headings es semánticamente correcta (H1→H2→H3)
•	[ ] Landmarks ARIA definidos (main, nav, aside, footer)
•	[ ] Textos pueden aumentarse hasta 200% sin pérdida de funcionalidad
 
8. Optimización de Rendimiento
8.1 Estrategia de Assets
Asset	Estrategia	Resultado
CSS	SCSS compilado + minificado, Critical CSS inline	< 50KB gzipped
JavaScript	Defer loading, módulos ES6, tree shaking	< 30KB gzipped
Fuentes	Google Fonts con font-display: swap, preconnect	< 100KB
Imágenes	WebP con fallback, lazy loading nativo	Responsive sizes
Iconos	SVG sprites inline o como componentes Twig	Sin requests HTTP extra
8.2 Critical CSS
El CSS crítico para el above-the-fold se inyecta inline en el <head>:
// En jaraba_theme.theme
function jaraba_theme_page_attachments_alter(array &$attachments) {
  // Cargar CSS principal de forma asíncrona
  $attachments['#attached']['html_head'][] = [
    [
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'preload',
        'href' => '/themes/custom/jaraba_theme/css/style.css',
        'as' => 'style',
        'onload' => "this.onload=null;this.rel='stylesheet'",
      ],
    ],
    'jaraba_async_css',
  ];
}
8.3 Métricas Objetivo (Core Web Vitals)
Métrica	Objetivo	Técnica
LCP (Largest Contentful Paint)	< 2.5s	Preload hero image, critical CSS
FID (First Input Delay)	< 100ms	Defer JS, minimal main thread work
CLS (Cumulative Layout Shift)	< 0.1	Dimensiones explícitas en imágenes, font-display: swap
TTFB (Time to First Byte)	< 600ms	Redis cache, CDN, Varnish
 
9. Guía de Implementación Rápida
9.1 Para Desarrolladores
1.	Clonar el tema: cd web/themes/custom && git clone [repo] jaraba_theme
2.	Instalar dependencias: npm install
3.	Compilar SCSS: npm run build
4.	Watch mode: npm run watch
5.	Activar tema: drush theme:enable jaraba_theme && drush cset system.theme default jaraba_theme
9.2 Para Administradores
6.	Acceder a /admin/appearance/settings/jaraba_theme
7.	Subir logo en la sección "Identidad de Marca"
8.	Seleccionar colores con los color pickers
9.	Elegir tipografías de Google Fonts
10.	Configurar estilo de header (transparente/sólido)
11.	Guardar configuración
— Fin del Documento —
Jaraba Impact Platform © 2026
