# 🏦 Centro de Operaciones Financieras (FOC)
## Especificación Técnica de Integración v1.0

**Fecha:** 2026-01-13  
**Versión:** 1.0.0  
**Estado:** En Desarrollo  
**Módulo:** `jaraba_foc`

---

## 📑 Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Decisiones de Arquitectura](#2-decisiones-de-arquitectura)
3. [Modelo de Triple Motor Económico](#3-modelo-de-triple-motor-económico)
4. [Arquitectura de Módulo](#4-arquitectura-de-módulo)
5. [Entidades Financieras](#5-entidades-financieras)
6. [Integración Stripe Connect](#6-integración-stripe-connect)
7. [Motor de Proyecciones](#7-motor-de-proyecciones)
8. [Sistema de Alertas ECA](#8-sistema-de-alertas-eca)
9. [Métricas SaaS 2.0](#9-métricas-saas-20)
10. [Plan de Implementación](#10-plan-de-implementación)

---

## 1. Resumen Ejecutivo

El Centro de Operaciones Financieras (FOC) transforma la Jaraba Impact Platform de un ecosistema digital a una **infraestructura de inteligencia de negocio de nivel empresarial**.

### Capacidades Clave

| Capacidad | Descripción |
|-----------|-------------|
| **Data Warehouse Operativo** | Drupal 11 como cerebro analítico FinOps |
| **Unit Economics** | Rentabilidad hasta nivel atómico: tenant + producto |
| **Analítica Prescriptiva** | No solo qué pasó, sino qué hacer |
| **SSOT** | Single Source of Truth centralizado |

---

## 2. Decisiones de Arquitectura

| Decisión | Opción | Justificación |
|----------|--------|---------------|
| **Módulo** | `jaraba_foc` separado | Modularidad, testing independiente |
| **Stripe Connect** | Standard | Control total sobre onboarding y KYC |
| **Motor ML** | API Externa (Claude/GPT) | Integración AI-First, mayor potencia |
| **Prioridad** | Entidades primero | Fundamento arquitectónico sólido |

---

## 3. Modelo de Triple Motor Económico

```
┌─────────────────────────────────────────────────────────────┐
│                  TRIPLE MOTOR ECONÓMICO                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│   │   INSTITUCIONAL │  │ MERCADO PRIVADO │  │  LICENCIAS  │ │
│   │      30%        │  │       40%       │  │     30%     │ │
│   ├─────────────────┤  ├─────────────────┤  ├─────────────┤ │
│   │ • Subvenciones  │  │ • Infoproductos │  │ • Franquicia│ │
│   │ • PERTE         │  │ • Club Jaraba   │  │ • Cuotas    │ │
│   │ • Kit Digital   │  │ • Mentorías     │  │ • Royalties │ │
│   │ • ONGs          │  │ • Marketplace   │  │ • Certific. │ │
│   │                 │  │                 │  │             │ │
│   │ Bolsas presupu- │  │ Alta frecuencia │  │ MRR         │ │
│   │ estarias        │  │ transaccional   │  │ predecible  │ │
│   └─────────────────┘  └─────────────────┘  └─────────────┘ │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Arquitectura de Módulo

```
web/modules/custom/jaraba_foc/
├── jaraba_foc.info.yml
├── jaraba_foc.module
├── jaraba_foc.services.yml
├── jaraba_foc.routing.yml
├── jaraba_foc.permissions.yml
├── config/
│   ├── install/
│   │   └── jaraba_foc.settings.yml
│   └── schema/
│       └── jaraba_foc.schema.yml
├── src/
│   ├── Entity/
│   │   ├── FinancialTransaction.php      # Libro mayor inmutable
│   │   ├── CostAllocation.php            # Reparto de costes
│   │   └── FocMetricSnapshot.php         # Snapshots diarios
│   ├── Service/
│   │   ├── StripeConnectService.php      # Destination Charges
│   │   ├── EtlService.php                # Extract-Transform-Load
│   │   ├── MetricsCalculatorService.php  # Cálculo de métricas
│   │   └── ForecastingService.php        # Proyecciones via API
│   ├── Controller/
│   │   └── FocDashboardController.php
│   ├── EventSubscriber/
│   │   └── StripeWebhookSubscriber.php
│   └── Form/
│       └── FocSettingsForm.php
├── templates/
│   └── foc-dashboard.html.twig
└── scss/
    └── _foc-dashboard.scss
```

### Dependencias

```yaml
# jaraba_foc.info.yml
dependencies:
  - drupal:commerce
  - drupal:commerce_payment
  - ecosistema_jaraba_core:ecosistema_jaraba_core
  - eca:eca
```

---

## 5. Entidades Financieras

### 5.1 `financial_transaction` (Inmutable)

> ⚠️ **CRÍTICO**: Entidad append-only. No se permiten ediciones ni eliminaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `uuid` | UUID | Identificador único |
| `amount` | Decimal(10,4) | Monto (precisión alta, NUNCA float) |
| `currency` | String (ISO 4217) | EUR, USD |
| `timestamp` | DateTime (UTC) | Fecha/hora exacta |
| `transaction_type` | Entity Reference | Taxonomía controlada |
| `source_system` | String | stripe_connect, activecampaign, manual |
| `external_id` | String | ID origen (evita duplicados) |
| `related_tenant` | Entity Reference | Referencia a Group/Tenant |
| `related_vertical` | Entity Reference | Taxonomía Business Verticals |
| `related_campaign` | Entity Reference | Atribución CAC (opcional) |

### 5.2 `cost_allocation`

Resuelve rentabilidad real en multi-tenancy:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `total_cost` | Decimal | Gasto global (ej: €1.000 hosting) |
| `allocation_rules` | Reference múltiple | Reglas por tenant/vertical |
| `drivers` | String | Métricas base (uso disco, usuarios) |
| `period` | Daterange | Período de aplicación |

### 5.3 `foc_metric_snapshot`

Snapshot diario para trending histórico:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `snapshot_date` | Date | Fecha del snapshot |
| `scope_type` | String | platform, vertical, tenant |
| `scope_id` | Integer | ID del scope (null si platform) |
| `mrr`, `arr`, `churn_rate`, `nrr`, `grr`, `cac`, `ltv` | Decimal | Valores calculados |
| `metadata` | JSON | Datos contextuales adicionales |

---

## 6. Integración Stripe Connect

### 6.1 Modelo: Standard Accounts

```
┌─────────────────────────────────────────────────────────────┐
│                 DESTINATION CHARGES                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   Cliente paga €100                                          │
│        │                                                     │
│        ▼                                                     │
│   ┌─────────────────────────────────────────────────────┐   │
│   │              STRIPE CONNECT                          │   │
│   │                                                      │   │
│   │  1. Retiene fees Stripe: €2.90 + €0.30 = €3.20      │   │
│   │  2. Application Fee (5%): €5.00 → Plataforma        │   │
│   │  3. Deposita al Vendedor: €91.80                     │   │
│   │                                                      │   │
│   └─────────────────────────────────────────────────────┘   │
│                                                              │
│   BENEFICIOS:                                                │
│   ✅ Plataforma NO es Merchant of Record                     │
│   ✅ Solo tributa por comisiones (€5), no GMV (€100)         │
│   ✅ Riesgo financiero mínimo                                │
│   ✅ Standard = Control total sobre onboarding               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 6.2 Webhooks Requeridos

| Evento | Acción |
|--------|--------|
| `payment_intent.succeeded` | Crear `financial_transaction` |
| `invoice.paid` | Registrar ingreso recurrente |
| `subscription.created/updated/deleted` | Actualizar MRR |
| `account.updated` | Sincronizar estado vendedor |

---

## 7. Motor de Proyecciones

### 7.1 Implementación via API Externa

```php
class ForecastingService {
    
    public function __construct(
        private MultiAiProviderService $aiProvider,
        private MetricsCalculatorService $metrics
    ) {}
    
    public function projectMRR(int $months = 6): array {
        $historicalData = $this->metrics->getHistoricalMRR(24);
        
        $prompt = $this->buildForecastPrompt($historicalData, $months);
        
        return $this->aiProvider->generate($prompt, [
            'response_format' => 'json',
            'model' => 'claude-3-sonnet',
        ]);
    }
    
    public function runScenario(string $type): array {
        // base, optimistic, pessimistic, custom
    }
}
```

### 7.2 Escenarios Disponibles

| Escenario | Variables |
|-----------|-----------|
| **Base Case** | Continuación tendencias actuales |
| **Optimistic** | Churn -20%, New sales +30%, Expansion +25% |
| **Pessimistic** | Churn +30%, New sales -20%, CAC +25% |
| **Custom** | Modelado ad-hoc por usuario |

---

## 8. Sistema de Alertas ECA

### 8.1 Matriz de Alertas

| Alerta | Trigger | Severidad | Acción |
|--------|---------|-----------|--------|
| Churn Spike | >5% mensual | 🔴 Crítica | Tarea CRM + Secuencia AC |
| LTV:CAC Comprimido | <3:1 | 🟡 Advertencia | Alerta dashboard |
| Gross Margin Drop | <70% | 🔴 Crítica | Auditar COGS |
| Grant Burn Rate | > tiempo | 🔴 Crítica | Congelar partidas |
| Noisy Neighbor | Tenant GM <20% | 🟡 Advertencia | Revisar contrato |
| Runway Warning | <12 meses | 🔴 Crítica | Iniciar fundraising |

### 8.2 Playbooks Automatizados

**Churn Prevention:**
1. Identificar tenants at-risk (Health Score < 60)
2. ECA trigger → Crear task en CRM
3. ActiveCampaign → Secuencia de nurturing
4. CS Outreach → Onboarding refresh
5. Retention Offer → Descuento temporal si apropiado
6. Track outcome → Mejorar modelo predictivo

---

## 9. Métricas SaaS 2.0

### 9.1 Salud y Crecimiento (North Star)

| Métrica | Fórmula | Benchmark 2025 |
|---------|---------|----------------|
| MRR | New + Expansion - Churned | 15-20% MoM early stage |
| ARR | MRR × 12 | YoY growth 27% |
| Gross Margin | (Revenue - COGS) / Revenue | 70-85% |
| ARPU | MRR / Clientes Activos | Tendencia creciente |
| Rule of 40 | Growth Rate + Profit Margin | ≥ 40% |

### 9.2 Retención

| Métrica | Fórmula | Benchmark |
|---------|---------|-----------|
| NRR | (Start + Expansion - Churn) / Start | >100% (ideal 110-120%) |
| GRR | (Start - Churn - Contraction) / Start | 85-95% |
| Logo Churn | Clientes perdidos / Total inicio | <5% anual |

### 9.3 Unit Economics

| Métrica | Fórmula | Benchmark |
|---------|---------|-----------|
| CAC | S&M Spend / New Customers | Segmentar por canal |
| LTV | (ARPU × Gross Margin) / Churn Rate | LTV:CAC ≥ 3:1 |
| CAC Payback | CAC / (ARPU × Gross Margin) | <12 meses |
| Magic Number | Net New ARR / S&M Spend | >0.75 eficiente |

---

## 10. Plan de Implementación

### Fase 1: Entidades Financieras (Semanas 1-4)

- [ ] Crear estructura módulo `jaraba_foc`
- [ ] Implementar `FinancialTransaction` (inmutable)
- [ ] Implementar `CostAllocation`
- [ ] Implementar `FocMetricSnapshot`
- [ ] ETL básico para importación manual

### Fase 2: Stripe Connect (Semanas 5-6)

- [ ] Configurar Stripe Connect Standard
- [ ] Implementar webhooks
- [ ] `StripeConnectService` con Destination Charges
- [ ] UI de onboarding para vendedores

### Fase 3: Motor de Proyecciones (Semanas 7-10)

- [ ] `ForecastingService` con integración AI
- [ ] Modelado de escenarios
- [ ] Dashboard de proyecciones

### Fase 4: Alertas ECA (Semanas 11-12)

- [ ] Configurar ECA module
- [ ] Implementar matriz de alertas
- [ ] Playbooks automatizados
- [ ] Integración ActiveCampaign

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-13 | 1.0.0 | Creación inicial del documento de integración FOC |

---

> **Documento de Referencia**: `docs/tecnicos/20260113d-FOC_Documento_Tecnico_Definitivo_v2_Claude.md`
