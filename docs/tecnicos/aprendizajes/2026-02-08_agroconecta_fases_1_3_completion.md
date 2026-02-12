# 📝 AgroConecta Fases 1-3: Aprendizajes de Implementación

> **Fecha:** 2026-02-08
> **Vertical:** AgroConecta
> **Fases completadas:** 1 (Commerce Core), 2 (Orders + Checkout), 3 (Producer + Customer Portal)

---

## 1. Resumen de Implementación

### Fase 1: Commerce Core (Docs 47-48)
- **3 Content Entities** creadas: `ProductAgro` (15 campos), `ProducerProfile` (16 campos), `AgroCertification` (12 campos)
- **Módulo:** `jaraba_agroconecta_core` (40+ archivos)
- **Frontend:** Marketplace (`/marketplace`), Product Detail (`/producto/{id}`)
- **API:** 3 endpoints REST
- **Compliance:** 7/8 directivas SaaS (SCSS tokens, i18n, clean Twig, slide-panel, Content Entity, hook_preprocess_html)

### Fase 2: Orders + Checkout (Docs 49-50)
- **3 Content Entities** creadas: `OrderAgro` (27 campos), `SuborderAgro`, `OrderItemAgro`
- **Services:** `OrderService`, `CheckoutService`, `CartService`
- **Frontend:** Checkout multi-step, Cart management

### Fase 3: Portales (Docs 52-53)
- **Controllers:** `ProducerPortalController` (6 endpoints), `CustomerPortalController` (5 endpoints)
- **Services:** `ProducerDashboardService`
- **Templates:** 6 Twig templates
- **JS:** `producer-portal.js` (Chart.js), `customer-portal.js` (animaciones)
- **SCSS:** `_producer-portal.scss`, `_customer-portal.scss`
- **Config:** 11 routes, 11 theme hooks, 11 body classes, 2 libraries

---

## 2. Aprendizajes Clave

### 2.1 Entity Pattern Gold Standard
El patrón `OrderAgro` se convirtió en el estándar de referencia para Content Entities:
- **Annotation completa** con todos los handlers (list_builder, views_data, form, access, route_provider)
- **4 YAMLs obligatorios**: routing, links.menu, links.task, links.action
- **SettingsForm** para `field_ui_base_route` (habilita "Administrar campos" en `/admin/structure`)
- **Entity keys** correctos: id, uuid, label, owner
- **Links** correctos: canonical, add-form, edit-form, delete-form, collection

### 2.2 Suborder Pattern (Multi-Vendor)
El modelo de sub-pedidos por productor (`SuborderAgro`) permite fulfillment independiente por vendor:
- Un `OrderAgro` (pedido padre) se divide en N `SuborderAgro` (uno por productor)
- Cada suborder tiene su propia máquina de estados independiente
- El modelo simplifica la lógica de pagos split (Stripe Connect Destination Charges)

### 2.3 Portal Frontend sin Entidades Nuevas
Las Fases Producer/Customer Portal demostraron que no siempre se necesitan entidades nuevas:
- Los portales consumen entidades existentes (Orders, Products, SuborderAgro) vía services
- `ProducerDashboardService` agrega KPIs de entidades existentes
- Patrón: Controller + Service + Template + JS + SCSS = funcionalidad completa sin nueva tabla

### 2.4 Chart.js Integration
- Inyección de datos via `drupalSettings` (no Twig inline)
- Gráfico inicializado en `Drupal.behaviors` con `once()` para evitar duplicación
- Canvas responsive con `maintainAspectRatio: false`

### 2.5 SCSS Compliance
- Todos los parciales usan `@use 'variables' as *` + `var(--ej-color-agro, #556B2F)`
- Import en `main.scss` para cada parcial nuevo
- BEM naming consistente: `.producer-portal__*`, `.customer-portal__*`

---

## 3. Decisiones Arquitectónicas Tomadas

| Decisión | Elegido | Alternativa Descartada | Razón |
|----------|---------|------------------------|-------|
| Shipping pospuesto | Fase 5 (futura) | Fase 3 (original) | Requiere credenciales carriers externos |
| Portales adelantados | Fase 3 (actual) | Fase 4 (original) | Dependía solo de entidades ya creadas |
| Reviews + Notificaciones siguiente | Fase 4 (planificada) | Analytics o Search | Valor inmediato alto sin deps externas |
| No Drupal Commerce contrib | Custom entities | commerce_product/order | Control total multi-tenant, sin overhead |

---

## 4. Archivos Creados por Fase

### Fase 3 (Portales)

| Tipo | Archivo | Propósito |
|------|---------|-----------|
| Service | `ProducerDashboardService.php` | KPIs, alertas, charts |
| Controller | `ProducerPortalController.php` | 6 endpoints: dashboard, orders, order-detail, products, payouts, settings |
| Controller | `CustomerPortalController.php` | 5 endpoints: dashboard, orders, order-detail, addresses, favorites |
| Template | `agro-producer-dashboard.html.twig` | Dashboard con KPIs y chart |
| Template | `agro-producer-orders.html.twig` | Lista pedidos productor |
| Template | `agro-producer-order-detail.html.twig` | Detalle pedido productor |
| Template | `agro-customer-dashboard.html.twig` | Dashboard cliente |
| Template | `agro-customer-orders.html.twig` | Historial pedidos |
| Template | `agro-customer-order-detail.html.twig` | Detalle con timeline |
| JS | `producer-portal.js` | Chart.js + order actions |
| JS | `customer-portal.js` | Cancelación + animaciones |
| SCSS | `_producer-portal.scss` | Estilos portal productor |
| SCSS | `_customer-portal.scss` | Estilos portal cliente |

---

> **Próxima fase:** [20260208-Plan_Implementacion_AgroConecta_v2.md](../../implementacion/20260208-Plan_Implementacion_AgroConecta_v2.md) — Phase 4: Reviews + Notificaciones
