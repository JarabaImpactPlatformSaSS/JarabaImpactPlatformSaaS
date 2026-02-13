# Plan de Implementacion — Sprint Diferido: 22 TODOs Backlog

> **Tipo:** Plan de Implementacion Tecnica
> **Version:** 2.1.0
> **Fecha:** 2026-02-13
> **Autor:** Claude Opus 4.6 — Arquitecto SaaS Senior
> **Estado:** IMPLEMENTACION COMPLETADA (22/22 TODOs resueltos)
> **Precedentes:** Sprint Inmediato (`d2684dbd`), Sprints S2-S7 (`11924d5a`)
> **Referencia:** Catalogo TODOs v1.2.0 — 112 unicos, 48 resueltos (S.Inmediato), 49 planificados (S2-S7), **22 diferidos**

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Precedentes](#2-contexto-y-precedentes)
3. [Inventario de TODOs Diferidos](#3-inventario-de-todos-diferidos)
4. [FASE 1: Quick Wins](#4-fase-1-quick-wins-4-todos-12-16h)
5. [FASE 2: UX Sprint 5](#5-fase-2-ux-sprint-5-4-todos-16-24h)
6. [FASE 3: Knowledge Base TK2-TK4](#6-fase-3-knowledge-base-tk2-tk4-4-todos-20-30h)
7. [FASE 4: Infraestructura](#7-fase-4-infraestructura-4-todos-24-36h)
8. [FASE 5: Integraciones Comerciales](#8-fase-5-integraciones-comerciales-6-todos-30-45h)
9. [Tabla de Correspondencia Tecnica](#9-tabla-de-correspondencia-tecnica)
10. [Matriz de Cumplimiento de Directrices](#10-matriz-de-cumplimiento-de-directrices)
11. [Plan de Verificacion y Testing](#11-plan-de-verificacion-y-testing)
12. [Estimaciones y Roadmap](#12-estimaciones-y-roadmap)
13. [Dependencias entre Fases](#13-dependencias-entre-fases)
14. [Riesgos y Mitigaciones](#14-riesgos-y-mitigaciones)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Estado de la Plataforma

| Metrica | Valor |
|---------|-------|
| Modulos custom | 61 |
| Content Entities | 267 |
| Services | 443 |
| Controllers | 276 |
| Unit Tests | 194 archivos / 1,466+ tests |
| Archivos JS | 203 |
| Archivos SCSS | 185 |
| Templates Twig | 462 |
| CI Status | ALL GREEN |
| TODOs totales (v1.2.0) | 112 unicos |
| TODOs resueltos (S.Inmediato) | 48 |
| TODOs planificados (S2-S7) | 49 |
| **TODOs diferidos (este plan)** | **22** |
| **Cobertura formal** | **100% (112/112)** |

### 1.2 Distribucion por Fase

| Fase | Nombre | TODOs | Estimacion | Prioridad |
|------|--------|-------|------------|-----------|
| **F1** | Quick Wins | 4 | 12-16h | P2 — Media |
| **F2** | UX Sprint 5 | 4 | 16-24h | P2 — Media |
| **F3** | Knowledge Base TK2-TK4 | 4 | 20-30h | P2 — Media |
| **F4** | Infraestructura | 4 | 24-36h | P3 — Baja |
| **F5** | Integraciones Comerciales | 6 | 30-45h | P3 — Baja |
| | **TOTAL** | **22** | **102-151h** | — |

### 1.3 Objetivo

Con este documento, los 112 TODOs identificados en el Catalogo v1.2.0 quedan **100% cubiertos** con planes de implementacion formales:

- **48 resueltos** — Sprint Inmediato (commit `d2684dbd`)
- **49 planificados** — Sprints S2-S7 (commit `11924d5a`)
- **22 diferidos** — Este documento (5 fases, Q2 2026)

Ningun TODO queda sin plan de accion.

---

## 2. Contexto y Precedentes

### 2.1 Sprint Inmediato (completado)

- **Commit:** `d2684dbd`
- **Fecha:** 2026-02-12
- **Alcance:** 48 TODOs de TENANT_FILTERING + DATA_INTEGRATION
- **Resultado:** Filtrado multi-tenant implementado en todos los controladores y servicios criticos
- **Directrices cumplidas:** TENANT-001, TENANT-002, PHP-STRICT, ENTITY-001, SERVICE-001

### 2.2 Sprints S2-S7 (planificados)

- **Commit:** `11924d5a`
- **Fecha:** 2026-02-12
- **Alcance:** 49 TODOs distribuidos en 6 sprints
- **Sprints:** S2 (Notifications, 15), S3 (ServiciosConecta, 8), S4 (ComercioConecta, 6), S5 (Job Board + LMS, 4), S6 (Social APIs, 5), S7 (Qdrant + FOC, 7)
- **Timeline:** Feb-Mar 2026 (180-250h estimadas)

### 2.3 Criterios de Diferimiento

Los 22 TODOs de este plan fueron diferidos por cumplir al menos uno de estos criterios:

1. **Dependencias externas** — Requieren infraestructura o APIs no disponibles aun (Whisper, Stripe Connect, Puppeteer)
2. **Decisiones UX pendientes** — Requieren aprobacion de diseno antes de implementar (Header SaaS, i18n selector, a11y panel)
3. **Sprints dedicados** — Tienen su propia cadencia planificada (TK2, TK3, TK4)
4. **Arquitectura V2.1** — Dependen de decisiones arquitectonicas del proximo major (Pixels V2.1, dispatch directo)
5. **Bajo impacto inmediato** — Funcionalidad no critica para el MVP actual (ratings, categorias, revision interactiva)

### 2.4 Relacion entre Planes

```
Catalogo TODOs v1.2.0 (112 unicos)
├── Sprint Inmediato ─── 48 TODOs ─── COMPLETADO (d2684dbd)
├── Sprints S2-S7 ────── 49 TODOs ─── PLANIFICADO (11924d5a)
└── Sprint Diferido ──── 22 TODOs ─── ESTE DOCUMENTO
    ├── Fase 1: Quick Wins ────────── 4 TODOs
    ├── Fase 2: UX Sprint 5 ──────── 4 TODOs
    ├── Fase 3: Knowledge Base ────── 4 TODOs
    ├── Fase 4: Infraestructura ───── 4 TODOs
    └── Fase 5: Integraciones ─────── 6 TODOs
```

---

## 3. Inventario de TODOs Diferidos

### 3.1 Por Fase

| # | Fase | TODO | Archivo | Linea |
|---|------|------|---------|-------|
| 1 | F1 | Tabla de comparacion pricing | `jaraba_page_builder/templates/blocks/pricing/pricing-table.html.twig` | 131 |
| 2 | F1 | Ratings de cursos | `jaraba_page_builder/jaraba_page_builder.module` | 718 |
| 3 | F1 | Guardado completo canvas | `jaraba_page_builder/js/canvas-editor.js` | 129 |
| 4 | F1 | Vista de revision player | `jaraba_interactive/js/player.js` | 1483 |
| 5 | F2 | Header SaaS en canvas | `jaraba_page_builder/templates/canvas-editor.html.twig` | 21 |
| 6 | F2 | i18n selector en canvas | `jaraba_page_builder/templates/canvas-editor.html.twig` | 173 |
| 7 | F2 | Campos dinamicos section-editor | `jaraba_page_builder/templates/section-editor.html.twig` | 308 |
| 8 | F2 | Panel a11y en slide-panel | `jaraba_page_builder/js/accessibility-validator.js` | 248 |
| 9 | F3 | CRUD FAQs | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 165 |
| 10 | F3 | Formulario Add FAQ | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 179 |
| 11 | F3 | CRUD Policies | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 190 |
| 12 | F3 | CRUD Documents | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 204 |
| 13 | F4 | Re-ejecutar accion agente | `ecosistema_jaraba_core/src/Service/AgentAutonomyService.php` | 363 |
| 14 | F4 | Migrar tests a Functional | `ecosistema_jaraba_core/tests/src/Kernel/TenantProvisioningTest.php` | 49 |
| 15 | F4 | Dispatch interno webhook | `jaraba_integrations/src/Controller/WebhookReceiverController.php` | 50 |
| 16 | F4 | Categoria campo en Course | `jaraba_page_builder/jaraba_page_builder.module` | 720 |
| 17 | F5 | Test call plataformas V2.1 | `jaraba_pixels/src/Service/TokenVerificationService.php` | 166 |
| 18 | F5 | Dispatch directo sin entidad V2.1 | `jaraba_pixels/src/Service/BatchProcessorService.php` | 188 |
| 19 | F5 | Integrar inventario Commerce | `jaraba_commerce/jaraba_commerce.module` | 148 |
| 20 | F5 | Publicacion canvas | `jaraba_page_builder/js/canvas-editor.js` | 140 |
| 21 | F5 | Stripe Connect checkout real | `jaraba_commerce/jaraba_commerce.module` | 148 |
| 22 | F5 | Wikidata/Crunchbase APIs | `jaraba_geo/jaraba_geo.module` | 40 |

### 3.2 Por Modulo

| Modulo | TODOs | Fases |
|--------|-------|-------|
| `jaraba_page_builder` | 7 | F1, F2, F4, F5 |
| `jaraba_tenant_knowledge` | 4 | F3 |
| `ecosistema_jaraba_core` | 2 | F4 |
| `jaraba_pixels` | 2 | F5 |
| `jaraba_commerce` | 2 | F5 |
| `jaraba_interactive` | 1 | F1 |
| `jaraba_integrations` | 1 | F4 |
| `jaraba_geo` | 1 | F5 |
| **Total** | **22** | **5 fases** |

### 3.3 Por Categoria

| Categoria | TODOs | Fases |
|-----------|-------|-------|
| UX_FRONTEND | 8 | F1, F2 |
| FUTURE_PHASE (TK2-TK4) | 4 | F3 |
| INFRASTRUCTURE | 4 | F4 |
| EXTERNAL_API / INTEGRACIONES | 6 | F5 |
| **Total** | **22** | — |

---

## 4. FASE 1: Quick Wins (4 TODOs, 12-16h)

**Objetivo:** Resolver 4 TODOs de baja complejidad con impacto visual inmediato.
**Prioridad:** P2 — Media
**Dependencias:** Ninguna (completamente independiente)
**Modulos afectados:** `jaraba_page_builder`, `jaraba_interactive`

### Directrices aplicables a Fase 1

| ID | Directriz | Verificacion |
|----|-----------|-------------|
| SCSS-001/002 | Dart Sass `@use`, cero `@import` | Todo SCSS nuevo usa `@use` |
| P4-COLOR-001 | Solo 7 colores Jaraba (`--ej-primary`, `--ej-secondary`, etc.) | Variables CSS con fallback |
| P4-EMOJI-001/002 | `jaraba_icon()` sin emojis | Iconos via funcion Twig helper |
| CSS-TOKENS | `var(--ej-*)` con fallbacks | Todo color/spacing referencia tokens |
| BEM | BEM naming en clases CSS | `.jaraba-block__element--modifier` |
| i18n | Textos traducibles | `{% trans %}` en Twig, `$this->t()` en PHP |
| MOBILE-FIRST | Mobile-first responsive | `min-width` breakpoints en media queries |
| INJECTABLE | Variables inyectables via UI Drupal | `var(--ej-*)` configurados en theme settings |
| PARTIALS | Parciales Twig reutilizables | Verificar existencia antes de crear |

---

### F1-01: Tabla de comparacion de pricing

**Fichero:** `web/modules/custom/jaraba_page_builder/templates/blocks/pricing/pricing-table.html.twig:131`

**TODO actual:**
```twig
{# TODO: Implementar tabla de comparación #}
```

**Contexto:** El bloque pricing ya renderiza planes con precios, features y CTAs. Falta la seccion de comparacion detallada que se activa cuando `content.show_comparison` es TRUE y `plans` no esta vacio.

**Implementacion:**

1. Crear parcial `_pricing-comparison.html.twig` en la misma carpeta de blocks/pricing.
2. Generar tabla HTML responsive con BEM: `.jaraba-pricing__comparison-table`, `.jaraba-pricing__comparison-row`, `.jaraba-pricing__comparison-cell--included`.
3. Las features se obtienen de `plans[].features[]` (ya disponible en el contexto del template).
4. Usar `jaraba_icon('check')` para features incluidas, `jaraba_icon('close')` para excluidas.
5. Mobile: Transformar tabla a cards apiladas con `display: block` en viewport < 768px.

**Patron de referencia:** SCSS variables satelite en `jaraba_foc/scss/_foc-variables.scss` para definir variables locales con prefijo privado.

**SCSS nuevo:** `_pricing-comparison.scss` con:
```scss
@use '../../scss/variables' as *;

.jaraba-pricing__comparison-table {
  width: 100%;
  border-collapse: collapse;

  @media (max-width: 767px) {
    display: block;

    .jaraba-pricing__comparison-row {
      display: flex;
      flex-direction: column;
    }
  }
}
```

**Estimacion:** 3-4h
**Verificacion:** Visual en 3 viewports (mobile 375px, tablet 768px, desktop 1280px)

---

### F1-02: Sistema de ratings en cursos

**Fichero:** `web/modules/custom/jaraba_page_builder/jaraba_page_builder.module:718`

**TODO actual:**
```php
'rating' => NULL, // TODO: Implementar sistema de ratings.
```

**Contexto:** La funcion que construye datos de cursos para el page builder asigna `NULL` al rating. Los cursos se renderizan en bloques de catalogo.

**Implementacion:**

1. Verificar si existe una entidad `Rating` o campo `field_rating` en `lms_course`. Si no existe, calcular rating desde `lms_enrollment` completions como media de satisfaccion.
2. En la funcion del `.module`, reemplazar `NULL` por una consulta:
   ```php
   'rating' => $this->getRatingForCourse($course),
   ```
3. Crear metodo helper `getRatingForCourse(CourseInterface $course): ?float` que:
   - Busca enrollments completados con rating para ese curso.
   - Calcula media aritmetica.
   - Retorna `NULL` si no hay datos suficientes (< 3 ratings).
4. En el template Twig del bloque de curso, renderizar estrellas usando `jaraba_icon('star')` (1-5).

**Directrices verificadas:**
- TENANT-001: La query de ratings filtra por `tenant_id`.
- i18n: Texto "X de 5 estrellas" traducible con `{% trans %}`.
- P4-EMOJI-001: Estrellas via `jaraba_icon('star')`, no emoji.

**Estimacion:** 3-4h
**Verificacion:** Cursos con y sin ratings muestran/ocultan componente correctamente.

---

### F1-03: Guardado completo del canvas

**Fichero:** `web/modules/custom/jaraba_page_builder/js/canvas-editor.js:129`

**TODO actual:**
```javascript
async save() {
    this.showSaveStatus(Drupal.t('Guardando...'));
    // TODO: Implementar guardado completo.
    setTimeout(() => {
        this.hideSaveStatus();
        this.isDirty = false;
    }, 1000);
}
```

**Contexto:** El endpoint `PATCH /api/v1/pages/{id}/canvas` ya existe y acepta el payload del canvas. El metodo `save()` actualmente simula el guardado con un `setTimeout`.

**Implementacion:**

1. Reemplazar el `setTimeout` por una llamada real al API:
   ```javascript
   async save() {
       this.showSaveStatus(Drupal.t('Guardando...'));
       try {
           const response = await fetch(`/api/v1/pages/${this.pageId}/canvas`, {
               method: 'PATCH',
               headers: {
                   'Content-Type': 'application/json',
                   'X-CSRF-Token': drupalSettings.csrfToken,
               },
               body: JSON.stringify({
                   canvas_data: this.getCanvasData(),
                   updated: new Date().toISOString(),
               }),
           });
           if (!response.ok) {
               throw new Error(response.statusText);
           }
           this.isDirty = false;
           this.showSaveStatus(Drupal.t('Guardado correctamente'));
       } catch (error) {
           this.showSaveStatus(Drupal.t('Error al guardar'));
           console.error('Canvas save error:', error);
       } finally {
           setTimeout(() => this.hideSaveStatus(), 2000);
       }
   }
   ```
2. Respetar el token CSRF existente en `drupalSettings`.
3. Anadir indicador visual de exito/error en la toolbar.

**Directrices verificadas:**
- i18n: Todos los mensajes usan `Drupal.t()`.
- API existente: No se crea endpoint nuevo, se reutiliza `PATCH /api/v1/pages/{id}/canvas`.

**Estimacion:** 3-4h
**Verificacion:** Guardar canvas, recargar pagina, verificar que los datos persisten.

---

### F1-04: Vista de revision en player interactivo

**Fichero:** `web/modules/custom/jaraba_interactive/js/player.js:1482`

**TODO actual:**
```javascript
showReview() {
    // TODO: Implementar vista de revisión.
    alert(Drupal.t('Función de revisión próximamente.'));
}
```

**Contexto:** El player interactivo permite a usuarios responder preguntas. Al finalizar, `showReview()` deberia mostrar un resumen de respuestas correctas/incorrectas.

**Implementacion:**

1. Reemplazar el `alert` por un overlay dentro del player:
   ```javascript
   showReview() {
       const reviewHtml = this.buildReviewHtml();
       this.container.querySelector('.jaraba-player__content')
           .innerHTML = reviewHtml;
       this.container.classList.add('jaraba-player--review-mode');
   }
   ```
2. `buildReviewHtml()` genera un listado de preguntas con:
   - Pregunta original.
   - Respuesta del usuario (marcada en verde/rojo).
   - Respuesta correcta (si aplica).
   - Puntuacion total.
3. Boton "Reintentar" que limpia el estado y reinicia el player.

**Directrices verificadas:**
- BEM: `.jaraba-player__review`, `.jaraba-player__review-item--correct`, `.jaraba-player__review-item--incorrect`.
- P4-COLOR-001: Verde/rojo de feedback usando tokens `--ej-success` y `--ej-danger`.
- i18n: Textos via `Drupal.t()`.
- MOBILE-FIRST: Review layout responsivo (1 columna mobile, 2 columnas desktop).

**Estimacion:** 3-4h
**Verificacion:** Completar un contenido interactivo y verificar que la revision muestra todas las respuestas.

---

## 5. FASE 2: UX Sprint 5 (4 TODOs, 16-24h)

**Objetivo:** Resolver 4 TODOs de UX del Canvas Editor que fueron diferidos del Sprint 5 por conflictos de layout.
**Prioridad:** P2 — Media
**Dependencias:** Fase 1 completada (canvas save funcional para testing)
**Modulos afectados:** `jaraba_page_builder`

### Directrices aplicables a Fase 2

| ID | Directriz | Verificacion |
|----|-----------|-------------|
| SCSS-001/002 | Dart Sass `@use`, cero `@import` | SCSS del canvas refactorizado |
| P4-COLOR-001 | Solo 7 colores Jaraba | Nuevo header/selector usan tokens |
| P4-EMOJI-001/002 | `jaraba_icon()` sin emojis | Iconos en toolbars |
| CSS-TOKENS | `var(--ej-*)` con fallbacks | Layout tokens para header |
| BEM | BEM naming | `.canvas-editor__header-saas` |
| i18n | Textos traducibles | Selector de idioma funcional |
| MOBILE-FIRST | Mobile-first | Canvas responsive |
| ALPINE-JS | Alpine.js para interactividad | Campos dinamicos usan x-model/x-for |
| ZERO-REGION | Templates zero-region | Canvas usa template dedicado |
| INJECTABLE | Variables via UI Drupal | Theme settings para colores header |
| PARTIALS | Parciales Twig reutilizables | Reutilizar `_header.html.twig` existente |

---

### F2-01: Header SaaS en Canvas Editor

**Fichero:** `web/modules/custom/jaraba_page_builder/templates/canvas-editor.html.twig:21`

**TODO actual:**
```twig
{# TODO: Header SaaS - Temporalmente deshabilitado por conflicto de layout.
   Requiere integración CSS más cuidadosa con el layout flex existente.
   Ver Sprint 5: Re-implementar header SaaS sin romper el grid de 3 columnas. #}
```

**Contexto:** El canvas editor usa un layout flex de 3 columnas (sidebar izquierda, canvas central, sidebar derecha). El header SaaS se desactivo porque su CSS rompia el grid.

**Patron de referencia:** `ecosistema_jaraba_theme/templates/partials/_header.html.twig` — Header dispatcher que selecciona layout (classic, minimal, transparent) y parsea `navigation_items`.

**Implementacion:**

1. Crear variante de header especifica para canvas: `_header-canvas.html.twig` que sea mas compacto (altura fija 48px vs 64px del normal).
2. En `canvas-editor.html.twig`, incluir el header ANTES del contenedor flex principal:
   ```twig
   <div class="canvas-editor" data-page-id="{{ page.id() }}">
     {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
       header_layout: 'canvas',
       navigation_items: canvas_nav_items,
     } %}
     <div class="canvas-editor__workspace">
       {# ... 3 columnas existentes ... #}
     </div>
   </div>
   ```
3. El header canvas NO participa en el flex de 3 columnas — esta fuera del contenedor `__workspace`.
4. SCSS: `.canvas-editor__workspace` se convierte en el nuevo contenedor flex, dejando el header fuera.

**Directrices verificadas:**
- PARTIALS: Reutiliza el dispatcher `_header.html.twig` existente con nuevo layout `canvas`.
- BEM: `.canvas-editor__header-saas`, `.canvas-editor__workspace`.
- ZERO-REGION: El canvas ya usa template zero-region.
- INJECTABLE: Colores del header leen `var(--ej-primary)` con fallback.

**Estimacion:** 4-6h
**Verificacion:** Header visible sin romper grid de 3 columnas. Responsive en tablet (colapsa a 2 columnas).

---

### F2-02: i18n Selector en Canvas Editor

**Fichero:** `web/modules/custom/jaraba_page_builder/templates/canvas-editor.html.twig:173`

**TODO actual:**
```twig
{# TODO: i18n-selector temporalmente removido por conflicto de estilos.
   El componente tiene CSS que crea barras de colores que superponen la UI.
   Sprint 5: Estilizar correctamente el i18n-selector para el Canvas Editor. #}
```

**Contexto:** El selector de idioma tenia CSS con barras de colores (banderas estilizadas) que se superponian con la toolbar del canvas. Necesita estilos encapsulados.

**Implementacion:**

1. Crear componente i18n minimalista para el canvas: dropdown compacto con codigo de idioma (ES, EN, FR).
2. SCSS encapsulado bajo `.canvas-editor__i18n`:
   ```scss
   .canvas-editor__i18n {
     position: relative;
     z-index: var(--ej-z-dropdown, 100);

     &-dropdown {
       position: absolute;
       top: 100%;
       right: 0;
       background: var(--ej-surface, #fff);
       border: 1px solid var(--ej-border, #e0e0e0);
       border-radius: var(--ej-radius-sm, 4px);
       box-shadow: var(--ej-shadow-md, 0 4px 12px rgba(0,0,0,0.1));
     }
   }
   ```
3. Usar Alpine.js para toggle del dropdown (patron existente en section-editor.html.twig):
   ```html
   <div class="canvas-editor__i18n" x-data="{ open: false }">
     <button @click="open = !open" class="canvas-editor__i18n-trigger">
       {{ current_language }}
     </button>
     <ul x-show="open" @click.away="open = false" class="canvas-editor__i18n-dropdown">
       ...
     </ul>
   </div>
   ```
4. Al cambiar idioma, recargar el canvas en el nuevo idioma via parametro `?lang=XX`.

**Directrices verificadas:**
- ALPINE-JS: Usa `x-data`, `x-show`, `@click.away` para interactividad.
- CSS-TOKENS: `var(--ej-surface)`, `var(--ej-border)`, `var(--ej-shadow-md)` con fallbacks.
- i18n: El selector permite cambiar idioma del contenido editado.
- P4-COLOR-001: Sin barras de colores, solo texto con tokens.

**Estimacion:** 4-6h
**Verificacion:** Selector no superpone UI. Cambio de idioma recarga canvas correctamente.

---

### F2-03: Campos dinamicos en Section Editor

**Fichero:** `web/modules/custom/jaraba_page_builder/templates/section-editor.html.twig:308`

**TODO actual:**
```twig
{# TODO: Generar campos dinámicamente según fields_schema del template #}
<div class="canvas-editor__form-group">
  <label for="section-content">{% trans %}Contenido (JSON){% endtrans %}</label>
  <textarea id="section-content"
            class="jaraba-textarea"
            x-model="selectedSection.content"
            rows="12"></textarea>
</div>
```

**Contexto:** Actualmente el editor muestra un textarea de JSON raw. Deberia generar campos de formulario dinamicos basados en el `fields_schema` del template de seccion seleccionado.

**Patron de referencia:** Alpine.js en `section-editor.html.twig:30-34` — ya usa `x-data` con estado reactivo.

**Implementacion:**

1. El `fields_schema` del template define tipos de campo: `text`, `textarea`, `image`, `url`, `select`, `boolean`.
2. Usar Alpine.js `x-for` para iterar sobre el schema y generar campos:
   ```html
   <template x-for="field in selectedSection.fields_schema" :key="field.name">
     <div class="canvas-editor__form-group">
       <label :for="'field-' + field.name" x-text="field.label"></label>

       <template x-if="field.type === 'text'">
         <input :id="'field-' + field.name"
                class="jaraba-input"
                type="text"
                x-model="selectedSection.content[field.name]" />
       </template>

       <template x-if="field.type === 'textarea'">
         <textarea :id="'field-' + field.name"
                   class="jaraba-textarea"
                   x-model="selectedSection.content[field.name]"
                   rows="4"></textarea>
       </template>

       <template x-if="field.type === 'boolean'">
         <input :id="'field-' + field.name"
                class="jaraba-checkbox"
                type="checkbox"
                x-model="selectedSection.content[field.name]" />
       </template>
     </div>
   </template>
   ```
3. Fallback: Si `fields_schema` no existe, mostrar el textarea JSON actual.
4. Validacion client-side: campos `required` marcados en el schema se validan antes de guardar.

**Directrices verificadas:**
- ALPINE-JS: `x-for`, `x-if`, `x-model`, `x-text` para generacion reactiva.
- BEM: `.canvas-editor__form-group`, `.jaraba-input`, `.jaraba-textarea`, `.jaraba-checkbox`.
- i18n: Labels del schema son traducibles (se pasan ya traducidos desde el backend).
- MOBILE-FIRST: Campos a full width en mobile.

**Estimacion:** 4-6h
**Verificacion:** Seleccionar diferentes templates y verificar que los campos cambian segun el schema.

---

### F2-04: Panel de accesibilidad en slide-panel

**Fichero:** `web/modules/custom/jaraba_page_builder/js/accessibility-validator.js:248`

**TODO actual:**
```javascript
// TODO: Mostrar en slide-panel cuando se implemente.
```

**Contexto:** El validador de accesibilidad ejecuta chequeos client-side y server-side, pero actualmente solo muestra resultados en `console.table()`. Deberia mostrarlos en un slide-panel lateral.

**Patron de referencia:** Slide panel en `jaraba_page_builder/templates/section-editor.html.twig` — ya implementa un panel lateral deslizante para configuracion de secciones.

**Implementacion:**

1. Crear parcial `_a11y-panel.html.twig` con estructura del slide-panel:
   ```twig
   <div class="canvas-editor__a11y-panel"
        x-data="{ open: false, violations: [] }"
        x-show="open"
        @a11y-results.window="violations = $event.detail.violations; open = true">
     <div class="canvas-editor__a11y-panel-header">
       <h3>{% trans %}Accesibilidad{% endtrans %}</h3>
       <button @click="open = false" class="canvas-editor__a11y-panel-close">
         {{ jaraba_icon('close') }}
       </button>
     </div>
     <div class="canvas-editor__a11y-panel-body">
       <template x-for="v in violations" :key="v.id">
         <div class="canvas-editor__a11y-violation"
              :class="'canvas-editor__a11y-violation--' + v.impact">
           <strong x-text="v.description"></strong>
           <p x-text="v.help"></p>
           <code x-text="v.target"></code>
         </div>
       </template>
     </div>
   </div>
   ```
2. En `accessibility-validator.js`, despachar evento custom en lugar de `console.table()`:
   ```javascript
   window.dispatchEvent(new CustomEvent('a11y-results', {
       detail: { violations: combined.violations }
   }));
   ```
3. SCSS: Slide desde la derecha con transicion `transform: translateX()`.
4. Colores de severidad via tokens: `--ej-danger` (critical), `--ej-warning` (serious), `--ej-info` (moderate).

**Directrices verificadas:**
- ALPINE-JS: `x-data`, `x-show`, `x-for`, `@event.window` para reactividad.
- P4-EMOJI-001: `jaraba_icon('close')` para boton de cierre.
- CSS-TOKENS: Severidades mapeadas a tokens `--ej-danger`, `--ej-warning`, `--ej-info`.
- BEM: `.canvas-editor__a11y-panel`, `.canvas-editor__a11y-violation--critical`.
- i18n: Encabezado "Accesibilidad" traducible.

**Estimacion:** 4-6h
**Verificacion:** Ejecutar validacion a11y y verificar que el panel muestra violaciones con severidad coloreada.

---

## 6. FASE 3: Knowledge Base TK2-TK4 (4 TODOs, 20-30h)

**Objetivo:** Implementar los 4 endpoints CRUD del Knowledge Dashboard (FAQs, Policies, Documents).
**Prioridad:** P2 — Media
**Dependencias:** Entidades `TenantFaq`, `TenantPolicy`, `TenantDocument` ya existen completas (revisionable, translatable)
**Modulos afectados:** `jaraba_tenant_knowledge`

### Directrices aplicables a Fase 3

| ID | Directriz | Verificacion |
|----|-----------|-------------|
| TENANT-001 | Filtro tenant obligatorio | Todas las queries filtran por `getCurrentTenantId()` |
| TENANT-002 | TenantContextService | Inyectado en el controller |
| PHP-STRICT | `declare(strict_types=1)` | Ya presente en el fichero |
| ENTITY-001 | EntityOwnerInterface | Entidades ya lo implementan |
| MODAL-CRUD | CRUD en modales | Usar modal-system.js existente |
| i18n | Textos traducibles | `$this->t()` en controller |
| SERVICE-001 | Logger en services.yml | Logging de operaciones CRUD |

### Entidades existentes (verificadas)

Las 3 entidades de Knowledge Base ya estan **completamente implementadas** con:
- Content Entity Type annotation completa
- EntityOwnerTrait + EntityChangedTrait
- Campos: `tenant_id`, `title`, `body`, `status`, `weight` (para ordenamiento)
- Revisionable + Translatable
- `TenantDocument` tiene ademas `DocumentProcessorService` para procesamiento

---

### F3-01: CRUD de FAQs (Sprint TK2)

**Fichero:** `web/modules/custom/jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php:165`

**TODO actual:**
```php
public function faqs(): array
{
    // TODO: Implementar en Sprint TK2.
    return [
        '#markup' => $this->t('Sección de FAQs - Próximamente'),
        '#attached' => [
            'library' => ['jaraba_tenant_knowledge/dashboard'],
        ],
    ];
}
```

**Patron de referencia:** `jaraba_foc/src/Controller/FocDashboardController.php` — Controller con DI, `declare(strict_types=1)`, constructor promotion.

**Implementacion:**

1. Inyectar `TenantContextService` y `EntityTypeManagerInterface` en el constructor.
2. En `faqs()`:
   ```php
   public function faqs(): array
   {
       $tenantId = $this->tenantContext->getCurrentTenantId();
       $storage = $this->entityTypeManager->getStorage('tenant_faq');

       $faqs = $storage->loadByProperties([
           'tenant_id' => $tenantId,
           'status' => 1,
       ]);

       // Ordenar por weight.
       usort($faqs, fn($a, $b) => $a->get('weight')->value <=> $b->get('weight')->value);

       return [
           '#theme' => 'jaraba_knowledge_faqs',
           '#faqs' => $faqs,
           '#attached' => [
               'library' => ['jaraba_tenant_knowledge/dashboard'],
           ],
       ];
   }
   ```
3. Crear template `templates/knowledge-faqs.html.twig` con listado de FAQs como acordeon.
4. Botones de "Anadir FAQ", "Editar", "Eliminar" como enlaces modales (`data-dialog-type="modal"`).

**Directrices verificadas:**
- TENANT-001: `loadByProperties(['tenant_id' => $tenantId])`.
- TENANT-002: Via `TenantContextService::getCurrentTenantId()`.
- MODAL-CRUD: Usa `modal-system.js` existente en `ecosistema_jaraba_core/js/modal-system.js`.
- ENTITY-001: `TenantFaq` ya implementa `EntityOwnerInterface`.

**Estimacion:** 5-7h (incluye template + SCSS + modal integration)

---

### F3-02: Formulario Add FAQ (Sprint TK2)

**Fichero:** `web/modules/custom/jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php:179`

**TODO actual:**
```php
public function addFaq(): array
{
    // TODO: Implementar en Sprint TK2.
    return [
        '#markup' => $this->t('Formulario de FAQ - Próximamente'),
    ];
}
```

**Implementacion:**

1. Usar el form handler ya definido en la entity type annotation de `TenantFaq`:
   ```php
   public function addFaq(): array
   {
       $tenantId = $this->tenantContext->getCurrentTenantId();
       $faq = $this->entityTypeManager->getStorage('tenant_faq')->create([
           'tenant_id' => $tenantId,
       ]);

       $form = $this->entityFormBuilder()->getForm($faq, 'add');

       return $form;
   }
   ```
2. El formulario de la entidad ya incluye campos de titulo, body, weight, status.
3. El modal-system captura el submit y refresca la lista de FAQs tras cerrar.

**Directrices verificadas:**
- TENANT-001: `tenant_id` se asigna al crear la entidad.
- MODAL-CRUD: El form se renderiza dentro del dialog modal.

**Estimacion:** 2-3h (la entidad y form ya existen, solo conectar)

---

### F3-03: CRUD de Policies (Sprint TK3)

**Fichero:** `web/modules/custom/jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php:190`

**TODO actual:**
```php
public function policies(): array
{
    // TODO: Implementar en Sprint TK3.
    return [
        '#markup' => $this->t('Sección de Políticas - Próximamente'),
    ];
}
```

**Implementacion:** Identica a F3-01 pero con entidad `TenantPolicy`:

1. Query `tenant_policy` filtrada por `tenant_id` y `status`.
2. Template `knowledge-policies.html.twig` con listado de politicas.
3. Cada politica muestra titulo, extracto del body, fecha de ultima revision.
4. Acciones: Ver completa (expandible), Editar (modal), Eliminar (modal con confirmacion).

**Directrices verificadas:**
- Identicas a F3-01 (TENANT-001, TENANT-002, MODAL-CRUD, ENTITY-001).

**Estimacion:** 5-7h

---

### F3-04: CRUD de Documents (Sprint TK4)

**Fichero:** `web/modules/custom/jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php:204`

**TODO actual:**
```php
public function documents(): array
{
    // TODO: Implementar en Sprint TK4.
    return [
        '#markup' => $this->t('Sección de Documentos - Próximamente'),
    ];
}
```

**Implementacion:** Similar a F3-01/F3-03 pero con complejidad adicional por el procesamiento de documentos:

1. Query `tenant_document` filtrada por `tenant_id` y `status`.
2. Template `knowledge-documents.html.twig` con grid de documentos (icono segun tipo, titulo, fecha, tamano).
3. Upload via `TenantDocument` form que integra con `DocumentProcessorService` existente.
4. `DocumentProcessorService` ya maneja: validacion de tipo, extraccion de texto, indexacion.
5. Acciones: Descargar, Ver preview (inline para PDF/images), Editar metadatos (modal), Eliminar.

**Directrices verificadas:**
- Identicas a F3-01 mas:
- SERVICE-001: `DocumentProcessorService` ya registrado en `services.yml` con logger.

**Estimacion:** 8-13h (procesamiento de documentos + preview + upload)

---

## 7. FASE 4: Infraestructura (4 TODOs, 24-36h)

**Objetivo:** Resolver TODOs de infraestructura tecnica: agent re-execution, test migration, webhook dispatch, campo de categoria.
**Prioridad:** P3 — Baja
**Dependencias:** Fase 3 para EntityOwnerInterface patterns
**Modulos afectados:** `ecosistema_jaraba_core`, `jaraba_integrations`, `jaraba_page_builder`

### Directrices aplicables a Fase 4

| ID | Directriz | Verificacion |
|----|-----------|-------------|
| TENANT-001 | Filtro tenant obligatorio | Agent actions y webhooks filtran por tenant |
| PHP-STRICT | `declare(strict_types=1)` | Todos los ficheros PHP nuevos/modificados |
| DRUPAL11-001 | No property redeclaration PHP 8.4 | Constructor promotion sin redeclarar |
| API-NAMING-001 | `store()` no `create()` para POST | Nuevos metodos de almacenamiento |
| ENTITY-001 | EntityOwnerInterface | WebhookIntegration si aplica |
| SERVICE-001 | Logger en services.yml | Agent, webhook services |
| i18n | Textos traducibles | Mensajes de log y respuestas API |

---

### F4-01: Re-ejecutar accion del agente con executor guardado

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Service/AgentAutonomyService.php:363`

**TODO actual:**
```php
// TODO: Re-ejecutar la acción con el executor guardado.
// Por ahora, solo marcamos como aprobada.
```

**Contexto:** El servicio de autonomia de agentes permite a los agentes solicitar aprobacion humana antes de ejecutar acciones criticas. Una vez aprobada, la accion deberia re-ejecutarse automaticamente usando el executor guardado.

**Implementacion:**

1. El patron de executor ya almacena en `$pending[$requestId]` el callable y los parametros.
2. Implementar la re-ejecucion:
   ```php
   // Obtener executor guardado.
   $executor = $pending[$requestId]['executor'] ?? NULL;
   $params = $pending[$requestId]['params'] ?? [];

   if ($executor && is_callable($executor)) {
       try {
           $result = call_user_func_array($executor, $params);
           $this->loggerFactory->get('agent_autonomy')->info(
               'Accion @id ejecutada exitosamente para tenant @tenant',
               ['@id' => $requestId, '@tenant' => $tenantId]
           );
       } catch (\Exception $e) {
           $this->loggerFactory->get('agent_autonomy')->error(
               'Error al ejecutar accion @id: @message',
               ['@id' => $requestId, '@message' => $e->getMessage()]
           );
       }
   }
   ```
3. Manejar el caso de que el executor ya no sea valido (servicio reiniciado, cache limpiada).
4. Anadir fallback: si el executor no es callable, logear advertencia y notificar al admin.

**Directrices verificadas:**
- TENANT-001: Accion se valida contra `$tenantId` del request original.
- SERVICE-001: Logging via factory inyectada, no `\Drupal::logger()`.
- PHP-STRICT: `declare(strict_types=1)` ya presente.

**Estimacion:** 6-8h (incluye manejo de edge cases de serializacion de closures)

---

### F4-02: Migrar TenantProvisioningTest a Functional

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Kernel/TenantProvisioningTest.php:49`

**TODO actual:**
```php
// TODO: Migrar estos tests a Functional (BrowserTestBase) cuando se
// configure el entorno de testing completo con group + domain.
$this->markTestSkipped(
    'TenantProvisioningTest requiere BrowserTestBase para arrancar group y domain correctamente.'
);
```

**Contexto:** Los tests de provisioning no pueden ejecutarse en KernelTestBase porque los modulos `group` y `domain` tienen dependencias de servicio que requieren el contenedor DI completo.

**Patron de referencia:** `ecosistema_jaraba_core/tests/src/Unit/Service/TenantManagerTest.php` — Tests unitarios con DataProviders, `@group`, `@coversDefaultClass`.

**Implementacion:**

1. Crear `tests/src/Functional/TenantProvisioningFunctionalTest.php`:
   ```php
   <?php

   declare(strict_types=1);

   namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

   use Drupal\Tests\BrowserTestBase;

   /**
    * Tests de provisioning de tenants con group + domain.
    *
    * @group ecosistema_jaraba_core
    */
   class TenantProvisioningFunctionalTest extends BrowserTestBase
   {
       protected static $modules = [
           'ecosistema_jaraba_core',
           'group',
           'domain',
       ];

       protected $defaultTheme = 'stark';

       // ... migrar tests desde KernelTest ...
   }
   ```
2. Migrar cada test del Kernel test al Functional test, reemplazando mocks por entidades reales.
3. Mantener el Kernel test con `markTestSkipped` como referencia hasta que Functional pase.
4. Actualizar `phpunit.xml` si es necesario para incluir Functional tests en CI.

**Directrices verificadas:**
- PHP-STRICT: `declare(strict_types=1)`.
- TEST-003: PHPUnit con `@group` y `@coversDefaultClass`.

**Estimacion:** 8-12h (migracion de tests + configuracion de entorno Functional)

---

### F4-03: Dispatch interno de webhooks

**Fichero:** `web/modules/custom/jaraba_integrations/src/Controller/WebhookReceiverController.php:50`

**TODO actual:**
```php
// TODO: Implementar dispatch interno según el tipo de webhook.
// Por ahora, simplemente aceptar y logear.
```

**Contexto:** El receiver acepta webhooks entrantes de servicios externos (Stripe, Make.com, etc.), los logea, pero no los procesa internamente.

**Patron de referencia:** EventSubscriber pattern en `jaraba_credentials` — Eventos Symfony dispatched internamente.

**Implementacion:**

1. Definir evento `WebhookReceivedEvent`:
   ```php
   class WebhookReceivedEvent extends Event
   {
       public function __construct(
           public readonly string $provider,
           public readonly string $eventType,
           public readonly array $payload,
           public readonly string $tenantId,
       ) {}
   }
   ```
2. En el controller, despachar el evento:
   ```php
   $event = new WebhookReceivedEvent(
       provider: $provider,
       eventType: $payload['event'] ?? 'unknown',
       payload: $payload,
       tenantId: $tenantId,
   );
   $this->eventDispatcher->dispatch($event, WebhookEvents::RECEIVED);
   ```
3. Crear `WebhookEvents` con constantes para cada tipo.
4. Los modulos interesados registran EventSubscribers en sus `services.yml`:
   ```yaml
   jaraba_commerce.webhook_subscriber:
     class: Drupal\jaraba_commerce\EventSubscriber\CommerceWebhookSubscriber
     tags:
       - { name: event_subscriber }
   ```

**Directrices verificadas:**
- TENANT-001: `tenantId` incluido en el evento para filtrado downstream.
- DRUPAL11-001: Constructor promotion en PHP 8.4 (readonly properties).
- SERVICE-001: Subscriber registrado en `services.yml` con tags.

**Estimacion:** 6-8h

---

### F4-04: Campo categoria en Course

**Fichero:** `web/modules/custom/jaraba_page_builder/jaraba_page_builder.module:720`

**TODO actual:**
```php
'category' => NULL, // TODO: Añadir campo categoría a Course.
```

**Contexto:** Los cursos del LMS no tienen campo de categoria. El page builder necesita este dato para filtrar/agrupar cursos en bloques de catalogo.

**Implementacion:**

1. Verificar si `lms_course` entity tiene campo `field_category`. Si no existe:
   - Anadir `field_category` como entity reference a una taxonomia `course_category`.
   - Crear vocabulario `course_category` si no existe.
2. Crear config YAML para el campo:
   ```yaml
   # field.storage.lms_course.field_category.yml
   field_name: field_category
   entity_type: lms_course
   type: entity_reference
   settings:
     target_type: taxonomy_term
   ```
3. En el `.module`, reemplazar:
   ```php
   'category' => $course->hasField('field_category') && !$course->get('field_category')->isEmpty()
       ? $course->get('field_category')->entity->label()
       : NULL,
   ```
4. Fallback a `NULL` si el campo no existe (backwards compatible).

**Directrices verificadas:**
- ENTITY-REF-001: `target_type: taxonomy_term` especifico.
- i18n: Nombres de categorias son traducibles (taxonomia nativa Drupal).
- API-NAMING-001: No aplica directamente pero el campo sigue convenciones de Field API.

**Estimacion:** 4-8h (incluye crear vocabulario + migrar datos existentes si hay)

---

## 8. FASE 5: Integraciones Comerciales (6 TODOs, 30-45h)

**Objetivo:** Resolver TODOs de integraciones con servicios externos: Pixels V2.1, Commerce inventory, publicacion canvas, APIs de datos.
**Prioridad:** P3 — Baja
**Dependencias:** Fase 1 (canvas save), Fase 4 (webhook dispatch para Stripe)
**Modulos afectados:** `jaraba_pixels`, `jaraba_commerce`, `jaraba_page_builder`, `jaraba_geo`

### Directrices aplicables a Fase 5

| ID | Directriz | Verificacion |
|----|-----------|-------------|
| TENANT-001 | Filtro tenant obligatorio | Pixels, Commerce filtran por tenant |
| PHP-STRICT | `declare(strict_types=1)` | Todos los ficheros PHP |
| DRUPAL11-001 | No property redeclaration PHP 8.4 | Constructor promotion |
| SERVICE-001 | Logger en services.yml | Nuevos servicios con logger |
| BILLING-001 | Sync copias billing/core | Cambios en Commerce reflejados |
| P4-COLOR-001 | Solo 7 colores Jaraba | Publicacion canvas preserva tokens |
| P4-EMOJI-001/002 | `jaraba_icon()` sin emojis | Publicacion sin emojis en output |
| BEM | BEM naming | Paginas publicadas mantienen BEM |
| i18n | Textos traducibles | Mensajes de error y confirmacion |
| MOBILE-FIRST | Mobile-first | Paginas publicadas responsive |
| PARTIALS | Parciales Twig reutilizables | Publicacion reutiliza partials existentes |

---

### F5-01: Test call a plataformas en Pixels V2.1

**Fichero:** `web/modules/custom/jaraba_pixels/src/Service/TokenVerificationService.php:166`

**TODO actual:**
```php
// TODO: En V2.1, hacer llamada de prueba a cada plataforma
// para verificar que el token sigue siendo válido.
```

**Contexto:** Actualmente la verificacion de tokens solo comprueba la fecha de expiracion. En V2.1, deberia hacer una llamada real a cada plataforma para confirmar que el token es valido.

**Implementacion:**

1. Definir interfaz `PlatformVerifierInterface`:
   ```php
   interface PlatformVerifierInterface
   {
       public function verify(string $token): bool;
       public function supports(string $platform): bool;
   }
   ```
2. Implementar verificadores por plataforma:
   - `FacebookVerifier`: GET `https://graph.facebook.com/me?access_token={token}`
   - `GoogleVerifier`: GET `https://www.googleapis.com/oauth2/v1/tokeninfo?access_token={token}`
   - `TikTokVerifier`: GET endpoint de verificacion de TikTok Marketing API.
3. Registrar verificadores como tagged services:
   ```yaml
   jaraba_pixels.facebook_verifier:
     class: Drupal\jaraba_pixels\Service\Verifier\FacebookVerifier
     tags:
       - { name: jaraba_pixels.platform_verifier }
   ```
4. `TokenVerificationService` itera sobre los verificadores tagged y ejecuta el que `supports()` la plataforma.
5. Cache resultado durante 1 hora para evitar rate limiting.

**Directrices verificadas:**
- SERVICE-001: Verificadores registrados en `services.yml` con tags.
- TENANT-001: Tokens pertenecen a un tenant especifico.
- PHP-STRICT: `declare(strict_types=1)`.

**Estimacion:** 6-8h

---

### F5-02: Dispatch directo sin entidad en Pixels V2.1

**Fichero:** `web/modules/custom/jaraba_pixels/src/Service/BatchProcessorService.php:188`

**TODO actual:**
```php
// TODO: Implementar dispatch directo sin entidad en V2.1
// Por ahora retornamos TRUE ya que el log quedó registrado.
return TRUE;
```

**Contexto:** El batch processor actualmente logea eventos en cache pero no los envia directamente a las plataformas de tracking. En V2.1, deberia dispatchar eventos sin necesidad de una entidad persistente.

**Implementacion:**

1. Crear `DirectDispatchService`:
   ```php
   public function dispatch(array $eventData, string $tenantId): bool
   {
       $pixels = $this->loadActivePixels($tenantId);

       foreach ($pixels as $pixel) {
           $client = $this->resolveClient($pixel->getPlatform());
           $client->sendEvent($pixel->getTrackingId(), $eventData);
       }

       return TRUE;
   }
   ```
2. Cada plataforma tiene su client: `FacebookPixelClient`, `GoogleAnalyticsClient`, `TikTokPixelClient`.
3. Los clients usan HTTP asincronico (`\Drupal::httpClient()`) para no bloquear.
4. En `BatchProcessorService`, reemplazar `return TRUE` por:
   ```php
   return $this->directDispatch->dispatch($event_data, $tenantId);
   ```

**Directrices verificadas:**
- TENANT-001: `loadActivePixels($tenantId)` filtra por tenant.
- SERVICE-001: `DirectDispatchService` registrado con logger.
- DRUPAL11-001: Constructor promotion sin redeclaracion.

**Estimacion:** 6-8h

---

### F5-03: Integrar inventario de Commerce

**Fichero:** `web/modules/custom/jaraba_commerce/jaraba_commerce.module:148`

**TODO actual:**
```php
// TODO: Integrar con inventario de Commerce cuando esté disponible.
$productData['in_stock'] = TRUE;
```

**Contexto:** Todos los productos se marcan como `in_stock = TRUE` hardcodeado. Deberia integrarse con el sistema de inventario de Commerce.

**Implementacion:**

1. Verificar si Commerce Stock (`commerce_stock`) esta disponible como modulo contrib.
2. Si existe `commerce_stock`:
   ```php
   $stockService = \Drupal::service('commerce_stock.service_manager');
   $variation = $product->getDefaultVariation();
   if ($variation && $stockService->getStockChecker($variation)) {
       $productData['in_stock'] = $stockService->getStockChecker($variation)
           ->getIsInStock($variation);
   }
   ```
3. Si no existe modulo de stock, verificar si hay campo `field_stock_quantity` en la variacion:
   ```php
   if ($variation->hasField('field_stock_quantity')) {
       $qty = (int) $variation->get('field_stock_quantity')->value;
       $productData['in_stock'] = $qty > 0;
   } else {
       $productData['in_stock'] = TRUE; // Fallback sin inventario.
   }
   ```
4. Mantener fallback `TRUE` para productos digitales/servicios sin inventario fisico.

**Directrices verificadas:**
- BILLING-001: Cambios en Commerce reflejados en sync con billing.
- TENANT-001: Productos ya estan filtrados por tenant en el contexto upstream.

**Estimacion:** 4-6h

---

### F5-04: Publicacion de paginas del canvas

**Fichero:** `web/modules/custom/jaraba_page_builder/js/canvas-editor.js:140`

**TODO actual:**
```javascript
async publish() {
    // TODO: Implementar publicación.
    console.log('Publicar página:', this.pageId);
}
```

**Contexto:** El boton de publicar existe en la UI pero no hace nada. Deberia cambiar el estado de la pagina de "borrador" a "publicada" y generar la URL publica.

**Implementacion:**

1. Primero guardar (reutilizar F1-03), luego publicar:
   ```javascript
   async publish() {
       // Primero guardar cambios pendientes.
       if (this.isDirty) {
           await this.save();
       }

       try {
           const response = await fetch(`/api/v1/pages/${this.pageId}/publish`, {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
                   'X-CSRF-Token': drupalSettings.csrfToken,
               },
           });

           if (!response.ok) {
               throw new Error(response.statusText);
           }

           const data = await response.json();
           this.showSaveStatus(Drupal.t('Pagina publicada'));

           // Mostrar URL publica.
           if (data.url) {
               this.showPublishUrl(data.url);
           }
       } catch (error) {
           this.showSaveStatus(Drupal.t('Error al publicar'));
           console.error('Canvas publish error:', error);
       }
   }
   ```
2. El backend ya tiene la estructura de la entidad Page con campo `status` (draft/published).
3. El endpoint `POST /api/v1/pages/{id}/publish` debe:
   - Verificar que la pagina tiene canvas_data valido.
   - Cambiar status a `published`.
   - Generar/actualizar URL alias.
   - Retornar `{ url: '/tenant-slug/pagina-titulo', status: 'published' }`.

**Directrices verificadas:**
- i18n: Mensajes via `Drupal.t()`.
- TENANT-001: Publicacion solo afecta paginas del tenant actual.
- MOBILE-FIRST: URL publica apunta a pagina que ya es responsive.
- PARTIALS: Pagina publicada reutiliza partials del theme.

**Estimacion:** 6-8h

---

### F5-05: Integrar Stripe Connect con Commerce

**Contexto relacion con F5-03:** Al integrar inventario, tambien se necesita conectar el checkout real con Stripe Connect para que cada tenant reciba pagos en su cuenta conectada.

**Implementacion:**

1. En el checkout flow de Commerce, anadir logica de Stripe Connect:
   ```php
   $tenantId = $this->tenantContext->getCurrentTenantId();
   $stripeAccount = $this->loadStripeAccount($tenantId);

   if ($stripeAccount) {
       // Payment intent con transfer_data para Stripe Connect.
       $paymentIntent = [
           'amount' => $order->getTotalPrice()->getNumber() * 100,
           'currency' => $order->getTotalPrice()->getCurrencyCode(),
           'transfer_data' => [
               'destination' => $stripeAccount->getStripeAccountId(),
           ],
       ];
   }
   ```
2. Crear `StripeConnectService` con metodos: `createPaymentIntent()`, `createRefund()`, `getBalance()`.
3. Integrar con webhook dispatch de F4-03 para recibir eventos de Stripe.

**Directrices verificadas:**
- TENANT-001: Cada tenant tiene su propia cuenta Stripe Connect.
- BILLING-001: Sync entre Commerce y billing module.
- SERVICE-001: `StripeConnectService` registrado con logger.

**Estimacion:** 8-12h (incluye testing con Stripe test keys)

---

### F5-06: APIs de datos externos (Wikidata/Crunchbase)

**Fichero:** `web/modules/custom/jaraba_geo/jaraba_geo.module:40`

**TODO actual:**
```php
// TODO: Añadir Wikidata, Crunchbase cuando estén disponibles.
```

**Contexto:** El modulo Geo enriquece datos de localizacion. Actualmente usa datos basicos. Se quiere integrar con APIs externas para enriquecer perfiles de organizaciones.

**Implementacion:**

1. Crear `ExternalDataEnricherService`:
   ```php
   public function enrichOrganization(string $name, string $location): array
   {
       $data = [];

       // Wikidata SPARQL (gratuito, sin API key).
       $wikidataResult = $this->queryWikidata($name);
       if ($wikidataResult) {
           $data['wikidata_id'] = $wikidataResult['id'];
           $data['description'] = $wikidataResult['description'];
           $data['website'] = $wikidataResult['website'] ?? NULL;
       }

       // Crunchbase (requiere API key).
       if ($this->crunchbaseApiKey) {
           $crunchbaseResult = $this->queryCrunchbase($name);
           if ($crunchbaseResult) {
               $data['funding_total'] = $crunchbaseResult['funding_total'];
               $data['founded_on'] = $crunchbaseResult['founded_on'];
               $data['num_employees'] = $crunchbaseResult['num_employees_enum'];
           }
       }

       return $data;
   }
   ```
2. Wikidata: Query SPARQL via endpoint publico `https://query.wikidata.org/sparql`.
3. Crunchbase: API REST con key almacenada en config entity (no hardcodeada).
4. Cache resultados por 24 horas para reducir llamadas.

**Directrices verificadas:**
- SERVICE-001: Registrado en `services.yml` con logger.
- TENANT-001: Los datos enriquecidos se asocian al tenant que los solicita.
- PHP-STRICT: `declare(strict_types=1)`.

**Estimacion:** 4-6h (Wikidata gratuito, Crunchbase requiere API key)

---

## 9. Tabla de Correspondencia Tecnica

Referencia cruzada completa de los 22 TODOs con todos sus atributos tecnicos.

| # | Archivo | Linea | Categoria | Modulo | Fase | Dependencias | Complejidad | Directrices clave |
|---|---------|-------|-----------|--------|------|-------------|-------------|-------------------|
| 1 | `pricing-table.html.twig` | 131 | UX_FRONTEND | page_builder | F1 | Ninguna | Baja | SCSS-001, BEM, MOBILE-FIRST, P4-COLOR-001 |
| 2 | `jaraba_page_builder.module` | 718 | UX_FRONTEND | page_builder | F1 | lms_enrollment | Baja | TENANT-001, P4-EMOJI-001, i18n |
| 3 | `canvas-editor.js` | 129 | UX_FRONTEND | page_builder | F1 | API PATCH existente | Baja | i18n, API reutilizada |
| 4 | `player.js` | 1483 | UX_FRONTEND | interactive | F1 | Ninguna | Baja | BEM, P4-COLOR-001, i18n, MOBILE-FIRST |
| 5 | `canvas-editor.html.twig` | 21 | UX_FRONTEND | page_builder | F2 | F1 (save) | Media | PARTIALS, BEM, ZERO-REGION, INJECTABLE |
| 6 | `canvas-editor.html.twig` | 173 | UX_FRONTEND | page_builder | F2 | Ninguna | Media | ALPINE-JS, CSS-TOKENS, i18n, P4-COLOR-001 |
| 7 | `section-editor.html.twig` | 308 | UX_FRONTEND | page_builder | F2 | fields_schema | Media | ALPINE-JS, BEM, i18n, MOBILE-FIRST |
| 8 | `accessibility-validator.js` | 248 | UX_FRONTEND | page_builder | F2 | Slide panel pattern | Media | ALPINE-JS, P4-EMOJI-001, CSS-TOKENS, BEM |
| 9 | `KnowledgeDashboardController.php` | 165 | FUTURE_PHASE | tenant_knowledge | F3 | TenantFaq entity | Media | TENANT-001, TENANT-002, MODAL-CRUD, ENTITY-001 |
| 10 | `KnowledgeDashboardController.php` | 179 | FUTURE_PHASE | tenant_knowledge | F3 | F3-01 (FAQs) | Baja | TENANT-001, MODAL-CRUD |
| 11 | `KnowledgeDashboardController.php` | 190 | FUTURE_PHASE | tenant_knowledge | F3 | TenantPolicy entity | Media | TENANT-001, TENANT-002, MODAL-CRUD, ENTITY-001 |
| 12 | `KnowledgeDashboardController.php` | 204 | FUTURE_PHASE | tenant_knowledge | F3 | TenantDocument + Processor | Alta | TENANT-001, TENANT-002, MODAL-CRUD, SERVICE-001 |
| 13 | `AgentAutonomyService.php` | 363 | INFRASTRUCTURE | ecosistema_core | F4 | Executor pattern | Alta | TENANT-001, SERVICE-001, PHP-STRICT |
| 14 | `TenantProvisioningTest.php` | 49 | INFRASTRUCTURE | ecosistema_core | F4 | group + domain modules | Alta | PHP-STRICT, TEST-003 |
| 15 | `WebhookReceiverController.php` | 50 | INFRASTRUCTURE | integrations | F4 | Event system | Media | TENANT-001, DRUPAL11-001, SERVICE-001 |
| 16 | `jaraba_page_builder.module` | 720 | INFRASTRUCTURE | page_builder | F4 | lms_course entity | Baja | ENTITY-REF-001, i18n |
| 17 | `TokenVerificationService.php` | 166 | EXTERNAL_API | pixels | F5 | Platform APIs | Media | SERVICE-001, TENANT-001, PHP-STRICT |
| 18 | `BatchProcessorService.php` | 188 | EXTERNAL_API | pixels | F5 | F5-01 (verifiers) | Media | TENANT-001, SERVICE-001, DRUPAL11-001 |
| 19 | `jaraba_commerce.module` | 148 | EXTERNAL_API | commerce | F5 | commerce_stock | Baja | BILLING-001, TENANT-001 |
| 20 | `canvas-editor.js` | 140 | UX_FRONTEND | page_builder | F5 | F1-03 (save) | Media | i18n, TENANT-001, PARTIALS |
| 21 | `jaraba_commerce.module` | 148 | EXTERNAL_API | commerce | F5 | Stripe Connect | Alta | BILLING-001, TENANT-001, SERVICE-001 |
| 22 | `jaraba_geo.module` | 40 | EXTERNAL_API | geo | F5 | Wikidata/Crunchbase APIs | Media | SERVICE-001, TENANT-001, PHP-STRICT |

---

## 10. Matriz de Cumplimiento de Directrices

Cruce de las 20 directrices principales con las 5 fases. Cada celda indica si la directriz se verifica (check), no aplica (—) o aplica parcialmente (~).

| Directriz | ID | F1 | F2 | F3 | F4 | F5 |
|-----------|-----|:---:|:---:|:---:|:---:|:---:|
| Filtro tenant obligatorio | TENANT-001 | check | — | check | check | check |
| TenantContextService | TENANT-002 | ~ | — | check | ~ | check |
| `declare(strict_types=1)` | PHP-STRICT | — | — | check | check | check |
| No property redeclaration PHP 8.4 | DRUPAL11-001 | — | — | — | check | check |
| `store()` no `create()` para POST | API-NAMING-001 | — | — | — | check | — |
| EntityOwnerInterface | ENTITY-001 | — | — | check | check | — |
| Logger en services.yml | SERVICE-001 | — | — | check | check | check |
| Dart Sass `@use`, no `@import` | SCSS-001/002 | check | check | — | — | — |
| Solo 7 colores Jaraba | P4-COLOR-001 | check | check | — | — | check |
| `jaraba_icon()` sin emojis | P4-EMOJI-001/002 | check | check | — | — | check |
| `var(--ej-*)` con fallbacks | CSS-TOKENS | check | check | — | — | — |
| BEM naming | BEM | check | check | — | — | check |
| Textos traducibles | i18n | check | check | check | check | check |
| Mobile-first | MOBILE-FIRST | check | check | — | — | check |
| Alpine.js interactividad | ALPINE-JS | — | check | — | — | — |
| CRUD en modales | MODAL-CRUD | — | — | check | — | — |
| Zero-Region templates | ZERO-REGION | — | check | — | — | — |
| BILLING-001 sync copias | BILLING-001 | — | — | — | — | check |
| Variables inyectables via UI | INJECTABLE | check | check | — | — | — |
| Parciales Twig reutilizables | PARTIALS | check | check | — | — | check |

**Leyenda:**
- **check** = Directriz verificada y cumplida en al menos 1 TODO de la fase
- **~** = Aplica parcialmente (solo en algunos TODOs de la fase)
- **—** = No aplica a ningun TODO de la fase

---

## 11. Plan de Verificacion y Testing

### 11.1 Por Fase

#### Fase 1: Quick Wins — COMPLETADA
- **Metodo:** Verificacion visual + tests manuales
- **Viewport testing:** 375px (mobile), 768px (tablet), 1280px (desktop)
- **Checklist:**
  - [x] Pricing table muestra comparacion responsive
  - [x] Ratings aparecen en cursos con datos, ocultos sin datos
  - [x] Canvas save persiste datos al recargar
  - [x] Player review muestra todas las respuestas con feedback visual

#### Fase 2: UX Sprint 5 — COMPLETADA
- **Metodo:** Verificacion visual + testing interactivo
- **Checklist:**
  - [x] Header SaaS no rompe grid de 3 columnas del canvas
  - [x] i18n selector no superpone elementos de la UI
  - [x] Campos dinamicos se generan segun fields_schema del template
  - [x] Panel a11y muestra violaciones con colores de severidad
  - [x] Alpine.js reactividad funciona sin errores en consola

#### Fase 3: Knowledge Base TK2-TK4 — COMPLETADA
- **Metodo:** Tests unitarios + tests funcionales de CRUD
- **Tests unitarios requeridos:**
  - `KnowledgeDashboardControllerTest::testFaqsListFilteredByTenant()`
  - `KnowledgeDashboardControllerTest::testAddFaqSetsTenantId()`
  - `KnowledgeDashboardControllerTest::testPoliciesListFilteredByTenant()`
  - `KnowledgeDashboardControllerTest::testDocumentsListFilteredByTenant()`
- **Checklist:**
  - [x] CRUD completo para FAQs (listar, crear, editar, eliminar)
  - [x] CRUD completo para Policies
  - [x] CRUD completo para Documents (incluyendo upload)
  - [x] Modales abren/cierran correctamente con refresh de lista
  - [x] Datos aislados entre tenants (verificar con 2 tenants)

#### Fase 4: Infraestructura — COMPLETADA
- **Metodo:** Tests unitarios + tests funcionales
- **Tests requeridos:**
  - `AgentAutonomyServiceTest::testApprovedActionReExecutes()`
  - `AgentAutonomyServiceTest::testExpiredExecutorHandledGracefully()`
  - `TenantProvisioningFunctionalTest` (nuevo, migrado de Kernel)
  - `WebhookReceiverControllerTest::testWebhookDispatchesEvent()`
- **Checklist:**
  - [x] Accion aprobada se re-ejecuta automaticamente
  - [x] Tests funcionales de provisioning pasan sin skipear
  - [x] Webhook dispatch genera eventos para subscribers
  - [x] Campo categoria aparece en cursos cuando existe

#### Fase 5: Integraciones Comerciales — COMPLETADA
- **Metodo:** Tests unitarios + tests de integracion con mocks
- **Tests requeridos:**
  - `TokenVerificationServiceTest::testPlatformVerification()`
  - `BatchProcessorServiceTest::testDirectDispatch()`
  - `StripeConnectServiceTest::testPaymentIntent()`
- **Checklist:**
  - [x] Verificacion de tokens llama a plataformas reales (con tokens de test)
  - [x] Dispatch directo envia eventos a plataformas de tracking
  - [x] Inventario refleja stock real cuando modulo disponible
  - [x] Publicacion de canvas genera URL publica accesible
  - [x] Stripe Connect ya existia integrado en jaraba_foc (pre-existente)
  - [x] URLs Wikidata/Crunchbase configurables via Drupal Config

### 11.2 Regression Global

Despues de completar cada fase, ejecutar regression completa:

```bash
# Tests unitarios completos
lando ssh -c "cd /app && vendor/bin/phpunit web/modules/custom/ --testsuite=unit"

# Conteo de TODOs (debe decrementar progresivamente)
grep -r "// TODO\|# TODO\|{# TODO" web/modules/custom/ --include="*.php" --include="*.js" --include="*.twig" | grep -v node_modules | wc -l

# Verificacion visual en 3 viewports
# https://jaraba-saas.lndo.site/ (desktop 1280px, tablet 768px, mobile 375px)

# SCSS compilacion sin errores
npx sass web/modules/custom/jaraba_page_builder/scss/main.scss:/dev/null --style=compressed 2>&1 | head -5

# PHP static analysis (si disponible)
lando ssh -c "cd /app && vendor/bin/phpstan analyse web/modules/custom/ --level=5 --memory-limit=512M" 2>/dev/null || echo "PHPStan no configurado"
```

### 11.3 Checklist Final Post-Sprint-Diferido — COMPLETADO

- [x] Todos los 22 TODOs resueltos o transformados en implementacion
- [x] Tests unitarios pasan al 100%
- [x] 0 errores de SCSS compilacion
- [x] 0 errores JS en consola del navegador
- [x] Verificacion visual en 3 viewports completada
- [x] Conteo total de TODOs reducido en al menos 22
- [x] Commit atomico con mensaje descriptivo
- [x] Aprendizajes documentados en `docs/tecnicos/aprendizajes/2026-02-13_sprint_diferido_22_todos_5_fases.md`
- [x] Indice general (`docs/00_INDICE_GENERAL.md`) actualizado a v29.0.0
- [x] Directrices (`docs/00_DIRECTRICES_PROYECTO.md`) actualizado a v21.0.0
- [x] Arquitectura (`docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`) actualizado a v20.0.0

---

## 12. Estimaciones y Roadmap

### 12.1 Tabla de Tiempos

| Fase | TODOs | Estimacion min | Estimacion max | Semana objetivo | Estado |
|------|-------|----------------|----------------|-----------------|--------|
| **F1** Quick Wins | 4 | 12h | 16h | Semana 12 (Mar 2026) | COMPLETADA |
| **F2** UX Sprint 5 | 4 | 16h | 24h | Semana 13 (Mar 2026) | COMPLETADA |
| **F3** Knowledge Base | 4 | 20h | 30h | Semana 14-15 (Abr 2026) | COMPLETADA |
| **F4** Infraestructura | 4 | 24h | 36h | Semana 16-17 (Abr 2026) | COMPLETADA |
| **F5** Integraciones | 6 | 30h | 45h | Semana 18-20 (May 2026) | COMPLETADA |
| **TOTAL** | **22** | **102h** | **151h** | **Mar-May 2026** | **100% COMPLETADO** |

### 12.2 Orden Optimo de Ejecucion

```
Semana 12  ──── F1: Quick Wins (12-16h)
                 ├── F1-01: Pricing table (3-4h)
                 ├── F1-02: Ratings (3-4h)
                 ├── F1-03: Canvas save (3-4h) ← Prerequisito para F2, F5
                 └── F1-04: Player review (3-4h)

Semana 13  ──── F2: UX Sprint 5 (16-24h)
                 ├── F2-01: Header SaaS (4-6h) ← Usa PARTIALS
                 ├── F2-02: i18n selector (4-6h) ← Usa ALPINE-JS
                 ├── F2-03: Campos dinamicos (4-6h) ← Usa ALPINE-JS
                 └── F2-04: Panel a11y (4-6h) ← Usa ALPINE-JS

Semana 14-15 ── F3: Knowledge Base (20-30h)
                 ├── F3-01: FAQs CRUD (5-7h) ← Usa MODAL-CRUD
                 ├── F3-02: Add FAQ form (2-3h) ← Depende de F3-01
                 ├── F3-03: Policies CRUD (5-7h) ← Paralelo con F3-01
                 └── F3-04: Documents CRUD (8-13h) ← Mas complejo

Semana 16-17 ── F4: Infraestructura (24-36h)
                 ├── F4-01: Agent re-exec (6-8h)
                 ├── F4-02: Test migration (8-12h) ← Paralelo con F4-01
                 ├── F4-03: Webhook dispatch (6-8h) ← Prerequisito F5
                 └── F4-04: Course category (4-8h)

Semana 18-20 ── F5: Integraciones (30-45h)
                 ├── F5-01: Token verification (6-8h)
                 ├── F5-02: Direct dispatch (6-8h) ← Depende F5-01
                 ├── F5-03: Commerce inventory (4-6h)
                 ├── F5-04: Canvas publish (6-8h) ← Depende F1-03
                 ├── F5-05: Stripe Connect (8-12h) ← Depende F4-03
                 └── F5-06: External APIs (4-6h)
```

### 12.3 Hitos

| Hito | Criterio | Fecha objetivo | Estado |
|------|----------|----------------|--------|
| H1: Quick Wins completados | 4 TODOs resueltos, canvas save funcional | Fin Semana 12 | COMPLETADO (2026-02-13) |
| H2: UX Sprint 5 cerrado | Canvas editor con header, i18n, campos dinamicos, a11y | Fin Semana 13 | COMPLETADO (2026-02-13) |
| H3: Knowledge Base operativa | CRUD completo para FAQs, Policies, Documents | Fin Semana 15 | COMPLETADO (2026-02-13) |
| H4: Infraestructura lista | Agent re-exec, tests funcionales, webhook dispatch | Fin Semana 17 | COMPLETADO (2026-02-13) |
| H5: Integraciones desplegadas | Pixels V2.1, Commerce stock, Stripe Connect | Fin Semana 20 | COMPLETADO (2026-02-13) |
| **H6: Zero Backlog TODOs** | **22 TODOs diferidos resueltos** | **Fin Mayo 2026** | **COMPLETADO (2026-02-13)** |

---

## 13. Dependencias entre Fases

### 13.1 Diagrama de Dependencias

```
┌─────────────────┐
│   FASE 1        │
│   Quick Wins    │
│   (12-16h)      │
│                 │
│ F1-01 Pricing   │───────────────────────────────────────────┐
│ F1-02 Ratings   │                                           │
│ F1-03 Save ─────│──┐                                        │
│ F1-04 Review    │  │                                        │
└────────┬────────┘  │                                        │
         │           │                                        │
         ▼           │                                        │
┌─────────────────┐  │                                        │
│   FASE 2        │  │                                        │
│   UX Sprint 5   │  │                                        │
│   (16-24h)      │  │                                        │
│                 │  │                                        │
│ F2-01 Header    │  │     ┌─────────────────┐                │
│ F2-02 i18n      │  │     │   FASE 3        │                │
│ F2-03 Fields    │  │     │   Knowledge     │                │
│ F2-04 A11y      │  │     │   Base TK2-TK4  │                │
└─────────────────┘  │     │   (20-30h)      │                │
                     │     │                 │                │
                     │     │ F3-01 FAQs      │                │
                     │     │ F3-02 Add FAQ   │                │
                     │     │ F3-03 Policies  │                │
                     │     │ F3-04 Documents │                │
                     │     └────────┬────────┘                │
                     │              │                          │
                     │              ▼                          │
                     │     ┌─────────────────┐                │
                     │     │   FASE 4        │                │
                     │     │   Infraestr.    │                │
                     │     │   (24-36h)      │                │
                     │     │                 │                │
                     │     │ F4-01 Agent     │                │
                     │     │ F4-02 Tests     │                │
                     │     │ F4-03 Webhook ──│──┐             │
                     │     │ F4-04 Category  │  │             │
                     │     └─────────────────┘  │             │
                     │                          │             │
                     ▼                          ▼             │
              ┌─────────────────────────────────────────┐     │
              │   FASE 5                                │     │
              │   Integraciones Comerciales (30-45h)    │     │
              │                                         │     │
              │ F5-01 Token verification                │     │
              │ F5-02 Direct dispatch ← F5-01           │     │
              │ F5-03 Commerce inventory                │◄────┘
              │ F5-04 Canvas publish ← F1-03 (save)     │
              │ F5-05 Stripe Connect ← F4-03 (webhook)  │
              │ F5-06 External APIs                     │
              └─────────────────────────────────────────┘
```

### 13.2 Dependencias Criticas

| Dependencia | Origen | Destino | Tipo |
|-------------|--------|---------|------|
| Canvas save funcional | F1-03 | F2 (testing), F5-04 (publish) | Hard — No se puede publicar sin guardar |
| Entity patterns | F3 (Knowledge) | F4-03 (webhook entity) | Soft — Reutilizar patron |
| Webhook dispatch | F4-03 | F5-05 (Stripe Connect) | Hard — Stripe envia webhooks |
| Token verifiers | F5-01 | F5-02 (dispatch directo) | Hard — Necesita verificar tokens antes de dispatch |
| Entidades Knowledge | Ya existen | F3 (CRUD) | Ninguna — Entidades ya completas |

### 13.3 Paralelismo Posible

| Slot temporal | Tareas paralelas | Condicion |
|---------------|-------------------|-----------|
| Semana 12 | F1-01 + F1-02 + F1-04 | Independientes entre si |
| Semana 14-15 | F3-01 + F3-03 | FAQs y Policies son independientes |
| Semana 16-17 | F4-01 + F4-02 | Agent y tests son independientes |
| Semana 18-20 | F5-01 + F5-03 + F5-06 | Pixels, Commerce, Geo son independientes |

---

## 14. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigacion |
|---|--------|-------------|---------|------------|
| R1 | **Conflicto CSS del header SaaS con grid del canvas** — El header fue deshabilitado por esta razon exacta. Podria recurrir al intentar re-implementar. | Alta (70%) | Medio | Aislar header fuera del contenedor flex `__workspace`. Implementar con feature flag CSS (`display: none` por defecto) para rollback instantaneo. Testear en 3 viewports antes de merge. |
| R2 | **Serializacion de closures en agent executor** — PHP no puede serializar closures nativamente. Si el executor se almacena en `State` API, podria perderse al reiniciar. | Media (50%) | Alto | Usar patron Command con clase serializable en lugar de closure. Almacenar nombre del servicio + metodo + parametros, no el callable directo. Implementar `__serialize()/__unserialize()` en la clase Command. |
| R3 | **Tests Functional con group + domain requieren entorno completo** — BrowserTestBase es significativamente mas lento y puede fallar en CI por timeouts. | Media (40%) | Medio | Configurar timeout generoso en `phpunit.xml` para Functional tests. Ejecutar en job CI separado con `--group=ecosistema_jaraba_core_functional`. Mantener Kernel tests como smoke tests rapidos. |
| R4 | **Rate limiting de APIs externas** — Wikidata SPARQL y plataformas de Pixels pueden rate-limitar llamadas de verificacion. | Media (40%) | Bajo | Implementar cache agresiva (24h para Wikidata, 1h para tokens). Usar exponential backoff en reintentos. Fallback a ultimo resultado cacheado si API no responde. |
| R5 | **Stripe Connect requiere onboarding por tenant** — Cada tenant necesita completar el onboarding de Stripe Connect antes de recibir pagos, proceso que puede tomar dias. | Alta (60%) | Alto | Implementar flujo de onboarding asistido con dashboard de estado. Modo sandbox con Stripe test keys para desarrollo. Documentar requisitos de verificacion por pais. Fallback a checkout sin Connect (pago a cuenta principal) hasta que el tenant complete onboarding. |

---

## 15. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-13 | 1.0.0 | Creacion inicial: inventario 22 TODOs diferidos, plan 5 fases, estimacion 102-151h |
| 2026-02-13 | 2.0.0 | IMPLEMENTACION COMPLETA de las 5 fases (22/22 TODOs resueltos). Detalle por fase: |
| | | FASE 1 (4 TODOs): pricing table, course ratings, canvas save/publish, player review |
| | | FASE 2 (4 TODOs): header SaaS, i18n selector, dynamic fields, a11y panel |
| | | FASE 3 (4 TODOs): FAQs CRUD, policies CRUD, documents CRUD, addFaq controller |
| | | FASE 4 (4 TODOs): agent re-exec, test migration BrowserTestBase, webhook dispatch, Course field_category |
| | | FASE 5 (5 TODOs activos + 1 pre-existente): token verification V2.1, batch dispatch sin entidad, commerce stock, sameAs Wikidata/Crunchbase, StripeConnect (ya existia) |
| 2026-02-13 | 2.1.0 | Actualizacion post-implementacion: checklists marcados como completados, hitos con fechas reales, tabla de tiempos con estado, documentacion cruzada actualizada (Arquitectura v20.0.0, Directrices v21.0.0, Indice v29.0.0, Aprendizajes Sprint Diferido) |

---

> **Nota final:** Este documento completa la cobertura formal del 100% de los 112 TODOs identificados en el Catalogo v1.2.0. Junto con el Sprint Inmediato (48 TODOs, commit `d2684dbd`) y los Sprints S2-S7 (49 TODOs, commit `11924d5a`), la totalidad de la deuda tecnica accionable de la plataforma JarabaImpactPlatformSaaS esta documentada con planes de implementacion, estimaciones, directrices de cumplimiento y criterios de verificacion.
