# 🎯 Plan de Trabajo: Constructor de Páginas Clase Mundial

**Fecha:** 2026-02-03 (Trabajo Pendiente)  
**Preparado:** 2026-02-02 21:30  
**Contexto:** Evolución del Page Builder actual hacia experiencia visual de primer nivel

---

## 📊 Resumen Sesión Anterior (2026-02-02)

### Completado ✅
- [x] Bug rendering PageContent (`hook_theme()` dinámico) → Páginas renderizando
- [x] Template Frontend Limpia (Zero Region Policy)
- [x] Header inline sin menú ecosistema heredado
- [x] Body classes via `hook_preprocess_html()`
- [x] SCSS reset grid para full-width
- [x] Documentación: aprendizaje #34, directrices v5.0.0, índice v8.5.0

---

## ⚠️ GAP CRÍTICO: Page Builder Actual vs Clase Mundial

### Estado Actual
| Aspecto | Estado | Score |
|---------|--------|-------|
| Templates disponibles | 70+ templates | ✅ 9/10 |
| Renderizado frontend | Funcional | ✅ 8/10 |
| **Experiencia de edición** | Formularios complejos | ❌ 3/10 |
| **Drag-and-Drop visual** | No existe | ❌ 0/10 |
| **Preview en tiempo real** | Solo post-guardado | ❌ 2/10 |
| **Ordenamiento de bloques** | Formulario Drupal | ❌ 3/10 |

### Benchmark: Constructores Clase Mundial
- **Wix / Squarespace**: Canvas visual, drag-drop secciones, preview instantáneo
- **Webflow**: Diseño visual full, CSS en tiempo real, animaciones
- **Elementor**: Sidebar con widgets, arrastrar al canvas, WYSIWYG
- **Framer**: Motion design integrado, colaboración en tiempo real

---

## 🎯 Visión: Page Builder Visual "Jaraba Canvas"

### Arquitectura Propuesta

