# Canvas Editor v3 - Arquitectura Híbrida GrapesJS

**Fecha:** 2026-02-04  
**Contexto:** Migración del Canvas Editor de arquitectura EDI (configurador) a arquitectura híbrida GrapesJS (constructor)

---

## Decisión Arquitectónica

### Problema

La implementación actual del Canvas Editor es un **"configurador de páginas"** (reordenar bloques, cambiar variantes), NO un **"constructor de páginas"** con capacidades de drag-and-drop, inline editing y undo/redo propias de un builder moderno.

### Análisis de Gaps

| Capacidad | Estado EDI | Coste Cerrar Gap | Riesgo |
|-----------|-----------|------------------|--------|
| Drag-and-drop | SortableJS (solo reordenar) | 40-60h | Alto (edge cases) |
| Edición inline | No existe | 30-40h | Medio |
| Undo/Redo | No existe | 25-35h | Alto (state mgmt) |
| Auto-save | Botón manual | 8-12h | Bajo |
| Preview responsive | No existe | 15-20h | Medio |

**Total cerrar gaps EDI**: 118-162h con resultado inferior

### Decisión: Arquitectura Híbrida

- **Motor de edición**: GrapesJS → drag-drop, inline, undo/redo nativos (maduros, probados)
- **Preservar de EDI**: Design Tokens, hot-swap, templates Twig, AI Field Generator
- **Coste híbrido**: 155-195h con resultado clase mundial

---

## Documento Maestro Creado

**Ubicación**: `docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md`

### Contenido

1. **Resumen Ejecutivo** — Visión del constructor v3
2. **Visión Conceptual** — Diagrama ASCII del editor completo
3. **Arquitectura de Componentes** — GrapesJS + Componentes Jaraba
4. **Modelo de Datos** — Entidades ERD + Persistencia dual
5. **Bloques de Contenido** — 67 bloques + Feature flags
6. **Flujo de Edición UX** — Estados + Panel traits
7. **Renderizado Público** — Pipeline Zero Region
8. **Integraciones** — AI Content Assistant + Menu Editor
9. **Performance y Cache** — Estrategia multi-tenant
10. **Matriz Capacidades** — Score clase mundial
11. **Guía de Implementación** — Estructura archivos, sprints, criterios aceptación, tests

### Documentos Relacionados

| Doc | Título |
|-----|--------|
| 162 | Page Builder Sistema Completo |
| 177 | Global Navigation System |
| 178 v1/v2 | Canvas Visual (body-only / full-page) |

---

## Aprendizajes Clave

### 1. Configurador ≠ Constructor

> Un configurador permite seleccionar y ordenar piezas predefinidas.  
> Un constructor permite crear, modificar y componer libremente.

**Implicación**: Para UX clase mundial, necesitamos un constructor. Las capacidades de constructor (drag-drop, inline editing, undo/redo) son complejas de implementar correctamente "desde cero".

### 2. Híbrido > Reemplazo Total

En lugar de:
- ❌ Reemplazar todo por GrapesJS (perdemos Design Tokens, hot-swap, AI integration)
- ❌ Implementar todo desde cero (costoso, riesgoso)

Mejor:
- ✅ **Híbrido**: GrapesJS para core builder + preservar inversiones EDI

### 3. Dual Mode UX

Ofrecer dos modos permite:
- **Canvas Visual** (GrapesJS) — Para usuarios que quieren control total
- **Configurador Rápido** (EDI actual) — Para usuarios que prefieren simplicidad

Esto respeta diferentes perfiles de usuario y niveles de competencia técnica.

### 4. Persistencia Dual Obligatoria

| Cambio | Alcance | Endpoint |
|--------|---------|----------|
| Variante header/footer | **Global** | `/api/v1/site/header` |
| Bloques en body | **Local** | `/api/v1/pages/{id}/canvas` |

**Importante**: Mostrar toast de advertencia cuando el usuario modifica parciales globales.

---

## Comandos y Referencias

```bash
# Documentación
docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md

# Especificaciones relacionadas
docs/tecnicos/20260126d-162_Page_Builder_Sistema_Completo_EDI_v1_Claude.md
docs/tecnicos/20260127a-177_Global_Navigation_System_v1_Claude.md
docs/tecnicos/20260203a-178_Page_Builder_Canvas_Visual_v2_Claude.md

# Auditorías
docs/tecnicos/audit_canvas_vs_specs.md
docs/tecnicos/20260204a-Evaluacion_Arquitectonica_Jaraba_Canvas_Claude.md
```

---

## Tags

`#canvas-editor` `#grapesjs` `#arquitectura` `#page-builder` `#clase-mundial`
