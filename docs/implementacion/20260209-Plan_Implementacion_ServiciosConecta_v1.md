# Plan de Implementacion ServiciosConecta v1.0

> **Fecha:** 2026-02-09
> **Ultima actualizacion:** 2026-02-09
> **Autor:** Claude Opus 4.6
> **Version:** 1.0.0
> **Estado:** Planificacion inicial
> **Vertical:** ServiciosConecta (Plataforma de Confianza Digital para Profesionales)
> **Modulo principal:** `jaraba_servicios_conecta`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision y Posicionamiento](#11-visión-y-posicionamiento)
  - [1.2 Relacion con la infraestructura existente](#12-relación-con-la-infraestructura-existente)
  - [1.3 Patron arquitectonico de referencia](#13-patrón-arquitectónico-de-referencia)
  - [1.4 Avatar principal: Elena](#14-avatar-principal-elena)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-técnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuración)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-código)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstraccion @ai.provider](#313-directriz-ai-via-abstracción-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
- [4. Arquitectura del Modulo](#4-arquitectura-del-módulo)
  - [4.1 Nombre y ubicacion](#41-nombre-y-ubicación)
  - [4.2 Dependencias](#42-dependencias)
  - [4.3 Estructura de directorios](#43-estructura-de-directorios)
  - [4.4 Compilacion SCSS](#44-compilación-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 1: Services Core + Provider Profile + Service Offerings](#6-fase-1-services-core--provider-profile--service-offerings)
  - [6.1 Justificacion](#61-justificación)
  - [6.2 Entidades](#62-entidades)
  - [6.3 Taxonomias](#63-taxonomías)
  - [6.4 Services](#64-services)
  - [6.5 Controllers](#65-controllers)
  - [6.6 Templates y Parciales Twig](#66-templates-y-parciales-twig)
  - [6.7 Frontend Assets](#67-frontend-assets)
  - [6.8 Archivos a Crear](#68-archivos-a-crear)
  - [6.9 Archivos a Modificar](#69-archivos-a-modificar)
  - [6.10 SCSS: Directrices](#610-scss-directrices)
  - [6.11 Verificacion](#611-verificación)
- [7. FASE 2: Booking Engine + Calendar Sync](#7-fase-2-booking-engine--calendar-sync)
  - [7.1 Justificacion](#71-justificación)
  - [7.2 Entidades](#72-entidades)
  - [7.3 Services](#73-services)
  - [7.4 Controllers](#74-controllers)
  - [7.5 Templates y Parciales Twig](#75-templates-y-parciales-twig)
  - [7.6 Frontend Assets](#76-frontend-assets)
  - [7.7 Archivos a Crear](#77-archivos-a-crear)
  - [7.8 Archivos a Modificar](#78-archivos-a-modificar)
  - [7.9 SCSS: Directrices](#79-scss-directrices)
  - [7.10 Verificacion](#710-verificación)
- [8. FASE 3: Video Conferencing + Buzon de Confianza + Firma Digital](#8-fase-3-video-conferencing--buzón-de-confianza--firma-digital)
  - [8.1 Justificacion](#81-justificación)
  - [8.2 Entidades](#82-entidades)
  - [8.3 Services](#83-services)
  - [8.4 Controllers](#84-controllers)
  - [8.5 Templates y Parciales Twig](#85-templates-y-parciales-twig)
  - [8.6 Archivos a Crear](#86-archivos-a-crear)
  - [8.7 Archivos a Modificar](#87-archivos-a-modificar)
  - [8.8 Verificacion](#88-verificación)
- [9. FASE 4: Portal Cliente Documental](#9-fase-4-portal-cliente-documental)
- [10. FASE 5: AI Triaje de Casos + Presupuestador Automatico](#10-fase-5-ai-triaje-de-casos--presupuestador-automático)
- [11. FASE 6: Copilot de Servicios](#11-fase-6-copilot-de-servicios)
- [12. FASE 7: Dashboard Profesional](#12-fase-7-dashboard-profesional)
- [13. FASE 8: Dashboard Admin + Analytics](#13-fase-8-dashboard-admin--analytics)
- [14. FASE 9: Sistema de Facturacion](#14-fase-9-sistema-de-facturación)
- [15. FASE 10: Reviews, Notificaciones y API Publica](#15-fase-10-reviews-notificaciones-y-api-pública)
- [16. Paleta de Colores y Design Tokens](#16-paleta-de-colores-y-design-tokens)
  - [16.1 Tokens de Color del Vertical](#161-tokens-de-color-del-vertical)
  - [16.2 Presets por Tipo de Profesional](#162-presets-por-tipo-de-profesional)
  - [16.3 Implementacion en SCSS](#163-implementación-en-scss)
- [17. Patron de Iconos SVG](#17-patrón-de-iconos-svg)
- [18. Orden de Implementacion Global](#18-orden-de-implementación-global)
- [19. Registro de Cambios](#19-registro-de-cambios)

---

## 1. Resumen Ejecutivo

ServiciosConecta es el quinto y ultimo vertical comercializable del Ecosistema Jaraba. A diferencia de los verticales de comercio (AgroConecta, ComercioConecta), ServiciosConecta opera bajo el paradigma **"No vendemos stock, vendemos confianza, tiempo y conocimiento"**. Su pitch diferenciador es: _"Tu consulta profesional en la nube: agenda, documentos y firmas sin salir de la plataforma"_.

El vertical digitaliza el capital intelectual y profesional de zonas rurales y periurbanas, conectando abogados, clinicas, arquitectos, consultores, asesores fiscales y otros profesionales con clientes que necesitan servicios de confianza. ServiciosConecta completa el ecosistema de impacto digital, habilitando a Jaraba para ofrecer una propuesta integral unica: desde la formacion y empleabilidad hasta la gestion de una consulta profesional consolidada.

### 1.1 Vision y Posicionamiento

ServiciosConecta se posiciona como la **Plataforma de Confianza Digital para Profesionales** con cinco pilares diferenciadores:

- **Booking Engine nativo**: Motor de reservas con pago anticipado, sincronizacion bidireccional Google Calendar/Outlook, slots inteligentes anti-colision, videollamada Jitsi integrada y recordatorios multicanal. TTV objetivo < 45 segundos.
- **Buzon de Confianza**: Custodia documental cifrada end-to-end (AES-256-GCM) con arquitectura zero-knowledge. El servidor nunca ve el contenido en texto plano. Cumplimiento RGPD completo con derechos ARCO y cadena de auditoria inmutable tipo blockchain.
- **Firma Digital PAdES**: Integracion nativa con AutoFirma y Cl@ve para firmas electronicas cualificadas (eIDAS). Nivel PAdES-LTA para validez a largo plazo (>10 anos). Flujos multi-firmante secuenciales y paralelos.
- **AI Triaje + Presupuestador**: Agentes de IA (Gemini 2.0 Flash con Strict Grounding) que clasifican consultas entrantes por urgencia/categoria, derivan al profesional adecuado y generan presupuestos automaticos basados en el catalogo real de servicios.
- **Copilot RAG profesional**: Asistente de IA con busqueda vectorial (Qdrant) sobre documentos del caso que responde con citas verificables, sugiere acciones ejecutables y prepara reuniones.

### 1.2 Relacion con la infraestructura existente

ServiciosConecta se construye sobre la infraestructura consolidada del ecosistema:

- **ecosistema_jaraba_core**: Entidades base (Tenant, Vertical, SaasPlan, Feature), servicios compartidos (TenantManager, PlanValidator, FinOpsTrackingService), Stripe Connect, sistema de permisos RBAC multi-tenant.
- **ecosistema_jaraba_theme**: Tema unificado con Federated Design Tokens, parciales Twig reutilizables (_header, _footer, _copilot-fab), slide-panel singleton, premium cards con glassmorphism.
- **jaraba_rag**: Pipeline RAG con Qdrant, embeddings, grounding validator. Se reutiliza para el Copilot de Servicios (doc 93).
- **jaraba_journey**: Journey engine con definicion ya creada para ServiciosConecta (`ServiciosConectaJourneyDefinition.php`): journey Profesional (8 pasos) y journey Cliente Servicios (6 pasos).
- **jaraba_email**: Sistema de email marketing con generacion de asuntos/cuerpos por IA.
- **jaraba_geo**: Geolocalizacion y busqueda por proximidad reutilizable para buscar profesionales cercanos.
- **Reutilizacion de ComercioConecta (~70%)**: Patron de portales (Provider/Client), sistema de reviews, notificaciones, SEO Schema.org, estructura de dashboards.

### 1.3 Patron arquitectonico de referencia

- **Content Entities con Field UI + Views** para todos los datos de negocio (perfiles, reservas, documentos, facturas, resenas).
- **Frontend limpio sin regiones Drupal**: Templates Twig full-width con parciales reutilizables, sin page.content ni bloques heredados.
- **CRUD en slide-panel modal**: Todas las acciones de crear/editar/ver se abren en panel lateral sin abandonar la pagina actual.
- **Federated Design Tokens**: SCSS con variables inyectables via CSS Custom Properties configurables desde la UI de Drupal sin tocar codigo.
- **Body classes via hook_preprocess_html()**: Nunca attributes.addClass() en templates Twig para el body.
- **API REST versionada** bajo `/api/v1/servicios/` con autenticacion, rate limiting y HMAC en webhooks.
- **AI via @ai.provider**: Abstraccion del modulo AI de Drupal, nunca llamadas HTTP directas a APIs de IA.
- **Automaciones via hooks Drupal**: hook_entity_insert/update/delete, hook_cron, no ECA BPMN.
- **Textos siempre traducibles**: `$this->t()` en PHP, `{% trans %}` en Twig, `Drupal.t()` en JS.
- **Dart Sass moderno**: `color.adjust()` en lugar de `darken()`/`lighten()` deprecados.

### 1.4 Avatar principal: Elena

Elena Martinez Garcia representa al profesional liberal que necesita digitalizar su practica:

| Atributo | Descripcion |
|----------|-------------|
| **Nombre** | Elena Martinez Garcia |
| **Profesion** | Abogada especializada en derecho civil y familia |
| **Ubicacion** | Cabra (Cordoba) - 20.000 habitantes |
| **Edad** | 42 anos |
| **Situacion** | Despacho propio con 1 administrativa a media jornada |
| **Facturacion** | ~60.000 EUR/ano, quiere escalar sin contratar mas personal |
| **Pain Points** | No-shows (15%), gestion manual de documentos, firmas presenciales, tiempo en presupuestos |
| **Meta** | Reducir tareas administrativas un 50% y ampliar radio de accion a comarcas cercanas |

**Tipos de profesionales target:**

| Categoria | Profesiones | Schema.org Type |
|-----------|-------------|-----------------|
| Legal | Abogados, procuradores, notarios | `Attorney`, `LegalService` |
| Salud | Medicos, fisioterapeutas, psicologos, odontologos | `Physician`, `Dentist`, `MedicalBusiness` |
| Tecnico | Arquitectos, ingenieros, peritos | `ProfessionalService` |
| Financiero | Asesores fiscales, gestores, contables | `AccountingService`, `FinancialService` |
| Consultoria | Consultores, coaches, formadores | `ProfessionalService`, `EducationalOrganization` |
| Bienestar | Nutricionistas, entrenadores, terapeutas | `HealthAndBeautyBusiness` |

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

La siguiente tabla mapea cada especificacion tecnica de ServiciosConecta con su fase de implementacion, entidades principales y nivel de reutilizacion respecto a otros verticales del ecosistema.

| Doc # | Titulo Especificacion | Fase | Entidades Principales | Reutilizacion |
|-------|----------------------|------|----------------------|---------------|
| **82** | ServiciosConecta_Services_Core | Fase 1 | `provider_profile`, `service_offering`, `booking`, `availability_slot`, `availability_exception` | 70% (ComercioConecta Commerce Core -> adaptacion a modelo de servicios) |
| **83** | ServiciosConecta_Provider_Profile | Fase 1 | `provider_profile` (extension detallada de credenciales, SEO, geolocalizacion) | 85% (MerchantProfile -> ProviderProfile con credenciales profesionales) |
| **84** | ServiciosConecta_Service_Offerings | Fase 1 | `service_offering` (extension), `service_package`, `client_package` | 60% (ProductRetail -> ServiceOffering con modelo de precios diferente) |
| **85** | ServiciosConecta_Booking_Engine_Core | Fase 2 | `temporary_hold`, `reminder_schedule` | 0% (componente exclusivo: motor de reservas con anti-colision) |
| **86** | ServiciosConecta_Calendar_Sync | Fase 2 | `calendar_connection`, `synced_calendar`, `external_event_cache` | 0% (componente exclusivo: sincronizacion bidireccional Google/Outlook) |
| **87** | ServiciosConecta_Video_Conferencing | Fase 3 | `video_room`, `video_participant` | 0% (componente exclusivo: integracion Jitsi Meet con JWT) |
| **88** | ServiciosConecta_Buzon_Confianza | Fase 3 | `secure_document`, `document_access`, `document_audit_log` | 0% (componente exclusivo: custodia cifrada zero-knowledge) |
| **89** | ServiciosConecta_Firma_Digital_PAdES | Fase 3 | `signature_request`, `signature_signer`, `digital_signature` | 0% (componente exclusivo: AutoFirma + Cl@ve + PAdES-LTA) |
| **90** | ServiciosConecta_Portal_Cliente_Documental | Fase 4 | `client_case`, `document_request`, `document_delivery`, `case_activity` | 30% (Customer Portal de ComercioConecta como base de UX) |
| **91** | ServiciosConecta_AI_Triaje_Casos | Fase 5 | `client_inquiry`, `inquiry_triage` | 30% (patron de IA de jaraba_copilot_v2 + Strict Grounding) |
| **92** | ServiciosConecta_Presupuestador_Auto | Fase 5 | `service_catalog_item`, `quote`, `quote_line_item` | 30% (patron de IA + modelo de facturacion existente) |
| **93** | ServiciosConecta_Copilot_Servicios | Fase 6 | `copilot_conversation`, `copilot_message`, `document_embedding` | 60% (jaraba_rag pipeline completo reutilizable) |
| **94** | ServiciosConecta_Dashboard_Profesional | Fase 7 | `dashboard_config`, `provider_alert` | 50% (TenantDashboard de ecosistema_jaraba_core como patron) |
| **95** | ServiciosConecta_Dashboard_Admin | Fase 8 | `analytics_snapshot` | 50% (FinOpsDashboard como patron de metricas agregadas) |
| **96** | ServiciosConecta_Sistema_Facturacion | Fase 9 | `invoice`, `invoice_line`, `credit_note` | 40% (Stripe Connect existente + modelo FOC de jaraba_foc) |
| **97** | ServiciosConecta_Reviews_Ratings | Fase 10 | `review`, `review_request`, `provider_rating_summary` | 90% (ReviewAgro -> ReviewServicios con metricas de servicio) |
| **98** | ServiciosConecta_Notificaciones_Multicanal | Fase 10 | `notification`, `notification_preference`, `notification_template` | 95% (jaraba_email + patron de notificaciones existente) |
| **99** | ServiciosConecta_API_Integration_Guide | Fase 10 | Sin entidades propias (fachada publica) | 80% (patron API REST existente en /api/v1/) |

**Resumen de reutilizacion:**

| Categoria | Documentos | % Reutilizacion medio | Esfuerzo relativo |
|-----------|-----------|----------------------|-------------------|
| Componentes compartidos (portales, reviews, notificaciones) | 83, 90, 97, 98, 99 | ~80% | Bajo - adaptacion de labels y flujos |
| Componentes adaptados (core, dashboards, facturacion) | 82, 84, 94, 95, 96 | ~50% | Medio - logica especifica de servicios |
| Componentes exclusivos (booking, calendario, video, vault, firma) | 85, 86, 87, 88, 89 | 0% | Alto - desarrollo desde cero |
| Componentes de IA (triaje, presupuestador, copilot) | 91, 92, 93 | ~40% | Medio-Alto - prompts y logica especifica |

---

## 3. Cumplimiento de Directrices del Proyecto

Esta seccion documenta como ServiciosConecta cumple con cada directriz obligatoria del proyecto, segun `docs/00_DIRECTRICES_PROYECTO.md` v5.8.0 y los workflows definidos en `.agent/workflows/`.

### 3.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `.agent/workflows/i18n-traducciones.md`

Todo texto visible al usuario DEBE ser traducible. No se admite ningun string hardcodeado en la interfaz.

| Contexto | Metodo | Ejemplo ServiciosConecta |
|----------|--------|--------------------------|
| PHP (Controllers, Services) | `$this->t('Texto')` | `$this->t('Reserve a consultation')` |
| PHP (Entities, fuera de clase con DI) | `new TranslatableMarkup('Texto')` | `new TranslatableMarkup('Provider Profile')` |
| Twig templates | `{% trans %}Texto{% endtrans %}` | `{% trans %}Upcoming appointments{% endtrans %}` |
| Twig con variables | `{{ 'Texto @var'|t({'@var': value}) }}` | `{{ 'Booking #@num confirmed'|t({'@num': booking.number}) }}` |
| JavaScript | `Drupal.t('Texto')` | `Drupal.t('Slot not available')` |
| Annotations de entidad | `@Translation('Texto')` | `@Translation('Service Offering')` |
| YAML (menus, permisos) | Texto directo (Drupal traduce) | `title: 'Manage service bookings'` |
| Formularios | `'#title' => $this->t('Texto')` | `'#title' => $this->t('Cancellation policy')` |
| Mensajes de estado | `$this->messenger()->addStatus($this->t('Texto'))` | `$this->messenger()->addStatus($this->t('Booking confirmed successfully.'))` |

**Reglas criticas:**
- Los annotations `@Translation()` reciben el texto en ingles; la traduccion al espanol se gestiona via `/admin/config/regional/translate`.
- Los labels de entidad usan `label`, `label_collection`, `label_singular`, `label_plural` todos con `@Translation()`.
- Los textos de botones, placeholders y mensajes de error son SIEMPRE traducibles.

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

ServiciosConecta como modulo satelite NUNCA define variables `$ej-*`. Solo consume CSS Custom Properties con fallbacks inline.

| Capa | Nombre | Ubicacion | Que define ServiciosConecta |
|------|--------|-----------|----------------------------|
| 1 | SCSS Tokens (SSOT) | `ecosistema_jaraba_core/scss/_variables.scss` | Nada (solo lectura) |
| 2 | CSS Custom Properties | `ecosistema_jaraba_core/scss/_injectable.scss` | Nada (solo lectura) |
| 3 | Componente local | `jaraba_servicios_conecta/scss/_variables-servicios.scss` | Variables locales `$servicios-*` como fallback |
| 4 | Tenant Override | `hook_preprocess_html()` del tema | Nada (gestionado por ecosistema_jaraba_core) |
| 5 | Vertical Preset | Config Entity de vertical | Color primario del vertical ServiciosConecta |

**Ejemplo CORRECTO:**
```scss
// jaraba_servicios_conecta/scss/_variables-servicios.scss
// Variables locales del vertical - SOLO como fallback para var(--ej-*)
$servicios-primary: #4A90D9;    // Azul Confianza
$servicios-secondary: #2D6A4F;  // Verde Profesional
$servicios-cream: #F0F4FF;      // Fondo suave azulado

// USO en parciales SCSS del modulo:
.servicios-card {
  background: var(--ej-bg-surface, #{$servicios-cream});
  color: var(--ej-text-primary, #1A1A2E);
  border-left: 4px solid var(--ej-color-primary, #{$servicios-primary});
}
```

**Ejemplo PROHIBIDO:**
```scss
// NUNCA hacer esto en un modulo satelite
$ej-color-primary: #4A90D9;  // PROHIBIDO: redefine token SSOT
```

**Mixin obligatorio** (de `ecosistema_jaraba_core/scss/_mixins.scss`):
```scss
@mixin css-var($property, $var-name, $fallback) {
  #{$property}: var(--ej-#{$var-name}, $fallback);
}
```

### 3.3 Directriz: Dart Sass moderno

**Referencia:** `.agent/workflows/scss-estilos.md`

Toda funcion de manipulacion de color debe usar la sintaxis moderna de Dart Sass.

**CORRECTO:**
```scss
@use 'sass:color';

.servicios-btn--hover {
  background: color.adjust($servicios-primary, $lightness: -10%);
}

.servicios-card--elevated {
  box-shadow: 0 4px 12px color.change($servicios-primary, $alpha: 0.15);
}
```

**PROHIBIDO (funciones deprecadas):**
```scss
// NUNCA usar:
background: darken($servicios-primary, 10%);   // DEPRECADO
background: lighten($servicios-primary, 10%);  // DEPRECADO
border-color: saturate($color, 20%);           // DEPRECADO
```

**Compilacion:**
```bash
# Dentro del contenedor Docker:
lando ssh -c "cd /app/web/modules/custom/jaraba_servicios_conecta && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"
lando drush cr
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Todas las paginas frontend de ServiciosConecta usan templates Twig limpias que renderizan el HTML completo sin `{{ page.content }}`, sin bloques heredados, sin sidebars, y con layout full-width pensado para movil.

**Estructura obligatoria de cada pagina frontend:**

```twig
{# page--servicios-marketplace.html.twig #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('jaraba_servicios_conecta/servicios.marketplace') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium page-wrapper--servicios">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name,
    'site_slogan': site_slogan,
    'logo': logo,
    'logged_in': logged_in
  } only %}

  <main class="main-content main-content--full main-content--servicios" role="main">
    <div class="main-content__inner main-content__inner--full">
      {# Contenido especifico de la pagina - SIN page.content #}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name
  } only %}

  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' only %}
</div>
```

**Reglas:**
- **Layout full-width**: `max-width: 1400px` centrado con `margin: 0 auto`, padding responsive.
- **Mobile-first**: Todas las media queries usan `min-width` (breakpoint-up).
- **Sin sidebar de admin** para tenants: El tenant NO tiene acceso al tema de administracion de Drupal. Solo el usuario administrador de plataforma ve la toolbar.
- **Parciales reutilizables**: `_header.html.twig`, `_footer.html.twig` y `_copilot-fab.html.twig` se incluyen siempre desde el tema, NUNCA se duplican en el modulo.
- **Configuracion del tema**: Los parciales (_header, _footer) usan variables configurables desde la UI de Drupal (logo, site_name, enlaces del footer, redes sociales), de modo que NO hay que tocar codigo para cambiar contenido del header/footer.

### 3.5 Directriz: Body classes via hook_preprocess_html

**Referencia:** `.agent/workflows/frontend-page-pattern.md`, directriz critica del proyecto.

Las clases anadidas con `attributes.addClass()` en templates Twig **NO funcionan para el body** porque Drupal renderiza `<body>` en `html.html.twig`, no en `page.html.twig`. Se DEBE usar `hook_preprocess_html()`.

```php
/**
 * Implements hook_preprocess_html().
 *
 * Inyecta clases CSS al <body> segun la ruta activa de ServiciosConecta.
 * DIRECTRIZ: NUNCA usar attributes.addClass() en templates Twig para body.
 * Drupal renderiza <body> en html.html.twig, no en page.html.twig,
 * por lo que las clases anadidas en page--*.html.twig no llegan al body.
 */
function jaraba_servicios_conecta_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName();

  // Mapa de rutas de ServiciosConecta a clases CSS del body.
  // Cada clase permite aplicar estilos globales (fondo, tipografia, layout)
  // especificos de cada seccion del vertical.
  $routeClasses = [
    'jaraba_servicios_conecta.marketplace' => 'page--servicios-marketplace',
    'jaraba_servicios_conecta.provider_detail' => 'page--servicios-provider',
    'jaraba_servicios_conecta.booking_page' => 'page--servicios-booking',
    'jaraba_servicios_conecta.provider_dashboard' => 'page--servicios-dashboard',
    'jaraba_servicios_conecta.client_portal' => 'page--servicios-client-portal',
    'jaraba_servicios_conecta.vault' => 'page--servicios-vault',
    'jaraba_servicios_conecta.admin_dashboard' => 'page--servicios-admin',
  ];

  if (isset($routeClasses[$route_name])) {
    $variables['attributes']['class'][] = $routeClasses[$route_name];
    // Clase generica del vertical para estilos compartidos.
    $variables['attributes']['class'][] = 'page--servicios';
    // Clase para layout limpio sin regiones de Drupal.
    $variables['attributes']['class'][] = 'page--clean-layout';
  }
}
```

### 3.6 Directriz: CRUD en modales slide-panel

**Referencia:** `.agent/workflows/slide-panel-modales.md`

Todas las acciones de crear, editar y ver en paginas frontend DEBEN abrirse en un slide-panel modal para que el usuario no abandone la pagina en la que esta trabajando.

**Patron HTML (data attributes, sin JS adicional):**
```html
<!-- Boton que abre el slide-panel de creacion -->
<button class="btn btn--primary"
        data-slide-panel="create-service"
        data-slide-panel-url="/servicios/service-offering/add?ajax=1"
        data-slide-panel-title="{{ 'Create service'|t }}"
        data-slide-panel-size="--large">
  {{ jaraba_icon('actions', 'add', { size: '18px' }) }}
  {% trans %}New service{% endtrans %}
</button>

<!-- Boton que abre el slide-panel de edicion -->
<button class="btn btn--outline"
        data-slide-panel="edit-service-{{ service.id }}"
        data-slide-panel-url="/servicios/service-offering/{{ service.id }}/edit?ajax=1"
        data-slide-panel-title="{{ 'Edit service'|t }}">
  {{ jaraba_icon('actions', 'edit', { size: '16px' }) }}
</button>
```

**Patron PHP en Controller (deteccion AJAX):**
```php
/**
 * Renderiza el formulario de creacion de servicio.
 *
 * Si la peticion es AJAX (slide-panel), devuelve solo el HTML del formulario
 * sin el layout completo de pagina. Esto permite que el slide-panel
 * cargue unicamente el contenido del formulario.
 */
public function addForm(Request $request): Response|array {
  $form = $this->formBuilder()->getForm(ServiceOfferingForm::class);

  // Si es AJAX (peticion desde slide-panel), devolver solo el HTML del form.
  if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($form);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  // Si es navegacion directa, devolver render array completo.
  return $form;
}
```

**Tamanos de panel disponibles:** `--small` (360px), `--medium` (480px), `--large` (600px), `--full` (100%)

**Dependencia de libreria:**
```yaml
dependencies:
  - ecosistema_jaraba_theme/slide-panel
```

### 3.7 Directriz: Entidades con Field UI y Views

**Referencia:** `.agent/workflows/drupal-custom-modules.md`, `docs/00_DIRECTRICES_PROYECTO.md`

Todas las entidades de negocio de ServiciosConecta DEBEN ser Content Entities que soporten Field UI y Views para garantizar plena integracion con la estructura de navegacion y administracion de Drupal.

**Checklist obligatorio por entidad:**

- `@ContentEntityType` annotation completa con `id`, `label`, `label_collection`, `label_singular`, `label_plural`
- Handlers: `list_builder`, `views_data` (`Drupal\views\EntityViewsData`), `form` (default/add/edit/delete), `access`, `route_provider` (`AdminHtmlRouteProvider`)
- `field_ui_base_route` apuntando a la ruta de settings en `/admin/structure/`
- Links: `canonical`, `add-form`, `edit-form`, `delete-form`, `collection`
- Collection en `/admin/content/servicios-{entity}` (pestaña en Content)
- Settings en `/admin/structure/servicios-{entity}` (enlace en Structure)
- Navegacion admin: 4 archivos YAML:
  - `.routing.yml`: Ruta de settings + rutas de entidad
  - `.links.menu.yml`: Enlace en Structure (`parent: system.admin_structure`)
  - `.links.task.yml`: Pestaña en Content (`base_route: system.admin_content`)
  - `.links.action.yml`: Boton "Agregar" en la coleccion

**Patron de entity_keys:**
```php
entity_keys = {
  "id" = "id",
  "uuid" = "uuid",
  "label" = "title",  // o "name" segun la entidad
  "owner" = "uid",
}
```

**Despliegue de entidades nuevas:** Siempre usar `drush entity:updates` (no `drush updb`, que NO instala tablas de entidades nuevas).

### 3.8 Directriz: No hardcodear configuracion

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Toda configuracion de negocio DEBE ser editable desde la UI de Drupal. No se admiten valores hardcodeados.

| Dato | Mecanismo | Ejemplo ServiciosConecta |
|------|-----------|--------------------------|
| Categorias de profesion | Taxonomia `profession` | Legal, Salud, Tecnico, Financiero, Consultoria, Bienestar |
| Especialidades | Taxonomias jerarquicas por profesion | specialty_legal, specialty_health, etc. |
| Politicas de cancelacion | Config Entity o campos de entidad | Flexible (24h), Moderada (48h), Estricta (72h) |
| Textos de emails/notificaciones | Templates Twig almacenados como Config Entity | `notification_template` con variables Twig |
| Comision de plataforma | Campo de `tenant_services` Group Type | Configurable por tenant via UI |
| Duracion de sesiones | Campos de `service_offering` | 30, 45, 60, 90, 120 minutos |
| Buffer entre citas | Campo de `provider_profile` | 15 min por defecto, configurable |
| Dias de antelacion | Campo de `provider_profile` | 60 dias por defecto, configurable |
| Tarifa del profesional | Campo de `service_offering` | Editable por el profesional via UI |
| Textos del footer | Configuracion del tema Drupal | Editables en `/admin/appearance/settings/ecosistema_jaraba_theme` |
| Logo y nombre del sitio | Configuracion basica de Drupal | `system.site` |

**Patron de fallback con `?:` (falsy coalesce):**
```php
// CORRECTO: Usa ?: porque Drupal config puede devolver string vacio ''.
$commission = $this->config('jaraba_servicios_conecta.settings')
  ->get('platform_commission') ?: 10;

// INCORRECTO: ?? solo comprueba null, no string vacio.
$commission = $this->config('jaraba_servicios_conecta.settings')
  ->get('platform_commission') ?? 10;
```

### 3.9 Directriz: Parciales Twig reutilizables

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Antes de extender el codigo de una pagina, verificar si ya existe un parcial reutilizable o si se necesita crear uno nuevo para reutilizar en otras paginas.

**Parciales existentes que ServiciosConecta DEBE reutilizar (NO duplicar):**

| Parcial | Ubicacion | Uso en ServiciosConecta |
|---------|-----------|------------------------|
| `_header.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Header de todas las paginas frontend |
| `_footer.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Footer de todas las paginas frontend |
| `_copilot-fab.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | FAB del copilot IA en todas las paginas |
| `_hero.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Hero section de landing pages |
| `_stats.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Estadisticas en dashboards |
| `_article-card.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Tarjetas de contenido generico |

**Parciales NUEVOS que ServiciosConecta debe crear (en el modulo, no en el tema):**

| Parcial | Ubicacion | Reutilizado en |
|---------|-----------|----------------|
| `_provider-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Marketplace, busqueda, dashboard admin |
| `_service-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Catalogo, perfil del profesional |
| `_booking-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard profesional, portal cliente |
| `_case-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard profesional, dashboard admin |
| `_document-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Buzon de confianza, portal cliente |
| `_quote-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard profesional, portal cliente |
| `_review-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Perfil profesional, dashboard |
| `_invoice-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard profesional, portal cliente |
| `_alert-card.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard profesional, dashboard admin |
| `_activity-timeline.html.twig` | `jaraba_servicios_conecta/templates/partials/` | Dashboard, portal cliente, detalle caso |

**Convenio de inclusion:**
```twig
{# Incluir parcial del modulo con namespace #}
{% include '@jaraba_servicios_conecta/partials/_provider-card.html.twig' with {
  'provider': provider,
  'show_rating': true,
  'show_actions': logged_in
} only %}
```

**Configuracion del tema para parciales:** Los parciales que muestran contenido editable (footer links, redes sociales, textos legales) DEBEN obtener sus valores de la configuracion del tema de Drupal (`/admin/appearance/settings/ecosistema_jaraba_theme`), de modo que el administrador pueda cambiarlos sin tocar codigo.

### 3.10 Directriz: Seguridad

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

- Rate limiting obligatorio en todos los endpoints de LLM/embedding: 100 req/hora RAG, 50 req/hora Copilot.
- Prompt sanitization contra whitelist para todos los datos interpolados en system prompts.
- Circuit breaker para proveedores de LLM: saltar proveedor 5 min tras 5 fallos consecutivos.
- API keys en variables de entorno (`.env`), NUNCA en configuracion exportable de Drupal.
- Aislamiento de tenant en Qdrant con filtro `must` (AND), NUNCA `should` (OR) para `tenant_id`.
- Verificacion HMAC obligatoria en todos los webhooks personalizados (Stripe, Google Calendar, Microsoft Graph).
- Todos los endpoints `/api/v1/*` requieren autenticacion.
- Cifrado AES-256-GCM del lado del cliente para el Buzon de Confianza (zero-knowledge).
- Validacion de certificados digitales contra CRL/OCSP para firma PAdES.
- Tokens de acceso para portales de cliente con TTL limitado y single-use opcional.

### 3.11 Directriz: Comentarios de codigo

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Los comentarios deben cubrir tres dimensiones en espanol:

1. **Estructura**: Organizacion, relaciones entre componentes, patrones usados, jerarquias.
2. **Logica**: Proposito (por que), flujo de ejecucion, reglas de negocio, decisiones de diseno, edge cases.
3. **Sintaxis**: Parametros (tipo + proposito), valores de retorno, excepciones, estructuras de datos complejas.

**Anti-patrones prohibidos:** Comentarios que repiten el codigo, comentarios vagos ("hace cosas"), comentarios desactualizados.

**Idioma:** Comentarios en espanol. Variables y funciones en ingles.

### 3.12 Directriz: Iconos SVG duotone

**Referencia:** `.agent/workflows/scss-estilos.md`

Cada icono nuevo DEBE crearse en dos versiones:

1. `{name}.svg` - Version outline (trazo)
2. `{name}-duotone.svg` - Version duotone (2 tonos con opacidad)

**Directorio de iconos ServiciosConecta:** `ecosistema_jaraba_core/images/icons/services/`

**Estructura del SVG duotone:**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa de fondo (opacidad 0.3) -->
  <path d="..." fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (trazo o relleno solido) -->
  <path d="..." stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Uso en Twig:**
```twig
{{ jaraba_icon('services', 'calendar-booking', { color: 'corporate', size: '24px' }) }}
{{ jaraba_icon('services', 'vault-lock', { variant: 'duotone', color: 'impulse', size: '32px' }) }}
```

**Colores de marca disponibles:** `corporate` (#233D63), `impulse` (#FF8C42), `innovation` (#00A9A5), `agro` (#556B2F), `success`, `warning`, `danger`, `neutral`.

### 3.13 Directriz: AI via abstraccion @ai.provider

**Referencia:** `.agent/workflows/ai-integration.md`, `docs/00_DIRECTRICES_PROYECTO.md`

NUNCA implementar clientes HTTP directos a APIs de IA (OpenAI, Anthropic, Google). Siempre usar la abstraccion del modulo AI de Drupal.

```php
// CORRECTO: Usar la abstraccion @ai.provider de Drupal.
$provider = \Drupal::service('ai.provider');
$response = $provider->chat([
  new ChatMessage('system', $systemPrompt),
  new ChatMessage('user', $userMessage),
], 'gemini-2.0-flash', ['temperature' => 0.1]);

// PROHIBIDO: Llamada HTTP directa.
$client = new \GuzzleHttp\Client();
$response = $client->post('https://api.openai.com/v1/chat/completions', [...]);
```

**Especializacion de proveedor IA por funcion en ServiciosConecta:**

| Funcion | Proveedor | Razon |
|---------|-----------|-------|
| Triaje de casos (doc 91) | Gemini 2.0 Flash | Strict Grounding, JSON schema, temperatura 0.1 |
| Presupuestador (doc 92) | Gemini 2.0 Flash | Strict Grounding contra catalogo real, temperatura 0.2 |
| Copilot RAG (doc 93) | Gemini 2.0 Flash | Respuestas con citas, integracion con Qdrant |
| Deteccion de modo (router) | Claude Haiku | Barato y rapido para clasificacion de intent |

### 3.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `.agent/workflows/drupal-eca-hooks.md`

Las automaciones del vertical se implementan via hooks nativos de Drupal, NO via ECA BPMN.

```php
/**
 * Implements hook_entity_insert().
 *
 * Cuando se crea una reserva (booking), envia notificacion de confirmacion
 * al cliente y al profesional, y crea un evento en el calendario externo
 * si el profesional tiene sincronizacion activa.
 */
function jaraba_servicios_conecta_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() !== 'booking') {
    return;
  }
  // Logica de notificacion y sincronizacion de calendario.
  \Drupal::service('jaraba_servicios_conecta.booking_notification')
    ->sendConfirmation($entity);
  \Drupal::service('jaraba_servicios_conecta.calendar_sync')
    ->pushBookingToExternal($entity);
}
```

**Hooks a implementar en ServiciosConecta:**

| Hook | Entidad | Accion |
|------|---------|--------|
| `hook_entity_insert` | `booking` | Enviar confirmacion, crear evento calendario, programar recordatorios |
| `hook_entity_update` | `booking` | Detectar cambios de estado (confirmada, cancelada, completada) |
| `hook_entity_insert` | `secure_document` | Indexar en Qdrant para RAG del Copilot |
| `hook_entity_delete` | `secure_document` | Eliminar vectores de Qdrant |
| `hook_entity_insert` | `client_inquiry` | Disparar triaje automatico de IA |
| `hook_entity_update` | `quote` | Detectar aceptacion -> crear caso + factura |
| `hook_entity_update` | `client_case` | Detectar cierre -> solicitar review |
| `hook_cron` | Varios | Limpiar holds expirados, enviar recordatorios, renovar webhooks calendario, snapshots analytics |

---

## 4. Arquitectura del Modulo

### 4.1 Nombre y ubicacion

```
web/modules/custom/jaraba_servicios_conecta/
```

### 4.2 Dependencias

```yaml
# jaraba_servicios_conecta.info.yml
name: 'Jaraba ServiciosConecta'
type: module
description: 'Vertical ServiciosConecta: Plataforma de Confianza Digital para Profesionales. Motor de reservas, buzon de confianza cifrado, firma digital PAdES, triaje IA, presupuestador automatico y copilot RAG para abogados, medicos, arquitectos, consultores y otros profesionales.'
package: 'Jaraba Verticals'
core_version_requirement: ^11
dependencies:
  - drupal:user
  - drupal:file
  - drupal:views
  - drupal:field_ui
  - drupal:taxonomy
  - drupal:datetime
  - drupal:link
  - ecosistema_jaraba_core:ecosistema_jaraba_core
```

> **Nota:** Las dependencias externas (Stripe PHP SDK, Google Calendar API, Microsoft Graph, Jitsi) se gestionan via Composer a nivel de proyecto, no como dependencias del modulo Drupal. El modulo AI de Drupal (`ai`) se inyecta via servicio `@ai.provider`, no como dependencia directa del info.yml.

### 4.3 Estructura de directorios

```
jaraba_servicios_conecta/
├── jaraba_servicios_conecta.info.yml
├── jaraba_servicios_conecta.module
├── jaraba_servicios_conecta.routing.yml
├── jaraba_servicios_conecta.services.yml
├── jaraba_servicios_conecta.libraries.yml
├── jaraba_servicios_conecta.permissions.yml
├── jaraba_servicios_conecta.install
├── jaraba_servicios_conecta.links.menu.yml
├── jaraba_servicios_conecta.links.task.yml
├── jaraba_servicios_conecta.links.action.yml
├── config/
│   ├── install/
│   │   └── taxonomy.vocabulary.*.yml          # Vocabularios de taxonomia
│   └── schema/
│       └── jaraba_servicios_conecta.schema.yml # Schema de configuracion
├── css/
│   └── servicios-conecta.css                   # CSS compilado (NO editar)
├── js/
│   ├── servicios-marketplace.js                # Filtros y busqueda marketplace
│   ├── servicios-booking.js                    # Motor de reservas frontend
│   ├── servicios-calendar.js                   # Selector de slots
│   ├── servicios-vault.js                      # Cliente de cifrado Web Crypto
│   ├── servicios-autofirma.js                  # Cliente AutoFirma protocolo
│   ├── servicios-video.js                      # Cliente Jitsi IFrame API
│   ├── servicios-dashboard.js                  # Graficas y widgets
│   └── servicios-copilot.js                    # Chat del copilot
├── package.json
├── scss/
│   ├── main.scss                               # Punto de entrada SCSS
│   ├── _variables-servicios.scss               # Variables locales del vertical
│   ├── _marketplace.scss                       # Estilos del marketplace
│   ├── _provider-profile.scss                  # Perfil del profesional
│   ├── _service-catalog.scss                   # Catalogo de servicios
│   ├── _booking.scss                           # Motor de reservas
│   ├── _calendar.scss                          # Selector de calendario
│   ├── _video-room.scss                        # Sala de videoconferencia
│   ├── _vault.scss                             # Buzon de confianza
│   ├── _signature.scss                         # Firma digital
│   ├── _client-portal.scss                     # Portal del cliente
│   ├── _dashboard-professional.scss            # Dashboard profesional
│   ├── _dashboard-admin.scss                   # Dashboard admin
│   ├── _invoicing.scss                         # Facturacion
│   ├── _reviews.scss                           # Resenas
│   └── _copilot.scss                           # Copilot de servicios
├── src/
│   ├── Access/
│   │   ├── ProviderProfileAccessControlHandler.php
│   │   ├── ServiceOfferingAccessControlHandler.php
│   │   ├── BookingAccessControlHandler.php
│   │   ├── SecureDocumentAccessControlHandler.php
│   │   ├── SignatureRequestAccessControlHandler.php
│   │   ├── ClientCaseAccessControlHandler.php
│   │   ├── ClientInquiryAccessControlHandler.php
│   │   ├── QuoteAccessControlHandler.php
│   │   ├── InvoiceAccessControlHandler.php
│   │   └── ReviewAccessControlHandler.php
│   ├── Controller/
│   │   ├── MarketplaceController.php           # Marketplace frontend
│   │   ├── ProviderController.php              # Perfil publico del profesional
│   │   ├── BookingController.php               # Pagina de reserva
│   │   ├── ProviderDashboardController.php     # Dashboard profesional
│   │   ├── ClientPortalController.php          # Portal del cliente
│   │   ├── VaultController.php                 # Buzon de confianza
│   │   ├── SignatureController.php             # Firma digital
│   │   ├── AdminDashboardController.php        # Dashboard admin
│   │   ├── CopilotController.php               # Chat copilot
│   │   ├── ProviderApiController.php           # API REST proveedores
│   │   ├── BookingApiController.php            # API REST reservas
│   │   ├── ServiceApiController.php            # API REST servicios
│   │   ├── VaultApiController.php              # API REST documentos
│   │   ├── SignatureApiController.php          # API REST firmas
│   │   ├── InquiryApiController.php            # API REST consultas
│   │   ├── QuoteApiController.php              # API REST presupuestos
│   │   ├── InvoiceApiController.php            # API REST facturas
│   │   ├── CalendarWebhookController.php       # Webhooks Google/Microsoft
│   │   └── StripeWebhookController.php         # Webhooks Stripe
│   ├── Entity/
│   │   ├── ProviderProfile.php                 # Perfil del profesional
│   │   ├── ServiceOffering.php                 # Oferta de servicio
│   │   ├── ServicePackage.php                  # Bono/paquete de sesiones
│   │   ├── ClientPackage.php                   # Paquete comprado por cliente
│   │   ├── AvailabilitySlot.php                # Slot de disponibilidad semanal
│   │   ├── AvailabilityException.php           # Excepcion de disponibilidad
│   │   ├── Booking.php                         # Reserva de cita
│   │   ├── TemporaryHold.php                   # Reserva temporal (5min TTL)
│   │   ├── ReminderSchedule.php                # Recordatorio programado
│   │   ├── CalendarConnection.php              # Conexion OAuth calendario
│   │   ├── SyncedCalendar.php                  # Calendario sincronizado
│   │   ├── ExternalEventCache.php              # Cache de eventos externos
│   │   ├── VideoRoom.php                       # Sala de videoconferencia
│   │   ├── VideoParticipant.php                # Participante en videollamada
│   │   ├── SecureDocument.php                  # Documento cifrado
│   │   ├── DocumentAccess.php                  # Acceso compartido a documento
│   │   ├── DocumentAuditLog.php                # Log de auditoria inmutable
│   │   ├── SignatureRequest.php                # Solicitud de firma multi-firmante
│   │   ├── SignatureSigner.php                 # Firmante individual
│   │   ├── DigitalSignature.php                # Firma digital aplicada
│   │   ├── ClientCase.php                      # Caso/expediente
│   │   ├── DocumentRequest.php                 # Solicitud de documento al cliente
│   │   ├── DocumentDelivery.php                # Entrega de documento al cliente
│   │   ├── CaseActivity.php                    # Actividad del caso (timeline)
│   │   ├── ClientInquiry.php                   # Consulta entrante
│   │   ├── InquiryTriage.php                   # Resultado de triaje IA
│   │   ├── ServiceCatalogItem.php              # Item del catalogo de precios
│   │   ├── Quote.php                           # Presupuesto
│   │   ├── QuoteLineItem.php                   # Linea de presupuesto
│   │   ├── CopilotConversation.php             # Conversacion del copilot
│   │   ├── CopilotMessage.php                  # Mensaje del copilot
│   │   ├── DocumentEmbedding.php               # Metadatos de embedding Qdrant
│   │   ├── DashboardConfig.php                 # Configuracion de widgets dashboard
│   │   ├── ProviderAlert.php                   # Alerta del profesional
│   │   ├── AnalyticsSnapshot.php               # Snapshot diario de metricas
│   │   ├── Invoice.php                         # Factura
│   │   ├── InvoiceLine.php                     # Linea de factura
│   │   ├── CreditNote.php                      # Nota de credito/rectificativa
│   │   ├── Review.php                          # Resena del cliente
│   │   ├── ReviewRequest.php                   # Solicitud de resena
│   │   ├── ProviderRatingSummary.php           # Resumen agregado de ratings
│   │   ├── Notification.php                    # Notificacion enviada
│   │   ├── NotificationPreference.php          # Preferencias de canal
│   │   └── NotificationTemplate.php            # Plantilla de notificacion
│   ├── Form/
│   │   ├── ProviderProfileForm.php
│   │   ├── ProviderProfileSettingsForm.php     # Field UI settings
│   │   ├── ServiceOfferingForm.php
│   │   ├── ServiceOfferingSettingsForm.php
│   │   ├── BookingForm.php
│   │   ├── BookingSettingsForm.php
│   │   ├── AvailabilitySlotForm.php
│   │   ├── SecureDocumentForm.php
│   │   ├── SignatureRequestForm.php
│   │   ├── ClientCaseForm.php
│   │   ├── QuoteForm.php
│   │   ├── InvoiceForm.php
│   │   ├── ReviewForm.php
│   │   ├── ServiciosSettingsForm.php            # Configuracion general del vertical
│   │   └── CalendarConnectionForm.php
│   ├── ListBuilder/
│   │   ├── ProviderProfileListBuilder.php
│   │   ├── ServiceOfferingListBuilder.php
│   │   ├── BookingListBuilder.php
│   │   ├── SecureDocumentListBuilder.php
│   │   ├── SignatureRequestListBuilder.php
│   │   ├── ClientCaseListBuilder.php
│   │   ├── ClientInquiryListBuilder.php
│   │   ├── QuoteListBuilder.php
│   │   ├── InvoiceListBuilder.php
│   │   └── ReviewListBuilder.php
│   └── Service/
│       ├── ProviderService.php                 # CRUD perfiles, verificacion, busqueda
│       ├── ServiceOfferingService.php          # CRUD servicios, paquetes, precios
│       ├── AvailabilityService.php             # Calculo de slots, excepciones
│       ├── BookingService.php                  # Ciclo de vida completo de reservas
│       ├── TemporaryHoldService.php            # Reservas temporales anti-colision
│       ├── ReminderService.php                 # Programacion y envio de recordatorios
│       ├── CalendarSyncService.php             # Orquestacion de sincronizacion
│       ├── GoogleCalendarAdapter.php           # Adaptador Google Calendar API v3
│       ├── MicrosoftGraphAdapter.php           # Adaptador Microsoft Graph API
│       ├── VideoRoomService.php                # Gestion de salas Jitsi
│       ├── DocumentVaultService.php            # Custodia de documentos cifrados
│       ├── DocumentAccessService.php           # Permisos de acceso compartido
│       ├── AuditLogService.php                 # Log inmutable con hash chain
│       ├── AutoFirmaAdapter.php                # Integracion protocolo afirma://
│       ├── ClaveAdapter.php                    # Integracion SAML Cl@ve Firma
│       ├── TimestampService.php                # Sellado de tiempo RFC 3161
│       ├── SignatureService.php                # Orquestacion de firmas PAdES
│       ├── ClientCaseService.php               # Gestion de expedientes
│       ├── DocumentRequestService.php          # Solicitudes de documentos
│       ├── DocumentDeliveryService.php         # Entregas de documentos
│       ├── TriageService.php                   # Triaje IA con Strict Grounding
│       ├── QuoteEstimatorService.php           # Presupuestador automatico IA
│       ├── CopilotService.php                  # Copilot RAG con citas
│       ├── RetrievalService.php                # Busqueda vectorial en Qdrant
│       ├── DashboardService.php                # Agregacion datos dashboard
│       ├── ProviderMetricsService.php          # Calculo de metricas profesional
│       ├── AdminDashboardService.php           # Dashboard admin con snapshots
│       ├── AnalyticsSnapshotService.php        # Generacion nocturna snapshots
│       ├── InvoiceService.php                  # Facturacion y ciclo de cobro
│       ├── StripeInvoiceService.php            # Integracion Stripe Invoicing
│       ├── ReviewService.php                   # Resenas y ratings
│       ├── ReviewRequestService.php            # Solicitudes automaticas de resena
│       ├── NotificationService.php             # Orquestacion multicanal
│       └── SchemaOrgService.php                # Generacion JSON-LD Schema.org
├── templates/
│   ├── servicios-marketplace.html.twig
│   ├── servicios-provider-profile.html.twig
│   ├── servicios-booking-page.html.twig
│   ├── servicios-provider-dashboard.html.twig
│   ├── servicios-client-portal.html.twig
│   ├── servicios-vault.html.twig
│   ├── servicios-video-room.html.twig
│   ├── servicios-signature-page.html.twig
│   ├── servicios-admin-dashboard.html.twig
│   ├── servicios-copilot.html.twig
│   └── partials/
│       ├── _provider-card.html.twig
│       ├── _service-card.html.twig
│       ├── _booking-card.html.twig
│       ├── _case-card.html.twig
│       ├── _document-card.html.twig
│       ├── _quote-card.html.twig
│       ├── _review-card.html.twig
│       ├── _invoice-card.html.twig
│       ├── _alert-card.html.twig
│       ├── _activity-timeline.html.twig
│       ├── _availability-calendar.html.twig
│       └── _stats-widget.html.twig
└── tests/
    └── src/
        ├── Functional/
        └── Kernel/
```

### 4.4 Compilacion SCSS

**package.json:**
```json
{
  "name": "jaraba-servicios-conecta",
  "version": "1.0.0",
  "description": "SCSS del vertical ServiciosConecta - Plataforma de Confianza Digital",
  "scripts": {
    "build": "sass scss/main.scss:css/servicios-conecta.css --style=compressed --no-source-map",
    "build:all": "npm run build",
    "watch": "sass --watch scss/main.scss:css/servicios-conecta.css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.71.0"
  }
}
```

**Comandos de compilacion:**
```bash
# Instalar dependencias (primera vez):
lando ssh -c "cd /app/web/modules/custom/jaraba_servicios_conecta && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm install"

# Compilar SCSS:
lando ssh -c "cd /app/web/modules/custom/jaraba_servicios_conecta && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"

# Limpiar cache de Drupal:
lando drush cr
```

---

## 5. Estado por Fases

| Fase | Descripcion | Docs Tecnicos | Estado | Entidades | Dependencia |
|------|-------------|---------------|--------|-----------|-------------|
| Fase 1 | Services Core + Provider Profile + Service Offerings | 82, 83, 84 | 🔶 **Planificada** | 8 | ecosistema_jaraba_core |
| Fase 2 | Booking Engine + Calendar Sync | 85, 86 | ⬜ Futura | 6 | Fase 1 |
| Fase 3 | Video Conferencing + Buzon Confianza + Firma Digital | 87, 88, 89 | ⬜ Futura | 8 | Fase 2 |
| Fase 4 | Portal Cliente Documental | 90 | ⬜ Futura | 4 | Fase 3 |
| Fase 5 | AI Triaje de Casos + Presupuestador Auto | 91, 92 | ⬜ Futura | 5 | Fase 1, jaraba_rag |
| Fase 6 | Copilot de Servicios | 93 | ⬜ Futura | 3 | Fase 3, Fase 5, jaraba_rag |
| Fase 7 | Dashboard Profesional | 94 | ⬜ Futura | 2 | Fases 1-6 |
| Fase 8 | Dashboard Admin + Analytics | 95 | ⬜ Futura | 1 | Fase 7 |
| Fase 9 | Sistema de Facturacion | 96 | ⬜ Futura | 3 | Fase 5, Stripe Connect |
| Fase 10 | Reviews + Notificaciones + API Publica | 97, 98, 99 | ⬜ Futura | 6 | Fases 1-9 |

**Total entidades:** 46 Content Entities
**Total fases:** 10
**Estimacion global:** 48 semanas (12 meses)

---

## 6. FASE 1: Services Core + Provider Profile + Service Offerings

### 6.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Fundamento del vertical: sin perfiles profesionales ni catalogo de servicios no existe marketplace. Habilita el registro de profesionales y la publicacion de servicios. |
| **Dependencias externas** | Solo `ecosistema_jaraba_core` (existente) y Stripe Connect (existente) |
| **Entidades** | 8 (`provider_profile`, `service_offering`, `service_package`, `client_package`, `booking`, `availability_slot`, `availability_exception`, mas taxonomias) |
| **Complejidad** | 🟡 Media (mucho codigo pero patrones establecidos de AgroConecta/ComercioConecta) |
| **Referencia AgroConecta** | ProducerProfile -> ProviderProfile (70%), ProductAgro -> ServiceOffering (60%) |
| **Docs tecnicos** | 82 (Services Core), 83 (Provider Profile), 84 (Service Offerings) |

### 6.2 Entidades

#### 6.2.1 Entidad `provider_profile`

**Tipo:** ContentEntity
**ID:** `provider_profile`
**Base table:** `provider_profile`
**Descripcion:** Perfil profesional completo con credenciales verificables, especialidades, configuracion de agenda, area de servicio geografica, SEO Schema.org y vinculacion a Stripe Connect. Es la identidad digital del profesional en la plataforma.

##### Annotation y Handlers

| Handler | Clase |
|---------|-------|
| `list_builder` | `Drupal\jaraba_servicios_conecta\ListBuilder\ProviderProfileListBuilder` |
| `views_data` | `Drupal\views\EntityViewsData` |
| `form.default/add/edit` | `Drupal\jaraba_servicios_conecta\Form\ProviderProfileForm` |
| `form.delete` | `Drupal\Core\Entity\ContentEntityDeleteForm` |
| `access` | `Drupal\jaraba_servicios_conecta\Access\ProviderProfileAccessControlHandler` |
| `route_provider.html` | `Drupal\Core\Entity\Routing\AdminHtmlRouteProvider` |

##### Campos (28)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `uid` | entity_reference (user) | ✅ | Usuario Drupal propietario del perfil |
| `tenant_id` | entity_reference (tenant) | ✅ | Tenant al que pertenece el profesional |
| `professional_title` | string(255) | ✅ | Titulo profesional (ej: "Abogada civilista") |
| `category_id` | entity_reference (taxonomy: profession) | ✅ | Categoria principal: Legal, Salud, Tecnico, etc. |
| `specialties` | map (JSON) | ❌ | Lista de especialidades con nivel: `[{id, name, level}]` |
| `bio` | text_long | ✅ | Descripcion profesional para el perfil publico |
| `photo` | image | ❌ | Foto de perfil del profesional |
| `credentials` | map (JSON) | ❌ | Credenciales verificables: `{college_registration, professional_license, liability_insurance, digital_certificate}` |
| `is_verified` | boolean | ✅ | Si las credenciales han sido verificadas. Default: false |
| `verified_at` | datetime | ❌ | Fecha de ultima verificacion |
| `rate_range` | map (JSON) | ❌ | Rango de tarifas: `{min, max, currency}` |
| `consultation_fee` | decimal(10,2) | ❌ | Tarifa de primera consulta (puede ser 0 = gratuita) |
| `service_area` | map (JSON) | ❌ | Area de servicio: `{lat, lng, radius_km, zones: []}` |
| `languages` | map (JSON) | ❌ | Idiomas hablados: `["es", "en", "fr"]` |
| `education` | map (JSON) | ❌ | Formacion: `[{institution, degree, year}]` |
| `certifications` | map (JSON) | ❌ | Certificaciones: `[{name, issuer, year, expires}]` |
| `social_links` | map (JSON) | ❌ | Redes sociales: `{linkedin, website, twitter}` |
| `booking_buffer_mins` | integer | ✅ | Minutos de margen entre citas. Default: 15 |
| `advance_booking_days` | integer | ✅ | Dias maximos de antelacion para reservar. Default: 60 |
| `min_notice_hours` | integer | ✅ | Horas minimas de antelacion. Default: 24 |
| `cancellation_policy` | list_string | ✅ | Politica de cancelacion: flexible/moderate/strict. Default: moderate |
| `stripe_account_id` | string(255) | ❌ | ID de cuenta Stripe Connect Express |
| `slug` | string(255) | ✅ | Slug unico para URL publica. UNIQUE |
| `seo_metadata` | map (JSON) | ❌ | Metadatos SEO: `{title, description, schema_type}` |
| `status` | list_string | ✅ | Estado: draft/pending_verification/active/suspended/inactive. Default: draft |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

##### Navegacion Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `routing.yml` | `jaraba_servicios_conecta.provider_profile.settings` | `/admin/structure/servicios-providers` |
| `links.task.yml` | Tab "Profesionales Servicios" | `base_route: system.admin_content`, weight: 60 |
| `links.menu.yml` | Structure "Profesionales Servicios" | `parent: system.admin_structure`, weight: 80 |
| `links.action.yml` | Boton "Agregar profesional" | `appears_on: entity.provider_profile.collection` |

##### Permisos

```yaml
manage servicios providers:
  title: 'Gestionar profesionales de ServiciosConecta'
  description: 'Crear, editar y eliminar perfiles profesionales'
  restrict access: true

view servicios providers:
  title: 'Ver profesionales de ServiciosConecta'
  description: 'Ver perfiles profesionales publicados'

edit own servicios provider:
  title: 'Editar propio perfil profesional'
  description: 'El profesional puede editar su propio perfil'
```

---

#### 6.2.2 Entidad `service_offering`

**Tipo:** ContentEntity
**ID:** `service_offering`
**Base table:** `service_offering`
**Descripcion:** Define un servicio individual que un profesional ofrece. Soporta 6 tipos de servicio (consulta, servicio completo, por hora, paquete/bono, presupuesto, suscripcion) con modelos de precios variables (fijo, por hora, rango, presupuesto, gratuito) y calculo de impuestos.

##### Annotation y Handlers

| Handler | Clase |
|---------|-------|
| `list_builder` | `Drupal\jaraba_servicios_conecta\ListBuilder\ServiceOfferingListBuilder` |
| `views_data` | `Drupal\views\EntityViewsData` |
| `form.default/add/edit` | `Drupal\jaraba_servicios_conecta\Form\ServiceOfferingForm` |
| `form.delete` | `Drupal\Core\Entity\ContentEntityDeleteForm` |
| `access` | `Drupal\jaraba_servicios_conecta\Access\ServiceOfferingAccessControlHandler` |
| `route_provider.html` | `Drupal\Core\Entity\Routing\AdminHtmlRouteProvider` |

##### Campos (22)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional que ofrece el servicio |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `title` | string(255) | ✅ | Nombre del servicio (ej: "Consulta inicial de derecho de familia") |
| `description` | text_long | ✅ | Descripcion detallada del servicio |
| `category_id` | entity_reference (taxonomy: service_category) | ✅ | Categoria del servicio |
| `service_type` | list_string | ✅ | Tipo: consultation/full_service/hourly/package/quote_based/subscription |
| `modality` | list_string | ✅ | Modalidad: presencial/online/hybrid/domicilio |
| `duration_minutes` | integer | ✅ | Duracion en minutos (30, 45, 60, 90, 120) |
| `duration_range` | map (JSON) | ❌ | Rango si es variable: `{min: 30, max: 120}` |
| `price_type` | list_string | ✅ | Tipo de precio: fixed/hourly/range/quote/free |
| `price` | decimal(10,2) | ❌ | Precio fijo (si price_type = fixed) |
| `price_range` | map (JSON) | ❌ | Rango de precios: `{min, max}` |
| `tax_rate` | decimal(5,2) | ✅ | Tipo de IVA aplicable. Default: 21.00 |
| `requires_prepayment` | boolean | ✅ | Si requiere pago anticipado. Default: false |
| `prepayment_amount` | decimal(10,2) | ❌ | Cantidad de pago anticipado |
| `what_includes` | map (JSON) | ❌ | Lo que incluye: `["Analisis inicial", "Informe escrito"]` |
| `what_to_bring` | map (JSON) | ❌ | Lo que el cliente debe traer: `["DNI", "Documentacion del caso"]` |
| `faqs` | map (JSON) | ❌ | Preguntas frecuentes: `[{question, answer}]` |
| `slug` | string(255) | ✅ | Slug unico por proveedor. UNIQUE compuesto (provider_id + slug) |
| `display_order` | integer | ✅ | Orden de visualizacion. Default: 0 |
| `status` | list_string | ✅ | Estado: draft/active/paused/archived. Default: draft |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

##### Navegacion Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `routing.yml` | `jaraba_servicios_conecta.service_offering.settings` | `/admin/structure/servicios-offerings` |
| `links.task.yml` | Tab "Servicios Ofertados" | `base_route: system.admin_content`, weight: 61 |
| `links.menu.yml` | Structure "Servicios Ofertados" | `parent: system.admin_structure`, weight: 81 |
| `links.action.yml` | Boton "Agregar servicio" | `appears_on: entity.service_offering.collection` |

##### Permisos

```yaml
manage servicios offerings:
  title: 'Gestionar ofertas de servicio'
  restrict access: true

view servicios offerings:
  title: 'Ver ofertas de servicio'

edit own servicios offerings:
  title: 'Editar propias ofertas de servicio'
```

---

#### 6.2.3 Entidad `service_package`

**Tipo:** ContentEntity
**ID:** `service_package`
**Base table:** `service_package`
**Descripcion:** Paquete o bono de sesiones que un profesional puede vender. Permite comprar N sesiones con descuento y periodo de validez. Ej: "Bono 5 sesiones de fisioterapia - 20% descuento".

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `service_id` | entity_reference (service_offering) | ✅ | FK al servicio base del paquete |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `title` | string(255) | ✅ | Nombre del paquete (ej: "Bono 5 sesiones") |
| `sessions_included` | integer | ✅ | Numero de sesiones incluidas |
| `price` | decimal(10,2) | ✅ | Precio total del paquete |
| `savings_percent` | decimal(5,2) | ❌ | Porcentaje de ahorro respecto a precio individual |
| `validity_days` | integer | ✅ | Dias de validez desde la compra. Default: 90 |
| `description` | text_long | ❌ | Descripcion del paquete |
| `status` | list_string | ✅ | Estado: active/paused/archived. Default: active |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

##### Navegacion Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `links.task.yml` | Tab "Paquetes Servicios" | `base_route: system.admin_content`, weight: 62 |

---

#### 6.2.4 Entidad `client_package`

**Tipo:** ContentEntity
**ID:** `client_package`
**Base table:** `client_package`
**Descripcion:** Registro del paquete comprado por un cliente. Trackea sesiones usadas, restantes y fecha de caducidad. Se decrementa automaticamente al confirmar una reserva asociada.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `package_id` | entity_reference (service_package) | ✅ | FK al paquete original comprado |
| `client_uid` | entity_reference (user) | ✅ | FK al usuario cliente que compro el paquete |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `sessions_total` | integer | ✅ | Total de sesiones del paquete |
| `sessions_used` | integer | ✅ | Sesiones consumidas. Default: 0 |
| `sessions_remaining` | integer | ✅ | Sesiones restantes (calculado) |
| `purchase_date` | datetime | ✅ | Fecha de compra |
| `expiry_date` | datetime | ✅ | Fecha de caducidad |
| `stripe_payment_id` | string(255) | ❌ | ID del PaymentIntent de Stripe |
| `status` | list_string | ✅ | Estado: active/exhausted/expired/refunded. Default: active |
| `created` | created | ✅ | Fecha de creacion |

---

#### 6.2.5 Entidad `booking`

**Tipo:** ContentEntity
**ID:** `booking`
**Base table:** `booking`
**Descripcion:** Entidad transaccional central del vertical. Representa una reserva de cita entre un cliente y un profesional. Contiene numero de reserva unico (SVC-XXXXX), slots de fecha/hora, modalidad, tracking de pago Stripe, URLs de reunion para videollamada, y un ciclo de vida con maquina de estados: pending -> confirmed -> in_progress -> completed.

##### Campos (28)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `booking_number` | string(20) | ✅ | Numero unico de reserva: SVC-XXXXX. UNIQUE |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `service_id` | entity_reference (service_offering) | ✅ | FK al servicio reservado |
| `client_uid` | entity_reference (user) | ✅ | FK al usuario cliente |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `booking_date` | datetime | ✅ | Fecha y hora de la cita |
| `end_time` | datetime | ✅ | Hora de fin (calculada: booking_date + duracion servicio) |
| `modality` | list_string | ✅ | Modalidad de la cita: presencial/online/domicilio |
| `location` | string(500) | ❌ | Direccion (si presencial/domicilio) |
| `meeting_url` | string(500) | ❌ | URL de la sala Jitsi (si online) |
| `client_notes` | text_long | ❌ | Notas del cliente al reservar |
| `provider_notes` | text_long | ❌ | Notas internas del profesional |
| `price_total` | decimal(10,2) | ✅ | Precio total de la cita (IVA incluido) |
| `price_paid` | decimal(10,2) | ✅ | Cantidad pagada. Default: 0 |
| `stripe_payment_intent_id` | string(255) | ❌ | ID del PaymentIntent de Stripe |
| `stripe_refund_id` | string(255) | ❌ | ID del Refund si se cancela con devolucion |
| `package_id` | entity_reference (client_package) | ❌ | FK al paquete si se usa sesion de bono |
| `cancellation_reason` | text_long | ❌ | Motivo de cancelacion |
| `cancelled_by` | list_string | ❌ | Quien cancelo: client/provider/system |
| `rescheduled_from` | entity_reference (booking) | ❌ | FK a la reserva original si es reprogramacion |
| `actual_start` | datetime | ❌ | Hora real de inicio (para seguimiento) |
| `actual_end` | datetime | ❌ | Hora real de fin |
| `status` | list_string | ✅ | Estado: pending/confirmed/in_progress/completed/cancelled/no_show/rescheduled. Default: pending |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

**Maquina de estados:**
- `pending` -> `confirmed` | `cancelled` | `failed`
- `confirmed` -> `in_progress` | `cancelled` | `rescheduled`
- `in_progress` -> `completed` | `no_show`
- `completed`, `cancelled`, `no_show`, `rescheduled` = estados finales

##### Navegacion Admin

| YAML | Clave | Path / Detalle |
|------|-------|----------------|
| `routing.yml` | `jaraba_servicios_conecta.booking.settings` | `/admin/structure/servicios-bookings` |
| `links.task.yml` | Tab "Reservas Servicios" | `base_route: system.admin_content`, weight: 63 |
| `links.menu.yml` | Structure "Reservas Servicios" | `parent: system.admin_structure`, weight: 82 |
| `links.action.yml` | Boton "Crear reserva" | `appears_on: entity.booking.collection` |

##### Permisos

```yaml
manage servicios bookings:
  title: 'Gestionar reservas de ServiciosConecta'
  restrict access: true

view own servicios bookings:
  title: 'Ver propias reservas (profesional o cliente)'

create servicios bookings:
  title: 'Crear reservas de servicio'
```

---

#### 6.2.6 Entidad `availability_slot`

**Tipo:** ContentEntity
**ID:** `availability_slot`
**Base table:** `availability_slot`
**Descripcion:** Slot de disponibilidad semanal recurrente. Define los horarios habituales del profesional (ej: "Lunes de 9:00 a 14:00, modalidad presencial"). Se repiten cada semana y se usan como base para calcular los slots disponibles para reserva.

##### Campos (12)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `day_of_week` | integer | ✅ | Dia de la semana: 1 (lunes) a 7 (domingo) |
| `start_time` | string(5) | ✅ | Hora de inicio: "09:00" (formato HH:MM) |
| `end_time` | string(5) | ✅ | Hora de fin: "14:00" (formato HH:MM) |
| `modality` | list_string | ✅ | Modalidad para este slot: presencial/online/both |
| `is_active` | boolean | ✅ | Si el slot esta activo. Default: true |
| `label` | string(255) | ❌ | Etiqueta opcional (ej: "Manana", "Tarde") |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

---

#### 6.2.7 Entidad `availability_exception`

**Tipo:** ContentEntity
**ID:** `availability_exception`
**Base table:** `availability_exception`
**Descripcion:** Excepcion puntual a la disponibilidad regular. Permite bloquear dias (vacaciones, festivos), marcar dias extra disponibles, o modificar horarios para una fecha concreta.

##### Campos (12)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `exception_date` | datetime | ✅ | Fecha de la excepcion |
| `exception_type` | list_string | ✅ | Tipo: blocked/available/modified |
| `start_time` | string(5) | ❌ | Hora inicio (si available o modified) |
| `end_time` | string(5) | ❌ | Hora fin (si available o modified) |
| `modality` | list_string | ❌ | Modalidad para la excepcion |
| `reason` | string(255) | ❌ | Motivo (ej: "Vacaciones", "Congreso") |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

---

### 6.3 Taxonomias

#### 6.3.1 Vocabulario `profession`

**Tipo:** Plano (no jerarquico)
**Proposito:** Categorias principales de profesion para clasificar a los profesionales.
**Campos adicionales:** `field_schema_org_type` (string) para mapeo Schema.org.

| Termino | Schema.org |
|---------|-----------|
| Legal | `Attorney`, `LegalService` |
| Salud | `Physician`, `MedicalBusiness` |
| Tecnico | `ProfessionalService` |
| Financiero | `AccountingService`, `FinancialService` |
| Consultoria | `ProfessionalService`, `EducationalOrganization` |
| Bienestar | `HealthAndBeautyBusiness` |

#### 6.3.2 Vocabularios de especialidad (jerarquicos)

Se crean vocabularios jerarquicos por profesion para una taxonomia de especialidades mas rica:

| Vocabulario | Ejemplo de terminos |
|-------------|---------------------|
| `specialty_legal` | Derecho civil, Derecho de familia, Derecho mercantil, Derecho laboral, Derecho penal |
| `specialty_health` | Medicina general, Fisioterapia, Psicologia, Odontologia, Nutricion |
| `specialty_technical` | Arquitectura, Ingenieria civil, Pericia judicial, Urbanismo |
| `specialty_financial` | Asesoria fiscal, Contabilidad, Gestion laboral, Auditoria |

#### 6.3.3 Vocabulario `service_category`

**Tipo:** Jerarquico
**Proposito:** Categorias para clasificar servicios ofertados.
**Campos adicionales:** `field_schema_org_type` (string), `field_icon_name` (string).

| Termino | Subterminos ejemplo |
|---------|---------------------|
| Consulta inicial | Primera visita, Diagnostico, Valoracion |
| Tramitacion | Escrituras, Licencias, Permisos |
| Representacion | Juicio, Mediacion, Arbitraje |
| Revision periodica | Checkup, Mantenimiento, Seguimiento |
| Formacion | Taller, Curso, Webinar |

#### 6.3.4 Vocabulario `service_modality`

**Tipo:** Plano
**Proposito:** Modalidades de prestacion del servicio.

| Termino | Descripcion |
|---------|-------------|
| Presencial | En el despacho/consulta del profesional |
| Online | Videollamada Jitsi Meet |
| Hibrido | El cliente elige al reservar |
| A domicilio | El profesional se desplaza |

#### 6.3.5 Vocabulario `professional_college`

**Tipo:** Plano
**Proposito:** Colegios profesionales para verificacion de colegiacion.

| Termino | Descripcion |
|---------|-------------|
| ICAM | Colegio de Abogados de Madrid |
| ICAC | Colegio de Abogados de Cordoba |
| COM | Colegio Oficial de Medicos |
| COAM | Colegio de Arquitectos de Madrid |
| (Configurable por administrador) | |

---

### 6.4 Services

#### 6.4.1 `ProviderService`

Gestiona el ciclo de vida completo de los perfiles profesionales: creacion con generacion automatica de slug, actualizacion de credenciales con cola de verificacion, busqueda por geolocalizacion y profesion, y calculo de completitud del perfil.

```
createProfile(userId, tenantId, data):
  -> Crea perfil profesional con slug auto-generado a partir de professional_title
  -> Valida unicidad del slug (si existe, anade sufijo numerico)
  -> Estado inicial: 'draft'
  -> Devuelve: ProviderProfile entity

updateCredentials(profileId, credentials):
  -> Actualiza credenciales (college_registration, license, insurance, certificate)
  -> Marca is_verified = false hasta verificacion manual/automatica
  -> Encola notificacion al admin para revision
  -> Devuelve: ProviderProfile entity actualizado

verifyCollegeRegistration(profileId, collegeId, registrationNumber):
  -> Verifica colegiacion contra API del colegio (si disponible)
  -> Si no hay API, marca como 'pending_manual_verification'
  -> Actualiza is_verified y verified_at
  -> Devuelve: bool

checkProfileCompleteness(profileId):
  -> Calcula % de completitud del perfil
  -> Campos obligatorios: title, category, bio, photo (ponderados)
  -> Requiere >= 70% para publicar perfil
  -> Devuelve: {percentage: int, missing_fields: string[], can_publish: bool}

searchProviders(filters):
  -> Busqueda con filtros: category, specialty, location (lat/lng/radius), languages
  -> Join con rating_summary para ordenar por valoracion
  -> Solo perfiles con status = 'active'
  -> Soporta paginacion
  -> Devuelve: array de ProviderProfile
```

#### 6.4.2 `ServiceOfferingService`

Gestiona el catalogo de servicios de cada profesional: creacion con slug por proveedor, busqueda con filtros multiples, calculo de precios con impuestos y gestion de paquetes/bonos.

```
createService(providerId, data):
  -> Crea oferta de servicio con slug unico por proveedor
  -> Valida coherencia de campos segun service_type y price_type
  -> Calcula savings_percent para paquetes si aplica
  -> Devuelve: ServiceOffering entity

getBookableServices(providerId, modality, categoryId):
  -> Filtra servicios activos del proveedor por modalidad y categoria
  -> Ordena por display_order
  -> Devuelve: array de ServiceOffering

calculatePrice(serviceId, options):
  -> Calcula precio total segun tipo de precio:
  -> fixed: precio directo + IVA
  -> hourly: rate * horas estimadas + IVA
  -> range: devuelve min/max con IVA
  -> free/quote: devuelve 0 o null
  -> Aplica descuento de paquete si clientPackageId proporcionado
  -> Devuelve: {subtotal, tax, total, currency, discount_applied}

searchServices(filters):
  -> Busqueda con filtros: category, modality, max_price, location (geo join con provider)
  -> Solo servicios con status = 'active' de providers activos
  -> Devuelve: array de ServiceOffering con datos del provider
```

#### 6.4.3 `AvailabilityService`

Calcula los slots disponibles para reserva combinando la agenda semanal, excepciones, reservas existentes y (en Fase 2) eventos de calendario externo. Es el nucleo algoritmico del motor de reservas.

```
getWeeklySchedule(providerId):
  -> Devuelve los slots recurrentes semanales del profesional
  -> Agrupados por dia de la semana
  -> Devuelve: array de AvailabilitySlot por dia

getAvailableSlots(providerId, serviceId, dateFrom, dateTo):
  -> Algoritmo de 8 capas:
  -> 1. Cargar slots recurrentes semanales
  -> 2. Aplicar excepciones (bloqueos, disponibilidad extra, modificaciones)
  -> 3. Restar reservas existentes (confirmed + pending)
  -> 4. (Fase 2) Restar eventos Google Calendar
  -> 5. (Fase 2) Restar eventos Microsoft Outlook
  -> 6. Restar holds temporales activos (5 min TTL)
  -> 7. Dividir en slots de la duracion del servicio
  -> 8. Filtrar por min_notice_hours y advance_booking_days
  -> Devuelve: array de {date, start_time, end_time, modality}

addException(providerId, date, type, data):
  -> Crea excepcion de disponibilidad
  -> Tipo blocked: bloquea todo el dia (vacaciones, festivo)
  -> Tipo available: anade disponibilidad extra
  -> Tipo modified: cambia horario para ese dia
  -> Devuelve: AvailabilityException entity

detectConflicts(providerId, datetime, duration):
  -> Comprueba si hay conflicto con:
  -> Reservas existentes (incluyendo buffer)
  -> Excepciones de bloqueo
  -> Devuelve: {has_conflict: bool, conflicts: array}
```

---

### 6.5 Controllers

#### Controllers Frontend

| Ruta | Metodo | Descripcion |
|------|--------|-------------|
| `/servicios` | `MarketplaceController::marketplace` | Marketplace de profesionales: busqueda, filtros, tarjetas de profesional |
| `/servicios/profesional/{slug}` | `ProviderController::providerDetail` | Perfil publico del profesional con servicios, reviews, calendario |
| `/mi-servicio` | `ProviderDashboardController::dashboard` | Dashboard del profesional (Fase 7 completa, Fase 1 = pagina basica) |
| `/mi-servicio/perfil` | `ProviderController::editProfile` | Editar perfil profesional (slide-panel) |
| `/mi-servicio/servicios` | `ProviderController::manageServices` | Gestionar catalogo de servicios |
| `/mi-servicio/disponibilidad` | `ProviderController::manageAvailability` | Gestionar agenda semanal y excepciones |

#### Controllers API REST

| Metodo | Path | Permiso |
|--------|------|---------|
| GET | `/api/v1/servicios/providers` | `view servicios providers` |
| GET | `/api/v1/servicios/providers/{id}` | `view servicios providers` |
| POST | `/api/v1/servicios/providers` | `manage servicios providers` |
| PATCH | `/api/v1/servicios/providers/{id}` | `edit own servicios provider` |
| GET | `/api/v1/servicios/services` | `view servicios offerings` |
| GET | `/api/v1/servicios/services/{id}` | `view servicios offerings` |
| POST | `/api/v1/servicios/services` | `edit own servicios offerings` |
| GET | `/api/v1/servicios/providers/{id}/availability` | `view servicios providers` |
| GET | `/api/v1/servicios/categories` | `access content` |

---

### 6.6 Templates y Parciales Twig

#### 6.6.1 Template `servicios-marketplace.html.twig`

**Funcion:** Marketplace publico de profesionales con busqueda, filtros y tarjetas de proveedor.
**Estructura:**
```twig
{#
  Marketplace de ServiciosConecta.
  Muestra el directorio de profesionales con busqueda por categoria,
  especialidad, ubicacion y valoracion. Cada profesional se renderiza
  como una tarjeta con foto, titulo, especialidades, rating y boton de reserva.
#}

{# Seccion hero con buscador prominente #}
<section class="servicios-hero">
  <div class="servicios-hero__container">
    <h1>{% trans %}Find your trusted professional{% endtrans %}</h1>
    <p>{% trans %}Lawyers, doctors, architects, consultants and more near you{% endtrans %}</p>
    <div class="servicios-search">
      <input type="text"
             class="servicios-search__input"
             placeholder="{{ 'What do you need?'|t }}"
             data-servicios-search>
      <select class="servicios-search__category" data-servicios-category>
        <option value="">{% trans %}All categories{% endtrans %}</option>
        {% for category in categories %}
          <option value="{{ category.id }}">{{ category.name }}</option>
        {% endfor %}
      </select>
      <button class="btn btn--primary servicios-search__btn" data-servicios-submit>
        {{ jaraba_icon('actions', 'search', { size: '20px' }) }}
        {% trans %}Search{% endtrans %}
      </button>
    </div>
  </div>
</section>

{# Grid de categorias de profesion #}
<section class="servicios-categories">
  <div class="servicios-categories__grid">
    {% for category in categories %}
      {% include '@jaraba_servicios_conecta/partials/_category-chip.html.twig' with {
        'category': category
      } only %}
    {% endfor %}
  </div>
</section>

{# Grid de profesionales destacados #}
<section class="servicios-providers">
  <h2>{% trans %}Featured professionals{% endtrans %}</h2>
  <div class="servicios-providers__grid">
    {% for provider in providers %}
      {% include '@jaraba_servicios_conecta/partials/_provider-card.html.twig' with {
        'provider': provider,
        'show_rating': true,
        'show_actions': true
      } only %}
    {% endfor %}
  </div>
</section>
```

**Variables del controller:**
- `categories`: Array de taxonomias de profesion con icono y conteo
- `providers`: Array de ProviderProfile activos con rating_summary
- `stats`: Contadores (total profesionales, total servicios, total reviews)

#### 6.6.2 Parcial `_provider-card.html.twig`

**Funcion:** Tarjeta de profesional reutilizable en marketplace, busqueda y dashboard admin.
**Estructura:**
```twig
{#
  Tarjeta de profesional.
  Muestra foto, titulo profesional, categoría, especialidades,
  valoracion media, precio desde y boton de accion.
  Reutilizable en: marketplace, resultados de busqueda, dashboard admin.

  Variables:
    - provider: Entidad ProviderProfile
    - show_rating: bool - Mostrar estrellas de valoracion
    - show_actions: bool - Mostrar botones de accion (reservar, ver perfil)
#}
<article class="provider-card" data-provider-id="{{ provider.id }}">
  <div class="provider-card__photo">
    {% if provider.photo %}
      <img src="{{ provider.photo }}" alt="{{ provider.professional_title }}" loading="lazy">
    {% else %}
      <div class="provider-card__photo-placeholder">
        {{ jaraba_icon('business', 'profile', { size: '48px', color: 'neutral' }) }}
      </div>
    {% endif %}
    {% if provider.is_verified %}
      <span class="provider-card__verified" title="{{ 'Verified professional'|t }}">
        {{ jaraba_icon('actions', 'verified', { size: '20px', color: 'success' }) }}
      </span>
    {% endif %}
  </div>
  <div class="provider-card__body">
    <h3 class="provider-card__title">{{ provider.professional_title }}</h3>
    <span class="provider-card__category">{{ provider.category_name }}</span>
    {% if show_rating and provider.rating_avg %}
      <div class="provider-card__rating">
        <span class="provider-card__stars" style="--rating: {{ provider.rating_avg }}"></span>
        <span class="provider-card__rating-count">({{ provider.review_count }})</span>
      </div>
    {% endif %}
    {% if provider.consultation_fee is not null %}
      <span class="provider-card__price">
        {% if provider.consultation_fee == 0 %}
          {% trans %}Free first consultation{% endtrans %}
        {% else %}
          {{ 'From @price EUR'|t({'@price': provider.consultation_fee}) }}
        {% endif %}
      </span>
    {% endif %}
  </div>
  {% if show_actions %}
    <div class="provider-card__actions">
      <a href="/servicios/profesional/{{ provider.slug }}" class="btn btn--outline btn--sm">
        {% trans %}View profile{% endtrans %}
      </a>
    </div>
  {% endif %}
</article>
```

---

### 6.7 Frontend Assets

```yaml
# jaraba_servicios_conecta.libraries.yml

servicios.frontend:
  css:
    theme:
      css/servicios-conecta.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global
    - ecosistema_jaraba_theme/slide-panel

servicios.marketplace:
  js:
    js/servicios-marketplace.js: {}
  dependencies:
    - jaraba_servicios_conecta/servicios.frontend
    - core/drupal
    - core/drupalSettings

servicios.provider-dashboard:
  js:
    js/servicios-dashboard.js: {}
  dependencies:
    - jaraba_servicios_conecta/servicios.frontend
    - core/drupal
```

---

### 6.8 Archivos a Crear

| Categoria | # | Archivos |
|-----------|---|----------|
| **Info/Config** | 8 | `info.yml`, `module`, `routing.yml`, `services.yml`, `libraries.yml`, `permissions.yml`, `install`, `links.menu.yml`, `links.task.yml`, `links.action.yml` |
| **Entities** | 7 | `ProviderProfile.php`, `ServiceOffering.php`, `ServicePackage.php`, `ClientPackage.php`, `Booking.php`, `AvailabilitySlot.php`, `AvailabilityException.php` |
| **ListBuilders** | 3 | `ProviderProfileListBuilder.php`, `ServiceOfferingListBuilder.php`, `BookingListBuilder.php` |
| **Forms** | 6 | `ProviderProfileForm.php`, `ProviderProfileSettingsForm.php`, `ServiceOfferingForm.php`, `ServiceOfferingSettingsForm.php`, `BookingForm.php`, `AvailabilitySlotForm.php` |
| **Access** | 3 | `ProviderProfileAccessControlHandler.php`, `ServiceOfferingAccessControlHandler.php`, `BookingAccessControlHandler.php` |
| **Services** | 3 | `ProviderService.php`, `ServiceOfferingService.php`, `AvailabilityService.php` |
| **Controllers** | 4 | `MarketplaceController.php`, `ProviderController.php`, `ProviderDashboardController.php`, `ServiceApiController.php` |
| **Templates** | 4 | `servicios-marketplace.html.twig`, `servicios-provider-profile.html.twig`, `partials/_provider-card.html.twig`, `partials/_service-card.html.twig` |
| **SCSS** | 5 | `main.scss`, `_variables-servicios.scss`, `_marketplace.scss`, `_provider-profile.scss`, `_service-catalog.scss` |
| **JS** | 1 | `servicios-marketplace.js` |
| **Build** | 1 | `package.json` |
| **Taxonomias** | 6 | YAML de vocabularios en `config/install/` |
| **Theme templates** | 2 | `page--servicios-marketplace.html.twig`, `page--servicios-provider.html.twig` |
| **Total** | **53** | |

### 6.9 Archivos a Modificar

| Archivo | Cambios |
|---------|---------|
| `ecosistema_jaraba_theme.theme` | +1 funcion `hook_theme_suggestions_page_alter()` para rutas de ServiciosConecta |
| `ecosistema_jaraba_core/scss/main.scss` | Importar nuevo parcial `_servicios-dashboard.scss` si se crea en core |
| Ningun otro archivo del core | Fase 1 es autocontenida en el modulo |

### 6.10 SCSS: Directrices

- **BEM naming**: `.servicios-*` como prefijo de bloque (ej: `.servicios-card`, `.servicios-hero`, `.servicios-search`)
- **Variables**: Solo `$servicios-*` locales como fallback. Usar siempre `var(--ej-*, #{$fallback})`.
- **Mobile-first**: Todas las media queries con `@include breakpoint-up(md)` etc.
- **Animaciones**: Transiciones suaves con `var(--ej-transition)`. Hover lift con `transform: translateY(-2px)` y `box-shadow`.
- **Cards**: Usar `@include card-base(true)` del mixin global para tarjetas con hover.
- **Botones**: Usar `@include button-primary` y `@include button-outline` de los mixins globales.
- **Responsive grid**: `@include grid-auto-fit(280px, 1.5rem)` para grids adaptativos.
- **Estrellas de rating**: CSS custom property `--rating` con `background: linear-gradient()` para renderizar puntuacion parcial sin JS.

### 6.11 Verificacion

#### Post-Creacion

1. `lando ssh -c "cd /app && composer dump-autoload -o"`
2. `lando drush en jaraba_servicios_conecta -y`
3. `lando drush entity:updates -y` (crear tablas de entidades)
4. `lando drush cr`
5. Verificar en `/admin/content` que aparecen las pestanas: Profesionales Servicios, Servicios Ofertados, Reservas Servicios
6. Verificar en `/admin/structure` que aparecen: Profesionales Servicios, Servicios Ofertados, Reservas Servicios
7. Verificar Field UI: `/admin/structure/servicios-providers/fields` muestra los campos
8. Compilar SCSS: `lando ssh -c "cd /app/web/modules/custom/jaraba_servicios_conecta && npm install && npm run build"`
9. `lando drush cr`
10. Verificar `/servicios` muestra el marketplace
11. Verificar `/servicios/profesional/{slug}` muestra el perfil

#### Funcional

- [ ] Crear un perfil profesional desde `/admin/content/servicios-providers/add`
- [ ] Verificar que el slug se genera automaticamente
- [ ] Crear un servicio asociado al perfil
- [ ] Verificar que el marketplace muestra el profesional activo
- [ ] Verificar que el perfil publico muestra los servicios
- [ ] Verificar que los filtros del marketplace funcionan (categoria, busqueda)
- [ ] Verificar que la pagina tiene layout full-width sin sidebars
- [ ] Verificar body class `page--servicios-marketplace` en el HTML
- [ ] Verificar que los textos son traducibles en `/admin/config/regional/translate`
- [ ] Verificar responsive en movil (Chrome DevTools)

---

## 7. FASE 2: Booking Engine + Calendar Sync

### 7.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Diferenciador competitivo clave: reserva de cita con pago anticipado, anti-colision, sincronizacion de calendario. Reduce no-shows del 15% al <3%. TTV objetivo <45 segundos. |
| **Dependencias externas** | Google Calendar API v3, Microsoft Graph API, Stripe Connect (existente) |
| **Entidades** | 6 (`temporary_hold`, `reminder_schedule`, `calendar_connection`, `synced_calendar`, `external_event_cache` + extension de `booking`) |
| **Complejidad** | 🔴 Alta (algoritmo de 8 capas, OAuth externo, webhooks bidireccionales, TTL holds) |
| **Referencia** | 0% reutilizacion (componente exclusivo de ServiciosConecta) |
| **Docs tecnicos** | 85 (Booking Engine Core), 86 (Calendar Sync) |

### 7.2 Entidades

#### 7.2.1 Entidad `temporary_hold`

**Tipo:** ContentEntity
**ID:** `temporary_hold`
**Base table:** `temporary_hold`
**Descripcion:** Reserva temporal de un slot durante el proceso de checkout. Tiene un TTL de 5 minutos para evitar que dos clientes reserven el mismo horario. Si el pago se completa, se convierte en booking; si expira, el slot vuelve a estar disponible. Soporta sesiones de invitado (sin cuenta de usuario) via session_id.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `service_id` | entity_reference (service_offering) | ✅ | FK al servicio |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `client_uid` | entity_reference (user) | ❌ | FK al usuario (null si invitado) |
| `session_id` | string(255) | ❌ | Session ID para invitados sin cuenta |
| `hold_date` | datetime | ✅ | Fecha y hora del slot reservado temporalmente |
| `hold_end` | datetime | ✅ | Fin del slot |
| `expires_at` | datetime | ✅ | Momento de expiracion del hold (created + 5 min) |
| `status` | list_string | ✅ | Estado: active/converted/expired/released. Default: active |
| `booking_id` | entity_reference (booking) | ❌ | FK al booking si se convirtio |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 7.2.2 Entidad `reminder_schedule`

**Tipo:** ContentEntity
**ID:** `reminder_schedule`
**Base table:** `reminder_schedule`
**Descripcion:** Programacion de recordatorios automaticos asociados a una reserva. Se crean automaticamente al confirmar una reserva con 5 tipos: confirmacion, recordatorio 24h, recordatorio 2h, recordatorio 15min y solicitud de review. Cada uno con su canal (email, SMS, push) y estado de envio.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `booking_id` | entity_reference (booking) | ✅ | FK a la reserva |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `reminder_type` | list_string | ✅ | Tipo: confirmation/reminder_24h/reminder_2h/reminder_15m/review |
| `scheduled_at` | datetime | ✅ | Momento programado de envio |
| `channels` | map (JSON) | ✅ | Canales: `["email", "sms", "push"]` |
| `recipient_uid` | entity_reference (user) | ✅ | FK al destinatario |
| `recipient_role` | list_string | ✅ | Rol: client/provider |
| `delivery_status` | list_string | ✅ | Estado: pending/sent/delivered/failed. Default: pending |
| `sent_at` | datetime | ❌ | Momento real de envio |
| `error_message` | string(500) | ❌ | Mensaje de error si fallo |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 7.2.3 Entidad `calendar_connection`

**Tipo:** ContentEntity
**ID:** `calendar_connection`
**Base table:** `calendar_connection`
**Descripcion:** Conexion OAuth de un profesional con un proveedor de calendario externo (Google Calendar o Microsoft Outlook). Almacena tokens de acceso/refresco cifrados, estado de sincronizacion y control de errores para degradacion graceful.

##### Campos (16)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `platform` | list_string | ✅ | Plataforma: google/microsoft |
| `email` | string(255) | ✅ | Email de la cuenta conectada |
| `access_token` | text_long | ✅ | Token de acceso cifrado (AES-256) |
| `refresh_token` | text_long | ✅ | Token de refresco cifrado |
| `token_expires_at` | datetime | ✅ | Momento de expiracion del access_token |
| `scopes` | map (JSON) | ✅ | Scopes OAuth concedidos |
| `sync_status` | list_string | ✅ | Estado: active/paused/error/disconnected. Default: active |
| `last_sync_at` | datetime | ❌ | Ultima sincronizacion exitosa |
| `error_count` | integer | ✅ | Contador de errores consecutivos. Default: 0 |
| `last_error` | text_long | ❌ | Ultimo error registrado |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 7.2.4 Entidad `synced_calendar`

**Tipo:** ContentEntity
**ID:** `synced_calendar`
**Base table:** `synced_calendar`
**Descripcion:** Calendario individual seleccionado para sincronizacion dentro de una conexion. Un profesional puede tener multiples calendarios (personal, trabajo) y elegir cuales sincronizar y en que direccion (solo lectura, solo escritura, bidireccional). El calendario marcado como primario es donde se crean los eventos de reserva.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `connection_id` | entity_reference (calendar_connection) | ✅ | FK a la conexion OAuth |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `external_calendar_id` | string(500) | ✅ | ID del calendario en la plataforma externa |
| `calendar_name` | string(255) | ✅ | Nombre legible del calendario |
| `sync_direction` | list_string | ✅ | Direccion: read/write/both. Default: read |
| `is_primary` | boolean | ✅ | Si es el calendario principal para escribir eventos. Default: false |
| `webhook_channel_id` | string(255) | ❌ | ID del canal de webhook (Google) o subscription (Microsoft) |
| `webhook_expires_at` | datetime | ❌ | Expiracion del webhook (7 dias Google, 3 dias Microsoft) |
| `sync_token` | string(500) | ❌ | Token incremental para sincronizacion delta |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 7.2.5 Entidad `external_event_cache`

**Tipo:** ContentEntity
**ID:** `external_event_cache`
**Base table:** `external_event_cache`
**Descripcion:** Cache local de eventos de calendarios externos. Solo almacena datos temporales (fecha/hora de inicio y fin, estado), NUNCA el titulo ni la descripcion del evento por privacidad. Se usa para calcular disponibilidad sin hacer llamadas en tiempo real a Google/Microsoft.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `calendar_id` | entity_reference (synced_calendar) | ✅ | FK al calendario sincronizado |
| `provider_id` | entity_reference (provider_profile) | ✅ | FK al profesional |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `external_event_id` | string(500) | ✅ | ID del evento en la plataforma externa |
| `event_start` | datetime | ✅ | Hora de inicio del evento |
| `event_end` | datetime | ✅ | Hora de fin del evento |
| `is_all_day` | boolean | ✅ | Si es evento de todo el dia. Default: false |
| `busy_status` | list_string | ✅ | Estado: busy/tentative/free. Default: busy |
| `etag` | string(255) | ❌ | ETag para deteccion de cambios |
| `synced_at` | datetime | ✅ | Ultima sincronizacion de este evento |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

---

### 7.3 Services

#### 7.3.1 `TemporaryHoldService`

Gestiona las reservas temporales (5 minutos de TTL) para evitar doble reserva durante el checkout. Crea holds atomicos, verifica expiracion, convierte a booking o libera automaticamente.

```
createHold(providerId, serviceId, datetime, clientUid, sessionId):
  -> Verifica que no existe otro hold activo para el mismo slot
  -> Verifica que no hay booking confirmado en ese horario
  -> Crea hold con expires_at = now() + 5 minutos
  -> Devuelve: TemporaryHold entity o error si slot ocupado

convertToBooking(holdId, paymentData):
  -> Verifica que el hold esta activo y no ha expirado
  -> Crea booking a partir del hold
  -> Marca hold como 'converted' con FK al booking
  -> Devuelve: Booking entity

cleanupExpiredHolds():
  -> Ejecutado por hook_cron cada minuto
  -> Busca holds con expires_at < now() y status = 'active'
  -> Los marca como 'expired'
  -> Devuelve: int (numero de holds limpiados)
```

#### 7.3.2 `BookingService`

Ciclo de vida completo de reservas: creacion con pago, confirmacion, cancelacion con politicas de reembolso, reprogramacion, completado, no-show. Integra Stripe para pagos y Jitsi para videollamadas.

```
createBooking(holdId, clientData, paymentIntentId):
  -> Convierte hold temporal en booking
  -> Genera booking_number unico: SVC-XXXXX
  -> Asocia PaymentIntent de Stripe
  -> Estado inicial: 'pending' (si requiere confirmacion manual) o 'confirmed'
  -> Programa recordatorios automaticos (5 tipos)
  -> Si online: crea sala Jitsi (Fase 3)
  -> Envia notificacion de confirmacion
  -> Devuelve: Booking entity

confirmBooking(bookingId):
  -> Transicion: pending -> confirmed
  -> Captura el pago en Stripe (si era authorize-only)
  -> Programa recordatorios
  -> Notifica al cliente
  -> Devuelve: Booking entity

cancelBooking(bookingId, cancelledBy, reason):
  -> Transicion: pending/confirmed -> cancelled
  -> Aplica politica de cancelacion del proveedor:
  -> Flexible (24h): 100% reembolso si >24h, 50% si <24h
  -> Moderate (48h): 100% si >48h, 50% si >24h, 0% si <24h
  -> Strict (72h): 100% si >72h, 50% si >48h, 0% si <48h
  -> Si cancelledBy = 'provider': siempre 100% reembolso al cliente
  -> Procesa refund en Stripe
  -> Cancela evento en calendario externo
  -> Cancela recordatorios pendientes
  -> Notifica a la otra parte
  -> Devuelve: Booking entity

rescheduleBooking(bookingId, newDatetime):
  -> Verifica disponibilidad del nuevo slot
  -> Crea nueva booking con rescheduled_from = bookingId original
  -> Marca booking original como 'rescheduled'
  -> Actualiza evento en calendario externo
  -> Reprograma recordatorios
  -> Devuelve: Booking entity (nueva)

completeBooking(bookingId):
  -> Transicion: in_progress -> completed
  -> Registra actual_end
  -> Decrementa sesion si usa paquete
  -> Programa solicitud de review (24h despues)
  -> Devuelve: Booking entity

markNoShow(bookingId):
  -> Transicion: confirmed/in_progress -> no_show
  -> No hay reembolso (el profesional ya reservo su tiempo)
  -> Registra en metricas del cliente
  -> Devuelve: Booking entity
```

#### 7.3.3 `ReminderService`

Programa y envia recordatorios automaticos multicanal (email, SMS, push) asociados a reservas.

```
scheduleReminders(bookingId):
  -> Calcula las 5 fechas de recordatorio:
  -> confirmation: inmediato
  -> reminder_24h: booking_date - 24h
  -> reminder_2h: booking_date - 2h
  -> reminder_15m: booking_date - 15min
  -> review: booking_date + 24h (si completada)
  -> Crea ReminderSchedule por cada uno con canales segun preferencias
  -> Devuelve: array de ReminderSchedule

processReminders():
  -> Ejecutado por hook_cron cada 5 minutos
  -> Busca recordatorios con scheduled_at <= now() y status = 'pending'
  -> Envia por cada canal configurado (email via jaraba_email, SMS via Twilio)
  -> Actualiza delivery_status y sent_at
  -> Devuelve: int (recordatorios procesados)
```

#### 7.3.4 `CalendarSyncService`

Orquesta la sincronizacion bidireccional con Google Calendar y Microsoft Outlook usando el patron Strategy (GoogleCalendarAdapter, MicrosoftGraphAdapter).

```
initiateOAuth(providerId, platform):
  -> Genera URL de autorizacion OAuth (Google o Microsoft)
  -> Almacena state token para CSRF
  -> Devuelve: string (authorization URL)

handleOAuthCallback(platform, code, state):
  -> Intercambia code por tokens de acceso/refresco
  -> Cifra tokens con AES-256
  -> Crea CalendarConnection
  -> Lista calendarios disponibles
  -> Devuelve: array de calendarios disponibles

syncFromExternal(connectionId):
  -> Usa syncToken para sincronizacion incremental (delta)
  -> Solo descarga cambios desde la ultima sincronizacion
  -> Actualiza external_event_cache (solo fechas, NO contenido)
  -> Maneja errores (401: refresh token, 429: backoff)
  -> Devuelve: {events_added, events_updated, events_deleted}

pushBookingToExternal(bookingId):
  -> Crea evento en el calendario primario del profesional
  -> Incluye: titulo generico, hora, duracion, cliente como asistente
  -> Almacena external_event_id en el booking
  -> Devuelve: string (external event ID)

deleteExternalEvent(bookingId):
  -> Elimina el evento del calendario externo (cancelacion)
  -> Devuelve: bool

renewWebhooks():
  -> Ejecutado por hook_cron cada 12h
  -> Renueva canales de webhook de Google (expiran en 7 dias)
  -> Renueva subscriptions de Microsoft (expiran en 3 dias)
  -> Devuelve: int (webhooks renovados)
```

---

### 7.4 Controllers

#### Controllers Frontend

| Ruta | Metodo | Descripcion |
|------|--------|-------------|
| `/servicios/reservar/{provider_slug}/{service_slug}` | `BookingController::bookingPage` | Pagina de reserva: calendario, slots, formulario de pago |
| `/mi-servicio/agenda` | `ProviderDashboardController::agenda` | Calendario del profesional con reservas |
| `/mi-servicio/calendario/conectar` | `CalendarController::connectCalendar` | Flujo OAuth para conectar Google/Outlook |

#### Controllers API REST y Webhooks

| Metodo | Path | Descripcion |
|--------|------|-------------|
| GET | `/api/v1/servicios/availability/{provider_id}` | Slots disponibles por rango de fechas |
| POST | `/api/v1/servicios/holds` | Crear hold temporal |
| DELETE | `/api/v1/servicios/holds/{id}` | Liberar hold |
| POST | `/api/v1/servicios/bookings` | Crear reserva (convertir hold) |
| PATCH | `/api/v1/servicios/bookings/{id}/confirm` | Confirmar reserva |
| PATCH | `/api/v1/servicios/bookings/{id}/cancel` | Cancelar reserva |
| PATCH | `/api/v1/servicios/bookings/{id}/reschedule` | Reprogramar reserva |
| GET | `/api/v1/servicios/bookings/{id}/ics` | Descargar ICS |
| GET | `/servicios/oauth/google/callback` | Callback OAuth Google |
| GET | `/servicios/oauth/microsoft/callback` | Callback OAuth Microsoft |
| POST | `/servicios/webhook/google-calendar` | Webhook Google Calendar |
| POST | `/servicios/webhook/microsoft-graph` | Webhook Microsoft Graph |

---

### 7.5 Templates y Parciales Twig

#### 7.5.1 Template `servicios-booking-page.html.twig`

**Funcion:** Pagina de reserva con selector de fecha/hora, resumen del servicio, datos del cliente y formulario de pago Stripe.
**Variables del controller:**
- `provider`: Entidad ProviderProfile
- `service`: Entidad ServiceOffering
- `available_dates`: Fechas disponibles (proximos N dias)

#### 7.5.2 Parciales nuevos

| Parcial | Funcion |
|---------|---------|
| `_booking-card.html.twig` | Tarjeta de reserva con estado, fecha, profesional, acciones |
| `_availability-calendar.html.twig` | Selector visual de calendario con slots disponibles |

---

### 7.6 Frontend Assets

```yaml
servicios.booking:
  js:
    js/servicios-booking.js: {}
    js/servicios-calendar.js: {}
  dependencies:
    - jaraba_servicios_conecta/servicios.frontend
    - core/drupal
    - core/drupalSettings
    - core/once
```

### 7.7 Archivos a Crear

| Categoria | # | Archivos |
|-----------|---|----------|
| **Entities** | 5 | `TemporaryHold.php`, `ReminderSchedule.php`, `CalendarConnection.php`, `SyncedCalendar.php`, `ExternalEventCache.php` |
| **Services** | 6 | `TemporaryHoldService.php`, `BookingService.php`, `ReminderService.php`, `CalendarSyncService.php`, `GoogleCalendarAdapter.php`, `MicrosoftGraphAdapter.php` |
| **Controllers** | 3 | `BookingController.php`, `BookingApiController.php`, `CalendarWebhookController.php` |
| **Forms** | 2 | `BookingSettingsForm.php`, `CalendarConnectionForm.php` |
| **Templates** | 3 | `servicios-booking-page.html.twig`, `partials/_booking-card.html.twig`, `partials/_availability-calendar.html.twig` |
| **SCSS** | 2 | `_booking.scss`, `_calendar.scss` |
| **JS** | 2 | `servicios-booking.js`, `servicios-calendar.js` |
| **Theme** | 1 | `page--servicios-booking.html.twig` |
| **Total** | **24** | |

### 7.8 Archivos a Modificar

| Archivo | Cambios |
|---------|---------|
| `jaraba_servicios_conecta.module` | +hooks hook_entity_insert (booking), hook_cron (holds, reminders, webhooks) |
| `jaraba_servicios_conecta.routing.yml` | +12 rutas (booking, calendar, webhooks) |
| `jaraba_servicios_conecta.services.yml` | +6 services |
| `jaraba_servicios_conecta.permissions.yml` | +3 permisos (bookings) |
| `jaraba_servicios_conecta.libraries.yml` | +1 library (servicios.booking) |

### 7.9 SCSS: Directrices

- Calendario interactivo: grid CSS con `grid-template-columns: repeat(7, 1fr)` para dias de la semana
- Slots disponibles: chips con `background: var(--ej-color-success)`, hover con `color.adjust($lightness: -10%)`
- Slots ocupados: chips con `background: var(--ej-gray-200)`, `cursor: not-allowed`
- Hold activo: animacion `pulse` suave para indicar countdown de 5 minutos
- Responsive: calendario se convierte en lista vertical en movil

### 7.10 Verificacion

- [ ] Crear slots de disponibilidad semanal para un profesional
- [ ] Verificar que `/servicios/reservar/{slug}/{service}` muestra el calendario
- [ ] Verificar que los slots ocupados no aparecen como disponibles
- [ ] Crear hold temporal y verificar que bloquea el slot
- [ ] Verificar que el hold expira automaticamente tras 5 minutos
- [ ] Crear reserva completa con numero SVC-XXXXX
- [ ] Verificar que el booking aparece en el dashboard del profesional
- [ ] Conectar Google Calendar via OAuth y verificar sincronizacion
- [ ] Verificar que eventos externos bloquean slots
- [ ] Verificar que la reserva crea evento en el calendario externo

---

## 8. FASE 3: Video Conferencing + Buzon de Confianza + Firma Digital

### 8.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Tres pilares de la "Confianza Digital": videollamada integrada, custodia cifrada zero-knowledge, firma electronica cualificada. Diferenciador unico en el mercado. |
| **Dependencias externas** | Jitsi Meet API, AutoFirma (FNMT), Cl@ve (gobierno), TSA (FNMT, Camerfirma) |
| **Entidades** | 8 (`video_room`, `video_participant`, `secure_document`, `document_access`, `document_audit_log`, `signature_request`, `signature_signer`, `digital_signature`) |
| **Complejidad** | 🔴 Alta (cifrado client-side, protocolo afirma://, PAdES-LTA, SAML Cl@ve, JWT Jitsi) |
| **Referencia** | 0% reutilizacion (3 componentes exclusivos) |
| **Docs tecnicos** | 87 (Video), 88 (Buzon Confianza), 89 (Firma Digital PAdES) |

### 8.2 Entidades

#### 8.2.1 Entidad `video_room`

**Tipo:** ContentEntity
**ID:** `video_room`
**Base table:** `video_room`
**Descripcion:** Sala de videoconferencia Jitsi creada automaticamente para reservas online. Nombre unico `jrb-{tenant}-{uuid_short}`, password generado, JWT separados para moderador (profesional) y participante (cliente), tracking de duracion real y soporte de grabacion con consentimiento.

##### Campos (18)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `booking_id` | entity_reference (booking) | ✅ | FK a la reserva asociada |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `room_name` | string(255) | ✅ | Nombre unico: `jrb-{tenant_slug}-{uuid_short}`. UNIQUE |
| `room_password` | string(100) | ✅ | Password generado para la sala |
| `provider_jwt` | text_long | ❌ | JWT del profesional (moderador) |
| `client_jwt` | text_long | ❌ | JWT del cliente (participante) |
| `provider_join_url` | string(1000) | ✅ | URL de acceso para el profesional |
| `client_join_url` | string(1000) | ✅ | URL de acceso para el cliente |
| `actual_start` | datetime | ❌ | Hora real de inicio de la videollamada |
| `actual_end` | datetime | ❌ | Hora real de finalizacion |
| `duration_minutes` | integer | ❌ | Duracion real en minutos (calculado) |
| `recording_consent` | map (JSON) | ❌ | Consentimiento: `{provider: bool, client: bool}` |
| `recording_url` | string(1000) | ❌ | URL de la grabacion si se grabo |
| `status` | list_string | ✅ | Estado: created/in_progress/ended. Default: created |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 8.2.2 Entidad `video_participant`

**Tipo:** ContentEntity
**ID:** `video_participant`
**Base table:** `video_participant`
**Descripcion:** Registro de asistencia de cada participante a una videollamada. Trackea tiempos de conexion/desconexion, duracion total y calidad de conexion. Util para facturacion por tiempo y resolucion de disputas.

##### Campos (12)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `room_id` | entity_reference (video_room) | ✅ | FK a la sala |
| `uid` | entity_reference (user) | ✅ | FK al usuario participante |
| `role` | list_string | ✅ | Rol: moderator/participant |
| `joined_at` | datetime | ✅ | Momento de conexion |
| `left_at` | datetime | ❌ | Momento de desconexion |
| `total_time_seconds` | integer | ❌ | Tiempo total conectado en segundos |
| `connection_quality` | list_string | ❌ | Calidad: good/moderate/poor |
| `created` | created | ✅ | Fecha de creacion |

#### 8.2.3 Entidad `secure_document`

**Tipo:** ContentEntity
**ID:** `secure_document`
**Base table:** `secure_document`
**Descripcion:** Documento custodiado en el Buzon de Confianza con cifrado end-to-end. El servidor solo almacena el archivo cifrado, el DEK (Data Encryption Key) cifrado con la clave maestra del usuario, y metadatos. NUNCA almacena contenido en texto plano. El descifrado ocurre exclusivamente en el navegador del usuario via Web Crypto API.

##### Campos (20)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `owner_uid` | entity_reference (user) | ✅ | FK al propietario del documento |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `case_id` | entity_reference (client_case) | ❌ | FK al caso (si esta asociado a un expediente) |
| `title` | string(255) | ✅ | Nombre del documento (cifrado en transito, almacenado en claro) |
| `file_encrypted` | file | ✅ | Archivo cifrado (AES-256-GCM) |
| `file_size` | integer | ✅ | Tamano del archivo original en bytes |
| `mime_type` | string(100) | ✅ | MIME type original del archivo |
| `encrypted_dek` | text_long | ✅ | DEK cifrado con la clave maestra del usuario (AES-256-KW) |
| `encryption_iv` | string(100) | ✅ | IV usado para el cifrado AES-256-GCM |
| `encryption_tag` | string(100) | ✅ | Authentication tag del cifrado GCM |
| `content_hash` | string(64) | ✅ | SHA-256 del contenido original (verificacion de integridad) |
| `version` | integer | ✅ | Numero de version. Default: 1 |
| `previous_version_id` | entity_reference (secure_document) | ❌ | FK a la version anterior |
| `signature_request_id` | entity_reference (signature_request) | ❌ | FK si tiene firma digital solicitada |
| `expires_at` | datetime | ❌ | Fecha de caducidad del documento |
| `status` | list_string | ✅ | Estado: active/archived/deleted. Default: active |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 8.2.4 Entidad `document_access`

**Tipo:** ContentEntity
**ID:** `document_access`
**Base table:** `document_access`
**Descripcion:** Registro de acceso compartido a un documento cifrado. Cada usuario con quien se comparte un documento recibe su propia copia del DEK, re-cifrada con la clave publica RSA-OAEP del destinatario. Soporta permisos granulares (ver, descargar, firmar), limite de descargas, caducidad y contrasena de acceso.

##### Campos (16)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `document_id` | entity_reference (secure_document) | ✅ | FK al documento |
| `grantor_uid` | entity_reference (user) | ✅ | FK al usuario que comparte |
| `grantee_uid` | entity_reference (user) | ❌ | FK al usuario destinatario (null si acceso por token) |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `encrypted_dek_for_grantee` | text_long | ✅ | DEK re-cifrado con RSA-OAEP del destinatario |
| `permissions` | map (JSON) | ✅ | Permisos: `{view: true, download: true, sign: false}` |
| `access_token` | string(255) | ❌ | Token para acceso sin cuenta (enlaces compartidos). UNIQUE |
| `access_password` | string(255) | ❌ | Hash de contrasena de acceso adicional |
| `max_downloads` | integer | ❌ | Limite de descargas. Null = ilimitado |
| `download_count` | integer | ✅ | Descargas realizadas. Default: 0 |
| `expires_at` | datetime | ❌ | Fecha de caducidad del acceso |
| `is_revoked` | boolean | ✅ | Si el acceso ha sido revocado. Default: false |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 8.2.5 Entidad `document_audit_log`

**Tipo:** ContentEntity
**ID:** `document_audit_log`
**Base table:** `document_audit_log`
**Descripcion:** Log de auditoria inmutable tipo blockchain. Cada registro contiene un hash encadenado (SHA-256 del hash anterior + datos actuales) que permite detectar cualquier manipulacion del historico. Registra todas las acciones sobre documentos: creacion, visualizacion, descarga, comparticion, firma, revocacion, eliminacion.

##### Campos (14)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `document_id` | entity_reference (secure_document) | ✅ | FK al documento |
| `actor_uid` | entity_reference (user) | ✅ | FK al usuario que realizo la accion |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `action` | list_string | ✅ | Tipo: created/viewed/downloaded/shared/signed/revoked/deleted |
| `details` | map (JSON) | ❌ | Detalles adicionales segun accion |
| `ip_address` | string(45) | ✅ | IP del actor (IPv4 o IPv6) |
| `user_agent` | string(500) | ❌ | User-Agent del navegador |
| `hash_chain` | string(64) | ✅ | SHA-256(previous_hash + current_data). Inmutable |
| `previous_hash` | string(64) | ❌ | Hash del registro anterior en la cadena. Null para el primero |
| `created` | created | ✅ | Fecha de creacion (inmutable) |

#### 8.2.6 Entidad `signature_request`

**Tipo:** ContentEntity
**ID:** `signature_request`
**Base table:** `signature_request`
**Descripcion:** Solicitud de firma digital multi-firmante. Define un documento que debe ser firmado por uno o mas firmantes en orden secuencial o paralelo. Soporta niveles PAdES B/T/LT/LTA (por defecto LTA para documentos legales). Tiene su propio ciclo de vida con recordatorios de expiracion.

##### Campos (16)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `document_id` | entity_reference (secure_document) | ✅ | FK al documento a firmar |
| `creator_uid` | entity_reference (user) | ✅ | FK al usuario que solicita las firmas |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `title` | string(255) | ✅ | Titulo de la solicitud |
| `description` | text_long | ❌ | Instrucciones para los firmantes |
| `signing_order` | list_string | ✅ | Orden: sequential/parallel. Default: sequential |
| `pades_level` | list_string | ✅ | Nivel PAdES: B/T/LT/LTA. Default: LTA |
| `expires_at` | datetime | ✅ | Fecha limite para firmar. Default: +30 dias |
| `reminder_days` | map (JSON) | ❌ | Dias para recordatorios: `[7, 3, 1]` |
| `status` | list_string | ✅ | Estado: draft/pending/partially_signed/completed/expired/cancelled. Default: draft |
| `completed_at` | datetime | ❌ | Fecha de finalizacion (todas las firmas) |
| `signed_document_id` | entity_reference (secure_document) | ❌ | FK al documento firmado final |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 8.2.7 Entidad `signature_signer`

**Tipo:** ContentEntity
**ID:** `signature_signer`
**Base table:** `signature_signer`
**Descripcion:** Firmante individual dentro de una solicitud de firma. Contiene los datos de contacto, rol (Abogado, Cliente, Testigo), orden de firma, metodo utilizado (AutoFirma, Cl@ve) y datos del certificado digital tras la firma.

##### Campos (16)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `request_id` | entity_reference (signature_request) | ✅ | FK a la solicitud de firma |
| `uid` | entity_reference (user) | ❌ | FK al usuario firmante (si tiene cuenta) |
| `name` | string(255) | ✅ | Nombre completo del firmante |
| `email` | string(255) | ✅ | Email del firmante |
| `nif` | string(20) | ❌ | NIF/DNI para verificar certificado |
| `signing_order` | integer | ✅ | Orden de firma (1, 2, 3...) si sequential |
| `role` | string(100) | ✅ | Rol del firmante (ej: "Abogado", "Cliente", "Testigo") |
| `access_token` | string(255) | ✅ | Token unico de acceso para firmar. UNIQUE |
| `signature_method` | list_string | ❌ | Metodo: autofirma/clave/simple. Null hasta que firme |
| `certificate_info` | map (JSON) | ❌ | Datos del certificado: `{subject_dn, issuer_dn, serial, valid_from, valid_to}` |
| `signed_at` | datetime | ❌ | Momento de la firma |
| `status` | list_string | ✅ | Estado: pending/notified/signed/declined. Default: pending |
| `created` | created | ✅ | Fecha de creacion |
| `changed` | changed | ✅ | Fecha de ultima modificacion |

#### 8.2.8 Entidad `digital_signature`

**Tipo:** ContentEntity
**ID:** `digital_signature`
**Base table:** `digital_signature`
**Descripcion:** Firma digital aplicada a un documento. Contiene los datos tecnicos de la firma: certificado X.509, sello de tiempo RFC 3161, posicion visual en el PDF, y resultado de la verificacion. Es el registro criptografico probatorio de la firma.

##### Campos (18)

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | ✅ | PK autoincremental |
| `uuid` | uuid | ✅ | Identificador universal unico |
| `signer_id` | entity_reference (signature_signer) | ✅ | FK al firmante |
| `request_id` | entity_reference (signature_request) | ✅ | FK a la solicitud |
| `tenant_id` | entity_reference (tenant) | ✅ | FK al tenant |
| `certificate_subject_dn` | string(500) | ✅ | Distinguished Name del sujeto del certificado |
| `certificate_issuer_dn` | string(500) | ✅ | DN del emisor del certificado |
| `certificate_serial` | string(100) | ✅ | Numero de serie del certificado |
| `certificate_valid_from` | datetime | ✅ | Inicio de validez del certificado |
| `certificate_valid_to` | datetime | ✅ | Fin de validez del certificado |
| `timestamp_tsa` | string(255) | ❌ | Nombre de la TSA que sello |
| `timestamp_time` | datetime | ❌ | Momento del sello de tiempo |
| `timestamp_serial` | string(100) | ❌ | Serial del sello de tiempo |
| `signature_method` | list_string | ✅ | Metodo: autofirma/clave |
| `visual_position` | map (JSON) | ❌ | Posicion en PDF: `{page, x, y, width, height}` |
| `verification_status` | list_string | ✅ | Estado: valid/invalid/expired/revoked. Default: valid |
| `signature_data` | text_long | ✅ | Datos CMS de la firma (Base64) |
| `created` | created | ✅ | Fecha de creacion |

---

### 8.3 Services

#### 8.3.1 `VideoRoomService`

```
createRoomForBooking(bookingId):
  -> Genera nombre unico: jrb-{tenant_slug}-{uuid_short(8)}
  -> Genera password aleatorio (12 chars)
  -> Genera JWT moderador (profesional) y participante (cliente)
  -> Construye URLs de acceso con config overrides Jitsi
  -> Devuelve: VideoRoom entity
```

#### 8.3.2 `DocumentVaultService`

```
store(ownerUid, tenantId, encryptedFile, encryptedDek, iv, tag, contentHash, metadata):
  -> Almacena archivo pre-cifrado en servidor (el servidor NUNCA descifra)
  -> Registra metadatos y hash de integridad
  -> Crea registro en audit_log con hash_chain
  -> Devuelve: SecureDocument entity

retrieve(documentId, actorUid):
  -> Verifica permisos de acceso del actor
  -> Devuelve archivo cifrado + DEK cifrado para descifrado client-side
  -> Registra acceso en audit_log
  -> Devuelve: {encrypted_file, encrypted_dek, iv, tag}
```

#### 8.3.3 `DocumentAccessService`

```
shareDocument(documentId, grantorUid, granteeUid, permissions, options):
  -> Re-cifra DEK con clave publica RSA-OAEP del destinatario
  -> Crea DocumentAccess con permisos granulares
  -> Opcionalmente: max_downloads, expires_at, access_password
  -> Registra en audit_log
  -> Notifica al destinatario
  -> Devuelve: DocumentAccess entity

revokeAccess(accessId, grantorUid):
  -> Marca is_revoked = true
  -> Registra en audit_log
  -> Notifica al destinatario
  -> Devuelve: bool
```

#### 8.3.4 `AuditLogService`

```
logAction(documentId, actorUid, action, details):
  -> Obtiene hash del registro anterior en la cadena
  -> Calcula hash_chain: SHA-256(previous_hash + action + actor + timestamp)
  -> Crea registro inmutable (NUNCA se edita ni elimina)
  -> Devuelve: DocumentAuditLog entity

verifyIntegrity(documentId):
  -> Recalcula toda la cadena de hashes desde el primer registro
  -> Compara con los hashes almacenados
  -> Detecta cualquier registro manipulado o eliminado
  -> Devuelve: {is_valid: bool, broken_at: int|null}
```

#### 8.3.5 `SignatureService`

```
createRequest(creatorUid, documentId, signers, options):
  -> Crea SignatureRequest con nivel PAdES (default LTA)
  -> Crea SignatureSigner por cada firmante con access_token unico
  -> Si sequential: activa solo el primer firmante
  -> Envia invitaciones por email
  -> Devuelve: SignatureRequest entity

prepareAutoFirmaSession(signerId):
  -> Obtiene documento, calcula hash del PDF
  -> Crea sesion de firma con TTL 5 minutos
  -> Genera URL afirma:// con parametros de firma
  -> Devuelve: {session_id, autofirma_url, expires_in: 300}

completeAutoFirmaSignature(sessionId, cmsSignature):
  -> Valida la firma CMS recibida de AutoFirma
  -> Verifica certificado X.509 (validez, NIF, CRL/OCSP)
  -> Obtiene sello de tiempo RFC 3161 de TSA cualificada
  -> Incrusta firma + timestamp + LTV data en PDF (PAdES-LTA)
  -> Actualiza firmante y solicitud
  -> Si sequential: activa el siguiente firmante
  -> Si es el ultimo: marca solicitud como completada
  -> Devuelve: DigitalSignature entity
```

---

### 8.4 Controllers

| Ruta | Metodo | Descripcion |
|------|--------|-------------|
| `/servicios/videollamada/{booking_id}` | `VideoController::joinRoom` | Pagina con Jitsi IFrame API embebido |
| `/mi-servicio/buzon` | `VaultController::vault` | Buzon de Confianza del profesional |
| `/servicios/firma/{access_token}` | `SignatureController::signPage` | Pagina de firma para firmantes |
| POST `/servicios/autofirma/prepare` | `SignatureApiController::prepareAutoFirma` | Preparar sesion AutoFirma |
| POST `/servicios/autofirma/complete` | `SignatureApiController::completeAutoFirma` | Completar firma |

---

### 8.5 Templates y Parciales Twig

| Template | Funcion |
|----------|---------|
| `servicios-video-room.html.twig` | Sala Jitsi embebida full-screen |
| `servicios-vault.html.twig` | Buzon de documentos con lista de archivos |
| `servicios-signature-page.html.twig` | Pagina de firma con instrucciones y boton AutoFirma |
| `partials/_document-card.html.twig` | Tarjeta de documento con iconos, estado, acciones |

### 8.6 Archivos a Crear

| Categoria | # | Archivos |
|-----------|---|----------|
| **Entities** | 8 | VideoRoom, VideoParticipant, SecureDocument, DocumentAccess, DocumentAuditLog, SignatureRequest, SignatureSigner, DigitalSignature |
| **Services** | 7 | VideoRoomService, DocumentVaultService, DocumentAccessService, AuditLogService, AutoFirmaAdapter, ClaveAdapter, SignatureService, TimestampService |
| **Controllers** | 3 | VideoController (frontend), VaultController, SignatureController + APIs |
| **Templates** | 7 | 3 paginas + 4 parciales |
| **SCSS** | 3 | `_video-room.scss`, `_vault.scss`, `_signature.scss` |
| **JS** | 3 | `servicios-vault.js`, `servicios-autofirma.js`, `servicios-video.js` |
| **Total** | **31** | |

### 8.7 Archivos a Modificar

| Archivo | Cambios |
|---------|---------|
| `routing.yml` | +10 rutas (video, vault, signature, APIs, webhooks) |
| `services.yml` | +8 services |
| `permissions.yml` | +6 permisos (vault, signature) |
| `module` | +hooks entity_insert (secure_document -> index Qdrant), preprocess_html (body classes) |
| `libraries.yml` | +3 libraries (video, vault, signature) |

### 8.8 Verificacion

- [ ] Crear sala de videollamada automaticamente al confirmar reserva online
- [ ] Verificar Jitsi IFrame con branding del tenant
- [ ] Subir documento cifrado al Buzon (verificar que el servidor NO ve el contenido)
- [ ] Compartir documento con permisos granulares
- [ ] Verificar integridad del audit log (hash chain)
- [ ] Crear solicitud de firma PAdES
- [ ] Firmar con AutoFirma (requiere instalacion local)
- [ ] Verificar sello de tiempo RFC 3161
- [ ] Verificar PAdES-LTA en Adobe Reader

---

## 9. FASE 4: Portal Cliente Documental

**Docs:** 90

**Entidades:** `client_case`, `document_request`, `document_delivery`, `case_activity`

**Descripcion:** Capa de workflow sobre el Buzon de Confianza que transforma la custodia documental en un sistema completo de gestion de expedientes. Los profesionales pueden solicitar documentos a clientes con checklists estructurados, plazos y recordatorios automaticos, asi como entregar documentos con acuse de recibo y solicitud de firma. El cliente accede via token sin necesidad de cuenta.

**Funcionalidades clave:**
- Gestion de expedientes (casos) con numero EXP-2026-XXXX
- Solicitud de documentos con checklist, instrucciones y deadlines
- Portal de cliente accesible via token link (sin login obligatorio)
- Entrega de documentos con acuse de recibo y/o firma digital
- Timeline de actividad visible para ambas partes
- Recordatorios automaticos a 3 dias y 48h antes del deadline
- Barra de progreso con % de documentos completados

**Services:**

```
ClientCaseService:
  createCase(providerId, clientData, documentRequests):
    -> Genera numero EXP-2026-XXXX + access_token
    -> Crea solicitudes de documentos en batch
    -> Envia notificacion al cliente con link al portal

  getCaseProgress(caseId):
    -> Calcula % completitud basado en documentos requeridos vs recibidos
    -> Devuelve: {total, received, approved, rejected, pending, percentage}

DocumentRequestService:
  uploadDocument(requestId, encryptedFile, clientToken):
    -> Almacena en Buzon de Confianza
    -> Concede acceso al profesional
    -> Actualiza estado a 'uploaded'
    -> Notifica al profesional

  reviewDocument(requestId, decision, feedback):
    -> Aprueba o rechaza con motivo
    -> Si rechazado: notifica al cliente con instrucciones
```

**Verificacion:**
- [ ] Crear caso con solicitudes de documentos
- [ ] Verificar portal del cliente accesible via token
- [ ] Subir documento desde el portal del cliente
- [ ] Aprobar/rechazar documentos y verificar notificaciones
- [ ] Verificar recordatorios automaticos

---

## 10. FASE 5: AI Triaje de Casos + Presupuestador Automatico

**Docs:** 91, 92

**Entidades:** `client_inquiry`, `inquiry_triage`, `service_catalog_item`, `quote`, `quote_line_item`

**Descripcion:** Inteligencia artificial aplicada a la gestion de consultas entrantes y generacion automatica de presupuestos. El triaje usa Gemini 2.0 Flash con Strict Grounding para clasificar consultas por urgencia (1-5), categoria, extraer entidades (fechas, importes, partes) y sugerir el profesional mas adecuado. El presupuestador genera estimaciones basadas en el catalogo real de servicios del profesional.

**Funcionalidades clave:**
- Triaje automatico de consultas desde formulario web, email y WhatsApp
- Clasificacion por categoria, subcategoria y urgencia con confianza
- Extraccion de entidades: fechas criticas, importes, partes involucradas
- Sugerencia de profesional con carga de trabajo actual
- Generacion de presupuesto automatico basado en catalogo real
- Portal de cliente para ver/aceptar/rechazar/negociar presupuestos
- Generacion de PDF del presupuesto

**Services:**

```
TriageService:
  processInquiry(inquiryId):
    -> Carga contexto: taxonomia del tenant, providers activos con carga, historial cliente
    -> Construye prompt Strict Grounding (temperatura 0.1, JSON schema)
    -> Llama a Gemini 2.0 Flash via @ai.provider
    -> Valida resultado contra taxonomia real (solo categorias existentes)
    -> Crea InquiryTriage con categoria, urgencia, provider sugerido, respuesta draft
    -> Si urgencia >= 4: notificacion inmediata al provider

QuoteEstimatorService:
  generateEstimate(triageId, providerId):
    -> Carga catalogo de servicios del profesional
    -> Construye prompt Strict Grounding (solo items del catalogo, no inventar precios)
    -> Llama a Gemini 2.0 Flash (temperatura 0.2, JSON schema)
    -> Valida que todos los items sugeridos existen en el catalogo real
    -> Crea Quote con lineas detalladas, subtotal, IVA, total
    -> Genera numero PRES-2026-XXXX
```

**Verificacion:**
- [ ] Enviar consulta por formulario web y verificar triaje automatico (<3 segundos)
- [ ] Verificar que la categoria asignada existe en la taxonomia del tenant
- [ ] Verificar que el profesional sugerido esta activo y con capacidad
- [ ] Generar presupuesto automatico y verificar que usa precios reales del catalogo
- [ ] Verificar portal de cliente para ver/aceptar presupuesto

---

## 11. FASE 6: Copilot de Servicios

**Docs:** 93

**Entidades:** `copilot_conversation`, `copilot_message`, `document_embedding`

**Descripcion:** Asistente RAG (Retrieval-Augmented Generation) para el trabajo diario del profesional. Busca en documentos del caso almacenados en Qdrant, responde con citas verificables, sugiere acciones ejecutables (enviar email, crear tarea, programar reunion) y prepara reuniones con resumen del caso.

**Funcionalidades clave:**
- Chat contextual con referencia al caso activo
- Busqueda vectorial en Qdrant con aislamiento por tenant + caso
- Respuestas con citas a documentos fuente (pagina y fragmento)
- 9 acciones ejecutables desde el chat (send_email, create_task, etc.)
- Indexacion automatica de documentos al subirlos al Buzon
- Sugerencias proactivas matutinas (vencimientos, tareas pendientes)

**Services:**

```
CopilotService:
  chat(conversationId, userMessage, caseId):
    -> Detecta intento (search/summarize/draft/suggest/compare)
    -> Busca en Qdrant con filtros tenant_id + case_id (must, NUNCA should)
    -> Construye prompt con fragmentos recuperados y Strict Grounding
    -> Genera respuesta con citas y acciones sugeridas
    -> scoreThreshold: 0.7 para relevancia

RetrievalService:
  searchDocuments(tenantId, caseId, query, limit):
    -> Genera embedding del query con text-embedding-004
    -> Busca en Qdrant con filtros de seguridad
    -> Descifra fragmentos relevantes del Buzon
    -> Devuelve: array de {document_id, chunk_text, score}
```

**Dependencias:** jaraba_rag (pipeline RAG existente), Qdrant (existente), Gemini 2.0 Flash

---

## 12. FASE 7: Dashboard Profesional

**Docs:** 94

**Entidades:** `dashboard_config`, `provider_alert`

**Descripcion:** Centro de mando diario del profesional. Muestra alertas, agenda del dia, consultas urgentes, casos prioritarios, documentos pendientes, metricas de productividad, presupuestos pendientes y actividad reciente. Vista configurable con widgets reposicionables y actualizacion en tiempo real via WebSocket.

**Funcionalidades clave:**
- 8 widgets configurables (alertas, agenda, consultas, casos, documentos, metricas, presupuestos, actividad)
- Generacion proactiva de alertas (deadlines <48h, consultas urgentes sin asignar, reservas sin confirmar)
- Metricas: casos activos, tasa de conversion, tiempo medio de respuesta, ingresos del periodo
- Frecuencias de actualizacion por widget (real-time a 1 hora)

**Service: `DashboardService`**
```
getDashboardData(providerId, period):
  -> Agrega datos de todos los modulos: alertas, bookings, inquiries, cases, documents, metrics, quotes, activity
  -> Respeta configuracion de widgets del profesional
  -> Devuelve: array indexado por widget_id
```

---

## 13. FASE 8: Dashboard Admin + Analytics

**Docs:** 95

**Entidades:** `analytics_snapshot`

**Descripcion:** Dashboard de inteligencia de negocio para gerentes/socios de despachos y administradores de tenant. KPIs agregados con comparativa MoM, evolucion de ingresos, heatmap de carga de trabajo, funnel de conversion, revenue por categoria, comparativa de profesionales y top clientes. Usa snapshots diarios pre-calculados (cron 02:00) para rendimiento optimo.

**Funcionalidades clave:**
- KPIs con comparativa mes anterior: MRR, revenue, ticket medio, LTV, conversion, churn
- Evolucion de ingresos (grafica de lineas, 12 meses)
- Heatmap de carga de trabajo por profesional
- Funnel de conversion: consulta -> triaje -> presupuesto -> caso -> factura
- Revenue por categoria de servicio (grafica de tarta)
- Comparativa de profesionales (tabla con metricas)
- Exportacion a PDF y Excel
- RBAC: socio (todo), director area (su departamento), admin (financiero/operativo)

**Service: `AnalyticsSnapshotService`**
```
generateDailySnapshots():
  -> Ejecutado por hook_cron a las 02:00
  -> Calcula metricas a nivel tenant, por provider, por categoria
  -> Almacena como JSON en analytics_snapshot
  -> Retiene 365 dias de historico
```

---

## 14. FASE 9: Sistema de Facturacion

**Docs:** 96

**Entidades:** `invoice`, `invoice_line`, `credit_note`

**Descripcion:** Ciclo completo de facturacion desde la aceptacion del presupuesto hasta el cobro. Soporta multiples modelos de facturacion (por caso, hitos, provision, recurrente, por horas, exito). Integra Stripe Invoicing para cobro online automatico. Calcula retencion IRPF para profesionales. Genera PDF conforme a normativa espanola.

**Funcionalidades clave:**
- Series de facturacion: FAC (facturas), REC (recibos), PRO (proformas)
- Datos fiscales del cliente (nombre, NIF, direccion)
- Desglose: subtotal, descuento, IVA (por linea), IRPF (retencion), total
- Integracion Stripe Invoicing con Destination Charges (Connect)
- Generacion automatica desde presupuesto aceptado
- Recordatorios de cobro automaticos (3 dias antes, dia+1, dia+7)
- Notas de credito/rectificativas con refund Stripe
- Facturacion recurrente mensual via Stripe Subscriptions
- Cumplimiento Facturae 3.2.2, SII/AEAT, Verifactu

**Service: `InvoiceService`**
```
createFromQuote(quoteId):
  -> Copia lineas del presupuesto aceptado
  -> Calcula IRPF segun tipo de profesional
  -> Crea Stripe Invoice via Connect
  -> Genera PDF y numero de factura secuencial

issue(invoiceId):
  -> Bloquea numero, genera PDF definitivo
  -> Crea invoice en Stripe
  -> Marca como 'issued'

send(invoiceId):
  -> Envia email con PDF adjunto + enlace de pago Stripe
  -> Actualiza estado a 'sent'
```

---

## 15. FASE 10: Reviews, Notificaciones y API Publica

**Docs:** 97, 98, 99

**Entidades:** `review`, `review_request`, `provider_rating_summary`, `notification`, `notification_preference`, `notification_template`

**Descripcion:** Fase de cierre que integra tres sistemas transversales. Reviews: reputacion automatizada con solicitud post-servicio, ratings multi-dimension, moderacion, widgets embebibles y Schema.org. Notificaciones: sistema multicanal centralizado (email, SMS, WhatsApp, push, in-app) con preferencias, quiet hours y rate limiting. API: documentacion publica para integraciones externas con OAuth2, webhooks HMAC y SDKs.

**Funcionalidades clave Reviews:**
- Solicitud automatica 24h despues de caso cerrado + factura pagada
- Ratings multi-dimension: overall, communication, professionalism, value
- Auto-publicacion >= 4 estrellas, moderacion < 4 estrellas
- Respuesta del profesional con notificacion al cliente
- Widget HTML/JS embebible en web externa
- Schema.org AggregateRating + Review para Google rich snippets
- Tabla denormalizada provider_rating_summary para queries rapidas

**Funcionalidades clave Notificaciones:**
- 5 canales: Email (SendGrid/SES), SMS (Twilio), WhatsApp (Twilio), Push (Firebase), In-App (WebSocket)
- 18 tipos de notificacion predefinidos (bookings, invoices, documents, cases, reviews, quotes)
- Preferencias por usuario y por categoria
- Quiet hours (solo email fuera de horario, excepto urgentes)
- Rate limiting por canal
- Templates Twig por tipo + canal + idioma

**Funcionalidades clave API:**
- OAuth2 Authorization Code Flow + API Key (server-to-server)
- 10 scopes (read/write para cases, clients, bookings, invoices, documents)
- Rate limiting por plan: Free (60/min), Professional (300/min), Enterprise (1000/min)
- 12 tipos de webhook con firma HMAC SHA-256
- SDKs: PHP, JavaScript, Python
- Postman Collection

---

## 16. Paleta de Colores y Design Tokens

### 16.1 Tokens de Color del Vertical

ServiciosConecta usa el Azul Confianza como color primario del vertical, transmitiendo profesionalidad, confianza y seguridad.

| Token CSS | Valor | Uso Semantico |
|-----------|-------|---------------|
| `--ej-servicios-primary` | `#4A90D9` | Azul Confianza - acciones principales, headers, iconos activos |
| `--ej-servicios-secondary` | `#2D6A4F` | Verde Profesional - verificaciones, credenciales, confirmaciones |
| `--ej-servicios-accent` | `#8B5CF6` | Violeta Premium - funciones IA, copilot, features premium |
| `--ej-servicios-warm` | `#F59E0B` | Ambar Alerta - urgencias, deadlines, recordatorios |
| `--ej-servicios-bg` | `#F0F4FF` | Azul Suave - fondos de pagina del vertical |
| `--ej-servicios-surface` | `#FFFFFF` | Blanco - tarjetas, modales, formularios |
| `--ej-servicios-trust` | `#10B981` | Verde Confianza - buzon cifrado, firmas validadas |
| `--ej-servicios-signature` | `#DC2626` | Rojo Firma - firma pendiente, documentos criticos |

### 16.2 Presets por Tipo de Profesional

| Preset ID | Profesion | Primary | Secondary | Mood |
|-----------|-----------|---------|-----------|------|
| `preset-legal` | Abogados | `#1E3A5F` (Azul Marino) | `#B8860B` (Dorado) | Autoridad, tradicion |
| `preset-health` | Salud | `#0D7C66` (Verde Clinico) | `#E8F5E9` (Verde Suave) | Cuidado, bienestar |
| `preset-technical` | Tecnico | `#455A64` (Gris Azulado) | `#FF8C42` (Naranja) | Precision, innovacion |
| `preset-financial` | Financiero | `#1B5E20` (Verde Oscuro) | `#FFF8E1` (Crema) | Solidez, confianza |
| `preset-consulting` | Consultoria | `#4A148C` (Purpura) | `#E1BEE7` (Lavanda) | Creatividad, estrategia |
| `preset-wellness` | Bienestar | `#00897B` (Teal) | `#E0F2F1` (Menta) | Serenidad, armonia |

### 16.3 Implementacion en SCSS

```scss
// jaraba_servicios_conecta/scss/_variables-servicios.scss
//
// Variables locales del vertical ServiciosConecta.
// DIRECTRIZ: NUNCA definir $ej-* aqui. Solo variables locales $servicios-*.
// Estas variables son FALLBACKS para var(--ej-*, #{$fallback}).
// Los valores reales se inyectan via CSS Custom Properties desde la UI de Drupal.

@use 'sass:color';

// === Paleta del vertical ===
$servicios-primary: #4A90D9;      // Azul Confianza
$servicios-secondary: #2D6A4F;    // Verde Profesional
$servicios-accent: #8B5CF6;       // Violeta Premium (IA, copilot)
$servicios-warm: #F59E0B;         // Ambar Alerta
$servicios-bg: #F0F4FF;           // Fondo azulado suave
$servicios-surface: #FFFFFF;      // Superficie de tarjetas
$servicios-trust: #10B981;        // Verde Confianza (cifrado, firmas)
$servicios-signature: #DC2626;    // Rojo Firma

// === Derivados con Dart Sass moderno ===
$servicios-primary-hover: color.adjust($servicios-primary, $lightness: -10%);
$servicios-primary-light: color.adjust($servicios-primary, $lightness: 40%);
$servicios-primary-alpha-15: color.change($servicios-primary, $alpha: 0.15);

// === Uso en componentes (siempre con var() e inyectable) ===
// .servicios-header {
//   background: var(--ej-servicios-primary, #{$servicios-primary});
//   color: var(--ej-text-light, #FFFFFF);
// }
```

---

## 17. Patron de Iconos SVG

ServiciosConecta necesita iconos especificos para su dominio profesional. Todos se crean bajo `ecosistema_jaraba_core/images/icons/services/` con ambas versiones (outline + duotone).

| Icono | Categoria | Uso |
|-------|-----------|-----|
| `calendar-booking` | services | Motor de reservas, slots, agenda |
| `vault-lock` | services | Buzon de Confianza, documentos cifrados |
| `signature-pen` | services | Firma digital, certificados |
| `video-call` | services | Videollamadas, salas Jitsi |
| `briefcase-pro` | services | Perfil profesional, despacho |
| `scale-justice` | services | Profesionales legales |
| `stethoscope` | services | Profesionales de salud |
| `ruler-pencil` | services | Profesionales tecnicos |
| `calculator-finance` | services | Profesionales financieros |
| `brain-ai` | services | Triaje IA, copilot, estimaciones |
| `quote-document` | services | Presupuestos, facturas |
| `shield-trust` | services | Verificacion, credenciales, confianza |
| `clock-booking` | services | Duracion, tiempo, horarios |
| `star-review` | services | Valoraciones, ratings |
| `bell-notification` | services | Recordatorios, notificaciones |

**Uso en Twig:**
```twig
{{ jaraba_icon('services', 'calendar-booking', { color: 'corporate', size: '24px' }) }}
{{ jaraba_icon('services', 'vault-lock', { variant: 'duotone', color: 'impulse', size: '32px' }) }}
{{ jaraba_icon('services', 'signature-pen', { variant: 'duotone', color: 'success', size: '28px' }) }}
```

---

## 18. Orden de Implementacion Global

Dentro de cada fase, seguir este orden estricto (identico al patron establecido en AgroConecta y ComercioConecta):

1. **Entities** (.php con annotation, campos base, entity_keys)
2. **Handlers** (ListBuilder, AccessControlHandler)
3. **Forms** (EntityForm, SettingsForm)
4. **Install** (.install con hook_schema si necesario, hook_install para taxonomias)
5. **Routing** (.routing.yml con rutas de settings, frontend y API)
6. **Permissions** (.permissions.yml)
7. **Links** (.links.menu.yml, .links.task.yml, .links.action.yml)
8. **Services** (.services.yml + clases Service)
9. **Controllers** (Frontend + API)
10. **.module** (hook_theme, hook_preprocess_html, hook_entity_insert/update, hook_cron)
11. **Templates Twig** (paginas + parciales)
12. **JavaScript** (interactividad frontend)
13. **SCSS** (partials, main.scss, compilacion)
14. **Compilar SCSS** (`npm run build`)
15. **Verificacion** (drush en, entity:updates, cr, tests funcionales)

**Comandos de verificacion post-implementacion:**
```bash
# 1. Activar modulo
lando drush en jaraba_servicios_conecta -y

# 2. Instalar tablas de entidades
lando drush entity:updates -y

# 3. Limpiar cache
lando drush cr

# 4. Verificar rutas
lando drush router:rebuild

# 5. Compilar SCSS
lando ssh -c "cd /app/web/modules/custom/jaraba_servicios_conecta && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm install && npm run build"

# 6. Limpiar cache de nuevo
lando drush cr

# 7. Verificar en navegador
# https://jaraba-saas.lndo.site/servicios
# https://jaraba-saas.lndo.site/admin/content (pestanas de entidades)
# https://jaraba-saas.lndo.site/admin/structure (enlaces de estructura)
```

---

## 19. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-09 | 1.0.0 | Creacion inicial del plan de implementacion con 10 fases, 46 entidades, 18 especificaciones tecnicas cubiertas, directrices completas de i18n, SCSS, theming, frontend, seguridad e IA. Fases 1-3 detalladas completamente, Fases 4-10 en formato resumido. |

---

> **Orden de implementacion de fases:** 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7 -> 8 -> 9 -> 10
>
> **Nota:** Las Fases 5 (AI) y 9 (Facturacion) pueden ejecutarse en paralelo a Fases 4 y 7 respectivamente, siempre que las dependencias de Fase 1 esten completadas.
>
> **Referencias:**
> - Especificaciones tecnicas: `docs/tecnicos/20260117e-82` a `20260117e-99` (18 documentos)
> - Gap Analysis: `docs/tecnicos/20260117d-Gap_Analysis_Documentacion_Tecnica_ServiciosConecta_v1_Claude.md`
> - Arquitectura: `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`
> - Directrices: `docs/00_DIRECTRICES_PROYECTO.md` v5.8.0
> - Theming: `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`
> - Patron de referencia: `jaraba_comercio_conecta` (vertical mas cercano en arquitectura)
> - Workflows: `scss-estilos.md`, `drupal-custom-modules.md`, `frontend-page-pattern.md`, `slide-panel-modales.md`, `i18n-traducciones.md`, `ai-integration.md`, `drupal-eca-hooks.md`
