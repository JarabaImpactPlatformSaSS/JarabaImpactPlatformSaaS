# Bloque D: Admin Center Premium - Documento de Implementación
## Centro de Gestión SaaS Enterprise

**Fecha de creación:** 2026-01-23  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar)
3. [Pasos de Implementación](#3-pasos-de-implementación)
4. [Checklist Directrices](#4-checklist-directrices)
   - [4.1 Referencias Obligatorias](#41-referencias-obligatorias)
   - [4.2 Checklist Específico](#42-checklist-específico-bloque-d)
5. [Registro de Cambios](#5-registro-de-cambios)

---

## 1. Matriz de Especificaciones

| Doc | Archivo |
|-----|---------|
| 104 | [20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md](../tecnicos/20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md) |
| FOC | [20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md](../tecnicos/20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md) |

---

## 2. Checklist Multidisciplinar

### Arquitectura
- [ ] ¿WebSockets para real-time?
- [ ] ¿Dark mode nativo?

### Finanzas
- [ ] ¿Integración FOC completa?
- [ ] ¿Stripe Console embebida?

### UX
- [ ] ¿FCP < 1.5s?
- [ ] ¿Glanceability < 5s?

---

## 3. Pasos de Implementación

### Sprint D1-D2: Design System (70h)
- [ ] Tokens CSS admin (`--admin-*`)
- [ ] Layout 3 columnas
- [ ] Sidebar colapsable
- [ ] Command Palette (Cmd+K)

### Sprint D3-D4: Dashboard (70h)
- [ ] KPIs: MRR, Tenants, Users, NRR, Churn, Alerts
- [ ] Widgets Chart.js
- [ ] Sparklines

### Sprint D5-D6: Tenants (70h)
- [ ] Lista con DataTable
- [ ] Detalle 360º
- [ ] **Health Score** (6 factores)
- [ ] Impersonation con audit log

### Sprint D7-D8: Users (60h)
- [ ] Directorio global
- [ ] RBAC matriz
- [ ] Sesiones activas
- [ ] Force logout

### Sprint D9-D10: Finance (80h)
- [ ] Revenue Trend
- [ ] Métricas SaaS 2.0
- [ ] Cohort Analysis
- [ ] Stripe Connect Console

### Sprint D11-D12: Analytics (70h)
- [ ] Report Builder drag-drop
- [ ] Templates predefinidos
- [ ] Scheduled Reports

### Sprint D13-D14: Alerts (60h)
- [ ] Centro notificaciones
- [ ] Reglas configurables
- [ ] **Playbooks automatizados**

### Sprint D15-D16: Settings (50h)
- [ ] Config global
- [ ] Billing Plans CRUD
- [ ] API Keys

### Sprint D17-D18: Logs (45h)
- [ ] Activity Log
- [ ] Audit Trail inmutable
- [ ] Error Log

### Sprint D19-D20: Real-time (60h)
- [ ] WebSocket events
- [ ] Live updates
- [ ] Polish + QA

---

## 4. Checklist Directrices ⚠️

> **VERIFICAR ANTES DE CADA COMMIT**

### 4.1 Referencias Obligatorias

- 📋 [DIRECTRICES_DESARROLLO.md](../tecnicos/DIRECTRICES_DESARROLLO.md) - Checklist central
- 📁 Workflows `.agent/workflows/`:
  - `/scss-estilos` - SCSS y variables inyectables
  - `/i18n-traducciones` - Internacionalización
  - `/sdc-components` - SDC con Compound Variants
  - `/drupal-custom-modules` - Content Entities

### 4.2 Checklist Específico Bloque D

| Área | Verificar |
|------|-----------|
| **SCSS** | Variables `--admin-*`, dark mode CSS, sin hardcoded |
| **Entities** | `alert_rule`, `scheduled_report`, `api_key` |
| **i18n** | Labels y KPI names traducibles |
| **SDC** | component.yml + twig + scss |

---

## 5. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-23 | 1.0.0 | Creación inicial |
| 2026-01-23 | 1.1.0 | Expandida sección 4 - Directrices Obligatorias |

