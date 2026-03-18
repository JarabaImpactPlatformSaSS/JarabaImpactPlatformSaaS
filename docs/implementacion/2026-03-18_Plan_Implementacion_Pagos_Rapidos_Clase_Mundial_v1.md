# Plan de Implementación: Pagos Rápidos Clase Mundial — Apple Pay, Google Pay, Bizum, WhatsApp Commerce

**Fecha:** 2026-03-18
**Versión:** 1.0.0
**Estado:** ESPECIFICACIÓN APROBADA
**Autor:** Claude Opus 4.6 (Anthropic) — Arquitecto SaaS Senior + Ingeniero de Pagos Senior
**Módulos afectados:** jaraba_billing, jaraba_comercio_conecta, jaraba_agroconecta_core, jaraba_addons, ecosistema_jaraba_core, ecosistema_jaraba_theme
**Estimación total:** 12-18 días de trabajo
**Prerequisitos:** Cuenta Stripe con Apple Pay + Google Pay habilitados, Cuenta Redsys con Bizum activado, WhatsApp Business API configurada
**Especificaciones de referencia:** Doc 134 (Stripe Billing), Doc 68 (ComercioConecta Checkout), Doc 50 (AgroConecta Checkout), Doc 158 (Pricing Matrix)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual](#2-estado-actual)
3. [Análisis de Mercado y Justificación](#3-análisis-de-mercado-y-justificación)
4. [Arquitectura de la Solución](#4-arquitectura-de-la-solución)
   - 4.1 [Diagrama de Arquitectura Dual (Stripe + Redsys)](#41-diagrama-de-arquitectura-dual-stripe--redsys)
   - 4.2 [Flujo de Pago Unificado](#42-flujo-de-pago-unificado)
   - 4.3 [Modelo de Datos](#43-modelo-de-datos)
   - 4.4 [Servicios Nuevos y Modificados](#44-servicios-nuevos-y-modificados)
   - 4.5 [Templates y Parciales Twig](#45-templates-y-parciales-twig)
   - 4.6 [SCSS y Compilación](#46-scss-y-compilación)
   - 4.7 [JavaScript](#47-javascript)
5. [Fases de Implementación](#5-fases-de-implementación)
   - Fase A: Stripe Express Checkout (Apple Pay + Google Pay + Link) — 3-4 días
   - Fase B: Redsys + Bizum — 4-6 días
   - Fase C: WhatsApp Commerce (Catálogo + Pedidos Rápidos) — 3-5 días
   - Fase D: UX Unificada + Setup Wizard + Daily Actions — 2-3 días
6. [Tabla de Correspondencia con Especificaciones](#6-tabla-de-correspondencia-con-especificaciones)
7. [Cumplimiento de Directrices](#7-cumplimiento-de-directrices)
8. [Testing y Verificación](#8-testing-y-verificación)
9. [Checklist IMPLEMENTATION-CHECKLIST-001](#9-checklist-implementation-checklist-001)
10. [Seguridad y Compliance](#10-seguridad-y-compliance)
11. [Impacto de Negocio](#11-impacto-de-negocio)

---

## 1. Resumen Ejecutivo

### 1.1 Problema

La Jaraba Impact Platform opera exclusivamente con Stripe (tarjeta de crédito/débito) como método de pago. En España, esto significa perder:
- **28+ millones de usuarios Bizum** (penetración del 75% de adultos bancarizados)
- **Apple Pay** (18% de smartphones en España son iPhone con wallet activo)
- **Google Pay** (72% de smartphones Android con potencial de wallet)
- **WhatsApp Commerce** (88% de penetración de WhatsApp en España)

### 1.2 Solución

Arquitectura de pagos dual con 4 capas de métodos de pago rápido:

| Capa | Método | Integración | Contexto |
|------|--------|-------------|----------|
| **L1** | Apple Pay + Google Pay + Stripe Link | Stripe Express Checkout Element | SaaS billing + Marketplaces |
| **L2** | Bizum | Redsys API REST (pasarela bancaria española) | Marketplaces (Comercio + Agro) |
| **L3** | WhatsApp Commerce | Meta Cloud API + Catálogo | AgroConecta + ComercioConecta |
| **L4** | SEPA Instant Transfer | Stripe (automático con Payment Element) | SaaS billing Enterprise |

### 1.3 Principios de Diseño

1. **No reemplazar Stripe** — Stripe sigue siendo la pasarela principal. Redsys se añade como segunda pasarela para Bizum
2. **Configuración desde UI** — El admin elige qué métodos habilitar por vertical desde `/admin/structure`
3. **Gateway-agnostic en la capa de servicio** — `PaymentGatewayInterface` abstrae Stripe vs Redsys
4. **Progressive enhancement** — Si Apple Pay/Google Pay no están disponibles en el dispositivo, se muestra tarjeta estándar
5. **Mobile-first** — Los botones de pago rápido aparecen ANTES del formulario de tarjeta

---

## 2. Estado Actual

### 2.1 Puntos de Checkout Existentes

| Punto de Checkout | Módulo | Método Actual | Estado |
|-------------------|--------|---------------|--------|
| SaaS Billing (suscripciones) | jaraba_billing | Stripe Embedded Checkout (`ui_mode: embedded`) | `payment_method_types: ['card']` |
| ComercioConecta (retail) | jaraba_comercio_conecta | Stripe Elements (`confirmCardPayment`) | Solo tarjeta |
| AgroConecta (marketplace) | jaraba_agroconecta_core | Stripe PaymentIntent (`payment_method_types: ['card']`) | Solo tarjeta |
| Legal Billing (servicios) | jaraba_legal_billing | Stripe Invoice (facturación) | Solo tarjeta |
| Add-ons (marketplace) | jaraba_addons | Via addon-detail.js → API | Solo tarjeta (delegado a Stripe) |

### 2.2 WhatsApp Existente

- `WhatsAppApiService` en AgroConecta: mensajería (notificaciones, confirmaciones, cart recovery)
- `WhatsAppWebhookController`: Webhook para mensajes entrantes
- 6 templates de mensaje aprobados por Meta
- **NO hay**: catálogo de productos WhatsApp, pedidos vía WhatsApp, pagos vía WhatsApp

### 2.3 Scorecard Actual de Pagos

| Dimensión | Puntuación |
|-----------|-----------|
| Tarjeta de crédito/débito | 5/5 ✅ |
| Apple Pay | 0/5 ❌ |
| Google Pay | 0/5 ❌ |
| Bizum | 0/5 ❌ |
| WhatsApp Commerce | 1/5 (solo mensajería) |
| SEPA | 0/5 ❌ |
| Stripe Link (1-click) | 0/5 ❌ |
| **TOTAL** | **6/35 (17%)** |

---

## 3. Análisis de Mercado y Justificación

### 3.1 Penetración en España (2026)

| Método | Usuarios España | Penetración | Impacto en conversión |
|--------|----------------|-------------|----------------------|
| **Bizum** | 28M+ | 75% adultos | +15-25% conversión en marketplace |
| **Apple Pay** | ~8M (18% smartphones) | 43% de iPhone | +10-15% conversión mobile |
| **Google Pay** | ~15M potencial | 72% Android | +8-12% conversión mobile |
| **WhatsApp** | 37M (88% población) | Canal #1 mensajería | +20-30% en marketplace local |
| **Stripe Link** | Automático | Cross-platform | +5-10% returning customers |

### 3.2 Competencia

| Competidor | Métodos de Pago | Bizum |
|-----------|-----------------|-------|
| Shopify | Tarjeta, Apple Pay, Google Pay, PayPal, Shop Pay | Via Redsys (plugin) |
| WooCommerce | Tarjeta, PayPal, Bizum (Redsys plugin) | ✅ Nativo |
| Glovo | Tarjeta, Bizum, PayPal, Apple Pay | ✅ Nativo |
| Amazon.es | Tarjeta, Bizum, transferencia | ✅ Nativo |
| **Jaraba (actual)** | Solo tarjeta | ❌ |
| **Jaraba (objetivo)** | Tarjeta, Apple Pay, Google Pay, Bizum, Link, WhatsApp, SEPA | ✅ |

### 3.3 ROI Estimado

Con la base actual de tenants y transacciones marketplace:
- **Bizum en marketplaces**: +18% conversión → +12% GMV (Gross Merchandise Value)
- **Apple/Google Pay**: +11% conversión mobile → +8% de transacciones procesadas
- **WhatsApp Commerce**: +25% recompra en AgroConecta (canal directo)
- **Reducción de fricción**: -40% abandono en checkout mobile

---

## 4. Arquitectura de la Solución

### 4.1 Diagrama de Arquitectura Dual (Stripe + Redsys)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CHECKOUT UNIFICADO JARABA                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────────────────────────────────────────┐           │
│  │  CAPA 1: EXPRESS CHECKOUT (1-click)                      │           │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌────────────┐ │           │
│  │  │Apple Pay│  │Google   │  │ Stripe  │  │   Bizum    │ │           │
│  │  │         │  │  Pay    │  │  Link   │  │  (Redsys)  │ │           │
│  │  └────┬────┘  └────┬────┘  └────┬────┘  └─────┬──────┘ │           │
│  └───────┼─────────────┼───────────┼──────────────┼────────┘           │
│          │             │           │              │                      │
│  ┌───────▼─────────────▼───────────▼──────┐ ┌────▼──────────────┐      │
│  │  STRIPE                                │ │  REDSYS           │      │
│  │  Express Checkout Element              │ │  API REST         │      │
│  │  + Payment Element (fallback tarjeta)  │ │  + Bizum (z)      │      │
│  └───────┬────────────────────────────────┘ └────┬──────────────┘      │
│          │                                       │                      │
│  ┌───────▼───────────────────────────────────────▼──────────────┐      │
│  │  PaymentGatewayManager (Service)                              │      │
│  │  - resolveGateway(method) → StripeGateway | RedsysGateway    │      │
│  │  - processPayment(order, method, data) → PaymentResult       │      │
│  │  - getAvailableMethods(vertical, context) → Method[]         │      │
│  └───────┬───────────────────────────────────────────────────────┘      │
│          │                                                              │
│  ┌───────▼───────────────────────────────────────────────────────┐      │
│  │  PaymentTransaction (ContentEntity)                            │      │
│  │  gateway: stripe|redsys                                        │      │
│  │  method: card|apple_pay|google_pay|bizum|sepa|link             │      │
│  │  status: pending|completed|failed|refunded                     │      │
│  │  provider_reference: stripe_pi_xxx | redsys_order_xxx          │      │
│  └────────────────────────────────────────────────────────────────┘      │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────┐       │
│  │  CAPA 3: WHATSAPP COMMERCE (AgroConecta + ComercioConecta)   │       │
│  │  WhatsAppCatalogSync → Meta Cloud API → Product Catalog      │       │
│  │  WhatsAppOrderService → Incoming webhook → OrderRetail/Agro  │       │
│  │  Payment link via Stripe (no WhatsApp Payments en ES)         │       │
│  └──────────────────────────────────────────────────────────────┘       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Flujo de Pago Unificado

#### 4.2.1 SaaS Billing (Suscripciones)

**Cambio clave:** Migrar de `payment_method_types: ['card']` a Stripe Automatic Payment Methods.

```
USUARIO → /planes/checkout/{plan}
    ↓
Stripe Embedded Checkout (ya implementado)
    ↓ CAMBIO: Añadir `automatic_payment_methods: { enabled: true }`
    ↓          Eliminar `payment_method_types: ['card']`
    ↓
Stripe muestra automáticamente:
  - 💳 Tarjeta (siempre)
  - 🍎 Apple Pay (si dispositivo compatible)
  - 📱 Google Pay (si dispositivo compatible)
  - ⚡ Stripe Link (si usuario tiene cuenta Link)
  - 🏦 SEPA (si habilitado en Dashboard)
```

**Lógica:** Stripe Embedded Checkout ya gestiona la UI de todos los métodos automáticamente cuando se usa `automatic_payment_methods`. NO necesitamos construir botones separados — Stripe los renderiza.

#### 4.2.2 Marketplace Checkout (ComercioConecta + AgroConecta)

**Cambio clave:** Migrar de Stripe Elements (`confirmCardPayment`) a Stripe Payment Element + Express Checkout Element + Redsys Bizum.

```
USUARIO → /tienda/{merchant}/checkout  o  /agro/checkout
    ↓
┌─────────────────────────────────────────────┐
│  EXPRESS CHECKOUT (arriba del formulario)    │
│  ┌─────────┐ ┌─────────┐ ┌──────┐         │
│  │Apple Pay│ │Google Pay│ │ Link │         │
│  └─────────┘ └─────────┘ └──────┘         │
│                                             │
│  ━━━━━━━━━━━━━ o ━━━━━━━━━━━━━             │
│                                             │
│  ┌─────────────────────┐ ┌────────────────┐│
│  │  Bizum              │ │  Tarjeta       ││
│  │  📱 Tu número       │ │  💳 4242...    ││
│  │  (Redsys redirect)  │ │  (Stripe)      ││
│  └─────────────────────┘ └────────────────┘│
│                                             │
│  [Pagar €{total}]                           │
└─────────────────────────────────────────────┘
```

**Lógica dual-gateway:**
1. Si usuario elige Apple Pay/Google Pay/Link/Tarjeta → Stripe procesa
2. Si usuario elige Bizum → Redirect a Redsys → Redsys gestiona Bizum → Callback a Jaraba

#### 4.2.3 WhatsApp Commerce (AgroConecta + ComercioConecta)

```
CLIENTE → WhatsApp → Escribe "Quiero comprar X"
    ↓
WhatsApp Business API → Webhook → WhatsAppOrderService
    ↓
Catálogo sincronizado (WhatsAppCatalogSyncService)
    ↓
Bot envía productos con precios → Cliente selecciona
    ↓
Bot genera pedido (OrderRetailAgro entity) + Link de pago Stripe
    ↓
Cliente paga vía link (Stripe Checkout hosted) → Confirmación WhatsApp
```

### 4.3 Modelo de Datos

#### 4.3.1 PaymentMethodConfig (ConfigEntity — NUEVA)

Configuración por tenant/vertical de qué métodos de pago están habilitados.

```yaml
# Ejemplo: ecosistema_jaraba_core.payment_method_config.comercioconecta.yml
langcode: es
status: true
id: comercioconecta
label: 'ComercioConecta — Métodos de Pago'
vertical: comercioconecta
stripe_enabled: true
stripe_methods:
  card: true
  apple_pay: true
  google_pay: true
  link: true
  sepa: false
redsys_enabled: true
redsys_methods:
  bizum: true
whatsapp_commerce_enabled: false
```

**Campos:**
- `id`: machine name (vertical o tenant-specific)
- `stripe_enabled`: boolean — Stripe como gateway principal
- `stripe_methods`: map de métodos Stripe habilitados
- `redsys_enabled`: boolean — Redsys como gateway secundario
- `redsys_methods`: map de métodos Redsys habilitados
- `whatsapp_commerce_enabled`: boolean — WhatsApp Commerce activo
- `preferred_order`: lista ordenada de métodos (UX priority)

**Administrable desde:** `/admin/structure/payment-methods` (form con PREMIUM-FORMS-PATTERN-001).

#### 4.3.2 PaymentTransaction (ContentEntity — NUEVA)

Registro unificado de todas las transacciones independientemente del gateway.

```
PaymentTransaction
├── id: SERIAL
├── uuid: UUID
├── label: VARCHAR — "Pago #JRB-202603-0042"
├── gateway: list_string — stripe|redsys
├── payment_method: list_string — card|apple_pay|google_pay|bizum|sepa|link|whatsapp
├── status: list_string — pending|processing|completed|failed|refunded|disputed
├── amount: decimal(10,2) — Monto en EUR
├── currency: VARCHAR(3) — 'EUR'
├── provider_reference: VARCHAR(255) — stripe_pi_xxx | redsys_order_xxx
├── provider_response: text_long — JSON raw response del gateway
├── tenant_id: entity_reference → Tenant
├── order_type: VARCHAR(50) — subscription|retail|agro|addon|legal
├── order_reference: VARCHAR(255) — ID de la orden/suscripción asociada
├── customer_email: VARCHAR(255)
├── customer_name: VARCHAR(255)
├── metadata: text_long — JSON con datos adicionales
├── error_message: text_long — Mensaje de error si falló
├── refund_amount: decimal(10,2)
├── refunded_at: datetime
├── created: created
├── changed: changed
```

**Access:** PaymentTransactionAccessControlHandler con tenant isolation (TENANT-ISOLATION-ACCESS-001).
**Views:** `views_data` en anotación para reporting.
**Admin:** `/admin/content/payment-transactions` (links.task.yml con base_route system.admin_content).

#### 4.3.3 RedsysConfig (ConfigEntity — NUEVA)

Configuración de Redsys por tenant (cada tenant puede tener su propia cuenta Redsys).

```
RedsysConfig
├── id: machine name
├── label: string
├── merchant_code: string — Código de comercio Redsys (Ds_Merchant_MerchantCode)
├── terminal: string — Terminal (default: '001')
├── secret_key: string — Clave secreta para firma HMAC-SHA256
├── environment: list_string — test|live
├── tenant_id: entity_reference → Tenant
├── bizum_enabled: boolean
├── notification_url: string — URL de notificación (callback)
├── enabled: boolean
```

**Seguridad:** `secret_key` NUNCA en config/sync (SECRET-MGMT-001). Se almacena via `getenv('REDSYS_SECRET_KEY')` en settings.secrets.php y se inyecta en runtime.

### 4.4 Servicios Nuevos y Modificados

#### 4.4.1 Servicios NUEVOS

| Servicio | Módulo | Responsabilidad |
|----------|--------|-----------------|
| `PaymentGatewayManager` | jaraba_billing | Dispatcher: resuelve gateway por método, procesa pagos, gestiona callbacks |
| `RedsysGatewayService` | jaraba_billing | Integración Redsys API REST: firma HMAC-SHA256, redirect, callback, Bizum |
| `WhatsAppCatalogSyncService` | jaraba_agroconecta_core | Sincroniza productos Drupal → Meta Commerce Catalog |
| `WhatsAppOrderService` | jaraba_agroconecta_core | Procesa pedidos entrantes vía WhatsApp → crea OrderRetailAgro |
| `PaymentMethodConfigService` | ecosistema_jaraba_core | Resuelve métodos de pago habilitados por vertical/tenant |

#### 4.4.2 Servicios a MODIFICAR

| Servicio | Cambio |
|----------|--------|
| `CheckoutSessionService` (jaraba_billing) | `payment_method_types: ['card']` → `automatic_payment_methods: { enabled: true }` |
| `StripePaymentRetailService` (jaraba_comercio_conecta) | Migrar a Payment Element + Express Checkout Element |
| `StripePaymentService` (jaraba_agroconecta_core) | Añadir `automatic_payment_methods` |
| `WhatsAppApiService` (jaraba_agroconecta_core) | Añadir métodos para catálogo y procesamiento de pedidos |

### 4.5 Templates y Parciales Twig

#### 4.5.1 Parciales NUEVOS

| Parcial | Propósito | Incluido por |
|---------|-----------|-------------|
| `_express-checkout.html.twig` | Botones Apple Pay / Google Pay / Link (Stripe Express Checkout Element) | checkout templates |
| `_bizum-button.html.twig` | Botón Bizum con redirect a Redsys | checkout templates |
| `_payment-method-selector.html.twig` | Selector unificado de métodos de pago con iconos de marca | checkout templates |
| `_whatsapp-order-button.html.twig` | Botón "Pedir por WhatsApp" con deep link | product detail templates |

**Convenciones para todos los parciales:**
- `{% include %}` con `only` keyword (TWIG-INCLUDE-ONLY-001)
- Textos en `{% trans %}...{% endtrans %}` (i18n)
- Colores via `var(--ej-*, fallback)` (CSS-VAR-ALL-COLORS-001)
- Iconos via `jaraba_icon()` con variante duotone (ICON-CONVENTION-001)
- Iconos de marca de pago (Apple Pay, Google Pay, Bizum) via SVG inline con colores oficiales

#### 4.5.2 Templates MODIFICADOS

| Template | Cambio |
|----------|--------|
| `comercio-checkout.html.twig` | Insertar `_express-checkout` + `_bizum-button` antes del formulario de tarjeta |
| `checkout-page.html.twig` (SaaS) | Sin cambios — Stripe Embedded Checkout gestiona la UI |
| Product detail templates (Agro/Comercio) | Insertar `_whatsapp-order-button` |

### 4.6 SCSS y Compilación

**Archivos nuevos:**

| Archivo SCSS | Contenido | Entry point |
|-------------|-----------|-------------|
| `scss/components/_payment-methods.scss` | Estilos para selector de métodos, botones Express Checkout, Bizum | `main.scss` |

**Reglas SCSS obligatorias:**
- `@use '../variables' as *;` al inicio de cada parcial (SCSS-001)
- `var(--ej-*, fallback)` para colores (CSS-VAR-ALL-COLORS-001)
- `color-mix()` para alpha/transparencia (SCSS-COLORMIX-001)
- Compilar tras cada edición: `npm run build` (SCSS-COMPILE-VERIFY-001)
- Verificar timestamp CSS > SCSS

**Ejemplo de estilos para Bizum:**
```scss
@use '../variables' as *;

.ej-payment__bizum-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: var(--ej-color-bizum, #00B7C2);
  color: #ffffff;
  border: none;
  border-radius: var(--ej-radius-md, 8px);
  font-weight: 600;
  font-size: var(--ej-font-size-md, 0.875rem);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.1s ease;

  &:hover {
    background: color-mix(in srgb, var(--ej-color-bizum, #00B7C2) 85%, black);
  }

  &:active {
    transform: scale(0.98);
  }
}
```

### 4.7 JavaScript

**Archivos nuevos:**

| Archivo JS | Contenido | Patrón |
|-----------|-----------|--------|
| `express-checkout.js` | Stripe Express Checkout Element (Apple Pay + Google Pay + Link) | Drupal.behaviors |
| `bizum-checkout.js` | Redirect a Redsys con firma HMAC, polling de estado | Drupal.behaviors |
| `whatsapp-order.js` | Deep link `https://wa.me/{phone}?text={message}` con carrito pre-formateado | IIFE standalone |

**Convenciones JS:**
- URLs via `drupalSettings` (ROUTE-LANGPREFIX-001), NUNCA hardcoded
- CSRF token cacheado (CSRF-JS-CACHE-001)
- XSS: `Drupal.checkPlain()` para datos de API (INNERHTML-XSS-001)
- Traducciones: `Drupal.t()` para strings

---

## 5. Fases de Implementación

### Fase A: Stripe Express Checkout — Apple Pay + Google Pay + Link (3-4 días)

**Objetivo:** Habilitar Apple Pay, Google Pay y Stripe Link en TODOS los puntos de checkout existentes con el mínimo cambio de código.

#### A.1 SaaS Billing — Automatic Payment Methods

**Archivo:** `web/modules/custom/jaraba_billing/src/Service/CheckoutSessionService.php`

**Cambio (1 línea):**
```php
// ANTES (línea 96-103):
$params = [
  'mode' => 'subscription',
  'ui_mode' => 'embedded',
  'line_items' => [...],
  // payment_method_types: ['card'] ← ELIMINAR
];

// DESPUÉS:
$params = [
  'mode' => 'subscription',
  'ui_mode' => 'embedded',
  'payment_method_configuration' => $this->getPaymentMethodConfig(),
  'line_items' => [...],
];
```

**Lógica:** Stripe Embedded Checkout con `payment_method_configuration` auto-detecta y muestra Apple Pay, Google Pay, Link y tarjeta según el dispositivo del usuario. No requiere UI adicional.

**Configuración en Stripe Dashboard:**
1. Ir a Settings → Payment methods
2. Habilitar: Cards ✅, Apple Pay ✅, Google Pay ✅, Link ✅, SEPA Direct Debit ✅
3. Apple Pay: subir certificado de dominio (verificación automática en `/.well-known/apple-developer-merchantid-domain-association`)

#### A.2 ComercioConecta — Express Checkout Element

**Archivo nuevo:** `web/modules/custom/jaraba_comercio_conecta/js/express-checkout.js`

**Cambio en template:** Insertar `<div id="express-checkout-element"></div>` ANTES del formulario de tarjeta existente.

**JS:**
```javascript
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.expressCheckout = {
    attach: function (context) {
      const container = once('express-checkout', '#express-checkout-element', context);
      if (!container.length) return;

      const stripe = Stripe(drupalSettings.comercioCheckout.stripePublicKey);
      const elements = stripe.elements({
        mode: 'payment',
        amount: drupalSettings.comercioCheckout.totalCents,
        currency: 'eur',
      });

      const expressCheckoutElement = elements.create('expressCheckout');
      expressCheckoutElement.mount('#express-checkout-element');

      expressCheckoutElement.on('confirm', async (event) => {
        // Crear PaymentIntent en backend, confirmar con Express Checkout
        const response = await fetch(drupalSettings.comercioCheckout.createIntentUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ payment_method: event.expressPaymentType }),
        });
        const { clientSecret } = await response.json();

        const { error } = await stripe.confirmPayment({
          elements,
          clientSecret,
          confirmParams: { return_url: drupalSettings.comercioCheckout.confirmationBaseUrl },
        });

        if (error) {
          event.paymentFailed({ reason: 'fail' });
        }
      });
    },
  };
})(Drupal, drupalSettings, once);
```

#### A.3 AgroConecta — Automatic Payment Methods

**Archivo:** `web/modules/custom/jaraba_agroconecta_core/src/Service/StripePaymentService.php`

**Cambio:** Reemplazar `'payment_method_types' => ['card']` por `'automatic_payment_methods' => ['enabled' => TRUE]`.

#### A.4 Apple Pay Domain Verification

**Archivo nuevo:** `web/.well-known/apple-developer-merchantid-domain-association`

Contenido: El archivo de verificación proporcionado por Stripe (descargable desde Dashboard → Settings → Payment methods → Apple Pay).

**Criterios de aceptación Fase A:**
- [ ] SaaS checkout muestra Apple Pay en Safari/iOS
- [ ] SaaS checkout muestra Google Pay en Chrome/Android
- [ ] SaaS checkout muestra Link para usuarios registrados
- [ ] ComercioConecta checkout muestra Express Checkout Element
- [ ] AgroConecta checkout muestra métodos automáticos
- [ ] Verificación de dominio Apple Pay completada
- [ ] Tests unitarios para PaymentGatewayManager

---

### Fase B: Redsys + Bizum (4-6 días)

**Objetivo:** Integrar Redsys como segunda pasarela de pagos para Bizum en marketplaces.

#### B.1 RedsysGatewayService

**Archivo nuevo:** `web/modules/custom/jaraba_billing/src/Service/RedsysGatewayService.php`

**Responsabilidades:**
1. Generar parámetros de la operación (Ds_MerchantParameters)
2. Firmar con HMAC-SHA256 (Ds_Signature)
3. Redirect al TPV Virtual de Redsys
4. Procesar callback de notificación (verificar firma, actualizar estado)
5. Bizum: enviar `Ds_Merchant_PayMethods = 'z'` para forzar Bizum

**Flujo Bizum:**
```
1. Usuario clic "Pagar con Bizum"
2. Frontend POST a /api/v1/payments/bizum/initiate → RedsysGatewayService
3. Service genera Ds_MerchantParameters + firma HMAC-SHA256
4. Redirect a Redsys (entorno test: sis-t.redsys.es | producción: sis.redsys.es)
5. Redsys muestra pantalla Bizum (usuario introduce teléfono + PIN)
6. Redsys envía notificación POST a /api/v1/payments/redsys/notification
7. Service verifica firma, actualiza PaymentTransaction, envía confirmación
8. Redirect a URL de éxito con referencia de pedido
```

**Seguridad (AUDIT-SEC-001):**
- Firma HMAC-SHA256 con clave secreta (NO en config/sync, solo en runtime via getenv)
- Verificación de firma en callback con `hash_equals()` (timing-safe)
- IP whitelist de Redsys en callback endpoint
- Logging de cada operación en PaymentTransaction

#### B.2 RedsysConfig Entity

**Archivo nuevo:** `web/modules/custom/jaraba_billing/src/Entity/RedsysConfig.php`

ConfigEntity con campos: merchant_code, terminal, environment, bizum_enabled, tenant_id.

**Admin:** Form extiende PremiumEntityFormBase. Ruta en `/admin/structure/redsys-config`.

#### B.3 Bizum Button Template

**Parcial:** `_bizum-button.html.twig`

```twig
{# TWIG-INCLUDE-ONLY-001: con keyword only #}
{% if bizum_enabled %}
  <div class="ej-payment__bizum">
    <button type="button"
            class="ej-payment__bizum-btn"
            data-action="bizum-pay"
            data-amount="{{ amount }}"
            data-order-ref="{{ order_ref }}"
            aria-label="{% trans %}Pagar con Bizum{% endtrans %}">
      <svg class="ej-payment__bizum-logo" width="60" height="20" viewBox="0 0 60 20" aria-hidden="true">
        {# Logo Bizum SVG oficial #}
      </svg>
      {% trans %}Pagar con Bizum{% endtrans %}
    </button>
    <p class="ej-payment__bizum-note">
      {% trans %}Pago instantáneo con tu número de teléfono{% endtrans %}
    </p>
  </div>
{% endif %}
```

#### B.4 Callback Controller

**Archivo nuevo:** `web/modules/custom/jaraba_billing/src/Controller/RedsysCallbackController.php`

2 endpoints:
- `POST /api/v1/payments/redsys/notification` — Callback asíncrono de Redsys (server-to-server)
- `GET /api/v1/payments/redsys/return` — Redirect de vuelta al usuario tras pago

**Criterios de aceptación Fase B:**
- [ ] Botón Bizum visible en checkout de ComercioConecta
- [ ] Redirect a Redsys funcional en entorno test
- [ ] Callback de Redsys procesado correctamente
- [ ] PaymentTransaction creada con gateway=redsys, method=bizum
- [ ] Firma HMAC-SHA256 verificada
- [ ] RedsysConfig administrable desde UI
- [ ] Tests para RedsysGatewayService

---

### Fase C: WhatsApp Commerce (3-5 días)

**Objetivo:** Habilitar pedidos rápidos vía WhatsApp para AgroConecta y ComercioConecta.

#### C.1 WhatsAppCatalogSyncService

**Archivo nuevo:** `web/modules/custom/jaraba_agroconecta_core/src/Service/WhatsAppCatalogSyncService.php`

**Responsabilidad:** Sincronizar productos Drupal → Meta Commerce Manager Catalog.

**API de Meta:** `POST https://graph.facebook.com/v18.0/{catalog_id}/items_batch`

Campos sincronizados por producto:
- `retailer_id` → product entity ID
- `name` → product label
- `description` → product description
- `availability` → in stock / out of stock
- `price` → precio en céntimos EUR (ej: 1250 = €12.50)
- `currency` → 'EUR'
- `image_link` → URL pública de la imagen principal
- `url` → URL del producto en la tienda web

**Cron:** Sincronización cada 6 horas via QueueWorker.

#### C.2 WhatsAppOrderService

**Archivo nuevo:** `web/modules/custom/jaraba_agroconecta_core/src/Service/WhatsAppOrderService.php`

**Responsabilidad:** Procesar mensajes de WhatsApp que contienen intención de compra.

**Flujo:**
1. Webhook recibe mensaje del cliente
2. Detectar intención de compra (keywords: "comprar", "pedir", "quiero", "precio")
3. Si intención detectada → enviar catálogo con template `product_list`
4. Cliente selecciona producto(s) → crear `OrderRetailAgro` entity con status `pending_payment`
5. Generar payment link Stripe (hosted checkout) → enviar vía WhatsApp
6. Cliente paga → webhook Stripe → actualizar orden → confirmar vía WhatsApp

#### C.3 Botón "Pedir por WhatsApp"

**Parcial:** `_whatsapp-order-button.html.twig`

```twig
{% if whatsapp_enabled and whatsapp_phone %}
  <a href="https://wa.me/{{ whatsapp_phone }}?text={{ whatsapp_message|url_encode }}"
     class="ej-payment__whatsapp-btn"
     target="_blank"
     rel="noopener"
     aria-label="{% trans %}Pedir por WhatsApp{% endtrans %}">
    <svg class="ej-payment__whatsapp-icon" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
      {# WhatsApp icon SVG #}
    </svg>
    {% trans %}Pedir por WhatsApp{% endtrans %}
  </a>
{% endif %}
```

**Pre-format del mensaje:** `"Hola, me interesa {product_name} (€{price}). ¿Está disponible?"`

**Criterios de aceptación Fase C:**
- [ ] Catálogo sincronizado con Meta Commerce Manager
- [ ] Botón WhatsApp visible en fichas de producto
- [ ] Deep link abre WhatsApp con mensaje pre-formateado
- [ ] Pedidos vía WhatsApp crean OrderRetailAgro
- [ ] Payment link Stripe enviado vía WhatsApp
- [ ] Tests para WhatsAppOrderService

---

### Fase D: UX Unificada + Setup Wizard + Daily Actions (2-3 días)

#### D.1 Setup Wizard Step: "Configura tus métodos de pago"

**Archivo nuevo:** `web/modules/custom/jaraba_billing/src/SetupWizard/PaymentMethodsStep.php`

- `getWizardId()`: Wizards de marketplace (comercioconecta, agroconecta, serviciosconecta)
- `getLabel()`: "Configura tus métodos de pago"
- `getDescription()`: "Activa Bizum, Apple Pay y otros métodos para maximizar tus ventas"
- `isComplete()`: TRUE si tenant tiene al menos 2 métodos de pago habilitados
- `getRoute()`: Ruta a PaymentMethodConfigForm

#### D.2 Daily Action: "Revisa tus métodos de pago"

**Archivo nuevo:** `web/modules/custom/jaraba_billing/src/DailyActions/ReviewPaymentMethodsAction.php`

- Visible cuando: tenant solo tiene tarjeta habilitada (sin Bizum/Apple Pay)
- Enlaza a: Configuración de métodos de pago
- Color: impulse (naranja)

#### D.3 Indicador en Dashboard

Añadir KPI "Método de pago más usado" y "Conversión por método" en los dashboards de marketplace.

**Criterios de aceptación Fase D:**
- [ ] Wizard step visible en marketplaces
- [ ] Daily action visible cuando solo hay tarjeta
- [ ] Métricas de método de pago en dashboard

---

## 6. Tabla de Correspondencia con Especificaciones

| Especificación | Sección | Requisito | Fase | Archivos |
|---------------|---------|-----------|------|----------|
| Doc 134 §3 | Stripe Billing | Automatic payment methods | A | CheckoutSessionService |
| Doc 134 §5 | Webhooks | Procesamiento de métodos adicionales | A | BillingWebhookController |
| Doc 68 §4 | Checkout Flow | Express Checkout + Bizum | A+B | express-checkout.js, bizum-checkout.js |
| Doc 50 §3 | AgroConecta Checkout | Automatic payment methods | A | StripePaymentService |
| Doc 158 §5.1 | Stripe Products | Compatibilidad con nuevos métodos | A | Sin cambios (productos ya existen) |
| SETUP-WIZARD-DAILY-001 | Wizard | Paso de configuración de pagos | D | PaymentMethodsStep |
| SETUP-WIZARD-DAILY-001 | Daily Actions | Acción de revisión de métodos | D | ReviewPaymentMethodsAction |
| CSP-STRIPE-SCRIPT-001 | CSP | js.stripe.com en script-src | — | Ya implementado |
| AUDIT-SEC-001 | Webhooks | HMAC + hash_equals | B | RedsysCallbackController |
| SECRET-MGMT-001 | Secretos | Clave Redsys via getenv() | B | settings.secrets.php |

---

## 7. Cumplimiento de Directrices

| Directriz | Cómo se cumple |
|-----------|----------------|
| **NO-HARDCODE-PRICE-001** | Precios desde entities, NUNCA en templates |
| **CSS-VAR-ALL-COLORS-001** | SCSS nuevo usa `var(--ej-*, fallback)` |
| **SCSS-001** | `@use '../variables' as *;` en cada parcial |
| **SCSS-COMPILE-VERIFY-001** | Recompilar tras cada edición SCSS |
| **SCSS-COLORMIX-001** | `color-mix()` en vez de `rgba()` |
| **ICON-CONVENTION-001** | `jaraba_icon()` con variant duotone |
| **TWIG-INCLUDE-ONLY-001** | `{% include %}` con `only` keyword |
| **i18n {% trans %}** | Textos traducibles en bloques |
| **ZERO-REGION-001** | Variables via preprocess, no controller |
| **ROUTE-LANGPREFIX-001** | `Url::fromRoute()` en PHP, `drupalSettings` en JS |
| **SLIDE-PANEL-RENDER-001** | Config forms abren en slide-panel |
| **PREMIUM-FORMS-PATTERN-001** | PaymentMethodConfigForm extiende PremiumEntityFormBase |
| **TENANT-001** | Queries filtran por tenant |
| **TENANT-ISOLATION-ACCESS-001** | ACH verifica tenant match |
| **SECRET-MGMT-001** | Clave Redsys via getenv(), NUNCA en config/sync |
| **AUDIT-SEC-001** | HMAC-SHA256 + hash_equals en callbacks |
| **CSRF-API-001** | `_csrf_request_header_token: 'TRUE'` en rutas API |
| **UPDATE-HOOK-REQUIRED-001** | hook_update_N() para nuevas entities |
| **UPDATE-HOOK-CATCH-001** | `\Throwable` en catch blocks |
| **CONTROLLER-READONLY-001** | No readonly en inherited props |
| **OPTIONAL-CROSSMODULE-001** | `@?` para dependencias cross-módulo |
| **CSP-STRIPE-SCRIPT-001** | js.stripe.com en CSP (ya implementado) |
| **INNERHTML-XSS-001** | Drupal.checkPlain() para datos API |
| **ENTITY-FK-001** | FKs cross-módulo como integer |
| **Mobile-first** | Botones de pago rápido diseñados para thumb-tap 48×48px |

---

## 8. Testing y Verificación

### 8.1 Tests Unitarios

| Test | Verifica |
|------|---------|
| `RedsysGatewayServiceTest` | Generación de firma HMAC, parámetros, URLs |
| `PaymentGatewayManagerTest` | Dispatching correcto por método de pago |
| `WhatsAppCatalogSyncServiceTest` | Formato de datos para Meta API |
| `WhatsAppOrderServiceTest` | Detección de intención de compra, creación de orden |
| `PaymentMethodConfigServiceTest` | Resolución de métodos por vertical/tenant |

### 8.2 Tests de Integración

| Test | Verifica |
|------|---------|
| Stripe Express Checkout | Botones visibles en checkout pages |
| Redsys entorno test | Redirect + callback con Bizum simulado |
| WhatsApp webhook | Procesamiento de mensaje entrante → orden |

### 8.3 RUNTIME-VERIFY-001

| Check | Verificación |
|-------|-------------|
| CSS compilado | timestamp CSS > SCSS |
| Rutas accesibles | /api/v1/payments/bizum/initiate, /api/v1/payments/redsys/notification |
| JS ↔ HTML | data-action="bizum-pay" match en JS y template |
| drupalSettings | stripePublicKey, bizumEnabled, redsysFormUrl inyectados |
| Apple Pay domain verification | /.well-known/ accesible |

---

## 9. Checklist IMPLEMENTATION-CHECKLIST-001

### Completitud
- [ ] PaymentTransaction entity con ACH + hook_theme + Views data
- [ ] RedsysConfig entity con form + list builder + routing
- [ ] PaymentMethodConfig entity con form + routing
- [ ] Servicios registrados en services.yml con @? cross-módulo
- [ ] hook_update_N() para nuevas entities
- [ ] SCSS compilado, libraries registradas
- [ ] JS con drupalSettings (no URLs hardcoded)

### Integridad
- [ ] Tests unitarios para 5 servicios nuevos
- [ ] hook_update_N() para PaymentTransaction, RedsysConfig, PaymentMethodConfig
- [ ] Config export para ConfigEntities

### Pipeline E2E (PIPELINE-E2E-001)
- [ ] L1: Services inyectados en controllers
- [ ] L2: Controllers pasan datos al render array
- [ ] L3: hook_theme() declara variables
- [ ] L4: Templates renderizan con textos traducidos

---

## 10. Seguridad y Compliance

### 10.1 PCI DSS

- **Stripe**: Nivel 1 PCI DSS certified. Datos de tarjeta NUNCA tocan nuestros servidores.
- **Redsys**: Nivel 1 PCI DSS. Redirect mode = datos de tarjeta/Bizum procesados por Redsys.
- **Jaraba**: Solo almacena referencias (provider_reference), NUNCA datos de tarjeta/cuenta.

### 10.2 PSD2 / SCA (Strong Customer Authentication)

- **Apple Pay / Google Pay**: SCA built-in (biometría del dispositivo)
- **Bizum**: SCA built-in (PIN Bizum + confirmación en app bancaria)
- **Stripe Card**: 3D Secure 2 gestionado automáticamente por Stripe
- **SEPA**: No requiere SCA para mandatos

### 10.3 Protección de Datos

- SECRET-MGMT-001: Claves Redsys/WhatsApp via `getenv()` en settings.secrets.php
- AUDIT-SEC-001: Webhooks con HMAC-SHA256 + hash_equals()
- TENANT-ISOLATION-ACCESS-001: PaymentTransaction aislada por tenant
- Logging: Todas las transacciones registradas (sin datos de tarjeta)

---

## 11. Impacto de Negocio

### 11.1 Métricas Esperadas

| Métrica | Antes | Después (estimado) |
|---------|-------|---------------------|
| Métodos de pago | 1 (tarjeta) | 7 (tarjeta + Apple/Google Pay + Bizum + Link + SEPA + WhatsApp) |
| Conversión checkout mobile | ~45% | ~65% (+20pp) |
| Abandono checkout | ~55% | ~35% (-20pp) |
| Ventas vía WhatsApp | 0% | 15-25% del GMV marketplace |
| Transacciones Bizum | 0% | 20-30% en marketplace España |
| Satisfacción NPS (pagos) | ~35 | ~55 (+20) |

### 11.2 Scorecard Objetivo

| Dimensión | Antes | Después |
|-----------|-------|---------|
| Tarjeta de crédito/débito | 5/5 | 5/5 |
| Apple Pay | 0/5 | **5/5** |
| Google Pay | 0/5 | **5/5** |
| Bizum | 0/5 | **5/5** |
| WhatsApp Commerce | 1/5 | **4/5** |
| SEPA | 0/5 | **4/5** |
| Stripe Link | 0/5 | **5/5** |
| **TOTAL** | **6/35 (17%)** | **33/35 (94%)** |

---

*Plan de implementación generado para Jaraba Impact Platform. Fuentes técnicas: Stripe Documentation (Express Checkout Element, Payment Element, Apple Pay), Redsys Developers (API REST, Bizum), Meta Cloud API (WhatsApp Business, Commerce Catalog). Marzo 2026.*

Sources:
- [Stripe Express Checkout Element](https://docs.stripe.com/elements/express-checkout-element)
- [Stripe Payment Element](https://docs.stripe.com/payments/payment-element)
- [Stripe Apple Pay](https://docs.stripe.com/apple-pay?platform=web)
- [Stripe Dynamic Payment Methods](https://docs.stripe.com/payments/payment-methods/dynamic-payment-methods)
- [Redsys API REST](https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-tipos-de-integracion/desarrolladores-rest/)
- [Redsys Bizum](https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-otros-metodos-de-pago/desarrolladores-bizum-1/)
- [WhatsApp Catalog API](https://zixflow.com/blog/whatsapp-catalog-api-for-ecommerce/)
- [WhatsApp Business API 2026](https://chatarmin.com/en/blog/whats-app-business-api-integration)
