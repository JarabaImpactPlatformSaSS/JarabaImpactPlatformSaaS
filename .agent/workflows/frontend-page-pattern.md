---
description: Crear página frontend SaaS (full-width, sin regiones Drupal)
---

# Workflow: Frontend Page Pattern

Este workflow describe cómo crear una nueva página frontend full-width para el SaaS.

## Principio Fundamental: Zero Region Policy

> **NUNCA usar `{{ page.content }}` ni ningún `{{ page.* }}` en templates custom.**
>
> `{{ page.content }}` renderiza TODOS los bloques asignados a la región "content"
> en Block Layout, incluyendo FABs, menús contextuales y bloques sin restricción
> de visibilidad. Esto contamina el frontend con elementos heredados de Drupal.
>
> **Usar siempre `{{ clean_content }}`** — variable extraída por `preprocess_page()`
> que contiene SOLO el output del controlador (system_main_block).

## Variables Limpias Disponibles

Extraídas automáticamente en `ecosistema_jaraba_theme_preprocess_page()`:

| Variable | Contenido | Fuente |
|----------|-----------|--------|
| `{{ clean_content }}` | Render array del controlador | `system_main_block` de `page.content` |
| `{{ clean_messages }}` | Mensajes de estado/warning/error | `system_messages_block` de `page.highlighted` |
| `{{ avatar_nav }}` | Navegacion contextual por avatar | `AvatarNavigationService` (items, avatar, avatar_label) |
| `{{ copilot_context }}` | Contexto para copiloto IA | `CopilotContextService` |

Estas variables están disponibles en **todas** las `page--*.html.twig` del tema.

### avatar_nav (Navegacion Contextual)

La variable `avatar_nav` se inyecta automaticamente en `preprocess_page()` cuando:
- La ruta NO es admin (`isAdminRoute()`)
- El Theme Setting `enable_avatar_nav` esta activo (default TRUE)
- El servicio `ecosistema_jaraba_core.avatar_navigation` esta disponible

Estructura:
```php
$variables['avatar_nav'] = [
  'items' => [...],       // Array de items con id, label, url, icon_category, icon_name, weight, active
  'avatar' => 'jobseeker', // Tipo de avatar detectado
  'avatar_label' => 'Candidato', // Etiqueta traducible
];
```

La barra se renderiza automaticamente dentro de `_header.html.twig` via include de `_avatar-nav.html.twig`.

> **IMPORTANTE**: Si tu page template usa `} only %}` al incluir el header, DEBES pasar `'avatar_nav': avatar_nav|default(null)` en el bloque `with { ... }`. Sin esto, la navegacion contextual no se mostrara en esa pagina.

## Prerequisitos

- Ruta definida en `*.routing.yml` del módulo
- Controlador que devuelve render array con `#theme`

## Pasos

### 1. Crear Template de Página

Ubicación: `web/themes/custom/ecosistema_jaraba_theme/templates/page--{route}.html.twig`

```twig
{# page--{route}.html.twig - Zero Region Policy #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/{libreria}') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes.addClass('page-{tipo}', '{tipo}-page') }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Skip to main content{% endtrans %}
  </a>

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({}),
    avatar_nav: avatar_nav|default(null)
  } %}

  <main id="main-content" class="{tipo}-main">
    {# Mensajes del sistema (status, warning, error) #}
    {% if clean_messages %}
      <div class="highlighted container">
        {{ clean_messages }}
      </div>
    {% endif %}

    {# Contenido del controlador — SIN bloques heredados #}
    <div class="{tipo}-wrapper">
      {{ clean_content }}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

### 2. Registrar Sugerencia de Template

Archivo: `ecosistema_jaraba_theme.theme`

```php
function ecosistema_jaraba_theme_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
    $route = \Drupal::routeMatch()->getRouteName();

    if ($route === 'mi_modulo.mi_ruta') {
        $suggestions[] = 'page__mi_ruta';
    }
}
```

### 2.5. Añadir Clases al Body (CRITICO)

> **Las clases añadidas en el template con `attributes.addClass()` NO funcionan para el body**. Debes usar `hook_preprocess_html()`.

Archivo: `ecosistema_jaraba_theme.theme` (buscar función existente y añadir al final)

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();

  // Añadir clases al body para rutas específicas
  if ($route === 'mi_modulo.mi_ruta') {
    $variables['attributes']['class'][] = '{tipo}-page';
    $variables['attributes']['class'][] = 'page-{tipo}';
    $variables['attributes']['class'][] = 'full-width-layout';
  }
}
```

> **NO crear función duplicada**. Si ya existe `ecosistema_jaraba_theme_preprocess_html`, añadir la lógica dentro de la función existente.

### 3. Añadir Estilos SCSS

Archivo: `scss/_mi-componente.scss`

