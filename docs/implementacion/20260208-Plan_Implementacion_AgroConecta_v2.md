# 📋 Plan de Implementación AgroConecta v2.0

> **Fecha:** 2026-02-08
> **Estado:** Fase 4 en planificación
> **Vertical:** AgroConecta (Marketplace Agroalimentario Multi-Vendor)
> **Versión anterior:** [v1.0](./20260208-Plan_Implementacion_AgroConecta_v1.md)

---

## 📊 Estado por Fases (Actualizado 2026-02-08)

| Fase | Descripción | Docs | Estado | Entidades |
|------|-------------|------|--------|-----------|
| **1** | Commerce Core: Catálogo, ProductAgro, ProducerProfile | 47-48 | ✅ **Completada** | ProductAgro, ProducerProfile, AgroCertification |
| **2** | Orders + Checkout: OrderAgro, SuborderAgro, Payments | 49-50 | ✅ **Completada** | OrderAgro, SuborderAgro, OrderItemAgro |
| **3** | Producer Portal + Customer Portal: Dashboards frontend | 52-53 | ✅ **Completada** | — (controllers + templates) |
| **4** | **Reviews + Notificaciones** | **54, 59** | 🔶 **Planificada** | ReviewAgro, NotificationTemplate/Log/Preference |
| 5 | Shipping & Logistics: MRW, SEUR, GLS | 51 | ⬜ Futura | agro_shipment, agro_shipping_zone |
| 6 | Search, Promotions, Analytics, Admin | 55-58 | ⬜ Futura | category, collection, promotion, coupon |
| 7 | Traceability + QR + Partner Hub | 80-82 | ⬜ Futura | agro_batch, agro_trace_event, agro_qr |
| 8 | AI Agents: Producer Copilot + Sales Agent | 67-68 | ⬜ Futura | — (jaraba_agroconecta_ai) |
| 9 | Mobile App PWA | 60-61 | ⬜ Futura | — |

### Variación respecto a v1.0

> [!NOTE]
> En v1.0 Shipping era Fase 3 y Portales era Fase 4. Se invirtió el orden porque los portales
> dependían de las entidades Order/Suborder ya creadas en Fase 2, mientras que Shipping requiere
> integraciones con carriers externos (MRW, SEUR, GLS) que necesitan credenciales de producción.
> Reviews + Notificaciones se adelanta porque tiene valor inmediato alto sin dependencias externas bloqueantes.

---

## 🌱 FASE 4: Reviews + Notificaciones — Especificación Completa

### 4.1 Justificación (Doc 54 + Doc 59)

| Criterio | Reviews (Doc 54) | Notificaciones (Doc 59) |
|----------|------------------|-------------------------|
| **Valor negocio** | Social proof (+15-20% conversión), SEO | Operaciones críticas marketplace |
| **Dependencias ext** | Ninguna bloqueante | Email nativo Drupal (Symfony Mailer) |
| **Entidades** | 1 (ReviewAgro) | 3 (Template, Log, Preference) |
| **Complejidad** | 🟡 Media | 🟡 Media |
| **Complementa Fase 3** | Widget en Product + rating en Producer Portal | Notificaciones en Customer Portal |

---

### 4.2 Entidad `ReviewAgro`

**Tipo:** ContentEntity  
**ID:** `review_agro`  
**Base table:** `review_agro`

#### Annotation & Handlers (Patrón idéntico a OrderAgro)

| Handler | Clase |
|---------|-------|
| `list_builder` | `ReviewAgroListBuilder` |
| `views_data` | `Drupal\views\EntityViewsData` |
| `form.default/add/edit` | `ReviewAgroForm` |
| `form.delete` | `ContentEntityDeleteForm` |
| `access` | `ReviewAgroAccessControlHandler` |
| `route_provider.html` | `AdminHtmlRouteProvider` |

