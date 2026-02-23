# 179 — Vertical Retention Playbooks: Plan de Implementación Exhaustivo

**Fecha**: 2026-02-20
**Autor**: Claude Assistant
**Estado**: Aprobado para implementación
**Módulo destino**: `jaraba_customer_success` (existente)
**Dependencias**: `ecosistema_jaraba_core`, `group`
**Versión del documento**: 1.0

---

## Tabla de Contenidos

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
- [2. Problema Identificado y Solución](#2-problema-identificado-y-solución)
- [3. Arquitectura de la Solución](#3-arquitectura-de-la-solución)
- [4. Entidades Nuevas](#4-entidades-nuevas)
  - [4.1 VerticalRetentionProfile](#41-verticalretentionprofile)
  - [4.2 SeasonalChurnPrediction](#42-seasonalchurnprediction)
- [5. Servicios Nuevos](#5-servicios-nuevos)
  - [5.1 VerticalRetentionService](#51-verticalretentionservice)
  - [5.2 SeasonalChurnService](#52-seasonalchurnservice)
- [6. Endpoints API REST](#6-endpoints-api-rest)
- [7. Dashboard Frontend (FOC)](#7-dashboard-frontend-foc)
- [8. QueueWorker y Cron](#8-queueworker-y-cron)
- [9. Perfiles Verticales (Config YAML)](#9-perfiles-verticales-config-yaml)
- [10. Archivos a Crear y Modificar](#10-archivos-a-crear-y-modificar)
- [11. Esqueletos de Código Completos](#11-esqueletos-de-código-completos)
  - [11.1 Entidades e Interfaces](#111-entidades-e-interfaces)
  - [11.2 Access Handlers](#112-access-handlers)
  - [11.3 List Builders](#113-list-builders)
  - [11.4 Formulario](#114-formulario)
  - [11.5 Servicios](#115-servicios)
  - [11.6 Controllers](#116-controllers)
  - [11.7 QueueWorker](#117-queueworker)
  - [11.8 Routing YAML](#118-routing-yaml)
  - [11.9 Services YAML](#119-services-yaml)
  - [11.10 Permissions YAML](#1110-permissions-yaml)
  - [11.11 Links YAML](#1111-links-yaml)
  - [11.12 Libraries YAML](#1112-libraries-yaml)
  - [11.13 Schema YAML](#1113-schema-yaml)
  - [11.14 Config Install YAML](#1114-config-install-yaml)
  - [11.15 Hooks del Módulo](#1115-hooks-del-módulo)
  - [11.16 Install / Update Hook](#1116-install--update-hook)
  - [11.17 Templates Twig](#1117-templates-twig)
  - [11.18 JavaScript](#1118-javascript)
  - [11.19 SCSS](#1119-scss)
- [12. Tabla de Correspondencia Specs — Implementación](#12-tabla-de-correspondencia-specs---implementación)
- [13. Tabla de Cumplimiento de Directrices](#13-tabla-de-cumplimiento-de-directrices)
- [14. Plan de Sprints](#14-plan-de-sprints)
- [15. Checklist de Verificación E2E](#15-checklist-de-verificación-e2e)

---

## 1. Resumen Ejecutivo

Este documento detalla la implementación paso a paso del sistema de **Vertical Retention Playbooks** dentro del módulo existente `jaraba_customer_success`. El objetivo es verticalizar completamente el motor de retención de la plataforma, sustituyendo el enfoque genérico actual por uno que reconozca los ciclos estacionales, las señales de churn específicas y los patrones de uso propios de cada vertical del ecosistema Jaraba.

### Objetivos

| Objetivo | Métrica | Componente |
|----------|---------|------------|
| Health scores verticalizados | Pesos configurables por vertical | `VerticalRetentionService` |
| Predicciones ajustadas estacionalmente | Probabilidad base +/- ajuste estacional | `SeasonalChurnService` |
| Playbooks con timeline D+N por vertical | Steps ejecutados automáticamente | `VerticalRetentionCronWorker` |
| 5 perfiles verticales precargados | AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento | Config YAML x5 |
| Dashboard de retención visual | Heatmap, risk cards, timeline | Ruta `/customer-success/retention` |
| 7 endpoints REST nuevos | CRUD perfiles + risk assessment + predicciones + ejecuciones | `RetentionApiController` |

### Alcance

- **2 entidades nuevas**: `VerticalRetentionProfile`, `SeasonalChurnPrediction`
- **2 servicios nuevos**: `VerticalRetentionService`, `SeasonalChurnService`
- **1 QueueWorker nuevo**: `VerticalRetentionCronWorker`
- **2 controllers nuevos**: `RetentionApiController` (API), `RetentionDashboardController` (FOC)
- **7 endpoints API REST nuevos**
- **1 ruta FOC nueva**: `/customer-success/retention`
- **5 configs YAML de perfiles verticales**
- **4 templates Twig** (1 principal + 3 parciales)
- **1 library JS/CSS nueva**
- **1 SCSS parcial en tema**
- **Modificación de 11 archivos existentes del módulo**

---

## 2. Problema Identificado y Solución

### Gap Actual

| Aspecto | Estado Actual (Doc 113) | Estado Objetivo (Doc 179) |
|---------|------------------------|---------------------------|
| Health Score | Pesos fijos: engagement 30%, adoption 25%, satisfaction 20%, support 15%, growth 10% | Pesos configurables por vertical. AgroConecta prioriza adopción feature (35%), ComercioConecta prioriza transacciones (30%) |
| Churn Prediction | Signal-based genérico: baja salud, bajo engagement, trend descendente | Señales verticales: 0 productos 30d (agro), QR sin escaneos (comercio), BMC no actualizado 60d (emprendimiento) |
| Estacionalidad | No existe | Calendario 12 meses por vertical. Ene-Feb alto riesgo agro (entre cosechas), Jul-Ago bajo riesgo comercio (rebajas verano) |
| Playbooks | Steps genéricos: email, internal alert, in-app | Steps verticalizados con contenido específico: "Preparación próxima campaña" (agro), "Kit Black Friday" (comercio), "Casos de éxito empleados" (empleabilidad) |
| Inactividad | Umbral único | Umbrales por vertical: 60d agro (ciclo largo), 21d comercio (ciclo corto), 14d empleabilidad (alta urgencia) |

### Impacto del Gap

- **Falsos positivos**: Un agricultor inactivo en diciembre (entre cosechas) no es churn. El sistema actual lo marca como `at_risk`.
- **Falsos negativos**: Un comerciante sin transacciones POS durante 21 días en temporada alta SÍ es churn crítico. El sistema actual espera 30 días genéricos.
- **Playbooks ineficaces**: Enviar un "descubre nuestras funciones premium" a un emprendedor en fase de ideación cuando necesita una sesión de mentoría.
- **Oportunidades perdidas**: No detectar que un agricultor post-cosecha exitosa es candidato a expansión (más hectáreas, seguro de cosecha).

### Solución

Verticalizar el motor de retención manteniendo retrocompatibilidad total con el módulo existente:

```
                    jaraba_customer_success (existente)
                    +----------------------------------+
                    |  HealthScoreCalculatorService     | <-- Se mantiene intacto
                    |  ChurnPredictionService           | <-- Se mantiene intacto
                    |  PlaybookExecutorService          | <-- Se mantiene intacto
                    +----------------------------------+
                              |        |
                    +---------+--------+---------+
                    |                             |
          VerticalRetentionService      SeasonalChurnService
          (NUEVO - orquesta todo)       (NUEVO - predicciones
           |                              estacionales)
           |
           +---> Lee VerticalRetentionProfile (config por vertical)
           +---> Ajusta pesos del HealthScoreCalculatorService
           +---> Evalua senales de churn especificas de la vertical
           +---> Selecciona playbook verticalizado
           +---> Genera SeasonalChurnPrediction (append-only)
```

---

## 3. Arquitectura de la Solución

### 3.1 Flujo de Datos Principal

```
CRON (03:00 diario)
     |
     v
VerticalRetentionCronWorker (QueueWorker)
     |
     +---> Para cada tenant activo:
     |       |
     |       +---> 1. Identificar vertical del tenant (group type)
     |       |
     |       +---> 2. Cargar VerticalRetentionProfile de esa vertical
     |       |
     |       +---> 3. VerticalRetentionService::evaluateTenant()
     |       |       |
     |       |       +---> Recalcular health score con pesos verticales
     |       |       +---> Evaluar senales de churn verticales
     |       |       +---> Determinar si es inactividad estacional vs real
     |       |       +---> Calcular risk_level ajustado
     |       |
     |       +---> 4. SeasonalChurnService::predict()
     |       |       |
     |       |       +---> Calcular base_churn_probability
     |       |       +---> Aplicar seasonal_adjustment del calendario
     |       |       +---> Guardar SeasonalChurnPrediction (append-only)
     |       |
     |       +---> 5. Si risk >= threshold:
     |               |
     |               +---> Seleccionar playbook verticalizado
     |               +---> Ejecutar via PlaybookExecutorService existente
     |
     +---> Fin del batch
```

### 3.2 Modelo de Datos Relacional

```
+---------------------------+       +---------------------------+
| vertical_retention_profile|       | seasonal_churn_prediction |
+---------------------------+       +---------------------------+
| id (serial)               |       | id (serial)               |
| uuid                      |       | uuid                      |
| vertical_id (string)      |<-+    | tenant_id (entity_ref)    |
| label (string)            |  |    | vertical_id (string)      |---+
| seasonality_calendar (JSON)|  |    | prediction_month (string) |   |
| churn_risk_signals (JSON) |  |    | base_churn_probability    |   |
| health_score_weights (JSON)|  |    | seasonal_adjustment       |   |
| critical_features (JSON)  |  |    | adjusted_probability      |   |
| reengagement_triggers(JSON)|  |    | seasonal_context (JSON)   |   |
| upsell_signals (JSON)     |  |    | recommended_playbook (ref)|   |
| seasonal_offers (JSON)    |  +----| intervention_urgency      |   |
| expected_usage_pattern    |       | created                   |   |
| max_inactivity_days (int) |       +---------------------------+   |
| playbook_overrides (JSON) |                                       |
| status (active/inactive)  |       +---------------------------+   |
| created                   |       | customer_health (existente)|   |
| changed                   |       +---------------------------+   |
+---------------------------+       | tenant_id  ------->  group |   |
                                    | overall_score              |   |
        +------------------------+  | category                   |   |
        | cs_playbook (existente)|  +---------------------------+   |
        +------------------------+                                  |
        | trigger_type           |  Se usa vertical_id para unir ---+
        | steps (JSON)           |  perfiles con predicciones y tenants
        +------------------------+
```

### 3.3 Integración con Módulo Existente

El diseño sigue el principio **Open/Closed**: los servicios existentes no se modifican, los nuevos servicios los consumen via inyección de dependencias.

| Servicio Existente | Rol en la Nueva Arquitectura |
|---|---|
| `HealthScoreCalculatorService` | `VerticalRetentionService` llama a `calculate()` y luego re-pondera con pesos verticales |
| `ChurnPredictionService` | Sigue generando `ChurnPrediction` genérico. `SeasonalChurnService` genera `SeasonalChurnPrediction` complementaria |
| `PlaybookExecutorService` | Ejecuta playbooks verticales igual que los genéricos. Los steps usan el mismo formato JSON |
| `EngagementScoringService` | `VerticalRetentionService` consulta engagement score como input para evaluación |
| `LifecycleStageService` | Se consulta para contextualizar risk assessment (ej: emprendedor en fase ideación) |

---

## 4. Entidades Nuevas

### 4.1 VerticalRetentionProfile

**Tabla base**: `vertical_retention_profile`
**Tipo**: Content Entity (no config, para poder tener varias instancias editables en admin)
**Patrón**: Replica exacta del patrón `CsPlaybook` (label con `name`, formulario CRUD, AdminHtmlRouteProvider)

| Campo | Tipo Drupal | Descripción | Restricciones |
|-------|-------------|-------------|---------------|
| `id` | serial | PK autoincrement | - |
| `uuid` | uuid | UUID v4 | - |
| `vertical_id` | `string` (max 64) | Identificador máquina de la vertical: `agroconecta`, `comercioconecta`, `serviciosconecta`, `empleabilidad`, `emprendimiento` | Requerido, único |
| `label` | `string` (max 255) | Nombre legible: "AgroConecta" | Requerido |
| `seasonality_calendar` | `string_long` | JSON con 12 entradas (una por mes). Cada entrada: `{month: 1, risk_level: "high"/"medium"/"low", label: "Entre cosechas", adjustment: -0.15}` | Requerido |
| `churn_risk_signals` | `string_long` | JSON array de señales verticales. Cada señal: `{signal_id: "no_products_30d", metric: "products_listed", operator: "==", threshold: 0, lookback_days: 30, weight: 0.3, description: "Sin productos en 30 días"}` | Requerido |
| `health_score_weights` | `string_long` | JSON con pesos verticales: `{engagement: 25, adoption: 35, satisfaction: 15, support: 10, growth: 15}`. Deben sumar 100. | Requerido |
| `critical_features` | `string_long` | JSON array de features críticas para la vertical: `["product_listing", "order_management", "harvest_tracking"]` | Requerido |
| `reengagement_triggers` | `string_long` | JSON array de triggers para re-engagement: `{trigger: "seasonal_start", message_template: "campaign_prep", delay_days: 0}` | Requerido |
| `upsell_signals` | `string_long` | JSON array de señales de expansión: `{signal: "gmv_growth_20pct", recommended_plan: "premium", message: "Tu negocio esta creciendo"}` | Opcional |
| `seasonal_offers` | `string_long` | JSON array de ofertas estacionales: `{months: [11, 12], offer_type: "discount", description: "Descuento pre-campaña"}` | Opcional |
| `expected_usage_pattern` | `string_long` | JSON con patrón de uso esperado por mes: `{1: "low", 2: "low", 3: "medium", ...}` | Requerido |
| `max_inactivity_days` | `integer` | Máximo días sin actividad antes de considerar churn real. Agro=60, Comercio=21, Empleo=14. | Requerido, min 7, max 180 |
| `playbook_overrides` | `string_long` | JSON con mapeo trigger_type -> playbook_id para esta vertical. Sobreescribe el playbook genérico. | Opcional |
| `status` | `list_string` | `active` / `inactive` | Default `active` |
| `created` | `created` | Timestamp de creación | Auto |
| `changed` | `changed` | Timestamp última modificación | Auto |

**Constantes**:
```php
public const STATUS_ACTIVE = 'active';
public const STATUS_INACTIVE = 'inactive';
public const VERTICAL_AGRO = 'agroconecta';
public const VERTICAL_COMERCIO = 'comercioconecta';
public const VERTICAL_SERVICIOS = 'serviciosconecta';
public const VERTICAL_EMPLEABILIDAD = 'empleabilidad';
public const VERTICAL_EMPRENDIMIENTO = 'emprendimiento';
```

**Métodos clave**:
- `getVerticalId(): string`
- `getSeasonalityCalendar(): array` (json_decode)
- `getChurnRiskSignals(): array`
- `getHealthScoreWeights(): array`
- `getCriticalFeatures(): array`
- `getMaxInactivityDays(): int`
- `getPlaybookOverrides(): array`
- `getSeasonalAdjustment(int $month): float` — devuelve el ajuste estacional para un mes dado
- `isActive(): bool`

### 4.2 SeasonalChurnPrediction

**Tabla base**: `seasonal_churn_prediction`
**Tipo**: Content Entity
**Política**: **Append-only** — no se editan ni eliminan predicciones una vez creadas. Esto permite trazar la evolución histórica de riesgo por tenant.

| Campo | Tipo Drupal | Descripción | Restricciones |
|-------|-------------|-------------|---------------|
| `id` | serial | PK autoincrement | - |
| `uuid` | uuid | UUID v4 | - |
| `tenant_id` | `entity_reference` (group) | FK al tenant evaluado | Requerido |
| `vertical_id` | `string` (max 64) | ID de la vertical del tenant en el momento de la predicción | Requerido |
| `prediction_month` | `string` (max 7) | Mes de la predicción en formato `YYYY-MM` | Requerido |
| `base_churn_probability` | `float` | Probabilidad base de churn sin ajuste estacional (0.00 - 1.00) | Requerido |
| `seasonal_adjustment` | `float` | Factor de ajuste estacional (-0.30 a +0.30). Positivo = mayor riesgo, negativo = menor riesgo | Requerido |
| `adjusted_probability` | `float` | Probabilidad final: `clamp(base + adjustment, 0, 1)` | Requerido |
| `seasonal_context` | `string_long` | JSON con contexto: `{month_label: "Entre cosechas", expected_pattern: "low", actual_pattern: "inactive", signals_triggered: [...]}` | Requerido |
| `recommended_playbook` | `entity_reference` (cs_playbook) | Playbook recomendado según riesgo y vertical | Opcional |
| `intervention_urgency` | `list_string` | `none` / `low` / `medium` / `high` / `critical` | Requerido |
| `created` | `created` | Timestamp | Auto |

**Constantes**:
```php
public const URGENCY_NONE = 'none';
public const URGENCY_LOW = 'low';
public const URGENCY_MEDIUM = 'medium';
public const URGENCY_HIGH = 'high';
public const URGENCY_CRITICAL = 'critical';
```

**Métodos clave**:
- `getTenantId(): string`
- `getVerticalId(): string`
- `getPredictionMonth(): string`
- `getBaseProbability(): float`
- `getSeasonalAdjustment(): float`
- `getAdjustedProbability(): float`
- `getSeasonalContext(): array`
- `getInterventionUrgency(): string`

---

## 5. Servicios Nuevos

### 5.1 VerticalRetentionService

**Service ID**: `jaraba_customer_success.vertical_retention`
**Clase**: `Drupal\jaraba_customer_success\Service\VerticalRetentionService`

**Dependencias inyectadas**:
- `EntityTypeManagerInterface $entityTypeManager`
- `HealthScoreCalculatorService $healthCalculator`
- `EngagementScoringService $engagementScoring`
- `LifecycleStageService $lifecycleStage`
- `StateInterface $state`
- `LoggerInterface $logger`

**Métodos públicos**:

#### `evaluateTenant(string $tenantId): array`
Motor principal. Retorna un array con la evaluación completa del tenant:
```php
return [
  'tenant_id' => $tenantId,
  'vertical_id' => 'agroconecta',
  'health_score' => 72,
  'adjusted_health_score' => 78,  // Ajustado por estacionalidad
  'risk_level' => 'medium',
  'is_seasonal_inactivity' => true,
  'signals_triggered' => [...],
  'recommended_action' => 'reengagement',
  'recommended_playbook_id' => 5,
  'seasonal_context' => [...],
];
```

**Algoritmo interno**:
1. Obtener `group` entity del tenant -> extraer `group_type` -> mapear a `vertical_id`
2. Cargar `VerticalRetentionProfile` via entity query `vertical_id = X, status = active`
3. Si no existe perfil verticalizado -> fallback a evaluación genérica (retrocompatibilidad)
4. Calcular health score base via `HealthScoreCalculatorService::calculate()`
5. Re-ponderar con `profile->getHealthScoreWeights()` (fórmula abajo)
6. Consultar mes actual -> obtener `seasonal_adjustment` del calendario
7. Evaluar señales de churn verticales via `evaluateVerticalSignals()`
8. Determinar si inactividad es estacional vs real comparando `expected_usage_pattern[month]` con actividad real
9. Calcular risk_level final: `base_risk * (1 + seasonal_adjustment) * signal_multiplier`
10. Si risk >= threshold -> seleccionar playbook verticalizado via `getPlaybookOverrides()`

**Fórmula de re-ponderación**:
```
adjusted_score = (
  engagement * vertical_weights.engagement +
  adoption * vertical_weights.adoption +
  satisfaction * vertical_weights.satisfaction +
  support * vertical_weights.support +
  growth * vertical_weights.growth
) / 100
```

#### `evaluateVerticalSignals(string $tenantId, VerticalRetentionProfile $profile): array`
Evalúa cada señal de la configuración `churn_risk_signals` contra datos reales del tenant.

Para cada señal:
1. Leer `metric` (ej: `products_listed`)
2. Consultar `finops_usage_log` para `tenant_id` + `metric_type` en los últimos `lookback_days`
3. Aplicar `operator` + `threshold`
4. Si se activa: agregar a `signals_triggered` con su `weight`

Retorna array de señales activadas con peso acumulado.

#### `getProfileForTenant(string $tenantId): ?VerticalRetentionProfile`
Mapea tenant -> vertical -> perfil. Usa cache en State API con key `jaraba_cs.retention_profile.{tenant_id}` y TTL de 24h.

#### `getRiskAssessment(string $tenantId): array`
Wrapper público que combina `evaluateTenant()` + `SeasonalChurnService::getLatestPrediction()` para API consumption. Incluye histórico de las últimas 6 predicciones.

#### `runBatchEvaluation(): int`
Evalúa todos los tenants activos en batch. Usa entity query sobre `group` type `tenant`. Retorna conteo de evaluados. Almacena timestamp en State `jaraba_cs.retention_last_batch`.

### 5.2 SeasonalChurnService

**Service ID**: `jaraba_customer_success.seasonal_churn`
**Clase**: `Drupal\jaraba_customer_success\Service\SeasonalChurnService`

**Dependencias inyectadas**:
- `EntityTypeManagerInterface $entityTypeManager`
- `ChurnPredictionService $churnPrediction`
- `StateInterface $state`
- `LoggerInterface $logger`

**Métodos públicos**:

#### `predict(string $tenantId, VerticalRetentionProfile $profile): SeasonalChurnPrediction`
Genera una predicción estacional para un tenant.

**Algoritmo**:
1. Obtener `ChurnPrediction` genérica más reciente del tenant
2. Extraer `base_churn_probability` de la predicción genérica
3. Obtener mes actual (1-12)
4. Consultar `profile->getSeasonalAdjustment($month)` -> `seasonal_adjustment`
5. `adjusted_probability = max(0, min(1, base + adjustment))`
6. Construir `seasonal_context` JSON con datos del mes
7. Determinar `intervention_urgency`:
   - `adjusted_probability < 0.15` -> `none`
   - `adjusted_probability < 0.30` -> `low`
   - `adjusted_probability < 0.50` -> `medium`
   - `adjusted_probability < 0.75` -> `high`
   - `>= 0.75` -> `critical`
8. Seleccionar `recommended_playbook` según urgencia y vertical
9. Crear y guardar entidad `SeasonalChurnPrediction` (append-only)

#### `getLatestPrediction(string $tenantId): ?SeasonalChurnPrediction`
Entity query: `tenant_id = X`, sort by `created DESC`, range 0,1.

#### `getPredictionHistory(string $tenantId, int $months = 6): array`
Entity query: últimas N predicciones para generar gráficos de tendencia.

#### `getMonthlyPredictions(string $month): array`
Todas las predicciones de un mes dado (formato `YYYY-MM`). Para el dashboard general.

#### `runMonthlyPredictions(): int`
Genera predicciones para todos los tenants activos para el mes actual. Se invoca desde el cron worker. Retorna conteo.

---

## 6. Endpoints API REST

Todos los endpoints siguen el patrón existente del módulo: `JsonResponse` con envelope `{success, data, meta}` o `{success, error}`.

| # | Método | Path | Controller::method | Permiso | CSRF | Descripción |
|---|--------|------|--------------------|---------|------|-------------|
| 1 | GET | `/api/v1/retention/profiles` | `RetentionApiController::listProfiles` | `administer customer success` | No | Lista todos los perfiles verticales |
| 2 | GET | `/api/v1/retention/profiles/{vertical_id}` | `RetentionApiController::getProfile` | `view customer health scores` | No | Detalle de un perfil vertical |
| 3 | PUT | `/api/v1/retention/profiles/{vertical_id}` | `RetentionApiController::updateProfile` | `administer customer success` | Si | Actualiza un perfil vertical |
| 4 | GET | `/api/v1/retention/risk-assessment/{tenant_id}` | `RetentionApiController::riskAssessment` | `view churn predictions` | No | Evaluación de riesgo completa de un tenant |
| 5 | GET | `/api/v1/retention/seasonal-predictions` | `RetentionApiController::seasonalPredictions` | `view churn predictions` | No | Predicciones estacionales (filtrable por mes/vertical) |
| 6 | GET | `/api/v1/retention/playbook-executions` | `RetentionApiController::playbookExecutions` | `manage playbooks` | No | Ejecuciones de playbooks verticales |
| 7 | POST | `/api/v1/retention/playbook-executions/{id}/override` | `RetentionApiController::overrideExecution` | `manage playbooks` | Si | Override manual de una ejecución de playbook |

### Detalle de cada endpoint

**Endpoint 1 — GET /api/v1/retention/profiles**
```json
{
  "success": true,
  "data": [
    {
      "vertical_id": "agroconecta",
      "label": "AgroConecta",
      "status": "active",
      "max_inactivity_days": 60,
      "health_score_weights": {"engagement": 25, "adoption": 35, ...},
      "critical_features_count": 5,
      "churn_signals_count": 4
    }
  ],
  "meta": {"total": 5, "timestamp": "2026-02-20T10:00:00+01:00"}
}
```

**Endpoint 2 — GET /api/v1/retention/profiles/{vertical_id}**
```json
{
  "success": true,
  "data": {
    "vertical_id": "agroconecta",
    "label": "AgroConecta",
    "seasonality_calendar": [...],
    "churn_risk_signals": [...],
    "health_score_weights": {...},
    "critical_features": [...],
    "reengagement_triggers": [...],
    "upsell_signals": [...],
    "seasonal_offers": [...],
    "expected_usage_pattern": {...},
    "max_inactivity_days": 60,
    "playbook_overrides": {...}
  },
  "meta": {"timestamp": "2026-02-20T10:00:00+01:00"}
}
```

**Endpoint 3 — PUT /api/v1/retention/profiles/{vertical_id}**
- Requiere `_csrf_request_header_token: 'TRUE'`
- Body: JSON con campos a actualizar (partial update)
- Validación: `health_score_weights` deben sumar 100, `max_inactivity_days` entre 7 y 180

**Endpoint 4 — GET /api/v1/retention/risk-assessment/{tenant_id}**
```json
{
  "success": true,
  "data": {
    "tenant_id": "tenant_123",
    "vertical_id": "agroconecta",
    "health_score": 72,
    "adjusted_health_score": 78,
    "risk_level": "medium",
    "is_seasonal_inactivity": true,
    "signals_triggered": [
      {"signal_id": "no_products_30d", "weight": 0.3, "description": "Sin productos en 30 dias"}
    ],
    "seasonal_prediction": {
      "base_probability": 0.35,
      "seasonal_adjustment": -0.15,
      "adjusted_probability": 0.20,
      "month_label": "Entre cosechas",
      "urgency": "low"
    },
    "prediction_history": [...]
  },
  "meta": {"timestamp": "2026-02-20T10:00:00+01:00"}
}
```

**Endpoint 5 — GET /api/v1/retention/seasonal-predictions**
- Query params: `?month=2026-02` (opcional), `?vertical_id=agroconecta` (opcional), `?limit=50&offset=0`
- Retorna lista de `SeasonalChurnPrediction` serializadas

**Endpoint 6 — GET /api/v1/retention/playbook-executions**
- Query params: `?vertical_id=X` (opcional), `?status=running` (opcional), `?limit=50&offset=0`
- Retorna ejecuciones de playbooks filtradas

**Endpoint 7 — POST /api/v1/retention/playbook-executions/{id}/override**
- Requiere CSRF
- Body: `{"action": "pause"|"resume"|"cancel", "reason": "Manual override"}`
- Valida que la ejecución exista y este en estado compatible

---

## 7. Dashboard Frontend (FOC)

### Ruta

**Path**: `/customer-success/retention`
**Controller**: `RetentionDashboardController::dashboard`
**Permiso**: `view customer health scores`
**Template**: `retention-dashboard.html.twig`
**Library**: `jaraba_customer_success/retention-dashboard`
**Layout**: Zero-region (mismo patrón que `/customer-success`)

### Componentes visuales

**1. Header con navegación contextual**
- Links a las demás secciones de Customer Success (dashboard, playbooks, expansión, NPS)
- Tab activa en "Retention"

**2. Stats cards (fila superior)**
- Total tenants evaluados
- Tenants en riesgo (high + critical)
- Tasa de retención mensual
- Playbooks activos verticales

**3. Heatmap estacional (componente central)**
- Grid 12 columnas (meses) x 5 filas (verticales)
- Cada celda coloreada según risk_level del calendario: verde (low), amarillo (medium), rojo (high)
- Mes actual resaltado con borde
- Tooltip con label del mes y ajuste estacional
- Template parcial: `_retention-calendar-heatmap.html.twig`

**4. Risk cards por vertical (sección media)**
- Una card por vertical con:
  - Nombre e icono de la vertical
  - Número de tenants en riesgo / total
  - Top 3 señales de churn más frecuentes
  - Playbook activo
  - Botón "Ver detalle"
- Template parcial: `_retention-risk-card.html.twig`

**5. Timeline de playbook executions (sección inferior)**
- Lista cronológica de las últimas 20 ejecuciones
- Cada entry: tenant, playbook, step actual, status badge, timestamp
- Template parcial: `_playbook-timeline.html.twig`

### Variables pasadas al template

```php
return [
  '#theme' => 'jaraba_cs_retention_dashboard',
  '#stats' => [
    'total_tenants' => $totalTenants,
    'at_risk_tenants' => $atRiskCount,
    'retention_rate' => $retentionRate,
    'active_playbooks' => $activePlaybooksCount,
  ],
  '#heatmap_data' => $heatmapData,  // array[vertical_id][month] => risk_level
  '#risk_cards' => $riskCards,       // array por vertical con stats
  '#recent_executions' => $recentExecutions,
  '#attached' => [
    'library' => ['jaraba_customer_success/retention-dashboard'],
    'drupalSettings' => [
      'jarabaCs' => [
        'retentionHeatmap' => $heatmapData,
        'currentMonth' => (int) date('n'),
      ],
    ],
  ],
  '#cache' => [
    'tags' => ['vertical_retention_profile_list', 'seasonal_churn_prediction_list', 'playbook_execution_list'],
    'max-age' => 300,
  ],
];
```

---

## 8. QueueWorker y Cron

### VerticalRetentionCronWorker

**Plugin ID**: `jaraba_vertical_retention_cron`
**Cron time**: 120 segundos (2 minutos de ejecución permitida)
**Operaciones**:

| Operation | Servicio | Método | Descripción |
|-----------|----------|--------|-------------|
| `vertical_evaluation` | `VerticalRetentionService` | `runBatchEvaluation()` | Evalúa todos los tenants con perfiles verticales |
| `seasonal_predictions` | `SeasonalChurnService` | `runMonthlyPredictions()` | Genera predicciones estacionales mensuales |

### Integración con cron existente

Se añade al `hook_cron()` existente del módulo, que ya crea items para el worker genérico. Los nuevos items se crean en una cola separada para no interferir:

```php
// En jaraba_customer_success_cron() existente, anadir:
$retentionQueue = \Drupal::queue('jaraba_vertical_retention_cron');
$retentionQueue->createItem(['operation' => 'vertical_evaluation']);

// Las predicciones estacionales solo se generan el dia 1 de cada mes.
if ((int) date('j') === 1) {
  $retentionQueue->createItem(['operation' => 'seasonal_predictions']);
}
```

---

## 9. Perfiles Verticales (Config YAML)

Los 5 perfiles se instalan como contenido inicial via `hook_install()` de un update hook (no como config entities). Se cargan programaticamente creando entidades `VerticalRetentionProfile`.

Sin embargo, para referencia y documentación, los datos de cada perfil se definen en archivos YAML en `config/install/` que el update hook lee y procesa.

### 9.1 AgroConecta

**Archivo**: `config/install/jaraba_customer_success.retention_profile.agroconecta.yml`

```yaml
vertical_id: agroconecta
label: 'AgroConecta'
max_inactivity_days: 60
health_score_weights:
  engagement: 25
  adoption: 35
  satisfaction: 15
  support: 10
  growth: 15
seasonality_calendar:
  - { month: 1, risk_level: high, label: 'Entre cosechas (invierno)', adjustment: 0.15 }
  - { month: 2, risk_level: high, label: 'Pre-siembra', adjustment: 0.10 }
  - { month: 3, risk_level: medium, label: 'Inicio siembra primavera', adjustment: 0.0 }
  - { month: 4, risk_level: low, label: 'Siembra activa', adjustment: -0.10 }
  - { month: 5, risk_level: low, label: 'Crecimiento cultivos', adjustment: -0.15 }
  - { month: 6, risk_level: low, label: 'Pre-cosecha cereales', adjustment: -0.10 }
  - { month: 7, risk_level: low, label: 'Cosecha cereales', adjustment: -0.20 }
  - { month: 8, risk_level: medium, label: 'Post-cosecha / vendimia', adjustment: -0.05 }
  - { month: 9, risk_level: low, label: 'Cosecha frutas / vendimia', adjustment: -0.15 }
  - { month: 10, risk_level: medium, label: 'Siembra otono', adjustment: -0.05 }
  - { month: 11, risk_level: medium, label: 'Fin campana', adjustment: 0.05 }
  - { month: 12, risk_level: high, label: 'Invierno / inactividad esperada', adjustment: 0.15 }
churn_risk_signals:
  - { signal_id: no_products_30d, metric: products_listed, operator: '==', threshold: 0, lookback_days: 30, weight: 0.30, description: 'Sin productos listados en 30 dias' }
  - { signal_id: no_orders_45d, metric: orders_received, operator: '==', threshold: 0, lookback_days: 45, weight: 0.25, description: 'Sin pedidos recibidos en 45 dias' }
  - { signal_id: gmv_drop_50pct, metric: gmv_change_pct, operator: '<', threshold: -50, lookback_days: 30, weight: 0.25, description: 'Descenso de GMV superior al 50%' }
  - { signal_id: no_login_30d, metric: last_login_days, operator: '>', threshold: 30, lookback_days: 30, weight: 0.20, description: 'Sin login en 30 dias' }
critical_features:
  - product_listing
  - order_management
  - harvest_tracking
  - weather_alerts
  - marketplace_visibility
reengagement_triggers:
  - { trigger: seasonal_start, message_template: campaign_prep, delay_days: 0 }
  - { trigger: post_harvest, message_template: review_season, delay_days: 7 }
  - { trigger: new_feature, message_template: feature_announcement, delay_days: 0 }
upsell_signals:
  - { signal: gmv_growth_20pct, recommended_plan: premium_agro, message: 'Tu negocio agricola esta creciendo' }
  - { signal: hectares_increase, recommended_plan: enterprise_agro, message: 'Gestion de mas explotaciones' }
seasonal_offers:
  - { months: [2, 3], offer_type: early_bird, description: 'Descuento pre-campana primavera' }
  - { months: [8, 9], offer_type: harvest_bundle, description: 'Pack cosecha + logistica' }
expected_usage_pattern:
  1: low
  2: low
  3: medium
  4: high
  5: high
  6: high
  7: high
  8: medium
  9: high
  10: medium
  11: medium
  12: low
playbook_overrides:
  health_drop: 3
  churn_risk: 7
  expansion: 12
```

### 9.2 ComercioConecta

**Archivo**: `config/install/jaraba_customer_success.retention_profile.comercioconecta.yml`

```yaml
vertical_id: comercioconecta
label: 'ComercioConecta'
max_inactivity_days: 21
health_score_weights:
  engagement: 30
  adoption: 20
  satisfaction: 20
  support: 10
  growth: 20
seasonality_calendar:
  - { month: 1, risk_level: medium, label: 'Post-rebajas invierno', adjustment: 0.05 }
  - { month: 2, risk_level: high, label: 'Valle post-navidad', adjustment: 0.15 }
  - { month: 3, risk_level: high, label: 'Valle pre-primavera', adjustment: 0.10 }
  - { month: 4, risk_level: medium, label: 'Inicio primavera', adjustment: 0.0 }
  - { month: 5, risk_level: low, label: 'Dia de la Madre / primavera', adjustment: -0.05 }
  - { month: 6, risk_level: low, label: 'Rebajas verano inicio', adjustment: -0.15 }
  - { month: 7, risk_level: low, label: 'Rebajas verano pleno', adjustment: -0.20 }
  - { month: 8, risk_level: high, label: 'Valle agosto', adjustment: 0.10 }
  - { month: 9, risk_level: medium, label: 'Vuelta al cole', adjustment: 0.0 }
  - { month: 10, risk_level: low, label: 'Pre-Black Friday', adjustment: -0.05 }
  - { month: 11, risk_level: low, label: 'Black Friday / Singles Day', adjustment: -0.25 }
  - { month: 12, risk_level: low, label: 'Campana Navidad', adjustment: -0.20 }
churn_risk_signals:
  - { signal_id: no_flash_offers_30d, metric: flash_offers_created, operator: '==', threshold: 0, lookback_days: 30, weight: 0.25, description: 'Sin Flash Offers creadas en 30 dias' }
  - { signal_id: qr_no_scans, metric: qr_scans, operator: '==', threshold: 0, lookback_days: 21, weight: 0.20, description: 'QR generados sin escaneos en 21 dias' }
  - { signal_id: no_pos_transactions_21d, metric: pos_transactions, operator: '==', threshold: 0, lookback_days: 21, weight: 0.30, description: 'Sin transacciones POS en 21 dias' }
  - { signal_id: no_login_14d, metric: last_login_days, operator: '>', threshold: 14, lookback_days: 14, weight: 0.25, description: 'Sin login en 14 dias' }
critical_features:
  - flash_offers
  - qr_codes
  - pos_integration
  - customer_loyalty
  - inventory_management
reengagement_triggers:
  - { trigger: seasonal_sale, message_template: sale_prep_kit, delay_days: 0 }
  - { trigger: competitor_event, message_template: competitive_advantage, delay_days: 0 }
  - { trigger: low_pos_usage, message_template: pos_training, delay_days: 3 }
upsell_signals:
  - { signal: transaction_volume_high, recommended_plan: premium_commerce, message: 'Tus ventas justifican funciones premium' }
  - { signal: multi_location, recommended_plan: enterprise_commerce, message: 'Gestiona multiples puntos de venta' }
seasonal_offers:
  - { months: [10, 11], offer_type: black_friday_kit, description: 'Kit Black Friday para tu comercio' }
  - { months: [6, 7], offer_type: summer_boost, description: 'Impulso rebajas de verano' }
expected_usage_pattern:
  1: medium
  2: low
  3: low
  4: medium
  5: medium
  6: high
  7: high
  8: low
  9: medium
  10: high
  11: high
  12: high
playbook_overrides:
  health_drop: 4
  churn_risk: 8
  expansion: 13
```

### 9.3 ServiciosConecta

**Archivo**: `config/install/jaraba_customer_success.retention_profile.serviciosconecta.yml`

```yaml
vertical_id: serviciosconecta
label: 'ServiciosConecta'
max_inactivity_days: 30
health_score_weights:
  engagement: 30
  adoption: 25
  satisfaction: 25
  support: 10
  growth: 10
seasonality_calendar:
  - { month: 1, risk_level: medium, label: 'Inicio de ano / baja demanda', adjustment: 0.05 }
  - { month: 2, risk_level: medium, label: 'Recuperacion lenta', adjustment: 0.05 }
  - { month: 3, risk_level: low, label: 'Primavera - demanda crece', adjustment: -0.05 }
  - { month: 4, risk_level: low, label: 'Servicios primaverales', adjustment: -0.10 }
  - { month: 5, risk_level: low, label: 'Alta actividad', adjustment: -0.10 }
  - { month: 6, risk_level: low, label: 'Pre-verano activo', adjustment: -0.05 }
  - { month: 7, risk_level: medium, label: 'Vacaciones - baja leve', adjustment: 0.05 }
  - { month: 8, risk_level: high, label: 'Agosto - paralizacion', adjustment: 0.15 }
  - { month: 9, risk_level: low, label: 'Vuelta actividad', adjustment: -0.10 }
  - { month: 10, risk_level: low, label: 'Otono activo', adjustment: -0.10 }
  - { month: 11, risk_level: low, label: 'Pre-navidad - servicios altos', adjustment: -0.05 }
  - { month: 12, risk_level: medium, label: 'Navidad - servicios mixtos', adjustment: 0.05 }
churn_risk_signals:
  - { signal_id: no_bookings_21d, metric: bookings_created, operator: '==', threshold: 0, lookback_days: 21, weight: 0.30, description: 'Sin reservas en 21 dias' }
  - { signal_id: no_quotes_30d, metric: quotes_sent, operator: '==', threshold: 0, lookback_days: 30, weight: 0.25, description: 'Sin presupuestos enviados en 30 dias' }
  - { signal_id: trust_inbox_unread_7d, metric: trust_inbox_unread, operator: '>', threshold: 0, lookback_days: 7, weight: 0.25, description: 'Buzon de Confianza sin responder en 7 dias' }
  - { signal_id: no_login_21d, metric: last_login_days, operator: '>', threshold: 21, lookback_days: 21, weight: 0.20, description: 'Sin login en 21 dias' }
critical_features:
  - booking_management
  - quote_generator
  - trust_inbox
  - client_reviews
  - portfolio_showcase
reengagement_triggers:
  - { trigger: roi_report, message_template: monthly_roi, delay_days: 0 }
  - { trigger: new_reviews, message_template: review_notification, delay_days: 1 }
  - { trigger: booking_reminder, message_template: booking_setup, delay_days: 3 }
upsell_signals:
  - { signal: high_booking_volume, recommended_plan: premium_services, message: 'Automatiza la gestion de tus reservas' }
  - { signal: positive_reviews_growth, recommended_plan: featured_listing, message: 'Destaca tu perfil con resenas positivas' }
seasonal_offers:
  - { months: [3, 4], offer_type: spring_visibility, description: 'Impulso de visibilidad primavera' }
  - { months: [9, 10], offer_type: autumn_relaunch, description: 'Relanzamiento de otono' }
expected_usage_pattern:
  1: medium
  2: medium
  3: high
  4: high
  5: high
  6: high
  7: medium
  8: low
  9: high
  10: high
  11: high
  12: medium
playbook_overrides:
  health_drop: 5
  churn_risk: 9
  expansion: 14
```

### 9.4 Empleabilidad

**Archivo**: `config/install/jaraba_customer_success.retention_profile.empleabilidad.yml`

```yaml
vertical_id: empleabilidad
label: 'Empleabilidad'
max_inactivity_days: 14
health_score_weights:
  engagement: 35
  adoption: 25
  satisfaction: 15
  support: 10
  growth: 15
seasonality_calendar:
  - { month: 1, risk_level: low, label: 'Propositos ano nuevo / alta busqueda', adjustment: -0.15 }
  - { month: 2, risk_level: low, label: 'Convocatorias Q1 activas', adjustment: -0.10 }
  - { month: 3, risk_level: low, label: 'Periodo contratacion activo', adjustment: -0.10 }
  - { month: 4, risk_level: medium, label: 'Meseta primavera', adjustment: 0.0 }
  - { month: 5, risk_level: medium, label: 'Pre-verano', adjustment: 0.05 }
  - { month: 6, risk_level: medium, label: 'Contratos temporales verano', adjustment: 0.0 }
  - { month: 7, risk_level: high, label: 'Vacaciones - baja busqueda', adjustment: 0.15 }
  - { month: 8, risk_level: high, label: 'Agosto - minima actividad', adjustment: 0.20 }
  - { month: 9, risk_level: low, label: 'Vuelta - pico contratacion', adjustment: -0.15 }
  - { month: 10, risk_level: low, label: 'Contratacion Q4', adjustment: -0.10 }
  - { month: 11, risk_level: low, label: 'Campana navidad temporal', adjustment: -0.05 }
  - { month: 12, risk_level: medium, label: 'Cierre ano', adjustment: 0.10 }
churn_risk_signals:
  - { signal_id: hired_status, metric: application_status_hired, operator: '==', threshold: 1, lookback_days: 30, weight: 0.40, description: 'Candidato contratado (churn por exito)' }
  - { signal_id: no_searches_14d, metric: job_searches, operator: '==', threshold: 0, lookback_days: 14, weight: 0.25, description: 'Sin busquedas de empleo en 14 dias' }
  - { signal_id: no_applications_21d, metric: applications_sent, operator: '==', threshold: 0, lookback_days: 21, weight: 0.20, description: 'Sin candidaturas enviadas en 21 dias' }
  - { signal_id: profile_incomplete, metric: profile_completion_pct, operator: '<', threshold: 50, lookback_days: 0, weight: 0.15, description: 'Perfil incompleto (menos del 50%)' }
critical_features:
  - job_search
  - cv_builder
  - application_tracking
  - interview_prep
  - skill_assessments
reengagement_triggers:
  - { trigger: new_matching_jobs, message_template: job_match_alert, delay_days: 0 }
  - { trigger: skill_gap_identified, message_template: training_suggestion, delay_days: 2 }
  - { trigger: profile_stale, message_template: profile_update_reminder, delay_days: 7 }
upsell_signals:
  - { signal: multiple_applications, recommended_plan: premium_job_seeker, message: 'Destaca tu perfil frente a otros candidatos' }
  - { signal: interview_prep_needed, recommended_plan: coaching_pack, message: 'Prepara tus entrevistas con un coach' }
seasonal_offers:
  - { months: [1, 2], offer_type: new_year_boost, description: 'Impulso busqueda ano nuevo' }
  - { months: [9, 10], offer_type: back_to_work, description: 'Vuelta al mercado laboral' }
expected_usage_pattern:
  1: high
  2: high
  3: high
  4: medium
  5: medium
  6: medium
  7: low
  8: low
  9: high
  10: high
  11: high
  12: medium
playbook_overrides:
  health_drop: 6
  churn_risk: 10
  expansion: 15
```

**Nota especial Empleabilidad**: El churn por éxito (candidato contratado) debe tratarse de forma diferente. No es un problema a resolver sino una oportunidad para:
1. Pedir testimonio/review
2. Ofrecer servicios de desarrollo profesional
3. Reactivar si cambia de empleo en el futuro

### 9.5 Emprendimiento

**Archivo**: `config/install/jaraba_customer_success.retention_profile.emprendimiento.yml`

```yaml
vertical_id: emprendimiento
label: 'Emprendimiento'
max_inactivity_days: 30
health_score_weights:
  engagement: 30
  adoption: 30
  satisfaction: 15
  support: 15
  growth: 10
seasonality_calendar:
  - { month: 1, risk_level: low, label: 'Propositos / nuevos proyectos', adjustment: -0.10 }
  - { month: 2, risk_level: low, label: 'Convocatorias subvenciones Q1', adjustment: -0.10 }
  - { month: 3, risk_level: low, label: 'Aceleradoras spring batch', adjustment: -0.05 }
  - { month: 4, risk_level: medium, label: 'Desarrollo proyectos', adjustment: 0.0 }
  - { month: 5, risk_level: medium, label: 'Evaluacion progreso', adjustment: 0.05 }
  - { month: 6, risk_level: medium, label: 'Pre-verano / demo days', adjustment: 0.0 }
  - { month: 7, risk_level: high, label: 'Vacaciones - abandono proyectos', adjustment: 0.15 }
  - { month: 8, risk_level: high, label: 'Agosto - maxima desercion', adjustment: 0.20 }
  - { month: 9, risk_level: low, label: 'Reactivacion / fall batch', adjustment: -0.10 }
  - { month: 10, risk_level: low, label: 'Convocatorias Q4', adjustment: -0.10 }
  - { month: 11, risk_level: medium, label: 'Cierre proyectos anuales', adjustment: 0.05 }
  - { month: 12, risk_level: medium, label: 'Planificacion siguiente ano', adjustment: 0.05 }
churn_risk_signals:
  - { signal_id: bmc_stale_60d, metric: bmc_last_updated_days, operator: '>', threshold: 60, lookback_days: 0, weight: 0.30, description: 'Business Model Canvas sin actualizar en 60 dias' }
  - { signal_id: no_mentoring_30d, metric: mentoring_sessions, operator: '==', threshold: 0, lookback_days: 30, weight: 0.25, description: 'Sin sesiones de mentoria en 30 dias' }
  - { signal_id: no_milestones_45d, metric: milestones_completed, operator: '==', threshold: 0, lookback_days: 45, weight: 0.25, description: 'Sin hitos completados en 45 dias' }
  - { signal_id: no_login_21d, metric: last_login_days, operator: '>', threshold: 21, lookback_days: 21, weight: 0.20, description: 'Sin login en 21 dias' }
critical_features:
  - bmc_canvas
  - mentoring_platform
  - milestone_tracker
  - financial_projections
  - pitch_builder
reengagement_triggers:
  - { trigger: stale_project, message_template: project_checkup, delay_days: 0 }
  - { trigger: new_funding_round, message_template: funding_alert, delay_days: 0 }
  - { trigger: mentor_available, message_template: mentor_matching, delay_days: 2 }
upsell_signals:
  - { signal: mvp_phase_reached, recommended_plan: growth_pack, message: 'Tu MVP esta listo - acelera con el Growth Pack' }
  - { signal: funding_needed, recommended_plan: investor_connect, message: 'Conecta con inversores de nuestro ecosistema' }
seasonal_offers:
  - { months: [1, 2], offer_type: new_year_launch, description: 'Lanzamiento ano nuevo - descuento aceleracion' }
  - { months: [9, 10], offer_type: fall_accelerator, description: 'Batch de otono - plazas limitadas' }
expected_usage_pattern:
  1: high
  2: high
  3: high
  4: medium
  5: medium
  6: medium
  7: low
  8: low
  9: high
  10: high
  11: medium
  12: medium
playbook_overrides:
  health_drop: 6
  churn_risk: 11
  expansion: 16
```

**Nota especial Emprendimiento**: El churn varía drásticamente por fase del proyecto:
- **Ideación**: 40% churn natural (muchos abandonan la idea). Playbook enfocado en mentoría y validación.
- **Validación**: 25% churn (pivotan o abandonan tras feedback de mercado). Playbook enfocado en resiliencia y casos de éxito.
- **MVP**: 15% churn (ya comprometidos). Playbook enfocado en aceleración y recursos.
- **Growth**: 5% churn (alto engagement). Playbook enfocado en expansión y financiación.

El `VerticalRetentionService` consulta `LifecycleStageService` para ajustar las predicciones según la fase.

---

## 10. Archivos a Crear y Modificar

### Archivos NUEVOS (dentro de `web/modules/custom/jaraba_customer_success/`)

| # | Archivo | Tipo | Descripción |
|---|---------|------|-------------|
| 1 | `src/Entity/VerticalRetentionProfile.php` | PHP | Entidad perfil vertical |
| 2 | `src/Entity/VerticalRetentionProfileInterface.php` | PHP | Interface de la entidad |
| 3 | `src/Entity/SeasonalChurnPrediction.php` | PHP | Entidad predicción estacional |
| 4 | `src/Entity/SeasonalChurnPredictionInterface.php` | PHP | Interface de la entidad |
| 5 | `src/VerticalRetentionProfileListBuilder.php` | PHP | List builder admin |
| 6 | `src/SeasonalChurnPredictionListBuilder.php` | PHP | List builder admin |
| 7 | `src/Access/VerticalRetentionProfileAccessControlHandler.php` | PHP | Access handler |
| 8 | `src/Access/SeasonalChurnPredictionAccessControlHandler.php` | PHP | Access handler |
| 9 | `src/Form/VerticalRetentionProfileForm.php` | PHP | Formulario CRUD |
| 10 | `src/Service/VerticalRetentionService.php` | PHP | Servicio motor verticalizado |
| 11 | `src/Service/SeasonalChurnService.php` | PHP | Servicio predicciones estacionales |
| 12 | `src/Controller/RetentionApiController.php` | PHP | Controller API REST |
| 13 | `src/Controller/RetentionDashboardController.php` | PHP | Controller dashboard FOC |
| 14 | `src/Plugin/QueueWorker/VerticalRetentionCronWorker.php` | PHP | QueueWorker cron |
| 15 | `config/install/jaraba_customer_success.retention_profile.agroconecta.yml` | YAML | Config perfil AgroConecta |
| 16 | `config/install/jaraba_customer_success.retention_profile.comercioconecta.yml` | YAML | Config perfil ComercioConecta |
| 17 | `config/install/jaraba_customer_success.retention_profile.serviciosconecta.yml` | YAML | Config perfil ServiciosConecta |
| 18 | `config/install/jaraba_customer_success.retention_profile.empleabilidad.yml` | YAML | Config perfil Empleabilidad |
| 19 | `config/install/jaraba_customer_success.retention_profile.emprendimiento.yml` | YAML | Config perfil Emprendimiento |
| 20 | `templates/jaraba-cs-retention-dashboard.html.twig` | Twig | Template dashboard retención |
| 21 | `templates/partials/_retention-risk-card.html.twig` | Twig | Parcial card de riesgo |
| 22 | `templates/partials/_retention-calendar-heatmap.html.twig` | Twig | Parcial heatmap estacional |
| 23 | `templates/partials/_playbook-timeline.html.twig` | Twig | Parcial timeline playbook |
| 24 | `js/retention-dashboard.js` | JS | Interactividad dashboard |

### Archivo NUEVO en tema

| # | Archivo | Tipo | Descripción |
|---|---------|------|-------------|
| 25 | `web/themes/custom/ecosistema_jaraba_theme/scss/_retention-playbooks.scss` | SCSS | Estilos dashboard retención |

### Archivos a MODIFICAR

| # | Archivo | Cambio |
|---|---------|--------|
| 26 | `jaraba_customer_success.routing.yml` | +8 rutas (7 API + 1 FOC) |
| 27 | `jaraba_customer_success.services.yml` | +2 servicios |
| 28 | `jaraba_customer_success.permissions.yml` | +2 permisos |
| 29 | `jaraba_customer_success.links.menu.yml` | +1 enlace menu |
| 30 | `jaraba_customer_success.links.task.yml` | +2 tabs |
| 31 | `jaraba_customer_success.links.action.yml` | +1 botón acción |
| 32 | `jaraba_customer_success.libraries.yml` | +1 library |
| 33 | `jaraba_customer_success.module` | +hooks theme, preprocess, cron additions |
| 34 | `config/schema/jaraba_customer_success.schema.yml` | +schemas para configs YAML |
| 35 | `jaraba_customer_success.install` | +update hook para BD activa e inserción perfiles |
| 36 | `ecosistema_jaraba_theme/scss/main.scss` | +`@use 'retention-playbooks'` |

### Documentación

| # | Archivo | Cambio |
|---|---------|--------|
| 37 | Este documento | NUEVO |
| 38 | `docs/00_INDICE_GENERAL.md` | Actualizar a v72.0.0 |

---

## 11. Code Skeletons Completos

### 11.1 Entidades e Interfaces

#### `src/Entity/VerticalRetentionProfileInterface.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Vertical Retention Profile entities.
 */
interface VerticalRetentionProfileInterface extends ContentEntityInterface, EntityChangedInterface {

  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';

  public const VERTICAL_AGRO = 'agroconecta';
  public const VERTICAL_COMERCIO = 'comercioconecta';
  public const VERTICAL_SERVICIOS = 'serviciosconecta';
  public const VERTICAL_EMPLEABILIDAD = 'empleabilidad';
  public const VERTICAL_EMPRENDIMIENTO = 'emprendimiento';

  /**
   * Gets the vertical machine ID.
   */
  public function getVerticalId(): string;

  /**
   * Gets the human-readable label.
   */
  public function getLabel(): string;

  /**
   * Gets the seasonality calendar (12-month array).
   *
   * @return array<int, array{month: int, risk_level: string, label: string, adjustment: float}>
   */
  public function getSeasonalityCalendar(): array;

  /**
   * Gets the seasonal adjustment factor for a given month.
   *
   * @param int $month
   *   Month number (1-12).
   *
   * @return float
   *   Adjustment factor (-0.30 to +0.30).
   */
  public function getSeasonalAdjustment(int $month): float;

  /**
   * Gets the churn risk signals configuration.
   *
   * @return array<int, array{signal_id: string, metric: string, operator: string, threshold: mixed, lookback_days: int, weight: float, description: string}>
   */
  public function getChurnRiskSignals(): array;

  /**
   * Gets the health score weights for this vertical.
   *
   * @return array{engagement: int, adoption: int, satisfaction: int, support: int, growth: int}
   */
  public function getHealthScoreWeights(): array;

  /**
   * Gets the list of critical features for this vertical.
   *
   * @return string[]
   */
  public function getCriticalFeatures(): array;

  /**
   * Gets the re-engagement triggers.
   */
  public function getReengagementTriggers(): array;

  /**
   * Gets the upsell signals.
   */
  public function getUpsellSignals(): array;

  /**
   * Gets the seasonal offers.
   */
  public function getSeasonalOffers(): array;

  /**
   * Gets the expected usage pattern per month.
   *
   * @return array<int, string>
   *   Map of month (1-12) => expected usage level (low/medium/high).
   */
  public function getExpectedUsagePattern(): array;

  /**
   * Gets maximum inactivity days before churn classification.
   */
  public function getMaxInactivityDays(): int;

  /**
   * Gets playbook overrides (trigger_type => playbook_id).
   */
  public function getPlaybookOverrides(): array;

  /**
   * Checks if the profile is active.
   */
  public function isActive(): bool;

}
```

#### `src/Entity/VerticalRetentionProfile.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vertical Retention Profile entity.
 *
 * Stores per-vertical retention configuration: seasonality calendar,
 * churn risk signals, health score weights, critical features, and
 * playbook overrides. One profile per vertical (AgroConecta, ComercioConecta,
 * ServiciosConecta, Empleabilidad, Emprendimiento).
 *
 * @ContentEntityType(
 *   id = "vertical_retention_profile",
 *   label = @Translation("Vertical Retention Profile"),
 *   label_collection = @Translation("Vertical Retention Profiles"),
 *   label_singular = @Translation("vertical retention profile"),
 *   label_plural = @Translation("vertical retention profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\VerticalRetentionProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jaraba_customer_success\Form\VerticalRetentionProfileForm",
 *       "edit" = "Drupal\jaraba_customer_success\Form\VerticalRetentionProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\VerticalRetentionProfileAccessControlHandler",
 *   },
 *   base_table = "vertical_retention_profile",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/content/retention-profiles",
 *     "add-form" = "/admin/content/retention-profiles/add",
 *     "canonical" = "/admin/content/retention-profiles/{vertical_retention_profile}",
 *     "edit-form" = "/admin/content/retention-profiles/{vertical_retention_profile}/edit",
 *     "delete-form" = "/admin/content/retention-profiles/{vertical_retention_profile}/delete",
 *   },
 *   field_ui_base_route = "entity.vertical_retention_profile.collection",
 * )
 */
class VerticalRetentionProfile extends ContentEntityBase implements VerticalRetentionProfileInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['vertical_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical ID'))
      ->setDescription(t('Machine name of the vertical (e.g., agroconecta).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Human-readable name of the vertical.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonality_calendar'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonality Calendar'))
      ->setDescription(t('JSON array with 12 monthly entries defining risk levels and adjustments.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => ['rows' => 12],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['churn_risk_signals'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Churn Risk Signals'))
      ->setDescription(t('JSON array of vertical-specific churn signals with metrics and thresholds.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => ['rows' => 10],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['health_score_weights'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Health Score Weights'))
      ->setDescription(t('JSON object with 5 weight values (engagement, adoption, satisfaction, support, growth) summing to 100.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['critical_features'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Critical Features'))
      ->setDescription(t('JSON array of feature machine names critical for this vertical.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reengagement_triggers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Re-engagement Triggers'))
      ->setDescription(t('JSON array of trigger configurations for re-engagement campaigns.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['upsell_signals'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Upsell Signals'))
      ->setDescription(t('JSON array of expansion/upsell signal configurations.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 30,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_offers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonal Offers'))
      ->setDescription(t('JSON array of seasonal offer configurations.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 35,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expected_usage_pattern'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Expected Usage Pattern'))
      ->setDescription(t('JSON object mapping month numbers (1-12) to expected usage levels (low/medium/high).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 40,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_inactivity_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Inactivity Days'))
      ->setDescription(t('Maximum days of inactivity before considering real churn (not seasonal).'))
      ->setRequired(TRUE)
      ->setSetting('min', 7)
      ->setSetting('max', 180)
      ->setDefaultValue(30)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 45,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 45,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['playbook_overrides'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Playbook Overrides'))
      ->setDescription(t('JSON object mapping trigger_type to playbook_id for this vertical.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 50,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_INACTIVE => t('Inactive'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 55,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 55,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the profile was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the profile was last updated.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerticalId(): string {
    return (string) $this->get('vertical_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalityCalendar(): array {
    $json = $this->get('seasonality_calendar')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalAdjustment(int $month): float {
    $calendar = $this->getSeasonalityCalendar();
    foreach ($calendar as $entry) {
      if (isset($entry['month']) && (int) $entry['month'] === $month) {
        return (float) ($entry['adjustment'] ?? 0.0);
      }
    }
    return 0.0;
  }

  /**
   * {@inheritdoc}
   */
  public function getChurnRiskSignals(): array {
    $json = $this->get('churn_risk_signals')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHealthScoreWeights(): array {
    $json = $this->get('health_score_weights')->value;
    return json_decode((string) $json, TRUE) ?? [
      'engagement' => 30,
      'adoption' => 25,
      'satisfaction' => 20,
      'support' => 15,
      'growth' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCriticalFeatures(): array {
    $json = $this->get('critical_features')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReengagementTriggers(): array {
    $json = $this->get('reengagement_triggers')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUpsellSignals(): array {
    $json = $this->get('upsell_signals')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalOffers(): array {
    $json = $this->get('seasonal_offers')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExpectedUsagePattern(): array {
    $json = $this->get('expected_usage_pattern')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxInactivityDays(): int {
    return (int) $this->get('max_inactivity_days')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaybookOverrides(): array {
    $json = $this->get('playbook_overrides')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->get('status')->value === self::STATUS_ACTIVE;
  }

}
```

#### `src/Entity/SeasonalChurnPredictionInterface.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Seasonal Churn Prediction entities.
 *
 * Append-only: predictions are never edited or deleted once created.
 */
interface SeasonalChurnPredictionInterface extends ContentEntityInterface {

  public const URGENCY_NONE = 'none';
  public const URGENCY_LOW = 'low';
  public const URGENCY_MEDIUM = 'medium';
  public const URGENCY_HIGH = 'high';
  public const URGENCY_CRITICAL = 'critical';

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): string;

  /**
   * Gets the vertical ID at time of prediction.
   */
  public function getVerticalId(): string;

  /**
   * Gets the prediction month (YYYY-MM).
   */
  public function getPredictionMonth(): string;

  /**
   * Gets the base churn probability (0.00 - 1.00).
   */
  public function getBaseProbability(): float;

  /**
   * Gets the seasonal adjustment factor.
   */
  public function getSeasonalAdjustment(): float;

  /**
   * Gets the final adjusted probability.
   */
  public function getAdjustedProbability(): float;

  /**
   * Gets the seasonal context data.
   */
  public function getSeasonalContext(): array;

  /**
   * Gets the intervention urgency level.
   */
  public function getInterventionUrgency(): string;

}
```

#### `src/Entity/SeasonalChurnPrediction.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Seasonal Churn Prediction entity.
 *
 * Stores seasonally-adjusted churn predictions per tenant per month.
 * This entity is APPEND-ONLY: once created, predictions are never edited
 * or deleted, allowing full historical traceability.
 *
 * @ContentEntityType(
 *   id = "seasonal_churn_prediction",
 *   label = @Translation("Seasonal Churn Prediction"),
 *   label_collection = @Translation("Seasonal Churn Predictions"),
 *   label_singular = @Translation("seasonal churn prediction"),
 *   label_plural = @Translation("seasonal churn predictions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\SeasonalChurnPredictionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\SeasonalChurnPredictionAccessControlHandler",
 *   },
 *   base_table = "seasonal_churn_prediction",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/seasonal-predictions",
 *     "canonical" = "/admin/content/seasonal-predictions/{seasonal_churn_prediction}",
 *   },
 * )
 */
class SeasonalChurnPrediction extends ContentEntityBase implements SeasonalChurnPredictionInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this prediction is for.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical ID'))
      ->setDescription(t('Vertical identifier at time of prediction.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['prediction_month'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prediction Month'))
      ->setDescription(t('Month of prediction in YYYY-MM format.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 7)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['base_churn_probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Base Churn Probability'))
      ->setDescription(t('Base probability before seasonal adjustment (0.00 - 1.00).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_adjustment'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Seasonal Adjustment'))
      ->setDescription(t('Seasonal adjustment factor (-0.30 to +0.30).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjusted_probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Adjusted Probability'))
      ->setDescription(t('Final probability after seasonal adjustment (0.00 - 1.00).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonal Context'))
      ->setDescription(t('JSON with contextual data: month label, expected pattern, actual pattern, triggered signals.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recommended_playbook'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recommended Playbook'))
      ->setDescription(t('The playbook recommended for this risk level and vertical.'))
      ->setSetting('target_type', 'cs_playbook')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['intervention_urgency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Intervention Urgency'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::URGENCY_NONE => t('None'),
        self::URGENCY_LOW => t('Low'),
        self::URGENCY_MEDIUM => t('Medium'),
        self::URGENCY_HIGH => t('High'),
        self::URGENCY_CRITICAL => t('Critical'),
      ])
      ->setDefaultValue(self::URGENCY_NONE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the prediction was generated.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): string {
    return (string) $this->get('tenant_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerticalId(): string {
    return (string) $this->get('vertical_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPredictionMonth(): string {
    return (string) $this->get('prediction_month')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseProbability(): float {
    return (float) $this->get('base_churn_probability')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalAdjustment(): float {
    return (float) $this->get('seasonal_adjustment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedProbability(): float {
    return (float) $this->get('adjusted_probability')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalContext(): array {
    $json = $this->get('seasonal_context')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getInterventionUrgency(): string {
    return (string) $this->get('intervention_urgency')->value;
  }

}
```

### 11.2 Access Handlers

#### `src/Access/VerticalRetentionProfileAccessControlHandler.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access handler for Vertical Retention Profile entities.
 */
class VerticalRetentionProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view customer health scores'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer customer success'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer customer success');
  }

}
```

#### `src/Access/SeasonalChurnPredictionAccessControlHandler.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access handler for Seasonal Churn Prediction entities.
 *
 * Append-only policy: update and delete are restricted to admin only.
 */
class SeasonalChurnPredictionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view churn predictions'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer customer success'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer customer success');
  }

}
```

### 11.3 List Builders

#### `src/VerticalRetentionProfileListBuilder.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Vertical Retention Profile entities.
 */
class VerticalRetentionProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['vertical_id'] = $this->t('Vertical ID');
    $header['label'] = $this->t('Label');
    $header['max_inactivity'] = $this->t('Max Inactivity');
    $header['signals_count'] = $this->t('Signals');
    $header['status'] = $this->t('Status');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface $entity */
    $row['vertical_id'] = [
      'data' => ['#markup' => '<code>' . $entity->getVerticalId() . '</code>'],
    ];
    $row['label'] = $entity->getLabel();
    $row['max_inactivity'] = (string) $this->t('@days days', ['@days' => $entity->getMaxInactivityDays()]);

    $signalsCount = count($entity->getChurnRiskSignals());
    $row['signals_count'] = (string) $signalsCount;

    $statusColor = $entity->isActive() ? '#00A9A5' : '#6c757d';
    $statusLabel = $entity->isActive() ? (string) $this->t('Active') : (string) $this->t('Inactive');
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $statusColor . '; font-weight: bold;">' . $statusLabel . '</span>',
      ],
    ];

    $row['changed'] = \Drupal::service('date.formatter')
      ->format($entity->getChangedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

}
```

#### `src/SeasonalChurnPredictionListBuilder.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Seasonal Churn Prediction entities.
 */
class SeasonalChurnPredictionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['vertical'] = $this->t('Vertical');
    $header['month'] = $this->t('Month');
    $header['base_prob'] = $this->t('Base Prob.');
    $header['adjustment'] = $this->t('Adjustment');
    $header['adjusted_prob'] = $this->t('Adjusted');
    $header['urgency'] = $this->t('Urgency');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface $entity */
    $tenantRef = $entity->get('tenant_id')->entity;
    $row['tenant'] = $tenantRef ? $tenantRef->label() : $entity->getTenantId();
    $row['vertical'] = $entity->getVerticalId();
    $row['month'] = $entity->getPredictionMonth();
    $row['base_prob'] = round($entity->getBaseProbability() * 100) . '%';

    $adj = $entity->getSeasonalAdjustment();
    $adjColor = $adj > 0 ? '#DC3545' : ($adj < 0 ? '#00A9A5' : '#6c757d');
    $adjSign = $adj > 0 ? '+' : '';
    $row['adjustment'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $adjColor . ';">' . $adjSign . round($adj * 100) . '%</span>',
      ],
    ];

    $row['adjusted_prob'] = round($entity->getAdjustedProbability() * 100) . '%';

    $urgency = $entity->getInterventionUrgency();
    $urgencyColors = [
      'none' => '#6c757d',
      'low' => '#00A9A5',
      'medium' => '#FFB84D',
      'high' => '#FF8C42',
      'critical' => '#DC3545',
    ];
    $urgencyColor = $urgencyColors[$urgency] ?? '#6c757d';
    $row['urgency'] = [
      'data' => [
        '#markup' => '<span style="color: ' . $urgencyColor . '; font-weight: bold;">' . ucfirst($urgency) . '</span>',
      ],
    ];

    $row['created'] = \Drupal::service('date.formatter')
      ->format((int) $entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
```

### 11.4 Formulario

#### `src/Form/VerticalRetentionProfileForm.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Vertical Retention Profile add/edit.
 */
class VerticalRetentionProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['vertical_id']['#group'] = 'basic_info';
    $form['label']['#group'] = 'basic_info';
    $form['status']['#group'] = 'basic_info';
    $form['max_inactivity_days']['#group'] = 'basic_info';

    $form['scoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Health Score Weights'),
      '#description' => $this->t('JSON object with keys: engagement, adoption, satisfaction, support, growth. Values must sum to 100. Example: {"engagement": 25, "adoption": 35, "satisfaction": 15, "support": 10, "growth": 15}'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    $form['health_score_weights']['#group'] = 'scoring';

    $form['seasonality'] = [
      '#type' => 'details',
      '#title' => $this->t('Seasonality Configuration'),
      '#description' => $this->t('JSON arrays for calendar, usage patterns, and seasonal offers.'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    $form['seasonality_calendar']['#group'] = 'seasonality';
    $form['expected_usage_pattern']['#group'] = 'seasonality';
    $form['seasonal_offers']['#group'] = 'seasonality';

    $form['churn_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Churn Detection'),
      '#description' => $this->t('Vertical-specific churn signals and features.'),
      '#open' => TRUE,
      '#weight' => 30,
    ];
    $form['churn_risk_signals']['#group'] = 'churn_config';
    $form['critical_features']['#group'] = 'churn_config';

    $form['engagement'] = [
      '#type' => 'details',
      '#title' => $this->t('Re-engagement & Expansion'),
      '#open' => FALSE,
      '#weight' => 40,
    ];
    $form['reengagement_triggers']['#group'] = 'engagement';
    $form['upsell_signals']['#group'] = 'engagement';
    $form['playbook_overrides']['#group'] = 'engagement';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate health_score_weights sums to 100.
    $weightsRaw = $form_state->getValue(['health_score_weights', 0, 'value']);
    if ($weightsRaw) {
      $weights = json_decode($weightsRaw, TRUE);
      if (!is_array($weights)) {
        $form_state->setErrorByName('health_score_weights', $this->t('Health Score Weights must be valid JSON.'));
      }
      elseif (array_sum($weights) !== 100) {
        $form_state->setErrorByName('health_score_weights', $this->t('Health Score Weights must sum to exactly 100. Current sum: @sum.', [
          '@sum' => array_sum($weights),
        ]));
      }
    }

    // Validate seasonality_calendar is valid JSON array with 12 entries.
    $calendarRaw = $form_state->getValue(['seasonality_calendar', 0, 'value']);
    if ($calendarRaw) {
      $calendar = json_decode($calendarRaw, TRUE);
      if (!is_array($calendar)) {
        $form_state->setErrorByName('seasonality_calendar', $this->t('Seasonality Calendar must be valid JSON.'));
      }
      elseif (count($calendar) !== 12) {
        $form_state->setErrorByName('seasonality_calendar', $this->t('Seasonality Calendar must have exactly 12 entries (one per month).'));
      }
    }

    // Validate churn_risk_signals is valid JSON array.
    $signalsRaw = $form_state->getValue(['churn_risk_signals', 0, 'value']);
    if ($signalsRaw) {
      $signals = json_decode($signalsRaw, TRUE);
      if (!is_array($signals)) {
        $form_state->setErrorByName('churn_risk_signals', $this->t('Churn Risk Signals must be a valid JSON array.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $label = $this->entity->label();
    $messageArgs = ['%label' => $label];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Vertical Retention Profile %label has been created.', $messageArgs));
    }
    else {
      $this->messenger()->addStatus($this->t('Vertical Retention Profile %label has been updated.', $messageArgs));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
```

### 11.5 Servicios

#### `src/Service/VerticalRetentionService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface;
use Psr\Log\LoggerInterface;

/**
 * Vertical retention evaluation engine.
 *
 * Orchestrates health score re-weighting, vertical-specific churn signal
 * evaluation, seasonal inactivity detection, and playbook selection
 * for each tenant based on their vertical profile.
 */
class VerticalRetentionService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected HealthScoreCalculatorService $healthCalculator,
    protected EngagementScoringService $engagementScoring,
    protected LifecycleStageService $lifecycleStage,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evaluates a tenant's retention risk with vertical-specific logic.
   *
   * @param string $tenantId
   *   The tenant group entity ID.
   *
   * @return array
   *   Evaluation result with keys: tenant_id, vertical_id, health_score,
   *   adjusted_health_score, risk_level, is_seasonal_inactivity,
   *   signals_triggered, recommended_action, recommended_playbook_id,
   *   seasonal_context.
   */
  public function evaluateTenant(string $tenantId): array {
    $profile = $this->getProfileForTenant($tenantId);

    // Fallback to generic evaluation if no vertical profile exists.
    if (!$profile) {
      return $this->buildGenericEvaluation($tenantId);
    }

    // 1. Calculate base health score.
    $healthEntity = $this->healthCalculator->calculate($tenantId);
    $baseScore = $healthEntity ? (int) $healthEntity->get('overall_score')->value : 50;

    // 2. Re-weight with vertical weights.
    $adjustedScore = $this->reweightHealthScore($healthEntity, $profile);

    // 3. Get seasonal context.
    $currentMonth = (int) date('n');
    $seasonalAdj = $profile->getSeasonalAdjustment($currentMonth);
    $expectedPattern = $profile->getExpectedUsagePattern()[$currentMonth] ?? 'medium';

    // 4. Evaluate vertical-specific signals.
    $signals = $this->evaluateVerticalSignals($tenantId, $profile);
    $signalWeight = array_sum(array_column($signals, 'weight'));

    // 5. Determine seasonal vs real inactivity.
    $isSeasonalInactivity = $this->isSeasonalInactivity(
      $tenantId,
      $profile,
      $currentMonth
    );

    // 6. Calculate risk level.
    $riskScore = $this->calculateRiskScore(
      $adjustedScore,
      $signalWeight,
      $seasonalAdj,
      $isSeasonalInactivity
    );
    $riskLevel = $this->classifyRisk($riskScore);

    // 7. Select recommended action and playbook.
    $recommendedAction = $this->determineAction($riskLevel, $isSeasonalInactivity);
    $playbookId = $this->selectPlaybook($profile, $riskLevel);

    return [
      'tenant_id' => $tenantId,
      'vertical_id' => $profile->getVerticalId(),
      'health_score' => $baseScore,
      'adjusted_health_score' => $adjustedScore,
      'risk_level' => $riskLevel,
      'is_seasonal_inactivity' => $isSeasonalInactivity,
      'signals_triggered' => $signals,
      'recommended_action' => $recommendedAction,
      'recommended_playbook_id' => $playbookId,
      'seasonal_context' => [
        'month' => $currentMonth,
        'adjustment' => $seasonalAdj,
        'expected_pattern' => $expectedPattern,
      ],
    ];
  }

  /**
   * Evaluates vertical-specific churn signals for a tenant.
   *
   * @return array
   *   Array of triggered signals with signal_id, weight, description.
   */
  public function evaluateVerticalSignals(string $tenantId, VerticalRetentionProfileInterface $profile): array {
    $triggered = [];
    $signals = $profile->getChurnRiskSignals();

    foreach ($signals as $signal) {
      $metricValue = $this->getMetricValue($tenantId, $signal['metric'], (int) ($signal['lookback_days'] ?? 30));
      if ($this->evaluateCondition($metricValue, $signal['operator'], $signal['threshold'])) {
        $triggered[] = [
          'signal_id' => $signal['signal_id'],
          'weight' => (float) $signal['weight'],
          'description' => $signal['description'] ?? '',
          'metric_value' => $metricValue,
        ];
      }
    }

    return $triggered;
  }

  /**
   * Gets the vertical retention profile for a given tenant.
   */
  public function getProfileForTenant(string $tenantId): ?VerticalRetentionProfileInterface {
    // Check State cache.
    $cacheKey = 'jaraba_cs.retention_profile.' . $tenantId;
    $cachedProfileId = $this->state->get($cacheKey);
    $cacheTimestamp = $this->state->get($cacheKey . '.ts', 0);

    // Cache valid for 24 hours.
    if ($cachedProfileId && (time() - $cacheTimestamp) < 86400) {
      $profile = $this->entityTypeManager
        ->getStorage('vertical_retention_profile')
        ->load($cachedProfileId);
      if ($profile instanceof VerticalRetentionProfileInterface && $profile->isActive()) {
        return $profile;
      }
    }

    // Resolve vertical from tenant group type.
    $verticalId = $this->resolveVerticalId($tenantId);
    if (!$verticalId) {
      return NULL;
    }

    // Query profile by vertical_id.
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties([
        'vertical_id' => $verticalId,
        'status' => VerticalRetentionProfileInterface::STATUS_ACTIVE,
      ]);

    $profile = reset($profiles) ?: NULL;

    // Cache the result.
    if ($profile) {
      $this->state->set($cacheKey, $profile->id());
      $this->state->set($cacheKey . '.ts', time());
    }

    return $profile;
  }

  /**
   * Gets a full risk assessment for API consumption.
   *
   * @return array
   *   Combined evaluation + prediction history.
   */
  public function getRiskAssessment(string $tenantId): array {
    $evaluation = $this->evaluateTenant($tenantId);

    // Get prediction history from SeasonalChurnService.
    // This will be injected at runtime via the controller.
    $evaluation['prediction_history'] = [];

    return $evaluation;
  }

  /**
   * Runs batch evaluation for all active tenants.
   *
   * @return int
   *   Number of tenants evaluated.
   */
  public function runBatchEvaluation(): int {
    $count = 0;

    try {
      $tenants = $this->entityTypeManager
        ->getStorage('group')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'tenant')
        ->execute();

      foreach ($tenants as $tenantId) {
        try {
          $this->evaluateTenant((string) $tenantId);
          $count++;
        }
        catch (\Exception $e) {
          $this->logger->error('Retention evaluation failed for tenant @id: @error', [
            '@id' => $tenantId,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Batch retention evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    $this->state->set('jaraba_cs.retention_last_batch', time());
    $this->logger->info('Vertical retention batch completed: @count tenants evaluated.', [
      '@count' => $count,
    ]);

    return $count;
  }

  /**
   * Re-weights health score with vertical-specific weights.
   */
  protected function reweightHealthScore($healthEntity, VerticalRetentionProfileInterface $profile): int {
    if (!$healthEntity) {
      return 50;
    }

    $weights = $profile->getHealthScoreWeights();
    $engagement = (int) $healthEntity->get('engagement_score')->value;
    $adoption = (int) $healthEntity->get('adoption_score')->value;
    $satisfaction = (int) $healthEntity->get('satisfaction_score')->value;
    $support = (int) $healthEntity->get('support_score')->value;
    $growth = (int) $healthEntity->get('growth_score')->value;

    $score = (
      $engagement * ($weights['engagement'] ?? 30) +
      $adoption * ($weights['adoption'] ?? 25) +
      $satisfaction * ($weights['satisfaction'] ?? 20) +
      $support * ($weights['support'] ?? 15) +
      $growth * ($weights['growth'] ?? 10)
    ) / 100;

    return (int) round(max(0, min(100, $score)));
  }

  /**
   * Determines if inactivity is seasonal (expected) vs real churn.
   */
  protected function isSeasonalInactivity(string $tenantId, VerticalRetentionProfileInterface $profile, int $month): bool {
    $expectedPattern = $profile->getExpectedUsagePattern()[$month] ?? 'medium';

    // If we expect low usage this month, inactivity is likely seasonal.
    if ($expectedPattern === 'low') {
      $daysInactive = $this->getDaysInactive($tenantId);
      return $daysInactive <= $profile->getMaxInactivityDays();
    }

    return FALSE;
  }

  /**
   * Calculates composite risk score.
   */
  protected function calculateRiskScore(int $healthScore, float $signalWeight, float $seasonalAdj, bool $isSeasonal): float {
    // Base risk from health score (inverted: low health = high risk).
    $baseRisk = (100 - $healthScore) / 100;

    // Add signal weight contribution.
    $riskScore = $baseRisk * 0.5 + $signalWeight * 0.3 + max(0, $seasonalAdj) * 0.2;

    // Reduce risk if inactivity is seasonal.
    if ($isSeasonal) {
      $riskScore *= 0.6;
    }

    return max(0.0, min(1.0, $riskScore));
  }

  /**
   * Classifies risk level from score.
   */
  protected function classifyRisk(float $riskScore): string {
    if ($riskScore < 0.25) {
      return 'low';
    }
    if ($riskScore < 0.50) {
      return 'medium';
    }
    if ($riskScore < 0.75) {
      return 'high';
    }
    return 'critical';
  }

  /**
   * Determines recommended action based on risk and seasonality.
   */
  protected function determineAction(string $riskLevel, bool $isSeasonal): string {
    if ($isSeasonal) {
      return 'monitor';
    }
    return match ($riskLevel) {
      'critical' => 'immediate_intervention',
      'high' => 'reengagement',
      'medium' => 'proactive_outreach',
      'low' => 'monitor',
      default => 'monitor',
    };
  }

  /**
   * Selects appropriate playbook from vertical overrides.
   */
  protected function selectPlaybook(VerticalRetentionProfileInterface $profile, string $riskLevel): ?int {
    $overrides = $profile->getPlaybookOverrides();
    $triggerType = match ($riskLevel) {
      'critical', 'high' => 'churn_risk',
      'medium' => 'health_drop',
      default => NULL,
    };

    if ($triggerType && isset($overrides[$triggerType])) {
      return (int) $overrides[$triggerType];
    }
    return NULL;
  }

  /**
   * Resolves vertical ID from a tenant group entity.
   */
  protected function resolveVerticalId(string $tenantId): ?string {
    try {
      $group = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$group) {
        return NULL;
      }
      // Map group type to vertical_id. Convention:
      // group type machine name contains the vertical identifier.
      $groupType = $group->bundle();
      $verticalMap = [
        'tenant_agroconecta' => 'agroconecta',
        'tenant_comercioconecta' => 'comercioconecta',
        'tenant_serviciosconecta' => 'serviciosconecta',
        'tenant_empleabilidad' => 'empleabilidad',
        'tenant_emprendimiento' => 'emprendimiento',
        // Fallback: use group type as vertical.
        'tenant' => NULL,
      ];
      return $verticalMap[$groupType] ?? NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to resolve vertical for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets a metric value for a tenant from usage logs.
   */
  protected function getMetricValue(string $tenantId, string $metric, int $lookbackDays): mixed {
    try {
      $connection = \Drupal::database();
      $since = strtotime("-{$lookbackDays} days");

      $count = $connection->select('finops_usage_log', 'f')
        ->condition('f.tenant_id', $tenantId)
        ->condition('f.metric_type', $metric)
        ->condition('f.created', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();

      return (int) $count;
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not fetch metric @metric for tenant @tenant: @error', [
        '@metric' => $metric,
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Evaluates a condition: value OPERATOR threshold.
   */
  protected function evaluateCondition(mixed $value, string $operator, mixed $threshold): bool {
    return match ($operator) {
      '==' => $value == $threshold,
      '!=' => $value != $threshold,
      '>' => $value > $threshold,
      '>=' => $value >= $threshold,
      '<' => $value < $threshold,
      '<=' => $value <= $threshold,
      default => FALSE,
    };
  }

  /**
   * Gets number of days since last activity for a tenant.
   */
  protected function getDaysInactive(string $tenantId): int {
    try {
      $connection = \Drupal::database();
      $lastActivity = $connection->select('finops_usage_log', 'f')
        ->fields('f', ['created'])
        ->condition('f.tenant_id', $tenantId)
        ->orderBy('f.created', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($lastActivity) {
        return (int) ((time() - (int) $lastActivity) / 86400);
      }
    }
    catch (\Exception $e) {
      // Ignore — return max inactivity.
    }
    return 999;
  }

  /**
   * Builds a generic evaluation for tenants without vertical profile.
   */
  protected function buildGenericEvaluation(string $tenantId): array {
    $healthEntity = $this->healthCalculator->calculate($tenantId);
    $baseScore = $healthEntity ? (int) $healthEntity->get('overall_score')->value : 50;

    return [
      'tenant_id' => $tenantId,
      'vertical_id' => 'generic',
      'health_score' => $baseScore,
      'adjusted_health_score' => $baseScore,
      'risk_level' => $this->classifyRisk((100 - $baseScore) / 100),
      'is_seasonal_inactivity' => FALSE,
      'signals_triggered' => [],
      'recommended_action' => 'monitor',
      'recommended_playbook_id' => NULL,
      'seasonal_context' => [],
    ];
  }

}
```

#### `src/Service/SeasonalChurnService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_customer_success\Entity\SeasonalChurnPrediction;
use Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface;
use Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface;
use Psr\Log\LoggerInterface;

/**
 * Seasonal churn prediction service.
 *
 * Generates monthly churn predictions adjusted for seasonal patterns
 * specific to each vertical. All predictions are append-only.
 */
class SeasonalChurnService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ChurnPredictionService $churnPrediction,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Generates a seasonal churn prediction for a tenant.
   *
   * @param string $tenantId
   *   The tenant group entity ID.
   * @param \Drupal\jaraba_customer_success\Entity\VerticalRetentionProfileInterface $profile
   *   The vertical retention profile.
   *
   * @return \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface
   *   The created prediction entity.
   */
  public function predict(string $tenantId, VerticalRetentionProfileInterface $profile): SeasonalChurnPredictionInterface {
    // 1. Get base churn probability from generic predictor.
    $baseProbability = $this->getBaseProbability($tenantId);

    // 2. Get seasonal adjustment.
    $currentMonth = (int) date('n');
    $seasonalAdjustment = $profile->getSeasonalAdjustment($currentMonth);

    // 3. Calculate adjusted probability (clamped to 0-1).
    $adjustedProbability = max(0.0, min(1.0, $baseProbability + $seasonalAdjustment));

    // 4. Build seasonal context.
    $calendar = $profile->getSeasonalityCalendar();
    $monthEntry = $calendar[$currentMonth - 1] ?? [];
    $expectedPattern = $profile->getExpectedUsagePattern()[$currentMonth] ?? 'medium';

    $seasonalContext = [
      'month_label' => $monthEntry['label'] ?? '',
      'month_risk_level' => $monthEntry['risk_level'] ?? 'medium',
      'expected_pattern' => $expectedPattern,
      'base_probability' => $baseProbability,
      'adjustment_applied' => $seasonalAdjustment,
    ];

    // 5. Determine urgency.
    $urgency = $this->classifyUrgency($adjustedProbability);

    // 6. Select recommended playbook.
    $playbookId = $this->selectPlaybook($profile, $urgency);

    // 7. Create prediction entity (append-only).
    $predictionMonth = date('Y-m');
    $storage = $this->entityTypeManager->getStorage('seasonal_churn_prediction');

    /** @var \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface $prediction */
    $prediction = $storage->create([
      'tenant_id' => $tenantId,
      'vertical_id' => $profile->getVerticalId(),
      'prediction_month' => $predictionMonth,
      'base_churn_probability' => $baseProbability,
      'seasonal_adjustment' => $seasonalAdjustment,
      'adjusted_probability' => $adjustedProbability,
      'seasonal_context' => json_encode($seasonalContext, JSON_THROW_ON_ERROR),
      'recommended_playbook' => $playbookId,
      'intervention_urgency' => $urgency,
    ]);
    $prediction->save();

    $this->logger->info('Seasonal prediction for tenant @tenant (@vertical): @prob% (base @base% + adj @adj%).', [
      '@tenant' => $tenantId,
      '@vertical' => $profile->getVerticalId(),
      '@prob' => round($adjustedProbability * 100),
      '@base' => round($baseProbability * 100),
      '@adj' => round($seasonalAdjustment * 100),
    ]);

    return $prediction;
  }

  /**
   * Gets the latest prediction for a tenant.
   */
  public function getLatestPrediction(string $tenantId): ?SeasonalChurnPredictionInterface {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->load(reset($ids));
  }

  /**
   * Gets prediction history for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param int $months
   *   Number of recent predictions to return.
   *
   * @return \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface[]
   */
  public function getPredictionHistory(string $tenantId, int $months = 6): array {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'DESC')
      ->range(0, $months)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);
  }

  /**
   * Gets all predictions for a given month.
   *
   * @param string $month
   *   Month in YYYY-MM format.
   *
   * @return \Drupal\jaraba_customer_success\Entity\SeasonalChurnPredictionInterface[]
   */
  public function getMonthlyPredictions(string $month): array {
    $ids = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('prediction_month', $month)
      ->sort('adjusted_probability', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);
  }

  /**
   * Runs monthly predictions for all active tenants.
   *
   * @return int
   *   Number of predictions generated.
   */
  public function runMonthlyPredictions(): int {
    $count = 0;

    try {
      $profiles = $this->entityTypeManager
        ->getStorage('vertical_retention_profile')
        ->loadByProperties(['status' => VerticalRetentionProfileInterface::STATUS_ACTIVE]);

      foreach ($profiles as $profile) {
        // Get all tenants for this vertical.
        $tenants = $this->entityTypeManager
          ->getStorage('group')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', 'tenant')
          ->execute();

        foreach ($tenants as $tenantId) {
          try {
            $this->predict((string) $tenantId, $profile);
            $count++;
          }
          catch (\Exception $e) {
            $this->logger->error('Seasonal prediction failed for tenant @id: @error', [
              '@id' => $tenantId,
              '@error' => $e->getMessage(),
            ]);
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Monthly prediction batch failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    $this->state->set('jaraba_cs.seasonal_last_predictions', time());
    $this->logger->info('Seasonal predictions batch completed: @count predictions generated.', [
      '@count' => $count,
    ]);

    return $count;
  }

  /**
   * Gets base churn probability from the generic predictor.
   */
  protected function getBaseProbability(string $tenantId): float {
    try {
      $predictions = $this->entityTypeManager
        ->getStorage('churn_prediction')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($predictions)) {
        $prediction = $this->entityTypeManager
          ->getStorage('churn_prediction')
          ->load(reset($predictions));
        if ($prediction) {
          return (float) $prediction->get('probability')->value;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not fetch base probability for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    // Default moderate probability if no generic prediction exists.
    return 0.30;
  }

  /**
   * Classifies urgency from probability.
   */
  protected function classifyUrgency(float $probability): string {
    if ($probability < 0.15) {
      return SeasonalChurnPrediction::URGENCY_NONE;
    }
    if ($probability < 0.30) {
      return SeasonalChurnPrediction::URGENCY_LOW;
    }
    if ($probability < 0.50) {
      return SeasonalChurnPrediction::URGENCY_MEDIUM;
    }
    if ($probability < 0.75) {
      return SeasonalChurnPrediction::URGENCY_HIGH;
    }
    return SeasonalChurnPrediction::URGENCY_CRITICAL;
  }

  /**
   * Selects a playbook for the given urgency and vertical.
   */
  protected function selectPlaybook(VerticalRetentionProfileInterface $profile, string $urgency): ?int {
    if ($urgency === SeasonalChurnPrediction::URGENCY_NONE) {
      return NULL;
    }

    $overrides = $profile->getPlaybookOverrides();
    $triggerType = match ($urgency) {
      SeasonalChurnPrediction::URGENCY_CRITICAL,
      SeasonalChurnPrediction::URGENCY_HIGH => 'churn_risk',
      SeasonalChurnPrediction::URGENCY_MEDIUM => 'health_drop',
      default => NULL,
    };

    if ($triggerType && isset($overrides[$triggerType])) {
      return (int) $overrides[$triggerType];
    }
    return NULL;
  }

}
```

### 11.6 Controllers

#### `src/Controller/RetentionApiController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for Vertical Retention endpoints.
 */
class RetentionApiController extends ControllerBase {

  public function __construct(
    protected VerticalRetentionService $retentionService,
    protected SeasonalChurnService $seasonalChurnService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.vertical_retention'),
      $container->get('jaraba_customer_success.seasonal_churn'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * GET /api/v1/retention/profiles — List all vertical profiles.
   */
  public function listProfiles(): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadMultiple();

    $data = [];
    foreach ($profiles as $profile) {
      $data[] = [
        'id' => (int) $profile->id(),
        'vertical_id' => $profile->getVerticalId(),
        'label' => $profile->getLabel(),
        'status' => $profile->get('status')->value,
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'health_score_weights' => $profile->getHealthScoreWeights(),
        'critical_features_count' => count($profile->getCriticalFeatures()),
        'churn_signals_count' => count($profile->getChurnRiskSignals()),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * GET /api/v1/retention/profiles/{vertical_id} — Get profile detail.
   */
  public function getProfile(string $vertical_id): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['vertical_id' => $vertical_id]);

    $profile = reset($profiles);
    if (!$profile) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Profile not found.')],
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $profile->id(),
        'vertical_id' => $profile->getVerticalId(),
        'label' => $profile->getLabel(),
        'seasonality_calendar' => $profile->getSeasonalityCalendar(),
        'churn_risk_signals' => $profile->getChurnRiskSignals(),
        'health_score_weights' => $profile->getHealthScoreWeights(),
        'critical_features' => $profile->getCriticalFeatures(),
        'reengagement_triggers' => $profile->getReengagementTriggers(),
        'upsell_signals' => $profile->getUpsellSignals(),
        'seasonal_offers' => $profile->getSeasonalOffers(),
        'expected_usage_pattern' => $profile->getExpectedUsagePattern(),
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'playbook_overrides' => $profile->getPlaybookOverrides(),
      ],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * PUT /api/v1/retention/profiles/{vertical_id} — Update profile.
   */
  public function updateProfile(string $vertical_id, Request $request): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['vertical_id' => $vertical_id]);

    $profile = reset($profiles);
    if (!$profile) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Profile not found.')],
      ], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (empty($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'BAD_REQUEST', 'message' => (string) $this->t('Request body must be valid JSON.')],
      ], 400);
    }

    // Validate weights if provided.
    if (isset($data['health_score_weights'])) {
      $weights = $data['health_score_weights'];
      if (!is_array($weights) || array_sum($weights) !== 100) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Health score weights must sum to 100.')],
        ], 422);
      }
    }

    // Validate max_inactivity_days if provided.
    if (isset($data['max_inactivity_days'])) {
      $days = (int) $data['max_inactivity_days'];
      if ($days < 7 || $days > 180) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Max inactivity days must be between 7 and 180.')],
        ], 422);
      }
    }

    // Apply updates.
    $jsonFields = [
      'seasonality_calendar', 'churn_risk_signals', 'health_score_weights',
      'critical_features', 'reengagement_triggers', 'upsell_signals',
      'seasonal_offers', 'expected_usage_pattern', 'playbook_overrides',
    ];

    foreach ($data as $field => $value) {
      if ($profile->hasField($field)) {
        if (in_array($field, $jsonFields, TRUE) && is_array($value)) {
          $profile->set($field, json_encode($value, JSON_THROW_ON_ERROR));
        }
        else {
          $profile->set($field, $value);
        }
      }
    }

    $profile->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['vertical_id' => $profile->getVerticalId()],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * GET /api/v1/retention/risk-assessment/{tenant_id} — Risk assessment.
   */
  public function riskAssessment(string $tenant_id): JsonResponse {
    $evaluation = $this->retentionService->evaluateTenant($tenant_id);

    // Add prediction history.
    $history = $this->seasonalChurnService->getPredictionHistory($tenant_id, 6);
    $predictionHistory = [];
    foreach ($history as $prediction) {
      $predictionHistory[] = [
        'month' => $prediction->getPredictionMonth(),
        'base_probability' => $prediction->getBaseProbability(),
        'seasonal_adjustment' => $prediction->getSeasonalAdjustment(),
        'adjusted_probability' => $prediction->getAdjustedProbability(),
        'urgency' => $prediction->getInterventionUrgency(),
      ];
    }
    $evaluation['prediction_history'] = $predictionHistory;

    // Add latest seasonal prediction detail.
    $latest = $this->seasonalChurnService->getLatestPrediction($tenant_id);
    if ($latest) {
      $evaluation['seasonal_prediction'] = [
        'base_probability' => $latest->getBaseProbability(),
        'seasonal_adjustment' => $latest->getSeasonalAdjustment(),
        'adjusted_probability' => $latest->getAdjustedProbability(),
        'month_label' => $latest->getSeasonalContext()['month_label'] ?? '',
        'urgency' => $latest->getInterventionUrgency(),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $evaluation,
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * GET /api/v1/retention/seasonal-predictions — List predictions.
   */
  public function seasonalPredictions(Request $request): JsonResponse {
    $month = $request->query->get('month', date('Y-m'));
    $verticalId = $request->query->get('vertical_id');
    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $query = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('prediction_month', $month)
      ->sort('adjusted_probability', 'DESC')
      ->range($offset, $limit);

    if ($verticalId) {
      $query->condition('vertical_id', $verticalId);
    }

    $ids = $query->execute();
    $predictions = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);

    $data = [];
    foreach ($predictions as $prediction) {
      $data[] = [
        'id' => (int) $prediction->id(),
        'tenant_id' => $prediction->getTenantId(),
        'vertical_id' => $prediction->getVerticalId(),
        'prediction_month' => $prediction->getPredictionMonth(),
        'base_probability' => $prediction->getBaseProbability(),
        'seasonal_adjustment' => $prediction->getSeasonalAdjustment(),
        'adjusted_probability' => $prediction->getAdjustedProbability(),
        'urgency' => $prediction->getInterventionUrgency(),
        'created' => date('c', (int) $prediction->get('created')->value),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'month' => $month,
        'offset' => $offset,
        'limit' => $limit,
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * GET /api/v1/retention/playbook-executions — List executions.
   */
  public function playbookExecutions(Request $request): JsonResponse {
    $status = $request->query->get('status');
    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $query = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('started_at', 'DESC')
      ->range($offset, $limit);

    if ($status) {
      $query->condition('status', $status);
    }

    $ids = $query->execute();
    $executions = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->loadMultiple($ids);

    $data = [];
    foreach ($executions as $execution) {
      $playbookRef = $execution->get('playbook_id')->entity;
      $tenantRef = $execution->get('tenant_id')->entity;
      $data[] = [
        'id' => (int) $execution->id(),
        'playbook_name' => $playbookRef ? $playbookRef->label() : '',
        'tenant_name' => $tenantRef ? $tenantRef->label() : '',
        'current_step' => (int) $execution->get('current_step')->value,
        'total_steps' => (int) $execution->get('total_steps')->value,
        'status' => $execution->get('status')->value,
        'started_at' => date('c', (int) $execution->get('started_at')->value),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'offset' => $offset,
        'limit' => $limit,
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * POST /api/v1/retention/playbook-executions/{id}/override — Override execution.
   */
  public function overrideExecution(string $id, Request $request): JsonResponse {
    $execution = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->load($id);

    if (!$execution) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Execution not found.')],
      ], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (empty($data) || !isset($data['action'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'BAD_REQUEST', 'message' => (string) $this->t('Action is required.')],
      ], 400);
    }

    $action = $data['action'];
    $allowedActions = ['pause', 'resume', 'cancel'];
    if (!in_array($action, $allowedActions, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Action must be one of: @actions.', [
          '@actions' => implode(', ', $allowedActions),
        ])],
      ], 422);
    }

    $currentStatus = $execution->get('status')->value;
    $newStatus = match ($action) {
      'pause' => 'paused',
      'resume' => 'running',
      'cancel' => 'cancelled',
    };

    // Validate state transitions.
    $validTransitions = [
      'running' => ['paused', 'cancelled'],
      'paused' => ['running', 'cancelled'],
    ];

    if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus], TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_TRANSITION', 'message' => (string) $this->t('Cannot @action an execution with status @status.', [
          '@action' => $action,
          '@status' => $currentStatus,
        ])],
      ], 409);
    }

    $execution->set('status', $newStatus);
    if ($newStatus === 'cancelled') {
      $execution->set('completed_at', time());
    }
    $execution->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $execution->id(),
        'status' => $newStatus,
        'reason' => $data['reason'] ?? '',
      ],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

}
```

#### `src/Controller/RetentionDashboardController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Frontend dashboard controller for Vertical Retention.
 */
class RetentionDashboardController extends ControllerBase {

  public function __construct(
    protected VerticalRetentionService $retentionService,
    protected SeasonalChurnService $seasonalChurnService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.vertical_retention'),
      $container->get('jaraba_customer_success.seasonal_churn'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Renders the retention dashboard.
   */
  public function dashboard(): array {
    // Load all vertical profiles for heatmap.
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['status' => 'active']);

    $heatmapData = [];
    $riskCards = [];

    foreach ($profiles as $profile) {
      $verticalId = $profile->getVerticalId();
      $calendar = $profile->getSeasonalityCalendar();

      // Build heatmap row.
      $heatmapData[$verticalId] = [
        'label' => $profile->getLabel(),
        'months' => [],
      ];
      foreach ($calendar as $entry) {
        $heatmapData[$verticalId]['months'][(int) $entry['month']] = [
          'risk_level' => $entry['risk_level'] ?? 'medium',
          'label' => $entry['label'] ?? '',
          'adjustment' => $entry['adjustment'] ?? 0,
        ];
      }

      // Build risk card data.
      $riskCards[$verticalId] = [
        'label' => $profile->getLabel(),
        'vertical_id' => $verticalId,
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'signals_count' => count($profile->getChurnRiskSignals()),
        'critical_features' => $profile->getCriticalFeatures(),
        'at_risk_count' => 0,
        'total_count' => 0,
        'top_signals' => array_slice($profile->getChurnRiskSignals(), 0, 3),
      ];
    }

    // Count tenants and get recent executions.
    $totalTenants = 0;
    $atRiskCount = 0;

    try {
      $tenantCount = $this->entityTypeManager
        ->getStorage('group')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();
      $totalTenants = (int) $tenantCount;
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Get recent playbook executions.
    $recentExecutions = [];
    try {
      $executionIds = $this->entityTypeManager
        ->getStorage('playbook_execution')
        ->getQuery()
        ->accessCheck(TRUE)
        ->sort('started_at', 'DESC')
        ->range(0, 20)
        ->execute();

      $executions = $this->entityTypeManager
        ->getStorage('playbook_execution')
        ->loadMultiple($executionIds);

      foreach ($executions as $execution) {
        $playbookRef = $execution->get('playbook_id')->entity;
        $tenantRef = $execution->get('tenant_id')->entity;
        $recentExecutions[] = [
          'playbook_name' => $playbookRef ? $playbookRef->label() : (string) $this->t('Unknown'),
          'tenant_name' => $tenantRef ? $tenantRef->label() : (string) $this->t('Unknown'),
          'current_step' => (int) $execution->get('current_step')->value,
          'total_steps' => (int) $execution->get('total_steps')->value,
          'status' => $execution->get('status')->value,
          'started_at' => (int) $execution->get('started_at')->value,
        ];
      }
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Count active playbooks.
    $activePlaybooksCount = 0;
    try {
      $activePlaybooksCount = (int) $this->entityTypeManager
        ->getStorage('cs_playbook')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Ignore.
    }

    return [
      '#theme' => 'jaraba_cs_retention_dashboard',
      '#stats' => [
        'total_tenants' => $totalTenants,
        'at_risk_tenants' => $atRiskCount,
        'retention_rate' => $totalTenants > 0 ? round((($totalTenants - $atRiskCount) / $totalTenants) * 100, 1) : 100,
        'active_playbooks' => $activePlaybooksCount,
      ],
      '#heatmap_data' => $heatmapData,
      '#risk_cards' => $riskCards,
      '#recent_executions' => $recentExecutions,
      '#attached' => [
        'library' => ['jaraba_customer_success/retention-dashboard'],
        'drupalSettings' => [
          'jarabaCs' => [
            'retentionHeatmap' => $heatmapData,
            'currentMonth' => (int) date('n'),
          ],
        ],
      ],
      '#cache' => [
        'tags' => [
          'vertical_retention_profile_list',
          'seasonal_churn_prediction_list',
          'playbook_execution_list',
        ],
        'max-age' => 300,
      ],
    ];
  }

}
```

### 11.7 QueueWorker

#### `src/Plugin/QueueWorker/VerticalRetentionCronWorker.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for vertical retention cron operations.
 *
 * @QueueWorker(
 *   id = "jaraba_vertical_retention_cron",
 *   title = @Translation("Vertical Retention cron operations"),
 *   cron = {"time" = 120}
 * )
 */
class VerticalRetentionCronWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected VerticalRetentionService $retentionService,
    protected SeasonalChurnService $seasonalChurnService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_customer_success.vertical_retention'),
      $container->get('jaraba_customer_success.seasonal_churn'),
      $container->get('logger.channel.jaraba_customer_success'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $operation = $data['operation'] ?? '';

    match ($operation) {
      'vertical_evaluation' => $this->retentionService->runBatchEvaluation(),
      'seasonal_predictions' => $this->seasonalChurnService->runMonthlyPredictions(),
      default => $this->logger->warning('Unknown vertical retention operation: @op', ['@op' => $operation]),
    };
  }

}
```

### 11.8 Routing YAML

Additions to `jaraba_customer_success.routing.yml`:

```yaml
# === Vertical Retention Routes ===

# Frontend dashboard.
jaraba_customer_success.retention_dashboard:
  path: '/customer-success/retention'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionDashboardController::dashboard'
    _title: 'Retention Dashboard'
  requirements:
    _permission: 'view customer health scores'

# API: List retention profiles.
jaraba_customer_success.api.retention.profiles:
  path: '/api/v1/retention/profiles'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::listProfiles'
  methods: [GET]
  requirements:
    _permission: 'administer customer success'

# API: Get retention profile detail.
jaraba_customer_success.api.retention.profile_detail:
  path: '/api/v1/retention/profiles/{vertical_id}'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::getProfile'
  methods: [GET]
  requirements:
    _permission: 'view customer health scores'

# API: Update retention profile.
jaraba_customer_success.api.retention.profile_update:
  path: '/api/v1/retention/profiles/{vertical_id}'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::updateProfile'
  methods: [PUT]
  requirements:
    _permission: 'administer customer success'
    _csrf_request_header_token: 'TRUE'

# API: Tenant risk assessment.
jaraba_customer_success.api.retention.risk_assessment:
  path: '/api/v1/retention/risk-assessment/{tenant_id}'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::riskAssessment'
  methods: [GET]
  requirements:
    _permission: 'view churn predictions'

# API: Seasonal predictions.
jaraba_customer_success.api.retention.seasonal_predictions:
  path: '/api/v1/retention/seasonal-predictions'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::seasonalPredictions'
  methods: [GET]
  requirements:
    _permission: 'view churn predictions'

# API: Playbook executions list.
jaraba_customer_success.api.retention.playbook_executions:
  path: '/api/v1/retention/playbook-executions'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::playbookExecutions'
  methods: [GET]
  requirements:
    _permission: 'manage playbooks'

# API: Override playbook execution.
jaraba_customer_success.api.retention.override_execution:
  path: '/api/v1/retention/playbook-executions/{id}/override'
  defaults:
    _controller: '\Drupal\jaraba_customer_success\Controller\RetentionApiController::overrideExecution'
  methods: [POST]
  requirements:
    _permission: 'manage playbooks'
    _csrf_request_header_token: 'TRUE'
```

### 11.9 Services YAML

Additions to `jaraba_customer_success.services.yml`:

```yaml
  jaraba_customer_success.vertical_retention:
    class: Drupal\jaraba_customer_success\Service\VerticalRetentionService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_customer_success.health_calculator'
      - '@jaraba_customer_success.engagement_scoring'
      - '@jaraba_customer_success.lifecycle_stage'
      - '@state'
      - '@logger.channel.jaraba_customer_success'

  jaraba_customer_success.seasonal_churn:
    class: Drupal\jaraba_customer_success\Service\SeasonalChurnService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_customer_success.churn_prediction'
      - '@state'
      - '@logger.channel.jaraba_customer_success'
```

### 11.10 Permissions YAML

Additions to `jaraba_customer_success.permissions.yml`:

```yaml
manage retention profiles:
  title: 'Manage vertical retention profiles'
  description: 'Create, edit, and configure vertical retention profiles.'

view seasonal predictions:
  title: 'View seasonal churn predictions'
  description: 'View seasonally-adjusted churn predictions.'
```

### 11.11 Links YAML

#### Additions to `jaraba_customer_success.links.menu.yml`:

```yaml
jaraba_customer_success.retention_profiles:
  title: 'Retention Profiles'
  description: 'Manage vertical retention profiles.'
  route_name: entity.vertical_retention_profile.collection
  parent: system.admin_structure
  weight: 26
```

#### Additions to `jaraba_customer_success.links.task.yml`:

```yaml
jaraba_customer_success.retention_profiles_tab:
  title: 'Retention Profiles'
  route_name: entity.vertical_retention_profile.collection
  base_route: jaraba_customer_success.admin.dashboard
  weight: 25

jaraba_customer_success.seasonal_predictions_tab:
  title: 'Seasonal Predictions'
  route_name: entity.seasonal_churn_prediction.collection
  base_route: jaraba_customer_success.admin.dashboard
  weight: 35
```

#### Additions to `jaraba_customer_success.links.action.yml`:

```yaml
jaraba_customer_success.add_retention_profile:
  title: 'Add Retention Profile'
  route_name: entity.vertical_retention_profile.add_form
  appears_on:
    - entity.vertical_retention_profile.collection
```

### 11.12 Libraries YAML

Addition to `jaraba_customer_success.libraries.yml`:

```yaml
retention-dashboard:
  version: '1.0'
  css:
    theme:
      css/retention-dashboard.css: {}
  js:
    js/retention-dashboard.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

### 11.13 Schema YAML

Additions to `config/schema/jaraba_customer_success.schema.yml`:

```yaml
jaraba_customer_success.retention_profile.*:
  type: config_object
  label: 'Vertical Retention Profile configuration'
  mapping:
    vertical_id:
      type: string
      label: 'Vertical machine name'
    label:
      type: label
      label: 'Vertical display name'
    max_inactivity_days:
      type: integer
      label: 'Maximum inactivity days'
    health_score_weights:
      type: mapping
      label: 'Health score weights'
      mapping:
        engagement:
          type: integer
          label: 'Engagement weight'
        adoption:
          type: integer
          label: 'Adoption weight'
        satisfaction:
          type: integer
          label: 'Satisfaction weight'
        support:
          type: integer
          label: 'Support weight'
        growth:
          type: integer
          label: 'Growth weight'
    seasonality_calendar:
      type: sequence
      label: 'Seasonality calendar'
      sequence:
        type: mapping
        mapping:
          month:
            type: integer
            label: 'Month number'
          risk_level:
            type: string
            label: 'Risk level'
          label:
            type: string
            label: 'Month label'
          adjustment:
            type: float
            label: 'Seasonal adjustment'
    churn_risk_signals:
      type: sequence
      label: 'Churn risk signals'
      sequence:
        type: mapping
        mapping:
          signal_id:
            type: string
            label: 'Signal ID'
          metric:
            type: string
            label: 'Metric name'
          operator:
            type: string
            label: 'Comparison operator'
          threshold:
            type: integer
            label: 'Threshold value'
          lookback_days:
            type: integer
            label: 'Lookback days'
          weight:
            type: float
            label: 'Signal weight'
          description:
            type: string
            label: 'Signal description'
    critical_features:
      type: sequence
      label: 'Critical features'
      sequence:
        type: string
        label: 'Feature name'
    reengagement_triggers:
      type: sequence
      label: 'Re-engagement triggers'
      sequence:
        type: mapping
        mapping:
          trigger:
            type: string
            label: 'Trigger name'
          message_template:
            type: string
            label: 'Message template'
          delay_days:
            type: integer
            label: 'Delay days'
    upsell_signals:
      type: sequence
      label: 'Upsell signals'
      sequence:
        type: mapping
        mapping:
          signal:
            type: string
            label: 'Signal name'
          recommended_plan:
            type: string
            label: 'Recommended plan'
          message:
            type: string
            label: 'Upsell message'
    seasonal_offers:
      type: sequence
      label: 'Seasonal offers'
      sequence:
        type: mapping
        mapping:
          months:
            type: sequence
            label: 'Active months'
            sequence:
              type: integer
              label: 'Month number'
          offer_type:
            type: string
            label: 'Offer type'
          description:
            type: string
            label: 'Offer description'
    expected_usage_pattern:
      type: mapping
      label: 'Expected usage pattern by month'
    playbook_overrides:
      type: mapping
      label: 'Playbook overrides by trigger type'
```

### 11.14 Config Install YAML

See section 9 for the complete content of the 5 YAML files. The files are placed in `config/install/` and read programmatically by the update hook to create entity instances.

### 11.15 Module Hooks

Additions to `jaraba_customer_success.module`:

```php
/**
 * Implements hook_theme() — additions for retention dashboard.
 *
 * Add these entries to the existing hook_theme() return array:
 */

// Inside the existing jaraba_customer_success_theme() function, add:
'jaraba_cs_retention_dashboard' => [
  'variables' => [
    'stats' => [],
    'heatmap_data' => [],
    'risk_cards' => [],
    'recent_executions' => [],
  ],
  'template' => 'jaraba-cs-retention-dashboard',
],

/**
 * Implements hook_theme_suggestions_page_alter().
 *
 * Add to existing function or create new:
 */
function jaraba_customer_success_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route === 'jaraba_customer_success.retention_dashboard') {
    $suggestions[] = 'page__customer_success';
  }
}

/**
 * Implements hook_preprocess_html().
 *
 * Add body class for retention dashboard. Add to existing or create:
 */
function jaraba_customer_success_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route === 'jaraba_customer_success.retention_dashboard') {
    $variables['attributes']['class'][] = 'page-customer-success';
    $variables['attributes']['class'][] = 'page-retention-dashboard';
  }
}

/**
 * hook_cron() additions — add to existing function:
 */
// Inside the existing jaraba_customer_success_cron():
$retentionQueue = \Drupal::queue('jaraba_vertical_retention_cron');
$retentionQueue->createItem(['operation' => 'vertical_evaluation']);

// Seasonal predictions only on the 1st of each month.
if ((int) date('j') === 1) {
  $retentionQueue->createItem(['operation' => 'seasonal_predictions']);
}
```

### 11.16 Install / Update Hook

Addition to `jaraba_customer_success.install`:

```php
/**
 * Install Vertical Retention Profile and Seasonal Churn Prediction entities.
 * Seed 5 vertical retention profiles from YAML configuration.
 */
function jaraba_customer_success_update_10001(): void {
  // 1. Install new entity schemas.
  $entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();

  $entityTypeManager = \Drupal::entityTypeManager();

  // Install VerticalRetentionProfile entity type.
  $entityType = $entityTypeManager->getDefinition('vertical_retention_profile');
  if ($entityType) {
    $entityDefinitionUpdateManager->installEntityType($entityType);
  }

  // Install SeasonalChurnPrediction entity type.
  $entityType = $entityTypeManager->getDefinition('seasonal_churn_prediction');
  if ($entityType) {
    $entityDefinitionUpdateManager->installEntityType($entityType);
  }

  // 2. Seed vertical retention profiles from YAML files.
  $configPath = \Drupal::service('extension.list.module')
    ->getPath('jaraba_customer_success') . '/config/install';

  $verticals = [
    'agroconecta',
    'comercioconecta',
    'serviciosconecta',
    'empleabilidad',
    'emprendimiento',
  ];

  $storage = $entityTypeManager->getStorage('vertical_retention_profile');

  foreach ($verticals as $vertical) {
    $yamlFile = $configPath . '/jaraba_customer_success.retention_profile.' . $vertical . '.yml';
    if (!file_exists($yamlFile)) {
      continue;
    }

    $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);
    if (!$data) {
      continue;
    }

    // Check if already exists.
    $existing = $storage->loadByProperties(['vertical_id' => $vertical]);
    if (!empty($existing)) {
      continue;
    }

    // Create entity with JSON-encoded fields.
    $jsonFields = [
      'seasonality_calendar', 'churn_risk_signals', 'health_score_weights',
      'critical_features', 'reengagement_triggers', 'upsell_signals',
      'seasonal_offers', 'expected_usage_pattern', 'playbook_overrides',
    ];

    $entityData = [];
    foreach ($data as $key => $value) {
      if (in_array($key, $jsonFields, TRUE) && (is_array($value))) {
        $entityData[$key] = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
      }
      else {
        $entityData[$key] = $value;
      }
    }

    $entity = $storage->create($entityData);
    $entity->save();
  }

  \Drupal::messenger()->addStatus(t('Vertical Retention Playbooks: 2 entity types installed, 5 profiles seeded.'));
}
```

### 11.17 Templates Twig

#### `templates/jaraba-cs-retention-dashboard.html.twig`

```twig
{#
/**
 * @file
 * jaraba-cs-retention-dashboard.html.twig - Dashboard de retencion verticalizada.
 *
 * ARQUITECTURA:
 * - BEM: cs-retention / cs-retention__* / cs-retention--*
 * - Variables CSS var(--ej-*) para colores y tipografia.
 * - Zero-region: se renderiza dentro de {{ clean_content }}.
 *
 * VARIABLES:
 * - stats: array {total_tenants, at_risk_tenants, retention_rate, active_playbooks}.
 * - heatmap_data: array[vertical_id] => {label, months: array[month] => {risk_level, label, adjustment}}.
 * - risk_cards: array[vertical_id] => {label, vertical_id, max_inactivity_days, signals_count, ...}.
 * - recent_executions: array [{playbook_name, tenant_name, current_step, total_steps, status, started_at}, ...].
 */
#}

<div class="cs-retention">
  {# Header with navigation #}
  <header class="cs-retention__header">
    <div class="cs-retention__header-content">
      <h1 class="cs-retention__title">{% trans %}Vertical Retention Dashboard{% endtrans %}</h1>
      <p class="cs-retention__subtitle">{% trans %}Seasonal risk analysis and vertical-specific playbook management.{% endtrans %}</p>
    </div>
    <nav class="cs-retention__nav">
      <a href="{{ path('jaraba_customer_success.frontend.dashboard') }}" class="cs-retention__nav-link">{% trans %}Overview{% endtrans %}</a>
      <a href="{{ path('jaraba_customer_success.frontend.playbooks') }}" class="cs-retention__nav-link">{% trans %}Playbooks{% endtrans %}</a>
      <a href="{{ path('jaraba_customer_success.frontend.expansion') }}" class="cs-retention__nav-link">{% trans %}Expansion{% endtrans %}</a>
      <a href="{{ path('jaraba_customer_success.nps_survey') }}" class="cs-retention__nav-link">{% trans %}NPS{% endtrans %}</a>
      <a href="{{ path('jaraba_customer_success.retention_dashboard') }}" class="cs-retention__nav-link cs-retention__nav-link--active">{% trans %}Retention{% endtrans %}</a>
    </nav>
  </header>

  {# Stats cards #}
  <section class="cs-retention__stats">
    <div class="cs-retention__stat-card">
      <span class="cs-retention__stat-value" data-count="{{ stats.total_tenants }}">0</span>
      <span class="cs-retention__stat-label">{% trans %}Total Tenants{% endtrans %}</span>
    </div>
    <div class="cs-retention__stat-card cs-retention__stat-card--danger">
      <span class="cs-retention__stat-value" data-count="{{ stats.at_risk_tenants }}">0</span>
      <span class="cs-retention__stat-label">{% trans %}At Risk{% endtrans %}</span>
    </div>
    <div class="cs-retention__stat-card cs-retention__stat-card--success">
      <span class="cs-retention__stat-value" data-count="{{ stats.retention_rate }}">0</span>
      <span class="cs-retention__stat-label">{% trans %}Retention Rate %{% endtrans %}</span>
    </div>
    <div class="cs-retention__stat-card">
      <span class="cs-retention__stat-value" data-count="{{ stats.active_playbooks }}">0</span>
      <span class="cs-retention__stat-label">{% trans %}Active Playbooks{% endtrans %}</span>
    </div>
  </section>

  {# Seasonal heatmap #}
  {% include '@jaraba_customer_success/partials/_retention-calendar-heatmap.html.twig' with {
    heatmap_data: heatmap_data,
  } only %}

  {# Risk cards by vertical #}
  <section class="cs-retention__risk-section">
    <h2 class="cs-retention__section-title">{% trans %}Risk by Vertical{% endtrans %}</h2>
    <div class="cs-retention__risk-grid">
      {% for vertical_id, card in risk_cards %}
        {% include '@jaraba_customer_success/partials/_retention-risk-card.html.twig' with {
          card: card,
        } only %}
      {% endfor %}
    </div>
  </section>

  {# Playbook execution timeline #}
  {% include '@jaraba_customer_success/partials/_playbook-timeline.html.twig' with {
    executions: recent_executions,
  } only %}
</div>
```

#### `templates/partials/_retention-calendar-heatmap.html.twig`

```twig
{#
/**
 * @file
 * _retention-calendar-heatmap.html.twig - Heatmap estacional 12x5.
 *
 * VARIABLES:
 * - heatmap_data: array[vertical_id] => {label, months: array[month] => {risk_level, label, adjustment}}.
 */
#}

{% set month_names = [
  1: 'Ene'|t, 2: 'Feb'|t, 3: 'Mar'|t, 4: 'Abr'|t,
  5: 'May'|t, 6: 'Jun'|t, 7: 'Jul'|t, 8: 'Ago'|t,
  9: 'Sep'|t, 10: 'Oct'|t, 11: 'Nov'|t, 12: 'Dic'|t
] %}

<section class="cs-heatmap">
  <h2 class="cs-heatmap__title">{% trans %}Seasonal Risk Calendar{% endtrans %}</h2>

  <div class="cs-heatmap__grid" role="table" aria-label="{% trans %}Seasonal risk heatmap{% endtrans %}">
    {# Header row with month names #}
    <div class="cs-heatmap__row cs-heatmap__row--header" role="row">
      <div class="cs-heatmap__cell cs-heatmap__cell--label" role="columnheader">{% trans %}Vertical{% endtrans %}</div>
      {% for m in 1..12 %}
        <div class="cs-heatmap__cell cs-heatmap__cell--header" role="columnheader">{{ month_names[m] }}</div>
      {% endfor %}
    </div>

    {# Data rows per vertical #}
    {% for vertical_id, vertical in heatmap_data %}
      <div class="cs-heatmap__row" role="row">
        <div class="cs-heatmap__cell cs-heatmap__cell--label" role="rowheader">{{ vertical.label }}</div>
        {% for m in 1..12 %}
          {% set month_data = vertical.months[m] ?? {risk_level: 'medium', label: '', adjustment: 0} %}
          <div class="cs-heatmap__cell cs-heatmap__cell--{{ month_data.risk_level }}"
               role="cell"
               title="{{ month_data.label }} ({{ month_data.adjustment > 0 ? '+' : '' }}{{ (month_data.adjustment * 100)|round }}%)"
               data-month="{{ m }}"
               data-vertical="{{ vertical_id }}">
            <span class="cs-heatmap__indicator"></span>
          </div>
        {% endfor %}
      </div>
    {% endfor %}
  </div>

  {# Legend #}
  <div class="cs-heatmap__legend">
    <span class="cs-heatmap__legend-item">
      <span class="cs-heatmap__legend-dot cs-heatmap__legend-dot--low"></span>
      {% trans %}Low Risk{% endtrans %}
    </span>
    <span class="cs-heatmap__legend-item">
      <span class="cs-heatmap__legend-dot cs-heatmap__legend-dot--medium"></span>
      {% trans %}Medium Risk{% endtrans %}
    </span>
    <span class="cs-heatmap__legend-item">
      <span class="cs-heatmap__legend-dot cs-heatmap__legend-dot--high"></span>
      {% trans %}High Risk{% endtrans %}
    </span>
  </div>
</section>
```

#### `templates/partials/_retention-risk-card.html.twig`

```twig
{#
/**
 * @file
 * _retention-risk-card.html.twig - Card de riesgo por vertical.
 *
 * VARIABLES:
 * - card: {label, vertical_id, max_inactivity_days, signals_count,
 *          critical_features, at_risk_count, total_count, top_signals}.
 */
#}

<article class="cs-risk-card">
  <header class="cs-risk-card__header">
    <h3 class="cs-risk-card__title">{{ card.label }}</h3>
    <span class="cs-risk-card__badge">{{ card.vertical_id }}</span>
  </header>

  <div class="cs-risk-card__stats">
    <div class="cs-risk-card__stat">
      <span class="cs-risk-card__stat-value">{{ card.at_risk_count }}/{{ card.total_count }}</span>
      <span class="cs-risk-card__stat-label">{% trans %}At Risk{% endtrans %}</span>
    </div>
    <div class="cs-risk-card__stat">
      <span class="cs-risk-card__stat-value">{{ card.max_inactivity_days }}d</span>
      <span class="cs-risk-card__stat-label">{% trans %}Max Inactivity{% endtrans %}</span>
    </div>
    <div class="cs-risk-card__stat">
      <span class="cs-risk-card__stat-value">{{ card.signals_count }}</span>
      <span class="cs-risk-card__stat-label">{% trans %}Signals{% endtrans %}</span>
    </div>
  </div>

  {% if card.top_signals|length > 0 %}
    <div class="cs-risk-card__signals">
      <h4 class="cs-risk-card__signals-title">{% trans %}Top Signals{% endtrans %}</h4>
      <ul class="cs-risk-card__signal-list">
        {% for signal in card.top_signals %}
          <li class="cs-risk-card__signal-item">
            <span class="cs-risk-card__signal-weight">{{ (signal.weight * 100)|round }}%</span>
            <span class="cs-risk-card__signal-desc">{{ signal.description }}</span>
          </li>
        {% endfor %}
      </ul>
    </div>
  {% endif %}

  <footer class="cs-risk-card__footer">
    <a href="{{ path('jaraba_customer_success.api.retention.profile_detail', {vertical_id: card.vertical_id}) }}"
       class="cs-risk-card__link">
      {% trans %}View Detail{% endtrans %}
    </a>
  </footer>
</article>
```

#### `templates/partials/_playbook-timeline.html.twig`

```twig
{#
/**
 * @file
 * _playbook-timeline.html.twig - Timeline de ejecuciones de playbooks.
 *
 * VARIABLES:
 * - executions: array [{playbook_name, tenant_name, current_step, total_steps, status, started_at}, ...].
 */
#}

<section class="cs-pb-timeline">
  <h2 class="cs-pb-timeline__title">{% trans %}Recent Playbook Executions{% endtrans %}</h2>

  {% if executions|length > 0 %}
    <div class="cs-pb-timeline__list">
      {% for execution in executions %}
        <div class="cs-pb-timeline__item cs-pb-timeline__item--{{ execution.status|default('pending') }}">
          <div class="cs-pb-timeline__marker">
            <span class="cs-pb-timeline__dot cs-pb-timeline__dot--{{ execution.status|default('pending') }}"></span>
            <span class="cs-pb-timeline__line"></span>
          </div>

          <div class="cs-pb-timeline__content">
            <div class="cs-pb-timeline__header">
              <span class="cs-pb-timeline__playbook">{{ execution.playbook_name }}</span>
              <span class="cs-pb-timeline__status cs-pb-timeline__status--{{ execution.status }}">
                {{ execution.status|capitalize }}
              </span>
            </div>
            <span class="cs-pb-timeline__tenant">{{ execution.tenant_name }}</span>
            <div class="cs-pb-timeline__progress">
              <span class="cs-pb-timeline__step">
                {% trans %}Step {{ execution.current_step }} of {{ execution.total_steps }}{% endtrans %}
              </span>
              {% if execution.started_at %}
                <span class="cs-pb-timeline__time">{{ execution.started_at|date('d M Y H:i') }}</span>
              {% endif %}
            </div>
          </div>
        </div>
      {% endfor %}
    </div>
  {% else %}
    <div class="cs-pb-timeline__empty">
      <p>{% trans %}No recent playbook executions. Executions will appear as playbooks are triggered.{% endtrans %}</p>
    </div>
  {% endif %}
</section>
```

### 11.18 JavaScript

#### `js/retention-dashboard.js`

```javascript
/**
 * @file
 * Retention Dashboard - Interactividad del dashboard de retencion verticalizada.
 *
 * PROPOSITO:
 * - Animacion de contadores de estadisticas.
 * - Highlight del mes actual en el heatmap.
 * - Tooltips en celdas del heatmap.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Stat counter animation.
   */
  Drupal.behaviors.csRetentionStatCounters = {
    attach: function (context) {
      once('cs-retention-counters', '.cs-retention__stat-value', context).forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target)) {
          return;
        }

        var duration = 800;
        var start = 0;
        var startTime = null;

        function animate(timestamp) {
          if (!startTime) {
            startTime = timestamp;
          }
          var progress = Math.min((timestamp - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.round(start + (target - start) * eased);

          if (progress < 1) {
            requestAnimationFrame(animate);
          }
        }

        requestAnimationFrame(animate);
      });
    }
  };

  /**
   * Heatmap current month highlight.
   */
  Drupal.behaviors.csRetentionHeatmapHighlight = {
    attach: function (context) {
      once('cs-heatmap-highlight', '.cs-heatmap__grid', context).forEach(function (grid) {
        var settings = drupalSettings.jarabaCs || {};
        var currentMonth = settings.currentMonth || new Date().getMonth() + 1;

        var cells = grid.querySelectorAll('.cs-heatmap__cell[data-month="' + currentMonth + '"]');
        cells.forEach(function (cell) {
          cell.classList.add('cs-heatmap__cell--current');
        });

        // Highlight header too.
        var headers = grid.querySelectorAll('.cs-heatmap__cell--header');
        if (headers[currentMonth - 1]) {
          headers[currentMonth - 1].classList.add('cs-heatmap__cell--current-header');
        }
      });
    }
  };

  /**
   * Heatmap cell tooltip on hover.
   */
  Drupal.behaviors.csRetentionHeatmapTooltips = {
    attach: function (context) {
      once('cs-heatmap-tooltips', '.cs-heatmap__cell[data-month]', context).forEach(function (cell) {
        var title = cell.getAttribute('title');
        if (!title) {
          return;
        }

        cell.addEventListener('mouseenter', function () {
          cell.setAttribute('aria-label', title);
        });
      });
    }
  };

})(Drupal, drupalSettings, once);
```

### 11.19 SCSS

#### `web/themes/custom/ecosistema_jaraba_theme/scss/_retention-playbooks.scss`

```scss
@use 'variables' as *;

// =============================================
// Retention Dashboard Styles
// =============================================
// BEM: cs-retention, cs-heatmap, cs-risk-card, cs-pb-timeline
// Variables: var(--ej-*) with SCSS fallbacks
// Mobile-first responsive design

// --- Main Layout ---
.cs-retention {
  max-width: 1200px;
  margin: 0 auto;
  padding: $ej-spacing-md;

  &__header {
    display: flex;
    flex-direction: column;
    gap: $ej-spacing-md;
    margin-bottom: $ej-spacing-xl;

    @include respond-to(md) {
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
    }
  }

  &__title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--ej-color-corporate, $ej-color-corporate);
    margin: 0;
  }

  &__subtitle {
    font-size: 0.875rem;
    color: var(--ej-color-text-muted, #64748b);
    margin: $ej-spacing-xs 0 0;
  }

  &__nav {
    display: flex;
    gap: $ej-spacing-xs;
    flex-wrap: wrap;
  }

  &__nav-link {
    padding: $ej-spacing-xs $ej-spacing-sm;
    border-radius: 6px;
    font-size: 0.875rem;
    color: var(--ej-color-corporate, $ej-color-corporate);
    text-decoration: none;
    transition: background-color 0.2s;

    &:hover {
      background-color: color-mix(in srgb, var(--ej-color-corporate, $ej-color-corporate) 10%, transparent);
    }

    &--active {
      background-color: var(--ej-color-corporate, $ej-color-corporate);
      color: #fff;
    }
  }

  // --- Stats ---
  &__stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: $ej-spacing-md;
    margin-bottom: $ej-spacing-xl;

    @include respond-to(md) {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  &__stat-card {
    background: #fff;
    border-radius: 12px;
    padding: $ej-spacing-lg;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;

    &--danger {
      border-color: color-mix(in srgb, var(--ej-color-danger, $ej-color-danger) 30%, transparent);
    }

    &--success {
      border-color: color-mix(in srgb, var(--ej-color-success, $ej-color-success) 30%, transparent);
    }
  }

  &__stat-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--ej-color-corporate, $ej-color-corporate);
  }

  &__stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--ej-color-text-muted, #64748b);
    margin-top: $ej-spacing-xs;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  // --- Risk Section ---
  &__risk-section {
    margin-bottom: $ej-spacing-xl;
  }

  &__section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--ej-color-corporate, $ej-color-corporate);
    margin: 0 0 $ej-spacing-md;
  }

  &__risk-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: $ej-spacing-md;

    @include respond-to(md) {
      grid-template-columns: repeat(2, 1fr);
    }

    @include respond-to(lg) {
      grid-template-columns: repeat(3, 1fr);
    }
  }
}

// --- Heatmap ---
.cs-heatmap {
  background: #fff;
  border-radius: 12px;
  padding: $ej-spacing-lg;
  margin-bottom: $ej-spacing-xl;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow-x: auto;

  &__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--ej-color-corporate, $ej-color-corporate);
    margin: 0 0 $ej-spacing-md;
  }

  &__grid {
    display: grid;
    grid-template-columns: 140px repeat(12, 1fr);
    gap: 2px;
    min-width: 600px;
  }

  &__row {
    display: contents;

    &--header {
      font-weight: 600;
    }
  }

  &__cell {
    padding: $ej-spacing-xs;
    text-align: center;
    font-size: 0.75rem;
    border-radius: 4px;
    min-height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: default;
    transition: transform 0.15s, box-shadow 0.15s;

    &--label {
      text-align: left;
      justify-content: flex-start;
      font-weight: 500;
      color: var(--ej-color-corporate, $ej-color-corporate);
      background: transparent;
    }

    &--header {
      font-weight: 600;
      font-size: 0.7rem;
      color: var(--ej-color-text-muted, #64748b);
    }

    &--low {
      background-color: color-mix(in srgb, var(--ej-color-success, $ej-color-success) 25%, transparent);
    }

    &--medium {
      background-color: color-mix(in srgb, var(--ej-color-warning, $ej-color-warning) 30%, transparent);
    }

    &--high {
      background-color: color-mix(in srgb, var(--ej-color-danger, $ej-color-danger) 30%, transparent);
    }

    &--current {
      outline: 2px solid var(--ej-color-corporate, $ej-color-corporate);
      outline-offset: -1px;
      transform: scale(1.05);
      z-index: 1;
    }

    &--current-header {
      color: var(--ej-color-corporate, $ej-color-corporate);
      font-weight: 700;
    }

    &:hover:not(&--label):not(&--header) {
      transform: scale(1.1);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      z-index: 2;
    }
  }

  &__legend {
    display: flex;
    gap: $ej-spacing-md;
    margin-top: $ej-spacing-md;
    justify-content: center;
  }

  &__legend-item {
    display: flex;
    align-items: center;
    gap: $ej-spacing-xs;
    font-size: 0.75rem;
    color: var(--ej-color-text-muted, #64748b);
  }

  &__legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;

    &--low {
      background-color: color-mix(in srgb, var(--ej-color-success, $ej-color-success) 40%, transparent);
    }

    &--medium {
      background-color: color-mix(in srgb, var(--ej-color-warning, $ej-color-warning) 45%, transparent);
    }

    &--high {
      background-color: color-mix(in srgb, var(--ej-color-danger, $ej-color-danger) 45%, transparent);
    }
  }
}

// --- Risk Card ---
.cs-risk-card {
  background: #fff;
  border-radius: 12px;
  padding: $ej-spacing-lg;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $ej-spacing-md;
  }

  &__title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ej-color-corporate, $ej-color-corporate);
    margin: 0;
  }

  &__badge {
    font-size: 0.65rem;
    padding: 2px 8px;
    border-radius: 10px;
    background-color: color-mix(in srgb, var(--ej-color-corporate, $ej-color-corporate) 10%, transparent);
    color: var(--ej-color-corporate, $ej-color-corporate);
    font-weight: 500;
  }

  &__stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: $ej-spacing-sm;
    margin-bottom: $ej-spacing-md;
  }

  &__stat {
    text-align: center;
  }

  &__stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ej-color-corporate, $ej-color-corporate);
  }

  &__stat-label {
    display: block;
    font-size: 0.65rem;
    color: var(--ej-color-text-muted, #64748b);
    text-transform: uppercase;
  }

  &__signals {
    flex: 1;
    margin-bottom: $ej-spacing-md;
  }

  &__signals-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ej-color-text-muted, #64748b);
    text-transform: uppercase;
    margin: 0 0 $ej-spacing-xs;
  }

  &__signal-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  &__signal-item {
    display: flex;
    align-items: flex-start;
    gap: $ej-spacing-xs;
    padding: $ej-spacing-xs 0;
    font-size: 0.8rem;
    border-bottom: 1px solid #f1f5f9;

    &:last-child {
      border-bottom: none;
    }
  }

  &__signal-weight {
    font-weight: 600;
    color: var(--ej-color-impulse, $ej-color-impulse);
    min-width: 36px;
  }

  &__signal-desc {
    color: #475569;
  }

  &__footer {
    margin-top: auto;
    padding-top: $ej-spacing-sm;
    border-top: 1px solid #f1f5f9;
  }

  &__link {
    font-size: 0.8rem;
    color: var(--ej-color-corporate, $ej-color-corporate);
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}

// --- Playbook Timeline ---
.cs-pb-timeline {
  background: #fff;
  border-radius: 12px;
  padding: $ej-spacing-lg;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);

  &__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--ej-color-corporate, $ej-color-corporate);
    margin: 0 0 $ej-spacing-md;
  }

  &__list {
    position: relative;
  }

  &__item {
    display: flex;
    gap: $ej-spacing-md;
    padding-bottom: $ej-spacing-md;

    &:last-child {
      padding-bottom: 0;

      .cs-pb-timeline__line {
        display: none;
      }
    }
  }

  &__marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 20px;
  }

  &__dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;

    &--running {
      background-color: var(--ej-color-success, $ej-color-success);
    }

    &--completed {
      background-color: var(--ej-color-innovation, $ej-color-innovation);
    }

    &--failed {
      background-color: var(--ej-color-danger, $ej-color-danger);
    }

    &--cancelled,
    &--paused,
    &--pending {
      background-color: #94a3b8;
    }
  }

  &__line {
    width: 2px;
    flex: 1;
    background-color: #e2e8f0;
    margin-top: 4px;
  }

  &__content {
    flex: 1;
    min-width: 0;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: $ej-spacing-sm;
  }

  &__playbook {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--ej-color-corporate, $ej-color-corporate);
  }

  &__status {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
    text-transform: uppercase;

    &--running {
      background-color: color-mix(in srgb, var(--ej-color-success, $ej-color-success) 15%, transparent);
      color: var(--ej-color-success, $ej-color-success);
    }

    &--completed {
      background-color: color-mix(in srgb, var(--ej-color-innovation, $ej-color-innovation) 15%, transparent);
      color: var(--ej-color-innovation, $ej-color-innovation);
    }

    &--failed {
      background-color: color-mix(in srgb, var(--ej-color-danger, $ej-color-danger) 15%, transparent);
      color: var(--ej-color-danger, $ej-color-danger);
    }

    &--cancelled,
    &--paused {
      background-color: #f1f5f9;
      color: #64748b;
    }
  }

  &__tenant {
    font-size: 0.8rem;
    color: #64748b;
  }

  &__progress {
    display: flex;
    align-items: center;
    gap: $ej-spacing-sm;
    margin-top: $ej-spacing-xs;
    font-size: 0.75rem;
    color: var(--ej-color-text-muted, #64748b);
  }

  &__empty {
    text-align: center;
    padding: $ej-spacing-xl;
    color: var(--ej-color-text-muted, #64748b);
  }
}
```

Addition to `main.scss`:

```scss
@use 'retention-playbooks';
```

---

## 12. Tabla de Correspondencia Specs - Implementación

| # | Spec (Doc 179) | Componente Implementado | Archivo Principal | Estado |
|---|----------------|------------------------|-------------------|--------|
| S1 | Perfil de retención por vertical | Entidad `VerticalRetentionProfile` | `src/Entity/VerticalRetentionProfile.php` | Skeleton completo |
| S2 | Calendario estacionalidad 12 meses | Campo `seasonality_calendar` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S3 | Señales de churn por vertical | Campo `churn_risk_signals` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S4 | Pesos health score verticales | Campo `health_score_weights` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S5 | Features críticas por vertical | Campo `critical_features` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S6 | Triggers re-engagement | Campo `reengagement_triggers` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S7 | Señales de upsell | Campo `upsell_signals` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S8 | Ofertas estacionales | Campo `seasonal_offers` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S9 | Patrón uso esperado mensual | Campo `expected_usage_pattern` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S10 | Max días inactividad por vertical | Campo `max_inactivity_days` (INT) | `VerticalRetentionProfile.php` | Skeleton completo |
| S11 | Overrides de playbook por vertical | Campo `playbook_overrides` (JSON) | `VerticalRetentionProfile.php` | Skeleton completo |
| S12 | Predicción churn estacional | Entidad `SeasonalChurnPrediction` | `src/Entity/SeasonalChurnPrediction.php` | Skeleton completo |
| S13 | Probabilidad base + ajuste | Campos `base_churn_probability`, `seasonal_adjustment`, `adjusted_probability` | `SeasonalChurnPrediction.php` | Skeleton completo |
| S14 | Urgencia de intervención | Campo `intervention_urgency` (ENUM) | `SeasonalChurnPrediction.php` | Skeleton completo |
| S15 | Motor evaluación verticalizada | Servicio `VerticalRetentionService` | `src/Service/VerticalRetentionService.php` | Skeleton completo |
| S16 | Predicción estacional mensual | Servicio `SeasonalChurnService` | `src/Service/SeasonalChurnService.php` | Skeleton completo |
| S17 | Perfil AgroConecta | Config YAML | `config/install/...agroconecta.yml` | Datos completos |
| S18 | Perfil ComercioConecta | Config YAML | `config/install/...comercioconecta.yml` | Datos completos |
| S19 | Perfil ServiciosConecta | Config YAML | `config/install/...serviciosconecta.yml` | Datos completos |
| S20 | Perfil Empleabilidad | Config YAML | `config/install/...empleabilidad.yml` | Datos completos |
| S21 | Perfil Emprendimiento | Config YAML | `config/install/...emprendimiento.yml` | Datos completos |
| S22 | 7 Endpoints API REST | Controller `RetentionApiController` | `src/Controller/RetentionApiController.php` | Skeleton completo |
| S23 | Dashboard FOC con heatmap | Controller `RetentionDashboardController` + templates | `src/Controller/RetentionDashboardController.php` | Skeleton completo |
| S24 | Cron diario 03:00 | QueueWorker `VerticalRetentionCronWorker` | `src/Plugin/QueueWorker/VerticalRetentionCronWorker.php` | Skeleton completo |
| S25 | ECA Flow predicciones mensuales | Cron condicional día 1 | `jaraba_customer_success.module` | Skeleton completo |
| S26 | Heatmap estacional visual | Template parcial | `templates/partials/_retention-calendar-heatmap.html.twig` | Skeleton completo |
| S27 | Risk cards por vertical | Template parcial | `templates/partials/_retention-risk-card.html.twig` | Skeleton completo |
| S28 | Timeline playbook executions | Template parcial | `templates/partials/_playbook-timeline.html.twig` | Skeleton completo |

---

## 13. Tabla de Cumplimiento de Directrices

| # | Directriz ID | Descripción | Cumplimiento | Donde |
|---|-------------|-------------|--------------|-------|
| D1 | CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` en PUT/POST API | SI | Routing YAML: rutas `profile_update` y `override_execution` |
| D2 | TWIG-XSS-001 | `\|safe_html` en contenido tenant, nunca `\|raw` | SI | Templates no usan `\|raw`. Datos de entidad renderizados via Twig autoescaping. |
| D3 | TM-CAST-001 | `(string) $this->t()` en render arrays | SI | ListBuilders y Controllers castan TranslatableMarkup |
| D4 | DRUPAL-ENTUP-001 | `installEntityType()` en update hook, no `applyUpdates()` | SI | `jaraba_customer_success_update_10001()` |
| D5 | i18n | `{% trans %}` Twig, `$this->t()` PHP, `Drupal.t()` JS | SI | Todos los templates, PHP y JS |
| D6 | SCSS | `var(--ej-*)`, `color-mix()`, `@use`, BEM, mobile-first | SI | `_retention-playbooks.scss` |
| D7 | Paleta colores | 7 colores oficiales Jaraba | SI | SCSS usa `$ej-color-*` y `var(--ej-color-*)` |
| D8 | Iconos | `jaraba_icon()` nunca emojis | SI | No se usan emojis en templates |
| D9 | Zero-region | `hook_theme_suggestions_page_alter()` + `clean_content` | SI | Module hooks y template principal |
| D10 | Field UI | `field_ui_base_route` en entidades | SI | `VerticalRetentionProfile` entity annotation |
| D11 | Views Data | `views_data = EntityViewsData` en handlers | SI | Ambas entidades |
| D12 | Admin nav | `/admin/structure/` y `/admin/content/` | SI | Entity links y menu links |
| D13 | Body classes | `hook_preprocess_html()` | SI | Module hooks |
| D14 | Parciales Twig | `{% include %}` reutilizables | SI | 3 parciales: heatmap, risk-card, timeline |
| D15 | Tenant isolation | `tenant_id` FK obligatorio | SI | `SeasonalChurnPrediction.tenant_id` entity_reference |
| D16 | Append-only | `SeasonalChurnPrediction` sin edición | SI | No tiene form handlers, access handler restringe update/delete |
| D17 | Constructor Promotion PHP 8.2+ | `protected` en constructor | SI | Todos los servicios, controllers, QueueWorker |
| D18 | `declare(strict_types=1)` | En todos los PHP | SI | Todos los archivos PHP |

---

## 14. Plan de Sprints

| Sprint | Duración | Entregables | Dependencias |
|--------|----------|-------------|--------------|
| **Sprint 1: Entidades** | 3 días | VerticalRetentionProfile (entity + interface + access + list builder + form), SeasonalChurnPrediction (entity + interface + access + list builder). Update hook 10001. Schema YAML. | Ninguna |
| **Sprint 2: Config** | 2 días | 5 YAML config files. Permissions. Links (menu, task, action). Routing para entity CRUD. | Sprint 1 |
| **Sprint 3: Servicios** | 4 días | VerticalRetentionService completo. SeasonalChurnService completo. Services YAML. Unit tests básicos. | Sprint 1 |
| **Sprint 4: API** | 3 días | RetentionApiController (7 endpoints). Routing API. CSRF validation. Integration tests. | Sprint 3 |
| **Sprint 5: Frontend** | 3 días | RetentionDashboardController. 4 templates Twig. JS. SCSS. Library YAML. Module hooks (theme, preprocess, cron). main.scss update. | Sprint 3 |
| **Sprint 6: Integración** | 2 días | QueueWorker. Cron integration. E2E testing. CSS compilation. Cache clear. Deploy staging. | Sprint 4, 5 |

**Total estimado**: 17 días de desarrollo (3-4 semanas con QA).

---

## 15. Checklist de Verificación E2E

### Entidades

- [ ] `VerticalRetentionProfile` se instala sin errores (`drush entup`)
- [ ] `SeasonalChurnPrediction` se instala sin errores
- [ ] Admin list en `/admin/content/retention-profiles` muestra perfiles
- [ ] Admin list en `/admin/content/seasonal-predictions` muestra predicciones
- [ ] CRUD completo de perfiles via formulario admin
- [ ] Validación: pesos suman 100, calendario tiene 12 entradas
- [ ] SeasonalChurnPrediction no tiene formularios de edición (append-only)
- [ ] 5 perfiles iniciales cargados tras ejecutar update hook

### Servicios

- [ ] `VerticalRetentionService::evaluateTenant()` retorna evaluación correcta
- [ ] Tenants sin perfil vertical reciben evaluación genérica (retrocompatibilidad)
- [ ] Re-ponderación de health score respeta pesos verticales
- [ ] Detección de inactividad estacional vs real funciona para Agro (enero = estacional)
- [ ] Señales de churn se evalúan contra `finops_usage_log`
- [ ] `SeasonalChurnService::predict()` genera predicción con ajuste correcto
- [ ] Predicciones son append-only (no se modifican tras creación)
- [ ] Batch evaluation procesa todos los tenants sin errores

### API

- [ ] GET `/api/v1/retention/profiles` retorna 5 perfiles
- [ ] GET `/api/v1/retention/profiles/agroconecta` retorna detalle completo
- [ ] PUT `/api/v1/retention/profiles/agroconecta` requiere CSRF token
- [ ] PUT rechaza pesos que no suman 100 (422)
- [ ] GET `/api/v1/retention/risk-assessment/{tenant_id}` retorna evaluación + predicciones
- [ ] GET `/api/v1/retention/seasonal-predictions` soporta filtros month/vertical_id
- [ ] GET `/api/v1/retention/playbook-executions` soporta filtro status
- [ ] POST `.../override` valida transiciones de estado (running->paused ok, completed->running 409)

### Frontend

- [ ] Ruta `/customer-success/retention` renderiza correctamente
- [ ] Zero-region: usa template `page--customer-success.html.twig`
- [ ] Heatmap muestra grid 12x5 con colores correctos
- [ ] Mes actual resaltado en heatmap
- [ ] Tooltips muestran info del mes al hover
- [ ] Risk cards muestran datos de cada vertical
- [ ] Timeline muestra ejecuciones recientes con status badges
- [ ] Stat counters se animan al cargar
- [ ] Responsive: grid 2 cols en mobile, 4 cols en desktop

### Estilos

- [ ] SCSS compila sin errores (`npm run build`)
- [ ] Variables `var(--ej-*)` con fallbacks correctos
- [ ] `color-mix()` para variantes
- [ ] BEM consistente: `cs-retention__*`, `cs-heatmap__*`, `cs-risk-card__*`, `cs-pb-timeline__*`
- [ ] Mobile-first con breakpoints `respond-to(md)`, `respond-to(lg)`

### Seguridad

- [ ] CSRF token requerido en PUT y POST API
- [ ] Permisos verificados en cada ruta
- [ ] No hay `|raw` en templates
- [ ] Access handlers restringen operaciones correctamente
- [ ] Entity queries usan `accessCheck(TRUE)`

### Cron

- [ ] Queue items se crean en `hook_cron()`
- [ ] `vertical_evaluation` procesa batch de tenants
- [ ] `seasonal_predictions` solo se ejecuta el día 1 del mes
- [ ] Errores individuales no detienen el batch completo

### Cache

- [ ] Dashboard tiene `max-age: 300`
- [ ] Cache tags incluyen listas de entidades
- [ ] `drush cr` limpia correctamente

---

*Fin del documento de implementación. Versión 1.0 — 2026-02-20.*
