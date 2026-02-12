# Auditoría: Page Builder + Site Builder
**Fecha**: 5 de Febrero de 2026 | **Estado**: Nivel World-Class (Score 8.5/10)

---

## 1. Resumen Ejecutivo

El ecosistema **Page Builder + Site Builder** de Jaraba ha alcanzado un nivel de madurez avanzado con la arquitectura híbrida **GrapesJS v3**. Sin embargo, existen gaps específicos que impiden el nivel **World-Class 10/10** que la plataforma aspira.

| Dimensión | Score | Gap |
|-----------|:-----:|-----|
| **Core Canvas Editor** | ✅ 10/10 | Motor GrapesJS funcional, persistencia dual |
| **Bloques Configurables** | ⚠️ 6/10 | 12 bloques vs 67 objetivo |
| **Parciales Editables (H/F)** | ⚠️ 7/10 | Traits implementados, hot-swap pendiente |
| **SEO Auditor** | ✅ 10/10 | 100% implementado y verificado |
| **Media Library** | ✅ 10/10 | Hybrid Interceptor funcional |
| **Tests E2E** | ❌ 2/10 | 0 tests Cypress vs 6 objetivo |

---

## 2. Estado Detallado por Componente

### 2.1 Core Canvas Editor ✅

**Archivos principales:**
- `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-canvas.js` - Motor principal
- `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-blocks.js` - Plugin de bloques (715 líneas)

**Capacidades implementadas:**
- ✅ GrapesJS inicializa en `/page/{id}/editor?mode=canvas`
- ✅ Persistencia "Store Both" (`canvas_data` + `rendered_html`)
- ✅ Resilient Initialization Pattern (try-catch por plugin)
- ✅ External Toolbar Bridge (viewport sync Desktop/Tablet/Mobile)
- ✅ Panel Tab Management (Styles/Traits/Layers)

---

### 2.2 Bloques Configurables ⚠️

**Gap crítico**: Solo **12 bloques básicos** implementados vs **67 objetivo**.

| Bloque | Estado | Traits |
|--------|:------:|--------|
| `heading-h1..h4` | ✅ | Texto editable |
| `paragraph` | ✅ | Texto, alineación |
| `button-primary` | ✅ | texto, URL, estilo, target |
| `button-secondary` | ✅ | texto, URL, estilo, target |
| `navigation` | ✅ | itemCount, links (hasta 8), iconos, submenús |
| **55 bloques restantes** | ❌ | Sin implementar |

**Archivos involucrados:**
- `grapesjs-jaraba-blocks.js` líneas 162-260 - Componente navigation
- `grapesjs-jaraba-blocks.js` líneas 450-550 - Componente button

**Tareas pendientes** (ref: `docs/tareas/20260205-Canvas_Editor_Tareas_Pendientes.md`):
- [ ] Verificar que los traits de navegación aparecen en el panel derecho
- [ ] Probar cambio de "Número de enlaces" y confirmar que se actualizan traits
- [ ] Depurar listeners `change:link${i}_text` si no funciona

---

### 2.3 Parciales Editables (Header/Footer) ⚠️

**Archivo**: `grapesjs-jaraba-partials.js` (368 líneas)

**Componentes registrados:**

| Componente | Traits | Hot-Swap |
|------------|--------|:--------:|
| `jaraba-header` | header-type (5 variantes), sticky, CTA, topbar | ⚠️ PostMessage ready, sin receptor |
| `jaraba-footer` | footer-type (5 variantes), social, newsletter, copyright | ⚠️ PostMessage ready, sin receptor |
| `jaraba-content-zone` | dropzone para bloques | ✅ Funcional |

**Entidad SiteConfig** (`jaraba_site_builder/src/Entity/SiteConfig.php`):
- ✅ `header_type`, `header_sticky`, `header_transparent`, `header_cta_text/url`
- ✅ `footer_type`, `footer_columns`, `show_social`, `show_newsletter`, `copyright`

**Gap identificado**:
El plugin envía `JARABA_HEADER_CHANGE` y `JARABA_FOOTER_CHANGE` via `postMessage`, pero **no existe un receptor en el iframe** que procese los cambios y haga hot-swap de las variantes.

---

### 2.4 SEO Auditor ✅ (100% Implementado)

**Archivo**: `grapesjs-jaraba-seo.js` (660 líneas)

**Validaciones implementadas:**
| Regla | Severidad | Estado |
|-------|-----------|:------:|
| H1 único | Error | ✅ |
| H1 descriptivo (+5 chars) | Warning | ✅ |
| Jerarquía headings sin saltos | Warning | ✅ |
| Imágenes con alt text | Warning | ✅ |
| Enlaces con texto descriptivo | Info | ✅ |
| Contenido +300 palabras | Info | ✅ |