```scss
.{tipo}-page {
    min-height: 100vh;
    background: var(--ej-bg-body, $ej-bg-body);

    // Override hero--split grid (respaldo defensivo)
    &.hero--split {
        display: block !important;
        grid-template-columns: unset !important;
    }
}

.{tipo}-main {
    width: 100%;
    min-height: calc(100vh - 160px);
}

.{tipo}-wrapper {
    max-width: 1400px;
    margin-inline: auto;
    padding: $ej-spacing-xl $ej-spacing-lg;

    @media (max-width: 767px) {
        padding: $ej-spacing-lg $ej-spacing-md;
    }
}
```

// turbo
### 4. Compilar SCSS

```bash
cd web/themes/custom/ecosistema_jaraba_theme
source ~/.nvm/nvm.sh && nvm use 20 && npm run build
```

// turbo
### 5. Limpiar Cache

```bash
lando drush cr
```

### 6. Verificar en Navegador

Navegar a la nueva ruta y verificar:
- [ ] Header con logo y navegacion visible
- [ ] Avatar nav contextual visible (barra bajo header en desktop, bottom nav en mobile)
- [ ] Contenido full-width sin sidebar
- [ ] Footer visible al final
- [ ] Sin bloques heredados de Drupal (tabs, FABs, menus contextuales)
- [ ] Sin admin toolbar solapando (solo visible para admins)

## Errores Comunes

| Error | Por que falla | Solucion |
|-------|---------------|----------|
| `{{ page.content }}` en template | Filtra TODOS los bloques de la region content | Usar `{{ clean_content }}` |
| `{{ page.breadcrumb }}` | Renderiza bloque breadcrumb de Drupal | Omitir — usar breadcrumb propio o ninguno |
| `{{ page.highlighted }}` | Renderiza tabs y acciones de admin | Usar `{{ clean_messages }}` solo para mensajes |
| `attributes.addClass()` en template | No aplica al `<body>` en Drupal | Usar `hook_preprocess_html()` |
| Duplicar `hook_preprocess_html()` | PHP fatal error | Añadir a la funcion existente |
| Crear nuevo preprocess para extraer system_main | Duplica logica | Usar `clean_content` ya disponible desde `preprocess_page()` |

## Estado de Migracion (2026-02-13)

**Migración COMPLETA** — Todas las templates usan `{{ clean_content }}`.

**Cobertura de rutas: 100%** — 324/324 rutas frontend de módulos custom tienen
template limpio asignado vía `hook_theme_suggestions_page_alter()`.

### Arquitectura de Cobertura

1. **Catch-all de baja prioridad** (inicio de `theme_suggestions_page_alter`):
   - Asigna `page__dashboard` a TODAS las rutas de módulos custom del ecosistema.
   - Guard `isAdminRoute()` excluye rutas admin.
   - Array `$ecosistema_prefixes` con 44 prefijos de módulos.

2. **Mapeos específicos** (secciones individuales):
   - Rutas con template propio sobreescriben el catch-all.
   - Ejemplo: `jaraba_events.*` → `page__eventos` (mayor prioridad).

3. **Body classes catch-all** (en `hook_preprocess_html`):
   - Añade `dashboard-page` + `full-width-layout` + clase por módulo.
   - Guard: solo si no tiene ya `dashboard-page`, `landing-page`, etc.

### Templates Migradas

Todas las `page--*.html.twig` usan `{{ clean_content }}`:
- `page--dashboard.html.twig` — `{{ dashboard_content }}` (alias)
- `page--content-hub.html.twig` — `{{ clean_content }}`
- `page--auth.html.twig` — `{{ auth_form }}` (variable propia)
- `page--front.html.twig` — no usa `page.*`
- 31 templates más — todas migradas a `{{ clean_content }}`

### Para Añadir un Nuevo Módulo

Si se crea un nuevo módulo `jaraba_nuevo`:
1. Añadir `'jaraba_nuevo.'` al array `$ecosistema_prefixes` en `theme_suggestions_page_alter()`
2. (Opcional) Si necesita template específico, añadir sección después del catch-all
3. (Opcional) Añadir al array `$module_body_classes` en `hook_preprocess_html()` para CSS por módulo

## Referencia

- [docs/tecnicos/aprendizajes/2026-01-29_frontend_pages_pattern.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-29_frontend_pages_pattern.md)
- [docs/tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md) - Particulas y slide-panel
- [page--content-hub.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--content-hub.html.twig) - Ejemplo canonico migrado

---

## Variante: Admin Center Shell (Spec f104)

Para paginas que forman parte del **Admin Center Premium**, NO se usa el template estandar con header/footer global. En su lugar se usa el **shell layout** con sidebar + topbar.

### Diferencias con el patron estandar

| Aspecto | Patron Estandar | Admin Center Shell |
|---------|----------------|--------------------|
| Layout | Header + Main + Footer | Sidebar + Topbar + Content |
| Template | `page--{route}.html.twig` | `admin-center-{seccion}.html.twig` via shell |
| Navegacion | `_header.html.twig` + avatar_nav | Sidebar con menu sections |
| Body class | `page-{tipo}` + `full-width-layout` | `page-admin-center` + `admin-center-page` |
| `_admin_route` | No especificado (default) | `FALSE` (fuerza frontend theme) |
| Dark mode | No | Si (body.dark-mode + prefers-color-scheme) |
| SCSS namespace | `_{tipo}.scss` | `_admin-center-{seccion}.scss` |

