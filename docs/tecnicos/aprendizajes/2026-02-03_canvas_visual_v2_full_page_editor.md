# 🏗️ Canvas Visual v2: Full Page Editor - Aprendizajes

> **Fecha**: 2026-02-03  
> **Contexto**: Análisis multidisciplinar del Jaraba Canvas Visual v2 Full Page Editor  
> **Estado**: Aprobado para implementación

---

## 1. Contexto del Análisis

Se realizó un análisis exhaustivo comparando el Canvas Editor v2.7 actual con la propuesta v2 "Full Page Editor", involucrando 8 perspectivas senior: Negocio, Finanzas, Marketing, UX, Arquitectura SaaS, Drupal, IA y SEO/GEO.

---

## 2. Hallazgos Clave

### 2.1 Evolución Arquitectónica Necesaria

**De**: Editor de **contenido de página** (solo body)  
**A**: Editor de **página completa** (header + nav + body + footer)

```
Canvas v2.7 (actual):    [Header fijo] + [BODY editable] + [Footer fijo]
Canvas v2 (propuesto):   [Header ✏️] + [Nav ✏️] + [Body ✏️] + [Footer ✏️]
```

### 2.2 Gap de Competitividad

| Competidor | Fortaleza | Lo que Jaraba Añade |
|------------|-----------|---------------------|
| Webflow | Diseño CSS completo | + Multi-tenant + Verticales |
| Framer | Animaciones Motion | + Design Tokens por tenant |
| Elementor | Ecosistema WordPress | + Simplicidad + IA contextual |
| Squarespace | Templates premium | + Personalización avanzada |

### 2.3 Decisión: Hybrid Isolation v2

Mantener el patrón "Hybrid Isolation" del Canvas v2.7:
- **Limpiar**: Toolbar admin, breadcrumbs, mensajes Drupal
- **Preservar**: Header, footer, navegación del tenant (editables)

---

## 3. Patrones Técnicos Validados

### 3.1 Componentes GrapesJS Non-Draggable

```javascript
// Componentes estructurales: NO se arrastran, SÍ se editan
editor.DomComponents.addType('jaraba-header', {
  model: {
    defaults: {
      draggable: false,
      removable: false,
      copyable: false,
      selectable: true,  // Permite selección → abre panel
      traits: ['header_type', 'sticky', 'cta_text']
    }
  }
});
```

### 3.2 Persistencia Dual

```yaml
# GLOBAL (afecta todo el sitio)
site_header_config → Header compartido por todas las páginas
site_footer_config → Footer compartido
site_menu → Menú de navegación principal

# LOCAL (por página)
page_content.grapesjs_html → HTML de bloques
page_content.grapesjs_css → CSS de bloques
page_content.grapesjs_components → JSON de estructura
```

### 3.3 Pre-Rendering de Variantes

```php
// Cargar TODAS las variantes de header/footer al inicio
$headerVariants = $this->headerService->preRenderAllVariants($tenant);
// → Cambio de variante NO requiere llamada API
```

### 3.4 Undo/Redo con Stacks Separados

```javascript
// Evitar conflictos entre cambios globales y locales
const undoManagerGlobal = new UndoManager();
const undoManagerLocal = new UndoManager();

// Al cambiar header (global)
undoManagerGlobal.add({ type: 'header', prev, next });

// Al mover bloque (local)
undoManagerLocal.add({ type: 'component', prev, next });
```

---

## 4. Lecciones de Investigación Competitiva

### 4.1 Tendencias 2025-2026

| Tendencia | Implementación Propuesta |
|-----------|--------------------------|
| **AI-Assisted Design** | Prompt-to-Section (diferenciador único) |
| **On-Page Editing** | Edición inline de textos sin modal |
| **Microinteractions** | Efectos Aceternity/Magic UI en bloques premium |
| **Responsive Granular** | 3 breakpoints con control por elemento |
| **Accesibilidad** | WCAG 2.1 AA obligatorio |

### 4.2 Feature Único: Prompt-to-Section

Ningún competidor ofrece generación de secciones completas con contexto de vertical de negocio:

```
Usuario: "Crea una sección de pricing para mi agencia de marketing"

Jaraba Canvas AI:
1. Detecta vertical: Servicios
2. Selecciona bloque: pricing-table-premium
3. Genera contenido: 3 planes contextualizados
4. Aplica Design Tokens del tenant
5. → Resultado listo para editar
```

---

## 5. Métricas de Éxito Definidas

| KPI | Actual | Target 3m | Target 6m |
|-----|--------|-----------|-----------|
| Páginas/mes | 50 | 150 | 300 |
| Tiempo creación | 45min | 20min | 10min |
| NPS Constructor | N/A | +40 | +60 |

---

## 6. Riesgos y Mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| Confusión usuario global vs local | Toast informativo + badge color diferente |
| Latencia preview variantes | Pre-cache Redis + carga eager |
| CSS pollution entre zonas | Namespace `.jaraba-canvas-*` + scoping |
| Regresión en frontend público | Tests E2E visuales (Percy.io) |

---

## 7. Dependencias Críticas

### Doc 177: Global Navigation System

**Estado actual**:
- ✅ Entidades `site_header_config`, `site_footer_config`, `site_menu`
- ⚠️ Header Builder UI: Parcial
- ⚠️ Footer Builder UI: Parcial

**Acción**: Paralelizar desarrollo con Sprint 3 del Canvas v2.

---

## 8. Referencias

- [20260203-178_Jaraba_Canvas_v2_Full_Page_Editor_Plan_Claude.md](./20260203-178_Jaraba_Canvas_v2_Full_Page_Editor_Plan_Claude.md)
- [20260203a-178_Page_Builder_Canvas_Visual_v2_Claude.md](./20260203a-178_Page_Builder_Canvas_Visual_v2_Claude.md)
- [Jaraba Page Builder System KI](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/knowledge/jaraba_page_builder_system/artifacts/overview.md)

---

> **Lección Principal**: El Canvas v2 no es solo una mejora incremental, es un **cambio de paradigma** de editor de contenido a editor de experiencia de página completa. El ROI justifica la inversión (237-329%).
