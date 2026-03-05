# Plan de Implementacion: Remediacion Completa Billing, Commerce y Fiscal — Clase Mundial

**Fecha de creacion:** 2026-03-05
**Ultima actualizacion:** 2026-03-05
**Autor:** Claude Opus 4.6 (Anthropic) — Consultor Senior SaaS / Arquitecto de Sistemas de Facturacion
**Version:** 1.0.0
**Categoria:** Plan de Implementacion Estrategico
**Codigo:** REM-BILLING-001
**Estado:** PLANIFICADO
**Esfuerzo estimado:** 101-125h (3 sprints, 20 acciones)
**Documentos fuente:** Auditoria Gaps Billing Commerce Fiscal v1 (2026-03-05), Doc 158 (Pricing Matrix), VERT-PRICING-001, VERT-ADDON-001, Plan Remediacion v2.1
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v110.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v63.0.0, `CLAUDE.md` v1.2.0
**Modulos afectados:** `jaraba_billing`, `jaraba_addons`, `jaraba_foc`, `jaraba_comercio_conecta`, `jaraba_commerce`, `ecosistema_jaraba_core`, `jaraba_page_builder`, `jaraba_ai_agents`, `jaraba_credential_stack`, `ecosistema_jaraba_theme`
**Rutas principales:** `/planes/{vertical_key}` (pricing pages), `/my-dashboard` (dashboard tenant), `/addons` (catalogo), `/admin/config/billing/*` (admin billing)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Principios rectores](#13-principios-rectores)
   - 1.4 [Metricas de impacto](#14-metricas-de-impacto)
   - 1.5 [Alcance y exclusiones](#15-alcance-y-exclusiones)
   - 1.6 [Filosofia "Sin Humo"](#16-filosofia-sin-humo)
   - 1.7 [Relacion con auditorias previas](#17-relacion-con-auditorias-previas)
2. [Diagnostico: Mapa de Gaps](#2-diagnostico-mapa-de-gaps)
   - 2.1 [Matriz "El Codigo Existe" vs "El Usuario Lo Experimenta"](#21-matriz-el-codigo-existe-vs-el-usuario-lo-experimenta)
   - 2.2 [Dependencias entre gaps](#22-dependencias-entre-gaps)
   - 2.3 [Riesgos de no remediar](#23-riesgos-de-no-remediar)
3. [Arquitectura de la Solucion](#3-arquitectura-de-la-solucion)
   - 3.1 [Flujo completo de facturacion end-to-end (estado objetivo)](#31-flujo-completo-de-facturacion-end-to-end)
   - 3.2 [Flujo de delegacion fiscal (VeriFactu, Facturae B2G, E-Factura B2B)](#32-flujo-de-delegacion-fiscal)
   - 3.3 [Modelo unificado de addons (consolidacion TenantAddon → AddonSubscription)](#33-modelo-unificado-de-addons)
   - 3.4 [Integracion Stripe bidireccional (local ↔ Stripe)](#34-integracion-stripe-bidireccional)
   - 3.5 [Feature gating extendido (AI, Credentials, Page Builder)](#35-feature-gating-extendido)
   - 3.6 [FOC como ledger financiero real (commerce → FinancialTransaction)](#36-foc-como-ledger-financiero-real)
   - 3.7 [IVA/Tax y compliance LSSI-CE en pricing pages](#37-iva-tax-y-compliance-lssi-ce)
   - 3.8 [Diagrama de entidades y relaciones (estado objetivo)](#38-diagrama-de-entidades-y-relaciones)
4. [Sprint P0 — Criticos: Bloquean Produccion (Semanas 1-2)](#4-sprint-p0--criticos-bloquean-produccion)
   - 4.1 [GAP-C01: Conectar Delegacion Fiscal al Ciclo de Facturacion](#41-gap-c01-conectar-delegacion-fiscal)
   - 4.2 [GAP-C02: Activar Dunning via Cron](#42-gap-c02-activar-dunning-via-cron)
   - 4.3 [GAP-C03: Sincronizar Suscripciones Locales con Stripe](#43-gap-c03-sincronizar-suscripciones-locales-con-stripe)
   - 4.4 [GAP-C04: Implementar Usage Metering Real hacia Stripe](#44-gap-c04-implementar-usage-metering-real)
5. [Sprint P1 — Altos: Integridad del Sistema (Semanas 3-5)](#5-sprint-p1--altos-integridad-del-sistema)
   - 5.1 [GAP-H01: Consolidar Sistema Dual de Addons](#51-gap-h01-consolidar-sistema-dual-de-addons)
   - 5.2 [GAP-H02 + GAP-H03: Unificar PlanValidator y TenantMeteringService](#52-gap-h02--gap-h03-unificar-servicios-duplicados)
   - 5.3 [GAP-H04: Conectar FOC con Commerce](#53-gap-h04-conectar-foc-con-commerce)
   - 5.4 [GAP-H05: Gate AI Endpoints con FeatureAccess y Token Limits](#54-gap-h05-gate-ai-endpoints)
   - 5.5 [GAP-H06: Gate Credenciales con Plan](#55-gap-h06-gate-credenciales-con-plan)
   - 5.6 [GAP-H07 + GAP-H08: Webhooks FOC Marketplace y Upgrade Triggers](#56-gap-h07--gap-h08-webhooks-foc-y-upgrade-triggers)
6. [Sprint P2 — Medios: Coherencia y Compliance (Semanas 6-9)](#6-sprint-p2--medios-coherencia-y-compliance)
   - 6.1 [GAP-M01: IVA/Tax en Pricing Pages + Compliance LSSI-CE](#61-gap-m01-iva-tax-en-pricing-pages)
   - 6.2 [GAP-M02: Comision Marketplace Configurable](#62-gap-m02-comision-marketplace-configurable)
   - 6.3 [GAP-M03: Wallet Schema Declarativo](#63-gap-m03-wallet-schema-declarativo)
   - 6.4 [GAP-M04: Deduplicacion de Webhooks Stripe](#64-gap-m04-deduplicacion-de-webhooks)
   - 6.5 [GAP-M05: Auto-creacion Customer Stripe en Onboarding](#65-gap-m05-auto-creacion-customer-stripe)
   - 6.6 [GAP-M06: Pricing Pages para Verticales Faltantes](#66-gap-m06-pricing-pages-verticales-faltantes)
   - 6.7 [GAP-M08: Upgrade Triggers en AI, Page Builder y Credentials](#67-gap-m08-upgrade-triggers-extendidos)
   - 6.8 [Revenue Metrics desde FOC](#68-revenue-metrics-desde-foc)
7. [Especificaciones Tecnicas Detalladas](#7-especificaciones-tecnicas-detalladas)
   - 7.1 [hook_cron() para jaraba_billing: Firma y logica](#71-hook-cron-para-jaraba_billing)
   - 7.2 [FiscalInvoiceDelegationService: Punto de conexion](#72-fiscalinvoicedelegationservice-punto-de-conexion)
   - 7.3 [StripeSubscriptionService: Metodos requeridos](#73-stripesubscriptionservice-metodos-requeridos)
   - 7.4 [UsageStripeSyncService: Implementacion real con Stripe Metered Billing API](#74-usagestripesyncservice-implementacion-real)
   - 7.5 [AddonSubscription como SSoT: Migracion de TenantAddon](#75-addonsubscription-como-ssot)
   - 7.6 [MarketplaceCommissionConfig: Nueva ConfigEntity](#76-marketplacecommissionconfig-nueva-configentity)
   - 7.7 [StripeWebhookEventLog: Tabla de deduplicacion](#77-stripewebhookeventlog-tabla-de-deduplicacion)
   - 7.8 [IVA/Tax: Templates Twig y variables inyectables](#78-iva-tax-templates-twig)
   - 7.9 [SCSS: Tokens y componentes para pricing pages](#79-scss-tokens-y-componentes)
   - 7.10 [JavaScript: Interacciones de billing y pricing toggle](#710-javascript-interacciones)
8. [Tabla de Archivos Creados/Modificados](#8-tabla-de-archivos)
9. [Tabla de Correspondencia con Especificaciones Tecnicas](#9-correspondencia-especificaciones)
10. [Tabla de Cumplimiento de Directrices](#10-cumplimiento-directrices)
11. [Verificacion RUNTIME-VERIFY-001](#11-verificacion-runtime)
12. [Plan de Testing](#12-plan-de-testing)
13. [Coherencia con Documentacion Tecnica Existente](#13-coherencia-documentacion)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Remediacion completa de **20 gaps** identificados en la auditoria exhaustiva de los sistemas de billing, commerce y fiscal de Jaraba Impact Platform. Los gaps se organizan en 3 niveles de severidad:

- **4 CRITICOS** (bloquean produccion): Delegacion fiscal no conectada, dunning sin cron, suscripciones locales sin Stripe, usage metering stubbed.
- **8 ALTOS** (afectan integridad): Sistema dual de addons, servicios duplicados (PlanValidator, TenantMeteringService), FOC desconectado, endpoints AI/Credentials sin gate.
- **8 MEDIOS** (degradan calidad): IVA ausente en pricing, comision hardcoded, wallet schema on-demand, webhooks sin dedup, customer Stripe no auto-creado, verticales sin pricing page, upgrade triggers sub-utilizados, revenue metrics desalineadas.

### 1.2 Por que se implementa

La auditoria de 2026-03-05 revelo una brecha sistematica entre **"el codigo existe"** y **"el usuario lo experimenta"**:

| Componente | Codigo | Runtime | Consecuencia |
|------------|--------|---------|-------------|
| VeriFactu fiscal | 3 modulos completos | Nunca invocados | **Incumplimiento RD 1007/2023** — riesgo sancionador |
| Dunning 6 pasos | Logica completa | Solo paso 0 se ejecuta | **Tenants morosos sin suspension** — perdida de revenue |
| Stripe sync | Metodos implementados | 5 flujos desconectados | **Cobros incorrectos** — cambio de plan local sin Stripe |
| Usage metering | 93 lineas de codigo | Solo logging | **Uso gratuito ilimitado** de API calls, storage, AI tokens |
| Feature gating | FeatureAccessService completo | 3 AI endpoints sin gate | **Consumo LLM sin control** — impacto en costes |

Cada gap representa no solo una funcionalidad incompleta, sino un **riesgo operacional, financiero o legal** que crece con cada nuevo tenant.

### 1.3 Principios rectores

1. **Compliance primero**: Los gaps fiscales (VeriFactu, LSSI-CE) se resuelven antes que cualquier mejora de producto. RD 1007/2023 establece obligacion de registro electronico de facturas.
2. **Revenue protection**: Los gaps que causan perdida de ingresos (Stripe sync, dunning, usage metering) se implementan inmediatamente despues de compliance.
3. **Single Source of Truth**: Donde hay sistemas duplicados (TenantAddon/AddonSubscription, PlanValidator x2, TenantMeteringService x2), se elige UN ganador y se migra.
4. **Backward-compatible**: Cada cambio mantiene las interfaces publicas existentes. Los consumidores internos se actualizan sin romper modulos no afectados.
5. **Observable**: Cada flujo nuevo incluye logging estructurado, metricas de exito/fallo, y alertas para estados anormales.
6. **Sin Humo**: Se aprovecha al maximo la infraestructura ya construida. No se reinventa, se conecta.

### 1.4 Metricas de impacto

```
+----------------------------------------------------------------------+
|               IMPACTO PROYECTADO POR SPRINT                          |
+----------------------------------------------------------------------+
|                                                                      |
| Sprint | Semanas | Gaps | Revenue Impact     | Risk Reduction       |
| -------+---------|------+--------------------|---------------------- |
| P0     |   1-2   |  5   | EUR 0 -> real sync | Fiscal: ALTO→BAJO    |
| P1     |   3-5   |  7   | +15-20% ARPU       | Integrity: ALTO→BAJO |
| P2     |   6-9   |  8   | +5-10% conversion  | Compliance: MEDIO→OK |
| -------+---------|------+--------------------|---------------------- |
| TOTAL  |   9     | 20   | Billing operativo  | Produccion-ready     |
|                                                                      |
+----------------------------------------------------------------------+
```

**Desglose de revenue recovery:**
- **Dunning activo**: Recuperacion de 15-30% de facturas fallidas (promedio SaaS: 20% via dunning automatico)
- **Stripe sync**: Eliminacion de discrepancias cobro/servicio que causan chargebacks y soporte manual
- **Usage metering**: Habilitacion de cobros por exceso de uso (AI tokens, API calls, storage)
- **Feature gating AI**: Ahorro de EUR 200-500/mes en costes LLM al limitar uso a planes que los incluyen

### 1.5 Alcance y exclusiones

**EN ALCANCE:**
- Conexion de FiscalInvoiceDelegationService al webhook invoice.paid
- Cron de dunning en jaraba_billing.module
- Sincronizacion bidireccional local↔Stripe para los 5 flujos identificados
- Implementacion real de UsageStripeSyncService con Stripe Metered Billing API
- Consolidacion TenantAddon → AddonSubscription (migracion + deprecacion)
- Unificacion PlanValidator y TenantMeteringService (eliminar duplicados)
- Conexion FOC ← commerce (FinancialTransaction en checkout)
- Feature gating para 3 AI endpoints + Credentials
- IVA/Tax en pricing pages con texto legal LSSI-CE
- MarketplaceCommissionConfig (ConfigEntity configurable)
- Webhook deduplication table
- Auto-creacion Stripe Customer en onboarding
- Pricing pages para andalucia_ei y jaraba_content_hub
- Upgrade triggers extendidos (AI, Page Builder, Credentials)
- Revenue metrics desde FOC

**FUERA DE ALCANCE (planes futuros):**
- Migracion a Stripe Tax API (requiere activacion en Stripe Dashboard)
- Multi-currency billing (GAP-CURRENCY del plan Gaps Clase Mundial)
- Proration automatica con Stripe Proration API (actualmente solo preview)
- Stripe Connect Enhanced Onboarding para vendors marketplace
- Dashboard visual de revenue con graficos interactivos (se usa RevenueMetricsService via API)

### 1.6 Filosofia "Sin Humo"

La infraestructura critica **YA EXISTE** en todos los casos. Este plan NO crea servicios nuevos de billing desde cero. Conecta tuberias que estan desconectadas:

| Lo que existe | Lo que falta |
|---------------|-------------|
| `FiscalInvoiceDelegationService` con logica real para VeriFactu/Facturae/E-Factura | Una linea en `BillingWebhookController` que llame a `processFinalizedInvoice()` |
| `DunningService` con 6 pasos completos | Una funcion `jaraba_billing_cron()` que llame a `processDunning()` |
| `StripeSubscriptionService::updateSubscription()` | 3 lineas en `changePlan()` y `cancelSubscription()` que invoquen al servicio |
| `UsageStripeSyncService` con estructura completa | La llamada real a `\Stripe\SubscriptionItem::createUsageRecord()` |
| `AddonSubscription` con subscribe/cancel/renew | Stripe Subscription Item creation en `subscribe()` |
| `FinancialTransaction` entity inmutable | Un hook en `CheckoutService` que cree el registro |

**El esfuerzo es de conexion, no de construccion.** Esto reduce drasticamente el riesgo y acelera la entrega.

### 1.7 Relacion con auditorias previas

Este plan es continuacion directa de:

- **Auditoria Gaps Billing Commerce Fiscal v1** (2026-03-05): Define los 20 gaps que este plan resuelve. Referencia: `docs/analisis/2026-03-05_Auditoria_Gaps_Billing_Commerce_Fiscal_v1.md`
- **Analisis de Gaps Clase Mundial 100%** (2026-03-03): Identifica billing al 90% con gaps operacionales. Este plan cierra el 10% restante. Referencia: `docs/analisis/2026-03-03_Gaps_Clase_Mundial_Analisis_Completo_v1.md`
- **Plan Verticalizacion Planes y Precios** (VERT-PRICING-001): Establece la arquitectura de pricing pages y ConfigEntities que este plan extiende con IVA/tax. Referencia: `docs/implementacion/2026-03-03_Plan_Implementacion_Verticalizacion_Planes_Precios_SaaS_Clase_Mundial_v1.md`
- **Plan Verticales Componibles** (VERT-ADDON-001): Define TenantVerticalService y VerticalAddonBillingService que este plan conecta con Stripe. Referencia: `docs/implementacion/2026-03-05_Plan_Implementacion_Verticales_Componibles_Addon_Marketplace_v1.md`
- **Doc 158** (Pricing Matrix): Fuente de verdad financiera para precios, tiers, y Stripe Price IDs. Referencia: `docs/tecnicos/20260119d-158_Platform_Vertical_Pricing_Matrix_v1_Claude.md`

---

## 2. Diagnostico: Mapa de Gaps

### 2.1 Matriz "El Codigo Existe" vs "El Usuario Lo Experimenta"

```
+--------------------------------------------------------------------------+
|                  ESTADO ACTUAL vs ESTADO OBJETIVO                        |
+--------------------------------------------------------------------------+
|                                                                          |
|  Componente              | Existe | Funciona | Objetivo      | Sprint   |
|  ------------------------+--------+----------+---------------+--------- |
|  VeriFactu records       |  SI    |  NO      | Auto-registro | P0       |
|  Facturae B2G (FACe)     |  SI    |  NO      | Auto-envio    | P0       |
|  E-Factura B2B           |  SI    |  NO      | Auto-envio    | P0       |
|  Dunning 6 pasos         |  SI    |  PARCIAL | Cron diario   | P0       |
|  Stripe plan sync        |  SI    |  NO      | Bidireccional | P0       |
|  Stripe addon sync       |  SI    |  NO      | Sub Items     | P1       |
|  Usage metering Stripe   |  SI    |  NO      | API real      | P0       |
|  Addon SSoT unificado    |  NO    |  NO      | 1 entidad     | P1       |
|  PlanValidator unico     |  NO    |  NO      | 1 servicio    | P1       |
|  TenantMetering unico    |  NO    |  NO      | 1 servicio    | P1       |
|  FOC ← commerce          |  NO    |  NO      | Auto-registro | P1       |
|  AI feature gating       |  PARCIAL| PARCIAL | Todos los EP  | P1       |
|  Credential plan gate    |  NO    |  NO      | Plan limits   | P1       |
|  FOC webhooks marketplace|  NO    |  NO      | 3 eventos     | P1       |
|  Upgrade triggers full   |  PARCIAL| PARCIAL | +5 puntos     | P2       |
|  IVA/Tax pricing pages   |  NO    |  NO      | LSSI-CE       | P2       |
|  Commission configurable |  NO    |  NO      | ConfigEntity  | P2       |
|  Wallet hook_schema()    |  NO    |  NO      | Declarativo   | P2       |
|  Webhook dedup           |  NO    |  NO      | Event log     | P2       |
|  Stripe Customer onboard |  PARCIAL| PARCIAL | Auto-crear    | P2       |
|  Pricing 3 verticales    |  NO    |  NO      | 3 rutas       | P2       |
|  Revenue FOC-sourced     |  NO    |  NO      | Metricas reales| P2      |
|                                                                          |
+--------------------------------------------------------------------------+
```

### 2.2 Dependencias entre gaps

```
  GAP-C01 (fiscal)  <-------- independiente --------> GAP-C02 (dunning)
       |                                                    |
       |                                                    v
       |                                            GAP-C03 (Stripe sync)
       |                                                    |
       v                                                    v
  GAP-M04 (webhook dedup)                          GAP-C04 (usage metering)
                                                            |
                                                            v
                                                   GAP-H01 (addon unif.)
                                                            |
                                    +----------+------------+----------+
                                    |          |                       |
                                    v          v                       v
                            GAP-H02/H03   GAP-H05/H06           GAP-H04 (FOC)
                           (PV/TM unif.)  (AI/Cred gate)              |
                                                                      v
                                                               GAP-M08 (revenue)
```

**Lectura:** P0 se puede ejecutar en paralelo (los 4 gaps son independientes). P1 requiere que GAP-C03 este completo para GAP-H01 (la consolidacion de addons necesita Stripe sync funcional). P2 depende de P1 para coherencia.

### 2.3 Riesgos de no remediar

| Gap | Riesgo si no se remedia | Impacto financiero | Impacto legal |
|-----|-------------------------|--------------------|--------------|
| C01 | Facturas no registradas en VeriFactu | Multas AEAT | **ALTO** — RD 1007/2023 |
| C02 | Morosos sin suspension → servicio gratis | EUR 500-2000/mes | Bajo |
| C03 | Cobros incorrectos → chargebacks | EUR 200-1000/mes + comisiones | Medio (PSD2) |
| C04 | Uso ilimitado sin cobro | EUR 300-800/mes en LLM/storage | Bajo |
| H01 | Feature gates inconsistentes | Soporte manual | Bajo |
| H04 | Ledger financiero incompleto | Auditoria imposible | Medio (fiscal) |
| M01 | Precios sin IVA | Reclamaciones consumidores | **ALTO** — LSSI-CE Art. 10 |

---

## 3. Arquitectura de la Solucion

### 3.1 Flujo completo de facturacion end-to-end (estado objetivo)

Una vez implementados todos los gaps P0, el flujo de facturacion sera:

```
TENANT                   PLATAFORMA                         STRIPE                   FISCAL
  |                         |                                  |                        |
  |-- Elige plan ---------->|                                  |                        |
  |                         |-- createSubscription() --------->|                        |
  |                         |<-------- subscription.created ---|                        |
  |                         |-- syncSubscription() local       |                        |
  |                         |                                  |                        |
  |                         |<-------- invoice.paid -----------|                        |
  |                         |-- syncInvoice() local            |                        |
  |                         |-- processFinalizedInvoice() -----|------> VeriFactu       |
  |                         |                                  |------> Facturae (B2G)  |
  |                         |                                  |------> E-Factura (B2B) |
  |                         |                                  |                        |
  |-- Cambia plan --------->|                                  |                        |
  |                         |-- changePlan() local             |                        |
  |                         |-- updateStripeSubscription() --->|                        |
  |                         |<-------- prorated invoice -------|                        |
  |                         |                                  |                        |
  |-- Cancela plan -------->|                                  |                        |
  |                         |-- cancelSubscription() local     |                        |
  |                         |-- cancelStripeSubscription() --->|                        |
  |                         |                                  |                        |
  |                         |<-------- invoice.payment_failed -|                        |
  |                         |-- startDunning() (paso 0)        |                        |
  |                         |                                  |                        |
  |                         |<== cron diario ==>               |                        |
  |                         |-- processDunning()               |                        |
  |                         |   paso 3: email recordatorio     |                        |
  |                         |   paso 7: restriccion features   |                        |
  |                         |   paso 14: suspension servicio   |                        |
  |                         |   paso 21: cancelacion           |                        |
  |                         |                                  |                        |
  |                         |<== cron diario ==>               |                        |
  |                         |-- syncUsageToStripe() real       |                        |
  |                         |-- createUsageRecord() ---------->|                        |
```

### 3.2 Flujo de delegacion fiscal (VeriFactu, Facturae B2G, E-Factura B2B)

El `FiscalInvoiceDelegationService` ya implementa la logica de decision. Solo falta invocarlo:

```php
// BillingWebhookController::handleInvoicePaid() — LINEA A ANADIR
$this->syncInvoice($invoiceData); // Ya existe
// >>> NUEVA LINEA <<<
$this->fiscalDelegation?->processFinalizedInvoice($invoiceEntity);
```

La logica interna del servicio decide automaticamente:

```
processFinalizedInvoice($invoice)
  |
  +-> SIEMPRE: VeriFactu::registerInvoice($invoice)
  |   - Genera registro XML segun RD 1007/2023
  |   - Firma con certificado digital del tenant
  |   - Envia a AEAT (o loggea en sandbox)
  |   - Almacena hash + timestamp en BillingInvoice
  |
  +-> SI buyerType == 'aapp': Facturae::submitToFACe($invoice)
  |   - Genera XML Facturae 3.2.2
  |   - Firma XAdES-EPES
  |   - Envia via SOAP a FACe
  |   - Almacena numero de registro FACe
  |
  +-> SI buyerType == 'empresa' && amount > 5000: EFactura::submit($invoice)
      - Genera XML UBL 2.1
      - Envia al SPFE (cuando este operativo)
      - Fallback: loggea para envio manual
```

**Inyeccion del servicio**: Se inyecta como `@?jaraba_billing.fiscal_invoice_delegation` (opcional, `@?`) en `BillingWebhookController`. Esto cumple OPTIONAL-CROSSMODULE-001 porque el servicio fiscal puede no estar instalado en entornos de desarrollo.

### 3.3 Modelo unificado de addons (consolidacion TenantAddon → AddonSubscription)

**Estado actual (problematico):**

```
jaraba_billing                    jaraba_addons
+-------------------+            +------------------------+
| TenantAddon       |            | AddonSubscription      |
| - addon_code STR  |            | - addon_id REF->Addon  |
| - tenant_id GROUP |            | - tenant_id TENANT     |
| - stripe_item_id  |            | - status               |
| - 9 codes fijos   |            | - billing_cycle        |
+-------------------+            +------------------------+
       |                                   |
  FeatureAccessService              TenantVerticalService
  (consulta TenantAddon)           (consulta AddonSubscription)
       |                                   |
  Features regulares               Solo verticales
```

**Estado objetivo (unificado):**

```
jaraba_addons (SSoT)
+-----------------------------------+
| AddonSubscription (UNICO)         |
| - addon_id REF -> Addon           |
| - tenant_id REF -> Tenant         |
| - status (active|cancelled|...)   |
| - billing_cycle (monthly|yearly)  |
| - stripe_subscription_item_id STR |  <-- NUEVO: migrado de TenantAddon
| - start_date / end_date           |
+-----------------------------------+
         |
    +----+----+
    |         |
  FeatureAccessService    TenantVerticalService
  (consulta AMBOS tipos)  (filtra type=vertical)
```

**Estrategia de migracion:**
1. Anadir campo `stripe_subscription_item_id` a `AddonSubscription` (hook_update)
2. Script de migracion: TenantAddon → AddonSubscription (mapeo addon_code → Addon entity)
3. Actualizar FeatureAccessService para consultar AddonSubscription en lugar de TenantAddon
4. Deprecar TenantAddon (no eliminar aun — mantener 1 release para rollback)
5. Actualizar todos los consumidores (BillingApiController, AddonApiController)

### 3.4 Integracion Stripe bidireccional (local ↔ Stripe)

Cada flujo local debe tener su contraparte Stripe:

| Accion local | Metodo Stripe | Parametros clave |
|-------------|--------------|------------------|
| `changePlan()` | `\Stripe\Subscription::update()` | `items[0].price` = nuevo Stripe Price ID |
| `cancelSubscription()` | `\Stripe\Subscription::update()` | `cancel_at_period_end: true` |
| `AddonSubscriptionService::subscribe()` | `\Stripe\SubscriptionItem::create()` | `subscription`, `price` |
| `AddonSubscriptionService::cancel()` | `\Stripe\SubscriptionItem::delete()` | `$itemId` |
| `syncUsageToStripe()` | `\Stripe\SubscriptionItem::createUsageRecord()` | `quantity`, `timestamp` |

**Patron de implementacion:**

```php
// TenantSubscriptionService::changePlan() — MODIFICACION
public function changePlan(int $tenantId, string $newPlanKey): bool {
  // 1. Actualizar local (ya existe)
  $this->updateLocalSubscription($tenantId, $newPlanKey);

  // 2. NUEVO: Sincronizar con Stripe
  $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
  $stripeSubId = $tenant->get('stripe_subscription_id')->value;
  if ($stripeSubId && $this->stripeSubscriptionService) {
    $newPriceId = $this->resolveStripePriceId($newPlanKey, $tenant);
    $this->stripeSubscriptionService->updateSubscription($stripeSubId, [
      'items' => [['id' => $this->getCurrentItemId($stripeSubId), 'price' => $newPriceId]],
      'proration_behavior' => 'create_prorations',
    ]);
  }

  return TRUE;
}
```

**Credenciales Stripe**: Toda comunicacion con Stripe usa `getenv('STRIPE_SECRET_KEY')` via `settings.secrets.php` (STRIPE-ENV-UNIFY-001). NUNCA desde config/sync.

### 3.5 Feature gating extendido (AI, Credentials, Page Builder)

Los 3 endpoints AI no gateados y el modulo de Credentials necesitan la misma infraestructura que ya usa el Copilot Stream:

```
Request a /api/v1/page-builder/seo-ai-suggest
  |
  +-> SeoSuggestionController::suggest()
       |
       +-> NUEVO: FeatureAccessService::canAccess($tenantId, 'ai_seo_tools')
       |   |
       |   +-> PlanValidator: Plan Professional/Enterprise incluye ai_seo_tools? SI
       |   +-> AddonSubscription: Tiene addon 'ai_toolkit'? SI (fallback)
       |   +-> Retorna TRUE/FALSE
       |
       +-> NUEVO: AIUsageLimitService::checkAndDecrementLimit($tenantId, 'seo_suggestion', 1)
       |   |
       |   +-> Verifica tokens disponibles para el periodo actual
       |   +-> Si excedido: retorna FALSE + trigger UpgradeTriggerService
       |
       +-> Si ambos TRUE: ejecutar llamada LLM
       +-> Si FALSE: retornar 403 con UpgradeTrigger payload
```

**Mapa de features a gates:**

| Endpoint | Feature key | Planes que lo incluyen | Addon alternativo |
|----------|------------|------------------------|-------------------|
| SEO AI Suggest | `ai_seo_tools` | Professional, Enterprise | `ai_toolkit` |
| AI Template Generator | `ai_template_gen` | Professional, Enterprise | `ai_toolkit` |
| AI Image Suggestions | `ai_image_suggest` | Enterprise | `ai_premium` |
| Credential Stacks | `credential_stacks` | Professional (3), Enterprise (ilim.) | N/A |

### 3.6 FOC como ledger financiero real (commerce → FinancialTransaction)

El Financial Operations Center (FOC) tiene una entity `FinancialTransaction` disenada como ledger inmutable. Actualmente esta vacia para transacciones de marketplace:

```
Flujo actual (roto):
  OrderRetail completado → PayoutRecord creado → [FIN]
  MRR calculado desde billing_invoice (SaaS solo, no marketplace)

Flujo objetivo:
  OrderRetail completado → CheckoutService::processCheckout()
    → PayoutRecord creado (vendor payout)
    → FinancialTransaction creada (ledger)  <-- NUEVO
       type: 'marketplace_sale'
       amount: total_order
       commission: platform_fee
       vendor_payout: total - commission
       metadata: { order_id, vendor_id, vertical }
    → RevenueMetricsService puede calcular desde FOC
```

**Implementacion:**

```php
// En CheckoutService::processCheckout() — DESPUES del PayoutRecord
if (\Drupal::hasService('jaraba_foc.financial_transaction_service')) {
  try {
    $foc = \Drupal::service('jaraba_foc.financial_transaction_service');
    $foc->createTransaction([
      'type' => 'marketplace_sale',
      'amount' => $order->getTotalPrice(),
      'commission_amount' => $platformFee,
      'vendor_payout' => $vendorAmount,
      'currency' => 'EUR',
      'reference_type' => 'order_retail',
      'reference_id' => $order->id(),
      'tenant_id' => $order->get('tenant_id')->target_id,
      'metadata' => json_encode([
        'vendor_id' => $vendorId,
        'vertical' => 'comercioconecta',
        'items_count' => count($order->getItems()),
      ]),
    ]);
  } catch (\Throwable $e) {
    $this->logger->error('FOC transaction creation failed: @msg', ['@msg' => $e->getMessage()]);
    // No bloquear la venta por fallo de FOC
  }
}
```

Se usa `\Drupal::hasService()` + try-catch para degradacion graceful (PRESAVE-RESILIENCE-001).

### 3.7 IVA/Tax y compliance LSSI-CE en pricing pages

Las pricing pages de todos los verticales deben cumplir con la normativa espanola de comercio electronico:

**LSSI-CE Art. 10 — Informacion obligatoria en ofertas comerciales:**
- Precio final con indicacion de impuestos
- Indicacion clara de si el IVA esta incluido o excluido
- Identificacion del prestador de servicios

**Implementacion en templates Twig:**

```twig
{# pricing-page.html.twig — Seccion de precios #}
<div class="ej-pricing__price-display">
  <span class="ej-pricing__price-amount">
    {{ price|number_format(2, ',', '.') }} &euro;
  </span>
  <span class="ej-pricing__price-period">/{% trans %}mes{% endtrans %}</span>
  <span class="ej-pricing__price-tax-note">
    {% trans %}IVA no incluido{% endtrans %}
  </span>
</div>

{# Footer legal LSSI-CE #}
<footer class="ej-pricing__legal-footer">
  <p class="ej-pricing__legal-text">
    {% trans %}Todos los precios son en euros (EUR) y no incluyen el IVA aplicable (21% en Espana peninsular). Al proceder con la suscripcion, se aplicara el IVA correspondiente segun tu ubicacion fiscal.{% endtrans %}
  </p>
  <p class="ej-pricing__legal-links">
    <a href="{{ path('ecosistema_jaraba_core.legal.terms') }}">{% trans %}Terminos de Uso{% endtrans %}</a>
    &middot;
    <a href="{{ path('ecosistema_jaraba_core.legal.privacy') }}">{% trans %}Politica de Privacidad{% endtrans %}</a>
  </p>
</footer>
```

**SCSS con tokens inyectables:**

```scss
.ej-pricing__price-tax-note {
  font-size: $ej-font-size-xs;
  color: var(--ej-text-muted, #{$ej-color-text-muted-fallback});
  font-weight: $ej-font-weight-medium;
}

.ej-pricing__legal-footer {
  margin-top: var(--ej-spacing-2xl, #{$ej-spacing-2xl});
  padding-top: var(--ej-spacing-lg, #{$ej-spacing-lg});
  border-top: 1px solid var(--ej-border-color, #{$ej-color-border-fallback});
  text-align: center;
}

.ej-pricing__legal-text {
  font-size: $ej-font-size-sm;
  color: var(--ej-text-muted, #{$ej-color-text-muted-fallback});
  line-height: 1.6;
  max-width: 720px;
  margin-inline: auto;
}
```

Todos los colores usan `var(--ej-*, fallback)` (CSS-VAR-ALL-COLORS-001). Los textos usan `{% trans %}` (textos traducibles). Los SCSS usan Dart Sass moderno con `@use` y `color-mix()`.

### 3.8 Diagrama de entidades y relaciones (estado objetivo)

```
+-------------------+     +-------------------+     +-------------------+
| Tenant            |     | AddonSubscription |     | Addon             |
| (ecosistema_core) |     | (jaraba_addons)   |     | (jaraba_addons)   |
+-------------------+     +-------------------+     +-------------------+
| id                |<-+--| tenant_id (REF)   |  +->| id                |
| vertical (REF)    |  |  | addon_id (REF) ---+--+  | addon_type        |
| plan_key          |  |  | status            |     | vertical_ref      |
| stripe_sub_id     |  |  | billing_cycle     |     | price_monthly     |
| stripe_cust_id    |  |  | stripe_item_id    |     | price_yearly      |
+-------------------+  |  | start_date        |     | features_included |
         |              |  | end_date          |     +-------------------+
         |              |  +-------------------+
         v              |
+-------------------+   |  +-------------------+     +-------------------+
| BillingInvoice    |   |  | BillingUsageRecord|     | FinancialTransact.|
| (jaraba_billing)  |   |  | (jaraba_billing)  |     | (jaraba_foc)      |
+-------------------+   |  +-------------------+     +-------------------+
| id                |   +--| tenant_id (REF)   |     | id                |
| tenant_id (REF)   |     | metric             |     | type              |
| stripe_invoice_id |     | quantity           |     | amount            |
| amount            |     | period_start       |     | commission_amount |
| status            |     | synced_to_stripe   |     | vendor_payout     |
| verifactu_hash    |     +-------------------+     | reference_type    |
| facturae_reg_num  |                                | reference_id      |
+-------------------+                                | tenant_id (REF)   |
                                                      +-------------------+
         +-------------------+
         | MarketplaceComm.  |     +--------------------+
         | Config (NUEVA)    |     | StripeWebhookEvent |
         +-------------------+     | Log (NUEVA tabla)  |
         | id                |     +--------------------+
         | vertical          |     | event_id (PK)      |
         | commission_pct    |     | event_type          |
         | min_commission    |     | processed_at        |
         | effective_date    |     | idempotency_key     |
         +-------------------+     +--------------------+
```

---

## 4. Sprint P0 — Criticos: Bloquean Produccion (Semanas 1-2)

### 4.1 GAP-C01: Conectar Delegacion Fiscal al Ciclo de Facturacion

**Descripcion extensa:**

El sistema fiscal de Jaraba Impact Platform consta de 3 modulos independientes que implementan los 3 regimenes fiscales espanoles de facturacion electronica: `jaraba_verifactu` (registro anti-fraude obligatorio desde enero 2026, RD 1007/2023), `jaraba_facturae` (facturas XML para Administraciones Publicas via FACe), y `jaraba_einvoice_b2b` (factura electronica B2B via SPFE cuando entre en vigor). Los 3 modulos estan completamente implementados, incluyendo generacion XML, firma digital, y envio SOAP en el caso de FACe.

El problema: `FiscalInvoiceDelegationService` (servicio orquestador que decide cual de los 3 modulos debe actuar segun el tipo de destinatario) **nunca es invocado** por el flujo de facturacion. Cuando Stripe envia el webhook `invoice.paid`, `BillingWebhookController::handleInvoicePaid()` sincroniza la factura localmente pero **no llama a la delegacion fiscal**. El resultado es que ninguna factura pagada se registra en VeriFactu, ninguna se envia a FACe, y ninguna se prepara para E-Factura.

**Logica de negocio:**

Segun RD 1007/2023 (Sistema VeriFactu), **toda factura emitida por un obligado tributario espanol debe registrarse en la sede electronica de la AEAT** en tiempo real o casi-real. El incumplimiento puede acarrear sanciones del 0.5% del importe de cada factura no declarada, con un minimo de EUR 1.000 por trimestre.

**Ficheros a modificar:**

| Fichero | Accion | Lineas |
|---------|--------|--------|
| `jaraba_billing/src/Controller/BillingWebhookController.php` | Inyectar `FiscalInvoiceDelegationService` como `@?` | Linea ~50 (constructor), ~142 (handleInvoicePaid) |
| `jaraba_billing/jaraba_billing.services.yml` | Anadir argumento `@?jaraba_billing.fiscal_invoice_delegation` al controller | Seccion controller |

**Implementacion detallada:**

En `BillingWebhookController`:

```php
// Constructor — anadir parametro opcional
public function __construct(
  // ... args existentes ...
  private readonly ?FiscalInvoiceDelegationServiceInterface $fiscalDelegation = NULL,
) {
  // ... asignaciones existentes ...
}

// handleInvoicePaid() — anadir DESPUES de syncInvoice()
private function handleInvoicePaid(array $invoiceData): void {
  $invoiceEntity = $this->syncInvoice($invoiceData); // Ya existe

  // NUEVA LINEA: Delegacion fiscal
  if ($invoiceEntity && $this->fiscalDelegation) {
    try {
      $this->fiscalDelegation->processFinalizedInvoice($invoiceEntity);
      $this->logger->info('Fiscal delegation completed for invoice @id', [
        '@id' => $invoiceEntity->id(),
      ]);
    } catch (\Throwable $e) {
      // Loggear pero NO bloquear el webhook — la factura ya esta pagada
      $this->logger->error('Fiscal delegation failed for invoice @id: @msg', [
        '@id' => $invoiceEntity->id(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }
}
```

**Directrices de aplicacion:**
- `OPTIONAL-CROSSMODULE-001`: Inyeccion con `@?` (el modulo fiscal puede no estar instalado)
- `PRESAVE-RESILIENCE-001`: try-catch(\Throwable) para no bloquear el webhook
- `LOGGER-INJECT-001`: Logging via `$this->logger` (ya inyectado como `@logger.channel.jaraba_billing`)
- `STRIPE-ENV-UNIFY-001`: Sin cambios — las credenciales Stripe ya se leen via `getenv()`
- `UPDATE-HOOK-CATCH-001`: No aplica (no hay cambio de schema)

**Testing:**

| Tipo | Fichero | Assertions |
|------|---------|------------|
| Unit | `tests/src/Unit/BillingWebhookControllerTest.php` | fiscal delegation invocado tras syncInvoice, graceful si fiscal=NULL, graceful si fiscal lanza exception |
| Kernel | `tests/src/Kernel/FiscalDelegationIntegrationTest.php` | Invoice entity con verifactu_hash poblado tras procesamiento |

**Verificacion RUNTIME-VERIFY-001:**
1. CSS compilado: N/A (no hay cambios SCSS)
2. Tablas DB: N/A (no hay cambio de schema)
3. Rutas accesibles: Webhook endpoint existente, sin cambios
4. data-* selectores: N/A (no hay cambios frontend)
5. drupalSettings: N/A

**Esfuerzo estimado:** 2h

---

### 4.2 GAP-C02: Activar Dunning via Cron

**Descripcion extensa:**

El `DunningService` implementa un proceso de cobro progresivo en 6 pasos que escala la presion sobre el tenant moroso: dia 0 (email amable), dia 3 (segundo email), dia 7 (restriccion de features premium), dia 10 (notificacion de suspension inminente), dia 14 (suspension del servicio), dia 21 (cancelacion definitiva). Cada paso tiene su email asociado, sus restricciones, y su logica de decision.

El problema: la **funcion `processDunning()` nunca se ejecuta** porque no existe `hook_cron()` en `jaraba_billing.module`. El paso 0 se ejecuta correctamente via webhook `invoice.payment_failed`, pero los pasos 1-5 requieren un cron diario que itere sobre las suscripciones en estado `dunning` y avance el paso segun los dias transcurridos.

**Logica de negocio del dunning:**

```
Dia 0:  invoice.payment_failed → startDunning()
        - Email: "Tu pago ha fallado, por favor actualiza tu metodo de pago"
        - Accion: Ninguna restriccion

Dia 3:  processDunning() via cron
        - Email: "Segundo intento fallido — actualiza tu metodo de pago"
        - Accion: Banner en dashboard del tenant

Dia 7:  processDunning() via cron
        - Email: "Funciones premium restringidas"
        - Accion: FeatureAccessService bloquea features no-starter

Dia 10: processDunning() via cron
        - Email: "Suspension inminente — 4 dias para regularizar"
        - Accion: Banner critico + notificacion admin

Dia 14: processDunning() via cron
        - Email: "Servicio suspendido — acceso solo lectura"
        - Accion: Tenant marcado suspended, solo vista

Dia 21: processDunning() via cron
        - Email: "Suscripcion cancelada — datos disponibles 30 dias"
        - Accion: Cancelacion local + Stripe
```

**Ficheros a modificar:**

| Fichero | Accion |
|---------|--------|
| `jaraba_billing/jaraba_billing.module` | Anadir `jaraba_billing_cron()` |

**Implementacion detallada:**

```php
/**
 * Implements hook_cron().
 *
 * Procesa el dunning diario para suscripciones con pagos fallidos.
 * Escala progresivamente: email → restriccion → suspension → cancelacion.
 */
function jaraba_billing_cron(): void {
  // Ejecutar solo una vez al dia (no cada ejecucion de cron).
  $lastRun = \Drupal::state()->get('jaraba_billing.dunning_last_run', 0);
  $now = \Drupal::time()->getRequestTime();

  // Si se ejecuto hace menos de 20 horas, saltar.
  if (($now - $lastRun) < 72000) {
    return;
  }

  if (\Drupal::hasService('jaraba_billing.dunning')) {
    try {
      /** @var \Drupal\jaraba_billing\Service\DunningService $dunning */
      $dunning = \Drupal::service('jaraba_billing.dunning');
      $processed = $dunning->processDunning();

      \Drupal::logger('jaraba_billing')->info(
        'Dunning cron: @count suscripciones procesadas.',
        ['@count' => $processed]
      );

      \Drupal::state()->set('jaraba_billing.dunning_last_run', $now);
    } catch (\Throwable $e) {
      \Drupal::logger('jaraba_billing')->error(
        'Dunning cron error: @msg',
        ['@msg' => $e->getMessage()]
      );
    }
  }
}
```

**Notas de implementacion:**
- Se usa `\Drupal::state()` para throttle (1 ejecucion/dia), no `\Drupal::config()` (state es efimero y no se exporta)
- Se usa `\Drupal::hasService()` por consistencia con el patron de hooks procedurales
- Se usa `\Drupal::service()` directamente en `.module` (permitido por la directriz: "services: inyeccion de dependencias SIEMPRE. `\Drupal::service()` solo en .module y hooks procedurales")
- `try-catch(\Throwable)` — UPDATE-HOOK-CATCH-001 se aplica tambien a cron (PHP 8.4 TypeError)

**Directrices de aplicacion:**
- Uso de `\Drupal::service()` en `.module`: Permitido por convencion del proyecto
- `PRESAVE-RESILIENCE-001`: Patron try-catch aplicado
- Cron Idempotency: El throttle con `\Drupal::state()` previene multiples ejecuciones

**Testing:**

| Tipo | Fichero | Assertions |
|------|---------|------------|
| Unit | `tests/src/Unit/DunningServiceTest.php` (existente) | Verificar que processDunning() escala pasos correctamente |
| Kernel | `tests/src/Kernel/DunningCronTest.php` (nuevo) | Verificar que hook_cron invoca processDunning, respeta throttle |

**Esfuerzo estimado:** 1h

---

### 4.3 GAP-C03: Sincronizar Suscripciones Locales con Stripe

**Descripcion extensa:**

Este gap cubre los **5 flujos** donde las entidades locales se modifican pero Stripe no se actualiza, causando una desincronizacion que resulta en cobros incorrectos. La correccion requiere anadir llamadas a `StripeSubscriptionService` en los puntos donde se modifica el estado local.

**Flujo 1: Cambio de plan (`changePlan()`)**

El tenant cambia de Professional a Enterprise desde el dashboard. `TenantSubscriptionService::changePlan()` actualiza el campo `subscription_plan` en la entidad Tenant local, pero no modifica la Stripe Subscription. El tenant tiene acceso Enterprise localmente pero sigue pagando Professional en Stripe.

```php
// TenantSubscriptionService::changePlan() — MODIFICACION
public function changePlan(int $tenantId, string $newPlanKey): bool {
  $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
  if (!$tenant) {
    return FALSE;
  }

  // 1. Actualizar local (ya existe)
  $oldPlan = $tenant->get('subscription_plan')->value;
  $tenant->set('subscription_plan', $newPlanKey);
  $tenant->save();

  // 2. NUEVO: Sincronizar con Stripe
  $stripeSubId = $tenant->get('stripe_subscription_id')->value;
  if ($stripeSubId && $this->stripeSubscriptionService) {
    try {
      $newPriceId = $this->resolvePriceId($newPlanKey, $tenant);
      if ($newPriceId) {
        $this->stripeSubscriptionService->updateSubscription($stripeSubId, [
          'items' => [[
            'id' => $this->getCurrentSubscriptionItemId($stripeSubId),
            'price' => $newPriceId,
          ]],
          'proration_behavior' => 'create_prorations',
        ]);
        $this->logger->info('Plan changed @old -> @new for tenant @tid, Stripe updated.', [
          '@old' => $oldPlan,
          '@new' => $newPlanKey,
          '@tid' => $tenantId,
        ]);
      }
    } catch (\Throwable $e) {
      // Revertir cambio local si Stripe falla
      $tenant->set('subscription_plan', $oldPlan);
      $tenant->save();
      $this->logger->error('Stripe plan change failed for tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  return TRUE;
}
```

**Patron critico: Revert on Stripe failure.** Si la llamada a Stripe falla (red, tarjeta invalida, Price ID inexistente), se revierte el cambio local para mantener consistencia. El tenant permanece en su plan anterior y recibe un error explicativo.

**Flujo 2: Cancelacion de plan (`cancelSubscription()`)**

```php
// TenantSubscriptionService::cancelSubscription() — MODIFICACION
public function cancelSubscription(int $tenantId): bool {
  $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
  if (!$tenant) {
    return FALSE;
  }

  // 1. NUEVO: Cancelar en Stripe (cancel_at_period_end = no cobrar mas)
  $stripeSubId = $tenant->get('stripe_subscription_id')->value;
  if ($stripeSubId && $this->stripeSubscriptionService) {
    try {
      $this->stripeSubscriptionService->cancelSubscription($stripeSubId, [
        'cancel_at_period_end' => TRUE, // Mantiene acceso hasta fin del periodo
      ]);
    } catch (\Throwable $e) {
      $this->logger->error('Stripe cancellation failed for tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      // Continuar con cancelacion local — Stripe se puede reconciliar manualmente
    }
  }

  // 2. Marcar local como cancelled (ya existe)
  $tenant->set('subscription_status', 'cancelled');
  $tenant->save();

  return TRUE;
}
```

**Flujo 3-5: Subscribe/Cancel Addon (ver Sprint P1, GAP-H01)**

La sincronizacion de addons con Stripe se implementa despues de la consolidacion de TenantAddon → AddonSubscription para no duplicar trabajo.

**Ficheros a modificar:**

| Fichero | Accion |
|---------|--------|
| `jaraba_billing/src/Service/TenantSubscriptionService.php` | Anadir llamadas Stripe en changePlan() y cancelSubscription() |
| `jaraba_billing/jaraba_billing.services.yml` | Anadir `@?jaraba_billing.stripe_subscription` como argumento |

**Metodo helper: `resolvePriceId()`**

```php
/**
 * Resuelve el Stripe Price ID para un plan + vertical.
 *
 * Usa PlanResolverService para resolver el tier normalizado,
 * luego busca el Stripe Price ID en SaasPlanTier ConfigEntity.
 */
private function resolvePriceId(string $planKey, $tenant): ?string {
  $vertical = $tenant->get('vertical')->entity?->getMachineName() ?? 'demo';
  $tier = $this->planResolver?->normalize($planKey) ?? $planKey;

  // Buscar SaasPlanTier con cascade vertical_tier → _default_tier
  $tierConfig = $this->planResolver?->getTierConfig($vertical, $tier);

  return $tierConfig?->get('stripe_price_monthly') ?? NULL;
}
```

**Directrices de aplicacion:**
- `STRIPE-ENV-UNIFY-001`: Credenciales via `getenv()` en `settings.secrets.php`
- `OPTIONAL-CROSSMODULE-001`: `@?` para StripeSubscriptionService
- `SERVICE-CALL-CONTRACT-001`: Firmas de updateSubscription() y cancelSubscription() verificadas contra la implementacion existente
- `TENANT-002`: Tenant resuelto via entityTypeManager, no queries ad-hoc
- `ACCESS-STRICT-001`: Comparaciones de tenant ID con `(int)` cast en ambos lados

**Testing:**

| Tipo | Fichero | Assertions |
|------|---------|------------|
| Unit | `tests/src/Unit/TenantSubscriptionServiceTest.php` | changePlan revierte si Stripe falla, cancelSubscription marca local incluso si Stripe falla |
| Unit | Mock de StripeSubscriptionService | Verifica parametros correctos pasados a updateSubscription() |

**Esfuerzo estimado:** 6h (changePlan 4h + cancelSubscription 2h)

---

### 4.4 GAP-C04: Implementar Usage Metering Real hacia Stripe

**Descripcion extensa:**

`UsageStripeSyncService::syncUsageToStripe()` es el metodo responsable de enviar los registros de uso (API calls, storage consumido, AI tokens utilizados, emails enviados) a Stripe para facturacion basada en uso. Actualmente, las 93 lineas del metodo hacen logging e incrementan un contador pero **nunca llaman a la API de Stripe**.

Stripe Metered Billing funciona asi:
1. El SaaS crea un Subscription con un Price de tipo `metered` (no recurrente fijo)
2. Periodicamente, el SaaS envia `Usage Records` con la cantidad consumida
3. Al final del periodo, Stripe calcula el total y genera la factura

**Implementacion detallada:**

```php
/**
 * Sincroniza registros de uso pendientes con Stripe Metered Billing.
 *
 * Lee BillingUsageRecord entities no sincronizadas, agrupa por metrica,
 * y envia Usage Records a Stripe via la API de Subscription Items.
 *
 * @return int Numero de registros sincronizados.
 */
public function syncUsageToStripe(): int {
  $syncedCount = 0;

  // 1. Obtener registros no sincronizados
  $query = $this->entityTypeManager->getStorage('billing_usage_record')->getQuery()
    ->accessCheck(FALSE)
    ->condition('synced_to_stripe', FALSE)
    ->condition('period_end', \Drupal::time()->getRequestTime(), '<=')
    ->sort('created', 'ASC')
    ->range(0, 100); // Batch de 100 para no sobrecargar

  $ids = $query->execute();
  if (empty($ids)) {
    return 0;
  }

  $records = $this->entityTypeManager->getStorage('billing_usage_record')
    ->loadMultiple($ids);

  // 2. Agrupar por tenant + metrica
  $grouped = [];
  foreach ($records as $record) {
    $tenantId = (int) $record->get('tenant_id')->target_id;
    $metric = $record->get('metric')->value;
    $key = "{$tenantId}_{$metric}";
    $grouped[$key]['tenant_id'] = $tenantId;
    $grouped[$key]['metric'] = $metric;
    $grouped[$key]['quantity'] = ($grouped[$key]['quantity'] ?? 0)
      + (int) $record->get('quantity')->value;
    $grouped[$key]['records'][] = $record;
  }

  // 3. Para cada grupo, enviar a Stripe
  foreach ($grouped as $group) {
    $tenantId = $group['tenant_id'];
    $metric = $group['metric'];
    $quantity = $group['quantity'];

    try {
      // Resolver Stripe Subscription Item ID para esta metrica
      $itemId = $this->resolveStripeSubscriptionItemId($tenantId, $metric);
      if (!$itemId) {
        $this->logger->warning(
          'No Stripe subscription item for tenant @tid metric @metric — skipping.',
          ['@tid' => $tenantId, '@metric' => $metric]
        );
        continue;
      }

      // Llamada REAL a Stripe API
      \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
      \Stripe\SubscriptionItem::createUsageRecord($itemId, [
        'quantity' => $quantity,
        'timestamp' => \Drupal::time()->getRequestTime(),
        'action' => 'increment', // Suma al total del periodo
      ]);

      // Marcar registros como sincronizados
      foreach ($group['records'] as $record) {
        $record->set('synced_to_stripe', TRUE);
        $record->set('stripe_synced_at', \Drupal::time()->getRequestTime());
        $record->save();
      }

      $syncedCount += count($group['records']);

      $this->logger->info(
        'Stripe usage synced: tenant @tid, metric @metric, qty @qty.',
        ['@tid' => $tenantId, '@metric' => $metric, '@qty' => $quantity]
      );

    } catch (\Stripe\Exception\ApiErrorException $e) {
      $this->logger->error(
        'Stripe usage sync failed for tenant @tid metric @metric: @msg',
        ['@tid' => $tenantId, '@metric' => $metric, '@msg' => $e->getMessage()]
      );
      // No marcar como sincronizado — se reintentara en el proximo cron
    } catch (\Throwable $e) {
      $this->logger->error(
        'Unexpected error syncing usage for tenant @tid: @msg',
        ['@tid' => $tenantId, '@msg' => $e->getMessage()]
      );
    }
  }

  return $syncedCount;
}
```

**Cron integration:**

Anadir al `jaraba_billing_cron()` del GAP-C02:

```php
// Dentro de jaraba_billing_cron() — DESPUES del bloque de dunning
if (\Drupal::hasService('jaraba_billing.usage_stripe_sync')) {
  try {
    $synced = \Drupal::service('jaraba_billing.usage_stripe_sync')->syncUsageToStripe();
    if ($synced > 0) {
      \Drupal::logger('jaraba_billing')->info(
        'Usage sync cron: @count registros sincronizados con Stripe.',
        ['@count' => $synced]
      );
    }
  } catch (\Throwable $e) {
    \Drupal::logger('jaraba_billing')->error(
      'Usage sync cron error: @msg',
      ['@msg' => $e->getMessage()]
    );
  }
}
```

**Campo nuevo en BillingUsageRecord:**

Si `synced_to_stripe` y `stripe_synced_at` no existen como campos base, anadir:

```php
// En BillingUsageRecord::baseFieldDefinitions()
$fields['synced_to_stripe'] = BaseFieldDefinition::create('boolean')
  ->setLabel(t('Sincronizado con Stripe'))
  ->setDefaultValue(FALSE);

$fields['stripe_synced_at'] = BaseFieldDefinition::create('timestamp')
  ->setLabel(t('Fecha sincronizacion Stripe'))
  ->setDefaultValue(0);
```

Y el correspondiente `hook_update_N()` (UPDATE-HOOK-REQUIRED-001):

```php
function jaraba_billing_update_N(): void {
  $manager = \Drupal::entityDefinitionUpdateManager();
  try {
    $manager->installFieldStorageDefinition(
      'synced_to_stripe',
      'billing_usage_record',
      'jaraba_billing',
      BaseFieldDefinition::create('boolean')
        ->setName('synced_to_stripe')
        ->setTargetEntityTypeId('billing_usage_record')
        ->setLabel(t('Sincronizado con Stripe'))
        ->setDefaultValue(FALSE)
    );
    $manager->installFieldStorageDefinition(
      'stripe_synced_at',
      'billing_usage_record',
      'jaraba_billing',
      BaseFieldDefinition::create('timestamp')
        ->setName('stripe_synced_at')
        ->setTargetEntityTypeId('billing_usage_record')
        ->setLabel(t('Fecha sincronizacion Stripe'))
        ->setDefaultValue(0)
    );
  } catch (\Throwable $e) {
    \Drupal::logger('jaraba_billing')->error('Update hook failed: @msg', ['@msg' => $e->getMessage()]);
  }
}
```

**Directrices:**
- `UPDATE-FIELD-DEF-001`: `setName()` y `setTargetEntityTypeId()` obligatorios en BaseFieldDefinition dentro de update hooks
- `UPDATE-HOOK-REQUIRED-001`: hook_update obligatorio para nuevos campos
- `UPDATE-HOOK-CATCH-001`: try-catch(\Throwable) en update hook
- `STRIPE-ENV-UNIFY-001`: `getenv('STRIPE_SECRET_KEY')`
- `TENANT-001`: Query filtra por tenant

**Esfuerzo estimado:** 4h

---

## 5. Sprint P1 — Altos: Integridad del Sistema (Semanas 3-5)

### 5.1 GAP-H01: Consolidar Sistema Dual de Addons

**Descripcion extensa:**

El sistema actual tiene dos entidades paralelas que representan "tenant tiene addon activo": `TenantAddon` en `jaraba_billing` y `AddonSubscription` en `jaraba_addons`. Esta dualidad viola el principio de Single Source of Truth y causa inconsistencias en feature gating.

**Decision arquitectonica: AddonSubscription gana.**

Justificacion:
- `AddonSubscription` tiene modelo de datos mas rico (entity reference a Addon, billing cycle, status machine)
- `AddonSubscription` soporta todos los tipos de addon (incluido `vertical`)
- `AddonSubscription` referencia `Tenant` entity (correcto segun TENANT-BRIDGE-001)
- `TenantAddon` referencia `Group` entity (inconsistente con el modelo Tenant)
- `TenantAddon` usa `addon_code` string hardcoded (fragil, no extensible)

**Plan de migracion en 5 pasos:**

**Paso 1: Anadir `stripe_subscription_item_id` a AddonSubscription**

```php
// En AddonSubscription::baseFieldDefinitions()
$fields['stripe_subscription_item_id'] = BaseFieldDefinition::create('string')
  ->setLabel(t('Stripe Subscription Item ID'))
  ->setDescription(t('ID del item en la suscripcion Stripe'))
  ->setSetting('max_length', 255)
  ->setDisplayConfigurable('view', TRUE);
```

**Paso 2: Script de migracion TenantAddon → AddonSubscription**

```php
// jaraba_addons_update_10003()
function jaraba_addons_update_10003(): string {
  $migrated = 0;

  // Cargar todos los TenantAddon activos
  $tenantAddons = \Drupal::entityTypeManager()
    ->getStorage('tenant_addon')
    ->loadByProperties(['status' => 'active']);

  foreach ($tenantAddons as $tenantAddon) {
    $addonCode = $tenantAddon->get('addon_code')->value;
    $groupId = $tenantAddon->get('tenant_id')->target_id;
    $stripeItemId = $tenantAddon->get('stripe_subscription_item_id')->value;

    // Resolver Group → Tenant via TenantBridgeService
    $tenantId = NULL;
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_bridge')) {
      $bridge = \Drupal::service('ecosistema_jaraba_core.tenant_bridge');
      $tenant = $bridge->getTenantForGroup($groupId);
      $tenantId = $tenant?->id();
    }
    if (!$tenantId) {
      continue; // Skip si no se puede resolver
    }

    // Buscar Addon entity correspondiente por machine_name
    $addons = \Drupal::entityTypeManager()
      ->getStorage('addon')
      ->loadByProperties(['machine_name' => $addonCode]);
    $addon = reset($addons);
    if (!$addon) {
      continue; // Skip si no existe Addon entity para este code
    }

    // Verificar que no existe ya la AddonSubscription
    $existing = \Drupal::entityTypeManager()
      ->getStorage('addon_subscription')
      ->loadByProperties([
        'addon_id' => $addon->id(),
        'tenant_id' => $tenantId,
        'status' => 'active',
      ]);
    if (!empty($existing)) {
      continue; // Ya migrado
    }

    // Crear AddonSubscription
    $sub = \Drupal::entityTypeManager()
      ->getStorage('addon_subscription')
      ->create([
        'addon_id' => $addon->id(),
        'tenant_id' => $tenantId,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'stripe_subscription_item_id' => $stripeItemId,
        'start_date' => $tenantAddon->get('created')->value,
      ]);
    $sub->save();
    $migrated++;
  }

  return "Migrated $migrated TenantAddon records to AddonSubscription.";
}
```

**Paso 3: Actualizar FeatureAccessService para consultar AddonSubscription**

El `FeatureAccessService` en `jaraba_billing` actualmente consulta `TenantAddon` con addon_code fijo. Se modifica para consultar `AddonSubscription` via el servicio existente:

```php
// FeatureAccessService::canAccess() — MODIFICACION
// ANTES: $this->hasTenantAddon($tenantId, $addonCode)
// DESPUES: $this->hasActiveAddonSubscription($tenantId, $featureKey)

private function hasActiveAddonSubscription(int $tenantId, string $featureKey): bool {
  // Buscar Addon con features_included que contenga esta feature
  if ($this->addonSubscriptionService) {
    $activeAddons = $this->addonSubscriptionService->getTenantSubscriptions($tenantId);
    foreach ($activeAddons as $sub) {
      $addon = $sub->get('addon_id')->entity;
      if (!$addon) {
        continue;
      }
      $features = json_decode($addon->get('features_included')->value ?? '[]', TRUE);
      if (in_array($featureKey, $features, TRUE)) {
        return TRUE;
      }
    }
  }
  return FALSE;
}
```

**Paso 4: Conectar AddonSubscriptionService con Stripe**

```php
// AddonSubscriptionService::subscribe() — MODIFICACION
public function subscribe(int $tenantId, int $addonId, string $cycle): AddonSubscription {
  // ... validaciones existentes ...

  $subscription = $this->entityTypeManager->getStorage('addon_subscription')
    ->create([
      'addon_id' => $addonId,
      'tenant_id' => $tenantId,
      'status' => 'active',
      'billing_cycle' => $cycle,
      'start_date' => \Drupal::time()->getRequestTime(),
    ]);

  // NUEVO: Crear Stripe Subscription Item
  $addon = $this->entityTypeManager->getStorage('addon')->load($addonId);
  $stripePriceId = $this->resolveAddonStripePriceId($addon, $cycle);

  if ($stripePriceId && $this->stripeSubscriptionService) {
    $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
    $stripeSubId = $tenant?->get('stripe_subscription_id')->value;

    if ($stripeSubId) {
      try {
        \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
        $item = \Stripe\SubscriptionItem::create([
          'subscription' => $stripeSubId,
          'price' => $stripePriceId,
        ]);
        $subscription->set('stripe_subscription_item_id', $item->id);
      } catch (\Stripe\Exception\ApiErrorException $e) {
        $this->logger->error('Stripe addon subscribe failed: @msg', [
          '@msg' => $e->getMessage(),
        ]);
        // Continuar sin Stripe — se reconcilia manualmente
      }
    }
  }

  $subscription->save();

  // Invalidar cache de verticales si es addon tipo vertical
  if ($addon && $addon->get('addon_type')->value === 'vertical') {
    $this->invalidateVerticalCache($tenantId);
  }

  return $subscription;
}
```

**Paso 5: Deprecar TenantAddon**

Marcar TenantAddon como deprecated en el codigo (no eliminar aun):

```php
/**
 * @deprecated in jaraba_billing:1.x and will be removed in jaraba_billing:2.x.
 *   Use AddonSubscription from jaraba_addons module instead.
 * @see \Drupal\jaraba_addons\Entity\AddonSubscription
 */
class TenantAddon extends ContentEntityBase {
```

**Directrices:**
- `TENANT-BRIDGE-001`: Migracion usa `TenantBridgeService` para resolver Group→Tenant
- `ENTITY-FK-001`: Cross-modulo (billing→addons) via integer, no entity_reference
- `OPTIONAL-CROSSMODULE-001`: AddonSubscriptionService inyectado con `@?`
- `SERVICE-CALL-CONTRACT-001`: Firmas verificadas

**Esfuerzo estimado:** 16h

---

### 5.2 GAP-H02 + GAP-H03: Unificar Servicios Duplicados

**Descripcion extensa:**

Existen dos copias de `PlanValidator` y dos copias de `TenantMeteringService`. La estrategia es **mantener el de `jaraba_billing`** (modulo especializado en billing) y **deprecar el de `ecosistema_jaraba_core`** (modulo core que no deberia contener logica de billing).

**PlanValidator unificado:**

1. Auditar consumidores de ambas versiones con: `grep -rn 'plan_validator\|PlanValidator' web/modules/custom/`
2. Migrar todos los consumidores del core al de billing
3. En `ecosistema_jaraba_core.services.yml`: marcar como alias → `@jaraba_billing.plan_validator`
4. Verificar que la firma de ambos es compatible (SERVICE-CALL-CONTRACT-001)

**TenantMeteringService unificado:**

Misma estrategia: billing gana, core delega via alias.

```yaml
# ecosistema_jaraba_core.services.yml — DEPRECACION
services:
  # DEPRECATED: Use jaraba_billing.plan_validator
  ecosistema_jaraba_core.plan_validator:
    alias: jaraba_billing.plan_validator
    deprecated: 'The "%alias_id%" service is deprecated. Use "jaraba_billing.plan_validator" instead.'

  # DEPRECATED: Use jaraba_billing.tenant_metering
  ecosistema_jaraba_core.tenant_metering:
    alias: jaraba_billing.tenant_metering
    deprecated: 'The "%alias_id%" service is deprecated. Use "jaraba_billing.tenant_metering" instead.'
```

**Esfuerzo estimado:** 8h (4h + 4h)

---

### 5.3 GAP-H04: Conectar FOC con Commerce

**Descripcion detallada en seccion 3.6.** Implementar la creacion de `FinancialTransaction` en `CheckoutService::processCheckout()`.

**Esfuerzo estimado:** 8h

---

### 5.4 GAP-H05: Gate AI Endpoints con FeatureAccess y Token Limits

**Descripcion extensa:**

3 endpoints AI estan expuestos sin verificacion de plan:
- `SeoSuggestionController::suggest()` — genera sugerencias SEO via LLM
- `AiTemplateController::generate()` — genera templates de pagina via LLM
- `AiImageController::suggest()` — sugiere imagenes via LLM

Cada uno debe anadir 2 verificaciones antes de la llamada LLM:
1. `FeatureAccessService::canAccess($tenantId, $featureKey)` — tiene acceso por plan/addon?
2. `AIUsageLimitService::checkAndDecrementLimit($tenantId, $metric, 1)` — tiene tokens disponibles?

**Patron de implementacion (reutilizable):**

```php
// Trait o metodo helper en controller base
private function gateAiEndpoint(int $tenantId, string $featureKey, string $usageMetric): ?JsonResponse {
  // 1. Feature gate
  if ($this->featureAccessService && !$this->featureAccessService->canAccess($tenantId, $featureKey)) {
    $upgradePayload = $this->upgradeTriggerService?->buildTriggerPayload($tenantId, $featureKey);
    return new JsonResponse([
      'error' => 'feature_not_available',
      'message' => (string) t('Esta funcionalidad no esta disponible en tu plan actual.'),
      'upgrade' => $upgradePayload,
    ], 403);
  }

  // 2. Usage limit
  if ($this->aiUsageLimitService && !$this->aiUsageLimitService->checkAndDecrementLimit($tenantId, $usageMetric, 1)) {
    $upgradePayload = $this->upgradeTriggerService?->buildTriggerPayload($tenantId, 'ai_usage_limit');
    return new JsonResponse([
      'error' => 'usage_limit_exceeded',
      'message' => (string) t('Has alcanzado el limite de uso de IA para este periodo.'),
      'upgrade' => $upgradePayload,
    ], 429);
  }

  return NULL; // Acceso permitido
}
```

**Uso en cada controller:**

```php
// SeoSuggestionController::suggest()
$gate = $this->gateAiEndpoint($tenantId, 'ai_seo_tools', 'seo_suggestion');
if ($gate) {
  return $gate;
}
// ... continuar con la llamada LLM ...
```

**Textos traducibles:** Los mensajes de error usan `t()` para internacionalizacion.

**Esfuerzo estimado:** 6h

---

### 5.5 GAP-H06: Gate Credenciales con Plan

**Descripcion extensa:**

`CredentialStackAccessControlHandler::checkAccess()` solo verifica permisos Drupal. Debe anadir verificacion de limites por plan:

```php
// CredentialStackAccessControlHandler::checkAccess()
if ($operation === 'create') {
  // NUEVO: Verificar limite de stacks por plan
  if ($this->planValidator) {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    $currentCount = $this->countExistingStacks($tenantId);
    $limit = $this->planValidator->resolveEffectiveLimit($tenantId, 'credential_stacks');

    if ($limit > 0 && $currentCount >= $limit) {
      return AccessResult::forbidden('Credential stack limit reached for current plan.')
        ->addCacheContexts(['user'])
        ->addCacheTags(['tenant:' . $tenantId]);
    }
  }
}
```

**Directrices:**
- `TENANT-ISOLATION-ACCESS-001`: Verifica tenant match
- `ACCESS-RETURN-TYPE-001`: Retorna `AccessResultInterface`
- `ACCESS-STRICT-001`: Comparaciones con `(int)` cast

**Esfuerzo estimado:** 2h

---

### 5.6 GAP-H07 + GAP-H08: Webhooks FOC Marketplace y Upgrade Triggers

**Webhooks FOC (GAP-H07):**

Anadir 3 handlers en `StripeWebhookController` del modulo FOC:

```php
case 'account.application.authorized':
  $this->handleVendorConnected($event);
  break;
case 'account.application.deauthorized':
  $this->handleVendorDisconnected($event);
  break;
case 'customer.subscription.paused':
  $this->handleSubscriptionPaused($event);
  break;
```

**Upgrade Triggers extendidos (GAP-H08):**

Anadir invocaciones de `UpgradeTriggerService` en:
- 3 AI controllers (SeoSuggestion, AiTemplate, AiImage)
- 1 Page Builder (cuando se alcanza limite de paginas)
- 1 Credentials (cuando se alcanza limite de stacks)

**Esfuerzo estimado:** 8h (FOC 4h + Triggers 4h)

---

## 6. Sprint P2 — Medios: Coherencia y Compliance (Semanas 6-9)

### 6.1 GAP-M01: IVA/Tax en Pricing Pages + Compliance LSSI-CE

**Descripcion extensa:**

Todas las pricing pages deben cumplir LSSI-CE Art. 10 (obligacion de informar precios con impuestos). La implementacion consiste en:

1. **Template Twig**: Anadir indicacion "IVA no incluido" junto a cada precio.
2. **Footer legal**: Anadir texto legal con enlaces a politica de privacidad y terminos.
3. **SCSS**: Estilos para los nuevos elementos con tokens `var(--ej-*)`.
4. **Configuracion desde UI**: El texto "IVA no incluido" y el porcentaje deben ser configurables desde Apariencia > Ecosistema Jaraba Theme (TAB 14 "Paginas Legales") para que no haya que tocar codigo para actualizarlos.

**Template pricing-page.html.twig (parcial de precios):**

Los precios ya se renderizan en templates existentes. Se anade la nota de IVA como parcial reutilizable:

```twig
{# templates/partials/_price-tax-note.html.twig #}
{# Nota de IVA configurable desde Theme Settings UI #}
{% set tax_note = tax_note_text|default('IVA no incluido'|t) %}
{% set tax_rate = tax_rate_value|default('21') %}
<span class="ej-pricing__tax-note" aria-label="{% trans %}Informacion fiscal{% endtrans %}">
  {{ tax_note }}
  {% if show_tax_rate %}
    ({{ tax_rate }}%)
  {% endif %}
</span>
```

**SCSS:**

```scss
// scss/components/_pricing-tax.scss
@use '../variables' as *;

.ej-pricing__tax-note {
  display: inline-block;
  font-size: var(--ej-font-size-xs, 0.75rem);
  color: var(--ej-text-muted, #{$ej-color-text-muted-fallback});
  font-weight: var(--ej-font-weight-medium, 500);
  margin-top: var(--ej-spacing-xs, 0.25rem);
}

.ej-pricing__legal-footer {
  margin-top: var(--ej-spacing-2xl, 3rem);
  padding-top: var(--ej-spacing-lg, 1.5rem);
  border-top: 1px solid var(--ej-border-color, #{$ej-color-border-fallback});
  text-align: center;
}

.ej-pricing__legal-text {
  font-size: var(--ej-font-size-sm, 0.875rem);
  color: var(--ej-text-muted, #{$ej-color-text-muted-fallback});
  line-height: 1.6;
  max-width: 720px;
  margin-inline: auto;
}

.ej-pricing__legal-links {
  margin-top: var(--ej-spacing-md, 1rem);
  font-size: var(--ej-font-size-sm, 0.875rem);

  a {
    color: var(--ej-azul-corporativo, #{$ej-color-primary-fallback});
    text-decoration: none;
    transition: color 150ms cubic-bezier(0.4, 0, 0.2, 1);

    &:hover {
      color: color-mix(in srgb, var(--ej-azul-corporativo, #{$ej-color-primary-fallback}) 75%, black);
    }
  }
}
```

Todos los colores usan `var(--ej-*, fallback)` (CSS-VAR-ALL-COLORS-001). Se usa `color-mix()` en lugar de `darken()` (SCSS-COLORMIX-001). El fichero usa `@use` (no `@import`).

**Configuracion desde Theme Settings UI:**

En `ecosistema_jaraba_theme.theme` anadir settings al TAB existente de "Paginas Legales":

```php
// En ecosistema_jaraba_theme_form_system_theme_settings_alter()
$form['legal_settings']['tax_note_text'] = [
  '#type' => 'textfield',
  '#title' => t('Texto de nota IVA'),
  '#default_value' => theme_get_setting('tax_note_text') ?? 'IVA no incluido',
  '#description' => t('Texto que aparece junto a los precios en las paginas de pricing.'),
];

$form['legal_settings']['tax_rate_value'] = [
  '#type' => 'number',
  '#title' => t('Porcentaje IVA'),
  '#default_value' => theme_get_setting('tax_rate_value') ?? 21,
  '#min' => 0,
  '#max' => 100,
  '#description' => t('Porcentaje de IVA aplicable (21% para Espana peninsular).'),
];
```

**Inyeccion en preprocess:**

```php
function ecosistema_jaraba_theme_preprocess_pricing_page(array &$variables): void {
  $variables['tax_note_text'] = theme_get_setting('tax_note_text') ?? t('IVA no incluido');
  $variables['tax_rate_value'] = theme_get_setting('tax_rate_value') ?? 21;
  $variables['show_tax_rate'] = (bool) theme_get_setting('show_tax_rate_in_pricing');
}
```

Asi el contenido de IVA es **configurable desde la UI de Drupal** sin tocar codigo.

**Compilacion SCSS:** Tras editar el SCSS, compilar y verificar timestamp:

```bash
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/components/_pricing-tax.scss css/components/pricing-tax.css --style=compressed"
```

Verificar que `css/pricing-tax.css` tiene timestamp > `scss/_pricing-tax.scss` (SCSS-COMPILE-VERIFY-001).

**Esfuerzo estimado:** 8h

---

### 6.2 GAP-M02: Comision Marketplace Configurable

**Descripcion extensa:**

Actualmente la comision del marketplace esta hardcoded al 10% en `CheckoutService`. Se crea una ConfigEntity `MarketplaceCommissionConfig` que permite configurar:
- Comision por vertical (agroconecta 8%, comercioconecta 6%, serviciosconecta 10%)
- Comision minima en EUR
- Fecha de vigencia (para cambios futuros)

**ConfigEntity:**

```php
/**
 * @ConfigEntityType(
 *   id = "marketplace_commission_config",
 *   label = @Translation("Configuracion de Comision Marketplace"),
 *   config_prefix = "commission",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   admin_permission = "administer marketplace commissions",
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\MarketplaceCommissionConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\MarketplaceCommissionConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   links = {
 *     "collection" = "/admin/config/commerce/commissions",
 *     "add-form" = "/admin/config/commerce/commissions/add",
 *     "edit-form" = "/admin/config/commerce/commissions/{marketplace_commission_config}/edit",
 *     "delete-form" = "/admin/config/commerce/commissions/{marketplace_commission_config}/delete",
 *   },
 * )
 */
```

**Uso en CheckoutService:**

```php
// CheckoutService::processCheckout() — MODIFICACION
// ANTES: $commission_rate = 10.0;
// DESPUES:
$commission_rate = $this->getCommissionRate($vertical, $tenantId);

private function getCommissionRate(string $vertical, int $tenantId): float {
  // Buscar config especifica para este vertical
  $config = $this->entityTypeManager
    ->getStorage('marketplace_commission_config')
    ->load($vertical);

  if ($config) {
    return (float) $config->get('commission_pct');
  }

  // Fallback: config default
  $default = $this->entityTypeManager
    ->getStorage('marketplace_commission_config')
    ->load('_default');

  return $default ? (float) $default->get('commission_pct') : 10.0;
}
```

**Directrices:**
- `UPDATE-HOOK-REQUIRED-001`: hook_update para instalar la nueva ConfigEntity
- ConfigEntity con `AdminHtmlRouteProvider` para auto-generar rutas CRUD
- Navegacion admin en `/admin/config/commerce/commissions`
- Permisos via `admin_permission`

**Esfuerzo estimado:** 8h

---

### 6.3 GAP-M03: Wallet Schema Declarativo

Mover las tablas `billing_tenant_wallet` y `billing_wallet_ledger` de `ensureTablesExist()` (runtime) a `hook_schema()` (declarativo). Crear `hook_update_N()` para entornos que ya tienen las tablas (verificar existencia antes de crear).

**Esfuerzo estimado:** 2h

---

### 6.4 GAP-M04: Deduplicacion de Webhooks Stripe

**Descripcion extensa:**

Stripe puede reintentar webhooks hasta 3 veces si no recibe respuesta 200 en 10 segundos. Sin deduplicacion, esto causa emails duplicados, doble procesamiento de facturas, y posibles inconsistencias.

**Tabla de deduplicacion:**

```php
// hook_schema()
function jaraba_billing_schema(): array {
  $schema['stripe_webhook_event_log'] = [
    'description' => 'Log de eventos Stripe procesados para deduplicacion.',
    'fields' => [
      'event_id' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'description' => 'Stripe Event ID (evt_xxx)',
      ],
      'event_type' => [
        'type' => 'varchar',
        'length' => 128,
        'not null' => TRUE,
        'description' => 'Tipo de evento Stripe',
      ],
      'processed_at' => [
        'type' => 'int',
        'not null' => TRUE,
        'description' => 'Timestamp de procesamiento',
      ],
      'idempotency_key' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'Clave de idempotencia para prevenir duplicados',
      ],
    ],
    'primary key' => ['event_id'],
    'indexes' => [
      'event_type' => ['event_type'],
      'processed_at' => ['processed_at'],
    ],
  ];
  return $schema;
}
```

**Check de deduplicacion en BillingWebhookController:**

```php
private function isDuplicateEvent(string $eventId): bool {
  $exists = \Drupal::database()->select('stripe_webhook_event_log', 'l')
    ->condition('event_id', $eventId)
    ->countQuery()
    ->execute()
    ->fetchField();
  return $exists > 0;
}

private function logProcessedEvent(string $eventId, string $eventType): void {
  \Drupal::database()->insert('stripe_webhook_event_log')
    ->fields([
      'event_id' => $eventId,
      'event_type' => $eventType,
      'processed_at' => \Drupal::time()->getRequestTime(),
    ])
    ->execute();
}

// En handleWebhook():
if ($this->isDuplicateEvent($event->id)) {
  return new JsonResponse(['status' => 'already_processed'], 200);
}
// ... procesar evento ...
$this->logProcessedEvent($event->id, $event->type);
```

**Cron de limpieza:** Anadir al `jaraba_billing_cron()` limpieza de eventos > 30 dias:

```php
\Drupal::database()->delete('stripe_webhook_event_log')
  ->condition('processed_at', $now - (30 * 86400), '<')
  ->execute();
```

**Esfuerzo estimado:** 4h

---

### 6.5 GAP-M05: Auto-creacion Customer Stripe en Onboarding

Crear Stripe Customer automaticamente cuando se crea un Tenant, no cuando se intenta suscribir:

```php
// ecosistema_jaraba_core.module o jaraba_billing.module
function jaraba_billing_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() !== 'tenant') {
    return;
  }

  if (\Drupal::hasService('jaraba_billing.stripe_customer')) {
    try {
      $stripeCustomer = \Drupal::service('jaraba_billing.stripe_customer');
      $stripeCustomer->createOrGetCustomer($entity);
    } catch (\Throwable $e) {
      \Drupal::logger('jaraba_billing')->error(
        'Auto Stripe customer creation failed for tenant @tid: @msg',
        ['@tid' => $entity->id(), '@msg' => $e->getMessage()]
      );
    }
  }
}
```

**Esfuerzo estimado:** 2h

---

### 6.6 GAP-M06: Pricing Pages para Verticales Faltantes

Ampliar la regex de la ruta `/planes/{vertical_key}` para incluir `andalucia_ei` y `jaraba_content_hub`:

```yaml
# ecosistema_jaraba_core.routing.yml
ecosistema_jaraba_core.pricing.vertical:
  path: '/planes/{vertical_key}'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\PricingController::verticalPricing'
  requirements:
    _permission: 'access content'
    vertical_key: 'empleabilidad|emprendimiento|comercioconecta|agroconecta|jarabalex|serviciosconecta|formacion|andalucia_ei|jaraba_content_hub'
```

Crear SaasPlan entities para los 2 verticales faltantes (via `hook_update_N()` o seed script).

**Esfuerzo estimado:** 4h

---

### 6.7 GAP-M08: Upgrade Triggers Extendidos

Anadir `UpgradeTriggerService::trigger()` en los 5 puntos identificados:

| Punto | Trigger type | Condicion |
|-------|-------------|-----------|
| SeoSuggestionController | `ai_usage_limit` | Cuando checkAndDecrementLimit retorna FALSE |
| AiTemplateController | `ai_usage_limit` | Idem |
| AiImageController | `ai_usage_limit` | Idem |
| PageBuilderController | `page_limit_reached` | Cuando QuotaManager retorna limit_reached |
| CredentialStackAccess | `credential_limit` | Cuando count >= limit |

**Esfuerzo estimado:** 4h

---

### 6.8 Revenue Metrics desde FOC

Modificar `RevenueMetricsService` para calcular MRR/ARR incluyendo transacciones de marketplace desde FOC:

```php
public function calculateMrr(): array {
  // 1. MRR de suscripciones (existente — billing_invoice)
  $subscriptionMrr = $this->calculateSubscriptionMrr();

  // 2. NUEVO: MRR de comisiones marketplace (financial_transaction)
  $commissionMrr = 0;
  if (\Drupal::hasService('jaraba_foc.financial_transaction_service')) {
    $foc = \Drupal::service('jaraba_foc.financial_transaction_service');
    $commissionMrr = $foc->getMonthlyCommissionRevenue();
  }

  return [
    'subscription_mrr' => $subscriptionMrr,
    'commission_mrr' => $commissionMrr,
    'total_mrr' => $subscriptionMrr + $commissionMrr,
  ];
}
```

**Esfuerzo estimado:** 8h

---

## 7. Especificaciones Tecnicas Detalladas

### 7.1 hook_cron() para jaraba_billing: Firma y logica

El cron de billing ejecuta 3 tareas en secuencia:
1. **Dunning**: Procesa suscripciones morosas (GAP-C02)
2. **Usage sync**: Sincroniza metricas de uso con Stripe (GAP-C04)
3. **Event cleanup**: Limpia logs de webhooks > 30 dias (GAP-M04)

Cada tarea tiene su propio throttle via `\Drupal::state()` y su propio try-catch.

### 7.2 FiscalInvoiceDelegationService: Punto de conexion

El servicio ya existe en `jaraba_billing/src/Service/FiscalInvoiceDelegationService.php`. Su metodo publico `processFinalizedInvoice(BillingInvoice $invoice)` encapsula toda la logica de decision fiscal. Solo necesita ser invocado desde `BillingWebhookController::handleInvoicePaid()`.

### 7.3 StripeSubscriptionService: Metodos requeridos

El servicio ya expone:
- `updateSubscription(string $subscriptionId, array $params): \Stripe\Subscription`
- `cancelSubscription(string $subscriptionId, array $params): \Stripe\Subscription`
- `createSubscription(string $customerId, string $priceId, array $params): \Stripe\Subscription`

Todos los metodos usan `getenv('STRIPE_SECRET_KEY')` internamente.

### 7.4 UsageStripeSyncService: Implementacion real con Stripe Metered Billing API

Detallado en seccion 4.4. Usa `\Stripe\SubscriptionItem::createUsageRecord()` con `action: 'increment'`.

### 7.5 AddonSubscription como SSoT: Migracion de TenantAddon

Detallado en seccion 5.1. Migracion via `hook_update_10003()` con `TenantBridgeService` para resolver Group→Tenant.

### 7.6 MarketplaceCommissionConfig: Nueva ConfigEntity

Detallada en seccion 6.2. ConfigEntity con `AdminHtmlRouteProvider`, ID = vertical machine_name, cascade `{vertical}` → `_default`.

### 7.7 StripeWebhookEventLog: Tabla de deduplicacion

Detallada en seccion 6.4. Tabla en `hook_schema()` con PK = `event_id` (Stripe Event ID).

### 7.8 IVA/Tax: Templates Twig y variables inyectables

- Parcial `_price-tax-note.html.twig` con variables `tax_note_text` y `tax_rate_value`
- Variables configurables desde Theme Settings UI (TAB 14)
- Inyectadas via `hook_preprocess_pricing_page()`
- Textos con `{% trans %}` para i18n

### 7.9 SCSS: Tokens y componentes para pricing pages

- `_pricing-tax.scss` con tokens `var(--ej-*)` y fallbacks SCSS
- `color-mix()` para hover states (no `darken()`)
- `@use` (no `@import`)
- Compilacion: `npx sass` + verificacion timestamp

### 7.10 JavaScript: Interacciones de billing y pricing toggle

Los pricing toggles (mensual/anual) ya funcionan con Vanilla JS + `Drupal.behaviors`. No se requieren cambios de JS para los gaps de este plan. Los nuevos elementos (nota IVA, footer legal) son estaticos y no requieren interactividad.

---

## 8. Tabla de Archivos Creados/Modificados

| Sprint | Fichero | Accion | Modulo |
|--------|---------|--------|--------|
| P0 | `jaraba_billing/src/Controller/BillingWebhookController.php` | MODIFICAR: inyectar fiscal delegation | jaraba_billing |
| P0 | `jaraba_billing/jaraba_billing.services.yml` | MODIFICAR: anadir @? arg a controller | jaraba_billing |
| P0 | `jaraba_billing/jaraba_billing.module` | MODIFICAR: anadir hook_cron() | jaraba_billing |
| P0 | `jaraba_billing/src/Service/TenantSubscriptionService.php` | MODIFICAR: anadir Stripe sync en changePlan/cancel | jaraba_billing |
| P0 | `jaraba_billing/src/Service/UsageStripeSyncService.php` | MODIFICAR: implementar llamadas Stripe reales | jaraba_billing |
| P0 | `jaraba_billing/jaraba_billing.install` | MODIFICAR: hook_update para campos synced_to_stripe | jaraba_billing |
| P1 | `jaraba_addons/src/Entity/AddonSubscription.php` | MODIFICAR: anadir campo stripe_subscription_item_id | jaraba_addons |
| P1 | `jaraba_addons/jaraba_addons.install` | MODIFICAR: hook_update_10003 migracion TenantAddon | jaraba_addons |
| P1 | `jaraba_addons/src/Service/AddonSubscriptionService.php` | MODIFICAR: anadir Stripe Subscription Item creation | jaraba_addons |
| P1 | `jaraba_billing/src/Service/FeatureAccessService.php` | MODIFICAR: consultar AddonSubscription | jaraba_billing |
| P1 | `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | MODIFICAR: alias para PV y TM deprecados | ecosistema_jaraba_core |
| P1 | `jaraba_comercio_conecta/src/Service/CheckoutService.php` | MODIFICAR: crear FinancialTransaction | jaraba_comercio_conecta |
| P1 | `jaraba_page_builder/src/Controller/SeoSuggestionController.php` | MODIFICAR: anadir feature gate + usage limit | jaraba_page_builder |
| P1 | `jaraba_page_builder/src/Controller/AiTemplateController.php` | MODIFICAR: idem | jaraba_page_builder |
| P1 | `jaraba_page_builder/src/Controller/AiImageController.php` | MODIFICAR: idem | jaraba_page_builder |
| P1 | `jaraba_credential_stack/src/CredentialStackAccessControlHandler.php` | MODIFICAR: anadir plan limit | jaraba_credential_stack |
| P1 | `jaraba_foc/src/Controller/StripeWebhookController.php` | MODIFICAR: 3 nuevos event handlers | jaraba_foc |
| P2 | `ecosistema_jaraba_theme/templates/partials/_price-tax-note.html.twig` | CREAR | ecosistema_jaraba_theme |
| P2 | `ecosistema_jaraba_theme/scss/components/_pricing-tax.scss` | CREAR | ecosistema_jaraba_theme |
| P2 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | MODIFICAR: settings IVA + preprocess | ecosistema_jaraba_theme |
| P2 | `jaraba_comercio_conecta/src/Entity/MarketplaceCommissionConfig.php` | CREAR | jaraba_comercio_conecta |
| P2 | `jaraba_comercio_conecta/src/Form/MarketplaceCommissionConfigForm.php` | CREAR | jaraba_comercio_conecta |
| P2 | `jaraba_billing/jaraba_billing.install` | MODIFICAR: hook_schema + hook_update | jaraba_billing |
| P2 | `jaraba_billing/src/Controller/BillingWebhookController.php` | MODIFICAR: deduplicacion eventos | jaraba_billing |
| P2 | `jaraba_billing/jaraba_billing.module` | MODIFICAR: entity_insert para Stripe Customer | jaraba_billing |
| P2 | `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | MODIFICAR: regex vertical_key ampliada | ecosistema_jaraba_core |
| P2 | `jaraba_billing/src/Service/RevenueMetricsService.php` | MODIFICAR: incluir FOC data | jaraba_billing |

---

## 9. Tabla de Correspondencia con Especificaciones Tecnicas

| Gap ID | Especificacion | Doc Fuente | Seccion en este Plan |
|--------|---------------|------------|---------------------|
| GAP-C01 | RD 1007/2023 (VeriFactu), Facturae 3.2.2, UBL 2.1 | Doc fiscal, CLAUDE.md | 4.1 |
| GAP-C02 | Spec 134 §6 (Dunning 6 pasos) | Doc 134 | 4.2 |
| GAP-C03 | Stripe Subscription API, Spec 158 §5 (Billing Flow) | Doc 158, Stripe Docs | 4.3 |
| GAP-C04 | Stripe Metered Billing API, Spec 158 §4.3 (Usage-based) | Doc 158, Stripe Docs | 4.4 |
| GAP-H01 | TENANT-BRIDGE-001, ENTITY-FK-001 | CLAUDE.md, 00_DIRECTRICES | 5.1 |
| GAP-H02 | SERVICE-CALL-CONTRACT-001, CONTAINER-DEPS-002 | CLAUDE.md | 5.2 |
| GAP-H03 | SERVICE-CALL-CONTRACT-001 | CLAUDE.md | 5.2 |
| GAP-H04 | FOC Architecture, Financial Transaction immutability | Doc FOC | 5.3 |
| GAP-H05 | AI Usage Limits, Spec 158 §6.1 (Feature Access) | Doc 158, CLAUDE.md | 5.4 |
| GAP-H06 | PlanValidator::enforceCredentialStackLimit() | Doc credential_stack | 5.5 |
| GAP-H07 | Stripe Connect events, FOC webhook handling | Stripe Docs, Doc FOC | 5.6 |
| GAP-H08 | UpgradeTriggerService (39 types), Spec 158 §6.2 | Doc 158 | 5.6, 6.7 |
| GAP-M01 | LSSI-CE Art. 10, IVA 21% Espana | Legislacion espanola | 6.1 |
| GAP-M02 | Spec 158 §4.2 (Commissions), marketplace architecture | Doc 158 | 6.2 |
| GAP-M03 | hook_schema() best practices, Drupal DB API | Drupal API | 6.3 |
| GAP-M04 | Stripe webhook retries, idempotency | Stripe Docs | 6.4 |
| GAP-M05 | Stripe Customer lifecycle, onboarding flow | Stripe Docs, Doc onboarding | 6.5 |
| GAP-M06 | Vertical routing pattern, ROUTE-VAR-FIRST-SEGMENT-001 | CLAUDE.md | 6.6 |
| GAP-M08 | UpgradeTriggerService expansion, conversion optimization | Doc 158 | 6.7 |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz ID | Descripcion | Aplicacion en este Plan |
|--------------|------------|------------------------|
| TENANT-BRIDGE-001 | Usar TenantBridgeService para Tenant↔Group | Migracion TenantAddon usa bridge para resolver Group→Tenant (5.1) |
| TENANT-001 | Toda query filtra por tenant | Todas las queries de billing/addons incluyen tenant_id |
| TENANT-002 | Usar TenantContextService | Controllers resuelven tenant via servicio, no queries ad-hoc |
| TENANT-ISOLATION-ACCESS-001 | Access handlers verifican tenant match | CredentialStackAccessControlHandler (5.5) |
| OPTIONAL-CROSSMODULE-001 | Cross-modulo usa @? | FiscalDelegation, StripeSubscription, FOC inyectados con @? |
| CONTAINER-DEPS-002 | Sin dependencias circulares | Aliases deprecados (5.2) evitan ciclos |
| LOGGER-INJECT-001 | Logger inyectado correctamente | Todos los servicios modificados usan LoggerInterface directa |
| STRIPE-ENV-UNIFY-001 | Credenciales via getenv() | Todas las llamadas Stripe usan getenv('STRIPE_SECRET_KEY') |
| SERVICE-CALL-CONTRACT-001 | Firmas de metodo coinciden | Verificadas para updateSubscription, cancelSubscription, etc. |
| PRESAVE-RESILIENCE-001 | try-catch en hooks con servicios opcionales | Fiscal delegation, FOC creation, Stripe Customer creation |
| UPDATE-HOOK-REQUIRED-001 | hook_update para cambios de schema | Campos synced_to_stripe, stripe_subscription_item_id, CommissionConfig |
| UPDATE-FIELD-DEF-001 | setName + setTargetEntityTypeId en updates | Aplicado en todos los hook_update_N() |
| UPDATE-HOOK-CATCH-001 | try-catch(\Throwable) en hook_update | Aplicado en todos los hook_update_N() |
| CONTROLLER-READONLY-001 | No readonly en propiedades heredadas | Controllers no usan readonly para entityTypeManager |
| ACCESS-RETURN-TYPE-001 | checkAccess() retorna AccessResultInterface | CredentialStackAccessControlHandler (5.5) |
| ACCESS-STRICT-001 | Comparaciones con (int) cast + === | Todos los access checks de ownership |
| CSS-VAR-ALL-COLORS-001 | Todos los colores var(--ej-*, fallback) | Parcial _pricing-tax.scss usa tokens inyectables |
| SCSS-COLORMIX-001 | color-mix() en lugar de darken/lighten | Hover states en _pricing-tax.scss |
| SCSS-001 | @use (no @import) | Todos los nuevos ficheros SCSS |
| SCSS-COMPILE-VERIFY-001 | Verificar timestamp CSS > SCSS | Checklist de verificacion post-implementacion |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | Templates usan path() para enlaces legales |
| ZERO-REGION-001 | Variables via hook_preprocess | tax_note_text inyectado via preprocess, no controller |
| ICON-CONVENTION-001 | jaraba_icon() con paleta Jaraba | No aplica directamente (no hay iconos nuevos en billing) |
| PREMIUM-FORMS-PATTERN-001 | Forms extienden PremiumEntityFormBase | MarketplaceCommissionConfigForm (ConfigEntity, no aplica PEFB) |
| FORM-CACHE-001 | No setCached(TRUE) incondicional | No aplica (sin formularios en slide-panel) |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API | No aplica (sin innerHTML nuevo) |
| CSRF-API-001 | _csrf_request_header_token en API routes | Endpoints existentes, sin cambios |
| AUDIT-SEC-001 | Webhooks con HMAC + hash_equals | BillingWebhookController ya lo implementa |
| PHANTOM-ARG-001 | Args yml = params constructor | Verificado para cada servicio modificado |
| ENTITY-FK-001 | Cross-modulo = integer, mismo modulo = entity_reference | AddonSubscription.addon_id es entity_reference (mismo modulo) |
| LABEL-NULLSAFE-001 | entity->label() puede ser NULL | No aplica directamente |
| DOC-GUARD-001 | Edit incremental en master docs | Este plan es documento NUEVO, no modifica master docs |

---

## 11. Verificacion RUNTIME-VERIFY-001

Tras completar CADA sprint, ejecutar los 12 checks:

### Sprint P0

| Check | Verificacion | Comando |
|-------|-------------|---------|
| 1 | CSS compilado | N/A (sin cambios SCSS en P0) |
| 2 | Tablas DB | `lando drush ev "print_r(\Drupal::database()->schema()->fieldExists('billing_usage_record_field_data', 'synced_to_stripe'));"` |
| 3 | Rutas accesibles | `lando drush route:list --path=/api/v1/billing` (sin cambios de rutas) |
| 4 | data-* selectores | N/A (sin cambios frontend) |
| 5 | drupalSettings | N/A |
| 6 | hook_cron registrado | `lando drush ev "print_r(function_exists('jaraba_billing_cron'));"` |
| 7 | Servicio fiscal inyectable | `lando drush ev "print_r(\Drupal::hasService('jaraba_billing.fiscal_invoice_delegation'));"` |
| 8 | Stripe API accesible | `lando drush ev "\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY')); print \Stripe\Balance::retrieve()->available[0]->amount;"` |

### Sprint P1

| Check | Verificacion | Comando |
|-------|-------------|---------|
| 1 | Campo stripe_subscription_item_id | `lando drush ev "print_r(\Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('stripe_subscription_item_id', 'addon_subscription'));"` |
| 2 | Migracion TenantAddon completada | `lando drush ev "echo count(\Drupal::entityTypeManager()->getStorage('addon_subscription')->loadByProperties(['status' => 'active']));"` |
| 3 | PlanValidator alias funcional | `lando drush ev "print get_class(\Drupal::service('ecosistema_jaraba_core.plan_validator'));"` (debe ser billing) |
| 4 | FeatureAccess consulta AddonSub | Unit test: mock AddonSubscription → FeatureAccessService retorna TRUE |
| 5 | AI endpoints gateados | `curl -s https://jaraba-saas.lndo.site/api/v1/page-builder/seo-ai-suggest` → debe retornar 403 sin autenticacion |

### Sprint P2

| Check | Verificacion | Comando |
|-------|-------------|---------|
| 1 | CSS compilado (pricing-tax) | `ls -la css/components/pricing-tax.css` (timestamp > scss) |
| 2 | Tabla webhook_event_log | `lando drush ev "print_r(\Drupal::database()->schema()->tableExists('stripe_webhook_event_log'));"` |
| 3 | Ruta pricing andalucia_ei | `lando drush route:list --path=/planes/andalucia_ei` |
| 4 | CommissionConfig entity | `lando drush ev "print_r(\Drupal::entityTypeManager()->hasDefinition('marketplace_commission_config'));"` |
| 5 | IVA text configurable | Apariencia > Ecosistema Jaraba Theme > TAB 14: campo "Texto de nota IVA" visible |

---

## 12. Plan de Testing

### Tests por Sprint

| Sprint | Tipo | Fichero | Assertions clave |
|--------|------|---------|-----------------|
| P0 | Unit | `BillingWebhookControllerTest.php` | Fiscal delegation invocado; graceful si NULL; graceful si exception |
| P0 | Unit | `DunningCronTest.php` | Throttle respetado; processDunning invocado |
| P0 | Unit | `TenantSubscriptionServiceTest.php` | changePlan revierte si Stripe falla; cancel marca local incluso si Stripe falla |
| P0 | Unit | `UsageStripeSyncServiceTest.php` | Registros marcados synced; Stripe API invocado con params correctos; batch respetado |
| P1 | Unit | `AddonConsolidationTest.php` | Migracion TenantAddon → AddonSubscription; FeatureAccessService consulta nuevo SSoT |
| P1 | Unit | `AiFeatureGateTest.php` | 403 sin plan; 429 sin tokens; 200 con ambos |
| P1 | Unit | `CredentialGateTest.php` | Limit enforced; plan upgrade suggested |
| P2 | Unit | `WebhookDeduplicationTest.php` | Segundo procesamiento del mismo event_id retorna 200 sin accion |
| P2 | Unit | `CommissionConfigTest.php` | Config por vertical; fallback a _default; cascade correcto |
| P2 | Kernel | `RevenueMetricsFocTest.php` | MRR incluye comisiones marketplace |

### Patron de mocking (PHP 8.4)

Segun MOCK-DYNPROP-001 y MOCK-METHOD-001, los mocks de entidades usan clases anonimas con typed properties:

```php
$mockAddon = new class('vertical', 'comercioconecta') {
  public function __construct(
    private readonly string $type,
    private readonly string $ref,
  ) {}

  public function get(string $name): object {
    $value = match ($name) {
      'addon_type' => $this->type,
      'vertical_ref' => $this->ref,
      'features_included' => '["merchant_portal","product_catalog"]',
      default => '',
    };
    return new class($value) {
      public ?string $value;
      public function __construct(string $v) { $this->value = $v; }
    };
  }
};
```

---

## 13. Coherencia con Documentacion Tecnica Existente

### Relacion con planes de implementacion activos

| Plan | Relacion | Accion requerida |
|------|---------|-----------------|
| VERT-PRICING-001 (Verticalizacion Precios) | Las pricing pages creadas en ese plan se extienden con IVA/tax | Reutilizar templates existentes, anadir parcial _price-tax-note |
| VERT-ADDON-001 (Verticales Componibles) | TenantVerticalService y VerticalAddonBillingService se conectan con Stripe | Stripe sync implementado aqui, vertical cache invalidation ya existe |
| Plan Gaps Clase Mundial 100% | Este plan cierra los gaps de billing (90%→100%) identificados ahi | Score billing 90%→100% tras completar P0+P1 |
| Plan Salvaguardas Control Complitud | Los validation scripts deben actualizarse para cubrir los nuevos servicios | Anadir checks para fiscal delegation, dunning cron, Stripe sync |

### Actualizacion de master docs

Tras completar este plan, actualizar:
- **00_DIRECTRICES_PROYECTO.md**: Anadir regla STRIPE-SYNC-001 (toda modificacion local de suscripcion debe sincronizar con Stripe)
- **00_INDICE_GENERAL.md**: Actualizar score de Billing de 90% a 100%
- **CLAUDE.md**: Anadir reglas aprendidas durante la implementacion

**IMPORTANTE:** Estas actualizaciones de master docs deben ir en un commit SEPARADO con prefijo `docs:` (DOC-GUARD-001, COMMIT-SCOPE-001).

---

## 14. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-05 | Claude Opus 4.6 | Creacion del plan a partir de Auditoria Gaps Billing Commerce Fiscal v1 |
