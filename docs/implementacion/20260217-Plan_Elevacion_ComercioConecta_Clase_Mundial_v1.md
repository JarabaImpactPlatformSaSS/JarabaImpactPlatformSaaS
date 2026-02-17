# Plan de Elevacion ComercioConecta a Clase Mundial — v1.0

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Implementacion |
| **Version** | 1.0.0 |
| **Fecha** | 2026-02-17 |
| **Estado** | En Progreso (Sprint 1 Ejecutado) |
| **Vertical** | ComercioConecta (Marketplace de Comercio de Proximidad) |
| **Modulos Implicados** | `jaraba_comercio_conecta`, `ecosistema_jaraba_core`, `jaraba_journey`, `jaraba_ai_agents`, `jaraba_email`, `jaraba_billing`, `jaraba_analytics`, `ecosistema_jaraba_theme` |
| **Referencia de Paridad** | AgroConecta (14 fases) + Empleabilidad (10 fases) + Emprendimiento (7 gaps) + JarabaLex (14 fases) |
| **Docs de Referencia** | 00_DIRECTRICES_PROYECTO v41.0.0, 00_DOCUMENTO_MAESTRO_ARQUITECTURA v41.0.0, 07_VERTICAL_CUSTOMIZATION_PATTERNS v1.1.0, 2026-02-05_arquitectura_theming_saas_master v2.1, 00_FLUJO_TRABAJO_CLAUDE v2.0.0, 20260209-Plan_Implementacion_ComercioConecta_v1.md |
| **Estimacion Total** | 18 Fases - 320-400 horas - 14.400-18.000 EUR |

---

## Indice de Navegacion (TOC)

