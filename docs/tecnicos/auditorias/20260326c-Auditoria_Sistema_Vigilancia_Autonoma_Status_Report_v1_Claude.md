# Auditoria del Sistema de Vigilancia Autonoma de Status Report (STATUS-REPORT-PROACTIVE-001)

**Fecha de creacion:** 2026-03-26 18:00
**Ultima actualizacion:** 2026-03-26 18:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Metodologia:** Arquitecto SaaS Senior, Ingeniero de Software Senior, Ingeniero de Drupal Senior
**Referencia previa:** CLAUDE.md v1.10.0 — STATUS-REPORT-PROACTIVE-001
**Ambito:** Ecosistema Jaraba — ecosistema_jaraba_core, jaraba_rag, jaraba_insights_hub
**Documentos fuente:** 00_DIRECTRICES_PROYECTO v167, 00_DOCUMENTO_MAESTRO_ARQUITECTURA v151
**Evento desencadenante:** Error en produccion `/admin/reports/status` — Qdrant host falso positivo + SEO notification service no registrado

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Incidente Desencadenante](#3-incidente-desencadenante)
4. [Arquitectura del Sistema de Vigilancia](#4-arquitectura-del-sistema-de-vigilancia)
5. [Hallazgos Criticos (2)](#5-hallazgos-criticos-2)
6. [Hallazgos Altos (2)](#6-hallazgos-altos-2)
7. [Hallazgos Medios (1)](#7-hallazgos-medios-1)
8. [Hallazgos Bajos (2)](#8-hallazgos-bajos-2)
9. [Areas Aprobadas (PASS)](#9-areas-aprobadas-pass)
10. [Matriz de Riesgo Consolidada](#10-matriz-de-riesgo-consolidada)
11. [Analisis de Impacto en el SaaS](#11-analisis-de-impacto-en-el-saas)
12. [Arquitectura Actual vs Clase Mundial](#12-arquitectura-actual-vs-clase-mundial)
13. [Plan de Remediacion Priorizado](#13-plan-de-remediacion-priorizado)
14. [Nuevas Reglas Propuestas](#14-nuevas-reglas-propuestas)
15. [Glosario de Terminos](#15-glosario-de-terminos)
16. [Registro de Cambios](#16-registro-de-cambios)

---

## 1. Resumen Ejecutivo

**Puntuacion Global: 6.5/10**

El sistema STATUS-REPORT-PROACTIVE-001 implementa un modelo de vigilancia en 3 capas (cron runtime, validador CI, GitHub Actions diario) que cubre el ciclo completo de deteccion. Sin embargo, presenta **gaps criticos en la cadena de alerta** que permitieron que un ERROR de produccion persistiera sin notificacion proactiva durante al menos 24 horas.

### Distribucion de Hallazgos

| Severidad | Cantidad | Descripcion |
|-----------|----------|-------------|
| CRITICA   | 2        | Sin email fallback en monitor runtime, baseline triplicada sin SSOT |
| ALTA      | 2        | hook_requirements no respeta degradacion graceful, shouldRun() dead code |
| MEDIA     | 1        | Webhook config no versionada en config/sync |
| BAJA      | 2        | SSH path hardcoded en workflow, falta mail template |
| PASS      | 8        | Service registration, cron wiring, snapshot persistence, etc. |

---

## 2. Contexto y Alcance

### Infraestructura Auditada

| Componente | Ubicacion | Lineas |
|-----------|-----------|--------|
| StatusReportMonitorService | ecosistema_jaraba_core/src/Service/ | 254 |
| AlertingService | ecosistema_jaraba_core/src/Service/ | 322 |
| AlertingSettingsForm | ecosistema_jaraba_core/src/Form/ | 268 |
| validate-status-report.php | scripts/validation/ | 151 |
| diagnose-status.yml | .github/workflows/ | 248 |
| ci-notify-email.php | scripts/ | 72 |
| hook_cron integration | ecosistema_jaraba_core.module | L2785-2804 |
| hook_mail (23 tipos) | ecosistema_jaraba_core.module | L948-1548 |
| hook_requirements (jaraba_rag) | jaraba_rag.install | L173-216 |
| hook_requirements (insights_hub) | jaraba_insights_hub.install | L78-119 |

### Verticales Afectadas

Todos los 10 verticales del SaaS dependen del status report para monitoreo de salud, ya que hook_requirements es transversal a los 94 modulos custom.

---

## 3. Incidente Desencadenante

**Fecha:** 2026-03-26
**Severidad de impacto:** ALTA

### Cronologia

1. Se despliega SEO-DEPLOY-NOTIFY-001 (Google Indexing Automation) con implementacion parcial
2. `jaraba_insights_hub.seo_notification` service se registra en services.yml pero un pre-commit hook lo revierte silenciosamente
3. `SeoNotificationLog` ContentEntity se crea sin hook_update_N()
4. En produccion, `jaraba_rag` hook_requirements muestra ERROR por Qdrant host vacio — cuando RAG esta intencionalmente deshabilitado (degradacion graceful)
5. El sistema de vigilancia autonoma **no alerto** sobre ninguno de estos errores

### Causa Raiz

El sistema de vigilancia tiene 3 capas funcionales, pero:
- **Capa 1 (cron)**: Alerta a Slack/Teams — si webhooks no estan configurados, las alertas se pierden silenciosamente
- **Capa 2 (CI)**: Funciona correctamente via validate-all.sh pero solo corre en PRs/pushes, no en produccion
- **Capa 3 (GitHub Actions)**: Funciona correctamente (cron diario), pero el error ocurrio ENTRE ejecuciones

---

## 4. Arquitectura del Sistema de Vigilancia

### Diagrama de Flujo E2E

```
CAPA 1: RUNTIME (cada 6h via hook_cron)
  ecosistema_jaraba_core.module::_cron_extended()
    -> StatusReportMonitorService::check()
      -> invokeAll('requirements', 'runtime')
      -> diff(current, previousSnapshot)
      -> alert() -> AlertingService -> Slack/Teams
                  -> Logger (watchdog)
                  X No email fallback

CAPA 2: CI/CD (en cada push/PR)
  .github/workflows/ci.yml
    -> validate-all.sh --full
      -> validate-status-report.php
        -> drush core:requirements --format=json
        -> exit 0 (PASS) / 1 (FAIL) / 2 (WARN)

CAPA 3: DIAGNOSTICO DIARIO (06:00 UTC)
  .github/workflows/diagnose-status.yml
    -> SSH a produccion
      -> drush core:requirements --format=json
      -> Python: clasificar errores/warnings
      -> Si errores: crear GitHub Issue + email
```

### Baseline de Warnings Esperados (TRIPLICADA)

| Ubicacion | Keys | Entorno |
|-----------|------|---------|
| StatusReportMonitorService::EXPECTED_WARNINGS | base_domain, experimental_modules, update_contrib, update_core | runtime |
| validate-status-report.php L35-47 | dev: 4 keys, prod: 1 key (experimental_modules) | dev/prod |
| diagnose-status.yml L67-72 | base_domain, experimental_modules, update_contrib, update_core | prod |

**PROBLEMA**: No existe una fuente unica de verdad (SSOT). Cambios en la baseline requieren editar 3 archivos.

---

## 5. Hallazgos Criticos (2)

### CRIT-001: Sin Email Fallback en StatusReportMonitorService

**Archivo:** `ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php` L209-252
**Regla violada:** Principio de defensa en profundidad

**Descripcion:**
`StatusReportMonitorService::alert()` solo envia alertas via `AlertingService` (Slack/Teams). Si los webhooks no estan configurados o fallan, las alertas se pierden silenciosamente. Solo queda el log en watchdog, que nadie monitorea proactivamente.

**Impacto:**
- En produccion IONOS, si los webhooks de Slack/Teams no estan configurados, NINGUN ser humano recibe notificacion de nuevos errores en status report
- El error de Qdrant persistio sin deteccion proactiva

**Evidencia:**
```php
// L209-252: alert() solo llama a AlertingService
protected function alert(array $changes): void {
    // ...
    if ($this->alerting !== NULL) {
        $this->alerting->send(...); // Slack/Teams
    }
    // Solo log, NO email fallback
    $this->logger->error('Status report monitor detected...');
}
```

**Comparacion:** El health check (`_ecosistema_jaraba_core_cron_health_check()`) SÍ envia email como fallback (L2905-2920). StatusReportMonitorService no sigue este patron.

### CRIT-002: Baseline Triplicada sin SSOT

**Archivos:** StatusReportMonitorService.php L40-45, validate-status-report.php L35-47, diagnose-status.yml L67-72
**Regla violada:** Principio DRY, ausencia de SSOT

**Descripcion:**
La lista de warnings esperados (baseline) esta definida en 3 ubicaciones independientes con inconsistencias:
- El script PHP distingue entre `dev` (4 keys) y `prod` (1 key)
- El servicio runtime usa 4 keys fijas (sin distincion de entorno)
- El workflow GitHub usa 4 keys fijas (para prod, donde deberia usar 1)

**Impacto:**
- Anadir un nuevo warning esperado requiere editar 3 archivos
- Inconsistencia entre entornos: diagnose-status.yml usa baseline de dev para prod

---

## 6. Hallazgos Altos (2)

### ALTA-001: hook_requirements No Respeta Degradacion Graceful (CORREGIDO)

**Archivo:** `jaraba_rag/jaraba_rag.install` L180-190
**Estado:** CORREGIDO en commit 809f52f99

**Descripcion:**
`jaraba_rag_requirements()` mostraba REQUIREMENT_ERROR cuando `vector_db.host` estaba vacio, sin verificar el flag `disabled` que `settings.jaraba_rag.php` establece para degradacion graceful.

**Fix aplicado:** Logica condicional que muestra WARNING cuando RAG esta intencionalmente deshabilitado.

### ALTA-002: shouldRun() es Dead Code

**Archivo:** `StatusReportMonitorService.php` L61-64
**Regla violada:** Codigo muerto reduce mantenibilidad

**Descripcion:**
El metodo `shouldRun()` implementa una guarda de intervalo (6h) pero NUNCA es llamado. El cron ya implementa su propia guarda identica (`six_hour_check_last`). Resultado: doble guarda de intervalo, una activa y otra muerta.

**Riesgo:** Un desarrollador futuro podria llamar `check()` directamente sin la guarda de cron, ejecutando el monitor en cada request.

---

## 7. Hallazgos Medios (1)

### MEDIA-001: Webhook Config No Versionada

**Archivo:** `ecosistema_jaraba_core.alerting` config
**Regla violada:** Principio de infraestructura como codigo

**Descripcion:**
Las URLs de webhook de Slack/Teams se configuran via UI (AlertingSettingsForm) pero NO se exportan a config/sync. Si se ejecuta `drush cim`, se pierden. Si se reconstruye el entorno, hay que reconfigurar manualmente.

**Recomendacion:** Usar variables de entorno via settings.secrets.php (patron SECRET-MGMT-001) para webhooks.

---

## 8. Hallazgos Bajos (2)

### BAJA-001: SSH Path Hardcoded en diagnose-status.yml

**Archivo:** `.github/workflows/diagnose-status.yml` L57
**Descripcion:** `cd /var/www/jaraba` hardcodeado. Fragil si cambia la ruta del proyecto en el servidor.

### BAJA-002: Falta Mail Template status_report_alert

**Archivo:** `ecosistema_jaraba_core.module` hook_mail
**Descripcion:** De los 23 tipos de email definidos en hook_mail, no existe `status_report_alert`. Necesario para implementar el email fallback de CRIT-001.

---

## 9. Areas Aprobadas (PASS)

| Area | Veredicto | Detalle |
|------|-----------|---------|
| Service registration (services.yml) | PASS | 4 args correctos, @? para alerting |
| Cron wiring (hook_cron) | PASS | try-catch \Throwable, hasService() guard |
| Snapshot persistence (State API) | PASS | Sobrevive cache clears |
| Diff algorithm | PASS | Detecta nuevos errores, nuevos warnings, y resueltos |
| CI integration (validate-all.sh) | PASS | validate-status-report.php en linea 496 |
| GitHub Actions issue creation | PASS | Labels, timestamp, multi-severity |
| GitHub Actions email fallback | PASS | ci-notify-email.php via SMTP |
| Error handling resilience | PASS | \Throwable en todas las capas |

---

## 10. Matriz de Riesgo Consolidada

| ID | Hallazgo | Probabilidad | Impacto | Riesgo | Prioridad |
|----|----------|-------------|---------|--------|-----------|
| CRIT-001 | Sin email fallback | ALTA | ALTO | CRITICO | P0 |
| CRIT-002 | Baseline triplicada | MEDIA | MEDIO | ALTO | P1 |
| ALTA-001 | Qdrant falso positivo | CONFIRMADA | ALTO | CORREGIDO | - |
| ALTA-002 | shouldRun() dead code | BAJA | MEDIO | MEDIO | P2 |
| MEDIA-001 | Webhook no versionada | MEDIA | MEDIO | MEDIO | P2 |
| BAJA-001 | SSH hardcoded | BAJA | BAJO | BAJO | P3 |
| BAJA-002 | Falta mail template | ALTA (bloquea P0) | MEDIO | ALTO | P0 |

---

## 11. Analisis de Impacto en el SaaS

### Impacto en Tenants
Los tenants NO son directamente afectados por el status report — es una herramienta de operaciones internas. Sin embargo, errores no detectados (como schema mismatches o servicios no registrados) pueden causar:
- 500 errors en rutas de admin
- Container corruption que afecta a todas las rutas
- Degradacion silenciosa de features (RAG, SEO notifications)

### Impacto en Compliance
Para un SaaS clase mundial con IA nativa, el monitoring proactivo es un diferenciador. La ausencia de email fallback contradice el principio de "IA que aprende y es proactiva" que define la identidad del ecosistema.

---

## 12. Arquitectura Actual vs Clase Mundial

| Aspecto | Actual | Clase Mundial |
|---------|--------|---------------|
| Deteccion | 3 capas (cron/CI/daily) | 3 capas + realtime webhook |
| Alerting | Slack/Teams solo | Email + Slack/Teams + SMS para CRIT |
| Baseline | Triplicada, inconsistente | SSOT centralizada en config |
| Email | Solo en GitHub Actions | En todas las capas como fallback |
| Dead code | shouldRun() sin usar | Zero dead code |
| Config | Webhooks en runtime | Webhooks via env vars (SECRET-MGMT-001) |

---

## 13. Plan de Remediacion Priorizado

### Sprint P0 (Inmediato)
1. **CRIT-001 + BAJA-002**: Anadir mail template `status_report_alert` + email fallback en StatusReportMonitorService::alert()
2. Integrar `shouldRun()` en el flujo de cron o eliminarlo

### Sprint P1 (Siguiente iteracion)
3. **CRIT-002**: Centralizar baseline en constante PHP compartida
4. Mover webhook URLs a variables de entorno (SECRET-MGMT-001)

### Sprint P2 (Mejora continua)
5. Extraer path del servidor a variable en diagnose-status.yml
6. Anadir test unitario para StatusReportMonitorService::diff()

---

## 14. Nuevas Reglas Propuestas

| Regla | Descripcion |
|-------|-------------|
| ALERTING-EMAIL-FALLBACK-001 | Todo servicio de alerta DEBE tener email como canal de ultimo recurso. Si AlertingService no tiene webhooks configurados, DEBE enviar email al admin del sitio |
| STATUS-BASELINE-SSOT-001 | La lista de warnings esperados DEBE definirse en una unica ubicacion (constante o config) y referenciarse desde todas las capas |
| MONITOR-NO-DEADCODE-001 | Metodos publicos en servicios de monitoring DEBEN estar referenciados por al menos 1 consumidor. Dead code en monitoring = alerta silenciada |

---

## 15. Glosario de Terminos

| Sigla | Significado |
|-------|-------------|
| SSOT | Single Source of Truth — fuente unica de verdad |
| STATUS-REPORT-PROACTIVE-001 | Regla del proyecto que define el sistema de monitoring proactivo en 3 capas |
| REQUIREMENT_ERROR | Constante Drupal (valor 2) que indica error critico en hook_requirements |
| REQUIREMENT_WARNING | Constante Drupal (valor 1) que indica advertencia en hook_requirements |
| State API | API de Drupal para persistir datos clave-valor que sobreviven cache clears |
| Degradacion graceful | Patron donde una feature se desactiva sin romper el sistema |
| RAG | Retrieval-Augmented Generation — busqueda semantica con IA |
| MJML | Markup language para emails responsive |
| DRY | Don't Repeat Yourself — principio de no duplicar codigo/config |

---

## 16. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-26 | 1.0.0 | Auditoria inicial: 2 CRIT, 2 ALTA, 1 MEDIA, 2 BAJA, 8 PASS |
