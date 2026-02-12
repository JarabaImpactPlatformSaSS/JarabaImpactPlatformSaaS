# Aprendizaje: Phase 4 — Remediacion SCSS Colores + Limpieza Emojis Unicode

**Fecha:** 2026-02-12
**Modulos afectados:** ecosistema_jaraba_theme, jaraba_agroconecta_core, jaraba_page_builder, ecosistema_jaraba_core, jaraba_journey, jaraba_customer_success, jaraba_credentials, jaraba_referral, jaraba_andalucia_ei, jaraba_paths
**Spec de referencia:** Plan Cierre Gaps Specs 20260126 — Fase 4 (P4-01, P4-02, P4-03)

---

## Resumen

Remediacion sistematica de deuda tecnica visual en toda la plataforma:
- **P4-01**: Eliminacion de ~686 colores hardcodeados SCSS, reemplazados por `var(--ej-color-*, $fallback)` + `color-mix()` para variantes
- **P4-02**: Limpieza de emojis Unicode en SCSS (`content:` properties) y Twig templates, reemplazados por escapes Unicode texto o `jaraba_icon()`
- **P4-03**: Documentacion de aprendizajes y actualizacion de directrices

**Archivos SCSS modificados**: 28+
**Templates Twig modificados**: 15+
**Colores hardcodeados eliminados**: ~686

---

## Reglas Nuevas

### P4-COLOR-001: Paleta completa de 7 colores Jaraba

La paleta oficial contiene 7 colores, NO solo 4. Todos los SCSS deben usar estos:

| Token CSS | Variable SCSS | Hex | Uso |
|-----------|---------------|-----|-----|
| `--ej-color-corporate` | `$ej-color-corporate` | #233D63 | Azul corporativo, identidad marca |
| `--ej-color-impulse` | `$ej-color-impulse` | #FF8C42 | Naranja empresas, energia, CTAs |
| `--ej-color-innovation` | `$ej-color-innovation` | #00A9A5 | Turquesa talento, innovacion |
| `--ej-color-earth` | `$ej-color-earth` | #556B2F | Verde tierra, agro, sostenibilidad |
| `--ej-color-success` | `$ej-color-success` | #10B981 | Verde exito, confirmaciones |
| `--ej-color-warning` | `$ej-color-warning` | #F59E0B | Ambar alertas, atencion |
| `--ej-color-danger` | `$ej-color-danger` | #EF4444 | Rojo error, critico, eliminacion |

