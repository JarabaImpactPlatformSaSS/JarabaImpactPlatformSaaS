# Auditoria Exhaustiva: Landing `/despachos` — Vertical JarabaLex

**Fecha de creacion:** 2026-02-28 10:00
**Ultima actualizacion:** 2026-02-28 10:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Analisis / Auditoria Estrategica
**Vertical:** jarabalex (Despachos)
**Modulos afectados:** ecosistema_jaraba_core, jaraba_legal_intelligence, jaraba_legal_cases, jaraba_legal_calendar, jaraba_legal_billing, jaraba_legal_vault, jaraba_legal_lexnet, jaraba_legal_templates, ecosistema_jaraba_theme
**Estado:** Completado
**Directrices:** v98.0.0 | **Flujo:** v51.0.0 | **Indice:** v127.0.0
**Documentos fuente:** DIRECTRICES v98.0.0, Arquitectura Theming Master v1.0.0, Plan Elevacion JarabaLex v1, Plan Implementacion JarabaLex Legal Practice v1

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance y Metodologia](#2-alcance-y-metodologia)
3. [Inventario Completo de la Landing /despachos](#3-inventario-completo-de-la-landing-despachos)
   - 3.1 [Estructura de secciones](#31-estructura-de-secciones)
   - 3.2 [SEO y Schema.org](#32-seo-y-schemaorg)
   - 3.3 [Inventario de CTAs](#33-inventario-de-ctas)
   - 3.4 [Accesibilidad](#34-accesibilidad)
4. [Hallazgo P0-01: Plan Gratuito sin Respaldo en Configuracion](#4-hallazgo-p0-01-plan-gratuito-sin-respaldo-en-configuracion)
5. [Hallazgo P0-02: Modulos Deshabilitados Vendidos como Features](#5-hallazgo-p0-02-modulos-deshabilitados-vendidos-como-features)
6. [Hallazgo P0-03: LexNET — El Diferenciador Invisible](#6-hallazgo-p0-03-lexnet--el-diferenciador-invisible)
7. [Hallazgo P0-04: Fragmentacion jarabalex vs despachos](#7-hallazgo-p0-04-fragmentacion-jarabalex-vs-despachos)
8. [Hallazgo P0-05: Cascada de Pricing Rota](#8-hallazgo-p0-05-cascada-de-pricing-rota)
9. [Hallazgo P0-06: Lead Magnet sin Controller](#9-hallazgo-p0-06-lead-magnet-sin-controller)
10. [Hallazgo P0-07: Vertical No Canonico](#10-hallazgo-p0-07-vertical-no-canonico)
11. [Hallazgos P1: Argumentos de Venta Ausentes](#11-hallazgos-p1-argumentos-de-venta-ausentes)
    - 11.1 [P1-01: 8 Fuentes Oficiales no mencionadas](#111-p1-01-8-fuentes-oficiales-no-mencionadas)
    - 11.2 [P1-02: Busqueda Semantica IA no explicada](#112-p1-02-busqueda-semantica-ia-no-explicada)
    - 11.3 [P1-03: Calculadora de Plazos LEC 130.2](#113-p1-03-calculadora-de-plazos-lec-1302)
    - 11.4 [P1-04: Templates de Documentos con GrapesJS](#114-p1-04-templates-de-documentos-con-grapesjs)
    - 11.5 [P1-05: Cadena de Custodia SHA-256](#115-p1-05-cadena-de-custodia-sha-256)
    - 11.6 [P1-06: Diagnostico Legal vs Auditoria Digital](#116-p1-06-diagnostico-legal-vs-auditoria-digital)
    - 11.7 [P1-07: Social Proof Debil](#117-p1-07-social-proof-debil)
    - 11.8 [P1-08: Planes Detallados Ausentes](#118-p1-08-planes-detallados-ausentes)
12. [Hallazgos P2: SEO, Design Tokens y UX](#12-hallazgos-p2-seo-design-tokens-y-ux)
    - 12.1 [P2-01: Schema.org Insuficiente](#121-p2-01-schemaorg-insuficiente)
    - 12.2 [P2-02: Meta Description Ausente](#122-p2-02-meta-description-ausente)
    - 12.3 [P2-03: Design Tokens Inconsistentes](#123-p2-03-design-tokens-inconsistentes)
    - 12.4 [P2-04: Pain Points Genericos](#124-p2-04-pain-points-genericos)
    - 12.5 [P2-05: Redirect Legacy Confuso](#125-p2-05-redirect-legacy-confuso)
    - 12.6 [P2-06: Canonical y hreflang Ausentes](#126-p2-06-canonical-y-hreflang-ausentes)
13. [Analisis "Codigo Existe vs Usuario Experimenta"](#13-analisis-codigo-existe-vs-usuario-experimenta)
14. [Analisis Competitivo](#14-analisis-competitivo)
15. [Tabla de Correspondencia con Directrices del Proyecto](#15-tabla-de-correspondencia-con-directrices-del-proyecto)
16. [Matriz de Impacto y Priorizacion](#16-matriz-de-impacto-y-priorizacion)
17. [Conclusion Estrategica](#17-conclusion-estrategica)
18. [Registro de Cambios](#18-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La landing de `/despachos` (URL publica: `https://plataformadeecosistemas.com/despachos`) presenta **problemas criticos de coherencia, consistencia y veracidad** que afectan directamente la credibilidad del SaaS, la conversion de usuarios y potencialmente la conformidad legal bajo la LGDCU (Ley General para la Defensa de los Consumidores y Usuarios).

Se han identificado **21 hallazgos** organizados en 3 niveles de severidad:

| Severidad | Cantidad | Descripcion |
|-----------|----------|-------------|
| **P0 (Critico)** | 7 | Promesas falsas, features inexistentes, vertical no configurado |
| **P1 (Importante)** | 8 | Argumentos de venta ausentes, social proof debil, pricing incompleto |
| **P2 (Mejora)** | 6 | SEO, design tokens, UX, redirects |

**El hallazgo mas grave:** La landing promete un plan gratuito con "5 expedientes gratis", "Copiloto IA incluido", "Agenda con alertas" y "Boveda documental 1 GB" — pero **ninguna de estas promesas tiene respaldo en la configuracion del SaaS**. No existen FreemiumVerticalLimit, no existen SaasPlan, y 3 de los 4 modulos que soportan estas features estan deshabilitados a nivel de Drupal.

**El hallazgo mas estrategico:** La integracion con LexNET (sistema de intercambio telematico con juzgados y tribunales del CGPJ), que es el **killer feature** mas potente del vertical y el mayor diferenciador competitivo frente a Aranzadi/vLex/Lefebvre, **no se menciona en ninguna parte de la landing**.

---

## 2. Alcance y Metodologia

### 2.1 Alcance

Esta auditoria cubre:

1. **Contenido visible** de la landing `/despachos` en produccion
2. **Configuracion backend** del SaaS: FreemiumVerticalLimit, SaasPlan, SaasPlanFeatures, SaasPlanTier
3. **Estado de modulos** Drupal: habilitados vs deshabilitados en `core.extension.yml`
4. **Codigo fuente**: controller `VerticalLandingController::despachos()`, templates Twig, SCSS
5. **Coherencia** entre lo prometido en la landing y la experiencia real post-registro
6. **Comparativa** con la landing hermana `/jarabalex`
7. **Cumplimiento** de directrices del proyecto: ZERO-REGION, ICON, COLOR, TRANS, SCSS, SEO

### 2.2 Metodologia

| Paso | Tecnica | Herramientas |
|------|---------|-------------|
| 1 | Extraccion de contenido de la landing en produccion | WebFetch + analisis de codigo fuente |
| 2 | Revision del controller y template chain | Lectura de `VerticalLandingController.php`, template Twig orchestrator y 9 section partials |
| 3 | Auditoria de configuracion | Busqueda exhaustiva en `config/install/` y `config/sync/` de FreemiumVerticalLimit, SaasPlan, SaasPlanFeatures |
| 4 | Verificacion de estado de modulos | Lectura de `config/sync/core.extension.yml` |
| 5 | Analisis de los 7 modulos legales | Revision de .info.yml, entities, services, controllers de cada modulo |
| 6 | Analisis competitivo | Comparativa de features vs Aranzadi, vLex, Lefebvre |
| 7 | Verificacion de directrices | Cruce con DIRECTRICES v98.0.0, Arquitectura Theming Master, 07_FLUJO_TRABAJO_CLAUDE v51.0.0 |

### 2.3 Archivos Clave Revisados

| Archivo | Lineas relevantes |
|---------|-------------------|
| `ecosistema_jaraba_core/src/Controller/VerticalLandingController.php` | 510-700, 925-960 |
| `ecosistema_jaraba_theme/templates/partials/vertical-landing-content.html.twig` | Completo |
| `ecosistema_jaraba_theme/templates/partials/_landing-pricing-preview.html.twig` | Completo |
| `ecosistema_jaraba_theme/templates/page--jarabalex.html.twig` | Completo |
| `config/sync/core.extension.yml` | Seccion module |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.jarabalex_*` | 18 ficheros |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.saas_plan.jarabalex_*` | 3 ficheros |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.vertical.jarabalex.yml` | Completo |

---

## 3. Inventario Completo de la Landing /despachos

### 3.1 Estructura de secciones

La landing se compone de 9 secciones estandar renderizadas por el template orchestrator `vertical-landing-content.html.twig`:

| # | Seccion | Partial Twig | Contenido principal |
|---|---------|-------------|---------------------|
| 1 | **Hero** | `_landing-hero.html.twig` | H1: "Digitaliza tu despacho con IA". Subtitulo: "Expedientes, citas, facturacion y documentos. Todo en un solo lugar, con inteligencia artificial integrada." CTAs: "Empieza gratis" + "Solicitar demo" |
| 2 | **Pain Points** | `_landing-pain-points.html.twig` | 4 items: expedientes dispersos, citas solapadas, facturacion manual, documentos inseguros |
| 3 | **Solution Steps** | `_landing-solution-steps.html.twig` | 3 pasos: Configura tu despacho, Importa expedientes, Automatiza y factura |
| 4 | **Features Grid** | `_landing-features-grid.html.twig` | 6 features: Copiloto IA Borradores, Gestion Expedientes, Agenda Inteligente, Facturacion Automatizada, Citaciones Multi-formato, Boveda Documental |
| 5 | **Social Proof** | `_landing-social-proof.html.twig` | 2 metricas ("2h ahorro diario", "0 plazos incumplidos") + 2 testimonios (Elena, Roberto) |
| 6 | **Lead Magnet** | `_landing-lead-magnet.html.twig` | "Auditoria Digital para Despachos" con slide-panel de captura email. URL recurso: `/despachos/auditoria-digital` |
| 7 | **Pricing Preview** | `_landing-pricing-preview.html.twig` | "Desde 0 EUR/mes". Features: 5 expedientes gratis, Copiloto IA incluido, Agenda con alertas, Boveda documental 1 GB |
| 8 | **FAQ** | `_landing-faq.html.twig` | 6 preguntas: importacion, relacion JarabaLex, normativa fiscal, seguridad, multi-abogado, calendario |
| 9 | **Final CTA** | `_landing-final-cta.html.twig` | H2: "Listo para digitalizar tu despacho?" CTA: "Empieza gratis ahora" |

**Ruta:** `/despachos`
**Controller:** `VerticalLandingController::despachos()` (linea 604)
**vertical_key:** `despachos` (NO `jarabalex`)
**Color token:** `corporate` (#233D63)

### 3.2 SEO y Schema.org

| Elemento | Estado | Detalle |
|----------|--------|---------|
| `<title>` | Presente | "Despachos - Digitaliza tu despacho con IA \| Jaraba" |
| `<meta description>` | **AUSENTE** | No se genera meta description especifica para `/despachos` |
| Schema.org Organization | Presente (global) | Datos genericos de la plataforma |
| Schema.org WebApplication | Presente (global) | Datos genericos con `"JarabaLex -- Inteligencia Legal con IA"` en featureList |
| Schema.org FAQPage | Presente | 6 preguntas del FAQ generadas automaticamente por `_landing-faq.html.twig` |
| Schema.org SoftwareApplication | **AUSENTE** | No hay schema especifico para el producto "Despachos" |
| Schema.org Product/AggregateOffer | **AUSENTE** | No hay schema de pricing detallado |
| `<link rel="canonical">` | **AUSENTE** | No hay canonical explicito |
| `<link rel="alternate" hreflang>` | **AUSENTE** | No hay hreflang para ES/EN |

### 3.3 Inventario de CTAs

| # | Ubicacion | Texto | Destino | Tracking | Estado |
|---|-----------|-------|---------|----------|--------|
| 1 | Hero primario | "Empieza gratis" | `/es/user/register` | `hero_despachos` | Funcional |
| 2 | Hero secundario | "Solicitar demo" | `/contacto` | `hero_secondary_despachos` | Funcional |
| 3 | Lead magnet | "Hacer auditoria gratuita" | Slide-panel form | `lead_magnet_despachos` | Slide-panel funcional, pero recurso `/despachos/auditoria-digital` **SIN CONTROLLER** |
| 4 | Lead magnet form | "Enviar y acceder" | POST form | N/A | Formulario HTML, sin backend real |
| 5 | Lead magnet exito | "Descargar ahora" | `/despachos/auditoria-digital` | N/A | **RUTA INEXISTENTE** |
| 6 | Pricing | "Ver todos los planes" | `/planes` | `pricing_despachos` | Funcional (pero /planes no tiene seccion despachos) |
| 7 | Final CTA | "Empieza gratis ahora" | `/es/user/register` | `final_despachos` | Funcional |

### 3.4 Accesibilidad

| Criterio | Estado | Notas |
|----------|--------|-------|
| `aria-labelledby` en secciones | OK | Cada seccion vincula su H2 |
| `aria-hidden` en decorativos | OK | Iconos, conectores, numeros de paso |
| `role="list"` en listas | OK | FAQ y pricing features |
| `aria-haspopup="dialog"` en slide-panel | OK | Lead magnet trigger |
| `focus-visible` | OK | Estilos de foco definidos |
| `prefers-reduced-motion` | OK | Soporte declarado en CSS |
| Skip to main content | OK | Enlace en header global |

---

## 4. Hallazgo P0-01: Plan Gratuito sin Respaldo en Configuracion

### Descripcion

La seccion de pricing de la landing (seccion 7) promete un plan gratuito con 4 features especificas:

```
"5 expedientes gratis"
"Copiloto IA incluido"
"Agenda con alertas"
"Boveda documental 1 GB"
```

Estas promesas estan hardcodeadas en el controller (`VerticalLandingController.php`, lineas 658-670):

```php
'pricing' => [
    'headline' => $this->t('Planes para despachos'),
    'from_price' => '0',
    'currency' => 'EUR',
    'period' => 'mes',
    'features_preview' => [
        $this->t('5 expedientes gratis'),
        $this->t('Copiloto IA incluido'),
        $this->t('Agenda con alertas'),
        $this->t('Boveda documental 1 GB'),
    ],
],
```

### Evidencia de la Ausencia

| Fuente de Configuracion | Busqueda realizada | Resultado |
|-------------------------|-------------------|-----------|
| `FreemiumVerticalLimit` | `ls config/install/ecosistema_jaraba_core.freemium_vertical_limit.despachos*` | **0 ficheros** |
| `FreemiumVerticalLimit` | `ls config/sync/ecosistema_jaraba_core.freemium_vertical_limit.despachos*` | **0 ficheros** |
| `SaasPlan` | `ls config/install/ecosistema_jaraba_core.saas_plan.despachos*` | **0 ficheros** |
| `SaasPlan` | `ls config/sync/ecosistema_jaraba_core.saas_plan.despachos*` | **0 ficheros** |
| `SaasPlanFeatures` | `ls config/install/ecosistema_jaraba_core.plan_features.despachos*` | **0 ficheros** |
| `Vertical ConfigEntity` | `ls config/install/ecosistema_jaraba_core.vertical.despachos*` | **0 ficheros** |
| `BaseAgent::VERTICALS` | Lista de 10 verticales canonicos | **"despachos" NO incluido** |

### Comparativa con JarabaLex (que SI tiene configuracion)

| Feature | Config JarabaLex Free | Config Despachos Free |
|---------|----------------------|----------------------|
| Busquedas/mes | 10 (`jarabalex_free_searches_per_month`) | **INEXISTENTE** |
| Alertas | 1 (`jarabalex_free_max_alerts`) | **INEXISTENTE** |
| Bookmarks | 10 (`jarabalex_free_max_bookmarks`) | **INEXISTENTE** |
| Citaciones | 0 bloqueado (`jarabalex_free_citation_insert`) | **INEXISTENTE** |
| Digest | 0 bloqueado (`jarabalex_free_digest_access`) | **INEXISTENTE** |
| API | 0 bloqueado (`jarabalex_free_api_access`) | **INEXISTENTE** |
| Expedientes | **INEXISTENTE** | **INEXISTENTE** |
| Boveda storage | **INEXISTENTE** | **INEXISTENTE** |
| Agenda | **INEXISTENTE** | **INEXISTENTE** |

### Analisis Detallado por Promesa

**"5 expedientes gratis":**
- La entidad `ClientCase` vive en `jaraba_legal_cases` (habilitado)
- Pero `jaraba_legal_cases` no define un tier `free` en su configuracion de limites
- Solo define `starter`, `pro`, `enterprise`
- No existe `FreemiumVerticalLimit` con `feature_key: max_cases` para ninguna vertical
- El `JarabaLexFeatureGateService` no tiene gate para `max_cases`

**"Copiloto IA incluido":**
- El copiloto legal existe como `SmartLegalCopilotAgent` (Gen 2)
- Pero no hay copilot limit configurado para `despachos`
- Los limites de copilot del SaaS (`copilot_uses_per_month`) estan en `_default_*` tiers, no en `despachos_*`

**"Agenda con alertas":**
- La entidad `LegalDeadline` vive en `jaraba_legal_calendar`
- **El modulo `jaraba_legal_calendar` esta DESHABILITADO** en `core.extension.yml`
- Aunque estuviera habilitado, no hay FreemiumVerticalLimit para funcionalidades de calendario

**"Boveda documental 1 GB":**
- Las entidades de boveda viven en `jaraba_legal_vault`
- **El modulo `jaraba_legal_vault` esta DESHABILITADO** en `core.extension.yml`
- `jaraba_legal_vault.settings.yml` define limites solo para `starter` (500 MB), `pro` (5 GB), `enterprise` (unlimited)
- **No hay tier free, y la cifra de 1 GB no aparece en ninguna configuracion**

### Impacto

- **Conversion:** Un usuario que se registre esperando "5 expedientes gratis" no encontrara la funcionalidad prometida — frustacion inmediata y abandono
- **Legal:** Potencial vulnerabilidad bajo LGDCU arts. 20-21 (informacion precontractual) y Ley de Competencia Desleal art. 5 (actos de engano)
- **Reputacional:** Dano a la marca si usuarios comparten experiencias negativas en redes profesionales (LinkedIn, foros juridicos)

---

## 5. Hallazgo P0-02: Modulos Deshabilitados Vendidos como Features

### Descripcion

De las 6 features principales de la landing, 3 dependen de modulos Drupal que estan **deshabilitados** en produccion.

### Estado de los 7 Modulos Legales

| Modulo | Ficheros | Entidades | APIs REST | Implementacion | Habilitado | Mencionado en Landing |
|--------|----------|-----------|-----------|----------------|------------|----------------------|
| `jaraba_legal_intelligence` | 137 | 5 | 20+ | Completa: busqueda semantica, NLP 9 etapas, 8 fuentes | **SI** | Parcialmente ("citaciones") |
| `jaraba_legal_cases` | 21 | 4 | 18 | Completa: expedientes, triaje IA, actividad | **SI** | SI ("Gestion de Expedientes") |
| `jaraba_legal_calendar` | 22 | 5 | 10+ | Completa: plazos LEC 130.2, Google/Outlook sync | **NO** | SI ("Agenda Inteligente") |
| `jaraba_legal_billing` | 31 | 7 | 25+ | Completa: minutas, facturas, Stripe | **NO** | SI ("Facturacion Automatizada") |
| `jaraba_legal_vault` | 22 | 5 | 18 + portal | Completa: AES-256-GCM, hash chain SHA-256 | **NO** | SI ("Boveda Documental") |
| `jaraba_legal_lexnet` | 15 | 2 | 7 | Completa: mTLS, CGPJ API, notificaciones | **NO** | **NO** |
| `jaraba_legal_templates` | 14 | 2 | 4 | Completa: GrapesJS 11 bloques, merge fields | **NO** | **NO** |

### Impacto por Feature Vendida

| Feature en Landing | Modulo Requerido | Estado Modulo | Experiencia del Usuario |
|-------------------|------------------|---------------|------------------------|
| "Copiloto IA para Borradores" | `jaraba_legal_intelligence` | Habilitado | Parcialmente accesible (copilot) |
| "Gestion de Expedientes" | `jaraba_legal_cases` | Habilitado | **Accesible** |
| "Agenda Inteligente" | `jaraba_legal_calendar` | **Deshabilitado** | **INEXISTENTE para el usuario** |
| "Facturacion Automatizada" | `jaraba_legal_billing` | **Deshabilitado** | **INEXISTENTE para el usuario** |
| "Citaciones Multi-formato" | `jaraba_legal_intelligence` | Habilitado | Accesible (citaciones) |
| "Boveda Documental" | `jaraba_legal_vault` | **Deshabilitado** | **INEXISTENTE para el usuario** |

### Detalle de Implementaciones (codigo real, no stubs)

Es importante destacar que los modulos deshabilitados contienen **implementaciones reales y completas**, no placeholders:

**`jaraba_legal_calendar` (deshabilitado):**
- `DeadlineCalculatorService`: Calculo de plazos procesales conforme a LEC arts. 130-136, incluyendo agosto inhabil para no-penal, exclusion de fines de semana, festivos nacionales y autonomicos
- `CalendarSyncService`: Sincronizacion bidireccional con Google Calendar y Microsoft Outlook
- `LegalAgendaService`: Vista unificada de citas, vistas judiciales y plazos
- `DeadlineAlertService`: Alertas automaticas con anticipacion configurable
- 5 entidades: LegalDeadline, CourtHearing, CalendarConnection, ExternalEventCache, SyncedCalendar

**`jaraba_legal_billing` (deshabilitado):**
- `TimeTrackingService`: Cronometro JS con tracking de tiempo billable/no-billable por expediente
- `InvoiceManagerService`: Generacion de facturas desde entradas de tiempo, series fiscales legales
- `StripeInvoiceService`: Integracion con Stripe para cobros y webhooks
- `QuoteManagerService`: Presupuestos con portal de aceptacion por token
- 7 entidades: LegalInvoice, TimeEntry, Quote, ServiceCatalogItem, InvoiceLine, QuoteLineItem, CreditNote
- Compatibilidad TicketBAI/SII mencionada en FAQ

**`jaraba_legal_vault` (deshabilitado):**
- `VaultEncryptionService`: Cifrado AES-256-GCM con envelope encryption (libsodium)
- `VaultAuditLogService`: Log append-only con hash chain SHA-256 para cadena de custodia digital
- `DocumentAccessService`: Control de acceso granular con tokens de comparticion temporal
- Portal de cliente con autenticacion por token (sin login de usuario)
- 5 entidades: SecureDocument, DocumentAccess, DocumentAuditLog, DocumentDelivery, DocumentRequest

---

## 6. Hallazgo P0-03: LexNET — El Diferenciador Invisible

### Descripcion

La integracion con **LexNET** (sistema de intercambio telematico de documentos con juzgados y tribunales gestionado por el CGPJ — Consejo General del Poder Judicial de Espana) es, desde una perspectiva de producto y mercado, el **diferenciador competitivo mas potente** del vertical JarabaLex. Sin embargo, **no se menciona en ningun lugar de la landing `/despachos`**.

### Contexto de Mercado

- LexNET es **obligatorio** para todos los profesionales juridicos en Espana desde el 1 de enero de 2016 (Ley 42/2015, de 5 de octubre, de reforma de la Ley de Enjuiciamiento Civil)
- Todo abogado en ejercicio debe usar LexNET diariamente para presentar escritos, recibir notificaciones judiciales y consultar el estado de procedimientos
- La experiencia nativa de LexNET es notoriamente deficiente: interfaz web lenta, desconectada de cualquier sistema de gestion, requiere certificado digital en cada sesion
- **Ningun competidor de precio similar** (sub-200 EUR/mes) ofrece integracion con LexNET
- Los grandes players (Aranzadi, vLex, Lefebvre) cobran 3.000-8.000 EUR/ano y su integracion LexNET es limitada o inexistente

### Lo que Existe en el Codigo

El modulo `jaraba_legal_lexnet` contiene:

| Componente | Descripcion |
|-----------|-------------|
| `LexnetApiClient` | Cliente API completo con autenticacion mTLS y certificados QES (firma electronica cualificada) |
| `LexnetSyncService` | Servicio de polling de notificaciones desde la API del CGPJ |
| `LexnetSubmissionService` | Servicio de envio de escritos a LexNET con gestion de adjuntos |
| `LexnetNotification` entity | Entidad para almacenar notificaciones judiciales recibidas |
| `LexnetSubmission` entity | Entidad para tracking de escritos enviados |
| `LexnetDashboardController` | Dashboard de notificaciones con filtros y estado |
| `LexnetApiController` | 7 endpoints REST para integracion |
| `LexnetSettingsForm` | Formulario de configuracion de credenciales y certificados |

### Impacto de la Omision

| Aspecto | Sin mencionar LexNET | Con LexNET destacado |
|---------|---------------------|---------------------|
| Diferenciacion | Producto generico de gestion | **Unico SaaS asequible con LexNET integrado** |
| Pain point principal | No abordado | "Dejas de hacer login en LexNET 10 veces al dia" |
| Conversion abogados | Dudosa (parece generico) | Alta (resuelve dolor diario #1) |
| Competitividad vs Aranzadi | Sin ventaja clara | **Ventaja 15x en precio con feature comparable** |
| SEO keywords | Pierde "gestion despacho lexnet", "integracion lexnet" | Captura keywords de alto valor comercial |

### Recomendacion

LexNET debe ser:
1. **Feature #1 o #2** en la grid de features (por encima de "Citaciones Multi-formato")
2. **Pain point dedicado**: "Abrir LexNET, descargar notificaciones, y subir respuestas manualmente a otro sistema 10 veces al dia"
3. **FAQ dedicada**: "Se integra con LexNET / CGPJ?"
4. **Distintivo visual** prominente: "Integracion oficial con LexNET / CGPJ"
5. **Schema.org:** Mencion en `featureList` del schema `SoftwareApplication`

---

## 7. Hallazgo P0-04: Fragmentacion jarabalex vs despachos

### Descripcion

Existen DOS landings separadas para el mismo vertical legal:

| Aspecto | `/jarabalex` | `/despachos` |
|---------|-------------|--------------|
| **Controller** | `jarabalex()` linea 510 | `despachos()` linea 604 |
| **vertical_key** | `jarabalex` | `despachos` |
| **Foco** | Investigacion legal IA (busqueda, alertas, citaciones, fuentes) | Gestion de despacho (expedientes, citas, facturacion, boveda) |
| **Plan free config** | SI (18 FreemiumVerticalLimits) | **NO** (0 configs) |
| **SaaS Plans** | SI (3 planes: Starter 49EUR, Pro 99EUR, Enterprise 199EUR) | **NO** (0 planes) |
| **Es vertical canonico** | SI (`BaseAgent::VERTICALS`) | **NO** |
| **Lead magnet** | "Diagnostico Legal Gratuito" (controller real con evaluacion 6 areas) | "Auditoria Digital" (URL sin controller) |
| **Color token** | `corporate` + design tokens propios (#1E3A5F navy + #C8A96E gold) | `corporate` (#233D63) |
| **Template pagina** | `page--jarabalex.html.twig` (con copilot legal personalizado) | Usa `page--vertical-landing.html.twig` (generico) |

### Problema Fundamental

El FAQ de `/despachos` dice literalmente:

> "Son complementarios. JarabaLex busca jurisprudencia y normativa. Despachos gestiona el dia a dia del despacho: expedientes, citas, facturacion. Se integran nativamente."

Pero esta distincion es **confusa e irrelevante para el usuario target**. Un abogado quiere **una solucion integral** para su despacho. No quiere tener que entender la diferencia entre dos subproductos del mismo ecosistema. Los competidores (Aranzadi Fusión, vLex, Wolters Kluwer) ofrecen soluciones integrales que cubren tanto investigacion legal como gestion del despacho bajo una unica marca.

### Evidencia de Fragmentacion

El docblock del controller explica la intencion original:

```php
/**
 * Landing para despachos profesionales.
 *
 * Complementa JarabaLex (investigacion legal): este vertical gestiona el
 * despacho (expedientes, citas, facturacion, boveda documental).
 */
```

Ademas, existe un redirect legacy (linea 826): `/legal` redirige 301 a `/despachos`, cuando originalmente apuntaba a `/jarabalex`. Esto confirma la confusion historica entre las dos URLs.

### Impacto

1. **SEO:** Dos URLs compiten por las mismas keywords ("gestion despacho", "software juridico"), diluyendo la autoridad de dominio
2. **Conversion:** El usuario que llega a `/despachos` no descubre las features de investigacion legal, y viceversa
3. **Mantenimiento:** Duplicacion de esfuerzo en contenido, SEO, analytics, testing
4. **Pricing:** `/despachos` no tiene planes configurados; el usuario tiene que descubrir que los planes estan en `/jarabalex`
5. **Funnels:** Los 3 funnels de analytics (`jarabalex_acquisition`, `jarabalex_activation`, `jarabalex_monetization`) solo rastrean el vertical `jarabalex`, no `despachos`

---

## 8. Hallazgo P0-05: Cascada de Pricing Rota

### Descripcion

El metodo `buildLanding()` intenta enriquecer el pricing con datos dinamicos de `MetaSitePricingService`, pero la cascada falla para `despachos`:

```
1. MetaSitePricingService::getFromPrice('despachos')
   -> PlanResolverService::getFeatures('despachos', 'starter')
      -> Busca SaasPlanFeatures con ID 'despachos_starter' -> NO EXISTE
      -> Fallback: busca '_default_starter' -> EXISTE (pero es generico: max_pages, storage_gb, ai_queries)
      -> Retorna features genericos que no aplican a un despacho legal
2. Resultado: el pricing hardcodeado del controller prevalece sin validacion
```

Esto significa que los datos de pricing que ve el usuario (5 expedientes gratis, etc.) son **puramente cosmeticos** — no se corresponden con ningun plan real, ni pasan por el sistema de FeatureGate, ni se validan contra FreemiumVerticalLimit.

---

## 9. Hallazgo P0-06: Lead Magnet sin Controller

### Descripcion

El lead magnet de `/despachos` ofrece una "Auditoria Digital para Despachos" con URL de recurso `/despachos/auditoria-digital`.

Sin embargo:
- **No existe controller** para la ruta `/despachos/auditoria-digital`
- **No existe ruta** en ningun `*.routing.yml` para esa URL
- El formulario del slide-panel es HTML puro sin backend de procesamiento
- Comparativamente, `/jarabalex` ofrece un "Diagnostico Legal Gratuito" en `/jarabalex/diagnostico-legal` con controller real (`LegalLandingController::diagnostico()`) que evalua 6 areas juridicas con puntuaciones y recomendaciones especificas

---

## 10. Hallazgo P0-07: Vertical No Canonico

### Descripcion

`despachos` no es un vertical canonico del SaaS. Los 10 verticales canonicos definidos en `BaseAgent::VERTICALS` son:

```
empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex,
serviciosconecta, andalucia_ei, jaraba_content_hub, formacion, demo
```

Esto implica que `despachos`:
- No tiene config entity `Vertical` propia
- No tiene `JourneyDefinition` (progresion de usuario)
- No tiene funnels de analytics propios
- No tiene email sequences propios
- No tiene health score service propio
- No tiene cross-vertical bridge propio
- No aparece en el dropdown de verticales en admin
- No se puede asignar a un Tenant

---

## 11. Hallazgos P1: Argumentos de Venta Ausentes

### 11.1 P1-01: 8 Fuentes Oficiales no mencionadas

La landing `/jarabalex` destaca que el vertical integra 8 fuentes oficiales de datos juridicos:

| Fuente | Ambito | API | Destacada en /jarabalex | Mencionada en /despachos |
|--------|--------|-----|------------------------|-------------------------|
| CENDOJ | Nacional — Resoluciones judiciales | SI | SI | NO |
| BOE | Nacional — Legislacion | SI | SI | NO |
| DGT | Nacional — Consultas vinculantes tributarias | SI | SI | NO |
| TEAC | Nacional — Doctrina administrativa tributaria | SI | SI | NO |
| EUR-Lex | UE — Legislacion europea | SI | SI | NO |
| CURIA | UE — Jurisprudencia TJUE | SI | SI | NO |
| HUDOC | Europeo — Resoluciones TEDH | SI | SI | NO |
| EDPB | Europeo — Proteccion de datos | SI | SI | NO |

La unica mencion en `/despachos` es en el FAQ: "JarabaLex busca jurisprudencia y normativa" — una frase generica que no transmite la amplitud de las fuentes integradas.

### 11.2 P1-02: Busqueda Semantica IA no explicada

El core de `jaraba_legal_intelligence` incluye:
- Embeddings vectoriales de 3072 dimensiones
- Qdrant como motor de busqueda vectorial
- Pipeline NLP de 9 etapas (extraccion de entidades, clasificacion de jurisdiccion, auto-resumen, etc.)
- `LegalMergeRankService` para fusion y re-ranking de resultados nacionales + europeos

En `/despachos`, esto se reduce a un generico "Copiloto IA para Borradores" que no explica la sofisticacion tecnologica subyacente.

### 11.3 P1-03: Calculadora de Plazos LEC 130.2

`DeadlineCalculatorService` implementa el calculo real de plazos procesales conforme a la LEC:
- Agosto es mes inhabil para procedimientos no penales
- Fines de semana excluidos del computo de plazos
- Festivos nacionales y autonomicos
- Reglas especificas de la LGT art. 48 para plazos tributarios

En la landing, esto se reduce a "plazos procesales con alertas automaticas" sin explicar que es un **calculo normativo real**, no un simple recordatorio.

### 11.4 P1-04: Templates de Documentos con GrapesJS

`jaraba_legal_templates` ofrece:
- 11 bloques GrapesJS especificos para documentos legales
- Sistema de merge fields (sustitucion automatica de datos del expediente)
- `AiDraftingService` para generacion de borradores asistida por IA

Ni siquiera se menciona la existencia de esta funcionalidad.

### 11.5 P1-05: Cadena de Custodia SHA-256

`VaultAuditLogService` implementa un log append-only con hash chain SHA-256. Esto proporciona **cadena de custodia digital** con potencial valor probatorio en procedimientos judiciales. La landing solo menciona "cifrado end-to-end" genericamente, perdiendo un argumento diferencial para abogados que necesitan trazabilidad documental con valor probatorio.

### 11.6 P1-06: Diagnostico Legal vs Auditoria Digital

| Aspecto | `/jarabalex` Lead Magnet | `/despachos` Lead Magnet |
|---------|-------------------------|-------------------------|
| Nombre | "Diagnostico Legal Gratuito" | "Auditoria Digital para Despachos" |
| Controller | `LegalLandingController::diagnostico()` (real) | **NO EXISTE** |
| Ruta | `/jarabalex/diagnostico-legal` (definida en routing) | `/despachos/auditoria-digital` (404) |
| Funcionalidad | 6 areas juridicas, puntuaciones, recomendaciones con articulos legales | Solo formulario de captura email |
| Valor percibido | Alto (evaluacion profesional gratuita) | Bajo (generico, sin entrega real) |

### 11.7 P1-07: Social Proof Debil

- Solo 2 testimonios con nombres genericos sin apellidos
- Sin fotos, sin perfiles de LinkedIn, sin links verificables
- Las metricas ("2h ahorro diario", "0 plazos incumplidos") no estan respaldadas por datos reales
- Falta: numero de despachos registrados, numero de expedientes gestionados, NPS

### 11.8 P1-08: Planes Detallados Ausentes

La seccion de pricing solo muestra el plan free. No hay:
- Tabla comparativa de planes (Starter vs Pro vs Enterprise)
- Precios de referencia (49, 99, 199 EUR/mes)
- Detalle de features por plan
- Toggle mensual/anual con descuento

El CTA "Ver todos los planes" lleva a `/planes`, pero esa pagina no tiene seccion especifica para despachos.

---

## 12. Hallazgos P2: SEO, Design Tokens y UX

### 12.1 P2-01: Schema.org Insuficiente

Falta `SoftwareApplication` con:
```json
{
  "@type": "SoftwareApplication",
  "name": "JarabaLex Despachos",
  "applicationCategory": "LegalService",
  "operatingSystem": "Web",
  "offers": { "@type": "AggregateOffer", "lowPrice": "0", "highPrice": "199", "priceCurrency": "EUR" },
  "featureList": ["Gestion de expedientes", "Integracion LexNET", ...]
}
```

### 12.2 P2-02: Meta Description Ausente

No se genera `<meta name="description">` especifica. Impacto directo en CTR de SERPs. Deberia incluir: "Software de gestion para despachos de abogados con IA. Expedientes, facturacion, LexNET, boveda cifrada. Desde 0 EUR/mes."

### 12.3 P2-03: Design Tokens Inconsistentes

- `/despachos` usa color token `corporate` (#233D63)
- JarabaLex design tokens definidos en `ecosistema_jaraba_core.design_token_config.vertical_jarabalex.yml`: primario #1E3A5F (navy) + acento #C8A96E (gold)
- Son dos azules diferentes, generando percepcion de inconsistencia visual al navegar entre `/despachos` y las paginas de `/legal/*`

### 12.4 P2-04: Pain Points Genericos

Los 4 pain points actuales son validos pero superficiales. Falta el **pain point #1** de todo abogado espanol:

> "Consultar notificaciones en LexNET, descargar escritos, y volver a subir respuestas manualmente a otro sistema — 10 veces al dia"

### 12.5 P2-05: Redirect Legacy Confuso

`/legal` redirige 301 a `/despachos` (linea 826 del controller). Originalmente apuntaba a `/jarabalex`. Los backlinks SEO existentes a `/legal` ahora llevan a la landing de gestion de despacho en vez de investigacion legal.

### 12.6 P2-06: Canonical y hreflang Ausentes

- No hay `<link rel="canonical" href="https://plataformadeecosistemas.com/despachos">`
- No hay `<link rel="alternate" hreflang="es" href="...">` ni `hreflang="x-default"`
- Incumple directriz HREFLANG-SEO-001

---

## 13. Analisis "Codigo Existe vs Usuario Experimenta"

Este es el analisis central de la auditoria. Resume la brecha entre la realidad tecnica del codebase y la experiencia real del usuario final.

### Tabla Completa

| Funcionalidad | Codigo (LOC) | Entidades | APIs | Modulo | Habilitado | Mencionado Landing | Configurable Free | EXPERIENCIA REAL |
|--------------|-------------|-----------|------|--------|------------|-------------------|-------------------|------------------|
| Busqueda semantica IA | 22,703 | 5 | 20+ | `jaraba_legal_intelligence` | SI | Parcial | SI (10/mes) | Accesible via `/legal/search` |
| Gestion expedientes | ~3,500 | 4 | 18 | `jaraba_legal_cases` | SI | SI | NO configurado | Accesible pero sin limites free |
| Agenda/Plazos LEC | ~3,800 | 5 | 10+ | `jaraba_legal_calendar` | **NO** | SI | NO | **INEXISTENTE** |
| Facturacion legal | ~5,200 | 7 | 25+ | `jaraba_legal_billing` | **NO** | SI | NO | **INEXISTENTE** |
| Boveda cifrada | ~3,600 | 5 | 18+ | `jaraba_legal_vault` | **NO** | SI | NO | **INEXISTENTE** |
| Integracion LexNET | ~2,400 | 2 | 7 | `jaraba_legal_lexnet` | **NO** | **NO** | NO | **INEXISTENTE** |
| Templates documentos | ~2,200 | 2 | 4 | `jaraba_legal_templates` | **NO** | **NO** | NO | **INEXISTENTE** |
| Copiloto legal IA | ~1,800 | - | - | `jaraba_legal_intelligence` | SI | SI | Limitado | Accesible via FAB |
| Citaciones 4 formatos | ~800 | 1 | 2 | `jaraba_legal_intelligence` | SI | SI | Bloqueado free | Solo planes de pago |
| Alertas inteligentes | ~1,200 | 1 | 4 | `jaraba_legal_intelligence` | SI | NO | 1 alerta free | Accesible |

### Resumen por Estado

| Estado | Count | Porcentaje |
|--------|-------|------------|
| Codigo existe + Habilitado + Mencionado + Configurado | 2 | 20% |
| Codigo existe + Habilitado + Mencionado + NO configurado | 1 | 10% |
| Codigo existe + Habilitado + NO mencionado | 2 | 20% |
| Codigo existe + NO habilitado + Mencionado (**CRITICO**) | 3 | **30%** |
| Codigo existe + NO habilitado + NO mencionado | 2 | 20% |

**Conclusion:** El 30% de las features vendidas en la landing son **promesas vacias** — el codigo existe pero el usuario no puede acceder a nada porque los modulos estan deshabilitados.

---

## 14. Analisis Competitivo

### Posicionamiento en el Mercado Legal Tech Espanol

| Feature | Aranzadi Fusion (3.000-8.000 EUR/ano) | vLex (2.400-6.000 EUR/ano) | Lefebvre (3.600-7.200 EUR/ano) | JarabaLex (588-2.388 EUR/ano) |
|---------|-----|------|---------|----------|
| Busqueda jurisprudencia | SI | SI | SI | SI (8 fuentes, IA semantica) |
| Busqueda IA semantica | Limitada | SI (Vincent AI) | Limitada | SI (Qdrant 3072D) |
| Legislacion actualizada | SI | SI | SI | SI (BOE, EUR-Lex) |
| Alertas inteligentes | Basicas | SI | SI | SI (10 tipos) |
| Gestion expedientes | NO | NO | Parcial | SI (`jaraba_legal_cases`) |
| **Integracion LexNET** | NO | NO | NO | **SI** (`jaraba_legal_lexnet`) |
| Agenda/Plazos procesales | NO | NO | Parcial | SI (`jaraba_legal_calendar`) |
| Facturacion legal | NO | NO | NO | SI (`jaraba_legal_billing`) |
| Boveda cifrada | NO | NO | NO | SI (`jaraba_legal_vault`) |
| Templates documentos | NO | Parcial | SI | SI (`jaraba_legal_templates`) |
| Plan gratuito | NO | NO | NO | **SI** (10 busquedas/mes) |
| Precio entrada | 250 EUR/mes | 200 EUR/mes | 300 EUR/mes | **0 EUR/mes** (free) / 49 EUR/mes (Starter) |

**JarabaLex tiene el portfolio de features mas completo del mercado a una fraccion del precio.** Pero la landing `/despachos` no comunica esta ventaja competitiva porque:
1. No menciona LexNET (el unico con esta integracion)
2. No menciona las 8 fuentes oficiales
3. No muestra los precios de los planes de pago
4. No se compara con la competencia

---

## 15. Tabla de Correspondencia con Directrices del Proyecto

| Directriz | ID | Cumplimiento | Hallazgo Relacionado | Accion Requerida |
|-----------|----|-------------|---------------------|------------------|
| Textos traducibles | I18N-METASITE-001 | **SI** | - | Textos usan `$this->t()` en controller |
| Variables CSS inyectables | CSS-VAR-ALL-COLORS-001 | **PARCIAL** | P2-03 | Color token `corporate` no usa design tokens propios de jarabalex |
| Zero-region template | ZERO-REGION-001 | **SI** | - | `page--vertical-landing.html.twig` no usa `{{ page.content }}` |
| Body classes via hook | LEGAL-BODY-001 | **SI** | - | Classes inyectadas via `hook_preprocess_html()` |
| Iconos SVG duotone | ICON-DUOTONE-001 | **SI** | - | Todos los iconos usan `variant: 'duotone'` |
| Colores de iconos Jaraba | ICON-COLOR-001 | **SI** | - | Todos usan `color: 'corporate'` |
| No emojis | ICON-EMOJI-001 | **SI** | - | Ningun emoji en templates |
| Schema.org FAQ | SCHEMA-FAQ-001 | **SI** | - | Auto-generado por `_landing-faq.html.twig` |
| Schema.org SoftwareApp | - | **NO** | P2-01 | Falta schema especifico para producto |
| Meta description | SEO-META-001 | **NO** | P2-02 | No se genera meta description |
| Hreflang | HREFLANG-SEO-001 | **NO** | P2-06 | Faltan tags hreflang |
| Canonical | - | **NO** | P2-06 | Falta canonical explicito |
| Slide-panel render | SLIDE-PANEL-RENDER-001 | **SI** | - | Lead magnet usa `data-slide-panel` correctamente |
| FreemiumVerticalLimit | FREEMIUM-TIER-001 | **NO** | P0-01 | No existen limites configurados |
| Feature gating | LEGAL-GATE-001 | **NO** | P0-01 | No hay gate service para despachos |
| FeatureGate cascade | PLAN-CASCADE-001 | **NO** | P0-05 | `despachos_starter` no existe, fallback generico |
| Lead magnet legal | LEGAL-LEADMAGINT-001 | **NO** | P0-06 | URL sin controller, sin disclaimer legal |
| Vertical canonico | VERTICAL-CANONICAL-001 | **NO** | P0-07 | `despachos` no esta en `BaseAgent::VERTICALS` |
| Color tokens vertical | VERTICAL-ELEV-003 | **NO** | P2-03 | No hay `--ej-despachos-*` ni usa `--ej-legal-*` |
| Dart Sass compilacion | SCSS-COMPILE-VERIFY-001 | N/A | - | No hay SCSS especifico de despachos |
| Parciales reutilizables | - | **SI** | - | Usa el sistema de 9 section partials |

---

## 16. Matriz de Impacto y Priorizacion

| ID | Hallazgo | Impacto Negocio | Impacto Tecnico | Impacto Legal | Esfuerzo | Prioridad |
|----|----------|----------------|-----------------|---------------|----------|-----------|
| P0-01 | Plan free sin config | **CRITICO** (abandono) | Alto | **CRITICO** (LGDCU) | Medio | **INMEDIATO** |
| P0-02 | Modulos deshabilitados vendidos | **CRITICO** (frustacion) | Alto | Alto | Alto | **INMEDIATO** |
| P0-03 | LexNET invisible | **CRITICO** (diferenciacion perdida) | Bajo | - | Bajo | **INMEDIATO** |
| P0-04 | Fragmentacion jarabalex/despachos | Alto (confusion) | Medio | - | Alto | **CORTO PLAZO** |
| P0-05 | Pricing cascade rota | Alto (datos cosmeticos) | Medio | Medio | Medio | **CORTO PLAZO** |
| P0-06 | Lead magnet sin controller | Alto (conversion perdida) | Bajo | - | Bajo | **INMEDIATO** |
| P0-07 | Vertical no canonico | Alto (sin funnel analytics) | Alto | - | Alto | **CORTO PLAZO** |
| P1-01 | 8 fuentes no mencionadas | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P1-02 | Busqueda semantica no explicada | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P1-03 | Calculadora LEC no explicada | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P1-04 | Templates no mencionados | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P1-05 | Cadena custodia no explicada | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P1-06 | Lead magnet inferior | Alto (conversion) | Bajo | - | Bajo | **CORTO PLAZO** |
| P1-07 | Social proof debil | Medio | Bajo | - | Medio | MEDIO PLAZO |
| P1-08 | Planes detallados ausentes | Alto (conversion) | Bajo | - | Medio | **CORTO PLAZO** |
| P2-01 | Schema.org insuficiente | Bajo | Bajo | - | Bajo | LARGO PLAZO |
| P2-02 | Meta description ausente | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P2-03 | Design tokens inconsistentes | Bajo | Medio | - | Medio | LARGO PLAZO |
| P2-04 | Pain points genericos | Medio | Bajo | - | Bajo | MEDIO PLAZO |
| P2-05 | Redirect legacy confuso | Bajo | Bajo | - | Bajo | LARGO PLAZO |
| P2-06 | Canonical/hreflang ausentes | Medio | Bajo | - | Bajo | MEDIO PLAZO |

---

## 17. Conclusion Estrategica

### Diagnostico

La landing de `/despachos` tiene un problema de fondo: **vende una vision que el SaaS no respalda operativamente**. De las 4 features del plan gratuito, ninguna tiene configuracion de limites. De las 6 features principales, 3 pertenecen a modulos deshabilitados. El mayor diferenciador competitivo (LexNET) es invisible. Y la fragmentacion con `/jarabalex` diluye una propuesta de valor que, si se presentara unificada, seria extraordinariamente potente.

### El Problema Real

El codigo existe. Las implementaciones son solidas y de nivel profesional — mas de 40,000 lineas de codigo funcional repartidas en 7 modulos. Pero el usuario no experimenta nada de eso. Y la landing le promete cosas que no encontrara al registrarse.

**Este es el peor escenario posible en conversion SaaS: promesa alta + experiencia nula = abandono inmediato + dano reputacional.**

### Recomendacion Principal

**Unificar** `/despachos` y `/jarabalex` en una **unica landing integral** que presente todo el portfolio de funcionalidades legales (investigacion + gestion + LexNET + boveda + facturacion + templates) bajo la marca `JarabaLex`. Esto requiere:

1. Redirigir `/despachos` a `/jarabalex` (301)
2. Redisenar `/jarabalex` para cubrir ambas propuestas de valor
3. Habilitar progresivamente los modulos satelite (calendar, billing, vault, lexnet, templates)
4. Crear la infraestructura FreemiumVerticalLimit para las features de gestion de despacho
5. Destacar LexNET como killer feature diferencial

El plan de implementacion detallado se encuentra en:
`docs/implementacion/2026-02-28_Plan_Unificacion_Landing_JarabaLex_Despachos_Clase_Mundial_v1.md`

---

## 18. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-28 | 1.0.0 | Creacion del documento con auditoria exhaustiva completa de 21 hallazgos |
