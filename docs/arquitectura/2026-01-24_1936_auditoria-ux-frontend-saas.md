# 🔍 Auditoría UX Multidisciplinar - Frontend SaaS

**Fecha:** 2026-01-24
**Versión:** 1.0.0
**Tipo:** Arquitectura - Auditoría
**Panel de Expertos:** UX Senior, Frontend Senior, Theming Senior, SEO/GEO Senior, IA Senior

---

## 1. Resumen Ejecutivo

> [!CAUTION]
> **Estado Global: CRÍTICO** - La interfaz pública del SaaS no cumple estándares mínimos de conversión ni SEO.

### Puntuaciones por Área

| Componente | UX | Frontend | SEO | IA | Global |
|------------|-----|----------|-----|-----|--------|
| Homepage Pública | 2/10 | 2/10 | 3/10 | 1/10 | **2/10** |
| Dashboard Job Seeker | 6/10 | 5/10 | N/A | 5/10 | **5.5/10** |
| Dashboard Recruiter | 5/10 | 4/10 | N/A | 6/10 | **5/10** |
| Dashboard Emprendedor | 1/10 | 1/10 | N/A | 1/10 | **1/10** |
| Dashboard Productor | 1/10 | 1/10 | N/A | 1/10 | **1/10** |

---

## 2. Hallazgos Detallados

### 2.1 Homepage Pública (`/`)

#### Problema Crítico
La homepage muestra el mensaje default de Drupal: **"Aún no se ha creado ningún contenido de página de inicio"**.

#### Impacto SEO/GEO
- **Sin meta description**: Google indexará texto aleatorio
- **Título genérico**: "¡Bienvenidos! | Jaraba"
- **Sin H1 estratégico**: No hay jerarquía semántica
- **GEO (AI Discovery)**: Invisible para ChatGPT, Perplexity, etc.

#### Impacto Conversión
- **Time to Value**: Infinito (usuario no entiende qué hacer)
- **Tasa de rebote esperada**: >90%
- **CTA visible**: Solo "Iniciar sesión" (barrera alta)

### 2.2 Dashboards por Avatar

#### Job Seeker (`/admin/dashboard/career`)
- **Estado técnico**: 404 - Ruta no registrada
- **Bloque existe**: `Dashboard de Carrera Candidato`
- **Configuración actual**: Solo visible en `/user/*`
- **Problema**: Routing y visibilidad incorrectos

#### Recruiter (`/admin/dashboard/recruiter`)
- **Estado técnico**: 404 pero contenido visible por accidente
- **Panel funcional**: Tiene tabs, métricas, FAB del Copilot
- **Problema**: Conflicto de visibilidad de bloques

#### Emprendedor (`/admin/dashboard/entrepreneur`)
- **Estado técnico**: 404 completo
- **Bloque existe**: No encontrado
- **BMC/Canvas**: No implementado en UI

#### Productor (`/admin/dashboard/producer`)
- **Estado técnico**: 404 completo
- **Catálogo productos**: No implementado en UI

---

## 3. Arquitectura Propuesta

### 3.1 Progressive Profiling Pattern

```
┌─────────────────────────────────────────────────────────────────────┐
│                    LANDING PAGE PRE-LOGIN                           │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  HERO: "Impulsa tu ecosistema digital"                       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ¿Qué te gustaría lograr hoy?                                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐              │
│  │ 👤 Busco │ │ 🏢 Busco │ │ 🚀 Quiero│ │ 🌾 Vendo │              │
│  │ empleo   │ │ talento  │ │ emprender│ │ productos│              │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘              │
│                                                                     │
│  [Click] → Mini-journey explicativo → CTA Registro/Login          │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│               POST-LOGIN: DASHBOARD PERSONALIZADO                   │
│  Journey Engine detecta avatar → Dashboard específico               │
│  Copilot contextual → Guía proactiva                                │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.2 Rutas Corregidas

| Avatar | Ruta Actual | Ruta Propuesta | Controller |
|--------|-------------|----------------|------------|
| Job Seeker | N/A (404) | `/dashboard/career` | `DynamicDashboardController` |
| Recruiter | N/A (404) | `/dashboard/recruiter` | `DynamicDashboardController` |
| Emprendedor | N/A | `/dashboard/entrepreneur` | `DynamicDashboardController` |
| Productor | N/A | `/dashboard/producer` | `DynamicDashboardController` |

---

## 4. Plan de Remediación

### Fase 1: Quick Wins Críticos (40h)

| Tarea | Horas | Prioridad |
|-------|-------|-----------|
| Crear Homepage con Hero Section | 16h | 🔴 Crítica |
| Añadir Meta Tags SEO | 4h | 🔴 Crítica |
| Registrar rutas de dashboards | 8h | 🔴 Crítica |
| Corregir visibilidad de bloques | 8h | 🔴 Crítica |
| Implementar Grid de Intenciones | 4h | 🔴 Crítica |

### Fase 2: Dashboards por Avatar (80h)

| Tarea | Horas | Prioridad |
|-------|-------|-----------|
| 19 dashboards para cada avatar | 60h | 🟡 Alta |
| Integrar Journey Engine | 12h | 🟡 Alta |
| Copilot contextual por avatar | 8h | 🟡 Alta |

### Fase 3: Estándares Clase Mundial (60h)

| Tarea | Horas | Prioridad |
|-------|-------|-----------|
| Micro-animaciones (Framer Motion) | 20h | 🟢 Media |
| Dark Mode opcional | 12h | 🟢 Media |
| PWA offline-first | 16h | 🟢 Media |
| Lighthouse > 90 | 12h | 🟢 Media |

---

## 5. Métricas de Éxito

| Métrica | Actual | Objetivo | Timeline |
|---------|--------|----------|----------|
| Lighthouse Performance | ~40 | > 90 | Q1 2026 |
| Time to First Value | > 10 clicks | < 3 clicks | Q1 2026 |
| SEO Score | 0% | 100% | Q1 2026 |
| Avatar Dashboard Coverage | 2/19 | 19/19 | Q2 2026 |
| Core Web Vitals | ❌ | ✅ | Q1 2026 |

---

## 6. Evidencia Visual

### 6.1 Homepage Actual
Mensaje default de Drupal sin propuesta de valor.

### 6.2 Configuración de Bloques
Dashboard de Carrera configurado solo para `/user/*` en lugar de ruta dedicada.

---

## 7. Conclusiones

1. **La interfaz pública es inaceptable** para un SaaS de clase mundial
2. **El motor interno es potente** (Journey Engine, Copilot v3) pero invisible al usuario
3. **Se requiere intervención inmediata** en Homepage y rutas de dashboards
4. **La propuesta de Progressive Profiling** es la estrategia correcta

---

## 8. Aprobación

| Rol | Aprobado | Fecha |
|-----|----------|-------|
| UX Senior | ✅ | 2026-01-24 |
| Frontend Senior | ✅ | 2026-01-24 |
| SEO/GEO Senior | ✅ | 2026-01-24 |
| IA Senior | ✅ | 2026-01-24 |
| Theming Senior | ✅ | 2026-01-24 |

---

## 9. Referencias

- [Doc 103: Journey Engine](./tecnicos/20260116f-103_UX_Journey_Avatares_v1_Claude.md)
- [Doc 100: Frontend Premium](./tecnicos/20260115f-100_Frontend_Premium_UI_Components_v1_Claude.md)
- [Plan Maestro v3.0](./planificacion/20260123-Plan_Maestro_Unificado_SaaS_v3_Claude.md)
