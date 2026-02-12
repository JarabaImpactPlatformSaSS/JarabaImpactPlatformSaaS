# 📋 Plan de Implementación AgroConecta v1.0

> **Fecha:** 2026-02-08
> **Estado:** Planificado
> **Estimación:** ~720h (7 fases)
> **Vertical:** AgroConecta (Marketplace Agroalimentario Multi-Vendor)

---

## 📑 Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Decisiones Arquitectónicas](#2-decisiones-arquitectónicas)
3. [Componentes Reutilizables](#3-componentes-reutilizables)
4. [Directrices SaaS Obligatorias](#4-directrices-saas-obligatorias)
5. [Fases de Implementación](#5-fases-de-implementación)
6. [Estructura de Módulos](#6-estructura-de-módulos)
7. [Verificación](#7-verificación)

---

## 1. Resumen Ejecutivo

Implementación del vertical AgroConecta como marketplace agroalimentario multi-vendor. Se compone de **3 módulos Drupal** (`jaraba_agroconecta_core`, `jaraba_agroconecta_traceability`, `jaraba_agroconecta_ai`) distribuidos en 7 fases secuenciales.

**Documentación técnica base:** 18 documentos (Docs 47-61, 67-68, 80-82) + 2 aprendizajes IA.

---

## 2. Decisiones Arquitectónicas

| Decisión | Elección | Justificación |
|----------|----------|---------------|
| Commerce Platform | Drupal Commerce 3.x (NO Ecwid) | SSR para GEO, control total multi-tenant |
| Payments | Stripe Connect Destination Charges | Split payments, plataforma NO es Merchant of Record |
| Order Model | `suborder_agro` por productor | Fulfillment independiente por vendor |
| Traceability | Hybrid off-chain/on-chain (Hash Anchoring) | Verificable sin costes blockchain completos |
| Mobile | PWA primero → React Native/Expo futuro | 2 apps: consumidor + productor |
| AI Pipeline | RAG unificado Qdrant | Colecciones `agro_products`, `agro_producers`, `agro_regulations` |

---

## 3. Componentes Reutilizables

| Componente | Módulo Origen | Acción |
|-----------|---------------|--------|
| Commerce Store por Tenant | `jaraba_commerce` | Extender |
| Schema.org JSON-LD | `jaraba_commerce` | Reusar |
| Social Commerce Webhooks | `jaraba_social_commerce` | Reusar |
| Stripe Connect Split | `jaraba_foc` | Reusar patrón |
| RAG Pipeline | `jaraba_rag` | Extender colecciones |
| AI Provider Abstraction | `jaraba_copilot_v2` + `@ai.provider` | Reusar |
| Firma Digital | `FirmaDigitalService` | Reusar |
| PDF Certificados | `CertificadoPdfService` | Reusar |
| Agent Framework | `jaraba_ai_agents` | Reusar |
| Design Tokens SCSS | `ecosistema_jaraba_core` | Consumir `var(--ej-*)` |
| Slide-Panel CRUD | `ecosistema_jaraba_theme` | Reusar |
| Partials (header/footer) | `ecosistema_jaraba_theme/partials/` | Reusar + crear agro |

---

## 4. Directrices SaaS Obligatorias

### 4.1 SCSS: Federated Design Tokens
- Solo `var(--ej-*, $fallback)` — NUNCA `$ej-*` en módulos
- `package.json` obligatorio con script `build`
- Dart Sass moderno: `@use 'sass:color'` + `color.adjust()`
- Colores AgroConecta: `--ej-color-agro: #556B2F`, `--ej-color-agro-dark: #3E4E23`

### 4.2 i18n
- PHP: `$this->t('Texto')` | Twig: `{% trans %}` | JS: `Drupal.t()`

### 4.3 Frontend Pages
- Templates `page--*.html.twig` limpias sin `page.content`
- Partials via `{% include '@ecosistema_jaraba_theme/partials/*.html.twig' %}`
- Body classes via `hook_preprocess_html()`, NUNCA `attributes.addClass()`
- Variables configurables desde Theme Settings para footer/header

### 4.4 Slide-Panel CRUD
- Toda acción crear/editar/ver en frontend abre en slide-panel
- Controlador detecta `$request->isXmlHttpRequest()` → solo HTML
- Dependencia: `ecosistema_jaraba_theme/slide-panel`

### 4.5 Content Entities
- 4 archivos YAML obligatorios: `routing.yml`, `links.menu.yml`, `links.task.yml`, `links.action.yml`
- Navegación: `/admin/content` (tab), `/admin/structure` (Field UI)
- Handlers: `list_builder`, `views_data`, `form`, `access`, `route_provider`

### 4.6 Iconos
- Siempre 2 versiones: `{nombre}.svg` (outline) + `{nombre}-duotone.svg`
- Ubicación: `ecosistema_jaraba_core/images/icons/verticals/`
- Uso: `{{ jaraba_icon('verticals', 'finca', { color: 'agro' }) }}`

### 4.7 Seguridad
- Rate limiting en APIs
- Prompt sanitization en agentes IA
- HMAC verification en webhooks Stripe y carriers

---

## 5. Fases de Implementación

| Fase | Descripción | Horas | Módulo |
|------|-------------|-------|--------|
| **1** | Commerce Core: Entidades producto, productor, Commerce 3.x | 120h | `jaraba_agroconecta_core` |
| **2** | Orders + Checkout + Payments: Stripe Connect splits | 100h | `jaraba_agroconecta_core` |
| **3** | Shipping & Logistics: MRW, SEUR, GLS | 80h | `jaraba_agroconecta_core` |
| **4** | Portales Producer + Customer: Dashboards frontend | 100h | `jaraba_agroconecta_core` |
| **5** | Traceability + QR + Certifications | 100h | `jaraba_agroconecta_traceability` |
| **6** | AI Agents: Producer Copilot + Sales Agent | 120h | `jaraba_agroconecta_ai` |
| **7** | Search, Reviews, Promotions, Analytics, Mobile | 100h | `jaraba_agroconecta_core` |

**Total: ~720h**

---

## 6. Estructura de Módulos

### 6.1 `jaraba_agroconecta_core`

**Content Entities:**

| Entidad | ID | `/admin/content` | `/admin/structure` |
|---------|-----|---|---|
| Producto Agro | `product_agro` | `/admin/content/agro-products` | `/admin/structure/agro-product` |
| Perfil Productor | `producer_profile` | `/admin/content/agro-producers` | `/admin/structure/agro-producer` |
| Pedido Agro | `order_agro` | `/admin/content/agro-orders` | `/admin/structure/agro-order` |
| Subpedido | `suborder_agro` | `/admin/content/agro-suborders` | `/admin/structure/agro-suborder` |
| Envío | `agro_shipment` | `/admin/content/agro-shipments` | `/admin/structure/agro-shipment` |
| Review | `agro_review` | `/admin/content/agro-reviews` | `/admin/structure/agro-review` |

**Frontend Pages:**

| Ruta | Template | Body classes |
|------|----------|-------------|
| `/marketplace` | `page--marketplace.html.twig` | `marketplace-page` |
| `/mi-finca` | `page--mi-finca.html.twig` | `producer-portal-page` |
| `/mis-pedidos` | `page--mis-pedidos.html.twig` | `customer-orders-page` |
| `/checkout` | `page--agro-checkout.html.twig` | `agro-checkout-page` |

### 6.2 `jaraba_agroconecta_traceability`

**Content Entities:** `agro_batch`, `agro_trace_event`, `agro_trace_certificate`, `agro_qr`

### 6.3 `jaraba_agroconecta_ai`

**Agents:** Producer Copilot (contexto productor) + Sales Agent (WhatsApp/Web)

---

## 7. Verificación

### Checklist por Fase
- [ ] SCSS: 0 `$ej-*` locales, 100% `var(--ej-*)`
- [ ] i18n: 0 textos sin `$this->t()` / `{% trans %}` / `Drupal.t()`
- [ ] Templates: 100% parciales `{% include %}`, 0 `page.content`
- [ ] Body classes: 100% via `hook_preprocess_html()`
- [ ] CRUD: 100% en slide-panel
- [ ] Entities: 4 YAML, Field UI, Views
- [ ] Iconos: 2 versiones por icono
- [ ] Seguridad: HMAC, rate limiting, prompt sanitization

---

> **Referencias:**
> - [00_DIRECTRICES_PROYECTO.md](../00_DIRECTRICES_PROYECTO.md) — Directrices maestras
> - [00_DOCUMENTO_MAESTRO_ARQUITECTURA.md](../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md) — Arquitectura §7.1
> - [arquitectura_theming_saas_master.md](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md) — SCSS Federated Design Tokens
> - Workflows: `/frontend-page-pattern`, `/slide-panel-modales`, `/drupal-custom-modules`, `/scss-estilos`, `/i18n-traducciones`
