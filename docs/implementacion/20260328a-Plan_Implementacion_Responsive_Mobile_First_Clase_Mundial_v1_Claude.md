# Plan de Implementacion: Responsive Mobile-First Clase Mundial

> **Fecha:** 2026-03-28
> **Ultima actualizacion:** 2026-03-28
> **Autor:** Claude Opus 4.6 (1M context)
> **Version:** 1.0
> **Estado:** Aprobado para implementacion
> **Referencia auditoria:** `docs/tecnicos/auditorias/20260328-Auditoria_Responsive_Mobile_First_SaaS_Metasitios_v1_Claude.md`
> **Alcance:** 18 archivos SCSS, 10+ templates Twig, 4 validators PHP, 42 archivos con nowrap
> **Estimacion total:** 12-16 horas de implementacion (3 sprints)
> **Dependencias:** npm (Dart Sass), PHP 8.4, Lando container
> **Reglas nuevas:** VIEWPORT-DVH-001, NOWRAP-OVERFLOW-001, IMG-DIMENSIONS-001, FIXED-STACKING-MOBILE-001, MOBILE-MENU-DVH-001

---

## Tabla de Contenidos

1. [Objetivo](#1-objetivo)
2. [Estado Actual (Pre-implementacion)](#2-estado-actual)
3. [Arquitectura de la Solucion](#3-arquitectura-de-la-solucion)
4. [Sprint 1: Viewport DVH — P0 Criticos](#4-sprint-1-viewport-dvh)
   - 4.1 [Regla VIEWPORT-DVH-001](#41-regla-viewport-dvh-001)
   - 4.2 [Patron de Aplicacion](#42-patron-de-aplicacion)
   - 4.3 [Tabla de Correspondencia — 41 Cambios](#43-tabla-de-correspondencia)
   - 4.4 [Mobile Menu Fix — MOBILE-MENU-DVH-001](#44-mobile-menu-fix)
   - 4.5 [Compilacion y Verificacion](#45-compilacion-y-verificacion)
5. [Sprint 2: Overflow + CLS + Stacking — P1 Altos](#5-sprint-2-overflow-cls-stacking)
   - 5.1 [NOWRAP-OVERFLOW-001: Proteccion Overflow](#51-nowrap-overflow-001)
   - 5.2 [IMG-DIMENSIONS-001: Dimensiones de Imagenes](#52-img-dimensions-001)
   - 5.3 [FIXED-STACKING-MOBILE-001: FABs Conscientes](#53-fixed-stacking-mobile-001)
   - 5.4 [Validator validate-viewport-dvh.php](#54-validator-viewport-dvh)
6. [Sprint 3: Mejoras P2/P3 + Validators](#6-sprint-3-mejoras-p2-p3)
   - 6.1 [Conversion px a rem](#61-conversion-px-a-rem)
   - 6.2 [Canvas Editor Mobile Screen](#62-canvas-editor-mobile-screen)
   - 6.3 [Validators Adicionales](#63-validators-adicionales)
   - 6.4 [touch-action en Scrollables](#64-touch-action-en-scrollables)
7. [Directrices de Cumplimiento](#7-directrices-de-cumplimiento)
8. [Safeguards Nuevos](#8-safeguards-nuevos)
9. [Testing Strategy](#9-testing-strategy)
10. [Deployment Notes](#10-deployment-notes)
11. [Setup Wizard + Daily Actions: Path a 10/10](#11-setup-wizard-daily-actions)
12. [Conversion Clase Mundial: Path a 10/10](#12-conversion-clase-mundial)
13. [Correspondencia Tecnica Completa](#13-correspondencia-tecnica)
14. [Glosario](#14-glosario)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Objetivo

Elevar la puntuacion mobile-first del frontend SaaS de **7.2/10 a 10/10 clase mundial**, resolviendo:

1. **41 usos de `100vh`** sin fallback `dvh` que causan desbordamiento en iOS Safari y Android Chrome
2. **~52 declaraciones `white-space: nowrap`** sin proteccion overflow que generan scroll horizontal
3. **Imagenes sin dimensiones** en templates criticos que degradan CLS (Core Web Vitals)
4. **Stacking de elementos fixed** que superponen FABs con bottom-nav en movil
5. **Gaps menores** de tipografia (px vs rem), canvas editor, y touch-action

### Principios de Implementacion

- **Progressive enhancement:** Cada fix agrega la linea `dvh` DESPUES de `vh`, browsers sin soporte la ignoran
- **Zero breaking changes:** Ningun fix elimina funcionalidad existente
- **Compilacion SCSS obligatoria:** Cada sprint termina con `npm run build` + verificacion de timestamps
- **Validator-backed:** Cada regla nueva tiene validator PHP para prevenir regresiones
- **Mobile-first verificado:** Cada fix se valida mentalmente en viewports 375px, 390px, 414px, 768px

---

## 2. Estado Actual

### 2.1 Lo Que Ya Existe

| Componente | Estado | Archivo de Referencia |
|------------|--------|----------------------|
| 6 breakpoints SCSS con mixin `respond-to()` | OK | `_variables.scss:153-184` |
| Bottom navigation con safe-area-inset | OK | `_mobile-components.scss:155-280` |
| Hamburger 44x44px con X animation | OK | `_mobile-menu.scss:59-124` |
| Slide panel con focus-trap y ARIA | OK | `_slide-panel.scss:1-697`, `slide-panel.js` |
| Responsive images parcial | OK | `_responsive-image.html.twig:1-59` |
| PWA con Service Worker y manifest | OK | `sw.js`, `manifest.webmanifest` |
| Touch swipe en carrusel 3D | OK | `jaraba-testimonials-3d.js:213-230` |
| Setup Wizard vertical en movil | OK | `_setup-wizard.scss:690-735` |
| Daily Actions grid responsive | OK | `_setup-wizard.scss:521-522, 729` |
| Meta-sitio body class cascade | OK | `.theme:2013` |
| DVH en checkout (unico uso actual) | OK | `_checkout.scss:188-189` |

### 2.2 Gaps a Cerrar

| Gap | Cantidad | Archivos | Sprint |
|-----|----------|----------|--------|
| 100vh sin dvh | 41 | 18 SCSS | Sprint 1 |
| Mobile menu height + padding | 1 | 1 SCSS | Sprint 1 |
| nowrap sin overflow | ~52 | 42 SCSS | Sprint 2 |
| Imagenes sin width/height | ~15 | 10 templates | Sprint 2 |
| FABs sin bottom-nav awareness | 3 | 3 SCSS | Sprint 2 |
| Validator dvh | 0 | 1 PHP nuevo | Sprint 2 |
| font-size px | 8 | 5 SCSS | Sprint 3 |
| Canvas mobile screen | 0 | 1 template nuevo | Sprint 3 |
| Validators overflow/img | 0 | 2 PHP nuevos | Sprint 3 |
| touch-action | 0 | 5 SCSS | Sprint 3 |

---

## 3. Arquitectura de la Solucion

### 3.1 Estrategia CSS: Progressive Enhancement

La estrategia clave es **progressive enhancement** para viewport units. Esto significa que:

1. Se MANTIENE la linea original `100vh` como fallback para browsers legacy
2. Se AGREGA una linea inmediatamente despues con `100dvh`
3. Los browsers modernos (93.5% soporte global) usan la segunda linea
4. Los browsers sin soporte ignoran `dvh` silenciosamente

```scss
// PATRON APROBADO — ya validado en _checkout.scss:188-189
.component {
    min-height: 100vh;   // Fallback browsers legacy
    min-height: 100dvh;  // Dynamic viewport height (excluye browser chrome)
}
```

### 3.2 Jerarquia de Viewport Units

| Unidad | Comportamiento | Uso Recomendado |
|--------|---------------|-----------------|
| `vh` | Incluye barra browser = desbordamiento en movil | **Fallback only** |
| `svh` | Viewport mas pequeno posible (con barras visibles) | Elementos que NUNCA deben desbordar |
| `lvh` | Viewport mas grande posible (barras ocultas) | Backgrounds decorativos full-screen |
| `dvh` | Se adapta dinamicamente al mostrar/ocultar barras | **USO PRINCIPAL para contenido** |

### 3.3 Flujo de Compilacion

```
Edit SCSS → npm run build (Lando) → Verificar timestamp CSS > SCSS → Commit
                                     ↓
                          validate-viewport-dvh.php (pre-commit)
                                     ↓
                          validate-scss-compile-freshness (pre-commit existente)
```

### 3.4 Compatibilidad con Sistema de Theming

Los cambios son **puramente CSS** — no afectan a:

- **TenantThemeConfig entity:** Ningun campo nuevo necesario
- **UnifiedThemeResolverService:** Resolucion de variables no cambia
- **CSS Custom Properties `--ej-*`:** No se agregan nuevas variables
- **ThemeTokenService:** No genera viewport units
- **SiteConfig / Meta-sitio:** No afectado

Los viewport units son decisiones de layout del SCSS del tema, no valores configurables desde la UI de Drupal. Esto es correcto: `dvh` es una mejora tecnica de rendering, no una preferencia visual del tenant.

### 3.5 Compatibilidad con Textos Traducibles

Los cambios de este plan son **100% CSS/SCSS** — no hay textos nuevos de interfaz.

La unica excepcion es la pantalla de redireccion mobile del canvas editor (Sprint 3, 6.2) que SI requerira textos traducibles con `{% trans %}`.

### 3.6 Cumplimiento Zero Region Pattern

Los templates Twig afectados (HIGH-02, imagenes) ya siguen el Zero Region Pattern existente. Los cambios solo agregan atributos `width`/`height` a etiquetas `<img>` existentes — no se introducen nuevos bloques ni regiones de Drupal.

---

## 4. Sprint 1: Viewport DVH — P0 Criticos

**Estimacion:** 3 horas | **Riesgo:** BAJO (progressive enhancement)

### 4.1 Regla VIEWPORT-DVH-001

```
VIEWPORT-DVH-001 (P0)
TODA declaracion `height: 100vh`, `min-height: 100vh`, `max-height: 100vh`
o variantes con `calc(100vh - ...)` en SCSS DEBE ir seguida de la linea
equivalente con `dvh`.

Patron:
  min-height: 100vh;
  min-height: 100dvh;

  height: calc(100vh - 80px);
  height: calc(100dvh - 80px);

Excepciones:
- Archivos en `scss/admin-*` (admin-only, no cara al usuario movil)
- Contextos con comentario `// VH-SAFE: [razon]`
- Propiedades `max-height` donde el overflow es deseado

Validacion: php scripts/validation/validate-viewport-dvh.php
Pre-commit: Incluir en lint-staged para archivos .scss
```

### 4.2 Patron de Aplicacion

Para cada ocurrencia de `100vh` se aplica el patron de la linea inmediatamente posterior:

**Caso 1: `min-height: 100vh`** (mayoria de casos)
```scss
// ANTES
.hero--fullscreen {
    min-height: 100vh;
}

// DESPUES
.hero--fullscreen {
    min-height: 100vh;
    min-height: 100dvh;
}
```

**Caso 2: `height: 100vh`** (mobile menu, agent dashboard)
```scss
// ANTES
.mobile-menu-nav {
    height: 100vh;
}

// DESPUES
.mobile-menu-nav {
    height: 100vh;
    height: 100dvh;
}
```

**Caso 3: `calc(100vh - Npx)`** (dashboards con header)
```scss
// ANTES
.dashboard-main {
    min-height: calc(100vh - 160px);
}

// DESPUES
.dashboard-main {
    min-height: calc(100vh - 160px);
    min-height: calc(100dvh - 160px);
}
```

**Caso 4: Ya tiene dvh** (solo `_checkout.scss`)
```scss
// NO TOCAR — ya correcto
.checkout-main {
    min-height: calc(100vh - 200px);
    min-height: calc(100dvh - 200px);
}
```

### 4.3 Tabla de Correspondencia — 41 Cambios

#### Grupo A: Componentes del Tema (scss/components/)

| # | Archivo | Linea | Codigo Original | Codigo Nuevo (agregar despues) | Contexto |
|---|---------|-------|----------------|-------------------------------|----------|
| A1 | `_hero.scss` | 15 | `min-height: 100vh;` | `min-height: 100dvh;` | Hero fullscreen |
| A2 | `_hero.scss` | 174 | `min-height: 100vh;` | `min-height: 100dvh;` | Hero video |
| A3 | `_hero.scss` | 180 | `min-height: 100vh;` | `min-height: 100dvh;` | Hero animated |
| A4 | `_hero-landing.scss` | 120 | `min-height: 100vh;` | `min-height: 100dvh;` | Hero landing premium |
| A5 | `_landing-page.scss` | 237 | `height: 100vh;` | `height: 100dvh;` | Landing hero |
| A6 | `_landing-page.scss` | 498 | `min-height: 100vh;` | `min-height: 100dvh;` | Landing section |
| A7 | `_landing-page.scss` | 1680 | `height: 100vh;` | `height: 100dvh;` | Landing fullscreen |
| A8 | `_landing-page.scss` | 3077 | `min-height: 100vh;` | `min-height: 100dvh;` | Landing fullscreen default |
| A9 | `_mobile-menu.scss` | 134 | `height: 100vh;` | `height: 100dvh;` | Mobile sidebar nav |
| A10 | `_page-premium.scss` | 20 | `min-height: 100vh;` | `min-height: 100dvh;` | Pagina premium |
| A11 | `_error-pages.scss` | 13 | `min-height: 100vh;` | `min-height: 100dvh;` | 403/404/500 pages |
| A12 | `_checkout.scss` | 188 | (ya tiene dvh en 189) | **NO TOCAR** | Checkout — ya correcto |
| A13 | `_grapesjs-canvas.scss` | 62 | `height: 100vh;` | `height: 100dvh;` | Canvas editor iframe |
| A14 | `_grapesjs-canvas.scss` | 82 | `height: calc(100vh - var(...));` | `height: calc(100dvh - var(...));` | Canvas toolbar |
| A15 | `_grapesjs-canvas.scss` | 1218 | `min-height: calc(100vh - 60px);` | `min-height: calc(100dvh - 60px);` | Canvas panel |
| A16 | `_template-preview-premium.scss` | 31 | `min-height: 100vh;` | `min-height: 100dvh;` | Template preview |
| A17 | `_template-preview-premium.scss` | 313 | `height: calc(100vh - 2rem);` | `height: calc(100dvh - 2rem);` | Preview container |
| A18 | `_analytics-dashboard.scss` | 20 | `min-height: 100vh;` | `min-height: 100dvh;` | Analytics componente |
| A19 | `_page-builder.scss` | 1275 | `min-height: 100vh;` | `min-height: 100dvh;` | Page builder |
| A20 | `_page-builder.scss` | 5014 | `min-height: 100vh;` | `min-height: 100dvh;` | Page builder preview |
| A21 | `_page-builder.scss` | 5398 | `min-height: 100vh;` | `min-height: 100dvh;` | Page builder section |
| A22 | `_page-builder.scss` | 6077 | `min-height: 100vh;` | `min-height: 100dvh;` | Page builder full |
| A23 | `_page-builder.scss` | 6128 | `min-height: 100vh;` | `min-height: 100dvh;` | Page builder alt |
| A24 | `_revision-diff.scss` | 95 | `min-height: 100vh;` | `min-height: 100dvh;` | Diff view |

#### Grupo B: SCSS del Tema (scss/)

| # | Archivo | Linea | Codigo Original | Codigo Nuevo (agregar despues) | Contexto |
|---|---------|-------|----------------|-------------------------------|----------|
| B1 | `_auth.scss` | 74 | `min-height: 100vh;` | `min-height: 100dvh;` | Login/registro |
| B2 | `_content-hub.scss` | 29 | `min-height: 100vh;` | `min-height: 100dvh;` | Content hub wrapper |
| B3 | `_content-hub.scss` | 81 | `min-height: 100vh;` | `min-height: 100dvh;` | Content hub main |
| B4 | `_content-hub.scss` | 95 | `min-height: calc(100vh - 160px);` | `min-height: calc(100dvh - 160px);` | Content hub body |
| B5 | `_content-hub.scss` | 420 | `min-height: 100vh;` | `min-height: 100dvh;` | Content section |
| B6 | `_content-hub.scss` | 2060 | `min-height: 100vh;` | `min-height: 100dvh;` | Content area |
| B7 | `_content-hub.scss` | 2478 | `min-height: 100vh;` | `min-height: 100dvh;` | Content panel |
| B8 | `_content-hub.scss` | 2883 | `min-height: 100vh;` | `min-height: 100dvh;` | Content view |
| B9 | `_content-hub.scss` | 3178 | `min-height: 100vh;` | `min-height: 100dvh;` | Content layout |
| B10 | `_content-hub.scss` | 3233 | `height: 100vh;` | `height: 100dvh;` | Content fullscreen |
| B11 | `_content-hub.scss` | 3729 | `min-height: 100vh;` | `min-height: 100dvh;` | Content wrapper |
| B12 | `_agent-dashboard.scss` | 19 | `height: 100vh;` | `height: 100dvh;` | AI agent dashboard |
| B13 | `_analytics-dashboard.scss` | 21 | `min-height: calc(100vh - 160px);` | `min-height: calc(100dvh - 160px);` | Analytics dashboard |
| B14 | `_page-builder-dashboard.scss` | 19 | `min-height: calc(100vh - 160px);` | `min-height: calc(100dvh - 160px);` | PB dashboard |
| B15 | `_user-pages.scss` | 34 | `min-height: calc(100vh - 160px);` | `min-height: calc(100dvh - 160px);` | User pages |

**Total cambios Sprint 1:** 40 lineas nuevas (41 ocurrencias - 1 ya existente en checkout)

### 4.4 Mobile Menu Fix — MOBILE-MENU-DVH-001

**Archivo:** `scss/components/_mobile-menu.scss`
**Lineas:** 129-145

#### Codigo Actual

```scss
.mobile-menu-nav {
    position: fixed;
    top: 0;
    right: -100%;
    width: min(280px, 85vw);
    height: 100vh;
    background: var(--ej-bg-surface, #fff);
    padding: 80px 24px 100px;
    box-shadow: -4px 0 30px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    overflow-y: auto;
    transition: right 0.3s ease;
}
```

#### Codigo Corregido

```scss
.mobile-menu-nav {
    position: fixed;
    top: 0;
    right: -100%;
    width: min(280px, 85vw);
    height: 100vh;
    height: 100dvh;
    background: var(--ej-bg-surface, #fff);
    padding: 72px 24px 24px;
    box-shadow: -4px 0 30px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    overflow-y: auto;
    transition: right 0.3s ease;

    // Landscape phones y pantallas muy cortas
    @media (max-height: 500px) {
        padding: 56px 16px 16px;
    }
}
```

#### Justificacion de Cambios

| Cambio | Antes | Despues | Razon |
|--------|-------|---------|-------|
| height | 100vh | 100vh + 100dvh | Excluir browser chrome en movil |
| padding-top | 80px | 72px | Suficiente para header (64px) + margen |
| padding-bottom | 100px | 24px | 100px era excesivo — acciones fijas con absolute, no padding |
| padding landscape | N/A | 56px top, 16px bottom | Landscape tiene ~375px — maximizar contenido |

### 4.5 Compilacion y Verificacion

Despues de aplicar todos los cambios del Sprint 1:

```bash
# Dentro del contenedor Lando
lando npm run build --prefix web/themes/custom/ecosistema_jaraba_theme

# Verificar timestamps
php scripts/validation/validate-scss-compile-freshness.php

# Verificar que no se introdujo dvh sin vh fallback
grep -rn "100dvh" web/themes/custom/ecosistema_jaraba_theme/scss/ | wc -l
# Debe ser 41 (40 nuevos + 1 existente en checkout)
```

---

## 5. Sprint 2: Overflow + CLS + Stacking — P1 Altos

**Estimacion:** 5-6 horas | **Riesgo:** BAJO-MEDIO

### 5.1 NOWRAP-OVERFLOW-001: Proteccion Overflow

#### Regla

```
NOWRAP-OVERFLOW-001
TODA declaracion `white-space: nowrap` DEBE ir acompanada de al menos UNA
de estas protecciones:

Opcion A (texto truncado):
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%; // o valor especifico

Opcion B (contenedor scrollable):
  // Padre del elemento con nowrap
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;

Opcion C (responsive fallback):
  white-space: nowrap;
  @media (max-width: $ej-breakpoint-xs) {
      white-space: normal;
      word-break: break-word;
  }

Excepciones documentadas:
- .sr-only (accesibilidad — clip:rect ya oculta overflow)
- Elementos dentro de contenedor con overflow-x: auto
- Comentario // NOWRAP-SAFE: [razon]

Validacion: php scripts/validation/validate-nowrap-overflow.php
```

#### Estrategia por Tipo de Componente

| Tipo | Proteccion | Aplicar en |
|------|-----------|-----------|
| Badges/chips (estado, tipo) | Opcion A (truncate) | `_employability-pages.scss`, `_subscription-card.scss` |
| Titulos en cards | Opcion A (truncate) | `_content-hub.scss`, `coordinador-hub.scss` |
| Precios/numeros | Opcion C (responsive wrap) | `_subscription-card.scss`, `_pricing-page.scss` |
| Navigation items | Opcion A (truncate) | `_avatar-nav.scss`, `_notification-panel.scss` |
| Botones/CTAs | Opcion C (responsive wrap) | `_landing-page.scss`, `_landing-sections.scss` |
| Timestamps | Opcion A (truncate) | `_setup-wizard.scss` |

#### Tabla de Cambios (Muestra — Top 20 mas criticos)

| # | Archivo | Linea | Contexto | Proteccion a Agregar |
|---|---------|-------|----------|---------------------|
| 1 | `_subscription-card.scss` | 209 | available-in label | A: overflow + ellipsis + max-width |
| 2 | `_subscription-card.scss` | 337 | price label | C: responsive wrap en xs |
| 3 | `_subscription-card.scss` | 409 | feature text | A: overflow + ellipsis |
| 4 | `_subscription-card.scss` | 466 | plan name | A: overflow + ellipsis |
| 5 | `_subscription-card.scss` | 480 | billing period | A: overflow + ellipsis |
| 6 | `_landing-page.scss` | 375 | CTA badge | C: responsive wrap |
| 7 | `_landing-page.scss` | 617 | section badge | C: responsive wrap |
| 8 | `_landing-page.scss` | 1574 | stat label | A: overflow + ellipsis |
| 9 | `_landing-page.scss` | 1592 | stat value | A: overflow + ellipsis |
| 10 | `_landing-page.scss` | 1626 | partner name | A: overflow + ellipsis |
| 11 | `_employability-pages.scss` | 385 | status badge | A: overflow + ellipsis |
| 12 | `_employability-pages.scss` | 509 | CTA button | C: responsive wrap |
| 13 | `_employability-pages.scss` | 530 | company name | A: overflow + ellipsis |
| 14 | `coordinador-hub.scss` | 855 | metric label | A: overflow + ellipsis |
| 15 | `coordinador-hub.scss` | 1093 | tab text | A: overflow + ellipsis |
| 16 | `coordinador-hub.scss` | 1585 | participant name | A: overflow + ellipsis |
| 17 | `coordinador-hub.scss` | 1746 | status badge | A: overflow + ellipsis |
| 18 | `_notification-panel.scss` | (3 inst) | notification text | A: overflow + ellipsis |
| 19 | `_setup-wizard.scss` | 637 | step timestamp | A: overflow + ellipsis |
| 20 | `_checkout.scss` | (3 inst) | form labels | A: overflow + ellipsis |

### 5.2 IMG-DIMENSIONS-001: Dimensiones de Imagenes

#### Regla

```
IMG-DIMENSIONS-001
TODA etiqueta <img> en templates Twig DEBE incluir atributos width y height.

Patron para imagenes estaticas:
  <img src="{{ url }}" width="120" height="40" alt="..." loading="lazy">

Patron para imagenes dinamicas (usar parcial existente):
  {% include '@ecosistema_jaraba_theme/partials/_responsive-image.html.twig' with {
      src: image_url,
      alt: image_alt,
      width: image_width,
      height: image_height,
      sizes: '(max-width: 768px) 100vw, 50vw',
      lazy: true,
  } only %}

CSS companion (agregar en _layout.scss si no existe):
  img {
      max-width: 100%;
      height: auto;
  }

Excepciones:
- SVG inline (no usan <img>)
- Imagenes con clase .responsive-img + aspect-ratio CSS
- Imagenes generadas por Drupal image styles (ya incluyen width/height)
```

#### Tabla de Cambios

| # | Template | Linea | Tipo | width | height | Accion |
|---|----------|-------|------|-------|--------|--------|
| 1 | `header--classic.html.twig` | 16 | Logo | 120 | 40 | Agregar atributos |
| 2 | `page--auth.html.twig` | 48 | Logo | 160 | 55 | Agregar atributos |
| 3 | `hero--animated.html.twig` | 68 | Hero img | Dinamico | Dinamico | Usar parcial responsive |
| 4 | `hero--animated.html.twig` | 77 | Hero bg | Dinamico | Dinamico | Usar parcial responsive |
| 5 | `card--course.html.twig` | 13 | Card img | 400 | 225 | Agregar atributos |
| 6 | `_footer.html.twig` | 144 | Logo | 100 | 34 | Agregar atributos + loading="lazy" |
| 7 | `image-gallery.html.twig` | 59 | Gallery | Dinamico | Dinamico | Usar parcial responsive |
| 8 | `image-gallery.html.twig` | 93 | Gallery | Dinamico | Dinamico | Usar parcial responsive |
| 9 | `product-showcase.html.twig` | 40 | Product | 300 | 300 | Agregar atributos |
| 10 | `split-screen.html.twig` | 35 | Split | Dinamico | Dinamico | Usar parcial responsive |

### 5.3 FIXED-STACKING-MOBILE-001: FABs Conscientes

#### Regla

```
FIXED-STACKING-MOBILE-001
Elementos con `position: fixed` en la zona inferior de la pantalla DEBEN
ser conscientes de la bottom-nav (.has-bottom-nav).

Patron para FABs:
  .whatsapp-fab,
  .copilot-fab,
  .back-to-top {
      bottom: var(--ej-spacing-lg, 2rem);

      .has-bottom-nav & {
          bottom: calc(56px + env(safe-area-inset-bottom, 0) + var(--ej-spacing-md, 1rem));
      }
  }

Exclusion mutua en movil:
  @media (max-width: $ej-breakpoint-md) {
      // Solo 1 FAB visible. WhatsApp tiene prioridad sobre Copilot.
      .has-whatsapp-fab .copilot-fab {
          display: none;
      }
  }
```

#### Archivos a Modificar

| # | Archivo | Cambio | Impacto |
|---|---------|--------|---------|
| 1 | `_whatsapp-fab.scss` | Agregar `.has-bottom-nav &` override | FAB sube 72px |
| 2 | `_back-to-top.scss` | Agregar `.has-bottom-nav &` override | Boton sube 72px |
| 3 | `_mobile-components.scss` o tema | Agregar regla exclusion mutua FABs | 1 FAB en movil |
| 4 | `ecosistema_jaraba_theme.theme` | Agregar body class `.has-whatsapp-fab` cuando WAB activo | Para CSS targeting |

### 5.4 Validator validate-viewport-dvh.php

#### Especificacion

```php
<?php
/**
 * @file
 * Validator: VIEWPORT-DVH-001 — Detecta 100vh sin dvh fallback.
 *
 * Escanea archivos SCSS del tema y modulos custom buscando declaraciones
 * de 100vh que no tengan la linea dvh correspondiente inmediatamente despues.
 *
 * Uso: php scripts/validation/validate-viewport-dvh.php
 * Pre-commit: Si, para archivos .scss modificados
 * CI: Si, como run_check
 */
```

**Logica del validator:**

1. Glob `web/themes/custom/ecosistema_jaraba_theme/scss/**/*.scss`
2. Para cada archivo, buscar lineas con `100vh` (regex: `/\b100vh\b/`)
3. Verificar que la linea siguiente contiene `100dvh` (o `dvh`)
4. Excepciones: lineas con `// VH-SAFE:`, archivos admin-*, `max-height`
5. Reportar violaciones con archivo:linea
6. Exit code 1 si hay violaciones

---

## 6. Sprint 3: Mejoras P2/P3 + Validators

**Estimacion:** 4-5 horas | **Riesgo:** BAJO

### 6.1 Conversion px a rem

| Archivo | Linea | Actual | Nuevo |
|---------|-------|--------|-------|
| `admin-settings.scss` | 311 | `font-size: 14px` | `font-size: 0.875rem` |
| `admin-settings.scss` | 420 | `font-size: 12px` | `font-size: 0.75rem` |
| `coordinador-hub.scss` | 718 | `font-size: 11px` | `font-size: 0.6875rem` |
| `coordinador-hub.scss` | 731 | `font-size: 14px` | `font-size: 0.875rem` |
| `_landing-page.scss` | 2122 | `font-size: 24px` | `font-size: 1.5rem` |
| `_landing-page.scss` | 2130 | `font-size: 24px` | `font-size: 1.5rem` |
| `_landing-page.scss` | 2186 | `font-size: 24px` | `font-size: 1.5rem` |
| `_landing-page.scss` | 2195 | `font-size: 16px` | `font-size: 1rem` |

### 6.2 Canvas Editor Mobile Screen

Crear template parcial para redireccion en viewport < 768px:

**Archivo:** `templates/partials/_canvas-editor-mobile-redirect.html.twig`

```twig
{# Pantalla de redireccion para usuarios movil en canvas editor #}
<div class="canvas-editor-mobile-redirect">
    <div class="canvas-editor-mobile-redirect__icon">
        {{ jaraba_icon('interface', 'monitor', { variant: 'duotone', color: 'azul-corporativo', size: '64px' }) }}
    </div>
    <h2 class="canvas-editor-mobile-redirect__title">
        {% trans %}El editor de paginas funciona mejor en pantallas grandes{% endtrans %}
    </h2>
    <p class="canvas-editor-mobile-redirect__text">
        {% trans %}Para la mejor experiencia de edicion, accede desde un ordenador o tablet en horizontal.{% endtrans %}
    </p>
    <a href="{{ path('<front>') }}" class="canvas-editor-mobile-redirect__cta btn btn--primary">
        {% trans %}Volver al inicio{% endtrans %}
    </a>
</div>
```

**SCSS:** Incluir en `_grapesjs-canvas.scss`:
```scss
.canvas-editor-mobile-redirect {
    display: none;
    @media (max-width: $ej-breakpoint-md) {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        min-height: 100dvh;
        padding: var(--ej-spacing-xl, 2rem);
        text-align: center;
        gap: var(--ej-spacing-lg, 1.5rem);
    }
}
```

### 6.3 Validators Adicionales

#### validate-nowrap-overflow.php

Escanea archivos SCSS buscando `white-space: nowrap` sin proteccion overflow en las 5 lineas siguientes.

#### validate-img-dimensions.php

Escanea templates Twig buscando `<img` sin atributos `width` y `height`.

### 6.4 touch-action en Scrollables

Agregar `touch-action: pan-y` en componentes con scroll horizontal:

| Archivo | Selector | touch-action |
|---------|----------|-------------|
| `_pricing-page.scss` | `.pricing-comparison__mobile` | `pan-y` |
| `_content-hub.scss` | `.tabs-scrollable` | `pan-x` |
| `coordinador-hub.scss` | `.kanban-board` | `pan-x` |
| `_mobile-components.scss` | `.swipe-card` | Ya tiene (`pan-y`) |

---

## 7. Directrices de Cumplimiento

### 7.1 Correspondencia con Directrices del Proyecto

| Directriz | Cumplimiento en este Plan |
|-----------|--------------------------|
| **CSS-VAR-ALL-COLORS-001** | No afectado — cambios son viewport units, no colores |
| **SCSS-COMPILE-VERIFY-001** | Cada sprint termina con `npm run build` + timestamp check |
| **SCSS-001** (`@use` scope) | No se agregan nuevos parciales — ediciones en archivos existentes |
| **ICON-CONVENTION-001** | Canvas mobile redirect usa `jaraba_icon()` con duotone/azul-corporativo |
| **ICON-DUOTONE-001** | Default duotone en nuevo parcial |
| **ICON-COLOR-001** | Solo colores de paleta Jaraba |
| **Zero Region Pattern** | Templates afectados ya lo cumplen |
| **Textos traducibles** | Nuevo parcial usa `{% trans %}` |
| **Variables inyectables UI** | No se agregan variables de tema — dvh es layout, no visual |
| **Dart Sass moderno** | Todos los archivos usan `@use`, no `@import` |
| **CONTROLLER-READONLY-001** | No se modifican controllers |
| **PREMIUM-FORMS-PATTERN-001** | No se modifican forms |
| **TENANT-001** | No afecta queries |
| **SECRET-MGMT-001** | No se manejan secrets |
| **DOC-GUARD-001** | Documentos nuevos (no se editan master docs) |
| **COMMIT-SCOPE-001** | Commits separados: docs:, feat:, fix: |

### 7.2 Correspondencia con Flujo de Trabajo

| Paso del Flujo | Aplicacion |
|----------------|-----------|
| Leer directrices antes de codigo | Revisado CLAUDE.md, directrices, theming master |
| Verificar que no hay duplicidades | Confirmado: no existe validator dvh ni documento mobile |
| Compilar SCSS tras cada cambio | `lando npm run build` en cada sprint |
| Pre-commit hooks | Nuevos validators se integran en lint-staged |
| RUNTIME-VERIFY-001 | CSS compilado, templates accesibles, selectors match |

### 7.3 Correspondencia con Theming Master

| Seccion del Master | Relacion |
|--------------------|----------|
| Jerarquia 5 Capas | No afectada — dvh es layer CSS, no token |
| Patron de Compilacion | Se sigue exactamente |
| CSS Custom Properties | No se agregan nuevas |
| TenantThemeConfig | No se agregan campos |
| Mixins (respond-to) | Se usa el mixin existente para landscape override |
| Directivas nombradas | 5 reglas nuevas propuestas |

---

## 8. Safeguards Nuevos

### 8.1 Validators de Produccion

| Validator | Tipo | Integracion |
|-----------|------|------------|
| `validate-viewport-dvh.php` | `run_check` + pre-commit | lint-staged para .scss |
| `validate-nowrap-overflow.php` | `run_check` | validate-all.sh |
| `validate-img-dimensions.php` | `warn` | validate-all.sh |
| `validate-mobile-zindex-stack.php` | `warn` | validate-all.sh (inventario) |

### 8.2 Pre-commit Integration

Agregar en `.lintstagedrc.json` (o equivalente):

```json
{
    "web/themes/custom/ecosistema_jaraba_theme/scss/**/*.scss": [
        "php scripts/validation/validate-viewport-dvh.php --files"
    ]
}
```

### 8.3 CI Gate

Agregar step en `ci.yml`:

```yaml
- name: Validate mobile responsive patterns
  run: |
    php scripts/validation/validate-viewport-dvh.php
    php scripts/validation/validate-nowrap-overflow.php
```

### 8.4 Salvaguardas para el Futuro

| Salvaguarda | Proposito | Urgencia |
|-------------|-----------|----------|
| **validate-viewport-dvh.php** | Prevenir nuevos 100vh sin dvh | INMEDIATA — pre-commit |
| **validate-nowrap-overflow.php** | Prevenir nowrap sin protection | ALTA — CI gate |
| **validate-img-dimensions.php** | Prevenir CLS en nuevas imagenes | MEDIA — warn |
| **BackstopJS mobile viewport** | Visual regression en 375px | FUTURA — 3 viewports ya configurados |
| **Lighthouse CI mobile** | Scores automaticos movil | FUTURA — integrar en CI |
| **Core Web Vitals monitoring** | CLS/LCP/INP real users | FUTURA — via Google Search Console |

---

## 9. Testing Strategy

### 9.1 Validacion Post-Implementacion

Despues de cada sprint, verificar mentalmente en estos viewports:

| Viewport | Dispositivo | Verificar |
|----------|------------|-----------|
| 375x667 | iPhone SE | Hero visible completo, menu scrollable |
| 390x844 | iPhone 14 | CTA visible sin scroll en hero |
| 414x896 | iPhone 11 Pro Max | FABs no tapan bottom-nav |
| 360x800 | Samsung Galaxy S21 | Dashboard con header visible |
| 768x1024 | iPad | Tablet layout correcto |
| 375x375 | iPhone SE landscape | Menu mobile funcional |

### 9.2 BackstopJS (Existente)

Los 3 viewports ya configurados (desktop, tablet, mobile) cubriran regresiones visuales.

### 9.3 Validators Automaticos

```bash
# Post-implementacion
php scripts/validation/validate-viewport-dvh.php
php scripts/validation/validate-nowrap-overflow.php
php scripts/validation/validate-img-dimensions.php
bash scripts/validation/validate-all.sh --fast
```

---

## 10. Deployment Notes

### 10.1 Orden de Deploy

1. Aplicar cambios SCSS (Sprint 1, 2, 3)
2. Compilar CSS: `npm run build` dentro del contenedor Lando
3. Verificar timestamps: `php scripts/validation/validate-scss-compile-freshness.php`
4. Ejecutar validators nuevos
5. Commit: `feat(mobile): VIEWPORT-DVH-001 — add dvh fallback to 40 viewport declarations`
6. Deploy a produccion: `deploy.yml` ya incluye asset compilation

### 10.2 Rollback

El rollback es trivial: revertir el commit. Los cambios son aditivos (progressive enhancement) y no eliminan funcionalidad.

### 10.3 Cache Invalidation

- **OPcache:** FPM reload en deploy (ya configurado)
- **CSS aggregation:** Drupal invalida automaticamente al cambiar archivos CSS
- **CDN/Nginx:** `Vary: Host` ya configurado (VARY-HOST-001)
- **Redis:** Cache tags de theme se invalidan en deploy (drush cr)

---

## 11. Setup Wizard + Daily Actions: Path a 10/10

### Estado Actual: 8.5/10

### Gaps Identificados

| Gap | Impacto | Fix |
|-----|---------|-----|
| `white-space: nowrap` sin overflow en timestamp (linea 637) | Texto cortado en movil | Sprint 2 (NOWRAP-OVERFLOW-001) |
| Ring SVG 44px en mobile (vs 52px desktop) | Visualmente pequeno | **Deciscion del usuario**: subir a 48px? |
| Daily Actions primary card sin diferenciacion movil | Todas las cards iguales en 1-col | **Deciscion del usuario**: agregar borde destacado? |
| Stepper connectors en mobile | Linea vertical funcional pero fina (2px) | Aceptable |

### Acciones para 10/10

1. **Fix timestamp overflow** (incluido en Sprint 2)
2. **Ring SVG mobile size** — pregunta al usuario: el anillo de progreso del Setup Wizard en movil es 44px (vs 52px en desktop). 44px cumple touch target minimo pero es el elemento visual mas importante del wizard. Podriamos subirlo a 48px para mayor impacto visual sin sacrificar espacio. Trade-off: 4px extra de ancho que en un mobile de 375px es insignificante.
3. **Primary daily action card** — pregunta al usuario: en desktop, la accion primaria ocupa 2 columnas. En mobile (1 columna), todas las acciones se ven iguales. Podriamos agregar un borde izquierdo de 3px con `var(--ej-color-primary)` o un fondo sutil diferenciado para la accion primaria en movil.

---

## 12. Conversion Clase Mundial: Path a 10/10

### 12.1 Estado Actual Conversion Movil: 7.5/10

### 12.2 Checklist 10/10

| # | Criterio | Estado | Accion |
|---|----------|--------|--------|
| 1 | Hero CTA visible sin scroll (mobile) | 7/10 | Sprint 1 (dvh) resuelve |
| 2 | Form above the fold | 8/10 | Ya funciona con lead magnet |
| 3 | Load time <2s LTE | 7/10 | CSS 855KB (180KB gzip) — aceptable |
| 4 | CLS < 0.1 | 6/10 | Sprint 2 (img dimensions) resuelve |
| 5 | Touch targets 48px+ | 9/10 | Algunos 44px — minimo AA |
| 6 | Sticky CTA mobile | 8/10 | landing-sticky-cta.js funciona |
| 7 | Pricing cards responsive | 8/10 | Stack vertical OK |
| 8 | Exit-intent mobile | 8/10 | Existe en certificacion |
| 9 | Social proof visible | 8/10 | Trust strip + reviews |
| 10 | One-click actions | 7/10 | Sprint 2 (stacking) resuelve |
| 11 | Microinteracciones premium | 8/10 | Shimmer, particles, pulso |
| 12 | Scroll depth tracking | 8/10 | metasite-tracking.js |
| 13 | WhatsApp CTA visible | 7/10 | Sprint 2 (stacking) resuelve |
| 14 | Bottom-nav funcional | 9/10 | Solo SaaS auth users |
| 15 | Progressive profiling | 8/10 | Setup wizard + popups |

### 12.3 Score Proyectado Post-Implementacion

Con los 3 sprints completados:

| Area | Antes | Despues | Delta |
|------|-------|---------|-------|
| Viewport units | 3/10 | 9.5/10 | +6.5 |
| Overflow horizontal | 5/10 | 9/10 | +4 |
| CLS | 5.5/10 | 8.5/10 | +3 |
| Fixed stacking | 6/10 | 9/10 | +3 |
| **Global** | **7.2/10** | **9.3/10** | **+2.1** |

Para el salto final a 10/10 se necesitaria:
- Lighthouse CI automatizado en pipeline
- Real User Monitoring (RUM) con Core Web Vitals
- A/B testing de posicion de CTAs en movil

---

## 13. Correspondencia Tecnica Completa

### 13.1 Reglas Existentes Afectadas

| Regla | Relacion con este Plan |
|-------|----------------------|
| CSS-VAR-ALL-COLORS-001 | No afectada |
| SCSS-COMPILE-VERIFY-001 | Compilacion obligatoria post-edicion |
| SCSS-COLORMIX-001 | No afectada |
| SCSS-COMPILETIME-001 | No afectada |
| ICON-CONVENTION-001 | Canvas mobile redirect usa jaraba_icon() |
| ZERO-REGION-001 | Templates modificados ya cumplen |
| SLIDE-PANEL-RENDER-001 | No afectada |
| TWIG-INCLUDE-ONLY-001 | Nuevo parcial usa `only` |
| WCAG AA | Touch targets, focus, ARIA mantenidos |

### 13.2 Reglas Nuevas Propuestas (5)

| Regla | Tipo | Severidad | Validator |
|-------|------|-----------|-----------|
| VIEWPORT-DVH-001 | P0 | CRITICA | validate-viewport-dvh.php |
| NOWRAP-OVERFLOW-001 | P1 | ALTA | validate-nowrap-overflow.php |
| IMG-DIMENSIONS-001 | P1 | ALTA | validate-img-dimensions.php |
| FIXED-STACKING-MOBILE-001 | P1 | ALTA | validate-mobile-zindex-stack.php |
| MOBILE-MENU-DVH-001 | P0 | CRITICA | (cubierto por VIEWPORT-DVH-001) |

### 13.3 Archivos Totales a Modificar

| Tipo | Cantidad | Sprint |
|------|----------|--------|
| SCSS tema (viewport dvh) | 17 | Sprint 1 |
| SCSS tema (mobile menu) | 1 | Sprint 1 |
| SCSS tema (nowrap overflow) | ~42 | Sprint 2 |
| SCSS tema (FAB stacking) | 3 | Sprint 2 |
| Templates Twig (img dimensions) | ~10 | Sprint 2 |
| PHP validators (nuevos) | 4 | Sprint 2-3 |
| SCSS tema (px a rem) | 5 | Sprint 3 |
| Template Twig (canvas mobile) | 1 | Sprint 3 |
| SCSS tema (touch-action) | 4 | Sprint 3 |
| **TOTAL** | **~87 archivos** | **3 sprints** |

---

## 14. Glosario

| Sigla | Significado |
|-------|------------|
| CLS | Cumulative Layout Shift — metrica Core Web Vitals que mide inestabilidad visual durante la carga |
| CTA | Call To Action — boton o enlace de conversion principal |
| dvh | Dynamic Viewport Height — unidad CSS moderna que excluye la barra de navegacion del browser movil |
| FAB | Floating Action Button — boton de accion flotante posicionado fijo en la pantalla |
| INP | Interaction to Next Paint — metrica Core Web Vitals de responsividad a interaccion |
| LCP | Largest Contentful Paint — metrica Core Web Vitals de velocidad de carga percibida |
| PWA | Progressive Web Application — aplicacion web con capacidades nativas (offline, push) |
| RAF | requestAnimationFrame — API del navegador para animaciones eficientes sincronizadas con el refresh rate |
| RUM | Real User Monitoring — medicion de rendimiento con datos de usuarios reales |
| SCSS | Sassy CSS — preprocesador CSS compilado con Dart Sass en este proyecto |
| SR | Screen Reader — software lector de pantalla para accesibilidad |
| SVG | Scalable Vector Graphics — formato de imagen vectorial usado para iconos |
| svh | Small Viewport Height — unidad CSS que usa el viewport minimo (con todas las barras visibles) |
| SW | Service Worker — script en background del navegador para caching y funcionalidad offline |
| WCAG | Web Content Accessibility Guidelines — estandar internacional de accesibilidad web (nivel AA objetivo) |

---

## 15. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 2026-03-28 | Plan inicial. 3 sprints, 5 reglas nuevas, 4 validators, ~87 archivos |
