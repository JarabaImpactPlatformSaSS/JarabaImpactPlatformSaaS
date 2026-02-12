CONSTRUCTOR DE PÁGINAS SAAS
Sistema de Construcción Visual de Páginas
Multi-Tenant con Plantillas Premium
JARABA IMPACT PLATFORM
Especificación Técnica de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Ready for Development
Código:	160_Page_Builder_SaaS_v1
Dependencias:	100_Frontend_Architecture, 05_Core_Theming
 
1. Matriz de Contenido Hardcodeado en Frontend
Este análisis documenta todo el contenido actualmente codificado directamente en los archivos HTML/Twig del SaaS que debe migrarse a la base de datos para permitir edición a través de la interfaz sin necesidad de modificar código.
1.1 Archivos Analizados
Archivo	Tipo	Propósito
demo_empleabilidad.html	Demo Vertical	Portal de empleo
demo_emprendimiento.html	Demo Vertical	Hub emprendedores
demo_agroconecta.html	Demo Vertical	Marketplace agrícola
demo_comercioconecta.html	Demo Vertical	Comercio local
demo_serviciosconecta.html	Demo Vertical	Servicios profesionales
jarabaimpact_website.html	Web Institucional	Jaraba Impact B2B
ped_corporate_premium.html	Web Corporativa	PED S.L. Premium
pepejaraba_homepage_wireframe.html	Marca Personal	Pepe Jaraba Blog
avatar_detection_flow.html	UX Flow	Detección de avatar
1.2 Categorías de Contenido Hardcodeado
1.2.1 Textos de Interfaz (UI Copy)
Elemento	Ejemplo Encontrado	Migrar a
Títulos Hero	"Tu próximo trabajo te está buscando"	page_content.hero_title
Subtítulos Hero	"Conectamos talento con oportunidades..."	page_content.hero_subtitle
Labels de navegación	"Ofertas", "Formación", "Para empresas"	menu_link.title (traducible)
Botones CTA	"Publicar oferta", "Entrar"	block_content.cta_text
Placeholders	"Puesto, empresa o palabra clave"	field_config.placeholder
Tags populares	"Marketing Digital", "Desarrollador"	taxonomy_term (tags)
Textos footer	"Centro de ayuda", "Contacto"	menu_link.title
Copyright	"© 2026 Jaraba Impact S.L."	site_config.copyright
1.2.2 Estadísticas y Métricas
Métrica	Valor Hardcodeado	Solución
Ofertas activas	"+3.500"	metric_block.value (dinámico o editable)
Candidatos registrados	"+12.000"	metric_block.value
Tasa colocación	"87%"	metric_block.value
Cursos disponibles	"+150"	metric_block.value
Productores activos	"+250"	metric_block.value (AgroConecta)
Productos disponibles	"+1.200"	metric_block.value
Tiempo entrega	"24-48h"	metric_block.value
Fondos gestionados	"+100M€"	metric_block.value (institucional)
1.2.3 Colores y Gradientes
Archivo	Código Hardcodeado	Variable CSS Destino
demo_empleabilidad.html	gradient: #1B5E4F → #3498DB	--vertical-gradient
demo_agroconecta.html	gradient: #1B5E4F → #27AE60	--vertical-gradient
jarabaimpact_website.html	--primary: #1B4F72	--color-primary
jarabaimpact_website.html	--secondary: #17A589	--color-secondary
jarabaimpact_website.html	--accent: #E67E22	--color-accent
ped_corporate_premium.html	gradient-accent: #E67E22 → #F39C12	--gradient-accent
1.2.4 Contenido de Ejemplo (Seed Data)
Tipo de Contenido	Ejemplos	Entidad Destino
Ofertas de empleo	"Marketing Digital Manager", "Full Stack Developer"	job_listing entity
Empresas	"TechCorp España", "StartupHub Sevilla"	employer_profile entity
Cursos	"Excel Avanzado", "Diseño UX/UI"	course entity
Productos agrícolas	"Naranjas de Valencia", "Aceite de Oliva"	commerce_product
Productores	"Bodega Robles - Puente Genil"	producer_profile entity
Testimonios	Quotes de clientes ficticios	testimonial entity
1.3 Problemas de Traducibilidad Detectados
Los siguientes strings NO están preparados para traducción (no usan t() ni TranslatableMarkup):
•	Todos los textos de las demos HTML están hardcodeados en español
•	No hay uso de data-i18n attributes para JavaScript translations
•	Emojis usados como iconos en lugar de iconos vectoriales traducibles
•	Labels de formularios embebidos directamente en HTML
 
