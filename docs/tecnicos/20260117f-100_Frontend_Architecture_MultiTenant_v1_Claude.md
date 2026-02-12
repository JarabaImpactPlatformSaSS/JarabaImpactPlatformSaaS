ARQUITECTURA FRONTEND MULTI-TENANT
Sistema de Theming Escalonado
Design System & Component Library
Especificación Técnica de Implementación
Campo	Valor
Versión	1.0
Fecha	Enero 2026
Estado	Especificación Técnica
Código	100_Frontend_Architecture_v1
Dependencias	05_Core_Theming_jaraba_theme_v1
 
1. Resumen Ejecutivo
Este documento define la arquitectura frontend escalable del Ecosistema Jaraba, diseñada para servir múltiples verticales (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta) y permitir personalización profunda por cada tenant sin requerir conocimientos de programación.
1.1 Respuesta a la Pregunta Clave
¿Se necesita un theme base y subthemes escalonados hasta llegar al tenant? SÍ, pero con un enfoque moderno basado en Design Tokens y Component Variants, no en subthemes tradicionales de Drupal. La arquitectura propuesta utiliza:
• UN SOLO THEME BASE (jaraba_theme) que contiene TODOS los componentes y variantes posibles
• Design Tokens en cascada (Plataforma → Vertical → Plan → Tenant) que sobrescriben variables CSS
• Component Library con selectores visuales para elegir variantes de navegación, headers, cards, etc.
• Feature Flags por plan de suscripción que habilitan/deshabilitan opciones de personalización
1.2 Por Qué NO Subthemes Tradicionales
Aspecto	Subthemes Tradicionales	Design Tokens + Variants
Mantenimiento	5 subthemes = 5x actualizaciones	1 theme, tokens en BD
Personalización tenant	Requiere código/despliegue	UI visual, inmediato
Escalabilidad	1000 tenants = 1000 configs?	Config entity multi-tenant
Performance	Cache de theme por tenant	CSS Variables + cache HTTP
 
