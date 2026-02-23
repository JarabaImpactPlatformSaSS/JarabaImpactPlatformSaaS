# Plan de Implementacion: Sistema de Mensajeria Segura (jaraba_messaging)

**Fecha de creacion:** 2026-02-20 22:00
**Ultima actualizacion:** 2026-02-20 23:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.1.0
**Categoria:** Implementacion
**Codigo:** 178_Platform_Secure_Messaging_v1
**Documentos fuente:** `20260220c-178_Platform_Secure_Messaging_v1_Claude.md`, `20260220c-178A_Platform_Secure_Messaging_Anexo_Implementation_v1_Claude.md`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
3. [Tabla de Cumplimiento de Directrices del Proyecto](#3-tabla-de-cumplimiento-de-directrices-del-proyecto)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Entorno de Desarrollo](#5-entorno-de-desarrollo)
6. [Arquitectura del Modulo](#6-arquitectura-del-modulo)
   - 6.1 [Estructura de Archivos Completa](#61-estructura-de-archivos-completa)
   - 6.2 [Entidades Drupal (Content Entities)](#62-entidades-drupal-content-entities)
   - 6.3 [Entidades Custom Schema (hook_schema)](#63-entidades-custom-schema-hook_schema)
   - 6.4 [Servicios y Dependency Injection](#64-servicios-y-dependency-injection)
   - 6.5 [Controladores REST API](#65-controladores-rest-api)
   - 6.6 [Servidor WebSocket](#66-servidor-websocket)
   - 6.7 [Plugins ECA](#67-plugins-eca)
   - 6.8 [Queue Workers](#68-queue-workers)
7. [Arquitectura Frontend](#7-arquitectura-frontend)
   - 7.1 [Principios de Frontend Limpio (Zero-Region Policy)](#71-principios-de-frontend-limpio-zero-region-policy)
   - 7.2 [Templates Twig: Paginas y Parciales](#72-templates-twig-paginas-y-parciales)
   - 7.3 [SCSS: Modelo Federated Design Tokens](#73-scss-modelo-federated-design-tokens)
   - 7.4 [JavaScript: WebSocket Client y Chat Panel](#74-javascript-websocket-client-y-chat-panel)
   - 7.5 [Modales para Acciones CRUD](#75-modales-para-acciones-crud)
   - 7.6 [Integracion con Header, Navegacion y Footer del Tema](#76-integracion-con-header-navegacion-y-footer-del-tema)
8. [Internacionalizacion (i18n)](#8-internacionalizacion-i18n)
9. [Seguridad](#9-seguridad)
   - 9.1 [Cifrado AES-256-GCM](#91-cifrado-aes-256-gcm)
   - 9.2 [CSRF en Rutas API](#92-csrf-en-rutas-api)
   - 9.3 [XSS Prevention en Twig](#93-xss-prevention-en-twig)
   - 9.4 [Audit Trail Inmutable (Hash Chain)](#94-audit-trail-inmutable-hash-chain)
   - 9.5 [Rate Limiting](#95-rate-limiting)
   - 9.6 [Tenant Isolation](#96-tenant-isolation)
10. [Integracion con Ecosistema Existente](#10-integracion-con-ecosistema-existente)
11. [Configuracion Administrable desde UI de Drupal](#11-configuracion-administrable-desde-ui-de-drupal)
12. [Navegacion en Estructura Drupal (admin/structure y admin/content)](#12-navegacion-en-estructura-drupal-adminstructure-y-admincontent)
13. [Plan de Sprints Detallado](#13-plan-de-sprints-detallado)
14. [Estrategia de Testing](#14-estrategia-de-testing)
15. [Verificacion y Despliegue](#15-verificacion-y-despliegue)
16. [Troubleshooting](#16-troubleshooting)
17. [Referencias Cruzadas](#17-referencias-cruzadas)
18. [Registro de Cambios](#18-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

El modulo `jaraba_messaging` es un sistema de mensajeria segura bidireccional cifrada en tiempo real para la Jaraba Impact Platform. Permite conversaciones cifradas con AES-256-GCM entre profesionales y clientes, mentores y emprendedores, orientadores y candidatos, y productores y compradores, cubriendo las 5 verticales del SaaS (ServiciosConecta, JarabaLex, Empleabilidad, Emprendimiento, AgroConecta/ComercioConecta).

### 1.2 Por que se implementa

La plataforma carece de comunicacion bidireccional nativa. Los usuarios recurren a canales externos no trazables (WhatsApp, email personal, telefono) que vulneran el RGPD, carecen de audit trail, no tienen valor probatorio legal, y fragmentan la experiencia del SaaS. Este modulo cierra esa brecha reutilizando componentes existentes (Buzon de Confianza doc 88, Notificaciones doc 98, Copilot doc 93, Redis Pub/Sub doc 34, Dashboard Profesional doc 94).

### 1.3 Alcance del plan

Este documento detalla la implementacion completa del modulo `jaraba_messaging` conforme a:

- **Especificacion tecnica:** doc 178 (modelo de datos, APIs, WebSocket, seguridad, ECA, IA)
- **Anexo de implementacion:** doc 178A (artefactos YAML, entidades PHP, interfaces, schemas, tests)
- **Directrices del Proyecto:** v60.0.0 (CSRF, XSS, i18n, permisos, Drupal 10+ patterns, append-only entities, config seeding, field mapping, state machine, cron flags)
- **Directrices de Desarrollo:** v3.2 (paleta de colores, iconografia, SCSS, SDC, entidades, append-only, config seeding, booking API, state machine, cron idempotency)
- **Arquitectura de Theming SaaS:** v2.1 (Federated Design Tokens, CSS Custom Properties)
- **Flujo de Trabajo Claude:** v14.0.0 (mocking, CI/CD, seguridad API, remediation, append-only entities, config seeding)

### 1.4 Filosofia de implementacion

- **Sin Humo:** Reutilizacion maxima de componentes existentes (~100h ahorro estimado)
- **Frontend Limpio:** Templates Twig sin `{{ page.content }}` ni bloques Drupal, layout full-width mobile-first
- **Modales para acciones:** Crear/editar/ver conversaciones siempre en modal, sin abandonar la pagina actual
- **Configuracion desde UI:** Todos los parametros (retencion RGPD, rate limits, WebSocket, etc.) configurables desde `/admin/config/jaraba/messaging` sin tocar codigo
- **Tenant no ve admin Drupal:** El tenant opera exclusivamente en rutas frontend limpias

### 1.5 Estimacion

| Concepto | Horas Min | Horas Max |
|----------|-----------|-----------|
| Sprint 1: Foundation (Entidades + Cifrado + Audit) | 30h | 35h |
| Sprint 2: Core API (Servicios + REST + NotificationBridge) | 28h | 32h |
| Sprint 3: Real-Time (WebSocket + Presencia + Redis Pub/Sub) | 25h | 30h |
| Sprint 4: Frontend (Chat Panel + Templates + SCSS + Modales) | 30h | 35h |
| Sprint 5: Integration (Portal Cliente + Dashboard + ECA) | 22h | 28h |
| Sprint 6: AI + QA (Qdrant + Copilot + Security Audit + Tests) | 25h | 30h |
| **TOTAL** | **160h** | **190h** |

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

Esta tabla mapea cada seccion de los documentos fuente (doc 178 y doc 178A) con los pasos concretos de implementacion en este plan, el sprint asignado, y el estado de cumplimiento.

### 2.1 Correspondencia con doc 178 (Especificacion Tecnica)

| Seccion doc 178 | Descripcion | Seccion de este Plan | Sprint | Prioridad |
|------------------|-------------|---------------------|--------|-----------|
| 1.1-1.5 | Resumen ejecutivo, problema, solucion, diferenciadores, cross-vertical | [1. Resumen Ejecutivo](#1-resumen-ejecutivo) | - | Contexto |
| 2.1 | Diagrama de componentes | [6. Arquitectura del Modulo](#6-arquitectura-del-modulo) | - | Referencia |
| 2.2 | Stack tecnologico | [4. Requisitos Previos](#4-requisitos-previos) | - | Referencia |
| 2.3 | Relacion con componentes existentes | [10. Integracion con Ecosistema](#10-integracion-con-ecosistema-existente) | S5-S6 | Alta |
| 2.4 | Flujo de mensaje (envio a entrega) | [6.4 Servicios y DI](#64-servicios-y-dependency-injection), [6.6 WebSocket](#66-servidor-websocket) | S2-S3 | Critica |
| 2.5 | Estructura del modulo | [6.1 Estructura de Archivos](#61-estructura-de-archivos-completa) | S1 | Critica |
| 3.1 | Entidad secure_conversation | [6.2 Entidades Content Entity](#62-entidades-drupal-content-entities) | S1 | Critica |
| 3.2 | Entidad conversation_participant | [6.2 Entidades Content Entity](#62-entidades-drupal-content-entities) | S1 | Critica |
| 3.3 | Entidad secure_message | [6.3 Entidades Custom Schema](#63-entidades-custom-schema-hook_schema) | S1 | Critica |
| 3.4 | Entidad message_audit_log | [6.3 Entidades Custom Schema](#63-entidades-custom-schema-hook_schema) | S1 | Critica |
| 3.5 | Entidad message_read_receipt | [6.3 Entidades Custom Schema](#63-entidades-custom-schema-hook_schema) | S1 | Alta |
| 3.6 | Diagrama ER | [6. Arquitectura del Modulo](#6-arquitectura-del-modulo) | S1 | Referencia |
| 4.1 | Decision cifrado servidor vs E2E | [9.1 Cifrado AES-256-GCM](#91-cifrado-aes-256-gcm) | S1 | Critica |
| 4.2 | Jerarquia de claves | [9.1 Cifrado AES-256-GCM](#91-cifrado-aes-256-gcm) | S1 | Critica |
| 4.3 | Algoritmos criptograficos | [9.1 Cifrado AES-256-GCM](#91-cifrado-aes-256-gcm) | S1 | Critica |
| 4.4 | MessageEncryptionService | [6.4 Servicios y DI](#64-servicios-y-dependency-injection) | S1 | Critica |
| 4.5 | Modelo de amenazas | [9. Seguridad](#9-seguridad) | S1-S6 | Alta |
| 4.6 | Politica retencion RGPD | [11. Configuracion desde UI](#11-configuracion-administrable-desde-ui-de-drupal) | S2 | Alta |
| 5.1 | MessagingService (orquestador) | [6.4 Servicios y DI](#64-servicios-y-dependency-injection) | S2 | Critica |
| 5.2 | NotificationBridgeService | [6.4 Servicios y DI](#64-servicios-y-dependency-injection), [10. Integracion](#10-integracion-con-ecosistema-existente) | S2 | Alta |
| 5.3 | MessageAuditService (hash chain) | [6.4 Servicios y DI](#64-servicios-y-dependency-injection), [9.4 Audit Trail](#94-audit-trail-inmutable-hash-chain) | S1 | Critica |
| 6.1 | Arquitectura transporte real-time | [6.6 Servidor WebSocket](#66-servidor-websocket) | S3 | Critica |
| 6.2 | Protocolo WebSocket | [6.6 Servidor WebSocket](#66-servidor-websocket) | S3 | Critica |
| 6.3 | PresenceService (Redis) | [6.4 Servicios y DI](#64-servicios-y-dependency-injection), [6.6 WebSocket](#66-servidor-websocket) | S3 | Alta |
| 7.1-7.4 | APIs REST (Conversations, Messages, Search, Utils) | [6.5 Controladores REST API](#65-controladores-rest-api) | S2 | Critica |
| 8.1 | Chat Panel (React components) | [7.4 JavaScript](#74-javascript-websocket-client-y-chat-panel) | S4 | Critica |
| 8.2 | Integracion en Portal Cliente | [10. Integracion](#10-integracion-con-ecosistema-existente) | S5 | Alta |
| 8.3 | Notificaciones visuales | [7.4 JavaScript](#74-javascript-websocket-client-y-chat-panel) | S4 | Alta |
| 9.1-9.2 | Flujos ECA | [6.7 Plugins ECA](#67-plugins-eca) | S5 | Alta |
| 10.1 | RAG sobre mensajes (Copilot) | [10. Integracion](#10-integracion-con-ecosistema-existente), [6.8 Queue Workers](#68-queue-workers) | S6 | Media |
| 10.2 | AI Skills para mensajeria | [10. Integracion](#10-integracion-con-ecosistema-existente) | S6 | Media |
| 10.3 | Filtrado seguridad IA (is_confidential) | [9. Seguridad](#9-seguridad) | S6 | Alta |
| 11 | Estrategia de testing | [14. Estrategia de Testing](#14-estrategia-de-testing) | S1-S6 | Critica |
| 12 | Roadmap de sprints | [13. Plan de Sprints](#13-plan-de-sprints-detallado) | - | Referencia |

### 2.2 Correspondencia con doc 178A (Anexo Implementacion)

| Seccion doc 178A | Artefacto | Seccion de este Plan | Sprint | Estado |
|------------------|-----------|---------------------|--------|--------|
| A1 | jaraba_messaging.info.yml | [6.1 Estructura de Archivos](#61-estructura-de-archivos-completa) | S1 | Por implementar |
| A2 | jaraba_messaging.services.yml | [6.4 Servicios y DI](#64-servicios-y-dependency-injection) | S1 | Por implementar |
| A3 | jaraba_messaging.routing.yml | [6.5 Controladores REST API](#65-controladores-rest-api) | S2 | Por implementar |
| A4 | jaraba_messaging.permissions.yml + Matriz de roles | [9.6 Tenant Isolation](#96-tenant-isolation), [12. Navegacion](#12-navegacion-en-estructura-drupal-adminstructure-y-admincontent) | S1 | Por implementar |
| A5.1 | SecureConversation.php (Content Entity) | [6.2 Entidades Content Entity](#62-entidades-drupal-content-entities) | S1 | Por implementar |
| A5.2 | SecureMessage DTO + custom schema | [6.3 Entidades Custom Schema](#63-entidades-custom-schema-hook_schema) | S1 | Por implementar |
| A6 | Interfaces PHP de servicios | [6.4 Servicios y DI](#64-servicios-y-dependency-injection) | S1-S2 | Por implementar |
| A7.1 | jaraba_messaging.schema.yml | [11. Configuracion desde UI](#11-configuracion-administrable-desde-ui-de-drupal) | S1 | Por implementar |
| A7.2 | jaraba_messaging.settings.yml | [11. Configuracion desde UI](#11-configuracion-administrable-desde-ui-de-drupal) | S1 | Por implementar |
| A8 | hook_schema() + hook_install() | [6.3 Entidades Custom Schema](#63-entidades-custom-schema-hook_schema) | S1 | Por implementar |
| A9.1 | chat-panel.html.twig | [7.2 Templates Twig](#72-templates-twig-paginas-y-parciales) | S4 | Por implementar |
| A9.2 | TypeScript types para React | [7.4 JavaScript](#74-javascript-websocket-client-y-chat-panel) | S4 | Por implementar |
| A10 | Especificaciones de test (T01-T20) | [14. Estrategia de Testing](#14-estrategia-de-testing) | S1-S6 | Por implementar |

---

## 3. Tabla de Cumplimiento de Directrices del Proyecto

Esta tabla verifica el cumplimiento de TODAS las directrices aplicables del proyecto para este modulo.

### 3.1 Directrices de Proyecto (v60.0.0)

| ID Regla | Regla | Como se cumple en jaraba_messaging | Seccion del Plan |
|----------|-------|-----------------------------------|------------------|
| CSRF-API-001 | Rutas API via fetch() usan `_csrf_request_header_token: 'TRUE'` | Todas las rutas en routing.yml usan `_csrf_request_header_token`. JS obtiene token de `/session/token` y lo envia como header `X-CSRF-Token`. | [9.2](#92-csrf-en-rutas-api) |
| TWIG-XSS-001 | Contenido usuario en Twig con `\|safe_html`, nunca `\|raw` | Todas las plantillas Twig del chat usan `\|safe_html` para mensajes de usuario. `\|raw` solo para JSON-LD auto-generado. | [9.3](#93-xss-prevention-en-twig) |
| TM-CAST-001 | TranslatableMarkup cast a `(string)` en render arrays | Todos los controladores castean `$this->t()` a `(string)` al asignar a variables de render array. | [9.3](#93-xss-prevention-en-twig) |
| PWA-META-001 | Ambos meta tags apple + standard presentes | No aplica directamente (modulo, no tema). Pero las plantillas de notificacion push respetan ambos meta tags. | N/A |
| TEST-MOCK-001 | Clases final no se mockean; inyectar como `object` | Servicios que dependan de clases contrib final se inyectan como `object` con interfaces temporales en tests. | [14. Testing](#14-estrategia-de-testing) |
| TEST-NS-001 | Interfaces mock temporales con `if (!interface_exists(...))` | Todas las interfaces de mock en tests se envuelven en esta verificacion. | [14. Testing](#14-estrategia-de-testing) |
| TEST-CACHE-001 | Mocks de entidad en AccessControl implementan metadatos cache | Mocks de SecureConversation implementan `getCacheContexts`, `getCacheTags`, `getCacheMaxAge`. | [14. Testing](#14-estrategia-de-testing) |
| DRUPAL-ENTUP-001 | No usar `applyUpdates()`; usar install/update explicito | Update hooks usan `installFieldStorageDefinition()` / `updateFieldStorageDefinition()`. | [6.2](#62-entidades-drupal-content-entities) |
| PB-CAT-001 | Categoria default unificada | No aplica (no es Page Builder template). | N/A |
| ENTITY-APPEND-001 | Entidades append-only sin form edit/delete | La tabla `message_audit_log` es inmutable: no tiene UPDATE ni DELETE, se accede via `MessageAuditService::log()` (append-only). | [9.4](#94-audit-trail-inmutable-hash-chain) |
| CONFIG-SEED-001 | Config seeding via update hook con `Yaml::decode()` + `json_encode()` | Las 5 configuraciones ECA en `config/eca/` se instalan con el modulo. Si se requieren datos seed adicionales, el update hook lee YAML y codifica JSON. | [6.7](#67-plugins-eca) |
| API-FIELD-001 | Campos en `create()` coinciden con `baseFieldDefinitions()` | Los controladores mapean campos del request JSON a campos de entidad exactos: `body` -> `body_encrypted`, `conversation_id` -> FK, etc. Mapeo explicito en cada controlador. | [6.5](#65-controladores-rest-api) |
| STATE-001 | Status values coinciden con `allowed_values` de la entidad | Los 4 status de SecureConversation (`active`, `archived`, `closed`, `deleted`) coinciden exactamente con `allowed_values` en `baseFieldDefinitions()`. Controllers usan valores literales, no genericos. | [6.2](#62-entidades-drupal-content-entities) |
| CRON-FLAG-001 | Cron con flags de notificacion idempotentes | `RetentionCleanupWorker` filtra por `retention_days` expirados. `NotificationBridgeService` usa delay de 30s + re-check online antes de notificar, evitando duplicados. ECA-MSG-003 verifica `last_notification > 4h` antes de enviar digest. | [6.8](#68-queue-workers), [6.7](#67-plugins-eca) |
| PB-PREVIEW-002 | Preview images por vertical | No aplica (no es Page Builder template). | N/A |
| PB-DUP-001 | No duplicar bloques GrapesJS | No aplica (no es Page Builder template). | N/A |

### 3.2 Directrices de Desarrollo (v3.2)

| Seccion | Directriz | Como se cumple | Seccion del Plan |
|---------|-----------|----------------|------------------|
| 1. i18n | Textos traducibles: `{% trans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS | TODOS los textos de interfaz son traducibles. Labels de entidades, mensajes de error, textos de plantillas, tooltips JS. | [8. i18n](#8-internacionalizacion-i18n) |
| 2. SCSS | Archivos SCSS, nunca CSS directo. Variables `var(--ej-*)`. Compilacion npm run build. | El modulo crea `scss/_messaging.scss` con variables CSS Custom Properties. package.json con scripts build. Dart Sass moderno. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| 3. Paleta | 7 colores oficiales Jaraba. `color-mix()` para variantes. NO Tailwind/Material/Bootstrap. | Todos los colores del chat usan la paleta Jaraba: corporate para headers, impulse para CTAs, success para enviados, danger para errores. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| 4. Iconografia | `jaraba_icon('category', 'name', {options})`. NO emojis. Unicode escapes en SCSS. | Iconos de chat (send, attach, reply, reactions) via `jaraba_icon()`. Sin emojis inline. | [7.2 Templates](#72-templates-twig-paginas-y-parciales) |
| 5. SDC | component.yml + twig + scss | La burbuja de mensaje se implementa como parcial Twig (`_message-bubble.html.twig`) con variantes CSS BEM (--own, --other, --system, --ai). Se opta por parciales Twig con BEM en lugar de SDC porque el componente se renderiza server-side dentro de un hilo de mensajes dinámico gestionado por JS, no como un componente Drupal standalone. Si en el futuro se necesita reutilizacion cross-module, se puede migrar a SDC. | [7.2 Templates](#72-templates-twig-paginas-y-parciales) |
| 6. Entidades | Interface, @ContentEntityType, campos base (created, changed, uuid), permisos | SecureConversation y ConversationParticipant como ContentEntityBase con interfaces. Permisos en permissions.yml. | [6.2 Entidades](#62-entidades-drupal-content-entities) |
| 7. APIs | DI tipada, Logger por canal, Config sin hardcodear | Servicios con DI completa. Logger `@logger.channel.jaraba_messaging`. Config via `ConfigFactory`. | [6.4 Servicios](#64-servicios-y-dependency-injection) |
| 8. CI/CD | Trivy skip-dirs, smoke tests con fallback | El modulo no modifica trivy.yaml pero sus tests siguen patrones de CI/CD del proyecto. | [15. Despliegue](#15-verificacion-y-despliegue) |
| 10. Entity Updates | No usar `applyUpdates()` | Todos los update hooks usan install/update explicito. | [6.2 Entidades](#62-entidades-drupal-content-entities) |
| 11. CSRF API | `_csrf_request_header_token` en rutas API via fetch() | Todas las rutas POST/PATCH/DELETE de la API usan este patron. | [9.2 CSRF](#92-csrf-en-rutas-api) |
| 12. Twig XSS | `\|safe_html` para contenido usuario | Plantillas de mensajes: body con `\|safe_html`. Preview con escape automatico de Twig. | [9.3 XSS](#93-xss-prevention-en-twig) |
| 13. PWA | Ambos meta tags presentes | No aplica directamente (responsabilidad del tema). | N/A |
| 14. Entidades Append-Only | Sin form edit/delete, AccessControlHandler restrictivo | `message_audit_log` es append-only: tabla custom sin entity form handlers, acceso solo via `MessageAuditService::log()` (create) y `verifyIntegrity()` (read). No tiene UPDATE ni DELETE. | [9.4](#94-audit-trail-inmutable-hash-chain) |
| 15. Config Seeding | Update hook con `Yaml::decode()` + `json_encode()` + idempotencia | Las configuraciones ECA (`config/eca/*.yml`) se instalan con el modulo. Update hooks futuros verifican existencia antes de crear con `loadByProperties()`. | [6.7](#67-plugins-eca) |
| 16. Booking API Field Mapping | Campos en `create()` coinciden con `baseFieldDefinitions()` | No aplica directamente (no es booking entity). Pero el principio se respeta: campos del request JSON se mapean explicitamente a campos de entidad en los controladores. | [6.5](#65-controladores-rest-api) |
| 17. State Machine | Status values coinciden con `allowed_values` | Los 4 status de `SecureConversation` (`active`, `archived`, `closed`, `deleted`) y los 5 status de `ConversationParticipant` (`active`, `left`, `removed`, `blocked`) coinciden con sus `allowed_values`. | [6.2](#62-entidades-drupal-content-entities) |
| 18. Cron Flags | Notificaciones filtran por flag NOT sent, marcan TRUE tras enviar | `RetentionCleanupWorker` filtra mensajes expirados por `retention_days`. Las notificaciones ECA verifican `last_notification` timestamp antes de enviar. Sin duplicados. | [6.8](#68-queue-workers) |

### 3.3 Arquitectura de Theming SaaS (v2.1)

| Principio | Como se cumple | Seccion del Plan |
|-----------|----------------|------------------|
| SSOT: Variables solo en ecosistema_jaraba_core | El modulo NO define variables `$ej-*`. Solo consume `var(--ej-*, fallback)`. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| Modulos solo consumen CSS Custom Properties | `_messaging.scss` usa exclusivamente `var(--ej-color-corporate, #233D63)` etc. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| package.json obligatorio | El modulo incluye package.json con scripts build/watch. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| Dart Sass (no LibSass) | devDependencies: `"sass": "^1.83.0"`. Usa `@use 'sass:color'` en lugar de `darken()`. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| Header de documentacion SCSS | Cada archivo SCSS principal tiene header con directriz y comando de compilacion. | [7.3 SCSS](#73-scss-modelo-federated-design-tokens) |
| Inyeccion de variables desde UI Drupal | Los colores del chat se pueden personalizar desde theme settings sin codigo. | [11. Configuracion](#11-configuracion-administrable-desde-ui-de-drupal) |

### 3.4 Flujo de Trabajo Claude (v14.0.0)

| Regla de Oro | Como se cumple | Seccion del Plan |
|-------------|----------------|------------------|
| 1. No hardcodear | Rate limits, retencion, puertos WS, etc. via Config Entities o State API. | [11. Configuracion](#11-configuracion-administrable-desde-ui-de-drupal) |
| 2. Inmutabilidad financiera | Audit log append-only con hash chain SHA-256. | [9.4 Audit Trail](#94-audit-trail-inmutable-hash-chain) |
| 3. Deteccion proactiva | ECA-MSG-005 notifica al profesional de primer mensaje; ECA-MSG-007 alerta SLA. | [6.7 Plugins ECA](#67-plugins-eca) |
| 4. Tenant isolation | `tenant_id` obligatorio en TODA query. Middleware de aislamiento. | [9.6 Tenant Isolation](#96-tenant-isolation) |
| 5. Mocking seguro | Value Objects final usados directamente; DI flexible para clases contrib. | [14. Testing](#14-estrategia-de-testing) |
| 6. DI flexible | Type hint `object` para dependencias final de contrib. | [6.4 Servicios](#64-servicios-y-dependency-injection) |
| 7. Documentar siempre | Este documento + PHPDoc en cada servicio + JSDoc en componentes React. | Todo el plan |
| 8. Privacidad diferencial | Los mensajes de conversaciones `is_confidential` NO se indexan en Qdrant. | [9. Seguridad](#9-seguridad) |
| 11. CSRF header en APIs | `_csrf_request_header_token: 'TRUE'` en todas las rutas API POST/PATCH/DELETE. | [9.2 CSRF](#92-csrf-en-rutas-api) |
| 12. Sanitizar contenido | `\|safe_html` en Twig, `Html::escape()` en emails, `(string)` cast para TranslatableMarkup. | [9.3 XSS](#93-xss-prevention-en-twig) |
| 13. Entidades append-only inmutables | `message_audit_log` es append-only: no tiene form handlers de edicion, no permite UPDATE ni DELETE. Acceso solo via `MessageAuditService`. | [9.4 Audit Trail](#94-audit-trail-inmutable-hash-chain) |
| 14. Config seeding con JSON encode | Configuraciones ECA y settings se instalan con el modulo. Si se requieren datos seed adicionales, el update hook usa `Yaml::decode()` + `json_encode()` para campos complejos, con verificacion de existencia para idempotencia. | [6.7 Plugins ECA](#67-plugins-eca) |
| 15. Status values exactos | Los status de SecureConversation (`active`/`archived`/`closed`/`deleted`) y ConversationParticipant (`active`/`left`/`removed`/`blocked`) coinciden exactamente con `allowed_values` de la entidad. Los controladores nunca usan valores genericos. | [6.2 Entidades](#62-entidades-drupal-content-entities) |

---

## 4. Requisitos Previos

### 4.1 Software Requerido

| Software | Version Minima | Proposito | Estado en el Proyecto |
|----------|----------------|-----------|----------------------|
| PHP | 8.4 | Backend Drupal 11 + openssl_encrypt/decrypt | Configurado en .lando.yml |
| Drupal | 11.x | Framework CMS + Content Entities + REST API | Instalado |
| MariaDB | 10.11+ | Almacenamiento de entidades y mensajes cifrados | Configurado en .lando.yml |
| Redis | 7.x | Pub/Sub, presencia, typing, rate limiting, cache | Configurado en .lando.yml (puerto 6379) |
| Node.js | 18+ (LTS) | Compilacion SCSS con Dart Sass | Disponible via NVM en WSL |
| Dart Sass | 1.83+ | Compilacion SCSS sin funciones deprecadas | En devDependencies de package.json |
| Ratchet PHP | 0.4.x | Servidor WebSocket en desarrollo | Por instalar via Composer |
| Lando | 3.x | Entorno de desarrollo local Docker | Configurado (.lando.yml) |

### 4.2 Modulos Drupal Requeridos

| Modulo | Tipo | Proposito | Estado |
|--------|------|-----------|--------|
| `ecosistema_jaraba_core` | Custom | SSOT de tokens, servicios base, entidades core | Instalado |
| `jaraba_theming` | Custom | Design Tokens, preprocess_html, inyeccion CSS vars | Instalado |
| `jaraba_notifications` (doc 98) | Custom | Puente para notificaciones offline (Email, SMS, Push) | Debe estar operativo |
| `group` | Contrib | Multi-tenancy via Group Module | Instalado |
| `eca` | Contrib | Event-Condition-Action para flujos automatizados | Instalado |
| `jwt` | Contrib | Autenticacion JWT para WebSocket handshake | Por verificar/instalar |

### 4.3 Servicios de Infraestructura

| Servicio | Proposito | Configuracion |
|----------|-----------|---------------|
| Redis 7 con Pub/Sub | Distribucion de mensajes cross-proceso, presencia, typing | Ya operativo en Lando (puerto 6379) |
| Qdrant | Indexacion semantica de mensajes para RAG del Copilot | Ya operativo en Lando (puerto 6333) |
| Tika | Procesamiento de adjuntos (extraccion texto) | Ya operativo en Lando (puerto 9998) |

### 4.4 Dependencias de Otros Documentos

| Documento | Estado Requerido | Impacto si No Disponible |
|-----------|-----------------|--------------------------|
| doc 88 (Buzon de Confianza) | Desplegado: cifrado AES-256-GCM funcional | **BLOQUEANTE**: sin cifrado ni adjuntos seguros |
| doc 98 (Notificaciones Multicanal) | Desplegado: al menos email + push operativos | **DEGRADADO**: sin notificaciones offline (solo WS) |
| doc 94 (Dashboard Profesional) | Desplegado con WebSocket funcional | **PARCIAL**: se puede desplegar WS independiente |
| doc 90 (Portal Cliente) | Desplegado con framework de tabs | **PARCIAL**: chat funciona standalone sin tab en portal |
| doc 93 (Copilot Servicios) | Desplegado con RAG pipeline | **NO BLOQUEANTE**: chat funciona sin IA; se integra cuando este listo |

---

## 5. Entorno de Desarrollo

### 5.1 Comandos Dentro del Contenedor Docker

Conforme a las directrices del proyecto, todos los comandos se ejecutan dentro del contenedor de Docker via Lando:

```bash
# Acceder al contenedor
lando ssh

# Limpiar cache despues de cambios
lando drush cr

# Importar configuracion
lando import-config

# Exportar configuracion
lando export-config

# Instalar modulo
lando drush en jaraba_messaging -y

# Ejecutar tests del modulo
lando ssh -c "cd /app && vendor/bin/phpunit web/modules/custom/jaraba_messaging/tests/"

# Compilar SCSS del modulo
lando ssh -c "cd /app/web/modules/custom/jaraba_messaging && npx sass scss/main.scss css/jaraba-messaging.css --style=compressed"

# Estado de Redis
lando redis-status
```

### 5.2 URL de Desarrollo

| Recurso | URL |
|---------|-----|
| SaaS principal | https://jaraba-saas.lndo.site/ |
| Mensajeria (frontend) | https://jaraba-saas.lndo.site/messaging |
| Admin mensajeria | https://jaraba-saas.lndo.site/admin/config/jaraba/messaging |
| Estructura conversaciones | https://jaraba-saas.lndo.site/admin/structure/secure-conversation |
| Listado conversaciones (admin) | https://jaraba-saas.lndo.site/admin/content/conversations |
| API base | https://jaraba-saas.lndo.site/api/v1/messaging/ |
| WebSocket (desarrollo) | wss://jaraba-saas.lndo.site:8090 |
| PHPMyAdmin | http://jaraba-saas.lndo.site:8080 |
| Mailhog (emails de test) | http://jaraba-saas.lndo.site:8025 |

---

## 6. Arquitectura del Modulo

### 6.1 Estructura de Archivos Completa

La estructura sigue el patron estandar del ecosistema Jaraba y las convenciones de doc 178A:

```
web/modules/custom/jaraba_messaging/
|-- jaraba_messaging.info.yml                    # [A1] Metadata del modulo
|-- jaraba_messaging.module                      # Hooks: schema, install, preprocess
|-- jaraba_messaging.services.yml                # [A2] DI completa de servicios
|-- jaraba_messaging.permissions.yml             # [A4] Permisos granulares
|-- jaraba_messaging.routing.yml                 # [A3] Rutas REST + admin + frontend
|-- jaraba_messaging.links.menu.yml              # Links en menu admin
|-- jaraba_messaging.links.task.yml              # Tabs en paginas admin
|-- jaraba_messaging.links.action.yml            # Botones de accion (crear conversacion)
|-- jaraba_messaging.libraries.yml               # Librerias CSS/JS
|-- package.json                                 # Build SCSS (Dart Sass)
|-- src/
|   |-- Entity/
|   |   |-- SecureConversation.php               # [A5.1] Content Entity
|   |   |-- SecureConversationInterface.php      # Interface de la entidad
|   |   |-- ConversationParticipant.php          # Content Entity (union)
|   |   |-- ConversationParticipantInterface.php # Interface
|   |-- Model/
|   |   |-- SecureMessageDTO.php                 # [A5.2] DTO para mensajes
|   |   |-- EncryptedPayload.php                 # Value Object cifrado
|   |   |-- IntegrityReport.php                  # Value Object para verificacion
|   |-- Service/
|   |   |-- MessagingService.php                 # Orquestador central
|   |   |-- MessagingServiceInterface.php        # [A6] Interface
|   |   |-- ConversationService.php              # CRUD conversaciones
|   |   |-- ConversationServiceInterface.php     #
|   |   |-- MessageService.php                   # CRUD mensajes (custom SQL)
|   |   |-- MessageServiceInterface.php          #
|   |   |-- MessageEncryptionService.php         # Cifrado/descifrado AES-256-GCM
|   |   |-- MessageEncryptionServiceInterface.php# [A6]
|   |   |-- MessageAuditService.php              # Audit log con hash chain
|   |   |-- MessageAuditServiceInterface.php     # [A6]
|   |   |-- NotificationBridgeService.php        # Puente a doc 98
|   |   |-- AttachmentBridgeService.php          # Puente a doc 88
|   |   |-- PresenceService.php                  # Online/typing via Redis
|   |   |-- PresenceServiceInterface.php         # [A6]
|   |   |-- SearchService.php                    # Full-text + Qdrant semantico
|   |   |-- SearchServiceInterface.php           # [A6]
|   |   |-- RetentionService.php                 # Limpieza RGPD programada
|   |   |-- TenantKeyService.php                 # Derivacion clave tenant
|   |-- Controller/
|   |   |-- ConversationController.php           # REST API conversaciones
|   |   |-- MessageController.php                # REST API mensajes
|   |   |-- PresenceController.php               # REST API presencia
|   |   |-- SearchController.php                 # REST API busqueda
|   |   |-- ExportController.php                 # RGPD art. 20 export
|   |   |-- AuditController.php                  # REST API audit log
|   |   |-- MessagingPageController.php          # Controlador de pagina frontend
|   |-- Access/
|   |   |-- SecureConversationAccessControlHandler.php
|   |   |-- ConversationAccessCheck.php          # Verifica participante
|   |   |-- ConversationOwnerAccessCheck.php     # Verifica owner
|   |   |-- MessageSendAccessCheck.php           # Verifica can_send
|   |   |-- MessageOwnerAccessCheck.php          # Verifica sender
|   |   |-- AttachmentAccessCheck.php            # Verifica can_attach
|   |   |-- AuditAccessCheck.php                 # Verifica owner o admin
|   |-- WebSocket/
|   |   |-- MessagingWebSocketServer.php         # Servidor WS Ratchet
|   |   |-- ConnectionManager.php                # Pool conexiones activas
|   |   |-- MessageHandler.php                   # Procesamiento frames WS
|   |   |-- AuthMiddleware.php                   # Validacion JWT en handshake
|   |-- Commands/
|   |   |-- JarabaMessagingCommands.php          # Drush command: ws-start
|   |-- Plugin/
|   |   |-- ECA/
|   |   |   |-- Event/
|   |   |   |   |-- MessageSentEvent.php         # Evento: mensaje enviado
|   |   |   |   |-- MessageReadEvent.php         # Evento: mensaje leido
|   |   |   |   |-- ConversationCreatedEvent.php # Evento: conversacion creada
|   |   |   |-- Condition/
|   |   |   |   |-- IsFirstMessage.php           # Condicion: primer mensaje
|   |   |   |   |-- RecipientNotOnline.php       # Condicion: destinatario offline
|   |   |   |   |-- NotificationNotMuted.php     # Condicion: no silenciado
|   |   |   |-- Action/
|   |   |       |-- SendAutoReply.php            # Accion: auto-respuesta
|   |   |       |-- SendNotification.php         # Accion: disparar notificacion
|   |-- Queue/
|   |   |-- MessageIndexWorker.php               # Indexacion Qdrant asincrona
|   |   |-- RetentionCleanupWorker.php           # Limpieza RGPD programada
|   |-- Form/
|   |   |-- MessagingSettingsForm.php            # Form admin de configuracion
|   |   |-- SecureConversationForm.php           # Form de entidad (admin)
|   |-- ListBuilder/
|   |   |-- SecureConversationListBuilder.php    # Listado admin de conversaciones
|   |-- Exception/
|       |-- RateLimitException.php
|       |-- AccessDeniedException.php
|       |-- EncryptionException.php
|       |-- DecryptionException.php
|       |-- IntegrityException.php
|       |-- EditWindowExpiredException.php
|-- js/
|   |-- messaging-client.js                      # WebSocket client + cifrado
|   |-- chat-panel.js                            # UI del panel de chat (vanilla JS + Drupal behaviors)
|   |-- conversation-list.js                     # Lista de conversaciones
|   |-- message-composer.js                      # Compositor de mensajes
|   |-- dist/                                    # JS minificado
|       |-- messaging-client.min.js
|       |-- chat-panel.min.js
|-- scss/
|   |-- main.scss                                # Entry point SCSS
|   |-- _messaging-panel.scss                    # Panel lateral deslizable
|   |-- _conversation-list.scss                  # Lista de conversaciones
|   |-- _message-thread.scss                     # Hilo de mensajes
|   |-- _message-bubble.scss                     # Burbuja individual
|   |-- _message-composer.scss                   # Area de escritura
|   |-- _typing-indicator.scss                   # Indicador de escritura
|   |-- _presence-badge.scss                     # Badge online/offline
|   |-- _attachment-preview.scss                 # Preview de adjuntos
|   |-- _search-overlay.scss                     # Busqueda en conversacion
|   |-- _notification-toast.scss                 # Toast de mensaje nuevo
|-- css/
|   |-- jaraba-messaging.css                     # Output compilado
|-- templates/
|   |-- page--messaging.html.twig                # Pagina frontend limpia (full-width)
|   |-- partials/
|   |   |-- _chat-panel.html.twig                # Parcial: panel de chat
|   |   |-- _conversation-list.html.twig         # Parcial: lista conversaciones
|   |   |-- _message-thread.html.twig            # Parcial: hilo de mensajes
|   |   |-- _message-bubble.html.twig            # Parcial: burbuja de mensaje
|   |   |-- _message-composer.html.twig          # Parcial: compositor
|   |   |-- _typing-indicator.html.twig          # Parcial: typing dots
|   |   |-- _conversation-widget.html.twig       # Widget para dashboard
|   |-- email/
|       |-- message-notification.html.twig       # Email de notificacion offline
|-- config/
|   |-- install/
|   |   |-- jaraba_messaging.settings.yml        # [A7.2] Config por defecto
|   |-- schema/
|   |   |-- jaraba_messaging.schema.yml          # [A7.1] Schema tipado
|   |-- eca/
|       |-- eca.model.message_offline_notification.yml    # ECA-MSG-001
|       |-- eca.model.auto_reply_off_hours.yml            # ECA-MSG-002
|       |-- eca.model.unread_reminder.yml                 # ECA-MSG-003
|       |-- eca.model.auto_close.yml                      # ECA-MSG-004
|       |-- eca.model.first_message.yml                   # ECA-MSG-005
|       |-- eca.model.legal_attachment.yml                 # ECA-MSG-006
|       |-- eca.model.sla_alert.yml                       # ECA-MSG-007
|       |-- eca.model.copilot_integration.yml             # ECA-MSG-008
|-- tests/
    |-- src/
        |-- Unit/
        |   |-- MessageEncryptionServiceTest.php          # T01-T03
        |   |-- MessageAuditServiceTest.php               # T08-T09
        |   |-- PresenceServiceTest.php
        |-- Kernel/
        |   |-- ConversationServiceTest.php               # T04-T06
        |   |-- MessagingServiceTest.php                  # T07
        |   |-- RateLimitTest.php
        |-- Functional/
            |-- ConversationApiTest.php                   # T10-T17
            |-- SearchApiTest.php
            |-- ExportApiTest.php
```

**Nota sobre la decision de arquitectura JS:** Aunque doc 178 menciona React para el Chat Panel, la implementacion usara vanilla JavaScript con Drupal behaviors por coherencia con el resto del ecosistema Jaraba. El panel de chat se implementa como componente Drupal con Drupal.behaviors, no como aplicacion React independiente. Esto evita introducir una dependencia de build (webpack/vite para JSX) que no existe en el proyecto. La complejidad de UI del chat (lista de conversaciones, hilo de mensajes, compositor) es manejable con JS vanilla + templates Twig parciales, que es el patron establecido en el SaaS (ver: copilot-fab, content-hub-dashboard, mobile-menu).

### 6.2 Entidades Drupal (Content Entities)

Las siguientes entidades usan `ContentEntityBase` con `baseFieldDefinitions()`, lo que les da acceso completo a Field UI, Views, REST, y la estructura de navegacion de Drupal:

#### SecureConversation

```php
/**
 * Notas de implementacion:
 *
 * - Extiende ContentEntityBase para integracion con Field UI y Views
 * - Implementa EntityChangedInterface para tracking de cambios
 * - Handlers: view_builder, list_builder (admin), access control, forms
 * - Links: canonical, collection (admin/content/conversations),
 *   edit-form, delete-form
 * - admin_permission para acceso al listado admin
 * - entity_keys: id, uuid, label (title)
 * - La entidad aparece en /admin/structure/secure-conversation (gestion)
 *   y en /admin/content/conversations (listado)
 *
 * Campos base: tenant_id, title, conversation_type, context_type,
 * context_id, initiated_by, encryption_key_id, is_confidential,
 * max_participants, is_archived, is_muted_by_system, last_message_at,
 * last_message_preview, last_message_sender_id, message_count,
 * participant_count, metadata, retention_days, auto_close_days,
 * status, created, changed
 *
 * Todos los labels usan $this->t() para i18n.
 */
```

#### ConversationParticipant

```php
/**
 * Notas de implementacion:
 *
 * - Content Entity de union (conversacion <-> usuario)
 * - Sin UI propia (gestionada via ConversationService)
 * - Constraint UNIQUE: (conversation_id, user_id)
 *
 * Campos base: conversation_id, user_id, role, display_name,
 * can_send, can_attach, can_invite, is_muted, is_pinned,
 * last_read_at, last_read_message_id, unread_count,
 * notification_pref, joined_at, left_at, removed_by, status
 */
```

**Integracion con Field UI y Views:**

Las entidades Content Entity se integran automaticamente con la infraestructura de Drupal:

- **Field UI:** Campos adicionales se pueden anadir desde `/admin/structure/secure-conversation/fields` sin codigo
- **Views:** Listados custom se crean desde la UI de Views (filtros, ordenacion, exposed filters)
- **REST:** Endpoint automatico via REST module si se habilita
- **Search API:** Indexable para busqueda avanzada

### 6.3 Entidades Custom Schema (hook_schema)

Las siguientes tablas usan `hook_schema()` porque requieren tipos de columna no soportados por `BaseFieldDefinition`:

- **secure_message:** `MEDIUMBLOB` para body_encrypted, `BIGSERIAL` para alto volumen, `VARBINARY` para IV/tag
- **message_audit_log:** `DATETIME(6)` microsegundos, `CHAR(64)` hash chain
- **message_read_receipt:** Tabla simple de read receipts

Estas tablas se gestionan via el service layer (`MessageService`, `MessageAuditService`) con DTOs (`SecureMessageDTO`), NO via Content Entity API. El acceso a datos se hace siempre a traves de los servicios inyectados, nunca con queries directas.

**Importante:** Conforme a DRUPAL-ENTUP-001, los update hooks para estas tablas usan `db_add_field()` / `db_change_field()` explicitamente, nunca `applyUpdates()`.

### 6.4 Servicios y Dependency Injection

Todos los servicios siguen el patron de DI estricta con:

- **Interfaces PHP** para cada servicio (doc 178A seccion A6)
- **Type hints** explicitos en constructores
- **Logger** dedicado: `@logger.channel.jaraba_messaging`
- **Config** via `@config.factory` (nunca hardcoded)
- **DI flexible** para dependencias final de contrib (type hint `object` donde sea necesario, conforme a regla de oro 6)

#### Mapa de servicios (jaraba_messaging.services.yml)

| Servicio | Clase | Dependencias Principales | Sprint |
|----------|-------|--------------------------|--------|
| `jaraba_messaging.messaging` | MessagingService | Todos los sub-servicios (orquestador) | S2 |
| `jaraba_messaging.conversation` | ConversationService | entity_type.manager, tenant.context, current_user, database | S1 |
| `jaraba_messaging.message` | MessageService | entity_type.manager, encryption, database | S1 |
| `jaraba_messaging.encryption` | MessageEncryptionService | tenant_key, logger | S1 |
| `jaraba_messaging.tenant_key` | TenantKeyService | tenant.context, config.factory | S1 |
| `jaraba_messaging.audit` | MessageAuditService | entity_type.manager, current_user, request_stack, database, logger | S1 |
| `jaraba_messaging.notification_bridge` | NotificationBridgeService | conversation, presence, jaraba_notifications.notification, queue | S2 |
| `jaraba_messaging.attachment_bridge` | AttachmentBridgeService | jaraba_buzon_confianza.vault (opcional), audit, logger | S5 |
| `jaraba_messaging.presence` | PresenceService | jaraba_core.redis, tenant.context | S3 |
| `jaraba_messaging.websocket_server` | MessagingWebSocketServer | messaging, presence, connection_manager, logger | S3 |
| `jaraba_messaging.connection_manager` | ConnectionManager | jaraba_core.redis | S3 |
| `jaraba_messaging.search` | SearchService | encryption, jaraba_ai.rag (Qdrant), database, queue, tenant.context | S6 |
| `jaraba_messaging.retention` | RetentionService | entity_type.manager, audit, database, config.factory, logger | S5 |

**Inyeccion opcional:** `AttachmentBridgeService` usa `@?jaraba_buzon_confianza.vault` (inyeccion condicional) para funcionalidad degradada si el Buzon no esta instalado.

**Servicios de acceso:** Los 7 Access Check classes (`ConversationAccessCheck`, `ConversationOwnerAccessCheck`, `MessageSendAccessCheck`, `MessageOwnerAccessCheck`, `AttachmentAccessCheck`, `AuditAccessCheck`) se registran como servicios taggeados en `services.yml` con tag `access_check`. El `SecureConversationAccessControlHandler` se registra via la anotacion `@ContentEntityType`.

**Comando Drush:** La clase `JarabaMessagingCommands` (en `src/Commands/`) implementa `DrushCommands` con el comando `jaraba-messaging:ws-start` para arrancar el servidor WebSocket desde CLI.

### 6.5 Controladores REST API

Todos los endpoints de API siguen las convenciones del proyecto:

- **CSRF:** Rutas POST/PATCH/DELETE usan `_csrf_request_header_token: 'TRUE'` (directriz CSRF-API-001)
- **Auth:** `_auth: ['jwt_auth', 'cookie']` para soporte dual
- **Format:** `_format: 'json'` en requirements donde aplica
- **Access:** Custom access checks por participacion, ownership, o admin
- **Rate limiting:** Implementado a nivel de servicio (no de ruta) via Redis counters

Los endpoints siguen la especificacion exacta de doc 178 secciones 7.1-7.4 (ver tabla de correspondencia).

**Nota sobre rutas frontend:** Ademas de la API REST, el modulo define rutas de pagina frontend:

```yaml
# Ruta de pagina frontend limpia (Zero-Region Policy)
jaraba_messaging.messaging_page:
  path: '/messaging'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessagingPageController::page'
    _title: 'Messaging'
  requirements:
    _permission: 'use jaraba messaging'
```

Esta ruta usa un template Twig limpio (`page--messaging.html.twig`) sin regiones ni bloques de Drupal, con header, navegacion y footer propios del tema via `{% include %}` de parciales.

### 6.6 Servidor WebSocket

El servidor WebSocket se implementa con Ratchet PHP 0.4 para desarrollo y esta preparado para migracion a Swoole 5.x en produccion:

- **Inicio:** Comando Drush custom: `drush jaraba-messaging:ws-start`
- **Puerto:** Configurable via UI (default 8090)
- **Auth:** JWT validation en handshake (query param `?token=xxx`)
- **Protocolo:** JSON estandarizado (doc 178 seccion 6.2)
- **Redis Subscriber:** Escucha canales `conv:{id}` y `user:{id}` para broadcasts cross-proceso
- **Reconnect:** Auto-reconnect en cliente con backoff exponencial (1s, 2s, 4s, 8s, max 30s)
- **Keepalive:** Ping/pong cada 30s configurable

### 6.7 Plugins ECA

8 flujos ECA definidos en doc 178 seccion 9:

| ID | Nombre | Trigger | Sprint |
|----|--------|---------|--------|
| ECA-MSG-001 | Notificacion offline | message.sent + destinatario offline | S2 |
| ECA-MSG-002 | Auto-respuesta fuera de horario | message.sent + fuera de horario | S5 |
| ECA-MSG-003 | Recordatorio no leidos | cron (cada 4h) | S5 |
| ECA-MSG-004 | Auto-cierre por inactividad | cron (diario) | S5 |
| ECA-MSG-005 | Primer mensaje de cliente | message.sent + primer mensaje | S5 |
| ECA-MSG-006 | Adjunto legal | message.sent + attachment + case_linked | S5 |
| ECA-MSG-007 | Alerta SLA de respuesta | cron (cada 30min) | S5 |
| ECA-MSG-008 | Integracion Copilot | message.sent + AI enabled | S6 |

### 6.8 Queue Workers

| Worker | Cola | Proposito | Sprint |
|--------|------|-----------|--------|
| `MessageIndexWorker` | `jaraba_messaging_index` | Descifra mensaje, genera embedding, upsert en Qdrant | S6 |
| `RetentionCleanupWorker` | `jaraba_messaging_retention` | Purga mensajes expirados segun politica RGPD del tenant | S5 |

---

## 7. Arquitectura Frontend

### 7.1 Principios de Frontend Limpio (Zero-Region Policy)

El frontend del modulo sigue estrictamente la filosofia del SaaS:

1. **Sin `{{ page.content }}` ni bloques heredados:** La pagina de mensajeria `/messaging` usa un template Twig limpio que NO renderiza regiones de Drupal.
2. **Layout full-width mobile-first:** El contenido ocupa el 100% del ancho disponible, con responsive breakpoints definidos en los tokens del tema.
3. **Header, navegacion y footer propios del tema:** Se incluyen via `{% include %}` de parciales existentes del tema (`_header.html.twig`, `_footer.html.twig`).
4. **Modales para todas las acciones CRUD:** Crear conversacion, ver detalles, editar mensaje, eliminar, archivar: todo se abre en modal sin abandonar la pagina.
5. **Sin sidebar admin para tenants:** Solo el super-admin ve la sidebar de administracion. Los tenants operan en rutas frontend limpias.
6. **Body classes via hook_preprocess_html():** Las clases del body NO se anaden con `attributes.addClass()` (que no funciona para `<body>`). Se usa `hook_preprocess_html()` del modulo `jaraba_theming`.

### 7.2 Templates Twig: Paginas y Parciales

#### Pagina principal: page--messaging.html.twig

```twig
{#
 # @file
 # Pagina frontend limpia para el sistema de mensajeria segura.
 # Zero-Region Policy: sin {{ page.content }} ni bloques de Drupal.
 # Mobile-first, full-width layout.
 #
 # Variables inyectadas desde MessagingPageController:
 #   - conversations: Lista de conversaciones del usuario
 #   - unread_total: Total de mensajes no leidos
 #   - websocket_url: URL del servidor WebSocket
 #   - current_user_id: UID del usuario actual
 #   - tenant_id: ID del tenant activo
 #   - theme_settings: Configuracion del tema para parciales
 #}

{# Header del tema (parcial reutilizable) #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  theme_settings: theme_settings,
  navigation_items: navigation_items,
  is_authenticated: is_authenticated,
  current_user: current_user,
} %}

<main class="messaging-page" role="main" id="main-content">
  <div class="messaging-layout">

    {# Panel izquierdo: lista de conversaciones #}
    {% include '@jaraba_messaging/partials/_conversation-list.html.twig' with {
      conversations: conversations,
      unread_total: unread_total,
    } %}

    {# Panel derecho: hilo de mensajes (vacio hasta seleccionar conversacion) #}
    <div class="messaging-thread-container"
         data-ws-url="{{ websocket_url }}"
         data-user-id="{{ current_user_id }}"
         data-tenant-id="{{ tenant_id }}"
         data-api-base="/api/v1/messaging"
         aria-label="{% trans %}Conversation thread{% endtrans %}"
         role="region">

      {# Estado vacio #}
      <div class="messaging-empty-state">
        {{ jaraba_icon('ui', 'message', { size: '48px' }) }}
        <h2>{% trans %}Select a conversation{% endtrans %}</h2>
        <p>{% trans %}Choose a conversation from the list to start messaging{% endtrans %}</p>
      </div>

      {# Contenedor para hilo de mensajes (renderizado via JS) #}
      <div class="messaging-thread" style="display:none;">
        {% include '@jaraba_messaging/partials/_message-thread.html.twig' %}
      </div>

      {# Compositor de mensajes #}
      {% include '@jaraba_messaging/partials/_message-composer.html.twig' %}
    </div>

  </div>
</main>

{# Footer del tema (parcial reutilizable) #}
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  theme_settings: theme_settings,
} %}
```

**Revision de parciales existentes:** Antes de crear un parcial nuevo, se verifica si ya existe uno reutilizable:

- `_header.html.twig` (dispatcher con variantes classic/minimal/transparent) -- **EXISTE, se reutiliza**
- `_footer.html.twig` (variantes minimal/standard/mega/split) -- **EXISTE, se reutiliza**
- `_copilot-fab.html.twig` (boton IA contextual) -- **EXISTE, se reutiliza** si el Copilot esta habilitado

#### Parciales nuevos del modulo:

| Parcial | Proposito | Reutilizable en |
|---------|-----------|-----------------|
| `_chat-panel.html.twig` | Panel lateral slide-in (para integracion en otras paginas) | Dashboard, Portal Cliente, Detalle Expediente |
| `_conversation-list.html.twig` | Lista de conversaciones con badges | Pagina messaging, Widget dashboard |
| `_message-thread.html.twig` | Hilo de mensajes con scroll virtual | Pagina messaging, Modal |
| `_message-bubble.html.twig` | Burbuja individual de mensaje | Dentro de message-thread |
| `_message-composer.html.twig` | Area de escritura con adjuntos | Pagina messaging, Panel slide-in |
| `_typing-indicator.html.twig` | Animacion "esta escribiendo..." | Dentro de message-thread |
| `_conversation-widget.html.twig` | Widget compacto de mensajes no leidos | Dashboard profesional |

**Textos traducibles en Twig:** Todos los textos usan `{% trans %}...{% endtrans %}` conforme a la directriz i18n (seccion 1 de Directrices de Desarrollo v3.2). Ejemplo:

```twig
{# CORRECTO: Texto traducible #}
<h2>{% trans %}Select a conversation{% endtrans %}</h2>

{# INCORRECTO: No usar |t en Twig #}
<h2>{{ 'Select a conversation'|t }}</h2>
```

**Iconografia:** Todos los iconos usan `{{ jaraba_icon('category', 'name', { options }) }}` conforme a la directriz de iconos (seccion 4 de Directrices de Desarrollo v3.2). Categorias relevantes:

| Icono | Llamada | Uso |
|-------|---------|-----|
| Enviar | `{{ jaraba_icon('actions', 'send', { size: '20px' }) }}` | Boton enviar mensaje |
| Adjuntar | `{{ jaraba_icon('actions', 'attach', { size: '20px' }) }}` | Boton adjuntar archivo |
| Responder | `{{ jaraba_icon('actions', 'reply', { size: '16px' }) }}` | Accion responder |
| Buscar | `{{ jaraba_icon('ui', 'search', { size: '20px' }) }}` | Busqueda en conversacion |
| Mensaje | `{{ jaraba_icon('ui', 'message', { size: '20px' }) }}` | Icono generico mensajeria |
| Online | `{{ jaraba_icon('ui', 'online', { size: '10px' }) }}` | Indicador presencia |
| Lock | `{{ jaraba_icon('ui', 'lock', { size: '14px' }) }}` | Conversacion confidencial |
| Archivo | `{{ jaraba_icon('general', 'file', { size: '16px' }) }}` | Adjunto generico |

### 7.3 SCSS: Modelo Federated Design Tokens

El modulo sigue estrictamente la arquitectura de theming v2.1:

#### Regla SSOT: El modulo NO define variables `$ej-*`

```scss
/**
 * @file
 * Estilos del sistema de mensajeria segura.
 *
 * DIRECTRIZ: Usa Design Tokens con CSS Custom Properties (var(--ej-*))
 * NO definir variables $ej-* localmente (SSOT en ecosistema_jaraba_core)
 *
 * COMPILACION:
 * lando ssh -c "cd /app/web/modules/custom/jaraba_messaging && npx sass scss/main.scss css/jaraba-messaging.css --style=compressed"
 */

// === PANEL DE MENSAJERIA ===
.messaging-page {
  display: grid;
  grid-template-columns: 320px 1fr;
  height: calc(100vh - 64px); // Resta altura del header
  background: var(--ej-bg-body, #F8FAFC);

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

// === LISTA DE CONVERSACIONES ===
.conversation-list {
  background: var(--ej-bg-surface, #FFFFFF);
  border-right: 1px solid var(--ej-border-color, #E5E7EB);
  overflow-y: auto;

  &__header {
    padding: var(--ej-spacing-md, 1rem);
    border-bottom: 1px solid var(--ej-border-color, #E5E7EB);
  }

  &__search {
    width: 100%;
    padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
    border: 1px solid var(--ej-border-color, #E5E7EB);
    border-radius: var(--ej-btn-radius, 8px);
    font-family: var(--ej-font-body, 'Outfit', sans-serif);
  }
}

// === BURBUJA DE MENSAJE ===
.message-bubble {
  max-width: 70%;
  padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
  border-radius: var(--ej-border-radius, 12px);
  margin-bottom: var(--ej-spacing-xs, 0.25rem);
  font-family: var(--ej-font-body, 'Outfit', sans-serif);
  font-size: var(--ej-font-size-base, 16px);

  // Mensaje propio (alineado a la derecha)
  &--own {
    background: color-mix(in srgb, var(--ej-color-corporate, #233D63) 10%, transparent);
    color: var(--ej-color-body, #334155);
    margin-left: auto;
    border-bottom-right-radius: 4px;
  }

  // Mensaje ajeno (alineado a la izquierda)
  &--other {
    background: var(--ej-bg-surface, #FFFFFF);
    color: var(--ej-color-body, #334155);
    border: 1px solid var(--ej-border-color, #E5E7EB);
    border-bottom-left-radius: 4px;
  }

  // Mensaje de sistema
  &--system {
    max-width: 90%;
    margin: var(--ej-spacing-sm, 0.5rem) auto;
    text-align: center;
    font-size: 0.85rem;
    color: var(--ej-color-muted, #64748B);
    background: transparent;
  }

  // Estado: enviando
  &--sending {
    opacity: var(--ej-hover-opacity, 0.85);
  }

  // Estado: error
  &--failed {
    border-color: var(--ej-color-danger, #EF4444);
  }
}

// === INDICADOR DE ESCRITURA ===
.typing-indicator {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-xs, 0.25rem);
  padding: var(--ej-spacing-xs, 0.25rem) var(--ej-spacing-md, 1rem);
  color: var(--ej-color-muted, #64748B);
  font-size: 0.85rem;

  &__dots {
    display: flex;
    gap: 3px;

    span {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--ej-color-muted, #64748B);
      animation: typing-bounce 1.4s infinite ease-in-out;

      &:nth-child(2) { animation-delay: 0.2s; }
      &:nth-child(3) { animation-delay: 0.4s; }
    }
  }
}

@keyframes typing-bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

// === BADGES DE NO LEIDOS ===
.unread-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  background: var(--ej-color-danger, #EF4444);
  color: #FFFFFF;
  font-size: 0.75rem;
  font-weight: 600;
}

// === PRESENCIA ONLINE ===
.presence-badge {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid var(--ej-bg-surface, #FFFFFF);

  &--online { background: var(--ej-color-success, #10B981); }
  &--offline { background: var(--ej-color-muted, #64748B); }
  &--away { background: var(--ej-color-warning, #F59E0B); }
}
```

**Nota:** Se usa `color-mix()` para variantes de color conforme a la directriz (seccion 3 de Directrices de Desarrollo v3.2). NO se usan colores Tailwind, Material Design, ni Bootstrap.

#### package.json del modulo

```json
{
  "name": "jaraba-messaging",
  "version": "1.0.0",
  "description": "Estilos SCSS para el sistema de mensajeria segura",
  "scripts": {
    "build": "sass scss/main.scss css/jaraba-messaging.css --style=compressed",
    "watch": "sass --watch scss/main.scss:css/jaraba-messaging.css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.83.0"
  }
}
```

### 7.4 JavaScript: WebSocket Client y Chat Panel

El JS del modulo sigue las convenciones del ecosistema:

- **Drupal behaviors:** Toda funcionalidad se encapsula en `Drupal.behaviors.jarabaMessaging`
- **Textos traducibles:** `Drupal.t('texto')` para todos los strings visibles
- **CSRF token:** Obtenido de `Drupal.url('session/token')`, cacheado, enviado como header `X-CSRF-Token`
- **No React:** Vanilla JS + Twig parciales renderizados server-side. El JS gestiona WebSocket, DOM updates, y interacciones

```javascript
/**
 * @file
 * WebSocket client y gestor del panel de mensajeria.
 *
 * Patron: Drupal.behaviors con WebSocket nativo.
 * CSRF: Token obtenido de /session/token, cacheado, enviado como X-CSRF-Token.
 * i18n: Todos los textos via Drupal.t()
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.jarabaMessaging = {
    attach: function (context) {
      once('jaraba-messaging', '.messaging-thread-container', context)
        .forEach(function (container) {
          // Inicializar WebSocket y UI
          const wsUrl = container.dataset.wsUrl;
          const userId = parseInt(container.dataset.userId, 10);
          const tenantId = parseInt(container.dataset.tenantId, 10);
          const apiBase = container.dataset.apiBase;

          // ...inicializacion del cliente WS...
        });
    }
  };

  // Ejemplo de texto traducible en JS:
  // Drupal.t('Sending message...')
  // Drupal.t('Message sent')
  // Drupal.t('@name is typing...', { '@name': senderName })

})(Drupal, drupalSettings, once);
```

### 7.5 Modales para Acciones CRUD

Todas las acciones de crear/editar/ver en el frontend se abren en un modal, para que el usuario no abandone la pagina en la que esta trabajando:

| Accion | Trigger | Modal |
|--------|---------|-------|
| Nueva conversacion | Boton "+" en lista de conversaciones | Modal con selector de participantes y contexto |
| Ver detalles conversacion | Click en header de conversacion | Modal con participantes, contexto, opciones |
| Editar mensaje | Menu contextual "Editar" en burbuja propia | Inline editing en la burbuja (no modal completo) |
| Eliminar mensaje | Menu contextual "Eliminar" en burbuja propia | Modal de confirmacion |
| Archivar conversacion | Boton "Archivar" en detalles | Modal de confirmacion |
| Adjuntar archivo | Boton clip en compositor | Modal de seleccion de archivo / drag & drop |
| Buscar en conversacion | Icono lupa en header | Overlay de busqueda (no modal, overlay) |
| Exportar conversacion (RGPD) | Menu "Exportar" en detalles | Modal de formato (JSON/PDF) + confirmacion |

El patron de modal reutiliza el componente `slide-panel` ya existente en el tema (`ecosistema_jaraba_theme/slide-panel` library).

### 7.6 Integracion con Header, Navegacion y Footer del Tema

La pagina `/messaging` reutiliza los parciales existentes del tema:

1. **Header:** `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}` — Dispatcher que selecciona variante (classic/minimal/transparent) segun `theme_settings.header_layout`
2. **Footer:** `{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}` — Con variantes (minimal/standard/mega/split)
3. **Copilot FAB:** `{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' %}` — Si el Copilot esta habilitado

**Configuracion del tema existente que usan los parciales:** Los parciales del tema ya leen configuracion desde la UI de Drupal (theme settings). El modulo de mensajeria hereda automaticamente:

| Setting del Tema | Parcial | Efecto |
|------------------|---------|--------|
| `header_layout` | `_header.html.twig` | Variante de header (classic/minimal/transparent/centered) |
| `footer_layout` | `_footer.html.twig` | Variante de footer (minimal/standard/mega/split) |
| `footer_copyright` | `_footer.html.twig` | Texto de copyright |
| `footer_nav_*` | `_footer.html.twig` | Columnas de navegacion del footer |
| `footer_social_*` | `_footer.html.twig` | Links de redes sociales |
| `sticky_header` | `_header.html.twig` | Header fijo al hacer scroll |
| `avatar_navigation_items` | `_header.html.twig` | Items de navegacion por avatar |

**Body classes via hook_preprocess_html():** El modulo registra sus rutas en `jaraba_theming` para que se anadan clases al body:

```php
// En jaraba_messaging.module
function jaraba_messaging_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'jaraba_messaging.')) {
    $variables['attributes']['class'][] = 'page-messaging';
    $variables['attributes']['class'][] = 'full-width-layout';
    $variables['attributes']['class'][] = 'no-sidebar';
  }
}
```

---

## 8. Internacionalizacion (i18n)

Cumplimiento completo de la directriz de textos traducibles (seccion 1 de Directrices de Desarrollo v3.2):

### 8.1 Twig Templates

```twig
{# CORRECTO: Bloque trans #}
{% trans %}Select a conversation{% endtrans %}
{% trans %}@name is typing...{% endtrans %}
{% trans %}Message sent{% endtrans %}

{# CORRECTO: Con variables #}
{% trans %}{{ count }} unread messages{% endtrans %}

{# INCORRECTO: No usar |t #}
{{ 'Message sent'|t }}
```

### 8.2 PHP Controllers y Services

```php
// CORRECTO: $this->t() en controladores
$build['#title'] = (string) $this->t('Secure Messaging');
$this->messenger()->addStatus($this->t('Conversation archived.'));

// CORRECTO: Cast (string) para render arrays (TM-CAST-001)
$variables['empty_message'] = (string) $this->t('No conversations yet');
```

### 8.3 JavaScript

```javascript
// CORRECTO: Drupal.t() para textos visibles
Drupal.t('Sending message...');
Drupal.t('@name is typing...', { '@name': senderName });
Drupal.t('Message sent');
Drupal.t('Connection lost. Reconnecting...');
```

### 8.4 Labels de Entidades

```php
// Todos los labels en baseFieldDefinitions() usan new TranslatableMarkup()
$fields['title'] = BaseFieldDefinition::create('string')
  ->setLabel(new TranslatableMarkup('Title'))
  ->setDescription(new TranslatableMarkup('Conversation title'));
```

### 8.5 Permisos

```yaml
# Todos los titles y descriptions en permissions.yml son traducibles automaticamente
use jaraba messaging:
  title: 'Use secure messaging'
  description: 'Send and receive messages within the platform'
```

---

## 9. Seguridad

### 9.1 Cifrado AES-256-GCM

- **Algoritmo:** AES-256-GCM con IV aleatorio de 12 bytes y tag de 16 bytes
- **Derivacion de clave:** Argon2id (memory=64MB, iterations=3, parallelism=4, output=32 bytes)
- **Jerarquia:** Platform Master Key (env var) -> Tenant Key (derivada con salt del tenant)
- **Integridad:** SHA-256 del texto plano antes de cifrar, verificacion en descifrado
- **Implementacion:** `openssl_encrypt()`/`openssl_decrypt()` de PHP con CSPRNG para IV

El campo `is_confidential` en SecureConversation excluye mensajes del indexado Qdrant/IA.

### 9.2 CSRF en Rutas API

Conforme a CSRF-API-001:

```yaml
# routing.yml - Todas las rutas POST/PATCH/DELETE
jaraba_messaging.messages.send:
  path: '/api/v1/messaging/conversations/{uuid}/messages'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::send'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\MessageSendAccessCheck::access'
    _csrf_request_header_token: 'TRUE'
  options:
    _auth: ['jwt_auth', 'cookie']
```

```javascript
// JS: Obtener y cachear token CSRF
let csrfToken = null;
async function getCsrfToken() {
  if (!csrfToken) {
    const response = await fetch(Drupal.url('session/token'));
    csrfToken = await response.text();
  }
  return csrfToken;
}

// Enviar con header X-CSRF-Token
async function sendMessage(conversationUuid, body) {
  const token = await getCsrfToken();
  return fetch(Drupal.url('api/v1/messaging/conversations/' + conversationUuid + '/messages?_format=json'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    body: JSON.stringify({ body: body, message_type: 'text' }),
  });
}
```

### 9.3 XSS Prevention en Twig

Conforme a TWIG-XSS-001:

```twig
{# CORRECTO: Contenido de usuario con |safe_html #}
<div class="message-bubble__body">{{ message.body|safe_html }}</div>

{# CORRECTO: Preview con escape automatico de Twig #}
<span class="conversation-preview">{{ conversation.last_message_preview }}</span>

{# INCORRECTO: Nunca |raw para contenido usuario #}
<div class="message-bubble__body">{{ message.body|raw }}</div>
```

En controladores PHP:
```php
// Cast (string) para TranslatableMarkup (TM-CAST-001)
$variables['empty_text'] = (string) $this->t('No messages in this conversation');

// Html::escape() para datos de usuario en emails
$senderName = Html::escape($message->getSenderName());
```

### 9.4 Audit Trail Inmutable (Hash Chain)

- **Patron:** Identico a `document_audit_log` del Buzon de Confianza (doc 88)
- **Calculo:** `hash_chain = SHA-256(previous_hash + json_encode(entry))`
- **Primer registro:** `previous_hash = str_repeat('0', 64)`
- **Verificacion:** `MessageAuditService::verifyIntegrity($conversationId)` recalcula toda la cadena
- **Inmutabilidad:** Tabla append-only, nunca UPDATE ni DELETE
- **Catalogo:** 18 tipos de acciones (conversation.created, message.sent, message.read, etc.)

### 9.5 Rate Limiting

- **30 msg/min** por usuario por conversacion
- **100 msg/min** por conversacion (total)
- **Implementacion:** Redis INCR + EXPIRE (60s TTL)
- **Bypass:** Permiso `bypass messaging rate limit` (solo sistema/admin)
- **Configurable:** Desde UI de admin (`/admin/config/jaraba/messaging`)

### 9.6 Tenant Isolation

- **`tenant_id` obligatorio** en TODA query de entidad
- **Middleware:** TenantContext service inyectado en todos los servicios
- **Access checks:** Validacion de tenant en cada endpoint de API
- **Qdrant:** Colecciones separadas por tenant (`messaging_{tenant_id}`)
- **Redis:** Keys prefijadas con tenant_id (`msg_rate:{tenant_id}:{user_id}:{conv_id}`)

---

## 10. Integracion con Ecosistema Existente

| Componente | Documento | Tipo de Integracion | Sprint | Detalle |
|------------|-----------|---------------------|--------|---------|
| Buzon de Confianza | doc 88 | Adjuntos cifrados via `DocumentVaultService::store()` | S5 | Archivos en chat se enrutan al vault con cifrado E2E |
| Notificaciones Multicanal | doc 98 | Puente offline via `NotificationService::send()` | S2 | Mensaje nuevo + destinatario offline -> notificacion por canal preferido |
| Portal Cliente | doc 90 | Nueva tab "Mensajes" en portal del expediente | S5 | Cliente ve conversaciones vinculadas a su expediente |
| Dashboard Profesional | doc 94 | Widget "Mensajes no leidos" con badge + lista rapida | S5 | WebSocket actualiza counter en real-time |
| Copilot Servicios | doc 93 | RAG sobre mensajes via Qdrant indexing | S6 | "Que me dijo Garcia sobre la escritura?" |
| Grupos Colaboracion | doc 34 | Redis Pub/Sub compartido para distribucion | S3 | Mismo canal Redis para mensajes y actividad de grupos |
| ECA Module | doc 06 | Plugins ECA: eventos, condiciones, acciones de mensajeria | S5 | 8 flujos automatizados (notificacion, auto-respuesta, SLA, etc.) |
| RBAC | doc 04 | Permisos granulares por rol y vertical | S1 | Matriz de permisos completa (doc 178A seccion A4.1) |
| AI Skills | doc 129 | Skill `client_communication` para redaccion asistida | S6 | chat_reply, chat_summary, sentiment_analysis |

---

## 11. Configuracion Administrable desde UI de Drupal

### 11.1 Formulario de Administracion

Ruta: `/admin/config/jaraba/messaging`
Form: `MessagingSettingsForm` (extiende `ConfigFormBase`)

Todos los parametros son configurables desde la UI sin tocar codigo:

| Seccion | Parametro | Tipo | Default | Descripcion |
|---------|-----------|------|---------|-------------|
| **Cifrado** | algorithm | string | aes-256-gcm | Algoritmo de cifrado |
| | argon2id_memory | integer | 65536 | Memoria Argon2id (KB) |
| | argon2id_iterations | integer | 3 | Iteraciones Argon2id |
| **Rate Limiting** | messages_per_minute_per_user | integer | 30 | Max mensajes/min por usuario |
| | messages_per_minute_per_conversation | integer | 100 | Max mensajes/min por conversacion |
| **Retencion RGPD** | default_message_retention_days | integer | 730 | Retencion mensajes (dias) |
| | audit_log_retention_days | integer | 2555 | Retencion audit log (dias) |
| | auto_close_inactive_days | integer | 90 | Auto-cierre por inactividad (dias) |
| **WebSocket** | host | string | 0.0.0.0 | Host del servidor WS |
| | port | integer | 8090 | Puerto del servidor WS |
| | ping_interval | integer | 30 | Intervalo ping (segundos) |
| | online_ttl | integer | 120 | TTL presencia online (segundos) |
| **Notificaciones** | offline_delay_seconds | integer | 30 | Delay antes de notificar offline |
| | digest_interval_hours | integer | 4 | Intervalo digest no leidos (horas) |

### 11.2 Schema de Configuracion

El schema (`config/schema/jaraba_messaging.schema.yml`) define tipos de datos para validacion automatica de Drupal. Esto evita errores de configuracion y permite que el Config Inspector module valide los valores.

### 11.3 Config Install

Los defaults (`config/install/jaraba_messaging.settings.yml`) se aplican durante `drush en jaraba_messaging`. Cambios posteriores se hacen desde la UI y se exportan con `drush cex`.

### 11.4 Variables CSS Inyectables desde Theme Settings

Los colores del panel de chat se pueden personalizar desde la configuracion del tema (`/admin/appearance/settings/ecosistema_jaraba_theme`) sin tocar SCSS:

```php
// Ejemplo: el jaraba_theming module inyecta variables CSS personalizadas
// que los estilos del messaging consumen via var(--ej-*)
:root {
  --ej-color-corporate: #233D63;  /* Personalizable por tenant */
  --ej-color-impulse: #FF8C42;    /* Personalizable por tenant */
  --ej-bg-surface: #FFFFFF;       /* Personalizable por tenant */
}
```

El modulo de mensajeria NO necesita settings propios de estilo porque hereda TODOS los tokens del tema via CSS Custom Properties. Si un tenant cambia su color corporativo, el chat cambia automaticamente.

---

## 12. Navegacion en Estructura Drupal (admin/structure y admin/content)

### 12.1 Entidades en /admin/structure

Las entidades Content Entity (SecureConversation, ConversationParticipant) se registran en la estructura de Drupal:

| Ruta | Proposito | Acceso |
|------|-----------|--------|
| `/admin/structure/secure-conversation` | Gestion de tipo de entidad SecureConversation | admin |
| `/admin/structure/secure-conversation/fields` | Field UI: campos adicionales | admin |
| `/admin/structure/secure-conversation/form-display` | Form display | admin |
| `/admin/structure/secure-conversation/display` | View display | admin |

### 12.2 Contenido en /admin/content

| Ruta | Proposito | Acceso |
|------|-----------|--------|
| `/admin/content/conversations` | Listado de todas las conversaciones (Views) | admin, tenant_admin |
| `/admin/content/conversations/{id}` | Detalle de conversacion (admin view) | admin |
| `/admin/content/conversations/{id}/audit` | Audit log de la conversacion | admin |

### 12.3 Configuracion en /admin/config

| Ruta | Proposito | Acceso |
|------|-----------|--------|
| `/admin/config/jaraba/messaging` | Settings del modulo | administer jaraba messaging |

### 12.4 Links de Menu Admin

```yaml
# jaraba_messaging.links.menu.yml
jaraba_messaging.admin:
  title: 'Secure Messaging'
  description: 'Configure the secure messaging system'
  route_name: jaraba_messaging.settings
  parent: system.admin_config_jaraba
  weight: 10

jaraba_messaging.conversations:
  title: 'Conversations'
  description: 'View and manage secure conversations'
  route_name: entity.secure_conversation.collection
  parent: system.admin_content
  weight: 5
```

**Nota sobre el tenant:** El tenant NO tiene acceso al tema de administracion de Drupal. Opera exclusivamente en la ruta frontend `/messaging`. Las rutas `/admin/*` son solo para super-admin y tenant-admin con permisos especificos.

---

## 13. Plan de Sprints Detallado

### Sprint 1: Foundation (Semanas 1-2, 30-35h)

**Objetivo:** Crear la base de datos, entidades, cifrado y audit trail.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Crear estructura de modulo (info.yml, module, services.yml) | 2h | Modulo instalable | A1, A2 |
| Implementar SecureConversation Content Entity + Interface | 4h | Entidad funcional en BD | A5.1, Directriz 6 |
| Implementar ConversationParticipant Content Entity + Interface | 3h | Entidad de union funcional | A5.1 |
| Implementar hook_schema() (secure_message, audit_log, read_receipt) | 3h | Tablas custom creadas | A8, DRUPAL-ENTUP-001 |
| Implementar SecureMessageDTO + EncryptedPayload Value Objects | 2h | DTOs inmutables | A5.2 |
| Implementar TenantKeyService (derivacion Argon2id) | 3h | Clave derivada por tenant | doc 178 sec 4.2 |
| Implementar MessageEncryptionService + Interface | 4h | Cifrado/descifrado funcional | A6, doc 178 sec 4.4 |
| Implementar MessageAuditService + hash chain + Interface | 4h | Audit log inmutable | A6, doc 178 sec 5.3 |
| Implementar permissions.yml + Access Control Handler | 2h | Permisos granulares | A4 |
| Implementar config/schema + config/install (settings) | 1h | Configuracion tipada | A7.1, A7.2 |
| Unit tests: T01-T03 (cifrado). Kernel tests: T08-T09 (audit hash chain) | 3h | Tests verde | A10 |

### Sprint 2: Core API (Semanas 3-4, 28-32h)

**Objetivo:** Servicios de negocio y API REST completa.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Implementar ConversationService + Interface | 4h | CRUD conversaciones | A6 |
| Implementar MessageService (custom SQL) + Interface | 4h | CRUD mensajes cifrados | A6 |
| Implementar MessagingService (orquestador) + Interface | 5h | Flujo completo envio | A6, doc 178 sec 5.1 |
| Implementar NotificationBridgeService (puente doc 98) | 3h | Notificaciones offline | doc 178 sec 5.2 |
| Implementar routing.yml (todos los endpoints REST) | 3h | Rutas funcionales | A3, CSRF-API-001 |
| Implementar ConversationController (list, create, view, update, close) | 4h | API conversaciones | doc 178 sec 7.1 |
| Implementar MessageController (list, send, edit, delete, read, reactions) | 4h | API mensajes | doc 178 sec 7.2 |
| Implementar Access Checks (6 custom access checks) | 2h | Autorizacion granular | A4.1 |
| Kernel tests: T04-T07 (conversaciones, mensajes, rate limit) | 3h | Tests verde | A10 |

### Sprint 3: Real-Time (Semanas 5-6, 25-30h)

**Objetivo:** Servidor WebSocket, presencia y distribucion en tiempo real.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Instalar Ratchet PHP via Composer | 1h | Dependencia instalada | |
| Implementar MessagingWebSocketServer (Ratchet) | 5h | Servidor WS arrancable | doc 178 sec 6.1 |
| Implementar AuthMiddleware (JWT validation en handshake) | 3h | Auth WS funcional | doc 178 sec 6.1 |
| Implementar ConnectionManager (pool conexiones + Redis SET) | 3h | Pool operativo | doc 178 sec 6.1 |
| Implementar MessageHandler (dispatch por type) | 3h | Protocolo WS completo | doc 178 sec 6.2 |
| Implementar PresenceService (Redis SETEX + Pub/Sub) + Interface | 3h | Presencia + typing | doc 178 sec 6.3, A6 |
| Implementar Drush command: ws-start | 1h | `drush jaraba-messaging:ws-start` | |
| Configurar Lando tooling para WS | 1h | `lando ws-start` | |
| Implementar PresenceController (REST) | 2h | API presencia | doc 178 sec 7.3 |
| WebSocket integration tests: handshake, envio, reconexion | 3h | Tests verde | A10 T18-T20 |

### Sprint 4: Frontend (Semanas 7-8, 30-35h)

**Objetivo:** UI del chat completa con templates Twig, SCSS y JS.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Crear page--messaging.html.twig (Zero-Region Policy) | 3h | Pagina limpia funcional | Sec 7.1, 7.2 |
| Implementar hook_preprocess_html() para body classes | 1h | Classes en body | Sec 7.6 |
| Crear parciales: _conversation-list, _message-thread, _message-bubble | 4h | Parciales renderizables | Sec 7.2 |
| Crear parciales: _message-composer, _typing-indicator, _chat-panel | 3h | Parciales completos | Sec 7.2 |
| Crear _conversation-widget.html.twig (para dashboard) | 1h | Widget dashboard | Sec 7.2 |
| Implementar SCSS: main.scss + 10 parciales | 4h | Estilos compilados | Sec 7.3, Theming v2.1 |
| Implementar messaging-client.js (WebSocket client) | 4h | WS client funcional | Sec 7.4, CSRF-API-001 |
| Implementar chat-panel.js (UI interactions, modales) | 4h | Chat interactivo | Sec 7.4, 7.5 |
| Implementar conversation-list.js (filtrado, badges) | 2h | Lista funcional | Sec 7.4 |
| Implementar message-composer.js (auto-grow, adjuntos, typing) | 3h | Compositor funcional | Sec 7.4 |
| Crear libraries.yml con todas las librerias | 1h | Assets cargados | |
| Accessibility (WCAG 2.1 AA): roles, aria, focus, keyboard nav | 2h | Chat accesible | doc 178 sec 11 |

### Sprint 5: Integration (Semanas 9-10, 22-28h)

**Objetivo:** Integracion con ecosistema y flujos ECA.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Implementar AttachmentBridgeService (puente doc 88) | 3h | Adjuntos cifrados | doc 178 sec 2.3 |
| Integrar tab "Mensajes" en Portal Cliente (doc 90) | 3h | Tab funcional | doc 178 sec 8.2 |
| Integrar widget en Dashboard Profesional (doc 94) | 2h | Widget con badge | doc 178 sec 8.2 |
| Implementar RetentionService (limpieza RGPD) | 2h | Cron de limpieza | doc 178 sec 4.6 |
| Implementar RetentionCleanupWorker (Queue) | 1h | Worker funcional | |
| Implementar 3 Event plugins ECA | 2h | Eventos disparados | doc 178 sec 9 |
| Implementar 3 Condition plugins ECA | 2h | Condiciones evaluadas | doc 178 sec 9 |
| Implementar 2 Action plugins ECA | 2h | Acciones ejecutadas | doc 178 sec 9 |
| Crear 5 YAML de configuracion ECA | 2h | Flujos instalados | doc 178 sec 9.2 |
| Implementar ExportController (RGPD art. 20) | 2h | Export JSON/PDF | doc 178 sec 7.3 |
| Implementar links.menu.yml + links.task.yml + links.action.yml | 1h | Navegacion admin | Sec 12 |
| Functional tests: T10-T17 (API, permisos, cross-tenant, RGPD) | 3h | Tests verde | A10 |

### Sprint 6: AI + QA (Semanas 11-12, 25-30h)

**Objetivo:** Integracion IA, audit de seguridad y go-live.

| Tarea | Horas | Entregable | Directriz Aplicable |
|-------|-------|------------|---------------------|
| Implementar SearchService + Interface | 3h | Busqueda full-text + semantica | doc 178 sec 10.1, A6 |
| Implementar MessageIndexWorker (Qdrant) | 3h | Indexacion asincrona | doc 178 sec 10.1 |
| Implementar SearchController + AuditController | 2h | APIs busqueda + audit | doc 178 sec 7.3 |
| Integrar con Copilot (RAG context injection) | 3h | "Que dijo Garcia?" funcional | doc 178 sec 10.1 |
| Registrar AI Skills (client_communication) | 2h | Skills disponibles | doc 178 sec 10.2 |
| Implementar filtrado is_confidential para IA | 1h | Conversaciones privadas excluidas | doc 178 sec 10.3 |
| Security audit: CSRF, XSS, rate limit, encryption, RBAC | 3h | 0 vulnerabilidades criticas | doc 178 sec 4.5 |
| Performance tests: 100 WS concurrentes, 1000 msg/min | 3h | p95 <200ms | doc 178 sec 11 |
| Accessibility audit: axe DevTools, WAVE, keyboard | 2h | 0 errores A/AA | doc 178 sec 11 |
| MessagingSettingsForm (admin UI) | 2h | Form completo | Sec 11.1 |
| Documentacion final + update hooks para config resync | 1h | Documentacion completa | Regla de oro 7, 10 |

---

## 14. Estrategia de Testing

### 14.1 Cobertura por Tipo

| Tipo | Herramienta | Cobertura Target | Ficheros |
|------|-------------|-----------------|----------|
| Unit | PHPUnit | 100% cifrado + audit | MessageEncryptionServiceTest, MessageAuditServiceTest, PresenceServiceTest |
| Kernel | Drupal KernelTestBase | >85% servicios | ConversationServiceTest, MessagingServiceTest, RateLimitTest |
| Functional | Drupal BrowserTestBase | Todos los endpoints API | ConversationApiTest, SearchApiTest, ExportApiTest |
| Integration | PHPUnit + Ratchet Client | WS handshake, envio, reconexion | (tests manuales + scripts k6) |

### 14.2 Tests Especificos (doc 178A seccion A10)

| ID | Test | Tipo | Escenario | Assertions Clave |
|----|------|------|-----------|-----------------|
| T01 | Cifrado round-trip | Unit | Cifrar 'Hola mundo', descifrar | assertEqual(descifrado, 'Hola mundo'); assertLength(iv, 12); assertLength(tag, 16) |
| T02 | Clave incorrecta | Unit | Cifrar tenant 1, descifrar tenant 2 | assertThrows(DecryptionException) |
| T03 | Hash integridad | Unit | Cifrar, alterar 1 byte, descifrar | assertThrows (GCM detecta tamper) |
| T04 | Crear conv 1:1 | Kernel | User A crea conv con User B | assertCount(2, participants); assertEquals('active', status) |
| T05 | Evitar duplicada | Kernel | Crear conv con mismos users 2 veces | assertEquals(conv1.id, conv2.id) |
| T06 | Enviar mensaje | Kernel | User A envia 'Hola' | assertCount(1, messages); assertEquals(1, unread_count) |
| T07 | Rate limit | Kernel | 31 mensajes en 1 minuto | 31o lanza RateLimitException |
| T08 | Audit hash chain | Kernel | Conv + 3 msgs + verify | verifyIntegrity() retorna {valid: true, total: 4} |
| T09 | Audit tamper | Kernel | 3 entries, modificar #2 | verifyIntegrity() retorna {valid: false, brokenAt: 2} |
| T10 | Permisos: cliente no inicia | Functional | POST /conversations con rol cliente | assertResponse(403) |
| T11 | Cross-tenant | Functional | User tenant 1 accede conv tenant 2 | assertResponse(403) |
| T12 | Listar conversaciones | Functional | GET /conversations (3 activas + 1 archivada) | assertCount(3, data) |
| T13 | Paginacion mensajes | Functional | GET /messages?limit=25 (60 msgs total) | assertCount(25); assertExists(cursor.next) |
| T14 | Editar en ventana | Functional | Enviar, esperar 1s, editar | assertResponse(200); is_edited=true |
| T15 | Editar fuera ventana | Functional | Enviar, simular 16min, editar | assertResponse(422) |
| T16 | Soft-delete | Functional | Enviar, eliminar, listar | is_deleted=true |
| T17 | RGPD export | Functional | POST /export como owner | assertResponse(200); JSON completo |
| T18 | WS auth fallida | Integration | Conectar WS sin JWT | Conexion rechazada 4001 |
| T19 | WS mensaje E2E | Integration | A envia por WS, B recibe | B recibe message.new en <100ms |
| T20 | Typing indicator | Integration | A typing, B recibe | B recibe typing event; desaparece en 5s |

### 14.3 Convenciones de Testing (Flujo de Trabajo Claude v14.0.0)

- **Mocking de clases final:** Inyectar como `object` en constructores; interfaces temporales con `if (!interface_exists(...))` (TEST-MOCK-001, TEST-NS-001)
- **Metadatos de cache en mocks:** Mocks de SecureConversation implementan `getCacheContexts`, `getCacheTags`, `getCacheMaxAge` (TEST-CACHE-001)
- **XPath para XML:** Si hay tests de XML (audit export), usar XPath no comparacion de cadenas (TEST-XML-001)

---

## 15. Verificacion y Despliegue

### 15.1 Verificacion Manual

| Verificacion | Como Probar | Resultado Esperado |
|--------------|-------------|-------------------|
| Pagina /messaging | Navegar como usuario autenticado | Pagina limpia sin sidebar, con header/footer del tema, lista vacia |
| Crear conversacion | Click "+" -> seleccionar participante -> enviar | Conversacion creada, mensaje visible |
| Mensaje en tiempo real | Abrir 2 navegadores, enviar mensaje | Mensaje aparece en <100ms en ambos |
| Typing indicator | Empezar a escribir en un navegador | "Escribiendo..." aparece en el otro |
| Read receipt | Leer mensaje en destinatario | Doble check azul en remitente |
| Adjuntar archivo | Enviar archivo en conversacion | Archivo cifrado, descargable |
| Busqueda | Buscar texto en conversacion | Resultados resaltados |
| Admin settings | Visitar /admin/config/jaraba/messaging | Formulario con todos los parametros |
| admin/content/conversations | Visitar como admin | Listado de todas las conversaciones |
| admin/structure/secure-conversation | Visitar como admin | Gestion de tipo de entidad |
| Cross-tenant | Intentar acceder a conv de otro tenant | 403 Forbidden |
| Rate limit | Enviar 31 mensajes rapido | Mensaje de error en el 31o |

### 15.2 Checklist Pre-Deploy

```markdown
### Pre-deploy Checklist jaraba_messaging
- [ ] i18n: Textos con {% trans %} / $this->t() / Drupal.t()
- [ ] SCSS: No CSS directo, variables var(--ej-*), compilado con Dart Sass
- [ ] Colores: Paleta 7 colores Jaraba + color-mix() para variantes
- [ ] Iconos: jaraba_icon('cat', 'name', {opts}) — sin emojis Unicode
- [ ] Compilado: npm run build + drush cr
- [ ] CSRF API: _csrf_request_header_token en rutas POST/PATCH/DELETE
- [ ] Twig XSS: |safe_html en contenido usuario, nunca |raw
- [ ] Roles: Permisos especificos (no solo authenticated)
- [ ] Tenant isolation: tenant_id en TODA query
- [ ] Tests: Unit (T01-T09) + Kernel (T04-T08) + Functional (T10-T17) todos verde
- [ ] Config export: drush cex incluye jaraba_messaging.settings
- [ ] Update hook: hook_update para resync de config si se modifican YAMLs
- [ ] Security audit: 0 vulnerabilidades criticas
- [ ] Accessibility: WCAG 2.1 AA, 0 errores A/AA
```

### 15.3 Despliegue

```bash
# Dentro del contenedor Docker
lando ssh

# 1. Instalar dependencias Composer (Ratchet)
cd /app && composer require cboden/ratchet

# 2. Instalar el modulo
drush en jaraba_messaging -y

# 3. Ejecutar update hooks
drush updb -y

# 4. Importar configuracion
drush cim -y

# 5. Compilar SCSS
cd /app/web/modules/custom/jaraba_messaging && npm install && npm run build

# 6. Limpiar cache
drush cr

# 7. Verificar entidades
drush entity:updates

# 8. Iniciar servidor WebSocket
drush jaraba-messaging:ws-start &

# 9. Verificar en navegador
# https://jaraba-saas.lndo.site/messaging
```

### 15.4 Rollback

```bash
# Deshabilitar modulo
drush pm:uninstall jaraba_messaging -y

# Limpiar cache
drush cr

# Las tablas custom (hook_schema) se eliminan automaticamente al desinstalar
```

---

## 16. Troubleshooting

### Problema 1: WebSocket no conecta

**Sintomas:** Chat muestra "Connection lost. Reconnecting..." permanentemente.

**Causas posibles:**
- Servidor WS no arrancado: `drush jaraba-messaging:ws-start`
- Puerto 8090 bloqueado: verificar firewall/Lando proxy
- JWT expirado: renovar sesion de Drupal

**Solucion:**
```bash
# Verificar que el WS server esta corriendo
lando ssh -c "ps aux | grep ratchet"

# Reiniciar servidor WS
lando ssh -c "drush jaraba-messaging:ws-start"

# Verificar desde browser console
# new WebSocket('wss://jaraba-saas.lndo.site:8090?token=XXX')
```

### Problema 2: Mensajes no se descifran

**Sintomas:** Mensajes aparecen como contenido binario o error de descifrado.

**Causa:** Clave del tenant incorrecta o corrupta, o variable de entorno JARABA_PMK no configurada.

**Solucion:**
```bash
# Verificar variable de entorno
lando ssh -c "echo \$JARABA_PMK"

# Verificar clave del tenant en BD
lando drush eval "var_dump(\Drupal::service('jaraba_messaging.tenant_key')->getTenantKey(1));"
```

### Problema 3: Estilos no aplican

**Sintomas:** Chat sin estilos o con estilos rotos.

**Causa:** SCSS no compilado o cache de Drupal.

**Solucion:**
```bash
# Recompilar SCSS
lando ssh -c "cd /app/web/modules/custom/jaraba_messaging && npm run build"

# Limpiar cache
lando drush cr

# Verificar que la libreria esta registrada
lando drush eval "print_r(\Drupal::service('library.discovery')->getLibraryByName('jaraba_messaging', 'messaging'));"
```

### Problema 4: CSRF 403 en API

**Sintomas:** Fetch a /api/v1/messaging/* devuelve 403.

**Causa:** Token CSRF no enviado o expirado.

**Solucion:**
```javascript
// Verificar en console del navegador
fetch(Drupal.url('session/token')).then(r => r.text()).then(console.log);
// Debe devolver un token tipo "abcdef123..."
```

---

## 17. Referencias Cruzadas

| Documento | Ubicacion | Relacion |
|-----------|-----------|----------|
| Directrices del Proyecto v60.0.0 | `docs/00_DIRECTRICES_PROYECTO.md` | Reglas CSRF-API-001, TWIG-XSS-001, TM-CAST-001, ENTITY-APPEND-001, CONFIG-SEED-001, API-FIELD-001, STATE-001, CRON-FLAG-001 |
| Documento Maestro de Arquitectura v59.0.0 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Arquitectura de alto nivel |
| Flujo de Trabajo Claude v14.0.0 | `docs/00_FLUJO_TRABAJO_CLAUDE.md` | Reglas de oro, patrones de testing, append-only entities, config seeding |
| Directrices de Desarrollo v3.2 | `docs/tecnicos/DIRECTRICES_DESARROLLO.md` | Checklist pre-commit (i18n, SCSS, colores, iconos, append-only, config seeding, state machine, cron flags) |
| Indice General v75.0.0 | `docs/00_INDICE_GENERAL.md` | Registro de todas las versiones |
| Arquitectura de Theming SaaS v2.1 | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Federated Design Tokens |
| Mapa URLs Frontend Premium | `docs/arquitectura/2026-01-26_mapa_urls_frontend_premium.md` | Templates de pagina premium |
| Auditoria UX Frontend | `docs/arquitectura/2026-01-24_1936_auditoria-ux-frontend-saas.md` | Patrones UX existentes |
| Especificacion Tecnica doc 178 | `docs/tecnicos/20260220c-178_Platform_Secure_Messaging_v1_Claude.md` | Documento fuente principal |
| Anexo Implementacion doc 178A | `docs/tecnicos/20260220c-178A_Platform_Secure_Messaging_Anexo_Implementation_v1_Claude.md` | Artefactos de implementacion |
| Plantilla Implementacion | `docs/plantillas/plantilla_implementacion.md` | Formato de documento |

---

## 18. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-02-20 | **1.1.0** | IA Asistente (Claude Opus 4.6) | **Actualizacion de cumplimiento de directrices y correccion de gaps:** (1) Referenciada directrices v60.0.0 (antes v56.0.0), desarrollo v3.2 (antes v3.0), flujo v14.0.0 (antes v11.0.0), indice v75.0.0 (antes v71.0.0), arquitectura v59.0.0 (antes v56.0.0). (2) Anadidas 8 reglas nuevas a tabla 3.1 (ENTITY-APPEND-001, CONFIG-SEED-001, API-FIELD-001, STATE-001, CRON-FLAG-001, PB-PREVIEW-002, PB-DUP-001). (3) Anadidas 5 secciones nuevas a tabla 3.2 (secciones 14-18 de Directrices Desarrollo v3.2). (4) Anadidas 3 reglas de oro a tabla 3.4 (reglas 13-15). (5) Actualizada tabla de referencias cruzadas. (6) Anadida clase Drush command (`JarabaMessagingCommands`) a estructura de archivos. (7) Corregida contradiccion SDC vs parcial Twig para burbuja de mensaje. (8) Completados 8 YAMLs ECA (antes solo 5 de 8). (9) Corregida clasificacion tests T08-T09 (Kernel, no Unit). (10) Documentados servicios Access Check y comando Drush en seccion 6.4. |
| 2026-02-20 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Creacion inicial. Plan de implementacion completo para jaraba_messaging conforme a docs 178/178A, directrices v56.0.0, theming v2.1, flujo v11.0.0. 6 sprints, 160-190h estimadas. |

---

> **Nota:** Recuerda actualizar el indice general (`00_INDICE_GENERAL.md`) despues de aprobar este documento.