2. Constructor de Páginas SaaS
El Constructor de Páginas es un sistema visual que permite a usuarios autorizados crear y gestionar páginas personalizadas sin necesidad de conocimientos técnicos. Se integra nativamente con el sistema de permisos multi-tenant y la arquitectura de Design Tokens.
2.1 Arquitectura del Sistema
El constructor se basa en un sistema de entidades de contenido emparejadas con plantillas Twig gemelas:
Componente	Tecnología	Función
page_template	Config Entity	Metadatos de plantilla (preview, categoría)
page_content	Content Entity	Contenido editable por usuario
template Twig	*.html.twig	Renderizado con variables inyectadas
Visual Picker UI	React + Alpine.js	Selector visual de plantillas
Form Builder	Drupal Form API	Formularios dinámicos por plantilla
2.2 Modelo de Datos
2.2.1 Entidad: page_template
Almacena los metadatos de las plantillas disponibles en el sistema:
Campo	Tipo	Descripción	Ejemplo
id	VARCHAR(64)	Machine name	hero_fullscreen
label	VARCHAR(255)	Nombre visible	Hero Pantalla Completa
description	TEXT	Descripción corta	Sección hero con imagen...
category	VARCHAR(64)	Categoría	hero, content, cta, footer
preview_image	file_reference	Imagen de preview	hero_fullscreen.jpg
twig_template	VARCHAR(255)	Ruta de plantilla	page-builder/hero--fullscreen
fields_schema	JSON	Definición de campos	{"title": {...}}
plans_required	VARCHAR(255)	Planes requeridos	professional,enterprise
verticals	VARCHAR(255)	Verticales compatibles	all, empleabilidad
status	BOOLEAN	Activa/Inactiva	TRUE
2.2.2 Entidad: page_content
Almacena el contenido específico creado por cada tenant:
Campo	Tipo	Descripción	Ejemplo
id	SERIAL	ID auto-increment	1
uuid	UUID	Identificador único	550e8400-e29b...
tenant_id	INT	FK a Group	42
template_id	VARCHAR(64)	FK a page_template	hero_fullscreen
title	VARCHAR(255)	Título de página	Nuestra Historia
path_alias	VARCHAR(255)	URL amigable	/nuestra-historia
content_data	JSON	Contenido estructurado	{"hero_title": "..."}
meta_title	VARCHAR(255)	SEO Title	Nuestra Historia | Empresa
meta_description	TEXT	SEO Description	Conoce la historia de...
status	INT	0=draft, 1=published	1
weight	INT	Orden en navegación	10
created	DATETIME	Fecha creación	2026-01-26 10:00:00
changed	DATETIME	Última modificación	2026-01-26 12:30:00
2.3 Sistema de Permisos
Permiso	Descripción
access page builder	Ver el panel del constructor
create page content	Crear nuevas páginas
edit own page content	Editar páginas propias
edit any page content	Editar cualquier página (admin)
delete own page content	Eliminar páginas propias
delete any page content	Eliminar cualquier página
administer page templates	Gestionar plantillas (super admin)
use premium templates	Acceso a plantillas premium
2.4 Flujo de Usuario
1.	Usuario accede a /admin/page-builder
2.	Sistema muestra galería visual de plantillas (filtradas por plan y vertical)
3.	Usuario selecciona plantilla mediante click en preview
4.	Sistema genera formulario dinámico basado en fields_schema
5.	Usuario completa campos (título, textos, imágenes, CTAs)
6.	Sistema muestra preview en tiempo real en iframe lateral
7.	Usuario define URL (path alias) y metadatos SEO
8.	Usuario guarda como borrador o publica directamente
9.	Sistema genera path alias y añade a repositorio de páginas
10.	Usuario puede añadir página a menú de navegación desde mismo panel
2.5 Integración con Planes de Pago
Plan	Plantillas Disponibles	Páginas Máx.	Bloques Premium
Starter	10 básicas	5	No
Professional	25 (básicas + premium)	20	5 bloques
Enterprise	Todas + custom	Ilimitadas	Todos
2.6 Estructura de Archivos
jaraba_page_builder/
├── jaraba_page_builder.info.yml
├── jaraba_page_builder.module
├── jaraba_page_builder.routing.yml
├── jaraba_page_builder.permissions.yml
├── src/
│   ├── Entity/
│   │   ├── PageTemplate.php
│   │   └── PageContent.php
│   ├── Form/
│   │   ├── PageContentForm.php
│   │   └── TemplateSettingsForm.php
│   ├── Controller/
│   │   ├── PageBuilderController.php
│   │   └── PreviewController.php
│   └── Service/
│       ├── TemplateManager.php
│       └── PageRenderer.php
├── templates/
│   └── page-builder/
│       ├── hero--fullscreen.html.twig
│       ├── hero--split.html.twig
│       ├── content--features.html.twig
│       └── ... (más plantillas)
└── js/
    ├── template-picker.js
    └── live-preview.js
 
