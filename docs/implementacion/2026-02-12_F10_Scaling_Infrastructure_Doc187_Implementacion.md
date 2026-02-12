# F10 — Scaling Infrastructure (Doc 187) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F10 de 12
**Estimacion:** 24-32h
**Dependencias:** Monitoring stack (Prometheus/Grafana), backup strategy doc

---

## 1. Objetivo

Implementar backup/restore per-tenant, tests de performance k6
multi-tenant y documentacion de escalado horizontal en 3 fases.

## 2. Estado Actual (25% implementado)

| Componente | Estado |
|------------|--------|
| Backup strategy doc | Existe (RPO 24h, RTO 4h) |
| Self-healing scripts (3) | Existen (db, qdrant, cache) |
| k6 load_test.js | Existe (smoke/load/stress, 4 escenarios) |
| Prometheus alerts (25+) | Existen (service, db, qdrant, billing, ssl, system) |
| Rollback script | Existe (7-step automated) |
| Per-tenant restore | NO existe |
| k6 multi-tenant scenarios | NO existe |
| Scaling threshold alerts | NO existe |
| Horizontal scaling guide | NO existe |

## 3. Entregables

### 3.1 restore_tenant.sh

Script bash para backup/restore de un solo tenant:
- Modo `backup`: exporta grupo + entidades relacionadas via tenant_id
- Modo `restore`: importa desde fichero de backup
- Compatible con Lando (dev) y IONOS (prod)
- Tablas: groups, crm_*, ai_agent_execution, social_*, email_*, etc.
- Safety: backup previo antes de restore, verificacion de integridad

### 3.2 k6 Multi-Tenant Load Test

Extender suite k6 con escenarios multi-tenant:
- Concurrent tenant API calls (CRM, AI agents, analytics)
- Pipeline operations (create/move/forecast)
- Tenant isolation verification
- Scaling breakpoint test (ramp to 100 VUs)
- Thresholds per-tenant: p95 < 500ms, error < 1%

### 3.3 Prometheus Scaling Alerts

Nuevas reglas de alerta para umbrales de escalado:
- TenantConcurrencyHigh: > 50 sesiones concurrentes/tenant
- APIResponseDegradation: p95 > 2s durante 5min
- QueueDepthCritical: > 1000 items en cola
- TenantCountScaleUp: > 100 tenants activos
- DatabaseConnectionsScaleUp: > 80% pool

### 3.4 Horizontal Scaling Guide

Documentacion de 3 fases:
- Fase 1: Single Server (IONOS L-16, hasta 50 tenants)
- Fase 2: Separated DB (app + DB separados, hasta 200 tenants)
- Fase 3: Load Balanced (multiple app, read replicas, hasta 1000+ tenants)

## 4. Archivos Creados

| Archivo | Tipo |
|---------|------|
| scripts/restore_tenant.sh | Nuevo |
| tests/performance/multi_tenant_load_test.js | Nuevo |
| monitoring/prometheus/rules/scaling_alerts.yml | Nuevo |
| docs/arquitectura/scaling-horizontal-guide.md | Nuevo |

## 5. Verificacion

- [ ] restore_tenant.sh tiene estructura correcta (backup/restore modes)
- [ ] k6 test tiene syntax JS valida y 4+ escenarios
- [ ] Prometheus alerts son YAML valido con 5+ reglas
- [ ] Scaling guide cubre 3 fases con umbrales y diagramas