2. Arquitectura de 5 Capas
El sistema evoluciona la arquitectura de 4 capas existente a 5 capas para soportar la complejidad multi-vertical:
┌─────────────────────────────────────────────────────────────┐
│  CAPA 5: CSS RUNTIME (Inyección Dinámica)                        │
│  hook_preprocess_html() → :root { --color-primary: #xxx }       │
├─────────────────────────────────────────────────────────────┤
│  CAPA 4: CONFIG ENTITY (Almacenamiento Multi-Tenant)              │
│  tenant_theme_config + tenant_component_selection                 │
├─────────────────────────────────────────────────────────────┤
│  CAPA 3: COMPONENT LIBRARY (Selectores Visuales)                  │
│  Visual Picker con miniaturas de variantes de componentes        │
├─────────────────────────────────────────────────────────────┤
│  CAPA 2: DESIGN TOKENS (Panel Admin Visual)                       │
│  Color pickers, font selects, spacing sliders                    │
├─────────────────────────────────────────────────────────────┤
│  CAPA 1: SCSS/CSS (ADN del Tema)                                  │
│  _variables.scss con defaults, Bootstrap 5 compilado             │
└─────────────────────────────────────────────────────────────┘
2.1 Cascada de Configuración
Las configuraciones visuales se heredan en cascada, permitiendo personalización granular donde cada nivel puede sobrescribir el anterior:
Nivel	Quién Configura	Qué Puede Cambiar	Ejemplo
1. Plataforma	Desarrollador (tú)	Defaults globales, ADN visual	#FF8C42 como primary
2. Vertical	Desarrollador (tú)	Iconografía, tonos, componentes	AgroConecta = verde agro
3. Plan	Sistema (automático)	Features habilitados	Pro = marca blanca parcial
4. Tenant	Admin del tenant	Logo, colores, fuentes	Bodega X = su logo
5. Usuario	Usuario final (futuro)	Tema claro/oscuro, tamaño texto	Preferencias a11y
 
3. Sistema de Design Tokens
Los Design Tokens son variables semánticas que abstraen las decisiones de diseño. Permiten cambiar la apariencia completa modificando solo valores, sin tocar componentes.
3.1 Categorías de Tokens
Categoría	Tokens	Variables CSS
Color - Brand	primary, secondary, accent, dark	--color-primary, --color-secondary
Color - Semantic	success, warning, error, info	--color-success, --color-error
Color - Surface	background, surface, elevated	--surface-bg, --surface-card
Typography	font-family (headings, body, mono)	--font-family-headings
Typography - Scale	size-xs a size-4xl (12 niveles)	--font-size-base, --font-size-lg
Spacing	space-1 a space-16 (escala 4px)	--space-4 = 16px, --space-8 = 32px
Border Radius	radius-none a radius-full	--radius-md = 8px, --radius-lg = 16px
Shadows	shadow-sm, shadow-md, shadow-lg, shadow-xl	--shadow-md, --shadow-lg
Transitions	duration-fast, duration-normal, duration-slow	--transition-duration: 200ms
3.2 Tokens por Vertical
Cada vertical tiene su propia paleta de tokens que hereda de la plataforma y sobrescribe valores específicos:
Vertical	Primary	Secondary	Accent
Plataforma (base)	#FF8C42 (Naranja)	#00A9A5 (Turquesa)	#233D63 (Azul oscuro)
Empleabilidad	#2563EB (Azul pro)	#00A9A5 (heredado)	#F59E0B (Amber)
Emprendimiento	#8B5CF6 (Violeta)	#EC4899 (Rosa)	#10B981 (Esmeralda)
AgroConecta	#16A34A (Verde campo)	#CA8A04 (Dorado trigo)	#7C3AED (Violet)
ComercioConecta	#FF8C42 (Naranja)	#3B82F6 (Azul)	#EF4444 (Rojo oferta)
ServiciosConecta	#0891B2 (Cyan)	#6366F1 (Indigo)	#F97316 (Orange)
 
4. Component Library con Selectores Visuales
Cada componente tiene múltiples VARIANTES prediseñadas. El administrador del tenant elige visualmente qué variante usar mediante miniaturas interactivas en el Visual Picker.
4.1 Header / Navegación Principal
Variante	Descripción	Ideal Para	Plan Mínimo
header--classic	Fondo sólido, logo izquierda, menú derecha	Corporativo, B2B	Starter
header--transparent	Transparente, se vuelve sólido al scroll	Landings, hero full	Starter
header--centered	Logo centrado, menú en dos columnas	E-commerce, marcas	Professional
header--mega	Mega-menús desplegables con imágenes	Catálogos grandes	Professional
header--sidebar	Navegación en sidebar fijo lateral	Dashboards, apps	Enterprise
header--minimal	Solo hamburger, fullscreen overlay	Portfolios, arte	Professional
4.2 Cards de Contenido
Variante	Características	Uso Típico
card--default	Imagen top, título, descripción, CTA	Listados generales
card--horizontal	Imagen lateral 40%, contenido 60%	Ofertas de empleo, artículos
card--product	Imagen grande, badge, precio, quick-add	Productos e-commerce
card--profile	Avatar circular, nombre, rol, social links	Mentores, equipo, candidatos
card--course	Progress bar, duración, nivel, instructor	Cursos LMS, itinerarios
card--metric	Icono, número grande, trend arrow, label	Dashboards, KPIs
card--testimonial	Quote, avatar, nombre, empresa, rating	Social proof, reviews
card--cta	Fondo gradiente, icono hero, título, botón	Acciones destacadas
4.3 Hero Sections
Variante	Características	Uso
hero--fullscreen	100vh, imagen/video fondo, overlay gradient	Home principal
hero--split	50% texto izquierda, 50% imagen derecha	Landings producto
hero--compact	40vh máx, título + breadcrumb	Páginas interiores
hero--animated	Parallax, partículas, animaciones Lottie	Verticales tech
hero--slider	Carrusel de slides con autoplay	Múltiples mensajes
 
5. Visual Picker: Interfaz de Configuración
El Visual Picker es el panel de administración donde los tenants personalizan su experiencia visual SIN CÓDIGO. Se accede desde /admin/appearance/jaraba-customizer
5.1 Estructura del Panel
Sección	Controles	Resultado
Identidad de Marca	Logo upload, favicon, colores (pickers)	--color-primary, logo en header
Tipografía	Selector Google Fonts con preview	--font-family-headings, --font-family-body
Header	Radio buttons con MINIATURAS de variantes	Clase header--{variante} en <header>
Hero por defecto	Selector visual de layouts hero	Template hero-{variante}.html.twig
Cards	Slider border-radius, selector sombras	--card-radius, --card-shadow
Footer	Columnas (1-4), toggles de secciones	--footer-columns, clases condicionales
Dark Mode	Toggle on/off, paleta oscura custom	Atributo data-theme="dark" en html
5.2 Preview en Tiempo Real
El Visual Picker incluye un iframe de preview que muestra los cambios EN VIVO antes de guardar. Tecnología: Alpine.js + CSS Custom Properties broadcast via postMessage.
// visual-picker.js
document.querySelectorAll('[data-token]').forEach(input => {
  input.addEventListener('input', (e) => {
    const token = e.target.dataset.token;
    const value = e.target.value;
    // Actualizar preview iframe
    previewFrame.contentWindow.postMessage({
      type: 'token-update', token, value
    }, '*');
  });
});
 
6. Experiencias por Avatar (Vertical)
Cada tipo de usuario (avatar) aterriza en una experiencia visual optimizada para su contexto. El sistema detecta automáticamente el vertical y rol para aplicar la configuración correcta.
Avatar	Vertical	Dashboard Default	Componentes Destacados
Lucía	Empleabilidad	JobSeeker Dashboard	card--course, card--horizontal (jobs)
Javier	Emprendimiento	Entrepreneur Dashboard	card--metric, hero--split
Marta	Comercio/Agro	Merchant Dashboard	card--product, header--mega
David	Consultoría	Consultant Dashboard	card--profile, card--metric
Elena	Institucional	Entity Admin Dashboard	header--sidebar, card--metric
Admin SaaS	Plataforma	Super Admin Dashboard	header--sidebar, FOC widgets
6.1 Detección Automática de Contexto
El sistema determina qué configuración visual aplicar mediante la siguiente lógica de prioridad:
1. Dominio (Domain Access): Si el tenant tiene dominio propio, aplica su config
2. Subdominio/Path: Detecta /agroconecta/* o agro.plataforma.es
3. Group Membership: El Group del usuario determina el tenant activo
4. Rol del Usuario: job_seeker vs entrepreneur vs merchant
 
7. Feature Flags por Plan de Suscripción
No todos los tenants tienen acceso a todas las opciones de personalización. El sistema de Feature Flags controla qué opciones del Visual Picker están disponibles según el plan contratado.
Feature	Starter	Professional	Enterprise	White Label
Colores (primary, secondary)	✓	✓	✓	✓
Logo propio	✓	✓	✓	✓
Tipografía custom (Google Fonts)	-	✓	✓	✓
Header variants (>3 opciones)	-	✓	✓	✓
Hero customization	-	✓	✓	✓
Favicon propio	-	✓	✓	✓
CSS custom adicional	-	-	✓	✓
Dominio propio	-	-	✓	✓
Ocultar "Powered by Jaraba"	-	-	-	✓
Twig templates custom	-	-	-	✓
 
8. Implementación Técnica
8.1 Estructura de Archivos del Theme
jaraba_theme/
├── jaraba_theme.info.yml
├── jaraba_theme.libraries.yml
├── jaraba_theme.theme            # Hooks PHP
│
├── scss/
│   ├── _tokens.scss              # Design tokens base
│   ├── _tokens-empleabilidad.scss
│   ├── _tokens-emprendimiento.scss
│   ├── _tokens-agroconecta.scss
│   │
│   ├── components/
│   │   ├── _header.scss          # Todas las variantes header--*
│   │   ├── _cards.scss           # Todas las variantes card--*
│   │   ├── _hero.scss            # Todas las variantes hero--*
│   │   ├── _buttons.scss
│   │   ├── _forms.scss
│   │   └── _footer.scss
│   │
│   └── style.scss                # Import principal
│
├── templates/
│   ├── layout/
│   ├── components/
│   │   ├── header/
│   │   │   ├── header--classic.html.twig
│   │   │   ├── header--transparent.html.twig
│   │   │   ├── header--centered.html.twig
│   │   │   └── header--sidebar.html.twig
│   │   ├── card/
│   │   └── hero/
│   └── dashboards/               # Templates por avatar
│       ├── dashboard--jobseeker.html.twig
│       ├── dashboard--entrepreneur.html.twig
│       └── dashboard--merchant.html.twig
│
└── js/
    ├── visual-picker.js          # Preview en tiempo real
    └── theme-runtime.js          # Aplicación de tokens
8.2 Entidad tenant_theme_config
Campo	Tipo	Descripción
id	SERIAL	ID interno auto-increment
tenant_id	INT	FK a Group (NULL = config de vertical o plataforma)
vertical_id	VARCHAR(32)	empleabilidad, emprendimiento, agroconecta, etc.
scope	ENUM	platform, vertical, plan, tenant
config_data	JSON	Todos los tokens y selections en formato JSON
created	DATETIME	Fecha de creación
changed	DATETIME	Última modificación
 
9. Roadmap de Implementación
Sprint	Semanas	Entregables	Dependencias
1	S1-S2	Design Tokens base + cascada PHP	05_Core_Theming existente
2	S3-S4	Component Library (header, cards)	Sprint 1
3	S5-S6	Visual Picker UI + preview iframe	Sprint 2
4	S7-S8	Tokens por vertical + feature flags	Sprint 3
5	S9-S10	Testing multi-tenant + documentación	Sprint 4
9.1 Estimación de Esfuerzo
Módulo	Horas Est.	Complejidad
Sistema de Design Tokens + Cascada	24-32h	Alta
Component Library (6 componentes x 4 variantes)	40-56h	Media
Visual Picker UI completo	32-40h	Alta
Feature Flags + integración planes	16-24h	Media
Testing + QA multi-tenant	16-24h	Media
TOTAL	128-176h	~5-7 semanas FTE
 
10. Conclusión
La arquitectura propuesta ofrece la máxima flexibilidad con el mínimo mantenimiento. Un solo theme base (jaraba_theme) contiene TODAS las variantes posibles de componentes, mientras que la personalización real ocurre mediante:
• Design Tokens en cascada almacenados en base de datos (no en archivos)
• Selectores visuales de variantes con miniaturas interactivas
• Feature Flags por plan que controlan qué opciones están disponibles
• CSS Custom Properties inyectadas en runtime para aplicación instantánea
Esta arquitectura permite que un tenant del plan Starter pueda cambiar colores y logo en 2 minutos, mientras que un cliente Enterprise puede tener una experiencia completamente white-label con dominio propio, todo sin que tú tengas que tocar una línea de código para cada personalización.
10.1 Beneficios Clave
Beneficio	Impacto
Mantenimiento reducido	1 theme para 5 verticales y 1000+ tenants potenciales
Time-to-Value acelerado	Tenant configura su marca en <5 minutos sin soporte
Upsell natural	Features bloqueados incentivan upgrade de plan
Consistencia de marca	Tokens aseguran que cambios no rompan el diseño
Experiencias optimizadas	Cada avatar aterriza en UI optimizada para su contexto
---
Documento preparado para el Ecosistema Jaraba
Enero 2026