#### Campos (16)

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `id` | integer (auto) | ✅ | PK |
| `uuid` | uuid | ✅ | UUID |
| `uid` | entity_reference (user) | ✅ | Autor de la review |
| `tenant_id` | entity_reference (taxonomy) | ✅ | Tenant multi-tenancy |
| `type` | string(16) | ✅ | `product`, `producer`, `order` |
| `target_entity_type` | string(32) | ✅ | Tipo de entidad target |
| `target_entity_id` | integer | ✅ | ID entidad target |
| `rating` | integer | ✅ | 1-5, con validación |
| `title` | string(100) | ❌ | Título opcional |
| `body` | string_long | ✅ | Texto de la review |
| `verified_purchase` | boolean | ✅ | Calculado al crear |
| `state` | string(16) | ✅ | `pending`, `approved`, `rejected`, `flagged` |
| `response` | string_long | ❌ | Respuesta del productor |
| `response_by` | entity_reference (user) | ❌ | Productor que respondió |
| `created` | created | ✅ | — |
| `changed` | changed | ✅ | — |

#### Navegación Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `routing.yml` | `entity.review_agro.collection` | `/admin/content/agro-reviews` |
| `links.task.yml` | Tab "Reseñas Agro" | `base_route: system.admin_content`, weight: 45 |
| `links.menu.yml` | Structure "Reseñas Agro" | `parent: system.admin_structure`, weight: 75 |
| `links.action.yml` | Botón "Añadir Reseña" | `appears_on: entity.review_agro.collection` |

#### Permisos

```yaml
manage agro reviews:
  title: 'Gestionar reseñas agro'
  restrict access: true

view agro reviews:
  title: 'Ver reseñas agro'

submit agro reviews:
  title: 'Enviar reseñas'

respond agro reviews:
  title: 'Responder a reseñas'
```

#### Service: `ReviewService`

```
submitReview():      Verifica compra (JOIN order_item_agro), crea con state=pending
getProductReviews(): Paginación, ordenación, filtro verified_only
getProducerRating(): Media ponderada (rating × log(num_reviews + 1))
getReviewSummary():  Distribución por estrellas, total, media, % verificadas
respondToReview():   Solo productor owner, una respuesta por review
moderateReview():    Aprobar/rechazar con log moderador
getReviewableProducts(): Productos con compra verificada sin review del usuario
```

#### API REST (7 endpoints)

| Method | Path | Permiso |
|--------|------|---------|
| `POST` | `/api/v1/agro/reviews` | `submit agro reviews` |
| `GET` | `/api/v1/agro/products/{id}/reviews` | `view agro reviews` |
| `GET` | `/api/v1/agro/producers/{id}/reviews` | `view agro reviews` |
| `GET` | `/api/v1/agro/reviews/{id}` | `view agro reviews` |
| `POST` | `/api/v1/agro/reviews/{id}/respond` | `respond agro reviews` |
| `PATCH` | `/api/v1/agro/reviews/{id}/moderate` | `manage agro reviews` |
| `GET` | `/api/v1/agro/me/reviewable-products` | `submit agro reviews` |

---

### 4.3 Entidad `NotificationTemplateAgro`

**Tipo:** ContentEntity | **ID:** `notification_template_agro`

#### Campos (13)

`id, uuid, type, channel (email|push|sms|in_app), name, subject, body, body_html, tokens (JSON), is_active, language, created, changed`

#### Handlers

ListBuilder + AccessControlHandler + Form + SettingsForm (Field UI)

---

### 4.4 Entidad `NotificationLogAgro`

**Tipo:** ContentEntity | **ID:** `notification_log_agro`

#### Campos (16)

`id, uuid, template_id, type, channel, recipient_type, recipient_id, recipient_email, subject, body_preview, context (JSON), status (pending|sent|delivered|failed|bounced), error_message, external_id, opened_at, clicked_at, created`

> Solo lectura — sin Form custom, solo ListBuilder para /admin/content.

---

### 4.5 Entidad `NotificationPreferenceAgro`

**Tipo:** ContentEntity | **ID:** `notification_preference_agro`

#### Campos (8)

