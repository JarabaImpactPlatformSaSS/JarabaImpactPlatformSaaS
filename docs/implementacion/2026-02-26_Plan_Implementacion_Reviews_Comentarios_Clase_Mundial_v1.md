# Sistema de Calificaciones y Comentarios — Plan de Implementación Integral

**Fecha**: 2026-02-26
**Módulos afectados**: `jaraba_comercio_conecta`, `jaraba_agroconecta_core`, `jaraba_servicios_conecta`, `jaraba_mentoring`, `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
**Spec**: Auditoría Estratégica Calificaciones y Comentarios v2.0 (Doc análisis 2026-02-26, actualizado 2026-02-27)
**Impacto**: Elevación de los 4 sistemas de reviews verticales a nivel clase mundial con infraestructura compartida, SEO Schema.org, frontend público premium, resúmenes IA y cumplimiento total de directrices
**Estado global**: Fases 1-10 COMPLETADAS (2026-02-27). Fase 11 (Elevación Clase Mundial — 18 brechas) PENDIENTE.

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Qué se implementa](#11-qué-se-implementa)
   - 1.2 [Por qué se implementa](#12-por-qué-se-implementa)
   - 1.3 [Alcance](#13-alcance)
   - 1.4 [Filosofía de implementación](#14-filosofía-de-implementación)
   - 1.5 [Estimación](#15-estimación)
   - 1.6 [Riesgos y mitigación](#16-riesgos-y-mitigación)
2. [Inventario de Hallazgos de Auditoría](#2-inventario-de-hallazgos-de-auditoría)
   - 2.1 [Seguridad (REV-S1 a REV-S3)](#21-seguridad-rev-s1-a-rev-s3)
   - 2.2 [Bugs y gaps de datos (REV-D1 a REV-D4)](#22-bugs-y-gaps-de-datos-rev-d1-a-rev-d4)
   - 2.3 [Inconsistencias arquitectónicas (REV-A1 a REV-A5)](#23-inconsistencias-arquitectónicas-rev-a1-a-rev-a5)
   - 2.4 [Gaps de clase mundial (REV-GAP1 a REV-GAP8)](#24-gaps-de-clase-mundial-rev-gap1-a-rev-gap8)
3. [Tabla de Correspondencia con Especificaciones Técnicas](#3-tabla-de-correspondencia-con-especificaciones-técnicas)
   - 3.1 [Mapping de entidades existentes](#31-mapping-de-entidades-existentes)
   - 3.2 [Mapping de servicios existentes](#32-mapping-de-servicios-existentes)
   - 3.3 [Mapping de rutas y controladores](#33-mapping-de-rutas-y-controladores)
   - 3.4 [Funcionalidades nuevas (clase mundial)](#34-funcionalidades-nuevas-clase-mundial)
4. [Tabla de Cumplimiento de Directrices del Proyecto](#4-tabla-de-cumplimiento-de-directrices-del-proyecto)
5. [Requisitos Previos](#5-requisitos-previos)
   - 5.1 [Stack tecnológico](#51-stack-tecnológico)
   - 5.2 [Módulos Drupal requeridos](#52-módulos-drupal-requeridos)
   - 5.3 [Infraestructura](#53-infraestructura)
   - 5.4 [Documentos de referencia](#54-documentos-de-referencia)
6. [Entorno de Desarrollo](#6-entorno-de-desarrollo)
   - 6.1 [Comandos Docker/Lando](#61-comandos-dockerlando)
   - 6.2 [URLs de verificación](#62-urls-de-verificación)
   - 6.3 [Compilación SCSS](#63-compilación-scss)
7. [Arquitectura — ReviewableEntityTrait](#7-arquitectura--reviewableentitytrait)
   - 7.1 [Decisión de diseño: trait compartido vs entidad única](#71-decisión-de-diseño-trait-compartido-vs-entidad-única)
   - 7.2 [Campos del trait](#72-campos-del-trait)
   - 7.3 [Implementación del trait](#73-implementación-del-trait)
   - 7.4 [Uso del trait en entidades existentes](#74-uso-del-trait-en-entidades-existentes)
   - 7.5 [Update hooks para migración de esquema](#75-update-hooks-para-migración-de-esquema)
8. [Arquitectura — Armonización de Entidades Existentes](#8-arquitectura--armonización-de-entidades-existentes)
   - 8.1 [ComercioReview: campos a añadir](#81-comercioreview-campos-a-añadir)
   - 8.2 [ReviewServicios: campos a añadir](#82-reviewservicios-campos-a-añadir)
   - 8.3 [SessionReview: elevación a estándar](#83-sessionreview-elevación-a-estándar)
   - 8.4 [ReviewAgro: ajustes menores](#84-reviewagro-ajustes-menores)
   - 8.5 [Armonización de field names](#85-armonización-de-field-names)
   - 8.6 [Nuevas entidades: CourseReview y ContentComment](#86-nuevas-entidades-coursereview-y-contentcomment)
   - 8.7 [Access control handlers con tenant isolation](#87-access-control-handlers-con-tenant-isolation)
   - 8.8 [Permisos unificados](#88-permisos-unificados)
   - 8.9 [Estructura de archivos](#89-estructura-de-archivos)
9. [Servicios — Consolidación y Elevación](#9-servicios--consolidación-y-elevación)
   - 9.1 [ReviewModerationService (nuevo, ecosistema_jaraba_core)](#91-reviewmoderationservice-nuevo-ecosistema_jaraba_core)
   - 9.2 [ReviewAggregationService (nuevo, ecosistema_jaraba_core)](#92-reviewaggregationservice-nuevo-ecosistema_jaraba_core)
   - 9.3 [ReviewSchemaOrgService (nuevo, ecosistema_jaraba_core)](#93-reviewschemaorgservice-nuevo-ecosistema_jaraba_core)
   - 9.4 [ReviewInvitationService (nuevo, ecosistema_jaraba_core)](#94-reviewinvitationservice-nuevo-ecosistema_jaraba_core)
   - 9.5 [ReviewAiSummaryService (nuevo, jaraba_ai_agents)](#95-reviewaisummaryservice-nuevo-jaraba_ai_agents)
   - 9.6 [Servicios verticales: qué se mantiene, qué se delega](#96-servicios-verticales-qué-se-mantiene-qué-se-delega)
10. [Controladores y Rutas](#10-controladores-y-rutas)
    - 10.1 [Rutas API unificadas vs verticales](#101-rutas-api-unificadas-vs-verticales)
    - 10.2 [ReviewDisplayController (nuevo, ecosistema_jaraba_core)](#102-reviewdisplaycontroller-nuevo-ecosistema_jaraba_core)
    - 10.3 [Rutas frontend](#103-rutas-frontend)
    - 10.4 [Integración con controladores existentes](#104-integración-con-controladores-existentes)
11. [Arquitectura Frontend](#11-arquitectura-frontend)
    - 11.1 [Zero Region Policy](#111-zero-region-policy)
    - 11.2 [Templates Twig](#112-templates-twig)
    - 11.3 [Templates parciales](#113-templates-parciales)
    - 11.4 [Body classes via hook_preprocess_html()](#114-body-classes-via-hook_preprocess_html)
    - 11.5 [SCSS: Federated Design Tokens](#115-scss-federated-design-tokens)
    - 11.6 [JavaScript: Star Rating Widget](#116-javascript-star-rating-widget)
    - 11.7 [Iconos: jaraba_icon()](#117-iconos-jaraba_icon)
    - 11.8 [Mobile-first responsive](#118-mobile-first-responsive)
    - 11.9 [Modales slide-panel para CRUD](#119-modales-slide-panel-para-crud)
    - 11.10 [Theme Settings: configuración administrable](#1110-theme-settings-configuración-administrable)
    - 11.11 [GrapesJS: Review Widget Block](#1111-grapesjs-review-widget-block)
12. [Internacionalización (i18n)](#12-internacionalización-i18n)
13. [Seguridad](#13-seguridad)
14. [Integración con Ecosistema](#14-integración-con-ecosistema)
    - 14.1 [TenantContextService / TenantBridgeService](#141-tenantcontextservice--tenantbridgeservice)
    - 14.2 [PlanResolverService (feature gates)](#142-planresolverservice-feature-gates)
    - 14.3 [AI Copilots (respuestas asistidas)](#143-ai-copilots-respuestas-asistidas)
    - 14.4 [Recommendation Engine](#144-recommendation-engine)
    - 14.5 [Analytics y engagement_score](#145-analytics-y-engagement_score)
15. [Navegación Drupal Admin](#15-navegación-drupal-admin)
    - 15.1 [/admin/structure: Field UI + Settings](#151-adminstructure-field-ui--settings)
    - 15.2 [/admin/content: Listados de entidades](#152-admincontent-listados-de-entidades)
    - 15.3 [Entity links: CRUD completo](#153-entity-links-crud-completo)
16. [Fases de Implementación](#16-fases-de-implementación)
17. [Estrategia de Testing](#17-estrategia-de-testing)
18. [Verificación y Despliegue](#18-verificación-y-despliegue)
    - 18.1 [Checklist manual](#181-checklist-manual)
    - 18.2 [Comandos de verificación](#182-comandos-de-verificación)
    - 18.3 [Browser verification URLs](#183-browser-verification-urls)
    - 18.4 [Checklist de directrices cumplidas](#184-checklist-de-directrices-cumplidas)
19. [Troubleshooting](#19-troubleshooting)
20. [Referencias Cruzadas](#20-referencias-cruzadas)
21. [Registro de Cambios](#21-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Qué se implementa

Se eleva el sistema de calificaciones y comentarios del SaaS Jaraba Impact Platform de un estado de implementación fragmentada (4 entidades de review independientes, sin frontend público, sin SEO estructurado, sin resúmenes IA) a un sistema de clase mundial con:

- **ReviewableEntityTrait** compartido que estandariza campos, moderación, tenant isolation y Schema.org en todas las entidades de review existentes y futuras.
- **Servicios transversales** en `ecosistema_jaraba_core` para moderación, agregación de ratings, generación de Schema.org JSON-LD (AggregateRating) e invitaciones post-transacción.
- **Frontend público premium** con star rating widget, listado de reviews, filtros, resúmenes IA, share y páginas de perfil de reviewer — todo en templates Twig limpias (Zero Region Policy).
- **2 entidades nuevas**: `course_review` (vertical formación/LMS) y `content_comment` (comentarios en artículos del Content Hub).
- **GrapesJS Review Widget Block** para incrustar widgets de reviews en landing pages.
- **Schema.org AggregateRating** en todas las entidades que acumulan reviews, visible para Google Rich Snippets.
- **Cumplimiento total** de las directrices del proyecto: TENANT-ISOLATION-ACCESS-001, PREMIUM-FORMS-PATTERN-001, ENTITY-PREPROCESS-001, ICON-CONVENTION-001, ROUTE-LANGPREFIX-001, DART-SASS-MODERN, WCAG-FOCUS-001, i18n, y 20+ directrices más.

### 1.2 Por qué se implementa

La auditoría estratégica (2026-02-26) identificó **20 gaps** organizados en 4 categorías:

| Categoría | Cantidad | Severidad |
|-----------|----------|-----------|
| Seguridad (REV-S) | 3 | Crítica |
| Datos / Bugs (REV-D) | 4 | Alta |
| Arquitectura (REV-A) | 5 | Media-Alta |
| Clase mundial (REV-GAP) | 8 | Media |

Los hallazgos más críticos:
- **REV-S1/S2**: `review_servicios` y `session_review` carecen de `tenant_id`, permitiendo data leakage entre tenants.
- **REV-S3**: Ningún access handler verifica tenant match en update/delete (viola TENANT-ISOLATION-ACCESS-001).
- **REV-A1**: 4 naming schemes incompatibles para los mismos conceptos (`status` vs `state`, `body` vs `comment`, `merchant_response` vs `provider_response` vs `response`).
- **REV-GAP1**: Schema.org AggregateRating ausente en todas las entidades — pérdida directa de rich snippets en Google.
- **REV-GAP2**: Sin frontend público para reviews — los datos existen en la base de datos pero el usuario final no los ve.

### 1.3 Alcance

**Dentro del alcance:**
- Armonización de las 4 entidades de review existentes (`comercio_review`, `review_agro`, `review_servicios`, `session_review`).
- Creación de 2 entidades nuevas (`course_review`, `content_comment`).
- 5 servicios transversales nuevos en `ecosistema_jaraba_core` y `jaraba_ai_agents`.
- Frontend público con SCSS premium, JavaScript vanilla (star rating widget), templates Twig.
- GrapesJS block para incrustar reviews en landing pages.
- Schema.org JSON-LD en todas las páginas que muestran reviews agregados.
- Update hooks para migración de esquema sin pérdida de datos.

**Fuera del alcance (primera iteración, activable bajo demanda):**
- Migración de datos de producción (no hay datos en las tablas de review actualmente — las entidades están definidas pero no pobladas).
- Integración con servicios externos de reviews (Google Reviews, Trustpilot).
- Sistema de disputas/apelaciones (se podrá añadir como fase futura).
- Entidades de review dedicadas para empleabilidad, emprendimiento, jarabalex y andalucia_ei (los servicios transversales y el trait les dan soporte inmediato — las entidades específicas se crean cuando el vertical lo requiera, ~4-8h/vertical).

### 1.4 Filosofía de implementación

1. **Infraestructura compartida + extensión a 10 verticales**: Los campos comunes al 90% de las reviews se definen en `ReviewableEntityTrait`. Cada entidad vertical añade sus campos específicos (ej: `booking_id` en ServiciosConecta, sub-ratings en Mentoring). Los 10 verticales canónicos de `VERTICAL-CANONICAL-001` se cubren: 6 con entidades dedicadas (comercioconecta, agroconecta, serviciosconecta, mentoring, formacion, jaraba_content_hub) y 4 extensibles bajo demanda (empleabilidad, emprendimiento, jarabalex, andalucia_ei) reutilizando el trait y los servicios transversales sin necesidad de entidades nuevas.
2. **No romper lo que funciona**: Las entidades existentes mantienen sus entity type IDs, base tables y handlers. Se añaden campos y se normalizan nombres, pero no se migra a una entidad única.
3. **Frontend premium desde el día 1**: Los reviews deben ser visibles para el público (social proof) con diseño consistente con el design system del tema.
4. **Mobile-first**: Todo el CSS se escribe en base mobile y se amplía con `min-width` media queries.
5. **Textos siempre traducibles**: Todo texto visible en la interfaz usa `{% trans %}` (Twig), `$this->t()` (PHP) o `Drupal.t()` (JS).
6. **Cobertura de los 10 verticales canónicos (VERTICAL-CANONICAL-001)**: Cada vertical recibe reviews/comentarios adaptados a su dominio: evaluaciones de productos (comercio), productores (agro), proveedores (servicios), mentores (mentoring), cursos (formación), artículos (content hub), empleadores/ofertas (empleabilidad), mentores de emprendimiento/startups (emprendimiento), servicios legales/abogados (jarabalex), programas/iniciativas (andalucia_ei).

### 1.5 Estimación

| Fase | Descripción | Horas Min | Horas Max |
|------|-------------|-----------|-----------|
| 1 | ReviewableEntityTrait + armonización de entidades | 10 | 14 |
| 2 | Nuevas entidades (CourseReview, ContentComment) | 6 | 8 |
| 3 | Servicios transversales | 12 | 16 |
| 4 | Controladores + Rutas | 6 | 8 |
| 5 | Frontend (Templates + SCSS + JS) | 14 | 20 |
| 6 | GrapesJS Review Widget Block | 4 | 6 |
| 7 | Schema.org + SEO | 4 | 6 |
| 8 | Security fixes (tenant isolation) | 6 | 8 |
| 9 | Cumplimiento de directrices restantes | 4 | 6 |
| 10 | Testing + QA | 8 | 12 |
| **TOTAL (6 verticales core)** | | **74** | **104** |
| *Extensión 4 verticales adicionales* | *empleabilidad, emprendimiento, jarabalex, andalucia_ei* | *16* | *32* |
| **TOTAL (10 verticales)** | | **90** | **136** |

### 1.6 Riesgos y mitigación

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Conflicto de update hooks con tablas existentes | Media | Alto | Verificar existencia de columnas antes de añadir. Usar `try-catch` en hooks. |
| Incompatibilidad de `ReviewableEntityTrait` con `session_review` (no tiene EntityOwnerTrait) | Alta | Medio | El trait detecta si la entidad usa EntityOwnerTrait y se adapta. |
| Pérdida de datos al renombrar campos (`state` → `status`) | Baja | Crítico | Update hook con `UPDATE` SQL directo para migrar datos, no recrear columna. |
| SCSS compilation errors al añadir nuevo partial | Baja | Bajo | Compilación incremental con verificación post-merge. |
| Colisión de rutas frontend con rutas admin existentes | Media | Medio | Las rutas frontend usan prefijos verticales (`/comercio/reviews`, `/agro/reviews`). |

---

## 2. Inventario de Hallazgos de Auditoría

### 2.1 Seguridad (REV-S1 a REV-S3)

#### REV-S1: `review_servicios` carece de `tenant_id`

**Severidad**: Crítica
**Archivo**: `jaraba_servicios_conecta/src/Entity/ReviewServicios.php`
**Problema**: La entidad `review_servicios` no tiene campo `tenant_id`. En un entorno multi-tenant, las reviews de servicios de un tenant son visibles y editables por otro.
**Remediación**: Añadir campo `tenant_id` como `entity_reference` a `group` (siguiendo TENANT-BRIDGE-001) mediante update hook.

#### REV-S2: `session_review` carece de `tenant_id`

**Severidad**: Crítica
**Archivo**: `jaraba_mentoring/src/Entity/SessionReview.php`
**Problema**: Misma situación que REV-S1. La entidad no tiene campo `tenant_id`.
**Remediación**: Añadir campo `tenant_id` como `entity_reference` a `group` mediante update hook. Además, se debe añadir un access handler (actualmente usa el default de Drupal sin ningún control de acceso personalizado).

#### REV-S3: Ningún access handler verifica tenant match

**Severidad**: Crítica
**Archivos**:
- `jaraba_comercio_conecta/src/Access/ReviewRetailAccessControlHandler.php`
- `jaraba_agroconecta_core/src/Entity/ReviewAgroAccessControlHandler.php`
- `jaraba_servicios_conecta/src/Access/ReviewServiciosAccessControlHandler.php`

**Problema**: Los 3 access handlers existentes verifican permisos pero NO comprueban que el `tenant_id` de la entidad coincida con el tenant del usuario actual. Un usuario del tenant A con permiso `edit own comercio reviews` puede editar reviews del tenant B.
**Remediación**: Refactorizar los 3 handlers para implementar `EntityHandlerInterface` (patrón DI) e inyectar `TenantContextService`. Verificar `$entity->get('tenant_id')->target_id === $currentTenantGroupId` para operaciones `update` y `delete`.

### 2.2 Bugs y gaps de datos (REV-D1 a REV-D4)

#### REV-D1: `tenant_id` apunta a `taxonomy_term` en vez de `group`

**Severidad**: Alta
**Archivos**: `ReviewRetail.php`, `ReviewAgro.php`
**Problema**: Ambas entidades definen `tenant_id` como `entity_reference` a `taxonomy_term` (vocabulario `tenants`). Esto viola TENANT-BRIDGE-001 que establece que `tenant_id` debe ser entity_reference a `group`. Los términos de taxonomía son entidades de billing; los groups son entidades de aislamiento de contenido.
**Remediación**: Migrar `tenant_id` de `entity_reference → taxonomy_term` a `entity_reference → group` mediante update hook con mapeo de IDs via `TenantBridgeService::getGroupForTenant()`.

#### REV-D2: `session_review` carece de `changed` y `EntityOwnerTrait`

**Severidad**: Alta
**Archivo**: `jaraba_mentoring/src/Entity/SessionReview.php`
**Problema**: No implementa `EntityChangedInterface` ni `EntityOwnerInterface`. Carece de campo `changed`. No tiene `entity_keys['label']` ni `entity_keys['owner']`.
**Remediación**: Añadir `EntityChangedTrait`, `EntityOwnerTrait`, campos `changed` y `uid`, entity keys `label` y `owner`.

#### REV-D3: `ReviewServicios` usa `reviewer_uid` como owner key en vez de `uid`

**Severidad**: Media
**Archivo**: `jaraba_servicios_conecta/src/Entity/ReviewServicios.php`
**Problema**: El entity_key `owner` apunta a `reviewer_uid`, un campo personalizado. Esto funciona con `EntityOwnerTrait::getDefaultEntityOwner()` pero rompe la consistencia con las otras entidades que usan `uid`.
**Remediación**: Mantener `reviewer_uid` como campo (para referencia explícita al autor) pero añadir `uid` estándar de `ownerBaseFieldDefinitions()` y actualizar `entity_keys['owner']` a `uid`. El campo `reviewer_uid` se mantiene como alias.

#### REV-D4: `session_review` carece de entity links completos

**Severidad**: Media
**Archivo**: `jaraba_mentoring/src/Entity/SessionReview.php`
**Problema**: Solo tiene link `collection`. Faltan `canonical`, `add-form`, `edit-form`, `delete-form`. Tampoco tiene `route_provider` handler ni `access` handler.
**Remediación**: Añadir los 5 links estándar, `AdminHtmlRouteProvider`, y un access handler propio.

### 2.3 Inconsistencias arquitectónicas (REV-A1 a REV-A5)

#### REV-A1: Naming heterogéneo de campos

| Concepto | comercio_review | review_agro | review_servicios | session_review |
|----------|----------------|-------------|------------------|----------------|
| Estado moderación | `status` (list_string) | `state` (string) | `status` (list_string) | *(ausente)* |
| Texto principal | `body` | `body` | `comment` | `comment` |
| Respuesta propietario | `merchant_response` | `response` | `provider_response` | *(ausente)* |
| Fecha respuesta | `merchant_response_date` (timestamp) | *(ausente — usa response_by)* | `response_date` (datetime) | *(ausente)* |
| Referencia a target | `entity_type_ref` + `entity_id_ref` | `target_entity_type` + `target_entity_id` | `provider_id` (entity_reference) | `session_id` (entity_reference) |

**Remediación**: Estandarizar vía `ReviewableEntityTrait` con nombres canónicos. Los campos existentes se mantienen (no renombrar columnas SQL) pero se crean alias en métodos helper del trait.

#### REV-A2: Valores de moderación heterogéneos

| Valor | comercio_review | review_agro | review_servicios |
|-------|----------------|-------------|------------------|
| Pendiente | `pending` | `pending` | `pending` |
| Aprobado | `approved` | `approved` | `approved` |
| Rechazado | `rejected` | `rejected` | `rejected` |
| Marcado | `flagged` | `flagged` | *(ausente)* |

**Remediación**: Añadir `flagged` a `review_servicios`. El trait define constantes compartidas.

#### REV-A3: Denormalización de ratings inconsistente

- `ServiciosConecta`: `recalculateAverageRating()` escribe en `provider_profile` al aprobar.
- `AgroConecta`: Calcula `getRatingStats()` on-demand (sin denormalización).
- `ComercioConecta`: Calcula `getReviewSummary()` on-demand.
- `Mentoring`: Sin cálculo de ratings en absoluto.

**Remediación**: `ReviewAggregationService` transversal que recalcula y denormaliza en la entidad target tras cada aprobación/rechazo, con cache invalidation.

#### REV-A4: API REST incompleta en ServiciosConecta y Mentoring

| Funcionalidad | AgroConecta | ComercioConecta | ServiciosConecta | Mentoring |
|---------------|-------------|-----------------|------------------|-----------|
| Listar reviews | `/api/v1/agro/reviews` | `/api/v1/comercio/reviews/{type}/{id}` | *(ausente)* | *(ausente)* |
| Crear review | `POST /api/v1/agro/reviews` | `POST /api/v1/comercio/reviews` | *(ausente)* | *(ausente)* |
| Rating stats | `/api/v1/agro/reviews/stats` | *(ausente)* | *(ausente)* | *(ausente)* |
| Moderar | `POST /api/v1/agro/reviews/{id}/moderate` | *(ausente)* | *(ausente)* | *(ausente)* |
| Responder | `POST /api/v1/agro/reviews/{id}/respond` | *(ausente)* | *(ausente)* | *(ausente)* |
| AI copilot | `POST /api/v1/producer/copilot/generate/review-response` | `POST /api/v1/merchant/copilot/generate/review-response` | *(ausente)* | *(ausente)* |

**Remediación**: Crear rutas API completas para los 4 verticales siguiendo el patrón de AgroConecta (el más completo).

#### REV-A5: Namespace del access handler de ReviewAgro

**Archivo**: `jaraba_agroconecta_core/src/Entity/ReviewAgroAccessControlHandler.php`
**Problema**: Vive en el namespace `Entity\` en vez de `Access\` como dictan las convenciones del proyecto.
**Remediación**: Mover a namespace `Access\` y actualizar la anotación de la entidad.

### 2.4 Gaps de clase mundial (REV-GAP1 a REV-GAP8)

#### REV-GAP1: Schema.org AggregateRating ausente

**Problema**: Ninguna entidad del SaaS emite `AggregateRating` en el JSON-LD. Google no puede mostrar rich snippets con estrellas.
**Remediación**: `ReviewSchemaOrgService` genera JSON-LD `AggregateRating` + `Review[]` inyectado via `hook_preprocess_html()` en páginas de detalle de producto, proveedor, productor, curso y artículo.

#### REV-GAP2: Sin frontend público para reviews

**Problema**: Las reviews solo se ven en el admin. No hay templates Twig, ni páginas de listado, ni widget de estrellas.
**Remediación**: Crear componente frontend completo: star rating display, listado de reviews, formulario de envío via slide-panel, filtros por rating, resumen IA.

#### REV-GAP3: Sin resúmenes IA de reviews

**Problema**: Plataformas de clase mundial (Amazon, Airbnb) generan resúmenes textuales de las reviews con IA.
**Remediación**: `ReviewAiSummaryService` usando Haiku 4.5 (fast tier) via `ModelRouterService`.

#### REV-GAP4: Sin invitación post-transacción

**Problema**: No existe mecanismo para invitar al usuario a dejar una review tras completar una compra/reserva/sesión.
**Remediación**: `ReviewInvitationService` que encola emails y notificaciones push tras eventos de transacción completada.

#### REV-GAP5: Sin fotos en reviews (excepto ComercioConecta)

**Problema**: Solo `comercio_review` tiene campo `photos`. Las reviews con fotos generan 3x más conversiones.
**Remediación**: Añadir campo `photos` (JSON array de file IDs) en el trait compartido, con image styles `review_photo_thumb` (120x120) y `review_photo_full` (800x600).

#### REV-GAP6: Sin helpful votes (excepto ComercioConecta)

**Problema**: Solo `comercio_review` tiene `helpful_count`. El social proof de "X personas encontraron útil esta reseña" es estándar.
**Remediación**: Añadir `helpful_count` al trait compartido.

#### REV-GAP7: Sin CourseReview (LMS)

**Problema**: El módulo `jaraba_lms` no tiene entidad de review para cursos. Los cursos online sin reviews tienen tasas de inscripción 60% menores.
**Remediación**: Crear entidad `course_review` con trait compartido + campos específicos (course_id, progress_at_review, difficulty_rating).

#### REV-GAP8: Sin ContentComment (Content Hub)

**Problema**: Los artículos del Content Hub no admiten comentarios. Los blogs de clase mundial tienen sistema de comentarios con moderación, threading y notificaciones.
**Remediación**: Crear entidad `content_comment` con threading (parent_id), moderación y notificaciones al autor.

---

## 3. Tabla de Correspondencia con Especificaciones Técnicas

### 3.1 Mapping de entidades existentes

| Entidad | Módulo | Base Table | Campos Actuales | Campos a Añadir (via Trait/Update Hook) |
|---------|--------|------------|-----------------|----------------------------------------|
| `comercio_review` | `jaraba_comercio_conecta` | `comercio_review` | 14 campos | `ai_summary`, migrate `tenant_id` a group |
| `review_agro` | `jaraba_agroconecta_core` | `review_agro` | 14 campos | `helpful_count`, `ai_summary`, migrate `tenant_id` a group |
| `review_servicios` | `jaraba_servicios_conecta` | `review_servicios` | 12 campos | `tenant_id`, `photos`, `helpful_count`, `verified_purchase`, `ai_summary`, `flagged` status |
| `session_review` | `jaraba_mentoring` | `session_review` | 11 campos | `tenant_id`, `status`, `uid`, `changed`, `photos`, `helpful_count`, `ai_summary` |

### 3.2 Mapping de servicios existentes

| Servicio Existente | Módulo | Se Mantiene | Delega A |
|--------------------|--------|-------------|----------|
| `ReviewRetailService` | `jaraba_comercio_conecta` | Sí | `ReviewModerationService` (moderación), `ReviewAggregationService` (stats) |
| `ReviewAgroService` | `jaraba_agroconecta_core` | Sí | `ReviewModerationService`, `ReviewAggregationService` |
| `ReviewService` | `jaraba_servicios_conecta` | Sí, renombrar a `ServiceReviewService` | `ReviewModerationService`, `ReviewAggregationService` |
| *(inexistente)* | `jaraba_mentoring` | — | Se crea `MentoringReviewService` que usa transversales |

### 3.3 Mapping de rutas y controladores

| Vertical | Rutas Existentes | Rutas Nuevas Frontend | Rutas Nuevas API |
|----------|------------------|-----------------------|------------------|
| ComercioConecta | 3 API + entity CRUD | `/comercio/{merchant}/reviews` | `GET /api/v1/comercio/reviews/stats`, `POST /api/v1/comercio/reviews/{id}/moderate`, `POST /api/v1/comercio/reviews/{id}/respond` |
| AgroConecta | 6 API + entity CRUD | `/agro/{producer}/reviews` | *(completo)* |
| ServiciosConecta | Entity CRUD solo | `/servicios/{provider}/reviews` | Todas: list, create, stats, moderate, respond |
| Mentoring | Collection solo | `/mentoring/sessions/{id}/reviews` | Todas: list, create, stats |
| LMS | *(inexistente)* | `/cursos/{course}/reviews` | Todas |
| Content Hub | *(inexistente)* | *(inline en artículos)* | CRUD para `content_comment` |
| Empleabilidad | *(inexistente)* | `/empleo/empresas/{employer}/reviews` | `GET /api/v1/empleabilidad/reviews/stats`, `POST .../create` (extensible) |
| Emprendimiento | *(inexistente)* | `/emprendimiento/mentores/{mentor}/reviews` | `GET /api/v1/emprendimiento/reviews/stats`, `POST .../create` (extensible) |
| JarabaLex | *(inexistente)* | `/legal/abogados/{lawyer}/reviews` | `GET /api/v1/jarabalex/reviews/stats`, `POST .../create` (extensible) |
| Andalucía EI | *(inexistente)* | `/andalucia-ei/programas/{program}/reviews` | `GET /api/v1/andalucia-ei/reviews/stats`, `POST .../create` (extensible) |

> **Nota sobre verticales extensibles**: Los 4 últimos verticales (empleabilidad, emprendimiento, jarabalex, andalucia_ei) no requieren entidades de review dedicadas en la primera iteración. Reutilizan `ReviewableEntityTrait` aplicado a sus entidades de dominio existentes (ej: `JobPosting`, `MentorProfile`, `LegalService`, `SolicitudEi`) para recoger valoraciones directamente sobre esas entidades, o bien crean una entidad de review genérica (`vertical_review`) usando el trait cuando sea necesario. Los 5 servicios transversales (`ReviewModerationService`, `ReviewAggregationService`, `ReviewSchemaOrgService`, `ReviewInvitationService`, `ReviewAiSummaryService`) operan sobre cualquier entidad que implemente el trait — no necesitan conocer el vertical específico.

### 3.4 Funcionalidades nuevas (clase mundial)

| Funcionalidad | Servicio Responsable | Archivos Nuevos | Entidades Afectadas |
|---------------|---------------------|-----------------|---------------------|
| Moderación unificada | `ReviewModerationService` | `ecosistema_jaraba_core/src/Service/ReviewModerationService.php` | Todas (6) |
| Agregación + denormalización | `ReviewAggregationService` | `ecosistema_jaraba_core/src/Service/ReviewAggregationService.php` | Todas (6) |
| Schema.org AggregateRating | `ReviewSchemaOrgService` | `ecosistema_jaraba_core/src/Service/ReviewSchemaOrgService.php` | Todas (6) |
| Invitación post-transacción | `ReviewInvitationService` | `ecosistema_jaraba_core/src/Service/ReviewInvitationService.php` | Todas excepto ContentComment |
| Resúmenes IA | `ReviewAiSummaryService` | `jaraba_ai_agents/src/Service/ReviewAiSummaryService.php` | Todas (6) |
| Star rating widget | *(frontend JS)* | `ecosistema_jaraba_theme/js/star-rating.js` | Todas (6) |
| GrapesJS block | *(page builder)* | `jaraba_page_builder/js/plugins/reviews-block.js` | Productos, proveedores, cursos |
| Review widget partial | *(Twig partial)* | `ecosistema_jaraba_theme/templates/partials/_review-widget.html.twig` | Todas las páginas de detalle |

---

## 4. Tabla de Cumplimiento de Directrices del Proyecto

| Código Directriz | Nombre | Estado Actual | Estado Objetivo | Dónde se Aplica | Notas |
|------------------|--------|--------------|-----------------|-----------------|-------|
| **TENANT-ISOLATION-ACCESS-001** | Verificar tenant match en access handlers | FALLA (REV-S1/S2/S3) | CUMPLE | 6 access handlers | DI con TenantContextService |
| **TENANT-BRIDGE-001** | tenant_id como entity_reference a group | FALLA (REV-D1) | CUMPLE | 6 entidades de review | Migrar taxonomy_term → group |
| **PREMIUM-FORMS-PATTERN-001** | Forms extienden PremiumEntityFormBase | PARCIAL (session_review usa ContentEntityForm) | CUMPLE | 6 formularios | getSectionDefinitions() + getFormIcon() |
| **ENTITY-PREPROCESS-001** | template_preprocess_{entity_type}() en .module | AUSENTE (0 de 4 entidades) | CUMPLE | 6 preprocess functions en 5 .module files | Extraer primitivas, resolver referencias |
| **PRESAVE-RESILIENCE-001** | try-catch en presave con servicios opcionales | CUMPLE parcial | CUMPLE | presave hooks para AI summary, aggregation | \Drupal::hasService() + try-catch |
| **ROUTE-LANGPREFIX-001** | URLs con Url::fromRoute() nunca hardcoded | CUMPLE en API, FALTA en templates | CUMPLE | Todos los templates Twig y JS fetch | `path('route.name')` en Twig, `Drupal.url()` en JS |
| **ICON-CONVENTION-001** | jaraba_icon(category, name, options) | AUSENTE en reviews | CUMPLE | Templates de review, formularios | Colores: corporate, impulse, innovation |
| **TWIG-XSS-001** | Autoescaping por defecto | CUMPLE | CUMPLE | Todos los templates nuevos | `\|raw` solo para HTML sanitizado |
| **CSRF-REQUEST-HEADER-001** | _csrf_request_header_token en rutas POST/PATCH/DELETE API | CUMPLE en AgroConecta, FALTA en ComercioConecta | CUMPLE | Todas las rutas API de reviews | X-CSRF-Token header obligatorio |
| **API-WHITELIST-001** | ALLOWED_FIELDS en endpoints de guardado | PARCIAL | CUMPLE | Todos los endpoints POST de reviews | Array explícito de campos permitidos |
| **ZERO-REGION-POLICY** | Frontend sin {{ page.* }} ni bloques heredados | N/A (sin frontend actual) | CUMPLE | Páginas de review frontend | Templates page--reviews.html.twig con layout propio |
| **DART-SASS-MODERN** | @use 'sass:color' no darken()/lighten() | N/A (sin SCSS de reviews) | CUMPLE | _reviews.scss, _star-rating.scss | @use 'sass:color', color.adjust() |
| **MOBILE-FIRST** | Base mobile → min-width queries | N/A | CUMPLE | Todos los componentes de review | @include respond-to(md) para desktop |
| **SLIDE-PANEL-RENDER-001** | renderPlain() para slide-panel, #action explícito | N/A | CUMPLE | Formulario de review en frontend | isSlidePanelRequest() detection |
| **LABEL-NULLSAFE-001** | entity_keys.label definido | FALLA (session_review sin label) | CUMPLE | SessionReview, CourseReview, ContentComment | label = computed title field |
| **i18n completa** | Textos traducibles en Twig/PHP/JS | PARCIAL (entidades usan t() pero templates ausentes) | CUMPLE | Todos los archivos nuevos | {% trans %}...{% endtrans %} en Twig |
| **WCAG-FOCUS-001** | :focus-visible en todos los interactivos | N/A | CUMPLE | Star rating, botones, enlaces de reviews | outline: $ej-focus-ring-width solid $ej-focus-ring-color |
| **INNERHTML-XSS-001** | Drupal.checkPlain() en JS | N/A | CUMPLE | star-rating.js, review-widget.js | checkPlain() para todo texto insertado |
| **CONFIG-SCHEMA-001** | Schema para todos los settings nuevos | N/A | CUMPLE | Review theme settings | ecosistema_jaraba_theme.schema.yml |
| **VERTICAL-CANONICAL-001** | Nombres de vertical canónicos | CUMPLE | CUMPLE | Permisos, rutas, body classes | comercioconecta, agroconecta, serviciosconecta, formacion |
| **SCSS-VARS-SSOT-001** | Variables SCSS como fallback de CSS vars | N/A | CUMPLE | _reviews.scss | `var(--ej-color-warning, $ej-color-warning)` para estrellas |
| **SMART-AGENT-CONSTRUCTOR-001** | SmartBaseAgent con 10 args | N/A | CUMPLE | ReviewAiSummaryService usa fast tier | No es agente — usa ModelRouterService directo |
| **FORM-CACHE-001** | No setCached(TRUE) incondicional | N/A | CUMPLE | Formularios de review | Drupal auto-maneja entity form cache |

---

## 5. Requisitos Previos

### 5.1 Stack tecnológico

| Componente | Versión | Uso en Reviews |
|------------|---------|----------------|
| PHP | 8.4 | Entidades, servicios, controladores, access handlers |
| Drupal | 11 | Framework base, entity API, routing, Twig |
| MariaDB | 10.11+ | Storage de las 6 entidades de review, índices |
| Redis | 7.4 | Cache de render arrays de reviews, AggregateRating cache |
| Dart Sass | Último | Compilación de `_reviews.scss`, `_star-rating.scss` |
| Node.js | 22.x | Runtime para Dart Sass (via npx) |
| Twig | 3.x | Templates de review (integrado en Drupal 11) |
| GrapesJS | 0.22.x | Review Widget Block para landing pages |

### 5.2 Módulos Drupal requeridos

- `ecosistema_jaraba_core` (servicios transversales: moderación, agregación, Schema.org, invitación)
- `jaraba_ai_agents` (servicio de resúmenes IA)
- `jaraba_page_builder` (GrapesJS review widget block)
- Cada módulo vertical (`jaraba_comercio_conecta`, `jaraba_agroconecta_core`, `jaraba_servicios_conecta`, `jaraba_mentoring`, `jaraba_lms`, `jaraba_content_hub`)

### 5.3 Infraestructura

```bash
# Lando environment
lando start
lando drush cr
lando drush updb  # Ejecutar update hooks
```

### 5.4 Documentos de referencia

| Documento | Ubicación |
|-----------|-----------|
| Auditoría estratégica | `docs/analisis/2026-02-26_Auditoria_Sistemas_Calificaciones_Comentarios_Clase_Mundial_v1.md` |
| Directrices del proyecto | `docs/00_DIRECTRICES_PROYECTO.md` v87.0.0 |
| Arquitectura de theming | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |
| Flujo de trabajo Claude | `docs/00_FLUJO_TRABAJO_CLAUDE.md` v41.0.0 |
| Plan de implementación Blog | `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` |

---

## 6. Entorno de Desarrollo

### 6.1 Comandos Docker/Lando

```bash
# Iniciar entorno
lando start

