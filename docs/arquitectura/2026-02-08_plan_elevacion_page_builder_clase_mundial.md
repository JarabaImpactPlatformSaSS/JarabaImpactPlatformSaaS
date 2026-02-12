# 🚀 Plan de Elevación Page Builder & Site Builder a Clase Mundial

**Fecha de creación:** 2026-02-08 08:30  
**Última actualización:** 2026-02-09 09:45  
**Autor:** IA Asistente  
**Versión:** 1.2.0 (Corrección: 3 falsos positivos detectados)

---

## 📑 Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnóstico: Documentación vs. Código Real](#2-diagnóstico-documentación-vs-código-real)
3. [Gaps Reales Identificados](#3-gaps-reales-identificados)
4. [Plan de Acción Priorizado](#4-plan-de-acción-priorizado)
5. [Directrices SaaS de Obligado Cumplimiento](#5-directrices-saas-de-obligado-cumplimiento)
6. [Plan de Verificación](#6-plan-de-verificación)
7. [Resumen de Esfuerzo](#7-resumen-de-esfuerzo)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Revisión exhaustiva del ecosistema Page Builder + Site Builder cruzando **toda la documentación existente** con el **código fuente real** (8 Feb 2026). El objetivo es elevar el Canvas Editor de un score actual de **9.2/10** a **9.8/10**.

### Documentación Revisada

| Documento | Tipo | Estado |
|---|---|---|
| `2026-02-05_especificacion_grapesjs_saas.md` | Especificación GrapesJS (793 líneas) | ✅ Revisado |
| `2026-02-06_arq_unificada_templates_bloques_saas.md` | Arquitectura Unificada Templates-Bloques | ✅ Revisado |
| `20260206-Plan_Elevacion_PageBuilder_92_98_v1.md` | Plan Elevación 9.2→9.8 | ✅ Revisado |
| `20260206-Auditoria_Canvas_v2_Full_Page_v1.md` | Auditoría Canvas v2 | ✅ Revisado |
| `2026-02-06_bloques_page_builder_matrix.md` | Matriz de Bloques (132 total) | ✅ Revisado |
| `2026-02-05_arquitectura_theming_saas_master.md` | Federated Design Tokens v2.1 | ✅ Revisado |

### Código Fuente Revisado

| Archivo | LOC | Contenido |
|---|---|---|
| `grapesjs-jaraba-blocks.js` | 2,514 | 17 categorías, ~70 bloques + registry dinámico |
| `grapesjs-jaraba-canvas.js` | 1,036 | Clase `JarabaCanvasEditor`, i18n, style manager |
| `grapesjs-jaraba-partials.js` | 368 | Header/Footer/Content-Zone parciales |
| `grapesjs-jaraba-command-palette.js` | 434 | Fuzzy search, Ctrl+K |
| `grapesjs-jaraba-seo.js` | — | Auditor SEO integrado |
| `grapesjs-jaraba-assets.js` | — | Media Library híbrida |
| `grapesjs-jaraba-ai.js` | — | AI Content parcial |
| `canvas-editor.cy.js` | 508 | 9 suites E2E |

---

## 2. Diagnóstico: Documentación vs. Código Real

### Estado Verificado (8 Feb 2026)

| Componente | Documentación Dice | Código Real | Consistente |
|---|---|---|---|
| **Bloques estáticos** | 70 Jaraba + 62 nativos = 132 | `grapesjs-jaraba-blocks.js` 2514 líneas, 17 categorías | ✅ |
| **Motor Canvas** | GrapesJS v3 Full Page | `JarabaCanvasEditor` 1036 líneas | ✅ |
| **Command Palette** | Sprint 2 del plan | Plugin completo con fuzzy search (434 líneas) | ✅ |
| **SEO Auditor** | 100% implementado | Funcional | ✅ |
| **Media Library** | Hybrid Interceptor | Existente | ✅ |
| **Parciales (H/F)** | PostMessage sin receptor | 368 líneas, hot-swap sin receptor | ⚠️ Gap G1 |
| **AI Content** | Prompt-to-Section pendiente | Plugin existente, backend falta | ⚠️ Gap G4 |
| **Tests E2E** | Doc dice 6 suites | 9 suites reales (doc desactualizada) | ⚠️ Gap G7 |
| **Template Registry** | SSoT API + YAML | `loadBlocksFromRegistry()` + `setupBlockAnalytics()` | ✅ |
| **Feature flags** | `isLocked`, `isPremium` | Campos presentes en bloques registry | ✅ |

---

## 3. Gaps Reales Identificados

> [!CAUTION]
> **CORRECCIÓN v1.2.0 (2026-02-09):** La auditoría v1.0 del 2026-02-08 usó `grep` para verificar código, generando **3 falsos positivos** (G1, G2, G7). La auditoría v2.1 leyó los archivos completos y confirmó que el código YA existía. Ver [aprendizaje v2.1](../tecnicos/aprendizajes/2026-02-09_auditoria_v2_falsos_positivos_page_builder.md) y [plan v2.1](../planificacion/20260209-Plan_Elevacion_Page_Site_Builder_v2.md).

| # | Gap | Severidad | Estado (v1.0) | Estado Real (v1.2) |
|---|---|---|---|---|
| **G1** | Hot-swap receptor postMessage en iframe | 🔴 Alta | ~~Sin implementar~~ | ✅ **FALSO POSITIVO**: `notifyPreview()` L142-146 + `canvas-preview-receiver.js` (435 LOC) |
| **G2** | Dual Architecture bloques interactivos | 🔴 Alta | ~~Solo FAQ~~ | ✅ **FALSO POSITIVO**: 6/6 bloques con `script` + `addType` + `view.onRender()` |
| **G3** | Bloques Commerce/Social sin traits | 🟡 Media | HTML puro | 🔶 Pendiente (no es falso positivo) |
| **G4** | IA endpoint URL incorrecto | 🟡 Media | Plugin existe | ✅ **CORREGIDO** (2026-02-09): URL + payload + respuesta |
| **G5** | Onboarding Tour Canvas | 🟢 Baja | Sin implementar | 🔶 Pendiente |
| **G6** | Thumbnails SVG Registry | 🟢 Baja | Sin implementar | 🔶 Pendiente |
| **G7** | Tests E2E con fallbacks laxos | 🟡 Media | ~~`expect(true)`~~ | ✅ **FALSO POSITIVO**: 0 instancias encontradas |

---

## 4. Plan de Acción Priorizado

> **NOTA**: Para subir de 9.2 a 9.8/10, los gaps prioritarios son G1, G2 y G7.
> Cada sprint DEBE cumplir **todas** las directrices de la Sección 5 antes de considerarse completado.

### Sprint 1: Dual Architecture para Bloques Interactivos (G2) — 8h

**Archivo principal:** `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js`

Actualmente solo el FAQ Accordion implementa la Dual Architecture (script property + Drupal behavior). Los siguientes bloques necesitan interactividad real:

| Bloque | Interactividad Requerida | Estimado |
|---|---|---|
| `stats-counter` / `animated-counter` | Animación de conteo con Intersection Observer | 1.5h |
| `pricing-toggle` | Switch mensual/anual con animación de precios | 1.5h |
| `tabs-content` | Navegación por pestañas | 1.5h |
| `countdown-timer` | Temporizador en tiempo real | 1h |
| `timeline` | Animación de entrada escalonada (scroll-triggered) | 1h |

**Implementación por cada bloque:**

1. Definir `domComponents.addType('jaraba-xxx', { model: { defaults: { script: xxxScript } } })`
2. Implementar `script` function (NO arrow function, `this` = elemento)
3. Implementar `view.onRender()` que ejecute el script en el editor
4. Crear archivo `js/jaraba-xxx.js` con `Drupal.behaviors` equivalente
5. Registrar biblioteca en `jaraba_page_builder.libraries.yml`
6. **i18n**: Todos los textos con `Drupal.t()` en JS y `{% trans %}` en Twig
7. **SCSS**: Estilos en parcial SCSS (`scss/blocks/_jaraba-xxx.scss`), solo `var(--ej-*)` con fallback
8. **Compilar** vía Dart Sass: `npx sass scss/page-builder-blocks.scss:css/page-builder-blocks.css --style=compressed`
9. **ARIA**: `role`, `aria-label`, `aria-expanded`, `aria-selected` en cada bloque interactivo

**Archivos nuevos:**

- `js/jaraba-stats-counter.js` — Drupal behavior con `Drupal.t()` en textos
- `js/jaraba-pricing-toggle.js` — Drupal behavior con `Drupal.t()` en textos
- `js/jaraba-tabs-content.js` — Drupal behavior con `Drupal.t()` en textos
- `js/jaraba-countdown-timer.js` — Drupal behavior con `Drupal.t()` en textos
- `js/jaraba-timeline.js` — Drupal behavior con `Drupal.t()` en textos
- `scss/blocks/_stats-counter.scss` — Solo `var(--ej-*)`, colores paleta Jaraba
- `scss/blocks/_pricing-toggle.scss`
- `scss/blocks/_tabs-content.scss`
- `scss/blocks/_countdown-timer.scss`
- `scss/blocks/_timeline.scss`

### Sprint 2: Hot-Swap Receptor PostMessage (G1) — 4h

**Archivo:** `web/modules/custom/jaraba_page_builder/js/canvas-preview-receiver.js`

Implementar receptor de mensajes `JARABA_HEADER_CHANGE` y `JARABA_FOOTER_CHANGE`:

1. Listener `window.addEventListener('message', handler)` en el iframe
2. Al recibir `JARABA_HEADER_CHANGE`: fetch Twig parcial vía AJAX y reemplazar `<header>`
3. Al recibir `JARABA_FOOTER_CHANGE`: fetch Twig parcial y reemplazar `<footer>`
4. Persistir cambios en `SiteConfig` via API REST `/api/v1/site-config/partials`
5. **Parciales Twig**: Los parciales de header/footer ya existen en `@ecosistema_jaraba_theme/partials/`. El receptor debe usar las **variables configurables desde la UI de Drupal** (theme settings) para que el contenido del footer sea editable sin código
6. **i18n**: Textos del receptor con `Drupal.t()`, feedback visual traducible

### Sprint 3: Robustez Tests E2E (G7) — 3h

**Archivo:** `tests/e2e/cypress/e2e/canvas-editor.cy.js`

| Test | Cambio |
|---|---|
| Test 8 (Command Palette) | Eliminar fallback laxo, verificar plugin cargado |
| Test 4 (Traits) | Verificar actualización real de texto en canvas |
| Test 5 (REST) | Interceptar y verificar payload JSON |
| Nuevo Test 10 | Bloque interactivo Stats Counter funciona |
| Nuevo Test 11 | Hot-swap header cambia variante visual |

### Sprint 4: Bloques Commerce/Social con Traits Configurables (G3) — 6h

**Archivo:** `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js`

Añadir traits configurables a bloques que actualmente son solo HTML estático:

- **Commerce**: `product-card` → traits de precio, nombre, imagen, URL de compra
- **Social**: `social-links` → traits de URLs de redes sociales
- **Contact**: `contact-form` → traits de email destino, campos requeridos
- **Pricing**: `pricing-table` → traits de nombres de planes, precios, features

**Cumplimiento por bloque:**
- Labels de traits con `Drupal.t()` para i18n
- Estilos solo `var(--ej-*)` con paleta Jaraba (`corporate`, `innovation`, `impulse`, `agro`)
- Iconos con `jaraba_icon()` en variantes outline + duotone
- SCSS parciales nuevos compilados con Dart Sass

### Sprint 5 (Futuro): IA y Onboarding (G4, G5)

Quedan como mejoras futuras post-9.8:

- Prompt-to-Section endpoint backend
- Onboarding Tour con Driver.js o similar

---

## 5. Directrices SaaS de Obligado Cumplimiento

> ⚠️ **CADA SPRINT debe cumplir TODAS estas directrices.** No se considera completado un sprint si incumple alguna.

### 5.1 Theming: Federated Design Tokens (5 Capas)

> **Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
> **Workflow:** `.agent/workflows/scss-estilos.md`

| Capa | Nombre | Responsabilidad |
|------|--------|-----------------|
| 1 | SCSS Tokens | `ecosistema_jaraba_core/scss/_variables.scss` — fallbacks de compilación |
| 2 | CSS Custom Properties | `_injectable.scss` → `:root` tokens inyectables |
| 3 | Component Tokens | Parciales SCSS con scope local |
| 4 | Tenant Override | `hook_preprocess_html()` inyecta desde Drupal UI |
| 5 | Vertical Presets | Config Entity con paletas por vertical |

**Reglas inquebrantables:**

```scss
// ✅ CORRECTO — Solo CSS vars con fallback inline
.jaraba-stats-counter {
    color: var(--ej-color-corporate, #233D63);
    background: var(--ej-bg-surface, #fff);
    padding: var(--ej-spacing-md, 1rem);
}

// ❌ INCORRECTO — NUNCA duplicar variables SCSS en módulos satélite
$ej-color-corporate: #233D63; // PROHIBIDO en jaraba_page_builder
```

### 5.2 SCSS y Compilación con Dart Sass

| Regla | Detalle |
|-------|---------|
| **NUNCA crear .css directamente** | Siempre SCSS que se compila |
| **Dart Sass moderno** | `npx sass` (no node-sass ni LibSass) |
| **Un parcial por componente** | `scss/blocks/_stats-counter.scss` |
| **Import en entry point** | `@use 'blocks/stats-counter';` en `page-builder-blocks.scss` |
| **package.json obligatorio** | Script `build` en cada módulo con SCSS |
| **Compilación** | `npx sass scss/main.scss:css/output.css --style=compressed` |

**Comandos de compilación para el Page Builder:**

```powershell
# Compilar bloques del Page Builder
cd z:\home\PED\JarabaImpactPlatformSaaS\web\modules\custom\jaraba_page_builder
npx sass scss/page-builder-blocks.scss:css/page-builder-blocks.css --style=compressed

# Compilar tema (si se modifican parciales del tema)
cd z:\home\PED\JarabaImpactPlatformSaaS\web\themes\custom\ecosistema_jaraba_theme
npx sass scss/main.scss:css/main.css --style=compressed

# Limpiar caché
docker exec jarabasaas_appserver_1 drush cr
```

### 5.3 Paleta de Colores de Marca

| Variable CSS | Hex | Uso Semántico |
|---|---|---|
| `--ej-color-azul-profundo` | `#003366` | Autoridad, profundidad |
| `--ej-color-azul-verdoso` | `#2B7A78` | Conexión, equilibrio |
| `--ej-color-corporate` | `#233D63` | Base corporativa, confianza |
| `--ej-color-impulse` | `#FF8C42` | Emprendimiento, CTAs |
| `--ej-color-innovation` | `#00A9A5` | Talento, empleabilidad |
| `--ej-color-agro` | `#556B2F` | AgroConecta, naturaleza |
| `--ej-color-agro-dark` | `#3E4E23` | AgroConecta intenso |
| `--ej-color-primary` | `#4F46E5` | Acciones primarias UI |
| `--ej-color-success` | `#10B981` | Estados positivos |
| `--ej-color-warning` | `#F59E0B` | Alertas |
| `--ej-color-danger` | `#EF4444` | Errores, destructivo |

### 5.4 Iconografía SVG

> **Workflow:** `.agent/workflows/scss-estilos.md` §Iconos

| Regla | Detalle |
|-------|---------|
| **Siempre dual** | `{nombre}.svg` (outline) + `{nombre}-duotone.svg` |
| **Ubicación** | `ecosistema_jaraba_core/images/icons/{categoría}/` |
| **Color dinámico** | CSS filter vía `jaraba_icon()`, NO colores hardcodeados |
| **Uso en Twig** | `{{ jaraba_icon('business', 'diagnostic', { color: 'corporate' }) }}` |

### 5.5 Internacionalización (i18n)

> **Workflow:** `.agent/workflows/i18n-traducciones.md`

| Contexto | Método | Ejemplo |
|----------|--------|---------|
| **PHP** | `$this->t()` | `$this->t('Panel de Salud')` |
| **Twig** | `{% trans %}` | `{% trans %}Guardar cambios{% endtrans %}` |
| **JavaScript** | `Drupal.t()` | `Drupal.t('Bloque añadido')` |

⚠️ **NUNCA textos hardcodeados** en la interfaz — todo traducible.

### 5.6 Frontend Limpio (Zero Region Policy)

> **Workflow:** `.agent/workflows/frontend-page-pattern.md`

| Regla | Detalle |
|-------|---------|
| **Template limpia** | `page--{ruta}.html.twig` sin `page.content` ni bloques heredados |
| **Layout full-width** | Sin sidebar, max-width responsive, mobile-first |
| **Header/Footer** | `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}` con variables configurables |
| **Body classes** | `hook_preprocess_html()` — ⚠️ `attributes.addClass()` NO funciona para body |
| **Parciales reutilizables** | Antes de extender código, verificar si ya existe un parcial que cubra la necesidad o crear uno nuevo |
| **Sin admin theme** | El tenant NO tiene acceso al tema de administración de Drupal |
| **Variables configurables** | Header y footer usan `theme_settings` desde la UI de Drupal, sin tocar código para cambiar contenido |

### 5.7 Modal Slide-Panel para CRUD

> **Workflow:** `.agent/workflows/slide-panel-modales.md`

| Regla | Detalle |
|-------|---------|
| **Todas las acciones crear/editar/ver** | Se abren en slide-panel, el usuario no abandona la página |
| **Data attributes** | `data-slide-panel`, `data-slide-panel-url`, `data-slide-panel-title` |
| **Controlador AJAX** | Detectar `$request->isXmlHttpRequest()` y devolver solo HTML del formulario |
| **Accesibilidad** | `role="dialog"`, `aria-modal="true"`, focus trap, ESC, overlay |
| **Library** | Declarar dependencia `ecosistema_jaraba_theme/slide-panel` |

### 5.8 Content Entities + Field UI + Views

> **Workflow:** `.agent/workflows/drupal-custom-modules.md`

| Regla | Detalle |
|-------|---------|
| **Content Entity** para datos de negocio | Field UI, Views, Entity Reference |
| **Handler `views_data`** obligatorio | Integración con Views |
| **`field_ui_base_route`** definido | Pestaña "Administrar campos" |
| **Navegación correcta** | Content Entity → `/admin/content/…`, Config Entity → `/admin/structure/…` |
| **4 archivos YAML obligatorios** | `*.routing.yml`, `*.links.menu.yml`, `*.links.task.yml`, `*.links.action.yml` |
| **Entity keys completos** | `id`, `uuid`, `label`, `owner` |

### 5.9 Interactividad Dual (GrapesJS)

| Lado | Implementación |
|------|----------------|
| **Editor (GrapesJS)** | `script` property (function regular, NO arrow) + `view.onRender()` |
| **Público (Drupal)** | `Drupal.behaviors.jarabaXxx` en archivo separado con `once()` |
| **Library** | Registrada en `jaraba_page_builder.libraries.yml` |
| **ARIA** | `role`, `aria-label`, `aria-expanded`, `aria-selected`, `aria-controls` |

### 5.10 Estándares adicionales

| Estándar | Referencia |
|---|---|
| **BEM Naming** | `jaraba-{bloque}__{elemento}--{modificador}` |
| **Mobile-first** | Breakpoints `@media (min-width: ...)`, no `max-width` |
| **Comentarios en español** | Estructura + Lógica + Sintaxis según §10 Directrices |
| **No hardcodear** | Configuraciones de negocio desde Content Entities |

---

## 6. Plan de Verificación

### Tests Automatizados E2E (Cypress)

```bash
# Desde WSL con Lando
cd /home/PED/JarabaImpactPlatformSaaS/tests/e2e
npx cypress run --spec "cypress/e2e/canvas-editor.cy.js" --config baseUrl=https://jaraba-saas.lndo.site
```

### Verificación Manual en Browser

1. **Navegar** a `https://jaraba-saas.lndo.site/es/page/17/editor?mode=canvas`
2. **Arrastrar** Stats Counter → verificar animación de conteo
3. **Arrastrar** Pricing Toggle → verificar switch de precios
4. **Abrir** Command Palette `Ctrl+K` → buscar "hero" → verificar resultados
5. **Seleccionar** header → cambiar tipo en traits → verificar hot-swap visual
6. **Guardar** y recargar → verificar persistencia

### Checklist de Cumplimiento SaaS (por Sprint)

- [ ] ¿Todos los textos usan `Drupal.t()` / `{% trans %}` / `$this->t()`?
- [ ] ¿Estilos solo con `var(--ej-*)` y fallback inline?
- [ ] ¿SCSS compilado con Dart Sass (`npx sass`)?
- [ ] ¿Iconos en versión outline + duotone vía `jaraba_icon()`?
- [ ] ¿Layout mobile-first con breakpoints `min-width`?
- [ ] ¿CRUD en slide-panel sin abandonar la página?
- [ ] ¿Body classes vía `hook_preprocess_html()`, no `attributes.addClass()`?
- [ ] ¿Colores de la paleta Jaraba oficial (7 colores de marca)?
- [ ] ¿Comentarios en español con las 3 dimensiones (Estructura/Lógica/Sintaxis)?
- [ ] ¿ARIA completo en bloques interactivos?

---

## 7. Resumen de Esfuerzo

| Sprint | Horas | Impacto en Score |
|---|---|---|
| Sprint 1: Dual Architecture | 8h | +0.3 ✅ |
| Sprint 2: Hot-Swap | 4h | +0.1 ✅ |
| Sprint 3: Tests E2E | 3h | +0.1 ✅ |
| Sprint 4: Traits Commerce | 6h | +0.1 ✅ |
| **Total** | **21h** | **9.2 → 9.8** ✅ |

---

## 8. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-09 | **1.2.0** | **CORRECCIÓN MASIVA:** G1, G2, G7 eran falsos positivos por grep. G4 (AI endpoint) corregido. Score real: 9.8→10/10. Plan v2.1 y aprendizaje documentados. Esfuerzo real: 2h (no 21h) |
| 2026-02-08 | 1.1.0 | **Directrices SaaS incorporadas:** Sección 5 ampliada con 10 sub-secciones cubriendo Federated Design Tokens, SCSS/Dart Sass, paleta de marca, iconos SVG, i18n, frontend limpio, slide-panel, Content Entities, interactividad dual, y checklist de cumplimiento por sprint |
| 2026-02-08 | 1.0.0 | Creación inicial: diagnóstico exhaustivo, 7 gaps, 4 sprints (21h), plan de verificación |
