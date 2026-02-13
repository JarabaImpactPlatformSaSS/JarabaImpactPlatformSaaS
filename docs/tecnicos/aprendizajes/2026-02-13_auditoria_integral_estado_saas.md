# Aprendizaje: Auditoría Integral del Estado SaaS (2026-02-13)

**Fecha:** 2026-02-13
**Categoría:** Auditoría / Seguridad / Rendimiento / Consistencia
**Impacto:** ALTO — 65 hallazgos nuevos (7 Críticos, 20 Altos, 26 Medios, 12 Bajos)

---

## Contexto

Auditoría integral del SaaS desde 15 disciplinas senior (Negocio, Carreras, Finanzas, Marketing, Publicidad, Arquitectura SaaS, Ingeniería SW, UX, Drupal, Web Dev, Theming, GrapesJS, SEO/GEO, IA, Seguridad). Codebase: 62 módulos custom, 268 Content Entities, ~769 rutas API. Auditoría anterior (2026-02-06): 19/87 hallazgos resueltos (22%).

---

## Lecciones Aprendidas

### 1. Índices de Base de Datos Son Prerrequisito para Escalar

**Situación:** 268 Content Entities sin un solo índice definido via `->addIndex()` en `baseFieldDefinitions()`. Con 326 usos de `->condition('tenant_id', ...)` en 139 archivos, cada query multi-tenant hace full table scan.

**Aprendizaje:** Los índices de BD NO son una optimización post-producción; son una necesidad ANTES de escalar. Sin índices, el sistema colapsa exponencialmente: 10 tenants < 1s, 100 tenants ~5s, 1000 tenants ~60s (inutilizable).

**Regla AUDIT-PERF-001:** Toda Content Entity con campo `tenant_id` DEBE definir `->addIndex('idx_tenant', ['tenant_id'])` en `baseFieldDefinitions()`. Entidades de alto volumen (AnalyticsEvent, BillingUsageRecord, UsageEvent) DEBEN tener índices compuestos en campos frecuentemente filtrados.

---

### 2. Locking Es Obligatorio en Operaciones Financieras

**Situación:** 0 usos de `LockBackendInterface` en 62 módulos. Las operaciones de creación de suscripción Stripe realizan 4 llamadas API secuenciales sin protección contra race conditions. 29 hooks cron sin protección de overlap.

**Aprendizaje:** En operaciones financieras (pagos, suscripciones, facturación), la ausencia de locking causa duplicación de recursos. Un doble-click del usuario puede crear 2 clientes Stripe, 2 suscripciones y 2 cobros.

**Regla AUDIT-PERF-002:** Todo flujo que involucre APIs de pago (Stripe, PayPal) DEBE adquirir un lock via `LockBackendInterface` con key `payment:{tenant_id}:{operation}` antes de la primera llamada API. Los hooks cron pesados DEBEN usar lock para prevenir overlap.

---

### 3. HMAC Es Obligatorio en TODOS los Webhooks

**Situación:** `WebhookReceiverController` acepta cualquier payload JSON sin verificar firma HMAC, a pesar de que el docblock menciona "Validates firma HMAC". `WhatsAppWebhookController` verifica GET (`hub_verify_token`) pero NO valida POST (`X-Hub-Signature-256`).

**Aprendizaje:** La directriz de HMAC obligatorio (sección 4.6 de Directrices) se documentó pero no se cumplió en todos los webhooks. La verificación de HMAC debe ser un patrón copiable, no reimplementado cada vez.

**Regla AUDIT-SEC-001:** Todo endpoint webhook DEBE verificar firma HMAC del payload antes de procesar. Usar el patrón existente en `BillingWebhookController` como referencia. Si un conector no proporciona firma, rechazar el webhook.

---

### 4. `_user_is_logged_in` No Es Suficiente para Rutas Sensibles

**Situación:** Más de 100 rutas API solo requieren `_user_is_logged_in` sin verificar permisos específicos. Esto incluye APIs de analytics de tenant, self-service (API keys, webhooks, dominios), APIs de Stripe y terminación de mentorías.

**Aprendizaje:** En un SaaS multi-tenant, autenticación ≠ autorización. Un usuario autenticado del Tenant A puede acceder a datos del Tenant B si la ruta solo verifica login.