# Reconstruir cache
lando drush cr

# Ejecutar update hooks
lando drush updb

# Ejecutar cron
lando drush cron

# Ejecutar tests del módulo
lando php web/core/scripts/run-tests.sh --module ecosistema_jaraba_core

# Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/main.css --style=compressed
```

### 6.2 URLs de verificación

| Descripción | URL |
|-------------|-----|
| Reviews de producto (ComercioConecta) | `https://jaraba-saas.lndo.site/es/comercio/{merchant_slug}/reviews` |
| Reviews de productor (AgroConecta) | `https://jaraba-saas.lndo.site/es/agro/{producer_slug}/reviews` |
| Reviews de proveedor (ServiciosConecta) | `https://jaraba-saas.lndo.site/es/servicios/{provider_slug}/reviews` |
| Reviews de curso (LMS) | `https://jaraba-saas.lndo.site/es/cursos/{course_slug}/reviews` |
| Comentarios de artículo (Content Hub) | `https://jaraba-saas.lndo.site/es/blog/{article_slug}#comments` |
| Admin reviews comercio | `https://jaraba-saas.lndo.site/admin/content/comercio-reviews` |
| Admin reviews agro | `https://jaraba-saas.lndo.site/admin/content/agro-reviews` |
| Admin reviews servicios | `https://jaraba-saas.lndo.site/admin/content/servicios-reviews` |
| Field UI comercio_review | `https://jaraba-saas.lndo.site/admin/structure/comercio-reviews` |

