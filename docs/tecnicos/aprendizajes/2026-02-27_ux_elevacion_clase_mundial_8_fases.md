# Aprendizaje #150 — Elevacion UX a Clase Mundial: 8 Fases + Auditoria Directrices

**Fecha:** 2026-02-27
**Tipo:** Implementacion + Patron + Auditoria
**Alcance:** Theme + Core Module + Notifications Module
**Directrices base:** v100.0.0 | **Flujo:** v53.0.0

---

## Contexto

Tras auditoria comparativa contra Stripe/HubSpot/Notion, se identificaron 8 brechas criticas en UX que impedian nivel clase mundial. Se implementaron en orden secuencial las 8 fases.

## Fases Implementadas

### FASE 1: Skeleton Screens
- **Ficheros:** `_skeleton.html.twig` + `_skeleton.scss`
- **Variantes:** card, stat, table, list
- **Patron:** `{% include '@ecosistema_jaraba_theme/partials/_skeleton.html.twig' with { variant: 'card', count: 3 } %}`
- **63 clases BEM** nuevas: `.ej-skeleton`, `.ej-skeleton__line--80`, etc.

### FASE 2: Empty States con CTAs
- **Ficheros:** `_empty-state.html.twig` + `_empty-state.scss`
- **Acepta:** icon, icon_name, title, description, cta_label, cta_url, secondary_label, secondary_url
- **Nota:** `.ej-btn--ghost` no existe — usar `.ej-btn--outline`

### FASE 3: Micro-interacciones CSS
- **Cards:** Hover lift + active press en `.card`
- **Buttons:** `:active:not(:disabled) { transform: scale(0.97); }`
- **Modals:** `@keyframes ej-fadeInScale` en `_slide-panel.scss`
- **No nuevo fichero** — modificaciones a existentes

### FASE 4: Error Recovery UX
- **`fetch-retry.js`:** `Drupal.jarabaFetch(url, options, maxRetries)` con retry exponencial
- **`_toasts.scss`:** 4 variantes (error, warning, success, info)
- **CSRF cacheado** como Promise reutilizable
- **Libreria global** en `ecosistema_jaraba_theme.info.yml`

### FASE 5: Bottom Navigation Mobile
- **CSS ya existia** en `_mobile-components.scss:150-282`
- **Creado solo:** `_bottom-nav.html.twig` + `bottom-nav.js`
- **Body class:** `has-bottom-nav` via `hook_preprocess_html()`
- **FAB central** dispara `CustomEvent('jaraba:quick-create')`

### FASE 6: Centro de Notificaciones
- **Modulo nuevo:** `jaraba_notifications/`
- **Entidad:** `Notification` (ContentEntity con EntityOwnerTrait + tenant_id)
- **5 endpoints REST:** list, count, mark-read, mark-all-read, dismiss
- **Panel desplegable:** `_notification-panel.html.twig` + `.scss` + JS con glassmorphism
- **Tipos:** system, social, workflow, ai

### FASE 7: Busqueda Global Mejorada
- **Mejorado:** `EntitySearchCommandProvider.php` — de 1 entity type a 3 (content_article, page_content, user)
- **Relevancia:** exact=95, starts_with=75, contains=55
- **Eliminado:** `GlobalSearchService.php` (redundante con CommandBar providers)
- **command-bar.js:** Seccion "Recientes" via localStorage (max 5 items), agrupacion por categoria

### FASE 8: Onboarding Quick-Start Overlay
- **Ficheros:** `_quick-start-overlay.html.twig` + `_quick-start.scss` + `quick-start.js`
- **3 acciones** contextualizadas por vertical (emprendimiento, empleabilidad, agroconecta, default)
- **Dismiss** via localStorage (`jaraba_quick_start_dismissed`)
- **Focus trap** + Esc close + focus restoration

## Patrones Clave Aprendidos

### 1. Service Collector vs Service Standalone
El CommandBar ya usaba patron service collector (`CommandRegistryService` + `CommandProviderInterface` con tag `jaraba.command_provider`). Crear un `GlobalSearchService` separado duplicaba funcionalidad. **Regla:** Antes de crear un servicio nuevo, verificar si existe un patrón collector que lo absorba.

### 2. CSS Existente Reutilizable
El bottom nav tenia 127 lineas de CSS listas sin template HTML. **Regla:** Siempre auditar SCSS existente antes de crear nuevo — puede que solo falte el Twig.

### 3. Body Class via hook_preprocess_html
`$variables['attributes']->addClass()` NO funciona para body. Usar `$variables['attributes']['class'][] = 'valor'`.

### 4. localStorage para First-Visit Flags
Para features one-shot (quick-start, onboarding hints), `localStorage` es mas simple que State API y no requiere endpoint backend.

### 5. Compilacion SCSS con Dart Sass
Comando canonical: `lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/main.css --style=compressed --no-source-map"`

## Metricas Finales

| Metrica | Antes | Despues |
|---------|-------|---------|
| Skeleton screens | 0% | Componente reutilizable (4 variantes) |
| Empty states | 0% | Componente reutilizable con CTA contextual |
| Micro-interacciones | ~30% | ~85% (cards, buttons, modals) |
| Error recovery | ~40% | ~90% (retry + toast) |
| Bottom nav mobile | CSS 100%, template 0% | 100% activado |
| Notificaciones | Bell icon, panel 0% | Modulo completo con 5 endpoints |
| Busqueda global | 1 entity type | 3 entity types + recientes |
| Onboarding quick-start | 0% | 100% con 3 verticales |
| Clases BEM nuevas | 0 | 63 |