**Regla AUDIT-SEC-002:** Toda ruta que devuelva o modifique datos de tenant DEBE usar `_permission: 'permiso_especifico'` en vez de `_user_is_logged_in`. Las rutas administrativas DEBEN verificar membership del Group/tenant.

---

### 5. `|raw` Requiere Sanitización Server-Side

**Situación:** 100+ templates Twig usan `|raw` para renderizar HTML de whitelabel, page builder, LMS, blog y respuestas de IA. El contenido es potencialmente controlado por admins de tenant.

**Aprendizaje:** `|raw` en Twig NO es inherentemente inseguro, pero requiere que el contenido sea sanitizado ANTES de llegar al template. Sin sanitización server-side, un admin de tenant puede inyectar JavaScript via `custom_footer_html`.

**Regla AUDIT-SEC-003:** Todo contenido que pase por `|raw` en Twig DEBE ser sanitizado en el controlador/servicio con `Xss::filterAdmin()` o `check_markup()` antes del renderizado. Nunca confiar en la buena fe del usuario, incluso si es admin.

---

### 6. Toda Content Entity DEBE Tener AccessControlHandler

**Situación:** 34 de 268 Content Entities (13%) carecen de handler `access` en su annotation `@ContentEntityType`. Entidades afectadas incluyen datos sensibles: `SalesMessageAgro`, `CustomerPreferenceAgro` (PII), `SocialAccount` (tokens OAuth), `SepeParticipante` (datos SEPE legalmente sensibles).

**Aprendizaje:** Sin AccessControlHandler, Drupal usa el handler por defecto que permite acceso a cualquier usuario con permisos genéricos. En un SaaS multi-tenant esto rompe el aislamiento.

**Regla AUDIT-CONS-001:** Toda Content Entity DEBE declarar un `access` handler en su annotation `@ContentEntityType`. El handler DEBE verificar tenant ownership para operaciones view/update/delete. Usar `TenantAccessControlHandler` como base.

---

### 7. Un Solo Servicio Canónico por Responsabilidad

**Situación:** `TenantContextService` existe duplicado: uno en `ecosistema_jaraba_core` (usa `admin_user_id`) y otro en `jaraba_rag` (usa `GroupMembershipLoaderInterface`). `ImpactCreditService` y `ExpansionRevenueService` tienen copias divergentes entre `jaraba_billing` y `ecosistema_jaraba_core` (59 líneas de diferencia en ExpansionRevenueService).

**Aprendizaje:** La duplicación de servicios es la causa raíz de bugs silenciosos. Cuando un servicio tiene 2+ implementaciones, los fixes aplicados a una no se propagan a las otras.

**Regla AUDIT-CONS-002:** Cada responsabilidad DEBE tener exactamente UN servicio canónico registrado en `services.yml`. Otros módulos DEBEN inyectarlo via DI. Si existe duplicación, eliminar las copias no registradas.

---

### 8. API Responses Deben Seguir un Formato Estándar

**Situación:** 28 patrones de respuesta JSON distintos entre controllers. Algunos usan `{status, data}`, otros `{success, message}`, otros `{error, code}`, y algunos devuelven arrays planos.

**Aprendizaje:** Sin un formato de respuesta estándar, el frontend no puede implementar un error handler genérico. Cada integración requiere parsing custom.

**Regla AUDIT-CONS-003:** Toda respuesta API DEBE seguir el envelope estándar: `{success: bool, data: mixed, error: {code: string, message: string}, meta: {page, total}}`. Los errores DEBEN usar códigos HTTP estándar (400, 401, 403, 404, 422, 500).

---

### 9. Versionado de API Es Prerrequisito

**Situación:** 76 rutas API no tienen prefijo `/api/v1/` versionado. Esto imposibilita hacer breaking changes sin romper integraciones existentes.

**Aprendizaje:** El versionado de API no es una mejora futura; es un prerrequisito para cualquier API que tenga consumidores externos o integraciones de terceros.

**Regla AUDIT-CONS-004:** Toda ruta API DEBE usar el prefijo `/api/v1/`. Nuevas versiones incompatibles DEBEN crear `/api/v2/`. El endpoint sin versión DEBE redirigir a la versión más reciente.

---

### 10. `tenant_id` DEBE Ser Entity Reference

**Situación:** 6 entidades (CandidateProfile, AnalyticsDaily, AnalyticsEvent, Course, Enrollment, JobPosting) definen `tenant_id` como `BaseFieldDefinition::create('integer')` en vez de `entity_reference`. Las 171 restantes usan correctamente `entity_reference`.

