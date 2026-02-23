# 📋 DIRECTRICES DEL PROYECTO - JarabaImpactPlatformSaaS

> **⚠️ DOCUMENTO MAESTRO**: Este documento debe leerse y memorizarse al inicio de cada conversación o al reanudarla.

**Fecha de creación:** 2026-01-09 15:28  
**Última actualización:** 2026-02-20
**Versión:** 61.0.0 (Secure Messaging Implementado — Doc 178 jaraba_messaging)

---

## 📑 Tabla de Contenidos (TOC)

1. [Información General del Proyecto](#1-información-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura Multi-tenant](#3-arquitectura-multi-tenant)
4. [Seguridad y Permisos](#4-seguridad-y-permisos)
5. [Principios de Desarrollo](#5-principios-de-desarrollo)
6. [Testing y Calidad](#6-testing-y-calidad)
7. [Estructura de Documentación](#7-estructura-de-documentación)
8. [Convenciones de Nomenclatura](#8-convenciones-de-nomenclatura)
9. [Formato de Documentos](#9-formato-de-documentos)
10. [Flujo de Trabajo de Documentación](#10-flujo-de-trabajo-de-documentación)
11. [Estándares de Código y Comentarios](#11-estándares-de-código-y-comentarios)
12. [Control de Versiones](#12-control-de-versiones)
13. [Procedimientos de Actualización](#13-procedimientos-de-actualización)
14. [Glosario de Términos](#14-glosario-de-términos)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 2. Stack Tecnológico

### 2.1 Backend & Core
- **Lenguaje:** PHP 8.4 (requerido para compatibilidad con Drupal 11).
- **Framework:** Drupal 11.
- **Motor de BD:** MariaDB 11.4+.
- **Caché & Pub/Sub:** Redis 7.4.

---

## 6. Testing y Calidad (Actualizado 2026-02-18)

| Norma | ID | Descripción | Prioridad |
|-------|----|-------------|-----------|
| **Mocking de Clases Final** | TEST-MOCK-001 | Las clases `final` (ej. AiProviderPluginManager) NO pueden mockearse directamente. Se DEBE inyectar como `object` en constructores y usar interfaces temporales en los tests. | P0 |
| **Namespaces en Tests** | TEST-NS-001 | Las interfaces de mock temporales DEBEN envolverse en `if (!interface_exists(...))` para evitar colisiones durante la ejecución masiva de suites. | P0 |
| **Metadatos de Caché** | TEST-CACHE-001 | Todo mock de entidad en tests de AccessControl DEBE implementar `getCacheContexts`, `getCacheTags` y `getCacheMaxAge` para evitar fallos de tipo en core. | P1 |
| **Firma Digital XML** | TEST-XML-001 | Las aserciones sobre XML con namespaces DEBEN usar XPath en lugar de comparaciones de cadenas literales para evitar fragilidad por prefijos. | P1 |
| **Trivy Config YAML** | CICD-TRIVY-001 | Las claves `skip-dirs` y `skip-files` en trivy.yaml DEBEN estar anidadas bajo el bloque `scan:`. Claves al nivel raíz o con nombres incorrectos (`exclude-dirs`) son ignoradas silenciosamente. Verificar en logs CI que el conteo de archivos es coherente. | P0 |
| **Deploy Smoke Fallback** | CICD-DEPLOY-001 | Todo smoke test que dependa de un secret de URL DEBE implementar un fallback (SSH/Drush) antes de fallar. Usar `::warning::` cuando el fallback tiene éxito, no `::error::`. | P1 |
| **Preview Image en Templates** | PB-PREVIEW-001 | Todo YAML de PageTemplate DEBE incluir `preview_image` apuntando al PNG correspondiente. Convención: `id` con guiones → `/modules/custom/jaraba_page_builder/images/previews/{id-con-guiones}.png`. | P0 |
| **Preview Data Rico** | PB-DATA-001 | Los `preview_data` de templates verticales DEBEN incluir arrays con 3+ items representativos del dominio (features, testimonials, faqs, stats, etc.), no solo campos genéricos. | P1 |
| **Categoría Default Unificada** | PB-CAT-001 | La categoría por defecto de PageTemplate DEBE ser `'content'` en las 3 fuentes: `PageTemplate.php`, `CanvasApiController`, `TemplateRegistryService`. | P1 |
| **Drupal 10+ Entity Updates** | DRUPAL-ENTUP-001 | `EntityDefinitionUpdateManager::applyUpdates()` fue eliminado en Drupal 10+. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explícitamente en update hooks. | P0 |
| **CSRF en Rutas API** | CSRF-API-001 | Toda ruta API (`/api/v1/*`) consumida via `fetch()` DEBE usar `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token`). El JS DEBE obtener el token de `/session/token` y enviarlo como header `X-CSRF-Token`. | P0 |
| **XSS en Twig** | TWIG-XSS-001 | Campos de contenido de usuario en Twig DEBEN usar `\|safe_html` (NUNCA `\|raw`). Solo se permite `\|raw` para JSON-LD schema auto-generado y HTML generado completamente por backend. | P0 |
| **TranslatableMarkup Cast** | TM-CAST-001 | Los valores de `$this->t()` que se pasan a render arrays o templates Twig DEBEN castearse a `(string)` en el controlador para evitar `InvalidArgumentException` por doble traducción. | P1 |
| **PWA Meta Tags Duales** | PWA-META-001 | La función `addPwaMetaTags()` DEBE incluir AMBOS meta tags: `apple-mobile-web-app-capable` (iOS Safari) y `mobile-web-app-capable` (Chrome/Android). Eliminar uno rompe PWA en la otra plataforma. | P1 |
| **Entidades Append-Only** | ENTITY-APPEND-001 | Las entidades de registro inmutable (predicciones, logs, auditoría) NO DEBEN tener form handlers de edición/eliminación. El AccessControlHandler DEBE denegar `update` y `delete`. Solo se permite `create` y `view`. | P0 |
| **Config Seeding via Update Hook** | CONFIG-SEED-001 | Al crear config entities que requieran datos iniciales, los YAMLs de `config/install/` DEBEN procesarse en un `update_hook` que lea los archivos YAML, codifique campos JSON con `json_encode()`, y cree las entidades via `Entity::create()->save()`. Los YAMLs raw almacenan arrays PHP, no JSON strings. | P1 |
| **Preview Image Todo Vertical** | PB-PREVIEW-002 | Todo vertical que se añada al Page Builder DEBE generar sus imágenes de preview PNG en `images/previews/` ANTES de desplegar a producción. Convención: `{vertical}-{tipo}.png`. Paleta consistente por vertical usando design tokens `--ej-{vertical}-*`. | P0 |
| **No Duplicar Bloques GrapesJS** | PB-DUP-001 | No DEBEN existir bloques con el mismo label en el BlockManager GrapesJS. Verificar `blockManager.get(id)` antes de registrar para evitar duplicados entre bloques estáticos y dinámicos (API Template Registry). | P1 |
| **Field Mapping en APIs** | API-FIELD-001 | Los campos de la entity `create()` DEBEN coincidir exactamente con los definidos en `baseFieldDefinitions()`. Nunca usar nombres de conveniencia del request (ej. `datetime`) como nombres de campos de entidad (ej. `booking_date`). Mapear explícitamente en el controlador. | P0 |
| **State Machine Status Values** | STATE-001 | Los valores de status en controladores, cron y hooks DEBEN coincidir con los `allowed_values` de la entidad. Si la entidad define `cancelled_client`/`cancelled_provider`, nunca usar `cancelled` genérico internamente. Mapear en el punto de entrada de la API. | P0 |
| **Cron Idempotency Flags** | CRON-FLAG-001 | Toda acción de cron que envíe notificaciones DEBE: (1) filtrar por flag `NOT sent` en la query, (2) marcar el flag como `TRUE` tras enviar, (3) guardar la entidad. Esto previene duplicados en ejecuciones concurrentes o reintentos. | P0 |
| **Cifrado Server-Side** | MSG-ENC-001 | Los datos sensibles en tablas custom (mensajes, adjuntos) DEBEN cifrarse con AES-256-GCM via `openssl_encrypt()`/`openssl_decrypt()`. IV de 12 bytes (aleatorio por mensaje), tag de 16 bytes almacenado junto al ciphertext. La clave se deriva con Argon2id (`sodium_crypto_pwhash`) desde una Platform Master Key (env var), NUNCA hardcodeada. Los DTOs readonly encapsulan datos descifrados en memoria. | P0 |
| **WebSocket Auth Middleware** | MSG-WS-001 | Las conexiones WebSocket DEBEN autenticarse en `onOpen()` con JWT o session cookie. El middleware DEBE validar el token, resolver el user_id y tenant_id, y adjuntarlos al objeto Connection ANTES de permitir mensajes. Conexiones sin auth valido se cierran inmediatamente con codigo 4401. | P0 |
| **Rate Limiting en Mensajeria** | MSG-RATE-001 | Los endpoints de envio de mensajes DEBEN implementar rate limiting: (1) por usuario (30 msg/min), (2) por conversacion (100 msg/min). Contadores via COUNT en tabla con ventana temporal. Lanzar `RateLimitException` con los campos `limit`, `windowSeconds` y `scope`. | P1 |

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-20 | **61.0.0** | **Secure Messaging Implementado (Doc 178):** Modulo `jaraba_messaging` implementado con 104 archivos. 3 reglas nuevas: MSG-ENC-001 (cifrado AES-256-GCM server-side con Argon2id KDF), MSG-WS-001 (autenticacion WebSocket con JWT middleware), MSG-RATE-001 (rate limiting por usuario y conversacion). Patrones: custom schema tables con DTOs readonly, hash chain SHA-256 para audit inmutable, optional DI con `@?`, cursor-based pagination, ECA plugins por codigo. Aprendizaje #106. |
| 2026-02-20 | 60.0.0 | **ServiciosConecta Sprint S3 — Booking API & State Machine Fix:** Correccion de `createBooking()` (field mapping, validaciones). Fix state machine y cron. 3 reglas: API-FIELD-001, STATE-001, CRON-FLAG-001. Aprendizaje #105. |
| 2026-02-20 | 59.0.0 | **Page Builder Preview Audit:** Auditoría de 4 escenarios del Page Builder. 66 imágenes premium glassmorphism 3D generadas para 6 verticales (AgroConecta, ComercioConecta, Empleabilidad, Emprendimiento, ServiciosConecta, JarabaLex). 219 bloques inventariados, 31 categorías, 4 duplicados detectados. Reglas PB-PREVIEW-002, PB-DUP-001. Aprendizaje #103. |
| 2026-02-20 | 58.0.0 | **Vertical Retention Playbooks (Doc 179):** Implementacion completa del motor de retencion verticalizado. 2 entidades nuevas (VerticalRetentionProfile, SeasonalChurnPrediction), 2 servicios (VerticalRetentionService, SeasonalChurnService), 7 endpoints API REST, 1 dashboard FOC, 5 perfiles verticales, QueueWorker cron. 25 archivos nuevos + 11 modificados. Reglas ENTITY-APPEND-001, CONFIG-SEED-001. Aprendizaje #104. |
| 2026-02-20 | 56.0.0 | **Gemini Remediation:** Auditoria y correccion de ~40 archivos modificados por otra IA. 21 archivos revertidos, 15 corregidos manualmente (CSRF, XSS, PWA, roles, i18n). Reglas CSRF-API-001, TWIG-XSS-001, TM-CAST-001, PWA-META-001. Aprendizaje #102. |
| 2026-02-18 | 55.0.0 | **Page Builder Template Consistency:** Resync de 129 templates con preview_image, metadatos corregidos (tildes, labels), preview_data rico para 55 verticales, pipelines Canvas/Picker unificados. Fix de `applyUpdates()` en Legal Intelligence. Reglas PB-PREVIEW-001, PB-DATA-001, PB-CAT-001, DRUPAL-ENTUP-001. |
| 2026-02-18 | 54.0.0 | **CI/CD Hardening:** Fix de config Trivy (`scan.skip-dirs`), deploy resiliente con fallback SSH. Nuevas reglas CICD-TRIVY-001 y CICD-DEPLOY-001. |
| 2026-02-18 | 53.0.0 | **The Unified & Stabilized SaaS:** Consolidación final de las 5 fases. Estabilización de 370+ tests en 17 módulos. Refactorización masiva de DI para clases final de IA y estandarización de mocks para PHPUnit 11. |
| 2026-02-18 | 52.0.0 | **The Living SaaS:** Implementación de las fronteras finales. Bloque O (ZKP) y Bloque P (Liquid UI). Nuevas reglas de privacidad matemática y adaptabilidad de interfaz. |
| 2026-02-18 | 51.0.0 | Economía Agéntica Implementada: Bloques M y N completados. |
| 2026-02-18 | 50.0.0 | SaaS Golden Master Candidate: Consolidación final de todos los bloques. |
