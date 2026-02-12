# 🏗️ Plan de Implementación Integral — Jaraba Impact Platform SaaS

> **Tipo:** Plan de Implementación Maestro  
> **Versión:** 1.0  
> **Fecha:** 2026-02-10  
> **Estado:** Borrador para Revisión ✍️  
> **Alcance:** Ecosistema completo — 170+ especificaciones técnicas, 6 verticales, 22 módulos custom  
> **Roles de Referencia:** Arquitecto SaaS, Ingeniero SW, UX, Drupal, Theming, GrapesJS, SEO/GEO, IA

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Visión General del Ecosistema](#2-visión-general-del-ecosistema)
3. [Arquitectura de Theming: Federated Design Tokens](#3-arquitectura-de-theming-federated-design-tokens)
4. [Directrices Obligatorias de Implementación](#4-directrices-obligatorias-de-implementación)
   - 4.1 [SCSS y Variables Inyectables](#41-scss-y-variables-inyectables)
   - 4.2 [Iconografía SVG Dual](#42-iconografía-svg-dual)
   - 4.3 [Twig Templates y Parciales](#43-twig-templates-y-parciales)
   - 4.4 [Body Classes via hook_preprocess_html()](#44-body-classes-via-hook_preprocess_html)
   - 4.5 [i18n: Textos Siempre Traducibles](#45-i18n-textos-siempre-traducibles)
   - 4.6 [Content Entities y Navegación Drupal](#46-content-entities-y-navegación-drupal)
   - 4.7 [Frontend Limpio: Zero Region Pattern](#47-frontend-limpio-zero-region-pattern)
   - 4.8 [Modales para Acciones CRUD](#48-modales-para-acciones-crud)
   - 4.9 [Paleta de Colores Oficial](#49-paleta-de-colores-oficial)
5. [Patrones de Implementación Estándar](#5-patrones-de-implementación-estándar)
   - 5.1 [Patrón Frontend Page](#51-patrón-frontend-page)
   - 5.2 [Patrón Content Entity](#52-patrón-content-entity)
   - 5.3 [Patrón SCSS Modular](#53-patrón-scss-modular)
   - 5.4 [Patrón Slide-Panel Modal](#54-patrón-slide-panel-modal)
   - 5.5 [Patrón Operacional Dashboard](#55-patrón-operacional-dashboard)
6. [Tabla de Correspondencia: Especificaciones ↔ Módulos](#6-tabla-de-correspondencia-especificaciones--módulos)
   - 6.1 [Core Platform (Docs 01-07)](#61-core-platform-docs-01-07)
   - 6.2 [Empleabilidad (Docs 08-24)](#62-empleabilidad-docs-08-24)
   - 6.3 [Emprendimiento (Docs 25-44)](#63-emprendimiento-docs-25-44)
   - 6.4 [AgroConecta (Docs 47-61, 80-82)](#64-agroconecta-docs-47-61-80-82)
   - 6.5 [ComercioConecta (Docs 62-79)](#65-comercioconecta-docs-62-79)
   - 6.6 [ServiciosConecta (Docs 82-99)](#66-serviciosconecta-docs-82-99)
   - 6.7 [Platform Features (Docs 100-140)](#67-platform-features-docs-100-140)
7. [Flujos de Trabajo Definidos](#7-flujos-de-trabajo-definidos)
8. [Checklist de Compliance Pre-Implementación](#8-checklist-de-compliance-pre-implementación)
9. [Roadmap de Implementación](#9-roadmap-de-implementación)
10. [Apéndice: Documentación de Referencia](#10-apéndice-documentación-de-referencia)

---

## 1. Resumen Ejecutivo

El **Jaraba Impact Platform SaaS** es un ecosistema multi-tenant construido sobre Drupal 11, que abarca **6 verticales de negocio** (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta y Core transversal), con más de **170 especificaciones técnicas** documentadas, **22 módulos custom** activos, y una inversión planificada de **~4,500 horas** en 24 meses.

### Propósito de Este Documento

Este plan de implementación sirve como **guía maestra unificada** para que cualquier equipo técnico futuro pueda:

1. **Comprender** la lógica, estructura y funcionalidades del SaaS sin necesidad de leer los 280+ documentos individuales
2. **Localizar rápidamente** qué especificación técnica corresponde a cada módulo y funcionalidad
3. **Respetar las directrices obligatorias** que garantizan coherencia, mantenibilidad y calidad de clase mundial
4. **Replicar los patrones probados** de implementación que ya funcionan en el ecosistema
5. **Planificar sprints** usando la tabla de correspondencia y el roadmap de fases

### Magnitud del Ecosistema

| Métrica | Valor |
|---------|-------|
| Especificaciones técnicas | 170+ documentos |
| Verticales de negocio | 6 |
| Módulos custom Drupal | 22 |
| Content Entities definidas | 80+ |
| Bloques Page Builder | 67 (45 base + 22 premium) |
| Templates Twig | 64+ |
| Líneas SCSS estimadas | 10,000+ |
| Aprendizajes documentados | 54 |
| Workflows operativos | 16 |
| URLs frontend verificadas | 17 |
| Horas totales roadmap | ~4,500h + 775-970h (Page Builder) |
| Timeline | 24 meses (Q1 2026 – Q2 2027) |

---

## 2. Visión General del Ecosistema

### 2.1 Stack Tecnológico

| Capa | Tecnología | Versión |
|------|------------|---------|
| CMS/Backend | Drupal | 11.x |
| PHP | PHP | 8.2+ |
| Base de Datos | MariaDB | 10.6+ |
| Cache/Queue | Redis | 7.x |
| Búsqueda Vectorial | Qdrant | Latest |
| Extracción Documentos | Apache Tika | 2.x |
| Frontend CSS | Dart Sass → CSS compilado | Latest |
| Page Builder | GrapesJS | 0.21+ |
| Testing E2E | Cypress | 13.x |
| Entorno Local | Lando | 3.x |
| CI/CD | GitHub Actions | - |
| IA/LLM | Multi-provider (OpenAI, Anthropic, Google) | - |
| Pagos | Stripe Connect | - |

### 2.2 Mapa de Verticales

```
┌─────────────────────────────────────────────────────┐
│                 JARABA IMPACT PLATFORM               │
├─────────────┬──────────────┬────────────────────────┤
│  CORE (01-07)                                        │
│  ecosistema_jaraba_core + ecosistema_jaraba_theme    │
├─────────────┼──────────────┼────────────────────────┤
│ EMPLEABILIDAD│ EMPRENDIMIENTO│ AI TRILOGY             │
│ (08-24)      │ (25-44)       │ (128-130)             │
│ jaraba_lms   │ jaraba_empren │ jaraba_content_hub    │
│ jaraba_empleo│ dimiento_core │ jaraba_ai_skills      │
│ jaraba_self  │               │ jaraba_knowledge      │
│  _discovery  │               │  _training            │
├──────────────┼──────────────┼───────────────────────┤
│ AGROCONECTA  │ COMERCIO     │ SERVICIOS             │
│ (47-61,80-82)│ CONECTA      │ CONECTA               │
│ jaraba_agro  │ (62-79)      │ (82-99)               │
│  conecta_core│ jaraba_comer │ jaraba_servicios      │
│              │  cio_conecta │  _conecta             │
├──────────────┴──────────────┴───────────────────────┤
│ TRANSVERSAL: jaraba_i18n, jaraba_ai_agents,          │
│ jaraba_consent, jaraba_email, jaraba_foc,             │
│ jaraba_credentials, jaraba_interactive,               │
│ jaraba_pixel_manager, jaraba_performance,             │
│ jaraba_social, jaraba_journey_engine                  │
└─────────────────────────────────────────────────────┘
```

### 2.3 Arquitectura Multi-Tenant

El SaaS opera bajo un modelo **Single-Instance Multi-Tenant** usando el módulo Group de Drupal:

- **Aislamiento de contenido**: Cada tenant (organización) tiene su propio Group con contenido aislado
- **Roles por tenant**: Admin Tenant, Editor, Viewer — mapeados vía Group Roles
- **Feature flags**: Funcionalidades activables por plan (Starter, Professional, Enterprise)
- **Theming por tenant**: Variables CSS inyectables permiten personalización visual sin recompilación
- **Dominio por tenant**: Subdominios automáticos (tenant.jaraba.com) vía Domain module

> [!IMPORTANT]
> **El tenant NO debe tener acceso al tema de administración de Drupal.** Toda la gestión se realiza a través del frontend limpio del SaaS.

---

## 3. Arquitectura de Theming: Federated Design Tokens

> **Documento Maestro:** [2026-02-05_arquitectura_theming_saas_master.md](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md)

### 3.1 Principio Fundamental

El sistema de theming sigue el patrón **Federated Design Tokens** con un SSOT (Single Source of Truth) centralizado en `ecosistema_jaraba_core`:

```
CAPA 1: Tokens Globales (ecosistema_jaraba_core/scss/_variables.scss)
    ↓ Hereda
CAPA 2: Tema Base (ecosistema_jaraba_theme/scss/)
    ↓ Hereda
CAPA 3: Módulos Verticales (jaraba_*/scss/)
    ↓ Personaliza
CAPA 4: Variables CSS Inyectables (runtime, override por tenant)
    ↓ Sobrescribe
CAPA 5: Inline Styles (solo para personalización extrema por tenant)
```

### 3.2 Flujo de Tokens

1. **Compilación (build-time):** Archivos SCSS se compilan a CSS con Dart Sass moderno
2. **Inyección (runtime):** Drupal inyecta variables CSS `var(--ej-*)` desde la configuración del tema
3. **Personalización:** Los tenants configuran colores, tipografía, spacing desde la UI de Drupal **sin tocar código**

### 3.3 Regla de Oro SCSS

```scss
// ✅ CORRECTO: Variable CSS inyectable con fallback SCSS
color: var(--ej-color-primary, #{$ej-color-primary-fallback});

// ❌ INCORRECTO: Color hardcodeado
color: #4F46E5;

// ❌ INCORRECTO: Solo variable SCSS sin inyectable
color: $ej-color-primary-fallback;
```

### 3.4 Dart Sass Moderno — Reglas Obligatorias

| Regla | Correcto | Incorrecto |
|-------|----------|------------|
| Sistema de módulos | `@use 'variables' as *;` | `@import 'variables';` |
| Funciones de color | `color.scale($c, $lightness: 85%)` | `lighten($c, 85%)` / `darken()` |
| Imports en parciales | Cada parcial declara sus propios `@use` | Heredar imports del main.scss |
| Math | `@use 'sass:math'; math.div(100, 3)` | `100 / 3` |

> [!CAUTION]
> En Dart Sass, **cada parcial es un módulo independiente**. Las variables importadas en `main.scss` NO se heredan a los parciales cargados con `@use`. Cada parcial DEBE declarar `@use 'variables' as *;` explícitamente.

---

## 4. Directrices Obligatorias de Implementación

### 4.1 SCSS y Variables Inyectables

> **Workflow:** [scss-estilos.md](../../.agent/workflows/scss-estilos.md)

**Regla inquebrantable:** NUNCA crear archivos CSS directamente. Siempre SCSS → compilación → CSS.

**Ubicaciones:**
- **Core SCSS parciales:** `web/modules/custom/ecosistema_jaraba_core/scss/`
- **Theme SCSS:** `web/themes/custom/ecosistema_jaraba_theme/scss/`
- **Verticales SCSS:** `web/modules/custom/jaraba_*/scss/` (pipeline independiente)

**Compilación del módulo Core:**
```bash
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core
export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts && npm run build
```

**Compilación del Theme (desde PowerShell):**
```powershell
cd z:\home\PED\JarabaImpactPlatformSaaS\web\themes\custom\ecosistema_jaraba_theme
npx sass scss/main.scss:css/main.css --style=compressed
docker exec jarabasaas_appserver_1 drush cr
```

> [!WARNING]
> **NUNCA usar `npm run build` del package.json del tema** ya que genera `ecosistema-jaraba-theme.css` en lugar de `main.css` que es el archivo que carga el sitio vía `libraries.yml`.

**Cada módulo vertical** tiene su propio `package.json` y pipeline SCSS:
```
jaraba_servicios_conecta/
├── package.json       → "build": "sass scss/main.scss css/jaraba-servicios-conecta.css ..."
├── scss/_variables.scss → Colores propios del vertical
└── scss/main.scss     → Entry point con @use de cada parcial
```

### 4.2 Iconografía SVG Dual

**Regla:** Siempre crear AMBAS versiones de cada icono:
1. `{nombre}.svg` — Versión outline (trazo)
2. `{nombre}-duotone.svg` — Versión duotone (2 tonos con opacity)

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/images/icons/`
```
icons/
├── analytics/    # Gráficos, métricas, análisis
├── business/     # Empresa, diagnóstico, objetivos
├── ai/           # IA, automatización, cerebro
├── ui/           # Interfaz, navegación, controles
├── actions/      # Acciones CRUD, refresh, download
└── verticals/    # Verticales específicos (agro, empleo)
```

**Uso en Twig:**
```twig
{# Outline (default) - KPIs, botones, elementos pequeños #}
{{ jaraba_icon('business', 'diagnostic', { color: 'azul-corporativo', size: '24px' }) }}

{# Duotone - headers de sección, cards destacadas #}
{{ jaraba_icon('business', 'diagnostic', { variant: 'duotone', color: 'naranja-impulso', size: '32px' }) }}
```

**Los colores se aplican dinámicamente** vía CSS filter desde `jaraba_icon()`. NO crear archivos separados por color.

### 4.3 Twig Templates y Parciales

**Principio:** Usar templates Twig **limpias, libres de regiones y bloques de Drupal**.

**Parciales reutilizables:**
```
templates/partials/
├── _header.html.twig          # Dispatcher de headers
├── _header-classic.html.twig  # Layout header clásico
├── _header-split.html.twig    # Layout header split
├── _header-centered.html.twig # Layout header centrado
├── _header-minimal.html.twig  # Layout header minimal
├── _footer.html.twig          # Footer del tema
└── _copilot-fab.html.twig     # FAB del copiloto IA
```

**Regla de parciales:** Antes de extender el código de una página, preguntarse:
1. ¿Ya existe un parcial para esto?
2. ¿Necesito crear uno nuevo para reutilizar en otras páginas?

**Include desde página:**
```twig
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name,
  logo: logo|default(''),
  logged_in: logged_in,
  theme_settings: theme_settings|default({})
} %}
```

**Cross-module Twig namespaces** (para parciales de módulos):
```twig
{% include '@jaraba_i18n/partials/_language-selector.html.twig' %}
```

> [!IMPORTANT]
> Las variables del parcial deben ser **configurables desde la UI de Drupal** (theme settings). El parcial usa `theme_settings.footer_text`, `theme_settings.footer_links`, etc., cuyos valores se configuran en Apariencia → Ecosistema Jaraba Theme → Configuración. **No hay que tocar código para cambiar el contenido del footer.**

### 4.4 Body Classes via hook_preprocess_html()

> [!CAUTION]
> ⚠️ **Las clases añadidas en el template con `attributes.addClass()` NO funcionan para el body.** Debes usar `hook_preprocess_html()`.

**Implementación correcta en `ecosistema_jaraba_theme.theme`:**
```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  if ($route === 'mi_modulo.mi_ruta') {
    $variables['attributes']['class'][] = 'mi-tipo-page';
    $variables['attributes']['class'][] = 'page-mi-tipo';
  }
}
```

> **NO crear función duplicada.** Si ya existe `ecosistema_jaraba_theme_preprocess_html`, añadir la lógica dentro de la función existente.

### 4.5 i18n: Textos Siempre Traducibles

**Regla:** TODO texto visible en la interfaz debe ser traducible.

| Contexto | Mecanismo |
|----------|-----------|
| Templates Twig | `{% trans %}Texto{% endtrans %}` o `{{ 'Texto'|t }}` |
| PHP Controllers | `$this->t('Texto')` |
| JavaScript | `Drupal.t('Texto')` |
| Theme Settings | Campos con `#title => t('Etiqueta')` |
| GrapesJS Blocks | Labels en `jaraba_i18n` namespace |

**Módulo de traducción avanzada:** `jaraba_i18n` — Dashboard en `/i18n` con traducción asistida por IA, multi-entidad, namespace cross-module.

### 4.6 Content Entities y Navegación Drupal

**Patrón obligatorio:** Todas las entidades de contenido deben tener:

1. **Navegación en `/admin/content`** — Listado con operaciones CRUD
2. **Navegación en `/admin/structure`** — Acceso a Field UI y gestión de campos
3. **Entity Reference** flexible para relaciones entre entidades
4. **Integración con Views** para listados personalizables

**Ejemplo de definición de rutas:**
```yaml
# entity.mi_entidad.collection (listado en /admin/content)
entity.mi_entidad.collection:
  path: '/admin/content/mi-entidad'
  defaults:
    _entity_list: 'mi_entidad'
    _title: 'Mi Entidad'
  requirements:
    _permission: 'administer mi_entidad'

# Acceso a Field UI en /admin/structure
entity.mi_entidad.settings:
  path: '/admin/structure/mi-entidad'
  defaults:
    _form: '\Drupal\mi_modulo\Form\MiEntidadSettingsForm'
    _title: 'Mi Entidad Settings'
  requirements:
    _permission: 'administer mi_entidad'
```

### 4.7 Frontend Limpio: Zero Region Pattern

**El SaaS debe tener control absoluto sobre el frontend**, limpio de la estructura y modo clásico de Drupal:

- **Sin `page.content`** ni bloques heredados de regiones
- **Layout full-width** (sin sidebar)
- **Mobile-first** en todos los layouts
- **Sin admin toolbar** para tenants (solo visible para administradores de plataforma)

**Template de página frontend limpia:**
```twig
{# page--mi-ruta.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/global') }}

<main id="main-content" class="mi-ruta-main">
  <div class="mi-ruta-wrapper">
    {{ page.content }}
  </div>
</main>
```

Con header y footer propios del tema vía `{% include %}` de parciales.

### 4.8 Modales para Acciones CRUD

**Regla:** Todas las acciones de crear/editar/ver en frontend deben abrirse en un **modal (slide-panel)**, para que el usuario no abandone la página.

```html
<a href="/url/del/contenido" 
   data-slide-panel="large"
   data-slide-panel-title="Título del Panel">
  Acción
</a>
```

La library global `slide-panel` maneja automáticamente apertura/cierre.

### 4.9 Paleta de Colores Oficial

#### Paleta de Marca Jaraba (7 colores)

| Variable SCSS | Variable CSS | Hex | Uso Semántico |
|---------------|--------------|-----|---------------|
| `$azul-profundo` | `--ej-color-azul-profundo` | `#003366` | Autoridad, profundidad |
| `$azul-verdoso` | `--ej-color-azul-verdoso` | `#2B7A78` | Conexión, equilibrio |
| `$azul-corporativo` | `--ej-color-corporate` | `#233D63` | La "J", confianza, base |
| `$naranja-impulso` | `--ej-color-impulse` | `#FF8C42` | Empresas, emprendimiento |
| `$verde-innovacion` | `--ej-color-innovation` | `#00A9A5` | Talento, empleabilidad |
| `$verde-oliva` | `--ej-color-agro` | `#556B2F` | AgroConecta, naturaleza |
| `$verde-oliva-oscuro` | `--ej-color-agro-dark` | `#3E4E23` | AgroConecta intenso |

#### Colores UI Extendidos

| Variable CSS | Hex | Uso |
|--------------|-----|-----|
| `--ej-color-primary` | `#4F46E5` | Acciones primarias UI |
| `--ej-color-secondary` | `#7C3AED` | IA, features premium |
| `--ej-color-success` | `#10B981` | Estados positivos |
| `--ej-color-warning` | `#F59E0B` | Alertas |
| `--ej-color-danger` | `#EF4444` | Errores, destructivo |
| `--ej-color-neutral` | `#64748B` | Muted, disabled |

---

## 5. Patrones de Implementación Estándar

### 5.1 Patrón Frontend Page

> **Workflow completo:** [frontend-page-pattern.md](../../.agent/workflows/frontend-page-pattern.md)

**Pasos para crear una nueva página frontend:**

1. Crear template `page--{ruta}.html.twig` con header/footer vía `{% include %}`
2. Registrar sugerencia en `ecosistema_jaraba_theme_theme_suggestions_page_alter()`
3. Añadir clases body en `ecosistema_jaraba_theme_preprocess_html()` (NO en template)
4. Crear parcial SCSS `_mi-componente.scss` con `@use 'variables' as *;`
5. Importar en `main.scss` con `@use 'mi-componente';`
6. Compilar SCSS y limpiar caché
7. Verificar en navegador: header visible, full-width, footer, sin sidebar

### 5.2 Patrón Content Entity

**Para cada nueva Content Entity:**

1. Definir clase Entity con annotations `@ContentEntityType`
2. Crear Form para add/edit
3. Crear ListBuilder para `/admin/content/{entidad}`
4. Definir rutas: collection, canonical, add-form, edit-form, delete-form, settings
5. Crear SettingsForm para `/admin/structure/{entidad}`
6. Configurar permisos en `*.permissions.yml`
7. Verificar Field UI y Views accesibles

### 5.3 Patrón SCSS Modular

```scss
// _mi-componente.scss — Parcial SCSS para un componente

@use 'sass:color';
@use 'variables' as *;

.mi-componente {
  background: var(--ej-bg-body, #{$ej-bg-body});
  color: var(--ej-text-primary, #{$ej-text-primary});
  
  &__header {
    background: var(--ej-color-corporate, #233D63);
  }
  
  &__card {
    border: 1px solid var(--ej-border-color, #{$ej-border-color});
    box-shadow: var(--ej-shadow-md);
    
    &:hover {
      transform: translateY(-4px);
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
  }
  
  @media (max-width: 767px) {
    padding: $ej-spacing-md;
  }
}
```

### 5.4 Patrón Slide-Panel Modal

Para acciones CRUD que no deben sacar al usuario de la página:

```html
<!-- Apertura -->
<a href="/admin/content/mi-entidad/add" 
   data-slide-panel="large"
   data-slide-panel-title="{% trans %}Crear Entidad{% endtrans %}">
  {{ jaraba_icon('actions', 'plus', { color: 'corporate' }) }}
  {% trans %}Crear{% endtrans %}
</a>
```

> **Workflow completo:** [slide-panel-modales.md](../../.agent/workflows/slide-panel-modales.md)

### 5.5 Patrón Operacional Dashboard

**Estructura estándar para dashboards de operaciones:**

1. **Header premium** con glassmorphism, partículas Canvas opcionales
2. **KPI cards** con diseño premium (glassmorphism + hover 3D lift)
3. **Contenido principal** con tabs si hay múltiples vistas
4. **Acciones rápidas** con slide-panel para CRUD
5. **Full-width** sin sidebar, mobile-first

---

## 6. Tabla de Correspondencia: Especificaciones ↔ Módulos

### 6.1 Core Platform (Docs 01-07)

> **Prioridad:** 🔴 P0 — Debe implementarse primero. Habilita todas las verticales.

| Doc | Nombre | Módulo Drupal | Horas | Estado |
|-----|--------|---------------|-------|--------|
| 01 | Core_Entidades_Esquema_BD | `ecosistema_jaraba_core` | 40-50 | ✅ Implementado |
| 02 | Core_Modulos_Personalizados | `ecosistema_jaraba_core` | 60-80 | ✅ Implementado |
| 03 | Core_APIs_Contratos | `ecosistema_jaraba_core` | 50-60 | ✅ Implementado |
| 04 | Core_Permisos_RBAC | `ecosistema_jaraba_core` | 30-40 | ✅ Implementado |
| 05 | Core_Theming_jaraba_theme | `ecosistema_jaraba_theme` | 40-50 | ✅ Implementado |
| 06 | Core_Flujos_ECA | `ecosistema_jaraba_core` | 40-50 | ✅ Implementado |
| 07 | Core_Configuracion_MultiTenant | `ecosistema_jaraba_core` | 50-60 | ✅ Implementado |

**Directrices de Compliance aplicables:** SCSS inyectable, Twig parciales, body classes, i18n, Content Entities.

### 6.2 Empleabilidad (Docs 08-24)

| Doc | Nombre | Módulo | Horas | Prioridad | Estado |
|-----|--------|--------|-------|-----------|--------|
| 08 | LMS Core | `jaraba_lms` | 40-50 | 🔴 P0 | ✅ |
| 09 | Learning Paths | `jaraba_lms` | 35-45 | 🔴 P0 | ✅ |
| 10 | Progress Tracking | `jaraba_lms` | 30-40 | 🟡 P1 | ✅ |
| 11 | Job Board Core | `jaraba_empleo` | 45-55 | 🔴 P0 | ✅ |
| 12 | Application System | `jaraba_empleo` | 35-45 | 🔴 P0 | ✅ |
| 13 | Employer Portal | `jaraba_empleo` | 40-50 | 🔴 P0 | ✅ |
| 14 | Job Alerts | `jaraba_empleo` | 25-35 | 🟡 P1 | ✅ |
| 15 | Candidate Profile | `jaraba_empleo` | 35-45 | 🔴 P0 | ✅ |
| 16 | CV Builder | `jaraba_empleo` | 40-50 | 🔴 P0 | ✅ |
| 17 | Credentials System | `jaraba_credentials` | 30-40 | 🟡 P1 | ✅ |
| 18 | Certification Workflow | `jaraba_credentials` | 25-35 | 🟡 P1 | ✅ |
| 19 | Matching Engine | `jaraba_empleo` | 50-60 | 🔴 P0 | ✅ |
| 20 | AI Copilot | `jaraba_empleo` | 45-55 | 🟡 P1 | ✅ |
| 21 | Recommendation System | `jaraba_empleo` | 35-45 | 🟡 P1 | ✅ |
| 22 | Dashboard JobSeeker | `jaraba_empleo` | 30-40 | 🔴 P0 | ✅ |
| 23 | Dashboard Employer | `jaraba_empleo` | 30-40 | 🔴 P0 | ✅ |
| 24 | Impact Metrics | `jaraba_empleo` | 25-35 | 🟢 P2 | ✅ |

### 6.3 Emprendimiento (Docs 25-44)

| Doc | Nombre | Módulo | Horas | Prioridad | Estado |
|-----|--------|--------|-------|-----------|--------|
| 25 | Business Diagnostic Core | `jaraba_emprendimiento_core` | 40-50 | 🔴 P0 | ✅ |
| 26 | Digital Maturity Assessment | `jaraba_emprendimiento_core` | 35-45 | 🔴 P0 | ✅ |
| 27 | Competitive Analysis Tool | `jaraba_emprendimiento_core` | 30-40 | 🟡 P1 | ✅ |
| 28 | Digitalization Paths | `jaraba_emprendimiento_core` | 35-45 | 🔴 P0 | ✅ |
| 29 | Action Plans | `jaraba_emprendimiento_core` | 30-40 | 🔴 P0 | ✅ |
| 30 | Progress Milestones | `jaraba_emprendimiento_core` | 25-35 | 🟡 P1 | ✅ |
| 31 | Mentoring Core | `jaraba_emprendimiento_core` | 40-50 | 🔴 P0 | ✅ |
| 32 | Mentoring Sessions | `jaraba_emprendimiento_core` | 35-45 | 🔴 P0 | ✅ |
| 33 | Mentor Dashboard | `jaraba_emprendimiento_core` | 30-40 | 🟡 P1 | ✅ |
| 34 | Collaboration Groups | `jaraba_emprendimiento_core` | 25-35 | 🟡 P1 | ✅ |
| 35 | Networking Events | `jaraba_emprendimiento_core` | 30-40 | 🟢 P2 | ⬜ |
| 36 | Business Model Canvas | `jaraba_emprendimiento_core` | 35-45 | 🔴 P0 | ✅ |
| 37 | MVP Validation | `jaraba_emprendimiento_core` | 30-40 | 🟡 P1 | ✅ |
| 38 | Financial Projections | `jaraba_emprendimiento_core` | 35-45 | 🟡 P1 | ✅ |
| 39 | Digital Kits | `jaraba_emprendimiento_core` | 25-35 | 🟡 P1 | ✅ |
| 40 | Membership System | `jaraba_emprendimiento_core` | 30-40 | 🟢 P2 | ⬜ |
| 41 | Dashboard Entrepreneur | `jaraba_emprendimiento_core` | 35-45 | 🔴 P0 | ✅ |
| 42 | Dashboard Program | `jaraba_emprendimiento_core` | 30-40 | 🟡 P1 | ✅ |
| 43 | Impact Metrics | `jaraba_emprendimiento_core` | 25-35 | 🟢 P2 | ⬜ |
| 44 | AI Business Copilot | `jaraba_emprendimiento_core` | 45-55 | 🟡 P1 | ✅ |

### 6.4 AgroConecta (Docs 47-61, 80-82)

| Doc | Nombre | Módulo | Horas | Prioridad | Estado |
|-----|--------|--------|-------|-----------|--------|
| 47 | Commerce Core | `jaraba_agroconecta_core` | 50-60 | 🔴 P0 | ✅ F1 |
| 48 | Product Catalog | `jaraba_agroconecta_core` | 40-50 | 🔴 P0 | ✅ F1 |
| 49 | Order System | `jaraba_agroconecta_core` | 45-55 | 🔴 P0 | ✅ F2 |
| 50 | Checkout Flow | `jaraba_agroconecta_core` | 40-50 | 🔴 P0 | ✅ F2 |
| 51 | Shipping & Logistics | `jaraba_agroconecta_core` | 45-55 | 🔴 P0 | ⬜ F5 |
| 52 | Producer Portal | `jaraba_agroconecta_core` | 40-50 | 🔴 P0 | ✅ F3 |
| 53 | Customer Portal | `jaraba_agroconecta_core` | 35-45 | 🟡 P1 | ✅ F3 |
| 54 | Reviews System | `jaraba_agroconecta_core` | 25-35 | 🟡 P1 | 🔶 F4 |
| 55 | Search & Discovery | `jaraba_agroconecta_core` | 35-45 | 🔴 P0 | ⬜ F6 |
| 56 | Promotions & Coupons | `jaraba_agroconecta_core` | 30-40 | 🟡 P1 | ⬜ F6 |
| 57 | Analytics Dashboard | `jaraba_agroconecta_core` | 35-45 | 🟡 P1 | ⬜ F6 |
| 58 | Admin Panel | `jaraba_agroconecta_core` | 30-40 | 🟡 P1 | ⬜ F6 |
| 59 | Notifications System | `jaraba_agroconecta_core` | 25-35 | 🟡 P1 | 🔶 F4 |
| 60 | Mobile App | `jaraba_agroconecta_core` | 60-80 | 🟢 P2 | ⬜ F9 |
| 61 | API Integration Guide | `jaraba_agroconecta_core` | 20-30 | 🟡 P1 | ⬜ F9 |
| 80 | Traceability System | `jaraba_agroconecta_core` | 40-50 | 🔴 P0 | ⬜ F7 |
| 81 | QR Dynamic | `jaraba_agroconecta_core` | 25-35 | 🔴 P0 | ⬜ F7 |
| 82 | Partner Document Hub | `jaraba_agroconecta_core` | 30-40 | 🟡 P1 | ✅ AC6-2 |

### 6.5 ComercioConecta (Docs 62-79)

> Reutiliza ~70% de AgroConecta (fork del Doc 47).

| Doc | Nombre | Módulo | Horas | Prioridad | Estado |
|-----|--------|--------|-------|-----------|--------|
| 62 | Commerce Core | `jaraba_comercio_conecta` | 30-40 | 🔴 P0 | ⬜ |
| 63 | POS Integration | `jaraba_comercio_conecta` | 45-55 | 🔴 P0 | ⬜ |
| 64 | Flash Offers | `jaraba_comercio_conecta` | 35-45 | 🔴 P0 | ⬜ |
| 65 | Dynamic QR | `jaraba_comercio_conecta` | 25-35 | 🔴 P0 | ⬜ |
| 66 | Product Catalog | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 67 | Order System | `jaraba_comercio_conecta` | 30-40 | 🟡 P1 | ⬜ |
| 68 | Checkout Flow | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 69 | Shipping & Logistics | `jaraba_comercio_conecta` | 30-40 | 🟡 P1 | ⬜ |
| 70 | Search & Discovery | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 71 | Local SEO | `jaraba_comercio_conecta` | 35-45 | 🔴 P0 | ⬜ |
| 72 | Promotions & Coupons | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 73 | Reviews & Ratings | `jaraba_comercio_conecta` | 20-30 | 🟢 P2 | ⬜ |
| 74 | Merchant Portal | `jaraba_comercio_conecta` | 35-45 | 🔴 P0 | ⬜ |
| 75 | Customer Portal | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 76 | Notifications | `jaraba_comercio_conecta` | 20-30 | 🟡 P1 | ⬜ |
| 77 | Mobile App | `jaraba_comercio_conecta` | 50-60 | 🟢 P2 | ⬜ |
| 78 | Admin Panel | `jaraba_comercio_conecta` | 25-35 | 🟡 P1 | ⬜ |
| 79 | API Integration Guide | `jaraba_comercio_conecta` | 15-25 | 🟢 P2 | ⬜ |

### 6.6 ServiciosConecta (Docs 82-99)

| Doc | Nombre | Módulo | Horas | Prioridad | Estado |
|-----|--------|--------|-------|-----------|--------|
| 82 | Services Core | `jaraba_servicios_conecta` | 40-50 | 🔴 P0 | ✅ F1 |
| 83 | Provider Profile | `jaraba_servicios_conecta` | 30-40 | 🔴 P0 | ✅ F1 |
| 84 | Service Offerings | `jaraba_servicios_conecta` | 35-45 | 🔴 P0 | ✅ F1 |
| 85 | Booking Engine Core | `jaraba_servicios_conecta` | 45-55 | 🔴 P0 | ✅ F1 |
| 86 | Calendar Sync | `jaraba_servicios_conecta` | 30-40 | 🟡 P1 | ⬜ F2 |
| 87 | Video Conferencing | `jaraba_servicios_conecta` | 40-50 | 🔴 P0 | ⬜ F2 |
| 88 | Buzón Confianza | `jaraba_servicios_conecta` | 35-45 | 🔴 P0 | ⬜ F3 |
| 89 | Firma Digital PAdES | `jaraba_servicios_conecta` | 40-50 | 🔴 P0 | ⬜ F3 |
| 90 | Portal Cliente Documental | `jaraba_servicios_conecta` | 35-45 | 🟡 P1 | ⬜ F3 |
| 91 | AI Triaje Casos | `jaraba_servicios_conecta` | 40-50 | 🟡 P1 | ⬜ F4 |
| 92 | Presupuestador Auto | `jaraba_servicios_conecta` | 35-45 | 🟡 P1 | ⬜ F4 |
| 93 | Copilot Servicios | `jaraba_servicios_conecta` | 45-55 | 🟡 P1 | ⬜ F4 |
| 94 | Dashboard Profesional | `jaraba_servicios_conecta` | 35-45 | 🔴 P0 | ⬜ F5 |
| 95 | Dashboard Admin | `jaraba_servicios_conecta` | 30-40 | 🟡 P1 | ⬜ F5 |
| 96 | Sistema Facturación | `jaraba_servicios_conecta` | 40-50 | 🔴 P0 | ⬜ F5 |
| 97 | Reviews & Ratings | `jaraba_servicios_conecta` | 20-30 | 🟢 P2 | ⬜ F6 |
| 98 | Notificaciones Multicanal | `jaraba_servicios_conecta` | 25-35 | 🟡 P1 | ⬜ F6 |
| 99 | API Integration Guide | `jaraba_servicios_conecta` | 20-30 | 🟢 P2 | ⬜ F6 |

### 6.7 Platform Features (Docs 100-140)

| Doc | Nombre | Módulo | Horas | Prioridad |
|-----|--------|--------|-------|-----------|
| 100 | Frontend Architecture MultiTenant | `ecosistema_jaraba_theme` | 128-176 | 🔴 P0 |
| 101 | Industry Style Presets | `ecosistema_jaraba_theme` | 40-50 | 🟡 P1 |
| 102 | Industry Style Presets Premium | `ecosistema_jaraba_theme` | 60-80 | 🟢 P2 |
| 103 | UX Journey Specifications Avatar | `jaraba_journey_engine` | 45-55 | 🟡 P1 |
| 104 | SaaS Admin Center Premium | `ecosistema_jaraba_core` | 80-100 | 🔴 P0 |
| 105-107 | SEPE Homologación | `jaraba_sepe` | 85-115 | 🔴 P0 (B2G) |
| 108 | AI Agent Flows | `jaraba_ai_agents` | 65-85 | 🟡 P1 |
| 109 | PWA Mobile | `jaraba_pwa` | 180-240 | 🔴 P0 |
| 110 | Onboarding ProductLed | `ecosistema_jaraba_core` | 155-205 | 🔴 P0 |
| 111 | UsageBased Pricing | `jaraba_foc` | 95-125 | 🔴 P0 |
| 112 | Integration Marketplace | `ecosistema_jaraba_core` | 120-160 | 🟡 P1 |
| 113 | Customer Success | `ecosistema_jaraba_core` | 85-115 | 🟡 P1 |
| 114 | Knowledge Base | `jaraba_knowledge_training` | 250-340 | 🔴 P0 |
| 115 | Security & Compliance | `ecosistema_jaraba_core` | 60-80 | 🔴 P0 |
| 116 | Advanced Analytics | `jaraba_pixel_manager` | 95-125 | 🟡 P1 |
| 117 | WhiteLabel | `ecosistema_jaraba_theme` | 80-100 | 🟢 P2 |
| 128 | AI Content Hub | `jaraba_content_hub` | 170-230 | 🔴 P0 |
| 129 | AI Skills System | `jaraba_ai_skills` | 145-195 | 🔴 P0 |
| 130 | Tenant Knowledge Training | `jaraba_knowledge_training` | 430-545 | 🔴 P0 |
| 131-140 | Infrastructure & DevOps | Varios | 265-345 | 🔴 P0 |

---

## 7. Flujos de Trabajo Definidos

Los siguientes workflows están documentados en `.agent/workflows/` y deben seguirse obligatoriamente:

| Workflow | Archivo | Propósito |
|----------|---------|-----------|
| SCSS Estilos | `scss-estilos.md` | Compilación SCSS, variables, paleta, iconos |
| Frontend Page | `frontend-page-pattern.md` | Crear páginas frontend limpias |
| Slide-Panel Modales | `slide-panel-modales.md` | CRUD en modales |
| Drupal Custom Modules | `drupal-custom-modules.md` | Crear módulos custom |
| SDC Components | `sdc-components.md` | Single Directory Components |
| i18n Traducciones | `i18n-traducciones.md` | Sistema de traducciones |
| Premium Cards | `premium-cards-pattern.md` | Patrón glassmorphism |
| Implementación Emprendimiento | `implementacion-emprendimiento.md` | Vertical emprendimiento |
| Implementación Gaps Empleabilidad | `implementacion-gaps-empleabilidad.md` | Gaps vertical empleo |
| Browser Verification | `browser-verification.md` | Verificación en navegador |
| Cypress E2E | `cypress-e2e.md` | Tests end-to-end |
| AI Integration | `ai-integration.md` | Integración IA multi-provider |
| Auditoría Exhaustiva | `auditoria-exhaustiva.md` | Proceso de auditoría |
| Auditoría UX | `auditoria-ux-clase-mundial.md` | Auditoría UX premium |
| Drupal ECA Hooks | `drupal-eca-hooks.md` | Automatizaciones ECA |
| Revisión Trimestral | `revision-trimestral.md` | Proceso de revisión |

---

## 8. Checklist de Compliance Pre-Implementación

Antes de implementar cualquier nueva funcionalidad, verificar:

### 🎨 Theming & SCSS
- [ ] ¿Creé archivos SCSS (NUNCA CSS directo)?
- [ ] ¿Usé `@use 'variables' as *;` en cada parcial?
- [ ] ¿Usé variables CSS inyectables `var(--ej-*, fallback)` donde aplica?
- [ ] ¿Usé `color.scale()` en lugar de `darken()`/`lighten()`?
- [ ] ¿Importé el parcial en `main.scss` con `@use`?
- [ ] ¿Compilé con Dart Sass moderno (`--style=compressed`)?
- [ ] ¿Respeté la paleta oficial Jaraba (7 colores + UI extendidos)?

### 🖼️ Iconos
- [ ] ¿Creé AMBAS versiones (outline + duotone)?
- [ ] ¿Usé `jaraba_icon()` para renderizar en Twig?
- [ ] ¿Los colores se aplican vía CSS filter (NO archivos por color)?

### 📄 Templates Twig
- [ ] ¿Template limpia sin regiones ni bloques de Drupal?
- [ ] ¿Header y footer vía `{% include %}` de parciales?
- [ ] ¿Layout full-width, mobile-first?
- [ ] ¿Verifiqué si ya existe un parcial antes de crear código nuevo?
- [ ] ¿Los parciales usan variables de `theme_settings` configurables desde la UI?

### 🏷️ Body Classes
- [ ] ¿Añadí clases al body vía `hook_preprocess_html()` (NO `attributes.addClass()`)?
- [ ] ¿Verifiqué que NO hay función duplicada?

### 🌐 i18n
- [ ] ¿Todos los textos visibles usan `{% trans %}`, `|t`, `$this->t()` o `Drupal.t()`?
- [ ] ¿Los labels de formularios tienen `#title => t('...')`?

### 🗃️ Content Entities
- [ ] ¿Navegación en `/admin/content/{entidad}` configurada?
- [ ] ¿Acceso a Field UI en `/admin/structure/{entidad}` configurado?
- [ ] ¿Integración con Views verificada?
- [ ] ¿Entity References definidos donde aplica?

### 🪟 Frontend & UX
- [ ] ¿Acciones CRUD abren en slide-panel/modal?
- [ ] ¿Sin admin toolbar para tenants?
- [ ] ¿Sin sidebar de admin (excepto para administradores de plataforma)?
- [ ] ¿Diseño premium (glassmorphism, hover effects, micro-animaciones)?

### 🏗️ Drupal
- [ ] ¿Comandos ejecutados dentro del contenedor Docker?
- [ ] ¿Caché limpiada con `docker exec jarabasaas_appserver_1 drush cr`?
- [ ] ¿Entity updates aplicados si hay cambios de esquema?

---

## 9. Roadmap de Implementación

> **Fuente:** Plan Maestro v3.0 — 7 Bloques, ~4,500h, 24 meses

| Fase | Quarter | Bloques | Horas | Foco |
|------|---------|---------|-------|------|
| 1 | Q1 2026 | A.1, A.2, B | 436h | Gaps auditoría, SEPE, Frontend, Copiloto v3 |
| 2 | Q2 2026 | A.3, C, E | 594h | AgroConecta, Journey Engine, Training |
| 3 | Q3 2026 | A.3, C, F | 560h | ComercioConecta, Journey expansión, Content Hub |
| 4 | Q4 2026 | C, D, G, A.4 | 780h | Journey completion, Admin Center, AI Skills |
| 5 | Q1 2027 | D, A.4 | 600h | Admin Center Premium, Marketing AI |
| 6 | Q2 2027 | A.4, Integration | 530h | ServiciosConecta expansión, Integration |

### Prioridad de Implementación por Vertical

1. **Core Platform** (Docs 01-07) — ✅ Completado
2. **Empleabilidad** (Docs 08-24) — ✅ Core completado
3. **Emprendimiento** (Docs 25-44) — ✅ Core completado
4. **AgroConecta** (Docs 47-61) — 🔶 Fases 1-3 completadas, Fase 4+ pendiente
5. **ServiciosConecta** (Docs 82-99) — 🔶 Fase 1 completada
6. **ComercioConecta** (Docs 62-79) — ⬜ Pendiente (reutiliza 70% de AgroConecta)

---

## 10. Apéndice: Documentación de Referencia

### Documentos Maestros
| Documento | Ruta |
|-----------|------|
| Directrices del Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` |
| Documento Maestro Arquitectura | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` |
| Índice General | `docs/00_INDICE_GENERAL.md` |
| Arquitectura Theming SaaS | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |
| Índice Maestro 170+ Specs | `docs/tecnicos/20260118l-141_Indice_Maestro_Consolidado_v1_Claude.md` |

### Workflows Operativos
| Workflow | Ruta |
|----------|------|
| SCSS y Variables | `.agent/workflows/scss-estilos.md` |
| Frontend Page Pattern | `.agent/workflows/frontend-page-pattern.md` |
| Slide-Panel Modales | `.agent/workflows/slide-panel-modales.md` |
| Drupal Custom Modules | `.agent/workflows/drupal-custom-modules.md` |

### Planes de Implementación por Vertical
| Vertical | Ruta |
|----------|------|
| AgroConecta v2 | `docs/implementacion/20260208-Plan_Implementacion_AgroConecta_v2.md` |
| ServiciosConecta v1 | `docs/implementacion/20260209-Plan_Implementacion_ServiciosConecta_v1.md` |
| Plan Maestro v3.0 | `docs/planificacion/20260123-Plan_Maestro_Unificado_SaaS_v3_Claude.md` |

### Aprendizajes Clave (Top 10)
| # | Aprendizaje | Ruta |
|---|-------------|------|
| 1 | Dart Sass `@use` Module System | `docs/tecnicos/aprendizajes/2026-02-09_servicios_conecta_fase1_implementation.md` |
| 2 | Frontend Limpio Zero Region | `docs/tecnicos/aprendizajes/2026-02-02_page_builder_frontend_limpio_zero_region.md` |
| 3 | Body Classes hook_preprocess_html | `docs/tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md` |
| 4 | Content Entities Drupal | `docs/tecnicos/aprendizajes/2026-01-25_content_entities_drupal.md` |
| 5 | Header Partials Dispatcher | `docs/tecnicos/aprendizajes/2026-01-25_header_partials_dispatcher.md` |
| 6 | Federated Design Tokens | `docs/tecnicos/aprendizajes/2026-02-05_arquitectura_theming_federated_tokens.md` |
| 7 | Twig Namespace Cross-Module | `docs/tecnicos/aprendizajes/2026-02-03_twig_namespace_cross_module.md` |
| 8 | Entity Navigation Pattern | `docs/tecnicos/aprendizajes/2026-01-19_entity_navigation_pattern.md` |
| 9 | GrapesJS Interactive Blocks | `docs/tecnicos/aprendizajes/2026-02-05_grapesjs_interactive_blocks_pattern.md` |
| 10 | Auditoría Profunda Multidimensional | `docs/tecnicos/aprendizajes/2026-02-06_auditoria_profunda_saas_multidimensional.md` |

---

> **Documento generado:** 2026-02-10  
> **Autor:** IA Asistente (revisión multi-rol: Arquitecto SaaS, SW, UX, Drupal, Theming, GrapesJS, SEO/GEO, IA)  
> **Próxima revisión:** Al completar cada fase del roadmap