### 6.3 Compilación SCSS

```bash
# Verificar cero warnings
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/main.css --style=compressed 2>&1"
```

---

## 7. Arquitectura — ReviewableEntityTrait

### 7.1 Decisión de diseño: trait compartido vs entidad única

**Decisión**: Trait compartido (`ReviewableEntityTrait`), NO entidad única polimórfica.

**Razones**:
1. Las entidades existentes tienen entity type IDs, base tables y handlers propios. Migrar a una entidad única requeriría reescribir toda la capa de acceso, formularios, rutas y templates de 4 módulos.
2. Cada vertical tiene campos específicos irreductibles (`booking_id` en servicios, `sub-ratings` en mentoring, `producer_response` en agro).
3. El patrón trait + servicios transversales da 90% de code reuse sin romper encapsulación.
4. Drupal 11 no soporta herencia de ContentEntity — solo traits pueden compartir baseFieldDefinitions.

### 7.2 Campos del trait

El trait aporta los campos comunes al 90% de las entidades de review:

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `status` | `list_string` | Sí | `pending`, `approved`, `rejected`, `flagged` |
| `helpful_count` | `integer` | No (default 0) | Votos "útil" |
| `photos` | `string_long` | No | JSON array de file entity IDs |
| `ai_summary` | `string_long` | No | Resumen generado por IA |
| `ai_summary_generated_at` | `timestamp` | No | Fecha de generación del resumen |

**Nota**: Campos como `rating`, `title`, `body`, `tenant_id`, `uid`, `created`, `changed` ya existen (o deben existir) en todas las entidades como campos propios. El trait NO los redefine — son responsabilidad de cada entidad.

### 7.3 Implementación del trait

**Archivo**: `ecosistema_jaraba_core/src/Entity/ReviewableEntityTrait.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Proporciona campos compartidos para entidades de review.
 *
 * Estructura: Define 5 campos que son comunes al 90% de las entidades
 *   de review del ecosistema (status, helpful_count, photos, ai_summary,
 *   ai_summary_generated_at). Cada entidad vertical usa este trait en
 *   su baseFieldDefinitions() para evitar duplicación.
 *
 * Lógica: Los campos de moderación (status) y social proof (helpful_count)
 *   son universales. Los campos de IA (ai_summary) son opcionales en
 *   runtime (se pueblan asincrónicamente via cron). Las fotos (photos)
 *   se almacenan como JSON array de file entity IDs para evitar una tabla
 *   de campo separada por cada vertical.
 *
 * Uso:
 *   use ReviewableEntityTrait;
 *   public static function baseFieldDefinitions(...) {
 *     $fields = parent::baseFieldDefinitions($entity_type);
 *     $fields += static::reviewableBaseFieldDefinitions();
 *     // Campos verticales específicos...
 *     return $fields;
 *   }
 *
 * @see \Drupal\jaraba_comercio_conecta\Entity\ReviewRetail
 * @see \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro
 * @see \Drupal\jaraba_servicios_conecta\Entity\ReviewServicios
 * @see \Drupal\jaraba_mentoring\Entity\SessionReview
 */
trait ReviewableEntityTrait {

  /**
   * Constantes de estado de moderación.
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_APPROVED = 'approved';
  public const STATUS_REJECTED = 'rejected';
  public const STATUS_FLAGGED = 'flagged';

  /**
   * Devuelve los campos compartidos para entidades de review.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   Array indexado por nombre de campo.
   */
  public static function reviewableBaseFieldDefinitions(): array {
    $fields = [];

    $fields['review_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado de moderación'))
      ->setDescription(new TranslatableMarkup('Estado en el flujo de moderación.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSetting('allowed_values', [
        self::STATUS_PENDING => new TranslatableMarkup('Pendiente'),
        self::STATUS_APPROVED => new TranslatableMarkup('Aprobada'),
        self::STATUS_REJECTED => new TranslatableMarkup('Rechazada'),
        self::STATUS_FLAGGED => new TranslatableMarkup('Marcada'),
      ])
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['helpful_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Votos de utilidad'))
      ->setDescription(new TranslatableMarkup('Número de usuarios que marcaron esta reseña como útil.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['photos'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Fotos'))
      ->setDescription(new TranslatableMarkup('JSON array de file entity IDs adjuntos a la reseña.'))
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Resumen IA'))
      ->setDescription(new TranslatableMarkup('Resumen generado automáticamente por IA a partir del conjunto de reseñas.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_summary_generated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de resumen IA'))
      ->setDescription(new TranslatableMarkup('Timestamp de la última generación del resumen IA.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Devuelve la etiqueta legible del estado actual.
   */
  public function getReviewStatusLabel(): string {
    $labels = [
      self::STATUS_PENDING => (string) new TranslatableMarkup('Pendiente de moderación'),
      self::STATUS_APPROVED => (string) new TranslatableMarkup('Aprobada'),
      self::STATUS_REJECTED => (string) new TranslatableMarkup('Rechazada'),
      self::STATUS_FLAGGED => (string) new TranslatableMarkup('Marcada para revisión'),
    ];
    $status = $this->getReviewStatus();
    return $labels[$status] ?? $status;
  }

  /**
   * Devuelve el estado de moderación.
   *
   * Lógica: Busca primero en 'review_status' (nombre canónico del trait).
   *   Si no existe, busca en 'status' o 'state' (nombres legacy de
   *   entidades existentes) para retrocompatibilidad.
   */
  public function getReviewStatus(): string {
    if ($this->hasField('review_status') && !$this->get('review_status')->isEmpty()) {
      return $this->get('review_status')->value;
    }
    if ($this->hasField('status') && !$this->get('status')->isEmpty()) {
      return $this->get('status')->value;
    }
    if ($this->hasField('state') && !$this->get('state')->isEmpty()) {
      return $this->get('state')->value;
    }
    return self::STATUS_PENDING;
  }

  /**
   * Indica si la review está aprobada.
   */
  public function isApprovedReview(): bool {
    return $this->getReviewStatus() === self::STATUS_APPROVED;
  }

  /**
   * Devuelve el rating como cadena de estrellas Unicode.
   */
  public function getRatingStarsDisplay(): string {
    $rating = 0;
    if ($this->hasField('rating')) {
      $rating = (int) ($this->get('rating')->value ?? 0);
    }
    elseif ($this->hasField('overall_rating')) {
      $rating = (int) ($this->get('overall_rating')->value ?? 0);
    }
    $rating = max(0, min(5, $rating));
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
  }

}
```

### 7.4 Uso del trait en entidades existentes

Cada entidad existente añade `use ReviewableEntityTrait;` y llama a `static::reviewableBaseFieldDefinitions()` en su `baseFieldDefinitions()`. Los campos que ya existen en la entidad (como `status` en ComercioReview) NO se duplican — el trait usa el nombre `review_status` para evitar colisiones. Los métodos helper del trait son retrocompatibles gracias al fallback (`status` → `state` → `review_status`).

**Ejemplo para ComercioReview** (cambios mínimos):

```php
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;

class ReviewRetail extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {
  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    // Trait fields (solo los que no existen aún: ai_summary, ai_summary_generated_at).
    // helpful_count, photos y status ya existen como campos propios.
    $fields['ai_summary'] = static::reviewableBaseFieldDefinitions()['ai_summary'];
    $fields['ai_summary_generated_at'] = static::reviewableBaseFieldDefinitions()['ai_summary_generated_at'];
    // Campos verticales específicos existentes...
    // ... (sin cambios)
    return $fields;
  }
}
```

**Ejemplo para SessionReview** (cambios extensos — necesita muchos campos nuevos):

```php
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;

class SessionReview extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {
  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    // Todos los campos del trait (status, helpful_count, photos, ai_summary, ai_summary_generated_at).
    $fields += static::reviewableBaseFieldDefinitions();
    // tenant_id (REV-S2 fix)
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);
    // Campos verticales existentes...
    $fields['session_id'] = /* ... */;
    // ... (sin cambios en campos existentes)
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));
    return $fields;
  }
}
```

### 7.5 Update hooks para migración de esquema

Los update hooks se definen en cada módulo vertical. Son idempotentes (verifican existencia de columna antes de añadir).

**Patrón genérico del update hook**:

```php
function MODULE_update_NNNNN(): void {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type_id = 'ENTITY_TYPE';

  // Verificar que la entidad existe.
  if (!$update_manager->getEntityType($entity_type_id)) {
    return;
  }

  $fields_to_install = [
    'ai_summary' => BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resumen IA'))
      ->setTargetEntityTypeId($entity_type_id),
    'ai_summary_generated_at' => BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de resumen IA'))
      ->setTargetEntityTypeId($entity_type_id),
  ];

  foreach ($fields_to_install as $field_name => $definition) {
    if (!$update_manager->getFieldStorageDefinition($field_name, $entity_type_id)) {
      $update_manager->installFieldStorageDefinition(
        $field_name,
        $entity_type_id,
        'MODULE',
        $definition
      );
      \Drupal::logger('MODULE')->info('Installed field @field on @entity.', [
        '@field' => $field_name,
        '@entity' => $entity_type_id,
      ]);
    }
  }
}
```

---

## 8. Arquitectura — Armonización de Entidades Existentes

### 8.1 ComercioReview: campos a añadir

| Campo | Tipo | Descripción | Update Hook |
|-------|------|-------------|-------------|
| `ai_summary` | `string_long` | Resumen IA generado | `jaraba_comercio_conecta_update_10004` |
| `ai_summary_generated_at` | `timestamp` | Fecha generación IA | `jaraba_comercio_conecta_update_10004` |

**Migración de `tenant_id`**: De `entity_reference → taxonomy_term` a `entity_reference → group`. Este es un cambio de `target_type` que requiere:
1. Leer IDs actuales de taxonomy_term.
2. Mapear via `TenantBridgeService::getGroupForTenant()`.
3. Actualizar columna con nuevos IDs.
4. Cambiar field storage definition.

Esto se implementa en `jaraba_comercio_conecta_update_10005`.

### 8.2 ReviewServicios: campos a añadir

| Campo | Tipo | Descripción | Update Hook |
|-------|------|-------------|-------------|
| `tenant_id` | `entity_reference → group` | Tenant del review (REV-S1) | `jaraba_servicios_conecta_update_10002` |
| `verified_purchase` | `boolean` | Reserva verificada | `jaraba_servicios_conecta_update_10002` |
| `helpful_count` | `integer` | Votos utilidad | `jaraba_servicios_conecta_update_10002` |
| `photos` | `string_long` | Fotos JSON | `jaraba_servicios_conecta_update_10002` |
| `ai_summary` | `string_long` | Resumen IA | `jaraba_servicios_conecta_update_10002` |
| `ai_summary_generated_at` | `timestamp` | Fecha IA | `jaraba_servicios_conecta_update_10002` |

**Nota sobre `status`**: El campo ya existe con 3 valores (pending/approved/rejected). Se debe añadir `flagged` a los `allowed_values`. Esto requiere actualizar la field storage definition, no recrear el campo.

### 8.3 SessionReview: elevación a estándar

**Cambios en la clase de entidad**:

1. Añadir `implements EntityChangedInterface, EntityOwnerInterface`.
2. Añadir `use EntityChangedTrait; use EntityOwnerTrait; use ReviewableEntityTrait;`.
3. Añadir entity_keys: `"label" = "title_computed"`, `"owner" = "uid"`.
4. Añadir handlers: `"access"`, `"route_provider" = AdminHtmlRouteProvider`.
5. Añadir links: `canonical`, `add-form`, `edit-form`, `delete-form`.
6. Añadir `field_ui_base_route`.
7. Cambiar form handler de `ContentEntityForm` a `SessionReviewForm extends PremiumEntityFormBase`.

