# 🚀 Plan de Elevación a Clase Mundial: Page Builder GrapesJS

> **Tipo:** Plan de Implementación  
> **Versión:** 1.0  
> **Fecha:** 2026-02-06 08:00  
> **Estado:** Pendiente Aprobación  
> **Objetivo:** Elevar Canvas Editor de Score 9.2/10 → 9.8/10

---

## 1. Resumen Ejecutivo

| Métrica | Actual | Objetivo |
|---------|--------|----------|
| **Bloques operativos** | 22 | 37 (+15) |
| **Tests E2E canvas** | 6 | 10 (+4) |
| **Score de madurez** | 9.2/10 | 9.8/10 |
| **Cobertura funcional** | 33% | 55% |
| **Tiempo estimado** | - | 12-16h |

---

## 2. Análisis del Estado Actual

### 2.1 Documentación Revisada

| Documento | Ubicación | Conclusión |
|-----------|-----------|------------|
| Canvas v2 Análisis | `2026-02-03_analisis_canvas_v2_clase_mundial.md` | Full Page Canvas v2 aprobado |
| Especificación GrapesJS | `2026-02-05_especificacion_grapesjs_saas.md` | 793 líneas de spec técnica |
| Arquitectura Theming | `2026-02-05_arquitectura_theming_saas_master.md` | v2.1 Federated Tokens |
| Auditoría Madurez | KI `jaraba_visual_builder_ecosystem` | Score 9.2/10 ✅ |

### 2.2 Infraestructura Existente

- **Plugin de Bloques**: `grapesjs-jaraba-blocks.js` (1104 líneas)
- **Bloques Operativos**: 22 de 67 (33%)
  - 12 básicos (tipografía, botones, navigation, divider, spacer)
  - 3 layout (grid 2/3/4 columnas)
  - 3 hero (centered, split, video)
  - 4 contenido (testimonial, FAQ accordion, team member, feature cards)
- **Tests E2E**: 6 test cases específicos de canvas-editor

---

## 3. Gaps Identificados

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| **A** | Expansión catálogo: 15 bloques de alto impacto | 8h | 🔴 Alta |
| **B** | Command Palette (⌘K) para acceso rápido | 4h | 🟡 Media |
| **C** | Tests E2E adicionales (persistencia, traits) | 3h | 🟡 Media |
| **D** | Polishing UX (tooltips, atajos teclado) | 2h | 🟢 Baja |

---

## 4. Plan de Implementación

### 4.1 Sprint 1: Expansión de Bloques (Gap A) - 8h

#### Modificar `grapesjs-jaraba-blocks.js`

**CTA Blocks (3 nuevos):**
| ID | Nombre | Descripción |
|----|--------|-------------|
| `cta-centered` | CTA Centrado | Título grande, descripción, botón central |
| `cta-split` | CTA 50/50 | Con imagen lateral |
| `cta-banner` | CTA Banner | Horizontal sticky-ready con urgencia |

**Stats Blocks (3 nuevos):**
| ID | Nombre | Descripción |
|----|--------|-------------|
| `stats-counter` | Contador | Contador animado con 4 métricas |
| `stats-progress` | Progreso | Barras de progreso visual |
| `stats-comparison` | Comparación | Antes/después visual |

**Pricing Blocks (3 nuevos):**
| ID | Nombre | Descripción |
|----|--------|-------------|
| `pricing-single` | Precio Individual | Card de precio destacada |
| `pricing-comparison` | Comparativa | Tabla de 3 planes |
| `pricing-toggle` | Con Toggle | Mensual/Anual animado |

**Contact Blocks (3 nuevos):**
| ID | Nombre | Descripción |
|----|--------|-------------|
| `contact-form` | Formulario | Formulario de contacto premium |
| `contact-info` | Info Contacto | Teléfono, email, dirección, mapa |
| `contact-cta` | CTA Calendario | Con integración calendario |

**Media Blocks (3 nuevos):**
| ID | Nombre | Descripción |
|----|--------|-------------|
| `image-gallery` | Galería | Responsive tipo Masonry |
| `video-embed` | Video Embed | YouTube/Vimeo con overlay |
| `image-text-overlay` | Imagen+Texto | Imagen con texto superpuesto |

---

### 4.2 Sprint 2: Command Palette (Gap B) - 4h

#### Nuevo archivo: `grapesjs-jaraba-command-palette.js`

**Características:**
- Atajo `⌘K` / `Ctrl+K` para abrir
- Búsqueda fuzzy de bloques, comandos, traits
- Historial de acciones recientes
- Categorías: Bloques, Acciones, Estilos, SEO

---

### 4.3 Sprint 3: Tests E2E (Gap C) - 3h

#### Modificar `canvas-editor.cy.js`

| Test | Descripción |
|------|-------------|
| Test 4: Trait Updates | Modificar trait de botón, verificar actualización |
| Test 5: REST Persistence | Guardar, verificar endpoint API |
| Test 6: Interactive Block | Verificar funcionalidad FAQ accordion |
| Test 7: Design Tokens | Verificar uso de `var(--ej-*)` |

---

## 5. Archivos Afectados

| Acción | Archivo | Cambios |
|--------|---------|---------|
| MODIFY | `grapesjs-jaraba-blocks.js` | +15 bloques (~600 líneas) |
| NEW | `grapesjs-jaraba-command-palette.js` | Plugin (~300 líneas) |
| MODIFY | `jaraba_page_builder.libraries.yml` | Registrar librería |
| MODIFY | `canvas-editor.cy.js` | +4 test cases |

---

## 6. Estándares Técnicos

| Estándar | Requisito |
|----------|-----------|
| **BEM Naming** | `jaraba-{bloque}__{elemento}--{modificador}` |
| **Design Tokens** | Uso de `var(--ej-*, $fallback)` |
| **i18n** | Textos en `Drupal.t()` |
| **Interactividad** | Dual Architecture (script + Drupal Behavior) |

---

## 7. Plan de Verificación

### 7.1 Tests Automatizados

```bash
cd /home/PED/JarabaImpactPlatformSaaS/tests/e2e
npm run cypress:run -- --spec "cypress/e2e/canvas-editor.cy.js"
```

### 7.2 Verificación Manual

1. Navegación: `https://jaraba-saas.lndo.site/es/page/17/editor?mode=canvas`
2. Verificar 5 nuevas categorías en panel de bloques
3. Probar Command Palette con `Ctrl+K`
4. Verificar persistencia al guardar y recargar

---

## 8. Referencias

- [Análisis Canvas v2](./2026-02-03_analisis_canvas_v2_clase_mundial.md)
- [Especificación GrapesJS](./2026-02-05_especificacion_grapesjs_saas.md)
- [Arquitectura Theming](./2026-02-05_arquitectura_theming_saas_master.md)
- [Auditoría Page Builder](./2026-02-05_auditoria_page_site_builder_clase_mundial.md)

---

> **Estado**: Pendiente aprobación para iniciar implementación.
