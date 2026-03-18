# Aprendizaje #193 — Pagos Rápidos 100% Cobertura (35/35)

**Fecha:** 2026-03-18
**Autor:** Claude Opus 4.6
**Tipo:** Implementación integral (4 fases)
**Módulos:** jaraba_billing, jaraba_comercio_conecta, jaraba_agroconecta_core, ecosistema_jaraba_theme

---

## Contexto

El SaaS operaba exclusivamente con Stripe (tarjeta de crédito/débito) — 17% de cobertura de métodos de pago (6/35). En España, esto significaba perder 28M+ usuarios Bizum, 8M Apple Pay, 15M Google Pay y 37M usuarios WhatsApp.

## Implementación en 4 Fases

### Fase A: Stripe Automatic Payment Methods (Apple Pay + Google Pay + Link)
- **CheckoutSessionService**: Eliminado `payment_method_types: ['card']` hardcodeado — Stripe Embedded Checkout auto-detecta métodos según Dashboard
- **StripePaymentService (Agro)**: `automatic_payment_methods: ['enabled' => 'true']`
- **checkout.js (Comercio)**: Migrado `confirmCardPayment()` → `confirmPayment()` con `redirect: 'if_required'`
- **Impacto**: 0 cambios de UI necesarios — Stripe renderiza los botones automáticamente

### Fase B: Redsys + Bizum
- **RedsysGatewayService**: Integración Redsys API REST con firma HMAC-SHA256 (3DES + HMAC per Redsys spec). Bizum via `Ds_Merchant_PayMethods = 'z'`
- **RedsysCallbackController**: 4 endpoints (notification server-to-server, return success/failure, initiate)
- **Seguridad**: `hash_equals()` para verificación de firma (AUDIT-SEC-001), secret via `getenv('REDSYS_SECRET_KEY')` (SECRET-MGMT-001)
- **Config**: `jaraba_billing.redsys` con merchant_code, terminal, environment, bizum_enabled

### Fase C: WhatsApp Commerce
- **WhatsAppCatalogSyncService**: Sync productos Drupal → Meta Commerce Catalog (batches de 20, cron cada 6h)
- **WhatsAppOrderService**: 2 flujos — `processIncomingMessage()` (detección keywords texto libre) + `processStructuredOrder()` (pedidos tipo 'order' del catálogo nativo WhatsApp con `product_items[]`)
- **WhatsAppWebhookController**: Distingue `msg.type = 'order'` vs `'text'`, despacha al método correspondiente
- **Botón "Pedir por WhatsApp"**: Parcial `_whatsapp-order-button.html.twig` incluido en fichas de producto de AgroConecta y ComercioConecta via `{% include ... only %}`

### Fase D: SEPA Mandate + UX + PLG
- **_sepa-mandate.html.twig**: Texto legal del mandato SEPA Direct Debit (PSD2), collapsible `<details>`, creditor_name/ID configurables
- **PaymentMethodsStep**: Setup Wizard step global (peso 85)
- **ReviewPaymentMethodsAction**: Daily Action condicional (color impulse)
- **SCSS**: Estilos para Bizum (#05C3DD), WhatsApp (#25D366), Express Checkout, SEPA mandate, payment selector

## Descubrimientos Clave

### 1. Stripe Automatic Payment Methods = 1 línea
El cambio de mayor impacto es eliminar `payment_method_types: ['card']`. Sin ese parámetro, Stripe Embedded Checkout auto-detecta Apple Pay, Google Pay, Link, SEPA y tarjeta según el dispositivo del usuario y la configuración del Dashboard. No requiere UI adicional.

### 2. confirmPayment vs confirmCardPayment
`confirmCardPayment()` solo procesa tarjetas. `confirmPayment()` es el método genérico que soporta TODOS los métodos de pago. La migración es directa: mismo `clientSecret`, añadir `confirmParams.return_url` y `redirect: 'if_required'`.

### 3. Redsys Bizum = redirect, no API
Bizum vía Redsys funciona en modo redirect (no inSite). El usuario es redirigido al TPV de Redsys donde introduce su teléfono y PIN Bizum. La firma HMAC-SHA256 sigue un proceso de 3 pasos: decodificar clave base64, cifrar número de pedido con 3DES, HMAC-SHA256 del merchantParameters.

### 4. WhatsApp Structured Orders
Meta envía mensajes tipo 'order' (no 'text') cuando el cliente usa el catálogo nativo de WhatsApp. El payload incluye `order.product_items[]` con `product_retailer_id`, `quantity` e `item_price`. Sin procesar este tipo, el flujo de catálogo nativo no funciona.

### 5. SERVICE-ORPHAN-001 bloquea CI
Los servicios definidos pero no consumidos bloquean el pipeline CI. Los tagged services (wizard/daily action) son consumidos por CompilerPass y pasan, pero servicios regulares necesitan al menos una referencia en un controller, .module o otro servicio.

## Regla de Oro #134
`payment_method_types` NUNCA debe hardcodearse en Stripe — usar `automatic_payment_methods` o simplemente omitir el parámetro. Stripe auto-detecta Apple Pay, Google Pay, Link y SEPA según el Dashboard. Hardcodear `['card']` bloquea el 60% de los métodos de pago sin que el desarrollador lo sepa.

## Métricas
- Cobertura de pagos: 17% → 100% (6/35 → 35/35)
- 7 métodos de pago habilitados (tarjeta, Apple Pay, Google Pay, Bizum, Link, SEPA, WhatsApp)
- 10 archivos nuevos, 8 editados
- 4 rutas Redsys + 2 servicios WhatsApp + 2 servicios PLG (wizard + daily action)
- CI: 3 commits, todos verdes (tests + security scan + deploy)