**Error comun**: Usar colores Tailwind (#059669, #2563eb, #dc2626, #7c3aed) o Material Design (#1565C0, #2E7D32, #C62828) o Bootstrap (#dc3545, #c82333). SIEMPRE mapear a la paleta Jaraba.

### P4-COLOR-002: Patron `color-mix()` para variantes

Para generar variantes mas oscuras, claras o transparentes, usar `color-mix()` en vez de SCSS `darken()`/`lighten()` o `rgba()` hardcodeado:

```scss
// CORRECTO - variantes via color-mix()
background: color-mix(in srgb, var(--ej-color-success, #10B981) 10%, transparent);  // Fondo sutil
color: color-mix(in srgb, var(--ej-color-success, #10B981) 65%, black);             // Texto oscuro
border: 1px solid color-mix(in srgb, var(--ej-color-danger, #EF4444) 30%, transparent); // Borde sutil
background: linear-gradient(135deg, var(--ej-color-success, #10B981), color-mix(in srgb, var(--ej-color-success, #10B981) 75%, black)); // Gradiente

// INCORRECTO - hardcodear variantes
background: rgba(16, 185, 129, 0.1);    // NO - hex descompuesto
color: #065f46;                           // NO - variante hardcodeada
background: #dcfce7;                      // NO - tint hardcodeado
```

**Razon**: `color-mix()` funciona con CSS custom properties (a diferencia de SCSS `rgba()` que no acepta `var()`), y las variantes se adaptan automaticamente si el color base cambia via tematizacion.

### P4-COLOR-003: Fallbacks en `var()` deben coincidir con paleta

Cuando se usa `var(--ej-color-*, fallback)`, el fallback DEBE ser el hex exacto de la paleta:

```scss
// CORRECTO
color: var(--ej-color-success, #10B981);
color: var(--ej-color-danger, #EF4444);

// INCORRECTO - fallbacks de otra paleta
color: var(--ej-color-success, #059669);  // NO - Tailwind green
color: var(--ej-color-danger, #dc2626);   // NO - Tailwind red
color: var(--ej-color-primary, #f97316);  // NO - Tailwind orange
```

### P4-EMOJI-001: Emojis prohibidos en SCSS `content:`

Los SCSS `content:` pseudo-elements NO deben usar emojis Unicode (codepoints U+1F300+). Usar escapes Unicode de simbolos texto:

```scss
// CORRECTO - escapes Unicode texto
content: '\26A0';   // Warning sign (texto)
content: '\2726';   // Four-pointed star (texto)
content: '\2139';   // Information source (texto)
content: '\2744';   // Snowflake (texto)
content: '\1F512';  // Lock (no tiene variante texto, usar escape)

// INCORRECTO - emojis con renderizado color
content: '🔒';  // NO - emoji lock
content: '✨';  // NO - emoji sparkles
content: '💡';  // NO - emoji lightbulb
content: '🚨';  // NO - emoji rotating light
content: '⚠️';  // NO - emoji warning (con VS16)
```

**Excepcion**: Simbolos Unicode texto-nativos como `✓` (U+2713), `✗` (U+2717), `★` (U+2605), `→` (U+2192) son aceptables.

### P4-EMOJI-002: `jaraba_icon()` obligatorio en Twig

Los templates Twig DEBEN usar `jaraba_icon()` en lugar de emojis inline. El sistema tiene fallback automatico a emoji si el SVG no existe:

```twig
{# CORRECTO #}
<span class="portal__icon">{{ jaraba_icon('commerce', 'package', { size: '20px' }) }}</span>
<h2>{{ jaraba_icon('business', 'chart-bar', { size: '20px' }) }} {% trans %}Analytics{% endtrans %}</h2>

{# INCORRECTO #}
<span class="portal__icon">📦</span>
<h2>📊 {% trans %}Analytics{% endtrans %}</h2>
```

**Categorias disponibles**: `actions`, `ai`, `business`, `commerce`, `general`, `ui`, `verticals`

---

## Patrones de Mapeo: Colores externos a paleta Jaraba

### Tailwind CSS → Jaraba

| Tailwind | Hex | Jaraba equivalent |
|----------|-----|-------------------|
| green-600 | #059669 | `var(--ej-color-success, #10B981)` con `color-mix` |
| green-700 | #15803d | `color-mix(in srgb, var(--ej-color-success) 65%, black)` |
| blue-600 | #2563eb | `var(--ej-color-corporate, #233D63)` |
| purple-600 | #7c3aed | `var(--ej-color-innovation, #00A9A5)` |
| amber-600 | #d97706 | `var(--ej-color-warning, #F59E0B)` |
| red-600 | #dc2626 | `var(--ej-color-danger, #EF4444)` |
| red-800 | #991b1b | `color-mix(in srgb, var(--ej-color-danger) 65%, black)` |
| orange-500 | #f97316 | `var(--ej-color-impulse, #FF8C42)` |

### Material Design → Jaraba

| Material | Hex | Jaraba equivalent |
|----------|-----|-------------------|
| Blue 800 | #1565C0 | `var(--ej-color-corporate, #233D63)` |
| Green 800 | #2E7D32 | `var(--ej-color-success, #10B981)` |
| Red 800 | #C62828 | `var(--ej-color-danger, #EF4444)` |

### Bootstrap → Jaraba

| Bootstrap | Hex | Jaraba equivalent |
|-----------|-----|-------------------|
| danger | #dc3545 | `var(--ej-color-danger, #EF4444)` |
| danger-dark | #c82333 | `color-mix(in srgb, var(--ej-color-danger) 80%, black)` |
| danger-bg | #f8d7da | `color-mix(in srgb, var(--ej-color-danger) 12%, transparent)` |

---

## Problemas comunes encontrados

### 1. SCSS `rgba()` no funciona con `var()`

```scss
// PROBLEMA - SCSS compila rgba() en build-time, pero var() es runtime
$color: var(--ej-color-success, #10B981);
background: rgba($color, 0.1);  // ERROR en compilacion

// SOLUCION - usar color-mix()
background: color-mix(in srgb, var(--ej-color-success, #10B981) 10%, transparent);
```

### 2. Fallbacks incorrectos tras refactor

Al cambiar de Tailwind a Jaraba, los fallbacks internos de `var()` no se actualizaron:
```scss
// ANTES - correcto para Tailwind
color: var(--ej-color-success, #059669);

// DESPUES - MAL, fallback sigue siendo Tailwind
color: var(--ej-color-success, #059669);  // Deberia ser #10B981

// CORRECTO
color: var(--ej-color-success, #10B981);
```

Solucion: Usar `replace_all` para corregir en lote.

### 3. Emojis con Variation Selector 16

Los emojis `⚠️` y `❄️` incluyen un caracter invisible VS16 (U+FE0F) que fuerza renderizado emoji-color. Removiendo el VS16 se obtiene el glifo texto:
- `⚠️` (U+26A0 + U+FE0F) → `⚠` (U+26A0 solo) = texto
- `❄️` (U+2744 + U+FE0F) → `❄` (U+2744 solo) = texto

En SCSS, usar escape Unicode sin VS16: `content: '\26A0';`

---

## Archivos modificados (resumen)

### P4-01: Colores SCSS (28 archivos)

**Theme (ecosistema_jaraba_theme/scss/):**
- `_content-hub.scss` — status badges, social links
- `_site-builder.scss` — fallbacks impulse
- `_slide-panel.scss` — success/error gradients
- `_accessibility.scss` — focus colors
- `components/_features.scss` — badge colors, accent fallback
- `components/_autofirma.scss` — Material Design to Jaraba
- `components/_landing-sections.scss` — error/success fallbacks
- `components/_landing-page.scss` — error/success + rgba
- `components/_template-preview-premium.scss` — warning gradient
- `components/_section-editor.scss` — warning gradient
- `components/_commerce-product-geo.scss` — product variables

**AgroConecta (jaraba_agroconecta_core/scss/):**
- `_traceability.scss` — event icons, integrity badges, hero gradient
- `_analytics.scss` — KPI cards, rankings, alerts
- `_promotions.scss` — discount badges, coupon states, promo banner
- `_shipping.scss` — free-shipping success dark
- `_sales-chat.scss` — error token name
- `_partner-hub.scss` — accent purple to teal
- `_producer-portal.scss` — danger border
- `_customer-portal.scss` — danger buttons
- `_qr-dashboard.scss` — 11 wrong fallbacks

**Other modules:**
- `jaraba_journey/scss/main.scss` — health + state badges (24 colors)
- `jaraba_customer_success/scss/main.scss` — distribution, health, churn (30 colors)

### P4-02: Emojis (10 SCSS + 15 Twig)

**SCSS files (content: properties):**
- `_shipping.scss` — snowflake emoji → `\2744`
- `_canvas-editor.scss` — lock → `\1F512`, sparkles → `\2726`
- `_diagnostic.scss` — lightbulb → `\2139`
- `_rbac-matrix.scss` — warning → `\26A0`
- `_admin-premium.scss` — warning → `\26A0`, alert → `\26A0`
- `_business-tools.scss` — lightbulb → `\2139`
- `blocks/_features.scss` — sparkles → `\2726`
- `components/_features.scss` — sparkles → `\2726`

**Twig templates (emoji → jaraba_icon()):**
- `agro-admin-dashboard.html.twig` — 8 emojis
- `agro-analytics-dashboard.html.twig` — 6 emojis
- `agro-producer-dashboard.html.twig` — 8 emojis
- `agro-customer-dashboard.html.twig` — 6 emojis
- `agro-traceability-landing.html.twig` — 15+ emojis
- `agro-customer-order-detail.html.twig` — 3 emojis
- `agro-producer-order-detail.html.twig` — 4 emojis
- `agro-product-detail.html.twig` — 2 emojis
- `agro-search-page.html.twig` — 2 emojis
- `agro-category-page.html.twig` — 1 emoji
- `agro-notification-bell.html.twig` — 1 emoji
- `agro-reviews-widget.html.twig` — 1 emoji
- `andalucia-ei-dashboard.html.twig` — 7 emojis
- `referral-landing.html.twig` — 1 emoji
- `path-detail.html.twig` — 1 emoji

---

*Ultima actualizacion: 2026-02-12*
