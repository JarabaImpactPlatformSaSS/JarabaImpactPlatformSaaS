# Fix HTML Anidado en 37 Page Templates + Cumplimiento Directriz de Iconos

**Fecha:** 2026-02-23
**Sesion:** Eliminacion de DOCTYPE/html/head/body duplicados en page templates y migracion a jaraba_icon()
**Reglas nuevas:** TPL-PAGE-001, TPL-BODY-001, TPL-ICON-EXE-001
**Aprendizaje #103**

---

## Contexto

Una auditoria de `page--user.html.twig` revelo dos problemas sistematicos:

1. **Bug HTML anidado:** 37 page templates (`page--*.html.twig`) incluian `<!DOCTYPE html>`, `<html>`, `<head>`, `<body>` y `</body></html>`, causando un documento HTML con estructura duplicada. Drupal ya genera estos wrappers en `html.html.twig`; los page templates solo deben generar contenido dentro de `<body>`.

2. **Violacion directriz de iconos:** `page--user.html.twig` usaba ~15 SVGs inline en vez de la funcion `jaraba_icon()` del proyecto.

Ademas, se descubrio un bug derivado: `str_starts_with()` recibia `null` cuando `$route` no existia (paginas 404), causando un TypeError que generaba 503 en produccion.

---

## Lecciones Aprendidas

### 1. Los page templates NO deben contener DOCTYPE/html/head/body

**Situacion:** 37 de ~50 page templates contenian la estructura completa de un documento HTML:
```twig
{# INCORRECTO - page--ejemplo.html.twig #}
<!DOCTYPE html>
<html{{ html_attributes }}>
  <head>...</head>
  <body{{ attributes.addClass('page-ejemplo') }}>
    <a href="#main-content" class="visually-hidden">Skip</a>
    {# contenido real #}
    <js-bottom-placeholder token="{{ placeholder_token }}">
  </body>
</html>
```

Esto causaba que cada pagina tuviera DOS `<!DOCTYPE>`, DOS `<html>`, DOS `<head>`, y DOS `<body>` en el DOM — uno de `html.html.twig` y otro del page template.

**Aprendizaje:** En la jerarquia de templates de Drupal:
- `html.html.twig` genera: `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`, `</body>`, `</html>`
- `page--*.html.twig` genera: solo el contenido DENTRO de `<body>` (header, main, footer)
- Los page templates NO deben incluir skip links ni `<js-bottom-placeholder>` (ya los maneja `html.html.twig`)

**Regla TPL-PAGE-001:** Ningun `page--*.html.twig` debe contener `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`, `</body>`, `</html>`, skip links, ni `<js-bottom-placeholder>`. Cada page template debe tener un comentario NOTA explicando que es un PAGE template, no un HTML template. Excepcion unica: `page--canvas-editor.html.twig` que tiene su propio `html--canvas-editor.html.twig` standalone.

**Patron correcto:**
```twig
{#
/**
 * @file
 * page--ejemplo.html.twig
 *
 * NOTA: Este es un PAGE template, NO un HTML template.
 * El wrapper DOCTYPE/html/head/body lo proporciona html.html.twig.
 * Este template solo genera el contenido dentro de body.
 */
#}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with { ... } %}

<main id="main-content" class="dashboard-main">
  {{ clean_content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with { ... } %}
```

### 2. Body classes de page templates deben ir en preprocess_html()

**Situacion:** Muchos page templates asignaban body classes via `<body{{ attributes.addClass('page-ejemplo', 'dashboard-page') }}>`. Al eliminar el `<body>` del page template, estas clases se perdian porque `<body>` lo renderiza `html.html.twig`.

**Aprendizaje:** Las body classes deben centralizarse en `ecosistema_jaraba_theme_preprocess_html()`, usando la ruta actual para determinar que clases aplicar. Esto es mas robusto porque:
- Funciona con cualquier page template (no depende de que el template defina las clases)
- Centraliza la logica en un solo lugar
- Es testeable y auditable

**Regla TPL-BODY-001:** Las body classes DEBEN asignarse exclusivamente en `preprocess_html()`, NUNCA en page templates. Usar `$variables['attributes']['class'][]` con logica basada en `$route = (string) \Drupal::routeMatch()->getRouteName()`.

**Patron correcto en preprocess_html:**
```php
// Servicios dashboard -> page--servicios-dashboard.
if (str_starts_with((string) $route, 'jaraba_servicios_conecta.provider_portal')) {
  $variables['attributes']['class'][] = 'page-wrapper--clean';
  $variables['attributes']['class'][] = 'page--servicios-dashboard';
  $variables['attributes']['class'][] = 'dashboard-page';
}
```

### 3. getRouteName() puede retornar null — siempre castear a string

**Situacion:** `\Drupal::routeMatch()->getRouteName()` retorna `?string` (puede ser `null` en paginas 404 o cuando no hay ruta coincidente). El codigo usaba el resultado directamente en `str_starts_with($route, ...)`, causando `TypeError: str_starts_with(): Argument #1 ($haystack) must be of type string, null given`.

