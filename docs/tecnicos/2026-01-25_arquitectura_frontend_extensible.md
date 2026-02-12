# 🏗️ Arquitectura Frontend Extensible - Sistema de Componentes Premium

**Fecha:** 2026-01-25  
**Versión:** 1.0.0  
**Estado:** DIRECTRIZ TÉCNICA

---

## 📋 Objetivo

Definir la arquitectura extensible del frontend premium para que el diseño, componentes y estilismo de la homepage se apliquen de forma coherente al resto de páginas del SaaS.

---

## 🎯 Decisión Arquitectónica: Dispatcher vs Clases CSS

### Criterio de Decisión

| Criterio | Dispatcher (Include Dinámico) | Clases CSS (Mismo Template) |
|----------|-------------------------------|-------------------------------|
| **Estructura HTML** | Muy diferente entre layouts | Similar/Idéntica |
| **Complejidad** | Alta (diferentes secciones) | Baja-Media (cambios estéticos) |
| **Mantenibilidad** | Mejor si layouts divergen mucho | Mejor si comparten 80%+ código |
| **Ejemplo** | Headers (5 layouts distintos) | Hero, Footer (variación CSS) |

### Recomendación por Partial

| Partial | Patrón | Justificación |
|---------|--------|---------------|
| **Header** | ✅ Dispatcher | 5 layouts con estructura HTML muy diferente |
| **Hero** | ❌ Clases CSS | Misma estructura, variación es visual (animaciones, tamaños) |
| **Footer** | ❌ Clases CSS | 4 layouts comparten 70%+ estructura |
| **Intenciones** | ❌ Clases CSS | Grid configurable, misma base |
| **Features** | ❌ Clases CSS | Cards con variación de iconos/colores |

> [!IMPORTANT]
> **Regla General:** Solo crear dispatcher si los layouts tienen **menos del 50% de código HTML compartido**. En caso contrario, usar clases CSS con modificadores BEM.

---

## 📂 Estructura de Carpetas del Tema

```
ecosistema_jaraba_theme/
├── templates/
│   ├── layout/
│   │   └── page.html.twig              # Layout estándar con regiones
│   ├── page/
│   │   └── page--front.html.twig       # Homepage (sin regiones Drupal)
│   └── partials/
│       ├── _header.html.twig           # Dispatcher
│       ├── _header-{layout}.html.twig  # 5 sub-partials
│       ├── _hero.html.twig             # Clases CSS
│       ├── _footer.html.twig           # Clases CSS
│       ├── _intentions-grid.html.twig  # Clases CSS
│       └── _features.html.twig         # Clases CSS
├── scss/
│   ├── main.scss                       # Entry point
│   ├── _variables.scss                 # Design tokens
│   └── components/
│       ├── _header.scss
│       ├── _hero-landing.scss
│       ├── _footer.scss
│       └── _mobile-menu.scss
└── js/
    ├── mobile-menu.js
    └── scroll-animations.js
```

---

## 🎨 Sistema de Design Tokens

### Variables CSS Inyectables

Todas las páginas deben usar las variables CSS definidas para permitir personalización por tenant:

```scss
// _variables.scss - Design Tokens
:root {
  // Colores de marca
  --ej-color-corporate: #233D63;
  --ej-color-impulse: #FF8C42;
  --ej-color-innovation: #00A9A5;
  
  // Superficies
  --ej-bg-primary: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  --ej-bg-surface: #ffffff;
  --ej-card-border: rgba(255, 255, 255, 0.1);
  
  // Tipografía
  --ej-font-family: 'Inter', system-ui, sans-serif;
  --ej-text-primary: #f8fafc;
  --ej-text-muted: #94a3b8;
  
  // Spacing
  --ej-spacing-section: 5rem;
  --ej-radius-lg: 1.5rem;
  --ej-shadow-glow: 0 0 30px rgba(255, 140, 66, 0.3);
}
```

### Clases Utilitarias Reutilizables

```scss
// Glassmorphism Cards
.glass-card {
  background: var(--ej-card-bg);
  backdrop-filter: blur(10px);
  border: 1px solid var(--ej-card-border);
  border-radius: var(--ej-radius-lg);
}

// Botones Premium
.btn-primary--glow {
  background: var(--ej-color-impulse);
  box-shadow: var(--ej-shadow-glow);
  transition: transform 0.2s, box-shadow 0.2s;
  &:hover { transform: translateY(-2px); }
}

// Gradientes de texto
.text-gradient {
  background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
```

