# Plan de Implementacion: Vigilancia Autonoma de Status Report — Clase Mundial

**Fecha de creacion:** 2026-03-26 18:30
**Ultima actualizacion:** 2026-03-26 18:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Metodologia:** Arquitecto SaaS Senior, Ingeniero de Software Senior, Ingeniero de Drupal Senior
**Referencia previa:** 20260326c-Auditoria_Sistema_Vigilancia_Autonoma_Status_Report_v1_Claude.md
**Ambito:** ecosistema_jaraba_core — StatusReportMonitorService, AlertingService, hook_mail
**Documentos fuente:** 00_DIRECTRICES_PROYECTO v167, CLAUDE.md v1.10.0
**Evento desencadenante:** Auditoria revela 7 gaps en sistema de vigilancia autonoma

---

## Tabla de Contenidos

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Pre-Implementacion: Checklist de Directrices](#3-pre-implementacion-checklist-de-directrices)
4. [Infraestructura Existente](#4-infraestructura-existente)
   - 4.1 [StatusReportMonitorService](#41-statusreportmonitorservice)
   - 4.2 [AlertingService](#42-alertingservice)
   - 4.3 [hook_mail (23 tipos existentes)](#43-hook_mail-23-tipos-existentes)
   - 4.4 [validate-status-report.php](#44-validate-status-reportphp)
   - 4.5 [diagnose-status.yml](#45-diagnose-statusyml)
5. [Sprint A — P0: Email Fallback + Baseline SSOT](#5-sprint-a--p0-email-fallback--baseline-ssot)
   - 5.1 [Tarea A1: Mail Template status_report_alert](#51-tarea-a1-mail-template-status_report_alert)
   - 5.2 [Tarea A2: Email Fallback en StatusReportMonitorService](#52-tarea-a2-email-fallback-en-statusreportmonitorservice)
   - 5.3 [Tarea A3: Baseline SSOT Centralizada](#53-tarea-a3-baseline-ssot-centralizada)
   - 5.4 [Tarea A4: Eliminar Dead Code shouldRun()](#54-tarea-a4-eliminar-dead-code-shouldrun)
6. [Sprint B — P1: Webhook Config via Env Vars](#6-sprint-b--p1-webhook-config-via-env-vars)
   - 6.1 [Tarea B1: Mover Webhooks a Variables de Entorno](#61-tarea-b1-mover-webhooks-a-variables-de-entorno)
7. [Sprint C — P2: Hardening Workflow](#7-sprint-c--p2-hardening-workflow)
   - 7.1 [Tarea C1: Parametrizar Path SSH en diagnose-status.yml](#71-tarea-c1-parametrizar-path-ssh-en-diagnose-statusyml)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia: Specs a Archivos](#9-tabla-de-correspondencia-specs-a-archivos)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Verificacion Post-Implementacion (RUNTIME-VERIFY-001)](#11-verificacion-post-implementacion-runtime-verify-001)
12. [Testing Strategy](#12-testing-strategy)
13. [Variables de Entorno y Secrets](#13-variables-de-entorno-y-secrets)
14. [Rollback Plan](#14-rollback-plan)
15. [Glosario](#15-glosario)
16. [Registro de Cambios](#16-registro-de-cambios)

---

## 1. Objetivos y Alcance

### Objetivo Principal
Elevar el sistema de vigilancia autonoma STATUS-REPORT-PROACTIVE-001 de 6.5/10 a 10/10 clase mundial, garantizando que **ningun error en `/admin/reports/status` pase desapercibido** en produccion.

### Objetivos Especificos
1. **Email como canal de ultimo recurso**: Si Slack/Teams fallan o no estan configurados, el admin recibe email
2. **SSOT de baseline**: Una unica fuente de verdad para warnings esperados
3. **Zero dead code**: Eliminar shouldRun() o integrarlo
4. **Webhooks seguros**: Configuracion via env vars (SECRET-MGMT-001)
5. **Workflow robusto**: Paths parametrizados en GitHub Actions

### Fuera de Alcance
- Integracion con SMS/PagerDuty (futuro)
- Monitoring de metricas de rendimiento (cubierto por WebVitals)
- Cambios en la UI de admin del status report

---

## 2. Principios Arquitectonicos

| Principio | Aplicacion |
|-----------|-----------|
| **Defensa en profundidad** | Email como ultimo recurso cuando Slack/Teams fallan |
| **SSOT (STATUS-BASELINE-SSOT-001)** | Baseline en constante PHP compartida |
| **SECRET-MGMT-001** | Webhooks via env vars, nunca en config/sync |
| **PRESAVE-RESILIENCE-001** | try-catch \Throwable en toda alerta |
| **DRY** | Eliminar duplicacion de baseline |
| **Zero dead code** | Todo metodo publico debe tener consumidor |

---

## 3. Pre-Implementacion: Checklist de Directrices

| Directriz | Status | Notas |
|-----------|--------|-------|
| TENANT-001 | N/A | Status report es transversal, no per-tenant |
| SECRET-MGMT-001 | APLICA | Webhooks DEBEN ir a env vars |
| PRESAVE-RESILIENCE-001 | APLICA | Envio de email en try-catch |
| LOGGER-INJECT-001 | VERIFICADO | Logger inyectado correctamente en service |
| PHANTOM-ARG-001 | VERIFICAR | Al modificar constructor, validar args |
| OPTIONAL-PARAM-ORDER-001 | VERIFICAR | AlertingService es @? (ultimo param) |
| CONTAINER-DEPS-002 | N/A | Sin dependencias circulares nuevas |
| UPDATE-HOOK-REQUIRED-001 | N/A | Sin nuevas entities |

---

## 4. Infraestructura Existente

### 4.1 StatusReportMonitorService

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php`
**Lineas:** 254
**Constructor:** 4 args (StateInterface, LoggerInterface, ModuleHandlerInterface, ?AlertingService)

**Metodos actuales:**
- `shouldRun(): bool` — Verifica intervalo 6h (DEAD CODE — nunca llamado)
- `check(): array` — Ejecuta comparacion snapshot, detecta cambios, alerta
- `getCurrentRequirements(): array` — Invoca hook_requirements de todos los modulos
- `getPreviousSnapshot(): array` — Lee snapshot anterior del State API
- `diff(current, previous): array` — Computa errores nuevos, warnings nuevos, resueltos
- `alert(changes): void` — Envia via AlertingService (SIN email fallback)

**Constantes:**
- `STATE_SNAPSHOT = 'jaraba_status_report.last_snapshot'`
- `STATE_LAST_CHECK = 'jaraba_status_report.last_check'`
- `CHECK_INTERVAL = 21600` (6 horas)
- `EXPECTED_WARNINGS = [4 keys]` — baseline hardcodeada

### 4.2 AlertingService

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/AlertingService.php`
**Lineas:** 322
**Canales:** Slack (Incoming Webhooks) + Microsoft Teams (MessageCard) + Logger
**Config:** `ecosistema_jaraba_core.alerting` (slack_webhook_url, teams_webhook_url, slack_enabled, teams_enabled)

### 4.3 hook_mail (23 tipos existentes)

**Archivo:** `ecosistema_jaraba_core.module` L948-1548
**Tipo relevante existente:** `health_alert` (L1367) — alerta de health check degradado
**Tipo necesario:** `status_report_alert` — NO EXISTE, hay que crearlo

### 4.4 validate-status-report.php

**Archivo:** `scripts/validation/validate-status-report.php` (151 lineas)
**Integracion CI:** Llamado desde validate-all.sh L496
**Baseline:** Dev (4 keys) vs Prod (1 key) — con distincion de entorno

### 4.5 diagnose-status.yml

**Archivo:** `.github/workflows/diagnose-status.yml` (248 lineas)
**Schedule:** Daily 06:00 UTC
**Acciones:** SSH a prod, drush requirements, crear GitHub Issue si errores, email via ci-notify-email.php
**Baseline:** 4 keys hardcodeadas (inconsistente con prod baseline del script PHP)

---

## 5. Sprint A — P0: Email Fallback + Baseline SSOT

### 5.1 Tarea A1: Mail Template status_report_alert

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module`
**Ubicacion:** Dentro de `ecosistema_jaraba_core_mail()`, despues del case `health_alert`

**Logica:**
Anadir un nuevo case en hook_mail para `status_report_alert` que genere un email con:
- Subject: "Alerta Status Report: N error(es), M warning(s) — Ecosistema Jaraba"
- Body: Lista de errores/warnings nuevos detectados con titulo y valor
- Footer: Link a /admin/reports/status y timestamp

**Patron a seguir:** Identico al case `health_alert` existente (L1367-1395).

### 5.2 Tarea A2: Email Fallback en StatusReportMonitorService

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php`

**Cambios requeridos:**

1. **Anadir dependencias:** `MailManagerInterface` y `ConfigFactoryInterface` al constructor
2. **Modificar alert():** Despues de intentar AlertingService, si falla o no esta configurado, enviar email
3. **Logica de email:**
   - Destinatario: `system.site.mail` (email del admin del sitio)
   - Throttling: Maximo 1 email cada 6 horas (ya controlado por CHECK_INTERVAL)
   - Solo enviar si hay errores o warnings NUEVOS (ya filtrado por diff())

**Constructor actualizado:**
```php
public function __construct(
  protected StateInterface $state,
  protected LoggerInterface $logger,
  protected ModuleHandlerInterface $moduleHandler,
  protected ConfigFactoryInterface $configFactory,
  protected MailManagerInterface $mailManager,
  protected ?AlertingService $alerting = NULL,
) {}
```

**IMPORTANTE:** Parametros opcionales (@?) DEBEN ir al final (OPTIONAL-PARAM-ORDER-001).

### 5.3 Tarea A3: Baseline SSOT Centralizada

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php`

**Cambios requeridos:**

1. **Convertir EXPECTED_WARNINGS en constante publica:**
```php
public const EXPECTED_WARNINGS_DEV = [
  'ecosistema_jaraba_base_domain',
  'experimental_modules',
  'update_contrib',
  'update_core',
];

public const EXPECTED_WARNINGS_PROD = [
  'experimental_modules',
];
```

2. **Metodo publico para obtener baseline segun entorno:**
```php
public static function getExpectedWarnings(string $env = 'dev'): array {
  return $env === 'prod' ? self::EXPECTED_WARNINGS_PROD : self::EXPECTED_WARNINGS_DEV;
}
```

3. **Modificar validate-status-report.php** para usar la constante:
```php
require_once 'web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php';
$baseline = StatusReportMonitorService::getExpectedWarnings($env);
```

4. **Modificar diagnose-status.yml** para leer baseline desde PHP:
```yaml
- name: Get baseline
  run: php -r "require 'web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php'; echo json_encode(Drupal\ecosistema_jaraba_core\Service\StatusReportMonitorService::getExpectedWarnings('prod'));"
```

**Nota:** La constante es autoloading-free (no requiere bootstrap Drupal) porque es una constante de clase estatica.

### 5.4 Tarea A4: Eliminar Dead Code shouldRun()

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/StatusReportMonitorService.php`

**Accion:** Eliminar el metodo `shouldRun()` y la constante `CHECK_INTERVAL` ya que el throttling de 6h es responsabilidad de hook_cron (`six_hour_check_last`).

**Alternativa:** Si se quiere mantener la constante para documentar el intervalo, renombrar a `RECOMMENDED_INTERVAL` y eliminar solo `shouldRun()`.

---

## 6. Sprint B — P1: Webhook Config via Env Vars

### 6.1 Tarea B1: Mover Webhooks a Variables de Entorno

**Archivos a modificar:**
- `config/deploy/settings.secrets.php` — anadir mapping
- `ecosistema_jaraba_core/src/Service/AlertingService.php` — leer de config (ya lo hace)
- `.github/workflows/deploy.yml` — anadir secrets

**Logica:**
```php
// settings.secrets.php
if ($slack_url = getenv('ALERTING_SLACK_WEBHOOK_URL')) {
  $config['ecosistema_jaraba_core.alerting']['slack_webhook_url'] = $slack_url;
}
if ($teams_url = getenv('ALERTING_TEAMS_WEBHOOK_URL')) {
  $config['ecosistema_jaraba_core.alerting']['teams_webhook_url'] = $teams_url;
}
```

**GitHub Secrets necesarios:**
- `ALERTING_SLACK_WEBHOOK_URL`
- `ALERTING_TEAMS_WEBHOOK_URL`

---

## 7. Sprint C — P2: Hardening Workflow

### 7.1 Tarea C1: Parametrizar Path SSH en diagnose-status.yml

**Archivo a modificar:** `.github/workflows/diagnose-status.yml`

**Cambio:** Reemplazar `cd /var/www/jaraba` por variable de entorno:
```yaml
env:
  DEPLOY_PATH: /var/www/jaraba
```

---

## 8. Medidas de Salvaguarda

| # | Riesgo | Mitigacion | Regla |
|:-:|--------|-----------|-------|
| 1 | Email SMTP falla | try-catch en envio, log warning | PRESAVE-RESILIENCE-001 |
| 2 | Constructor mismatch tras cambio | validate-phantom-args.php en pre-commit | PHANTOM-ARG-001 |
| 3 | Baseline SSOT no autoloadable | Constantes estaticas de clase, no requieren bootstrap | — |
| 4 | Email spam (muchos errores) | Throttling natural: check() se ejecuta cada 6h max | STATUS-REPORT-PROACTIVE-001 |
| 5 | Email to wrong recipient | Usa system.site.mail (config verificada) | — |
| 6 | diagnose-status.yml rompe por baseline PHP | Fallback a baseline hardcoded si PHP falla | — |

---

## 9. Tabla de Correspondencia: Specs a Archivos

| Spec | Archivo | Linea/Seccion | Prioridad |
|------|---------|---------------|-----------|
| SP-01 | ecosistema_jaraba_core.module (hook_mail) | Nuevo case `status_report_alert` | P0 |
| SP-02 | StatusReportMonitorService.php | Modificar alert(), constructor | P0 |
| SP-03 | ecosistema_jaraba_core.services.yml | Actualizar args del service | P0 |
| SP-04 | StatusReportMonitorService.php | Constantes EXPECTED_WARNINGS_DEV/PROD | P0 |
| SP-05 | validate-status-report.php | Usar baseline centralizada | P1 |
| SP-06 | diagnose-status.yml | Usar baseline centralizada | P1 |
| SP-07 | StatusReportMonitorService.php | Eliminar shouldRun() | P0 |
| SP-08 | settings.secrets.php | Mapping webhook env vars | P1 |
| SP-09 | deploy.yml | Secrets ALERTING_SLACK/TEAMS | P1 |
| SP-10 | diagnose-status.yml | Parametrizar DEPLOY_PATH | P2 |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz | Cumplimiento | Detalle |
|-----------|-------------|---------|
| SECRET-MGMT-001 | Sprint B | Webhooks via env vars |
| PRESAVE-RESILIENCE-001 | Sprint A | try-catch en email |
| PHANTOM-ARG-001 | Sprint A | Validar tras cambio constructor |
| OPTIONAL-PARAM-ORDER-001 | Sprint A | ?AlertingService al final |
| LOGGER-INJECT-001 | Verificado | Ya correcto |
| CONTAINER-DEPS-002 | Verificado | Sin circulares |
| ALERTING-EMAIL-FALLBACK-001 | Sprint A | Nueva regla propuesta |
| STATUS-BASELINE-SSOT-001 | Sprint A | Nueva regla propuesta |
| MONITOR-NO-DEADCODE-001 | Sprint A | Nueva regla propuesta |

---

## 11. Verificacion Post-Implementacion (RUNTIME-VERIFY-001)

| Capa | Verificacion | Comando |
|------|-------------|---------|
| PHP | Services.yml args match constructor | `php scripts/validation/validate-phantom-args.php` |
| PHP | Optional deps correcto | `php scripts/validation/validate-optional-deps.php` |
| Runtime | StatusReportMonitorService cargable | `drush php:eval "\Drupal::service('ecosistema_jaraba_core.status_report_monitor');"` |
| Runtime | Email enviable | `drush php:eval "\Drupal::service('plugin.manager.mail')->mail('ecosistema_jaraba_core', 'status_report_alert', 'test@test.com', 'es', ['subject' => 'Test', 'body' => 'Test']);"` |
| CI | validate-status-report.php pasa | `php scripts/validation/validate-status-report.php` |
| Status | 0 errores, 0 warnings inesperados | `drush core:requirements --severity=2` |

---

## 12. Testing Strategy

### Unit Test: StatusReportMonitorService::diff()

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/StatusReportMonitorServiceTest.php`

**Casos a cubrir:**
1. Snapshot vacio (primera ejecucion) — todos los errores son "nuevos"
2. Error nuevo detectado
3. Warning nuevo detectado (no en baseline)
4. Warning en baseline ignorado
5. Error resuelto detectado
6. Sin cambios — no alerta
7. Email fallback se invoca cuando AlertingService es NULL

### Kernel Test: Email Delivery

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Kernel/Service/StatusReportMonitorEmailTest.php`

**Caso:** Verificar que hook_mail genera subject y body correctos para `status_report_alert`.

---

## 13. Variables de Entorno y Secrets

| Variable | Descripcion | Requerida | Entorno |
|----------|-------------|-----------|---------|
| ALERTING_SLACK_WEBHOOK_URL | URL del webhook de Slack | No | Produccion |
| ALERTING_TEAMS_WEBHOOK_URL | URL del webhook de Teams | No | Produccion |

**Nota:** Estas variables son opcionales. El sistema funciona sin ellas gracias al email fallback.

---

## 14. Rollback Plan

| Paso | Accion | Riesgo |
|------|--------|--------|
| 1 | Revertir StatusReportMonitorService al estado anterior | Bajo — solo pierde email fallback |
| 2 | Revertir services.yml al estado anterior | Bajo — restaura args originales |
| 3 | NO revertir hook_mail — el case nuevo no tiene efectos secundarios | Ninguno |
| 4 | NO revertir baseline SSOT — es solo refactor de constantes | Ninguno |

---

## 15. Glosario

| Sigla | Significado |
|-------|-------------|
| SSOT | Single Source of Truth — fuente unica de verdad |
| STATUS-REPORT-PROACTIVE-001 | Regla de 3 capas de monitoring proactivo |
| ALERTING-EMAIL-FALLBACK-001 | Nueva regla: email como canal de ultimo recurso |
| STATUS-BASELINE-SSOT-001 | Nueva regla: baseline centralizada |
| MONITOR-NO-DEADCODE-001 | Nueva regla: zero dead code en monitoring |
| State API | API de Drupal para datos key-value persistentes |
| hook_requirements | Hook de Drupal que reporta estado del sistema |
| hook_mail | Hook de Drupal que define templates de email |
| MessageCard | Formato de Microsoft Teams para notificaciones |
| Incoming Webhook | URL de Slack para enviar mensajes programaticamente |

---

## 16. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-26 | 1.0.0 | Plan inicial: 3 sprints, 10 tareas, 3 nuevas reglas |
