# Plan de Implementacion: Sistema de Navegacion Contextual por Avatar (AvatarNavigationService)

**Fecha**: 2026-02-13
**Autor**: Claude (Asistente IA)
**Version**: 1.0
**Estado**: Implementado
**Spec Origen**: f-103 UX Journey Avatars, f-177 Global Nav System

---

## Tabla de Contenidos

1. [Contexto y Problema](#1-contexto-y-problema)
2. [Arquitectura de la Solucion](#2-arquitectura-de-la-solucion)
   - 2.1 [AvatarNavigationService (Servicio Central)](#21-avatarnavigationservice-servicio-central)
   - 2.2 [Inyeccion en hook_preprocess_page()](#22-inyeccion-en-hook_preprocess_page)
   - 2.3 [Parcial Twig: _avatar-nav.html.twig](#23-parcial-twig-_avatar-navhtmltwig)
   - 2.4 [Integracion en _header.html.twig](#24-integracion-en-_headerhtmltwig)
   - 2.5 [SCSS: _avatar-nav.scss](#25-scss-_avatar-navscss)
   - 2.6 [Theme Settings: enable_avatar_nav](#26-theme-settings-enable_avatar_nav)
   - 2.7 [Body Classes via hook_preprocess_html](#27-body-classes-via-hook_preprocess_html)
3. [Mapeo de Avatares a Items de Navegacion](#3-mapeo-de-avatares-a-items-de-navegacion)
4. [Tabla de Correspondencia con Especificaciones Tecnicas](#4-tabla-de-correspondencia-con-especificaciones-tecnicas)
5. [Directrices de Cumplimiento](#5-directrices-de-cumplimiento)
6. [Ficheros Creados/Modificados](#6-ficheros-creadosmodificados)
7. [Verificacion](#7-verificacion)

---

## 1. Contexto y Problema

### Diagnostico: Islas sin Mapa

Las 324+ rutas frontend del ecosistema SaaS operan como islas aisladas sin navegacion dinamica. El header muestra unicamente 5 enlaces estaticos de marketing (Empleo, Talento, Emprender, Comercio, Instituciones). Un usuario autenticado solo ve "Mi cuenta" y "Cerrar sesion" sin ningun camino visible hacia dashboards, herramientas ni modulos especificos de su rol.

### Building Blocks Existentes No Conectados

| Servicio Existente | Funcionalidad | Limitacion |
|---|---|---|
| `AvatarDetectionService` | Detecta avatar via cascada 4 niveles (Domain > Path/UTM > Group > Rol) | Solo se usa para redirect y copilot |
| `CopilotContextService` | Conoce avatar + vertical + plan + tenant | Solo sirve al copiloto IA |
| `EmployabilityMenuService` | Menu contextual funcional con items, iconos, active state | Solo cubre 1 de 7+ verticales (Empleabilidad) |
| `DashboardRedirectController` | Redirige `/dashboard` segun avatar | El usuario debe conocer la URL |

### Gap Analysis vs Spec f-103

La spec f-103 (UX Journey Avatars) define una Journey Engine completa de 3 capas:
1. **Context Engine** (AvatarDetectionService) — existente
2. **AI Decision Engine** — no implementado (fuera de alcance Fase 1)
3. **Presentation Engine** — parcialmente implementado

Esta Fase 1 conecta los building blocks existentes para crear navegacion contextual visible en TODAS las paginas frontend, sin necesidad de la Journey Engine completa (460-600h de la spec 103).

---

## 2. Arquitectura de la Solucion

### 2.1 AvatarNavigationService (Servicio Central)

- **Ubicacion**: `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php`
- **Patron**: Generalizacion de `EmployabilityMenuService` (que cubre solo Empleabilidad)
- **Registro**: `ecosistema_jaraba_core.services.yml` como `ecosistema_jaraba_core.avatar_navigation`
- **Dependencias inyectadas**:
  - `AvatarDetectionService` — detecta avatar del usuario
  - `CurrentRouteMatch` — determina ruta activa para highlight
  - `AccountProxy` — verifica autenticacion

**Metodos publicos**:

| Metodo | Retorno | Descripcion |
|---|---|---|
| `getNavigationItems()` | `array` | Items de navegacion segun avatar detectado |
| `getAvatar()` | `string` | Tipo de avatar actual |
| `getAvatarLabel()` | `string` | Etiqueta traducible del avatar |

**Estructura de cada item**:
```php
[
  'id' => string,           // Identificador unico
  'label' => TranslatableMarkup, // Texto traducible via $this->t()
  'url' => string,           // URL resuelta via Url::fromRoute()
  'icon_category' => string, // Categoria del icono SVG
  'icon_name' => string,     // Nombre del icono
  'weight' => int,           // Orden de presentacion
  'active' => bool,          // TRUE si ruta actual coincide
]
```

**Resolucion segura de URLs**: Cada ruta se resuelve con `try/catch` via `Url::fromRoute()`. Si el modulo que define la ruta no esta instalado, el item se omite silenciosamente. Esto permite que la navegacion sea resiliente a modulos opcionales.

### 2.2 Inyeccion en hook_preprocess_page()

- **Fichero**: `ecosistema_jaraba_theme.theme`, funcion `ecosistema_jaraba_theme_preprocess_page()`
- **Ubicacion**: Despues del bloque de COPILOT CONTEXT
- **Guard**: `isAdminRoute()` para no mostrar en rutas admin + `theme_get_setting('enable_avatar_nav')` para toggle

```php
$variables['avatar_nav'] = [
  'items' => $service->getNavigationItems(),
  'avatar' => $service->getAvatar(),
  'avatar_label' => $service->getAvatarLabel(),
];
```

### 2.3 Parcial Twig: _avatar-nav.html.twig

- **Ubicacion**: `templates/partials/_avatar-nav.html.twig`
- **Estructura HTML**: `<nav>` con `role="navigation"`, `aria-label` traducible
- **Badge de avatar**: Muestra el tipo de usuario (ej: "Candidato")
- **Items**: Lista `<ul>` con enlaces `<a>`, iconos via `jaraba_icon()`, clase `--active`
- **Accesibilidad**: `aria-current="page"` en item activo
- **Responsive**:
  - Mobile: bottom nav fija con iconos de 24px
  - Desktop: barra horizontal bajo header con iconos de 20px + labels

### 2.4 Integracion en _header.html.twig (Enfoque DRY)

La barra se incluye **dentro de `_header.html.twig`** al final, despues del Mobile Menu Overlay. Esto garantiza propagacion automatica a las ~33 page templates sin tocarlas individualmente.

```twig
{% if avatar_nav is defined and avatar_nav and avatar_nav.items|length > 0 %}
  {% include '@ecosistema_jaraba_theme/partials/_avatar-nav.html.twig' with {
    items: avatar_nav.items,
    avatar: avatar_nav.avatar,
    avatar_label: avatar_nav.avatar_label,
  } only %}
{% endif %}
```

**Templates con `only` actualizadas**: 7 page templates usan `only` al incluir el header, bloqueando variables del scope padre. Se anadio `'avatar_nav': avatar_nav|default(null)` a cada una:

1. `page--dashboard.html.twig`
2. `page--andalucia-ei.html.twig`
3. `page--comercio-marketplace.html.twig`
4. `page--emprendimiento--bmc.html.twig`
5. `page--emprendimiento--experimentos-gestion.html.twig`
6. `page--emprendimiento--hipotesis.html.twig`
7. `page--heatmap--analytics.html.twig`

Las demas templates (sin `only`) heredan `avatar_nav` automaticamente del scope padre.

### 2.5 SCSS: _avatar-nav.scss

- **Ubicacion**: `scss/components/_avatar-nav.scss`
- **Importado en**: `scss/main.scss` via `@use 'components/avatar-nav'`
- **Compilado a**: `css/main.css`
- **Patron**: Mobile-first, BEM
- **Variables**: Solo `var(--ej-*)` con fallbacks SCSS, sin `$ej-*` locales (SSOT)

**Clases BEM**:

| Clase | Descripcion |
|---|---|
| `.avatar-nav` | Contenedor principal |
| `.avatar-nav__badge` | Badge del avatar |
| `.avatar-nav__list` | Lista de items |
| `.avatar-nav__item` | Cada item |
| `.avatar-nav__link` | Enlace con icono + label |
| `.avatar-nav__link--active` | Estado activo |
| `.avatar-nav__icon` | Contenedor del icono SVG |
| `.avatar-nav__label` | Texto del enlace |

**Responsive**:
- **Mobile** (<768px): `position: fixed; bottom: 0; z-index: 900;` bottom nav con iconos 24px, labels 10px, `env(safe-area-inset-bottom)` para iOS
- **Desktop** (>=768px): barra horizontal relativa, iconos 20px, labels 13px, badge de avatar visible

### 2.6 Theme Settings: enable_avatar_nav

- **Tab**: Encabezado (Header) > Navegacion contextual
- **Campo**: `enable_avatar_nav` (checkbox, default TRUE)
- **Efecto**: Si desactivado, no se inyecta `avatar_nav` en preprocess

### 2.7 Body Classes via hook_preprocess_html

- **Clase**: `.has-avatar-nav` cuando hay items de navegacion
- **Guard**: Mismas condiciones que preprocess_page (no admin, setting activo)
- **Uso**: CSS defensivo `body.has-avatar-nav { padding-bottom: 60px; }` en mobile para la bottom nav fija

---

## 3. Mapeo de Avatares a Items de Navegacion

| Avatar | Items (label / ruta / icono) |
|--------|-----|
| **jobseeker** | Mi perfil (`jaraba_candidate.my_profile`, ui/user), Ofertas (`jaraba_job_board.search`, verticals/briefcase), Candidaturas (`jaraba_job_board.my_applications`, actions/check), Formacion (`jaraba_lms.my_learning`, ui/book), Itinerarios (`jaraba_paths.catalog`, ui/rocket) |
| **recruiter** | Panel (`jaraba_job_board.employer_dashboard`, analytics/gauge), Mis ofertas (`jaraba_job_board.employer_jobs`, business/diagnostic), Candidatos (`jaraba_job_board.employer_applications`, ui/users), Mi empresa (`jaraba_job_board.my_company`, business/company) |
| **entrepreneur** | Dashboard (`jaraba_business_tools.entrepreneur_dashboard`, analytics/dashboard), Canvas (`jaraba_business_tools.canvas_list`, business/canvas), Itinerarios (`jaraba_paths.catalog`, ui/rocket), Formacion (`jaraba_lms.my_learning`, ui/book), Mentores (`jaraba_mentoring.mentor_catalog`, ui/users) |
| **producer** | Mi empresa (`jaraba_job_board.my_company`, business/company), Marketplace (`jaraba_agroconecta.marketplace`, commerce/store), Formacion (`jaraba_lms.my_learning`, ui/book) |
| **merchant** | Mi comercio (`jaraba_comercio_conecta.merchant_portal`, commerce/store), Productos (`jaraba_comercio_conecta.merchant_portal.products`, commerce/shopping-bag), Stock (`jaraba_comercio_conecta.merchant_portal.stock`, commerce/barcode), Analiticas (`jaraba_comercio_conecta.merchant_portal.analytics`, analytics/chart-bar) |
| **service_provider** | Mi servicio (`jaraba_servicios_conecta.provider_portal`, business/company), Servicios (`jaraba_servicios_conecta.provider_portal.offerings`, ui/settings), Reservas (`jaraba_servicios_conecta.provider_portal.bookings`, ui/calendar), Calendario (`jaraba_servicios_conecta.provider_portal.calendar`, ui/clock) |
| **student** | Mis cursos (`jaraba_lms.my_learning`, ui/book), Catalogo (`jaraba_lms.catalog`, ui/search), Certificados (`jaraba_lms.my_certificates`, business/achievement), Itinerarios (`jaraba_paths.catalog`, ui/rocket) |
| **mentor** | Dashboard (`jaraba_mentoring.mentor_dashboard`, analytics/dashboard), Mentores (`jaraba_mentoring.mentor_catalog`, ui/users), Formacion (`jaraba_lms.my_learning`, ui/book) |
| **tenant_admin** / **admin** | Dashboard (`ecosistema_jaraba_core.tenant.dashboard`, analytics/dashboard), Plan (`ecosistema_jaraba_core.tenant.change_plan`, business/money), Ayuda (`jaraba_tenant_knowledge.help_center`, ui/help-circle) |
| **anonymous** | Empleo (`ecosistema_jaraba_core.landing_empleo`, verticals/briefcase), Emprender (`ecosistema_jaraba_core.landing_emprender`, verticals/rocket), Comercio (`ecosistema_jaraba_core.landing_comercio`, commerce/store), Cursos (`jaraba_lms.catalog`, ui/book), Registrarse (`user.register`, ui/user) |

---

## 4. Tabla de Correspondencia con Especificaciones Tecnicas

| Spec | Seccion | Elemento Implementado | Estado |
|------|---------|----------------------|--------|
| f-103 UX Journey Avatars | 3.1 Context Engine | AvatarDetectionService (existente) + AvatarNavigationService (nuevo) | Parcial (Capa 1 de 3) |
| f-103 UX Journey Avatars | 3.3 Presentation Engine | _avatar-nav.html.twig + SCSS responsive | Parcial (sin AI Decision Engine) |
| f-103 UX Journey Avatars | 19 Avatares | 10 avatares cubiertos (9 autenticados + 1 anonimo) | Cubierto |
| f-103 UX Journey Avatars | Zero-Click Intelligence | Active state highlight por ruta actual | Parcial (sin AI predictiva) |
| f-103 UX Journey Avatars | Max 3 clicks | Dashboard visible desde cualquier pagina via nav contextual | Cubierto |
| f-104 Admin Center | Sidebar navegacion | Patron reutilizado para admin (ya existente) | Existente |
| f-177 Global Nav System | 5 Header Types | Dispatcher header existente + avatar-nav debajo | Compatible |
| f-177 Global Nav System | Mobile Menu | Bottom nav fija en movil | Nuevo |
| f-177 Global Nav System | User Menu | Sustituye "Mi cuenta" estatico por nav contextual | Mejorado |
| Directriz #14 Nuclear | Zero Region Policy | Templates limpias, sin page.content | Cumplido |
| Directriz SCSS-001 | Dart Sass @use | SCSS con @use, sin @import, variables via @use | Cumplido |
| Directriz i18n | Textos traducibles | Todos los labels con t() y {% trans %} | Cumplido |
| Directriz Theming | CSS Custom Properties | var(--ej-*) con fallbacks SCSS | Cumplido |
| Directriz Icons | jaraba_icon SVG | Todos los iconos via jaraba_icon(), NO emojis | Cumplido |
| Directriz Mobile | Mobile-first | SCSS base movil, breakpoints para desktop | Cumplido |
| Directriz Body Classes | hook_preprocess_html | `.has-avatar-nav` cuando hay items de nav | Cumplido |

---

## 5. Directrices de Cumplimiento

### 5.1 Zero Region Policy
- El parcial `_avatar-nav.html.twig` NO usa `{{ page.* }}` ni bloques de Drupal
- Se inyecta via `{% include %}` con datos de `hook_preprocess_page()`
- Guard `{% if avatar_nav %}` evita render vacio

### 5.2 SCSS SSOT (Single Source of Truth)
- NO se definen variables `$ej-*` en `_avatar-nav.scss`
- Se consumen solo `var(--ej-*)` con fallback hardcoded
- Ejemplo: `background: var(--ej-color-corporate, #233D63);`

### 5.3 Dart Sass Moderno
- `@use '../variables' as *;` para acceso a breakpoints y mixins
- Sin `@import`, sin `darken()`/`lighten()`
- Compilacion con `--style=compressed`

### 5.4 Textos Traducibles
- PHP: `$this->t('Mi perfil')` en AvatarNavigationService
- Twig: `{% trans %}Navegacion contextual{% endtrans %}` en _avatar-nav.html.twig
- Accesibilidad: `aria-label="{% trans %}Contextual navigation{% endtrans %}"`

### 5.5 Iconos SVG (jaraba_icon)
- Cada item usa `jaraba_icon(category, name, options)`
- Categorias: `ui`, `verticals`, `analytics`, `business`, `commerce`, `actions`
- Tamano: `20px` en desktop, `24px` en mobile

### 5.6 Theme Settings Configurable
- Campo: `enable_avatar_nav` (checkbox, default TRUE)
- Tab: Encabezado > Navegacion contextual
- Guard en preprocess: si desactivado, no inyectar `avatar_nav`

### 5.7 Body Class
- `.has-avatar-nav` para CSS defensivo (padding-bottom en mobile)
- Solo en rutas no-admin y con setting activo

---

## 6. Ficheros Creados/Modificados

| Fichero | Accion | Descripcion |
|---------|--------|-------------|
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | CREADO | Servicio central de navegacion por avatar (10 avatares, resolucion segura de URLs) |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | MODIFICADO | Registrado `ecosistema_jaraba_core.avatar_navigation` con 3 dependencias |
| `ecosistema_jaraba_theme/templates/partials/_avatar-nav.html.twig` | CREADO | Parcial de navegacion contextual con accesibilidad completa |
| `ecosistema_jaraba_theme/scss/components/_avatar-nav.scss` | CREADO | Estilos BEM mobile-first (bottom nav + barra horizontal desktop) |
| `ecosistema_jaraba_theme/scss/main.scss` | MODIFICADO | Anadido `@use 'components/avatar-nav'` |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | MODIFICADO | Inyeccion en preprocess_page, preprocess_html, y form_alter |
| `ecosistema_jaraba_theme/templates/partials/_header.html.twig` | MODIFICADO | Include de _avatar-nav al final |
| `ecosistema_jaraba_theme/css/main.css` | COMPILADO | Recompilado con nuevos estilos |
| `page--dashboard.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--andalucia-ei.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--comercio-marketplace.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--emprendimiento--bmc.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--emprendimiento--experimentos-gestion.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--emprendimiento--hipotesis.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |
| `page--heatmap--analytics.html.twig` | MODIFICADO | Anadido avatar_nav al include with only |

---

## 7. Verificacion

1. `lando drush cr` — limpiar cache
2. Login como candidato — ver nav: Mi perfil, Ofertas, Candidaturas, Formacion, Itinerarios
3. Login como empleador — ver nav: Panel, Mis ofertas, Candidatos, Mi empresa
4. Login como emprendedor — ver nav: Dashboard, Canvas, Itinerarios, Formacion, Mentores
5. Acceder anonimo — ver nav: Empleo, Emprender, Comercio, Cursos, Registrarse
6. Verificar mobile (viewport < 768px) — bottom nav fija con iconos
7. Verificar que item activo se resalta segun ruta actual
8. Verificar accesibilidad: tab navigation funciona, aria-labels presentes
9. Verificar que Theme Settings > Encabezado > "Navegacion contextual" toggle funciona
10. Verificar que en rutas admin NO aparece la nav contextual