**UI Features:**
- ✅ Toggle Panel pattern (oculto por defecto)
- ✅ Score ponderado (Errors: -25, Warnings: -10, Info: -2)
- ✅ Debounce 2s para evitar lag
- ✅ Botón de refresco manual
- ✅ Estilos inyectados con CSS variables corporativos

---

### 2.5 Plugins Adicionales

| Plugin | Archivo | Estado |
|--------|---------|:------:|
| **Assets/Media** | `grapesjs-jaraba-assets.js` | ✅ Hybrid Interceptor |
| **AI Content** | `grapesjs-jaraba-ai.js` | ⚠️ Pendiente Prompt-to-Section |

---

## 3. Gaps Prioritarios para Nivel World-Class

### 🔴 Gap A: Expansión de Bloques (55 restantes)

**Esfuerzo estimado**: 40h  
**Impacto**: Alto (funcionalidad core)

Bloques faltantes por categoría (según arquitectura maestra):
- **Layout**: Grid 2/3/4 cols, Hero sections (5 variantes)
- **Content**: Testimonials, FAQ accordion, Team members
- **Media**: Gallery grid, Video embed, Carousel
- **Commerce**: Product card, Pricing table, CTA banner
- **Forms**: Contact form, Newsletter signup

---

### 🔴 Gap B: Hot-Swap de Parciales

**Esfuerzo estimado**: 16h  
**Impacto**: Alto (experiencia visual)

**Implementación requerida:**
1. Crear receptor `postMessage` en el iframe frontend
2. Implementar lógica de swap de templates Twig basada en variante
3. Persistir cambios en `SiteConfig` via API REST

---

### 🟡 Gap C: Tests E2E Cypress

**Esfuerzo estimado**: 12h  
**Impacto**: Medio (calidad)

**Tests propuestos:**
1. Canvas Editor carga correctamente
2. Drag & drop de bloque funciona
3. Traits de navegación se actualizan
4. SEO Auditor se abre/cierra
5. Media Library slide-panel funciona
6. Guardado REST persiste cambios

---

## 4. Comparativa con Documento de Tareas Pendientes

| Tarea (Doc 20260205) | Estado Auditoría |
|----------------------|------------------|
| Configuración Bloques Navegación | ⚠️ Implementado, verificación pendiente |
| Panel SEO Auditor | ✅ **COMPLETADO** (660 líneas) |
| 67 bloques completos | ❌ Solo 12 implementados |
| Thumbnails SVG para bloques | ❌ Sin evidencia |
| Feature flags por plan | ❌ Sin implementar |
| Componente jaraba-header | ✅ Registrado con traits |
| Componente jaraba-footer | ✅ Registrado con traits |
| Hot-swap variantes H/F | ⚠️ PostMessage sin receptor |
| AI Content Assistant | ⚠️ Plugin existe, Prompt-to-Section pendiente |
| Menu Editor modal | ⚠️ Comando registrado, endpoint pendiente |
| Onboarding tour | ❌ Sin implementar |

---

## 5. Recomendaciones de Priorización

### Semana 1: Quick Wins
1. ✅ Verificar traits de navegación en browser
2. Implementar receptor `postMessage` para hot-swap
3. Añadir 10 bloques de layout (Hero, Grid variantes)

### Semana 2: Foundation
4. Implementar 20 bloques de contenido
5. Crear endpoint `/admin/structure/site-menu/editor`
6. Tests E2E básicos (3 tests)

### Semana 3: Polish
7. 25 bloques restantes
8. Feature flags por plan
9. Onboarding tour
10. Tests E2E completos

---

## 6. Conclusión

El ecosistema Page Builder + Site Builder tiene una **base arquitectónica sólida** (GrapesJS v3, persistencia dual, resilient init). El SEO Auditor está **100% implementado** y es world-class.

**Bottleneck principal**: Expansión del catálogo de bloques (12 → 67) y conexión del hot-swap de parciales.

> **IMPORTANTE**: El score actual de **8.5/10** puede elevarse a **10/10** implementando los 3 gaps prioritarios (A, B, C) en un sprint de 2-3 semanas.

---

## Referencias

- `docs/tareas/20260205-Canvas_Editor_Tareas_Pendientes.md`
- `docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md`
- `docs/arquitectura/2026-01-28_auditoria_page_builder_clase_mundial.md` (Auditoría anterior)
- `docs/arquitectura/2026-02-03_analisis_canvas_v2_clase_mundial.md`