3. Sistema de Bloques Premium
Los bloques premium son componentes visuales de alto impacto que pueden añadirse a cualquier página sin hardcodear contenido. El sistema aplica automáticamente el estilismo premium del SaaS respetando los Design Tokens del tenant.
3.1 Arquitectura de Bloques
Capa	Tecnología	Función
block_template	Config Entity	Definición del bloque
block_content	Content Entity	Instancia con contenido
Twig Component	*.html.twig	Renderizado con slots
SCSS Module	_block-*.scss	Estilos con CSS Variables
JS Enhancement	Alpine.js	Interactividad opcional
3.2 Catálogo de Bloques Base
Bloque	Categoría	Descripción	Plan Mínimo
hero_fullscreen	Hero	Imagen 100vh con overlay	Starter
hero_split	Hero	50/50 texto + imagen	Starter
hero_video	Hero	Video de fondo con autoplay	Professional
stats_counter	Métricas	Contadores animados	Starter
stats_progress	Métricas	Barras de progreso	Professional
features_grid	Features	Grid de iconos + texto	Starter
features_tabs	Features	Features con tabs	Professional
testimonials_slider	Social Proof	Carrusel de testimonios	Starter
testimonials_masonry	Social Proof	Grid masonry	Professional
pricing_table	Conversión	Tabla de precios	Starter
cta_banner	Conversión	Banner CTA full-width	Starter
cta_floating	Conversión	CTA flotante sticky	Professional
team_grid	Equipo	Grid de miembros	Starter
faq_accordion	Contenido	FAQ con acordeón	Starter
timeline_vertical	Contenido	Timeline vertical	Professional
gallery_lightbox	Media	Galería con lightbox	Professional
map_interactive	Ubicación	Mapa interactivo	Professional
contact_form	Formularios	Formulario de contacto	Starter
newsletter_popup	Lead Gen	Popup de newsletter	Professional
3.3 Librerías Premium de Terceros
Para verticales que requieran experiencias inmersivas de nivel mundial, se integran componentes de librerías premium:
3.3.1 Aceternity UI (React)
Componentes de animación avanzada para experiencias premium:
•	Spotlight Effect: Efecto de luz que sigue el cursor
•	Card 3D: Tarjetas con efecto parallax tridimensional
•	Text Reveal: Animación de revelado de texto
•	Infinite Moving Cards: Carrusel infinito horizontal
•	Meteors Background: Fondo animado tipo meteoros
•	Aurora Background: Gradientes animados tipo aurora
3.3.2 Magic UI
Componentes con micro-interacciones y glassmorphism:
•	Bento Grid: Layouts estilo Bento con animaciones
•	Animated Beam: Líneas de conexión animadas
•	Orbiting Circles: Círculos orbitantes decorativos
•	Dock Menu: Menú estilo macOS Dock
•	Marquee: Texto infinito horizontal
•	Particles Background: Partículas interactivas
3.3.3 Integración con Design Tokens
Todos los componentes de terceros se adaptan a los Design Tokens del tenant mediante:
11.	Wrapper Twig que inyecta CSS Variables en scope
12.	Override de clases Tailwind con tokens personalizados
13.	Props de color/tipografía inyectadas desde Drupal settings
14.	Fallback a defaults si no hay config de tenant
 
