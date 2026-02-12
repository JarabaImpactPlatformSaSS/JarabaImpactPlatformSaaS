# Plan de Implementación — Gaps del Marketing AI Stack (Especificaciones 20260119)

**Fecha de creación:** 2026-02-11 18:00
**Última actualización:** 2026-02-11 18:00
**Autor:** AI Assistant (Claude Opus 4.6)
**Versión:** 1.0.0

---

## 📑 Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Tabla de Correspondencia con Especificaciones 20260119](#2-tabla-de-correspondencia-con-especificaciones-20260119)
3. [Análisis de Estado Actual vs. Especificaciones](#3-análisis-de-estado-actual-vs-especificaciones)
4. [GAP 1 — Módulo jaraba_events: Eventos y Webinars](#4-gap-1--módulo-jaraba_events-eventos-y-webinars)
   - 4.1 [Contexto y Justificación](#41-contexto-y-justificación)
   - 4.2 [Entidades de Contenido](#42-entidades-de-contenido)
   - 4.3 [Servicios](#43-servicios)
   - 4.4 [Controladores](#44-controladores)
   - 4.5 [Rutas y Navegación](#45-rutas-y-navegación)
   - 4.6 [Plantillas Twig](#46-plantillas-twig)
   - 4.7 [SCSS y Estilos](#47-scss-y-estilos)
   - 4.8 [Permisos](#48-permisos)
   - 4.9 [Integración con Módulos Existentes](#49-integración-con-módulos-existentes)
5. [GAP 2 — Framework Unificado de A/B Testing](#5-gap-2--framework-unificado-de-ab-testing)
   - 5.1 [Contexto y Justificación](#51-contexto-y-justificación)
   - 5.2 [Arquitectura del Framework Unificado](#52-arquitectura-del-framework-unificado)
   - 5.3 [Entidades de Contenido](#53-entidades-de-contenido)
   - 5.4 [Servicios](#54-servicios)
   - 5.5 [Controladores](#55-controladores)
   - 5.6 [Rutas y Navegación](#56-rutas-y-navegación)
   - 5.7 [Plantillas Twig](#57-plantillas-twig)
   - 5.8 [SCSS y Estilos](#58-scss-y-estilos)
   - 5.9 [Integración con Módulos Existentes](#59-integración-con-módulos-existentes)
6. [GAP 3 — Referral Program: Frontend y Entidades](#6-gap-3--referral-program-frontend-y-entidades)
   - 6.1 [Contexto y Justificación](#61-contexto-y-justificación)
   - 6.2 [Entidades de Contenido](#62-entidades-de-contenido)
   - 6.3 [Controladores y Frontend](#63-controladores-y-frontend)
   - 6.4 [Plantillas Twig](#64-plantillas-twig)
   - 6.5 [SCSS y Estilos](#65-scss-y-estilos)
7. [GAP 4 — Paid Ads Dashboard Consolidado](#7-gap-4--paid-ads-dashboard-consolidado)
   - 7.1 [Contexto y Justificación](#71-contexto-y-justificación)
   - 7.2 [Entidades de Contenido](#72-entidades-de-contenido)
   - 7.3 [Servicios](#73-servicios)
   - 7.4 [Controladores y Frontend](#74-controladores-y-frontend)
   - 7.5 [Plantillas Twig](#75-plantillas-twig)
   - 7.6 [SCSS y Estilos](#76-scss-y-estilos)
8. [GAP 5 — Pricing Add-ons Modulares](#8-gap-5--pricing-add-ons-modulares)
   - 8.1 [Contexto y Justificación](#81-contexto-y-justificación)
   - 8.2 [Entidades de Contenido](#82-entidades-de-contenido)
   - 8.3 [Servicios](#83-servicios)
   - 8.4 [Controladores y Frontend](#84-controladores-y-frontend)
9. [Plan de Fases y Estimación](#9-plan-de-fases-y-estimación)
10. [Checklist de Cumplimiento de Directrices](#10-checklist-de-cumplimiento-de-directrices)
11. [Guía de Compilación SCSS](#11-guía-de-compilación-scss)
12. [Patrones Reutilizables y Parciales Existentes](#12-patrones-reutilizables-y-parciales-existentes)
13. [Configuración del Tema y Variables Inyectables](#13-configuración-del-tema-y-variables-inyectables)
14. [Seguridad y Multi-Tenancy](#14-seguridad-y-multi-tenancy)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este documento define el plan de implementación detallado para cerrar los **5 gaps remanentes** identificados en las especificaciones técnicas con prefijo `20260119` (serie 145-158 del Marketing AI Stack). Tras una auditoría exhaustiva del estado actual del SaaS, se determinó que **10 de las 16 especificaciones ya están implementadas o son redundantes**, y quedan 5 gaps concretos por resolver.

### Gaps identificados

| Prioridad | Gap | Módulo afectado | Esfuerzo estimado |
|-----------|-----|-----------------|-------------------|
| **P1** | Eventos y Webinars — Registro, asistencia, analítica | `jaraba_events` (nuevo) | 15-20h |
| **P1** | A/B Testing universal (unificar 3 implementaciones) | `jaraba_ab_testing` (nuevo) | 12-18h |
| **P2** | Referral Program — Entidades persistentes + UI frontend | `ecosistema_jaraba_core` (extensión) | 8-12h |
| **P2** | Paid Ads — Dashboard consolidado + audience sync | `jaraba_ads` (nuevo) | 15-20h |
| **P2** | Pricing — Add-ons modulares + bundles + admin UI | `ecosistema_jaraba_core` (extensión) | 8-10h |

**Esfuerzo total estimado: 58-80 horas de desarrollo.**

### Decisiones arquitectónicas clave

1. **ActiveCampaign (spec 145) DESCARTADA:** `jaraba_crm` + `jaraba_email` nativos cubren el 100% de la funcionalidad requerida. No se integrará con plataformas CRM externas.
2. **GroupEvent reutilizado:** La entidad `GroupEvent` de `jaraba_groups` se mantiene para eventos internos de grupos. El nuevo módulo `jaraba_events` gestionará eventos públicos, webinars y eventos de marketing con registro, tickets y analítica.
3. **A/B Testing unificado:** Las tres implementaciones existentes (`PageExperiment`, `ABTestingService` en skills, `AIPromptABTestingService`) se mantienen pero delegarán la lógica estadística al nuevo framework unificado `jaraba_ab_testing`.
4. **Referral Program:** El `ReferralProgramService` existente ya implementa la lógica completa (códigos, conversiones, recompensas, estadísticas, mensajes IA). Solo faltan entidades Drupal persistentes (actualmente usa tablas directas) y un frontend con página limpia.

---

## 2. Tabla de Correspondencia con Especificaciones 20260119

| # Spec | Nombre | Estado | Módulo(s) existente(s) | Gap residual | Acción |
|--------|--------|--------|------------------------|-------------|--------|
| **145** | ActiveCampaign Automation Flows | **REDUNDANTE** | `jaraba_crm`, `jaraba_email` | Ninguno | ❌ No implementar. CRM nativo es superior |
| **146** | SendGrid + ECA Architecture | **IMPLEMENTADO** | `jaraba_email` (SendGrid + ECA hooks) | Ninguno | ✅ Completo |
| **147** | Auditoría Comunicación Nativa | **EJECUTADA** | Decisión: nativizar CRM/Email/Social | Ninguno | ✅ Decisión tomada y ejecutada |
| **148** | Mapa Arquitectónico Completo | **VIGENTE** | `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Actualizar inventario (47 módulos vs 24) | 📝 Actualizar documento |
| **149** | Marketing AI Stack Aprobación | **EJECUTADA** | `jaraba_crm`, `jaraba_email`, `jaraba_social` | Extensiones pendientes | ✅ Módulos core aprobados y creados |
| **02v2** | Módulos Personalizados v2 | **SUPERADO** | 47 módulos custom (vs 24 documentados) | Actualizar inventario | 📝 Actualizar documento |
| **118v2** | Roadmap Implementación v2 | **DESFASADO** | Sprints 14-18 ya completados | Actualizar roadmap | 📝 Actualizar documento |
| **150** | jaraba_crm Pipeline | **IMPLEMENTADO** | `jaraba_crm` (Company, Contact, Opportunity, Activity + Kanban AJAX) | Ninguno | ✅ Completo |
| **151** | jaraba_email Marketing | **IMPLEMENTADO** | `jaraba_email` (EmailCampaign, EmailList, EmailSequence + AI subject + MJML) | Ninguno | ✅ Completo |
| **152** | jaraba_social Automation | **IMPLEMENTADO** | `jaraba_social` (SocialAccount, SocialPost + 5 plataformas + IA variantes) | Ninguno | ✅ Completo |
| **153** | Paid Ads Integration | **PARCIAL** | `jaraba_pixels` (Meta CAPI + Google MP + LinkedIn + TikTok server-side) | Dashboard consolidado, audience sync, ROAS | 🔧 **GAP 4** de este plan |
| **154** | Retargeting Pixel Manager | **IMPLEMENTADO** | `jaraba_pixels` v2 (4 clientes, server-side, consent, Redis queue) | Ninguno | ✅ Completo |
| **155** | Events & Webinars | **PARCIAL** | `jaraba_groups` → `GroupEvent` (entity con tipos, formatos, precios) | Registro, asistencia, analítica, landing auto | 🔧 **GAP 1** de este plan |
| **156** | A/B Testing Framework | **PARCIAL** | 3 implementaciones separadas (pages, skills, prompts IA) | Framework unificado con estadística rigurosa | 🔧 **GAP 2** de este plan |
| **157** | Referral Program Universal | **PARCIAL** | `ReferralProgramService` completo (códigos, recompensas, stats, IA share) | Entidades Drupal + frontend UI | 🔧 **GAP 3** de este plan |
| **158** | Pricing Matrix Vertical | **PARCIAL** | `PlanValidator`, `TenantSubscriptionService`, Stripe Connect | Add-ons modulares, bundles, admin UI | 🔧 **GAP 5** de este plan |

### Leyenda de estados

- ✅ **Completo:** Especificación 100% cubierta por implementación existente
- ❌ **Descartada:** Especificación que contradice decisiones arquitectónicas del SaaS
- 📝 **Documentación:** Solo requiere actualización de documentación, no código
- 🔧 **Gap:** Requiere desarrollo según este plan

---

## 3. Análisis de Estado Actual vs. Especificaciones

### 3.1 Funcionalidades que NO se implementarán

Las siguientes funcionalidades documentadas en las especificaciones 20260119 **no se implementarán** por las razones indicadas:

| Funcionalidad | Spec | Razón de exclusión |
|---------------|------|-------------------|
| Integración ActiveCampaign | 145 | CRM nativo `jaraba_crm` es superior: zero vendor lock-in, multi-tenant nativo, datos en nuestra BD |
| Integración HubSpot | 147 | Misma razón que ActiveCampaign |
| Buffer/Hootsuite para social | 147 | `jaraba_social` nativo con IA generativa por plataforma |
| Calendly integración bidireccional | 155 | Se usará sistema de disponibilidad propio (ya existe `AvailabilitySlot` en `jaraba_servicios_conecta`) |
| Zoom API para grabaciones | 155 | Se integrará opcionalmente vía Make.com, no como dependencia dura |

### 3.2 Infraestructura existente que se reutiliza

| Componente existente | Módulo | Se reutiliza en |
|---------------------|--------|-----------------|
| `GroupEvent` entity | `jaraba_groups` | GAP 1 — Base de datos de tipos de evento, reutilizable para eventos de grupo |
| `ReferralProgramService` | `ecosistema_jaraba_core` | GAP 3 — Toda la lógica de negocio de referidos |
| `PageExperiment` + `ExperimentVariant` | `jaraba_page_builder` | GAP 2 — A/B de páginas (se conectará al framework unificado) |
| `AIPromptABTestingService` | `ecosistema_jaraba_core` | GAP 2 — A/B de prompts IA (delegará estadística al framework) |
| `ABTestingService` | `jaraba_skills` | GAP 2 — A/B de skills (delegará estadística al framework) |
| `PixelEventDispatcher` | `jaraba_pixels` | GAP 4 — Envío de conversiones a plataformas de ads |
| `PlanValidator` + `TenantSubscriptionService` | `ecosistema_jaraba_core` | GAP 5 — Validación de acceso por plan |
| Slide-Panel global | `ecosistema_jaraba_theme` | Todos los GAPs — Modal CRUD sin abandonar la página |
| `_header.html.twig` (dispatcher) | `ecosistema_jaraba_theme` | Todos los GAPs — Header configurable desde UI Drupal |
| `_footer.html.twig` (dispatcher) | `ecosistema_jaraba_theme` | Todos los GAPs — Footer configurable desde UI Drupal |
| `hook_preprocess_html()` | `ecosistema_jaraba_theme` | Todos los GAPs — Body classes para rutas nuevas |

---

## 4. GAP 1 — Módulo jaraba_events: Eventos y Webinars

### 4.1 Contexto y Justificación

**Especificación de referencia:** `20260119-155_Marketing_Events_Webinars_v1_Claude.md`

**Problema:** El SaaS ya tiene la entidad `GroupEvent` en `jaraba_groups`, que cubre eventos internos de grupos de colaboración (mastermind, networking). Sin embargo, **no existe** infraestructura para:
- **Registro público de asistentes** a eventos de marketing (webinars, demos, talleres)
- **Tracking de asistencia real** (quién asistió vs quién se registró)
- **Generación automática de landing pages** por evento
- **Secuencias de email pre/post evento** (integración con `jaraba_email`)
- **Certificados de asistencia** (integración con `jaraba_credentials`)
- **Analítica de eventos** (tasa de asistencia, engagement, conversión post-evento)

**Decisión arquitectónica:** Crear un nuevo módulo `jaraba_events` independiente de `jaraba_groups`. La razón es que los eventos de marketing tienen un ciclo de vida, entidades y lógica de negocio fundamentalmente distintos a los eventos de grupo (que son privados y vinculados a membresías). El nuevo módulo podrá referenciar `GroupEvent` cuando un evento de marketing pertenezca también a un grupo.

### 4.2 Entidades de Contenido

#### 4.2.1 Entidad `marketing_event`

**Tipo:** Content Entity (para Field UI + Views)
**Tabla base:** `marketing_event`
**Ruta admin:** `/admin/content/marketing-events`
**Ruta Field UI:** `/admin/structure/marketing-events`

**Justificación Content Entity:** Los eventos contienen datos de negocio que varían por tenant (títulos, descripciones, ponentes, fechas, precios). Los administradores de tenant necesitan poder añadir campos personalizados sin código (ej: campo "Patrocinador", campo "Nivel de dificultad") y crear Views para listados filtrados.

```
┌─────────────────────────────────────────────────────────┐
│ marketing_event (ContentEntityBase)                     │
├─────────────────────────────────────────────────────────┤
│ Handlers:                                               │
│   list_builder: MarketingEventListBuilder               │
│   views_data: Drupal\views\EntityViewsData              │
│   form: MarketingEventForm (default/add/edit)           │
│   form.delete: ContentEntityDeleteForm                  │
│   access: MarketingEventAccessControlHandler            │
│   route_provider.html: AdminHtmlRouteProvider            │
│                                                         │
│ Interfaces: EntityChangedInterface, EntityOwnerInterface │
│ Traits: EntityChangedTrait, EntityOwnerTrait            │
├─────────────────────────────────────────────────────────┤
│ CAMPOS BASE:                                            │
│                                                         │
│ --- Identificación ---                                  │
│ id          : integer (auto)                            │
│ uuid        : uuid                                      │
│ title       : string(255) — Título del evento           │
│ slug        : string(255) — URL amigable (auto-generado)│
│ uid         : entity_reference(user) — Creador          │
│                                                         │
│ --- Clasificación ---                                   │
│ event_type  : list_string — Tipo de evento              │
│               Valores: webinar, taller, demo, mentoria, │
│               feria_virtual, networking, conferencia     │
│ format      : list_string — Formato                     │
│               Valores: online, presencial, hibrido       │
│ vertical    : entity_reference(vertical) — Vertical     │
│ tenant_id   : entity_reference(tenant) — Tenant aislam. │
│                                                         │
│ --- Programación ---                                    │
│ start_date  : datetime — Fecha/hora de inicio           │
│ end_date    : datetime — Fecha/hora de fin              │
│ timezone    : string(64) — Zona horaria (ej: Europe/    │
│               Madrid)                                   │
│                                                         │
│ --- Contenido ---                                       │
│ description : text_long — Descripción completa (HTML)   │
│ short_desc  : string(500) — Resumen para tarjetas       │
│ image       : image — Imagen principal (directorio:     │
│               events/[year])                            │
│ speakers    : string(1024) — Ponentes (JSON array)      │
│                                                         │
│ --- Logística ---                                       │
│ meeting_url : link — URL de videoconferencia            │
│ location    : string(500) — Dirección física            │
│ max_attendees: integer — Aforo máximo (0 = ilimitado)   │
│ current_attendees: integer — Contador actual (computed)  │
│                                                         │
│ --- Monetización ---                                    │
│ is_free     : boolean — ¿Evento gratuito?               │
│ price       : decimal(10,2) — Precio en EUR             │
│ early_bird_price: decimal(10,2) — Precio early bird     │
│ early_bird_deadline: datetime — Límite early bird       │
│                                                         │
│ --- Estado ---                                          │
│ status      : list_string — Estado del evento           │
│               Valores: draft, published, ongoing,       │
│               completed, cancelled                      │
│ featured    : boolean — Destacado en marketplace        │
│                                                         │
│ --- SEO/GEO ---                                         │
│ meta_description: string(320) — Meta description        │
│ schema_type : list_string — Tipo Schema.org             │
│               Valores: Event, BusinessEvent,            │
│               EducationEvent, SocialEvent               │
│                                                         │
│ --- Metadatos ---                                       │
│ created     : created — Timestamp de creación           │
│ changed     : changed — Timestamp de modificación       │
└─────────────────────────────────────────────────────────┘
```

**Lógica de negocio:**
- El `slug` se genera automáticamente a partir del `title` en `hook_entity_presave()`, normalizando a kebab-case y garantizando unicidad por tenant.
- El campo `current_attendees` se recalcula con cada insert/delete en `event_registration`. No se almacena como campo editable sino como valor computado mediante un método `recomputeAttendees()` en el servicio.
- El `tenant_id` es obligatorio y se asigna automáticamente desde `TenantContextService` en la creación.
- El `schema_type` determina qué JSON-LD Schema.org se inyecta en la landing del evento para GEO.

#### 4.2.2 Entidad `event_registration`

**Tipo:** Content Entity
**Tabla base:** `event_registration`
**Ruta admin:** `/admin/content/event-registrations`
**Ruta Field UI:** `/admin/structure/event-registrations`

```
┌──────────────────────────────────────────────────────────┐
│ event_registration (ContentEntityBase)                    │
├──────────────────────────────────────────────────────────┤
│ --- Identificación ---                                   │
│ id          : integer (auto)                             │
│ uuid        : uuid                                       │
│ event_id    : entity_reference(marketing_event) — Evento │
│ uid         : entity_reference(user) — Usuario registrado│
│ tenant_id   : entity_reference(tenant) — Tenant          │
│                                                          │
│ --- Datos del registrado ---                             │
│ attendee_name : string(255) — Nombre (para no-logueados) │
│ attendee_email: email — Email del registrado             │
│ attendee_phone: string(20) — Teléfono (opcional)         │
│                                                          │
│ --- Estado ---                                           │
│ registration_status: list_string                         │
│   Valores: pending, confirmed, waitlisted, cancelled,    │
│            attended, no_show                              │
│ confirmation_token: string(64) — Token de confirmación   │
│                     (double opt-in)                       │
│ ticket_code : string(32) — Código único de ticket        │
│                                                          │
│ --- Monetización ---                                     │
│ payment_status: list_string                              │
│   Valores: free, pending_payment, paid, refunded         │
│ amount_paid : decimal(10,2) — Importe pagado             │
│ stripe_payment_id: string(255) — ID pago Stripe          │
│                                                          │
│ --- Asistencia ---                                       │
│ checked_in  : boolean — ¿Ha hecho check-in?             │
│ checkin_time : datetime — Hora de check-in               │
│ attendance_duration: integer — Minutos de asistencia     │
│                                                          │
│ --- Feedback ---                                         │
│ rating      : integer — Valoración 1-5                   │
│ feedback    : text_long — Comentario post-evento         │
│                                                          │
│ --- Certificado ---                                      │
│ certificate_issued: boolean — ¿Certificado emitido?      │
│ certificate_url: link — URL del certificado              │
│                                                          │
│ --- Metadatos ---                                        │
│ source      : list_string — Fuente de registro           │
│   Valores: web, api, email_invite, referral, import      │
│ utm_source  : string(255) — UTM de atribución           │
│ created     : created                                    │
│ changed     : changed                                    │
└──────────────────────────────────────────────────────────┘
```

**Lógica de negocio:**
- En `hook_entity_insert()` de `event_registration`:
  1. Verifica que el evento no haya alcanzado `max_attendees`. Si lo supera, cambia `registration_status` a `waitlisted` automáticamente.
  2. Genera `confirmation_token` con `bin2hex(random_bytes(32))`.
  3. Genera `ticket_code` con formato `EVT-{event_id}-{random_4chars_upper}` (ej: `EVT-42-X7K2`).
  4. Si el evento es gratuito (`is_free = TRUE`), establece `payment_status = 'free'` y `registration_status = 'confirmed'`.
  5. Dispara secuencia de email de confirmación vía `jaraba_email`.
  6. Actualiza el contador `current_attendees` del `marketing_event` padre.
  7. Crea lead en `jaraba_crm` si el usuario no existe como contacto (con `source = 'event'`).
- En `hook_entity_delete()`:
  1. Decrementa `current_attendees`.
  2. Si hay usuarios en `waitlisted`, promueve al primero a `confirmed`.

### 4.3 Servicios

#### 4.3.1 `EventRegistrationService`

**ID de servicio:** `jaraba_events.registration`
**Clase:** `Drupal\jaraba_events\Service\EventRegistrationService`

**Propósito:** Orquesta el flujo completo de registro, confirmación, cancelación y check-in de asistentes.

**Dependencias inyectadas:**
- `@entity_type.manager` — CRUD de entidades
- `@current_user` — Usuario actual
- `@logger.channel.jaraba_events` — Logs
- `@ecosistema_jaraba_core.tenant_context` — Contexto de tenant
- `@jaraba_email.sequence` — Disparar secuencias de email (si existe)
- `@jaraba_events.analytics` — Registrar métricas

**Métodos públicos:**

```php
/**
 * Registra un asistente en un evento.
 *
 * FLUJO DE EJECUCIÓN:
 * 1. Valida que el evento existe, está publicado y acepta registros
 * 2. Verifica capacidad (max_attendees vs current_attendees)
 * 3. Comprueba duplicados (mismo email + mismo evento)
 * 4. Crea entidad event_registration con estado apropiado
 * 5. Genera token de confirmación y código de ticket
 * 6. Dispara email de confirmación o de lista de espera
 * 7. Actualiza contador de asistentes en el evento
 * 8. Registra lead en CRM si es nuevo contacto
 *
 * REGLAS DE NEGOCIO:
 * - Usuarios logueados: uid se rellena automáticamente
 * - Usuarios anónimos: requiere attendee_name + attendee_email
 * - Eventos de pago: registration_status queda en 'pending' hasta confirmar pago
 * - Eventos gratuitos: registration_status pasa a 'confirmed' inmediatamente
 * - Aforo lleno: registration_status = 'waitlisted' (notificación diferente)
 *
 * @param int $event_id
 *   ID del marketing_event.
 * @param array $attendee_data
 *   Datos del asistente:
 *   - 'name' (string): Nombre completo.
 *   - 'email' (string): Email de contacto.
 *   - 'phone' (string, opcional): Teléfono.
 *   - 'utm_source' (string, opcional): Fuente de atribución.
 *
 * @return \Drupal\jaraba_events\Entity\EventRegistration
 *   La entidad de registro creada.
 *
 * @throws \Drupal\jaraba_events\Exception\EventFullException
 *   Si el evento ha alcanzado el aforo y no acepta lista de espera.
 * @throws \Drupal\jaraba_events\Exception\DuplicateRegistrationException
 *   Si el email ya está registrado en el mismo evento.
 * @throws \Drupal\jaraba_events\Exception\EventNotOpenException
 *   Si el evento no está en estado 'published'.
 */
public function register(int $event_id, array $attendee_data): EventRegistration;

/**
 * Confirma el registro mediante token de double opt-in.
 */
public function confirmByToken(string $token): EventRegistration;

/**
 * Cancela un registro existente y gestiona la lista de espera.
 */
public function cancel(int $registration_id, string $reason = ''): void;

/**
 * Registra el check-in de un asistente (presencial o virtual).
 */
public function checkIn(int $registration_id): EventRegistration;

/**
 * Obtiene los registros de un evento con filtros.
 */
public function getRegistrations(int $event_id, array $filters = [], int $limit = 50, int $offset = 0): array;

/**
 * Genera estadísticas de un evento.
 *
 * @return array
 *   Estructura:
 *   - 'total_registrations' (int): Total de registros.
 *   - 'confirmed' (int): Confirmados.
 *   - 'waitlisted' (int): En lista de espera.
 *   - 'attended' (int): Asistieron.
 *   - 'no_show' (int): No asistieron.
 *   - 'attendance_rate' (float): Tasa de asistencia (%).
 *   - 'average_rating' (float): Valoración media.
 *   - 'revenue' (float): Ingresos totales del evento.
 */
public function getEventStats(int $event_id): array;
```

#### 4.3.2 `EventAnalyticsService`

**ID de servicio:** `jaraba_events.analytics`
**Clase:** `Drupal\jaraba_events\Service\EventAnalyticsService`

**Propósito:** Calcula métricas de rendimiento de eventos para el dashboard: tasa de asistencia, conversión post-evento, ROI de eventos, engagement por tipo.

**Métodos principales:**
- `getEventPerformance(int $event_id): array` — Métricas individuales
- `getTenantEventMetrics(int $tenant_id, string $period = '30d'): array` — Métricas agregadas por tenant
- `getConversionFunnel(int $event_id): array` — Funnel: registro → confirmación → asistencia → conversión

#### 4.3.3 `EventLandingService`

**ID de servicio:** `jaraba_events.landing`
**Clase:** `Drupal\jaraba_events\Service\EventLandingService`

**Propósito:** Genera los datos necesarios para la landing page automática de cada evento, incluyendo Schema.org JSON-LD, meta tags SEO, y countdown dinámico.

**Métodos principales:**
- `buildLandingData(MarketingEvent $event): array` — Datos completos para el template
- `generateSchemaOrg(MarketingEvent $event): array` — JSON-LD para GEO
- `getRelatedEvents(MarketingEvent $event, int $limit = 3): array` — Eventos relacionados por vertical/tipo

### 4.4 Controladores

#### 4.4.1 `EventFrontendController`

**Clase:** `Drupal\jaraba_events\Controller\EventFrontendController`

**Propósito:** Gestiona las rutas frontend públicas de eventos. Cada método retorna un render array que apunta a una plantilla Twig limpia (Zero Region).

```php
/**
 * Controlador de páginas frontend de eventos.
 *
 * ESTRUCTURA:
 * Gestiona 4 rutas principales del frontend de eventos:
 * - /eventos — Listado/marketplace de eventos
 * - /eventos/{slug} — Landing page individual del evento
 * - /eventos/{slug}/registro — Formulario de registro (modal preferido)
 * - /eventos/{slug}/confirmacion/{token} — Confirmación double opt-in
 *
 * LÓGICA:
 * Todas las rutas están filtradas por tenant_id vía TenantContextService.
 * Los datos se obtienen de los servicios inyectados, nunca acceso directo a BD.
 * Las respuestas son render arrays con #theme apuntando a templates Twig limpias.
 * Las operaciones CRUD (registro) se abren en slide-panel vía AJAX.
 *
 * SINTAXIS:
 * - Extiende ControllerBase para acceso a DI container.
 * - Usa create() para inyección de dependencias.
 * - Detecta AJAX con $request->isXmlHttpRequest() para respuestas parciales.
 */
class EventFrontendController extends ControllerBase {

  /**
   * Listado de eventos próximos (marketplace).
   *
   * RUTA: /eventos
   * TEMPLATE: events-marketplace.html.twig
   * BODY CLASS: page-events-marketplace (vía hook_preprocess_html)
   */
  public function marketplace(Request $request): array;

  /**
   * Landing page individual de un evento.
   *
   * RUTA: /eventos/{slug}
   * TEMPLATE: event-landing.html.twig
   * BODY CLASS: page-event-landing (vía hook_preprocess_html)
   *
   * Incluye Schema.org Event JSON-LD para GEO.
   * Si el evento está en el pasado, muestra resumen con grabación si existe.
   */
  public function eventLanding(string $slug): array;

  /**
   * Formulario de registro (respuesta parcial para slide-panel).
   *
   * RUTA: /eventos/{slug}/registro
   * Detecta AJAX: Si XHR, retorna solo el formulario (sin page wrapper).
   * Si acceso directo, redirige a la landing con el panel abierto.
   */
  public function registerForm(string $slug, Request $request): Response;

  /**
   * Confirmación de registro vía token (double opt-in).
   *
   * RUTA: /eventos/{slug}/confirmacion/{token}
   * Tras confirmar, redirige a la landing con mensaje de éxito.
   */
  public function confirmRegistration(string $slug, string $token): RedirectResponse;
}
```

#### 4.4.2 `EventApiController`

**Clase:** `Drupal\jaraba_events\Controller\EventApiController`

**Propósito:** API REST JSON para integración con frontend dinámico y Make.com.

**Rutas API:**
- `GET /api/v1/events` — Listar eventos por tenant
- `GET /api/v1/events/{id}` — Detalle de un evento
- `POST /api/v1/events/{id}/register` — Registrar asistente
- `PATCH /api/v1/events/{id}/registrations/{reg_id}/checkin` — Check-in
- `GET /api/v1/events/{id}/stats` — Estadísticas del evento

**Seguridad:** Todas las rutas API requieren autenticación (`_user_is_logged_in: 'TRUE'`). Los endpoints de escritura (POST, PATCH) verifican permisos específicos. El `tenant_id` se filtra automáticamente.

### 4.5 Rutas y Navegación

#### 4.5.1 Archivo `jaraba_events.routing.yml`

```yaml
# ============================================================
# RUTAS DE CONFIGURACIÓN (Field UI)
# ============================================================
jaraba_events.marketing_event.settings:
  path: '/admin/structure/marketing-events'
  defaults:
    _form: '\Drupal\jaraba_events\Form\MarketingEventSettingsForm'
    _title: 'Configuración Eventos'
  requirements:
    _permission: 'manage marketing events'

jaraba_events.event_registration.settings:
  path: '/admin/structure/event-registrations'
  defaults:
    _form: '\Drupal\jaraba_events\Form\EventRegistrationSettingsForm'
    _title: 'Configuración Registros de Eventos'
  requirements:
    _permission: 'manage marketing events'

# ============================================================
# RUTAS FRONTEND (Páginas limpias — Zero Region)
# ============================================================
jaraba_events.marketplace:
  path: '/eventos'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventFrontendController::marketplace'
    _title: 'Eventos'
  requirements:
    _permission: 'access content'

jaraba_events.event_landing:
  path: '/eventos/{slug}'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventFrontendController::eventLanding'
    _title: 'Evento'
  requirements:
    _permission: 'access content'
    slug: '[a-z0-9\-]+'

jaraba_events.register_form:
  path: '/eventos/{slug}/registro'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventFrontendController::registerForm'
    _title: 'Registro'
  requirements:
    _permission: 'access content'
    slug: '[a-z0-9\-]+'

jaraba_events.confirm_registration:
  path: '/eventos/{slug}/confirmacion/{token}'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventFrontendController::confirmRegistration'
    _title: 'Confirmación'
  requirements:
    _permission: 'access content'
    slug: '[a-z0-9\-]+'
    token: '[a-f0-9]{64}'

# ============================================================
# RUTAS API REST
# ============================================================
jaraba_events.api.events:
  path: '/api/v1/events'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventApiController::listEvents'
  methods: [GET]
  requirements:
    _user_is_logged_in: 'TRUE'

jaraba_events.api.events.detail:
  path: '/api/v1/events/{event_id}'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventApiController::getEvent'
  methods: [GET]
  requirements:
    _user_is_logged_in: 'TRUE'
    event_id: '\d+'

jaraba_events.api.events.register:
  path: '/api/v1/events/{event_id}/register'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventApiController::registerAttendee'
  methods: [POST]
  requirements:
    _permission: 'register for events'
    event_id: '\d+'

jaraba_events.api.events.checkin:
  path: '/api/v1/events/{event_id}/registrations/{registration_id}/checkin'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventApiController::checkIn'
  methods: [PATCH]
  requirements:
    _permission: 'manage event registrations'
    event_id: '\d+'
    registration_id: '\d+'

jaraba_events.api.events.stats:
  path: '/api/v1/events/{event_id}/stats'
  defaults:
    _controller: '\Drupal\jaraba_events\Controller\EventApiController::eventStats'
  methods: [GET]
  requirements:
    _permission: 'view event analytics'
    event_id: '\d+'
```

#### 4.5.2 Archivo `jaraba_events.links.menu.yml`

```yaml
jaraba_events.marketing_event.settings:
  title: 'Eventos Marketing'
  description: 'Configuración de la entidad Evento de Marketing.'
  route_name: jaraba_events.marketing_event.settings
  parent: system.admin_structure
  weight: 90

jaraba_events.event_registration.settings:
  title: 'Registros de Eventos'
  description: 'Configuración de la entidad Registro de Evento.'
  route_name: jaraba_events.event_registration.settings
  parent: system.admin_structure
  weight: 91
```

#### 4.5.3 Archivo `jaraba_events.links.task.yml`

```yaml
jaraba_events.marketing_event.collection:
  title: 'Eventos'
  route_name: entity.marketing_event.collection
  base_route: system.admin_content
  weight: 70

jaraba_events.event_registration.collection:
  title: 'Registros Eventos'
  route_name: entity.event_registration.collection
  base_route: system.admin_content
  weight: 71
```

#### 4.5.4 Archivo `jaraba_events.links.action.yml`

```yaml
jaraba_events.marketing_event.add:
  title: 'Añadir Evento'
  route_name: entity.marketing_event.add_form
  appears_on:
    - entity.marketing_event.collection

jaraba_events.event_registration.add:
  title: 'Añadir Registro'
  route_name: entity.event_registration.add_form
  appears_on:
    - entity.event_registration.collection
```

### 4.6 Plantillas Twig

⚠️ **DIRECTRIZ CRÍTICA:** Todas las plantillas frontend usan el patrón Zero Region — sin `{{ page.content }}`, sin bloques de Drupal. Header y footer se incluyen desde los parciales existentes del tema (`_header.html.twig` y `_footer.html.twig`) que ya son configurables desde la UI de Drupal (`/admin/appearance/settings/ecosistema_jaraba_theme`).

#### 4.6.1 Template de página `page--eventos.html.twig`

**Ubicación:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--eventos.html.twig`

**Propósito:** Página limpia para la ruta `/eventos` y todas las subrutas. Reutiliza los parciales de header y footer del tema. No usa regiones de Drupal.

```twig
{#
 # Template: page--eventos.html.twig
 # Rutas: /eventos, /eventos/{slug}, /eventos/{slug}/registro
 # Variables: Inyectadas por hook_preprocess_page() desde EventFrontendController
 #
 # DIRECTRIZ: Patrón Zero Region. No usar {{ page.content }} ni bloques.
 # Header y footer son parciales configurables desde la UI del tema.
 # Body class "page-events-marketplace" añadida vía hook_preprocess_html().
 #}

{# Bibliotecas del módulo #}
{{ attach_library('jaraba_events/events-frontend') }}
{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/scroll-animations') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium">

  {# ACCESIBILIDAD: Skip link WCAG 2.1 AA #}
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Saltar al contenido principal{% endtrans %}
  </a>

  {# HEADER: Parcial reutilizable — configuración desde UI del tema #}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name,
    'site_slogan': site_slogan,
    'logo': logo,
    'logged_in': logged_in,
    'theme_settings': theme_settings|default({})
  } only %}

  {# CONTENIDO PRINCIPAL: Renderizado desde el controlador #}
  <main id="main-content" class="main-content main-content--full" role="main">
    {{ page.content }}
  </main>

  {# FOOTER: Parcial reutilizable — configuración desde UI del tema #}
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name,
    'logo': logo,
    'footer_copyright': footer_copyright,
    'theme_settings': theme_settings|default({})
  } only %}

  {# COPILOTO FAB: Detección contextual automática #}
  {% if copilot_context %}
    {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
      'context': copilot_context
    } only %}
  {% endif %}

</div>
```

**Nota sobre `{{ page.content }}`:** En este caso específico, se usa `{{ page.content }}` porque el controlador retorna el render array con `#theme => 'events_marketplace'` que renderiza la plantilla del módulo dentro de este contenedor limpio. El patrón Zero Region se mantiene porque el template de página NO usa regiones laterales, sidebar, breadcrumb ni bloques. Solo el contenido del controlador.

#### 4.6.2 Template del módulo `events-marketplace.html.twig`

**Ubicación:** `web/modules/custom/jaraba_events/templates/events-marketplace.html.twig`

```twig
{#
 # Template: events-marketplace.html.twig
 # Ruta: /eventos
 # Variables:
 #   - events (array): Lista de eventos próximos
 #   - categories (array): Tipos de evento para filtro
 #   - current_filters (array): Filtros activos
 #   - total_count (int): Total de eventos
 #
 # DIRECTRIZ: Textos siempre traducibles con {% trans %}.
 # Layout: full-width, mobile-first, grid responsive.
 # Acciones CRUD: Vía slide-panel (data-slide-panel).
 #}

<div class="events-marketplace">

  {# HERO COMPACTO #}
  <section class="events-marketplace__hero">
    <div class="events-marketplace__hero-content container">
      <h1 class="events-marketplace__title">
        {% trans %}Eventos y Webinars{% endtrans %}
      </h1>
      <p class="events-marketplace__subtitle">
        {% trans %}Formación, networking y oportunidades para tu negocio{% endtrans %}
      </p>
    </div>
  </section>

  {# FILTROS #}
  <section class="events-marketplace__filters container">
    <form class="events-filters" method="get" action="">
      <div class="events-filters__group">
        <label for="event-type" class="events-filters__label">
          {% trans %}Tipo{% endtrans %}
        </label>
        <select id="event-type" name="type" class="events-filters__select">
          <option value="">{% trans %}Todos{% endtrans %}</option>
          {% for key, label in categories %}
            <option value="{{ key }}"
              {{ current_filters.type == key ? 'selected' : '' }}>
              {{ label }}
            </option>
          {% endfor %}
        </select>
      </div>

      <div class="events-filters__group">
        <label for="event-format" class="events-filters__label">
          {% trans %}Formato{% endtrans %}
        </label>
        <select id="event-format" name="format" class="events-filters__select">
          <option value="">{% trans %}Todos{% endtrans %}</option>
          <option value="online" {{ current_filters.format == 'online' ? 'selected' : '' }}>
            {% trans %}Online{% endtrans %}
          </option>
          <option value="presencial" {{ current_filters.format == 'presencial' ? 'selected' : '' }}>
            {% trans %}Presencial{% endtrans %}
          </option>
          <option value="hibrido" {{ current_filters.format == 'hibrido' ? 'selected' : '' }}>
            {% trans %}Híbrido{% endtrans %}
          </option>
        </select>
      </div>

      <button type="submit" class="btn btn--primary btn--sm">
        {% trans %}Filtrar{% endtrans %}
      </button>
    </form>
  </section>

  {# GRID DE EVENTOS #}
  <section class="events-marketplace__grid container">
    {% if events|length > 0 %}
      <div class="events-grid">
        {% for event in events %}
          {% include '@jaraba_events/partials/event-card.html.twig' with {
            'event': event
          } only %}
        {% endfor %}
      </div>
    {% else %}
      <div class="events-marketplace__empty">
        <p>{% trans %}No hay eventos programados en este momento.{% endtrans %}</p>
        <p>{% trans %}Vuelve pronto para descubrir nuevas oportunidades.{% endtrans %}</p>
      </div>
    {% endif %}
  </section>

</div>
```

#### 4.6.3 Parcial `partials/event-card.html.twig`

**Ubicación:** `web/modules/custom/jaraba_events/templates/partials/event-card.html.twig`

**Propósito:** Tarjeta reutilizable para representar un evento. Se usa en el marketplace, en la landing de verticales, y en widgets de "Eventos relacionados". Sigue el patrón de glassmorphism premium.

```twig
{#
 # Parcial: event-card.html.twig
 # Uso: {% include '@jaraba_events/partials/event-card.html.twig' with { 'event': event } only %}
 # Variables:
 #   - event.title (string): Título del evento
 #   - event.slug (string): URL slug
 #   - event.event_type (string): Tipo (webinar, taller, demo...)
 #   - event.format (string): Formato (online, presencial, hibrido)
 #   - event.start_date (string): Fecha ISO
 #   - event.short_desc (string): Descripción corta
 #   - event.image_url (string|null): URL de imagen
 #   - event.is_free (bool): ¿Gratuito?
 #   - event.price (float): Precio
 #   - event.spots_remaining (int|null): Plazas restantes
 #}

<article class="event-card glass-card">

  {# IMAGEN #}
  {% if event.image_url %}
    <div class="event-card__image">
      <img src="{{ event.image_url }}" alt="{{ event.title }}" loading="lazy" />
    </div>
  {% endif %}

  {# BADGES #}
  <div class="event-card__badges">
    <span class="event-card__badge event-card__badge--type">
      {{ event.event_type|capitalize|t }}
    </span>
    <span class="event-card__badge event-card__badge--format">
      {{ event.format|capitalize|t }}
    </span>
    {% if event.is_free %}
      <span class="event-card__badge event-card__badge--free">
        {% trans %}Gratuito{% endtrans %}
      </span>
    {% endif %}
  </div>

  {# CONTENIDO #}
  <div class="event-card__body">
    <time class="event-card__date" datetime="{{ event.start_date }}">
      {{ event.start_date|date('d M Y · H:i') }}
    </time>

    <h3 class="event-card__title">
      <a href="/eventos/{{ event.slug }}">{{ event.title }}</a>
    </h3>

    <p class="event-card__description">{{ event.short_desc }}</p>
  </div>

  {# FOOTER #}
  <footer class="event-card__footer">
    {% if not event.is_free %}
      <span class="event-card__price">{{ event.price|number_format(2, ',', '.') }} €</span>
    {% endif %}

    {% if event.spots_remaining is not null and event.spots_remaining <= 10 %}
      <span class="event-card__spots event-card__spots--limited">
        {% trans %}{{ event.spots_remaining }} plazas restantes{% endtrans %}
      </span>
    {% endif %}

    <a href="/eventos/{{ event.slug }}"
       class="btn btn--primary btn--sm event-card__cta">
      {% trans %}Ver evento{% endtrans %}
    </a>
  </footer>

</article>
```

### 4.7 SCSS y Estilos

#### 4.7.1 Estructura de archivos SCSS

```
web/modules/custom/jaraba_events/
├── scss/
│   ├── _variables.scss          # NO definir variables propias.
│   │                            # Solo @use para acceder a mixins del core.
│   ├── _events-marketplace.scss # Estilos del marketplace
│   ├── _event-card.scss         # Estilos de la tarjeta de evento
│   ├── _event-landing.scss      # Estilos de la landing individual
│   ├── _event-registration.scss # Estilos del formulario de registro
│   ├── _event-dashboard.scss    # Estilos del dashboard de analítica
│   └── main.scss                # Punto de entrada principal
├── css/
│   └── jaraba-events.css        # Compilado (NUNCA editar directamente)
├── package.json                 # Configuración npm para compilación
└── jaraba_events.libraries.yml  # Registro de bibliotecas Drupal
```

#### 4.7.2 `package.json`

```json
{
  "name": "jaraba-events",
  "version": "1.0.0",
  "description": "SCSS para el módulo jaraba_events — Eventos y Webinars",
  "scripts": {
    "build": "sass scss/main.scss:css/jaraba-events.css --style=compressed",
    "watch": "sass --watch scss/main.scss:css/jaraba-events.css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.71.0"
  }
}
```

#### 4.7.3 `main.scss`

```scss
/**
 * @file
 * Estilos principales del módulo jaraba_events.
 *
 * DIRECTRIZ: Usar Design Tokens con CSS Custom Properties (var(--ej-*)).
 * NUNCA definir variables SCSS propias para colores, tipografía o espaciado.
 * Todos los valores se consumen de las variables inyectables del core.
 *
 * COMPILACIÓN:
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/jaraba_events && npx sass scss/main.scss css/jaraba-events.css --style=compressed"
 *
 * ALTERNATIVA (Windows con NVM):
 * cd web/modules/custom/jaraba_events && npx sass scss/main.scss:css/jaraba-events.css --style=compressed
 */

// Importar SOLO mixins del core (NO variables como valores propios)
// Los valores se consumen vía var(--ej-*, $fallback) inline
@use '../../../../modules/custom/ecosistema_jaraba_core/scss/mixins' as *;

// Parciales del módulo
@use 'events-marketplace';
@use 'event-card';
@use 'event-landing';
@use 'event-registration';
@use 'event-dashboard';
```

#### 4.7.4 `_event-card.scss` (ejemplo de parcial)

```scss
/**
 * @file
 * Estilos de la tarjeta de evento (event-card).
 *
 * DIRECTRIZ: Mobile-first. Glassmorphism premium.
 * Colores: var(--ej-*). Sombras: var(--ej-shadow-*).
 * Transiciones: var(--ej-transition-*).
 */

.event-card {
  // Layout base — Mobile first
  display: flex;
  flex-direction: column;
  border-radius: var(--ej-border-radius-lg, 14px);
  overflow: hidden;
  background: var(--ej-bg-card, #ffffff);
  border: 1px solid var(--ej-border-color-light, #eeeeee);
  box-shadow: var(--ej-shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.06));
  transition: var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1));

  // Hover premium
  &:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: var(--ej-shadow-lg, 0 8px 32px rgba(0, 0, 0, 0.14));
    transition: var(--ej-transition-spring, all 300ms cubic-bezier(0.34, 1.56, 0.64, 1));
  }

  // --- Imagen ---
  &__image {
    aspect-ratio: 16 / 9;
    overflow: hidden;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: var(--ej-transition-slow, all 400ms cubic-bezier(0.4, 0, 0.2, 1));
    }
  }

  &:hover &__image img {
    transform: scale(1.05);
  }

  // --- Badges ---
  &__badges {
    display: flex;
    gap: var(--ej-spacing-xs, 0.25rem);
    padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
    flex-wrap: wrap;
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.6rem;
    border-radius: var(--ej-border-radius-full, 9999px);
    font-size: var(--ej-font-size-xs, 0.75rem);
    font-weight: var(--ej-font-weight-medium, 500);
    text-transform: uppercase;
    letter-spacing: 0.05em;

    &--type {
      background: color-mix(in srgb, var(--ej-color-primary, #2E7D32) 15%, white);
      color: var(--ej-color-primary, #2E7D32);
    }

    &--format {
      background: color-mix(in srgb, var(--ej-color-secondary, #1B5E20) 15%, white);
      color: var(--ej-color-secondary, #1B5E20);
    }

    &--free {
      background: color-mix(in srgb, var(--ej-color-success, #43A047) 15%, white);
      color: var(--ej-color-success, #43A047);
    }
  }

  // --- Cuerpo ---
  &__body {
    padding: 0 var(--ej-spacing-md, 1rem) var(--ej-spacing-md, 1rem);
    flex: 1;
  }

  &__date {
    display: block;
    font-size: var(--ej-font-size-sm, 0.875rem);
    color: var(--ej-text-secondary, #757575);
    margin-bottom: var(--ej-spacing-xs, 0.25rem);
  }

  &__title {
    font-size: var(--ej-font-size-lg, 1.125rem);
    font-weight: var(--ej-font-weight-semibold, 600);
    margin: 0 0 var(--ej-spacing-sm, 0.5rem);
    line-height: 1.3;

    a {
      color: var(--ej-text-primary, #212121);
      text-decoration: none;

      &:hover {
        color: var(--ej-color-primary, #2E7D32);
      }
    }
  }

  &__description {
    font-size: var(--ej-font-size-sm, 0.875rem);
    color: var(--ej-text-secondary, #757575);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  // --- Footer ---
  &__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem) var(--ej-spacing-md, 1rem);
    border-top: 1px solid var(--ej-border-color-light, #eeeeee);
    gap: var(--ej-spacing-sm, 0.5rem);
    flex-wrap: wrap;
  }

  &__price {
    font-size: var(--ej-font-size-lg, 1.125rem);
    font-weight: var(--ej-font-weight-bold, 700);
    color: var(--ej-color-primary, #2E7D32);
  }

  &__spots {
    font-size: var(--ej-font-size-xs, 0.75rem);

    &--limited {
      color: var(--ej-color-warning, #FFA000);
      font-weight: var(--ej-font-weight-medium, 500);
    }
  }
}

// === GRID RESPONSIVE (Mobile-first) ===
.events-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--ej-spacing-lg, 1.5rem);

  // Tablets
  @media (min-width: 640px) {
    grid-template-columns: repeat(2, 1fr);
  }

  // Desktop
  @media (min-width: 1024px) {
    grid-template-columns: repeat(3, 1fr);
  }
}
```

### 4.8 Permisos

**Archivo:** `jaraba_events.permissions.yml`

```yaml
# --- Gestión de Eventos ---
manage marketing events:
  title: 'Gestionar eventos de marketing'
  description: 'Acceso completo a CRUD de todos los eventos de marketing.'
  restrict access: true

view marketing events:
  title: 'Ver eventos de marketing'
  description: 'Ver eventos publicados en el marketplace público.'

create marketing events:
  title: 'Crear eventos de marketing'
  description: 'Crear nuevos eventos de marketing para el tenant.'

edit own marketing events:
  title: 'Editar eventos propios'
  description: 'Editar eventos creados por el usuario actual.'

delete own marketing events:
  title: 'Eliminar eventos propios'
  description: 'Eliminar eventos creados por el usuario actual.'

# --- Gestión de Registros ---
manage event registrations:
  title: 'Gestionar registros de eventos'
  description: 'Acceso completo a todos los registros de asistentes.'
  restrict access: true

register for events:
  title: 'Registrarse en eventos'
  description: 'Permite a usuarios registrarse como asistente a eventos.'

view own event registrations:
  title: 'Ver registros propios'
  description: 'Ver los registros de eventos del usuario actual.'

# --- Analítica ---
view event analytics:
  title: 'Ver analítica de eventos'
  description: 'Acceder al dashboard de analítica de eventos.'
```

### 4.9 Integración con Módulos Existentes

| Módulo | Integración | Mecanismo |
|--------|-------------|-----------|
| `jaraba_email` | Secuencias pre/post evento (confirmación, recordatorio 24h, follow-up, certificado) | ECA hooks en `hook_entity_insert()` de `event_registration` |
| `jaraba_crm` | Crear lead automático al registrar asistente nuevo | Servicio `jaraba_crm.contact` inyectado en `EventRegistrationService` |
| `jaraba_credentials` | Emitir certificado de asistencia tras check-in | Hook en `hook_entity_update()` cuando `checked_in = TRUE` |
| `jaraba_pixels` | Enviar evento de conversión `EventRegistration` a Meta/Google | `PixelEventDispatcher::dispatch('CompleteRegistration', ...)` en registro |
| `jaraba_analytics` | Registrar evento para dashboard nativo | `jaraba_analytics.tracker` inyectado en el servicio |
| `jaraba_geo` | Schema.org `Event` JSON-LD en landing pages | `EventLandingService::generateSchemaOrg()` |
| `ecosistema_jaraba_core` | Tenant isolation, design tokens, slide-panel | `TenantContextService`, CSS variables, `data-slide-panel` |
| `jaraba_groups` | Vincular evento marketing a un grupo (opcional) | `entity_reference` a `collaboration_group` (campo adicional vía Field UI) |

---

## 5. GAP 2 — Framework Unificado de A/B Testing

### 5.1 Contexto y Justificación

**Especificación de referencia:** `20260119-156_Marketing_AB_Testing_Framework_v1_Claude.md`

**Problema:** Existen **tres implementaciones independientes** de A/B testing en el SaaS:

1. **`PageExperiment` + `ExperimentVariant`** en `jaraba_page_builder` — A/B de páginas/bloques del constructor. Tiene entidades completas, controlador de dashboard y API. Carece de cálculo de significancia estadística real (solo ratio de conversión simple).

2. **`ABTestingService`** en `jaraba_skills` — A/B de habilidades IA. Servicio stateless con selección aleatoria de variantes. Métricas por invocación pero sin persistencia en entidades ni cálculo estadístico.

3. **`AIPromptABTestingService`** en `ecosistema_jaraba_core` — A/B de prompts de IA. Tablas directas (`ai_ab_experiments`, `ai_ab_variants`, `ai_ab_conversion_logs`). Implementa Z-test y cálculo de significancia, pero no es reutilizable por otros módulos.

**Decisión arquitectónica:** Crear un módulo `jaraba_ab_testing` que:
- Provea un **servicio centralizado de estadística** (`StatisticalEngineService`) que todos los módulos puedan consumir.
- **No reemplace** las entidades existentes (PageExperiment, etc.), sino que las complemente con lógica estadística rigurosa.
- Provea un **dashboard unificado** que muestre todos los experimentos del SaaS (páginas, emails, prompts, skills).
- Implemente Z-test, Bonferroni correction, tamaño mínimo de muestra, y auto-stop al alcanzar significancia.

### 5.2 Arquitectura del Framework Unificado

```
┌──────────────────────────────────────────────────────────┐
│ jaraba_ab_testing (Framework Unificado)                   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  StatisticalEngineService                                │
│  ├── calculateSignificance(controlRate, variantRate,     │
│  │                          controlSize, variantSize)    │
│  ├── calculateMinSampleSize(baseRate, mde, alpha, power) │
│  ├── bonferroniCorrection(alpha, numVariants)            │
│  ├── shouldAutoStop(experiment)                          │
│  └── determineWinner(variants[])                         │
│                                                          │
│  ExperimentAggregatorService                             │
│  ├── getAllExperiments(tenant_id, filters)               │
│  ├── getExperimentsByType(type)                          │
│  └── getUnifiedDashboardData(tenant_id)                  │
│                                                          │
│  VariantAssignmentService                                │
│  ├── assignVariant(experiment_id, user_id)               │
│  ├── getAssignment(experiment_id, user_id)               │
│  └── trackExposure(experiment_id, variant_id, user_id)   │
│                                                          │
│  Adaptadores (conectan módulos existentes):              │
│  ├── PageBuilderExperimentAdapter                        │
│  ├── SkillsExperimentAdapter                             │
│  └── PromptExperimentAdapter                             │
│                                                          │
└──────────────────────────────────────────────────────────┘
        ↑              ↑                ↑
   jaraba_page_   jaraba_skills   ecosistema_
   builder                        jaraba_core
```

### 5.3 Entidades de Contenido

#### 5.3.1 Entidad `ab_experiment` (Registro Unificado)

**Tipo:** Content Entity
**Propósito:** Registro maestro que agrupa TODOS los experimentos del SaaS, independientemente de su módulo de origen. Cada módulo crea su propia entidad de detalle (PageExperiment, etc.) y registra aquí una referencia para el dashboard unificado.

```
┌──────────────────────────────────────────────────────────┐
│ ab_experiment (ContentEntityBase)                         │
├──────────────────────────────────────────────────────────┤
│ id            : integer (auto)                           │
│ uuid          : uuid                                     │
│ name          : string(255) — Nombre del experimento     │
│ experiment_type: list_string — Tipo de origen            │
│                 Valores: page, email, prompt, skill,     │
│                 landing, social                           │
│ source_module : string(64) — Módulo de origen            │
│ source_entity_id: string(128) — ID en el módulo origen   │
│ tenant_id     : entity_reference(tenant)                 │
│ uid           : entity_reference(user) — Creador         │
│                                                          │
│ --- Configuración ---                                    │
│ status        : list_string                              │
│                 Valores: draft, running, paused, completed│
│ goal_type     : list_string                              │
│                 Valores: conversion, click, form_submit,  │
│                 scroll_depth, engagement, revenue         │
│ goal_target   : string(255) — Selector/ID del objetivo   │
│ traffic_pct   : integer — % de tráfico (0-100)           │
│ confidence_threshold: decimal(5,4) — Umbral significancia│
│                       (default: 0.9500)                  │
│ min_sample_size: integer — Tamaño mínimo de muestra     │
│                                                          │
│ --- Resultados ---                                       │
│ winner_variant: string(64) — ID de variante ganadora     │
│ p_value       : decimal(10,8) — p-value calculado        │
│ total_exposures: integer — Total de exposiciones         │
│ total_conversions: integer — Total de conversiones       │
│                                                          │
│ --- Temporal ---                                         │
│ start_date    : datetime — Inicio del experimento        │
│ end_date      : datetime — Fin (null si activo)          │
│ created       : created                                  │
│ changed       : changed                                  │
└──────────────────────────────────────────────────────────┘
```

### 5.4 Servicios

#### 5.4.1 `StatisticalEngineService`

**ID de servicio:** `jaraba_ab_testing.statistical_engine`

**Propósito:** Motor estadístico reutilizable que calcula significancia, tamaño de muestra, correcciones y determinación de ganador. Es el corazón del framework y puede ser consumido por cualquier módulo.

```php
/**
 * Motor estadístico para A/B testing.
 *
 * ESTRUCTURA:
 * Servicio stateless que provee cálculos estadísticos puros.
 * No depende de entidades ni base de datos — solo recibe
 * datos numéricos y retorna resultados.
 *
 * LÓGICA:
 * Implementa Z-test de dos proporciones con corrección de
 * Bonferroni para tests A/B/n con múltiples variantes.
 * Determina automáticamente si un experimento ha alcanzado
 * significancia estadística y si debe detenerse.
 *
 * FÓRMULAS:
 * - Z-score: (p1 - p2) / sqrt(p_pool * (1 - p_pool) * (1/n1 + 1/n2))
 * - p_pool: (x1 + x2) / (n1 + n2)
 * - Bonferroni: alpha_adjusted = alpha / num_comparisons
 * - Tamaño muestra: basado en Lehr's formula
 */
class StatisticalEngineService {

  /**
   * Calcula la significancia estadística entre control y variante.
   *
   * @param float $control_rate Tasa de conversión del control (0-1).
   * @param float $variant_rate Tasa de conversión de la variante (0-1).
   * @param int $control_size Número de exposiciones del control.
   * @param int $variant_size Número de exposiciones de la variante.
   * @param float $alpha Nivel de significancia (default: 0.05).
   *
   * @return array
   *   - 'z_score' (float): Z-score calculado.
   *   - 'p_value' (float): P-value bilateral.
   *   - 'is_significant' (bool): ¿Es estadísticamente significativo?
   *   - 'confidence' (float): Nivel de confianza (1 - p_value).
   *   - 'lift' (float): Mejora relativa (%).
   *   - 'ci_lower' (float): Límite inferior del intervalo de confianza.
   *   - 'ci_upper' (float): Límite superior del intervalo de confianza.
   */
  public function calculateSignificance(
    float $control_rate,
    float $variant_rate,
    int $control_size,
    int $variant_size,
    float $alpha = 0.05
  ): array;

  /**
   * Calcula el tamaño mínimo de muestra necesario.
   *
   * @param float $baseline_rate Tasa de conversión base esperada (0-1).
   * @param float $minimum_detectable_effect MDE deseado (ej: 0.05 para 5%).
   * @param float $alpha Nivel de significancia (default: 0.05).
   * @param float $power Potencia estadística (default: 0.80).
   *
   * @return int Tamaño de muestra por variante.
   */
  public function calculateMinSampleSize(
    float $baseline_rate,
    float $minimum_detectable_effect,
    float $alpha = 0.05,
    float $power = 0.80
  ): int;

  /**
   * Aplica corrección de Bonferroni para tests A/B/n.
   */
  public function bonferroniCorrection(float $alpha, int $num_variants): float;

  /**
   * Determina si un experimento debe detenerse automáticamente.
   */
  public function shouldAutoStop(array $experiment_data): array;

  /**
   * Determina la variante ganadora entre múltiples variantes.
   */
  public function determineWinner(array $variants): array;
}
```

### 5.5 Controladores

#### `ABTestingDashboardController`

**Ruta frontend:** `/ab-testing`
**Body class:** `page-ab-testing-dashboard` (vía `hook_preprocess_html()`)

Dashboard unificado que muestra todos los experimentos activos del tenant, agrupados por tipo (páginas, emails, prompts, skills). Cada experimento muestra: nombre, variantes, exposiciones, conversiones, p-value, estado, y botón de acción (pausar/detener/declarar ganador).

### 5.6 Rutas y Navegación

Se aplican los mismos 4 archivos YAML que en GAP 1:
- `jaraba_ab_testing.routing.yml` — Configuración + frontend + API
- `jaraba_ab_testing.links.menu.yml` — En `/admin/structure`
- `jaraba_ab_testing.links.task.yml` — En `/admin/content`
- `jaraba_ab_testing.links.action.yml` — Botón "Añadir Experimento"

### 5.7 Plantillas Twig

- `page--ab-testing.html.twig` — Template de página limpia (Zero Region) en el tema
- `ab-testing-dashboard.html.twig` — Dashboard con tabla de experimentos
- `partials/experiment-card.html.twig` — Tarjeta de experimento reutilizable
- `partials/experiment-stats.html.twig` — Widget de estadísticas con barras de progreso

### 5.8 SCSS y Estilos

Misma estructura que GAP 1:
- `scss/main.scss` → importa parciales
- `scss/_ab-dashboard.scss` → Dashboard de experimentos
- `scss/_experiment-card.scss` → Tarjeta glassmorphism
- `package.json` → Compilación con Dart Sass
- Todos los colores vía `var(--ej-*, $fallback)`

### 5.9 Integración con Módulos Existentes

| Módulo existente | Cambio requerido | Descripción |
|------------------|-----------------|-------------|
| `jaraba_page_builder` | Inyectar `StatisticalEngineService` en `ExperimentService` | Reemplazar cálculo de conversión simple por Z-test con significancia |
| `jaraba_skills` | Registrar experimentos en `ab_experiment` | Cada experimento de skills crea un registro unificado |
| `ecosistema_jaraba_core` | `AIPromptABTestingService` delega a `StatisticalEngineService` | Eliminar código duplicado de Z-test y usar el servicio centralizado |

---

## 6. GAP 3 — Referral Program: Frontend y Entidades

### 6.1 Contexto y Justificación

**Especificación de referencia:** `20260119-157_Marketing_Referral_Program_Universal_v1_Claude.md`

**Estado actual:** El `ReferralProgramService` en `ecosistema_jaraba_core` ya implementa **toda la lógica de negocio**:
- Generación de códigos únicos por tenant
- Validación de códigos y caducidad (30 días)
- Tracking de referidos con IDs de tracking
- Conversión de referidos (cuando pagan)
- Sistema de recompensas flexible (crédito €20 referidor, 20% descuento referido)
- Estadísticas completas (total, tasa conversión, importe ganado)
- Generación de mensajes para compartir con IA (genérico, WhatsApp, email, Twitter)

**Lo que falta:**
1. **Entidades Content Entity** (actualmente usa tablas directas `referral_codes`, `referrals`, `referral_rewards`)
2. **Página frontend** `/referidos` con dashboard del programa
3. **Página de compartir** `/invite/{code}` — Landing para el referido
4. **Integración con Stripe** para payouts de recompensas en efectivo

**Decisión:** Migrar las tablas directas a Content Entities para obtener Field UI + Views, y crear el frontend con página limpia.

### 6.2 Entidades de Contenido

#### 6.2.1 Entidad `referral_code`

**Tipo:** Content Entity
**Migración:** Desde tabla `referral_codes`

Campos: `id`, `uuid`, `code` (string unique), `tenant_id` (entity_reference), `uid` (owner), `status` (active/expired/disabled), `uses_count` (integer), `max_uses` (integer, 0=ilimitado), `expires_at` (datetime), `created`, `changed`.

#### 6.2.2 Entidad `referral`

**Tipo:** Content Entity
**Migración:** Desde tabla `referrals`

Campos: `id`, `uuid`, `referral_code_id` (entity_reference), `referrer_uid` (entity_reference user), `referee_uid` (entity_reference user), `tenant_id`, `status` (pending/converted/expired), `converted_at` (datetime), `conversion_value` (decimal), `tracking_id` (string unique), `utm_source`, `created`, `changed`.

#### 6.2.3 Entidad `referral_reward`

**Tipo:** Content Entity
**Migración:** Desde tabla `referral_rewards`

Campos: `id`, `uuid`, `referral_id` (entity_reference), `recipient_uid` (entity_reference), `tenant_id`, `reward_type` (credit/discount/free_month), `reward_value` (decimal), `currency` (string), `status` (pending/applied/cancelled), `applied_at` (datetime), `stripe_transfer_id` (string, para payouts), `created`, `changed`.

### 6.3 Controladores y Frontend

#### `ReferralFrontendController`

**Rutas:**
- `GET /referidos` — Dashboard del programa de referidos (mis códigos, estadísticas, recompensas)
- `GET /invite/{code}` — Landing page pública para el referido (beneficios, registro)
- `POST /api/v1/referrals/track` — Registrar click en enlace de referido

**Template de página:** `page--referidos.html.twig` (Zero Region, mismo patrón que GAP 1)

### 6.4 Plantillas Twig

- `referral-dashboard.html.twig` — Dashboard con código, botones de compartir, estadísticas
- `referral-invite-landing.html.twig` — Landing para el referido (hero + beneficios + CTA)
- `partials/referral-share-buttons.html.twig` — Botones de compartir (WhatsApp, Email, Twitter, copiar enlace)
- `partials/referral-stats-card.html.twig` — Tarjeta con métricas (total referidos, convertidos, ganado)

### 6.5 SCSS y Estilos

Parciales SCSS dentro de `ecosistema_jaraba_core/scss/`:
- `_referral-dashboard.scss` — Dashboard de referidos
- `_referral-invite.scss` — Landing de invitación

Se añaden al `main.scss` existente del core. Todos los colores vía `var(--ej-*)`.

---

## 7. GAP 4 — Paid Ads Dashboard Consolidado

### 7.1 Contexto y Justificación

**Especificación de referencia:** `20260119-153_Marketing_Paid_Ads_Integration_v1_Claude.md`

**Estado actual:** `jaraba_pixels` gestiona el envío de conversiones server-side a 4 plataformas (Meta CAPI, Google MP, LinkedIn, TikTok). Sin embargo, **no existe**:
- Dashboard consolidado de campañas publicitarias
- Sincronización de métricas diarias de campañas
- Sincronización de audiencias desde `jaraba_crm`
- Cálculo nativo de ROAS/CAC integrado con `jaraba_foc`

**Decisión:** Crear un nuevo módulo `jaraba_ads` que consume las APIs de Meta Ads y Google Ads (lectura de métricas) y sincroniza audiencias desde `jaraba_crm`. El módulo `jaraba_pixels` se mantiene para la parte de envío de conversiones.

### 7.2 Entidades de Contenido

#### 7.2.1 `ads_account`

Campos: `id`, `uuid`, `tenant_id`, `platform` (meta/google/linkedin/tiktok), `account_id` (string), `account_name`, `access_token_encrypted` (string, AES-256), `refresh_token_encrypted`, `status` (active/paused/revoked), `last_sync` (datetime), `created`, `changed`.

#### 7.2.2 `ads_campaign_sync`

Campos: `id`, `uuid`, `tenant_id`, `ads_account_id` (entity_reference), `campaign_external_id` (string), `campaign_name`, `status` (active/paused/completed), `daily_budget` (decimal), `lifetime_budget` (decimal), `objective` (string), `last_sync` (datetime), `created`, `changed`.

#### 7.2.3 `ads_metrics_daily`

Campos: `id`, `uuid`, `tenant_id`, `campaign_id` (entity_reference), `date` (datetime), `impressions` (integer), `clicks` (integer), `conversions` (integer), `spend` (decimal), `revenue` (decimal), `ctr` (decimal, computed), `cpc` (decimal, computed), `roas` (decimal, computed), `created`.

#### 7.2.4 `ads_audience_sync`

Campos: `id`, `uuid`, `tenant_id`, `ads_account_id` (entity_reference), `crm_segment_id` (string), `audience_external_id` (string), `audience_name`, `audience_size` (integer), `sync_status` (pending/syncing/synced/error), `last_sync` (datetime), `created`, `changed`.

### 7.3 Servicios

- `jaraba_ads.sync` — `AdsSyncService` — Sincronización de métricas diarias vía cron
- `jaraba_ads.audience` — `AudienceSyncService` — Sincronización de audiencias CRM → plataformas
- `jaraba_ads.analytics` — `AdsAnalyticsService` — Cálculo de ROAS, CAC, métricas agregadas
- `jaraba_ads.meta_client` — `MetaAdsClient` — Cliente para Meta Marketing API v18.0
- `jaraba_ads.google_client` — `GoogleAdsClient` — Cliente para Google Ads API v16

### 7.4 Controladores y Frontend

- `AdsDashboardController` — Ruta `/ads-dashboard` — Dashboard consolidado (Zero Region)
- `AdsApiController` — Rutas `/api/v1/ads/*` — API REST

### 7.5 Plantillas Twig

- `page--ads-dashboard.html.twig` — Template de página limpia en el tema
- `ads-dashboard.html.twig` — Dashboard con gráficos (Chart.js), tabla de campañas, métricas KPI
- `partials/ads-campaign-row.html.twig` — Fila de tabla de campaña
- `partials/ads-kpi-card.html.twig` — Tarjeta KPI (impresiones, clics, ROAS, gasto)

### 7.6 SCSS y Estilos

Nuevo módulo con su propio `package.json`:
- `scss/main.scss` → `css/jaraba-ads.css`
- `scss/_ads-dashboard.scss`, `scss/_ads-kpi-card.scss`
- Todos los colores vía `var(--ej-*)`

---

## 8. GAP 5 — Pricing Add-ons Modulares

### 8.1 Contexto y Justificación

**Especificación de referencia:** `20260119-158_Platform_Vertical_Pricing_Matrix_v1_Claude.md`

**Estado actual:** `PlanValidator` y `TenantSubscriptionService` gestionan la validación de acceso por plan base (Básico/Profesional/Enterprise). Sin embargo, **no existe** un sistema de add-ons modulares que permita a un tenant contratar funcionalidades adicionales sin cambiar de plan.

**Decisión:** Extender `ecosistema_jaraba_core` con dos nuevas entidades y un servicio de gestión de add-ons.

### 8.2 Entidades de Contenido

#### 8.2.1 `addon_definition` (Config Entity)

**Propósito:** Define los add-ons disponibles en la plataforma, su precio, y compatibilidad con verticales/planes.

Campos (configuración, no Field UI): `id`, `label`, `description`, `monthly_price` (float), `annual_price` (float), `features` (sequence de strings), `compatible_verticals` (sequence), `compatible_plans` (sequence), `stripe_price_id` (string), `status` (active/deprecated).

#### 8.2.2 `tenant_addon` (Content Entity)

**Propósito:** Registra qué add-ons tiene contratados cada tenant, con fechas de suscripción y estado.

Campos: `id`, `uuid`, `tenant_id` (entity_reference), `addon_id` (string, referencia a Config Entity), `stripe_subscription_item_id` (string), `status` (active/cancelled/pending), `billing_cycle` (monthly/annual), `start_date` (datetime), `end_date` (datetime), `auto_renew` (boolean), `created`, `changed`.

### 8.3 Servicios

#### `AddonManagementService`

**ID:** `ecosistema_jaraba_core.addon_management`

**Métodos:**
- `getAvailableAddons(int $tenant_id): array` — Lista add-ons compatibles con el plan/vertical del tenant
- `subscribeAddon(int $tenant_id, string $addon_id, string $cycle): TenantAddon` — Contratar add-on
- `cancelAddon(int $tenant_id, string $addon_id): void` — Cancelar add-on
- `hasAddon(int $tenant_id, string $addon_id): bool` — Verificar si el tenant tiene un add-on
- `getBundles(): array` — Obtener bundles preconfigurados con descuento
- `calculateProration(int $tenant_id, string $addon_id): array` — Calcular prorrateo mid-cycle

### 8.4 Controladores y Frontend

- Extensión de `TenantDashboardController` con pestaña "Add-ons" en `/tenant/dashboard`
- Modal slide-panel para contratar/cancelar add-ons (sin abandonar la página)
- Widget en `/admin/structure/addon-definitions` para gestionar definiciones (admin)

---

## 9. Plan de Fases y Estimación

### Fase 1 — Fundamentos (Semana 1-2)

| Tarea | GAP | Estimación | Prioridad |
|-------|-----|-----------|-----------|
| Crear módulo `jaraba_events` con entidades + 4 YAML files | GAP 1 | 4h | P1 |
| Implementar `EventRegistrationService` | GAP 1 | 4h | P1 |
| Crear módulo `jaraba_ab_testing` con `StatisticalEngineService` | GAP 2 | 4h | P1 |
| Crear entidades `ab_experiment` + YAML files | GAP 2 | 3h | P1 |
| **Subtotal Fase 1** | | **15h** | |

### Fase 2 — Frontend y Templates (Semana 2-3)

| Tarea | GAP | Estimación | Prioridad |
|-------|-----|-----------|-----------|
| Templates Twig de eventos (marketplace, landing, card) | GAP 1 | 4h | P1 |
| SCSS de eventos (compilación Dart Sass) | GAP 1 | 2h | P1 |
| `hook_preprocess_html()` para body classes de nuevas rutas | GAP 1,2,3 | 1h | P1 |
| Dashboard unificado A/B Testing (template + SCSS) | GAP 2 | 3h | P1 |
| Migrar tablas referral → Content Entities | GAP 3 | 3h | P2 |
| Frontend `/referidos` dashboard + share buttons | GAP 3 | 3h | P2 |
| **Subtotal Fase 2** | | **16h** | |

### Fase 3 — Integraciones y Ads (Semana 3-4)

| Tarea | GAP | Estimación | Prioridad |
|-------|-----|-----------|-----------|
| Integrar `jaraba_events` con `jaraba_email` (secuencias) | GAP 1 | 2h | P1 |
| Integrar `jaraba_events` con `jaraba_crm` (leads) | GAP 1 | 1h | P1 |
| Adapters A/B Testing para page_builder, skills, prompts | GAP 2 | 4h | P2 |
| Crear módulo `jaraba_ads` con entidades | GAP 4 | 4h | P2 |
| Implementar `MetaAdsClient` + `GoogleAdsClient` | GAP 4 | 6h | P2 |
| Dashboard de Ads (template + SCSS) | GAP 4 | 4h | P2 |
| **Subtotal Fase 3** | | **21h** | |

### Fase 4 — Pricing y Cierre (Semana 4-5)

| Tarea | GAP | Estimación | Prioridad |
|-------|-----|-----------|-----------|
| Entidades de add-ons (Config + Content) | GAP 5 | 3h | P2 |
| `AddonManagementService` + Stripe integration | GAP 5 | 4h | P2 |
| UI de add-ons en tenant dashboard | GAP 5 | 3h | P2 |
| Schema.org JSON-LD para eventos (GEO) | GAP 1 | 2h | P2 |
| Tests Cypress E2E para eventos y A/B Testing | GAP 1,2 | 3h | P2 |
| Actualizar documentación (Mapa Arquitectónico, Roadmap) | DOC | 3h | P3 |
| **Subtotal Fase 4** | | **18h** | |

### Resumen de estimación

| Fase | Horas | Acumulado |
|------|-------|-----------|
| Fase 1 — Fundamentos | 15h | 15h |
| Fase 2 — Frontend y Templates | 16h | 31h |
| Fase 3 — Integraciones y Ads | 21h | 52h |
| Fase 4 — Pricing y Cierre | 18h | **70h** |

**Total estimado: 70 horas** (rango: 58-80h con contingencia del 15%).

---

## 10. Checklist de Cumplimiento de Directrices

Este checklist debe verificarse para **cada módulo y cada archivo** antes de considerar completada la implementación.

### 10.1 SCSS y Theming

- [ ] ❌ NUNCA editar archivos `.css` directamente — solo `.scss`
- [ ] ✅ Cada módulo con SCSS tiene su `package.json` con script `build`
- [ ] ✅ Compilación con Dart Sass moderno (`sass ^1.71.0`)
- [ ] ✅ Todos los colores usan `var(--ej-*, $fallback)` — NUNCA hex hardcodeado
- [ ] ✅ NUNCA definir `$ej-*` variables SCSS propias en módulos satélite
- [ ] ✅ Usar `color-mix(in srgb, ...)` en vez de `darken()`/`lighten()` (deprecated)
- [ ] ✅ Importar mixins del core con `@use` (Dart Sass module system)
- [ ] ✅ Cada parcial SCSS con header `@file` documentando compilación
- [ ] ✅ Mobile-first: estilos base para móvil, `@media (min-width: ...)` para desktop
- [ ] ✅ Variables inyectables configuradas desde UI de Drupal (no requieren código para cambiar colores)
- [ ] ✅ Ejecutar `drush cr` después de compilar SCSS

### 10.2 Templates Twig

- [ ] ✅ Páginas frontend usan patrón Zero Region (sin `{{ page.sidebar_* }}`)
- [ ] ✅ Header y footer incluidos desde parciales del tema (`{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}`)
- [ ] ✅ Header/footer son configurables desde UI del tema (textos, links, layout, redes sociales)
- [ ] ✅ Body classes añadidas vía `hook_preprocess_html()` — NUNCA `attributes.addClass()` en template
- [ ] ✅ Skip link de accesibilidad: `<a href="#main-content" class="visually-hidden focusable skip-link">`
- [ ] ✅ Parciales reutilizables con prefijo `_` en directorio `partials/`
- [ ] ✅ Incluir parciales con `{% include '...' with { vars } only %}` (keyword `only` obligatorio)
- [ ] ✅ Layout full-width pensado para móvil
- [ ] ✅ Acciones CRUD se abren en slide-panel (`data-slide-panel`, `data-slide-panel-url`)
- [ ] ✅ Verificar si ya existe un parcial antes de crear uno nuevo
- [ ] ✅ No usar `{{ page.content }}` como contenido principal en landings (usar variables del controlador)

### 10.3 i18n — Textos Traducibles

- [ ] ✅ **TODOS** los textos visibles usan `{% trans %}texto{% endtrans %}` en Twig
- [ ] ✅ **TODOS** los textos en controladores usan `$this->t('texto')`
- [ ] ✅ **TODOS** los textos en JavaScript usan `Drupal.t('texto')`
- [ ] ✅ Labels de campos de entidad usan `t('Label')`
- [ ] ✅ Descripciones de permisos usan `t('Descripción')`
- [ ] ✅ Textos base en español (idioma del proyecto)
- [ ] ✅ Unidades traducidas completas (ej: `meses` no `mo`, `inquilino` no `tenant`)

### 10.4 Entidades de Contenido

- [ ] ✅ Content Entity (no Config Entity) para datos de negocio que necesitan Field UI + Views
- [ ] ✅ Handler `views_data`: `Drupal\views\EntityViewsData` (OBLIGATORIO)
- [ ] ✅ Handler `list_builder`: ListBuilder personalizado con filtros
- [ ] ✅ Handler `form`: default, add, edit, delete
- [ ] ✅ Handler `access`: AccessControlHandler personalizado
- [ ] ✅ Handler `route_provider.html`: `AdminHtmlRouteProvider`
- [ ] ✅ `field_ui_base_route` configurado para acceder a Field UI
- [ ] ✅ Campos con `setDisplayConfigurable('form', TRUE)` y `setDisplayConfigurable('view', TRUE)`
- [ ] ✅ Campo `tenant_id` (entity_reference a tenant) para aislamiento multi-tenant
- [ ] ✅ Implements `EntityChangedInterface, EntityOwnerInterface`
- [ ] ✅ Traits: `EntityChangedTrait, EntityOwnerTrait`
- [ ] ✅ Links: canonical, add-form, edit-form, delete-form, collection
- [ ] ✅ Ejecutar `drush devel-entity-updates -y` después de crear entidades

### 10.5 Navegación de Drupal (4 YAML Files)

- [ ] ✅ `routing.yml` con rutas para settings (Field UI), frontend, y API
- [ ] ✅ `links.menu.yml` con parent `system.admin_structure` para Config Entities
- [ ] ✅ `links.task.yml` con base_route `system.admin_content` para Content Entities
- [ ] ✅ `links.action.yml` con `appears_on` apuntando a la collection
- [ ] ✅ Parámetros de ruta con regex: `slug: '[a-z0-9\-]+'`, `id: '\d+'`
- [ ] ✅ Rutas API con `_user_is_logged_in: 'TRUE'` (NUNCA `_access: 'TRUE'` para datos de tenant)

### 10.6 Seguridad y Multi-Tenancy

- [ ] ✅ `tenant_id` filtrado en TODAS las queries
- [ ] ✅ Tenant NO tiene acceso al tema de administración de Drupal (Gin)
- [ ] ✅ API keys en variables de entorno (NUNCA en config exportable)
- [ ] ✅ Webhooks con verificación HMAC
- [ ] ✅ Endpoints LLM/embedding con rate limiting
- [ ] ✅ Prompts sanitizados contra whitelist
- [ ] ✅ Errores internos NO expuestos al usuario (log detallado + mensaje genérico)

### 10.7 Comentarios y Documentación

- [ ] ✅ Comentarios en español
- [ ] ✅ Tres dimensiones: Estructura, Lógica, Sintaxis
- [ ] ✅ Clases con docblock completo (responsabilidad, relaciones, estado)
- [ ] ✅ Métodos públicos con @param, @return, @throws
- [ ] ✅ Bloques complejos con explicación paso a paso

### 10.8 Frontend Premium

- [ ] ✅ Glassmorphism en tarjetas: `backdrop-filter: blur(12px)`, borde semitransparente
- [ ] ✅ Hover premium: `translateY(-6px) scale(1.02)` con easing bounce
- [ ] ✅ Iconos SVG del sistema de iconos del core (outline + duotone)
- [ ] ✅ Sombras del sistema de tokens (`--ej-shadow-sm/md/lg/xl`)
- [ ] ✅ Transiciones del sistema de tokens (`--ej-transition/fast/spring/slow`)
- [ ] ✅ Border-radius del sistema de tokens (`--ej-border-radius-sm/md/lg/xl`)

---

## 11. Guía de Compilación SCSS

### Comandos de compilación por módulo

Todos los comandos deben ejecutarse **dentro del contenedor Docker**:

```bash
# jaraba_events
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_events && npx sass scss/main.scss css/jaraba-events.css --style=compressed"

# jaraba_ab_testing
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_ab_testing && npx sass scss/main.scss css/jaraba-ab-testing.css --style=compressed"

# jaraba_ads
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_ads && npx sass scss/main.scss css/jaraba-ads.css --style=compressed"

# ecosistema_jaraba_core (después de añadir _referral-dashboard.scss)
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"

# Limpiar caché después de compilar
docker exec jarabasaas_appserver_1 bash -c "cd /app && ./vendor/bin/drush cr"
```

### Verificación post-compilación

1. Ejecutar `drush cr` dentro del contenedor
2. Abrir `https://jaraba-saas.lndo.site/` en navegador
3. Hard refresh (Ctrl+F5)
4. Verificar en DevTools que los estilos se aplican correctamente
5. Verificar que no hay errores en la consola JS
6. Verificar responsive (móvil → tablet → desktop)

---

## 12. Patrones Reutilizables y Parciales Existentes

Antes de crear cualquier parcial nuevo, **verificar si ya existe** uno reutilizable:

### Parciales del tema existentes

| Parcial | Ubicación | Propósito | Reutilizar en |
|---------|-----------|-----------|---------------|
| `_header.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Header con dispatcher de layouts (classic, minimal, transparent, mega...) | Todas las páginas limpias |
| `_footer.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Footer con dispatcher (standard, mega, split, minimal) + redes sociales | Todas las páginas limpias |
| `_copilot-fab.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | FAB del copiloto contextual | Incluir automáticamente si `copilot_context` existe |
| `_hero.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Hero section de homepage | Adaptar para hero de marketplace de eventos |
| `_features.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Grid de features con iconos | Reutilizar para beneficios de eventos |
| `_stats.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Tarjetas de estadísticas | Reutilizar para KPIs de dashboard |
| `_article-card.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Tarjeta de artículo con imagen | Base para `event-card.html.twig` |

### Componentes SDC existentes

| Componente | Ubicación | Variantes | Reutilizar en |
|-----------|-----------|-----------|---------------|
| `card` | `ecosistema_jaraba_theme/components/card/` | 8 variantes (compound) | Tarjetas de eventos, experimentos, ads |
| `hero` | `ecosistema_jaraba_theme/components/hero/` | 5 variantes (compound) | Hero de marketplace de eventos |

### JavaScript existente

| Script | Ubicación | Propósito | Reutilizar en |
|--------|-----------|-----------|---------------|
| `slide-panel.js` | `ecosistema_jaraba_theme/js/` | Panel deslizante global para CRUD | Todas las acciones crear/editar/ver |
| `scroll-animations.js` | `ecosistema_jaraba_theme/js/` | Animaciones al hacer scroll | Páginas de marketplace |
| `mobile-menu.js` | `ecosistema_jaraba_theme/js/` | Menú hamburguesa responsive | Todas las páginas limpias |

---

## 13. Configuración del Tema y Variables Inyectables

### Variables configurables desde la UI de Drupal

Las siguientes variables se configuran en `/admin/appearance/settings/ecosistema_jaraba_theme` y se inyectan automáticamente en `:root` vía `hook_preprocess_html()`:

| Variable CSS | Campo en UI | Valor por defecto | Afecta a |
|-------------|-------------|-------------------|----------|
| `--ej-color-primary` | Color Primario | `#2E7D32` | Botones, enlaces, badges, acentos |
| `--ej-color-secondary` | Color Secundario | `#1B5E20` | Elementos secundarios, gradientes |
| `--ej-color-accent` | Color Acento | `#4CAF50` | Highlights, hover states |
| `--ej-font-family` | Familia tipográfica | `'Inter', sans-serif` | Todo el texto |
| `--ej-font-family-heading` | Fuente de títulos | `'Outfit', sans-serif` | H1-H6 |
| `--ej-bg-page` | Fondo de página | `linear-gradient(...)` | Body background |
| `--ej-bg-card` | Fondo de tarjetas | `#ffffff` | Todas las tarjetas glass |
| `--ej-border-radius-md` | Radio de bordes | `10px` | Componentes UI |
| `--ej-shadow-md` | Sombra media | `0 4px 16px rgba(...)` | Tarjetas, modales |

**Esto significa que:** Los nuevos módulos (eventos, ads, A/B testing, referidos) **heredan automáticamente** los colores, tipografía, sombras y efectos del tema sin necesidad de código adicional, porque consumen `var(--ej-*)`. Si un tenant cambia su color primario en la UI, **todos los módulos se actualizan inmediatamente**.

### Configuración de Header y Footer

| Ajuste | Clave en config | Valor ejemplo | Se configura en |
|--------|----------------|---------------|-----------------|
| Layout del header | `header_layout` | `classic` / `minimal` / `transparent` / `mega` | UI del tema |
| Items de navegación | `navigation_items` | `Eventos\|/eventos\nReferidos\|/referidos` | UI del tema (textarea) |
| Layout del footer | `footer_layout` | `standard` / `mega` / `split` / `minimal` | UI del tema |
| Copyright | `footer_copyright` | `© [year] Jaraba Impact` | UI del tema (token [year]) |
| Links de redes sociales | `footer_social_linkedin`, `_twitter`, `_instagram` | URLs completas | UI del tema |

**Implicación para los nuevos módulos:** Al añadir las rutas `/eventos`, `/referidos`, `/ab-testing`, `/ads-dashboard`, es necesario actualizar los `navigation_items` por defecto en la configuración del tema para que aparezcan en el menú. Esto se hace desde la UI, no tocando código.

---

## 14. Seguridad y Multi-Tenancy

### Aislamiento de datos

Cada entidad nueva (`marketing_event`, `event_registration`, `ab_experiment`, `ads_account`, `ads_campaign_sync`, `ads_metrics_daily`, `tenant_addon`, `referral_code`, `referral`, `referral_reward`) **DEBE** incluir:

1. **Campo `tenant_id`** (entity_reference a `tenant`) — Obligatorio en baseFieldDefinitions
2. **Filtro en ListBuilder** — `$query->condition('tenant_id', $current_tenant_id)`
3. **Filtro en AccessControlHandler** — Denegar acceso si `entity->tenant_id != current_tenant_id`
4. **Filtro en servicios** — Todo método público que consulte datos verifica tenant vía `TenantContextService`
5. **Filtro en API** — Cada endpoint REST filtra por tenant automáticamente

### Rate Limiting (endpoints que invocan APIs externas)

| Endpoint | Límite | Mecanismo |
|----------|--------|-----------|
| `POST /api/v1/events/{id}/register` | 100 req/hora por tenant | `RateLimiterService` |
| `GET /api/v1/ads/sync` | 10 req/hora por tenant (APIs externas) | `RateLimiterService` |
| `POST /api/v1/ab-testing/track` | 1000 req/hora por tenant | `RateLimiterService` |

### Tokens y credenciales

- **Meta Ads API token** → `$_ENV['META_ADS_ACCESS_TOKEN']` (NUNCA en Drupal config)
- **Google Ads API credentials** → `$_ENV['GOOGLE_ADS_CLIENT_ID']`, `$_ENV['GOOGLE_ADS_CLIENT_SECRET']`
- **Stripe keys** → Ya en env vars (reutilizar patrón existente)
- **Tokens por tenant** (ads_account.access_token) → Encriptados con AES-256 via `defuse/php-encryption`

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-11 | 1.0.0 | Creación del documento. 5 gaps identificados con plan detallado de implementación. Tabla de correspondencia con 16 especificaciones 20260119. |

---

*Documento generado como parte del Proyecto JarabaImpactPlatformSaaS. Sigue las directrices de `00_DIRECTRICES_PROYECTO.md` v6.3.0.*
