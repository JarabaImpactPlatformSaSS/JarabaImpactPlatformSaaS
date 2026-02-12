---
description: Crear página frontend SaaS (full-width, sin regiones Drupal)
---

# Workflow: Frontend Page Pattern

Este workflow describe cómo crear una nueva página frontend full-width para el SaaS, siguiendo el patrón establecido en `page--front.html.twig`.

## Prerequisitos

- Ruta definida en `*.routing.yml` del módulo
- Controlador que devuelve render array con `#theme`

## Pasos

### 1. Crear Template de Página

Ubicación: `web/themes/custom/ecosistema_jaraba_theme/templates/page--{route}.html.twig`

```twig
{# page--{route}.html.twig - Página frontend sin regiones Drupal #}
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
    theme_settings: theme_settings|default({})
  } %}

  <main id="main-content" class="{tipo}-main">
    <div class="{tipo}-wrapper">
      {{ page.content }}
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

### 2.5. Añadir Clases al Body (CRÍTICO)

> ⚠️ **Las clases añadidas en el template con `attributes.addClass()` NO funcionan para el body**. Debes usar `hook_preprocess_html()`.

Archivo: `ecosistema_jaraba_theme.theme` (buscar función existente y añadir al final)

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  // Añadir clases al body para rutas específicas
  if ($route === 'mi_modulo.mi_ruta') {
    $variables['attributes']['class'][] = '{tipo}-page';
    $variables['attributes']['class'][] = 'page-{tipo}';
  }
}
```

> ⚠️ **NO crear función duplicada**. Si ya existe `ecosistema_jaraba_theme_preprocess_html`, añadir la lógica dentro de la función existente.

### 3. Añadir Estilos SCSS

Archivo: `scss/_mi-componente.scss`

```scss
.{tipo}-page {
    min-height: 100vh;
    background: var(--ej-bg-body, $ej-bg-body);
    
    // Override hero--split grid (si existe en el tema)
    // NOTA (2026-02-11): hero--split ya NO se inyecta en rutas admin gracias al guard
    // en jaraba_theming.module (isAdminRoute). Este override es un respaldo defensivo
    // para páginas frontend que pudieran heredar el grid del tema.
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
### 5. Limpiar Caché

```bash
lando drush cr
```

### 6. Verificar en Navegador

Navegar a la nueva ruta y verificar:
- [ ] Header con logo y navegación visible
- [ ] Contenido full-width sin sidebar
- [ ] Footer visible al final
- [ ] Sin admin toolbar solapando (solo visible para admins)

## Referencia

- [docs/tecnicos/aprendizajes/2026-01-29_frontend_pages_pattern.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-29_frontend_pages_pattern.md)
- [docs/tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md) - Partículas y slide-panel
- [page--front.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig) - Ejemplo canónico

---

## Opciones Adicionales

### 7. Partículas Animadas en Header (Opcional)

Si el dashboard necesita partículas animadas como content-hub y site-builder:

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
        // Inicialización partículas con once() para evitar duplicación
      });
    }
  };
})(Drupal, once);
```

> ⚠️ **El JavaScript DEBE estar en archivo separado, NO inline en el template**

### 8. Botones con Slide-Panel (Opcional)

Para acciones CRUD que abren slide-panel:

```html
<a href="/url/del/contenido" 
   data-slide-panel="large"
   data-slide-panel-title="Título del Panel">
  Acción
</a>
```

La library global `slide-panel` maneja automáticamente la apertura/cierre.

Ver: [slide-panel-modales.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/slide-panel-modales.md)