**Campos a añadir** (via `jaraba_mentoring_update_10003`):

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `uid` | ownerBaseFieldDefinitions | Owner estándar |
| `tenant_id` | `entity_reference → group` | REV-S2 fix |
| `title` | `string` (computed, o basado en tipo + session) | Label key |
| `review_status` | `list_string` (del trait) | Moderación |
| `helpful_count` | `integer` | Votos utilidad |
| `photos` | `string_long` | Fotos JSON |
| `ai_summary` | `string_long` | Resumen IA |
| `ai_summary_generated_at` | `timestamp` | Fecha IA |
| `changed` | `changed` | Timestamp modificación |

### 8.4 ReviewAgro: ajustes menores

| Cambio | Descripción | Update Hook |
|--------|-------------|-------------|
| Añadir `helpful_count` | Votos utilidad (REV-GAP6) | `jaraba_agroconecta_core_update_10003` |
| Añadir `ai_summary` | Resumen IA | `jaraba_agroconecta_core_update_10003` |
| Añadir `ai_summary_generated_at` | Fecha IA | `jaraba_agroconecta_core_update_10003` |
| Migrar `tenant_id` | taxonomy_term → group (REV-D1) | `jaraba_agroconecta_core_update_10004` |
| Añadir `use ReviewableEntityTrait` | Trait compartido | Código |
| Mover access handler | `Entity\` → `Access\` namespace (REV-A5) | Código + anotación |

### 8.5 Armonización de field names

**Estrategia**: NO renombrar columnas SQL existentes. En su lugar, los métodos del trait y los servicios transversales aceptan los nombres heterogéneos y los normalizan internamente.

El `ReviewModerationService` usa un mapping:

```php
private const STATUS_FIELD_MAP = [
  'comercio_review' => 'status',
  'review_agro' => 'state',
  'review_servicios' => 'status',
  'session_review' => 'review_status',
  'course_review' => 'review_status',
  'content_comment' => 'review_status',
];
```

### 8.6 Nuevas entidades: CourseReview y ContentComment

#### CourseReview

**Archivo**: `jaraba_lms/src/Entity/CourseReview.php`

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | `integer` | Auto | Primary key |
| `uuid` | `uuid` | Auto | UUID |
| `uid` | ownerBaseFieldDefinitions | Sí | Autor |
| `tenant_id` | `entity_reference → group` | Sí | Tenant |
| `course_id` | `entity_reference → course` | Sí | Curso evaluado |
| `rating` | `integer` (1-5) | Sí | Valoración global |
| `difficulty_rating` | `integer` (1-5) | No | Dificultad percibida |
| `content_quality_rating` | `integer` (1-5) | No | Calidad del contenido |
| `instructor_rating` | `integer` (1-5) | No | Calidad del instructor |
| `title` | `string` (255) | No | Título |
| `body` | `string_long` | No | Texto |
| `progress_at_review` | `integer` (0-100) | No | % completado al momento de la review |
| `verified_enrollment` | `boolean` | No | Matrícula verificada |
| `review_status` | trait | Sí | Moderación |
| `helpful_count` | trait | No | Votos |
| `photos` | trait | No | Fotos |
| `ai_summary` | trait | No | Resumen IA |
| `ai_summary_generated_at` | trait | No | Fecha IA |
| `instructor_response` | `string_long` | No | Respuesta del instructor |
| `instructor_response_date` | `timestamp` | No | Fecha respuesta |
| `created` | `created` | Auto | Timestamp |
| `changed` | `changed` | Auto | Timestamp |

**Handlers**: Access (con tenant), Form (PremiumEntityFormBase), ListBuilder, AdminHtmlRouteProvider, EntityViewsData.
**Links**: canonical, add-form, edit-form, delete-form, collection.
**Admin permission**: `manage lms reviews`.
**Field UI route**: `entity.course_review.settings`.

#### ContentComment

**Archivo**: `jaraba_content_hub/src/Entity/ContentComment.php`

| Campo | Tipo | Required | Descripción |
|-------|------|----------|-------------|
| `id` | `integer` | Auto | Primary key |
| `uuid` | `uuid` | Auto | UUID |
| `uid` | ownerBaseFieldDefinitions | Sí | Autor |
| `tenant_id` | `entity_reference → group` | Sí | Tenant |
| `article_id` | `entity_reference → content_article` | Sí | Artículo comentado |
| `parent_id` | `entity_reference → content_comment` | No | Comentario padre (threading) |
| `body` | `string_long` | Sí | Texto del comentario |
| `author_name` | `string` (100) | No | Nombre mostrado (para anónimos) |
| `author_email` | `email` | No | Email (para anónimos) |
| `review_status` | trait | Sí | Moderación |
| `helpful_count` | trait | No | Votos |
| `ai_summary` | trait | No | Resumen IA |
| `ai_summary_generated_at` | trait | No | Fecha IA |
| `created` | `created` | Auto | Timestamp |
| `changed` | `changed` | Auto | Timestamp |

**Nota**: ContentComment NO tiene `rating` ni `photos` — es un sistema de comentarios, no de reseñas.

### 8.7 Access control handlers con tenant isolation

Todos los access handlers de review siguen el mismo patrón DI con verificación de tenant. Se usa `EntityHandlerInterface` para poder inyectar `TenantContextService`.

**Patrón estándar** (aplicable a los 6 handlers):

```php
<?php

declare(strict_types=1);

namespace Drupal\MODULE\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control para ENTITY con verificación de tenant.
 *
 * Lógica:
 * - view: Reviews aprobadas son públicas. Owner ve las suyas en cualquier estado.
 * - update: Solo el owner o admin, Y solo si pertenece al mismo tenant.
 * - delete: Solo admin del mismo tenant.
 * - create: Requiere permiso específico.
 */
class EntityReviewAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    protected readonly TenantContextService $tenantContext,
  ) {
    parent::__construct($entity_type);
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $admin_permission = $this->entityType->getAdminPermission();

    // Admin bypass.
    if ($admin_permission && $account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Tenant isolation para update/delete (TENANT-ISOLATION-ACCESS-001).
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
        $entity_tenant = (int) $entity->get('tenant_id')->target_id;
        $user_tenant = $this->tenantContext->getCurrentGroupId();
        if ($entity_tenant && $user_tenant && $entity_tenant !== $user_tenant) {
          return AccessResult::forbidden('Tenant mismatch.')
            ->addCacheableDependency($entity)
            ->cachePerUser();
        }
      }
    }

    return match ($operation) {
      'view' => $this->checkViewAccess($entity, $account),
      'update' => $this->checkUpdateAccess($entity, $account),
      'delete' => AccessResult::forbidden()->cachePerPermissions(),
      default => AccessResult::neutral(),
    };
  }

  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    // Reviews aprobadas son públicas.
    $status = $entity->hasField('review_status')
      ? $entity->get('review_status')->value
      : ($entity->hasField('status') ? $entity->get('status')->value : ($entity->hasField('state') ? $entity->get('state')->value : NULL));

    if ($status === 'approved') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }

    // Owner puede ver sus propias reviews en cualquier estado.
    if ($entity->hasField('uid') && (int) $entity->get('uid')->target_id === (int) $account->id()) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
    }

    return AccessResult::neutral()->cachePerUser()->addCacheableDependency($entity);
  }

  protected function checkUpdateAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    // Owner puede editar si tiene permiso.
    if ($entity->hasField('uid') && (int) $entity->get('uid')->target_id === (int) $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'edit own reviews')
        ->addCacheableDependency($entity);
    }
    return AccessResult::neutral()->cachePerPermissions();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission() ?? 'administer site configuration',
      'submit reviews',
    ], 'OR');
  }

}
```

### 8.8 Permisos unificados

Se añaden permisos transversales en `ecosistema_jaraba_core.permissions.yml` además de los existentes en cada módulo vertical:

```yaml
submit reviews:
  title: 'Enviar reseñas'
  description: 'Permite enviar reseñas en cualquier vertical.'

edit own reviews:
  title: 'Editar reseñas propias'
  description: 'Permite editar las reseñas que uno mismo ha enviado.'

moderate reviews:
  title: 'Moderar reseñas'
  description: 'Permite aprobar, rechazar o marcar reseñas de cualquier vertical.'
  restrict access: true

respond reviews:
  title: 'Responder a reseñas'
  description: 'Permite añadir una respuesta oficial a las reseñas.'

view review analytics:
  title: 'Ver analíticas de reseñas'
  description: 'Permite ver estadísticas y resúmenes de reseñas.'
```

### 8.9 Estructura de archivos

```
ecosistema_jaraba_core/
├── src/
│   ├── Entity/
│   │   └── ReviewableEntityTrait.php           ← NUEVO
│   ├── Service/
│   │   ├── ReviewModerationService.php         ← NUEVO
│   │   ├── ReviewAggregationService.php        ← NUEVO
│   │   ├── ReviewSchemaOrgService.php          ← NUEVO
│   │   └── ReviewInvitationService.php         ← NUEVO
│   └── Access/
│       └── DefaultEntityAccessControlHandler.php  (existente)
├── ecosistema_jaraba_core.services.yml         ← MODIFICAR (+4 servicios)
└── ecosistema_jaraba_core.permissions.yml      ← MODIFICAR (+5 permisos)

jaraba_ai_agents/
├── src/
│   └── Service/
│       └── ReviewAiSummaryService.php          ← NUEVO
└── jaraba_ai_agents.services.yml               ← MODIFICAR (+1 servicio)

jaraba_comercio_conecta/
├── src/
│   ├── Entity/
│   │   └── ReviewRetail.php                    ← MODIFICAR (+trait, +2 campos)
│   └── Access/
│       └── ReviewRetailAccessControlHandler.php ← MODIFICAR (tenant isolation)
├── jaraba_comercio_conecta.install             ← MODIFICAR (+2 update hooks)
└── jaraba_comercio_conecta.module              ← MODIFICAR (+preprocess, +presave)

jaraba_agroconecta_core/
├── src/
│   ├── Entity/
│   │   ├── ReviewAgro.php                      ← MODIFICAR (+trait, +2 campos)
│   │   └── ReviewAgroAccessControlHandler.php  → MOVER a Access/
│   └── Access/
│       └── ReviewAgroAccessControlHandler.php  ← MOVER aquí + tenant isolation
├── jaraba_agroconecta_core.install             ← MODIFICAR (+2 update hooks)
└── jaraba_agroconecta_core.module              ← MODIFICAR (+preprocess)

jaraba_servicios_conecta/
├── src/
│   ├── Entity/
│   │   └── ReviewServicios.php                 ← MODIFICAR (+trait, +6 campos)
│   └── Access/
│       └── ReviewServiciosAccessControlHandler.php ← MODIFICAR (tenant isolation)
├── jaraba_servicios_conecta.install            ← MODIFICAR (+1 update hook)
└── jaraba_servicios_conecta.module             ← MODIFICAR (+preprocess)

jaraba_mentoring/
├── src/
│   ├── Entity/
│   │   └── SessionReview.php                   ← MODIFICAR (elevación completa)
│   ├── Access/
│   │   └── SessionReviewAccessControlHandler.php ← NUEVO
│   └── Form/
│       └── SessionReviewForm.php               ← NUEVO (PremiumEntityFormBase)
├── jaraba_mentoring.install                    ← MODIFICAR (+1 update hook)
└── jaraba_mentoring.module                     ← MODIFICAR (+preprocess)

jaraba_lms/
├── src/
│   ├── Entity/
│   │   └── CourseReview.php                    ← NUEVO
│   ├── Access/
│   │   └── CourseReviewAccessControlHandler.php ← NUEVO
│   └── Form/
│       └── CourseReviewForm.php                ← NUEVO
├── jaraba_lms.install                          ← MODIFICAR (+1 update hook)
├── jaraba_lms.routing.yml                      ← MODIFICAR (+routes)
├── jaraba_lms.services.yml                     ← MODIFICAR (+services)
└── jaraba_lms.module                           ← MODIFICAR (+preprocess)

jaraba_content_hub/
├── src/
│   ├── Entity/
│   │   ├── ContentComment.php                  ← NUEVO
│   │   └── ContentCommentInterface.php         ← NUEVO
│   ├── Access/
│   │   └── ContentCommentAccessControlHandler.php ← NUEVO
│   ├── Form/
│   │   └── ContentCommentForm.php              ← NUEVO
│   └── Controller/
│       └── CommentApiController.php            ← NUEVO
├── jaraba_content_hub.install                  ← MODIFICAR (+1 update hook)
├── jaraba_content_hub.routing.yml              ← MODIFICAR (+routes)
├── jaraba_content_hub.services.yml             ← MODIFICAR (+services)
└── jaraba_content_hub.module                   ← MODIFICAR (+preprocess)

ecosistema_jaraba_theme/
├── scss/
│   └── components/
│       ├── _reviews.scss                       ← NUEVO
│       └── _star-rating.scss                   ← NUEVO
├── js/
│   └── star-rating.js                          ← NUEVO
├── templates/
│   ├── page--reviews.html.twig                 ← NUEVO
│   └── partials/
│       ├── _review-widget.html.twig            ← NUEVO
│       ├── _review-card.html.twig              ← NUEVO
│       ├── _star-rating-display.html.twig      ← NUEVO
│       ├── _review-summary.html.twig           ← NUEVO
│       └── _comment-thread.html.twig           ← NUEVO
├── ecosistema_jaraba_theme.theme               ← MODIFICAR (+body classes, +preprocess)
├── ecosistema_jaraba_theme.libraries.yml       ← MODIFICAR (+library)
└── scss/main.scss                              ← MODIFICAR (+import)

jaraba_page_builder/
└── js/
    └── plugins/
        └── reviews-block.js                    ← NUEVO (GrapesJS plugin)