**Aprendizaje:** Usar `integer` para `tenant_id` rompe la integridad referencial y previene el uso de `->entity` accessor, `->referencedEntities()` y joins automáticos de Drupal.

**Regla AUDIT-CONS-005:** El campo `tenant_id` DEBE ser `entity_reference` con `target_type = 'tenant'`. NUNCA usar `integer` para campos que referencian otras entidades. Migración: update hook con cambio de campo + migración de datos.

---

### 11. Las Publicaciones Sociales Síncronas Bloquean al Usuario

**Situación:** `SocialPostService::publish()` itera sobre TODAS las cuentas sociales conectadas y llama a cada API síncronamente. Instagram requiere 2 llamadas secuenciales. Sin timeout configurado (Guzzle default = infinito).

**Aprendizaje:** Toda operación que involucre APIs externas (social media, webhooks, notificaciones) DEBE ejecutarse de forma asíncrona. Un timeout de una sola plataforma bloquea al usuario indefinidamente.

**Regla AUDIT-PERF-003:** Las llamadas a APIs externas de social media, notificaciones push y webhooks outbound DEBEN ejecutarse via `QueueWorker` de Drupal. El controlador DEBE devolver respuesta inmediata al usuario y encolar las operaciones.

---

## Tabla Consolidada de Reglas AUDIT-*

| ID | Dimensión | Descripción | Prioridad |
|----|-----------|-------------|-----------|
| AUDIT-SEC-001 | Seguridad | HMAC obligatorio en TODOS los webhooks | P0 |
| AUDIT-SEC-002 | Seguridad | `_permission` en rutas sensibles (no solo `_user_is_logged_in`) | P0 |
| AUDIT-SEC-003 | Seguridad | Sanitización server-side antes de `\|raw` en Twig | P0 |
| AUDIT-PERF-001 | Rendimiento | Índices DB obligatorios en tenant_id + campos frecuentes | P0 |
| AUDIT-PERF-002 | Rendimiento | LockBackendInterface para operaciones financieras | P0 |
| AUDIT-PERF-003 | Rendimiento | Queue async para APIs externas (social, webhooks) | P0 |
| AUDIT-CONS-001 | Consistencia | AccessControlHandler obligatorio en TODA Content Entity | P0 |
| AUDIT-CONS-002 | Consistencia | TenantContextService canónico único (eliminar duplicados) | P0 |
| AUDIT-CONS-003 | Consistencia | API response envelope estándar {success, data, error, message} | P1 |
| AUDIT-CONS-004 | Consistencia | Todas las rutas API con prefijo /api/v1/ | P1 |
| AUDIT-CONS-005 | Consistencia | tenant_id DEBE ser entity_reference (nunca integer) | P0 |

---

## Métricas de la Auditoría

| Métrica | Valor |
|---------|-------|
| Total hallazgos nuevos | 65 |
| Hallazgos Críticos | 7 (PERF-N01, PERF-N02, PERF-N03, CONS-N01, CONS-N02, CONS-N03, CONS-N04) |
| Hallazgos Altos | 20 |
| Hallazgos Medios | 26 |
| Hallazgos Bajos | 12 |
| Hallazgos previos pendientes | 68/87 (22% resueltos) |
| Módulos verificados | 62/62 (100% match) |
| Content Entities auditadas | 268 |
| Rutas API analizadas | ~769 |
| Archivos PHP analizados | 200+ |
| Templates Twig analizados | 100+ |
| Reglas nuevas generadas | 11 (AUDIT-*) |

---

## Referencias Cruzadas

| Documento | Enlace |
|-----------|--------|
| Auditoría Integral 2026-02-13 | [20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md](../auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md) |
| Plan de Remediación | [20260213-Plan_Remediacion_Auditoria_Integral_v1.md](../../implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md) |
| Directrices v20.0.0 | [00_DIRECTRICES_PROYECTO.md](../../00_DIRECTRICES_PROYECTO.md) |
| Documento Maestro v19.0.0 | [00_DOCUMENTO_MAESTRO_ARQUITECTURA.md](../../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md) |
| Auditoría anterior (2026-02-06) | [20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md](../auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md) |

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-13 | 1.0.0 | Creación inicial — 11 lecciones aprendidas de auditoría integral desde 15 disciplinas |
