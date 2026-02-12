# Aprendizaje: Site Builder Frontend Full-Width y Partículas

**Fecha:** 2026-01-29  
**Contexto:** Implementación del frontend limpio para Site Builder con partículas animadas  
**Módulo:** `jaraba_site_builder`

## Problema

El Site Builder frontend (`/site-builder`) no mostraba full-width como content-hub. El layout estaba restringido al 50% izquierdo de la pantalla.

## Causa Raíz

1. **Clase `hero--split` en el body** - El tema base aplica un grid 50/50 en el body
2. **Clase `page-site-builder` ausente** - No se añadía la clase al body, por lo que el CSS override no funcionaba
3. **Error conceptual** - `attributes.addClass()` en templates Twig **NO funcionan para el body**

## Solución

### 1. Añadir clases via `hook_preprocess_html()`

```php
// ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  $site_builder_routes = [
    'jaraba_site_builder.frontend.dashboard',
    'jaraba_site_builder.frontend.tree',
    'jaraba_site_builder.frontend.config',
    'jaraba_site_builder.frontend.redirects',
    'jaraba_site_builder.frontend.sitemap',
  ];
  
  if (in_array($route, $site_builder_routes)) {
    $variables['attributes']['class'][] = 'dashboard-page';
    $variables['attributes']['class'][] = 'page-site-builder';
  }
}
```

### 2. Añadir estilos para anular grid

```scss
body.page-site-builder {
  &.hero--split {
    display: block !important;
    grid-template-columns: unset !important;
  }
}
```

### 3. Partículas con Drupal behaviors

El JavaScript de partículas debe estar en archivo **separado**, no inline en el template:

```javascript
// js/site-builder-dashboard.js
(function (Drupal, once) {
  Drupal.behaviors.siteBuilderParticles = {
    attach: function (context) {
      once('site-builder-particles', '#site-builder-particles', context).forEach(function (canvas) {
        // Inicialización con once() para evitar duplicación
      });
    }
  };
})(Drupal, once);
```

### 4. Slide-panel con data attributes

Los botones que abren slide-panel deben usar `data-slide-panel` (la library global lo maneja):

```html
<a href="/page-builder/templates" 
   data-slide-panel="large"
   data-slide-panel-title="Nueva Página">
```

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme.theme` | `hook_preprocess_html()` clases body |
| `_site-builder.scss` | Header partículas, variante corporate, layout |
| `site-builder-dashboard.html.twig` | Canvas, data-slide-panel |
| `site-builder-dashboard.js` | Partículas con `once()` |
| `libraries.yml` | Library site-builder |

## Lecciones Clave

1. **`attributes.addClass()` NO funciona en templates para el body** - Siempre usar `hook_preprocess_html()`
2. **JavaScript de Drupal behaviors debe estar en archivo separado** - No inline en templates
3. **Usar `once()` para behaviors** - Evita inicialización múltiple
4. **Seguir patrón de content-hub** - Consistencia visual y funcional
5. **Verificar clases del body con DevTools** - Comparar con módulos que funcionan

## Verificación

- ✅ Layout full-width (1352px vs 578px antes)
- ✅ Partículas animadas en canvas
- ✅ Header azul corporativo con gradiente
- ✅ Botón "Nueva Página" abre slide-panel
- ✅ Clases `page-site-builder` y `dashboard-page` en body

## Referencias

- [Workflow frontend-page-pattern.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/frontend-page-pattern.md)
- [Content Hub Dashboard](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_content_hub/templates/content-hub-dashboard-frontend.html.twig) - Patrón de referencia
- [Slide Panel Pattern](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/slide-panel-modales.md)
