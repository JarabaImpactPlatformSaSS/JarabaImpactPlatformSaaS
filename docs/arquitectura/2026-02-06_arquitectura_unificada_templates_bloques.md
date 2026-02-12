# Arquitectura Unificada Templates-Bloques GrapesJS

> **Fecha**: 2026-02-06  
> **Estado**: Aprobado  
> **Versión**: 1.0.0

---

## 1. Contexto y Problema

### 1.1 Situación Inicial

El ecosistema Page Builder evolucionó en dos fases:

1. **Fase Pre-GrapesJS** (2026-01): Galería de 76 templates HTML/Twig
2. **Fase GrapesJS** (2026-02): Integración Canvas Editor con ~35 bloques JS

Esto resultó en **dos catálogos independientes** sin sincronización:

```
GALERÍA TEMPLATES (76)        BLOQUES GRAPESJS (~35)
/page-builder/templates   ←→   grapesjs-jaraba-blocks.js
      ✗ SIN SINCRONIZAR ✗
```

### 1.2 Impacto

| Dimensión | Problema |
|-----------|----------|
| **UX** | Usuario ve 76 templates pero solo usa 35 en Canvas |
| **Mantenimiento** | Cambios duplicados en 2 lugares |
| **Theming** | Estilos potencialmente inconsistentes |
| **SEO** | Estructura semántica no unificada |

---

## 2. Arquitectura Single Source of Truth

### 2.1 Diagrama de Solución

```
┌─────────────────────────────────────────────────────────────────┐
│           ARQUITECTURA UNIFICADA (IMPLEMENTADA)                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                 ┌──────────────────────────┐                    │
│                 │  TEMPLATE REGISTRY       │                    │
│                 │  (Single Source of Truth)│                    │
│                 │  ━━━━━━━━━━━━━━━━━━━━━━  │                    │
│                 │  - YAML/JSON definitions │                    │
│                 │  - Semantic structure    │                    │
│                 │  - Design tokens         │                    │
│                 │  - i18n strings          │                    │
│                 │  - Feature flags         │                    │
│                 └───────────┬──────────────┘                    │
│                             │                                   │
│         ┌───────────────────┼───────────────────┐               │
│         │                   │                   │               │
│         ▼                   ▼                   ▼               │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐        │
│  │ Galería     │     │ GrapesJS    │     │ API/IA      │        │
│  │ Templates   │     │ Blocks      │     │ Suggestions │        │
│  │ (Frontend)  │     │ (Canvas)    │     │ (Backend)   │        │
│  └─────────────┘     └─────────────┘     └─────────────┘        │
│                                                                 │
│  ✅ Un solo catálogo → múltiples consumidores                   │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Componentes

| Componente | Ubicación | Propósito |
|------------|-----------|-----------|
| **TemplateRegistryService** | `jaraba_page_builder/src/Service/` | Fuente única de definiciones |
| **GrapesJS Blocks Plugin** | `jaraba_page_builder/js/grapesjs-jaraba-blocks.js` | Consumidor para Canvas Editor |
| **Template Gallery** | `/page-builder/templates` | Consumidor para selección visual |
| **API Endpoint** | `/api/v1/page-builder/templates` | Consumidor para IA/externos |

---

## 3. Estrategia de Migración

### 3.1 Fase 1: Bridge ✅ COMPLETADA

| Item | Estado | Detalle |
|------|--------|---------|
| Crear TemplateRegistryService PHP | ✅ | `src/Service/TemplateRegistryService.php` |
| 70 templates YAML | ✅ | `config/install/jaraba_page_builder.template.*.yml` |
| 70 bloques GrapesJS | ✅ | `grapesjs-jaraba-blocks.js` (2100 líneas) |
| Paridad visual 1:1 | ✅ | 100% verificado |

### 3.2 Fase 2: Consolidación ✅ COMPLETADA

| Item | Estado | Detalle |
|------|--------|---------|
| Templates YAML | ✅ | 70 archivos migrados |
| TemplateRegistryService | ✅ | 5 endpoints API REST |
| GrapesJS consume API | ✅ | `loadBlocksFromRegistry()` |
| Fallback resiliente | ✅ | Bloques estáticos disponibles si API falla |
| Verificación | ✅ | 62 estáticos + 70 API = **132 bloques** |

### 3.3 Fase 3: Extensión ✅ COMPLETADA

| Item | Estado | Detalle |
|------|--------|---------|
| Feature flags | ✅ | `isLocked`, `isPremium`, `requiredPlan` |
| Estilos bloqueados | ✅ | SCSS con 🔒 y opacidad |
| Analytics tracking | ✅ | `setupBlockAnalytics()` |
| Fallback resiliente | ✅ | Bloques estáticos si API falla |

---

## 4. Inventario Actual (PARIDAD 100% COMPLETADA)

> **✅ HITO COMPLETADO**: 70 bloques GrapesJS = 70 templates YAML

### 4.1 Estadísticas Finales

| Métrica | Valor |
|---------|-------|
| Templates YAML | **70** |
| Bloques Jaraba | **70** |
| Bloques Nativos GrapesJS | **62** |
| Total en Canvas | **132** |
| Categorías | **14** |
| Paridad | **100%** ✅ |

### 4.2 Distribución por Categoría

| Categoría | Bloques | Descripción |
|-----------|:-------:|-------------|
| **Basic** | 12 | H1-H4, párrafo, botones, navegación |
| **Layout** | 3 | Grid 2/3/4 columnas |
| **Hero** | 3 | Centrado, 50/50, Video |
| **Content** | 4 | Testimonial, FAQ, Equipo, Features |
| **CTA** | 4 | Centrado, Split, Minimal, Newsletter |
| **Stats** | 3 | Grid, Contador, Progreso |
| **Pricing** | 4 | Tabla, Card, Toggle, Features |
| **Contact** | 4 | Form, Info, Mapa, Split |
| **Media** | 5 | Imagen, Video, Galería |
| **Commerce** | 4 | Producto, Grid, Carrito, Pagos |
| **Social** | 5 | Redes, Proof, Compartir, Logos |
| **Advanced** | 5 | Timeline, Tabs, Tabla, Embed |
| **Utilities** | 5 | Alerta, Countdown, Wizard, Cookies |
| **Premium** | 9 | Glassmorphism, Parallax, Flip-3D |

### 4.3 Matriz Completa

Consultar: [2026-02-06_matriz_bloques_page_builder.md](../tecnicos/2026-02-06_matriz_bloques_page_builder.md)

---

## 5. Decisiones Arquitectónicas (ADRs)

### ADR-001: Single Source of Truth

**Decisión**: Implementar Template Registry como fuente única.

**Razón**: Evitar mantenimiento duplicado y garantizar consistencia.

**Consecuencias**: Requiere migración gradual de templates legacy.

### ADR-002: Bridge Pattern

**Decisión**: No eliminar galería actual, crear puente de sincronización.

**Razón**: Minimizar riesgo y permitir rollback.

**Consecuencias**: Período de coexistencia con ambos sistemas.

---

## 6. Referencias

- [Auditoría Page Builder Clase Mundial](./2026-02-05_auditoria_page_site_builder_clase_mundial.md)
- [Especificación GrapesJS SaaS](./2026-02-05_especificacion_grapesjs_saas.md)
- [KI: Jaraba Visual Builder Ecosystem](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/knowledge/jaraba_visual_builder_ecosystem/artifacts/overview.md)
