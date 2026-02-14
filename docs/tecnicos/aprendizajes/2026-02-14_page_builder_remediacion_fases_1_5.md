# Aprendizaje #75: Remediación Page Builder — FASES 1-5

**Fecha:** 2026-02-14
**Sesión:** 2 sesiones continuas (~4h estimadas)
**Módulo principal:** `jaraba_page_builder`
**Archivos modificados:** ~15 archivos (JS, SCSS, YML, PHP)
**Archivos creados:** 1 (grapesjs-jaraba-icons.js) + 6 CSS compilados

---

## Contexto

Remediación integral del Page Builder siguiendo 5 fases identificadas en la auditoría previa. Objetivo: eliminar deuda técnica (emojis, fuentes inconsistentes, bloques duplicados, SCSS faltante) y elevar la calidad del código a estándar de producción.

---

## FASE 0: Publish 404 + SEO URLs + Navigation

### F0.1 — Publish endpoint 404

| Situación | El endpoint `/api/v1/pages/{id}/publish` devolvía 404 porque la ruta dependía del entity type `page_content` para resolver `{page_content}`, pero el JS enviaba un ID numérico sin pasar por el route parameter converter |
|-----------|---|
| Aprendizaje | Crear un Controller dedicado (`PageContentPublishController`) con ruta manual `/{id}` que carga la entidad explícitamente via `entityTypeManager->getStorage('page_content')->load($id)` |
| Regla | **PB-ROUTE-001**: Las rutas de API custom para PageContent DEBEN cargar la entidad manualmente (no confiar en entity parameter upcast) |

### F0.2 — SEO URLs automáticas

| Situación | Las páginas publicadas usaban URLs con IDs numéricos (`/p/123`) en lugar de slugs semánticos |
|-----------|---|
| Aprendizaje | Implementar `preSave()` en la entidad PageContent para generar automáticamente un slug a partir del título: `transliterate → lowercase → preg_replace → trim` |
| Regla | **PB-SEO-001**: Toda entidad PageContent DEBE generar slug SEO automáticamente en `preSave()` |

### F0.3 — Navigation Behavior + SCSS

| Situación | El bloque Navigation de GrapesJS solo tenía interactividad en el canvas pero no en el frontend publicado |
|-----------|---|
| Aprendizaje | Implementar `Drupal.behaviors.jarabaNavigation` separado del script del canvas (dual architecture: canvas `script` property + frontend behavior) |
| Regla | **PB-DUAL-001**: Todo componente interactivo DEBE tener dual implementación (GrapesJS `script` para canvas + `Drupal.behaviors` para frontend) |

---

## FASE 1: SCSS Compilation + Library Registration

### F1.1 — Docker NVM Path para SCSS

| Situación | `npx sass` fallaba con "command not found" dentro del contenedor Docker `jarabasaas_appserver_1` |
|-----------|---|
| Aprendizaje | Node.js está instalado via NVM en `/user/.nvm/versions/node/v20.20.0/bin` dentro del contenedor Docker. Requiere `export PATH=...` previo |
| Comando | `docker exec jarabasaas_appserver_1 bash -c "export PATH=/user/.nvm/versions/node/v20.20.0/bin:$PATH && cd /app/web/modules/custom/jaraba_page_builder && npx sass scss/blocks/_name.scss css/name.css --style=compressed"` |

### F1.2 — darken() deprecation en Dart Sass

| Situación | `darken(#FF8C42, 8%)` generaba deprecation warning. El archivo es un parcial standalone sin `@use 'sass:color'` |
|-----------|---|
| Aprendizaje | Para parciales standalone, usar valores hex pre-computados como alternativa válida: `#eb7a30` = `darken(#FF8C42, 8%)` |
| Regla | **SCSS-003**: En parciales standalone sin `@use 'sass:color'`, pre-computar valores. En parciales con `@use`, usar `color.scale()` |

### F1.3 — Pipeline de libraries independiente