## Ficheros Creados (16)

| Fichero | Fase |
|---------|------|
| `templates/partials/_skeleton.html.twig` | 1 |
| `scss/components/_skeleton.scss` | 1 |
| `templates/partials/_empty-state.html.twig` | 2 |
| `scss/components/_empty-state.scss` | 2 |
| `js/fetch-retry.js` | 4 |
| `scss/components/_toasts.scss` | 4 |
| `templates/partials/_bottom-nav.html.twig` | 5 |
| `js/bottom-nav.js` | 5 |
| `jaraba_notifications/` (modulo completo) | 6 |
| `templates/partials/_notification-panel.html.twig` | 6 |
| `scss/components/_notification-panel.scss` | 6 |
| `js/notification-panel.js` | 6 |
| `templates/partials/_quick-start-overlay.html.twig` | 8 |
| `scss/components/_quick-start.scss` | 8 |
| `js/quick-start.js` | 8 |

## Ficheros Modificados (8)

| Fichero | Cambio |
|---------|--------|
| `scss/main.scss` | 5 nuevos @use imports |
| `scss/components/_cards.scss` | Hover lift + active press |
| `scss/components/_buttons.scss` | Active scale(0.97) |
| `scss/_slide-panel.scss` | @keyframes ej-fadeInScale |
| `ecosistema_jaraba_theme.libraries.yml` | 4 librerias nuevas |
| `ecosistema_jaraba_theme.info.yml` | fetch-retry global |
| `ecosistema_jaraba_theme.theme` | body class + libraries attach |
| `templates/page.html.twig` | 2 includes condicionales |
| `EntitySearchCommandProvider.php` | Cross-entity search (3 tipos) |
| `command-bar.js` | Recientes + agrupacion por categoria |

## Auditoria Post-Implementacion

### Violaciones Encontradas (6)

| # | Directriz | Fichero | Linea | Fix |
|---|-----------|---------|-------|-----|
| V1 | ICON-COLOR-001 | `_empty-state.html.twig` | 39 | Anadido `color: icon_color\|default('naranja-impulso')` a `jaraba_icon()` |
| V2 | ICON-COLOR-001 + DUOTONE-001 | `_bottom-nav.html.twig` | 41 | Anadido `variant: 'duotone', color: 'naranja-impulso'` |
| V3 | DESIGN-TOKEN-FEDERATED-001 | `_notification-panel.scss` | 83 | `rgba(255,140,66,0.08)` → `var(--ej-color-primary-light, ...)` |
| V4 | DESIGN-TOKEN-FEDERATED-001 | `_notification-panel.scss` | 123 | `#ffffff` → `var(--ej-text-on-primary, #ffffff)` |
| V5 | DESIGN-TOKEN-FEDERATED-001 | `_notification-panel.scss` | 152 | `rgba(255,140,66,0.04)` → `var(--ej-color-primary-subtle, ...)` |
| V6 | DESIGN-TOKEN-FEDERATED-001 | `_notification-panel.scss` | 111 | `20px` → `var(--ej-border-radius-pill, 20px)` |

### Areas Verificadas Compliant (12)

| Area | Estado | Notas |
|------|--------|-------|
| SCSS SSOT (no local `$ej-*`) | PASS | 0 variables locales en 5 SCSS satelite |
| BEM-NAMING-001 | PASS | 63+ clases correctamente nombradas |
| i18n Twig | PASS | `{% trans %}` / `\|trans` en 5 partials |
| i18n JS | PASS | `Drupal.t()` en 4 JS files |
| XSS Prevention | PASS | `textContent` only, never innerHTML para user data |
| CSRF-JS-CACHE-001 | PASS | Token cacheado como Promise |
| DRUPAL-BEHAVIORS-001 | PASS | 4 behaviors con `once()` |
| MODAL-ACCESSIBILITY-001 | PASS | Focus trap + Esc + restore en quick-start |
| COMPILED-CSS-NEVER-EDIT-001 | PASS | Solo SCSS editado |
| Clean Frontend | PASS | Sin `page.content`, partials via `{% include %}` |
| Theme Settings | PASS | 55+ CSS vars; componentes usan `var(--ej-*)` |
| Material Icons JS | ACEPTADO | Patron establecido (command-bar.js), sin API JS para SVG |

### Tokens CSS Nuevos Introducidos

| Token | Uso | Fallback |
|-------|-----|----------|
| `--ej-text-on-primary` | Texto sobre fondos de color primario | `#ffffff` |
| `--ej-color-primary-subtle` | Tinte muy sutil del primario (4% opacidad) | `rgba(255, 140, 66, 0.04)` |
| `--ej-border-radius-pill` | Border radius forma pastilla para filtros | `20px` |

### Patron: jaraba_icon() — Signature Real

```
jaraba_icon(string $category, string $name, array $options = [])
```

Opciones: `variant` (outline/duotone/filled), `color` (paleta Jaraba: naranja-impulso, azul-corporativo, verde-innovacion, white, neutral), `size` (CSS string o numero).

**Regla aprendida:** SIEMPRE especificar `color` con nombre de paleta Jaraba. El default "current color" no es fiable cuando el SVG se renderiza como `<img>`.

### Directriz Nueva: CSS-VAR-ALL-COLORS-001

Todo valor visual en SCSS satelite DEBE estar envuelto en `var(--ej-*, fallback)`. NUNCA hex hardcodeado. Tokens custom usan fallback como valor por defecto hasta que se inyecten desde Theme Settings UI.
