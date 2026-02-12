---
description: Implementación de gaps del vertical Empleabilidad y sistema de autoaprendizaje IA
---

# Workflow: Implementación Gaps Empleabilidad Q1 2026

Este workflow guía la implementación del plan de cierre de gaps identificados en la auditoría técnica del vertical Empleabilidad.

## Estado de Implementación

| Fase | Estado | Fecha |
|------|--------|-------|
| Fase 1: Autoaprendizaje IA | ✅ Completado | 2026-01-17 |
| Fase 2: Matching Semántico | ✅ Completado | 2026-01-17 |
| Fase 3: Open Badges + Gamificación | ✅ Completado | 2026-01-17 |
| Fase 4: Recommendation ML | ✅ Completado | 2026-01-17 |
| Best Practices | ✅ Completado | 2026-01-17 |
| **Fase 5: Automatizaciones ECA** | ✅ Completado | 2026-01-17 |

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

- [x] `docs/00_DIRECTRICES_PROYECTO.md` (v3.4.0)
- [x] `docs/00_INDICE_GENERAL.md` (v4.6.0)
- [x] `docs/tecnicos/20260117g-Auditoria_ECA_Empleabilidad_v1.md`
- [x] Knowledge Base: `2026-01-17_learnings_hooks_eca.md`
