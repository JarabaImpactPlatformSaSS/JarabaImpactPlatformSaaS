# 🏆 Plan de Implementación: Casos de Éxito Clase Mundial

> **Tipo:** Plan de implementación técnico
> **Fecha:** 2026-02-27
> **Roles:** Arquitecto SaaS · Ingeniero Drupal · Ingeniero UX · Diseñador Theming · GrapesJS · SEO/GEO · IA
> **Estado:** PENDIENTE DE APROBACIÓN

---

## 📑 Tabla de Contenidos

1. [Descripción del Problema](#1-descripción-del-problema)
2. [Revisión Requerida por el Usuario](#2-revisión-requerida-por-el-usuario)
3. [Arquitectura Propuesta](#3-arquitectura-propuesta)
4. [Cambios Propuestos](#4-cambios-propuestos)
   - 4.1 [Módulo `jaraba_success_cases`](#41-módulo-jaraba_success_cases)
   - 4.2 [Tema `ecosistema_jaraba_theme`](#42-tema-ecosistema_jaraba_theme)
   - 4.3 [Page Builder Integration](#43-page-builder-integration)
   - 4.4 [Módulo Core — Métricas SSOT](#44-módulo-core--métricas-ssot)
5. [Tabla de Correspondencia Técnica](#5-tabla-de-correspondencia-técnica)
6. [Cumplimiento de Directrices del Proyecto](#6-cumplimiento-de-directrices-del-proyecto)
7. [Plan de Verificación](#7-plan-de-verificación)

---

## 1. Descripción del Problema

### Estado actual

Los casos de éxito están dispersos en los 4 meta-sitios con:

| Problema | Impacto |
|----------|---------|
| **Sin entidad Drupal centralizada** | Los testimonios están hardcodeados en las plantillas de los metasitios y en Page Builder blocks |
| **Framing inconsistente** | Mismas personas aparecen con datos diferentes en cada sitio |
| **Métricas discrepantes** | +50.000 vs +15.000, 5 ODS vs 6 ODS |
| **Sin vídeo embed** | Los vídeos están como raw files sin componente responsivo |
| **Sin SEO estructurado** | No hay schema.org `Review`, `Person`, `Organization` |

### Infraestructura existente reutilizable

| Componente | Módulo | Lo que reutilizamos |
|------------|--------|---------------------|
| **Testimonial Slider** | `jaraba_page_builder` | Template `testimonials-slider.html.twig`, SCSS `_testimonials-3d.scss` |
| **Testimonial 3D** | `jaraba_page_builder` | Template `testimonials-3d.html.twig`, JS `jaraba-testimonials-3d.js` |
| **SDC Card testimonial** | `ecosistema_jaraba_theme` | Variante `testimonial` del componente `Card` |
| **Article patrón** | `jaraba_content_hub` | Blog pattern (Controller → `#theme` → Template → Page template) |
| **Slide Panel** | `ecosistema_jaraba_theme` | CRUD sin abandonar página |

---

## 2. Revisión Requerida por el Usuario

> [!IMPORTANT]
> **Decisión 1: ¿Módulo nuevo o feature dentro de `ecosistema_jaraba_core`?**
> Propongo un **módulo nuevo** `jaraba_success_cases` porque:
> - Tiene su propia entidad con Field UI
> - Puede desactivarse independientemente
> - Sigue el patrón de verticales (`jaraba_content_hub`, `jaraba_customer_success`)

> [!WARNING]
> **Decisión 2: ¿Content Entity o Nodes con Bundle?**
> Propongo **Content Entity custom** (no nodos Drupal) porque:
> - Es consistente con el 100% del proyecto (ningún módulo usa nodos)
> - Control total sobre campos, sin overhead de revisiones/workflows de nodos
> - Integración limpia con Views y Field UI via `field_ui_base_route`

> [!CAUTION]
> **Decisión 3: Permisos — ¿quién puede crear/editar casos?**
> Opciones: (a) Solo `site_admin`, (b) `site_admin + content_editor`, (c) Definir nuevo rol `success_case_manager`
> Recomendación: opción (a) por simplicidad — solo administradores SaaS.

---

## 3. Arquitectura Propuesta

### 3.1 Flujo de datos

```
docs/assets/casos-de-exito/    ← Recursos fuente (briefs, fotos, vídeos)
        ↓ (Seeder Script)
Content Entity: SuccessCase    ← Entidad Drupal centralizada
        ↓ (View Modes)
4 Meta-sitios:
  ├── pepejaraba.com/casos-de-exito       (view mode: personal_story)
  ├── jarabaimpact.com/impacto            (view mode: business_impact)
  ├── plataformadeecosistemas.es/impacto  (view mode: institutional_evidence)
  └── /instituciones                       (view mode: testimonial_card)
```

### 3.2 Cadena de renderizado (Frontend Page Pattern)

```
Ruta: jaraba_success_cases.list
  → SuccessCasesController::list()
    → #theme = success_cases_list
      → success-cases-list.html.twig
        → Envuelto por page--success-cases.html.twig (Zero Region Policy)
          → Usa {{ clean_content }}
```

### 3.3 View modes por meta-sitio

| View Mode | Meta-sitio | Framing | Secciones |
|-----------|------------|---------|-----------|
| `personal_story` | pepejaraba.com | Historia personal | Reto → Solución → Resultado + Quote largo + Foto + Vídeo inline |
| `business_impact` | jarabaimpact.com | ROI empresarial | KPIs + Métricas + Quote + CTA |
| `institutional_evidence` | PED | Evidencia pública | Stats agregados + ODS + Programas + Testimonial card grid |
| `testimonial_card` | /instituciones | Card de testimonio | Foto + Quote corto + Rating + Video embed |

### 3.4 Diagrama de entidad

```
SuccessCase (Content Entity)
├── id (INT, auto)
├── uuid (UUID)
├── label / name (VARCHAR 255) ← Nombre completo
├── slug (VARCHAR 128) ← URL-safe slug
├── status (BOOLEAN) ← Publicado/borrador
├── created / changed (TIMESTAMP)
├── uid (INT, FK → users) ← Autor
│
├── [Datos Personales]
│   ├── profession (VARCHAR 255)
│   ├── company (VARCHAR 255)
│   ├── sector (VARCHAR 255)
│   ├── location (VARCHAR 255)
│   ├── website (VARCHAR 512)
│   ├── linkedin (VARCHAR 512)
│
├── [Historia Narrativa]
│   ├── challenge_before (TEXT, formatted)
│   ├── solution_during (TEXT, formatted)
│   ├── result_after (TEXT, formatted)
│
├── [Quotes]
│   ├── quote_short (VARCHAR 512)
│   ├── quote_long (TEXT)
│
├── [Métricas]
│   ├── metrics (MAP/JSON) ← key-value flexible
│   ├── rating (INT 1-5)
│
├── [Programa/Vertical]
│   ├── program_name (VARCHAR 255)
│   ├── vertical (ENTITY_REFERENCE → taxonomy)
│   ├── program_funder (VARCHAR 255)
│   ├── program_year (VARCHAR 32)
│
├── [Multimedia - via Field UI]
│   ├── photo_profile (entity_reference → media:image)
│   ├── photo_before (entity_reference → media:image)
│   ├── logo_company (entity_reference → media:image)
│   ├── video_testimonial (entity_reference → media:video | remote_video)
│   ├── video_clip_short (entity_reference → media:video)
│
├── [SEO]
│   ├── meta_description (VARCHAR 320)
│   ├── schema_type (VARCHAR 64) ← Review, Testimonial, Person...
│
└── [Control]
    ├── weight (INT) ← Orden de presentación
    └── featured (BOOLEAN) ← Destacado en home/header
```

---

## 4. Cambios Propuestos

---

### 4.1 Módulo `jaraba_success_cases`

Nuevo módulo en `web/modules/custom/jaraba_success_cases/`.

#### [NEW] [jaraba_success_cases.info.yml](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/jaraba_success_cases.info.yml)

Metadatos del módulo: nombre, tipo `module`, dependencias (`drupal:system`, `drupal:user`, `drupal:file`, `drupal:media`, `ecosistema_jaraba_core`).

#### [NEW] [SuccessCase.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/Entity/SuccessCase.php)

Content Entity con annotation `@ContentEntityType`:
- **Handlers:** `list_builder`, `views_data` (`EntityViewsData`), `form` (default, add, edit, delete), `access`, `route_provider` (`AdminHtmlRouteProvider`)
- **Keys:** id, uuid, label, owner (`uid`), status, created, changed
- **Links:** canonical, add-form, edit-form, delete-form, collection
- **`field_ui_base_route`:** `entity.success_case.settings`
- **Campos base:** Todos los de la sección 3.4, definidos en `baseFieldDefinitions()`
- **PHP 8.4:** Sin constructor promotion para propiedades heredadas de `ContentEntityBase` (Regla DRUPAL11-002)

#### [NEW] [SuccessCaseListBuilder.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/SuccessCaseListBuilder.php)

Extiende `EntityListBuilder`. Columnas: Nombre, Profesión, Vertical, Publicado, Destacado, Acciones.

#### [NEW] [SuccessCaseForm.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/Form/SuccessCaseForm.php)

Extiende `ContentEntityForm`. Organiza campos en fieldsets: Datos Personales, Historia Narrativa, Quotes, Métricas, Programa, Multimedia, SEO.
Los guidelines de formato se ocultan con `_jaraba_success_cases_hide_format_guidelines()` (patrón slide-panel).

#### [NEW] [SuccessCaseAccessControlHandler.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/SuccessCaseAccessControlHandler.php)

Permisos: `administer success cases`, `view published success cases`, `view unpublished success cases`.

#### [NEW] [SuccessCaseSettingsForm.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/Form/SuccessCaseSettingsForm.php)

Formulario en `/admin/structure/success-case` para Field UI.

#### [NEW] [SuccessCasesController.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/src/Controller/SuccessCasesController.php)

**Rutas frontend:**
- `list()` → Grid de todos los casos publicados, con filtros por vertical + paginación
- `detail($slug)` → Vista detallada de un caso individual
- `apiList()` → JSON API para cargar en GrapesJS blocks

**Preprocess en controlador:**
Extrae datos primitivos de la entidad (nombre, profesión, fotos, vídeos) para pasar al template como variables planas. Media: usa `ImageStyle::load('success_case_card')->buildUrl($uri)` para responsive images con srcset.

**Detección de meta-sitio:**
El controlador detecta el meta-sitio activo (vía `MetaSiteDetectionService` si existe, o vía hostname) para seleccionar el view mode correcto y el framing adecuado.

#### [NEW] [jaraba_success_cases.routing.yml](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/jaraba_success_cases.routing.yml)

Rutas frontend:
```yaml
jaraba_success_cases.list:
  path: '/casos-de-exito'
  defaults:
    _controller: '\Drupal\jaraba_success_cases\Controller\SuccessCasesController::list'
    _title: 'Casos de Éxito'

jaraba_success_cases.detail:
  path: '/caso-de-exito/{slug}'
  defaults:
    _controller: '\Drupal\jaraba_success_cases\Controller\SuccessCasesController::detail'

jaraba_success_cases.api.list:
  path: '/api/success-cases'
  defaults:
    _controller: '\Drupal\jaraba_success_cases\Controller\SuccessCasesController::apiList'
```

Ruta admin:
```yaml
entity.success_case.settings:
  path: '/admin/structure/success-case'
```

#### [NEW] YAML de navegación

- `jaraba_success_cases.links.menu.yml` → parent: `system.admin_structure`
- `jaraba_success_cases.links.task.yml` → tab en `/admin/content` via `base_route: system.admin_content`
- `jaraba_success_cases.links.action.yml` → botón "Añadir Caso de Éxito" en collection
- `jaraba_success_cases.permissions.yml` → 3 permisos granulares

#### [NEW] [jaraba_success_cases.module](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/jaraba_success_cases.module)

- `hook_theme()` → registra 3 templates: `success_cases_list`, `success_case_detail`, `success_case_card`
- `hook_theme_suggestions_page_alter()` → `page__success_cases` para las rutas `jaraba_success_cases.*`
- `hook_preprocess_html()` → body classes `page-success-cases`, `full-width-layout`, `page--clean-layout`
- `template_preprocess_success_case_card()` → extrae datos primitivos, responsive images, ODS badges
- `hook_form_alter()` → ocultar format guidelines para formularios de SuccessCase

#### [NEW] Templates Twig del módulo

```
templates/
├── success-cases-list.html.twig       ← Grid responsivo con filtros + KPIs globales
├── success-case-detail.html.twig      ← Vista detallada individual
├── success-case-card.html.twig        ← Card reutilizable (usada en list + PB)
└── success-case-metrics-bar.html.twig ← Barra de métricas globales (SSOT)
```

**success-cases-list.html.twig:**
Hero con gradiente + KPIs del ` _metricas-globales.md` SSOT + filtros por vertical + grid de cards responsivo (3 cols desktop, 2 tablet, 1 mobile) + paginación.

**success-case-detail.html.twig:**
Header con foto + nombre + vertical badge → Sección "Reto" (con icono challenge) → "Solución" → "Resultado" con métricas → Video embed responsivo (lite-youtube-embed) → Quote largo con comilla decorativa → CTA sección.

**success-case-card.html.twig:**
Foto circular → Nombre + Profesión → Quote corto → Vertical badge → Rating (si hay) → CTA "Ver caso completo".
Usa BEM: `.success-card`, `.success-card__avatar`, `.success-card__quote`, etc.

#### [NEW] [jaraba_success_cases.libraries.yml](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/jaraba_success_cases.libraries.yml)

```yaml
success-cases:
  css:
    component:
      css/jaraba-success-cases.css: {}
  js:
    js/success-cases.js: {}
  dependencies:
    - core/once
    - core/drupal
    - ecosistema_jaraba_core/global
```

#### [NEW] SCSS Pipeline

```
scss/
├── _variables.scss        ← $sc-primary, $sc-accent (derivados de paleta Jaraba)
├── _list.scss             ← Grid, filtros, KPIs hero
├── _detail.scss           ← Vista detallada, vídeo embed, quote
├── _card.scss             ← Success case card con hover 3D lift
├── _metrics-bar.scss      ← Barra de métricas con contadores animados
└── main.scss              ← Entry point con @use de cada parcial
```

Cada parcial usa `@use 'variables' as *;` + `@use 'sass:color';` (Dart Sass module system).
Colores: `var(--ej-color-corporate, #233D63)`, `var(--ej-color-impulse, #FF8C42)`, `var(--ej-color-innovation, #00A9A5)`.
Font: `var(--ej-font-family, 'Outfit', sans-serif)`.
Premium effects: glassmorphism cards, hover 3D lift, shine-on-hover, counter animation.

#### [NEW] [Seeder Script](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_success_cases/scripts/seed_success_cases.php)

Script ejecutable con `drush scr` que lee los `brief.md` de `docs/assets/casos-de-exito/` y crea entidades SuccessCase con datos pre-poblados. Busca Media entities existentes o crea nuevas para las fotos.

---

### 4.2 Tema `ecosistema_jaraba_theme`

#### [NEW] [page--success-cases.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--success-cases.html.twig)

Page template siguiendo Zero Region Policy exacta del workflow `frontend-page-pattern.md`:
- `{{ clean_content }}` para el contenido del controlador
- `{% include '_header.html.twig' %}` con `avatar_nav`
- `{% include '_footer.html.twig' %}`
- `{{ attach_library('ecosistema_jaraba_theme/global') }}`

#### [MODIFY] [ecosistema_jaraba_theme.theme](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme)

Añadir en `hook_theme_suggestions_page_alter()`:
```php
if (str_starts_with($route_name, 'jaraba_success_cases.')) {
    $suggestions[] = 'page__success_cases';
}
```

Añadir en `hook_preprocess_html()`:
```php
if (str_starts_with($route_name, 'jaraba_success_cases.')) {
    $variables['attributes']['class'][] = 'page-success-cases';
    $variables['attributes']['class'][] = 'full-width-layout';
    $variables['attributes']['class'][] = 'page--clean-layout';
}
```

#### [MODIFY] [_content-hub.scss](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/scss/_content-hub.scss) (opcional)

Si los success cases comparten layout con el blog (grid de cards + paginación), reutilizar clases existentes (`.blog-pagination`, `.author-bio`). Alternativa: importar parciales del módulo.

---

### 4.3 Page Builder Integration

#### [NEW] Bloque GrapesJS: `success-cases-slider`

Un nuevo bloque de Page Builder que se alimenta del API `/api/success-cases` para mostrar success cases directamente en páginas creadas con GrapesJS. Usa el componente `testimonials-slider.html.twig` existente, mapeando los campos:

| Campo SuccessCase | Campo Testimonial Slider |
|-------------------|--------------------------|
| `name` | `author_name` |
| `profession` | `author_title` |
| `company` | `author_company` |
| `photo_profile` | `author_image` |
| `quote_short` | `quote` |
| `rating` | `rating` |

#### [NEW] Config YAML de template PB

```yaml
# config/install/jaraba_page_builder.template.success_cases_slider.yml
id: success_cases_slider
label: 'Casos de Éxito — Slider'
category: 'Testimonials'
# Usa la misma estructura de datos que testimonials-slider
```

---

### 4.4 Módulo Core — Métricas SSOT

#### [MODIFY] [ecosistema_jaraba_core.module](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module)

Añadir servicio `GlobalMetricsService` que lee las métricas desde la configuración de Drupal (establecidas vía admin) y las expone a los templates:

```php
// En hook_preprocess_page():
$variables['global_metrics'] = \Drupal::service('ecosistema_jaraba_core.global_metrics')->getAll();
```

Las métricas SSOT se configuran en `/admin/config/jaraba/global-metrics` y se propagan automáticamente a los 4 meta-sitios.

---

## 5. Tabla de Correspondencia Técnica

| Especificación | Tecnología | Fichero/Componente | Directriz |
|----------------|------------|--------------------|-----------| 
| **Entidad centralizada** | Drupal Content Entity | `SuccessCase.php` | `drupal-custom-modules.md` §Checklist |
| **Field UI** | `field_ui_base_route` | `entity.success_case.settings` | `drupal-custom-modules.md` §5 |
| **Views integration** | `EntityViewsData` handler | `SuccessCase.php` annotation | `drupal-custom-modules.md` §5 |
| **Frontend pages** | Zero Region Policy | `page--success-cases.html.twig` | `frontend-page-pattern.md` |
| **Body classes** | `hook_preprocess_html()` | `ecosistema_jaraba_theme.theme` | `frontend-page-pattern.md` §2.5 |
| **Template suggestions** | `hook_theme_suggestions_page_alter()` | `.theme` | `frontend-page-pattern.md` §2 |
| **SCSS** | Dart Sass + `@use` + `var(--ej-*)` | `scss/_*.scss` + `main.scss` | `scss-estilos.md` |
| **Compilación SCSS** | `npx sass scss/main.scss css/...` | Docker NVM path | `scss-estilos.md` §Compilación |
| **Iconos** | `jaraba_icon(category, name, opts)` | Templates Twig | `scss-estilos.md` §Iconografía |
| **Paleta colores** | 7 colores marca + aliases | CSS custom properties | `scss-estilos.md` §Paleta |
| **Premium cards** | Glassmorphism + hover 3D | `_card.scss` | `scss-estilos.md` §12-15 |
| **Font** | `'Outfit'` sans-serif | Todo SCSS | `scss-estilos.md` §23 |
| **Modales CRUD** | Slide-panel off-canvas | `data-slide-panel` triggers | `slide-panel-modales.md` |
| **i18n** | `{% trans %}` + `$this->t()` | Templates + Controller | `i18n-traducciones.md` |
| **SDC components** | Card variant `testimonial` | `components/card/` | `sdc-components.md` |
| **PB blocks** | GrapesJS template + SCSS | `testimonials-slider` reutilizado | PB pipeline independiente |
| **SEO** | Schema.org JSON-LD | `<script type="application/ld+json">` | Directrices SEO |
| **PHP 8.4** | Sin constructor promotion heredada | `SuccessCasesController.php` | §DRUPAL11-002 |
| **Navegación admin** | 4 YAML obligatorios | `.links.menu/task/action.yml` + `.routing.yml` | `drupal-custom-modules.md` §4 |
| **Responsive** | Mobile-first breakpoints | `@media (min-width: ...)` | `00_DIRECTRICES_PROYECTO.md` |
| **Accessibility** | ARIA, focus-visible, reduced-motion | Templates + SCSS | WCAG 2.1 AA |

---

## 6. Cumplimiento de Directrices del Proyecto

| Directriz | Estado | Implementación |
|-----------|--------|----------------|
| ⛔ Nunca crear CSS directo | ✅ | Solo SCSS parciales en `scss/` |
| ⛔ Nunca `{{ page.content }}` | ✅ | `{{ clean_content }}` en todas las page templates |
| ⛔ Siempre `var(--ej-*)` | ✅ | Todas las propiedades de color, spacing, font |
| ⛔ Siempre `@use` no `@import` | ✅ | Dart Sass module system |
| ⛔ Ambos iconos (outline + duotone) | ✅ | Si creamos nuevos iconos |
| ⛔ Body classes en `hook_preprocess_html` | ✅ | No en template `attributes.addClass()` |
| ⛔ PHP 8.4 propiedades heredadas | ✅ | Sin `protected` en constructor para props de `ControllerBase` |
| ⛔ Font 'Outfit' obligatorio | ✅ | `var(--ej-font-family, 'Outfit', sans-serif)` |
| ⛔ `{% trans %}` para textos UI | ✅ | Todos los textos de interfaz |
| ⛔ Content Entities en `/admin/content` | ✅ | Collection en `/admin/content/success-cases` |
| ⛔ Slide-panel para CRUD frontend | ✅ | Edición de casos desde el grid |

---

## 7. Plan de Verificación

### 7.1 Tests Automatizados (Cypress E2E)

> Framework: Cypress en `tests/e2e/`. Config: `tests/e2e/cypress.config.js`.

#### [NEW] `tests/e2e/cypress/e2e/success-cases.cy.js`

```
Comandos para ejecutar:
1. cd tests/e2e
2. npx cypress run --spec cypress/e2e/success-cases.cy.js
```

| Test | Qué verifica |
|------|-------------|
| `visits success cases list page` | La ruta `/casos-de-exito` carga sin 500/404, muestra el grid |
| `shows success case detail` | Click en una card navega a `/caso-de-exito/{slug}`, muestra secciones |
| `filters by vertical` | Filtros de vertical funcionan (se reduce el number de cards) |
| `success case card renders correctly` | Card tiene avatar, nombre, quote, badge |
| `video embed loads` | Si el caso tiene vídeo, se renderiza `<video>` o iframe |
| `API returns JSON` | GET `/api/success-cases` devuelve JSON con array de casos |

### 7.2 Verificación en Navegador (Manual)

> [!IMPORTANT]
> Verificar los 4 meta-sitios con sus respectivos view modes.

| # | URL | Qué verificar | Resultado esperado |
|---|-----|---------------|-------------------|
| 1 | `https://pepejaraba.jaraba-saas.lndo.site/casos-de-exito` | Grid de cards carga, KPIs visibles, filtros funcionan | Cards con fotos + quotes + badges |
| 2 | `https://pepejaraba.jaraba-saas.lndo.site/caso-de-exito/{slug}` | Vista detallada con secciones Reto/Solución/Resultado | Narrativa completa + vídeo + quote |
| 3 | `https://jarabaimpact.jaraba-saas.lndo.site/impacto` | Casos con framing de ROI empresarial | Métricas + KPIs + CTA |
| 4 | `https://plataformadeecosistemas.jaraba-saas.lndo.site/impacto` | Evidencia institucional + stats agregados | ODS badges + KPIs globales + grid |
| 5 | `https://jaraba-saas.lndo.site/instituciones` | Testimonial cards integradas | Cards con vídeo embed |
| 6 | `https://jaraba-saas.lndo.site/admin/content/success-cases` | Listado admin funciona | Tabla con nombre, vertical, estado |
| 7 | `https://jaraba-saas.lndo.site/admin/structure/success-case` | Field UI accesible | Pestaña "Administrar campos" visible |
| 8 | Mobile (Chrome DevTools 375px) | Layout responsivo en móvil | 1 columna, touch targets, sin overflow |

### 7.3 Verificación SCSS

```bash
# Compilar SCSS del módulo
docker exec jarabasaas_appserver_1 bash -c \
  "export PATH=/user/.nvm/versions/node/v20.20.0/bin:\$PATH && \
   cd /app/web/modules/custom/jaraba_success_cases && \
   npx sass scss/main.scss css/jaraba-success-cases.css --style=compressed --no-source-map"

# Compilar SCSS del tema (si se modifica)
cd z:\home\PED\JarabaImpactPlatformSaaS\web\themes\custom\ecosistema_jaraba_theme
npx sass scss/main.scss:css/main.css --style=compressed

# Limpiar caché
docker exec jarabasaas_appserver_1 drush cr
```

### 7.4 Verificación SEO

Verificar en el HTML fuente de cada meta-sitio que contiene:
- `<script type="application/ld+json">` con schema `Review` o `Person`
- `<meta name="description">` en la página de detalle
- `<h1>` único por página
- Alt text en todas las imágenes

### 7.5 Verificación de Entidad Drupal

```bash
# 1. Instalar tablas de la nueva entidad
docker exec jarabasaas_appserver_1 drush devel-entity-updates -y
docker exec jarabasaas_appserver_1 drush cr

# 2. Verificar que la tabla existe
docker exec jarabasaas_appserver_1 drush sql:query "SHOW TABLES LIKE 'success_case%'"

# 3. Seed de datos iniciales
docker exec jarabasaas_appserver_1 drush scr \
  web/modules/custom/jaraba_success_cases/scripts/seed_success_cases.php

# 4. Verificar que las entidades se crearon
docker exec jarabasaas_appserver_1 drush sql:query "SELECT id, name, slug FROM success_case"
```
