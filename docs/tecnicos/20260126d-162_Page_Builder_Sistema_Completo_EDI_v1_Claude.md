
ECOSISTEMA JARABA
Especificación Técnica Definitiva

CONSTRUCTOR DE PÁGINAS SAAS
Sistema de Bloques Premium y Repositorio de Plantillas

Documento:	162_Page_Builder_Sistema_Completo_EDI_v1
Versión:	1.0 - Especificación Definitiva
Fecha:	26 de Enero de 2026
Destinatario:	EDI Google Antigravity - Equipo de Desarrollo
Clasificación:	CONFIDENCIAL - Implementación Directa
Horas Estimadas:	420-520 horas (€33,600-€41,600 @ €80/h)
 
ÍNDICE DE CONTENIDOS
1. Resumen Ejecutivo
2. Arquitectura del Constructor de Páginas
   2.1 Modelo de Datos - Entidades
   2.2 Esquema de Base de Datos Completo
   2.3 APIs REST Endpoints
   2.4 Sistema de Permisos RBAC
3. Sistema de Bloques Premium
   3.1 Catálogo de 45 Bloques Base
   3.2 Integración Aceternity UI (12 Componentes)
   3.3 Integración Magic UI (10 Componentes)
   3.4 Schema JSON por Bloque
4. Repositorio de Plantillas Premium
   4.1 Plantillas por Vertical (55 Total)
   4.2 Investigación de Mercado - Mejores Prácticas 2025-2026
   4.3 Especificación Técnica por Plantilla
5. Visual Template Picker - Interfaz de Usuario
   5.1 Galería Visual con Filtros
   5.2 Preview en Tiempo Real
   5.3 Form Builder Dinámico
6. Integración Multi-Tenant
   6.1 Cascada de Design Tokens
   6.2 Feature Flags por Plan
7. Roadmap de Implementación
8. Anexos Técnicos
 
1. RESUMEN EJECUTIVO
Este documento constituye la especificación técnica definitiva para implementar el Constructor de Páginas SaaS del Ecosistema Jaraba. El sistema permite a tenants crear páginas personalizadas mediante un editor visual sin código, seleccionando plantillas premium y configurando contenido a través de formularios dinámicos.
1.1 Objetivos del Sistema
•	Eliminar 100% del contenido hardcodeado en templates HTML actuales
•	Proporcionar constructor visual accesible a tenants según plan de suscripción
•	Ofrecer biblioteca de 45 bloques base + 22 bloques premium (Aceternity/Magic UI)
•	Incluir repositorio de 55 plantillas premium optimizadas por vertical
•	Integrar sistema completo con Design Tokens multi-tenant
•	Mantener compatibilidad con Field UI, Views y estructura de navegación Drupal
1.2 Alcance Técnico
Componente	Tecnología	Descripción
Backend	Drupal 11 + PHP 8.3	Entidades personalizadas, APIs REST, ECA
Frontend Admin	React 18 + Alpine.js	Visual Picker, Form Builder, Preview
Renderizado	Twig 3 + CSS Variables	Templates gemelos, Design Tokens
Bloques Premium	Aceternity UI + Magic UI	Framer Motion, Tailwind adaptado
Base de Datos	MariaDB 10.6	Entidades page_template, page_content, block_*
 
