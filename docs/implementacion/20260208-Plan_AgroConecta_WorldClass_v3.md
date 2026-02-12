# 🌿 Plan de Implementación AgroConecta — Elevación a Clase Mundial v3.0

> **Tipo:** Plan de Implementación  
> **Versión:** 3.0  
> **Fecha:** 2026-02-08  
> **Estado:** En planificación  
> **Alcance:** Fases 5-9 completas (Search, Shipping, Traceability, QR, Analytics, Admin, AI Agents, Mobile PWA)  
> **Autor:** Equipo Técnico Jaraba  

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual: Fases 1-4 Completadas](#2-estado-actual-fases-1-4-completadas)
3. [Visión Arquitectónica de Fases 5-9](#3-visión-arquitectónica-de-fases-5-9)
4. [Priorización y Dependencias](#4-priorización-y-dependencias)
5. [Fase 5: Commerce Intelligence](#5-fase-5-commerce-intelligence)
6. [Fase 6: Logistics & Traceability](#6-fase-6-logistics--traceability)
7. [Fase 7: AI Agents & Analytics](#7-fase-7-ai-agents--analytics)
8. [Fase 8: Admin Panel & B2B Hub](#8-fase-8-admin-panel--b2b-hub)
9. [Fase 9: Mobile PWA](#9-fase-9-mobile-pwa)
10. [Tabla de Correspondencia de Especificaciones Técnicas](#10-tabla-de-correspondencia-de-especificaciones-técnicas)
11. [Cumplimiento de Directrices del Proyecto](#11-cumplimiento-de-directrices-del-proyecto)
12. [Inventario Completo de Entidades](#12-inventario-completo-de-entidades)
13. [Inventario Completo de Services](#13-inventario-completo-de-services)
14. [Inventario Completo de APIs REST](#14-inventario-completo-de-apis-rest)
15. [Frontend: Templates, SCSS y JavaScript](#15-frontend-templates-scss-y-javascript)
16. [Estimación Total de Esfuerzo](#16-estimación-total-de-esfuerzo)
17. [Verificación y Testing](#17-verificación-y-testing)

---

## 1. Resumen Ejecutivo

Este plan cubre la elevación de AgroConecta desde su estado actual (Fases 1-4 completadas: Commerce Core, Orders & Payments, Portales Producer/Customer, Reviews & Notifications) hasta un marketplace de clase mundial con:

| Fase | Nombre | Sprints | Docs Técnicos de Referencia |
|------|--------|---------|----------------------------|
| **5** | Commerce Intelligence | AC5-1 a AC5-4 | 55, 56 |
| **6** | Logistics & Traceability | AC6-1 a AC6-4 | 51, 80, 81 |
| **7** | AI Agents & Analytics | AC7-1 a AC7-6 | 57, 67, 68 |
| **8** | Admin Panel & B2B Hub | AC8-1 a AC8-4 | 58, 82 |
| **9** | Mobile PWA | AC9-1 a AC9-4 | 60 |

**Totales estimados:**
- **~30 nuevas Content Entities**
- **~15 nuevos Services PHP**
- **~120+ endpoints REST API**
- **~18 sprints de 2 semanas**
- **~400-520 horas de desarrollo**

---

## 2. Estado Actual: Fases 1-4 Completadas

### 2.1 Entidades Existentes (Fase 1-4)

| Entidad | Tipo | Fase | Doc |
|---------|------|------|-----|
| `ProductAgro` | ContentEntity | F1 | 48 |
| `ProducerProfile` | ContentEntity | F1 | 52 |
| `AgroCertification` | ContentEntity | F1 | 48 |
| `OrderAgro` | ContentEntity | F2 | 49 |
| `SuborderAgro` | ContentEntity | F2 | 49 |
| `OrderItemAgro` | ContentEntity | F2 | 49 |
| `ReviewAgro` | ContentEntity | F4 | 54 |
| `NotificationTemplateAgro` | ContentEntity | F4 | 59 |
| `NotificationLogAgro` | ContentEntity | F4 | 59 |
| `NotificationPreferenceAgro` | ContentEntity | F4 | 59 |

### 2.2 Services Existentes

| Service | Responsabilidad |
|---------|----------------|
| `AgroCartService` | Gestión del carrito |
| `AgroPaymentService` | Procesamiento de pagos |
| `AgroOrderService` | Ciclo de vida de pedidos |
| `ReviewService` | Gestión de reseñas |
| `NotificationService` | Sistema de notificaciones |

### 2.3 Frontend Existente

| Ruta | Template | Descripción |
|------|----------|-------------|
| `/agroconecta` | `page--agroconecta.html.twig` | Landing principal |
| `/agroconecta/product/{id}` | `agro-product-detail.html.twig` | Detalle de producto |
| `/agroconecta/producer/{id}` | `agro-producer-profile.html.twig` | Perfil de productor |
| `/agroconecta/customer` | `agro-customer-dashboard.html.twig` | Dashboard cliente |
| `/agroconecta/producer/dashboard` | `agro-producer-dashboard.html.twig` | Dashboard productor |

---

## 3. Visión Arquitectónica de Fases 5-9

### 3.1 Diagrama de Dependencias entre Fases

```
Fase 5: Commerce Intelligence
├── AC5-1/2: Search & Discovery (Doc 55)
│   └── AgroCategory, AgroCollection, SearchService
└── AC5-3/4: Promotions & Coupons (Doc 56)
    └── PromotionAgro, CouponAgro, PromotionService
         │
         ▼
Fase 6: Logistics & Traceability
├── AC6-1/2: Shipping Core (Doc 51)
│   └── ShipmentAgro, ShippingZone, ShippingRate, TrackingEvent
└── AC6-3/4: Traceability + QR (Docs 80, 81)
    └── AgroBatch, TraceEvent, IntegrityProof, QrCode, QrScanEvent
         │
         ▼
Fase 7: AI Agents & Analytics
├── AC7-1/2: Analytics Dashboard (Doc 57)
│   └── AnalyticsDailyAgro, AlertRuleAgro
├── AC7-3/4: Producer Copilot (Doc 67)
│   └── CopilotConversationAgro, CopilotMessageAgro
└── AC7-5/6: Sales Agent (Doc 68)
    └── SalesConversation, SalesMessage, CustomerPreference
         │
         ▼
Fase 8: Admin & B2B
├── AC8-1/2: Admin Panel (Doc 58)
│   └── Dashboard unificado, reportes, configuración
└── AC8-3/4: Partner Document Hub (Doc 82)
    └── PartnerRelationship, ProductDocument, DocumentDownloadLog
         │
         ▼
Fase 9: Mobile PWA (Doc 60)
└── AC9-1/2/3/4: React Native + Expo
    └── App Cliente + App Productor Pro
```

### 3.2 Principio Arquitectónico: Cada Fase Aporta Valor Independiente

Cada fase puede desplegarse y funcionar de forma autónoma. Las dependencias hacia atrás son siempre sobre fases ya completadas. Las dependencias hacia adelante son opcionales (mejoran pero no bloquean).

---

## 4. Priorización y Dependencias

### 4.1 Orden de Implementación (Confirmado)

El orden sigue la lógica de negocio: primero las funcionalidades que generan ingresos directos, luego las que generan confianza, después las de inteligencia y eficiencia, y finalmente la expansión de canales.

| Prioridad | Sprint | Funcionalidad | Justificación de Negocio |
|-----------|--------|---------------|--------------------------|
| **P0** | AC5-1/2 | Search & Discovery | Sin búsqueda no hay conversión |
| **P0** | AC5-3/4 | Promotions & Coupons | Motor de conversión y retención |
| **P0** | AC6-1/2 | Shipping Core | Requisito para vender productos físicos |
| **P1** | AC6-3/4 | Traceability + QR | Diferenciador competitivo, confianza |
| **P1** | AC7-1/2 | Analytics Dashboard | Visibilidad operativa para la toma de decisiones |
| **P1** | AC7-3/4 | Producer Copilot | Productividad del productor, calidad de contenido |
| **P2** | AC7-5/6 | Sales Agent | Conversión asistida, cross-sell, cart recovery |
| **P2** | AC8-1/2 | Admin Panel | Operación a escala |
| **P2** | AC8-3/4 | Partner Document Hub | Diferenciador B2B, canal de distribución |
| **P3** | AC9-1/4 | Mobile PWA | Expansión de canal, engagement |

### 4.2 Mapa de Dependencias entre Sprints

```
AC5-1/2 (Search) ──────────────────┐
                                    │
AC5-3/4 (Promotions) ──────────────┤
                                    │
AC6-1/2 (Shipping) ────────────────┤
  └── depende de: OrderAgro (F2)   │
                                    │
AC6-3/4 (Traceability+QR) ────────┤
  └── depende de: ProductAgro (F1) │
                                    │
AC7-1/2 (Analytics) ───────────────┤
  └── depende de: Todos los datos  ├──► AC8-1/2 (Admin Panel)
                                    │     └── depende de: Todas las entidades
AC7-3/4 (Producer Copilot) ────────┤
  └── depende de: jaraba_ai_core   │
                                    │
AC7-5/6 (Sales Agent) ─────────────┤
  └── depende de: Search, RAG      │
                                    │
AC8-3/4 (Partner Hub) ─────────────┘
  └── depende de: ProductAgro, Producer

AC9-1/4 (Mobile) ──── depende de: TODAS las APIs anteriores
```

---

> **📋 Documentos complementarios de detalle por fase:**
> - [Fase 5 - Commerce Intelligence](20260208-Plan_AgroConecta_WC_Fase5.md)
> - [Fase 6 - Logistics & Traceability](20260208-Plan_AgroConecta_WC_Fase6.md)
> - [Fase 7 - AI Agents & Analytics](20260208-Plan_AgroConecta_WC_Fase7.md)
> - [Fase 8 - Admin Panel & B2B Hub](20260208-Plan_AgroConecta_WC_Fase8.md)
> - [Fase 9 - Mobile PWA](20260208-Plan_AgroConecta_WC_Fase9.md)

---

## 5. Fase 5: Commerce Intelligence

> **Detalle completo:** [20260208-Plan_AgroConecta_WC_Fase5.md](20260208-Plan_AgroConecta_WC_Fase5.md)

### 5.1 Sprint AC5-1/2: Search & Discovery (Doc 55)

**Objetivo:** Implementar el sistema de búsqueda, categorización y descubrimiento de productos del marketplace.

#### Entidades Nuevas

| Entidad | Tipo | Campos Clave | Propósito |
|---------|------|-------------|-----------|
| `AgroCategory` | ContentEntity | `name`, `slug`, `parent_id`, `icon`, `image`, `position`, `is_featured`, `product_count` | Taxonomía jerárquica de productos |
| `AgroCollection` | ContentEntity | `name`, `slug`, `description`, `image`, `type` (manual/smart), `rules` (JSON), `product_ids` (JSON), `is_featured`, `position` | Colecciones curadas o automáticas de productos |

#### Service: `AgroSearchService`

```
Namespace: Drupal\jaraba_agroconecta_core\Service
Dependencias: entity_type.manager, database, pager.manager

Métodos:
- searchProducts(string $query, array $filters, int $page): array
  → Búsqueda fulltext en title, description, producer con LIKE y MATCH
  → Filtros: category, price_range, certification, origin, rating
  → Paginación con Drupal Pager API
  → Retorna: ['results' => ProductAgro[], 'total' => int, 'facets' => []]

- autocomplete(string $query, int $limit = 5): array
  → Sugerencias rápidas de título de productos, categorías, productores
  → Cacheable con cache tags

- getProductsByCategory(int $category_id, array $sort, int $page): array
  → Listado paginado de productos por categoría

- getFeaturedCollections(int $limit = 6): array
  → Colecciones destacadas para la home

- getFacets(array $current_filters): array
  → Calcula facetas disponibles para los filtros activos
```

#### Endpoints API (6)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/v1/agro/search` | Búsqueda fulltext con filtros |
| `GET` | `/api/v1/agro/search/autocomplete` | Autocompletado |
| `GET` | `/api/v1/agro/categories` | Árbol de categorías |
| `GET` | `/api/v1/agro/categories/{id}/products` | Productos por categoría |
| `GET` | `/api/v1/agro/collections` | Listado de colecciones |
| `GET` | `/api/v1/agro/collections/{id}` | Detalle con productos |

#### Frontend

| Componente | Template Twig | SCSS Partial | JS |
|------------|--------------|-------------|-----|
| Página de Búsqueda | `page--agro-search.html.twig` | `_agro-search.scss` | `agro-search.js` |
| Página de Categoría | `page--agro-category.html.twig` | `_agro-category.scss` | `agro-category.js` |
| Sidebar de Filtros | `_agro-filters-sidebar.html.twig` (partial) | Incluido en `_agro-search.scss` | Incluido |
| Grid de Productos | `_agro-product-grid.html.twig` (partial) | `_agro-product-grid.scss` | — |
| Colección Destada | `_agro-collection-card.html.twig` (partial) | `_agro-collection.scss` | — |

**Rutas frontend:**
- `/agroconecta/search` → Búsqueda con filtros
- `/agroconecta/category/{slug}` → Página de categoría
- `/agroconecta/collection/{slug}` → Página de colección

---

### 5.2 Sprint AC5-3/4: Promotions & Coupons (Doc 56)

**Objetivo:** Implementar el sistema de promociones, cupones y descuentos del marketplace.

#### Entidades Nuevas

| Entidad | Tipo | Campos Clave | Propósito |
|---------|------|-------------|-----------|
| `PromotionAgro` | ContentEntity | `name`, `description`, `type` (percentage/fixed/buy_x_get_y/free_shipping), `value`, `conditions` (JSON: min_orderamount, categories, products), `starts_at`, `ends_at`, `usage_limit`, `usage_count`, `is_active`, `priority`, `stackable`, `tenant_id` | Ofertas del marketplace |
| `CouponAgro` | ContentEntity | `code`, `promotion_id` (FK), `usage_limit`, `usage_count`, `customer_limit`, `is_active`, `created_by`, `tenant_id` | Códigos canjeables vinculados a promociones |

#### Service: `AgroPromotionService`

```
Métodos:
- evaluateCart(array $cart_items, ?string $coupon_code): PromotionResult
  → Evalúa todas las promociones activas aplicables al carrito
  → Aplica reglas de prioridad y stacking
  → Retorna: descuento total, desglose por item, promociones aplicadas

- validateCoupon(string $code, int $customer_id, array $cart): ValidationResult
  → Validaciones: existe, activo, no expirado, dentro de límites de uso,
    no usado por este cliente si customer_limit = 1
  → Retorna: is_valid, message, promotion

- applyCoupon(string $code, int $order_id): bool
  → Registra uso del cupón e incrementa contadores

- getActivePromotions(): array
  → Promociones activas ordenadas por prioridad

- calculateDiscount(PromotionAgro $promo, array $items): float
  → Calcula el descuento según tipo de promoción
```

#### Endpoints API (5)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/v1/agro/promotions/evaluate` | Evaluar carrito contra promociones |
| `POST` | `/api/v1/agro/coupons/validate` | Validar código de cupón |
| `POST` | `/api/v1/agro/coupons/apply` | Aplicar cupón a pedido |
| `GET` | `/api/v1/agro/promotions/active` | Promociones activas públicas |
| `GET` | `/api/v1/agro/promotions/{id}` | Detalle de promoción |

#### Frontend

| Componente | Template | SCSS | Descripción |
|------------|----------|------|-------------|
| Campo de cupón en checkout | Incluido en checkout existente | `_agro-coupon.scss` | Input + botón "Aplicar" + feedback |
| Banner de promoción | `_agro-promo-banner.html.twig` (partial) | `_agro-promo-banner.scss` | Banner animado en landing |
| Badge de descuento en producto | Incluido en product card existente | Incluido | Badge "–20%" en la esquina |

---

## 6. Fase 6: Logistics & Traceability

> **Detalle completo:** [20260208-Plan_AgroConecta_WC_Fase6.md](20260208-Plan_AgroConecta_WC_Fase6.md)

### 6.1 Sprint AC6-1/2: Shipping Core (Doc 51)

**Objetivo:** Sistema de envíos multi-carrier con cálculo dinámico de tarifas, soporte para envío refrigerado y tracking unificado.

#### Entidades Nuevas (4)

| Entidad | Campos Clave | Propósito |
|---------|-------------|-----------|
| `ShipmentAgro` | `order_id`, `suborder_id`, `carrier`, `tracking_number`, `state` (pending/picked_up/in_transit/delivered/returned), `estimated_delivery`, `actual_delivery`, `shipping_cost`, `weight_kg`, `is_refrigerated`, `label_url`, `tenant_id` | Envío físico |
| `ShippingZoneAgro` | `name`, `country_code`, `postal_prefix`, `region`, `is_active`, `tenant_id` | Zonas geográficas |
| `ShippingRateAgro` | `zone_id`, `carrier`, `service_type`, `weight_min`, `weight_max`, `base_price`, `price_per_kg`, `delivery_days_min`, `delivery_days_max`, `is_refrigerated`, `tenant_id` | Tarifas por zona/carrier |
| `TrackingEventAgro` | `shipment_id`, `status`, `description`, `location`, `event_timestamp`, `raw_data` (JSON) | Eventos de tracking |

#### Service: `AgroShippingService`

```
Métodos:
- calculateRates(array $items, Address $destination): ShippingQuote[]
  → Calcula tarifas de todos los carriers para el destino
  → Considera peso, refrigeración, zona
  → Mock adapters iniciales (MRW, SEUR, GLS, Correos)

- createShipment(OrderAgro $order, string $carrier, string $service): ShipmentAgro
  → Crea envío, solicita etiqueta al carrier (mock), guarda tracking

- getTracking(string $tracking_number): TrackingEventAgro[]
  → Obtiene eventos de tracking del carrier (mock)
  → Almacena en local para cache

- updateTrackingStatus(ShipmentAgro $shipment): void
  → Cron: consulta carriers y actualiza estados
  → Dispara notificaciones en cambios de estado

- estimateDelivery(Address $origin, Address $dest, string $carrier): DateRange
  → Estima rango de entrega según zona y carrier
```

#### Endpoints API (8)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/v1/agro/shipping/rates` | Calcular tarifas |
| `POST` | `/api/v1/agro/shipping/shipments` | Crear envío |
| `GET` | `/api/v1/agro/shipping/shipments/{id}` | Detalle de envío |
| `GET` | `/api/v1/agro/shipping/shipments/{id}/tracking` | Eventos de tracking |
| `PATCH` | `/api/v1/agro/shipping/shipments/{id}` | Actualizar estado |
| `GET` | `/api/v1/agro/shipping/zones` | Listar zonas |
| `GET` | `/api/v1/agro/shipping/rates` | Listar tarifas |
| `GET` | `/api/v1/agro/shipping/carriers` | Carriers disponibles |

#### Frontend

- **Tracking timeline** (`_agro-tracking-timeline.html.twig`): Timeline visual de eventos
- **Selector de envío en checkout**: Integrado en checkout
- **SCSS:** `_agro-shipping.scss`, `_agro-tracking.scss`

---

### 6.2 Sprint AC6-3/4: Traceability + QR (Docs 80, 81)

**Objetivo:** Sistema de trazabilidad inmutable con hash chain, QR dinámicos phy-gitales y landing pages públicas.

#### Entidades Nuevas (6)

| Entidad | Propósito | Doc |
|---------|-----------|-----|
| `AgroBatch` | Lote de producción con cadena de custodia | 80 |
| `TraceEvent` | Evento inmutable de la cadena (siembra, cosecha, envasado) | 80 |
| `IntegrityProof` | Prueba de integridad via hash anchoring | 80 |
| `QrCodeAgro` | QR dinámico con tracking y analytics | 81 |
| `QrScanEvent` | Evento de escaneo con geolocalización | 81 |
| `QrLeadCapture` | Lead capturado desde landing de QR | 81 |

#### Services (4)

| Service | Métodos Clave |
|---------|---------------|
| `TraceabilityService` | `createBatch()`, `addTraceEvent()`, `verifyIntegrity()`, `generateCertificate()` |
| `QrGeneratorService` | `generateForLote()`, `generateForProduct()`, `generateForProducer()` |
| `QrScanTrackingService` | `trackScan()`, `markConversion()`, `getQrAnalytics()` |
| `LeadCaptureService` | `captureLead()`, `assignDiscountCode()`, `exportLeadsCsv()` |

#### Frontend

- **Landing de Trazabilidad** (`page--agro-traceability.html.twig`): Página pública SEO con Schema.org
- **Timeline de lote** (`_agro-batch-timeline.html.twig`): Timeline visual de eventos del lote
- **Dashboard QR del productor**: Widget en el portal del productor
- **SCSS:** `_agro-traceability.scss`, `_agro-qr.scss`

---

## 7. Fase 7: AI Agents & Analytics

> **Detalle completo:** [20260208-Plan_AgroConecta_WC_Fase7.md](20260208-Plan_AgroConecta_WC_Fase7.md)

### 7.1 Sprint AC7-1/2: Analytics Dashboard (Doc 57)

#### Entidades Nuevas (2)

| Entidad | Propósito |
|---------|-----------|
| `AnalyticsDailyAgro` | Métricas agregadas diarias: GMV, pedidos, conversión, usuarios |
| `AlertRuleAgro` | Reglas de alerta configurables: umbral, métrica, canal |

#### Service: `AgroAnalyticsService`

Cron de agregación diaria que calcula KPIs desde las entidades de pedidos, productos, usuarios. Dashboard frontend con Chart.js para gráficos interactivos, KPI cards, y tablas de ranking.

### 7.2 Sprint AC7-3/4: Producer Copilot (Doc 67)

#### Entidades Nuevas (2)

| Entidad | Propósito |
|---------|-----------|
| `CopilotConversationAgro` | Conversación del productor con el copiloto |
| `CopilotMessageAgro` | Mensaje individual con tokens/modelo/latencia |

#### Service: `ProducerCopilotService`

Integración con `jaraba_ai_core` existente. Pipeline RAG con Qdrant para grounding en datos de productos, precios de competencia y conocimiento agrícola. Generadores: descripción SEO, sugerencia de precio, respuesta a reseña, análisis de ventas.

### 7.3 Sprint AC7-5/6: Sales Agent (Doc 68)

#### Entidades Nuevas (3)

| Entidad | Propósito |
|---------|-----------|
| `SalesConversationAgro` | Conversación de compra asistida |
| `SalesMessageAgro` | Mensaje con intent, entities, acciones ejecutadas |
| `CustomerPreferenceAgro` | Preferencias declaradas/inferidas del cliente |

#### Service: `SalesAgentService`

Agent conversacional para consumidores. Pipeline: Intent Classification → Entity Extraction → Product Retrieval (Qdrant) → Response Generation → Action Execution (añadir al carrito, aplicar cupón). Chat widget embebido + futura integración WhatsApp Business.

---

## 8. Fase 8: Admin Panel & B2B Hub

> **Detalle completo:** [20260208-Plan_AgroConecta_WC_Fase8.md](20260208-Plan_AgroConecta_WC_Fase8.md)

### 8.1 Sprint AC8-1/2: Admin Panel (Doc 58)

Dashboard admin unificado en `/agroconecta/admin` con menú lateral dedicado. Secciones: Pedidos, Catálogo, Productores, Clientes, Marketing, Finanzas, Reportes, Configuración. Reportes exportables a CSV/PDF. Panel de configuración de carriers, zones, tarifas, promociones.

### 8.2 Sprint AC8-3/4: Partner Document Hub (Doc 82)

#### Entidades Nuevas (3)

| Entidad | Propósito |
|---------|-----------|
| `PartnerRelationship` | Relación B2B entre productor y partner (distribuidor, exportador, HORECA) |
| `ProductDocument` | Documento asociado a producto (ficha técnica, analítica, certificación) |
| `DocumentDownloadLog` | Registro de descargas para analytics y auditoría |

#### Services (3)

| Service | Funcionalidad |
|---------|---------------|
| `PartnerRelationshipService` | CRUD de relaciones, magic links, niveles de acceso |
| `PartnerDocumentService` | Gestión de documentos con permisos por nivel/tipo |
| `TechSheetGeneratorService` | Generación automática de fichas técnicas PDF |

#### Frontend

- **Portal Partner** (`page--agro-partner-portal.html.twig`): Acceso via magic link sin cuenta
- **Dashboard B2B del productor**: Widget en portal productor
- **SCSS:** `_agro-partner-portal.scss`, `_agro-hub-docs.scss`

---

## 9. Fase 9: Mobile PWA

> **Detalle completo:** [20260208-Plan_AgroConecta_WC_Fase9.md](20260208-Plan_AgroConecta_WC_Fase9.md)

### 9.1 Arquitectura

- **Framework:** React Native 0.73+ con Expo SDK 50
- **Lenguaje:** TypeScript 5.x
- **Estado:** Zustand + TanStack Query
- **Auth:** OAuth 2.0 + Secure Storage
- **Push:** Firebase Cloud Messaging + Expo Notifications
- **Pagos:** Stripe SDK + Apple Pay + Google Pay

### 9.2 Aplicaciones

| App | Usuarios | Prioridad |
|-----|----------|-----------|
| **AgroConecta** | Consumidores: Home, Búsqueda, Carrito, Checkout, Pedidos, Favoritos | P0 |
| **AgroConecta Pro** | Productores: Dashboard, Pedidos, Productos, Inventario, Finanzas | P1 |

### 9.3 Requisito Previo

Todas las APIs REST de Fases 5-8 deben estar completas y documentadas antes de comenzar el desarrollo móvil. La app consume exclusivamente las APIs existentes, no añade lógica backend nueva.

---

## 10. Tabla de Correspondencia de Especificaciones Técnicas

Esta tabla mapea cada documento técnico de especificación con los sprints, entidades, services y APIs que implementa.

| Código Doc | Título | Fase | Sprints | Entidades | Services | APIs | Estado |
|-----------|--------|------|---------|-----------|----------|------|--------|
| 47 | Commerce Core | F1 | AC1 | ProductAgro, ProducerProfile, AgroCertification | AgroCartService | 12 | ✅ |
| 48 | Product Catalog | F1 | AC1 | (incluido en 47) | — | — | ✅ |
| 49 | Orders & Payments | F2 | AC2 | OrderAgro, SuborderAgro, OrderItemAgro | AgroOrderService, AgroPaymentService | 8 | ✅ |
| 52 | Producer Portal | F3 | AC3 | — | — | 4 | ✅ |
| 53 | Customer Portal | F3 | AC3 | — | — | 4 | ✅ |
| 54 | Reviews | F4 | AC4 | ReviewAgro | ReviewService | 4 | ✅ |
| 59 | Notifications | F4 | AC4 | NotificationTemplate/Log/Preference | NotificationService | 6 | ✅ |
| **55** | **Search & Discovery** | **F5** | **AC5-1/2** | AgroCategory, AgroCollection | AgroSearchService | 6 | ⬜ |
| **56** | **Promotions & Coupons** | **F5** | **AC5-3/4** | PromotionAgro, CouponAgro | AgroPromotionService | 5 | ⬜ |
| **51** | **Shipping & Logistics** | **F6** | **AC6-1/2** | ShipmentAgro, ShippingZone, ShippingRate, TrackingEvent | AgroShippingService | 8 | ⬜ |
| **80** | **Traceability System** | **F6** | **AC6-3/4** | AgroBatch, TraceEvent, IntegrityProof | TraceabilityService | 6 | ⬜ |
| **81** | **QR Dynamic** | **F6** | **AC6-3/4** | QrCodeAgro, QrScanEvent, QrLeadCapture | QrGeneratorService, QrScanTrackingService, LeadCaptureService | 14 | ⬜ |
| **57** | **Analytics Dashboard** | **F7** | **AC7-1/2** | AnalyticsDailyAgro, AlertRuleAgro | AgroAnalyticsService | 8 | ⬜ |
| **67** | **Producer Copilot** | **F7** | **AC7-3/4** | CopilotConversationAgro, CopilotMessageAgro | ProducerCopilotService | 6 | ⬜ |
| **68** | **Sales Agent** | **F7** | **AC7-5/6** | SalesConversation, SalesMessage, CustomerPreference | SalesAgentService | 9 | ⬜ |
| **58** | **Admin Panel** | **F8** | **AC8-1/2** | — (usa entidades existentes) | AgroAdminService | 10 | ⬜ |
| **82** | **Partner Document Hub** | **F8** | **AC8-3/4** | PartnerRelationship, ProductDocument, DocumentDownloadLog | PartnerRelationshipService, PartnerDocumentService, TechSheetGeneratorService | 19 | ⬜ |
| **60** | **Mobile App** | **F9** | **AC9-1/4** | — (consume APIs) | — | — | ⬜ |

---

## 11. Cumplimiento de Directrices del Proyecto

Esta sección documenta cómo cada aspecto de la implementación cumplirá con las directrices vigentes del proyecto.

### 11.1 Theming: Federated Design Tokens

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **SSOT** | Todos los nuevos SCSS usan `var(--ej-*)` con fallbacks inline. NO se crean variables SCSS en módulos satélite | `2026-02-05_arquitectura_theming_saas_master.md` §2.2 |
| **Paleta Agro** | Se usan los tokens existentes: `--ej-color-agro` (`#556B2F`), `--ej-color-agro-dark` (`#3E4E23`) | `_variables.scss` L182-183 |
| **Compilación** | Cada parcial SCSS se compila dentro del `main.scss` del módulo `jaraba_agroconecta_core` | `scss-estilos.md` |
| **Package.json** | Si el módulo AgroConecta no tiene `package.json`, se crea con scripts de build estándar | `2026-02-05_arquitectura_theming_saas_master.md` §5.1 |
| **Dart Sass moderno** | `@use 'sass:color'` en lugar de funciones deprecadas (`darken()`, `lighten()`) | `2026-02-05_arquitectura_theming_saas_master.md` §6.2 |
| **Header de documentación** | Cada archivo SCSS incluye header con descripción y comando de compilación | `2026-02-05_arquitectura_theming_saas_master.md` §5.3 |

### 11.2 Frontend: Clean Twig Templates

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **Layout full-width limpio** | Todas las páginas frontend usan `page--agro-{route}.html.twig` sin `page.content` ni bloques de Drupal | `frontend-page-pattern.md` §1 |
| **Partials reutilizables** | Componentes compartidos como `_agro-product-grid.html.twig` se crean como partials con `{% include %}` | `frontend-page-pattern.md` §1 |
| **Body classes via hook** | `hook_preprocess_html()` para añadir clases al body. NUNCA `attributes.addClass()` en el template | `frontend-page-pattern.md` §2.5 |
| **Header/Footer heredados** | Cada template incluye `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}` y `_footer.html.twig` con variables configurables | `frontend-page-pattern.md` §1 |
| **Configuración del tema** | Los parciales usan `theme_settings` de Drupal para textos del footer, logo, y links de navegación, configurables desde UI | Drupal Theme Settings API |

### 11.3 i18n: Textos Traducibles

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **Twig** | Todos los textos usan `{{ 'Texto'|t }}` o `{% trans %}Texto{% endtrans %}` | `i18n-traducciones.md` |
| **JavaScript** | Todos los textos dinámicos usan `Drupal.t('Texto')` | `i18n-traducciones.md` |
| **PHP** | Todos los strings de servicio usan `$this->t('Texto')` o `new TranslatableMarkup()` | Drupal i18n API |

### 11.4 Entidades: Content Entity Checklist

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **4 archivos YAML obligatorios** | Cada entidad tiene `*.routing.yml`, `*.links.menu.yml`, `*.links.task.yml`, `*.links.action.yml` | `drupal-custom-modules.md` §4 |
| **Handlers completos** | Cada entidad define: `list_builder`, `views_data`, `form` (default/add/edit/delete), `access`, `route_provider` | `drupal-custom-modules.md` §1 |
| **Field UI** | `field_ui_base_route` apunta a `entity.{type}.settings` para cada entidad | `drupal-custom-modules.md` §5 |
| **Navegación structure** | Settings en `/admin/structure/agro-{entity}` con menu link parent `system.admin_structure` | `drupal-custom-modules.md` §4 |
| **Navegación content** | Collection en `/admin/content/agro-{entity}` con task link `base_route: system.admin_content` | `drupal-custom-modules.md` §4 |

### 11.5 CRUD: Slide-Panel Pattern

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **Todas las acciones CRUD en modal** | Crear/Editar/Ver se abren en slide-panel off-canvas, nunca navegan fuera de la página | `slide-panel-modales.md` |
| **Data attributes** | Botones de acción usan `data-slide-panel`, `data-slide-panel-url`, `data-slide-panel-title` | `slide-panel-modales.md` §2 |
| **AJAX response** | Controladores detectan `$request->isXmlHttpRequest()` y devuelven solo HTML del formulario | `slide-panel-modales.md` §3 |
| **Dependencia de library** | Cada módulo que usa slide-panel declara `ecosistema_jaraba_theme/slide-panel` como dependencia | `slide-panel-modales.md` lessons §7 |

### 11.6 Iconografía

| Directriz | Cómo se Cumple | Referencia |
|-----------|----------------|------------|
| **Categoría de iconos** | Iconos agro se ubican en `ecosistema_jaraba_core/images/icons/verticals/` | `scss-estilos.md` §Iconografía |
| **Dual variant** | Cada icono tiene versión outline (`.svg`) y duotone (`-duotone.svg`) | `scss-estilos.md` §Regla de Iconos |
| **Colores via CSS** | Colores aplicados dinámicamente via `jaraba_icon()` con paleta de marca | `scss-estilos.md` §Colores Disponibles |
| **Paleta** | `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `verde-oliva` | `scss-estilos.md` §Colores |

### 11.7 Mobile-First y Accesibilidad

| Directriz | Cómo se Cumple |
|-----------|----------------|
| **Mobile-first** | SCSS usa breakpoints ascendentes (`@media (min-width: ...)`) |
| **Touch targets** | Mínimo 44×44px para elementos interactivos |
| **ARIA** | Labels descriptivos, roles semánticos, focus management |
| **Contraste** | WCAG AA mínimo (4.5:1) |

---

## 12. Inventario Completo de Entidades

### 12.1 Entidades Nuevas por Fase (Total: ~27)

| # | Entidad | Fase | Sprint | Campos | Doc |
|---|---------|------|--------|--------|-----|
| 1 | `AgroCategory` | F5 | AC5-1 | ~10 | 55 |
| 2 | `AgroCollection` | F5 | AC5-1 | ~10 | 55 |
| 3 | `PromotionAgro` | F5 | AC5-3 | ~14 | 56 |
| 4 | `CouponAgro` | F5 | AC5-3 | ~8 | 56 |
| 5 | `ShipmentAgro` | F6 | AC6-1 | ~16 | 51 |
| 6 | `ShippingZoneAgro` | F6 | AC6-1 | ~6 | 51 |
| 7 | `ShippingRateAgro` | F6 | AC6-1 | ~12 | 51 |
| 8 | `TrackingEventAgro` | F6 | AC6-1 | ~8 | 51 |
| 9 | `AgroBatch` | F6 | AC6-3 | ~12 | 80 |
| 10 | `TraceEventAgro` | F6 | AC6-3 | ~10 | 80 |
| 11 | `IntegrityProofAgro` | F6 | AC6-3 | ~8 | 80 |
| 12 | `QrCodeAgro` | F6 | AC6-3 | ~18 | 81 |
| 13 | `QrScanEvent` | F6 | AC6-3 | ~20 | 81 |
| 14 | `QrLeadCapture` | F6 | AC6-3 | ~14 | 81 |
| 15 | `AnalyticsDailyAgro` | F7 | AC7-1 | ~20 | 57 |
| 16 | `AlertRuleAgro` | F7 | AC7-1 | ~10 | 57 |
| 17 | `CopilotConversationAgro` | F7 | AC7-3 | ~10 | 67 |
| 18 | `CopilotMessageAgro` | F7 | AC7-3 | ~12 | 67 |
| 19 | `SalesConversationAgro` | F7 | AC7-5 | ~14 | 68 |
| 20 | `SalesMessageAgro` | F7 | AC7-5 | ~14 | 68 |
| 21 | `CustomerPreferenceAgro` | F7 | AC7-5 | ~10 | 68 |
| 22 | `PartnerRelationship` | F8 | AC8-3 | ~14 | 82 |
| 23 | `ProductDocument` | F8 | AC8-3 | ~16 | 82 |
| 24 | `DocumentDownloadLog` | F8 | AC8-3 | ~6 | 82 |

---

## 13. Inventario Completo de Services

| # | Service | Fase | Dependencias |
|---|---------|------|-------------|
| 1 | `AgroSearchService` | F5 | entity_type.manager, database |
| 2 | `AgroPromotionService` | F5 | entity_type.manager |
| 3 | `AgroShippingService` | F6 | entity_type.manager, http_client |
| 4 | `TraceabilityService` | F6 | entity_type.manager, jaraba.firma_digital |
| 5 | `QrGeneratorService` | F6 | endroid/qr-code |
| 6 | `QrScanTrackingService` | F6 | entity_type.manager |
| 7 | `LeadCaptureService` | F6 | entity_type.manager |
| 8 | `AgroAnalyticsService` | F7 | entity_type.manager, database |
| 9 | `ProducerCopilotService` | F7 | jaraba_ai_core, jaraba_rag |
| 10 | `SalesAgentService` | F7 | jaraba_ai_core, jaraba_rag |
| 11 | `AgroAdminService` | F8 | entity_type.manager |
| 12 | `PartnerRelationshipService` | F8 | entity_type.manager |
| 13 | `PartnerDocumentService` | F8 | entity_type.manager, file.repository |
| 14 | `TechSheetGeneratorService` | F8 | PDF generator, Twig |

---

## 14. Inventario Completo de APIs REST

### 14.1 Resumen por Fase

| Fase | Endpoints Nuevos | Acumulado |
|------|-----------------|-----------|
| F1-4 (completadas) | ~38 | 38 |
| F5 Commerce Intelligence | 11 | 49 |
| F6 Logistics & Traceability | 28+ | 77 |
| F7 AI Agents & Analytics | 23 | 100 |
| F8 Admin & B2B | 29 | 129 |
| **Total** | **~129 endpoints** | |

---

## 15. Frontend: Templates, SCSS y JavaScript

### 15.1 Nuevas Páginas Frontend

| Ruta | Template | Body Class | SCSS |
|------|----------|------------|------|
| `/agroconecta/search` | `page--agro-search.html.twig` | `agro-search-page` | `_agro-search.scss` |
| `/agroconecta/category/{slug}` | `page--agro-category.html.twig` | `agro-category-page` | `_agro-category.scss` |
| `/agroconecta/collection/{slug}` | `page--agro-collection.html.twig` | `agro-collection-page` | (reutiliza) |
| `/agroconecta/tracking/{number}` | `page--agro-tracking.html.twig` | `agro-tracking-page` | `_agro-tracking.scss` |
| `/trazabilidad/{code}` | `page--agro-traceability.html.twig` | `agro-traceability-page` | `_agro-traceability.scss` |
| `/agroconecta/analytics` | `page--agro-analytics.html.twig` | `agro-analytics-page` | `_agro-analytics.scss` |
| `/agroconecta/admin` | `page--agro-admin.html.twig` | `agro-admin-page` | `_agro-admin.scss` |
| `/agroconecta/partner/{token}` | `page--agro-partner.html.twig` | `agro-partner-page` | `_agro-partner.scss` |

### 15.2 Nuevos Twig Partials

| Partial | Reutilizado en | Propósito |
|---------|---------------|-----------|
| `_agro-product-grid.html.twig` | Search, Category, Collection | Grid responsive de product cards |
| `_agro-filters-sidebar.html.twig` | Search, Category | Sidebar de filtros con facetas |
| `_agro-collection-card.html.twig` | Home, Collections | Card de colección destacada |
| `_agro-promo-banner.html.twig` | Home, Category | Banner de promoción activa |
| `_agro-tracking-timeline.html.twig` | Tracking, Customer Dashboard | Timeline visual de envío |
| `_agro-batch-timeline.html.twig` | Traceability landing | Timeline de eventos del lote |
| `_agro-chat-widget.html.twig` | Todas las páginas agro | Widget de chat del Sales Agent |
| `_agro-analytics-kpi.html.twig` | Analytics, Admin | Card de KPI con sparkline |

### 15.3 Nuevos SCSS Partials (todos en `jaraba_agroconecta_core/scss/`)

```
_agro-search.scss
_agro-category.scss
_agro-collection.scss
_agro-coupon.scss
_agro-promo-banner.scss
_agro-shipping.scss
_agro-tracking.scss
_agro-traceability.scss
_agro-qr.scss
_agro-analytics.scss
_agro-admin.scss
_agro-partner-portal.scss
_agro-hub-docs.scss
_agro-copilot.scss
_agro-sales-agent.scss
```

### 15.4 Nuevos JavaScript (Drupal Behaviors)

```
agro-search.js         → Autocomplete, filtros AJAX, infinite scroll
agro-category.js       → Filtro y ordenación dinámica
agro-checkout-promo.js → Validación de cupón en tiempo real
agro-tracking.js       → Actualización de tracking en tiempo real
agro-traceability.js   → Interacción con timeline de lote
agro-analytics.js      → Chart.js dashboards, exportación
agro-copilot.js        → Chat widget del Producer Copilot
agro-sales-agent.js    → Chat widget del Sales Agent
agro-partner.js        → Portal partner: descarga ZIP, búsqueda docs
```

---

## 16. Estimación Total de Esfuerzo

| Fase | Sprints | Horas Est. | Semanas |
|------|---------|-----------|---------|
| F5: Commerce Intelligence | AC5-1 a AC5-4 | 50-65h | 4 sem |
| F6: Logistics & Traceability | AC6-1 a AC6-4 | 80-110h | 4 sem |
| F7: AI Agents & Analytics | AC7-1 a AC7-6 | 100-140h | 6 sem |
| F8: Admin Panel & B2B Hub | AC8-1 a AC8-4 | 70-95h | 4 sem |
| F9: Mobile PWA | AC9-1 a AC9-4 | 100-140h | 8 sem |
| **TOTAL** | **18 sprints** | **400-550h** | **~26 sem** |

---

## 17. Verificación y Testing

### 17.1 Por cada Sprint

1. **Entidades:** `drush cr`, verificar tablas en DB, verificar `/admin/structure`, verificar `/admin/content`
2. **Services:** Tests unitarios con PHPUnit mock
3. **APIs:** Verificar endpoints con browser o curl
4. **Frontend:** Verificar en navegador a 3 breakpoints (mobile 375px, tablet 768px, desktop 1440px)
5. **i18n:** Verificar que todos los strings tienen `|t` o `Drupal.t()`
6. **SCSS:** Compilar sin errores, verificar que solo usa `var(--ej-*)`
7. **Accesibilidad:** Verificar ARIA, contraste, tab order

### 17.2 E2E Tests (Cypress)

Para cada funcionalidad crítica se creará un test Cypress siguiendo el patrón de `e2e_testing_guide.md`:
- Login robusto con `cy.session()`
- Verificación de rutas y elementos
- Flujos completos (buscar → añadir al carrito → checkout → tracking)

---

> **📋 Este documento es el índice maestro. Los detalles de implementación campo por campo, método por método, se encuentran en los documentos complementarios por fase referenciados en la sección 5-9.**

---

*Documento generado: 2026-02-08 | Jaraba Impact Platform | AgroConecta World-Class Elevation v3.0*
