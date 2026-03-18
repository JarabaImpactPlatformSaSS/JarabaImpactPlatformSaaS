# PLAN DE COHERENCIA: PRICING vs SERVICIOS ENTREGADOS
# Lo que se promete vs lo que el usuario recibe
# Fecha: 2026-03-18 | Version: 1.0
# Basado en: Doc 181 (Mentoring Desacople), Doc 158 v3 (Pricing Matrix corregida)
# Cruce: Codebase real (94 modulos, 1.131 servicios) vs promesas comerciales
# Autor: Claude Opus 4.6 (1M context) — auditoria de coherencia

---

## INDICE DE NAVEGACION (TOC)

1. [Resumen Ejecutivo — El Problema Central](#1-resumen-ejecutivo--el-problema-central)
2. [Diagnostico: 12 Incoherencias Sistémicas](#2-diagnostico-12-incoherencias-sistemicas)
   - 2.1 [Mentoring incluido en planes = pérdida económica](#21-mentoring-incluido-en-planes--perdida-economica)
   - 2.2 [Stripe Price IDs vacíos = checkout roto](#22-stripe-price-ids-vacios--checkout-roto)
   - 2.3 [FairUsePolicy sin config = sin enforcement](#23-fairusepolicy-sin-config--sin-enforcement)
   - 2.4 [Marketplace prometido con 0 oferta](#24-marketplace-prometido-con-0-oferta)
   - 2.5 [Matching Engine sin masa crítica](#25-matching-engine-sin-masa-critica)
   - 2.6 [Soporte dedicado con 0 equipo](#26-soporte-dedicado-con-0-equipo)
   - 2.7 [Jitsi sin infraestructura](#27-jitsi-sin-infraestructura)
   - 2.8 [Cursos propios inexistentes](#28-cursos-propios-inexistentes)
   - 2.9 [White Label inviable en Enterprise](#29-white-label-inviable-en-enterprise)
   - 2.10 [AI Copilot ausente en Starter](#210-ai-copilot-ausente-en-starter)
   - 2.11 [Early Adopter "forever" = anti-económico](#211-early-adopter-forever--anti-economico)
   - 2.12 [Comisiones por plan = incentivo perverso](#212-comisiones-por-plan--incentivo-perverso)
3. [Sprint 1 — Modelo de 3 Pilares (Doc 181 + 158 v3)](#3-sprint-1--modelo-de-3-pilares-doc-181--158-v3)
   - 3.1 [Pilar 1: Plan Base SaaS (subscription)](#31-pilar-1-plan-base-saas-subscription)
   - 3.2 [Pilar 2: Add-ons Marketing (subscription)](#32-pilar-2-add-ons-marketing-subscription)
   - 3.3 [Pilar 3: Servicios Profesionales (one-time payment)](#33-pilar-3-servicios-profesionales-one-time-payment)
4. [Sprint 2 — Correcciones Críticas de Código](#4-sprint-2--correcciones-criticas-de-codigo)
   - 4.1 [Stripe Price IDs: sincronizar con Stripe Dashboard](#41-stripe-price-ids-sincronizar-con-stripe-dashboard)
   - 4.2 [FairUsePolicy: crear ConfigEntities por tier](#42-fairusepolicy-crear-configentities-por-tier)
   - 4.3 [Mentoring: eliminar de planes, crear catálogo servicios](#43-mentoring-eliminar-de-planes-crear-catalogo-servicios)
   - 4.4 [AI Copilot: añadir a todos los Starter](#44-ai-copilot-anadir-a-todos-los-starter)
5. [Sprint 3 — Entidades Nuevas (professional_service + service_booking)](#5-sprint-3--entidades-nuevas-professional_service--service_booking)
   - 5.1 [Entity professional_service](#51-entity-professional_service)
   - 5.2 [Entity service_booking](#52-entity-service_booking)
   - 5.3 [ProfessionalServiceManager](#53-professionalservicemanager)
6. [Sprint 4 — Reframing UX (Honestidad en Landings y Pricing)](#6-sprint-4--reframing-ux-honestidad-en-landings-y-pricing)
   - 6.1 [Marketplace → "Tu tienda propia" + bonus](#61-marketplace--tu-tienda-propia--bonus)
   - 6.2 [Matching → "Se activa con +20 ofertas"](#62-matching--se-activa-con-20-ofertas)
   - 6.3 [Soporte → niveles honestos](#63-soporte--niveles-honestos)
   - 6.4 [Jitsi → Meet/Zoom links fase 0](#64-jitsi--meetzoom-links-fase-0)
   - 6.5 [White Label → add-on separado](#65-white-label--add-on-separado)
7. [Sprint 5 — ECA Flows y Automatización](#7-sprint-5--eca-flows-y-automatizacion)
8. [Tabla de Correspondencia: Especificaciones Técnicas](#8-tabla-de-correspondencia-especificaciones-tecnicas)
9. [Tabla de Cumplimiento: Directrices del Proyecto](#9-tabla-de-cumplimiento-directrices-del-proyecto)
10. [Reglas de Negocio v3 (Las 3 Reglas Fundamentales)](#10-reglas-de-negocio-v3-las-3-reglas-fundamentales)
11. [Impacto Económico: v2 vs v3](#11-impacto-economico-v2-vs-v3)
12. [Cronograma y Dependencias](#12-cronograma-y-dependencias)
13. [Criterios de Aceptación](#13-criterios-de-aceptacion)

---

## 1. RESUMEN EJECUTIVO — EL PROBLEMA CENTRAL

### Lo que se promete vs lo que el usuario recibe

La auditoría cruzada entre los documentos de pricing (Doc 158 v2/v3), la especificación de desacople de mentoring (Doc 181) y el codebase real revela **12 incoherencias sistémicas** entre lo que el SaaS promete comercialmente y lo que realmente puede entregar el día 0.

**El problema más grave:** El modelo de pricing v2 incluía horas de trabajo humano (mentoring 1:1) dentro de planes SaaS a precio fijo. Con un Enterprise a 199 EUR/mes que prometía 4h/mes de mentoring individual (coste real: 750 EUR/mes), **cada cliente Enterprise generaba una pérdida de -551 EUR/mes**. A escala, 50 Pro + 20 Enterprise = **-9.070 EUR/mes de pérdidas**.

**La solución (Doc 181 + 158 v3):** Separar el modelo en 3 pilares independientes:

| Pilar | Stripe | Escala | Margen |
|-------|--------|--------|--------|
| **Plan Base SaaS** | Subscription mensual/anual | Ilimitada (software) | >90% |
| **Add-ons Marketing** | Subscription mensual | Ilimitada (software) | >85% |
| **Servicios Profesionales** | One-time payment | Limitada (horas humanas) | >70% |

**Regla #0 (Doc 158 v3):** NUNCA incluir horas de trabajo humano en un plan SaaS.

### Estado técnico del codebase

| Componente | Estado | Severidad |
|------------|--------|-----------|
| Stripe Price IDs en SaasPlan | **Vacíos** — checkout no funciona | P0 |
| FairUsePolicy ConfigEntities | **No creadas** — sin enforcement | P0 |
| Mentoring en planes | **Incluido** — pérdida económica | P0 |
| AI Copilot en Starter | **Ausente** — diferenciador perdido | P1 |
| Marketplace | **0 oferta** — promesa vacía | P1 |
| Soporte dedicado | **0 equipo** — promesa incumplible | P1 |
| professional_service entity | **No existe** — Doc 181 pendiente | P1 |
| service_booking entity | **No existe** — Doc 181 pendiente | P1 |

---

## 2. DIAGNOSTICO: 12 INCOHERENCIAS SISTÉMICAS

### 2.1 Mentoring incluido en planes = pérdida económica

**Promesa (Doc 158 v2):** Plan Pro incluye 2h/mes mentoring grupal. Enterprise incluye 4h/mes mentoring individual.

**Realidad económica (Doc 181):**
- 1 sesión individual = 1.25h real (45 min sesión + 30 min preparación)
- Coste por sesión = 1.25h × 150 EUR/h = 187.50 EUR
- Enterprise 4h/mes = 4 sesiones = 750 EUR coste
- Precio Enterprise = 199 EUR/mes
- **Pérdida neta: -551 EUR/mes por cliente Enterprise**

**Realidad del codebase:**
- `SubscriptionContextService::FEATURE_LABELS` incluye `mentoring_1a1` como feature
- `LIMIT_LABELS` incluye `mentoring_sessions_monthly` con valores por tier
- `jaraba_mentoring` módulo existe (40+ archivos) pero sin enforcement de límites

**Solución (Doc 181 + 158 v3):**
- ELIMINAR mentoring individual de todos los planes SaaS
- CREAR catálogo de Servicios Profesionales con pago one-time (175-2.950 EUR)
- MANTENER Mastermind grupal en Pro/Enterprise (90 min, 8 personas, 1x/mes — margen +567 EUR/sesión)

**Implementación técnica:**
- Actualizar SaasPlan features: eliminar `mentoring_1a1` de Pro/Enterprise
- Añadir `mastermind_grupal` como feature en Pro/Enterprise
- Crear entities `professional_service` y `service_booking` (Sprint 3)
- Actualizar `SubscriptionContextService::FEATURE_LABELS` con cambios

---

### 2.2 Stripe Price IDs vacíos = checkout roto

**Promesa:** Usuario puede comprar planes desde `/planes/checkout/{plan}`.

**Realidad del codebase:**
- `CheckoutController::checkoutPage()` línea 78-83:
  ```php
  $priceId = $cycle === 'yearly'
    ? $saas_plan->getStripePriceYearlyId()   // → '' (vacío)
    : $saas_plan->getStripePriceId();         // → '' (vacío)
  $stripeReady = !empty($priceId);             // → FALSE
  ```
- Resultado: usuario ve "Configuración pendiente" en vez de formulario de pago
- **El checkout no funciona para NINGÚN plan**

**Solución:**
- Ejecutar `StripeProductSyncService::syncAll()` para crear Products + Prices en Stripe
- Guardar Price IDs de vuelta en SaasPlan entities
- Verificar que `$stripeReady = TRUE` para todos los planes con precio > 0
- Comando: `lando drush jaraba:stripe:sync` (si existe) o script manual

---

### 2.3 FairUsePolicy sin config = sin enforcement

**Promesa:** Fair Use Policy aplica límites por tier (70% warn, 85% throttle, 95% soft_block, 100% hard_block).

**Realidad del codebase:**
- `FairUsePolicyService::loadPolicy($tier)` busca ConfigEntities por tier
- **0 FairUsePolicy ConfigEntities creadas** en config/install o DB
- Fallback: cuando policy es NULL, `evaluate()` retorna `allowed = true` siempre
- Efecto: **ningún recurso se limita, ningún tenant se bloquea, ninguna alerta se envía**

**Solución:**
- Crear FairUsePolicy ConfigEntities para cada tier: `starter`, `professional`, `enterprise`, `_global`
- Definir límites realistas por recurso (ai_queries, storage_gb, max_pages, etc.)
- Verificar que `evaluate()` retorna correctamente warn/throttle/block

---

### 2.4 Marketplace prometido con 0 oferta

**Promesa (v2):** AgroConecta y ComercioConecta "conectan vendedores y compradores".

**Realidad:** 0 vendedores registrados, 0 productos en marketplace. Un marketplace sin oferta es un escaparate vacío.

**Solución (v3):** Reframing honesto:
- Posicionamiento día 0: "Tu tienda online propia" (valor para 1 usuario)
- Marketplace como bonus: "Cuando haya +15 productores, tu tienda se integra automáticamente en el marketplace"
- Comunicar con badge: `{% trans %}Próximamente{% endtrans %}` en features marketplace

---

### 2.5 Matching Engine sin masa crítica

**Promesa (v2):** Matching IA entre candidatos y ofertas de empleo.

**Realidad:** 0 ofertas publicadas. Un matching engine sin ofertas no produce resultados.

**Solución (v3):** Threshold explícito:
- "Se activa automáticamente cuando haya +20 ofertas publicadas"
- Fase 0: Agregación de ofertas externas (SAE, InfoJobs via scraping)

---

### 2.6 Soporte dedicado con 0 equipo

**Promesa (v2):** Enterprise incluye "Soporte dedicado + SLA".

**Realidad:** 0 personas en equipo de soporte. El soporte lo hace el fundador.

**Solución (v3):** Downgrade honesto:
- Starter: Documentación + AI chatbot (sin SLA)
- Pro: Email soporte con respuesta en 48h hábiles
- Enterprise: Email prioritario con respuesta en 24h

---

### 2.7 Jitsi sin infraestructura

**Promesa (v2):** Videoconferencia integrada via Jitsi Meet.

**Realidad:** Jitsi self-hosted requiere servidor dedicado + ancho de banda. Coste oculto.

**Solución (v3):** Fase 0: enlaces Meet/Zoom (gratuitos). Jitsi self-hosted cuando MRR > 5.000 EUR.

---

### 2.8 Cursos propios inexistentes

**Promesa (v2):** LMS con cursos propios del ecosistema.

**Realidad:** 0 cursos producidos. LMS existe técnicamente (jaraba_lms) pero sin contenido.

**Solución (v3):** Día 0: contenido curado (enlaces a recursos externos de calidad). Producción propia cuando ingresos lo justifiquen.

---

### 2.9 White Label inviable en Enterprise

**Promesa (v2):** Enterprise incluye White Label (marca propia del tenant).

**Realidad:** Custom domain + DNS + SSL + theming personalizado = trabajo significativo por cliente.

**Solución (v3):** Mover a add-on separado:
- Setup: 500 EUR (one-time)
- Mantenimiento: 50 EUR/mes
- Requisito: Plan Enterprise activo

---

### 2.10 AI Copilot ausente en Starter

**Promesa (v2):** AI Copilot solo en Pro/Enterprise.

**Problema:** El AI Copilot es el diferenciador principal del SaaS. Sin él en Starter, el usuario no experimenta el valor core.

**Solución (v3):** Añadir AI Copilot a TODOS los Starters:
- Starter: 25-50 consultas/mes (modo learn)
- Pro: 100-200 consultas/mes (5 modos completos)
- Enterprise: Ilimitado (5 modos + custom)

---

### 2.11 Early Adopter "forever" = anti-económico

**Promesa (v2):** Descuento 30% de por vida para early adopters.

**Problema:** Con Stripe Coupon `duration=forever`, los primeros clientes NUNCA pagan precio completo.

**Solución (v3):** Escala temporal:
- Año 1: 30% descuento
- Año 2: 15% descuento
- Año 3+: Precio normal

---

### 2.12 Comisiones por plan = incentivo perverso

**Promesa (v2):** Comisión marketplace varía por plan (Starter 8%, Pro 5%, Enterprise 3%).

**Problema:** Un vendedor compra Enterprise solo para pagar menos comisión, no por las features.

**Solución (v3):** Comisiones por volumen:
- GMV < 5.000 EUR/mes: 8%
- GMV 5.000-20.000 EUR/mes: 6%
- GMV > 20.000 EUR/mes: 4%

---

## 3. SPRINT 1 — MODELO DE 3 PILARES (Doc 181 + 158 v3)

**Duración:** 1-2 semanas
**Impacto:** Corrige el modelo de negocio fundamental

### 3.1 Pilar 1: Plan Base SaaS (subscription)

**Lo que incluye:** Plataforma + IA (escalable, sin horas humanas)

| Vertical | Starter | Pro | Enterprise |
|----------|---------|-----|------------|
| Empleabilidad | 29 EUR | 79 EUR | 149 EUR |
| Emprendimiento | 39 EUR | 99 EUR | 199 EUR |
| AgroConecta | 49 EUR | 129 EUR | 249 EUR |
| ComercioConecta | 39 EUR | 99 EUR | 199 EUR |
| ServiciosConecta | 29 EUR | 79 EUR | 149 EUR |
| JarabaLex | 39 EUR | 99 EUR | 199 EUR |

**Cambios v2 → v3:**
- ELIMINAR: mentoring_1a1, mentoring_grupal de features
- AÑADIR: copilot_ia_basic (25-50 consultas) a todos los Starter
- AÑADIR: mastermind_grupal a Pro/Enterprise
- ELIMINAR: networking_events de plans (mover a add-on)
- ELIMINAR: white_label de Enterprise (mover a add-on)
- CAMBIAR: soporte → docs+AI (Starter), email 48h (Pro), email 24h (Enterprise)

**Implementación:**
1. Actualizar `SubscriptionContextService::FEATURE_LABELS`
2. Actualizar SaasPlan ConfigEntities con features corregidos
3. Actualizar landing pages para reflejar features reales
4. Actualizar pricing page con features honestos

### 3.2 Pilar 2: Add-ons Marketing (subscription)

Sin cambios respecto a Doc 158 v2. 9 add-ons + 4 bundles.

**Nuevo add-on (v3):**
- `white_label`: 500 EUR setup + 50 EUR/mes (solo Enterprise)

### 3.3 Pilar 3: Servicios Profesionales (one-time payment)

**Nuevo pilar (Doc 181):**

| Servicio | Precio | Duración | Stripe mode |
|----------|--------|----------|-------------|
| Sesión individual 1:1 | 175 EUR | 45 min | payment |
| Pack 4 sesiones | 595 EUR | 4x 45 min | payment |
| Pack 8 sesiones | 1.095 EUR | 8x 45 min | payment |
| Programa Launch | 1.950 EUR | 12 semanas | payment |
| Programa Aceleración | 2.950 EUR | 12 semanas | payment |
| Workshop grupo | 79 EUR/persona | 2h | payment |
| Mastermind Premium | 295 EUR/persona | 3 meses | payment |
| Bootcamp | 495 EUR/persona | 5 días | payment |

**Margen:** >70% en todos los formatos (vs -551 EUR/mes del modelo v2).

---

## 4. SPRINT 2 — CORRECCIONES CRÍTICAS DE CÓDIGO

**Duración:** 2-3 semanas
**Impacto:** El checkout funciona, los límites se aplican

### 4.1 Stripe Price IDs: sincronizar con Stripe Dashboard

**Problema:** Todos los `stripe_price_id` y `stripe_price_yearly_id` están vacíos.

**Solución:**
1. Ejecutar `StripeProductSyncService::syncAll()` para crear Products en Stripe
2. El servicio ya mapea SaasPlan → Stripe Product + 2 Prices (monthly/yearly)
3. Verificar que los IDs se guardan en las entities
4. Comando: `lando drush eval "\\Drupal::service('jaraba_billing.stripe_product_sync')->syncAll();"`
5. Verificar: `lando drush eval` → cada SaasPlan tiene `stripe_price_id` no vacío
6. Test E2E: navegar a `/planes/checkout/{plan}` → ver formulario Stripe (no "Configuración pendiente")

**Directrices:** STRIPE-ENV-UNIFY-001 (keys en settings.secrets.php), STRIPE-URL-PREFIX-001 (no /v1/), STRIPE-CHECKOUT-001 (embedded).

### 4.2 FairUsePolicy: crear ConfigEntities por tier

**Problema:** 0 policies → evaluate() retorna `allowed` siempre.

**Solución:** Crear 4 FairUsePolicy ConfigEntities via hook_update_N:

```php
// Estructura por tier:
[
  'id' => 'starter',
  'label' => 'Starter Fair Use Policy',
  'tier' => 'starter',
  'limits' => [
    'ai_queries' => 50,
    'storage_gb' => 5,
    'max_pages' => 10,
    'copilot_sessions_daily' => 5,
  ],
  'thresholds' => [70, 85, 95],
  'burst_tolerance_pct' => 20,
  'grace_period_hours' => 2,
],
// ... professional, enterprise, _global
```

**Directrices:** UPDATE-HOOK-REQUIRED-001 (hook_update_N obligatorio), UPDATE-HOOK-CATCH-001 (\\Throwable).

### 4.3 Mentoring: eliminar de planes, crear catálogo servicios

**Fase 1 (Sprint 2):** Eliminar mentoring de features de planes SaaS
- Actualizar SaasPlan entities: quitar `mentoring_1a1` de Pro/Enterprise
- Actualizar `SubscriptionContextService::FEATURE_LABELS`: eliminar entrada `mentoring_1a1`
- Añadir `mastermind_grupal` como feature nuevo en Pro/Enterprise

**Fase 2 (Sprint 3):** Crear entities para servicios profesionales (ver Sprint 3)

### 4.4 AI Copilot: añadir a todos los Starter

**Cambio:** Añadir `copilot_ia_basic` (25-50 consultas/mes) a features de TODOS los planes Starter.

**Implementación:**
1. Actualizar SaasPlan ConfigEntities Starter de cada vertical
2. Actualizar `FEATURE_LABELS` con nueva entrada `copilot_ia_basic`
3. Actualizar `LIMIT_LABELS` con `copilot_queries_monthly` límite

---

## 5. SPRINT 3 — ENTIDADES NUEVAS

**Duración:** 2-3 semanas
**Impacto:** Catálogo de servicios profesionales operativo

### 5.1 Entity professional_service

**Módulo:** jaraba_billing
**Tipo:** ContentEntity

**Campos:**
- `title`: string (translatable) — Nombre del servicio
- `type`: string — single_session | pack | program | workshop | mastermind | bootcamp | institutional
- `price`: decimal — Precio EUR
- `duration_minutes`: integer — Duración en minutos
- `total_sessions`: integer — Nº sesiones incluidas (1 para single, N para pack/programa)
- `mentor_id`: entity_reference (user) — Mentor asignado (fase 0: Pepe)
- `stripe_product_id`: string — ID Stripe Product
- `vertical`: string — Vertical asociado (o 'all')
- `description`: text_long (translatable) — Descripción detallada
- `is_active`: boolean
- `tenant_id`: entity_reference (group) — TENANT-001

**AccessControlHandler:** DefaultEntityAccessControlHandler (AUDIT-CONS-001)
**Views data:** EntityViewsData
**Field UI:** field_ui_base_route
**Form:** PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001)

### 5.2 Entity service_booking

**Módulo:** jaraba_billing
**Tipo:** ContentEntity

**Campos:**
- `service_id`: entity_reference → professional_service
- `buyer_uid`: entity_reference (user) — Comprador
- `tenant_id`: entity_reference (group) — TENANT-001
- `stripe_payment_intent_id`: string
- `stripe_charge_id`: string
- `sessions_total`: integer — Sesiones contratadas
- `sessions_used`: integer — Sesiones consumidas
- `status`: string — pending_payment | paid | active | completed | cancelled | refunded | expired
- `start_date`: datetime
- `expiry_date`: datetime (6 meses desde activación)
- `notes`: text_long

**Estado machine (7 estados):**
```
pending_payment → paid → active → completed
                              ↘ cancelled
                              ↘ refunded
                              ↘ expired
```

### 5.3 ProfessionalServiceManager

**Servicio:** `jaraba_billing.professional_service_manager`

**Métodos públicos:**
- `listAvailableServices(?string $vertical = NULL): array`
- `getServiceDetail(int $serviceId): ?array`
- `initiateBooking(int $serviceId, int $buyerUid): ServiceBooking`
- `confirmPayment(int $bookingId, string $paymentIntentId): void`
- `scheduleSession(int $bookingId, string $datetime): MentoringSession`
- `completeSession(int $sessionId): void`
- `cancelBooking(int $bookingId): void`
- `getBookingDashboard(int $uid): array`
- `getMentorDashboard(int $mentorUid): array`

**Dependencias:** `@?jaraba_mentoring.session_scheduler`, `@?jaraba_foc.stripe_connect`

---

## 6. SPRINT 4 — REFRAMING UX

**Duración:** 1-2 semanas
**Impacto:** Las landings y pricing prometen solo lo entregable

### 6.1 Marketplace → "Tu tienda propia" + bonus

**Cambio en landings AgroConecta y ComercioConecta:**
- Título principal: "Tu tienda online profesional" (no "Marketplace")
- Feature marketplace: badge `{% trans %}Próximamente — se activa con +15 productores{% endtrans %}`
- CTA: "Crea tu tienda" (no "Únete al marketplace")

### 6.2 Matching → "Se activa con +20 ofertas"

**Cambio en landing Empleabilidad:**
- Feature matching: `{% trans %}Matching IA — se activa automáticamente cuando haya +20 ofertas{% endtrans %}`
- Fase 0: "Ofertas agregadas de SAE e InfoJobs"

### 6.3 Soporte → niveles honestos

**Cambio en pricing page (todas las verticales):**
- Starter: `{% trans %}Documentación + Asistente IA{% endtrans %}`
- Pro: `{% trans %}Email soporte (48h hábiles){% endtrans %}`
- Enterprise: `{% trans %}Email prioritario (24h){% endtrans %}`

### 6.4 Jitsi → Meet/Zoom links fase 0

**Cambio en ServiciosConecta:**
- Feature video: `{% trans %}Videoconferencia via Google Meet o Zoom{% endtrans %}` (no "Jitsi integrado")

### 6.5 White Label → add-on separado

**Cambio en pricing Enterprise (todas las verticales):**
- Eliminar "White Label" de features Enterprise
- Añadir en sección add-ons: "White Label: 500 EUR setup + 50 EUR/mes"

---

## 7. SPRINT 5 — ECA FLOWS Y AUTOMATIZACIÓN

4 flujos ECA nuevos para servicios profesionales:

1. **ECA-SVC-001:** Pago confirmado → activar booking + email confirmación + notificar mentor
2. **ECA-SVC-002:** Sesión completada → incrementar sessions_used + solicitar reseña + upsell si última sesión
3. **ECA-SVC-003:** Booking próximo a expirar → email alerta (14 días antes)
4. **ECA-SVC-004:** AI Copilot upsell → CTA contextual "Reserva sesión con mentor"

---

## 8. TABLA DE CORRESPONDENCIA: ESPECIFICACIONES TÉCNICAS

| Componente | Directriz | Fichero clave | Acción |
|------------|-----------|---------------|--------|
| SaasPlan features | NO-HARDCODE-PRICE-001 | config/sync/ecosistema_jaraba_core.saas_plan.*.yml | Eliminar mentoring, añadir copilot starter |
| Stripe sync | STRIPE-ENV-UNIFY-001 | StripeProductSyncService.php | syncAll() para poblar Price IDs |
| FairUsePolicy | UPDATE-HOOK-REQUIRED-001 | jaraba_billing.install | hook_update_N con 4 ConfigEntities |
| professional_service | AUDIT-CONS-001 | jaraba_billing/src/Entity/ | Nueva entity con ACH |
| service_booking | PREMIUM-FORMS-PATTERN-001 | jaraba_billing/src/Form/ | PremiumEntityFormBase |
| Checkout services | STRIPE-CHECKOUT-001 | CheckoutSessionService.php | mode='payment' para one-time |
| Landing reframing | {% trans %} | VerticalLandingController.php | Textos honestos traducibles |
| Pricing page | ZERO-REGION-001 | pricing-page.html.twig | Features actualizados |
| SubscriptionContext | OPTIONAL-CROSSMODULE-001 | SubscriptionContextService.php | Actualizar FEATURE_LABELS |

---

## 9. TABLA DE CUMPLIMIENTO: DIRECTRICES DEL PROYECTO

| # | Directriz | Cómo se cumple |
|---|-----------|----------------|
| 1 | ZERO-REGION-001 | Landings y pricing usan clean_content |
| 2 | CSS-VAR-ALL-COLORS-001 | Todos los estilos nuevos con var(--ej-*) |
| 3 | {% trans %} | Textos reframing traducibles |
| 4 | PREMIUM-FORMS-PATTERN-001 | service_booking form extiende PremiumEntityFormBase |
| 5 | TENANT-001 | professional_service y service_booking con tenant_id |
| 6 | AUDIT-CONS-001 | Ambas entities con AccessControlHandler |
| 7 | UPDATE-HOOK-REQUIRED-001 | hook_update_N para nuevas entities + FairUsePolicy |
| 8 | STRIPE-CHECKOUT-001 | Services usan mode='payment' (one-time) |
| 9 | NO-HARDCODE-PRICE-001 | Precios desde entities, no templates |
| 10 | ICON-CONVENTION-001 | jaraba_icon() duotone para catálogo servicios |
| 11 | SLIDE-PANEL-RENDER-001 | Booking form en slide-panel |
| 12 | ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() |

---

## 10. REGLAS DE NEGOCIO v3 (Las 3 Reglas Fundamentales)

### Regla #0: ZERO horas humanas en planes SaaS
"NUNCA incluir horas de trabajo humano en un plan SaaS. Los planes venden plataforma + IA (escalable). Las horas humanas se venden como Servicios Profesionales (bounded, priced)."

### Regla #1: AI Copilot en TODOS los Starters
"El diferenciador del SaaS es la IA. Sin Copilot en Starter, el usuario no experimenta el valor core. 25-50 consultas/mes mínimo."

### Regla #2: Solo prometer features que funcionen con 1 usuario
"No prometer marketplace sin vendedores, matching sin ofertas, networking sin eventos. Día 0: el valor debe ser para 1 usuario solo. Features multi-sided se activan con umbral explícito."

---

## 11. IMPACTO ECONÓMICO: v2 vs v3

### Escenario: 25h/mes de tiempo del fundador

| Modelo | Ingresos | Costes | Resultado |
|--------|----------|--------|-----------|
| **v2** (mentoring incluido) | 199 EUR × 20 Enterprise | 750 EUR × 20 | **-11.020 EUR/mes** |
| **v3** (servicios separados) | 199 EUR × 20 (SaaS) + 4.785 EUR (servicios) | 25h × 150 EUR = 3.750 EUR | **+5.015 EUR/mes** |
| **Swing** | — | — | **+16.035 EUR/mes** |

### Mastermind grupal (incluido en Pro/Enterprise)

- Coste: 1.5h × 150 EUR = 225 EUR por sesión
- Ingreso: 8 personas × 99 EUR (porción Pro) = 792 EUR
- **Margen: +567 EUR/sesión** (>70%)
- Capacidad: 3 sesiones/mes = 24 usuarios, 4.5h Pepe

---

## 12. CRONOGRAMA Y DEPENDENCIAS

```
Semana 1-2:   Sprint 1 — Modelo 3 pilares (decisión de negocio)
              ├── Actualizar SaasPlan features (eliminar mentoring, añadir copilot)
              ├── Actualizar SubscriptionContextService
              └── Documentar 3 reglas fundamentales v3

Semana 3-5:   Sprint 2 — Correcciones críticas código
              ├── Stripe Price IDs sync
              ├── FairUsePolicy ConfigEntities
              ├── AI Copilot en Starters
              └── Tests E2E checkout

Semana 6-8:   Sprint 3 — Entidades nuevas (paralelo con Sprint 4)
              ├── professional_service entity
              ├── service_booking entity
              ├── ProfessionalServiceManager
              └── Catálogo servicios frontend

Semana 6-8:   Sprint 4 — Reframing UX (paralelo con Sprint 3)
              ├── Landings reframing (marketplace, matching, soporte)
              ├── Pricing page actualizada
              └── Checkout servicios (mode=payment)

Semana 9-10:  Sprint 5 — ECA flows + automatización
              ├── 4 flujos ECA servicios profesionales
              └── AI Copilot upsell contextual
```

**Duración total:** 10 semanas (2.5 meses)

---

## 13. CRITERIOS DE ACEPTACIÓN

### Coherencia Pricing-Servicio (Meta-criterio)
- [ ] CADA feature listado en pricing page tiene implementación funcional en codebase
- [ ] NINGÚN plan SaaS incluye horas humanas
- [ ] AI Copilot presente en TODOS los Starters
- [ ] Marketplace/Matching con badges de umbral (no promesa absoluta)
- [ ] Soporte con niveles honestos y alcanzables

### Técnicos
- [ ] Stripe Price IDs poblados en todos los SaasPlan con precio > 0
- [ ] CheckoutController devuelve `$stripeReady = TRUE` para planes de pago
- [ ] FairUsePolicy ConfigEntities creadas (starter, professional, enterprise, _global)
- [ ] FairUsePolicyService.evaluate() retorna warn/throttle/block cuando corresponde
- [ ] professional_service + service_booking entities creadas con ACH + Field UI
- [ ] ProfessionalServiceManager servicio registrado y consumido
- [ ] Checkout servicios con mode='payment' (one-time, no subscription)
- [ ] Tests: checkout E2E, feature gating por tier, fair use enforcement

### UX
- [ ] Landing pages reflejan features reales (no over-promising)
- [ ] Pricing page muestra niveles de soporte honestos
- [ ] Features marketplace/matching muestran umbral de activación
- [ ] Catálogo servicios profesionales accesible desde perfil usuario

---

*Plan generado el 2026-03-18 por Claude Opus 4.6 (1M context).*
*Basado en: Doc 181 (Mentoring Desacople), Doc 158 v3 (Pricing Matrix corregida), auditoría codebase 94 módulos.*
*Todas las tareas cumplen las 12 directrices verificadas en sección 9.*
