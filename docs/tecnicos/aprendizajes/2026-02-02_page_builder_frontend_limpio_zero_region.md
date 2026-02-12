# 🏗️ Frontend Limpio para Page Builder (Zero Region Policy)

**Fecha:** 2026-02-02  
**Contexto:** Implementación de template ultra-limpia para páginas de PageContent entities  
**Categoría:** Arquitectura Frontend, SaaS Multi-tenant

---

## 📋 Problema Identificado

Las páginas creadas con el Page Builder (`entity.page_content.canonical`) mostraban:

1. **Grid 2 columnas heredado**: `body { display: grid; grid-template-columns: 50% 50% }`
2. **Menú ecosistema hardcoded**: Enlaces Empleo/Talento/Emprender/Comercio/Instituciones
3. **Mobile menu overlay**: Con los mismos enlaces del ecosistema principal
4. **Breadcrumbs**: Bloque de navegación heredado de Drupal

Esto violaba el principio **"Zero Region Policy"** para páginas de tenant.

---

## ✅ Solución Implementada

### 1. Template Suggestion para PageContent

```php
// ecosistema_jaraba_theme.theme - hook_theme_suggestions_page_alter()
$page_builder_frontend_routes = [
  'entity.page_content.canonical',
  'entity.page_content.edit_form',
  'entity.page_content.delete_form',
];
if (in_array($route, $page_builder_frontend_routes)) {
  $suggestions[] = 'page__page_builder';
}
```

### 2. Body Classes via hook_preprocess_html()

```php
// CORRECTO: Usar hook_preprocess_html() para clases en <body>
// NO funciona: attributes.addClass() en templates Twig

$page_builder_frontend_routes = [
  'entity.page_content.canonical',
  // ... otras rutas
];
if (in_array($route, $page_builder_frontend_routes)) {
  $variables['attributes']['class'][] = 'page-page-builder';
  $variables['attributes']['class'][] = 'page-builder-frontend';
  $variables['attributes']['class'][] = 'full-width-layout';
  $variables['attributes']['class'][] = 'page-content-frontend';
  $variables['attributes']['class'][] = 'tenant-page';
}
```

### 3. SCSS Reset del Grid

```scss
// _landing-page.scss
body.page-page-builder,
body.page-builder-frontend,
body.page-content-frontend,
body.full-width-layout {
    display: block !important;
    width: 100% !important;
    max-width: none !important;
    grid-template-columns: unset !important;

    // Ocultar regiones heredadas
    .region--sidebar-first,
    .region--sidebar-second,
    .block-system-main-menu:not(.landing-nav) {
        display: none !important;
    }
}
```

### 4. Template Ultra-Limpia

```twig
{# page--page-builder.html.twig #}
{# Header INLINE - No usa partials con menú ecosistema #}
<header class="landing-header landing-header--tenant">
  <div class="landing-header__container">
    <a href="{{ path('<front>') }}" class="landing-header__brand">
      <img src="{{ logo }}" alt="{{ site_name }}" class="landing-header__logo" />
      <span class="landing-header__name">{{ site_name }}</span>
    </a>
    <div class="landing-header__actions">
      {% if logged_in %}
        <a href="/user" class="btn-ghost">Mi cuenta</a>
        <a href="/user/logout" class="btn-ghost">Cerrar sesión</a>
      {% endif %}
    </div>
  </div>
</header>

<main id="main-content" class="page-builder-main page-builder-frontend">
  <div class="page-builder-content full-width">
    {{ page.content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

---

## 🚫 Errores a Evitar

| Error | Por qué falla | Solución |
|-------|---------------|----------|
| `attributes.addClass()` en template | No funciona para `<body>` en Drupal | Usar `hook_preprocess_html()` |
| Usar `_header.html.twig` partial | Incluye menú ecosistema hardcoded | Header inline en template |
| Solo CSS sin body classes | Selectores no aplican sin clases | Añadir clases via preprocess |
| `page.breadcrumb` en template | Renderiza bloque heredado | Omitir de template |

---

## 🎯 Resultado Final

- ✅ Layout full-width (100% viewport)
- ✅ Header limpio (solo logo + acciones usuario)
- ✅ Sin menú ecosistema (Empleo/Talento/etc)
- ✅ Sin breadcrumbs heredados
- ✅ Sin sidebars ni regiones Drupal
- ✅ Mobile-first responsive

---

## 📚 Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme.theme` | Template suggestion + body classes |
| `page--page-builder.html.twig` | Header inline, sin menú ecosistema |
| `scss/components/_landing-page.scss` | Reset grid + ocultar regiones |

---

## 🔗 Referencias

- Nuclear #14: Frontend Limpio (Zero Region Policy)
- [2026-01-29_site_builder_frontend_fullwidth.md](./2026-01-29_site_builder_frontend_fullwidth.md) - Patrón similar para Site Builder
- [2026-01-25_header_partials_dispatcher.md](./2026-01-25_header_partials_dispatcher.md) - Sistema de partials header

---

## 💡 Lección Clave

> **"Para páginas de tenant en SaaS multi-tenant, el frontend debe estar completamente aislado de los elementos del ecosistema principal. Usar header inline en la template evita el acoplamiento con partials que contienen menús hardcoded."**
