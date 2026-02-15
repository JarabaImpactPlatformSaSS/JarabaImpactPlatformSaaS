---
description: Implementación de gaps del vertical Empleabilidad, sistema de autoaprendizaje IA y elevación a clase mundial
---

# Workflow: Implementación Gaps Empleabilidad Q1 2026

Este workflow guía la implementación del plan de cierre de gaps identificados en la auditoría técnica del vertical Empleabilidad, incluyendo la elevación a clase mundial (10 fases).

## Estado de Implementación — Gaps Iniciales (Q1 2026)

| Fase | Estado | Fecha |
|------|--------|-------|
| Fase 1: Autoaprendizaje IA | ✅ Completado | 2026-01-17 |
| Fase 2: Matching Semántico | ✅ Completado | 2026-01-17 |
| Fase 3: Open Badges + Gamificación | ✅ Completado | 2026-01-17 |
| Fase 4: Recommendation ML | ✅ Completado | 2026-01-17 |
| Best Practices | ✅ Completado | 2026-01-17 |
| **Fase 5: Automatizaciones ECA** | ✅ Completado | 2026-01-17 |

## Estado de Implementación — Elevación a Clase Mundial (2026-02-15)

| Fase | Descripción | Estado | Fecha |
|------|-------------|--------|-------|
| Fase 1 | Clean Page Templates (Zero Region + FAB) | ✅ Completado | 2026-02-15 |
| Fase 2 | Sistema Modal CRUD (data-dialog-type) | ✅ Completado | 2026-02-15 |
| Fase 3 | SCSS Compliance (45+ rgba→color-mix) | ✅ Completado | 2026-02-15 |
| Fase 4 | Feature Gating (EmployabilityFeatureGateService) | ✅ Completado | 2026-02-15 |
| Fase 5 | Upgrade Triggers + Upsell IA | ✅ Completado | 2026-02-15 |
| Fase 6 | Email Sequences (5 secuencias MJML) | ✅ Completado | 2026-02-15 |
| Fase 7 | CRM Integration (7 estados pipeline) | ✅ Completado | 2026-02-15 |
| Fase 8 | Cross-Vertical Bridges (4 bridges) | ✅ Completado | 2026-02-15 |
| Fase 9 | AI Journey Progression Proactiva (7 reglas) | ✅ Completado | 2026-02-15 |
| Fase 10 | Health Scores + KPIs (5 dimensiones + 8 KPIs) | ✅ Completado | 2026-02-15 |

### Servicios Nuevos (Elevación)

| Servicio | Fase | Módulo |
|----------|------|--------|
| `EmployabilityFeatureGateService` | 4 | ecosistema_jaraba_core |
| `FeatureGateResult` (ValueObject) | 4 | ecosistema_jaraba_core |
| `EmployabilityEmailSequenceService` | 6 | ecosistema_jaraba_core |
| `TemplateLoaderService` | 6 | jaraba_diagnostic |
| `EmployabilityCrossVerticalBridgeService` | 8 | ecosistema_jaraba_core |
| `EmployabilityJourneyProgressionService` | 9 | ecosistema_jaraba_core |
| `EmployabilityCopilotAgent` | 5, 9 | jaraba_candidate |
| `CopilotApiController` (proactive) | 9 | jaraba_candidate |
| `EmployabilityHealthScoreService` | 10 | ecosistema_jaraba_core |

### Documentación

- **Plan:** `docs/implementacion/2026-02-15_Plan_Elevacion_Clase_Mundial_Vertical_Empleabilidad_v1.md`
- **Aprendizaje:** `docs/tecnicos/aprendizajes/2026-02-15_empleabilidad_elevacion_10_fases.md`
- **Arquitectura:** v27.0.0 | **Directrices:** v27.0.0

---

## ✅ Módulo ECA: Estrategia Adoptada

> **Decisión Arquitectónica:** Implementar automatizaciones via **hooks nativos de Drupal** en lugar de modelos YAML de ECA.
> 
> **Razón:** El modelador BPMN.IO genera configuraciones con referencias cruzadas complejas que hacen impracticable la creación manual de YAML.

### Flujos ECA Implementados (Hooks Nativos)

