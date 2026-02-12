# 📋 Tareas Pendientes (8 Feb 2026)

**Generado:** 2026-02-06 20:33  
**Actualizado:** 2026-02-08 08:30

---

## 🏢 BLOQUE D: ADMIN CENTER PREMIUM (Estado)

### Estado Actual: **NO INICIADO** (0/635h)

**Especificación:** [104_SaaS_Admin_Center_Premium](../tecnicos/20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md)

### Componentes Existentes (Dispersos)

| Controller | Ubicación | Función |
|------------|-----------|---------|
| `FinOpsDashboardController` | ecosistema_jaraba_core | Métricas FOC |
| `HealthDashboardController` | ecosistema_jaraba_core | Health Score |
| `TenantDashboardController` | ecosistema_jaraba_core | Panel tenant |
| `TenantAnalyticsController` | ecosistema_jaraba_core | Analytics básico |
| `TenantSelfServiceController` | ecosistema_jaraba_core | Self-service |

### Rutas Admin Existentes
- `/admin/jaraba/email/*` - jaraba_email
- `/admin/jaraba/analytics` - jaraba_analytics
- `/admin/structure/saas-plans` - Planes SaaS

---

## 🎯 TAREAS PENDIENTES BLOQUE D

### Sprint D1-D2: Design System (70h) - **INICIAR**
- [ ] Crear módulo `jaraba_admin_center`
- [ ] Tokens CSS admin (`--admin-*`)
- [ ] Layout 3 columnas
- [ ] Sidebar colapsable
- [ ] Command Palette (Cmd+K)

### Sprint D3-D4: Dashboard Unificado (70h)
- [ ] Consolidar FinOps + Health + Analytics
- [ ] KPIs: MRR, Tenants, Users, NRR, Churn
- [ ] Widgets Chart.js + Sparklines

### Sprint D5-D6: Tenants 360º (70h)
- [ ] Health Score (6 factores)
- [ ] Impersonation con audit log
- [ ] DataTable con búsqueda

---

## 🚀 PAGE BUILDER: ELEVACIÓN A CLASE MUNDIAL (9.2 → 9.8)

> **Plan completo:** [2026-02-08_plan_elevacion_page_builder_clase_mundial.md](../arquitectura/2026-02-08_plan_elevacion_page_builder_clase_mundial.md)

### Sprint PB-1: Dual Architecture Bloques Interactivos (8h) — ✅ COMPLETADO
- [x] `stats-counter` / `animated-counter` — Intersection Observer (1.5h)
- [x] `pricing-toggle` — Switch mensual/anual (1.5h)
- [x] `tabs-content` — Navegación por pestañas (1.5h)
- [x] `countdown-timer` — Temporizador en tiempo real (1h)
- [x] `timeline` — Animación scroll-triggered (1h)
- [x] Crear 5 archivos `Drupal.behaviors` + registrar bibliotecas
- [x] Auto-attachment en `hook_page_attachments()`

### Sprint PB-2: Hot-Swap Receptor PostMessage (4h) — ✅ YA IMPLEMENTADO
- [x] Implementar `canvas-preview-receiver.js` con listener `message` (435 líneas)
- [x] Receptor `JARABA_HEADER_CHANGE` → fetch parcial + replace `<header>`
- [x] Receptor `JARABA_FOOTER_CHANGE` → fetch parcial + replace `<footer>`
- [x] Persistir en `SiteConfig` via API REST (`SiteConfigApiController`)
- [ ] ⚠️ Alinear variantes entre JS receiver y PHP controller

### Sprint PB-3: Robustez Tests E2E (3h) — 🟡 MEDIA
- [ ] Eliminar todos los `expect(true).to.be.true` fallbacks
- [ ] Test 8 (Command Palette) — verificar plugin cargado
- [ ] Test 4 (Traits) — verificar actualización real en canvas
- [ ] Nuevo Test 10 — Stats Counter funciona
- [ ] Nuevo Test 11 — Hot-swap header cambia variante

### Sprint PB-4: Traits Commerce/Social (6h) — 🟡 MEDIA
- [ ] `product-card` → traits precio, nombre, imagen, URL compra
- [ ] `social-links` → traits URLs redes sociales
- [ ] `contact-form` → traits email destino, campos requeridos
- [ ] `pricing-table` → traits planes, precios, features