---

## 📄 Extensión a Otras Páginas

### Patrón para Nuevas Páginas Clean (Sin Regiones)

Para páginas que necesitan diseño premium completo (landings, onboarding, dashboards):

**1. Crear template de página:**
```twig
{# page--mi-pagina.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/landing-pages') }}

<div class="page-wrapper page-wrapper--clean">
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}
  
  <main class="main-content">
    {# Incluir partials según necesidad #}
    {% include '@ecosistema_jaraba_theme/partials/_hero.html.twig' with {
      title: 'Mi Título',
      subtitle: 'Mi subtítulo'
    } %}
    
    {{ page.content }}
  </main>
  
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
</div>
```

**2. Añadir theme suggestion en .theme:**
```php
function ecosistema_jaraba_theme_theme_suggestions_page_alter(&$suggestions, $variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'mi_modulo.landing')) {
    $suggestions[] = 'page__mi_pagina';
  }
}
```

### Patrón para Páginas con Layout Estándar

Para páginas que usan regiones de Drupal pero necesitan estilismo premium:

```twig
{# page.html.twig (layout estándar) #}
<div class="page-wrapper page-wrapper--with-sidebar">
  {{ page.header }}
  
  <div class="page-layout">
    <aside class="sidebar glass-card">
      {{ page.sidebar_first }}
    </aside>
    
    <main class="main-content">
      {{ page.content }}
    </main>
  </div>
  
  {{ page.footer }}
</div>
```

---

## 🧩 Componentes Reutilizables

### Jerarquía de Componentes

```
Página (page--*.html.twig)
├── Layout Wrapper (.page-wrapper)
├── Header Partial (_header.html.twig → dispatcher)
├── Secciones de Contenido
│   ├── Hero (_hero.html.twig)
│   ├── Features Grid (_features.html.twig)
│   ├── Stats Section (_stats.html.twig)
│   └── CTA Section (inline o partial)
└── Footer Partial (_footer.html.twig)
```

### Crear Nuevo Partial

```twig
{# _mi-seccion.html.twig #}
{#
/**
 * @file
 * Mi sección reutilizable.
 *
 * Variables:
 * - layout: default|compact|wide
 * - items: array de contenido
 */
#}
{% set ts = theme_settings|default({}) %}
{% set layout = ts.mi_seccion_layout|default('default') %}

<section class="mi-seccion mi-seccion--{{ layout }}">
  {# Contenido #}
</section>
```

---

## 📊 Configuración de Layouts en Theme Settings

### Añadir Nueva Opción de Layout

**1. En ecosistema_jaraba_theme.theme:**
```php
// En hook_form_system_theme_settings_alter()
$form['component_layouts']['mi_seccion_layout'] = [
  '#type' => 'radios',
  '#title' => t('Layout de Mi Sección'),
  '#options' => [
    'default' => t('Default'),
    'compact' => t('Compact'),
    'wide' => t('Wide'),
  ],
  '#default_value' => theme_get_setting('mi_seccion_layout') ?? 'default',
];
```

**2. En partial:**
```twig
{% set layout = ts.mi_seccion_layout|default('default') %}
<section class="mi-seccion mi-seccion--{{ layout }}">
```

**3. En SCSS:**
```scss
.mi-seccion {
  // Estilos base
  
  &--compact { padding: 2rem; }
  &--wide { max-width: 100%; }
}
```

---

## ✅ Checklist para Nuevas Páginas Premium

- [ ] ¿Usa design tokens (variables CSS `--ej-*`)?
- [ ] ¿Incluye partials reutilizables (header, footer)?
- [ ] ¿Tiene clase en body para scoping CSS?
- [ ] ¿Los componentes usan clases BEM con modificadores?
- [ ] ¿Los layouts son configurables desde theme settings?
- [ ] ¿El SCSS está en `scss/components/` e importado en `main.scss`?
- [ ] ¿El JS usa `Drupal.behaviors` para re-attach?

---

## 📁 Archivos de Referencia

| Archivo | Descripción |
|---------|-------------|
| [page--front.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page/page--front.html.twig) | Homepage premium de referencia |
| [_header.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header.html.twig) | Ejemplo de dispatcher |
| [_hero.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig) | Ejemplo de clases CSS |
| [main.scss](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/scss/main.scss) | Entry point SCSS |
