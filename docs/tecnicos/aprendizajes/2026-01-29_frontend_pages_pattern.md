# Patrón de Páginas Frontend SaaS

**Fecha**: 2026-01-29  
**Contexto**: Content Hub Dashboard / Page Builder

---

## Problema

Las páginas frontend del SaaS necesitan un layout **full-width** sin las regiones de Drupal (sidebar, breadcrumbs, admin toolbar) que limitan el contenido. Usar el template `page.html.twig` estándar genera restricciones de ancho y elementos no deseados.

## Solución: Template Dedicado (page--{route}.html.twig)

Crear un template Twig **dedicado sin regiones de Drupal** que incluya los parciales del tema (header, footer) directamente.

### Estructura del Patrón

```
┌─────────────────────────────────────────────────────────────┐
│  page--{route}.html.twig (Drupal Theme)                     │
│  └── Template HTML COMPLETO (<!DOCTYPE html>...<html/>)    │
│      ┌── {% include '_header.html.twig' %}                 │
│      ├── <main class="{tipo}-main">                        │
│      │   └── {{ page.content }}                            │
│      └── {% include '_footer.html.twig' %}                 │
└─────────────────────────────────────────────────────────────┘
```

### Archivos Necesarios

1. **Template de página** (en tema):
   - `templates/page--{route}.html.twig`
   
2. **Hook de sugerencias** (en .theme):
   - `hook_theme_suggestions_page_alter()` añade `page__{route}`

3. **Estilos SCSS**:
   - Clases `.{tipo}-page`, `.{tipo}-main`, `.{tipo}-wrapper`

### Ejemplo: page--content-hub.html.twig

```twig
{# Template COMPLETO HTML sin regiones de Drupal #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/content-hub') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes.addClass('page-content-hub', 'dashboard-page') }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Skip to main content{% endtrans %}
  </a>

  {# HEADER - Partial reutilizable #}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  {# MAIN - Full-width #}
  <main id="main-content" class="dashboard-main">
    <div class="dashboard-wrapper">
      {{ page.content }}
    </div>
  </main>

  {# FOOTER - Partial reutilizable #}
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

### Hook en .theme

```php
function ecosistema_jaraba_theme_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
    $route = \Drupal::routeMatch()->getRouteName();
    
    // Rutas con template dedicado
    if ($route === 'jaraba_content_hub.dashboard.frontend') {
        $suggestions[] = 'page__content_hub';
    }
}
```

### Estilos SCSS

```scss
.dashboard-page {
    min-height: 100vh;
    background: var(--ej-bg-body, $ej-bg-body);
}

.dashboard-main {
    width: 100%;
    min-height: calc(100vh - 160px);
}

.dashboard-wrapper {
    max-width: 1400px;
    margin-inline: auto;
    padding: $ej-spacing-xl $ej-spacing-lg;
    
    @media (max-width: 767px) {
        padding: $ej-spacing-lg $ej-spacing-md;
    }
}
```

---

## Templates Existentes con Este Patrón

| Template | Ruta | Propósito |
|----------|------|-----------|
| `page--front.html.twig` | `/` | Homepage / Landing page |
| `page--content-hub.html.twig` | `/content-hub` | Dashboard editor |
| `page--dashboard.html.twig` | `/employer`, `/jobseeker`, etc. | Dashboards de verticales |
| `page--vertical-landing.html.twig` | `/empleo`, `/talento`, etc. | Landing pages de verticales |

---

## Directrices Clave

1. **Nunca usar `{{ page.region }}`** en templates dedicados - limita el control del layout
2. **Siempre usar `{% include %}`** para parciales reutilizables (_header, _footer)
3. **Pasar variables explícitamente**: `site_name`, `logo`, `logged_in`, `theme_settings`
4. **Usar clases BEM específicas** para cada tipo de página
5. **El contenedor de ancho** debe estar en el wrapper, no en el main
6. **Registrar en hook_theme_suggestions_page_alter()** la nueva sugerencia de template
7. **Usar hook_preprocess_html()** para añadir clases al body (no funcionan desde el template de página)
8. **Override CSS para hero--split** si existe un grid global que restringe el ancho

---

## ⚠️ Gotcha Crítico: Clases del Body

Las clases añadidas en el template de página con `attributes.addClass()` **NO SE APLICAN** al body final porque Drupal procesa las clases en `html.html.twig`.

### Solución: hook_preprocess_html()

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  // Dashboard frontend routes - clase para anular grid hero--split
  $dashboard_frontend_routes = [
    'jaraba_content_hub.dashboard.frontend',  // /content-hub
  ];
  
  if (in_array($route, $dashboard_frontend_routes)) {
    $variables['attributes']['class'][] = 'dashboard-page';
    $variables['attributes']['class'][] = 'page-content-hub';
  }
}
```

> [!IMPORTANT]
> Si ya existe `hook_preprocess_html` en el archivo `.theme`, **añadir la lógica a la función existente**. No crear una función duplicada o causará Fatal Error de PHP.

---

## Override de hero--split

Si el tema añade una clase global `hero--split` que crea un grid restrictivo, añadir override CSS:

```scss
.dashboard-page {
    min-height: 100vh;
    background: var(--ej-bg-body, $ej-bg-body);
    
    // Override hero--split grid que restringe el ancho
    &.hero--split {
        display: block !important;
        grid-template-columns: unset !important;
    }
}
```

Esto permite que páginas con clase `dashboard-page` anulen el grid de `hero--split` sin modificar el código base del tema.

---

## Referencia

- [page--front.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig) - Ejemplo canónico
- [ecosistema_jaraba_theme.theme](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme) - `hook_preprocess_html()` y `hook_theme_suggestions_page_alter()`
- [_content-hub.scss](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/scss/_content-hub.scss) - Estilos dashboard y override hero--split