4. Repositorio de Plantillas Premium
4.1 Estructura del Repositorio
Se crea la siguiente estructura para inspiraciones y plantillas:
/mnt/project/page_builder_inspirations/
├── README.md
├── hero/
│   ├── hero_saas_landing.html
│   ├── hero_agency.html
│   └── hero_ecommerce.html
├── features/
│   ├── features_bento.html
│   └── features_cards.html
├── pricing/
│   └── pricing_tiered.html
├── testimonials/
│   └── testimonials_carousel.html
├── cta/
│   └── cta_gradient.html
└── footer/
    └── footer_mega.html
4.2 Plantillas por Caso de Uso
Caso de Uso	Plantillas Incluidas	Vertical Ideal
Landing SaaS B2B	hero_saas, features_bento, pricing_tiered, cta_demo	Emprendimiento
Portal de Empleo	hero_search, job_listings, stats_counter, employer_cta	Empleabilidad
Marketplace Agrícola	hero_product, category_grid, producer_spotlight, trust_badges	AgroConecta
Comercio Local	hero_offers, flash_deals, store_locator, reviews_wall	ComercioConecta
Servicios Profesionales	hero_booking, services_grid, credentials, contact_smart	ServiciosConecta
Blog/Magazine	hero_featured, article_grid, newsletter_inline, related_posts	Content Hub
About/Corporativo	hero_company, timeline_history, team_cards, values_icons	Institucional
Marca Personal	hero_personal, portfolio_grid, bio_extended, social_links	Personal Brand
 
5. Verificación de Cumplimiento de Directrices
5.1 Directriz de Iconos
Aspecto	Estado Actual	Acción Requerida
Librería definida	Lucide React	CUMPLE - Mantener Lucide
Uso en demos HTML	Emojis Unicode	INCUMPLE - Migrar a Lucide SVG
Consistencia vertical	Inconsistente	INCUMPLE - Unificar iconografía
Tamaños estandarizados	No definidos	PENDIENTE - Definir escala
Escala de tamaños propuesta para iconos:
•	icon-xs: 16px (inline text)
•	icon-sm: 20px (botones, badges)
•	icon-md: 24px (navegación, cards)
•	icon-lg: 32px (features, CTAs)
•	icon-xl: 48px (hero, headers)
•	icon-2xl: 64px (ilustraciones)
5.2 Paleta de Colores Oficial
Color	HEX	Uso
Primary (Azul Corporativo)	#233D63	Headers, footers, textos principales
Secondary (Turquesa)	#00A9A5	Empleabilidad, crecimiento, talento
Accent (Naranja Impulso)	#FF8C42	CTAs, emprendimiento, acción
Success	#28A745	Estados positivos, confirmaciones
Warning	#FFC107	Alertas, atención
Danger	#DC3545	Errores, eliminaciones
Text Primary	#333333	Texto principal
Text Muted	#666666	Texto secundario
Background	#FFFFFF	Fondo principal
Surface	#F4F4F4	Fondo de cards, secciones
5.3 Modelo SCSS con Variables Inyectables
Requisito	Estado	Notas
Archivos SCSS separados	CUMPLE	_variables.scss, _mixins.scss, etc.
Compilación a CSS minificado	CUMPLE	npm run build genera style.css
CSS Custom Properties	CUMPLE	Variables inyectadas en runtime
Config via interfaz Drupal	CUMPLE	Visual Picker en /admin/appearance
Cascada multi-tenant	CUMPLE	Plataforma → Vertical → Plan → Tenant
Demos HTML con tokens	INCUMPLE	Colores hardcodeados en <style>
5.4 Textos Traducibles
Componente	Estado	Acción
Módulos Drupal PHP	CUMPLE	Usar t() y TranslatableMarkup
Templates Twig	PENDIENTE	Usar {{ 'texto'|t }}
JavaScript/React	PENDIENTE	Implementar i18n con Drupal.t()
Demos HTML estáticos	INCUMPLE	No aplicable (prototipos)
Field labels en forms	PENDIENTE	Asegurar #title usa t()
Mensajes de error	PENDIENTE	Usar drupal_set_message con t()
 