1. [Contexto](#1-contexto)
2. [Estado Actual del Vertical](#2-estado-actual-del-vertical)
3. [Analisis de Gaps — Paridad con AgroConecta/Empleabilidad](#3-analisis-de-gaps)
4. [Plan de Implementacion por Sprints](#4-plan-de-implementacion-por-sprints)
   - [Sprint 1 — Infraestructura de Elevacion (Ejecutado)](#sprint-1--infraestructura-de-elevacion-ejecutado)
   - [Sprint 2 — Comercio Core Completo (Pendiente)](#sprint-2--comercio-core-completo-pendiente)
   - [Sprint 3 — Funcionalidades Avanzadas (Pendiente)](#sprint-3--funcionalidades-avanzadas-pendiente)
5. [Archivos Criticos](#5-archivos-criticos)
6. [Checklist Pre-Commit](#6-checklist-pre-commit)

---

## 1. Contexto

ComercioConecta es el vertical de **comercio de proximidad** del SaaS Jaraba Impact Platform. Su mision es crear un **"Sistema Operativo de Barrio"** que conecta comercios locales (tiendas de ropa, ferreterias, librerias, panaderias artesanales, etc.) con consumidores en un modelo omnicanal (fisico + digital). Este plan cubre las 18 fases necesarias para elevarlo al primer nivel de clase mundial, en paridad con AgroConecta y los demas verticales de referencia.

### Diferenciacion respecto a AgroConecta

| Aspecto | AgroConecta | ComercioConecta |
|---------|-------------|-----------------|
| **Sector** | Agroalimentario (productores rurales) | Comercio de proximidad (retail urbano) |
| **Modelo** | Productor-to-Consumer + B2B | Multi-vendor local marketplace |
| **Omnicanalidad** | Web + WhatsApp + PWA | Click & Collect + Ship-from-Store + POS |
| **Diferenciador** | Trazabilidad QR campo-a-mesa | Flash Offers geolocalizadas + SEO Local |
| **Copilot** | ProducerCopilot + SalesAgent | MerchantCopilot (por implementar) |
| **Color Token** | `#556B2F` (olive) | `#FF8C42` (orange) |

### Diferenciador Competitivo

- **Competidores**: Shopify Local, Wix Retail, Glovo Business, Amazon Local Shops (generalistas, sin IA nativa)
- **Diferenciacion**: IA nativa (Merchant Copilot), omnicanalidad real (POS + web + Click & Collect), flash offers geolocalizadas, QR dinamicos, SEO local automatizado
- **Precio**: Free (10 productos) / Starter 29 EUR/mes / Profesional 79 EUR/mes / Enterprise personalizado
- **TAM estimado**: 200M EUR (mercado retail local digital en Espana)

---

## 2. Estado Actual del Vertical

### Score de Paridad: ~15% (vs 100% AgroConecta)

| Metrica | Estado Actual | Target Clase Mundial |
|---------|---------------|---------------------|
| Content Entities | 4 | ~36 |
| Services PHP | 3 | ~22 |
| Controllers | 3 | ~17 |
| Endpoints REST API | 19 | ~50+ |
| Templates Twig | 5 + 2 parciales | ~20 + 5 parciales |
| SCSS Partials | 6 + main.scss | ~17 + main.scss |
| JS Behaviors | 1 | ~14 |
| FreemiumVerticalLimit configs | 0 | 15 (3 planes x 5 features) |
| JourneyDefinition | 0 avatares | 3 avatares (comerciante, consumidor, gestor_barrio) |
| AI Agents | 0 | 2 (MerchantCopilotAgent, ShopperAgent) |
| FeatureGateService | No existe | Implementado |
| UpgradeTriggerService | No hay triggers | 7 triggers |
| EmailSequenceService | No existe | 6 secuencias MJML |
| Zero-region template | No existe | page--comercio.html.twig |
| CrossVerticalBridgeService | No existe | 4 bridges |
| JourneyProgressionService | No existe | 10+ reglas proactivas |
| HealthScoreService | No existe | 5 dimensiones + 8 KPIs |
| ExperimentService | No existe | 4 funnel definitions |

### 2.1 Lo que EXISTE (Implementado — Fase 1 del Plan Original)

| Componente | Estado | Ubicacion |
|------------|--------|-----------|
| **Modulo `jaraba_comercio_conecta`** | Operativo | `web/modules/custom/jaraba_comercio_conecta/` |
| 4 Content Entities | Implementadas | `src/Entity/` (ProductRetail, ProductVariationRetail, StockLocation, MerchantProfile) |
| 3 Services | Implementados | `src/Service/` (MarketplaceService, ProductRetailService, MerchantDashboardService) |
| 3 Controllers | Implementados | `src/Controller/` (MarketplaceController, MerchantPortalController, ProductApiController) |
| 19 Routes REST + Frontend | Implementadas | `jaraba_comercio_conecta.routing.yml` |
| 5 Templates + 2 Parciales | Implementados | `templates/` (marketplace, product-detail, merchant-dashboard, merchant-products, merchant-analytics + 2 partials) |
| 6 SCSS Partials + main.scss | Implementados | `scss/` (con `@use`, Dart Sass) |
| 1 JS Behavior | Implementado | `js/marketplace.js` |
| 3 Taxonomias | Config install | `comercio_category`, `comercio_brand`, `comercio_attribute` |
| package.json + Dart Sass | Implementado | `package.json` con `sass ^1.71.0` |
| 16 SVG Icons | Implementados | `images/icons/` (store, shopping-bag, barcode, flash-sale, location-pin, qr-code, pos-terminal, delivery-truck) x2 (normal + duotone) |
| Forms + ListBuilders + Access | Implementados | Para las 4 entidades existentes |

### 2.2 Lo que FALTA (Gaps Criticos)

| # | Gap | Prioridad | Sprint |
|---|-----|-----------|--------|
| G1 | **FeatureGateService** — No existe `ComercioConectaFeatureGateService`. Sin tabla `{comercio_conecta_feature_usage}`. Sin FreemiumVerticalLimit configs. | P0 CRITICA | Sprint 1 (F1) |
| G2 | **UpgradeTriggerService** — No hay triggers para comercio_conecta. Sin llamadas `fire()`. | P0 CRITICA | Sprint 1 (F2) |
| G3 | **hook_preprocess_html + hook_theme_suggestions + hook_preprocess_page** — Sin body classes, sin template suggestions, sin zero-region. | P0 CRITICA | Sprint 1 (F3) |
| G4 | **page--comercio.html.twig** — No existe en el tema. Rutas caen a templates genericos. | P0 CRITICA | Sprint 1 (F4) |
| G5 | **SCSS Compliance** — Verificar `rgba()` bare, `color-mix()`, `var(--ej-*)` con fallbacks. | P1 ALTA | Sprint 1 (F5) |
| G6 | **Design Token Config** — No existe `design_token_config.vertical_comercio_conecta.yml`. | P2 MEDIA | Sprint 1 (F5) |
| G7 | **CopilotBridgeService** — No existe `ComercioConectaCopilotBridgeService`. Sin AI Agents para el vertical. | P1 ALTA | Sprint 1 (F13) |
| G8 | **EmailSequenceService** — No hay secuencias MJML para comercio. Sin directorio `comercio_conecta/` en `jaraba_email/templates/mjml/`. | P1 ALTA | Sprint 1 (F14) |
| G9 | **CrossVerticalBridgeService** — No existe `ComercioConectaCrossVerticalBridgeService`. Sin puentes hacia otros verticales. | P2 MEDIA | Sprint 1 (F15) |
| G10 | **JourneyProgressionService + HealthScoreService** — No existen. Sin reglas proactivas ni metricas de salud. | P0 CRITICA | Sprint 1 (F16) |
| G11 | **ExperimentService** — No hay A/B testing para el vertical. | P3 BAJA | Sprint 1 (F17) |
| G12 | **Page Builder Premium** — Sin templates de bloque verticales ni avatar navigation. | P1 ALTA | Sprint 1 (F18) |
| G13 | **Orders + Checkout + Payments** — Entidades de pedido, carrito, pago no implementadas. | P0 CRITICA | Sprint 2 (F6) |
| G14 | **Merchant + Customer Portal** — Portales incompletos, falta portal de consumidor. | P0 CRITICA | Sprint 2 (F7) |
| G15 | **Search + Local SEO** — Sin buscador avanzado ni optimizacion SEO local. | P1 ALTA | Sprint 2 (F8) |
| G16 | **Flash Offers + QR + Reviews + Notifications** — Sin ofertas relampago, QR dinamicos, resenas ni notificaciones. | P1 ALTA | Sprint 2 (F9) |
| G17 | **Shipping + POS + Admin + Analytics** — Sin logistica, integracion POS, panel admin ni analytics avanzado. | P1 ALTA | Sprint 3 (F10) |

---

## 3. Analisis de Gaps

### 3.1 Matriz de Paridad: ComercioConecta vs AgroConecta vs Empleabilidad

| Componente | Empleab. | AgroConecta | ComercioConecta |
|------------|:---:|:---:|:---:|
| Landing page publica (9 secciones) | OK | OK | X (Pendiente) |
| Lead magnet gratuito | OK | OK | X (Pendiente) |
| Zero-region page template | OK | Pendiente (F4) | X G4 |
| Modal CRUD | OK | Pendiente (F4) | X G4 |
| SCSS compliance (color-mix, var) | OK | Parcial (F5) | Parcial G5 |
| Design Token Config Entity | OK | Pendiente (F6) | X G6 |
| FeatureGateService | OK | Pendiente (F0) | X G1 |
| FreemiumVerticalLimit configs | OK | OK (15 configs) | X (0 configs) |
| Feature usage DB table | OK | Pendiente (F0) | X G1 |
| UpgradeTrigger types + fire() | OK | Pendiente (F1) | X G2 |
| Soft upsell en Copilot | OK | Pendiente (F2) | X G7 |
| EmailSequenceService | OK | Pendiente (F7) | X G8 |
| MJML templates | OK | OK (6 transacc) | X (0 templates) |
| CrossVerticalBridgeService | OK | Pendiente (F8) | X G9 |
| JourneyDefinition | OK | OK (3 avatares) | X (0 avatares) |
| JourneyProgressionService | OK | Pendiente (F9) | X G10 |
| HealthScoreService | OK | Pendiente (F10) | X G10 |
| CopilotAgent/BridgeService | OK | Parcial (F2) | X G7 |
| Avatar en NavigationService | OK | Parcial (F12) | X G12 |
| Funnel Definition | OK | Pendiente (F12) | X G11 |
| ExperimentService | OK | Pendiente (F11) | X G11 |
| hook_preprocess_page | OK | Pendiente (F3) | X G3 |
| hook_theme_suggestions_page_alter | OK | Pendiente (F3) | X G3 |
| Orders + Checkout | OK | OK | X G13 |
| Merchant/Customer Portal completo | OK | OK | Parcial G14 |
| Search + Discovery | OK | OK | X G15 |

**Score de paridad: ~4/26 completos (15,4%)** — ComercioConecta tiene la base minima funcional (4 entidades, 3 servicios, marketplace publico) pero carece de toda la capa de elevacion a clase mundial y la mayoria de las funcionalidades core de comercio (pedidos, pagos, checkout, envios, resenas, flash offers, POS).

---

## 4. Plan de Implementacion por Sprints

---

### Sprint 1 — Infraestructura de Elevacion (Ejecutado)

> **Estado:** EJECUTADO
> **Fases:** F1-F5, F13-F18
> **Estimacion:** 150-190 horas
> **Objetivo:** Elevar ComercioConecta al patron de clase mundial en paridad con Empleabilidad/AgroConecta, implementando toda la capa de infraestructura transversal.

---

#### F1: ComercioConectaFeatureGateService — Enforcement de 9 FreemiumVerticalLimit configs (EJECUTADO)

**Prioridad:** P0 CRITICA | **Estimacion:** 20-25h | **Gaps:** G1

**Descripcion:** Implementacion del servicio de feature gating que hace cumplir los FreemiumVerticalLimit configs para ComercioConecta. Incluye la tabla de tracking de uso `{comercio_conecta_feature_usage}` y la integracion con los servicios existentes.

**Componentes implementados:**

| Componente | Descripcion |
|------------|-------------|
| `ComercioConectaFeatureGateService` | Servicio principal con metodos `check()`, `recordUsage()`, `getUserPlan()`, `getRemainingUsage()`, `resetMonthlyUsage()` |
| Tabla `{comercio_conecta_feature_usage}` | Schema con campos `id`, `user_id`, `feature_key`, `usage_date`, `usage_count`. Indices: `user_feature_date` UNIQUE, `feature_date`. |
| 9 FreemiumVerticalLimit configs | 3 planes x 3 features: `products` (10/50/-1), `orders_per_month` (15/100/-1), `copilot_uses_per_month` (5/50/-1) |
| Integration con ProductRetailService | `create()` llama a FeatureGate `check('products')` |
| Integration con MarketplaceService | Operaciones gated por plan |
| `getAvailableAddons()` | Incluye `jaraba_comercio_conecta` en la lista de addons |

---

#### F2: UpgradeTriggerService — 7 triggers para ComercioConecta (EJECUTADO)

**Prioridad:** P0 CRITICA | **Estimacion:** 12-15h | **Gaps:** G2

**Descripcion:** Registro de 7 tipos de trigger en el `UpgradeTriggerService` centralizado, con llamadas `fire()` integradas en los servicios del modulo.

**Triggers implementados:**

| Trigger Key | Condicion | Mensaje (i18n) | CTA |
|------------|-----------|----------------|-----|
| `comercio_product_limit` | Limite de productos alcanzado | `{% trans %}Has alcanzado el limite de productos. Pasa a Starter para publicar mas.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_order_limit` | Limite de pedidos/mes alcanzado | `{% trans %}Has procesado todos tus pedidos del mes. Ampliate para seguir vendiendo.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_copilot_limit` | Limite de copilot alcanzado | `{% trans %}Tu asistente IA tiene mas ideas. Desbloquea el copilot ilimitado.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_first_sale` | Primera venta completada | `{% trans %}Tu primera venta! Pasa a Starter para herramientas profesionales.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_flash_offer_premium` | Intento de crear flash offer en plan free | `{% trans %}Las Flash Offers son una herramienta Pro. Ampliate para activarlas.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_pos_integration` | Intento de conectar POS en plan free | `{% trans %}Sincroniza tu TPV con tu tienda online. Disponible en plan Profesional.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |
| `comercio_analytics_advanced` | Intento de acceder a analiticas avanzadas | `{% trans %}Desbloquea metricas avanzadas para tu comercio.{% endtrans %}` | `/billing/upgrade?vertical=comercio_conecta` |

---

#### F3: hook_preprocess_html — Body Classes + hook_theme_suggestions + hook_preprocess_page (EJECUTADO)

**Prioridad:** P0 CRITICA | **Estimacion:** 10-12h | **Gaps:** G3

**Descripcion:** Implementacion de los 3 hooks obligatorios del patron zero-region en el fichero `.module`:

**Hooks implementados:**

| Hook | Funcion |
|------|---------|
| `hook_preprocess_html()` | Mapeo de rutas ComercioConecta a body classes (`page-comercio-marketplace`, `page-comercio-product`, `page-comercio-merchant`, `page-comercio-customer`, `page-comercio-checkout`, `page-comercio-search`) |
| `hook_theme_suggestions_page_alter()` | Template suggestions para rutas `jaraba_comercio_conecta.*` -> `page__comercio` |
| `hook_preprocess_page()` | Inyeccion de variables de pagina via zero-region pattern: `clean_content`, `page_title`, `breadcrumbs`, `copilot_context` |

**Rutas mapeadas a body classes:**

| Ruta | Body Class |
|------|-----------|
| `jaraba_comercio_conecta.marketplace` | `page-comercio-marketplace` |
| `jaraba_comercio_conecta.marketplace.product` | `page-comercio-product` |
| `jaraba_comercio_conecta.marketplace.merchant` | `page-comercio-merchant-page` |
| `jaraba_comercio_conecta.merchant_portal` | `page-comercio-merchant-dashboard` |
| `jaraba_comercio_conecta.merchant_portal.products` | `page-comercio-merchant-products` |
| `jaraba_comercio_conecta.merchant_portal.analytics` | `page-comercio-merchant-analytics` |
| `jaraba_comercio_conecta.merchant_portal.stock` | `page-comercio-merchant-stock` |
| `jaraba_comercio_conecta.merchant_portal.profile` | `page-comercio-merchant-profile` |

---

#### F4: Page Template Zero-Region mejorado con copilot FAB condicional (EJECUTADO)

**Prioridad:** P0 CRITICA | **Estimacion:** 12-15h | **Gaps:** G4

**Descripcion:** Creacion del template `page--comercio.html.twig` en `ecosistema_jaraba_theme/templates/` con layout zero-region completo. Incluye copilot FAB condicional basado en plan y contexto del usuario.

**Componentes implementados:**

| Componente | Descripcion |
|------------|-------------|
| `page--comercio.html.twig` | Template zero-region con `{{ clean_content }}`, header/footer via `{% include %}`, sin `page.content`, sin `page.sidebar_*` |
| Copilot FAB condicional | `{% if copilot_enabled %}{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' %}{% endif %}` |
| Modal CRUD | `data-dialog-type="modal"` con `drupal.dialog.ajax` en acciones CRUD |
| Layout | Full-width mobile-first con `max-width: 1400px` centrado |

---

#### F5: SCSS Compliance + Design Token Config (EJECUTADO)

**Prioridad:** P1 ALTA | **Estimacion:** 8-10h | **Gaps:** G5, G6

**Descripcion:** Auditoria y correccion de los 6 SCSS partials existentes para cumplir con las directrices P4-COLOR-001/002/003. Creacion del design token config entity.

**Acciones ejecutadas:**

| Accion | Detalle |
|--------|---------|
| Eliminacion de `rgba()` bare | Sustitucion por `color-mix(in srgb, var(--ej-color-comercio), transparent N%)` |
| `var(--ej-*)` con fallbacks | Todos los tokens CSS usan `var(--ej-color-comercio, #FF8C42)` con fallback |
| `@use` exclusivo | Eliminacion de cualquier `@import` residual. `@use 'sass:color'` para funciones modernas |
| Design Token Config | `design_token_config.vertical_comercio_conecta.yml` creado en config/install |
| Color token inyectable | `color_comercio: #FF8C42` -> `--ej-color-comercio` en `:root` |
| SCSS variable | `$ej-color-comercio` como fallback compilacion + `.vertical-landing--comercio_conecta` |

---

#### F13: ComercioConectaCopilotBridgeService (EJECUTADO)

**Prioridad:** P1 ALTA | **Estimacion:** 15-18h | **Gaps:** G7

**Descripcion:** Implementacion del servicio de bridge entre el copilot IA y el contexto del vertical ComercioConecta. Inyeccion de contexto vertical en BaseAgent.

**Componentes implementados:**

| Componente | Descripcion |
|------------|-------------|
| `ComercioConectaCopilotBridgeService` | Bridge service con context injection para `comercio_conecta` en `BaseAgent::getVerticalContext()` |
| Soft upsell integration | Mensajes de upsell contextuales en respuestas del copilot cuando se detectan limites |
| Modos copilot | `product_description`, `pricing_advice`, `seo_optimization`, `customer_response`, `flash_offer_copy`, `chat` |
| Contexto vertical | Inyecta catalogo, ventas, rating, plan actual, features disponibles |

---

#### F14: ComercioConectaEmailSequenceService + 6 MJML templates (EJECUTADO)

**Prioridad:** P1 ALTA | **Estimacion:** 18-22h | **Gaps:** G8

**Descripcion:** Implementacion del servicio de secuencias de email con 6 MJML templates para el lifecycle del comerciante.

**Secuencias implementadas:**

| Sequence ID | Nombre | Trigger | Emails | Cadencia |
|-------------|--------|---------|--------|----------|
| `SEQ_COMERCIO_001` | Onboarding Comerciante | Registro como merchant | 5 | D+0, D+1, D+3, D+5, D+7 |
| `SEQ_COMERCIO_002` | Activacion Primer Producto | Primer producto publicado | 3 | D+0, D+2, D+5 |
| `SEQ_COMERCIO_003` | Primera Venta | Primera venta completada | 3 | D+0, D+3, D+7 |
| `SEQ_COMERCIO_004` | Upsell Free->Starter | Limite de productos alcanzado | 3 | D+0, D+2, D+5 |
| `SEQ_COMERCIO_005` | Retencion | 15 dias sin actividad | 4 | D+0, D+3, D+7, D+14 |
| `SEQ_COMERCIO_006` | Carrito Abandonado | Carrito sin checkout 24h | 3 | H+1, H+24, H+72 |

**MJML Templates:**

| Template | Ubicacion |
|----------|-----------|
| `onboarding-welcome.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |
| `activation-first-product.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |
| `first-sale-celebration.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |
| `upsell-starter.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |
| `retention-come-back.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |
| `cart-abandoned.mjml` | `jaraba_email/templates/mjml/comercio_conecta/` |

---

#### F15: ComercioConectaCrossVerticalBridgeService (EJECUTADO)

**Prioridad:** P2 MEDIA | **Estimacion:** 10-12h | **Gaps:** G9

**Descripcion:** Implementacion de 4 bridges cross-vertical desde y hacia ComercioConecta.

**Bridges implementados:**

| Bridge Key | Direccion | Condicion | CTA |
|------------|-----------|-----------|-----|
| `comercio_to_emprendimiento` | Outgoing | Comerciante con >50 ventas + >4.5 rating | `{% trans %}Escala tu negocio con herramientas de emprendimiento{% endtrans %}` -> `/emprendimiento` |
| `comercio_to_fiscal` | Outgoing | Comerciante plan Profesional + >30 ventas/mes | `{% trans %}Gestiona tu facturacion con VeriFACTU{% endtrans %}` -> `/fiscal/dashboard` |
| `comercio_to_formacion` | Outgoing | Comerciante con >100 ventas totales | `{% trans %}Certificacion en comercio digital{% endtrans %}` -> `/courses` |
| `comercio_to_agroconecta` | Outgoing | Comerciante en sector alimentario sin tienda agro | `{% trans %}Anade productos agroalimentarios con trazabilidad{% endtrans %}` -> `/agroconecta` |

**Bridges incoming (desde otros verticales):**

| Bridge Key | Origen | Condicion |
|------------|--------|-----------|
| `emprendimiento_to_comercio` | Emprendimiento | Emprendedor en sector retail con canvas_completeness > 60% |
| `agroconecta_to_comercio` | AgroConecta | Productor que tambien vende productos elaborados retail |
| `empleabilidad_to_comercio` | Empleabilidad | Jobseeker interesado en autoempleo comercial |

---

#### F16: ComercioConectaJourneyProgressionService + ComercioConectaHealthScoreService (EJECUTADO)

**Prioridad:** P0 CRITICA | **Estimacion:** 25-30h | **Gaps:** G10

##### JourneyProgressionService

**Descripcion:** Motor de reglas proactivas para 3 avatares del vertical ComercioConecta.

**Avatares definidos:**

| Avatar | Descripcion | Reglas |
|--------|-------------|--------|
| `comerciante` | Dueno de comercio local | 8 reglas proactivas |
| `consumidor` | Comprador en marketplace local | 2 reglas proactivas |
| `gestor_barrio` | Coordinador de asociacion comercial (futuro) | 0 reglas (reservado) |

**Reglas proactivas (Avatar `comerciante`):**

| Rule ID | Estado | Condicion | Mensaje | Canal |
|---------|--------|-----------|---------|-------|
| `inactivity_discovery` | discovery | 3 dias sin actividad | `{% trans %}Tu tienda esta casi lista. Publica tu primer producto.{% endtrans %}` | `fab_dot` |
| `incomplete_catalog` | activation | 1 producto, sin foto de portada | `{% trans %}Una buena foto multiplica tus ventas. Anade fotos.{% endtrans %}` | `fab_expand` |
| `first_sale_nudge` | activation | Productos publicados, 0 pedidos en 7 dias | `{% trans %}Comparte tu tienda para recibir tus primeros pedidos.{% endtrans %}` | `fab_badge` |
| `review_response` | engagement | Resenas sin responder >3 dias | `{% trans %}Tienes resenas pendientes. Responde para mejorar tu reputacion.{% endtrans %}` | `fab_dot` |
| `flash_offer_opportunity` | engagement | >5 ventas, sin flash offers | `{% trans %}Crea tu primera Flash Offer y atrae clientes del barrio.{% endtrans %}` | `fab_expand` |
| `pos_integration_nudge` | conversion | Plan Starter, >20 ventas, sin POS | `{% trans %}Conecta tu TPV y sincroniza stock automaticamente.{% endtrans %}` | `fab_expand` |
| `upgrade_suggestion` | conversion | Plan free + 8 productos (80% limite) | `{% trans %}Tu catalogo esta casi lleno. Ampliate a Starter.{% endtrans %}` | `fab_expand` |
| `multi_location_expansion` | retention | >100 ventas + 1 ubicacion | `{% trans %}Abre una segunda ubicacion de stock y amplía tu alcance.{% endtrans %}` | `fab_dot` |

**Reglas proactivas (Avatar `consumidor`):**

| Rule ID | Estado | Condicion | Mensaje | Canal |
|---------|--------|-----------|---------|-------|
| `cart_abandoned` | activation | Carrito creado, sin checkout en 24h | `{% trans %}Tu carrito te espera. Completa tu pedido.{% endtrans %}` | `fab_dot` |
| `reorder_nudge` | retention | Ultimo pedido hace >30 dias | `{% trans %}Descubre las novedades de tus comercios favoritos.{% endtrans %}` | `fab_badge` |

##### HealthScoreService

**Descripcion:** Servicio de health score con 5 dimensiones ponderadas especificas para comercio de proximidad.

**Dimensiones de salud (5, total peso = 1.0):**

| Dimension | Peso | Descripcion | Calculo |
|-----------|------|-------------|---------|
| `catalog_health` | 0.25 | Salud del catalogo | Productos activos (max 30) + fotos por producto (max 30) + descripciones completas (max 20) + variaciones (max 20) |
| `sales_activity` | 0.30 | Actividad de ventas | Pedidos ultimo mes (max 40) + revenue trend (max 30) + conversion rate (max 30) |
| `customer_engagement` | 0.20 | Engagement con clientes | Resenas respondidas (max 40) + rating promedio (max 30) + tiempo respuesta (max 30) |
| `copilot_usage` | 0.10 | Uso del copilot IA | Usos copilot 7 dias (max 50) + acciones ejecutadas (max 50) |
| `omnichannel_presence` | 0.15 | Presencia omnicanal | POS conectado (max 30) + Click & Collect activo (max 30) + Flash Offers creadas (max 20) + QR dinamicos (max 20) |

**Categorias:**

| Score | Categoria | Significado |
|-------|-----------|-------------|
| >= 80 | `healthy` | Comerciante activo y rentable |
| >= 60 | `neutral` | Ventas regulares, margen de mejora |
| >= 40 | `at_risk` | Desengagement detectado |
| < 40 | `critical` | Alto riesgo de abandono |

**KPIs del Vertical (8):**

| KPI Key | Target | Unidad | Direccion | Label |
|---------|--------|--------|-----------|-------|
| `gmv_monthly` | 40000 | EUR | higher | `{% trans %}Volumen de negocio mensual{% endtrans %}` |
| `merchant_activation_rate` | 55 | % | higher | `{% trans %}Tasa de activacion (primer producto en 7 dias){% endtrans %}` |
| `order_completion_rate` | 90 | % | higher | `{% trans %}Tasa de pedidos completados{% endtrans %}` |
| `click_collect_adoption` | 30 | % | higher | `{% trans %}Adopcion Click & Collect{% endtrans %}` |
| `nps` | 50 | score | higher | `{% trans %}NPS del marketplace{% endtrans %}` |
| `arpu` | 32 | EUR/mes | higher | `{% trans %}ARPU por comerciante{% endtrans %}` |
| `conversion_free_paid` | 12 | % | higher | `{% trans %}Conversion Free a Pago{% endtrans %}` |
| `churn_rate` | 6 | % | lower | `{% trans %}Tasa de baja mensual{% endtrans %}` |

---

#### F17: ComercioConectaExperimentService + 4 funnel definitions (EJECUTADO)

**Prioridad:** P3 BAJA | **Estimacion:** 12-15h | **Gaps:** G11

**Descripcion:** Servicio de experimentacion A/B para el vertical ComercioConecta con 4 funnel definitions.

**Funnel Definitions:**

| Funnel Config | Descripcion | Pasos | Conversion Window |
|---------------|-------------|-------|-------------------|
| `comercio_merchant_acquisition` | Adquisicion comerciante | `landing_visit -> guia_download -> registration -> first_product_upload -> first_product_published` | 168h (7 dias) |
| `comercio_merchant_activation` | Activacion comerciante | `registration -> first_product -> first_photo -> first_order_received -> first_sale_completed` | 336h (14 dias) |
| `comercio_monetization` | Monetizacion | `product_limit_reached -> upgrade_modal_shown -> checkout_started -> payment_completed` | 72h (3 dias) |
| `comercio_consumer_purchase` | Compra consumidor | `marketplace_visit -> product_view -> add_to_cart -> checkout_started -> order_completed` | 48h (2 dias) |

**Experimentos iniciales:**

| Experiment Key | Variantes | Metrica |
|---------------|-----------|---------|
| `comercio_checkout_cta_color` | A: `--ej-color-comercio` / B: `--ej-color-accent` | `order_completed` |
| `comercio_product_card_layout` | A: Grid / B: List | `product_click_rate` |
| `comercio_pricing_page_variant` | A: 3 columnas / B: Slider | `plan_selected` |
| `comercio_flash_offer_format` | A: Banner / B: Modal | `flash_offer_claimed` |

---

#### F18: Page Builder Premium (11 templates) + Avatar Navigation (EJECUTADO)

**Prioridad:** P1 ALTA | **Estimacion:** 20-25h | **Gaps:** G12

**Descripcion:** 11 templates de bloque premium para el Page Builder vertical y configuracion completa de avatar navigation.

**Block Templates (11):**

| Template | Nombre | Uso |
|----------|--------|-----|
| `block--comercio-hero.html.twig` | Hero Marketplace | Landing page principal |
| `block--comercio-merchant-grid.html.twig` | Grid de Comercios | Directorio de comercios |
| `block--comercio-product-carousel.html.twig` | Carrusel de Productos | Destacados y novedades |
| `block--comercio-flash-offers.html.twig` | Flash Offers Banner | Ofertas relampago activas |
| `block--comercio-map.html.twig` | Mapa de Comercios | Geolocalizacion |
| `block--comercio-categories.html.twig` | Categorias | Navegacion por sector |
| `block--comercio-testimonials.html.twig` | Testimonios | Social proof |
| `block--comercio-stats.html.twig` | Estadisticas | Metricas del marketplace |
| `block--comercio-pricing.html.twig` | Tabla de Precios | Planes y precios |
| `block--comercio-faq.html.twig` | FAQ | Preguntas frecuentes |
| `block--comercio-cta.html.twig` | CTA Final | Registro/conversion |

**Avatar Navigation:**

| Avatar | Rol | Items |
|--------|-----|-------|
| `merchant` | `merchant` | Marketplace (`/comercio-local`), Mi Comercio (`/mi-comercio`), Productos (`/mi-comercio/productos`), Stock (`/mi-comercio/stock`), Analiticas (`/mi-comercio/analiticas`) |
| `consumer` | `authenticated` | Marketplace (`/comercio-local`), Mis Pedidos (`/mis-pedidos`), Mis Favoritos (`/mis-favoritos`) |
| `anonymous` | `anonymous` | Marketplace (`/comercio-local`), Registro (`/registro/comercio`) |

---

### Sprint 2 — Comercio Core Completo (Pendiente)

> **Estado:** PENDIENTE
> **Fases:** F6-F9
> **Estimacion:** 100-130 horas
> **Objetivo:** Completar toda la funcionalidad core de comercio: pedidos, checkout, pagos, portales, busqueda, SEO local, flash offers, QR dinamico, resenas y notificaciones.

---

#### F6: Orders + Checkout + Payments (PENDIENTE)

**Prioridad:** P0 CRITICA | **Estimacion:** 30-38h | **Gaps:** G13

**Descripcion:** Implementar el sistema completo de pedidos, carrito, checkout y pagos con integracion Stripe Connect.

**Entidades a crear:**

| Entidad | Descripcion |
|---------|-------------|
| `OrderRetail` | Pedido principal con estados: draft, pending, confirmed, processing, shipped, delivered, cancelled, refunded |
| `OrderItemRetail` | Linea de pedido vinculada a ProductVariationRetail |
| `SuborderRetail` | Sub-pedido por comerciante (multi-vendor split) |
| `Cart` | Carrito con items, cupones aplicados, metodo de envio |
| `CartItem` | Item del carrito con cantidad, variacion seleccionada |
| `ReturnRequest` | Solicitud de devolucion/cambio |
| `CouponRetail` | Cupon de descuento con reglas de aplicacion |
| `CouponRedemption` | Registro de uso de cupon |
| `AbandonedCart` | Carrito abandonado para recovery |

**Services a crear:**

| Service | Descripcion |
|---------|-------------|
| `OrderRetailService` | CRUD de pedidos, cambios de estado, notificaciones |
| `CheckoutService` | Flujo de checkout multi-step, validaciones |
| `CartService` | Gestion de carrito, calculos de totales |
| `StripePaymentRetailService` | Pagos Stripe Connect con split por comerciante |
| `CartRecoveryService` | Deteccion y recuperacion de carritos abandonados |

**Controllers a crear:**

| Controller | Rutas |
|------------|-------|
| `CheckoutController` | `/comercio-local/checkout`, `/comercio-local/checkout/payment`, `/comercio-local/checkout/confirmation` |
| `OrderController` | `/mis-pedidos`, `/mis-pedidos/{order}`, API REST orders |

---

#### F7: Merchant + Customer Portal (PENDIENTE)

**Prioridad:** P0 CRITICA | **Estimacion:** 25-30h | **Gaps:** G14

**Descripcion:** Completar el portal del comerciante con gestion de pedidos, pagos recibidos y configuracion. Crear el portal del consumidor con historial de compras, favoritos y perfil.

**Componentes a implementar:**

| Componente | Descripcion |
|------------|-------------|
| Merchant Portal: Pedidos | Listado de pedidos recibidos, cambio de estado, preparacion |
| Merchant Portal: Pagos | Historial de payouts Stripe, comisiones, ingresos |
| Merchant Portal: Configuracion | Horarios, zonas de envio, Click & Collect, datos fiscales |
| Customer Portal: Dashboard | Resumen de pedidos, comercios favoritos, notificaciones |
| Customer Portal: Pedidos | Historial, tracking, devolucion |
| Customer Portal: Favoritos/Wishlist | Productos y comercios guardados |
| `CustomerProfile` entity | Perfil del consumidor con preferencias y direcciones |
| `Wishlist` + `WishlistItem` entities | Listas de deseos |

---

#### F8: Search + Local SEO (PENDIENTE)

**Prioridad:** P1 ALTA | **Estimacion:** 20-25h | **Gaps:** G15

**Descripcion:** Implementar el buscador avanzado con facetas, autocompletado y optimizacion para "cerca de mi". Integracion con Google Business Profile y Schema.org LocalBusiness.

**Entidades a crear:**

| Entidad | Descripcion |
|---------|-------------|
| `SearchIndex` | Indice de busqueda local con pesos y ranking |
| `SearchSynonym` | Sinonimos y terminos equivalentes |
| `SearchLog` | Historial de busquedas para analytics |
| `LocalBusinessProfile` | Perfil SEO local con datos NAP (Name, Address, Phone) |
| `NapEntry` | Entradas de citacion en directorios externos |

**Services a crear:**

| Service | Descripcion |
|---------|-------------|
| `ComercioSearchService` | Busqueda con facetas, geolocalizacion, autocompletado |
| `LocalSeoService` | Generacion de Schema.org, sitemap, Google Business Profile sync |

---

#### F9: Flash Offers + QR + Reviews + Notifications (PENDIENTE)

**Prioridad:** P1 ALTA | **Estimacion:** 25-32h | **Gaps:** G16

**Descripcion:** Implementar las funcionalidades avanzadas de engagement: ofertas relampago geolocalizadas, codigos QR dinamicos, sistema de resenas y valoraciones, y notificaciones push.

**Entidades a crear:**

| Entidad | Descripcion |
|---------|-------------|
| `FlashOffer` | Oferta relampago con hora inicio/fin, descuento, radio GPS |
| `FlashOfferClaim` | Reclamacion/canje de flash offer |
| `QrCodeRetail` | QR dinamico con redireccion contextual y A/B testing |
| `QrScanEvent` | Evento de escaneo QR con geolocalizacion |
| `QrLeadCapture` | Captura de lead via QR |
| `ReviewRetail` | Resena con rating, texto, fotos |
| `QuestionAnswer` | Preguntas y respuestas sobre productos |
| `NotificationTemplate` | Template de notificacion configurable |
| `NotificationLog` | Registro de notificaciones enviadas |
| `NotificationPreference` | Preferencias de notificacion del usuario |
| `PushSubscription` | Suscripcion a notificaciones push (Web Push API) |

**Services a crear:**

| Service | Descripcion |
|---------|-------------|
| `FlashOfferService` | CRUD de flash offers, geolocalizacion, activacion/desactivacion |
| `QrRetailService` | Generacion y gestion de QR dinamicos |
| `ReviewRetailService` | CRUD de resenas, moderacion, respuestas |
| `NotificationService` | Envio de notificaciones multi-canal (email, push, in-app) |

---

### Sprint 3 — Funcionalidades Avanzadas (Pendiente)

> **Estado:** PENDIENTE
> **Fases:** F10-F12 (agrupadas en F10)
> **Estimacion:** 70-85 horas
> **Objetivo:** Completar las funcionalidades avanzadas de logistica, POS, administracion y analytics.

---

#### F10: Shipping + POS + Admin + Analytics (PENDIENTE)

**Prioridad:** P1 ALTA | **Estimacion:** 70-85h | **Gaps:** G17

**Descripcion:** Fase consolidada con 4 bloques funcionales avanzados.

##### F10a: Shipping & Logistics

| Entidad | Descripcion |
|---------|-------------|
| `ShipmentRetail` | Envio con tracking, carrier, estado |
| `ShippingMethodRetail` | Metodo de envio (standard, express, Click & Collect) |
| `ShippingZone` | Zona de envio con tarifas |
| `CarrierConfig` | Configuracion de transportistas (MRW, SEUR, Correos) |

| Service | Descripcion |
|---------|-------------|
| `ShippingRetailService` | Calculo de tarifas, tracking, integracion carriers |
| `ClickCollectService` | Reserva en tienda, horarios de recogida |

##### F10b: POS Integration

| Entidad | Descripcion |
|---------|-------------|
| `PosConnection` | Conexion con terminal punto de venta |
| `PosSync` | Registro de sincronizacion de stock/precios |
| `PosConflict` | Conflictos de sincronizacion bidireccional |

| Service | Descripcion |
|---------|-------------|
| `PosIntegrationService` | Sincronizacion bidireccional con TPV (SumUp, Zettle, Square) |

##### F10c: Admin Panel

| Entidad | Descripcion |
|---------|-------------|
| `ModerationQueue` | Cola de moderacion de productos y resenas |
| `IncidentTicket` | Tickets de incidencias entre comerciantes y consumidores |
| `PayoutRecord` | Registro de payouts a comerciantes |

| Service | Descripcion |
|---------|-------------|
| `ComercioAdminService` | Panel de administracion del marketplace |
| `ModerationService` | Moderacion de contenido y disputas |

##### F10d: Analytics Dashboard

| Service | Descripcion |
|---------|-------------|
| `ComercioAnalyticsService` | KPIs del marketplace: GMV, pedidos, comerciantes activos, conversion |
| `MerchantAnalyticsService` | Analytics por comerciante: ventas, productos top, horas pico |

---

## 5. Archivos Criticos

### 5.1 Archivos MODIFICADOS en Sprint 1

| # | Archivo | Fase | Modificacion |
|---|---------|------|-------------|
| 1 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module` | F3 | Anadir `hook_preprocess_html()`, `hook_theme_suggestions_page_alter()`, `hook_preprocess_page()` con mapeo de rutas a body classes y variables zero-region |
| 2 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.services.yml` | F1, F13, F14, F15, F16, F17 | Registrar todos los nuevos servicios: FeatureGate, CopilotBridge, EmailSequence, CrossVerticalBridge, JourneyProgression, HealthScore, Experiment |
| 3 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.install` | F1 | Crear tabla `{comercio_conecta_feature_usage}` en hook_update/install |
| 4 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.info.yml` | F13, F14, F15 | Anadir dependencias: `jaraba_ai_agents`, `jaraba_email`, `jaraba_billing`, `jaraba_journey`, `jaraba_analytics` |
| 5 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.libraries.yml` | F4, F5 | Registrar library `comercio_conecta.modal-actions` con `core/drupal.dialog.ajax` |
| 6 | `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.routing.yml` | F16 | Anadir ruta API `/api/v1/copilot/comercio/proactive` para journey progression |
| 7 | `web/modules/custom/jaraba_comercio_conecta/scss/_variables.scss` | F5 | Auditoria SCSS, eliminacion `rgba()` bare, `color-mix()` para variantes |
| 8 | `web/modules/custom/jaraba_comercio_conecta/scss/main.scss` | F5 | Verificar `@use` exclusivo, sin `@import` |
| 9 | `web/modules/custom/jaraba_comercio_conecta/scss/_marketplace.scss` | F5 | Auditoria tokens CSS con `var(--ej-*, fallback)` |
| 10 | `web/modules/custom/jaraba_comercio_conecta/scss/_merchant-dashboard.scss` | F5 | Auditoria tokens CSS |
| 11 | `web/modules/custom/jaraba_comercio_conecta/scss/_product-detail.scss` | F5 | Auditoria tokens CSS |
| 12 | `web/modules/custom/jaraba_comercio_conecta/scss/_merchant-products.scss` | F5 | Auditoria tokens CSS |
| 13 | `web/modules/custom/jaraba_comercio_conecta/scss/_components.scss` | F5 | Auditoria tokens CSS |
| 14 | `web/modules/custom/jaraba_comercio_conecta/src/Service/ProductRetailService.php` | F1 | Integrar FeatureGate `check('products')` en `create()` |
| 15 | `web/modules/custom/jaraba_comercio_conecta/src/Service/MarketplaceService.php` | F1 | Integrar FeatureGate para operaciones gated |
| 16 | `web/modules/custom/jaraba_comercio_conecta/src/Service/MerchantDashboardService.php` | F16 | Inyectar health score en contexto del dashboard |
| 17 | `web/modules/custom/jaraba_comercio_conecta/src/Controller/MerchantPortalController.php` | F4, F16 | Adaptar a zero-region pattern, inyectar health score y journey progression |
| 18 | `web/modules/custom/jaraba_comercio_conecta/src/Controller/MarketplaceController.php` | F4 | Adaptar a zero-region pattern |
| 19 | `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | F1, F2, F16 | Registrar servicios en core: FeatureGate, UpgradeTriggers, JourneyProgression, HealthScore |
| 20 | `ecosistema_jaraba_theme/templates/page--comercio.html.twig` | F4 | Template zero-region con `{{ clean_content }}` y copilot FAB condicional |

### 5.2 Archivos CREADOS en Sprint 1

| # | Archivo | Fase |
|---|---------|------|
| 1 | `ecosistema_jaraba_core/src/Service/ComercioConectaFeatureGateService.php` | F1 |
| 2 | `ecosistema_jaraba_core/src/Service/ComercioConectaCopilotBridgeService.php` | F13 |
| 3 | `ecosistema_jaraba_core/src/Service/ComercioConectaEmailSequenceService.php` | F14 |
| 4 | `ecosistema_jaraba_core/src/Service/ComercioConectaCrossVerticalBridgeService.php` | F15 |
| 5 | `ecosistema_jaraba_core/src/Service/ComercioConectaJourneyProgressionService.php` | F16 |
| 6 | `ecosistema_jaraba_core/src/Service/ComercioConectaHealthScoreService.php` | F16 |
| 7 | `ecosistema_jaraba_core/src/Service/ComercioConectaExperimentService.php` | F17 |
| 8 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_free_products.yml` | F1 |
| 9 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_free_orders_per_month.yml` | F1 |
| 10 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_free_copilot_uses_per_month.yml` | F1 |
| 11 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_starter_products.yml` | F1 |
| 12 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_starter_orders_per_month.yml` | F1 |
| 13 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_starter_copilot_uses_per_month.yml` | F1 |
| 14 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_profesional_products.yml` | F1 |
| 15 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_profesional_orders_per_month.yml` | F1 |
| 16 | `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.comercio_conecta_profesional_copilot_uses_per_month.yml` | F1 |
| 17 | `ecosistema_jaraba_core/config/install/design_token_config.vertical_comercio_conecta.yml` | F5 |
| 18 | `ecosistema_jaraba_theme/templates/page--comercio.html.twig` | F4 |
| 19 | `jaraba_email/templates/mjml/comercio_conecta/onboarding-welcome.mjml` | F14 |
| 20 | `jaraba_email/templates/mjml/comercio_conecta/activation-first-product.mjml` | F14 |
| 21 | `jaraba_email/templates/mjml/comercio_conecta/first-sale-celebration.mjml` | F14 |
| 22 | `jaraba_email/templates/mjml/comercio_conecta/upsell-starter.mjml` | F14 |
| 23 | `jaraba_email/templates/mjml/comercio_conecta/retention-come-back.mjml` | F14 |
| 24 | `jaraba_email/templates/mjml/comercio_conecta/cart-abandoned.mjml` | F14 |
| 25 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.comercio_merchant_acquisition.yml` | F17 |
| 26 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.comercio_merchant_activation.yml` | F17 |
| 27 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.comercio_monetization.yml` | F17 |
| 28 | `ecosistema_jaraba_core/config/install/jaraba_analytics.funnel_definition.comercio_consumer_purchase.yml` | F17 |
| 29-39 | 11 Page Builder block templates `block--comercio-*.html.twig` | F18 |

---

## 6. Checklist Pre-Commit

### Sprint 1 — Infraestructura de Elevacion

```
F1 (FeatureGate):
- [ ] ComercioConectaFeatureGateService registrado en services.yml
- [ ] Tabla {comercio_conecta_feature_usage} creada en install hook
- [ ] ProductRetailService::create() llama a FeatureGate check('products')
- [ ] MarketplaceService operaciones gated por plan
- [ ] 9 FreemiumVerticalLimit configs creados (3 planes x 3 features)
- [ ] getAvailableAddons() incluye jaraba_comercio_conecta

F2 (UpgradeTriggers):
- [ ] 7 tipos de trigger registrados en UpgradeTriggerService
- [ ] fire() se llama despues de cada denied en FeatureGateService
- [ ] Mensajes de trigger con $this->t() (i18n)
- [ ] CTA URLs apuntan a /billing/upgrade?vertical=comercio_conecta

F3 (Hooks):
- [ ] hook_preprocess_html() mapea 8+ rutas a body classes
- [ ] hook_theme_suggestions_page_alter() genera suggestion page__comercio
- [ ] hook_preprocess_page() inyecta clean_content, page_title, breadcrumbs

F4 (Zero-Region):
- [ ] page--comercio.html.twig en ecosistema_jaraba_theme/templates/
- [ ] clean_content se renderiza en todas las rutas frontend
- [ ] Copilot FAB incluido via _header.html.twig condicional
- [ ] Modal CRUD con data-dialog-type="modal" + drupal.dialog.ajax
- [ ] Layout full-width mobile-first con max-width: 1400px

F5 (SCSS + Design Tokens):
- [ ] 0 instancias de rgba() bare en los 6 SCSS partials
- [ ] color-mix() usado para todas las variantes de color
- [ ] var(--ej-*) con fallbacks correctos en todos los archivos
- [ ] @use exclusivo, 0 instancias de @import
- [ ] design_token_config.vertical_comercio_conecta.yml creado
- [ ] npm run build + drush cr exitosos

F13 (CopilotBridge):
- [ ] ComercioConectaCopilotBridgeService registrado en services.yml
- [ ] Entrada 'comercio_conecta' en BaseAgent::getVerticalContext()
- [ ] 6 modos copilot definidos (product_description, pricing_advice, seo_optimization, customer_response, flash_offer_copy, chat)
- [ ] Soft upsell integrado en respuestas copilot

F14 (EmailSequences):
- [ ] ComercioConectaEmailSequenceService registrado en services.yml
- [ ] 6 templates MJML en jaraba_email/templates/mjml/comercio_conecta/
- [ ] Enrollment automatico en hook_entity_insert()
- [ ] Todos los textos con {{ t('...') }}

F15 (CrossVerticalBridge):
- [ ] ComercioConectaCrossVerticalBridgeService registrado en services.yml
- [ ] 4 bridges outgoing implementados con condiciones
- [ ] Max 2 bridges simultaneos por usuario
- [ ] Dismiss action funcional

F16 (JourneyProgression + HealthScore):
- [ ] ComercioConectaJourneyProgressionService con 10 reglas (8 comerciante + 2 consumidor)
- [ ] API /api/v1/copilot/comercio/proactive operativa
- [ ] Cache State API con TTL 3600s
- [ ] Dismiss action funcional
- [ ] ComercioConectaHealthScoreService con 5 dimensiones (sum pesos = 1.0)
- [ ] 8 KPIs con targets y direccion
- [ ] Widget SVG ring en merchant dashboard

F17 (Experiments + Funnels):
- [ ] ComercioConectaExperimentService registrado en services.yml
- [ ] 4 FunnelDefinition configs creados
- [ ] 4 experimentos iniciales definidos

F18 (Page Builder + Avatar):
- [ ] 11 block templates block--comercio-*.html.twig creados
- [ ] Avatar merchant con 5 items deep link
- [ ] Avatar consumer con 3 items
- [ ] Anonymous navigation incluye comercio-local landing
```

### Sprint 2 — Comercio Core (Pendiente)

```
F6 (Orders + Checkout):
- [ ] 9 entidades de pedidos/carrito creadas con EntityOwnerInterface + tenant_id
- [ ] 5 services de orders/checkout/payments registrados
- [ ] Stripe Connect integration con split por comerciante
- [ ] Flujo checkout multi-step funcional
- [ ] CartRecoveryService con deteccion a 24h

F7 (Portales):
- [ ] Merchant Portal completo: pedidos, pagos, configuracion
- [ ] Customer Portal completo: dashboard, pedidos, favoritos
- [ ] CustomerProfile + Wishlist entities creadas
- [ ] Templates zero-region para todos los portales

F8 (Search + SEO):
- [ ] ComercioSearchService con facetas y geolocalizacion
- [ ] LocalSeoService con Schema.org LocalBusiness
- [ ] Autocompletado funcional
- [ ] 5 entidades de busqueda/SEO creadas

F9 (Flash + QR + Reviews + Notif):
- [ ] FlashOfferService con geolocalizacion GPS
- [ ] QrRetailService con QR dinamicos
- [ ] ReviewRetailService con moderacion
- [ ] NotificationService multi-canal
- [ ] 11 entidades creadas
```

### Sprint 3 — Funcionalidades Avanzadas (Pendiente)

```
F10 (Shipping + POS + Admin + Analytics):
- [ ] ShippingRetailService con carriers (MRW, SEUR, Correos)
- [ ] ClickCollectService con horarios de recogida
- [ ] PosIntegrationService con sync bidireccional
- [ ] ComercioAdminService panel de administracion
- [ ] ComercioAnalyticsService con KPIs marketplace
- [ ] 6 entidades de shipping/POS/admin creadas
```

### Checklist Global (Todas las Fases)

```
DIRECTRICES:
- [ ] I18N: Todos los textos con $this->t() / {% trans %} / Drupal.t()
- [ ] SCSS: @use exclusivo, var(--ej-*), color-mix(), 0 emojis color
- [ ] ZERO-REGION: 3 hooks obligatorios implementados
- [ ] ENTITY: EntityOwnerInterface, EntityChangedInterface, tenant_id en todas las entidades
- [ ] DRUPAL11: No redeclarar propiedades tipadas heredadas
- [ ] DECLARE: declare(strict_types=1) en todo fichero PHP
- [ ] RATE-LIMIT: En operaciones costosas (AI calls, exports, bulk ops)
- [ ] AI-PROVIDER: @ai.provider para llamadas IA via SmartBaseAgent
- [ ] FRONTEND: Sin page.content, sin bloques heredados, mobile-first
- [ ] MODAL-CRUD: data-dialog-type="modal" con drupal.dialog.ajax
- [ ] PARCIALES: Prefix _ en templates parciales, incluidos via {% include %}
- [ ] TENANT: Sin acceso al tema de administracion para el tenant
- [ ] DB-INDEX: Indices en tenant_id + campos frecuentes
- [ ] ACCESS: AccessControlHandler en cada Content Entity
```

---

## Registro de Cambios

| Version | Fecha | Cambio |
|---------|-------|--------|
| 1.0.0 | 2026-02-17 | Documento inicial. Sprint 1 (F1-F5, F13-F18) ejecutado. Sprint 2 (F6-F9) y Sprint 3 (F10) planificados. |
