# Bloques Premium con Efectos Especiales - Matriz y Arquitectura

> **Fecha:** 2026-02-06  
> **Módulo:** `jaraba_page_builder`  
> **Contexto:** Auditoría completa de bloques premium para garantizar consistencia visual Canvas/Preview/Vista Pública

---

## Problema Resuelto

Los bloques premium con animaciones JavaScript (Animated Beam, Typewriter, Parallax, etc.) no funcionaban correctamente porque:

1. La librería `premium-blocks` no se cargaba en la vista pública (`/page/{id}`)
2. El canvas de GrapesJS (iframe) no tenía acceso a `Drupal.behaviors` ni `once()`

---

## Solución Implementada

### 1. Vista Pública

Añadida librería en `hook_page_attachments()`:

```php
// jaraba_page_builder.module
$attachments['#attached']['library'][] = 'jaraba_page_builder/premium-blocks';
```

### 2. Canvas GrapesJS

Inyección de mocks y script en `grapesjs-jaraba-canvas.js`:

```javascript
// Inyectar mocks de Drupal/once en iframe
drupalMock.textContent = `
    window.Drupal = window.Drupal || {};
    Drupal.behaviors = Drupal.behaviors || {};
    window.once = function(id, selector, context) { ... };
`;

// Cargar premium-blocks.js
premiumScript.src = '/modules/custom/jaraba_page_builder/js/premium-blocks.js';

// Re-ejecutar behaviors al insertar bloques
editor.on('component:add', () => { ... });
```

---

## Matriz de Bloques Premium

### Bloques con JavaScript (10 behaviors)

| Bloque | Selector JS | Template | Efecto |
|--------|------------|----------|--------|
| Typewriter | `[data-typewriter]` | typewriter-text.html.twig | Texto con efecto máquina de escribir |
| Animated Counter | `[data-counter-duration]` | animated-counter.html.twig | Contadores que incrementan al scroll |
| Scroll Reveal | `[data-reveal]` | scroll-reveal.html.twig | Fade-in al entrar en viewport |
| Comparison Slider | `.jaraba-comparison-item` | comparison-slider.html.twig | Slider antes/después |
| Parallax Hero | `[data-parallax]` | parallax-hero.html.twig | Fondo con movimiento parallax |
| Tilt 3D | `[data-tilt]` | floating-cards.html.twig | Tarjetas con inclinación 3D al hover |
| Spotlight Text | `[data-spotlight]` | spotlight-text.html.twig | Foco de luz sigue cursor |
| Sticky Scroll | `.jaraba-sticky-scroll` | sticky-scroll.html.twig | Media fija durante scroll de texto |
| Animated Beam | `[data-block-type="animated_beam"]` | animated-beam.html.twig | Líneas SVG animadas conectando iconos |
| Card Stack 3D | `.jaraba-card-stack` | card-stack-3d.html.twig | Carrusel de tarjetas apiladas 3D |

### Bloques CSS-Only (14 bloques)

| Bloque | Efecto CSS |
|--------|-----------|
| Card Flip 3D | `:hover` con `rotateY(180deg)` |
| Glassmorphism Cards | `backdrop-filter: blur()` |
| Gradient Cards | Gradientes animados |
| Hover Glow Cards | `box-shadow` dinámico |
| Text Gradient | `background-clip: text` |
| Marquee Logos | `@keyframes scroll` infinito |
| Orbit Animation | `transform-origin` + `@keyframes orbit` |
| Video Background Hero | HTML5 `<video autoplay muted loop>` |
| Particle Hero | Canvas/CSS particles |
| Animated Background | Gradientes en movimiento |
| Split Screen | Layout Flexbox 50/50 |
| Feature Comparison | Grid/Table con iconos |
| Testimonials 3D | CSS `perspective` |
| Spotlight Grid | CSS hover gradient (sin JS) |

---

## Arquitectura de Carga

```
┌─────────────────────────────────────────────────────────────┐
│  VISTA PÚBLICA (/page/{id})                                  │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  hook_page_attachments()                                │ │
│  │  └── jaraba_page_builder/premium-blocks                 │ │
│  │      ├── Drupal.behaviors (nativo)                      │ │
│  │      └── once() (nativo)                                │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  CANVAS EDITOR (GrapesJS iframe)                            │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  canvas:frame:load event                                │ │
│  │  ├── Mock: window.Drupal + behaviors {}                 │ │
│  │  ├── Mock: window.once()                                │ │
│  │  └── Load: premium-blocks.js                            │ │
│  │                                                          │ │
│  │  component:add event                                     │ │
│  │  └── Re-attach Drupal.behaviors                         │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| [jaraba_page_builder.module](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_page_builder/jaraba_page_builder.module) | Añadida librería `premium-blocks` en hook_page_attachments |
| [grapesjs-jaraba-canvas.js](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-canvas.js) | Inyección mocks Drupal/once + premium-blocks.js + component:add listener |

---

## Lección Clave

> **Los iframes de GrapesJS son contextos aislados**. No tienen acceso a `Drupal.behaviors` ni a `once()` del documento padre. Para bloques con JavaScript interactivo:
> 
> 1. Inyectar mocks mínimos de Drupal en el iframe
> 2. Cargar scripts dinámicamente después del mock
> 3. Re-ejecutar behaviors cuando se añaden componentes nuevos

---

## Referencias

- [2026-02-05_grapesjs_interactive_blocks_pattern.md](./2026-02-05_grapesjs_interactive_blocks_pattern.md)
- [premium-blocks.js](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_page_builder/js/premium-blocks.js) - 10 behaviors con efectos
- [templates/blocks/premium/](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_page_builder/templates/blocks/premium) - 24 templates Twig