6. Integración con Estructura de Navegación Drupal
6.1 Rutas de Administración
Ruta	Propósito
/admin/structure/page-templates	CRUD de plantillas (super admin)
/admin/structure/block-templates	CRUD de bloques (super admin)
/admin/content/pages	Listado de páginas (por tenant)
/admin/content/blocks	Listado de bloques instanciados
/admin/page-builder	Panel visual del constructor
/admin/page-builder/new	Crear nueva página
/admin/page-builder/{id}/edit	Editar página existente
/admin/page-builder/preview/{id}	Preview de página
6.2 Integración con Field UI
Todas las entidades de contenido (page_content, block_content) exponen sus campos en Field UI para permitir extensiones:
•	/admin/structure/page-content/manage/fields - Gestión de campos
•	/admin/structure/page-content/manage/form-display - Display del formulario
•	/admin/structure/page-content/manage/display - Display de visualización
6.3 Integración con Views
Las entidades son compatibles con Views para crear listados personalizados:
•	Vista: Páginas recientes por tenant
•	Vista: Páginas más visitadas
•	Vista: Páginas en borrador pendientes de revisión
•	Vista: Bloques por categoría
6.4 Integración con Menu System
Las páginas creadas pueden añadirse a menús mediante:
15.	Checkbox en formulario de edición: "Añadir a menú principal"
16.	Selector de menú destino (main, footer, secondary)
17.	Campo de peso para ordenación
18.	Selector de padre para submenús
 
7. Roadmap de Implementación
Sprint	Semanas	Entregables	Horas Est.
Sprint 1	S1-S2	Entidades page_template, page_content. CRUD básico.	40-50h
Sprint 2	S3-S4	Template Picker UI con galería visual. Preview iframe.	50-60h
Sprint 3	S5-S6	Formularios dinámicos por template. Path alias auto.	40-50h
Sprint 4	S7-S8	Sistema de bloques. 10 bloques base.	60-70h
Sprint 5	S9-S10	Integración Aceternity/Magic UI. 6 bloques premium.	50-60h
Sprint 6	S11-S12	Permisos por plan. Testing multi-tenant.	40-50h
Total estimado: 280-340 horas (€22,400-27,200 @ €80/h)
8. Actualización de Gestión Financiera
El Constructor de Páginas se añade como feature en la matriz de planes:
Feature	Starter	Professional	Enterprise
Constructor de Páginas	Básico (5 pág)	Completo (20 pág)	Ilimitado
Plantillas disponibles	10	25	Todas + custom
Bloques premium	No	5 tipos	Todos
Aceternity/Magic UI	No	Limitado	Completo
Soporte prioritario	No	Email	Dedicado
Precio adicional sugerido: +€29/mes para acceso al Constructor en plan Starter.
--- Fin del Documento ---
Jaraba Impact Platform | 160_Page_Builder_SaaS_v1 | Enero 2026
