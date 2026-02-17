# Plan de Elevacion AgroConecta a Clase Mundial — v1.0

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Implementacion |
| **Version** | 1.0.0 |
| **Fecha** | 2026-02-17 |
| **Estado** | Planificacion |
| **Vertical** | AgroConecta (Marketplace Agroalimentario) |
| **Modulos Implicados** | `jaraba_agroconecta_core`, `ecosistema_jaraba_core`, `jaraba_journey`, `jaraba_ai_agents`, `jaraba_email`, `jaraba_billing`, `jaraba_analytics`, `ecosistema_jaraba_theme` |
| **Referencia de Paridad** | Empleabilidad (10 fases) + Emprendimiento (7 gaps) + JarabaLex (14 fases) |
| **Docs de Referencia** | 00_DIRECTRICES_PROYECTO v41.0.0, 00_DOCUMENTO_MAESTRO_ARQUITECTURA v41.0.0, 07_VERTICAL_CUSTOMIZATION_PATTERNS v1.1.0, 2026-02-05_arquitectura_theming_saas_master v2.1, 00_FLUJO_TRABAJO_CLAUDE v2.0.0 |
| **Estimacion Total** | 14 Fases - 260-330 horas - 11.700-14.850 EUR |

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual del Vertical](#2-estado-actual-del-vertical)
3. [Analisis de Gaps — Paridad con Empleabilidad/Emprendimiento/JarabaLex](#3-analisis-de-gaps)
4. [Dependencias Cruzadas y Flujos Entrada/Salida](#4-dependencias-cruzadas)
5. [Recorridos de Usuario](#5-recorridos-de-usuario)
6. [Plan de Implementacion por Fases](#6-plan-de-implementacion-por-fases)
   - [FASE 0 — FeatureGateService + Enforcement](#fase-0)
   - [FASE 1 — UpgradeTriggerService + Upsell Contextual IA](#fase-1)
   - [FASE 2 — CopilotBridgeService Dedicado](#fase-2)
   - [FASE 3 — hook_preprocess_html Consolidacion + Body Classes](#fase-3)
   - [FASE 4 — Page Template Zero-Region + Copilot FAB](#fase-4)
   - [FASE 5 — SCSS Compliance y Design Tokens](#fase-5)
   - [FASE 6 — Design Token Config Entity](#fase-6)
   - [FASE 7 — Email Sequences MJML](#fase-7)
   - [FASE 8 — CrossVerticalBridgeService](#fase-8)
   - [FASE 9 — JourneyProgressionService — Reglas Proactivas](#fase-9)
   - [FASE 10 — HealthScoreService — 5 Dimensiones + 8 KPIs](#fase-10)
   - [FASE 11 — ExperimentService — A/B Testing](#fase-11)
   - [FASE 12 — Avatar Navigation + Funnel Analytics](#fase-12)
   - [FASE 13 — QA Integral + Tests + Documentacion](#fase-13)
7. [Tabla de Correspondencia con Especificaciones Tecnicas](#7-tabla-de-correspondencia)
8. [Cumplimiento de Directrices](#8-cumplimiento-de-directrices)
9. [Checklist Pre-Commit por Fase](#9-checklist-pre-commit)
10. [Inventario de Archivos a Crear/Modificar](#10-inventario-de-archivos)

---

## 1. Resumen Ejecutivo

AgroConecta es el vertical de **Marketplace Agroalimentario** del SaaS Jaraba Impact Platform. Conecta a productores agroalimentarios locales con consumidores finales y compradores B2B, ofreciendo trazabilidad completa, tiendas online, QR phy-gitales, y herramientas de marketing potenciadas por IA.

El vertical es el mas avanzado en terminos de funcionalidad de negocio: **33+ Content Entities, 22 Services, 22 Controllers, 50+ endpoints REST, 20 templates, 17 SCSS partials, 14 JS behaviors**. Sin embargo, carece completamente de la **infraestructura de elevacion a clase mundial** que ya implementan Empleabilidad, Emprendimiento, Andalucia+EI y JarabaLex: FeatureGateService funcional, UpgradeTriggers, email sequences MJML, cross-vertical bridges, journey progression proactiva, health scores, y experiment service.

### Objetivo

Elevar AgroConecta al mismo nivel de clase mundial que los verticales de referencia, implementando las 14 fases del patron de elevacion vertical:

- **Monetizacion**: FeatureGateService funcional que haga cumplir los 15 FreemiumVerticalLimit existentes + upgrade triggers
- **Engagement**: Journey progression proactiva + email sequences + health score
- **Experiencia**: Zero-region template + SCSS compliance + design tokens + modal CRUD
- **Expansion**: Cross-vertical bridges + avatar navigation completa + funnel analytics
- **Retension**: Copilot bridge dedicado + A/B testing + credenciales

### Diferenciador Competitivo

- **Competidores**: Freshplaza, Agronoticias, Agrofy (generalistas, sin trazabilidad), Ecotenda, DelCampoATuMesa (nicho, sin IA)
- **Diferenciacion**: IA nativa (Producer Copilot + Sales Agent), trazabilidad inmutable con QR phy-gitales, marketplace multi-canal (web + WhatsApp + PWA)
- **Precio**: Free (5 productos) / Starter 29 EUR/mes / Profesional 79 EUR/mes / Enterprise personalizado
- **TAM estimado**: 150M EUR (mercado agroalimentario local digital en Espana)

### Base Tecnica Existente (Fortaleza)

| Metrica | Valor |
|---------|-------|
| Content Entities | 33+ |
| Services PHP | 22 |
| Controllers | 22 |
| Endpoints REST API | 50+ |
| Templates Twig | 20 + 3 parciales |
| SCSS Partials | 17 + main.scss |
| JS Behaviors | 14 |
| Fases marketplace completadas | 1-4 implementadas, 5-9 planificadas (v3) |
| FreemiumVerticalLimit configs | 15 (3 planes x 5 features) |
| JourneyDefinition | 3 avatares (productor, comprador_b2b, consumidor) |
| AI Agents | 2 (ProducerCopilotAgent, SalesAgent) |

---

## 2. Estado Actual del Vertical

### 2.1 Lo que EXISTE (Implementado)

| Componente | Estado | Ubicacion |
|------------|--------|-----------|
| **Modulo `jaraba_agroconecta_core`** | 182 archivos, ~25.000 LOC | `web/modules/custom/jaraba_agroconecta_core/` |
| 33+ Content Entities | Implementadas | `src/Entity/` (ProductAgro, ProducerProfile, OrderAgro, SuborderAgro, OrderItemAgro, AgroCertification, AgroCategory, AgroCollection, PromotionAgro, CouponAgro, ShippingMethodAgro, ShippingZoneAgro, ReviewAgro, NotificationTemplateAgro, NotificationLogAgro, NotificationPreferenceAgro, QrCodeAgro, QrScanEvent, QrLeadCapture, AgroBatch, TraceEventAgro, IntegrityProofAgro, AlertRuleAgro, AnalyticsDailyAgro, CopilotConversationAgro, CopilotMessageAgro, CopilotGeneratedContentAgro, SalesConversationAgro, SalesMessageAgro, CustomerPreferenceAgro, PartnerRelationship, ProductDocument, DocumentDownloadLog) |
| 22 Services | Implementados | `src/Service/` (MarketplaceService, ProductAgroService, ProducerProfileService, OrderService, StripePaymentService, ShippingService, ReviewAgroService, NotificationService, PromotionService, QrService, PartnerDocumentService, TraceabilityService, AgroSearchService, AgroAnalyticsService, ProducerDashboardService, ProducerCopilotService, DemandForecasterService, MarketSpyService, SalesAgentService, CrossSellEngine, CartRecoveryService, WhatsAppApiService) |
| 22 Controllers | Implementados | `src/Controller/` (marketplace, checkout, orders, producer/customer portals, search, shipping, QR, traceability, partner hub, copilot, sales agent, analytics, admin) |
| 50+ Routes REST + Frontend | Implementadas | `jaraba_agroconecta_core.routing.yml` (1536+ lineas) |
| 20 Templates + 3 Parciales | Implementados | `templates/` |
| 17 SCSS Partials + main.scss | Implementados | `scss/` (con `@use`, Dart Sass) |
| 14 JS Behaviors | Implementados | `js/` |
| package.json + Dart Sass | Implementado | `package.json` con `sass ^1.71.0` |
| `hook_preprocess_html` | Implementado | `.module` linea 188, 18 rutas mapeadas a body classes |
| `hook_theme` | Implementado | `.module` linea 34, 17 definiciones Twig |
| `hook_entity_insert` | Implementado | `.module` (cart recovery, notifications) |
| `hook_cron` | Implementado | `.module` (analytics daily aggregation) |
| `hook_mail` | Implementado | `.module` (6 tipos: copilot_content_ready, price_alert, demand_forecast, cart_recovery, new_order, review_notification) |
| **Vertical Config Entity** | Seed instalado | `ecosistema_jaraba_core.vertical.agroconecta.yml` |
| 15 FreemiumVerticalLimit | Config install | 3 planes x 5 features (products, orders_per_month, copilot_uses_per_month, photos_per_product, commission_pct) |
| **JourneyDefinition** | Implementada | `AgroConectaJourneyDefinition.php` con 3 avatares, cross-sell triggers |
| Body classes en theme | Parcial | `ecosistema_jaraba_theme.theme` — `page-agroconecta` para rutas `jaraba_agroconecta.*` |
| Color token inyectable | Implementado | `color_agro: #556B2F` → `--ej-color-agro` |
| SCSS variable | Implementado | `$ej-color-agro` + `.vertical-landing--agroconecta` |
| Landing page | Implementada | `VerticalLandingController::agroconecta()` con 9 secciones |
| Lead magnet | Implementado | `/agroconecta/guia-vende-online` (Guia AgroConecta PDF) |
| 2 AI Agents | Implementados | `ProducerCopilotAgent` (4 acciones), `SalesAgent` (6 acciones) |
| AvatarNavigation | Parcial | `producer` role con entrada `marketplace` |
| 6 MJML transaccionales | Implementados | `jaraba_email/templates/mjml/marketplace/` (new_order_seller, new_review, order_confirmed, order_delivered, order_shipped, payout_processed) |
| Onboarding wizard | Implementado | Branching para productor/comprador |
| Sandbox/Demo data | Implementado | Datos de demo para marketplace |
| Page Builder | Implementado | 11 block templates verticales |

### 2.2 Lo que FALTA (Gaps Criticos)

| # | Gap | Prioridad | Fase |
|---|-----|-----------|------|
| G1 | **FeatureGateService** — No existe `AgroConectaFeatureGateService`. Los 15 FreemiumVerticalLimit configs existen pero no se hacen cumplir. No hay tabla `{agroconecta_feature_usage}`. | P0 CRITICA | F0 |
| G2 | **UpgradeTriggerService** — No hay tipos de trigger para agroconecta en `UpgradeTriggerService`. No hay llamadas `fire()` en los servicios del modulo. | P0 CRITICA | F1 |
| G3 | **CopilotBridgeService dedicado** — No existe `AgroConectaCopilotBridgeService`. `ProducerCopilotService` existe pero no implementa soft upsell ni vertical context injection en BaseAgent. | P1 ALTA | F2 |
| G4 | **hook_preprocess_page** — No implementado. Las variables de pagina no se inyectan via zero-region pattern. | P0 CRITICA | F3, F4 |
| G5 | **hook_theme_suggestions_page_alter** — No implementado. No hay template suggestions para rutas agroconecta. | P0 CRITICA | F4 |
| G6 | **page--agroconecta.html.twig** — No existe en el tema. Todas las rutas caen a templates genericos (`page__dashboard`). | P0 CRITICA | F4 |
| G7 | **SCSS compliance** — `_producer-portal.scss` usa `rgba()` bare (sin `var()`) en al menos 2 sitios. Otros archivos tienen `rgba()` como fallback dentro de `var()` (aceptable pero mejorable). | P1 ALTA | F5 |
| G8 | **Design Token Config Entity** — No existe `design_token_config.vertical_agroconecta.yml` en config/install. | P2 MEDIA | F6 |
| G9 | **Email Sequences MJML** — No hay directorio `agroconecta/` en `jaraba_email/templates/mjml/`. Los 6 transaccionales estan en `marketplace/` (no nombrado `agroconecta/`). No hay secuencias de lifecycle (onboarding, re-engagement, upsell, retention). `hook_mail` keys (`copilot_content_ready`, `price_alert`, `demand_forecast`, `cart_recovery`) usan plain text, no MJML. | P1 ALTA | F7 |
| G10 | **CrossVerticalBridgeService** — No existe `AgroConectaCrossVerticalBridgeService`. No hay puentes hacia otros verticales. | P2 MEDIA | F8 |
| G11 | **JourneyProgressionService** — No existe `AgroConectaJourneyProgressionService`. La JourneyDefinition existe (3 avatares) pero no hay motor de reglas proactivas. | P0 CRITICA | F9 |
| G12 | **HealthScoreService** — No existe `AgroConectaHealthScoreService`. No hay metricas de salud del usuario marketplace. | P1 ALTA | F10 |
| G13 | **ExperimentService** — No hay servicio de A/B testing para el vertical. | P3 BAJA | F11 |
| G14 | **BaseAgent vertical context** — No hay entrada `'agroconecta'` en `BaseAgent::getVerticalContext()`. | P1 ALTA | F2 |
| G15 | **Avatar Navigation incompleta** — Solo 1 entrada `marketplace` para rol `producer`. Faltan deep links (dashboard productor, pedidos, productos). Falta rol `customer/buyer`. Falta anonymous navigation entry. | P1 ALTA | F12 |
| G16 | **Funnel Analytics** — No hay `FunnelDefinition` config entity para AgroConecta en `jaraba_analytics`. | P2 MEDIA | F12 |
| G17 | **Modal CRUD** — No verificado si templates usan `data-dialog-type="modal"` para acciones CRUD. | P1 ALTA | F4 |
| G18 | **Naming inconsistency MJML** — Directorio `marketplace/` en vez de `agroconecta/` (todos los demas verticales usan el nombre del vertical). | P2 MEDIA | F7 |
| G19 | **SaaS Plans especificos** — Usa los planes globales (basico/profesional/enterprise) en vez de planes verticales dedicados. | P2 MEDIA | F0 |
| G20 | **Feature usage DB table** — No existe tabla de tracking de uso para enforcement del feature gate. | P0 CRITICA | F0 |

---

## 3. Analisis de Gaps

### 3.1 Matriz de Paridad: AgroConecta vs Empleabilidad vs Emprendimiento vs JarabaLex

| Componente | Empleab. | Emprend. | JarabaLex | AgroConecta |
|------------|:---:|:---:|:---:|:---:|
| Landing page publica (9 secciones) | OK | OK | OK (F0) | OK |
| Lead magnet gratuito | OK Diagnostico | OK Calculadora | OK Diagnostico Legal | OK Guia PDF |
| Zero-region page template | OK | OK | OK (F1) | X G6 |
| Modal CRUD | OK | OK | OK (F2) | ? G17 |
| SCSS compliance (color-mix, var) | OK | OK | OK (F3) | Parcial G7 |
| Design Token Config Entity | OK | OK | OK (F3) | X G8 |
| FeatureGateService | OK | OK | OK (F4) | X G1 |
| FreemiumVerticalLimit configs | OK | OK | OK 9 configs | OK 15 configs |
| Feature usage DB table | OK | OK | OK (F4) | X G20 |
| UpgradeTrigger types + fire() | OK | OK | OK (F5) | X G2 |
| Soft upsell en Copilot | OK | OK | OK (F5) | X G2 |
| EmailSequenceService | OK 5 seq | OK 5 seq | OK 5 seq (F6) | X G9 |
| MJML templates | OK | OK | OK (F6) | Parcial (6 transacc, 0 seq) |
| CRM sync hooks | OK | OK | OK (F7) | X |
| CrossVerticalBridgeService | OK 4 bridges | OK 3 bridges | OK 4 bridges (F8) | X G10 |
| JourneyDefinition | OK 3 avatares | OK 3 avatares | OK 3 avatares (F9) | OK 3 avatares |
| JourneyProgressionService | OK 7 reglas | OK 7 reglas | OK 7 reglas (F9) | X G11 |
| HealthScoreService | OK 5 dim + 8 KPIs | OK 5 dim + 8 KPIs | OK 5 dim + 8 KPIs (F10) | X G12 |
| CopilotAgent/BridgeService | OK 6 modos | OK 6 modos | OK 6 modos (F11) | Parcial (2 agents, sin bridge) |
| Avatar en NavigationService | OK jobseeker/recruiter | OK entrepreneur | OK legal_professional (F12) | Parcial (1 entry) G15 |
| Vertical context en BaseAgent | OK empleo | OK emprendimiento | OK jarabalex (F11) | X G14 |
| Funnel Definition | OK | OK | OK 3 funnels (F12) | X G16 |
| ExperimentService | OK | OK | OK (F13) | X G13 |
| hook_preprocess_page | OK | OK | OK | X G4 |
| hook_theme_suggestions_page_alter | OK | OK | OK | X G5 |

**Score de paridad: 6/26 completos (23,1%), 4/26 parciales** — AgroConecta tiene la base tecnica mas robusta de todos los verticales (33+ entidades, 22 servicios) pero carece de toda la capa de elevacion a clase mundial (FeatureGate, UpgradeTriggers, EmailSequences, JourneyProgression, HealthScore, CrossVerticalBridge, ExperimentService, zero-region pattern).

### 3.2 Ventajas de AgroConecta sobre Otros Verticales

A diferencia de JarabaLex (que partia de 12,5% paridad), AgroConecta tiene:

1. **Landing page ya implementada** con 9 secciones + lead magnet
2. **JourneyDefinition ya implementada** con 3 avatares completos y cross-sell triggers
3. **2 AI Agents ya implementados** (ProducerCopilot y SalesAgent) con acciones especializadas
4. **15 FreemiumVerticalLimit configs ya existentes** (vs 9 de JarabaLex)
5. **6 MJML transaccionales ya existentes** (vs 0 de JarabaLex)
6. **Body classes ya implementadas** en `hook_preprocess_html()` con 18 rutas mapeadas

Esto reduce significativamente las fases 0 (Landing), 9 (JourneyDefinition) y 11 (CopilotAgent) respecto al plan de JarabaLex.

---

## 4. Dependencias Cruzadas

### 4.1 Modulos que AgroConecta CONSUME (Entrada)

| Modulo Origen | Que Consume | Servicio/Interfaz |
|---------------|-------------|-------------------|
| `ecosistema_jaraba_core` | TenantContext, FeatureAccess, VerticalConfig, DesignTokens | `@ecosistema_jaraba_core.tenant_context` |
| `jaraba_ai_agents` | SmartBaseAgent, ProducerCopilotAgent, SalesAgent | `@jaraba_ai_agents.producer_copilot_agent` |
| `jaraba_copilot_v2` | FAB Copilot UI, Copilot API | Templates parciales `_copilot-fab.html.twig` |
| `jaraba_billing` | FeatureAccessService, UpgradeTriggerService, Plan validation | `@jaraba_billing.feature_access` |
| `jaraba_journey` | JourneyStateManager, AgroConectaJourneyDefinition, journey_state entity | `@jaraba_journey.state_manager` |
| `jaraba_email` | SequenceManagerService, MJML rendering | `@jaraba_email.sequence_manager` |
| `jaraba_analytics` | FunnelTrackingService, EventService | `@jaraba_analytics.funnel_tracking` |
| `jaraba_crm` | PipelineService, ActivityService, ContactService | `@jaraba_crm.pipeline` |
| `ecosistema_jaraba_theme` | Templates de pagina, header/footer parciales, SCSS tokens | Templates Twig |

### 4.2 Modulos que CONSUMEN de AgroConecta (Salida)

| Modulo Destino | Que Consume | Interfaz |
|----------------|-------------|----------|
| `jaraba_copilot_v2` | Contexto marketplace, catalogo productos, historial pedidos | Via `ProducerCopilotService` / `SalesAgentService` |
| `jaraba_billing` | Comisiones por venta, planes marketplace | `StripePaymentService` commission hooks |
| `ecosistema_jaraba_core` | KPIs agregados del vertical, metrics dashboard | Via `AgroAnalyticsService` |
| `jaraba_servicios_conecta` | Productos como servicios enlazados | Potencial futuro via bridge |

### 4.3 Flujos de Datos Cruzados

```
+------------------+    +------------------------+    +------------------+
|  PRODUCTORES     |--->|  jaraba_agroconecta_   |--->| jaraba_copilot_v2|
|  (ProducerProfile|    |  core                  |    | (Producer FAB)   |
|  + ProductAgro)  |    |  [Commerce Engine]     |    +------------------+
+------------------+    |                        |
                        |  Entities:             |--->| jaraba_analytics |
+------------------+    |  - ProductAgro         |    | (Funnel events)  |
|  CONSUMIDORES    |--->|  - OrderAgro           |    +------------------+
|  (CustomerPortal |    |  - ReviewAgro          |
|  + SalesAgent)   |    |  - QrCodeAgro          |--->| jaraba_email     |
+------------------+    |  - TraceEventAgro      |    | (Sequences MJML) |
                        |  - AnalyticsDailyAgro  |    +------------------+
+------------------+    |  - ShipmentAgro        |
|  COMPRADORES B2B |--->|  - PartnerRelationship |    +------------------+
|  (PartnerHub +   |    |  [20+ entities more]   |--->| jaraba_billing   |
|  B2B Orders)     |    +------------------------+    | (FeatureGate +   |
+------------------+             |                    |  Upgrade Triggers)|
                                 v                    +------------------+
                        +------------------------+
                        | ecosistema_jaraba_core |
                        | - FeatureGateService   |
                        | - JourneyProgression   |
                        | - HealthScore          |
                        | - EmailSequence        |
                        | - CrossVerticalBridge  |
                        | - AvatarNavigation     |
                        +------------------------+
```

---

## 5. Recorridos de Usuario

### 5.1 Recorrido 1: Productor No Registrado -> Landing Publica

```
1. [SEO/SEM/Referral/QR] -> Productor llega a /agroconecta
2. Landing page 9 secciones (ya implementada en VerticalLandingController::agroconecta()):
   +- Hero: "Vende tus productos agroalimentarios online"
   +- Pain Points: 4 dolores del productor local
   +- Pasos: 3 pasos (Publica -> Vende -> Cobra)
   +- Features: 6 capacidades con IA (Copilot, Trazabilidad, QR, Analytics, Pagos, Envios)
   +- Social Proof: Testimonios + metricas
   +- Lead Magnet: "Guia Gratuita: Vende Online" -> /agroconecta/guia-vende-online
   +- Pricing: 3 planes (Free/Starter 29 EUR/Profesional 79 EUR)
   +- FAQ: 8 preguntas frecuentes
   +- Final CTA: Registro -> /registro/agroconecta
3. Lead Magnet: Guia AgroConecta PDF (ya implementado)
   +- Descarga gratuita con formulario de captura
   +- CTA: "Crea tu tienda gratuita"
   +- [Copilot FAB activo con contexto anonimo: modo faq]
4. Registro: /registro/agroconecta
   +- Plan Free automatico (5 productos, comision 15%)
   +- Auto-enrollment en SEQ_AGRO_001 (Onboarding Productor) [NUEVO F7]
   +- Onboarding wizard con branching (ya implementado)
   +- Redirect a /agroconecta/producer/dashboard
```

### 5.2 Recorrido 2: Productor Registrado -> Embudo de Ventas

```
DISCOVERY (Plan Free):
+- Dashboard productor: panel con productos, pedidos, metricas basicas
+- Copilot FAB: modo 'generate_description' — "Describe tu producto con IA"
+- Primera subida de producto con foto -> Copilot genera descripcion
+- IA Proactiva [NUEVO F9]: si 3 dias sin actividad -> nudge "Tu tienda esta casi lista"
+- Trigger: profile_started -> transicion a ACTIVATION

ACTIVATION (Plan Free, primera semana):
+- Publica primer producto con certificacion y trazabilidad
+- Copilot: modo 'suggest_price' — sugiere precio competitivo
+- Lead magnet interno: "Analisis de mercado para tu categoria"
+- Trigger [NUEVO F1]: first_product_published -> SEQ_AGRO_002 (Activacion)
+- Gate [NUEVO F0]: 5o producto -> modal upgrade "Has alcanzado el limite de 5 productos"

ENGAGEMENT (Plan Starter 29 EUR/mes):
+- 25 productos, comision 10%, 50 pedidos/mes, 10 fotos/producto
+- Recibe primer pedido -> Copilot: modo 'respond_review' — ayuda a responder resenas
+- IA Proactiva [NUEVO F9]: "Tienes 3 resenas sin responder, responde para mejorar tu rating"
+- Analytics dashboard: metricas de ventas, conversion, tendencias
+- Gate [NUEVO F0]: necesita trazabilidad avanzada -> modal upgrade "Trazabilidad QR es Pro"
+- Trigger [NUEVO F1]: 10th_order -> SEQ_AGRO_004 (Upsell Starter->Pro)

CONVERSION (Plan Profesional 79 EUR/mes):
+- Productos ilimitados, comision 5%, pedidos ilimitados, fotos ilimitadas
+- Trazabilidad completa con QR phy-gitales
+- Demand Forecaster + Market Spy activados
+- Copilot: todos los modos disponibles sin limites
+- Dashboard profesional con KPIs avanzados
+- Partner Hub B2B habilitado
+- Trigger [NUEVO F1]: reach_50_sales -> SEQ_AGRO_005 (Retention)

RETENTION (Plan Profesional/Enterprise):
+- Cross-vertical bridge [NUEVO F8]: "Gestiona la contabilidad de tu negocio" -> fiscal
+- Health Score visible en dashboard [NUEVO F10]
+- A/B testing de precios y descripciones [NUEVO F11]
+- Credenciales de productor certificado
```

### 5.3 Recorrido 3: Consumidor -> Compra + Fidelizacion

```
DISCOVERY:
+- Llega via SEO, QR de producto, redes sociales
+- Explora categorias, lee historias de productores
+- SalesAgent [existente]: modo 'recommend_products' -> sugerencias personalizadas
+- SalesAgent: modo 'search_catalog' -> busqueda asistida

ACTIVATION:
+- Lee historia del productor + certificaciones
+- Anade al carrito
+- SalesAgent: modo 'handle_cart' -> gestion del carrito
+- Si abandona carrito: CartRecoveryService [existente] + SEQ_AGRO_006 [NUEVO F7]

CONVERSION:
+- Checkout con envio calculado
+- Pago Stripe
+- Email confirmacion (MJML existente)

RETENTION:
+- Recibe pedido + notificacion tracking
+- Deja resena
+- SalesAgent: modo 'recover_cart' si no vuelve en 30 dias
+- Cross-vertical bridge [NUEVO F8]: "Emprende con productos locales" -> emprendimiento
```

### 5.4 Recorrido 4: Cross-Vertical (Escalera de Valor Transversal)

```
PUENTES DESDE AgroConecta (Outgoing):
+- -> Emprendimiento: "Convierte tu produccion en un negocio escalable"
|   Condicion: productor con >50 ventas + >4.5 rating
|   CTA: /emprendimiento
+- -> Fiscal (VeriFACTU): "Gestiona la facturacion de tu negocio"
|   Condicion: productor con plan Profesional + >20 ventas/mes
|   CTA: /fiscal/dashboard
+- -> Formacion: "Certificacion en comercio agroalimentario digital"
|   Condicion: productor con >100 ventas totales
|   CTA: /courses
+- -> Empleabilidad: "Busca empleo en el sector agroalimentario"
    Condicion: productor con journey_state at_risk + inactivo 30 dias
    CTA: /empleabilidad

PUENTES HACIA AgroConecta (Incoming, desde otros verticales):
+- <- Emprendimiento: bridge "Vende tus productos artesanales online"
|   Condicion: emprendedor en sector alimentario con canvas_completeness > 60%
+- <- Empleabilidad: bridge "Emprendimiento rural: crea tu tienda"
|   Condicion: jobseeker en zona rural con skills agroalimentarias
+- <- Fiscal: bridge "Tus clientes productores pueden vender online"
    Condicion: asesor fiscal con clientes en sector primario
```

---

## 6. Plan de Implementacion por Fases

---

### FASE 0 — FeatureGateService + Enforcement {#fase-0}

**Prioridad:** P0 CRITICA | **Estimacion:** 20-25h | **Gaps:** G1, G19, G20

**Descripcion:** Implementar el servicio de feature gating que haga cumplir los 15 FreemiumVerticalLimit existentes. Actualmente los servicios del modulo no verifican limites — cualquier usuario accede a todas las funcionalidades independientemente de su plan. Esta es la brecha mas critica para la monetizacion del vertical.

#### 6.0.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaFeatureGateService.php` | Servicio principal de feature gating. Constructor: `UpgradeTriggerService`, `Connection`, `AccountProxyInterface`, `LoggerInterface`. Metodos: `check(int $userId, string $featureKey, ?string $plan): FeatureGateResult`, `recordUsage(int $userId, string $featureKey): void`, `getUserPlan(int $userId): string`, `getRemainingUsage(int $userId, string $featureKey): int`, `resetMonthlyUsage(): void`. Feature keys: `products` (5/25/-1), `orders_per_month` (10/50/-1), `copilot_uses_per_month` (5/50/-1), `photos_per_product` (3/10/-1), `commission_pct` (15/10/5). Patron: seguir `EmployabilityFeatureGateService` exactamente. |
| Schema update en `ecosistema_jaraba_core.install` | Crear tabla `{agroconecta_feature_usage}` con campos: `id` (serial), `user_id` (int), `feature_key` (varchar 64), `usage_date` (varchar 10, YYYY-MM-DD), `usage_count` (int, default 0). Indices: `user_feature_date` UNIQUE, `feature_date`. |

#### 6.0.2 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_agroconecta_core/src/Service/ProductAgroService.php` | Antes de crear producto, llamar `check($userId, 'products')`. Si `denied`, retornar respuesta con `upgrade_message`. |
| `jaraba_agroconecta_core/src/Service/OrderService.php` | Antes de crear pedido, llamar `check($producerId, 'orders_per_month')`. |
| `jaraba_agroconecta_core/src/Service/ProducerCopilotService.php` | Antes de cada uso de copilot, llamar `check($userId, 'copilot_uses_per_month')`. |
| `jaraba_agroconecta_core/src/Service/ProductAgroService.php` | Al subir foto, verificar `check($userId, 'photos_per_product')` contra el producto especifico. |
| `jaraba_agroconecta_core/src/Service/StripePaymentService.php` | Aplicar comision segun `commission_pct` del plan del productor. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_feature_gate` con dependencias. |
| `jaraba_billing/src/Service/FeatureAccessService.php` | Anadir `jaraba_agroconecta_core` al array `$allAddons` de `getAvailableAddons()`. |

#### 6.0.3 Logica de Feature Keys

| Feature Key | Free | Starter (29 EUR) | Profesional (79 EUR) | Enterprise |
|-------------|------|---------|-----|-----------|
| `products` | 5 | 25 | -1 (ilimitados) | -1 |
| `orders_per_month` | 10 | 50 | -1 | -1 |
| `copilot_uses_per_month` | 5 | 50 | -1 | -1 |
| `photos_per_product` | 3 | 10 | -1 | -1 |
| `commission_pct` | 15% | 10% | 5% | Negociable |

#### 6.0.4 Configs Adicionales a Crear (Feature Keys marketplace avanzados)

| Config ID | Plan | Feature | Limit |
|-----------|------|---------|-------|
| `agroconecta_free_traceability_qr` | free | traceability_qr | 0 |
| `agroconecta_starter_traceability_qr` | starter | traceability_qr | 5 |
| `agroconecta_profesional_traceability_qr` | profesional | traceability_qr | -1 |
| `agroconecta_free_partner_hub` | free | partner_hub | 0 |
| `agroconecta_starter_partner_hub` | starter | partner_hub | 0 |
| `agroconecta_profesional_partner_hub` | profesional | partner_hub | -1 |
| `agroconecta_free_analytics_advanced` | free | analytics_advanced | 0 |
| `agroconecta_starter_analytics_advanced` | starter | analytics_advanced | 0 |
| `agroconecta_profesional_analytics_advanced` | profesional | analytics_advanced | -1 |
| `agroconecta_free_demand_forecaster` | free | demand_forecaster | 0 |
| `agroconecta_starter_demand_forecaster` | starter | demand_forecaster | 0 |
| `agroconecta_profesional_demand_forecaster` | profesional | demand_forecaster | -1 |

#### 6.0.5 Directrices de Aplicacion

- **LEGAL-GATE-001**: Cada servicio con limites DEBE usar FeatureGateService, nunca verificacion adhoc.
- **ENTITY-001**: Feature usage tracking via tabla DB, no via State API (volumen alto).
- **Patron**: Seguir exactamente `EmployabilityFeatureGateService` para check() retornando `FeatureGateResult` ValueObject.

---

### FASE 1 — UpgradeTriggerService + Upsell Contextual IA {#fase-1}

**Prioridad:** P0 CRITICA | **Estimacion:** 15-18h | **Gap:** G2

**Descripcion:** Integrar el sistema de upgrade triggers para que cada denegacion de feature gate dispare un trigger de upsell inteligente. Implementar soft suggestions contextuales en el copilot productor.

#### 6.1.1 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_billing/src/Service/UpgradeTriggerService.php` | Anadir tipos de trigger: `agro_product_limit_reached`, `agro_order_limit_reached`, `agro_copilot_limit_reached`, `agro_photo_limit_reached`, `agro_traceability_not_available`, `agro_partner_hub_not_available`, `agro_analytics_not_available`, `agro_demand_forecaster_not_available`. |
| `ecosistema_jaraba_core/src/Service/AgroConectaFeatureGateService.php` | Despues de `denied`, llamar `$this->upgradeTriggerService->fire('agro_{featureKey}_limit_reached', $userId, $context)`. |
| `jaraba_agroconecta_core/src/Service/ProductAgroService.php` | En resultado `denied`, incluir `upgrade_url`, `upgrade_plan`, `upgrade_message` del trigger. |
| `jaraba_agroconecta_core/src/Service/ProducerCopilotService.php` | Anadir metodo `getSoftSuggestion(int $userId): ?array` — evalua journey state y plan para sugerir upgrade si `plan === 'free'` y `journey_phase >= 3` (engagement). |

#### 6.1.2 Tabla de Triggers

| Trigger | Condicion | Mensaje | CTA |
|---------|-----------|---------|-----|
| `agro_product_limit_80` | 80% del limite de productos (4 de 5 free) | `{% trans %}Casi llenas tu catalogo. Con Starter tendras 25 productos.{% endtrans %}` | `/billing/upgrade?plan=starter&vertical=agroconecta` |
| `agro_product_limit_100` | Limite de productos alcanzado | `{% trans %}Has alcanzado el limite de 5 productos. Amplia tu catalogo con Starter.{% endtrans %}` | `/billing/upgrade?plan=starter&vertical=agroconecta` |
| `agro_order_limit` | Limite de pedidos mensuales | `{% trans %}Has alcanzado tus 10 pedidos este mes. No pierdas ventas.{% endtrans %}` | `/billing/upgrade` |
| `agro_copilot_limit` | Limite de usos copilot | `{% trans %}Has usado tus 5 consultas IA. Starter te da 50/mes.{% endtrans %}` | `/billing/upgrade` |
| `agro_traceability_blocked` | Intento de QR en plan free | `{% trans %}La trazabilidad QR genera confianza. Disponible desde Starter.{% endtrans %}` | `/billing/upgrade` |
| `agro_partner_blocked` | Intento B2B en plan no-Pro | `{% trans %}El Hub B2B abre un nuevo canal de venta. Disponible en Profesional.{% endtrans %}` | `/billing/upgrade?plan=profesional` |

---

### FASE 2 — CopilotBridgeService Dedicado {#fase-2}

**Prioridad:** P1 ALTA | **Estimacion:** 15-18h | **Gaps:** G3, G14

**Descripcion:** Crear el servicio puente entre el copilot central y el contexto del vertical AgroConecta, y registrar el vertical context en BaseAgent. Aunque ya existen `ProducerCopilotAgent` y `SalesAgent`, falta el bridge service que inyecta contexto de mercado, catalogo y metricas del productor en las respuestas del copilot.

#### 6.2.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `jaraba_agroconecta_core/src/Service/AgroConectaCopilotBridgeService.php` | Puente entre copilot y datos del marketplace. Metodos: `getRelevantContext(int $userId): array` (retorna productos del usuario, metricas recientes, pedidos pendientes, resenas sin responder), `getSoftSuggestion(int $userId): ?array` (evalua plan + journey para soft upsell), `getMarketInsights(int $userId): array` (tendencias de categoria, precios competidores via MarketSpyService). Constructor DI: `ProductAgroService`, `AgroAnalyticsService`, `MarketSpyService`, `AgroConectaFeatureGateService`, `JourneyStateManager`, `LoggerInterface`. |

#### 6.2.2 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_ai_agents/src/Agent/BaseAgent.php` | Anadir entrada en `getVerticalContext()`: `'agroconecta' => 'Ecosistema digital para productores agroalimentarios. Marketplace con trazabilidad, QR phy-gitales, IA para produccion, precios y marketing.'`. |
| `jaraba_ai_agents/jaraba_ai_agents.services.yml` | Registrar inyeccion de `AgroConectaCopilotBridgeService` en `ProducerCopilotAgent` y `SalesAgent`. |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.services.yml` | Registrar `jaraba_agroconecta.copilot_bridge` con dependencias. |
| `jaraba_agroconecta_core/src/Service/ProducerCopilotService.php` | Inyectar `AgroConectaCopilotBridgeService` y usar `getRelevantContext()` en cada llamada al agente. |

#### 6.2.3 Soft Upsell Logic

```php
private function getSoftSuggestion(int $userId): ?array {
    $plan = $this->featureGate->getUserPlan($userId);
    if ($plan !== 'free') return NULL;

    $journeyPhase = $this->getJourneyPhase($userId);
    if ($journeyPhase < 3) return NULL; // Solo a partir de engagement

    return match(TRUE) {
        $journeyPhase === 3 => [
            'plan' => 'starter',
            'message' => $this->t('Con Starter tendrias 25 productos, 50 pedidos/mes y comision del 10%.'),
        ],
        $journeyPhase >= 4 => [
            'plan' => 'profesional',
            'message' => $this->t('Con Profesional tendrias productos ilimitados, trazabilidad QR y Hub B2B.'),
        ],
        default => NULL,
    };
}
```

---

### FASE 3 — hook_preprocess_html Consolidacion + Body Classes {#fase-3}

**Prioridad:** P0 CRITICA | **Estimacion:** 5-8h | **Gaps:** G4 (parcial)

**Descripcion:** Verificar y consolidar el hook_preprocess_html existente (linea 188 del .module) para asegurar cobertura completa de body classes. El hook ya existe y cubre 18 rutas, pero necesita verificacion contra la tabla de rutas completa y asegurarse de que cumple con LEGAL-BODY-001.

#### 6.3.1 Verificaciones

1. Mapear TODAS las 50+ rutas de `jaraba_agroconecta_core.routing.yml` contra body classes.
2. Verificar que `hook_preprocess_html()` en `.module` cubre todas las rutas frontend (no solo API).
3. Confirmar que las body classes existentes siguen el patron: `page-agroconecta`, `page-agroconecta--{section}`.
4. Verificar que `ecosistema_jaraba_theme.theme` tambien aplica classes para el vertical.

#### 6.3.2 Body Classes Requeridas

| Ruta | Body Class |
|------|-----------|
| `jaraba_agroconecta.marketplace` | `page-agroconecta page-agroconecta--marketplace` |
| `jaraba_agroconecta.product_detail` | `page-agroconecta page-agroconecta--product` |
| `jaraba_agroconecta.checkout*` | `page-agroconecta page-agroconecta--checkout` |
| `jaraba_agroconecta.search_page` | `page-agroconecta page-agroconecta--search` |
| `jaraba_agroconecta.producer.dashboard` | `page-agroconecta page-agroconecta--producer` |
| `jaraba_agroconecta.customer.dashboard` | `page-agroconecta page-agroconecta--customer` |
| `jaraba_agroconecta.category_page` | `page-agroconecta page-agroconecta--category` |

#### 6.3.3 Directrices

- **LEGAL-BODY-001**: Body classes siempre via `hook_preprocess_html()`, NUNCA `attributes.addClass()` en template.
- Verificar en el tema: `ecosistema_jaraba_theme.theme` ya tiene match para `jaraba_agroconecta.*` → `page-agroconecta`.

---

### FASE 4 — Page Template Zero-Region + Copilot FAB {#fase-4}

**Prioridad:** P0 CRITICA | **Estimacion:** 15-18h | **Gaps:** G5, G6, G17

**Descripcion:** Crear el template de pagina zero-region `page--agroconecta.html.twig` y los hooks faltantes (`hook_preprocess_page`, `hook_theme_suggestions_page_alter`) para que TODAS las rutas frontend del vertical rendericen a traves de un template limpio sin `page.content`, con Copilot FAB integrado. Auditar y corregir modal CRUD.

#### 6.4.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_theme/templates/page--agroconecta.html.twig` | Template zero-region. Incluye `_header.html.twig`, `_footer.html.twig`, `_copilot-fab.html.twig`. Renderiza `{{ clean_content }}` inyectado via `hook_preprocess_page()`. Body classes: `page-agroconecta`, `vertical-agroconecta`, `full-width-layout`. Mobile-first layout. |

#### 6.4.2 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_agroconecta_core/jaraba_agroconecta_core.module` | Anadir `hook_preprocess_page()`: inyectar variables del controller en `$variables['clean_content']` siguiendo zero-region pattern. Referencia canonica: `jaraba_verifactu.module`. |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.module` | Anadir `hook_theme_suggestions_page_alter()`: todas las rutas `jaraba_agroconecta.*` frontend -> template suggestion `page__agroconecta`. Rutas API (`*.api.*`) no necesitan template suggestion. |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Verificar que `hook_theme_suggestions_page_alter()` incluye match para rutas agroconecta -> `page__agroconecta`. |
| Todos los templates con enlaces CRUD | Auditar y anadir `class="use-ajax" data-dialog-type="modal" data-dialog-options='{"width": 800}'` en acciones CRUD. |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.libraries.yml` | Anadir library `agroconecta.modal-actions` con dependencia `core/drupal.dialog.ajax`. |

#### 6.4.3 Templates a Auditar (Modal CRUD)

| Template | Acciones CRUD |
|----------|--------------|
| `agro-producer-dashboard.html.twig` | Crear producto, Editar producto, Ver pedido |
| `agro-customer-dashboard.html.twig` | Ver pedido, Escribir resena, Gestionar direcciones |
| `agro-product-detail.html.twig` | Anadir al carrito (modal de opciones), Escribir resena |
| `agro-checkout.html.twig` | Aplicar cupon, Seleccionar envio |
| `agro-producer-orders.html.twig` | Ver detalle pedido, Actualizar estado envio |
| `agro-partner-hub.html.twig` | Crear relacion, Ver documento, Descargar |

#### 6.4.4 Directrices

- **ZERO-REGION-001**: Variables SOLO via `hook_preprocess_page()`, controller retorna `['#type' => 'markup', '#markup' => '']`.
- **ZERO-REGION-002**: NUNCA pasar entidades como claves sin `#` en render arrays.
- **ZERO-REGION-003**: `#attached` del controller NO se procesa en zero-region; usar `$variables['#attached']` en hook.
- **NAV-002**: Copilot FAB incluido en `_header.html.twig`, no en cada template individual.
- Referencia canonica: `jaraba_verifactu.module`.

---

### FASE 5 — SCSS Compliance y Auditoria {#fase-5}

**Prioridad:** P1 ALTA | **Estimacion:** 10-12h | **Gap:** G7

**Descripcion:** Auditar todos los 17 archivos SCSS de `jaraba_agroconecta_core` para cumplimiento de directrices: reemplazar `rgba()` bare por `color-mix()`, verificar `var(--ej-*)` con fallbacks correctos, confirmar BEM naming, compilar CSS.

#### 6.5.1 Auditoria SCSS

| Archivo | Estado | Accion |
|---------|--------|--------|
| `main.scss` | OK `@use 'sass:color'` + `@use` syntax | Verificar solo `@use`, no `@import`. |
| `_marketplace.scss` | Verificar | Buscar `rgba()` bare, verificar `var(--ej-*)` con fallbacks. |
| `_checkout.scss` | Verificar | Idem. |
| `_reviews.scss` | Verificar | Idem. |
| `_notifications.scss` | Verificar | Idem. |
| `_search.scss` | Verificar | Idem. |
| `_admin.scss` | Verificar | Idem. |
| `_copilot.scss` | Verificar | Idem. |
| `_traceability.scss` | Verificar | Idem. |
| `_analytics.scss` | Verificar | Idem. |
| `_promotions.scss` | OK `color-mix()` (4 instancias) | Verificar resto. |
| `_qr-dashboard.scss` | Parcial `rgba()` + `color-mix()` (2 instancias) | Reemplazar `rgba()` → `color-mix()`. |
| `_sales-chat.scss` | Verificar | Idem. |
| `_producer-portal.scss` | FALLA `rgba()` bare sin `var()` | Reemplazar `rgba(0, 0, 0, 0.04)` → `color-mix(in srgb, var(--ej-color-text) 4%, transparent)`. Reemplazar `rgba(45, 122, 58, 0.1)` → `color-mix(in srgb, var(--ej-color-agro) 10%, transparent)`. |
| `_customer-portal.scss` | Verificar | Idem. |
| `_partner-hub.scss` | Verificar | Idem. |
| `_shipping.scss` | Verificar | Idem. |

#### 6.5.2 Patron de Reemplazo

```scss
// ANTES (incorrecto):
border-bottom: 1px solid rgba(0, 0, 0, 0.04);
background: rgba(45, 122, 58, 0.1);

// DESPUES (correcto):
border-bottom: 1px solid color-mix(in srgb, var(--ej-color-text, #2D3436) 4%, transparent);
background: color-mix(in srgb, var(--ej-color-agro, #556B2F) 10%, transparent);
```

#### 6.5.3 Compilacion

```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_agroconecta_core && /user/.nvm/versions/node/v20.20.0/bin/npx sass scss/main.scss:css/agroconecta.css --style=compressed --no-source-map"
lando drush cr
```

#### 6.5.4 Directrices

- **P4-COLOR-001/002/003**: Solo paleta Jaraba. AgroConecta usa primary `var(--ej-color-agro, #556B2F)`, secondary `#2D3436`, accent `#FF8C42`. Variantes con `color-mix()`.
- **SCSS-001**: `@use` crea scope aislado — cada partial DEBE incluir `@use '../variables' as *;` (o similar).
- **P4-EMOJI-001**: `jaraba_icon()` para todos los iconos, nunca emojis.
- **Arquitectura Theming Master v2.1**: Modulos satelite NUNCA definen `$ej-*`, solo consumen `var(--ej-*)`.

---

### FASE 6 — Design Token Config Entity {#fase-6}

**Prioridad:** P2 MEDIA | **Estimacion:** 5-8h | **Gap:** G8

**Descripcion:** Crear la configuracion de design tokens para el vertical AgroConecta, siguiendo el patron de los verticales existentes.

#### 6.6.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.vertical_agroconecta.yml` | Tokens de diseno completos: colores (primary `#556B2F` olive, secondary `#2D3436` dark, accent `#FF8C42` orange, success `#27AE60`, earth `#8B4513`), tipografia (Outfit headings, Inter body — conforme a design_token_config existente en config/sync), spacing (modular scale), effects (shadows, borders), component variants (header: marketplace, hero: product-showcase, cards: product-card, footer: marketplace-compact). |

#### 6.6.2 Contenido del Config

```yaml
langcode: es
status: true
id: vertical_agroconecta
label: 'Design Tokens AgroConecta'
vertical: agroconecta
colors:
  primary: '#556B2F'
  secondary: '#2D3436'
  accent: '#FF8C42'
  success: '#27AE60'
  earth: '#8B4513'
  background: '#FAFAF5'
  text: '#2D3436'
  text_secondary: '#636E72'
typography:
  heading_family: 'Outfit, system-ui, sans-serif'
  body_family: 'Inter, system-ui, sans-serif'
  heading_weight: '700'
  body_weight: '400'
spacing:
  base: '8px'
  scale: '1.5'
effects:
  shadow_sm: '0 1px 3px color-mix(in srgb, #2D3436 8%, transparent)'
  shadow_md: '0 4px 12px color-mix(in srgb, #2D3436 12%, transparent)'
  shadow_lg: '0 8px 24px color-mix(in srgb, #2D3436 16%, transparent)'
  border_radius: '12px'
component_variants:
  header: marketplace
  hero: product-showcase
  cards: product-card
  footer: marketplace-compact
```

---

### FASE 7 — Email Sequences MJML {#fase-7}

**Prioridad:** P1 ALTA | **Estimacion:** 20-25h | **Gaps:** G9, G18

**Descripcion:** Crear el servicio de secuencias de email y 6 templates MJML para el ciclo de vida del productor y consumidor AgroConecta. Renombrar/referenciar el directorio MJML existente `marketplace/` como `agroconecta/` para consistencia con el patron de los demas verticales.

#### 6.7.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaEmailSequenceService.php` | 6 secuencias. Metodos: `enroll(int $userId, string $sequenceKey)`, `ensureSequences()`, `getAvailableSequences()`. Patron: seguir `EmployabilityEmailSequenceService`. |
| `jaraba_email/templates/mjml/agroconecta/seq_onboarding_productor.mjml` | SEQ_AGRO_001: Bienvenida productor + primeros pasos + link a guia. Trigger: registro completado con avatar `productor`. |
| `jaraba_email/templates/mjml/agroconecta/seq_activacion.mjml` | SEQ_AGRO_002: "Tu primer producto esta listo, ahora comparte tu historia". Trigger: `first_product_published`. |
| `jaraba_email/templates/mjml/agroconecta/seq_reengagement.mjml` | SEQ_AGRO_003: "Tienes X nuevos pedidos sin responder" / "Tu tienda lleva 7 dias sin visitas". Trigger: 7 dias sin actividad. |
| `jaraba_email/templates/mjml/agroconecta/seq_upsell_starter.mjml` | SEQ_AGRO_004: Beneficios Starter (25 productos, 10% comision) + descuento primer mes. Trigger: 4o producto (80% del limite free) o 8o pedido. |
| `jaraba_email/templates/mjml/agroconecta/seq_upsell_pro.mjml` | SEQ_AGRO_005: Beneficios Profesional (ilimitados, QR, B2B) + caso de exito. Trigger: 20o producto (80% de Starter) o intento Partner Hub. |
| `jaraba_email/templates/mjml/agroconecta/seq_retention.mjml` | SEQ_AGRO_006: "Tu resumen del mes" + metricas ventas + sugerencias IA + cross-sell. Trigger: 30 dias suscrito. |

#### 6.7.2 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `jaraba_agroconecta_core/jaraba_agroconecta_core.module` | En `hook_entity_insert()`: si es `user` con vertical `agroconecta`, enrollar en `SEQ_AGRO_001`. |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_email_sequence`. |
| `jaraba_email/src/Service/TemplateLoaderService.php` | Registrar las 6 plantillas MJML del directorio `agroconecta/`. |

#### 6.7.3 Contenido de Secuencias

**SEQ_AGRO_001 (Onboarding Productor):**
- Asunto: `{% trans %}Bienvenido a AgroConecta — Tu tienda online esta lista{% endtrans %}`
- Contenido: 3 pasos para empezar (1. Sube tu primer producto, 2. Completa tu perfil de productor, 3. Comparte tu historia)
- CTA: `{% trans %}Subir mi primer producto{% endtrans %}` → `/agroconecta/producer/products/add`
- Color: `--ej-color-agro` primary, `--ej-color-accent` buttons

**SEQ_AGRO_004 (Upsell Starter):**
- Asunto: `{% trans %}Tu catalogo crece — Desbloquea mas con Starter{% endtrans %}`
- Contenido: Comparativa Free vs Starter (tabla), caso de exito de productor real, descuento 20% primer mes
- CTA: `{% trans %}Ampliar mi tienda{% endtrans %}` → `/billing/upgrade?plan=starter&vertical=agroconecta`

#### 6.7.4 Directrices

- **EMAIL-001**: Usar MJML, nunca plain HTML.
- **I18N-001**: Todos los textos en MJML con `{{ t('Texto') }}`.
- Todas las secuencias: `is_system = TRUE`, `is_active = TRUE`, `vertical = 'agroconecta'`.

---

### FASE 8 — CrossVerticalBridgeService {#fase-8}

**Prioridad:** P2 MEDIA | **Estimacion:** 15-18h | **Gap:** G10

**Descripcion:** Implementar puentes direccionales desde AgroConecta hacia otros verticales para maximizar el valor del cliente dentro del SaaS. Seguir el patron exacto de `EmployabilityCrossVerticalBridgeService`.

#### 6.8.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaCrossVerticalBridgeService.php` | 4 puentes outgoing. Metodos: `evaluateBridges(int $userId): array` (max 2, sorted by priority), `presentBridge(int $userId, string $bridgeId): array`, `trackBridgeResponse(int $userId, string $bridgeId, string $response): void`. Dismissed bridges en State: `agroconecta_bridge_dismissed_{userId}`. |

#### 6.8.2 Definicion de Puentes

| Bridge ID | Vertical Destino | Condicion | Prioridad | Icono | Color | CTA URL |
|-----------|-----------------|-----------|-----------|-------|-------|---------|
| `emprendimiento` | emprendimiento | `>50 ventas totales AND rating > 4.5` | 10 | `rocket` | `var(--ej-color-impulse)` | `/emprendimiento` |
| `fiscal` | fiscal | `plan >= profesional AND >20 ventas/mes` | 20 | `calculator` | `var(--ej-color-warning)` | `/fiscal/dashboard` |
| `formacion` | formacion | `>100 ventas totales` | 30 | `book` | `var(--ej-color-success)` | `/courses` |
| `empleabilidad` | empleabilidad | `journey_state = at_risk AND inactivo 30 dias` | 40 | `briefcase` | `var(--ej-color-innovation)` | `/empleabilidad` |

#### 6.8.3 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_cross_vertical_bridge`. |
| `jaraba_agroconecta_core/src/Controller/ProducerDashboardController.php` | Inyectar bridges en el contexto del dashboard productor (fail-open: si servicio no existe, no mostrar bridges). |
| `jaraba_agroconecta_core/templates/agro-producer-dashboard.html.twig` | Renderizar bridge cards con `jaraba_icon()` y `btn--outline`. |

#### 6.8.4 Puentes INCOMING (responsabilidad de otros verticales)

| Vertical Origen | Bridge -> AgroConecta | Responsable |
|----------------|-------------------|-------------|
| Emprendimiento | "Vende tus productos artesanales online" | `EmprendimientoCrossVerticalBridgeService` |
| Empleabilidad | "Emprendimiento rural: crea tu tienda" | `EmployabilityCrossVerticalBridgeService` |
| Fiscal | "Tus clientes productores pueden vender online" | Nuevo puente en fiscal |

---

### FASE 9 — JourneyProgressionService — Reglas Proactivas {#fase-9}

**Prioridad:** P0 CRITICA | **Estimacion:** 20-25h | **Gap:** G11

**Descripcion:** Crear el servicio de progresion proactiva con 8 reglas de IA que impulsan al productor por el embudo. La `AgroConectaJourneyDefinition` ya existe con 3 avatares — esta fase anade el motor de reglas que convierte la definicion en accion automatizada.

#### 6.9.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaJourneyProgressionService.php` | 8 reglas proactivas. Cache via State API (`agroconecta_proactive_pending_{userId}`, TTL 3600s). Metodos: `evaluate(int $userId): ?array`, `getPendingAction(int $userId): ?array`, `dismissAction(int $userId, string $ruleId): void`, `evaluateBatch(): int`. |

#### 6.9.2 Reglas Proactivas (Avatar `productor`)

| Rule ID | Estado | Condicion | Mensaje | CTA | Canal | Modo Copilot |
|---------|--------|-----------|---------|-----|-------|-------------|
| `inactivity_discovery` | discovery | 3 dias sin actividad | `{% trans %}Tu tienda esta casi lista. Sube tu primer producto y empieza a vender.{% endtrans %}` | `/agroconecta/producer/products/add` | `fab_dot` | `generate_description` |
| `incomplete_catalog` | activation | 1 producto, sin foto de portada | `{% trans %}Una buena foto multiplica tus ventas. Anade fotos a tu producto.{% endtrans %}` | `/agroconecta/producer/products` | `fab_expand` | `generate_description` |
| `first_sale_nudge` | activation | Productos publicados, 0 pedidos en 7 dias | `{% trans %}Comparte tu tienda en redes sociales para recibir tus primeros pedidos.{% endtrans %}` | Share link | `fab_badge` | `chat` |
| `review_response` | engagement | Resenas sin responder >3 dias | `{% trans %}Tienes resenas sin responder. Responde para mejorar tu reputacion.{% endtrans %}` | `/agroconecta/producer/dashboard#reviews` | `fab_dot` | `respond_review` |
| `pricing_optimization` | engagement | 5+ ventas, sin ajuste de precios | `{% trans %}Optimiza tus precios con IA. Nuestro copilot analiza el mercado por ti.{% endtrans %}` | Copilot `suggest_price` | `fab_expand` | `suggest_price` |
| `traceability_opportunity` | conversion | Plan Starter, >10 ventas, sin QR | `{% trans %}Anade trazabilidad QR a tus productos. Genera confianza y diferenciacion.{% endtrans %}` | Upgrade modal | `fab_expand` | `chat` |
| `upgrade_suggestion` | conversion | Plan free + 4 productos (80% limite) | `{% trans %}Tu catalogo esta casi lleno. Pasa a Starter y vende 25 productos.{% endtrans %}` | `/billing/upgrade` | `fab_expand` | `chat` |
| `b2b_expansion` | retention | >50 ventas + rating >4.5 | `{% trans %}Tus productos tienen calidad B2B. Abre el Hub de Distribuidores.{% endtrans %}` | Cross-vertical bridge | `fab_dot` | `chat` |

#### 6.9.3 Reglas Proactivas (Avatar `consumidor`)

| Rule ID | Estado | Condicion | Mensaje | Canal |
|---------|--------|-----------|---------|-------|
| `cart_abandoned` | activation | Carrito creado, sin checkout en 24h | `{% trans %}Tu carrito te espera. Completa tu pedido antes de que se agoten.{% endtrans %}` | `fab_dot` |
| `reorder_nudge` | retention | Ultimo pedido hace >30 dias | `{% trans %}Descubre las novedades de tus productores favoritos.{% endtrans %}` | `fab_badge` |

#### 6.9.4 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_journey_progression`. |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.routing.yml` | Anadir `jaraba_agroconecta.api.proactive`: `GET|POST /api/v1/copilot/agroconecta/proactive`. |
| `jaraba_agroconecta_core/src/Controller/ProducerDashboardController.php` | Anadir metodos `apiGetProactiveAction()` y `apiDismissProactiveAction()`. |

---

### FASE 10 — HealthScoreService — 5 Dimensiones + 8 KPIs {#fase-10}

**Prioridad:** P1 ALTA | **Estimacion:** 18-22h | **Gap:** G12

**Descripcion:** Implementar el servicio de health score con 5 dimensiones ponderadas especificas para el marketplace y 8 KPIs verticales, siguiendo el patron exacto de `EmployabilityHealthScoreService`.

#### 6.10.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaHealthScoreService.php` | Metodos: `calculateUserHealth(int $userId): array` (retorna `overall_score`, `category`, `dimensions`), `calculateVerticalKpis(): array` (retorna 8 KPIs con targets). |

#### 6.10.2 Dimensiones de Salud (5, total peso = 1.0)

| Dimension | Peso | Descripcion | Calculo |
|-----------|------|-------------|---------|
| `catalog_health` | 0.25 | Salud del catalogo | Productos activos (max 30) + fotos por producto (max 30) + descripciones completas (max 20) + certificaciones (max 20) |
| `sales_activity` | 0.30 | Actividad de ventas | Pedidos ultimo mes (max 40) + revenue trend (max 30) + conversion rate (max 30) |
| `customer_engagement` | 0.20 | Engagement con clientes | Resenas respondidas (max 40) + rating promedio (max 30) + tiempo respuesta (max 30) |
| `copilot_usage` | 0.10 | Uso del copilot IA | Usos copilot 7 dias (max 50) + acciones ejecutadas (max 50) |
| `marketplace_presence` | 0.15 | Presencia en marketplace | Trazabilidad QR activa (max 30) + Partner Hub B2B (max 30) + shipping zones (max 20) + social shares (max 20) |

#### 6.10.3 Categorias de Salud

| Score | Categoria | Significado |
|-------|-----------|-------------|
| >= 80 | `healthy` | Productor activo y rentable |
| >= 60 | `neutral` | Ventas regulares, margen de mejora |
| >= 40 | `at_risk` | Desengagement detectado |
| < 40 | `critical` | Alto riesgo de abandono |

#### 6.10.4 KPIs del Vertical (8)

| KPI Key | Target | Unidad | Direccion | Label |
|---------|--------|--------|-----------|-------|
| `gmv_monthly` | 50000 | EUR | higher | `{% trans %}Volumen de negocio mensual{% endtrans %}` |
| `producer_activation_rate` | 60 | % | higher | `{% trans %}Tasa de activacion (primer producto en 7 dias){% endtrans %}` |
| `order_completion_rate` | 85 | % | higher | `{% trans %}Tasa de pedidos completados{% endtrans %}` |
| `review_response_rate` | 70 | % | higher | `{% trans %}Tasa de respuesta a resenas{% endtrans %}` |
| `nps` | 55 | score | higher | `{% trans %}NPS del marketplace{% endtrans %}` |
| `arpu` | 35 | EUR/mes | higher | `{% trans %}ARPU por productor{% endtrans %}` |
| `conversion_free_paid` | 15 | % | higher | `{% trans %}Conversion Free a Pago{% endtrans %}` |
| `churn_rate` | 5 | % | lower | `{% trans %}Tasa de baja mensual{% endtrans %}` |

#### 6.10.5 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_health_score`. |
| `jaraba_agroconecta_core/src/Controller/ProducerDashboardController.php` | Inyectar health score en el contexto del dashboard productor. |
| `jaraba_agroconecta_core/templates/agro-producer-dashboard.html.twig` | Renderizar widget de health score con SVG ring + dimensiones. |

---

### FASE 11 — ExperimentService — A/B Testing {#fase-11}

**Prioridad:** P3 BAJA | **Estimacion:** 12-15h | **Gap:** G13

**Descripcion:** Implementar servicio de experimentacion A/B para el vertical AgroConecta, permitiendo testear variaciones en precios, descripciones, layouts y CTAs.

#### 6.11.1 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AgroConectaExperimentService.php` | Servicio de A/B testing. Metodos: `getVariant(int $userId, string $experimentKey): string`, `trackConversion(int $userId, string $experimentKey, string $variant): void`, `getResults(string $experimentKey): array`. Experimentos iniciales: `checkout_cta_color`, `product_card_layout`, `pricing_page_variant`. |

#### 6.11.2 Experimentos Iniciales

| Experiment Key | Variantes | Metrica de Conversion |
|---------------|-----------|----------------------|
| `checkout_cta_color` | A: `--ej-color-agro` / B: `--ej-color-accent` | `order_completed` |
| `product_card_layout` | A: vertical / B: horizontal | `add_to_cart` |
| `pricing_page_variant` | A: 3 columnas / B: slider interactivo | `plan_upgrade` |
| `landing_hero_copy` | A: "Vende tus productos" / B: "Conecta con tu mercado" | `registration_started` |

#### 6.11.3 Archivos a Modificar

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `ecosistema_jaraba_core.agroconecta_experiment`. |

---

### FASE 12 — Avatar Navigation + Funnel Analytics {#fase-12}

**Prioridad:** P1 ALTA | **Estimacion:** 15-18h | **Gaps:** G15, G16

**Descripcion:** Completar la integracion de AgroConecta en el sistema de navegacion global por avatar (actualmente solo 1 entrada basica para `producer`) y crear las definiciones de funnel para tracking de conversion.

#### 6.12.1 Avatar Navigation

**Archivos a Modificar:**

| Archivo | Modificacion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | **Avatar `producer` (ampliar):** Anadir items: `/agroconecta/producer/dashboard` (Mi Tienda), `/agroconecta/producer/orders` (Pedidos), `/agroconecta/producer/products` (Productos), `/agroconecta/producer/settings` (Configuracion). **Nuevo avatar `buyer`:** items: `/agroconecta` (Marketplace), `/agroconecta/customer/dashboard` (Mis Pedidos), `/agroconecta/customer/favorites` (Favoritos). **Anonymous navigation:** Anadir `ecosistema_jaraba_core.landing.agroconecta` como item visible. **Cross-vertical:** Si productor con >50 ventas → ofrecer `/fiscal/dashboard`. |

#### 6.12.2 Funnel Analytics

**Archivos a Crear:**

| Archivo | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_producer_acquisition.yml` | Funnel de adquisicion productor: `landing_visit -> guia_download -> registration -> first_product_upload -> first_product_published`. Conversion window: 168h (7 dias). |
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_producer_activation.yml` | Funnel de activacion productor: `registration -> first_product -> first_photo -> first_order_received -> first_sale_completed`. Conversion window: 336h (14 dias). |
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_monetization.yml` | Funnel de monetizacion: `product_limit_reached -> upgrade_modal_shown -> checkout_started -> payment_completed`. Conversion window: 72h (3 dias). |
| `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_consumer_purchase.yml` | Funnel de compra consumidor: `marketplace_visit -> product_view -> add_to_cart -> checkout_started -> order_completed`. Conversion window: 48h (2 dias). |

#### 6.12.3 Tracking Events

| Evento | Trigger | Datos |
|--------|---------|-------|
| `agro_landing_visit` | Landing page view | `source`, `medium`, `avatar` |
| `agro_guia_download` | Descarga guia PDF | `email_captured` |
| `agro_registration` | Registro | `plan`, `avatar` |
| `agro_product_created` | Producto creado | `category`, `has_photo`, `has_certification` |
| `agro_product_published` | Producto publicado | `product_count` |
| `agro_order_received` | Pedido recibido (productor) | `order_value`, `items_count` |
| `agro_order_completed` | Pedido completado (consumidor) | `order_value`, `payment_method` |
| `agro_review_submitted` | Resena enviada | `rating`, `has_text` |
| `agro_copilot_used` | Uso de copilot IA | `action`, `mode` |
| `agro_upgrade_modal` | Modal upgrade mostrado | `trigger`, `current_plan` |
| `agro_upgrade_completed` | Upgrade completado | `from_plan`, `to_plan` |
| `agro_cart_created` | Carrito creado | `items_count`, `cart_value` |
| `agro_cart_abandoned` | Carrito abandonado | `hours_since_creation`, `cart_value` |

---

### FASE 13 — QA Integral + Tests + Documentacion {#fase-13}

**Prioridad:** P2 MEDIA | **Estimacion:** 25-30h | **Gaps:** QA

**Descripcion:** Fase de cierre con tests unitarios, de integracion y documentacion completa.

#### 6.13.1 Tests a Crear

| Test | Tipo | Cobertura |
|------|------|-----------|
| `AgroConectaFeatureGateServiceTest.php` | Unit | `check()`, `recordUsage()`, plan limits, 5 feature keys |
| `AgroConectaJourneyProgressionServiceTest.php` | Unit | 8+2 reglas proactivas, cache, dismiss |
| `AgroConectaHealthScoreServiceTest.php` | Unit | 5 dimensiones, categorias, 8 KPIs |
| `AgroConectaEmailSequenceServiceTest.php` | Unit | 6 secuencias, enroll, ensure |
| `AgroConectaCrossVerticalBridgeServiceTest.php` | Unit | 4 bridges, max 2, dismiss |
| `AgroConectaCopilotBridgeServiceTest.php` | Unit | Context injection, soft upsell, market insights |
| `AgroConectaExperimentServiceTest.php` | Unit | Variantes, conversiones, resultados |
| `AgroConectaFeatureGateKernelTest.php` | Kernel | DB table creation, usage recording, monthly reset |
| `AgroConectaZeroRegionTest.php` | Functional | page--agroconecta.html.twig rendering, clean_content, body classes |

#### 6.13.2 Documentacion a Actualizar

| Documento | Actualizacion |
|-----------|-------------|
| `docs/00_DIRECTRICES_PROYECTO.md` | Incrementar version. Anadir reglas AgroConecta en seccion 5.8.x. Changelog. |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Incrementar version. Actualizar modulos en seccion 7.1. Changelog. |
| `docs/00_INDICE_GENERAL.md` | Incrementar version. Blockquote + entrada registro cambios. |
| `docs/tecnicos/aprendizajes/2026-02-17_agroconecta_elevacion_clase_mundial.md` | Nuevo aprendizaje documentando las 14 fases, patrones, y reglas descubiertas. |

#### 6.13.3 Items Futuros (Post-Clase-Mundial)

| Item | Prioridad | Descripcion |
|------|-----------|-------------|
| `jaraba_credentials_agroconecta` | P3 | Submódulo de credenciales (certificacion productor local verificado) |
| Fases 5-9 marketplace (v3) | P0 | Plan existente `20260208-Plan_AgroConecta_WorldClass_v3.md` (Commerce Intelligence, Logistics, AI Agents, Admin, Mobile PWA) |
| Stripe Price IDs | P1 (Ops) | Crear productos en Stripe para los 3 planes AgroConecta |
| WhatsApp Business API | P1 (Ops) | Configurar API en produccion para SalesAgent |

---

## 7. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion Tecnica (docs/tecnicos/) | Fase(s) Afectadas | Estado |
|----------------------------------------|-------------------|--------|
| Doc 48 — AgroConecta Commerce Core (Productos, Productores, Certificaciones) | Base | Implementado (F1) |
| Doc 49 — AgroConecta Orders & Payments | Base | Implementado (F2) |
| Doc 51 — AgroConecta Shipping Core | Referencia F4 | Implementado |
| Doc 52 — AgroConecta Producer Profile | Base | Implementado (F1) |
| Doc 54 — AgroConecta Reviews | Base | Implementado (F4) |
| Doc 55 — AgroConecta Search & Discovery | Referencia F4 | Implementado |
| Doc 56 — AgroConecta Promotions & Coupons | Referencia F1 | Implementado |
| Doc 57 — AgroConecta Analytics Dashboard | Referencia F10, F12 | Implementado |
| Doc 58 — AgroConecta Admin Panel | Referencia F4 | Planificado (v3) |
| Doc 59 — AgroConecta Notifications | Referencia F7, F9 | Implementado |
| Doc 60 — AgroConecta Mobile PWA | Futuro | Planificado (v3) |
| Doc 67 — AgroConecta Producer Copilot | Referencia F2 | Implementado |
| Doc 68 — AgroConecta Sales Agent | Referencia F2, F9 | Implementado |
| Doc 80 — AgroConecta Traceability | Referencia F0, F1 | Implementado |
| Doc 81 — AgroConecta QR Codes | Referencia F0, F1 | Implementado |
| Doc 82 — AgroConecta Partner Hub B2B | Referencia F0, F1 | Implementado |
| `20260208-Plan_AgroConecta_WorldClass_v3.md` | Todas | Plan de Fases 5-9 marketplace (complementario) |
| `20260208-Plan_Implementacion_AgroConecta_v1.md` | Todas | Plan original marketplace |
| `2026-02-16_zero_region_template_pattern.md` (Aprendizaje #88) | F3, F4 | Zero-region mandatory hooks |
| `2026-02-15_empleabilidad_elevacion_10_fases.md` (Aprendizaje) | Todas | Blueprint de 10 fases |
| `2026-02-15_emprendimiento_paridad_empleabilidad_7_gaps.md` (Aprendizaje) | Todas | Checklist de paridad |
| `2026-02-12_phase4_scss_colors_emoji_cleanup.md` (Aprendizaje) | F5 | P4-COLOR-001/002/003, P4-EMOJI-001/002 |
| `2026-02-13_avatar_navigation_contextual.md` (Aprendizaje) | F12 | NAV-001 a NAV-005 |
| `2026-02-05_arquitectura_theming_saas_master.md` (Arquitectura) | F5, F6 | 5-Layer Federated Design Tokens |
| `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` | Todas | 3 niveles customizacion, Feature Access pattern |
| `DIRECTRICES_DESARROLLO.md` | Todas | Pre-commit checklist |
| `00_FLUJO_TRABAJO_CLAUDE.md` v2.0.0 | Todas | Workflow de sesion + patron elevacion 14 fases |

---

## 8. Cumplimiento de Directrices

| Directriz | Como se Cumple |
|-----------|---------------|
| **I18N-001** | Todos los textos con `{% trans %}...{% endtrans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS. |
| **SCSS-001** | `@use` (no `@import`). Variables con `var(--ej-color-agro, fallback)`. `color-mix()` para variantes. |
| **P4-COLOR-001/002/003** | Solo paleta Jaraba. AgroConecta usa primary `#556B2F` (olive), accent `#FF8C42` (orange), secondary `#2D3436` (dark). Variantes con `color-mix()`. |
| **P4-EMOJI-001/002** | `jaraba_icon()` en Twig. Unicode text escapes en SCSS `content:`. Nunca emojis color. |
| **ZERO-REGION-001/002/003** | Variables via `hook_preprocess_page()` [NUEVO F4]. Controller retorna `['#type' => 'markup', '#markup' => '']`. 3 hooks obligatorios. |
| **LEGAL-GATE-001** | Todo servicio con limites usa `AgroConectaFeatureGateService::check()` [NUEVO F0]. |
| **LEGAL-BODY-001** | Body classes via `hook_preprocess_html()` [YA EXISTE, verificar F3]. NUNCA `attributes.addClass()` en template. |
| **VERTICAL-ELEV-001-005** | Config seeds ya implementados (vertical config + 15 FreemiumVerticalLimit). CSS custom props en `:root`. |
| **NAV-001-005** | Servicio centralizado AvatarNavigationService [AMPLIAR F12]. Variables en header, no en cada template. `try/catch` en `Url::fromRoute()`. |
| **ENTITY-001** | Content Entities con `EntityOwnerInterface`, `EntityChangedInterface`, `tenant_id` como `entity_reference`. [YA IMPLEMENTADO en 33+ entities]. |
| **DRUPAL11-001** | No redeclarar propiedades tipadas heredadas. No promoted constructor params para propiedades heredadas. |
| **AUDIT-CONS-001** | AccessControlHandler en cada Content Entity [YA IMPLEMENTADO]. |
| **AUDIT-PERF-001** | DB indexes en `tenant_id` + campos frecuentes [YA IMPLEMENTADO]. |
| **Dart Sass moderno** | `package.json` con `sass ^1.71.0`. Compilacion `--style=compressed`. `@use` con scope aislado. [YA IMPLEMENTADO]. |
| **Frontend limpio** | Sin `page.content` [NUEVO F4], sin bloques heredados. Layout full-width mobile-first. |
| **Modal CRUD** | `data-dialog-type="modal"` con `drupal.dialog.ajax` [AUDITAR F4]. |
| **Templates parciales** | Parciales con `_` prefix, incluidos via `{% include %}`. [YA IMPLEMENTADO]. |
| **Configuracion sin codigo** | Variables de tema configurables desde UI. `color_agro` configurable [YA IMPLEMENTADO]. |
| **Tenant sin admin** | Tenant no accede al tema de administracion. Solo rutas frontend limpias [YA IMPLEMENTADO]. |
| **Content Entities con Field UI + Views** | Navegacion en `/admin/structure` y `/admin/content` para todas las entidades [YA IMPLEMENTADO]. |
| **declare(strict_types=1)** | Obligatorio en todo fichero PHP nuevo. Verificar en existentes [F13]. |
| **Rate limiting** | En toda operacion costosa (AI calls, exports, bulk ops). Copilot uses gated [F0]. |
| **@ai.provider** | Obligatorio para llamadas IA. ProducerCopilotAgent y SalesAgent ya lo usan via SmartBaseAgent. |

---

## 9. Checklist Pre-Commit por Fase

```
FASE 0 (FeatureGate):
- [ ] AgroConectaFeatureGateService registrado en services.yml
- [ ] Tabla {agroconecta_feature_usage} creada en install hook
- [ ] ProductAgroService::create() llama a FeatureGate check('products')
- [ ] OrderService::create() llama a FeatureGate check('orders_per_month')
- [ ] ProducerCopilotService llama a FeatureGate check('copilot_uses_per_month')
- [ ] 12 FreemiumVerticalLimit configs adicionales creados
- [ ] getAvailableAddons() incluye jaraba_agroconecta_core

FASE 1 (UpgradeTriggers):
- [ ] 8 tipos de trigger registrados en UpgradeTriggerService
- [ ] fire() se llama despues de cada denied en FeatureGateService
- [ ] Mensajes de trigger con $this->t() (i18n)
- [ ] CTA URLs apuntan a /billing/upgrade con parametros correctos

FASE 4 (Zero-Region):
- [ ] page--agroconecta.html.twig en ecosistema_jaraba_theme/templates/
- [ ] hook_preprocess_page() implementado en .module
- [ ] hook_theme_suggestions_page_alter() implementado en .module
- [ ] clean_content se renderiza en todas las rutas frontend
- [ ] Copilot FAB incluido via _header.html.twig
- [ ] Modal CRUD auditado en todos los templates (data-dialog-type)
- [ ] Library agroconecta.modal-actions registrada con core/drupal.dialog.ajax

FASE 5 (SCSS):
- [ ] 0 instancias de rgba() bare en todos los 17 SCSS partials
- [ ] color-mix() usado para todas las variantes de color
- [ ] var(--ej-*) con fallbacks correctos
- [ ] npm run build + drush cr exitosos

FASE 7 (Email):
- [ ] 6 templates MJML en jaraba_email/templates/mjml/agroconecta/
- [ ] AgroConectaEmailSequenceService registrado en services.yml
- [ ] Enrollment automatico en hook_entity_insert()
- [ ] Todos los textos con {{ t('...') }}

FASE 9 (Journey Progression):
- [ ] AgroConectaJourneyProgressionService con 10 reglas (8 productor + 2 consumidor)
- [ ] API /api/v1/copilot/agroconecta/proactive
- [ ] Cache State API con TTL 3600s
- [ ] Dismiss action funcional

FASE 10 (Health Score):
- [ ] AgroConectaHealthScoreService con 5 dimensiones (sum pesos = 1.0)
- [ ] 8 KPIs con targets y direccion
- [ ] Widget SVG ring en producer dashboard

FASE 12 (Navigation + Funnels):
- [ ] Avatar producer ampliado con 4 items deep link
- [ ] Nuevo avatar buyer con 3 items
- [ ] Anonymous navigation incluye agroconecta landing
- [ ] 4 FunnelDefinition configs creados
```

---

## 10. Inventario de Archivos a Crear/Modificar

### 10.1 Archivos a CREAR (40+ nuevos)

| # | Archivo | Fase |
|---|---------|------|
| 1 | `ecosistema_jaraba_core/src/Service/AgroConectaFeatureGateService.php` | F0 |
| 2 | Schema update en `ecosistema_jaraba_core.install` (tabla `{agroconecta_feature_usage}`) | F0 |
| 3 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_traceability_qr.yml` | F0 |
| 4 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_starter_traceability_qr.yml` | F0 |
| 5 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_profesional_traceability_qr.yml` | F0 |
| 6 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_partner_hub.yml` | F0 |
| 7 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_starter_partner_hub.yml` | F0 |
| 8 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_profesional_partner_hub.yml` | F0 |
| 9 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_analytics_advanced.yml` | F0 |
| 10 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_starter_analytics_advanced.yml` | F0 |
| 11 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_profesional_analytics_advanced.yml` | F0 |
| 12 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_demand_forecaster.yml` | F0 |
| 13 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_starter_demand_forecaster.yml` | F0 |
| 14 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_profesional_demand_forecaster.yml` | F0 |
| 15 | `jaraba_agroconecta_core/src/Service/AgroConectaCopilotBridgeService.php` | F2 |
| 16 | `ecosistema_jaraba_theme/templates/page--agroconecta.html.twig` | F4 |
| 17 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.vertical_agroconecta.yml` | F6 |
| 18 | `ecosistema_jaraba_core/src/Service/AgroConectaEmailSequenceService.php` | F7 |
| 19 | `jaraba_email/templates/mjml/agroconecta/seq_onboarding_productor.mjml` | F7 |
| 20 | `jaraba_email/templates/mjml/agroconecta/seq_activacion.mjml` | F7 |
| 21 | `jaraba_email/templates/mjml/agroconecta/seq_reengagement.mjml` | F7 |
| 22 | `jaraba_email/templates/mjml/agroconecta/seq_upsell_starter.mjml` | F7 |
| 23 | `jaraba_email/templates/mjml/agroconecta/seq_upsell_pro.mjml` | F7 |
| 24 | `jaraba_email/templates/mjml/agroconecta/seq_retention.mjml` | F7 |
| 25 | `ecosistema_jaraba_core/src/Service/AgroConectaCrossVerticalBridgeService.php` | F8 |
| 26 | `ecosistema_jaraba_core/src/Service/AgroConectaJourneyProgressionService.php` | F9 |
| 27 | `ecosistema_jaraba_core/src/Service/AgroConectaHealthScoreService.php` | F10 |
| 28 | `ecosistema_jaraba_core/src/Service/AgroConectaExperimentService.php` | F11 |
| 29 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_producer_acquisition.yml` | F12 |
| 30 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_producer_activation.yml` | F12 |
| 31 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_monetization.yml` | F12 |
| 32 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.agroconecta_consumer_purchase.yml` | F12 |
| 33 | `tests/src/Unit/AgroConectaFeatureGateServiceTest.php` | F13 |
| 34 | `tests/src/Unit/AgroConectaJourneyProgressionServiceTest.php` | F13 |
| 35 | `tests/src/Unit/AgroConectaHealthScoreServiceTest.php` | F13 |
| 36 | `tests/src/Unit/AgroConectaEmailSequenceServiceTest.php` | F13 |
| 37 | `tests/src/Unit/AgroConectaCrossVerticalBridgeServiceTest.php` | F13 |
| 38 | `tests/src/Unit/AgroConectaCopilotBridgeServiceTest.php` | F13 |
| 39 | `tests/src/Unit/AgroConectaExperimentServiceTest.php` | F13 |
| 40 | `tests/src/Kernel/AgroConectaFeatureGateKernelTest.php` | F13 |
| 41 | `tests/src/Functional/AgroConectaZeroRegionTest.php` | F13 |
| 42 | `docs/tecnicos/aprendizajes/2026-02-17_agroconecta_elevacion_clase_mundial.md` | F13 |

### 10.2 Archivos a MODIFICAR (20+)

| # | Archivo | Fase(s) |
|---|---------|---------|
| 1 | `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | F0, F7, F8, F9, F10, F11 |
| 2 | `ecosistema_jaraba_core/ecosistema_jaraba_core.install` | F0 |
| 3 | `jaraba_agroconecta_core/src/Service/ProductAgroService.php` | F0, F1 |
| 4 | `jaraba_agroconecta_core/src/Service/OrderService.php` | F0 |
| 5 | `jaraba_agroconecta_core/src/Service/ProducerCopilotService.php` | F0, F2 |
| 6 | `jaraba_agroconecta_core/src/Service/StripePaymentService.php` | F0 |
| 7 | `jaraba_billing/src/Service/FeatureAccessService.php` | F0 |
| 8 | `jaraba_billing/src/Service/UpgradeTriggerService.php` | F1 |
| 9 | `jaraba_ai_agents/src/Agent/BaseAgent.php` | F2 |
| 10 | `jaraba_ai_agents/jaraba_ai_agents.services.yml` | F2 |
| 11 | `jaraba_agroconecta_core/jaraba_agroconecta_core.services.yml` | F2 |
| 12 | `jaraba_agroconecta_core/jaraba_agroconecta_core.module` | F3, F4, F7 |
| 13 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | F3, F4 |
| 14 | `ecosistema_jaraba_theme/templates/page--agroconecta.html.twig` | F4 (CREAR) |
| 15 | `jaraba_agroconecta_core/jaraba_agroconecta_core.libraries.yml` | F4 |
| 16 | 17 SCSS partials en `jaraba_agroconecta_core/scss/` | F5 |
| 17 | `jaraba_email/src/Service/TemplateLoaderService.php` | F7 |
| 18 | `jaraba_agroconecta_core/src/Controller/ProducerDashboardController.php` | F8, F9, F10 |
| 19 | `jaraba_agroconecta_core/templates/agro-producer-dashboard.html.twig` | F8, F10 |
| 20 | `jaraba_agroconecta_core/jaraba_agroconecta_core.routing.yml` | F9 |
| 21 | `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | F12 |
| 22 | Templates CRUD (6 archivos de templates con enlaces) | F4 |
| 23 | `docs/00_DIRECTRICES_PROYECTO.md` | F13 |
| 24 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | F13 |
| 25 | `docs/00_INDICE_GENERAL.md` | F13 |

---

## Estimacion Total por Fases

| Fase | Nombre | Prioridad | Horas | Coste (45 EUR/h) |
|------|--------|-----------|-------|-------------|
| F0 | FeatureGateService + Enforcement | P0 | 20-25h | 900-1.125 EUR |
| F1 | UpgradeTriggers + Upsell IA | P0 | 15-18h | 675-810 EUR |
| F2 | CopilotBridgeService + BaseAgent | P1 | 15-18h | 675-810 EUR |
| F3 | hook_preprocess_html Consolidacion | P0 | 5-8h | 225-360 EUR |
| F4 | Zero-Region Template + Modal CRUD | P0 | 15-18h | 675-810 EUR |
| F5 | SCSS Compliance + Auditoria | P1 | 10-12h | 450-540 EUR |
| F6 | Design Token Config Entity | P2 | 5-8h | 225-360 EUR |
| F7 | Email Sequences MJML (6 templates) | P1 | 20-25h | 900-1.125 EUR |
| F8 | CrossVerticalBridgeService | P2 | 15-18h | 675-810 EUR |
| F9 | JourneyProgressionService (10 reglas) | P0 | 20-25h | 900-1.125 EUR |
| F10 | HealthScoreService (5 dim + 8 KPIs) | P1 | 18-22h | 810-990 EUR |
| F11 | ExperimentService (A/B Testing) | P3 | 12-15h | 540-675 EUR |
| F12 | Avatar Navigation + Funnel Analytics | P1 | 15-18h | 675-810 EUR |
| F13 | QA, Tests, Documentacion | P2 | 25-30h | 1.125-1.350 EUR |
| **TOTAL** | | | **210-260h** | **9.450-11.700 EUR** |

---

**Nota**: Este plan es complementario al plan existente `20260208-Plan_AgroConecta_WorldClass_v3.md` que cubre las Fases 5-9 de funcionalidad marketplace (Commerce Intelligence, Logistics, AI Agents, Admin, Mobile PWA). El presente plan cubre exclusivamente la **infraestructura de elevacion a clase mundial** (embudo de ventas, monetizacion, engagement, expansion) que es ortogonal a la funcionalidad de negocio.
