# 🏗️ JARABA CANVAS v2 - FULL PAGE VISUAL EDITOR

> **Estado**: 📋 Plan Aprobado (2026-02-03)  
> **Inversión**: €9,600-12,400 (120-155h en 6 sprints)  
> **ROI**: 237-329% primer año  
> **Payback**: 3-4 meses  

---

## 1. Resumen Ejecutivo

El **Jaraba Canvas v2** representa la evolución del editor visual de página de un editor de **contenido de página** a un editor de **página completa** (Full Page Canvas) con header, navegación y footer editables in-place.

### Score Actual vs Objetivo

| Dimensión | Actual | Objetivo | Gap |
|-----------|--------|----------|-----|
| Canvas Visual Editor | 7.5/10 | 9.8/10 | **2.3** |
| Competitividad Webflow/Framer | 6.5/10 | 9.5/10 | **3.0** |

### Decisión Estratégica

✅ **APROBADO**: Implementar Full Page Canvas v2 según especificación v2 con adiciones clase mundial.

---

## 2. Propuesta de Valor

### Diferenciación vs Competencia

| Feature | Webflow | Framer | Elementor | **Jaraba** |
|---------|---------|--------|-----------|------------|
| Full Page Canvas | ✅ | ✅ | ❌ | ✅ |
| Header/Footer In-Line | ✅ | ❌ | ❌ | ✅ (v2) |
| Multi-tenant Nativo | ❌ | ❌ | ❌ | ✅ |
| Verticales de Negocio | ❌ | ❌ | ❌ | ✅ |
| Design Tokens UI | ✅ | ❌ | ❌ | ✅ |
| IA Generativa Nativa | ❌ | ✅ | ❌ | ✅ |

---

## 3. Arquitectura Técnica

### 3.1 Zonas del Canvas

```
┌────────────────────────────────────────────────────────────────┐
│ TOP BAR (opcional): Promociones, multi-idioma, login          │
├────────────────────────────────────────────────────────────────┤
│ HEADER: Logo + Navegación + CTA primario                      │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│              CONTENT BODY (Drag-Drop Bloques)                  │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│ FOOTER: Columnas + Social + Newsletter + Legal                │
└────────────────────────────────────────────────────────────────┘
```

### 3.2 Componentes GrapesJS

| Componente | `draggable` | `removable` | Persistencia |
|------------|-------------|-------------|--------------|
| `jaraba-header` | false | false | `site_header_config` |
| `jaraba-footer` | false | false | `site_footer_config` |
| `jaraba-content-zone` | zona | true | `page_content` |
| Bloques contenido | true | true | `page_content` |

### 3.3 Persistencia Dual

```yaml
# Cambios GLOBALES (afectan todo el sitio)
site_header_config:
  - header_type, sticky, topbar_enabled, cta_text

site_footer_config:
  - footer_type, show_social, show_newsletter

site_menu:
  - items: [{label, url, children}]

# Cambios LOCALES (por página)
page_content:
  - grapesjs_html, grapesjs_css, grapesjs_components
```

---

## 4. Roadmap de Implementación

### 4.1 Timeline

| Sprint | Semanas | Foco | Horas |
|--------|---------|------|-------|
| **1** | 1-2 | GrapesJS Core + Storage | 25-30h |
| **2** | 3-4 | 67 Bloques + Design Tokens | 25-35h |
| **3** | 5-6 | Header Editable + Menú | 25-30h |
| **4** | 7-8 | Footer + Premium Blocks | 20-25h |
| **5** | 9-10 | Renderizado Zero Region | 15-20h |
| **6** | 11-12 | AI + Polish + E2E | 15-20h |

**Total**: 120-155h | €9,600-12,400

### 4.2 Criterios de Aceptación

| Sprint | Criterio |
|--------|----------|
| 1 | Canvas con estructura header+body+footer. 15 bloques drag-drop. Auto-save REST. |
| 2 | 67 bloques categorizados. Premium bloqueados por plan. Tokens del tenant. |
| 3 | Click header abre panel. Cambio variante re-renderiza. Menú drag-drop. |
| 4 | Click footer abre panel. Efectos Aceternity. Responsive 3 viewports. |
| 5 | Frontend sin JS GrapesJS. Zero regiones. LCP <2.5s. |
| 6 | AI sugiere contenido. Tour onboarding. Tests E2E completos. |

---

## 5. Adiciones Clase Mundial

| Feature | Esfuerzo | Impacto | Sprint |
|---------|----------|---------|--------|
| Command Palette (⌘K) | 8h | Alto | 6 |
| **Prompt-to-Section AI** | 20h | Muy Alto | 6+ |
| Smart Guides (Figma-like) | 12h | Medio | 4 |
| Version Timeline Slider | 10h | Medio | 5 |

---

## 6. Dependencias

### Doc 177 (Global Navigation System)

| Componente | Estado | Acción |
|------------|--------|--------|
| `site_header_config` | ✅ | Listo |
| `site_footer_config` | ✅ | Listo |
| `site_menu` + `site_menu_item` | ✅ | Listo |
| Header Builder UI | ⚠️ Parcial | Completar panel |
| Footer Builder UI | ⚠️ Parcial | Completar panel |

### Endpoints API Nuevos

```yaml
GET  /api/v1/site/header/preview?type={variant}
PUT  /api/v1/site/header
GET  /api/v1/site/footer/preview?type={variant}
PUT  /api/v1/site/footer
PUT  /api/v1/site/menu/{id}
```

---

## 7. Análisis de Riesgos

| Riesgo | Prob. | Impacto | Mitigación |
|--------|-------|---------|------------|
| Retraso Doc 177 | Media | Alto | Paralelizar Header/Footer Builder |
| Latencia preview | Media | Medio | Pre-cache + Redis |
| Confusión global/local | Alta | Medio | Toast + Badge visual |
| Undo/redo cruzado | Media | Alto | Stacks separados |
| Regresión CSS frontend | Baja | Alto | E2E visual Percy.io |

---

## 8. Métricas de Éxito

| KPI | Baseline | 3 meses | 6 meses |
|-----|----------|---------|---------|
| Páginas creadas/mes | 50 | 150 | 300 |
| Tiempo creación página | 45min | 20min | 10min |
| NPS Constructor | N/A | +40 | +60 |
| Churn por editor | 5% | 2% | 1% |

---

## 9. Próximos Pasos

1. [x] Análisis multidisciplinar completado
2. [ ] Aprobar presupuesto €12,000 + €2,400 contingencia
3. [ ] Validar completitud Doc 177 (Header/Footer Builder)
4. [ ] Iniciar Sprint 1: GrapesJS Core + Storage
5. [ ] Definir equipo: 1 Backend, 1 Frontend, 0.5 UX

---

## 10. Referencias

- [Análisis Completo](./20260203-Jaraba_Canvas_v2_Analisis_Multidisciplinar_Claude.md)
- [Especificación v2 (Claude)](./20260203a-178_Page_Builder_Canvas_Visual_v2_Claude.md)
- [Especificación v1](../planificacion/20260203-Page_Builder_Canvas_Visual_v1.md)
- [Page Builder System KI](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/knowledge/jaraba_page_builder_system/artifacts/overview.md)

---

> **Documento creado**: 2026-02-03  
> **Aprobado por**: Comité Multidisciplinar Senior  
> **Versión**: 1.0