### Como anadir una nueva pagina al Admin Center

1. **Routing** — Anadir ruta en `ecosistema_jaraba_core.routing.yml`:
   ```yaml
   ecosistema_jaraba_core.admin.center.nueva_seccion:
     path: '/admin/jaraba/center/nueva-seccion'
     defaults:
       _controller: '\Drupal\ecosistema_jaraba_core\Controller\AdminCenterController::nuevaSeccion'
       _title: 'Nueva Seccion'
     requirements:
       _permission: 'administer site configuration'
     options:
       _admin_route: FALSE
   ```

2. **Controller** — Anadir metodo en `AdminCenterController`:
   ```php
   public function nuevaSeccion(): array {
     return [
       '#theme' => 'admin_center_nueva_seccion',
       '#attached' => ['library' => ['ecosistema_jaraba_core/admin-center-nueva-seccion']],
     ];
   }
   ```

3. **Theme hook** — Registrar en `ecosistema_jaraba_core.module` (`hook_theme()`):
   ```php
   'admin_center_nueva_seccion' => [
     'variables' => [],
     'template' => 'admin-center-nueva-seccion',
   ],
   ```

4. **Template** — Crear `templates/admin-center-nueva-seccion.html.twig` (el shell se envuelve automaticamente via el theme hook `admin_center_shell`).

5. **Body class + Template suggestion** — Anadir ruta a los arrays en `.theme`:
   - `$admin_center_body_routes` en `hook_preprocess_html()`
   - `$admin_center_routes` en `hook_theme_suggestions_page_alter()`

6. **Quick Link** — Anadir al array en `AdminCenterAggregatorService::getQuickLinks()`.

7. **SCSS** — Crear `scss/_admin-center-nueva-seccion.scss` + `@use` en `main.scss`.

8. **Library** — Registrar en `ecosistema_jaraba_core.libraries.yml`.

9. **API endpoints** — Si necesita datos async, anadir rutas API + metodos en `AdminCenterApiController` usando `ApiResponseTrait` (envelope `{success, data, meta}`).

> **IMPORTANTE**: Toda pagina nueva del Admin Center DEBE usar el shell layout. No crear paginas admin standalone fuera del shell (regla ADMIN-CENTER-001).

---

## Opciones Adicionales

### 7. Particulas Animadas en Header (Opcional)

Si el dashboard necesita particulas animadas como content-hub y site-builder:

```html
<!-- En el template -->
<header class="dashboard-header dashboard-header--premium dashboard-header--corporate">
  <canvas id="{tipo}-particles" class="dashboard-header__particles" aria-hidden="true"></canvas>
  <!-- contenido header -->
</header>
```

```javascript
// En {tipo}-dashboard.js
(function (Drupal, once) {
  Drupal.behaviors.{tipo}Particles = {
    attach: function (context) {
      once('{tipo}-particles', '#{tipo}-particles', context).forEach(function (canvas) {
        // Inicializacion particulas con once() para evitar duplicacion
      });
    }
  };
})(Drupal, once);
```

> **El JavaScript DEBE estar en archivo separado, NO inline en el template**

### 8. Botones con Slide-Panel (Opcional)

Para acciones CRUD que abren slide-panel:

```html
<a href="/url/del/contenido"
   data-slide-panel="large"
   data-slide-panel-title="Titulo del Panel">
  Accion
</a>
```

La library global `slide-panel` maneja automaticamente la apertura/cierre.

Ver: [slide-panel-modales.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/slide-panel-modales.md)

### 9. Canvas 2D Dashboard (HEATMAP-005)

> Aprendizaje 2026-02-12 — Validado en jaraba_heatmap (Dashboard Analytics)

Para dashboards con visualizaciones Canvas 2D (heatmaps, graficas), seguir el patron de 3 capas:

1. **Controller** devuelve `#theme` con datos + `#attached` library
2. **hook_preprocess_html** inyecta body classes (`page-heatmap-analytics`, `dashboard-page`)
3. **Page template** Twig con `{{ clean_content }}` (Zero Region)

```javascript
// JS usa Drupal.behaviors + once() + fetch() API para carga asincrona
(function (Drupal, once) {
  Drupal.behaviors.heatmapDashboard = {
    attach: function (context) {
      once('heatmap-dashboard', '.heatmap-canvas', context).forEach(function (canvas) {
        const ctx = canvas.getContext('2d');
        // Renderizado con gradientes radiales de calor
        fetch('/api/heatmap/data?' + new URLSearchParams({page: currentPage}))
          .then(r => r.json())
          .then(data => renderHeatmap(ctx, data));
      });
    }
  };
})(Drupal, once);
```

> [!IMPORTANT]
> Se usa Canvas 2D vanilla (no React) para coherencia con el stack frontend Drupal behaviors del proyecto.
> Ver: `docs/tecnicos/aprendizajes/2026-02-12_heatmaps_tracking_phases_1_5.md`