| Flujo | Módulo | Implementación | Estado |
|-------|--------|----------------|--------|
| Auto-enrollment | jaraba_lms | `DiagnosticEnrollmentService` | ✅ |
| Badge automático | jaraba_lms | `hook_entity_update` → `OpenBadgeService` | ✅ |
| XP automático | jaraba_lms | `hook_entity_insert/update` → `GamificationService` | ✅ |
| Notif. candidaturas | jaraba_job_board | `ApplicationNotificationService` (queue) | ✅ |
| Créditos impacto | ecosistema_jaraba_core | `ImpactCreditService` | ✅ |
| Job Alerts | jaraba_job_board | `JobAlertMatchingService` | ✅ |
| Company Follow | jaraba_job_board | `JobAlertMatchingService.processCompanyFollows()` | ✅ |
| Web Push | jaraba_job_board | `WebPushService` (VAPID) | ✅ |
| Digest diario | jaraba_job_board | `hook_cron` (9:00 AM) | ✅ |
| Embedding auto | jaraba_matching | `hook_entity_insert/update/delete` | ✅ |

---

## Fase 5: Automatizaciones ECA (Hooks Nativos) ✅

### 5.1 Servicios Core ✅

- [x] `ImpactCreditService` - Créditos de impacto (+20 apply, +500 hired)
- [x] `ApplicationNotificationService` - Notificaciones email/push
- [x] `JobAlertMatchingService` - Matching ofertas vs alertas
- [x] `WebPushService` - Push notifications (VAPID)
- [x] `DiagnosticEnrollmentService` - Auto-enrollment post-diagnóstico

### 5.2 Hooks Implementados ✅

- [x] `jaraba_lms_entity_update()` - Badge + XP on course complete
- [x] `jaraba_lms_entity_insert()` - XP on lesson complete
- [x] `jaraba_job_board_entity_insert()` - Créditos + notif on apply
- [x] `jaraba_job_board_entity_update()` - Notif + ML feedback on status change
- [x] `jaraba_job_board_cron()` - Email queue, alert queue, digest

### 5.3 Tablas Creadas ✅

- [x] `impact_credits_log` - Log de créditos
- [x] `impact_credits_balance` - Balance por usuario
- [x] `job_alert` - Alertas de empleo
- [x] `company_follow` - Seguimiento empresas
- [x] `push_subscription` - Suscripciones push
- [x] `diagnostic_result` - Resultados diagnóstico

---

## ✅ Todos los Gaps Cerrados

| Gap | Solución Implementada | Fecha |
|-----|----------------------|-------|
| ~~Flujos ECA~~ | Hooks nativos de Drupal | 2026-01-17 |
| ~~Triggers Embeddings~~ | `jaraba_matching_entity_insert/update/delete` | 2026-01-17 |
| ~~Alertas automatizadas~~ | `JobAlertMatchingService` | 2026-01-17 |
| ~~Notificaciones~~ | `ApplicationNotificationService` | 2026-01-17 |
| ~~Web Push~~ | `WebPushService` (VAPID) | 2026-01-17 |

---

## Verificación Final

// turbo
```bash
lando drush cr
lando drush uli
```

1. ✅ Verificar Dashboard Insights en `/admin/insights/copilot`
2. ✅ Probar matching semántico con candidato demo
3. ✅ Verificar gamificación en `/my-learning/gamification`
4. ✅ Verificar leaderboard en `/leaderboard`
5. ✅ Emitir badge automático (completar curso al 100%)
6. ✅ Verificar créditos de impacto al aplicar a oferta

---

## Documentación Actualizada

- [x] `docs/00_DIRECTRICES_PROYECTO.md` (v3.4.0 → v27.0.0)
- [x] `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` (v27.0.0)
- [x] `docs/00_INDICE_GENERAL.md` (v4.6.0 → v43.0.0)
- [x] `docs/tecnicos/20260117g-Auditoria_ECA_Empleabilidad_v1.md`
- [x] Knowledge Base: `2026-01-17_learnings_hooks_eca.md`
- [x] `docs/implementacion/2026-02-15_Plan_Elevacion_Clase_Mundial_Vertical_Empleabilidad_v1.md`
- [x] `docs/tecnicos/aprendizajes/2026-02-15_empleabilidad_elevacion_10_fases.md`
