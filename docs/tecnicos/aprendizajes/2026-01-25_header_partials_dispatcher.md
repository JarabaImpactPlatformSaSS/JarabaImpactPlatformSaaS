# 🏗️ Aprendizaje: Sistema de Partials con Dispatcher para Headers y Layouts Configurables

**Fecha:** 2026-01-25  
**Contexto:** Refactorización del sistema de headers para soportar 5 layouts seleccionables desde theme settings  
**Módulo/Tema:** `ecosistema_jaraba_theme`

---

## 📋 Problema Resuelto

La landing page necesitaba soportar múltiples diseños de header (classic, centered, hero, split, minimal) seleccionables desde la configuración del tema, sin duplicar menú móvil ni lógica compartida.

---

## 🏗️ Patrón Implementado: Partial Dispatcher

### Arquitectura

```
_header.html.twig (dispatcher)
├── Lee theme_settings.header_layout
├── Include condicional del sub-partial
│   ├── _header-classic.html.twig
│   ├── _header-centered.html.twig
│   ├── _header-hero.html.twig
│   ├── _header-split.html.twig
│   └── _header-minimal.html.twig
└── Mobile Menu Overlay (compartido)
```

### Dispatcher Principal

```twig
{# _header.html.twig - Dispatcher que incluye el header según configuración #}
{% set ts = theme_settings|default({}) %}
{% set header_layout = ts.header_layout|default('classic') %}

{# Variables compartidas para sub-partial #}
{% set header_vars = {
  site_name: site_name,
  logo: logo,
  logged_in: logged_in
} %}

{# Incluir el sub-partial correspondiente #}
{% include '@ecosistema_jaraba_theme/partials/_header-' ~ header_layout ~ '.html.twig' with header_vars %}

{# Mobile Menu Overlay (compartido por todos los layouts) #}
<div class="mobile-menu-overlay" aria-hidden="true">
  <nav class="mobile-menu-nav">
    <ul class="mobile-menu-list">
      <li><a href="/empleo">{% trans %}Empleo{% endtrans %}</a></li>
      ...
    </ul>
  </nav>
</div>
```

---

## 🎯 Cuándo Usar Dispatcher vs Clases CSS

| Patrón | Cuándo Usar | Ejemplo |
|--------|-------------|---------|
| **Dispatcher** (include dinámico) | Variación estructural HTML significativa | Headers (5 layouts distintos) |
| **Clases CSS** (mismo template) | Variación principalmente de estilos | Hero, Footer (clases `--layout`) |

### Ejemplo Clase CSS (Hero)

```twig
{# _hero.html.twig - Usa clases, no dispatcher #}
{% set hero_layout = ts.hero_layout|default('fullscreen') %}
<section class="hero-landing hero-landing--{{ hero_layout }}">
  ...
</section>
```

---

## ⚠️ Gotcha: Menú Móvil en Layout Minimal Desktop

### Problema
El layout "minimal" muestra solo logo + hamburguesa **incluso en desktop**. Pero el CSS tenía:

```scss
@media (min-width: 992px) {
    .mobile-menu-overlay {
        display: none !important;  // ¡Oculta el overlay en desktop!
    }
}
```

### Solución

```scss
// Excepción para layout minimal: permitir overlay en desktop
.header-layout-minimal .mobile-menu-overlay {
    display: block !important;
}

.header-layout-minimal .mobile-menu-overlay.is-open .mobile-menu-nav {
    right: 0;
}
```

### Clase en Body
El `.theme` file añade clase al body según layout:

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    $header_layout = theme_get_setting('header_layout') ?? 'classic';
    $variables['attributes']['class'][] = 'header-layout-' . $header_layout;
}
```

---

## 📂 Archivos Creados/Modificados

| Archivo | Cambio |
|---------|--------|
| `partials/_header.html.twig` | Dispatcher principal |
| `partials/_header-{layout}.html.twig` | 5 sub-partials |
| `scss/components/_mobile-menu.scss` | Excepción para minimal desktop |
| `js/mobile-menu.js` | Reforzado con `Drupal.behaviors` |
| `.theme` | Clase `header-layout-X` en body |

---

## 💡 Buenas Prácticas Aprendidas

1. **Dispatcher para variación estructural**: Cuando layouts tienen HTML muy diferente
2. **Clases CSS para variación estilística**: Cuando la estructura es igual
3. **Overlay compartido**: Mobile menu fuera del sub-partial para DRY
4. **Media queries con excepciones**: Añadir override para layouts especiales
5. **Clase en body**: Permite selectores CSS scoped por layout
6. **Drupal.behaviors para JS**: Re-attach en AJAX, nombre único para evitar conflictos