2. ARQUITECTURA DEL CONSTRUCTOR DE PÁGINAS
2.1 Modelo de Datos - Entidades Principales
El sistema se basa en tres entidades principales que trabajan en conjunto: page_template (configuración), page_content (contenido de usuario) y block_template (bloques reutilizables).
2.1.1 Entidad: page_template (Config Entity)
Campo	Tipo	Requerido	Descripción
id	string	Sí	Machine name: hero_fullscreen, landing_saas
label	string	Sí	Nombre visible: 'Hero Pantalla Completa'
description	text	No	Descripción para selector visual
category	string	Sí	hero|features|pricing|testimonials|cta|footer|landing|dashboard
preview_image	uri	Sí	Captura de pantalla para galería visual
twig_template	string	Sí	Ruta: @jaraba_page_builder/hero-fullscreen.html.twig
fields_schema	json	Sí	JSON Schema para Form Builder dinámico
plans_required	array	Sí	['starter','professional','enterprise']
verticals	array	No	['empleabilidad','agroconecta'] o vacío=todas
is_premium	boolean	Sí	Indica si usa bloques Aceternity/Magic UI
weight	integer	No	Orden en galería visual
status	boolean	Sí	Activo/Inactivo
2.1.2 Entidad: page_content (Content Entity)
Campo	Tipo	Requerido	Descripción
id	serial	Auto	ID autoincremental
uuid	uuid	Auto	Identificador único universal
tenant_id	entity_ref	Sí	Referencia a Group (multi-tenant)
template_id	string	Sí	Referencia a page_template.id
title	string	Sí	Título de la página
path_alias	string	Sí	URL amigable: /sobre-nosotros
content_data	json	Sí	Datos del formulario según fields_schema
meta_title	string	No	SEO: Título para buscadores
meta_description	text	No	SEO: Descripción meta
og_image	file	No	Imagen para compartir en redes
menu_link	string	No	Menú destino: main, footer, secondary
menu_weight	integer	No	Posición en menú
menu_parent	string	No	Página padre para submenús
status	boolean	Sí	Publicado/Borrador
created	timestamp	Auto	Fecha de creación
changed	timestamp	Auto	Última modificación
uid	entity_ref	Auto	Usuario autor
 