---

## 📊 Resumen Bloque D

| Sprint | Horas | Prioridad |
|--------|-------|-----------|
| D1-D2 Design System | 70h | 🔴 ALTA |
| D3-D4 Dashboard | 70h | 🔴 ALTA |
| D5-D6 Tenants | 70h | 🟡 MEDIA |
| D7-D20 Resto | 425h | 🟢 FUTURA |

**Total Bloque D:** 635h planificadas (Q3 2026 - Q1 2027)

## 📊 Resumen Page Builder Elevación

| Sprint | Horas | Prioridad |
|--------|-------|-----------|
| PB-1 Dual Architecture | 8h | 🔴 ALTA |
| PB-2 Hot-Swap | 4h | 🔴 ALTA |
| PB-3 Tests E2E | 3h | 🟡 MEDIA |
| PB-4 Traits Commerce | 6h | 🟡 MEDIA |

**Total Page Builder:** 21h → Score 9.2 → 9.8

---

## 🌱 AGROCONECTA: FASE 4 — REVIEWS + NOTIFICACIONES

> **Plan completo:** [20260208-Plan_Implementacion_AgroConecta_v2.md](../implementacion/20260208-Plan_Implementacion_AgroConecta_v2.md)
> **Fases anteriores:** 1 (Commerce Core ✅), 2 (Orders ✅), 3 (Portales ✅)
> **Docs técnicos:** 54 (Reviews), 59 (Notifications)

### Sprint AC4-1: Entidades + Handlers (10h) — 🔴 ALTA
- [ ] `ReviewAgro` entity (16 campos) + ListBuilder + AccessHandler + Form + SettingsForm
- [ ] `NotificationTemplateAgro` entity (13 campos) + handlers completos
- [ ] `NotificationLogAgro` entity (16 campos) + ListBuilder (read-only)
- [ ] `NotificationPreferenceAgro` entity (8 campos) + Form
- [ ] 4 YAMLs por entidad: routing, links.menu, links.task, links.action
- [ ] 7 permisos nuevos en permissions.yml

### Sprint AC4-2: Services + API Controllers (8h) — 🔴 ALTA
- [ ] `ReviewService` — submitReview, getProductReviews, getProducerRating, respondToReview, moderateReview
- [ ] `NotificationService` — send (orquestador), canal Email (Symfony Mailer), canal In-App
- [ ] `ReviewApiController` — 7 endpoints REST
- [ ] `NotificationApiController` — 7 endpoints REST

### Sprint AC4-3: Frontend — Templates + JS + SCSS (6h) — 🟡 MEDIA
- [ ] 4 templates reviews: widget, form, card, summary
- [ ] 2 templates notifications: centro, preferencias
- [ ] `reviews.js` — Estrellas interactivas SVG
- [ ] `notifications.js` — Dropdown, mark-read, badge
- [ ] `_reviews.scss` — Premium cards, distribución estrellas
- [ ] `_notifications.scss` — Dropdown glassmorphism, toggles preferencias

### Sprint AC4-4: Integración Portales + Verificación (4h) — 🟡 MEDIA
- [ ] Widget reseñas en Product Detail
- [ ] Rating productor en Producer Portal
- [ ] Centro notificaciones en Customer Portal
- [ ] Verificación completa: entidades admin + API + frontend

## 📊 Resumen AgroConecta Fase 4

| Sprint | Horas | Prioridad |
|--------|-------|-----------|
| AC4-1 Entidades | 10h | 🔴 ALTA |
| AC4-2 Services + API | 8h | 🔴 ALTA |
| AC4-3 Frontend | 6h | 🟡 MEDIA |
| AC4-4 Integración | 4h | 🟡 MEDIA |

**Total Fase 4:** 28h  |  **Output:** 27 archivos nuevos + 9 modificados  |  **4 entidades nuevas**

---

## 🎯 Acción Inmediata

1. **AgroConecta Sprint AC4-1**: Crear las 4 entidades con handlers completos (gold standard OrderAgro)
2. **Page Builder Sprint PB-3**: Robustez Tests E2E — Eliminar fallbacks y verificar funcionalidad real
3. **Page Builder Sprint PB-4**: Traits Commerce/Social configurables
4. **Bloque D Sprint D1**: Crear módulo `jaraba_admin_center` con estructura base