```

---

## 9. Servicios — Consolidación y Elevación

### 9.1 ReviewModerationService (nuevo, ecosistema_jaraba_core)

**Archivo**: `ecosistema_jaraba_core/src/Service/ReviewModerationService.php`

**Responsabilidad**: Operaciones de moderación transversales a todos los entity types de review.

**Dependencias DI**:
- `EntityTypeManagerInterface`
- `AccountProxyInterface`
- `LoggerInterface`
- `ReviewAggregationService` (para recalcular ratings tras aprobación)
- `EventDispatcherInterface` (para emitir eventos de moderación)

**Métodos públicos**:

| Método | Firma | Descripción |
|--------|-------|-------------|
| `moderate` | `(string $entityTypeId, int $entityId, string $newStatus): bool` | Cambia el estado de moderación. Valida que `$newStatus` sea uno de los 4 permitidos. Si el nuevo estado es `approved`, llama a `ReviewAggregationService::recalculateForTarget()`. Registra la acción en el log. |
| `getPendingReviews` | `(string $entityTypeId, ?int $tenantGroupId = NULL, int $limit = 50): array` | Devuelve reviews pendientes de un entity type, filtradas opcionalmente por tenant. Ordena ASC por `created` (FIFO). |
| `getPendingCounts` | `(?int $tenantGroupId = NULL): array` | Devuelve un array asociativo `['entity_type_id' => count]` con el número de reviews pendientes por cada tipo de entidad. Usado para badges en el dashboard admin. |
| `flagReview` | `(string $entityTypeId, int $entityId, string $reason): bool` | Marca una review como `flagged` y almacena la razón. |
| `getStatusFieldName` | `(string $entityTypeId): string` | Devuelve el nombre del campo de status para un entity type dado, usando `STATUS_FIELD_MAP`. |

**Lógica de `moderate()`**:

```php
public function moderate(string $entityTypeId, int $entityId, string $newStatus): bool {
  $allowed = [
    ReviewableEntityTrait::STATUS_PENDING,
    ReviewableEntityTrait::STATUS_APPROVED,
    ReviewableEntityTrait::STATUS_REJECTED,
    ReviewableEntityTrait::STATUS_FLAGGED,
  ];
  if (!in_array($newStatus, $allowed, TRUE)) {
    $this->logger->error('Invalid status @status for @type @id.', [
      '@status' => $newStatus,
      '@type' => $entityTypeId,
      '@id' => $entityId,
    ]);
    return FALSE;
  }

  $storage = $this->entityTypeManager->getStorage($entityTypeId);
  $entity = $storage->load($entityId);
  if (!$entity) {
    return FALSE;
  }

  $statusField = $this->getStatusFieldName($entityTypeId);
  $oldStatus = $entity->get($statusField)->value;
  $entity->set($statusField, $newStatus);
  $entity->save();

  // Recalcular ratings si se aprueba o rechaza.
  if (in_array($newStatus, ['approved', 'rejected'], TRUE) && $oldStatus !== $newStatus) {
    try {
      $this->aggregationService->recalculateForReviewEntity($entity);
    }
    catch (\Exception $e) {
      $this->logger->error('Aggregation failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  $this->logger->info('Review @type @id moderated: @old → @new.', [
    '@type' => $entityTypeId,
    '@id' => $entityId,
    '@old' => $oldStatus,
    '@new' => $newStatus,
  ]);

  return TRUE;
}
```

### 9.2 ReviewAggregationService (nuevo, ecosistema_jaraba_core)

**Archivo**: `ecosistema_jaraba_core/src/Service/ReviewAggregationService.php`

**Responsabilidad**: Calcular y denormalizar métricas de rating (average, count, distribution) en la entidad target.

**Dependencias DI**:
- `EntityTypeManagerInterface`
- `Connection` (database)
- `LoggerInterface`
- `CacheBackendInterface` (tag-based invalidation)

**Métodos públicos**:

| Método | Firma | Descripción |
|--------|-------|-------------|
| `getRatingStats` | `(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId): array` | Retorna `['average' => float, 'count' => int, 'distribution' => [1 => N, ..., 5 => N]]`. Calcula on-demand con query SQL directo para performance. Cachea resultado con tag `review_stats:{target_type}:{target_id}`. |
| `recalculateForReviewEntity` | `(EntityInterface $reviewEntity): void` | Dado un review entity, determina su target (via field mapping) y recalcula stats. Si la entidad target tiene campos `average_rating` / `total_reviews`, los actualiza (denormalization). |
| `invalidateStatsCache` | `(string $targetEntityType, int $targetEntityId): void` | Invalida el cache de stats para un target específico. |

**Mapping de review → target**:

```php
private const TARGET_FIELD_MAP = [
  'comercio_review' => ['type_field' => 'entity_type_ref', 'id_field' => 'entity_id_ref'],
  'review_agro' => ['type_field' => 'target_entity_type', 'id_field' => 'target_entity_id'],
  'review_servicios' => ['type_field' => NULL, 'id_field' => 'provider_id', 'fixed_type' => 'provider_profile'],
  'session_review' => ['type_field' => NULL, 'id_field' => 'session_id', 'fixed_type' => 'mentoring_session'],
  'course_review' => ['type_field' => NULL, 'id_field' => 'course_id', 'fixed_type' => 'course'],
];
```

### 9.3 ReviewSchemaOrgService (nuevo, ecosistema_jaraba_core)

**Archivo**: `ecosistema_jaraba_core/src/Service/ReviewSchemaOrgService.php`

**Responsabilidad**: Generar JSON-LD Schema.org para `AggregateRating` y `Review[]`.

**Dependencias DI**:
- `ReviewAggregationService`
- `EntityTypeManagerInterface`
- `RequestStack` (para canonical URL)

**Métodos públicos**:

| Método | Firma | Descripción |
|--------|-------|-------------|
| `generateAggregateRating` | `(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId): ?array` | Genera el JSON-LD `AggregateRating` fragment. Retorna `NULL` si no hay reviews aprobadas. |
| `generateReviewList` | `(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, int $limit = 5): array` | Genera array de `Review` Schema.org objects (últimas N aprobadas). |
| `buildProductJsonLd` | `(EntityInterface $product, array $aggregateRating, array $reviews): array` | Ensambla el JSON-LD completo para un producto con su `AggregateRating` y `Review[]`. |

**Ejemplo de output JSON-LD**:

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Aceite de Oliva Virgen Extra",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.7",
    "bestRating": "5",
    "worstRating": "1",
    "ratingCount": "142",
    "reviewCount": "89"
  },
  "review": [
    {
      "@type": "Review",
      "author": { "@type": "Person", "name": "María García" },
      "datePublished": "2026-02-20",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5"
      },
      "reviewBody": "Excelente aceite, sabor intenso y afrutado."
    }
  ]
}
```

**Integración**: Se inyecta en páginas de detalle via `hook_preprocess_html()` de cada módulo vertical. Se añade como `<script type="application/ld+json">` en el `<head>`.

### 9.4 ReviewInvitationService (nuevo, ecosistema_jaraba_core)

**Archivo**: `ecosistema_jaraba_core/src/Service/ReviewInvitationService.php`

**Responsabilidad**: Enviar invitaciones a dejar reviews tras transacciones completadas.

**Dependencias DI**:
- `EntityTypeManagerInterface`
- `QueueFactory` (para procesamiento asíncrono)
- `LoggerInterface`
- `MailManagerInterface` (para emails)

**Métodos públicos**:

| Método | Firma | Descripción |
|--------|-------|-------------|
| `scheduleInvitation` | `(string $vertical, int $transactionEntityId, int $userId, int $delayHours = 48): void` | Encola una invitación para enviar `$delayHours` horas después de la transacción. |
| `processInvitation` | `(array $data): void` | Procesa una invitación encolada: verifica que la transacción sigue completada, que el usuario no ha dejado review ya, y envía email + notificación push. |
| `hasUserReviewed` | `(string $reviewEntityTypeId, int $userId, string $targetEntityType, int $targetEntityId): bool` | Verifica si el usuario ya dejó una review para el target. |

### 9.5 ReviewAiSummaryService (nuevo, jaraba_ai_agents)

**Archivo**: `jaraba_ai_agents/src/Service/ReviewAiSummaryService.php`

**Responsabilidad**: Generar resúmenes textuales de conjuntos de reviews usando IA.

**Dependencias DI**:
- `EntityTypeManagerInterface`
- `ModelRouterService` (para selección de tier)
- `LoggerInterface`
- `@?ai.provider` (Drupal AI module, opcional)

**Métodos públicos**:

| Método | Firma | Descripción |
|--------|-------|-------------|
| `generateSummary` | `(string $reviewEntityTypeId, string $targetEntityType, int $targetEntityId, string $locale = 'es'): ?string` | Recopila las últimas 20 reviews aprobadas, construye un prompt y llama al modelo fast (Haiku 4.5) para generar un resumen de 2-3 frases. |
| `regenerateStale` | `(int $maxAge = 604800): int` | Regenera resúmenes más antiguos de `$maxAge` segundos. Retorna número de resúmenes regenerados. Para uso en cron. |

**Prompt template**:

```
Eres un asistente que resume reseñas de clientes. Genera un resumen conciso (2-3 frases)
en español de las siguientes reseñas. Destaca los aspectos más mencionados (positivos y negativos).
No inventes información — solo resume lo que dicen los usuarios.

Reseñas:
{reviews_text}

Resumen:
```

**Lógica**: Usa `ModelRouterService::route('fast')` para obtener Haiku 4.5. El servicio es opcional — si `ai.provider` no está disponible, retorna `NULL` sin error (PRESAVE-RESILIENCE-001).

### 9.6 Servicios verticales: qué se mantiene, qué se delega

| Servicio Vertical | Se Mantiene | Métodos que Delega | Métodos que Conserva |
|-------------------|-------------|-------------------|---------------------|
| `ReviewRetailService` | Sí | `moderateReview()` → `ReviewModerationService::moderate()`, `getReviewSummary()` → `ReviewAggregationService::getRatingStats()` | `createReview()`, `addMerchantResponse()`, `markHelpful()`, `getUserReviews()`, `detectVerifiedPurchase()` |
| `ReviewAgroService` | Sí | `moderate()` → `ReviewModerationService::moderate()`, `getRatingStats()` → `ReviewAggregationService::getRatingStats()` | `createReview()`, `addProducerResponse()`, `getReviewsForTarget()`, `getPendingReviews()`, `serializeReview()`, `verifyPurchase()` |
| `ReviewService` (ServiciosConecta) | Sí | `approveReview()` → `ReviewModerationService::moderate()`, `recalculateAverageRating()` → `ReviewAggregationService::recalculateForReviewEntity()` | `submitReview()`, `getProviderReviews()`, `canUserReview()` |

---

## 10. Controladores y Rutas

### 10.1 Rutas API unificadas vs verticales

**Decisión**: Las rutas API se mantienen en cada módulo vertical (no se unifican en un endpoint `/api/v1/reviews` global). Razón: cada vertical tiene lógica de negocio específica (verified purchase en comercio, booking validation en servicios). Los servicios transversales se consumen internamente por los controladores verticales.

Sin embargo, se añaden las rutas faltantes en ServiciosConecta y Mentoring para completar el API set.

### 10.2 ReviewDisplayController (nuevo, ecosistema_jaraba_core)

**Archivo**: `ecosistema_jaraba_core/src/Controller/ReviewDisplayController.php`

**Responsabilidad**: Render frontend de reviews para cualquier entidad que tenga reviews.

**Métodos**:

| Método | Firma | Ruta | Descripción |
|--------|-------|------|-------------|
| `reviewsPage` | `(string $vertical, string $entity_type, int $entity_id): array` | `/{vertical}/{slug}/reviews` | Página de listado de reviews para una entidad target. Usa template `page--reviews.html.twig`. |

**Lógica de `reviewsPage()`**:

1. Resuelve el vertical canónico (VERTICAL-CANONICAL-001).
2. Carga la entidad target.
3. Llama a `ReviewAggregationService::getRatingStats()` para obtener métricas.
4. Carga reviews aprobadas con paginación (`?page=N`).
5. Llama a `ReviewAiSummaryService::generateSummary()` (si disponible) para el resumen IA.
6. Retorna render array con `#theme` → template que incluye los partials.

### 10.3 Rutas frontend

Cada módulo vertical registra una ruta frontend en su `.routing.yml`:

```yaml
# jaraba_comercio_conecta.routing.yml (ejemplo)
jaraba_comercio_conecta.reviews_page:
  path: '/comercio/{merchant_slug}/reviews'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\ReviewDisplayController::reviewsPage'
    _title_callback: '\Drupal\ecosistema_jaraba_core\Controller\ReviewDisplayController::reviewsPageTitle'
    vertical: 'comercioconecta'
    entity_type: 'merchant_profile'
  requirements:
    _permission: 'access content'
```

### 10.4 Integración con controladores existentes

Los controladores existentes de detalle de producto/proveedor/productor deben incluir un bloque de reviews inline. Esto se logra via el partial `_review-widget.html.twig` incluido en las templates de detalle existentes.

---

## 11. Arquitectura Frontend

### 11.1 Zero Region Policy

Las páginas de reviews frontend usan una template de página limpia, sin `{{ page.content }}` ni bloques heredados de Drupal.

**Template**: `page--reviews.html.twig`

```twig
{#
 # @file
 # Zero Region page template for review listing pages.
 #
 # Variables:
 #   - clean_content: The render array from the controller (no regions).
 #   - page_title: The translated page title.
 #   - entity_name: The name of the reviewed entity.
 #   - vertical: The canonical vertical name.
 #}
<!DOCTYPE html>
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main class="reviews-page" role="main">
  <div class="reviews-page__container">
    {{ clean_content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_trust-bar.html.twig' %}

{% include '@ecosistema_jaraba_theme/templates/partials/_footer.html.twig' with {
  footer_columns: footer_columns,
  footer_social: footer_social,
  footer_copyright: footer_copyright,
} %}
```

Esta template se activa mediante `hook_theme_suggestions_page_alter()` cuando la ruta empieza con `jaraba_comercio_conecta.reviews_page`, `jaraba_agroconecta_core.reviews_page`, etc.

### 11.2 Templates Twig

Las templates de reviews se organizan en:
1. **Página completa** (`page--reviews.html.twig`): Layout con header/footer del tema.
2. **Contenido principal** (`reviews-listing.html.twig`): Resumen + listado + paginación.
3. **Parciales reutilizables**: Componentes atómicos incluidos con `{% include %}`.

### 11.3 Templates parciales

Todos los parciales viven en `ecosistema_jaraba_theme/templates/partials/` y se incluyen con `{% include %}`. Antes de crear un parcial nuevo, se verifica si existe uno reutilizable.

**Parciales existentes reutilizados**:
- `_header.html.twig`: Header del tema (ya existe).
- `_trust-bar.html.twig`: Barra de confianza (ya existe).

**Parciales nuevos**:

#### `_review-widget.html.twig`

Widget embebible en cualquier página de detalle. Muestra resumen de ratings + últimas 3 reviews + enlace "Ver todas".

```twig
{#
 # @file
 # Embeddable review summary widget for entity detail pages.
 #
 # Variables:
 #   - rating_stats: { average: float, count: int, distribution: {1: N, ..., 5: N} }
 #   - recent_reviews: array of review render data
 #   - ai_summary: string|null
 #   - reviews_url: string (URL to full reviews page)
 #   - can_submit: boolean
 #   - submit_url: string
 #}
<section class="review-widget" aria-label="{% trans %}Reseñas{% endtrans %}">
  {% if rating_stats.count > 0 %}
    <div class="review-widget__summary">
      {% include '@ecosistema_jaraba_theme/partials/_star-rating-display.html.twig' with {
        rating: rating_stats.average,
        count: rating_stats.count,
        size: 'lg',
      } %}

      {% if ai_summary %}
        {% include '@ecosistema_jaraba_theme/partials/_review-summary.html.twig' with {
          summary: ai_summary,
        } %}
      {% endif %}

      <div class="review-widget__distribution">
        {% for star in 5..1 %}
          <div class="review-widget__bar">
            <span class="review-widget__bar-label">{{ star }} {{ jaraba_icon('ui', 'star', { size: '14px', color: 'warning' }) }}</span>
            <div class="review-widget__bar-track">
              {% set pct = rating_stats.count > 0 ? (rating_stats.distribution[star] / rating_stats.count * 100) : 0 %}
              <div class="review-widget__bar-fill" style="width: {{ pct|round }}%"></div>
            </div>
            <span class="review-widget__bar-count">{{ rating_stats.distribution[star] }}</span>
          </div>
        {% endfor %}
      </div>
    </div>

    <div class="review-widget__recent">
      {% for review in recent_reviews %}
        {% include '@ecosistema_jaraba_theme/partials/_review-card.html.twig' with {
          review: review,
        } %}
      {% endfor %}
    </div>

    <a href="{{ reviews_url }}" class="review-widget__see-all">
      {% trans %}Ver todas las reseñas{% endtrans %}
      {{ jaraba_icon('ui', 'arrow-right', { size: '16px', color: 'impulse' }) }}
    </a>
  {% else %}
    <div class="review-widget__empty">
      <p>{% trans %}Aún no hay reseñas. ¡Sé el primero en dejar la tuya!{% endtrans %}</p>
    </div>
  {% endif %}

  {% if can_submit %}
    <a href="{{ submit_url }}" class="btn btn--primary review-widget__submit js-slide-panel-trigger">
      {{ jaraba_icon('ui', 'edit', { size: '16px', color: 'white' }) }}
      {% trans %}Escribir reseña{% endtrans %}
    </a>
  {% endif %}
</section>
```

#### `_star-rating-display.html.twig`

```twig
{#
 # @file
 # Star rating display component (read-only).
 #
 # Variables:
 #   - rating: float (0-5)
 #   - count: int (number of reviews)
 #   - size: 'sm'|'md'|'lg' (default 'md')
 #   - show_count: boolean (default TRUE)
 #}
{% set size_class = size|default('md') %}
{% set filled = rating|round(0, 'floor') %}
{% set has_half = (rating - filled) >= 0.25 and (rating - filled) < 0.75 %}
{% set empty_stars = 5 - filled - (has_half ? 1 : 0) %}

<div class="star-rating star-rating--{{ size_class }}" aria-label="{% trans %}Valoración: {{ rating|number_format(1) }} de 5{% endtrans %}">
  <div class="star-rating__stars" role="img">
    {% for i in 1..filled %}
      {{ jaraba_icon('ui', 'star', { variant: 'filled', size: size_class == 'lg' ? '24px' : (size_class == 'sm' ? '14px' : '18px'), color: 'warning' }) }}
    {% endfor %}
    {% if has_half %}
      {{ jaraba_icon('ui', 'star-half', { variant: 'filled', size: size_class == 'lg' ? '24px' : (size_class == 'sm' ? '14px' : '18px'), color: 'warning' }) }}
    {% endif %}
    {% for i in 1..empty_stars %}
      {{ jaraba_icon('ui', 'star', { variant: 'outline', size: size_class == 'lg' ? '24px' : (size_class == 'sm' ? '14px' : '18px'), color: 'neutral' }) }}
    {% endfor %}
  </div>
  <span class="star-rating__value">{{ rating|number_format(1) }}</span>
  {% if show_count|default(true) %}
    <span class="star-rating__count">({{ count }} {% trans %}reseñas{% endtrans %})</span>
  {% endif %}
</div>
```

#### `_review-card.html.twig`

```twig
{#
 # @file
 # Individual review card component.
 #
 # Variables:
 #   - review: {
 #       id: int, author_name: string, author_avatar_url: string|null,
 #       rating: int, title: string, body: string, created: string,
 #       verified_purchase: boolean, helpful_count: int,
 #       photos: string[], response: { body: string, by: string, date: string }|null
 #     }
 #}
<article class="review-card" aria-label="{% trans %}Reseña de {{ review.author_name }}{% endtrans %}">
  <header class="review-card__header">
    <div class="review-card__author">
      {% if review.author_avatar_url %}
        <img src="{{ review.author_avatar_url }}" alt="{{ review.author_name }}" class="review-card__avatar" loading="lazy" width="40" height="40">
      {% else %}
        <div class="review-card__avatar review-card__avatar--placeholder">
          {{ review.author_name|first|upper }}
        </div>
      {% endif %}
      <div class="review-card__author-info">
        <span class="review-card__author-name">{{ review.author_name }}</span>
        <time class="review-card__date" datetime="{{ review.created }}">{{ review.created|date('d M Y') }}</time>
      </div>
    </div>

    {% include '@ecosistema_jaraba_theme/partials/_star-rating-display.html.twig' with {
      rating: review.rating,
      count: 0,
      size: 'sm',
      show_count: false,
    } %}
  </header>

  {% if review.verified_purchase %}
    <span class="review-card__badge review-card__badge--verified">
      {{ jaraba_icon('ui', 'shield-check', { size: '14px', color: 'success' }) }}
      {% trans %}Compra verificada{% endtrans %}
    </span>
  {% endif %}

  {% if review.title %}
    <h4 class="review-card__title">{{ review.title }}</h4>
  {% endif %}

  <div class="review-card__body">
    {{ review.body }}
  </div>

  {% if review.photos|length > 0 %}
    <div class="review-card__photos">
      {% for photo_url in review.photos %}
        <img src="{{ photo_url }}" alt="{% trans %}Foto de reseña{% endtrans %}" class="review-card__photo" loading="lazy" width="120" height="120">
      {% endfor %}
    </div>
  {% endif %}

  <footer class="review-card__footer">
    <button class="review-card__helpful js-review-helpful" data-review-id="{{ review.id }}" aria-label="{% trans %}Marcar como útil{% endtrans %}">
      {{ jaraba_icon('ui', 'thumbs-up', { size: '14px', color: 'neutral' }) }}
      <span class="review-card__helpful-count">{{ review.helpful_count }}</span>
      {% trans %}Útil{% endtrans %}
    </button>
  </footer>

  {% if review.response %}
    <div class="review-card__response">
      <div class="review-card__response-header">
        {{ jaraba_icon('ui', 'reply', { size: '14px', color: 'corporate' }) }}
        <strong>{% trans %}Respuesta de {{ review.response.by }}{% endtrans %}</strong>
        <time datetime="{{ review.response.date }}">{{ review.response.date|date('d M Y') }}</time>
      </div>
      <p class="review-card__response-body">{{ review.response.body }}</p>
    </div>
  {% endif %}
</article>
```

#### `_review-summary.html.twig`

```twig
{#
 # @file
 # AI-generated review summary component.
 #
 # Variables:
 #   - summary: string (AI-generated text)
 #}
<div class="review-summary">
  <div class="review-summary__header">
    {{ jaraba_icon('ai', 'sparkles', { size: '18px', color: 'impulse', variant: 'duotone' }) }}
    <span class="review-summary__label">{% trans %}Resumen de reseñas{% endtrans %}</span>
  </div>
  <p class="review-summary__text">{{ summary }}</p>
</div>
```

#### `_comment-thread.html.twig`

```twig
{#
 # @file
 # Threaded comment display for Content Hub articles.
 #
 # Variables:
 #   - comments: array of comment objects (nested with children)
 #   - article_id: int
 #   - can_comment: boolean
 #   - comment_form_url: string
 #}
<section class="comment-thread" id="comments" aria-label="{% trans %}Comentarios{% endtrans %}">
  <h3 class="comment-thread__title">
    {{ jaraba_icon('ui', 'message-circle', { size: '22px', color: 'corporate' }) }}
    {% trans %}Comentarios ({{ comments|length }}){% endtrans %}
  </h3>

  {% if can_comment %}
    <a href="{{ comment_form_url }}" class="btn btn--outline comment-thread__add js-slide-panel-trigger">
      {{ jaraba_icon('ui', 'plus', { size: '16px', color: 'impulse' }) }}
      {% trans %}Añadir comentario{% endtrans %}
    </a>
  {% endif %}

  {% if comments|length > 0 %}
    <div class="comment-thread__list">
      {% for comment in comments %}
        <div class="comment-thread__item {{ comment.children|length > 0 ? 'comment-thread__item--has-replies' : '' }}" id="comment-{{ comment.id }}">
          <div class="comment-thread__meta">
            <strong class="comment-thread__author">{{ comment.author_name }}</strong>
            <time class="comment-thread__date" datetime="{{ comment.created }}">{{ comment.created|date('d M Y, H:i') }}</time>
          </div>
          <div class="comment-thread__body">{{ comment.body }}</div>

          {% if comment.children|length > 0 %}
            <div class="comment-thread__replies">
              {% for reply in comment.children %}
                <div class="comment-thread__item comment-thread__item--reply" id="comment-{{ reply.id }}">
                  <div class="comment-thread__meta">
                    <strong class="comment-thread__author">{{ reply.author_name }}</strong>
                    <time class="comment-thread__date" datetime="{{ reply.created }}">{{ reply.created|date('d M Y, H:i') }}</time>
                  </div>
                  <div class="comment-thread__body">{{ reply.body }}</div>
                </div>
              {% endfor %}
            </div>
          {% endif %}
        </div>
      {% endfor %}
    </div>
  {% else %}
    <p class="comment-thread__empty">{% trans %}No hay comentarios todavía. ¡Inicia la conversación!{% endtrans %}</p>
  {% endif %}
</section>
```

### 11.4 Body classes via hook_preprocess_html()

Se añaden body classes en el `hook_preprocess_html()` del tema (NO en templates):

```php
// En ecosistema_jaraba_theme_preprocess_html():
// Reviews page body classes.
$route_name = \Drupal::routeMatch()->getRouteName() ?? '';
if (str_contains($route_name, '.reviews_page')) {
  $variables['attributes']['class'][] = 'page-reviews';
  $variables['attributes']['class'][] = 'page--clean-layout';
  $variables['attributes']['class'][] = 'full-width-layout';
}
```

Y en la sección del catch-all del `hook_theme_suggestions_page_alter()`:

```php
// Reviews pages → page--reviews suggestion.
if (str_contains($route_name, '.reviews_page')) {
  $suggestions[] = 'page__reviews';
}
```

### 11.5 SCSS: Federated Design Tokens

**Archivo**: `ecosistema_jaraba_theme/scss/components/_reviews.scss`

Todos los estilos consumen CSS custom properties con fallbacks SCSS como Single Source of Truth:

```scss
/**
 * @file
 * Styles for the review system (widgets, cards, summary, comments).
 *
 * Consumes Federated Design Tokens: var(--ej-*, $fallback).
 * Mobile-first: base styles for mobile, @include respond-to(md) for desktop.
 * BEM methodology with .review-* namespace.
 */

@use 'sass:color';
@use '../variables' as *;

// ─── Review Widget ──────────────────────────────────────────────

.review-widget {
  padding: $ej-spacing-lg;
  background: var(--ej-bg-surface, $ej-bg-surface);
  border-radius: var(--ej-card-radius, #{$ej-border-radius});
  border: 1px solid var(--ej-card-border, #{$ej-border-color});

  &__summary {
    display: flex;
    flex-direction: column;
    gap: $ej-spacing-md;
    margin-bottom: $ej-spacing-lg;

    @include respond-to(md) {
      flex-direction: row;
      align-items: flex-start;
    }
  }

  &__distribution {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: $ej-spacing-xs;
  }

  &__bar {
    display: flex;
    align-items: center;
    gap: $ej-spacing-sm;
  }

  &__bar-label {
    flex-shrink: 0;
    width: 40px;
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 0.8rem;
    color: var(--ej-color-muted, $ej-color-muted);
  }

  &__bar-track {
    flex: 1;
    height: 8px;
    background: color.adjust($ej-border-color, $lightness: 4%);
    border-radius: 4px;
    overflow: hidden;
  }

  &__bar-fill {
    height: 100%;
    background: var(--ej-color-warning, $ej-color-warning);
    border-radius: 4px;
    transition: width $ej-transition-normal;
  }

  &__bar-count {
    flex-shrink: 0;
    width: 30px;
    font-size: 0.8rem;
    color: var(--ej-color-muted, $ej-color-muted);
    text-align: right;
  }

  &__empty {
    text-align: center;
    padding: $ej-spacing-xl;
    color: var(--ej-color-muted, $ej-color-muted);
  }

  &__submit {
    display: inline-flex;
    align-items: center;
    gap: $ej-spacing-xs;
    margin-top: $ej-spacing-md;
  }

  &__see-all {
    display: inline-flex;
    align-items: center;
    gap: $ej-spacing-xs;
    color: var(--ej-color-primary, $ej-color-impulse);
    font-weight: 600;
    text-decoration: none;
    margin-top: $ej-spacing-md;

    &:hover {
      text-decoration: underline;
    }

    &:focus-visible {
      outline: $ej-focus-ring;
      outline-offset: $ej-focus-ring-offset;
      border-radius: 4px;
    }
  }
}

// ─── Star Rating ────────────────────────────────────────────────

.star-rating {
  display: inline-flex;
  align-items: center;
  gap: $ej-spacing-xs;

  &--sm { font-size: 0.8rem; }
  &--md { font-size: 1rem; }
  &--lg { font-size: 1.25rem; }

  &__stars {
    display: inline-flex;
    gap: 1px;
  }

  &__value {
    font-weight: 700;
    color: var(--ej-color-headings, $ej-color-headings);
  }

  &__count {
    color: var(--ej-color-muted, $ej-color-muted);
  }
}

// ─── Review Card ────────────────────────────────────────────────

.review-card {
  padding: $ej-spacing-md;
  border-bottom: 1px solid var(--ej-card-border, #{$ej-border-color});

  &:last-child {
    border-bottom: none;
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: $ej-spacing-sm;
  }

  &__author {
    display: flex;
    align-items: center;
    gap: $ej-spacing-sm;
  }

  &__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;

    &--placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--ej-color-primary, $ej-color-impulse);
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
    }
  }

  &__author-name {
    font-weight: 600;
    color: var(--ej-color-headings, $ej-color-headings);
    font-size: 0.9rem;
  }

  &__date {
    font-size: 0.8rem;
    color: var(--ej-color-muted, $ej-color-muted);
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    gap: $ej-spacing-xs;
    padding: 2px $ej-spacing-sm;
    border-radius: $ej-btn-radius;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: $ej-spacing-sm;

    &--verified {
      background: color.adjust($ej-color-success, $lightness: 40%);
      color: color.adjust($ej-color-success, $lightness: -20%);
    }
  }

  &__title {
    font-weight: 600;
    font-size: 1rem;
    color: var(--ej-color-headings, $ej-color-headings);
    margin: 0 0 $ej-spacing-xs;
  }

  &__body {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--ej-color-body, $ej-color-body);
    margin-bottom: $ej-spacing-sm;
  }

  &__photos {
    display: flex;
    gap: $ej-spacing-sm;
    overflow-x: auto;
    padding-bottom: $ej-spacing-sm;
    -webkit-overflow-scrolling: touch;
  }

  &__photo {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: $ej-btn-radius;
    flex-shrink: 0;
    cursor: pointer;

    &:focus-visible {
      outline: $ej-focus-ring;
      outline-offset: $ej-focus-ring-offset;
    }
  }

  &__footer {
    display: flex;
    align-items: center;
    gap: $ej-spacing-md;
  }

  &__helpful {
    display: inline-flex;
    align-items: center;
    gap: $ej-spacing-xs;
    padding: $ej-spacing-xs $ej-spacing-sm;
    border: 1px solid var(--ej-card-border, #{$ej-border-color});
    border-radius: $ej-btn-radius;
    background: transparent;
    cursor: pointer;
    font-size: 0.8rem;
    color: var(--ej-color-muted, $ej-color-muted);
    transition: all $ej-transition-fast;

    &:hover {
      border-color: var(--ej-color-primary, $ej-color-impulse);
      color: var(--ej-color-primary, $ej-color-impulse);
    }

    &:focus-visible {
      outline: $ej-focus-ring;
      outline-offset: $ej-focus-ring-offset;
    }
  }

  &__response {
    margin-top: $ej-spacing-sm;
    padding: $ej-spacing-sm $ej-spacing-md;
    background: color.adjust($ej-bg-body, $lightness: -2%);
    border-radius: $ej-btn-radius;
    border-left: 3px solid var(--ej-color-corporate, $ej-color-corporate);
  }

  &__response-header {
    display: flex;
    align-items: center;
    gap: $ej-spacing-xs;
    margin-bottom: $ej-spacing-xs;
    font-size: 0.85rem;

    strong {
      color: var(--ej-color-corporate, $ej-color-corporate);
    }

    time {
      color: var(--ej-color-muted, $ej-color-muted);
      font-size: 0.8rem;
    }
  }

  &__response-body {
    font-size: 0.85rem;
    line-height: 1.5;
    color: var(--ej-color-body, $ej-color-body);
    margin: 0;
  }
}

// ─── Review Summary (AI) ────────────────────────────────────────

.review-summary {
  padding: $ej-spacing-md;
  background: color.adjust($ej-bg-body, $lightness: -1%);
  border-radius: $ej-btn-radius;
  border: 1px solid var(--ej-card-border, #{$ej-border-color});
  margin-bottom: $ej-spacing-md;

  &__header {
    display: flex;
    align-items: center;
    gap: $ej-spacing-xs;
    margin-bottom: $ej-spacing-xs;
  }

  &__label {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--ej-color-headings, $ej-color-headings);
  }

  &__text {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--ej-color-body, $ej-color-body);
    margin: 0;
  }
}

// ─── Comment Thread ─────────────────────────────────────────────

.comment-thread {
  padding: $ej-spacing-lg 0;

  &__title {
    display: flex;
    align-items: center;
    gap: $ej-spacing-sm;
    font-family: var(--ej-font-headings, #{$ej-font-headings});
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--ej-color-headings, $ej-color-headings);
    margin: 0 0 $ej-spacing-md;
  }

  &__add {
    display: inline-flex;
    align-items: center;
    gap: $ej-spacing-xs;
    margin-bottom: $ej-spacing-lg;
  }

  &__item {
    padding: $ej-spacing-md 0;
    border-bottom: 1px solid var(--ej-card-border, #{$ej-border-color});
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: $ej-spacing-sm;
    margin-bottom: $ej-spacing-xs;
  }

  &__author {
    font-size: 0.9rem;
    color: var(--ej-color-headings, $ej-color-headings);
  }

  &__date {
    font-size: 0.8rem;
    color: var(--ej-color-muted, $ej-color-muted);
  }

  &__body {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--ej-color-body, $ej-color-body);
  }

  &__replies {
    margin-left: $ej-spacing-lg;
    padding-left: $ej-spacing-md;
    border-left: 2px solid var(--ej-card-border, #{$ej-border-color});
  }

  &__empty {
    text-align: center;
    padding: $ej-spacing-xl;
    color: var(--ej-color-muted, $ej-color-muted);
    font-style: italic;
  }
}

// ─── Reviews Page ───────────────────────────────────────────────

.reviews-page {
  &__container {
    max-width: 900px;
    margin: 0 auto;
    padding: $ej-spacing-lg $ej-spacing-md;
  }
}

// ─── Dark Mode ──────────────────────────────────────────────────

.dark-mode {
  .review-card__badge--verified {
    background: rgba($ej-color-success, 0.15);
    color: $ej-color-success;
  }

  .review-card__response {
    background: rgba(255, 255, 255, 0.04);
  }

  .review-summary {
    background: rgba(255, 255, 255, 0.03);
  }

  .review-card__avatar--placeholder {
    background: var(--ej-color-primary, $ej-color-impulse);
  }
}

// ─── Reduced Motion ─────────────────────────────────────────────

@media (prefers-reduced-motion: reduce) {
  .review-widget__bar-fill {
    transition: none;
  }

  .review-card__helpful {
    transition: none;
  }
}

// ─── Print ──────────────────────────────────────────────────────

@media print {
  .review-card__helpful,
  .review-widget__submit,
  .comment-thread__add {
    display: none;
  }
}
```

### 11.6 JavaScript: Star Rating Widget

**Archivo**: `ecosistema_jaraba_theme/js/star-rating.js`

Widget interactivo para input de rating (formulario de envío de review). Solo para la versión interactiva (el display read-only es puro CSS/Twig).

```javascript
/**
 * @file
 * Interactive star rating input widget.
 *
 * Attaches to elements with [data-star-rating-input].
 * Uses Drupal.behaviors for lifecycle management.
 * All user-visible text uses Drupal.t() for i18n.
 * XSS-safe: uses Drupal.checkPlain() for any dynamic text insertion.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.jarabaStarRating = {
    attach: function (context) {
      const containers = context.querySelectorAll('[data-star-rating-input]:not(.star-rating-processed)');
      containers.forEach(function (container) {
        container.classList.add('star-rating-processed');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        const fieldName = container.dataset.fieldName || 'rating';
        let currentRating = parseInt(hiddenInput?.value || '0', 10);

        // Build interactive stars.
        const starsContainer = document.createElement('div');
        starsContainer.className = 'star-rating-input__stars';
        starsContainer.setAttribute('role', 'radiogroup');
        starsContainer.setAttribute('aria-label', Drupal.t('Seleccionar valoración'));

        for (let i = 1; i <= 5; i++) {
          const star = document.createElement('button');
          star.type = 'button';
          star.className = 'star-rating-input__star';
          star.dataset.value = i;
          star.setAttribute('role', 'radio');
          star.setAttribute('aria-checked', i <= currentRating ? 'true' : 'false');
          star.setAttribute('aria-label', Drupal.t('@count estrellas', { '@count': i }));
          star.innerHTML = i <= currentRating ? '★' : '☆';

          star.addEventListener('click', function () {
            currentRating = i;
            if (hiddenInput) {
              hiddenInput.value = i;
            }
            updateStars();
          });

          star.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight' && i < 5) {
              starsContainer.children[i].focus();
            } else if (e.key === 'ArrowLeft' && i > 1) {
              starsContainer.children[i - 2].focus();
            }
          });

          starsContainer.appendChild(star);
        }

        // Rating text.
        const ratingText = document.createElement('span');
        ratingText.className = 'star-rating-input__text';

        function updateStars() {
          Array.from(starsContainer.children).forEach(function (star, index) {
            const val = index + 1;
            star.innerHTML = val <= currentRating ? '★' : '☆';
            star.setAttribute('aria-checked', val <= currentRating ? 'true' : 'false');
            star.classList.toggle('star-rating-input__star--active', val <= currentRating);
          });
          ratingText.textContent = currentRating > 0
            ? Drupal.t('@count de 5', { '@count': currentRating })
            : Drupal.t('Sin valorar');
        }

        container.appendChild(starsContainer);
        container.appendChild(ratingText);
        updateStars();
      });
    }
  };

})(Drupal);
```

### 11.7 Iconos: jaraba_icon()

Los templates de review utilizan los siguientes iconos del sistema:

| Categoría | Nombre | Uso | Color |
|-----------|--------|-----|-------|
| `ui` | `star` | Estrellas de rating (outline + filled) | `warning` (#F59E0B) |
| `ui` | `star-half` | Media estrella | `warning` |
| `ui` | `shield-check` | Badge "compra verificada" | `success` (#10B981) |
| `ui` | `thumbs-up` | Botón "útil" | `neutral` (#64748B) |
| `ui` | `edit` | Botón "escribir reseña" | `white` |
| `ui` | `reply` | Indicador de respuesta | `corporate` (#233D63) |
| `ui` | `arrow-right` | Enlace "ver todas" | `impulse` (#FF8C42) |
| `ui` | `message-circle` | Título de comentarios | `corporate` |
| `ui` | `plus` | Botón "añadir comentario" | `impulse` |
| `ai` | `sparkles` | Label de resumen IA | `impulse` (duotone) |

Todos los iconos se renderizan via `jaraba_icon(category, name, { variant, size, color })` que genera un `<img>` SVG inline con fallback emoji.

### 11.8 Mobile-first responsive

Base styles son para mobile (< 768px). Desktop via `@include respond-to(md)`.

Breakpoints aplicados en reviews:
- **Base (mobile)**: Single column, stack layout. Review cards y distribution bars apilados verticalmente.
- **md (768px+)**: Summary layout se convierte en flex-row (stats a la izquierda, distribución a la derecha). Photos grid 3 columns.
- **lg (992px+)**: Review page container max-width 900px.

### 11.9 Modales slide-panel para CRUD

El formulario de envío de review se abre en un slide-panel modal (no en una página separada), para que el usuario no abandone la página en la que está.

**Trigger**: Botón con clase `.js-slide-panel-trigger` y `href` apuntando a la ruta del formulario de review.

**Controlador**: Detecta si es request de slide-panel via `isSlidePanelRequest()`:

```php
private function isSlidePanelRequest(Request $request): bool {
  return $request->isXmlHttpRequest()
    && !$request->query->has('_wrapper_format');
}
```

Si es slide-panel, renderiza con `renderPlain()` (no `render()` — evita BigPipe placeholders sin resolver) y establece `$form['#action'] = $request->getRequestUri()` (SLIDE-PANEL-RENDER-001).

### 11.10 Theme Settings: configuración administrable

Se añaden settings de review en la tab `components` (Tab 8) del theme settings form:

```php
// En ecosistema_jaraba_theme/includes/theme-settings-components.inc
$form['review_settings'] = [
  '#type' => 'details',
  '#title' => t('Review Display'),
  '#open' => FALSE,
];

$form['review_settings']['review_stars_color'] = [
  '#type' => 'color',
  '#title' => t('Star color'),
  '#default_value' => $config->get('review_stars_color') ?: '#F59E0B',
];

$form['review_settings']['review_show_ai_summary'] = [
  '#type' => 'checkbox',
  '#title' => t('Show AI-generated review summaries'),
  '#default_value' => $config->get('review_show_ai_summary') ?? TRUE,
];

$form['review_settings']['review_photos_enabled'] = [
  '#type' => 'checkbox',
  '#title' => t('Allow photos in reviews'),
  '#default_value' => $config->get('review_photos_enabled') ?? TRUE,
];
```

Las variables CSS se inyectan en `hook_preprocess_html()`:

```php
// Añadir al mapping existente:
'review_stars_color' => '--ej-review-stars-color',
```

Y en SCSS:

```scss
.star-rating-input__star--active {
  color: var(--ej-review-stars-color, $ej-color-warning);
}
```

### 11.11 GrapesJS: Review Widget Block

**Archivo**: `jaraba_page_builder/js/plugins/reviews-block.js`

Sigue el dual architecture pattern: `model.defaults.script` para editor, `Drupal.behaviors` para público.

```javascript
/**
 * @file
 * GrapesJS block: Review Widget.
 *
 * Renders a review summary widget for any reviewable entity.
 * In editor: fetches data from API and renders preview.
 * On public pages: server-side rendered (no JS needed).
 */
grapesjs.plugins.add('jaraba-reviews-block', function (editor) {
  const bm = editor.BlockManager;

  bm.add('jaraba-review-widget', {
    label: Drupal.t('Review Widget'),
    category: Drupal.t('Social Proof'),
    attributes: { class: 'gjs-block-review' },
    content: {
      type: 'jaraba-review-widget',
      content: '<div data-jaraba-review-widget data-entity-type="" data-entity-id="">Reviews will appear here</div>',
    },
  });

  editor.DomComponents.addType('jaraba-review-widget', {
    model: {
      defaults: {
        tagName: 'div',
        droppable: false,
        traits: [
          {
            type: 'select',
            name: 'data-entity-type',
            label: Drupal.t('Entity Type'),
            options: [
              { value: 'product_retail', name: Drupal.t('Product') },
              { value: 'producer_profile', name: Drupal.t('Producer') },
              { value: 'provider_profile', name: Drupal.t('Service Provider') },
              { value: 'course', name: Drupal.t('Course') },
            ],
          },
          {
            type: 'number',
            name: 'data-entity-id',
            label: Drupal.t('Entity ID'),
          },
        ],
      },
    },
  });
});
```

En el frontend público, el server-side rendering inyecta el HTML del widget via `hook_preprocess` del PageBuilder view builder.

---

## 12. Internacionalización (i18n)

Todos los textos visibles al usuario siguen las reglas de i18n del proyecto:

| Contexto | Método | Ejemplo |
|----------|--------|---------|
| Twig templates | `{% trans %}...{% endtrans %}` | `{% trans %}Reseñas{% endtrans %}` |
| PHP services/controllers | `$this->t()` con placeholders `@name` | `$this->t('Review @id approved.', ['@id' => $id])` |
| PHP entity fields | `new TranslatableMarkup()` o `t()` | `->setLabel(t('Valoración'))` |
| JavaScript | `Drupal.t()` con placeholders `@name` | `Drupal.t('@count estrellas', { '@count': i })` |
| Route titles | `_title_callback` | `ReviewDisplayController::reviewsPageTitle` |
| Entity labels | `@Translation()` en anotación | `label = @Translation("Reseña")` |
| Permissions | `title:` en `.permissions.yml` | `title: 'Enviar reseñas'` |

---

## 13. Seguridad

| Directriz | Implementación | Archivos |
|-----------|---------------|----------|
| **CSRF-REQUEST-HEADER-001** | `_csrf_request_header_token: 'TRUE'` en todas las rutas POST/PATCH/DELETE de API de reviews | `.routing.yml` de cada módulo |
| **TWIG-XSS-001** | Autoescaping Twig por defecto. `\|raw` solo para HTML proveniente de sanitizers | Todos los templates |
| **INNERHTML-XSS-001** | `Drupal.checkPlain()` para todo texto insertado via JS | `star-rating.js` |
| **TENANT-ISOLATION-ACCESS-001** | Access handlers con DI de TenantContextService verifican tenant match | 6 access handlers |
| **API-WHITELIST-001** | Cada endpoint POST define `ALLOWED_FIELDS` explícitamente | Controladores API |
| **PRESAVE-RESILIENCE-001** | `\Drupal::hasService()` + try-catch en presave para servicios opcionales (AI summary, aggregation) | `.module` presave hooks |
| **accessCheck(TRUE)** | Todas las entity queries públicas | Servicios y controladores |

---

## 14. Integración con Ecosistema

### 14.1 TenantContextService / TenantBridgeService

- Access handlers inyectan `TenantContextService` para obtener el group ID del tenant actual.
- Los update hooks que migran `tenant_id` de taxonomy_term a group usan `TenantBridgeService::getGroupForTenant()`.
- Las queries de reviews en servicios filtran por `tenant_id` cuando el contexto lo requiere.

### 14.2 PlanResolverService (feature gates)

- AI summaries solo disponibles en plans Professional/Enterprise.
- Photo uploads limitados por plan (Free: 0, Starter: 2, Professional: 5, Enterprise: 10).
- Se consulta `PlanResolverService::hasFeature('review_ai_summary')` antes de generar/mostrar.

### 14.3 AI Copilots (respuestas asistidas)

Los AI copilot endpoints existentes (`/api/v1/merchant/copilot/generate/review-response` y `/api/v1/producer/copilot/generate/review-response`) se mantienen. Se añaden endpoints análogos para ServiciosConecta y Mentoring.

### 14.4 Recommendation Engine

El engagement con reviews (crear, marcar útil, responder) alimenta el `engagement_score` del usuario, que a su vez influye en las recomendaciones de contenido.

### 14.5 Analytics y engagement_score

Cada interacción con reviews (submit, helpful vote, response) genera un evento de analytics que incrementa el engagement_score del usuario via `AnalyticsEventController`.

---

## 15. Navegación Drupal Admin

### 15.1 /admin/structure: Field UI + Settings

Cada entidad de review tiene `field_ui_base_route` apuntando a una ruta de settings en `/admin/structure/`:

| Entidad | Ruta Settings | Field UI Route |
|---------|--------------|----------------|
| `comercio_review` | `/admin/structure/comercio-reviews` | `jaraba_comercio_conecta.review.settings` |
| `review_agro` | `/admin/structure/agro-review` | `entity.review_agro.settings` |
| `review_servicios` | `/admin/structure/servicios-review` | `jaraba_servicios_conecta.review_servicios.settings` |
| `session_review` | `/admin/structure/session-review/settings` | `entity.session_review.settings` |
| `course_review` | `/admin/structure/course-review` | `entity.course_review.settings` |
| `content_comment` | `/admin/structure/content-comment` | `entity.content_comment.settings` |

### 15.2 /admin/content: Listados de entidades

Todas las entidades tienen link `collection` en `/admin/content/`:

| Entidad | Ruta Collection |
|---------|----------------|
| `comercio_review` | `/admin/content/comercio-reviews` |
| `review_agro` | `/admin/content/agro-reviews` |
| `review_servicios` | `/admin/content/servicios-reviews` |
| `session_review` | `/admin/content/session-reviews` |
| `course_review` | `/admin/content/course-reviews` |
| `content_comment` | `/admin/content/comments` |

### 15.3 Entity links: CRUD completo

Todas las entidades tienen los 5 links estándar: `canonical`, `add-form`, `edit-form`, `delete-form`, `collection`. Todos usan `AdminHtmlRouteProvider` como `route_provider`.

---

## 16. Fases de Implementación

### Fase 1: ReviewableEntityTrait + Armonización (10-14h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear `ReviewableEntityTrait` en `ecosistema_jaraba_core`.
2. Modificar `ReviewRetail` para usar trait (+ campos `ai_summary`, `ai_summary_generated_at`).
3. Modificar `ReviewAgro` para usar trait (+ campos `helpful_count`, `ai_summary`, `ai_summary_generated_at`, mover access handler a `Access\`).
4. Modificar `ReviewServicios` para usar trait (+ campos `tenant_id`, `verified_purchase`, `helpful_count`, `photos`, `ai_summary`, `ai_summary_generated_at`, añadir `flagged`).
5. Elevar `SessionReview` a estándar (+ `EntityChangedTrait`, `EntityOwnerTrait`, trait, `tenant_id`, `uid`, `changed`, todos los campos del trait, access handler, route provider, links).
6. Update hooks en cada módulo (4 hooks).
7. Crear access handlers con tenant isolation (4 handlers).
8. Crear `SessionReviewForm extends PremiumEntityFormBase`.

### Fase 2: Nuevas Entidades (6-8h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear `CourseReview` entity, interface, access handler, form, list builder.
2. Crear `ContentComment` entity, interface, access handler, form, list builder.
3. Update hooks para instalar ambas entidades.
4. Rutas admin y Field UI para ambas.
5. Registrar en `services.yml` de cada módulo.

### Fase 3: Servicios Transversales (12-16h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear `ReviewModerationService`.
2. Crear `ReviewAggregationService`.
3. Crear `ReviewSchemaOrgService`.
4. Crear `ReviewInvitationService`.
5. Crear `ReviewAiSummaryService`.
6. Registrar los 5 servicios en `services.yml`.
7. Refactorizar servicios verticales para delegar a transversales.
8. Crear presave hooks con PRESAVE-RESILIENCE-001.

### Fase 4: Controladores + Rutas (6-8h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear `ReviewDisplayController` (frontend público).
2. Completar rutas API en ServiciosConecta y Mentoring.
3. Crear `CommentApiController` para Content Hub.
4. Registrar rutas frontend en cada módulo vertical.
5. Rutas API con CSRF-REQUEST-HEADER-001.

### Fase 5: Frontend (Templates + SCSS + JS) (14-20h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear `page--reviews.html.twig`.
2. Crear 5 templates parciales.
3. Crear `_reviews.scss` con Federated Design Tokens.
4. Crear `star-rating.js`.
5. Añadir body classes en `hook_preprocess_html()`.
6. Añadir theme suggestions en `hook_theme_suggestions_page_alter()`.
7. Crear `template_preprocess_{entity}()` para las 6 entidades (ENTITY-PREPROCESS-001).
8. Añadir library en `.libraries.yml`.
9. Añadir import en `main.scss`.
10. Integrar widget de reviews en templates de detalle existentes.
11. Theme settings para reviews.

### Fase 6: GrapesJS Review Widget Block (4-6h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Crear plugin `reviews-block.js`.
2. Registrar block en el sistema de plugins de PageBuilder.
3. Server-side rendering del widget en public pages.
4. CSS para el bloque en contexto editor.

### Fase 7: Schema.org + SEO (4-6h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Implementar `generateAggregateRating()`.
2. Implementar `generateReviewList()`.
3. Integrar JSON-LD en `hook_preprocess_html()` de cada módulo vertical.
4. Verificar con Google Structured Data Testing Tool.

### Fase 8: Security Fixes (6-8h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Migrar `tenant_id` de taxonomy_term a group (2 update hooks).
2. Refactorizar 3 access handlers existentes con tenant isolation DI.
3. Añadir CSRF a rutas ComercioConecta que faltan.
4. Añadir API-WHITELIST-001 a todos los endpoints POST.
5. Verificar accessCheck(TRUE) en todas las queries públicas.

### Fase 9: Cumplimiento de Directrices (4-6h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Verificar i18n completo en todos los archivos.
2. Verificar WCAG :focus-visible en todos los interactivos.
3. Verificar ROUTE-LANGPREFIX-001 en templates y JS.
4. Verificar prefers-reduced-motion.
5. Verificar dark mode compatibility.
6. Verificar print styles.

### Fase 10: Testing + QA (8-12h)

**Estado**: COMPLETADO (2026-02-27)

**Tareas**:
1. Unit tests para ReviewableEntityTrait.
2. Unit tests para ReviewModerationService, ReviewAggregationService.
3. Kernel tests para schema migration (update hooks).
4. Kernel tests para access control con tenant isolation.
5. Functional tests para rutas frontend.
6. Functional tests para API endpoints.
7. Manual testing con checklist (sección 18).

### Fase 11: Elevación Clase Mundial — 18 Brechas (156-218h)

**Estado**: PENDIENTE

**Objetivo**: Elevar el sistema de Reviews & Comentarios del 55% actual al 80%+ de cobertura de features de clase mundial, cerrando las 18 brechas identificadas en la auditoría post-implementación v2.0.0.

**Referencia**: `docs/analisis/2026-02-26_Auditoria_Sistemas_Calificaciones_Comentarios_Clase_Mundial_v1.md` sección 12.

#### Sprint 11.1 — Table Stakes (46-64h)

**Estado**: PENDIENTE

**Tareas**:
1. **B-01: Helpfulness Voting UI** (8-12h)
   - Botones "útil/no útil" en review cards con conteo visible
   - Algoritmo Wilson Lower Bound Score para ranking de reviews
   - Endpoint API `POST /api/v1/reviews/{type}/{id}/vote` con CSRF
   - Actualización de `helpful_count` en entidad via presave
   - SCSS: botones inline con animación de feedback
   - JS: fetch + optimistic update

2. **B-02: Review Filtering/Sorting UI** (10-14h)
   - Barra de filtros: por estrellas (1-5), con fotos, verificadas
   - Ordenación: más recientes, más útiles, mejor valoración, peor valoración
   - Server-side: query parameters en `ReviewDisplayController`
   - Client-side: JS sorting sin reload para datasets pequeños (<100)
   - Contadores por filtro (ej: "4 estrellas (23)")
   - SCSS responsive mobile-first

3. **B-03: Verified Purchase Badge** (6-8h)
   - Lógica de verificación automática: cruzar `uid` del reviewer con transacciones/bookings del `target_entity`
   - Badge SVG "Compra verificada" / "Cliente verificado" según vertical
   - Template parcial `_verified-badge.html.twig`
   - Presave hook: auto-set `verified_purchase` si existe transacción
   - SCSS: badge inline con icono check

4. **B-04: Response from Owner** (10-14h)
   - Campo `owner_response` (string_long) + `owner_response_date` (datetime) en trait o entidad
   - Formulario de respuesta en admin (slide-panel)
   - Renderizado público: respuesta indentada bajo review card
   - Notificación al reviewer cuando el propietario responde
   - Permiso `respond to reviews` para propietarios/providers
   - SCSS: estilo diferenciado con icono de propietario

5. **B-05: Review Analytics Dashboard** (12-16h)
   - Página admin `/admin/reports/reviews`
   - Métricas: volumen total, rating promedio (trend 30/60/90 días), tasa de respuesta, distribución por sentiment
   - Gráficos: rating trend line, distribución de estrellas bar chart, reviews por vertical pie chart
   - Filtros: por vertical, por tenant, por período
   - Exportación CSV
   - SCSS: glassmorphism cards con design tokens

#### Sprint 11.2 — Differentiators (54-76h)

**Estado**: PENDIENTE

**Tareas**:
1. **B-06: Photo Gallery UI** (10-14h)
   - Galería lightbox para fotos adjuntas en review detail
   - Thumbnails en review cards (max 3 + "+N más")
   - Upload con preview en formulario de review (drag & drop)
   - Image styles: review_thumb (150x150), review_gallery (800x600)
   - Lazy loading con IntersectionObserver
   - SCSS: grid responsive + lightbox overlay

2. **B-07: Fake Review Detection** (12-16h)
   - Servicio `FakeReviewDetectionService` en jaraba_ai_agents
   - Scoring via Haiku 4.5 (fast tier): análisis de patrones textuales, metadata
   - Criterios: cuenta nueva + review extrema, reviews duplicadas, patrones de lenguaje sospechosos
   - Flag automático si score > threshold (configurable por tenant)
   - Dashboard de reviews flaggeadas para moderador
   - Integración con ReviewModerationService

3. **B-08: Review SEO Pages** (8-12h)
   - Rutas `/reviews/{entity_type}/{slug}` con paginación SEO (rel=prev/next)
   - Template `page--reviews-entity.html.twig` con AggregateRating JSON-LD
   - Meta tags: title, description, canonical, OG
   - Breadcrumb: Home > Vertical > Entidad > Reviews
   - Sitemap integration

4. **B-09: AI Sentiment Overlay** (6-8h)
   - Indicadores visuales: emoji + etiqueta (Positivo/Neutro/Negativo)
   - Cálculo via presave con Haiku 4.5 o regex fallback
   - Campo `sentiment` (list_string: positive/neutral/negative)
   - Filtro por sentiment en review list
   - SCSS: color-coded badges (verde/gris/rojo)

5. **B-10: Per-Tenant Review Config** (8-12h)
   - ConfigEntity `ReviewTenantSettings` con: max_photos, moderation_policy (manual/auto/hybrid), auto_approve_threshold, invitation_template, review_guidelines_text
   - Admin UI por tenant: `/admin/config/reviews/tenant/{group_id}`
   - Integración con ReviewModerationService y ReviewInvitationService
   - Default values para tenants sin config explícita

6. **B-11: Review Notification System** (10-14h)
   - Eventos: review_created, review_approved, review_responded, invitation_reminder
   - Canal email: templates MJML por evento (4 templates)
   - Canal in-app: integración con sistema de notificaciones existente
   - Preferencias de notificación por usuario
   - Cron para invitaciones recordatorio (7 días post-transacción)

#### Sprint 11.3 — Consolidación (56-78h)

**Estado**: PENDIENTE

**Tareas**:
1. **B-12: Auto-Translate Reviews** (6-8h)
   - Integración con ModelRouterService (fast tier) para traducción ES↔EN↔PT-BR
   - Campo `translated_body` (map) con traducciones por idioma
   - Detección automática de idioma del review
   - UI: toggle "Ver en [idioma]" en review card
   - Cron batch para traducción diferida

2. **B-13: Review Incentives / Gamification** (10-14h)
   - Entidad `ReviewerBadge` con tipos: first_review, helpful_reviewer, top_reviewer, photo_reviewer
   - Sistema de puntos: review=10pts, con foto=15pts, útil=5pts recibidos
   - Leaderboard por vertical: `/reviews/top-reviewers`
   - Badge display en review cards y perfil de usuario
   - Cron mensual para recalcular rankings

3. **B-14: Review Import/Export** (6-8h)
   - Import CSV con mapping de columnas (rating, body, author_email, date, product_id)
   - Validación y deduplicación por hash(email+target+date)
   - Export CSV/JSON con filtros
   - Drush command `jaraba:reviews:import` y `jaraba:reviews:export`
   - Batch processing para imports grandes (>1000 reviews)

4. **B-15: A/B Testing Review Layout** (8-12h)
   - 3 variantes: expanded (default), compact (collapsed), photo-first
   - Asignación por cookie/session (50/50 split configurable)
   - Métricas: click-through rate, time on page, conversion
   - Integración con analytics (event tracking)
   - Admin toggle para activar/desactivar A/B test

5. **B-16: Review Video Support** (12-16h)
   - Campo `video` (file, max 60s, formatos mp4/webm)
   - Upload con progress bar y preview thumbnail auto-generado
   - Player inline en review card (lazy load, no autoplay)
   - Moderación de video: review manual antes de publicar
   - Image styles para thumbnail: review_video_thumb (320x180)

6. **B-17: Review API Pública** (8-12h)
   - Endpoints REST: `GET /api/v1/public/reviews/{type}/{id}` (paginado, filtrable)
   - Autenticación: API key + rate limiting (100 req/min)
   - Documentación OpenAPI/Swagger
   - Permisos: `access review api`
   - Response format: JSON con AggregateRating incluido

7. **B-18: Review Webhooks** (6-8h)
   - Eventos: review_created, review_approved, review_responded, rating_changed
   - ConfigEntity `ReviewWebhookSubscription` con URL, eventos, secret
   - Delivery: Queue worker con retry (3 intentos, backoff exponencial)
   - Signature: HMAC-SHA256 del payload
   - Admin UI: `/admin/config/reviews/webhooks`

#### Estimación Fase 11

| Sprint | Horas min | Horas max | Features |
|--------|-----------|-----------|----------|
| 11.1 Table Stakes | 46 | 64 | B-01 a B-05 |
| 11.2 Differentiators | 54 | 76 | B-06 a B-11 |
| 11.3 Consolidación | 56 | 78 | B-12 a B-18 |
| **TOTAL** | **156** | **218** | **18 brechas** |

---

### Extensión a 4 verticales adicionales (empleabilidad, emprendimiento, jarabalex, andalucia_ei)

Los 4 verticales restantes de `VERTICAL-CANONICAL-001` se habilitan bajo demanda reutilizando toda la infraestructura construida en las Fases 1-10. No requieren nuevas entidades de review en la primera iteración.

**Estrategia por vertical:**

| Vertical | Entidad de dominio existente | Tipo de review | Campos específicos |
|----------|------------------------------|----------------|-------------------|
| **Empleabilidad** | `JobPosting`, `EmployerProfile` | Valoración de empleadores y ofertas de empleo | `interview_difficulty` (1-5), `recommend_employer` (boolean), `employment_status` (current/former), `salary_accuracy` (1-5) |
| **Emprendimiento** | `MentorProfile` (emprendimiento), `StartupProfile` | Valoración de mentores de startups y programas de emprendimiento | `mentoring_quality` (1-5), `network_value` (1-5), `practical_applicability` (1-5) |
| **JarabaLex** | `LegalService`, `LawyerProfile` | Valoración de abogados y servicios legales | `case_outcome_satisfaction` (1-5), `communication_quality` (1-5), `value_for_money` (1-5), `case_type` (list_string) |
| **Andalucía EI** | `SolicitudEi`, `ProgramaEi` | Valoración de programas e iniciativas de emprendimiento | `program_usefulness` (1-5), `mentor_quality` (1-5), `resources_quality` (1-5), `would_recommend` (boolean) |

**Patrón de implementación (idéntico para los 4):**

1. **Opción A — Trait sobre entidad de dominio (recomendada)**: Aplicar `ReviewableEntityTrait` directamente a la entidad de dominio existente (ej: `JobPosting` recibe campos `rating`, `review_status`, `helpful_count`, etc.). Ventaja: un solo save, Schema.org integrado. Limitación: 1 review por entidad (suficiente para muchos casos).

2. **Opción B — Entidad de review dedicada**: Crear `employer_review`, `startup_mentor_review`, `legal_review`, `program_ei_review` con `ReviewableEntityTrait` + campos específicos del vertical. Ventaja: múltiples reviews por entidad, moderación independiente. Coste: ~4-6h por vertical.

**Lo que ya funciona sin código adicional (tras Fases 1-10):**
- `ReviewModerationService::moderate()` acepta cualquier `$entity_type_id` — funciona con entidades nuevas automáticamente.
- `ReviewAggregationService::getAggregateRating()` acepta `$entity_type_id` + `$entity_id` — funciona con cualquier entidad.
- `ReviewSchemaOrgService::generateAggregateRating()` — funciona con cualquier entidad que tenga el trait.
- `ReviewInvitationService` — configurable por vertical en YAML.
- `ReviewAiSummaryService` — procesa cualquier entidad con `ai_summary` field.
- Templates parciales (`_star-rating`, `_review-card`, `_review-summary`) — reutilizables con `{% include %}`.
- `star-rating.js` — widget genérico que funciona con cualquier formulario.
- SCSS `_reviews.scss` — estilos genéricos independientes del vertical.

**Estimación por vertical adicional**: 4-8h (Opción A: 4h, Opción B: 6-8h).
**Estimación total para los 4**: 16-32h adicionales (fuera del scope de las Fases 1-10, activable bajo demanda).

---

## 17. Estrategia de Testing

### Unit Tests

| Test | Clase | Qué verifica |
|------|-------|--------------|
| ReviewableEntityTraitTest | `tests/src/Unit/ReviewableEntityTraitTest.php` | Constantes, `getReviewStatus()` con fallback, `getRatingStarsDisplay()` |
| ReviewModerationServiceTest | `tests/src/Unit/ReviewModerationServiceTest.php` | Status transitions, invalid status rejection, status field mapping |
| ReviewAggregationServiceTest | `tests/src/Unit/ReviewAggregationServiceTest.php` | Rating calculation, distribution, cache invalidation |
| ReviewSchemaOrgServiceTest | `tests/src/Unit/ReviewSchemaOrgServiceTest.php` | JSON-LD structure, AggregateRating format |

### Kernel Tests

| Test | Clase | Qué verifica |
|------|-------|--------------|
| ReviewSchemaMigrationTest | `tests/src/Kernel/ReviewSchemaMigrationTest.php` | Update hooks crean campos correctamente, datos existentes preservados |
| ReviewAccessControlTest | `tests/src/Kernel/ReviewAccessControlTest.php` | Tenant isolation: usuario de tenant A no puede editar review de tenant B |
| ReviewEntityCrudTest | `tests/src/Kernel/ReviewEntityCrudTest.php` | CRUD completo para las 6 entidades de review |

### Functional Tests

| Test | Clase | Qué verifica |
|------|-------|--------------|
| ReviewFrontendTest | `tests/src/Functional/ReviewFrontendTest.php` | Página de reviews retorna 200, contiene widget, paginación funciona |
| ReviewApiTest | `tests/src/Functional/ReviewApiTest.php` | Endpoints API retornan JSON correcto, CSRF obligatorio, permisos respetados |

---

## 18. Verificación y Despliegue

### 18.1 Checklist manual

1. `lando drush cr` sin errores PHP.
2. `lando drush entity:updates` muestra campos nuevos instalados.
3. `lando drush updb` ejecuta todos los update hooks sin errores.
4. Crear review desde admin → verificar formulario PremiumEntityFormBase con secciones.
5. Verificar tenant isolation: usuario de tenant A NO puede editar review de tenant B.
6. Visitar `/es/comercio/{slug}/reviews` → verificar página frontend con star widget, distribución, listado.
7. Verificar Schema.org JSON-LD en source HTML de página de producto.
8. Verificar AI summary aparece (si configurado).
9. Clic "Escribir reseña" → verificar slide-panel modal.
10. Enviar review → verificar estado `pending`.
11. Moderar review desde admin → verificar que `approved` la hace visible.
12. Verificar "Útil" incrementa `helpful_count`.
13. Verificar verified_purchase badge aparece para compras verificadas.
14. Verificar fotos en review (upload + display).
15. Visitar `/es/blog/{slug}#comments` → verificar comentarios threaded.
16. Verificar Field UI en `/admin/structure/comercio-reviews`.
17. Verificar listado admin en `/admin/content/comercio-reviews`.
18. Verificar GrapesJS block de reviews en landing page editor.
19. Verificar i18n: textos en español correctos.
20. Verificar WCAG: tab navigation, focus-visible outlines.
21. Verificar dark mode: colores correctos.
22. Verificar mobile (320px): layout responsive correcto.
23. Verificar SCSS compila sin warnings.
24. Verificar iconos jaraba_icon() renderizan correctamente.
25. Verificar RSS feed no roto por cambios.

### 18.2 Comandos de verificación

```bash
# Rebuild + update
lando drush cr
lando drush updb

# Verify entity updates
lando drush entity:updates

# Run cron for AI summaries and scheduled reviews
lando drush cron

# Run tests
lando php web/core/scripts/run-tests.sh --module ecosistema_jaraba_core --filter Review
lando php web/core/scripts/run-tests.sh --module jaraba_comercio_conecta --filter Review
lando php web/core/scripts/run-tests.sh --module jaraba_agroconecta_core --filter Review

# SCSS compilation
cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/main.css --style=compressed 2>&1 | grep -i warning

# Schema.org validation
curl -s https://jaraba-saas.lndo.site/es/comercio/test-merchant/reviews | grep -o 'application/ld+json'
```

### 18.3 Browser verification URLs

| Verificación | URL |
|--------------|-----|
| Reviews ComercioConecta | `https://jaraba-saas.lndo.site/es/comercio/{slug}/reviews` |
| Reviews AgroConecta | `https://jaraba-saas.lndo.site/es/agro/{slug}/reviews` |
| Reviews ServiciosConecta | `https://jaraba-saas.lndo.site/es/servicios/{slug}/reviews` |
| Reviews LMS | `https://jaraba-saas.lndo.site/es/cursos/{slug}/reviews` |
| Comments Content Hub | `https://jaraba-saas.lndo.site/es/blog/{slug}#comments` |
| Admin ComercioConecta | `https://jaraba-saas.lndo.site/admin/content/comercio-reviews` |
| Field UI | `https://jaraba-saas.lndo.site/admin/structure/comercio-reviews` |
| Theme Settings | `https://jaraba-saas.lndo.site/admin/appearance/settings/ecosistema_jaraba_theme` (Tab: Components) |

### 18.4 Checklist de directrices cumplidas

- [x] TENANT-ISOLATION-ACCESS-001: 6 access handlers con verificación de tenant
- [x] TENANT-BRIDGE-001: tenant_id como entity_reference a group
- [x] PREMIUM-FORMS-PATTERN-001: 6 formularios con getSectionDefinitions() + getFormIcon()
- [x] ENTITY-PREPROCESS-001: 6 template_preprocess functions
- [x] PRESAVE-RESILIENCE-001: try-catch en presave hooks
- [x] ROUTE-LANGPREFIX-001: Url::fromRoute() en templates y JS
- [x] ICON-CONVENTION-001: jaraba_icon() en todos los templates
- [x] TWIG-XSS-001: Autoescaping activo
- [x] CSRF-REQUEST-HEADER-001: En todas las rutas POST/PATCH/DELETE
- [x] API-WHITELIST-001: ALLOWED_FIELDS en endpoints POST
- [x] ZERO-REGION-POLICY: page--reviews.html.twig sin regiones
- [x] DART-SASS-MODERN: @use 'sass:color', color.adjust()
- [x] MOBILE-FIRST: Base mobile → @include respond-to(md)
- [x] SLIDE-PANEL-RENDER-001: renderPlain() para modales
- [x] LABEL-NULLSAFE-001: entity_keys.label en todas las entidades
- [x] i18n: {% trans %}, $this->t(), Drupal.t()
- [x] WCAG-FOCUS-001: :focus-visible en todos los interactivos
- [x] INNERHTML-XSS-001: Drupal.checkPlain() en JS
- [x] SCSS-VARS-SSOT-001: var(--ej-*, $fallback) en todos los estilos
- [x] Dark mode: Variantes .dark-mode
- [x] prefers-reduced-motion: Transitions desactivadas
- [x] Print styles: Ocultar interactivos

---

## 19. Troubleshooting

| Problema | Causa | Solución |
|----------|-------|----------|
| "Table review_agro already exists" al ejecutar update hook | Hook ejecutado dos veces | Los hooks verifican existencia con `getFieldStorageDefinition()` — idempotentes |
| Reviews no visibles en frontend | Estado `pending` por defecto | Moderar a `approved` desde admin |
| Schema.org JSON-LD no aparece | No hay reviews aprobadas para el target | Crear y aprobar al menos 1 review |
| Star rating widget no funciona | JS no cargado | Verificar library attachment en `.libraries.yml` y que el controlador la incluye |
| Slide-panel muestra HTML roto | Se usó `render()` en vez de `renderPlain()` | Verificar SLIDE-PANEL-RENDER-001 en el controlador |
| Error "tenant_id column doesn't exist" | Update hook no ejecutado | `lando drush updb` |
| SCSS warning "Using lighten() is deprecated" | Uso de función legacy | Usar `color.adjust($color, $lightness: N%)` |
| Access denied al crear review | Permiso `submit reviews` no asignado al rol | Asignar permiso en admin → people → permissions |
| AI summary retorna NULL | Servicio AI no disponible | Verificar `\Drupal::hasService('ai.provider')` y API key |

---

## 20. Referencias Cruzadas

| Documento | Relación |
|-----------|----------|
| `docs/analisis/2026-02-26_Auditoria_Sistemas_Calificaciones_Comentarios_Clase_Mundial_v1.md` v2.0.0 | Auditoría que origina este plan (actualizada con estado post-implementación y 18 brechas) |
| `docs/00_DIRECTRICES_PROYECTO.md` v89.0.0 | Directrices de cumplimiento obligatorio |
| `docs/00_FLUJO_TRABAJO_CLAUDE.md` v43.0.0 | Flujo de trabajo y aprendizajes |
| `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Arquitectura de theming (CSS vars, icons, SCSS) |
| `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` | Plan de blog (patrón de referencia) |
| `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` | Plan de IA (ReviewAiSummaryService usa ModelRouterService) |

---

## 21. Registro de Cambios

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 2026-02-26 | 1.0.0 | Creación inicial del plan de implementación |
| 2026-02-27 | 2.0.0 | Fases 1-10 marcadas COMPLETADO. Nueva Fase 11: Elevación Clase Mundial con 18 brechas (B-01 a B-18) en 3 sprints (Table Stakes, Differentiators, Consolidación). Auditoría post-implementación identifica 55% de cobertura vs 80% target. Estimación Fase 11: 156-218h adicionales. |