| Situación | ¿Los CSS del Page Builder deben ir en `ecosistema_jaraba_core/scss/main.scss`? |
|-----------|---|
| Aprendizaje | NO. El Page Builder tiene su propio pipeline: cada parcial SCSS compila a su propio CSS, registrado como library independiente en `jaraba_page_builder.libraries.yml` y attached en `.module` para rutas `entity.page_content.*` |
| Patrón | Un CSS = una library = un `$attachments['#attached']['library'][]` |

---

## FASE 2: Duplicate Block Redirection

### F2.1 — Bloques estáticos duplicando componentes interactivos

| Situación | Los bloques `timeline`, `tabs-content` y `countdown` tenían HTML estático duplicado, cuando ya existían component types interactivos (`jaraba-timeline`, `jaraba-tabs`, `jaraba-countdown`) con traits y scripts |
|-----------|---|
| Aprendizaje | GrapesJS `content` acepta tanto HTML string como un objeto component definition `{ type: 'jaraba-timeline' }`. Al usar el objeto, GrapesJS instancia el componente interactivo directamente |
| Regla | **PB-DEDUP-001**: Si existe un component type interactivo registrado, el bloque DEBE usar `content: { type: 'nombre-tipo' }` en lugar de HTML estático |

---

## FASE 3: IconRegistry + Emoji Replacement

### F3.1 — Creación del IconRegistry centralizado

| Situación | ~22 emojis en el HTML de contenido de bloques GrapesJS (📦, ⭐, 🚀, 👤, etc.) que se renderizaban diferente en cada SO/navegador |
|-----------|---|
| Aprendizaje | Crear un registry centralizado `Drupal.jarabaIcons` con SVGs inline usando `width="1em" height="1em"` para que escalen con `font-size`. API: `.get(name, fallback)`, `.stars(count)`, `.list()` |
| Archivo | `js/grapesjs-jaraba-icons.js` — 17 iconos SVG (Lucide-compatible) |

### F3.2 — iconOptions del sidebar NO se reemplazan

| Situación | Los emojis en `iconOptions` (categorías del sidebar GrapesJS: `{ icon: '📦', title: 'Ecommerce' }`) — ¿se reemplazan? |
|-----------|---|
| Aprendizaje | NO. Los `iconOptions` son labels de UI del editor GrapesJS, no contenido renderizado en el frontend. Los emojis ahí son perfectamente válidos |
| Regla | **PB-ICON-001**: Solo reemplazar emojis en `content` de bloques (HTML renderizado). Mantener emojis en `iconOptions` (UI del editor) |

### F3.3 — Orden de carga en libraries.yml

| Situación | `grapesjs-jaraba-icons.js` debe estar disponible ANTES de `grapesjs-jaraba-blocks.js` |
|-----------|---|
| Aprendizaje | En `jaraba_page_builder.libraries.yml`, el orden de archivos JS dentro de una library define el orden de carga. Colocar `icons.js` ANTES de `blocks.js` en la sección `js:` de `grapesjs-canvas` |

---

## FASE 5: Font-Family Unification

### F5.1 — Inconsistencia Inter vs Outfit

| Situación | Algunos bloques JS usaban `'Inter'` como fallback y otros `'Outfit'`. Los archivos SCSS tenían la misma mezcla |
|-----------|---|
| Aprendizaje | El tema del ecosistema (`ecosistema_jaraba_theme`) usa `'Outfit'` como tipografía principal. TODOS los fallbacks deben ser `'Outfit'` para coherencia visual |
| Alcance | 5 cambios en JS (`grapesjs-jaraba-blocks.js`) + 10 cambios en SCSS (8 parciales de bloques) |
| Regla | **SCSS-FONT-001**: El fallback de `var(--ej-font-family)` SIEMPRE es `'Outfit', sans-serif` |

---

## Fix Crítico: PHP 8.4 ControllerBase

### Redeclaración de tipo en propiedades heredadas