`id, uuid, user_id, notification_type, channel_email (bool), channel_push (bool), channel_sms (bool), channel_in_app (bool), created, changed`

---

### 4.6 Service: `NotificationService`

```
send(type, recipient, context, channels?):
  → Verifica preferencias usuario
  → Renderiza template con tokens Twig
  → Encola via Queue API
  → Registra en NotificationLogAgro

Canal Email (Sprint 1): Symfony Mailer nativo
Canal In-App (Sprint 1): NotificationLogAgro con channel=in_app
Canal Push/SMS: Futura (Sprint 3/4 cuando se configuren credenciales)
```

#### API REST Notificaciones (7 endpoints)

| Method | Path | Permiso |
|--------|------|---------|
| `GET` | `/api/v1/agro/me/notifications` | authenticated |
| `GET` | `/api/v1/agro/me/notifications/unread-count` | authenticated |
| `POST` | `/api/v1/agro/me/notifications/{id}/read` | authenticated |
| `POST` | `/api/v1/agro/me/notifications/read-all` | authenticated |
| `DELETE` | `/api/v1/agro/me/notifications/{id}` | authenticated |
| `GET` | `/api/v1/agro/me/notification-preferences` | authenticated |
| `PATCH` | `/api/v1/agro/me/notification-preferences` | authenticated |

---

### 4.7 Archivos a Crear (27 total)

| Categoría | # | Archivos |
|-----------|---|----------|
| Entities | 4 | `ReviewAgro.php`, `NotificationTemplateAgro.php`, `NotificationLogAgro.php`, `NotificationPreferenceAgro.php` |
| ListBuilders | 4 | Uno por entidad |
| AccessHandlers | 4 | Uno por entidad |
| Forms | 5 | `ReviewAgroForm` + 4 SettingsForms |
| Services | 2 | `ReviewService`, `NotificationService` |
| Controllers | 2 | `ReviewApiController`, `NotificationApiController` |
| Templates | 6 | 4 reviews + 2 notifications |
| JS | 2 | `reviews.js`, `notifications.js` |
| SCSS | 2 | `_reviews.scss`, `_notifications.scss` |

### 4.8 Archivos a Modificar (9)

| Archivo | Cambios |
|---------|---------|
| `routing.yml` | +18 rutas (API + frontend) |
| `services.yml` | +2 services |
| `libraries.yml` | +2 libraries |
| `.module` | +6 hook_theme + body classes |
| `permissions.yml` | +7 permissions |
| `links.task.yml` | +4 tabs |
| `links.menu.yml` | +4 Structure entries |
| `links.action.yml` | +2 action buttons |
| `scss/main.scss` | +2 imports |

### 4.9 SCSS: Directrices

- Variables con `var(--ej-color-agro, #556B2F)` y tokens `var(--ej-*)`
- BEM: `.agro-reviews__*`, `.agro-notifications__*`
- Premium card glassmorphism para review cards
- Estrellas SVG interactivas hover/selección
- Badge "Compra verificada" ✓
- Notification dropdown con badge contador
- Responsive mobile-first

---

## 🔍 Verificación Fase 4

### Post-Creación
1. `lando drush cr`
2. `drush scr install_entities.php`
3. Verificar 4 collection routes en `/admin/content/agro-*`
4. Verificar 4 Structure entries
5. Compilar SCSS: `npm run build`
6. `lando drush cr` (post-SCSS)

### Funcional
- CRUD review via admin form
- Submit review via API
- Widget estrellas en template
- Centro notificaciones en `/mi-cuenta/notificaciones`
- Preferencias notificación

---

> **Orden de implementación:** Entities → Handlers → Forms → Services → Controllers → Twig → JS/SCSS → Config → Verification

> **Referencias:**
> - [Plan v1.0](./20260208-Plan_Implementacion_AgroConecta_v1.md) — Decisiones arquitectónicas, componentes reutilizables, directrices SaaS
> - Workflows: `/drupal-custom-modules`, `/scss-estilos`, `/frontend-page-pattern`, `/slide-panel-modales`
