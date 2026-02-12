# Plan de Implementación ComercioConecta v1.0

> **Fecha:** 2026-02-09
> **Última actualización:** 2026-02-09
> **Autor:** Claude Opus 4.6
> **Versión:** 1.0.0
> **Estado:** Planificación inicial
> **Vertical:** ComercioConecta (Marketplace de Comercio de Proximidad)
> **Módulo principal:** `jaraba_comercio_conecta`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
- [2. Tabla de Correspondencia con Especificaciones Técnicas](#2-tabla-de-correspondencia-con-especificaciones-técnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
- [4. Arquitectura del Módulo](#4-arquitectura-del-módulo)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 1: Commerce Core + Catálogo + Merchant Profile](#6-fase-1-commerce-core--catálogo--merchant-profile)
  - [6.1 Justificación](#61-justificación)
  - [6.2 Entidades](#62-entidades)
  - [6.3 Taxonomías](#63-taxonomías)
  - [6.4 Services](#64-services)
  - [6.5 Controllers](#65-controllers)
  - [6.6 Templates y Parciales Twig](#66-templates-y-parciales-twig)
  - [6.7 Frontend Assets (JS + SCSS)](#67-frontend-assets-js--scss)
  - [6.8 Archivos a Crear](#68-archivos-a-crear)
  - [6.9 Archivos a Modificar](#69-archivos-a-modificar)
  - [6.10 SCSS: Directrices](#610-scss-directrices)
  - [6.11 Verificación](#611-verificación)
- [7. FASE 2: Orders + Checkout + Payments](#7-fase-2-orders--checkout--payments)
  - [7.1 Justificación](#71-justificación)
  - [7.2 Entidades](#72-entidades)
  - [7.3 Services](#73-services)
  - [7.4 Controllers](#74-controllers)
  - [7.5 Templates Twig](#75-templates-twig)
  - [7.6 Frontend Assets](#76-frontend-assets)
  - [7.7 Archivos a Crear/Modificar](#77-archivos-a-crearmodificar)
  - [7.8 Verificación](#78-verificación)
- [8. FASE 3: Merchant Portal + Customer Portal](#8-fase-3-merchant-portal--customer-portal)
  - [8.1 Justificación](#81-justificación)
  - [8.2 Controllers](#82-controllers)
  - [8.3 Templates y Parciales](#83-templates-y-parciales)
  - [8.4 Frontend Assets](#84-frontend-assets)
  - [8.5 Verificación](#85-verificación)
- [9. FASE 4: Search + Discovery + Local SEO](#9-fase-4-search--discovery--local-seo)
- [10. FASE 5: Promotions + Flash Offers + QR Dinámico](#10-fase-5-promotions--flash-offers--qr-dinámico)
- [11. FASE 6: Reviews + Ratings + Notificaciones](#11-fase-6-reviews--ratings--notificaciones)
- [12. FASE 7: Shipping & Logistics + POS Integration](#12-fase-7-shipping--logistics--pos-integration)
- [13. FASE 8: Admin Panel + Analytics Dashboard](#13-fase-8-admin-panel--analytics-dashboard)
- [14. FASE 9: API Integration + SDKs + Webhooks](#14-fase-9-api-integration--sdks--webhooks)
- [15. FASE 10: Mobile App (React Native)](#15-fase-10-mobile-app-react-native)
- [16. Paleta de Colores y Design Tokens del Vertical](#16-paleta-de-colores-y-design-tokens-del-vertical)
- [17. Patrón de Iconos SVG](#17-patrón-de-iconos-svg)
- [18. Orden de Implementación Global](#18-orden-de-implementación-global)
- [19. Registro de Cambios](#19-registro-de-cambios)

---

## 1. Resumen Ejecutivo

ComercioConecta es la vertical de **comercio de proximidad** del Ecosistema Jaraba. Su propósito es crear un "Sistema Operativo de Barrio" que conecta comercios locales (tiendas de ropa, ferreterías, librerías, panaderías artesanales, etc.) con consumidores en un modelo omnicanal (físico + digital). A diferencia de AgroConecta (centrada en productores agroalimentarios con trazabilidad de campo a mesa), ComercioConecta se enfoca en:

- **Marketplace multi-vendor local**: Múltiples comercios de proximidad agrupados por zona/barrio en una plataforma unificada.
- **Omnicanalidad**: Click & Collect, Ship-from-Store, reserva en tienda, pagos mixtos (online + TPV físico).
- **Integración POS**: Sincronización bidireccional de stock con terminales punto de venta existentes.
- **Flash Offers geolocalizadas**: Ofertas relámpago vinculadas a horarios comerciales y proximidad GPS.
- **QR Dinámico**: Códigos QR inteligentes con redirección contextual, A/B testing y captura de leads.
- **SEO Local**: Optimización para "cerca de mí", Google Business Profile, Schema.org LocalBusiness.

### Relación con la infraestructura existente

El módulo `jaraba_comercio_conecta` se construye sobre la infraestructura multi-tenant ya operativa en `ecosistema_jaraba_core` (entidades Tenant, Vertical, SaasPlan, Feature) y sobre el bridge de Commerce existente en `jaraba_commerce` (auto-creación de tiendas Stripe Connect por Tenant). Sigue el patrón probado de `jaraba_agroconecta_core` como referencia arquitectónica, pero **NO depende directamente de él** — cada vertical es un módulo autónomo.

### Patrón arquitectónico de referencia

Se replica el modelo de AgroConecta:
- Módulo autocontenido con entidades propias (NO extiende Drupal Commerce entities)
- Entidades ContentEntity con Field UI y Views integration
- Controllers con frontend limpio (templates Twig sin regiones Drupal)
- CRUD vía slide-panel modales
- API REST completa por cada entidad
- Hooks en `.module` para automatizaciones (NO ECA BPMN)
- SCSS con variables inyectables y Dart Sass moderno

---

## 2. Tabla de Correspondencia con Especificaciones Técnicas

Cada documento técnico de la carpeta `docs/tecnicos/` con prefijo `20260117b-` tiene correspondencia directa con una o más fases de este plan.

| Doc # | Título Especificación | Fase | Entidades Principales | Reutilización AgroConecta |
|-------|----------------------|------|----------------------|--------------------------|
| **62** | Commerce Core | Fase 1 | ProductRetail, ProductVariationRetail, StockLocation, MerchantProfile | 70% (ProductAgro → ProductRetail) |
| **63** | POS Integration | Fase 7 | PosConnection, PosSync, PosConflict | 0% (Nuevo) |
| **64** | Flash Offers | Fase 5 | FlashOffer, FlashOfferClaim | 0% (Nuevo) |
| **65** | Dynamic QR | Fase 5 | QrCodeRetail, QrScanEvent, QrLeadCapture | 60% (QrCodeAgro → QrCodeRetail) |
| **66** | Product Catalog | Fase 1 | ProductAttribute, AttributeValue, ImportJob | 75% (Patrón catálogo) |
| **67** | Order System | Fase 2 | OrderRetail, OrderItemRetail, SuborderRetail, ReturnRequest | 70% (OrderAgro → OrderRetail) |
| **68** | Checkout Flow | Fase 2 | Cart, CartItem, CouponRedemption, AbandonedCart | 65% (Patrón checkout) |
| **69** | Shipping & Logistics | Fase 7 | ShipmentRetail, ShippingMethodRetail, ShippingZone, CarrierConfig | 60% (Patrón shipping) |
| **70** | Search & Discovery | Fase 4 | SearchIndex, SearchSynonym, SearchLog | 70% (AgroSearchService) |
| **71** | Local SEO | Fase 4 | LocalBusinessProfile, NapEntry, CitationLog | 0% (Nuevo) |
| **72** | Promotions & Coupons | Fase 5 | PromotionRetail, CouponRetail, LoyaltyTransaction | 50% (PromotionAgro) |
| **73** | Reviews & Ratings | Fase 6 | ReviewRetail, QuestionAnswer | 65% (ReviewAgro → ReviewRetail) |
| **74** | Merchant Portal | Fase 3 | — (controllers + templates) | 0% (Nuevo, diferente a ProducerPortal) |
| **75** | Customer Portal | Fase 3 | CustomerProfile, Wishlist, WishlistItem | 60% (CustomerPreferenceAgro) |
| **76** | Notifications System | Fase 6 | NotificationTemplate, NotificationLog, NotificationPreference, PushSubscription | Similar a AgroConecta |
| **77** | Mobile App | Fase 10 | — (React Native, fuera de Drupal) | 0% (Nuevo) |
| **78** | Admin Panel | Fase 8 | ModerationQueue, IncidentTicket, PayoutRecord | 0% (Nuevo) |
| **79** | API Integration Guide | Fase 9 | ApiClient, WebhookSubscription, WebhookLog, RateLimitConfig | 0% (Nuevo) |

---

## 3. Cumplimiento de Directrices del Proyecto

Esta sección documenta cómo cada directriz crítica del proyecto se implementa en ComercioConecta.

### 3.1 Directriz: Textos de interfaz siempre traducibles (i18n)

| Contexto | Método | Ejemplo ComercioConecta |
|----------|--------|------------------------|
| PHP Controllers | `$this->t()` | `$this->t('Panel del Comerciante')` |
| Twig Templates | `{% trans %}` | `{% trans %}Mis Pedidos{% endtrans %}` |
| JavaScript | `Drupal.t()` | `Drupal.t('Pedido confirmado')` |
| Formularios | `'#title' => $this->t(...)` | `'#title' => $this->t('Nombre del producto')` |
| Breadcrumbs | `$this->t()` en Link | `Link::createFromRoute($this->t('Marketplace'), ...)` |

**Regla**: Nunca texto hardcodeado en templates ni en JS. La prioridad es pasar textos desde el controller con `$this->t()`. Si no es posible, usar `{% trans %}` en Twig.

### 3.2 Directriz: Modelo SCSS con variables inyectables

| Capa | Archivo | Función |
|------|---------|---------|
| **1. Tokens SCSS** | `ecosistema_jaraba_core/scss/_variables.scss` | Valores fallback en tiempo de compilación |
| **2. CSS Custom Properties** | `ecosistema_jaraba_core/scss/_injectable.scss` | Variables `:root` con prefijo `--ej-*` |
| **3. Parcial del módulo** | `jaraba_comercio_conecta/scss/_*.scss` | Consume `var(--ej-*, fallback)`, NO define `$ej-*` |
| **4. Override por Tenant** | `hook_preprocess_html()` | Inyecta `<style>:root { --ej-color-primary: #{valor}; }</style>` |
| **5. Preset Vertical** | ConfigEntity `StylePreset` | Paleta `comercio_barrio`, `comercio_boutique`, etc. |

**Regla inquebrantable**: El módulo `jaraba_comercio_conecta` NUNCA define variables `$ej-*`. Solo consume `var(--ej-*, fallback)`.

**Ejemplo correcto:**
```scss
@use 'sass:color';

.comercio-marketplace__card {
  background: var(--ej-bg-surface, #FFFFFF);
  border: 1px solid var(--ej-border-color, #E5E7EB);
  border-radius: var(--ej-radius-xl, 16px);
  color: var(--ej-text-body, #334155);
}
```

**Ejemplo INCORRECTO (prohibido):**
```scss
$ej-color-comercio: #FF8C42; // PROHIBIDO: definir $ej-* en módulo satélite
```

### 3.3 Directriz: Dart Sass moderno

```scss
// CORRECTO - Dart Sass moderno
@use 'sass:color';
$hover-color: color.adjust($base-color, $lightness: -10%);

// PROHIBIDO - Funciones deprecadas
$hover-color: darken($base-color, 10%); // NUNCA usar darken()
$light-color: lighten($base-color, 10%); // NUNCA usar lighten()
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

Todas las páginas frontend de ComercioConecta (marketplace, portales, checkout) usan templates Twig limpios:

- `page--comercio-*.html.twig` en el tema
- Header y footer vía `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}`
- Sin `page.sidebar_first`, sin `page.sidebar_second`
- Layout full-width con `max-width: 1400px` centrado
- Mobile-first responsive design
- Sin acceso al tema de administración de Drupal para el tenant

### 3.5 Directriz: Body classes vía hook_preprocess_html()

```php
// En ecosistema_jaraba_theme.theme (función EXISTENTE, añadir cases)
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    $route = \Drupal::routeMatch()->getRouteName();
    // Rutas de ComercioConecta
    $comercio_routes = [
        'jaraba_comercio_conecta.marketplace' => 'page-comercio-marketplace',
        'jaraba_comercio_conecta.product_detail' => 'page-comercio-product',
        'jaraba_comercio_conecta.merchant_portal' => 'page-comercio-merchant',
        'jaraba_comercio_conecta.customer_portal' => 'page-comercio-customer',
        'jaraba_comercio_conecta.checkout' => 'page-comercio-checkout',
        'jaraba_comercio_conecta.search' => 'page-comercio-search',
    ];
    if (isset($comercio_routes[$route])) {
        $variables['attributes']['class'][] = $comercio_routes[$route];
    }
}
```

### 3.6 Directriz: CRUD en modales slide-panel

Todas las acciones de crear/editar/ver en páginas frontend abren en slide-panel:

```html
<button data-slide-panel="nuevo-producto"
        data-slide-panel-url="/comercio/producto/add/ajax"
        data-slide-panel-title="{{ 'Nuevo Producto'|trans }}">
  + {% trans %}Crear Producto{% endtrans %}
</button>
```

El controller detecta peticiones AJAX y devuelve solo el formulario HTML sin page wrapper:
```php
if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($form);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
}
```

### 3.7 Directriz: Entidades con Field UI y Views

Todas las ContentEntity de ComercioConecta incluyen:
- `views_data` handler en la anotación `@ContentEntityType`
- `field_ui_base_route` apuntando a la ruta de settings
- Navegación completa: routing.yml + links.menu.yml + links.task.yml + links.action.yml
- Collection en `/admin/content/comercio-*`
- Structure en `/admin/structure/comercio-*`

### 3.8 Directriz: No hardcodear configuración de negocio

| Dato | Mecanismo | Ejemplo |
|------|-----------|---------|
| Comisiones | Campo en MerchantProfile + Config del Tenant | `commission_rate` editable por admin |
| Zonas de envío | Entidad ShippingZone con Form UI | Administrable sin código |
| Categorías | Vocabulario Taxonomy | Gestionable desde `/admin/structure/taxonomy` |
| Horarios comerciales | Campos en MerchantProfile | Editable por el comerciante |
| Umbral stock bajo | Campo en MerchantProfile | Configurable por comercio |
| Textos footer/header | Theme Settings (Config de tema) | Parciales Twig con variables de `theme_settings` |

### 3.9 Directriz: Parciales Twig reutilizables

Antes de crear código para una sección de página, verificar:
1. ¿Existe ya un parcial en `templates/partials/` del tema? (header, footer, copilot-fab, slide-panel)
2. ¿Necesito crear un parcial nuevo que se reutilice en varias páginas?
3. ¿El parcial usa variables configurables desde la UI de Drupal (theme_settings)?

**Parciales existentes** que ComercioConecta reutiliza directamente:
- `_header.html.twig` → Dispatcher multi-layout (classic, minimal, transparent)
- `_footer.html.twig` → Multi-layout (minimal, standard, mega, split)
- `_slide-panel.html.twig` → Panel deslizante para CRUD
- `_copilot-fab.html.twig` → Botón flotante copiloto IA

**Parciales nuevos** que ComercioConecta necesita crear:
- `_comercio-product-card.html.twig` → Card de producto con precio, badge, merchant
- `_comercio-merchant-card.html.twig` → Card de comercio con logo, rating, distancia
- `_comercio-order-status.html.twig` → Badge de estado de pedido con iconos
- `_comercio-review-stars.html.twig` → Widget estrellas interactivo
- `_comercio-cart-mini.html.twig` → Mini-carrito desplegable
- `_comercio-breadcrumb.html.twig` → Breadcrumb con Schema.org
- `_comercio-flash-offer-countdown.html.twig` → Temporizador flash offer

### 3.10 Directriz: Seguridad

- API keys de Stripe, carriers, FCM en `settings.local.php`, NUNCA en config exportable
- Rate limiting en endpoints LLM/embedding: 100 req/hora RAG, 50 req/hora Copilot
- Webhooks con verificación HMAC obligatoria
- Validación backend vía Form API para todo input de usuario
- Sanitización de output con `Html::escape()`
- Sin `_access: 'TRUE'` en endpoints con datos de tenant
- Parámetros de ruta con restricción regex

### 3.11 Directriz: Comentarios de código

Todos los archivos PHP siguen el estándar de 3 dimensiones:
1. **Estructura**: Organización, relaciones, jerarquía padre-hijo
2. **Lógica**: Propósito (POR QUÉ), flujo, reglas de negocio
3. **Sintaxis**: Parámetros con tipos, retornos, excepciones

Idioma de comentarios: **Español**. Idioma de variables/funciones: **Inglés**.

---

## 4. Arquitectura del Módulo

### 4.1 Nombre y ubicación

```
web/modules/custom/jaraba_comercio_conecta/
```

### 4.2 Dependencias

```yaml
# jaraba_comercio_conecta.info.yml
name: 'Jaraba ComercioConecta'
type: module
description: 'Vertical ComercioConecta: Marketplace de comercio de proximidad con omnicanalidad, POS, flash offers y SEO local.'
package: 'Jaraba Verticals'
core_version_requirement: ^10.3 || ^11
dependencies:
  - drupal:user
  - drupal:file
  - drupal:views
  - drupal:field_ui
  - drupal:taxonomy
  - ecosistema_jaraba_core:ecosistema_jaraba_core
```

> **Nota**: NO depende de `drupal:commerce` ni de `jaraba_commerce`. Las entidades de orden y producto son propias, siguiendo el patrón de AgroConecta.

### 4.3 Estructura de directorios

```
jaraba_comercio_conecta/
├── config/
│   └── install/                        # Taxonomías y config inicial
│       ├── taxonomy.vocabulary.comercio_category.yml
│       ├── taxonomy.vocabulary.comercio_brand.yml
│       └── taxonomy.vocabulary.comercio_attribute.yml
├── css/
│   └── jaraba-comercio-conecta.css     # Compilado (NUNCA editar directamente)
├── images/
│   └── icons/                          # SVG propios del vertical
│       ├── store.svg
│       ├── store-duotone.svg
│       ├── shopping-bag.svg
│       ├── shopping-bag-duotone.svg
│       ├── barcode.svg
│       ├── barcode-duotone.svg
│       ├── flash-sale.svg
│       ├── flash-sale-duotone.svg
│       ├── qr-code.svg
│       ├── qr-code-duotone.svg
│       ├── location-pin.svg
│       ├── location-pin-duotone.svg
│       ├── pos-terminal.svg
│       ├── pos-terminal-duotone.svg
│       ├── delivery-truck.svg
│       └── delivery-truck-duotone.svg
├── js/
│   ├── marketplace.js
│   ├── checkout.js
│   ├── merchant-portal.js
│   ├── customer-portal.js
│   ├── flash-offers.js
│   ├── reviews.js
│   ├── search.js
│   └── notifications.js
├── scss/
│   ├── main.scss                       # Punto de entrada, importa todos los parciales
│   ├── _variables-comercio.scss        # Variables locales (sin $ej-*, solo CSS vars)
│   ├── _marketplace.scss
│   ├── _product-detail.scss
│   ├── _checkout.scss
│   ├── _merchant-portal.scss
│   ├── _customer-portal.scss
│   ├── _flash-offers.scss
│   ├── _reviews.scss
│   ├── _search.scss
│   ├── _notifications.scss
│   ├── _admin-dashboard.scss
│   ├── _shipping.scss
│   └── _qr-dynamic.scss
├── src/
│   ├── Access/
│   │   ├── ProductRetailAccessControlHandler.php
│   │   ├── OrderRetailAccessControlHandler.php
│   │   ├── MerchantProfileAccessControlHandler.php
│   │   ├── ReviewRetailAccessControlHandler.php
│   │   ├── FlashOfferAccessControlHandler.php
│   │   └── ... (un handler por entidad)
│   ├── Controller/
│   │   ├── MarketplaceController.php
│   │   ├── ProductApiController.php
│   │   ├── CheckoutController.php
│   │   ├── OrderApiController.php
│   │   ├── MerchantPortalController.php
│   │   ├── CustomerPortalController.php
│   │   ├── SearchController.php
│   │   ├── SearchApiController.php
│   │   ├── ReviewApiController.php
│   │   ├── FlashOfferApiController.php
│   │   ├── QrController.php
│   │   ├── ShippingApiController.php
│   │   ├── NotificationApiController.php
│   │   ├── PromotionApiController.php
│   │   ├── AdminDashboardController.php
│   │   ├── PosApiController.php
│   │   └── WebhookController.php
│   ├── Entity/
│   │   ├── ProductRetail.php
│   │   ├── ProductVariationRetail.php
│   │   ├── StockLocation.php
│   │   ├── MerchantProfile.php
│   │   ├── OrderRetail.php
│   │   ├── OrderItemRetail.php
│   │   ├── SuborderRetail.php
│   │   ├── ReturnRequest.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── CustomerProfile.php
│   │   ├── Wishlist.php
│   │   ├── WishlistItem.php
│   │   ├── ReviewRetail.php
│   │   ├── QuestionAnswer.php
│   │   ├── PromotionRetail.php
│   │   ├── CouponRetail.php
│   │   ├── LoyaltyTransaction.php
│   │   ├── FlashOffer.php
│   │   ├── FlashOfferClaim.php
│   │   ├── QrCodeRetail.php
│   │   ├── QrScanEvent.php
│   │   ├── QrLeadCapture.php
│   │   ├── ShipmentRetail.php
│   │   ├── ShippingMethodRetail.php
│   │   ├── ShippingZone.php
│   │   ├── LocalBusinessProfile.php
│   │   ├── NotificationTemplateRetail.php
│   │   ├── NotificationLogRetail.php
│   │   ├── NotificationPreferenceRetail.php
│   │   ├── PushSubscription.php
│   │   ├── PosConnection.php
│   │   ├── AnalyticsDailyRetail.php
│   │   ├── ModerationQueue.php
│   │   ├── IncidentTicket.php
│   │   ├── ApiClient.php
│   │   ├── WebhookSubscription.php
│   │   └── WebhookLog.php
│   ├── Form/
│   │   ├── ProductRetailForm.php
│   │   ├── ProductRetailSettingsForm.php
│   │   ├── MerchantProfileForm.php
│   │   ├── MerchantProfileSettingsForm.php
│   │   ├── OrderRetailForm.php
│   │   ├── ReviewRetailForm.php
│   │   ├── FlashOfferForm.php
│   │   ├── PromotionRetailForm.php
│   │   ├── CouponRetailForm.php
│   │   ├── ShippingZoneForm.php
│   │   ├── NotificationTemplateRetailForm.php
│   │   ├── PosConnectionForm.php
│   │   └── ComercioConectaSettingsForm.php
│   ├── ListBuilder/
│   │   ├── ProductRetailListBuilder.php
│   │   ├── OrderRetailListBuilder.php
│   │   ├── MerchantProfileListBuilder.php
│   │   ├── ReviewRetailListBuilder.php
│   │   ├── FlashOfferListBuilder.php
│   │   ├── PromotionRetailListBuilder.php
│   │   ├── NotificationTemplateRetailListBuilder.php
│   │   └── ... (un listbuilder por entidad)
│   └── Service/
│       ├── MarketplaceService.php
│       ├── ProductRetailService.php
│       ├── OrderService.php
│       ├── CheckoutService.php
│       ├── StripePaymentService.php
│       ├── MerchantDashboardService.php
│       ├── CustomerPortalService.php
│       ├── SearchService.php
│       ├── LocalSeoService.php
│       ├── ReviewService.php
│       ├── PromotionService.php
│       ├── FlashOfferService.php
│       ├── QrService.php
│       ├── ShippingService.php
│       ├── NotificationService.php
│       ├── PosIntegrationService.php
│       ├── AnalyticsService.php
│       ├── WebhookService.php
│       └── ApiClientService.php
├── templates/
│   ├── comercio-marketplace.html.twig
│   ├── comercio-product-detail.html.twig
│   ├── comercio-checkout.html.twig
│   ├── comercio-order-confirmation.html.twig
│   ├── comercio-merchant-dashboard.html.twig
│   ├── comercio-merchant-orders.html.twig
│   ├── comercio-merchant-products.html.twig
│   ├── comercio-merchant-analytics.html.twig
│   ├── comercio-customer-dashboard.html.twig
│   ├── comercio-customer-orders.html.twig
│   ├── comercio-customer-order-detail.html.twig
│   ├── comercio-customer-wishlist.html.twig
│   ├── comercio-search-page.html.twig
│   ├── comercio-admin-dashboard.html.twig
│   └── partials/
│       ├── _comercio-product-card.html.twig
│       ├── _comercio-merchant-card.html.twig
│       ├── _comercio-order-status.html.twig
│       ├── _comercio-review-stars.html.twig
│       ├── _comercio-cart-mini.html.twig
│       ├── _comercio-breadcrumb.html.twig
│       ├── _comercio-flash-offer-countdown.html.twig
│       ├── _comercio-notification-bell.html.twig
│       └── _comercio-notification-prefs.html.twig
├── jaraba_comercio_conecta.info.yml
├── jaraba_comercio_conecta.install
├── jaraba_comercio_conecta.libraries.yml
├── jaraba_comercio_conecta.links.action.yml
├── jaraba_comercio_conecta.links.menu.yml
├── jaraba_comercio_conecta.links.task.yml
├── jaraba_comercio_conecta.module
├── jaraba_comercio_conecta.permissions.yml
├── jaraba_comercio_conecta.routing.yml
├── jaraba_comercio_conecta.services.yml
├── package.json
└── package-lock.json
```

### 4.4 Compilación SCSS

```json
// package.json
{
  "name": "jaraba-comercio-conecta",
  "version": "1.0.0",
  "scripts": {
    "build": "sass scss/main.scss:css/jaraba-comercio-conecta.css --style=compressed",
    "watch": "sass scss/main.scss:css/jaraba-comercio-conecta.css --watch"
  },
  "devDependencies": {
    "sass": "^1.80.0"
  }
}
```

**Comando de compilación (ejecutar dentro del contenedor Docker):**
```bash
cd /app/web/modules/custom/jaraba_comercio_conecta
# Compilación SCSS desde WSL (NO desde Docker, Docker no tiene node)
# En WSL:
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts
npm install
chmod +x node_modules/.bin/sass
npm run build
# Luego en Docker:
lando drush cr
```

---

## 5. Estado por Fases

| Fase | Descripción | Docs Técnicos | Estado | Entidades | Dependencia |
|------|-------------|---------------|--------|-----------|-------------|
| **1** | Commerce Core: Catálogo, ProductRetail, MerchantProfile, StockLocation | 62, 66 | 🔶 **Planificada** | ProductRetail, ProductVariationRetail, StockLocation, MerchantProfile | ecosistema_jaraba_core |
| **2** | Orders + Checkout: OrderRetail, Payments Stripe Connect | 67, 68 | ⬜ Futura | OrderRetail, OrderItemRetail, SuborderRetail, Cart, CartItem, ReturnRequest | Fase 1 |
| **3** | Merchant Portal + Customer Portal: Dashboards frontend | 74, 75 | ⬜ Futura | CustomerProfile, Wishlist, WishlistItem | Fase 2 |
| **4** | Search + Discovery + Local SEO | 70, 71 | ⬜ Futura | LocalBusinessProfile, SearchSynonym | Fase 1 |
| **5** | Promotions + Flash Offers + QR Dinámico | 64, 65, 72 | ⬜ Futura | PromotionRetail, CouponRetail, FlashOffer, FlashOfferClaim, QrCodeRetail, QrScanEvent, QrLeadCapture, LoyaltyTransaction | Fase 2 |
| **6** | Reviews + Ratings + Notificaciones | 73, 76 | ⬜ Futura | ReviewRetail, QuestionAnswer, NotificationTemplate/Log/Preference, PushSubscription | Fase 3 |
| **7** | Shipping & Logistics + POS Integration | 63, 69 | ⬜ Futura | ShipmentRetail, ShippingMethodRetail, ShippingZone, PosConnection | Fase 2 + Credenciales carriers |
| **8** | Admin Panel + Analytics Dashboard | 78 | ⬜ Futura | ModerationQueue, IncidentTicket, AnalyticsDailyRetail | Fase 3 |
| **9** | API Integration + SDKs + Webhooks | 79 | ⬜ Futura | ApiClient, WebhookSubscription, WebhookLog | Fase 2 |
| **10** | Mobile App (React Native + Expo) | 77 | ⬜ Futura | — (externo a Drupal) | Fase 9 (API completa) |

---

## 6. FASE 1: Commerce Core + Catálogo + Merchant Profile

### 6.1 Justificación

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Base fundacional: sin productos ni comercios no hay marketplace |
| **Dependencias externas** | Ninguna bloqueante (Stripe se integra en Fase 2) |
| **Entidades** | 4 (ProductRetail, ProductVariationRetail, StockLocation, MerchantProfile) |
| **Complejidad** | 🔴 Alta (entidades con muchos campos, variaciones, multi-ubicación stock) |
| **Referencia AgroConecta** | ProductAgro, ProducerProfile (70% reutilizable) |

### 6.2 Entidades

#### 6.2.1 Entidad `ProductRetail`

**Tipo:** ContentEntity
**ID:** `product_retail`
**Base table:** `product_retail`

##### Annotation & Handlers

| Handler | Clase |
|---------|-------|
| `list_builder` | `ProductRetailListBuilder` |
| `views_data` | `Drupal\views\EntityViewsData` |
| `form.default/add/edit` | `ProductRetailForm` |
| `form.delete` | `ContentEntityDeleteForm` |
| `access` | `ProductRetailAccessControlHandler` |
| `route_provider.html` | `AdminHtmlRouteProvider` |

##### Campos (28)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal único |
| `uid` | entity_reference (user) | ✅ | Usuario creador |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant obligatorio |
| `merchant_id` | entity_reference (merchant_profile) | ✅ | Comercio propietario del producto, FK, INDEX |
| `title` | string(255) | ✅ | Nombre del producto, indexado para búsqueda |
| `sku` | string(64) | ✅ | SKU principal, UNIQUE por tenant+merchant |
| `description` | text_long | ✅ | Descripción completa del producto con formato |
| `short_description` | string(500) | ❌ | Descripción corta para listados y SEO meta |
| `category_id` | entity_reference (taxonomy: comercio_category) | ✅ | Categoría principal (vocabulario jerárquico) |
| `brand_id` | entity_reference (taxonomy: comercio_brand) | ❌ | Marca (vocabulario plano) |
| `images` | image (multi-value, max 10) | ✅ | Galería de imágenes, primera = principal |
| `price` | decimal(10,2) | ✅ | Precio base en EUR |
| `compare_at_price` | decimal(10,2) | ❌ | Precio anterior (para mostrar descuento tachado) |
| `cost_price` | decimal(10,2) | ❌ | Coste para el comerciante (cálculo de margen, no público) |
| `tax_rate` | list_string | ✅ | Tipo IVA: `general_21`, `reducido_10`, `superreducido_4`, `exento_0` |
| `weight` | decimal(8,3) | ❌ | Peso en kg para cálculo de envío |
| `dimensions_length` | decimal(8,2) | ❌ | Largo en cm |
| `dimensions_width` | decimal(8,2) | ❌ | Ancho en cm |
| `dimensions_height` | decimal(8,2) | ❌ | Alto en cm |
| `barcode_type` | list_string | ❌ | Tipo de código: `ean13`, `ean8`, `upc`, `isbn`, `internal` |
| `barcode_value` | string(32) | ❌ | Valor del código de barras |
| `has_variations` | boolean | ✅ | Si tiene variaciones (tallas, colores). Default FALSE |
| `stock_quantity` | integer | ✅ | Stock total (suma de todas las ubicaciones). Default 0 |
| `low_stock_threshold` | integer | ❌ | Umbral de stock bajo, default 5 |
| `status` | list_string | ✅ | `draft`, `active`, `paused`, `out_of_stock`, `archived` |
| `seo_title` | string(70) | ❌ | Meta title para SEO |
| `seo_description` | string(160) | ❌ | Meta description para SEO |
| `created` | created | ✅ | Fecha de creación |
| `changed` | changed | ✅ | Fecha de última modificación |

##### Navegación Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `routing.yml` | `entity.product_retail.collection` | `/admin/content/comercio-products` |
| `links.task.yml` | Tab "Productos Comercio" | `base_route: system.admin_content`, weight: 50 |
| `links.menu.yml` | Structure "Productos Comercio" | `parent: system.admin_structure`, weight: 80 |
| `links.action.yml` | Botón "Añadir Producto" | `appears_on: entity.product_retail.collection` |

##### Permisos

```yaml
manage comercio products:
  title: 'Gestionar productos ComercioConecta'
  restrict access: true

view comercio products:
  title: 'Ver productos ComercioConecta'

create comercio products:
  title: 'Crear productos ComercioConecta'

edit own comercio products:
  title: 'Editar productos propios ComercioConecta'
```

---

#### 6.2.2 Entidad `ProductVariationRetail`

**Tipo:** ContentEntity
**ID:** `product_variation_retail`
**Base table:** `product_variation_retail`

##### Campos (16)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `product_id` | entity_reference (product_retail) | ✅ | Producto padre, FK con cascade delete |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `sku` | string(64) | ✅ | SKU de la variación, UNIQUE por tenant |
| `title` | string(255) | ✅ | Nombre descriptivo (ej: "Camiseta Azul - Talla M") |
| `price` | decimal(10,2) | ✅ | Precio de la variación (override del producto padre) |
| `compare_at_price` | decimal(10,2) | ❌ | Precio anterior para esta variación |
| `image` | image | ❌ | Imagen específica de la variación |
| `attributes` | map (JSON) | ✅ | Pares clave-valor de atributos: `{"color": "Azul", "talla": "M"}` |
| `barcode_value` | string(32) | ❌ | Código de barras específico de la variación |
| `stock_quantity` | integer | ✅ | Stock de esta variación (suma de ubicaciones). Default 0 |
| `weight` | decimal(8,3) | ❌ | Peso override si diferente al padre |
| `status` | list_string | ✅ | `active`, `inactive`, `out_of_stock` |
| `sort_order` | integer | ❌ | Orden de presentación. Default 0 |
| `created` | created | ✅ | Fecha de creación |
| `changed` | changed | ✅ | Fecha de modificación |

##### Navegación Admin

| YAML | Clave | Path |
|------|-------|------|
| `routing.yml` | `entity.product_variation_retail.collection` | `/admin/content/comercio-variations` |
| `links.task.yml` | Tab "Variaciones Comercio" | `base_route: system.admin_content`, weight: 51 |
| `links.menu.yml` | Structure "Variaciones Comercio" | `parent: system.admin_structure`, weight: 81 |
| `links.action.yml` | Botón "Añadir Variación" | `appears_on: entity.product_variation_retail.collection` |

---

#### 6.2.3 Entidad `StockLocation`

**Tipo:** ContentEntity
**ID:** `stock_location`
**Base table:** `stock_location`

Entidad **exclusiva de ComercioConecta** (no existe en AgroConecta). Permite gestionar stock en múltiples puntos: tienda física, almacén trasero, y reserva para canal online.

##### Campos (14)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `merchant_id` | entity_reference (merchant_profile) | ✅ | Comercio propietario, FK |
| `name` | string(100) | ✅ | Nombre de la ubicación (ej: "Tienda Principal", "Almacén") |
| `type` | list_string | ✅ | `storefront` (tienda), `warehouse` (almacén), `online_reserve` (reserva online) |
| `address` | text | ❌ | Dirección física de la ubicación |
| `latitude` | decimal(10,7) | ❌ | Coordenada latitud para geolocalización |
| `longitude` | decimal(10,7) | ❌ | Coordenada longitud para geolocalización |
| `is_pickup_point` | boolean | ✅ | Si acepta Click & Collect. Default FALSE |
| `is_ship_from` | boolean | ✅ | Si se puede enviar desde esta ubicación. Default FALSE |
| `priority` | integer | ✅ | Prioridad de fulfillment (1 = primero). Default 1 |
| `is_active` | boolean | ✅ | Estado activo. Default TRUE |
| `created` | created | ✅ | Fecha de creación |

##### Navegación Admin

| YAML | Clave | Path |
|------|-------|------|
| `routing.yml` | `entity.stock_location.collection` | `/admin/content/comercio-stock-locations` |
| `links.task.yml` | Tab "Ubicaciones Stock" | `base_route: system.admin_content`, weight: 52 |
| `links.menu.yml` | Structure "Ubicaciones Stock" | `parent: system.admin_structure`, weight: 82 |
| `links.action.yml` | Botón "Añadir Ubicación" | `appears_on: entity.stock_location.collection` |

---

#### 6.2.4 Entidad `MerchantProfile`

**Tipo:** ContentEntity
**ID:** `merchant_profile`
**Base table:** `merchant_profile`

Perfil del comerciante con toda la información operativa y de presentación pública.

##### Campos (32)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `uid` | entity_reference (user) | ✅ | Usuario administrador del comercio |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `business_name` | string(255) | ✅ | Nombre comercial (razón social o nombre público) |
| `slug` | string(128) | ✅ | Slug URL-friendly, UNIQUE por tenant |
| `business_type` | list_string | ✅ | `retail`, `food`, `services`, `crafts`, `other` |
| `description` | text_long | ✅ | Descripción del comercio para su página pública |
| `logo` | image | ❌ | Logo del comercio (recomendado 400x400px) |
| `cover_image` | image | ❌ | Imagen de portada (recomendado 1200x400px) |
| `gallery` | image (multi-value, max 6) | ❌ | Galería de fotos del comercio |
| `tax_id` | string(20) | ✅ | CIF/NIF del comercio |
| `phone` | string(20) | ✅ | Teléfono de contacto |
| `email` | email | ✅ | Email de contacto |
| `website` | uri | ❌ | Web propia del comercio |
| `address_street` | string(255) | ✅ | Dirección: calle y número |
| `address_city` | string(100) | ✅ | Ciudad |
| `address_postal_code` | string(10) | ✅ | Código postal |
| `address_province` | string(100) | ✅ | Provincia |
| `address_country` | string(2) | ✅ | País (ISO 3166-1 alpha-2). Default 'ES' |
| `latitude` | decimal(10,7) | ❌ | Coordenada latitud (geocodificación auto) |
| `longitude` | decimal(10,7) | ❌ | Coordenada longitud |
| `opening_hours` | map (JSON) | ❌ | Horarios por día: `{"lunes": {"open": "09:00", "close": "20:00"}, ...}` |
| `accepts_click_collect` | boolean | ✅ | Si ofrece recogida en tienda. Default FALSE |
| `delivery_radius_km` | decimal(5,1) | ❌ | Radio de reparto propio en km |
| `commission_rate` | decimal(5,2) | ❌ | Comisión específica (override del tenant default) |
| `stripe_account_id` | string(64) | ❌ | ID de Stripe Connect Express |
| `stripe_onboarding_complete` | boolean | ✅ | Onboarding Stripe completado. Default FALSE |
| `average_rating` | decimal(3,2) | ❌ | Media de rating calculada (desnormalizado para rendimiento) |
| `total_reviews` | integer | ❌ | Total de reseñas (desnormalizado). Default 0 |
| `verification_status` | list_string | ✅ | `pending`, `documents_submitted`, `under_review`, `approved`, `rejected`, `suspended` |
| `is_active` | boolean | ✅ | Comercio activo en el marketplace. Default FALSE |
| `created` | created | ✅ | Fecha de creación |
| `changed` | changed | ✅ | Fecha de modificación |

##### Navegación Admin

| YAML | Clave | Path |
|------|-------|------|
| `routing.yml` | `entity.merchant_profile.collection` | `/admin/content/comercio-merchants` |
| `links.task.yml` | Tab "Comerciantes" | `base_route: system.admin_content`, weight: 53 |
| `links.menu.yml` | Structure "Comerciantes" | `parent: system.admin_structure`, weight: 83 |
| `links.action.yml` | Botón "Añadir Comerciante" | `appears_on: entity.merchant_profile.collection` |

##### Permisos

```yaml
manage comercio merchants:
  title: 'Gestionar comerciantes ComercioConecta'
  restrict access: true

view comercio merchants:
  title: 'Ver comerciantes ComercioConecta'

edit own merchant profile:
  title: 'Editar perfil propio de comerciante'

view merchant analytics:
  title: 'Ver analíticas del comerciante'
```

---

### 6.3 Taxonomías

#### 6.3.1 `comercio_category` — Categorías de Producto Retail

Vocabulario **jerárquico** con hasta 3 niveles de profundidad. Estructura orientada a comercio minorista general (no agrícola).

**Términos raíz (Nivel 1):**
- Moda y Complementos
- Hogar y Decoración
- Alimentación y Gourmet
- Electrónica y Tecnología
- Salud y Belleza
- Deportes y Aire Libre
- Juguetes y Niños
- Libros y Papelería
- Mascotas
- Artesanía y Regalos

Cada término tiene campos adicionales: `field_icon` (SVG), `field_seo_description` (text), `field_featured` (boolean).

#### 6.3.2 `comercio_brand` — Marcas

Vocabulario **plano** para marcas comerciales. Campos: `field_logo` (image), `field_website` (uri).

#### 6.3.3 `comercio_attribute` — Atributos de Producto

Vocabulario **jerárquico** de 2 niveles para atributos de variación.

**Términos raíz:**
- Color → (Rojo, Azul, Verde, Negro, Blanco, Gris, Marrón, Rosa, Amarillo, Morado, Naranja, Beige, Multicolor)
- Talla → (XS, S, M, L, XL, XXL, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, Única)
- Material → (Algodón, Poliéster, Cuero, Madera, Metal, Cerámica, Vidrio, Plástico)
- Acabado → (Mate, Brillante, Satinado, Texturizado)

---

### 6.4 Services

#### 6.4.1 `MarketplaceService`

```
getMarketplaceProducts(tenant_id, filters, sort, page, per_page):
  → Listado paginado de productos activos del marketplace
  → Filtros: category, brand, price_min, price_max, merchant, in_stock
  → Ordenación: relevance, price_asc, price_desc, newest, rating
  → Devuelve: array de productos con merchant info embebida

getMerchants(tenant_id, filters, sort):
  → Listado de comercios activos
  → Filtros: business_type, nearby (lat/lng + radius), rating_min
  → Ordenación: distance, rating, newest, alphabetical

getMerchantBySlug(tenant_id, slug):
  → Perfil público del comercio con productos destacados

getMarketplaceStats(tenant_id):
  → Estadísticas: total productos, total comercios, total categorías
```

#### 6.4.2 `ProductRetailService`

```
createProduct(merchant_id, data):
  → Crea producto con validaciones (SKU único por tenant+merchant)
  → Auto-genera slug URL desde título
  → Si has_variations=true, no exige stock_quantity en producto padre

updateProduct(product_id, data):
  → Actualiza con verificación de ownership (merchant_id match)
  → Recalcula stock si cambian variaciones

getProductDetail(product_id):
  → Producto completo con variaciones, imágenes, merchant info, schema.org

getProductVariations(product_id):
  → Variaciones con stock por ubicación

createVariation(product_id, data):
  → Crea variación, actualiza stock total del padre

updateStock(product_id_or_variation_id, location_id, quantity, reason):
  → Ajuste de stock en una ubicación específica
  → Motivos: sale, return, adjustment, transfer, pos_sync
  → Registra movimiento para auditoría

importProducts(merchant_id, csv_file):
  → Importación masiva desde CSV
  → Mapeo de columnas configurable
  → Validación en lote con reporte de errores

exportProducts(merchant_id, format):
  → Exportación en CSV o JSON para backup/integración
```

#### 6.4.3 `MerchantDashboardService`

```
getMerchantDashboard(merchant_id):
  → KPIs: ventas hoy, ventas mes, pedidos pendientes, stock bajo, rating
  → Gráficos: tendencia ventas 30 días, top productos, distribución categorías

getStockAlerts(merchant_id):
  → Productos con stock <= low_stock_threshold
  → Productos sin stock (stock_quantity = 0)

getRecentOrders(merchant_id, limit):
  → Últimos N pedidos con estado y total
```

### 6.5 Controllers

#### 6.5.1 `MarketplaceController`

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/marketplace` | GET | Página principal del marketplace con filtros |
| `/marketplace/{merchant_slug}` | GET | Página pública del comercio |
| `/marketplace/producto/{product_id}` | GET | Página detalle de producto |

Cada ruta devuelve un render array con `#theme` apuntando al template Twig correspondiente. Para peticiones AJAX, devuelve solo el HTML del contenido.

#### 6.5.2 `ProductApiController`

| Método | Path | Permiso |
|--------|------|---------|
| `GET` | `/api/v1/comercio/products` | `view comercio products` |
| `GET` | `/api/v1/comercio/products/{id}` | `view comercio products` |
| `POST` | `/api/v1/comercio/products` | `create comercio products` |
| `PATCH` | `/api/v1/comercio/products/{id}` | `edit own comercio products` |
| `DELETE` | `/api/v1/comercio/products/{id}` | `manage comercio products` |
| `GET` | `/api/v1/comercio/products/{id}/variations` | `view comercio products` |
| `POST` | `/api/v1/comercio/products/{id}/variations` | `edit own comercio products` |
| `POST` | `/api/v1/comercio/stock/update` | `edit own comercio products` |
| `POST` | `/api/v1/comercio/stock/bulk-update` | `edit own comercio products` |
| `GET` | `/api/v1/comercio/merchants` | `view comercio merchants` |
| `GET` | `/api/v1/comercio/merchants/{id}` | `view comercio merchants` |
| `GET` | `/api/v1/comercio/merchants/nearby` | acceso público (marketplace público) |

### 6.6 Templates y Parciales Twig

#### 6.6.1 Template: `comercio-marketplace.html.twig`

**Función:** Página principal del marketplace con grid de productos, filtros laterales responsive, y barra de búsqueda.

**Estructura:**
```twig
{# Marketplace - Página principal de ComercioConecta #}
{# Usa layout full-width sin regiones Drupal #}
{# Todos los textos son traducibles con {% trans %} #}

{# Sección hero con buscador #}
<section class="comercio-marketplace__hero">
  <h1>{% trans %}Descubre tu comercio de barrio{% endtrans %}</h1>
  <div class="comercio-marketplace__search-bar">
    {# Buscador con autocompletado #}
  </div>
</section>

{# Grid de filtros + productos #}
<div class="comercio-marketplace__grid">
  <aside class="comercio-marketplace__filters">
    {# Filtros: categoría, precio, marca, distancia, rating #}
  </aside>
  <main class="comercio-marketplace__products">
    {% for product in products %}
      {% include '@jaraba_comercio_conecta/partials/_comercio-product-card.html.twig'
         with { product: product } %}
    {% endfor %}
    {# Paginación #}
  </main>
</div>
```

**Variables del controller:**
- `products` — Array de productos paginados
- `categories` — Árbol de categorías para filtro
- `brands` — Lista de marcas
- `merchants` — Comercios del marketplace
- `current_filters` — Filtros activos
- `total_results` — Total de resultados

#### 6.6.2 Parcial: `_comercio-product-card.html.twig`

**Función:** Card de producto reutilizable en marketplace, búsqueda, y merchant page.

**Variables:**
- `product.title` — Nombre
- `product.price` — Precio formateado
- `product.compare_at_price` — Precio tachado (si descuento)
- `product.images[0]` — Imagen principal
- `product.merchant.business_name` — Nombre del comercio
- `product.merchant.slug` — Para enlace al comercio
- `product.average_rating` — Estrellas
- `product.status` — Para badges ("Nuevo", "Agotado")

**Premium Card Pattern:** Aplica glassmorphism + hover lift según directriz.

#### 6.6.3 Parcial: `_comercio-merchant-card.html.twig`

**Función:** Card de comercio reutilizable.

**Variables:**
- `merchant.business_name` — Nombre
- `merchant.logo` — Logo
- `merchant.business_type` — Tipo para badge
- `merchant.address_city` — Ciudad
- `merchant.average_rating` — Rating
- `merchant.total_reviews` — Número de reseñas
- `merchant.distance_km` — Distancia (si geolocalización activa)
- `merchant.accepts_click_collect` — Badge Click & Collect

### 6.7 Frontend Assets

#### 6.7.1 Libraries (jaraba_comercio_conecta.libraries.yml)

```yaml
global:
  version: 1.0
  css:
    theme:
      css/jaraba-comercio-conecta.css: { minified: true }
  dependencies:
    - ecosistema_jaraba_core/global
    - ecosistema_jaraba_theme/slide-panel

marketplace:
  version: 1.0
  js:
    js/marketplace.js: { minified: false }
  dependencies:
    - core/drupal
    - core/once
    - jaraba_comercio_conecta/global

checkout:
  version: 1.0
  js:
    js/checkout.js: { minified: false }
  dependencies:
    - core/drupal
    - core/once
    - jaraba_comercio_conecta/global

merchant-portal:
  version: 1.0
  js:
    js/merchant-portal.js: { minified: false }
  dependencies:
    - core/drupal
    - core/once
    - jaraba_comercio_conecta/global

customer-portal:
  version: 1.0
  js:
    js/customer-portal.js: { minified: false }
  dependencies:
    - core/drupal
    - core/once
    - jaraba_comercio_conecta/global
```

### 6.8 Archivos a Crear (Fase 1: 35 archivos)

| Categoría | # | Archivos |
|-----------|---|----------|
| **Entities** | 4 | `ProductRetail.php`, `ProductVariationRetail.php`, `StockLocation.php`, `MerchantProfile.php` |
| **ListBuilders** | 4 | Uno por entidad |
| **AccessHandlers** | 4 | Uno por entidad |
| **Forms** | 6 | 4 EntityForms + 2 SettingsForms (ProductRetailSettingsForm, MerchantProfileSettingsForm) |
| **Services** | 3 | `MarketplaceService`, `ProductRetailService`, `MerchantDashboardService` |
| **Controllers** | 2 | `MarketplaceController`, `ProductApiController` |
| **Templates** | 4 | `comercio-marketplace`, `comercio-product-detail`, 2 parciales (product-card, merchant-card) |
| **JS** | 1 | `marketplace.js` |
| **SCSS** | 4 | `main.scss`, `_variables-comercio.scss`, `_marketplace.scss`, `_product-detail.scss` |
| **Config** | 3 | 3 taxonomy vocabularies |
| **Module files** | 4 | `info.yml`, `module`, `package.json`, `install` |

### 6.9 Archivos a Modificar (Fase 1: 5 archivos del tema)

| Archivo | Cambios |
|---------|---------|
| `ecosistema_jaraba_theme.theme` | +6 rutas en `hook_preprocess_html()` para body classes + `hook_theme_suggestions_page_alter()` para templates de comercio |
| `templates/page--comercio-marketplace.html.twig` | Nuevo template de página limpia con header/footer parciales |
| `templates/page--comercio-product.html.twig` | Nuevo template de página de producto |

Y los archivos propios del módulo:
| Archivo | Cambios |
|---------|---------|
| `routing.yml` | Todas las rutas de Fase 1 (~15 rutas) |
| `services.yml` | 3 services + logger channel |
| `libraries.yml` | Libraries global + marketplace |
| `.module` | `hook_theme()` + `hook_entity_insert()` + `hook_cron()` |
| `permissions.yml` | Permisos de productos y merchants |
| `links.task.yml` | 4 tabs en admin/content |
| `links.menu.yml` | 4 entries en admin/structure |
| `links.action.yml` | 4 action buttons |

### 6.10 SCSS: Directrices para Fase 1

- Variables con `var(--ej-color-impulse, #FF8C42)` como color primario del vertical (naranja, por la paleta de comercio de barrio: "Cercano, colorido, amigable")
- BEM estricto: `.comercio-marketplace__*`, `.comercio-product__*`, `.comercio-merchant__*`
- Premium card glassmorphism (directriz) para product cards y merchant cards
- Grid responsive: 4 columnas desktop → 2 tablet → 1 móvil
- Hover con `translateY(-6px) scale(1.02)` y `cubic-bezier(0.175, 0.885, 0.32, 1.275)`
- Filtros laterales colapsables en móvil (hamburger pattern)
- Badge de descuento con gradiente naranja
- Badge Click & Collect con icono `store.svg`
- Rating estrellas SVG con `--ej-color-warning` (#F59E0B)
- Fuentes: headings `var(--ej-font-headings, 'Outfit')`, body `var(--ej-font-body, 'Inter')`

### 6.11 Verificación Fase 1

#### Post-Creación
1. `lando drush cr`
2. `drush scr install_entities.php` (crear tablas si no existen)
3. Verificar 4 collection routes en `/admin/content/comercio-*`
4. Verificar 4 Structure entries en `/admin/structure/comercio-*`
5. Verificar Field UI disponible en cada entidad
6. Compilar SCSS desde WSL
7. `lando drush cr` (post-SCSS)

#### Funcional
- [ ] CRUD ProductRetail via admin form
- [ ] CRUD ProductVariationRetail via admin form
- [ ] CRUD StockLocation via admin form
- [ ] CRUD MerchantProfile via admin form
- [ ] Marketplace público accesible en `/marketplace`
- [ ] Producto detalle accesible en `/marketplace/producto/{id}`
- [ ] Página de comercio en `/marketplace/{merchant_slug}`
- [ ] API GET products funcional
- [ ] API GET merchants funcional
- [ ] Template limpio sin sidebar de admin
- [ ] Body class `page-comercio-marketplace` aplicada
- [ ] Mobile responsive (verificar en viewport 375px)
- [ ] Textos traducibles (verificar en `/admin/config/regional/translate`)

---

## 7. FASE 2: Orders + Checkout + Payments

### 7.1 Justificación

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Sin pedidos no hay transacciones — core del modelo económico |
| **Dependencias externas** | Stripe Connect (credenciales necesarias) |
| **Entidades** | 6 (OrderRetail, OrderItemRetail, SuborderRetail, Cart, CartItem, ReturnRequest) |
| **Complejidad** | 🔴 Alta (checkout multi-step, split payments, Click & Collect) |
| **Referencia AgroConecta** | OrderAgro, SuborderAgro, StripePaymentService (70% reutilizable) |

### 7.2 Entidades

#### 7.2.1 Entidad `OrderRetail`

**Tipo:** ContentEntity | **ID:** `order_retail` | **Base table:** `order_retail`

##### Campos (26)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `order_number` | string(32) | ✅ | Número legible: `ORD-AAAA-XXXXXX`. UNIQUE, generado automáticamente |
| `uid` | entity_reference (user) | ✅ | Cliente que realizó el pedido |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Aislamiento multi-tenant |
| `subtotal` | decimal(10,2) | ✅ | Subtotal antes de impuestos y envío |
| `tax_amount` | decimal(10,2) | ✅ | Total IVA |
| `shipping_amount` | decimal(10,2) | ✅ | Coste de envío. 0 si Click & Collect |
| `discount_amount` | decimal(10,2) | ❌ | Total descuentos aplicados. Default 0 |
| `total` | decimal(10,2) | ✅ | Total final = subtotal + tax + shipping - discount |
| `currency` | string(3) | ✅ | Moneda ISO 4217. Default 'EUR' |
| `status` | list_string | ✅ | `pending`, `confirmed`, `processing`, `ready_pickup`, `shipped`, `out_for_delivery`, `delivered`, `cancelled`, `returned` |
| `fulfillment_type` | list_string | ✅ | `shipping` (envío), `click_collect` (recogida tienda), `local_delivery` (reparto local) |
| `payment_status` | list_string | ✅ | `pending`, `paid`, `partially_refunded`, `refunded`, `failed` |
| `payment_method` | list_string | ✅ | `stripe_card`, `stripe_ideal`, `cash_on_delivery`, `in_store` |
| `stripe_payment_intent_id` | string(64) | ❌ | ID del PaymentIntent de Stripe |
| `shipping_name` | string(255) | ❌ | Nombre del destinatario |
| `shipping_address` | text | ❌ | Dirección de envío formateada |
| `shipping_phone` | string(20) | ❌ | Teléfono del destinatario |
| `billing_name` | string(255) | ❌ | Nombre de facturación |
| `billing_address` | text | ❌ | Dirección de facturación |
| `billing_tax_id` | string(20) | ❌ | NIF/CIF para factura |
| `pickup_location_id` | entity_reference (stock_location) | ❌ | Punto de recogida si Click & Collect |
| `pickup_code` | string(8) | ❌ | Código de recogida para C&C. Auto-generado |
| `notes` | text | ❌ | Notas del cliente |
| `created` | created | ✅ | Fecha del pedido |
| `changed` | changed | ✅ | Última actualización |

##### Navegación Admin

| YAML | Clave | Path |
|------|-------|------|
| `routing.yml` | `entity.order_retail.collection` | `/admin/content/comercio-orders` |
| `links.task.yml` | Tab "Pedidos Comercio" | `base_route: system.admin_content`, weight: 54 |
| `links.menu.yml` | Structure "Pedidos Comercio" | `parent: system.admin_structure`, weight: 84 |
| `links.action.yml` | Botón "Crear Pedido Manual" | `appears_on: entity.order_retail.collection` |

#### 7.2.2 Entidad `OrderItemRetail`

**Tipo:** ContentEntity | **ID:** `order_item_retail` | **Base table:** `order_item_retail`

##### Campos (14)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `order_id` | entity_reference (order_retail) | ✅ | Pedido padre |
| `product_id` | entity_reference (product_retail) | ✅ | Producto |
| `variation_id` | entity_reference (product_variation_retail) | ❌ | Variación si aplica |
| `merchant_id` | entity_reference (merchant_profile) | ✅ | Comercio vendedor |
| `title` | string(255) | ✅ | Nombre del producto (snapshot al momento de compra) |
| `sku` | string(64) | ✅ | SKU (snapshot) |
| `quantity` | integer | ✅ | Cantidad |
| `unit_price` | decimal(10,2) | ✅ | Precio unitario (snapshot al momento de compra) |
| `tax_rate` | decimal(5,2) | ✅ | Tipo IVA aplicado |
| `total` | decimal(10,2) | ✅ | quantity × unit_price |
| `attributes_snapshot` | map (JSON) | ❌ | Atributos de la variación: `{"color": "Azul", "talla": "M"}` |
| `created` | created | ✅ | Fecha |

#### 7.2.3 Entidad `SuborderRetail`

**Tipo:** ContentEntity | **ID:** `suborder_retail` | **Base table:** `suborder_retail`

Sub-pedido por comerciante. Un pedido multi-vendor se divide en sub-pedidos por merchant para gestión independiente.

##### Campos (12)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `order_id` | entity_reference (order_retail) | ✅ | Pedido padre |
| `merchant_id` | entity_reference (merchant_profile) | ✅ | Comercio del sub-pedido |
| `subtotal` | decimal(10,2) | ✅ | Subtotal del sub-pedido |
| `commission_amount` | decimal(10,2) | ✅ | Comisión plataforma |
| `merchant_amount` | decimal(10,2) | ✅ | Neto para el comerciante = subtotal - commission |
| `status` | list_string | ✅ | `pending`, `confirmed`, `processing`, `ready`, `shipped`, `delivered`, `cancelled` |
| `stripe_transfer_id` | string(64) | ❌ | ID del Transfer de Stripe al merchant |
| `tracking_number` | string(64) | ❌ | Número de seguimiento del envío |
| `tracking_url` | uri | ❌ | URL de seguimiento |
| `created` | created | ✅ | Fecha |

#### 7.2.4 Entidad `Cart`

**Tipo:** ContentEntity | **ID:** `cart_retail` | **Base table:** `cart_retail`

##### Campos (8)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `uid` | entity_reference (user) | ❌ | Usuario (null para anónimos) |
| `session_id` | string(128) | ❌ | Session ID para carritos anónimos |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Multi-tenant |
| `subtotal` | decimal(10,2) | ✅ | Subtotal calculado. Default 0 |
| `item_count` | integer | ✅ | Número de items. Default 0 |
| `created` | created | ✅ | Fecha creación |
| `changed` | changed | ✅ | Última modificación |

#### 7.2.5 Entidad `CartItem`

**Tipo:** ContentEntity | **ID:** `cart_item_retail` | **Base table:** `cart_item_retail`

##### Campos (8)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | PK |
| `cart_id` | entity_reference (cart_retail) | ✅ | Carrito padre |
| `product_id` | entity_reference (product_retail) | ✅ | Producto |
| `variation_id` | entity_reference (product_variation_retail) | ❌ | Variación si aplica |
| `quantity` | integer | ✅ | Cantidad. Min 1 |
| `unit_price` | decimal(10,2) | ✅ | Precio actual |
| `created` | created | ✅ | Fecha |
| `changed` | changed | ✅ | Última modificación |

#### 7.2.6 Entidad `ReturnRequest`

**Tipo:** ContentEntity | **ID:** `return_request_retail` | **Base table:** `return_request_retail`

##### Campos (14)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `return_number` | string(32) | ✅ | Número: `RET-AAAA-XXXXXX`. UNIQUE |
| `order_id` | entity_reference (order_retail) | ✅ | Pedido original |
| `uid` | entity_reference (user) | ✅ | Cliente |
| `tenant_id` | entity_reference (taxonomy_term) | ✅ | Multi-tenant |
| `items` | map (JSON) | ✅ | Items a devolver: `[{order_item_id, quantity, reason}]` |
| `reason` | list_string | ✅ | `damaged`, `defective`, `wrong_item`, `wrong_size`, `not_as_described`, `changed_mind`, `other` |
| `reason_detail` | text | ❌ | Detalle textual del motivo |
| `return_method` | list_string | ✅ | `prepaid_label`, `in_store`, `home_pickup` |
| `refund_method` | list_string | ✅ | `original_payment`, `store_credit`, `store_credit_bonus` |
| `status` | list_string | ✅ | `pending`, `approved`, `rejected`, `in_transit`, `received`, `refunded`, `closed` |
| `refund_amount` | decimal(10,2) | ❌ | Importe a reembolsar |
| `created` | created | ✅ | Fecha |

### 7.3 Services

#### 7.3.1 `OrderService`

```
createOrder(cart_id, checkout_data):
  → Valida stock disponible para todos los items
  → Crea OrderRetail + OrderItemRetail por cada item
  → Crea SuborderRetail agrupado por merchant_id
  → Genera order_number con formato ORD-AAAA-XXXXXX
  → Si Click & Collect: genera pickup_code alfanumérico 8 chars
  → Decrementa stock de cada producto/variación
  → Vacía el carrito

getOrderDetail(order_id):
  → Order completo con items, suborders, merchant info, tracking

getOrdersByUser(uid, filters):
  → Pedidos del usuario con paginación
  → Filtros: status, date_from, date_to

getOrdersByMerchant(merchant_id, filters):
  → Pedidos del comerciante (via suborders) con paginación

updateOrderStatus(order_id, new_status):
  → Validación de transiciones de estado permitidas
  → Dispara notificación al cliente

cancelOrder(order_id):
  → Verificación: solo cancelable en status pending/confirmed
  → Reincrementa stock
  → Inicia refund si ya pagado

processReturn(return_request_id, decision):
  → Aprueba o rechaza devolución
  → Si aprobada: inicia refund en Stripe, reincrementa stock
  → Si store_credit_bonus: 110% del valor como crédito
```

#### 7.3.2 `CheckoutService`

```
initCheckout(cart_id):
  → Valida carrito no vacío
  → Recalcula precios (por si cambiaron desde que se añadieron)
  → Calcula impuestos por item (según tax_rate del producto)
  → Agrupa items por merchant para mostrar sub-totales

calculateShipping(cart_id, address, fulfillment_type):
  → Si click_collect: shipping = 0
  → Si shipping: calcula según zonas y carriers disponibles
  → Si local_delivery: calcula según radio del merchant

createPaymentIntent(order_id):
  → Crea Stripe PaymentIntent con Destination Charges
  → Application Fee = commission del merchant (o default del tenant)
  → Metadata: order_number, tenant_id, merchant_ids

confirmPayment(order_id, payment_intent_id):
  → Verifica el payment fue exitoso
  → Actualiza payment_status = 'paid'
  → Actualiza order status = 'confirmed'
  → Dispara notificaciones

abandonedCartCheck():
  → Cron job: busca carritos con items > 1h sin modificar
  → Envía push notification a 1h
  → Envía email de recuperación a 24h
```

#### 7.3.3 `StripePaymentService`

```
createPaymentIntent(amount, currency, merchant_stripe_id, application_fee):
  → Crea PaymentIntent con destination charges
  → Devuelve client_secret para frontend

handleWebhook(payload, signature):
  → Verifica firma HMAC
  → Procesa: payment_intent.succeeded, payment_intent.failed,
    charge.refunded, payout.paid, account.updated

processRefund(order_id, amount):
  → Crea Refund en Stripe
  → Actualiza payment_status del pedido
  → Registra en log financiero

getPayoutHistory(merchant_id):
  → Lista de payouts del comerciante desde Stripe

onboardMerchant(merchant_id):
  → Crea Stripe Connect Express Account
  → Genera onboarding link
  → Guarda stripe_account_id en MerchantProfile
```

### 7.4 Controllers

#### 7.4.1 `CheckoutController`

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/checkout` | GET | Página de checkout (4 pasos) |
| `/checkout/cart` | GET | Vista del carrito |
| `/checkout/confirmation/{order_id}` | GET | Página de confirmación |

#### 7.4.2 `OrderApiController`

| Método | Path | Permiso |
|--------|------|---------|
| `POST` | `/api/v1/comercio/cart/add` | authenticated |
| `PATCH` | `/api/v1/comercio/cart/update` | authenticated |
| `DELETE` | `/api/v1/comercio/cart/remove/{item_id}` | authenticated |
| `GET` | `/api/v1/comercio/cart` | authenticated |
| `POST` | `/api/v1/comercio/checkout/init` | authenticated |
| `POST` | `/api/v1/comercio/checkout/shipping` | authenticated |
| `POST` | `/api/v1/comercio/checkout/payment-intent` | authenticated |
| `POST` | `/api/v1/comercio/checkout/confirm` | authenticated |
| `GET` | `/api/v1/comercio/orders` | authenticated |
| `GET` | `/api/v1/comercio/orders/{id}` | authenticated |
| `POST` | `/api/v1/comercio/orders/{id}/cancel` | authenticated |
| `POST` | `/api/v1/comercio/returns` | authenticated |
| `GET` | `/api/v1/comercio/merchant/orders` | `view merchant analytics` |
| `PATCH` | `/api/v1/comercio/merchant/orders/{id}/status` | `edit own merchant profile` |

#### 7.4.3 `WebhookController`

| Método | Path | Permiso |
|--------|------|---------|
| `POST` | `/webhook/comercio/stripe` | `_access: 'TRUE'` (verificación HMAC interna) |

### 7.5 Templates Twig

| Template | Descripción |
|----------|-------------|
| `comercio-checkout.html.twig` | Checkout 4 pasos: Carrito → Datos envío → Pago → Confirmación |
| `comercio-order-confirmation.html.twig` | Confirmación con resumen, número pedido, tracking |
| `partials/_comercio-cart-mini.html.twig` | Mini-carrito desplegable en header |
| `partials/_comercio-order-status.html.twig` | Badge de estado con iconos SVG |

### 7.6 Frontend Assets

| Archivo | Descripción |
|---------|-------------|
| `js/checkout.js` | Lógica multi-step, validación, Stripe Elements integration |
| `scss/_checkout.scss` | Estilos del checkout, progress bar, step indicators |

### 7.7 Archivos a Crear/Modificar (Fase 2)

**Crear (32 archivos):**

| Categoría | # | Archivos |
|-----------|---|----------|
| Entities | 6 | OrderRetail, OrderItemRetail, SuborderRetail, Cart, CartItem, ReturnRequest |
| ListBuilders | 3 | Order, SuborderRetail, ReturnRequest (Cart/CartItem no necesitan) |
| AccessHandlers | 4 | Order, SuborderRetail, Cart, ReturnRequest |
| Forms | 3 | OrderRetailForm, ReturnRequestForm, OrderRetailSettingsForm |
| Services | 3 | OrderService, CheckoutService, StripePaymentService |
| Controllers | 3 | CheckoutController, OrderApiController, WebhookController |
| Templates | 4 | checkout, order-confirmation, 2 parciales |
| JS | 1 | checkout.js |
| SCSS | 1 | _checkout.scss |

**Modificar (del módulo):**

| Archivo | Cambios |
|---------|---------|
| `routing.yml` | +20 rutas (checkout + order API + webhook) |
| `services.yml` | +3 services |
| `libraries.yml` | +1 library (checkout) |
| `.module` | +3 hooks (entity_insert para notificaciones, cron para abandoned cart) |
| `permissions.yml` | +6 permisos (orders, returns, checkout) |
| `links.task.yml` | +3 tabs (orders, suborders, returns) |
| `links.menu.yml` | +3 entries |
| `links.action.yml` | +1 action (crear pedido manual) |
| `scss/main.scss` | +1 import (_checkout) |

### 7.8 Verificación Fase 2

- [ ] CRUD OrderRetail via admin form
- [ ] Carrito: añadir, modificar cantidad, eliminar items
- [ ] Checkout multi-step funcional
- [ ] Stripe PaymentIntent creation exitosa
- [ ] Webhook Stripe procesado correctamente
- [ ] Confirmación de pedido con número legible
- [ ] Click & Collect con código de recogida
- [ ] Cancelación de pedido con refund
- [ ] Solicitud de devolución via frontend
- [ ] Sub-pedidos creados correctamente por merchant
- [ ] Stock decrementado tras compra
- [ ] Carrito abandonado detectado por cron

---

## 8. FASE 3: Merchant Portal + Customer Portal

### 8.1 Justificación

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | UX diferencial: dashboards propios para comerciantes y clientes |
| **Dependencias externas** | Ninguna bloqueante |
| **Entidades** | 3 (CustomerProfile, Wishlist, WishlistItem) |
| **Complejidad** | 🟡 Media (controllers + templates, reutiliza entities de Fase 1-2) |
| **Referencia AgroConecta** | ProducerPortalController, CustomerPortalController |

### 8.2 Controllers

#### 8.2.1 `MerchantPortalController`

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/mi-comercio` | GET | Dashboard principal del comerciante |
| `/mi-comercio/pedidos` | GET | Lista de pedidos con filtros |
| `/mi-comercio/pedidos/{order_id}` | GET | Detalle de pedido |
| `/mi-comercio/productos` | GET | Gestión de catálogo |
| `/mi-comercio/productos/add` | GET | Formulario nuevo producto (slide-panel en frontend) |
| `/mi-comercio/productos/{id}/edit` | GET | Editar producto (slide-panel) |
| `/mi-comercio/stock` | GET | Gestión de inventario multi-ubicación |
| `/mi-comercio/analiticas` | GET | Dashboard de ventas, KPIs, gráficos |
| `/mi-comercio/perfil` | GET | Editar perfil del comercio |
| `/mi-comercio/equipo` | GET | Gestión de miembros del equipo |
| `/mi-comercio/pagos` | GET | Historial de pagos y liquidaciones |
| `/mi-comercio/promociones` | GET | Crear y gestionar promociones propias |

Todas estas rutas usan templates Twig limpios sin sidebar de admin. El tenant NO tiene acceso al tema de administración de Drupal.

#### 8.2.2 `CustomerPortalController`

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/mi-cuenta` | GET | Dashboard cliente |
| `/mi-cuenta/pedidos` | GET | Historial de pedidos |
| `/mi-cuenta/pedidos/{order_id}` | GET | Detalle de pedido con tracking |
| `/mi-cuenta/favoritos` | GET | Wishlists |
| `/mi-cuenta/direcciones` | GET | Gestión de direcciones |
| `/mi-cuenta/fidelidad` | GET | Programa de lealtad, puntos, nivel |
| `/mi-cuenta/devoluciones` | GET | Solicitudes de devolución |
| `/mi-cuenta/resenas` | GET | Mis reseñas |
| `/mi-cuenta/notificaciones` | GET | Centro de notificaciones |
| `/mi-cuenta/preferencias` | GET | Preferencias de notificación |
| `/mi-cuenta/perfil` | GET | Editar perfil personal |

### 8.3 Templates y Parciales

**Templates nuevos (10):**
- `comercio-merchant-dashboard.html.twig`
- `comercio-merchant-orders.html.twig`
- `comercio-merchant-products.html.twig`
- `comercio-merchant-analytics.html.twig`
- `comercio-customer-dashboard.html.twig`
- `comercio-customer-orders.html.twig`
- `comercio-customer-order-detail.html.twig`
- `comercio-customer-wishlist.html.twig`
- `comercio-search-page.html.twig` (preparación Fase 4)
- `comercio-admin-dashboard.html.twig` (preparación Fase 8)

**Patrón de dashboard merchant:**
```twig
{# Dashboard del comerciante - Layout limpio sin admin Drupal #}
{# KPIs en cards premium con glassmorphism #}
<div class="comercio-merchant__kpis">
  <div class="comercio-merchant__kpi-card">
    <span class="comercio-merchant__kpi-label">{% trans %}Ventas Hoy{% endtrans %}</span>
    <span class="comercio-merchant__kpi-value">{{ sales_today|number_format(2, ',', '.') }} €</span>
  </div>
  {# ... más KPIs #}
</div>

{# Pedidos recientes #}
<section class="comercio-merchant__recent-orders">
  <h2>{% trans %}Pedidos Recientes{% endtrans %}</h2>
  {% for order in recent_orders %}
    {% include '@jaraba_comercio_conecta/partials/_comercio-order-status.html.twig'
       with { order: order } %}
  {% endfor %}
</section>

{# Acciones rápidas via slide-panel #}
<button data-slide-panel="nuevo-producto"
        data-slide-panel-url="/mi-comercio/productos/add/ajax"
        data-slide-panel-title="{{ 'Nuevo Producto'|trans }}">
  {% trans %}Añadir Producto{% endtrans %}
</button>
```

### 8.4 Frontend Assets

| Archivo | Descripción |
|---------|-------------|
| `js/merchant-portal.js` | Gráficos de ventas (Chart.js o similar), acciones rápidas, stock alerts |
| `js/customer-portal.js` | Wishlist toggle, tracking, notificaciones |
| `scss/_merchant-portal.scss` | Estilos del portal de comerciante |
| `scss/_customer-portal.scss` | Estilos del portal de cliente |

### 8.5 Verificación Fase 3

- [ ] Dashboard comerciante funcional en `/mi-comercio`
- [ ] KPIs renderizados con datos reales
- [ ] Gestión de productos via slide-panel
- [ ] Gestión de stock multi-ubicación
- [ ] Dashboard cliente funcional en `/mi-cuenta`
- [ ] Historial de pedidos con tracking
- [ ] Wishlist funcional (añadir, eliminar, compartir)
- [ ] Template limpio (sin admin toolbar para tenant)
- [ ] Responsive verificado en móvil 375px
- [ ] Todas las traducciones verificadas

---

## 9. FASE 4: Search + Discovery + Local SEO

**Docs:** 70, 71

**Entidades:** `LocalBusinessProfile`, `SearchSynonym`

**Funcionalidades clave:**
- Búsqueda full-text con Solr/base de datos con facetas (categoría, marca, precio, rating, distancia)
- Autocompletado con sugerencias de productos, comercios y categorías
- Sinónimos en español ("zapatillas" = "deportivas" = "sneakers")
- Google Business Profile integration vía API
- Schema.org LocalBusiness para cada MerchantProfile
- NAP consistency (Name, Address, Phone) normalizado
- Structured Data Testing con JSON-LD
- Breadcrumbs con Schema.org BreadcrumbList
- Páginas de categoría con SEO optimizado

**Service: `SearchService`**
```
search(query, filters, sort, page): Búsqueda principal con facetas
autocomplete(query, limit): Sugerencias de autocompletado
getSynonyms(): Mapa de sinónimos español
logSearch(query, results_count, clicked_item): Analytics de búsqueda
```

**Service: `LocalSeoService`**
```
generateLocalBusinessSchema(merchant_profile): JSON-LD LocalBusiness
generateProductSchema(product): JSON-LD Product con offers
generateBreadcrumbSchema(path): JSON-LD BreadcrumbList
syncGoogleBusinessProfile(merchant_id): Sync con Google Business API
```

---

## 10. FASE 5: Promotions + Flash Offers + QR Dinámico

**Docs:** 64, 65, 72

**Entidades:** `PromotionRetail`, `CouponRetail`, `FlashOffer`, `FlashOfferClaim`, `QrCodeRetail`, `QrScanEvent`, `QrLeadCapture`, `LoyaltyTransaction`

**Funcionalidades clave:**
- Motor de descuentos: porcentaje, fijo, envío gratis, 2x1, bundle
- Stacking de cupones con prioridad
- Detección de abuso (mismo IP, email variations, rate limiting)
- Flash Offers vinculadas a horarios del comercio (ej: "últimas 2 horas antes de cerrar")
- Push geofenced para flash offers (radio configurable)
- Widget countdown en tiempo real
- QR dinámico con shortcode redirect y analytics
- A/B testing de destinos QR
- Captura de leads via QR
- Programa de fidelidad: puntos por compra, niveles (Bronce, Plata, Oro, Platino)
- Redención de puntos como descuento o gifts

**Service: `FlashOfferService`**
```
createFlashOffer(merchant_id, data): Crear oferta con validación de horario
getActiveFlashOffers(lat, lng, radius): Ofertas activas geolocalizadas
claimOffer(offer_id, user_id): Reclamar oferta con validación anti-abuso
checkExpiringOffers(): Cron: notificar ofertas por expirar
```

**Service: `QrService`**
```
generateQr(merchant_id, destination_url, campaign): Genera QR con shortcode
resolveQr(shortcode): Resuelve destino con analytics
logScan(qr_id, metadata): Registra escaneo con geolocalización
getQrAnalytics(qr_id): Estadísticas de escaneos
```

---

## 11. FASE 6: Reviews + Ratings + Notificaciones

**Docs:** 73, 76

**Entidades:** `ReviewRetail`, `QuestionAnswer`, `NotificationTemplateRetail`, `NotificationLogRetail`, `NotificationPreferenceRetail`, `PushSubscription`

**Funcionalidades clave:**
- Reviews de producto y de comercio con rating 1-5
- Verificación de compra automática
- Moderación de reviews (pendiente → aprobada/rechazada)
- Respuesta del comerciante (una por review)
- Q&A de productos (preguntas y respuestas)
- Media ponderada Bayesiana para ratings
- AI moderation (detección de spam, reviews falsas)
- Incentivo de review post-compra (+puntos fidelidad)
- Notificaciones multicanal: email, push web, in-app
- 30+ tipos de notificación predefinidos
- Templates editables con variables Twig
- Preferencias de notificación por canal
- Queue API con prioridad y retry exponential backoff
- Tracking de apertura y clicks

**Service: `ReviewService`** (patrón idéntico a AgroConecta)
```
submitReview(data): Crea review con verificación de compra
getProductReviews(product_id, filters): Paginado con filtros
getMerchantRating(merchant_id): Media ponderada
moderateReview(review_id, decision): Aprobar/rechazar
respondToReview(review_id, response): Respuesta del comerciante
```

**Service: `NotificationService`** (patrón idéntico a AgroConecta)
```
send(type, recipient, context, channels): Encola notificación
renderTemplate(template_key, variables): Renderiza con Twig
processQueue(): Worker del cron
```

---

## 12. FASE 7: Shipping & Logistics + POS Integration

**Docs:** 63, 69

**Entidades:** `ShipmentRetail`, `ShippingMethodRetail`, `ShippingZone`, `CarrierConfig`, `PosConnection`, `PosSync`

**Funcionalidades clave:**
- 7 carriers españoles: Correos, SEUR, MRW, GLS, DHL, UPS, FedEx
- Generación automática de etiquetas vía API de cada carrier
- Tracking en tiempo real con webhook de carrier
- Zonas de envío con tarifas por peso/volumen
- Cálculo automático de envío en checkout
- POS multi-conector: adapter pattern para diferentes TPV (Cashlogy, Zettle, SumUp, Square)
- Sincronización bidireccional de stock: POS ↔ Online
- Resolución de conflictos de stock con estrategia last-write-wins + log
- Reconciliación periódica cada 15 minutos

**Service: `ShippingService`**
```
calculateShippingOptions(cart, address): Opciones con precio y tiempo estimado
createShipment(order_id, carrier, method): Genera shipment + etiqueta
getTrackingInfo(shipment_id): Estado del envío en tiempo real
handleCarrierWebhook(carrier, payload): Actualiza estado del shipment
```

**Service: `PosIntegrationService`**
```
connect(merchant_id, pos_type, credentials): Conecta con POS
syncStock(merchant_id): Sincronización completa
handlePosUpdate(pos_event): Procesa evento del POS (venta, devolución)
reconcile(merchant_id): Reconciliación programada
resolveConflict(product_id, pos_quantity, online_quantity): Resolución de conflicto
```

---

## 13. FASE 8: Admin Panel + Analytics Dashboard

**Docs:** 78

**Entidades:** `ModerationQueue`, `IncidentTicket`, `AnalyticsDailyRetail`, `PayoutRecord`

**Funcionalidades clave:**
- Dashboard KPIs plataforma: GMV, merchants activos, usuarios activos, conversion rate, AOV
- Gestión de merchants: verificación, suspensión, reactivación
- Moderación de catálogo: cola de productos pendientes, flagged por AI
- Moderación de reviews: cola de reviews pendientes
- Gestión de incidencias: tickets con SLA tracking
- Monitoreo de pagos: payouts, chargebacks, comisiones
- Feature flags para rollout gradual
- Audit logging (retención 2 años)
- Export de reports (CSV/Excel)
- Roles admin: Super Admin, Platform Admin, Support Manager, Content Manager, Finance Admin, Viewer

---

## 14. FASE 9: API Integration + SDKs + Webhooks

**Docs:** 79

**Entidades:** `ApiClient`, `WebhookSubscription`, `WebhookLog`, `RateLimitConfig`

**Funcionalidades clave:**
- OAuth 2.0 Client Credentials + API Keys
- Rate limiting por plan (Free: 60/min, Basic: 300/min, Premium: 1000/min, Enterprise: 5000/min)
- 13 eventos webhook: order.created/paid/cancelled/fulfilled/shipped/delivered, product.created/updated/deleted, stock.low/out, review.created, refund.created
- Verificación de firma HMAC SHA-256 para webhooks
- Sandbox con datos de prueba
- SDKs: PHP (Composer), Node.js (npm), Python (pip)
- Patrones de integración: POS Sync, ERP Order Import
- Certificación de integraciones en 5 pasos

---

## 15. FASE 10: Mobile App (React Native)

**Docs:** 77

**Tecnología:** React Native + Expo (fuera de Drupal)

**Funcionalidades:**
- App Customer: Catálogo, búsqueda con escáner de barras, carrito, checkout, tracking, programa fidelidad, push notifications
- App Merchant: Dashboard, gestión pedidos, escáner de códigos pickup, alertas de stock
- Deep linking: `comercioconecta://product/{id}`, `comercioconecta://order/{id}`
- Biometría para acceso seguro (Face ID / Touch ID)
- Offline support: catálogo cacheado, cola de operaciones offline
- Push notifications via FCM

---

## 16. Paleta de Colores y Design Tokens del Vertical

ComercioConecta utiliza la paleta "Comercio de Barrio" definida en la especificación de Industry Style Presets (Doc 101): **Naranja + Crema + Verde**, transmitiendo proximidad, trato personal y calidez.

### 16.1 Tokens de Color del Vertical

| Token CSS | Valor | Uso Semántico |
|-----------|-------|---------------|
| `--ej-color-comercio-primary` | `#FF8C42` | Color primario del vertical (naranja impulso, ya existente como `--ej-color-impulse`) |
| `--ej-color-comercio-secondary` | `#2B7A78` | Color secundario (azul verdoso, confianza comercial) |
| `--ej-color-comercio-accent` | `#10B981` | Acento (verde éxito, disponibilidad, activo) |
| `--ej-color-comercio-bg` | `#FFF7ED` | Fondo claro cálido (naranja suave) |
| `--ej-color-comercio-surface` | `#FFFFFF` | Superficie de cards |
| `--ej-color-comercio-text` | `#334155` | Texto principal |
| `--ej-color-comercio-muted` | `#64748B` | Texto secundario |

### 16.2 Presets por Tipo de Comercio

| Preset ID | Mood | Primary | Secondary |
|-----------|------|---------|-----------|
| `comercio_boutique` | Elegante, exclusivo | `#1A1A2E` | `#C9A227` |
| `comercio_barrio` | Cercano, familiar | `#FF8C42` | `#2B7A78` |
| `comercio_gastro` | Foodie, gourmet | `#8B4513` | `#D4A574` |
| `comercio_tech` | Moderno, digital | `#4F46E5` | `#06B6D4` |
| `comercio_wellness` | Zen, natural | `#059669` | `#A7F3D0` |

El preset `comercio_barrio` es el **default** para nuevos tenants de ComercioConecta. Los comerciantes pueden personalizar colores sobre el preset base desde el Visual Picker del onboarding.

### 16.3 Implementación en SCSS

```scss
// scss/_variables-comercio.scss
// NO define $ej-* (prohibido en módulo satélite)
// Solo documenta las CSS Custom Properties que el módulo consume

// Colores del vertical ComercioConecta (inyectados por theme/tenant)
// --ej-color-comercio-primary: #FF8C42 (naranja impulso)
// --ej-color-comercio-secondary: #2B7A78 (azul verdoso)
// --ej-color-comercio-accent: #10B981 (verde éxito)
// --ej-color-comercio-bg: #FFF7ED (fondo cálido)

// Variables locales del módulo (sin prefijo $ej-)
$comercio-card-radius: 16px;
$comercio-card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
$comercio-transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
```

---

## 17. Patrón de Iconos SVG

Siguiendo la directriz del proyecto, cada icono tiene dos versiones:
- **Outline**: Trazo limpio para uso general
- **Duotone**: Capa de fondo con `opacity="0.3"` y capa principal sólida

### 17.1 Iconos específicos de ComercioConecta

| Icono | Categoría | Uso |
|-------|-----------|-----|
| `store` / `store-duotone` | `verticals/` | Icono principal del vertical, merchant cards |
| `shopping-bag` / `shopping-bag-duotone` | `actions/` | Carrito, compras |
| `barcode` / `barcode-duotone` | `ui/` | Escáner, inventario, POS |
| `flash-sale` / `flash-sale-duotone` | `actions/` | Flash offers, ofertas relámpago |
| `qr-code` / `qr-code-duotone` | `ui/` | QR dinámico, escaneo |
| `location-pin` / `location-pin-duotone` | `ui/` | Geolocalización, cercano a mí |
| `pos-terminal` / `pos-terminal-duotone` | `ui/` | Integración POS/TPV |
| `delivery-truck` / `delivery-truck-duotone` | `actions/` | Envío, logística |
| `click-collect` / `click-collect-duotone` | `actions/` | Recogida en tienda |
| `star-rating` / `star-rating-duotone` | `ui/` | Reviews, valoraciones |
| `coupon` / `coupon-duotone` | `actions/` | Cupones, promociones |
| `loyalty-badge` / `loyalty-badge-duotone` | `ui/` | Programa de fidelidad |

### 17.2 Uso en Twig

```twig
{# Icono outline (por defecto) #}
{{ jaraba_icon('store') }}

{# Icono duotone #}
{{ jaraba_icon('store-duotone') }}

{# Con color CSS personalizado #}
<span class="comercio-icon" style="--icon-color: var(--ej-color-comercio-primary);">
  {{ jaraba_icon('flash-sale') }}
</span>
```

---

## 18. Orden de Implementación Global

El orden de implementación dentro de cada fase sigue la secuencia probada en AgroConecta:

```
1. Entities (.php) → Definiciones de entidad con anotaciones completas
2. Handlers → ListBuilder, AccessControlHandler
3. Forms → EntityForm, SettingsForm
4. install → Schema hooks si es necesario
5. Routing → routing.yml, links.task.yml, links.menu.yml, links.action.yml
6. Permissions → permissions.yml
7. Services → Lógica de negocio
8. Controllers → Rutas frontend y API
9. .module → hook_theme() + hooks de automatización
10. Templates Twig → Templates de página + parciales
11. JS → Comportamientos de frontend
12. SCSS → Estilos con variables inyectables
13. Compilar SCSS → npm run build
14. Config → Taxonomías, settings iniciales
15. Verificación → drush cr + tests funcionales
```

**Comandos de verificación post-implementación (en Docker):**
```bash
# Dentro del contenedor Lando
lando drush cr
lando drush scr install_entities.php   # Si tablas no existen
lando drush entity-updates             # Si hay cambios de schema
lando drush config-import -y           # Si hay config exportada

# Verificar rutas
lando drush route:list | grep comercio

# Verificar entidades
lando drush entity:info product_retail
lando drush entity:info merchant_profile
```

---

## 19. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-09 | 1.0.0 | Creación inicial del plan con 10 fases, 36+ entidades, correspondencia completa con 18 especificaciones técnicas (Docs 62-79) |

---

> **Orden de implementación de fases:** 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10
>
> **Referencias:**
> - Especificaciones técnicas: `docs/tecnicos/20260117b-62` a `20260117b-79`
> - Arquitectura: `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
> - Directrices: `docs/00_DIRECTRICES_PROYECTO.md`
> - Patrón de referencia: `jaraba_agroconecta_core` (30+ entidades, 17 controllers, 14 services)
> - Workflows: `drupal-custom-modules`, `scss-estilos`, `i18n-traducciones`, `frontend-page-pattern`, `slide-panel-modales`, `premium-cards-pattern`