| Situación | `drush cr` fallaba con PHP Fatal: `AdminCenterApiController::$entityTypeManager must not have a type` |
|-----------|---|
| Aprendizaje | PHP 8.4 prohíbe redeclarar con tipo propiedades heredadas sin tipo. `ControllerBase` declara `$entityTypeManager` y `$currentUser` sin tipo. Constructor promotion con `protected EntityTypeManagerInterface $entityTypeManager` es un Fatal Error |
| Solución | Usar parámetro sin `protected` y asignar manualmente: `$this->entityTypeManager = $entityTypeManager;` |
| Regla | **DRUPAL11-002**: En Controllers que extienden `ControllerBase`, NUNCA usar constructor promotion para `$entityTypeManager`, `$currentUser`, `$formBuilder`, `$moduleHandler` |

---

## Resumen de Archivos

### Creados
| Archivo | Líneas | Descripción |
|---------|--------|-------------|
| `js/grapesjs-jaraba-icons.js` | ~230 | IconRegistry SVG centralizado (17 iconos) |

### Modificados
| Archivo | Cambios |
|---------|---------|
| `jaraba_page_builder.libraries.yml` | +4 libraries CSS + icons.js en grapesjs-canvas |
| `jaraba_page_builder.module` | +4 library attachments |
| `js/grapesjs-jaraba-blocks.js` | 3 block redirections + ~22 emoji→icon + 5 font-family fixes |
| `scss/blocks/_product-card.scss` | darken() → pre-computed hex |
| `scss/blocks/_page-builder-core.scss` | Inter → Outfit (×4) |
| `scss/blocks/_contact-form.scss` | Inter → Outfit |
| `scss/blocks/_navigation.scss` | Inter → Outfit |
| `scss/blocks/_stats-counter.scss` | Inter → Outfit |
| `scss/blocks/_timeline.scss` | Inter → Outfit |
| `scss/blocks/_tabs-content.scss` | Inter → Outfit |
| `scss/blocks/_countdown-timer.scss` | Inter → Outfit |
| `scss/blocks/_faq-accordion.scss` | Inter → Outfit |
| `scss/blocks/_pricing-toggle.scss` | Inter → Outfit |
| `ecosistema_jaraba_core/.../AdminCenterApiController.php` | PHP 8.4 constructor fix |

### CSS Compilados (Docker → volume mount)
| Archivo | Tamaño |
|---------|--------|
| `css/product-card.css` | 1.7 KB |
| `css/social-links.css` | 935 B |
| `css/contact-form.css` | 1.9 KB |
| `css/page-builder-core.css` | 6 KB |
| `css/navigation.css` | recompilado |
| `css/page-builder-blocks.css` | recompilado |

---

## Reglas Nuevas

| Código | Regla | Sección |
|--------|-------|---------|
| **PB-ROUTE-001** | Rutas API PageContent: cargar entidad manualmente | FASE 0 |
| **PB-SEO-001** | Slug SEO automático en `preSave()` | FASE 0 |
| **PB-DUAL-001** | Dual Architecture: canvas script + Drupal.behaviors | FASE 0 |
| **PB-DEDUP-001** | Bloques: usar `{ type }` si existe componente interactivo | FASE 2 |
| **PB-ICON-001** | Emojis solo en iconOptions (UI), SVG en content (HTML) | FASE 3 |
| **SCSS-003** | Pre-computed hex en parciales standalone | FASE 1 |
| **SCSS-FONT-001** | Fallback tipográfico siempre `'Outfit'` | FASE 5 |
| **DRUPAL11-002** | No constructor promotion para props de ControllerBase | Fix PHP 8.4 |

---

## Verificación Final

- [x] 5 libraries registradas y detectadas (`drush scr`)
- [x] Ruta `/api/v1/pages/{id}/publish` operativa
- [x] `drush cr` exitoso (tras fix PHP 8.4)
- [x] 6 CSS compilados sin errores
- [x] Emojis reemplazados en contenido de bloques (~22 cambios)
- [x] Font-family unificado en JS + SCSS (15+ cambios)
- [x] 3 bloques estáticos redirigidos a componentes interactivos