```
┌─────────────────────────────────────────────────────────────────┐
│                    JARABA CANVAS EDITOR                         │
├───────────────┬─────────────────────────────────────────────────┤
│               │                                                  │
│   SLIDE-PANEL │           CANVAS PREVIEW                        │
│   (Bloques)   │           (Iframe Live)                         │
│               │                                                  │
│  ┌──────────┐ │   ┌────────────────────────────────────┐       │
│  │ 🎨 Hero  │ │   │                                    │       │
│  │ Drag me  │─┼──▶│    [BLOQUE SOLTADO AQUÍ]          │       │
│  └──────────┘ │   │                                    │       │
│  ┌──────────┐ │   │    ─────────────────────           │       │
│  │ 📊 Stats │ │   │                                    │       │
│  └──────────┘ │   │    [OTRO BLOQUE]                   │       │
│  ┌──────────┐ │   │                                    │       │
│  │ 💬 CTA   │ │   └────────────────────────────────────┘       │
│  └──────────┘ │                                                  │
│               │   [ ↑ ] [ ↓ ] [ ⚙️ ] [ 🗑️ ] (Acciones rápidas) │
├───────────────┴─────────────────────────────────────────────────┤
│  [💾 Guardar]  [👁️ Preview]  [🚀 Publicar]  [↩️ Deshacer]       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📋 Plan de Trabajo - Día 1 (2026-02-03)

### Fase 1: Análisis y Diseño (4-6h)
- [ ] **Auditoría competitiva detallada** (Wix, Webflow, Elementor, Framer)
  - Capturar screenshots de UX de edición
  - Documentar patrones de interacción
  - Identificar features must-have vs nice-to-have

- [ ] **Diseño UX del Canvas Editor**
  - Wireframes para slide-panel de bloques
  - Diseño de estados: empty, drag, drop, hover, selected
  - Flujo de ordenamiento drag-and-drop
  - Preview en iframe con live reload

- [ ] **Análisis técnico de implementación**
  - SortableJS vs interact.js vs dragula
  - Comunicación iframe ↔ editor parent
  - Persistencia optimista (guardar mientras editas)
  - Estrategia de undo/redo

### Fase 2: Prototipo MVP (8-12h)
- [ ] **Slide-Panel de Bloques**
  - Cards arrastrables por categoría
  - Preview thumbnail de cada template
  - Búsqueda/filtrado rápido

- [ ] **Canvas Droppable**
  - Iframe con página en tiempo real
  - Drop zones entre bloques
  - Indicadores visuales de posición

- [ ] **CRUD Rápido**
  - Edición inline de textos
  - Panel lateral para configuración avanzada
  - Auto-guardado cada N segundos

### Fase 3: Polish Premium (6-8h)
- [ ] Animaciones micro-interacciones
- [ ] Undo/Redo stack
- [ ] Keyboard shortcuts
- [ ] Mobile responsive editor
- [ ] Onboarding tutorial interactivo

---

## 💰 Análisis Financiero

### Inversión Estimada
| Fase | Horas | Costo (@€80/h) |
|------|-------|----------------|
| Diseño UX + Wireframes | 8h | €640 |
| Prototipo slide-panel + canvas | 20h | €1,600 |
| Drag-and-drop + SortableJS | 16h | €1,280 |
| Preview iframe live | 12h | €960 |
| Auto-guardado + CRUD inline | 10h | €800 |
| Animaciones + polish | 8h | €640 |
| Testing + bugs | 10h | €800 |
| **TOTAL** | **84h** | **€6,720** |

### ROI Proyectado
- **Ahorro vs Elementor Pro**: €49/mes × 50 tenants = €29,400/año
- **Ahorro vs Webflow**: €29/mes × 50 tenants = €17,400/año
- **Diferenciación competitiva**: Constructor nativo integrado con verticales
- **Payback**: 3-4 meses con 50 tenants activos

---

## 🛠️ Stack Tecnológico Propuesto

| Componente | Tecnología | Justificación |
|------------|------------|---------------|
| Drag-and-Drop | **SortableJS** | Ya usado en Tree Manager, maduro, performante |
| Estado JS | **Alpine.js** | Ligero, ya integrado, reactivo |
| Canvas Preview | **Iframe + postMessage** | Aislamiento seguro, live reload |
| Persistencia | **REST API + debounce** | Auto-guardado, optimistic UI |
| Animaciones | **GSAP / anime.js** | Micro-interacciones premium |

---

## 🎨 Especificaciones UX Mínimas

### Slide-Panel de Bloques
- Ancho: 320px (expandible a 400px)
- Categorías colapsables: Básico, Premium, Formularios, Media
- Cards con: thumbnail 80x60px, nombre, descripción corta
- Drag ghost: Card semitransparente siguiendo cursor

### Canvas Editor
- Ancho: Responsive (fluid al espacio disponible)
- Iframe con página real
- Drop zones: Barra horizontal 4px que aparece entre bloques
- Bloque seleccionado: Borde azul + toolbar flotante

### Toolbar Flotante (por bloque)
- Iconos: ↑ Subir, ↓ Bajar, ⚙️ Editar, 📋 Duplicar, 🗑️ Eliminar
- Aparece on hover/click sobre bloque
- Posición: Top-right del bloque

---

## 🚀 Quick Wins para Mañana

### Mínimo Viable (4h)
1. Slide-panel con lista de templates arrastrables
2. Área de drop básica que añade bloque al final
3. Reordenamiento con SortableJS en lista simple

### Mejora Incremental (4h adicionales)
4. Preview real del bloque añadido (no solo icono)
5. Eliminación de bloques con confirmación
6. Guardar orden en `field_blocks` del PageContent

---

## 📚 Referencias

- [SortableJS Docs](https://sortablejs.github.io/Sortable/)
- [Webflow Editor UX](https://webflow.com/)
- [Elementor Editor Patterns](https://elementor.com/)
- [Patrón Slide-Panel existente](../tecnicos/aprendizajes/2026-01-29_site_builder_frontend_fullwidth.md)

---

> **🌙 Descansa bien. Mañana atacamos el Canvas Editor.**