2.2 Esquema de Base de Datos Completo
2.2.1 SQL - Tabla page_content
CREATE TABLE page_content (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   uuid VARCHAR(128) NOT NULL UNIQUE,   tenant_id INT UNSIGNED NOT NULL,   template_id VARCHAR(64) NOT NULL,   title VARCHAR(255) NOT NULL,   path_alias VARCHAR(255) NOT NULL,   content_data JSON NOT NULL,   meta_title VARCHAR(255) DEFAULT NULL,   meta_description TEXT DEFAULT NULL,   og_image_fid INT UNSIGNED DEFAULT NULL,   menu_link VARCHAR(64) DEFAULT NULL,   menu_weight INT DEFAULT 0,   menu_parent VARCHAR(255) DEFAULT NULL,   status TINYINT(1) NOT NULL DEFAULT 1,   created INT NOT NULL,   changed INT NOT NULL,   uid INT UNSIGNED NOT NULL,   langcode VARCHAR(12) NOT NULL DEFAULT 'es',      INDEX idx_tenant (tenant_id),   INDEX idx_template (template_id),   INDEX idx_path (tenant_id, path_alias),   INDEX idx_status (status),   INDEX idx_menu (menu_link, menu_weight),      FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE,   FOREIGN KEY (uid) REFERENCES users_field_data(uid) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
2.2.2 SQL - Tabla block_content (Bloques Personalizados)
CREATE TABLE jaraba_block_content (   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,   uuid VARCHAR(128) NOT NULL UNIQUE,   tenant_id INT UNSIGNED NOT NULL,   block_type VARCHAR(64) NOT NULL COMMENT 'hero_fullscreen, stats_counter, etc.',   label VARCHAR(255) NOT NULL,   content_data JSON NOT NULL,   is_global TINYINT(1) DEFAULT 0 COMMENT 'Disponible para todas las páginas',   status TINYINT(1) NOT NULL DEFAULT 1,   created INT NOT NULL,   changed INT NOT NULL,   uid INT UNSIGNED NOT NULL,      INDEX idx_tenant (tenant_id),   INDEX idx_type (block_type),   INDEX idx_global (tenant_id, is_global),      FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
2.3 APIs REST - Endpoints Completos
Todos los endpoints requieren autenticación JWT y verifican permisos del tenant automáticamente.
2.3.1 Endpoints de Plantillas
Método	Endpoint	Descripción
GET	/api/v1/page-templates	Lista plantillas disponibles según plan del tenant
GET	/api/v1/page-templates/{id}	Detalle de plantilla con fields_schema completo
GET	/api/v1/page-templates/{id}/preview	HTML renderizado de preview con datos de ejemplo
GET	/api/v1/page-templates/categories	Lista categorías con conteo de plantillas
2.3.2 Endpoints de Contenido de Páginas
Método	Endpoint	Descripción
GET	/api/v1/pages	Lista páginas del tenant (paginado, filtrable)
POST	/api/v1/pages	Crear nueva página con template y content_data
GET	/api/v1/pages/{id}	Detalle de página con datos completos
PATCH	/api/v1/pages/{id}	Actualizar página (parcial)
DELETE	/api/v1/pages/{id}	Eliminar página
POST	/api/v1/pages/{id}/preview	Generar preview HTML con datos enviados
POST	/api/v1/pages/{id}/publish	Publicar página (cambiar status)
POST	/api/v1/pages/{id}/duplicate	Duplicar página existente
 
2.4 Sistema de Permisos RBAC
Permiso	Descripción
access page builder	Acceder al constructor de páginas (verificado por plan)
view page templates	Ver galería de plantillas disponibles
use premium templates	Usar plantillas con bloques Aceternity/Magic UI
create page content	Crear nuevas páginas
edit own page content	Editar páginas propias
edit any page content	Editar cualquier página del tenant
delete own page content	Eliminar páginas propias
delete any page content	Eliminar cualquier página del tenant
manage page menus	Añadir/quitar páginas de menús de navegación
administer page templates	ADMIN: Gestionar plantillas del sistema
administer page builder	ADMIN: Configuración global del constructor
2.4.1 Matriz de Permisos por Plan
Capacidad	Starter	Professional	Enterprise
Plantillas básicas	10	25	55 (todas)
Plantillas premium (Aceternity/Magic)	0	8	22 (todas)
Máximo páginas	5	25	Ilimitado
Bloques base	15	35	45 (todos)
Bloques premium	0	10	22 (todos)
Gestión de menús	Básica	Completa	Avanzada + Multi-idioma
SEO avanzado	—	✓	✓ + Schema.org
Personalización CSS	—	Básica	Completa
 
3. SISTEMA DE BLOQUES PREMIUM
El sistema incluye 67 bloques totales: 45 bloques base nativos + 12 componentes Aceternity UI + 10 componentes Magic UI. Cada bloque tiene su schema JSON, template Twig y adaptación a Design Tokens.
3.1 Catálogo Completo de 45 Bloques Base
Categoría: Hero Sections (8 bloques)
ID Bloque	Nombre	Campos Principales
hero_fullscreen	Hero Pantalla Completa	title, subtitle, cta_primary, cta_secondary, background_image, overlay_opacity
hero_split	Hero Dividido 50/50	title, subtitle, cta, image, image_position (left/right)
hero_video	Hero con Video	title, subtitle, video_url, poster_image, autoplay
hero_gradient	Hero con Gradiente	title, subtitle, cta, gradient_from, gradient_to, gradient_angle
hero_centered	Hero Centrado	title, subtitle, cta, trust_logos[], stats[]
hero_carousel	Hero Carrusel	slides[]{title, subtitle, image, cta}, autoplay_interval
hero_minimal	Hero Minimalista	title, subtitle, cta, icon
hero_parallax	Hero Parallax	title, subtitle, layers[]{image, speed}
Categoría: Features & Benefits (7 bloques)
ID Bloque	Nombre	Campos Principales
features_grid	Grid de Features	title, subtitle, features[]{icon, title, description}, columns (2/3/4)
features_tabs	Features con Tabs	title, tabs[]{label, icon, content, image}
features_alternating	Features Alternadas	title, features[]{title, description, image, cta}
features_comparison	Tabla Comparativa	title, columns[], rows[]{feature, values[]}
features_icons	Features con Iconos	title, features[]{icon, title, description, link}
features_cards	Cards de Features	title, cards[]{icon, title, description, cta}
features_timeline	Timeline de Features	title, items[]{date, title, description, icon}
 
Categoría: Stats & Metrics (4 bloques)
ID Bloque	Nombre	Campos Principales
stats_counter	Contador Animado	stats[]{value, suffix, label, icon}, animate_on_scroll
stats_progress	Barras de Progreso	title, stats[]{label, value, max, color}
stats_cards	Cards de Estadísticas	stats[]{icon, value, label, trend, trend_value}
stats_inline	Stats en Línea	stats[]{value, label}, separator, background
Categoría: Testimonials & Social Proof (5 bloques)
ID Bloque	Nombre	Campos Principales
testimonials_slider	Carrusel de Testimonios	title, testimonials[]{quote, author, role, company, avatar, rating}
testimonials_grid	Grid de Testimonios	title, testimonials[], columns (2/3)
testimonials_masonry	Masonry de Testimonios	title, testimonials[], highlight_first
logos_carousel	Logos de Clientes	title, logos[]{image, alt, url}, speed, grayscale
social_proof_bar	Barra de Social Proof	items[]{icon, text}, background
Categoría: Pricing & CTA (6 bloques)
ID Bloque	Nombre	Campos Principales
pricing_table	Tabla de Precios	title, plans[]{name, price, period, features[], cta, highlighted}
pricing_cards	Cards de Precios	title, subtitle, plans[], show_toggle, annual_discount
cta_banner	Banner CTA	title, subtitle, cta_primary, cta_secondary, background
cta_floating	CTA Flotante	text, cta, position (bottom-right/bottom-left)
cta_fullwidth	CTA Ancho Completo	title, subtitle, cta, background_gradient
newsletter_form	Formulario Newsletter	title, subtitle, placeholder, button_text, success_message
 
Categoría: Content & Media (8 bloques)
ID Bloque	Nombre	Campos Principales
text_image	Texto con Imagen	title, content (richtext), image, image_position
gallery_grid	Galería Grid	title, images[]{src, alt, caption}, columns, lightbox
gallery_masonry	Galería Masonry	title, images[], lightbox
video_embed	Video Embebido	title, video_url (YouTube/Vimeo), autoplay, poster
accordion_faq	Acordeón FAQ	title, items[]{question, answer}, allow_multiple
tabs_content	Tabs de Contenido	tabs[]{label, icon, content (richtext)}
quote_block	Cita Destacada	quote, author, source, style (minimal/bordered/background)
cards_grid	Grid de Cards	title, cards[]{image, title, description, cta}, columns
Categoría: Navigation & Footer (4 bloques)
ID Bloque	Nombre	Campos Principales
footer_simple	Footer Simple	logo, copyright, social_links[], links[]
footer_columns	Footer Multi-columna	logo, columns[]{title, links[]}, social_links[], copyright
footer_cta	Footer con CTA	cta_title, cta_subtitle, cta_button, columns[], copyright
breadcrumbs	Migas de Pan	items[]{label, url}, separator, show_home
Categoría: Forms & Contact (3 bloques)
ID Bloque	Nombre	Campos Principales
contact_form	Formulario de Contacto	title, fields[]{type, label, required}, submit_text, recipient
contact_split	Contacto Dividido	title, info[]{icon, label, value}, form_fields[]
map_contact	Mapa + Contacto	title, address, coords{lat,lng}, info[], form
 
3.2 Integración Aceternity UI (12 Componentes Premium)
Aceternity UI proporciona componentes con animaciones avanzadas basadas en Framer Motion. Cada componente se adapta al sistema de Design Tokens del Ecosistema Jaraba.
Componente	Tipo	Plan Mínimo	Uso Recomendado
Spotlight Effect	Background	Professional	Hero sections, landing pages
3D Card Effect	Card	Professional	Features, productos, servicios
Text Reveal	Typography	Professional	Títulos hero, headlines
Infinite Moving Cards	Carousel	Professional	Testimonios, logos clientes
Meteors Background	Background	Enterprise	Hero premium, páginas especiales
Aurora Background	Background	Enterprise	Landing pages premium
Tracing Beam	Navigation	Enterprise	Scroll storytelling
Hover Border Gradient	Card	Professional	Cards interactivas
Floating Navbar	Navigation	Enterprise	Navegación premium
Glowing Stars	Background	Enterprise	Secciones destacadas
Lamp Effect	Typography	Enterprise	Títulos dramáticos
Sparkles	Effect	Professional	Destacar elementos
3.3 Integración Magic UI (10 Componentes Premium)
Componente	Tipo	Plan Mínimo	Uso Recomendado
Bento Grid	Layout	Professional	Features, dashboards
Animated Beam	Effect	Enterprise	Conexiones, integraciones
Orbiting Circles	Animation	Enterprise	Tech stacks, ecosistemas
Dock Menu	Navigation	Professional	Navegación macOS style
Marquee	Animation	Professional	Logos, testimonios
Particles Background	Background	Enterprise	Hero sections premium
Blur Fade	Animation	Professional	Transiciones de contenido
Typing Animation	Typography	Professional	Headlines dinámicos
Number Ticker	Counter	Professional	Estadísticas animadas
Shine Border	Effect	Professional	Cards destacadas
 
3.4 Schema JSON por Bloque - Ejemplo Detallado
Cada bloque define su schema JSON que determina los campos del Form Builder dinámico. Ejemplo completo para hero_fullscreen:
{   "$schema": "http://json-schema.org/draft-07/schema#",   "type": "object",   "properties": {     "title": {       "type": "string",       "title": "Título Principal",       "description": "Headline principal del hero",       "maxLength": 100,       "ui:widget": "text",       "ui:placeholder": "Tu próximo gran proyecto comienza aquí"     },     "subtitle": {       "type": "string",       "title": "Subtítulo",       "description": "Texto de apoyo bajo el título",       "maxLength": 200,       "ui:widget": "textarea"     },     "cta_primary": {       "type": "object",       "title": "CTA Principal",       "properties": {         "text": { "type": "string", "title": "Texto del botón", "default": "Comenzar ahora" },         "url": { "type": "string", "title": "URL destino", "format": "uri" },         "style": { "type": "string", "enum": ["solid", "outline", "gradient"], "default": "solid" }       }     },     "cta_secondary": {       "type": "object",       "title": "CTA Secundario",       "properties": {         "text": { "type": "string", "title": "Texto" },         "url": { "type": "string", "format": "uri" },         "icon": { "type": "string", "title": "Icono Lucide", "ui:widget": "icon-picker" }       }     },     "background_image": {       "type": "string",       "title": "Imagen de Fondo",       "format": "uri",       "ui:widget": "image-upload"     },     "overlay_opacity": {       "type": "number",       "title": "Opacidad del Overlay",       "minimum": 0,       "maximum": 100,       "default": 50,       "ui:widget": "slider"     },     "text_alignment": {       "type": "string",       "title": "Alineación del Texto",       "enum": ["left", "center", "right"],       "default": "center",       "ui:widget": "button-group"     },     "trust_logos": {       "type": "array",       "title": "Logos de Confianza",       "items": {         "type": "object",         "properties": {           "image": { "type": "string", "format": "uri", "ui:widget": "image-upload" },           "alt": { "type": "string", "title": "Texto alternativo" }         }       },       "ui:options": { "orderable": true, "addable": true, "removable": true }     }   },   "required": ["title", "cta_primary"] }
 
4. REPOSITORIO DE PLANTILLAS PREMIUM
4.1 Investigación de Mercado - Tendencias 2025-2026
Basado en análisis de +500 landing pages SaaS de alto rendimiento (Landingfolio, SaaSFrame, SaaS Landing Page), identificamos las siguientes tendencias clave:
Tendencia	Implementación Recomendada
Story-Driven Heroes	Headlines narrativos que muestran el valor en segundos. Incluir antes/después visual.
CTAs Personalizados	Texto dinámico según segmento. 'Ver ejemplos para tu industria' vs genérico 'Ver más'.
Micro-animaciones	Hover effects, scroll reveals, contadores animados. Framer Motion integrado.
Product Demos Inline	Screenshots interactivos, videos embebidos en hero, tours guiados.
Bold Serif Headlines	Tipografía expresiva para títulos. Gradientes de color en texto.
Minimal Navigation	Sticky headers simples, CTAs persistentes, anchor links a secciones.
Social Proof Visible	Logos, testimonios, stats 'By the Numbers' above the fold.
Mobile-First CRO	Diseño responsive con CTAs táctiles, formularios simplificados.
4.2 Plantillas por Vertical (55 Total)
4.2.1 Empleabilidad (12 plantillas)
Template ID	Nombre	Plan	Bloques
emp_landing_main	Landing Principal Empleo	Starter	5 base
emp_job_seeker	Portal Candidatos	Starter	6 base
emp_employer	Portal Empresas	Professional	7 base + 2 premium
emp_courses_catalog	Catálogo de Cursos	Starter	4 base
emp_course_detail	Detalle de Curso	Starter	6 base
emp_success_stories	Casos de Éxito	Professional	5 base + 3 premium
emp_about	Sobre Nosotros	Starter	5 base
emp_contact	Contacto	Starter	3 base
emp_blog_list	Blog - Listado	Professional	4 base
emp_blog_post	Blog - Artículo	Professional	5 base
emp_premium_landing	Landing Premium	Enterprise	8 base + 6 premium
emp_pricing	Planes y Precios	Professional	4 base
 
4.2.2 AgroConecta (10 plantillas)
Template ID	Nombre	Plan	Bloques
agro_landing_main	Landing Marketplace	Starter	6 base
agro_producer_profile	Perfil Productor	Starter	5 base
agro_product_catalog	Catálogo Productos	Starter	4 base
agro_product_detail	Detalle Producto	Starter	6 base
agro_traceability	Página Trazabilidad	Professional	5 base + 2 premium
agro_how_it_works	Cómo Funciona	Starter	5 base
agro_for_producers	Para Productores	Professional	6 base + 3 premium
agro_sustainability	Sostenibilidad	Professional	5 base + 2 premium
agro_recipes	Recetas del Campo	Starter	4 base
agro_premium_landing	Landing Premium	Enterprise	7 base + 5 premium
4.2.3 ComercioConecta (9 plantillas)
Template ID	Nombre	Plan	Bloques
com_landing_main	Landing Comercio Local	Starter	5 base
com_merchant_profile	Perfil Comercio	Starter	5 base
com_flash_offers	Ofertas Flash	Professional	4 base + 2 premium
com_directory	Directorio Comercios	Starter	4 base
com_for_merchants	Para Comercios	Professional	6 base + 2 premium
com_loyalty	Programa Fidelización	Professional	5 base + 1 premium
com_events	Eventos Locales	Starter	4 base
com_neighborhoods	Por Barrios	Starter	4 base
com_premium_landing	Landing Premium	Enterprise	6 base + 5 premium
 
4.2.4 ServiciosConecta (9 plantillas)
Template ID	Nombre	Plan	Bloques
srv_landing_main	Landing Servicios	Starter	5 base
srv_provider_profile	Perfil Profesional	Starter	6 base
srv_service_detail	Detalle Servicio	Starter	5 base
srv_booking	Reserva de Citas	Professional	4 base + 2 premium
srv_for_professionals	Para Profesionales	Professional	6 base + 3 premium
srv_categories	Categorías Servicios	Starter	4 base
srv_testimonials	Reseñas Clientes	Professional	4 base + 1 premium
srv_pricing	Planes Profesionales	Professional	4 base
srv_premium_landing	Landing Premium	Enterprise	6 base + 5 premium
4.2.5 Emprendimiento (10 plantillas)
Template ID	Nombre	Plan	Bloques
ent_landing_main	Landing Emprendimiento	Starter	6 base
ent_entrepreneur_profile	Perfil Emprendedor	Starter	5 base
ent_programs	Programas Formación	Professional	5 base + 2 premium
ent_mentoring	Red de Mentores	Professional	5 base + 2 premium
ent_resources	Recursos Gratis	Starter	4 base
ent_success_stories	Casos de Éxito	Professional	5 base + 3 premium
ent_events	Eventos y Workshops	Starter	4 base
ent_funding	Financiación	Professional	5 base + 1 premium
ent_community	Comunidad	Professional	5 base + 2 premium
ent_premium_landing	Landing Premium	Enterprise	7 base + 6 premium
4.2.6 Genéricas Multi-Vertical (5 plantillas)
Template ID	Nombre	Plan	Uso
gen_about	Sobre Nosotros	Starter	Todas las verticales
gen_contact	Contacto	Starter	Todas las verticales
gen_faq	Preguntas Frecuentes	Starter	Todas las verticales
gen_privacy	Política Privacidad	Starter	Todas las verticales
gen_terms	Términos y Condiciones	Starter	Todas las verticales
 
7. ROADMAP DE IMPLEMENTACIÓN
Plan de desarrollo en 8 sprints de 2 semanas. Total estimado: 420-520 horas (€33,600-€41,600 @ €80/h).
Sprint	Entregables	Horas	Dependencias
1 (S1-S2)	Entidades page_template, page_content, block_template. CRUD básico. Migraciones BD.	50-60h	—
2 (S3-S4)	Visual Template Picker UI. Galería con filtros. Preview iframe básico.	60-70h	Sprint 1
3 (S5-S6)	Form Builder dinámico basado en JSON Schema. Validación en tiempo real.	50-60h	Sprint 2
4 (S7-S8)	Sistema de Bloques Base. 25 bloques nativos. Templates Twig.	70-80h	Sprint 3
5 (S9-S10)	20 bloques base adicionales. Integración Design Tokens.	50-60h	Sprint 4
6 (S11-S12)	Integración Aceternity UI (12 componentes). Adaptación a Drupal.	60-70h	Sprint 5
7 (S13-S14)	Integración Magic UI (10 componentes). 55 plantillas premium.	50-70h	Sprint 6
8 (S15-S16)	Permisos RBAC por plan. Testing E2E. Documentación usuario.	30-50h	Sprint 7
7.1 Criterios de Aceptación por Sprint
Sprint 1 - Criterios
•	Entidades page_template y page_content creadas y funcionales
•	CRUD completo accesible via /admin/structure/page-templates y /admin/content/pages
•	Migraciones de BD ejecutables y reversibles
•	Tests unitarios para entidades (>80% cobertura)
•	Integración con Field UI y Views verificada
Sprint 2 - Criterios
•	Galería visual de plantillas con imágenes preview
•	Filtros por categoría, plan y vertical funcionando
•	Preview en iframe actualizable en tiempo real
•	Responsive en tablet y móvil
•	Performance: <2s carga inicial galería
 
8. ANEXOS TÉCNICOS
8.1 Estructura de Archivos del Módulo
modules/custom/jaraba_page_builder/ ├── jaraba_page_builder.info.yml ├── jaraba_page_builder.module ├── jaraba_page_builder.install ├── jaraba_page_builder.routing.yml ├── jaraba_page_builder.permissions.yml ├── jaraba_page_builder.services.yml ├── config/ │   ├── install/ │   │   ├── jaraba_page_builder.settings.yml │   │   └── core.entity_form_display.page_content.*.yml │   └── schema/ │       └── jaraba_page_builder.schema.yml ├── src/ │   ├── Entity/ │   │   ├── PageTemplate.php │   │   ├── PageContent.php │   │   ├── BlockTemplate.php │   │   └── BlockContent.php │   ├── Form/ │   │   ├── PageContentForm.php │   │   ├── PageTemplateForm.php │   │   └── SettingsForm.php │   ├── Controller/ │   │   ├── PageBuilderController.php │   │   ├── TemplatePickerController.php │   │   └── PreviewController.php │   ├── Plugin/ │   │   ├── Block/ │   │   │   └── PageContentBlock.php │   │   └── Field/ │   │       └── FieldFormatter/ │   │           └── PageContentFormatter.php │   ├── Service/ │   │   ├── TemplateManager.php │   │   ├── BlockManager.php │   │   ├── SchemaValidator.php │   │   └── DesignTokensInjector.php │   └── Access/ │       └── PlanBasedAccessCheck.php ├── templates/ │   ├── blocks/ │   │   ├── hero-fullscreen.html.twig │   │   ├── hero-split.html.twig │   │   ├── features-grid.html.twig │   │   └── ... (45 bloques base) │   ├── premium/ │   │   ├── aceternity/ │   │   │   ├── spotlight-effect.html.twig │   │   │   └── ... (12 componentes) │   │   └── magic-ui/ │   │       ├── bento-grid.html.twig │   │       └── ... (10 componentes) │   └── layouts/ │       └── page-content.html.twig ├── js/ │   ├── template-picker.js │   ├── form-builder.js │   ├── preview-handler.js │   └── premium/ │       ├── aceternity-adapter.js │       └── magic-ui-adapter.js └── css/     ├── page-builder-admin.css     └── premium-components.css
8.2 Configuración Recomendada composer.json
{   "require": {     "drupal/group": "^3.0",     "drupal/token": "^1.11",     "drupal/pathauto": "^1.11",     "drupal/metatag": "^2.0",     "drupal/schema_metatag": "^3.0",     "drupal/jsonapi_extras": "^3.23"   },   "require-dev": {     "phpunit/phpunit": "^10.0",     "drupal/core-dev": "^11.0"   } }
8.3 Dependencias Frontend (package.json)
{   "name": "jaraba-page-builder-frontend",   "dependencies": {     "react": "^18.2.0",     "react-dom": "^18.2.0",     "framer-motion": "^11.0.0",     "@radix-ui/react-icons": "^1.3.0",     "lucide-react": "^0.300.0",     "tailwindcss": "^3.4.0",     "alpinejs": "^3.13.0",     "@rjsf/core": "^5.15.0",     "@rjsf/validator-ajv8": "^5.15.0"   } }
 

FIN DEL DOCUMENTO

Este documento contiene toda la información técnica necesaria para que el equipo EDI Google Antigravity implemente el Constructor de Páginas SaaS del Ecosistema Jaraba.

Versión 1.0 | 26 de Enero de 2026 | CONFIDENCIAL
Plataforma de Ecosistemas Digitales S.L. © 2026