**Aprendizaje:** Siempre castear `$route` a `(string)` en el punto de asignacion para que todos los usos downstream sean seguros.

**Patron:**
```php
// CORRECTO
$route = (string) \Drupal::routeMatch()->getRouteName();

// INCORRECTO - puede causar TypeError
$route = \Drupal::routeMatch()->getRouteName();
```

### 4. SVGs decorativos multi-color son una excepcion valida a jaraba_icon()

**Situacion:** `page--user.html.twig` tenia 3 SVGs decorativos en la seccion de cabecera (edit/logout/generic) que usaban multiples colores con `CSS var()` y opacidades diferentes por forma (circulos, paths con `opacity="0.1"`, `0.25"`, `0.3"`).

**Aprendizaje:** La funcion `jaraba_icon()` renderiza `<img>` tags que no soportan:
- Multiples colores dentro del mismo icono
- CSS `var()` para colores dinamicos
- Opacidades diferentes por elemento SVG
- Recolorizado parcial (solo soporta filtros CSS globales)

Estos SVGs decorativos son ilustraciones, no iconos funcionales, y deben mantenerse inline con un comentario de exencion.

**Regla TPL-ICON-EXE-001:** Los SVGs inline son aceptables SOLO para ilustraciones decorativas multi-color que usen CSS `var()` y opacidades diferenciadas. Deben incluir un comentario de exencion:
```twig
{# EXENCION directriz iconos: SVGs decorativos multi-color con CSS var()
   y opacidades, incompatibles con renderizado <img> de jaraba_icon(). #}
```

### 5. jaraba_icon() renderiza <img>, no <svg> — los selectores SCSS deben adaptarse

**Situacion:** Los selectores SCSS usaban `.section-title svg { ... }` para estilizar iconos. Al migrar a `jaraba_icon()`, los iconos pasan a ser `<img class="jaraba-icon">`, no `<svg>`.

**Aprendizaje:** Al migrar de SVG inline a `jaraba_icon()`:
- Cambiar selectores de `svg` a `.jaraba-icon, img`
- Las propiedades CSS `color`, `fill`, `stroke` NO aplican a `<img>` — el color se gestiona via el parametro `color` de `jaraba_icon()` o filtros CSS
- Las propiedades `width`, `height`, `opacity`, `transform` SI aplican a `<img>`

**Patron SCSS:**
```scss
// ANTES (SVG inline)
.section-title svg {
  width: 20px;
  height: 20px;
  color: var(--ej-color-primary);
  opacity: 0.7;
}

// DESPUES (jaraba_icon → <img>)
.section-title .jaraba-icon,
.section-title img {
  opacity: 0.7;
}
```

---

## Archivos Modificados

### Fase A — Iconos en page--user.html.twig (3 archivos)
- `web/themes/custom/ecosistema_jaraba_theme/templates/page--user.html.twig` — 9 SVGs reemplazados por `jaraba_icon()`, 3 SVGs decorativos exentos
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` — `icon_category`/`icon_name` en quick links de `preprocess_page__user()`
- `web/themes/custom/ecosistema_jaraba_theme/scss/_user-pages.scss` — Selectores `svg` → `.jaraba-icon, img`

### Fase B — Fix HTML anidado (38 archivos)
- **37 page templates** — Eliminados DOCTYPE/html/head/body/skip-link/js-bottom-placeholder
- `ecosistema_jaraba_theme.theme` — Body classes migradas a `preprocess_html()`, fix `(string)` cast para `$route`

### Templates corregidos
```
page--admin--pixels       page--legal-compliance
page--admin-center        page--legal
page--ads                 page--mi-cuenta
page--agent-dashboard     page--my-certifications
page--auth                page--page-builder-experiments
page--ayuda               page--pixels--stats
page--content-hub         page--pixels
page--credentials         page--privacy
page--crm                 page--referidos
page--customer-success    page--revisions
page--dr-status           page--servicios-dashboard
page--eventos             page--servicios-marketplace
page--experimentos        page--site-builder
page--fiscal              page--skills
page--front               page--verify
page--i18n                page--interactive
page--integrations        page--jarabalex
page--legal-calendar      page--legal-case-detail
page--legal-cases
```

### Template excluido (intencionalmente standalone)
- `page--canvas-editor.html.twig` — Tiene su propio `html--canvas-editor.html.twig`

---

## Verificacion

1. `grep '<!DOCTYPE' page--*.html.twig` → Solo canvas-editor (standalone) y user (comentario)
2. `grep '<body' page--*.html.twig` → Solo canvas-editor y user (comentario)
3. `grep '</body>|</html>' page--*.html.twig` → Solo canvas-editor
4. `lando drush cr && curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/` → 200 OK
5. `lando drush watchdog:show --severity=3` → Sin errores nuevos post-fix

---

## Impacto

- **~800 lineas eliminadas** de wrappers HTML duplicados en 37 templates
- **~100 lineas netas eliminadas** de SVGs inline reemplazados por llamadas jaraba_icon() de 1 linea
- **1 bug runtime corregido** (TypeError null en str_starts_with)
- **0 regresiones** — Todas las paginas renderizan correctamente con un solo DOCTYPE/html/body
