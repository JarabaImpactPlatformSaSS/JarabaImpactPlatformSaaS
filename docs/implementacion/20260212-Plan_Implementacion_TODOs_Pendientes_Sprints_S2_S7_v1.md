# Plan de Implementacion — TODOs Pendientes: Sprints S2-S7

**Fecha de creacion:** 2026-02-12
**Ultima actualizacion:** 2026-02-12
**Autor:** Claude Opus 4.6 — Arquitecto SaaS Senior
**Version:** 1.0.0
**Precedente:** Sprint Inmediato completado (48 TODOs resueltos, commit `d2684dbd`)
**Referencia:** Catalogo TODOs v1.2.0 — 112 unicos, 48 resueltos, **64 pendientes**

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de TODOs Pendientes](#2-inventario-de-todos-pendientes)
3. [Sprint S2 — Notifications & Events (ECA Dispatch)](#3-sprint-s2--notifications--events-eca-dispatch)
4. [Sprint S3 — ServiciosConecta Fases 2-6](#4-sprint-s3--serviciosconecta-fases-2-6)
5. [Sprint S4 — ComercioConecta Fases 2-7](#5-sprint-s4--comercioconecta-fases-2-7)
6. [Sprint S5 — Job Board + LMS Re-enable](#6-sprint-s5--job-board--lms-re-enable)
7. [Sprint S6 — Social APIs + Make.com](#7-sprint-s6--social-apis--makecom)
8. [Sprint S7 — Qdrant Semantic + FOC Metricas](#8-sprint-s7--qdrant-semantic--foc-metricas)
9. [Backlog — Baja Prioridad / Dependencias Externas](#9-backlog--baja-prioridad--dependencias-externas)
10. [Directrices de Obligado Cumplimiento](#10-directrices-de-obligado-cumplimiento)
11. [Arbol de Dependencias entre Sprints](#11-arbol-de-dependencias-entre-sprints)
12. [Estimaciones y Roadmap](#12-estimaciones-y-roadmap)
13. [Verificacion y Validacion](#13-verificacion-y-validacion)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Tras el Sprint Inmediato (TENANT_FILTERING + DATA_INTEGRATION), quedan **64 TODOs** en codigo propio distribuidos en 20 modulos. Este plan los organiza en **6 sprints priorizados** (S2-S7) mas un backlog de items de baja prioridad o con dependencias externas.

| Metrica | Valor |
|---------|-------|
| TODOs totales pendientes | 64 |
| Sprints planificados | 6 (S2-S7) |
| TODOs en sprints | 49 |
| TODOs en backlog | 15 |
| Modulos afectados | 20 |
| Estimacion total | 180-250h |

### Criterios de Priorizacion

1. **Impacto en usuario final**: Notificaciones y alertas son funcionalidad visible
2. **Verticales incompletos**: ServiciosConecta y ComercioConecta tienen APIs stub (501)
3. **Dependencias internas**: Los re-enable de LMS/Job Board dependen de S2
4. **Dependencias externas**: OAuth social y Qdrant requieren infraestructura adicional

---

## 2. Inventario de TODOs Pendientes

### 2.1 Por Categoria

| Categoria | TODOs | Sprints | Prioridad |
|-----------|-------|---------|-----------|
| NOTIFICATIONS / EMAIL | 15 | S2 | P0 — Critico |
| SERVICIOS_CONECTA | 8 | S3 | P0 — Vertical incompleto |
| COMERCIO_CONECTA | 6 | S4 | P1 — Alto |
| JOB_BOARD + LMS | 4 | S5 | P1 — Re-enable funcionalidad |
| SOCIAL / MAKE.COM | 5 | S6 | P2 — Medio |
| QDRANT + FOC | 7 | S7 | P2 — Medio |
| EXTERNAL_API / INFRA | 6 | Backlog | P3 — Bajo |
| PAGE_BUILDER | 5 | Backlog | P3 — Bajo |
| TENANT_KNOWLEDGE | 4 | Backlog | P3 — Sprints TK dedicados |

### 2.2 Por Modulo

| Modulo | TODOs | Sprint |
|--------|-------|--------|
| ecosistema_jaraba_core | 8 | S2 + Backlog |
| jaraba_servicios_conecta | 8 | S3 |
| jaraba_comercio_conecta | 6 | S4 |
| jaraba_social | 4 | S6 |
| jaraba_lms | 4 | S2 + S5 |
| jaraba_job_board | 5 | S2 + S5 |
| jaraba_foc | 4 | S7 |
| jaraba_matching | 2 | S7 |
| jaraba_page_builder | 5 | Backlog |
| jaraba_tenant_knowledge | 4 | Backlog |
| jaraba_credentials | 1 | S2 |
| jaraba_groups | 1 | S2 |
| jaraba_funding | 1 | S7 |
| jaraba_integrations | 1 | Backlog |
| jaraba_content_hub | 1 | Backlog |
| jaraba_pixels | 2 | Backlog |
| jaraba_commerce | 1 | Backlog |
| jaraba_interactive | 1 | Backlog |
| jaraba_geo | 1 | Backlog |

---

## 3. Sprint S2 — Notifications & Events (ECA Dispatch)

**Objetivo:** Implementar el dispatch de eventos y envio de emails en todos los puntos donde hay TODOs de notificacion.
**TODOs:** 15
**Estimacion:** 30-40h
**Dependencias:** `jaraba_email` (TemplateLoaderService ya existe con 24 templates MJML)
**Patron de referencia:** `jaraba_credentials/src/Service/CredentialIssuer.php` (event dispatching para ECA)

### 3.1 Patron Comun: Event Dispatch + hook_mail

Todos los TODOs de esta categoria siguen el mismo patron:

```php
// 1. Crear evento custom
$event = new GenericEvent($entity, ['type' => 'enrollment_created', 'data' => $data]);
$this->eventDispatcher->dispatch($event, 'jaraba_lms.enrollment.created');

// 2. O enviar email directo via MailManager
$mailManager = \Drupal::service('plugin.manager.mail');
$mailManager->mail('jaraba_lms', 'enrollment_created', $recipientEmail, 'es', $params);
```

### 3.2 Tareas

#### S2-01: EnrollmentService — 3 eventos (jaraba_lms)
**Fichero:** `web/modules/custom/jaraba_lms/src/Service/EnrollmentService.php`

| Linea | Metodo | Evento | Destinatario |
|-------|--------|--------|--------------|
| 130 | `enroll()` | `jaraba_lms.enrollment.created` | Estudiante (bienvenida) |
| 227 | `recalculateProgress()` | `jaraba_lms.enrollment.completed` | Estudiante (certificado) + Sistema (badge) |
| 294 | `sendAbandonedCourseReminders()` | Queue email via `plugin.manager.mail` | Estudiante (re-engagement) |

**Cambios:**
1. Verificar que `EventDispatcherInterface` ya esta inyectado (si, lo esta).
2. L130: Dispatch `GenericEvent` con enrollment data tras `$enrollment->save()`.
3. L227: Dispatch evento de completion. Incluir `course_id`, `user_id`, `completion_date`.
4. L294: Implementar `\Drupal::service('plugin.manager.mail')->mail()` con template `enrollment_reminder`. Cargar enrollment para obtener `user_id` y `course_id`.
5. Anadir `hook_mail()` en `jaraba_lms.module` para los 3 tipos de email.

#### S2-02: ApplicationService — 2 eventos (jaraba_job_board)
**Fichero:** `web/modules/custom/jaraba_job_board/src/Service/ApplicationService.php`

| Linea | Metodo | Evento | Destinatario |
|-------|--------|--------|--------------|
| 121 | `apply()` | `jaraba_job_board.application.created` | Employer (nueva candidatura) + Candidato (confirmacion) |
| 261 | `updateStatus()` | `jaraba_job_board.application.status_changed` | Candidato (segun status: shortlisted/interviewed/offered/rejected) |

**Cambios:**
1. L121: Dispatch evento con `job_id`, `candidate_id`, `application_id`.
2. L261: Dispatch evento con `application_id`, `old_status`, `new_status`. El handler ECA decidira que email enviar segun el status.
3. Anadir `hook_mail()` en `jaraba_job_board.module` para tipos: `application_received`, `application_confirmed`, `status_shortlisted`, `status_interviewed`, `status_offered`, `status_rejected`.

#### S2-03: WebhookController — 3 emails Stripe (ecosistema_jaraba_core)
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/WebhookController.php`

| Linea | Metodo | Template Email | Destinatario |
|-------|--------|----------------|--------------|
| 381 | `handleSubscriptionDeleted()` | `subscription_cancelled` | Admin tenant |
| 463 | `handlePaymentFailed()` | `payment_failed` | Admin tenant |
| 493 | `handleTrialWillEnd()` | `trial_expiring` | Admin tenant |

**Cambios:**
1. Inyectar `MailManagerInterface` via `create()`.
2. Obtener email del admin: `$tenant->getAdminUser()->getEmail()`.
3. Para cada TODO, llamar a `$this->mailManager->mail('ecosistema_jaraba_core', $template, $email, 'es', $params)`.
4. Los templates ya existen en jaraba_email (24 templates MJML). Si no hay template especifico, usar `generic_notification`.

#### S2-04: UsageLimitsWorker — email de alerta (ecosistema_jaraba_core)
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Plugin/QueueWorker/UsageLimitsWorker.php`
**Linea:** 115

**Cambios:**
1. Inyectar `MailManagerInterface` via `ContainerFactoryPluginInterface`.
2. En `sendAlert()`, obtener email admin del tenant y enviar mail con tipo `usage_limit_warning` (80%) o `usage_limit_exceeded` (100%).
3. Params: `tenant_name`, `limit_type`, `percentage`, `current`, `max`, `upgrade_url`.

#### S2-05: Credentials notification queue (jaraba_credentials)
**Fichero:** `web/modules/custom/jaraba_credentials/jaraba_credentials.module`
**Linea:** 510

**Cambios:**
1. Cargar `plugin.manager.mail` en la funcion.
2. Segun `$data['type']`, enviar email con template correspondiente: `credential_issued`, `credential_expiring`, `credential_revoked`.
3. Cargar usuario destinatario via `$data['user_id']`.

#### S2-06: Group event reminders (jaraba_groups)
**Fichero:** `web/modules/custom/jaraba_groups/jaraba_groups.module`
**Linea:** 237

**Cambios:**
1. Obtener miembros registrados del evento via `group_relationship` con `plugin_id = 'group_event_attendance'` (o equivalente).
2. Para cada miembro, enviar email con `plugin.manager.mail` tipo `event_reminder`.
3. Params: `event_title`, `start_datetime`, `location`, `event_url`.

#### S2-07: Trial expiring reminder (ecosistema_jaraba_core)
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module`
**Linea:** 1173

**Cambios:**
1. Cargar admin user del tenant: `$tenant->getAdminUser()`.
2. Enviar email via `plugin.manager.mail` tipo `trial_expiring_3days`.
3. Params: `tenant_name`, `trial_end_date`, `upgrade_url`.

### 3.3 Verificacion Sprint S2

- [ ] Todos los eventos se dispatchen correctamente (verificar con `drush ev`)
- [ ] Los `hook_mail()` devuelven subject + body correctos para cada tipo
- [ ] Los emails se renderizan correctamente (verificar en MailHog/Mailtrap)
- [ ] Los eventos son consumibles por ECA (verificar que aparecen como triggers disponibles)
- [ ] No hay regresiones en los tests existentes

---

## 4. Sprint S3 — ServiciosConecta Fases 2-6

**Objetivo:** Implementar la logica de negocio pendiente en el marketplace de servicios profesionales.
**TODOs:** 8
**Estimacion:** 40-55h
**Dependencias:** Entidades `Booking` y `AvailabilitySlot` ya existen
**Patron de referencia:** `jaraba_agroconecta_core/src/Controller/SalesApiController.php` (API controller JSON response)

### 4.1 Tareas

#### S3-01: createBooking() — API completa (Fase 2)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/src/Controller/ServiceApiController.php:209`

**Implementacion:**
1. Validar datos de entrada: `provider_id`, `service_id`, `datetime`, `duration`.
2. Verificar disponibilidad via `AvailabilityService::isSlotAvailable($provider_id, $datetime, $duration)`.
3. Crear entidad `Booking` con status `pending_confirmation`.
4. Si `advance_payment_required`: crear Stripe PaymentIntent y devolver `client_secret`.
5. Si modalidad online: generar meeting URL (placeholder hasta integracion video).
6. Marcar slot como ocupado en `AvailabilitySlot`.
7. Retornar `JsonResponse` con booking_id, status, meeting_url, payment_info.

#### S3-02: updateBooking() — cambio de estado (Fase 2)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/src/Controller/ServiceApiController.php:221`

**Implementacion:**
1. Cargar booking por ID. Verificar que el usuario actual tiene permiso (provider o client).
2. Validar transicion de estado permitida (pending→confirmed, confirmed→cancelled, etc.).
3. Actualizar status. Si `cancelled`, liberar slot de disponibilidad.
4. Retornar `JsonResponse` con booking actualizado.

#### S3-03: Cron — reservas pendientes >24h (Fase 2)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/jaraba_servicios_conecta.module:213`

**Implementacion:**
1. Query `booking` entities con `status = 'pending_confirmation'` y `created < (now - 86400)`.
2. Auto-cancelar reservas no confirmadas. Liberar slots.
3. Log de cada auto-cancelacion.

#### S3-04: Cron — recordatorios de citas (Fase 4)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/jaraba_servicios_conecta.module:214`

**Implementacion:**
1. Query bookings con `status = 'confirmed'` y `datetime` entre ahora+23h y ahora+25h.
2. Enviar email recordatorio a cliente y proveedor (depende de S2 para hook_mail).
3. Segundo check para recordatorios 1h antes (ahora+55min a ahora+65min).

#### S3-05: Notificacion aprobacion profesional (Fase 6)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/jaraba_servicios_conecta.module:168`

**Implementacion:**
1. En `entity_update()`, tras detectar aprobacion, obtener email del profesional.
2. Enviar email via `plugin.manager.mail` tipo `provider_approved`.

#### S3-06: Notificacion estado reserva + liberar slot (Fases 4-5)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/jaraba_servicios_conecta.module:185-186`

**Implementacion:**
1. En `entity_update()` de Booking, detectar cambio de status.
2. Enviar email segun nuevo status (confirmed, cancelled, completed).
3. Si `cancelled`: query `AvailabilitySlot` correspondiente y marcar como disponible.

#### S3-07: Detectar no-shows + limpiar slots (Fase 5)
**Fichero:** `web/modules/custom/jaraba_servicios_conecta/jaraba_servicios_conecta.module:215-216`

**Implementacion:**
1. Query bookings `confirmed` con `datetime` pasada y sin `check_in_at`.
2. Marcar como `no_show`. Registrar en log.
3. Query `AvailabilitySlot` con `end_datetime` pasada y `status = 'booked'`. Liberar.

### 4.2 Verificacion Sprint S3

- [ ] POST `/api/v1/servicios/bookings` crea reservas correctamente (no 501)
- [ ] PATCH `/api/v1/servicios/bookings/{id}` actualiza estado
- [ ] Cron auto-cancela reservas pendientes >24h
- [ ] Recordatorios de citas se envian a 24h y 1h
- [ ] Slots se liberan al cancelar o expirar

---

## 5. Sprint S4 — ComercioConecta Fases 2-7

**Objetivo:** Completar KPIs de dashboard merchant y automatizaciones cron.
**TODOs:** 6
**Estimacion:** 25-35h
**Dependencias:** Entidades `MerchantShop`, `ComercioProduct`, `ComercioOrder` ya existen

### 5.1 Tareas

#### S4-01: KPIs del merchant dashboard (Fase 2)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/src/Service/MerchantDashboardService.php:110`

**Implementacion:**
1. `ventas_hoy`: Query `comercio_order` con `status = 'completed'` y `created` = hoy, SUM(`total_price`).
2. `ventas_mes`: Idem filtrando por mes actual.
3. `pedidos_pendientes`: COUNT `comercio_order` con `status = 'pending'`.
4. `ingresos_mes`: SUM(`total_price`) de ordenes completadas del mes.

#### S4-02: Cron — carritos abandonados (Fase 2)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module:187`

**Implementacion:**
1. Query `comercio_cart` con `status = 'active'` y `changed < (now - 3600)`.
2. Enviar email recordatorio al cliente (depende de S2 para hook_mail).
3. Marcar carrito con `reminder_sent = TRUE` para no repetir.

#### S4-03: Cron — flash offers expiradas (Fase 5)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module:188`

**Implementacion:**
1. Query `flash_offer` (o campo en producto) con `end_date < now` y `status = 'active'`.
2. Actualizar status a `expired`. Restaurar precio original si aplica.

#### S4-04: Cron — reconciliacion stock POS (Fase 7)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module:189`

**Implementacion:**
1. Query productos con `pos_sync_enabled = TRUE`.
2. Comparar stock en BD con ultimo dato reportado por POS.
3. Registrar discrepancias en log. Si diferencia >20%, generar alerta.

#### S4-05: Notificacion aprobacion comerciante (Fase 6)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module:143`

**Implementacion:**
1. En `entity_update()`, obtener email del merchant.
2. Enviar email via `plugin.manager.mail` tipo `merchant_approved`.

#### S4-06: Notificacion stock bajo (Fase 6)
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.module:160`

**Implementacion:**
1. En `entity_update()`, al detectar stock bajo (< umbral configurable).
2. Enviar email al merchant: `low_stock_alert` con producto, stock actual, umbral.

### 5.2 Verificacion Sprint S4

- [ ] Dashboard merchant muestra ventas_hoy, ventas_mes, pedidos_pendientes, ingresos_mes reales
- [ ] Cron detecta y notifica carritos abandonados
- [ ] Flash offers expiradas se desactivan automaticamente
- [ ] Emails de aprobacion y stock bajo se envian correctamente

---

## 6. Sprint S5 — Job Board + LMS Re-enable

**Objetivo:** Completar funcionalidades stub en Job Board y re-habilitar hooks desactivados.
**TODOs:** 4
**Estimacion:** 15-20h
**Dependencias:** S2 completado (eventos ECA necesarios)

### 6.1 Tareas

#### S5-01: closeExpiredJobs() (jaraba_job_board)
**Fichero:** `web/modules/custom/jaraba_job_board/src/Service/JobPostingService.php:96`

**Implementacion:**
1. Query `job_posting` con `expiration_date < now` y `status = 'published'`.
2. Actualizar status a `expired`. Disparar evento `job_posting.expired`.
3. Retornar conteo de jobs cerrados.

#### S5-02: processScheduledAlerts() (jaraba_job_board)
**Fichero:** `web/modules/custom/jaraba_job_board/src/Service/JobAlertService.php:66`

**Implementacion:**
1. Query `job_alert` entities activas con criterios de busqueda (skills, location, salary_range).
2. Para cada alerta, query `job_posting` recientes que matcheen criterios.
3. Si hay resultados nuevos, encolar email con jobs matcheados.
4. Actualizar `last_sent_at` en la alerta.

#### S5-03: Re-enable presave hook (jaraba_job_board)
**Fichero:** `web/modules/custom/jaraba_job_board/jaraba_job_board.module:289`

**Implementacion:**
1. Descomentar el hook `entity_presave` que llama a MatchingService.
2. MatchingService ya esta implementado (Sprint Inmediato lo completo).
3. Verificar que no hay errores con un test manual.

#### S5-04: Re-enable cron (jaraba_lms)
**Fichero:** `web/modules/custom/jaraba_lms/jaraba_lms.module:126`

**Implementacion:**
1. Descomentar las llamadas a `EnrollmentService` y `CertificationService` en cron.
2. Los servicios ya estan implementados (Sprint Inmediato).
3. Verificar que cron ejecuta sin errores.

### 6.2 Verificacion Sprint S5

- [ ] `closeExpiredJobs()` cierra jobs expirados en cron
- [ ] `processScheduledAlerts()` envia alertas a candidatos con matching jobs
- [ ] Hook presave de job_board funciona sin errores
- [ ] Cron de LMS ejecuta enrollment reminders y certification checks

---

## 7. Sprint S6 — Social APIs + Make.com

**Objetivo:** Implementar integraciones reales con plataformas sociales y Make.com.
**TODOs:** 5
**Estimacion:** 25-35h
**Dependencias:** Credenciales OAuth de cada plataforma, webhook URL de Make.com
**Riesgo:** Requiere cuentas de desarrollador en Meta, X, LinkedIn, Make.com

### 7.1 Tareas

#### S6-01: SocialPostService — API clients por plataforma
**Fichero:** `web/modules/custom/jaraba_social/src/Service/SocialPostService.php:167`

**Implementacion:**
1. Crear `SocialPlatformClientInterface` con metodo `publish(SocialAccount $account, string $content, array $media): array`.
2. Implementar clientes: `FacebookClient` (Graph API v19), `InstagramClient` (Graph API), `TwitterClient` (X API v2), `LinkedInClient` (Marketing API).
3. Cada cliente usa `\Drupal::httpClient()` para las llamadas HTTP.
4. En `publishToPlatform()`, resolver cliente por `$account->getPlatform()` y llamar a `publish()`.

#### S6-02: SocialAccountService — OAuth real
**Fichero:** `web/modules/custom/jaraba_social/src/Service/SocialAccountService.php:149`

**Implementacion:**
1. Implementar `refreshToken()` con llamada HTTP al endpoint de token de cada plataforma.
2. Facebook/Instagram: POST `https://graph.facebook.com/oauth/access_token` con `grant_type=fb_exchange_token`.
3. X/Twitter: POST `https://api.twitter.com/2/oauth2/token` con `grant_type=refresh_token`.
4. LinkedIn: POST `https://www.linkedin.com/oauth/v2/accessToken`.
5. Actualizar `access_token` y `token_expires` en la entidad SocialAccount.

#### S6-03: SocialCalendarService — analisis engagement real
**Fichero:** `web/modules/custom/jaraba_social/src/Service/SocialCalendarService.php:214`

**Implementacion:**
1. Query `social_post` entities publicadas en los ultimos 90 dias.
2. Agrupar por dia de la semana y hora. Calcular media de `engagement_rate` por slot.
3. Retornar los 5 mejores slots como horarios optimos.
4. Fallback a valores genericos si no hay suficiente historico (<10 posts).

#### S6-04: MakeComIntegrationService — webhook HTTP
**Fichero:** `web/modules/custom/jaraba_social/src/Service/MakeComIntegrationService.php:105`

**Implementacion:**
1. En `publishViaWebhook()`, usar `\Drupal::httpClient()->post($webhookUrl, ['json' => $payload])`.
2. Validar response (200/201 = exito, otros = error).
3. Registrar resultado en log y actualizar `social_post` con `external_post_id` si retornado.

#### S6-05: MakeComIntegrationService — verificacion conectividad
**Fichero:** `web/modules/custom/jaraba_social/src/Service/MakeComIntegrationService.php:262`

**Implementacion:**
1. En `testConnection()`, enviar POST al webhook con payload de prueba `{'test': true}`.
2. Retornar TRUE si response 200, FALSE si timeout o error.

### 7.2 Verificacion Sprint S6

- [ ] Posts se publican realmente en cada plataforma (con cuentas de prueba)
- [ ] Tokens se refrescan automaticamente antes de expirar
- [ ] Horarios optimos se calculan desde datos reales
- [ ] Webhook de Make.com recibe y procesa payloads

---

## 8. Sprint S7 — Qdrant Semantic + FOC Metricas

**Objetivo:** Integrar busqueda semantica con Qdrant e implementar metricas FOC reales.
**TODOs:** 7
**Estimacion:** 30-40h
**Dependencias:** Qdrant operativo (ya configurado en infraestructura Lando), colecciones creadas
**Patron de referencia:** `jaraba_rag/src/Service/JarabaRagService.php` (embedding + Qdrant search)

### 8.1 Tareas

#### S7-01: getSimilarJobs() — Qdrant semantic similarity
**Fichero:** `web/modules/custom/jaraba_matching/src/Controller/MatchingApiController.php:195`

**Implementacion:**
1. Cargar job_posting por ID. Obtener embedding del job (titulo + descripcion).
2. Si no existe embedding, generarlo via `@ai.provider` y almacenar.
3. Buscar en Qdrant collection `jaraba_jobs` los K vectores mas similares.
4. Filtrar por tenant_id (MUST filter). Excluir el job actual.
5. Retornar array de similar_jobs con score de similaridad.

#### S7-02: reindexJob() — Qdrant indexacion
**Fichero:** `web/modules/custom/jaraba_matching/src/Controller/MatchingApiController.php:251`

**Implementacion:**
1. Cargar job_posting. Generar embedding desde titulo + descripcion + skills_required.
2. Upsert en Qdrant collection `jaraba_jobs` con `point_id = job_id`, `payload = {tenant_id, status, skills}`.
3. Retornar success con embedding dimensions.

#### S7-03: FundingMatchingEngine — semantic score
**Fichero:** `web/modules/custom/jaraba_funding/src/Service/Intelligence/FundingMatchingEngine.php:368`

**Implementacion:**
1. Obtener embedding de la descripcion del FundingSubscription.
2. Obtener embedding de la descripcion del FundingCall.
3. Calcular similaridad coseno entre ambos vectores.
4. Normalizar a escala 0-100.
5. Si Qdrant no disponible, mantener fallback 50.0.

#### S7-04: FocDashboardController — alertas reales
**Fichero:** `web/modules/custom/jaraba_foc/src/Controller/FocDashboardController.php:279`

**Implementacion:**
1. Llamar a `AlertService::evaluatePlatformAlerts()` (ya existe).
2. Formatear alertas como array con tipo, mensaje, severidad, timestamp.
3. Incluir alertas de MRR drop, churn spike, overdue invoices.

#### S7-05: EtlService — metricas completas
**Fichero:** `web/modules/custom/jaraba_foc/src/Service/EtlService.php:245`

**Implementacion:**
1. Anadir metricas faltantes al snapshot: `quick_ratio`, `revenue_per_employee`, `gross_margin`.
2. Usar `MetricsCalculatorService` para cada calculo.

#### S7-06: MetricsCalculatorService — CAC real
**Fichero:** `web/modules/custom/jaraba_foc/src/Service/MetricsCalculatorService.php:424`

**Implementacion:**
1. Query `foc_transaction` con `type = 'marketing_expense'` del ultimo periodo.
2. Sumar gastos de marketing. Dividir por numero de nuevos clientes adquiridos.
3. Si no hay datos suficientes, mantener fallback '200.00'.

#### S7-07: AlertService — MRR drop detection
**Fichero:** `web/modules/custom/jaraba_foc/src/Service/AlertService.php:108`

**Implementacion:**
1. Obtener MRR actual y MRR del periodo anterior via `MetricsCalculatorService`.
2. Calcular diferencia porcentual.
3. Si drop > threshold (configurable, default 10%), generar alerta.

### 8.2 Verificacion Sprint S7

- [ ] `/api/v1/matching/jobs/{id}/similar` devuelve jobs semanticamente similares
- [ ] `/api/v1/matching/jobs/{id}/reindex` genera y almacena embedding en Qdrant
- [ ] Dashboard FOC muestra alertas reales basadas en metricas
- [ ] CAC se calcula desde transacciones reales cuando hay datos

---

## 9. Backlog — Baja Prioridad / Dependencias Externas

Items que pueden esperar o dependen de decisiones/infraestructura externa.

### 9.1 Page Builder (5 TODOs)

| Fichero | Linea | TODO | Nota |
|---------|-------|------|------|
| canvas-editor.js | 129 | Guardado completo | Requiere definir flujo save con GrapesJS |
| canvas-editor.js | 140 | Publicacion | Requiere workflow de publicacion aprobado |
| accessibility-validator.js | 249 | Slide-panel | UX decision pendiente |
| .module | 722 | Ratings de cursos | Requiere entidad Rating |
| .module | 724 | Categoria de Course | Requiere campo en lms_course |

### 9.2 Tenant Knowledge (4 TODOs — Sprints TK2-TK4)

| Fichero | Linea | TODO | Sprint |
|---------|-------|------|--------|
| KnowledgeDashboardController | 165 | FAQs | TK2 |
| KnowledgeDashboardController | 179 | Add FAQ form | TK2 |
| KnowledgeDashboardController | 190 | Policies | TK3 |
| KnowledgeDashboardController | 204 | Documents | TK4 |

### 9.3 Infraestructura / APIs Externas (6 TODOs)

| Fichero | Linea | TODO | Dependencia |
|---------|-------|------|-------------|
| VideoGeoService | 123 | Whisper API transcripcion | Cuenta OpenAI + presupuesto |
| AgentAutonomyService | 364 | Re-ejecutar accion | Diseno de executor pattern |
| WebhookReceiverController | 51 | Dispatch interno | Definir tipos de webhook |
| ContentHubDashboardController | 321 | AI writing assistant route | UX decision |
| pwa-register.js | 268 | VAPID public key | Configuracion servidor push |
| jaraba_commerce.module | 148 | Inventario Commerce | Commerce Inventory contrib |
| interactive/player.js | 1483 | Vista revision | UX decision |
| BatchProcessorService | 188 | Dispatch directo V2.1 | Arquitectura V2.1 |
| TokenVerificationService | 166 | Test call plataformas V2.1 | Arquitectura V2.1 |
| jaraba_geo.module | 40 | Wikidata/Crunchbase | APIs disponibles |
| TenantProvisioningTest | 50 | Migrar a Functional | Tiempo de CI |

---

## 10. Directrices de Obligado Cumplimiento

Todo codigo implementado en estos sprints DEBE cumplir:

| Directriz | Referencia |
|-----------|------------|
| TENANT-001 | Filtro tenant obligatorio en queries de datos |
| TENANT-002 | TenantContextService como punto unico de inyeccion |
| ENTITY-REF-001 | target_type mas especifico disponible |
| BILLING-001 | Cambios sincronizados jaraba_billing / ecosistema_jaraba_core |
| SERVICE-001 | DI via constructor, NUNCA `\Drupal::service()` en clases con DI |
| TEST-003 | Tests unitarios con `PHPUnit\Framework\TestCase` |
| SCSS-002 | Zero `@import`, solo `@use` |
| HEATMAP-001 | QueueWorker con ContainerFactoryPluginInterface |

### Patron Email (nuevo para S2)

```php
// En hook_mail():
function MYMODULE_mail($key, &$message, $params) {
  switch ($key) {
    case 'enrollment_created':
      $message['subject'] = t('Inscripcion confirmada: @course', ['@course' => $params['course_name']]);
      $message['body'][] = t('Hola @name, te has inscrito en @course.', $params);
      break;
  }
}

// En servicio:
$this->mailManager->mail('jaraba_lms', 'enrollment_created', $email, 'es', [
  'course_name' => $course->label(),
  'name' => $user->getDisplayName(),
]);
```

### Patron Qdrant (para S7)

```php
// Buscar similares
$results = $this->qdrantClient->search('jaraba_jobs', [
  'vector' => $embedding,
  'limit' => $limit,
  'filter' => [
    'must' => [
      ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
      ['key' => 'status', 'match' => ['value' => 'published']],
    ],
  ],
]);
```

---

## 11. Arbol de Dependencias entre Sprints

```
S2 (Notifications/Email)
├── S3 (ServiciosConecta) — usa hook_mail de S2
├── S4 (ComercioConecta) — usa hook_mail de S2
├── S5 (Job Board + LMS) — re-enable depende de que eventos funcionen
│
S6 (Social APIs) — independiente
S7 (Qdrant + FOC) — independiente

Backlog — sin dependencias criticas
```

**Ejecucion optima:**
- S2 primero (desbloquea S3, S4, S5)
- S3 + S6 en paralelo (independientes)
- S4 + S7 en paralelo (independientes)
- S5 al final (verifica todo)

---

## 12. Estimaciones y Roadmap

| Sprint | TODOs | Estimacion | Semana objetivo |
|--------|-------|------------|-----------------|
| **S2** Notifications | 15 | 30-40h | Semana 8 (Feb) |
| **S3** ServiciosConecta | 8 | 40-55h | Semana 9-10 (Mar) |
| **S4** ComercioConecta | 6 | 25-35h | Semana 9-10 (Mar) |
| **S5** Job Board + LMS | 4 | 15-20h | Semana 11 (Mar) |
| **S6** Social APIs | 5 | 25-35h | Semana 11-12 (Mar) |
| **S7** Qdrant + FOC | 7 | 30-40h | Semana 12-13 (Mar) |
| **Backlog** | 15+ | 40-60h | Q2 2026 |
| **TOTAL** | **64** | **205-285h** | **Feb-Mar 2026** |

### Hitos

| Hito | Criterio | Fecha objetivo |
|------|----------|----------------|
| H1: Emails operativos | Todos los eventos ECA dispatch + hook_mail | Fin Feb 2026 |
| H2: Verticales completos | ServiciosConecta + ComercioConecta sin 501 | Mid Mar 2026 |
| H3: Features re-enabled | Job Board + LMS cron activos | Fin Mar 2026 |
| H4: Integraciones externas | Social APIs + Qdrant operativos | Fin Mar 2026 |
| H5: Zero sprint TODOs | Todos los sprints S2-S7 completados | Fin Mar 2026 |

---

## 13. Verificacion y Validacion

### 13.1 Por Sprint

Cada sprint DEBE incluir:
1. **Tests unitarios**: Minimo 1 test por metodo implementado.
2. **Grep de TODOs**: Verificar reduccion de TODOs tras cada sprint.
3. **Revision de regresiones**: Ejecutar `vendor/bin/phpunit web/modules/custom/` completo.
4. **Documentacion**: Actualizar aprendizaje correspondiente.

### 13.2 Metricas de Progreso

```bash
# Conteo de TODOs antes y despues de cada sprint
grep -r "// TODO" web/modules/custom/ --include="*.php" --include="*.js" --include="*.module" -l | wc -l

# Conteo excluyendo node_modules
grep -r "// TODO" web/modules/custom/ --include="*.php" --include="*.module" | grep -v node_modules | wc -l
```

### 13.3 Criterio de Done por Sprint

- [ ] 0 TODOs pendientes de la categoria del sprint
- [ ] Tests pasan al 100%
- [ ] Commit atomico con mensaje descriptivo
- [ ] Entrada en registro de cambios de directrices
- [ ] Aprendizaje documentado si hay patrones nuevos

---

## 14. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-12 | 1.0.0 | Creacion inicial: inventario 64 TODOs, plan 6 sprints + backlog |
