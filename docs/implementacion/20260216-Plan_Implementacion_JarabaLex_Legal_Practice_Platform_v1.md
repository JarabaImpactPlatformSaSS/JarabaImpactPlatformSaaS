# Plan de Implementacion: JarabaLex Legal Practice Platform v1.0

| Metadato | Valor |
|----------|-------|
| **Documento** | Plan de Implementacion |
| **Version** | 1.0.0 |
| **Fecha** | 2026-02-16 |
| **Estado** | Planificacion Aprobada |
| **Vertical** | JarabaLex (Legal Intelligence → Legal Practice Platform) |
| **Modulos Nuevos** | `jaraba_legal_cases`, `jaraba_legal_calendar`, `jaraba_legal_vault`, `jaraba_legal_billing`, `jaraba_legal_lexnet`, `jaraba_legal_templates` |
| **Modulos Existentes Afectados** | `jaraba_legal_intelligence`, `ecosistema_jaraba_core`, `jaraba_facturae`, `ecosistema_jaraba_theme` |
| **Docs de Referencia** | 85, 86, 88, 89, 90, 91, 92, 96, 00_DIRECTRICES v40.0.0, 00_ARQUITECTURA v40.0.0 |
| **Estimacion Total** | 3 Macro-Fases (A/B/C) · 9 Sub-Fases · 890–1,180 horas · 40,050–53,100 EUR |

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision Estrategica](#11-vision-estrategica)
  - [1.2 Analisis de Mercado](#12-analisis-de-mercado)
  - [1.3 Analisis Multidisciplinar](#13-analisis-multidisciplinar)
  - [1.4 Ventajas Competitivas a Lograr](#14-ventajas-competitivas-a-lograr)
  - [1.5 Infraestructura Existente Reutilizable](#15-infraestructura-existente-reutilizable)
  - [1.6 Esfuerzo Estimado Total](#16-esfuerzo-estimado-total)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
- [4. Arquitectura General de Modulos](#4-arquitectura-general-de-modulos)
  - [4.1 Mapa de Modulos y Dependencias](#41-mapa-de-modulos-y-dependencias)
  - [4.2 Estructura de Directorios Estandar](#42-estructura-de-directorios-estandar)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. MACRO-FASE A: Nucleo Operativo — Expedientes + Agenda](#6-macro-fase-a-nucleo-operativo--expedientes--agenda)
  - [6.1 FASE A1: jaraba_legal_cases — Gestion de Expedientes](#61-fase-a1-jaraba_legal_cases--gestion-de-expedientes)
  - [6.2 FASE A2: jaraba_legal_calendar — Agenda Juridica y Tributaria](#62-fase-a2-jaraba_legal_calendar--agenda-juridica-y-tributaria)
  - [6.3 FASE A3: Integracion Expedientes ↔ Legal Intelligence](#63-fase-a3-integracion-expedientes--legal-intelligence)
- [7. MACRO-FASE B: Productividad Completa — Documentos + Facturacion](#7-macro-fase-b-productividad-completa--documentos--facturacion)
  - [7.1 FASE B1: jaraba_legal_vault — Buzon de Confianza](#71-fase-b1-jaraba_legal_vault--buzon-de-confianza)
  - [7.2 FASE B2: Portal Cliente Documental](#72-fase-b2-portal-cliente-documental)
  - [7.3 FASE B3: jaraba_legal_billing — Facturacion Legal + Time Tracking](#73-fase-b3-jaraba_legal_billing--facturacion-legal--time-tracking)
- [8. MACRO-FASE C: Diferenciacion Competitiva — LexNET + Plantillas IA](#8-macro-fase-c-diferenciacion-competitiva--lexnet--plantillas-ia)
  - [8.1 FASE C1: jaraba_legal_lexnet — Integracion LexNET](#81-fase-c1-jaraba_legal_lexnet--integracion-lexnet)
  - [8.2 FASE C2: jaraba_legal_templates — Plantillas de Escritos con IA](#82-fase-c2-jaraba_legal_templates--plantillas-de-escritos-con-ia)
  - [8.3 FASE C3: Copilot Legal v2 — 2 Nuevos Modos Contextuales](#83-fase-c3-copilot-legal-v2--2-nuevos-modos-contextuales)
- [9. Inventario Consolidado de Entidades](#9-inventario-consolidado-de-entidades)
- [10. Inventario Consolidado de Services](#10-inventario-consolidado-de-services)
- [11. Inventario Consolidado de Endpoints REST API](#11-inventario-consolidado-de-endpoints-rest-api)
- [12. Paleta de Colores y Design Tokens](#12-paleta-de-colores-y-design-tokens)
- [13. Patron de Iconos SVG](#13-patron-de-iconos-svg)
- [14. Orden Global de Implementacion](#14-orden-global-de-implementacion)
- [15. Estimacion de Esfuerzo](#15-estimacion-de-esfuerzo)
- [16. Registro de Cambios](#16-registro-de-cambios)

---

## 1. Resumen Ejecutivo

JarabaLex es hoy un producto de **Legal Intelligence clase mundial**: busqueda semantica via Qdrant, NLP pipeline de 9 etapas, 8 spiders de datos juridicos, copilot IA con 6 modos, alertas inteligentes, citation graph, y sistema freemium completo. Sin embargo, le faltan las herramientas que un profesional del derecho necesita para gestionar su practica diaria.

Este plan implementa las **6 necesidades fundamentales** que transforman JarabaLex de un producto de investigacion legal a una **plataforma completa de gestion de despacho profesional**, la primera en Espana que integra nativamente IA + practice management desde su diseno.

### 1.1 Vision Estrategica

| Dimension | Hoy (Legal Intelligence) | Objetivo (Legal Practice Platform) | Multiplicador |
|-----------|--------------------------|-------------------------------------|---------------|
| TAM Espana | ~EUR 50M (Legal AI) | ~EUR 300M+ (Legal Tech Platforms) | **6x** |
| Tiempo en plataforma | ~15 min/dia (busqueda puntual) | ~6-8 horas/dia (herramienta principal) | **25-30x** |
| ARPU | EUR 74/usuario/mes (media planes) | EUR 129/usuario/mes (bundle) | **+74%** |
| Churn mensual | 5-7% (sustituible) | 2-3% (datos del despacho = lock-in) | **-60%** |
| LTV (24 meses) | EUR 1,776 | EUR 3,096 | **+74%** |
| Segmentos | Abogados litigantes | Abogados + Asesores Fiscales + Procuradores | **3x** |

**Posicionamiento objetivo:** *"JarabaLex — La plataforma completa del despacho inteligente. Gestiona tus expedientes, controla tus plazos, comparte documentos con tus clientes, factura tus honorarios, y busca jurisprudencia — todo con IA integrada, en un solo lugar."*

### 1.2 Analisis de Mercado

#### Tamano de mercado y crecimiento

| Metrica | Valor |
|---------|-------|
| Mercado Legal Tech Europa 2025 | USD 6,170M |
| Proyeccion Europa 2030 | USD 10,310M (CAGR 10.81%) |
| Mercado Legal AI Espana 2024 | USD 18.1M |
| Proyeccion Legal AI Espana 2030 | USD 50.8M (CAGR 18%) |
| Plataformas LegalTech Online Espana | USD 1,200M |
| Segmento small firms CAGR | 11.17% (el mas rapido) |
| Despachos con sistemas legacy | 71% |
| Abogados colegiados Espana | ~155,000-243,000 |

#### Mega-operaciones 2024-2025 que confirman la convergencia

| Operacion | Importe | Tesis |
|-----------|---------|-------|
| **Clio + vLex** | USD 1,000M (valoracion Clio: $5B) | Practice management + legal research + AI en una plataforma |
| **Karnov + Aranzadi + La Ley** | EUR 160M | Consolidacion editorial → Aranzadi One (research + practice + KAILA AI) |
| **Legora** (fundraise) | USD 150M (valoracion $1.8B) | AI legal con partnership Tirant lo Blanch |
| **Relativity + PredictaLex** | EUR 62M | AI multilingue para jurisdicciones civiles |

**Patron inequivoco:** El mercado premia la integracion vertical completa (research + management + AI), no los point solutions.

#### Competidores directos en Espana

| Feature | Aranzadi One | Lefebvre (NEO+LexON) | Clio+vLex | Tirant (SOFIA) | **JarabaLex (objetivo)** |
|---------|-------------|----------------------|-----------|----------------|--------------------------|
| Legal Research DB | Si (editorial) | Si (Mementos) | Si (1B+ docs) | Si | **Si (IA semantica)** |
| AI Copilot | KAILA (30% premium) | GenIA-L | Vincent AI | SOFIA 3.0 | **Si (6+2 modos, RAG)** |
| Gestion Expedientes | Si | Si (LexON) | Si (Clio Manage) | No | **Si (N1)** |
| Agenda Judicial/Tributaria | Si | Si | Si | No | **Si (N2)** |
| LexNET certificado | **Si** | **Si** | No | No | **Si (N5)** |
| Portal Cliente | Limitado | CLIENT LINK | Si | No | **Si (N3, zero-knowledge)** |
| Facturacion Legal | Si | Si (BI) | Si | No | **Si (N4, VeriFactu)** |
| Control de Horas | Si | Si | Si | No | **Si (N4)** |
| Firma Electronica | No nativo | Lefebvre FIRMA | Si | No | **Si (PAdES existente)** |
| NLP Pipeline propio | No | No | No | No | **Si (9 etapas, unico)** |
| Citation Graph | No | No | No | No | **Si (unico)** |
| Multi-tenant SaaS | No | No | Si | No | **Si (nativo)** |
| Precio | EUR 117/mes+ | No publico | USD 399/mes+ | No publico | **EUR 99-199/mes** |

**Diferenciador clave:** JarabaLex sera la **unica plataforma en Espana** que combina practice management + legal research + NLP pipeline propio + citation graph + copilot IA contextual + multi-tenant SaaS nativo. Aranzadi One se acerca pero no tiene IA nativa (KAILA es un addon con 30% premium). Clio+vLex es global pero no tiene LexNET ni adaptacion al mercado espanol.

### 1.3 Analisis Multidisciplinar

#### Consultor de Negocio Senior
El bundle research+practice management crea **retention estructural**: un despacho que gestiona expedientes + busca jurisprudencia en la misma plataforma tiene costes de cambio altisimos. El modelo de Aranzadi One (EUR 117/mes por research+management) demuestra willingness-to-pay. JarabaLex a EUR 99/mes con mejor IA seria un 15% mas barato con tecnologia superior.

#### Analista Financiero Senior
Practice management es **sticky** (datos del despacho, expedientes, historial de clientes) mientras que legal research es **sustituible** (puedo buscar en vLex manana). El bundle reduce churn de 5-7% a 2-3% mensual, mejorando LTV un 74%. CAC payback se reduce de 8-10 meses a 4-6 meses por mayor conversion (propuesta de valor completa).

#### Experto en Mercados Senior
71% de los despachos espanoles usan sistemas legacy — oportunidad masiva de modernizacion. El segmento small firms crece al 11.17% CAGR (el mas rapido). LexNET integration es un **moat estructural**: herramientas internacionales (Clio, MyCase, PracticePanther) no pueden competir en Espana sin ella.

#### Consultor de Marketing Senior
El cambio de posicionamiento de "busqueda juridica" a "plataforma completa" desbloquea mensajes de conversion mas fuertes, segmentacion por avatar (abogado litigante, asesor fiscal, procurador), cross-sell natural (busqueda gratuita → expedientes → upgrade), y reduccion de churn.

#### Publicista Senior
Narrativa: *"Tu despacho necesita UNA herramienta, no cinco."* Use case: Buscas jurisprudencia → La vinculas al expediente → JarabaLex te recuerda el plazo → Generas el escrito con citas insertadas → Lo envias al cliente desde el portal seguro → Registras el tiempo → Facturas automaticamente. Ese flujo completo es imposible hoy. Con practice management, es el flujo natural.

#### Arquitecto SaaS Senior
Los 6 modulos nuevos comparten `tenant_id` entity_reference, permisos RBAC, API envelope estandar, y patron zero-region. `ClientCase` es la entidad pivote — todo gira alrededor del expediente. El copilot existente (6 modos) se enriquece con contexto del expediente para RAG contextualizado.

#### Ingeniero UX Senior
El dashboard del profesional debe ser una **vista de trabajo**: expedientes activos, plazos proximos, documentos pendientes — con la busqueda juridica integrada en el contexto del caso. El profesional no entra a buscar y se va; **vive** en JarabaLex 8 horas al dia.

#### Ingeniero de Drupal Senior
Todos los datos operativos como Content Entities con Field UI + Views + AccessControlHandler. Patron integer FK para referencias cross-module a entidades que pueden no existir aun (como `LegalCitation.expediente_id`). Cada modulo independiente pero con dependencias opcionales.

#### Ingeniero de IA Senior
Con expedientes, el copilot puede: (1) busqueda contextualizada ("busca jurisprudencia para este expediente"), (2) generacion de escritos con hechos + jurisprudencia + plantilla, (3) analisis de plazos, (4) prediccion de resultado (ML con expedientes cerrados), (5) deteccion de conflictos de intereses.

#### Ingeniero SEO/GEO Senior
Practice management desbloquea SEO local: cada despacho-tenant puede tener landing publica optimizada para "abogado [especialidad] [ciudad]". El portal cliente genera enlaces permanentes que mejoran autoridad de dominio.

### 1.4 Ventajas Competitivas a Lograr

| # | Ventaja | Descripcion | Competidores que NO la tienen |
|---|---------|-------------|-------------------------------|
| **VC-1** | NLP Pipeline propio (9 etapas) | Extraccion, segmentacion, NER juridico, clasificacion IA, embeddings, citation graph — no depende de contenido editorial | Todos (Aranzadi, Lefebvre, vLex, Tirant usan contenido editorial) |
| **VC-2** | Copilot contextual al expediente | IA que lee el caso completo (hechos, partes, plazos, documentos) para buscar y redactar | Todos (sus IAs operan sin contexto de caso) |
| **VC-3** | Zero-knowledge document vault | Cifrado AES-256-GCM client-side, servidor nunca ve datos en claro | Todos (almacenamiento server-side convencional) |
| **VC-4** | Multi-tenant SaaS nativo | Cada despacho aislado, white-label, sin instalacion | Aranzadi, Lefebvre, Tirant (on-premise o SaaS basico) |
| **VC-5** | LexNET + IA integrada | Notificaciones judiciales + busqueda semantica + generacion de escritos en un flujo | Clio/vLex (no tienen LexNET), Tirant (no tiene management) |
| **VC-6** | Computo automatico de plazos | Calculo de plazos procesales (LEC 130-136) y tributarios (LGT 48) con calendario laboral | Parcial en Aranzadi One; Clio/vLex no |
| **VC-7** | Precio competitivo con IA incluida | EUR 99-199/mes vs EUR 117/mes+ (Aranzadi, sin IA real) o USD 399/mes+ (vLex) | Karnov cobra 30% premium por KAILA |
| **VC-8** | Citation graph como moat de datos | Grafo de citas entre resoluciones construido automaticamente — crece con cada ingesta | Ningun competidor tiene citation graph |

### 1.5 Infraestructura Existente Reutilizable

| Componente existente | Modulo | Reutilizacion |
|---------------------|--------|---------------|
| `LegalCitation.expediente_id` (integer FK) | jaraba_legal_intelligence | Patron FK a entidad futura — ya apunta a `ClientCase` |
| API endpoints `/api/v1/legal/expediente/{id}/references` | jaraba_legal_intelligence | Ya implementado, solo falta la entidad destino |
| `attachToExpediente()` en LegalCitationService | jaraba_legal_intelligence | Logica completa de vinculacion citation→expediente |
| `AvailabilitySlot` + `AvailabilityService` | jaraba_servicios_conecta | Patron de calendario reutilizable para agenda juridica |
| `FacturaeXmlService` + `FacturaeNumberingService` | jaraba_facturae | Generacion Facturae 3.2.2 XML para facturacion legal |
| `CryptographyService` (libsodium Ed25519) | jaraba_credentials | Patron de criptografia (firma + primitivas libsodium) reutilizable para Buzon de Confianza; el cifrado AES-256-GCM del vault es servicio nuevo sobre la misma base |
| `TemplateLoaderService` + MJML system | jaraba_email | Motor de plantillas con `{{ variable }}` merge — base para escritos |
| `LegalCopilotAgent` (6 modos) | jaraba_legal_intelligence | Extender con 2 modos nuevos (case_assistant, document_drafter) |
| `JarabaLexFeatureGateService` | ecosistema_jaraba_core | Gate de features para nuevas funcionalidades |
| Patron zero-region (3 hooks) | ecosistema_jaraba_theme | Reutilizable para todas las paginas nuevas |
| PAdES digital signature | jaraba_servicios_conecta (doc 89) | Firma electronica para documentos del vault |

**Estimacion de reutilizacion: 35-40%** del codigo base ya existe como infraestructura, patrones o servicios parciales.

### 1.6 Esfuerzo Estimado Total

| Macro-Fase | Modulos | Entidades | Services | Endpoints | Horas (min) | Horas (max) |
|------------|---------|-----------|----------|-----------|-------------|-------------|
| **A** Nucleo Operativo | jaraba_legal_cases, jaraba_legal_calendar | 9 | 8 | 28 | 320 | 420 |
| **B** Productividad | jaraba_legal_vault, jaraba_legal_billing | 12 | 9 | 48 | 310 | 410 |
| **C** Diferenciacion | jaraba_legal_lexnet, jaraba_legal_templates | 4 | 6 | 13 | 260 | 350 |
| **TOTAL** | **6 modulos nuevos** | **25** | **23** | **89** | **890** | **1,180** |

**Inversion estimada:** 890–1,180 horas · EUR 45/hora = **EUR 40,050–53,100**
**Total ecosistema post-implementacion:** 89 nuevos + 31 existentes = ~120 endpoints REST API
**Timeline:** ~10 meses (1 desarrollador senior), ~5 meses (2 en paralelo) — 3 macro-fases secuenciales

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

| Doc # | Titulo Especificacion | Macro-Fase | Modulo Drupal | Entidades Principales | Estado |
|-------|----------------------|------------|--------------|----------------------|--------|
| **90** | Portal Cliente Documental | A1 | `jaraba_legal_cases` | ClientCase, CaseActivity, DocumentRequest | ⬜ Planificada |
| **91** | AI Triaje de Casos | A1 | `jaraba_legal_cases` | ClientInquiry, InquiryTriage | ⬜ Planificada |
| **85** | Booking Engine Core | A2 | `jaraba_legal_calendar` | LegalDeadline, CourtHearing | ⬜ Planificada |
| **86** | Calendar Sync | A2 | `jaraba_legal_calendar` | CalendarConnection, SyncedCalendar, ExternalEventCache | ⬜ Planificada |
| **88** | Buzon de Confianza | B1 | `jaraba_legal_vault` | SecureDocument, DocumentAccess, DocumentAuditLog | ⬜ Planificada |
| **90** | Portal Cliente (workflow) | B2 | `jaraba_legal_vault` | DocumentDelivery (extension) | ⬜ Planificada |
| **96** | Sistema Facturacion | B3 | `jaraba_legal_billing` | LegalInvoice, InvoiceLine, TimeEntry, CreditNote | ⬜ Planificada |
| **92** | Presupuestador Auto | B3 | `jaraba_legal_billing` | ServiceCatalogItem, Quote, QuoteLineItem | ⬜ Planificada |
| — | LexNET Integration (nuevo) | C1 | `jaraba_legal_lexnet` | LexnetNotification, LexnetSubmission | ⬜ Planificada |
| — | Plantillas Escritos IA (nuevo) | C2 | `jaraba_legal_templates` | LegalTemplate, GeneratedDocument | ⬜ Planificada |

**Dependencias entre documentos:**

| Doc Origen | Doc Destino | Datos que fluyen |
|-----------|-------------|------------------|
| 91 (Triaje) | 90 (Portal/Cases) | `inquiry.converted_to_case_id` → auto-creacion expediente |
| 92 (Presupuestador) | 90 (Cases) | `quote.converted_to_case_id` → auto-creacion expediente |
| 92 (Presupuestador) | 96 (Facturacion) | `quote.accepted` → auto-creacion factura |
| 90 (Cases) | 88 (Buzon) | `secure_document.case_id` → documentos vinculados a expediente |
| 86 (Calendar) | 85 (Booking) | Eventos externos bloquean disponibilidad |
| 85 (Booking) | 86 (Calendar) | Reservas confirmadas → push a calendario externo |

---

## 3. Cumplimiento de Directrices del Proyecto

### 3.1 Directriz: i18n — Textos siempre traducibles

Todos los strings visibles al usuario usan `TranslatableMarkup` en PHP, `|t` en Twig, `Drupal.t()` en JS:

```php
$fields['status'] = BaseFieldDefinition::create('list_string')
  ->setLabel(new TranslatableMarkup('Estado'))
  ->setSetting('allowed_values', [
    'active' => new TranslatableMarkup('Activo'),
    'on_hold' => new TranslatableMarkup('En espera'),
    'completed' => new TranslatableMarkup('Completado'),
    'archived' => new TranslatableMarkup('Archivado'),
  ]);
```

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

```scss
// _legal-cases-dashboard.scss
.legal-cases-dashboard {
  background: var(--ej-bg-surface, #FFFFFF);
  border-radius: var(--ej-border-radius-lg, 14px);
  padding: var(--ej-spacing-lg, 1.5rem);

  &__header {
    color: var(--ej-legal-primary, #1E3A5F);
    font-family: var(--ej-font-heading, 'Libre Baskerville', serif);
  }

  &__deadline-urgent {
    background: color-mix(in srgb, var(--ej-color-danger, #E53935) 10%, transparent);
    border-left: 4px solid var(--ej-color-danger, #E53935);
  }
}
```

### 3.3 Directriz: Dart Sass moderno

Compilacion via Docker NVM: `@use` (no `@import`), `color-mix()` (no `rgba()`), `var(--ej-*)` (no `$ej-*`).

### 3.4 Directriz: Frontend limpio sin regiones Drupal

Todas las paginas de los 6 modulos usan templates zero-region: `page--legal-cases.html.twig`, `page--legal-calendar.html.twig`, etc. Sin `{{ page.content }}`, sin sidebars, sin breadcrumbs heredados.

### 3.5 Directriz: Body classes via hook_preprocess_html()

```php
function jaraba_legal_cases_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route, 'jaraba_legal_cases.')) {
    $variables['attributes']['class'][] = 'vertical-jarabalex';
    $variables['attributes']['class'][] = 'page--legal-cases';
  }
}
```

### 3.6 Directriz: CRUD en modales slide-panel

Creacion/edicion de expedientes, plazos, documentos, facturas y plantillas via `data-dialog-type="modal"` con `drupal.dialog.ajax`.

### 3.7 Directriz: Entidades con Field UI y Views

Las 25 entidades del plan son Content Entities con Field UI, Views data, AccessControlHandler, ListBuilder y AdminHtmlRouteProvider.

### 3.8 Directriz: No hardcodear configuracion

Plazos procesales base, modelos de facturacion, plantillas de escritos, configuracion LexNET — todo via Config Entities o admin forms, nunca valores en codigo.

### 3.9 Directriz: Parciales Twig reutilizables

Cada modulo define parciales bajo `templates/partials/`: `_case-card.html.twig`, `_deadline-badge.html.twig`, `_document-row.html.twig`, `_invoice-summary.html.twig`, `_timeline-entry.html.twig`.

### 3.10 Directriz: Seguridad

- `_permission` en todas las rutas sensibles
- Cifrado AES-256-GCM para documentos del vault (libsodium)
- Hash chain (SHA-256) en audit log del vault (inmutable, deteccion de tampering)
- HMAC en webhooks (Stripe, LexNET)
- Sanitizacion antes de `|raw` en templates
- Token-based access para portal cliente (no passwords en URLs)

### 3.11 Directriz: Comentarios de codigo

Comentarios 3D: fichero (header `@file`), clase/servicio (docblock con descripcion funcional), metodo (parametros + return + logica no obvia).

### 3.12 Directriz: Iconos SVG duotone

Categorias: `legal/` (gavel, briefcase, scale, document, calendar, clock, invoice, template), `security/` (lock, shield, key). Dos versiones: outline + duotone.

### 3.13 Directriz: AI via abstraccion @ai.provider

Generacion de escritos, triaje de casos, y estimacion de presupuestos usan `@ai.provider` (Gemini 2.0 Flash). Nunca HTTP directo a APIs LLM.

### 3.14 Directriz: Automaciones via hooks Drupal

Hooks nativos PHP en `.module`: `hook_entity_insert`, `hook_entity_update`, `hook_cron`, `hook_mail`. No ECA YAML para logica compleja.

### 3.15 Directriz: Reglas JarabaLex (Seccion 5.8.5)

- **LEGAL-RAG-001:** Disclaimer + citas verificables en respuestas copilot
- **LEGAL-GATE-001:** FeatureGateService obligatorio en servicios con limites
- **LEGAL-BODY-001:** Body classes via `hook_preprocess_html()`, nunca `attributes.addClass()`

---

## 4. Arquitectura General de Modulos

### 4.1 Mapa de Modulos y Dependencias

```
┌─────────────────────────────────────────────────────────────────────┐
│                    JARABALEX LEGAL PRACTICE PLATFORM                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  MACRO-FASE C (Diferenciacion)                                      │
│  ┌──────────────────┐  ┌──────────────────┐                        │
│  │ jaraba_legal_     │  │ jaraba_legal_     │                        │
│  │ lexnet            │  │ templates         │                        │
│  │ (LexNET API)      │  │ (Escritos IA)     │                        │
│  └────────┬─────────┘  └────────┬─────────┘                        │
│           │                      │                                   │
│  MACRO-FASE B (Productividad)    │                                   │
│  ┌──────────────────┐  ┌────────┴─────────┐                        │
│  │ jaraba_legal_     │  │ jaraba_legal_     │                        │
│  │ vault             │  │ billing           │                        │
│  │ (Buzon+Portal)    │  │ (Facturas+Horas)  │                        │
│  └────────┬─────────┘  └────────┬─────────┘                        │
│           │                      │                                   │
│  MACRO-FASE A (Nucleo)           │                                   │
│  ┌──────────────────┐  ┌────────┴─────────┐                        │
│  │ jaraba_legal_     │  │ jaraba_legal_     │                        │
│  │ cases      ◄──────┼──┤ calendar          │                        │
│  │ (Expedientes)     │  │ (Agenda+Plazos)   │                        │
│  └────────┬─────────┘  └──────────────────┘                        │
│           │                                                          │
│  EXISTENTE│                                                          │
│  ┌────────┴──────────────────────────────────────────────────┐      │
│  │ jaraba_legal_intelligence (Legal Research + AI + NLP)      │      │
│  │ · LegalSearchService · LegalCopilotAgent · CitationGraph  │      │
│  └───────────────────────────────────────────────────────────┘      │
│                                                                     │
│  INFRAESTRUCTURA TRANSVERSAL                                        │
│  ┌───────────────────────────────────────────────────────────┐      │
│  │ ecosistema_jaraba_core (FeatureGate, HealthScore, Journey) │      │
│  │ jaraba_facturae (Facturae 3.2.2 XML) · jaraba_email (MJML)│      │
│  │ jaraba_credentials (libsodium) · ecosistema_jaraba_theme   │      │
│  └───────────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.2 Estructura de Directorios Estandar

```
web/modules/custom/jaraba_legal_{modulo}/
├── jaraba_legal_{modulo}.info.yml
├── jaraba_legal_{modulo}.module
├── jaraba_legal_{modulo}.install
├── jaraba_legal_{modulo}.services.yml
├── jaraba_legal_{modulo}.routing.yml
├── jaraba_legal_{modulo}.links.menu.yml
├── jaraba_legal_{modulo}.links.task.yml
├── jaraba_legal_{modulo}.links.action.yml
├── jaraba_legal_{modulo}.permissions.yml
├── jaraba_legal_{modulo}.libraries.yml
├── config/
│   ├── install/
│   └── schema/
│       └── jaraba_legal_{modulo}.schema.yml
├── src/
│   ├── Entity/
│   ├── Controller/
│   ├── Service/
│   ├── Form/
│   ├── Access/
│   └── EventSubscriber/
├── templates/
│   ├── page--legal-{modulo}.html.twig
│   └── partials/
├── scss/
├── css/
├── js/
├── images/icons/
└── package.json
```

---

## 5. Estado por Fases

| Macro-Fase | Sub-Fase | Descripcion | Modulo | Entidades | Estado | Dependencia |
|------------|----------|-------------|--------|-----------|--------|-------------|
| **A** | A1 | Gestion de Expedientes | jaraba_legal_cases | ClientCase, CaseActivity, ClientInquiry, InquiryTriage | ⬜ | jaraba_legal_intelligence |
| **A** | A2 | Agenda Juridica y Tributaria | jaraba_legal_calendar | LegalDeadline, CourtHearing, CalendarConnection, SyncedCalendar, ExternalEventCache | ⬜ | A1 |
| **A** | A3 | Integracion Expedientes ↔ Legal Intelligence | jaraba_legal_intelligence (mod) | — | ⬜ | A1 |
| **B** | B1 | Buzon de Confianza | jaraba_legal_vault | SecureDocument, DocumentAccess, DocumentAuditLog | ⬜ | A1 |
| **B** | B2 | Portal Cliente Documental | jaraba_legal_vault (ext) | DocumentRequest, DocumentDelivery | ⬜ | B1 |
| **B** | B3 | Facturacion Legal + Time Tracking | jaraba_legal_billing | LegalInvoice, InvoiceLine, TimeEntry, CreditNote, ServiceCatalogItem, Quote, QuoteLineItem | ⬜ | A1, jaraba_facturae |
| **C** | C1 | Integracion LexNET | jaraba_legal_lexnet | LexnetNotification, LexnetSubmission | ⬜ | A1, A2 |
| **C** | C2 | Plantillas Escritos IA | jaraba_legal_templates | LegalTemplate, GeneratedDocument | ⬜ | A1, C1 |
| **C** | C3 | Copilot Legal v2 (2 modos nuevos) | jaraba_legal_intelligence (mod) | — | ⬜ | A1, C2 |

**Diagrama de dependencias:**

```
A1 (Expedientes) ──────────────────────┐
  │                                     │
  ├── A2 (Agenda) ──────────────────────┤
  │                                     │
  ├── A3 (Integracion Legal Intel) ─────┤
  │                                     │
  ├── B1 (Vault) ── B2 (Portal) ───────┤── Independientes entre macro-fases
  │                                     │   pero dependen de A1
  ├── B3 (Billing) ─────────────────────┤
  │                                     │
  ├── C1 (LexNET) ─────────────────────┤
  │     │                               │
  │     └── C2 (Plantillas) ── C3 ──────┘
  │
  └── TODAS las fases dependen de A1 (ClientCase es la entidad pivote)
```

---

## 6. MACRO-FASE A: Nucleo Operativo — Expedientes + Agenda

### 6.1 FASE A1: jaraba_legal_cases — Gestion de Expedientes

**Justificacion:** El expediente es la entidad pivote de todo el ecosistema. Sin expedientes, el profesional no tiene razon para vivir en JarabaLex. `LegalCitation.expediente_id` ya apunta a esta entidad (integer FK). Los endpoints `/api/v1/legal/expediente/{id}/references` y `attachToExpediente()` ya estan implementados esperando esta entidad.

#### 6.1.1 Entidad `ClientCase` (Expediente Digital)

**Tipo:** ContentEntity
**ID:** `client_case`
**Base table:** `client_case`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK autoincremental |
| `uuid` | uuid | Si | Identificador universal unico |
| `case_number` | string(32) | Si | Auto-generado: EXP-YYYY-NNNN (unico por tenant) |
| `tenant_id` | entity_reference (group) | Si | Aislamiento multi-tenant obligatorio |
| `provider_id` | entity_reference (user) | Si | Profesional responsable del expediente |
| `client_id` | entity_reference (user) | Si | Cliente del expediente |
| `title` | string(255) | Si | Titulo descriptivo del caso |
| `description` | text_long | No | Descripcion detallada de los hechos |
| `case_type_tid` | entity_reference (taxonomy_term) | No | Tipo: Fiscal, Laboral, Civil, Mercantil, Penal, Administrativo |
| `practice_area_tid` | entity_reference (taxonomy_term) | No | Area de practica especifica |
| `client_access_token` | string(64) | Si | Token unico para acceso al portal cliente |
| `status` | list_string | Si | active, on_hold, completed, archived. Default: active |
| `priority` | list_string | Si | low, normal, high, urgent. Default: normal |
| `opposing_party` | string(255) | No | Nombre de la parte contraria |
| `court` | string(255) | No | Organo judicial (si procede) |
| `court_case_number` | string(64) | No | Numero de procedimiento judicial |
| `jurisdiction_tid` | entity_reference (taxonomy_term) | No | Jurisdiccion (reutiliza legal_jurisdiction existente) |
| `due_date` | datetime | No | Fecha limite general del expediente |
| `opened_at` | datetime | Si | Fecha de apertura |
| `closed_at` | datetime | No | Fecha de cierre |
| `estimated_value` | decimal(12,2) | No | Valor economico estimado del asunto |
| `billing_model` | list_string | No | fixed, hourly, milestone, success_fee, retainer, subscription |
| `created` | created | Si | Timestamp de creacion |
| `changed` | changed | Si | Timestamp de ultima modificacion |

**Indices DB:** `tenant_id`, `provider_id`, `client_id`, `status`, `case_number` (unique), `case_type_tid`.

**Handlers:**

| Handler | Clase |
|---------|-------|
| list_builder | `ClientCaseListBuilder` |
| views_data | `ClientCaseViewsData` |
| form (default/add/edit) | `ClientCaseForm` |
| form (delete) | `ContentEntityDeleteForm` |
| access | `ClientCaseAccessControlHandler` |
| route_provider | `AdminHtmlRouteProvider` |

**Metodo de negocio — auto-generacion de case_number:**

```php
public function preSave(EntityStorageInterface $storage): void {
  parent::preSave($storage);
  if ($this->isNew() && empty($this->get('case_number')->value)) {
    $year = date('Y');
    $tenantId = $this->get('tenant_id')->target_id ?? 0;
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('case_number', "EXP-{$year}-%", 'LIKE')
      ->count()
      ->execute();
    $this->set('case_number', sprintf('EXP-%s-%04d', $year, (int) $count + 1));
  }
}
```

#### 6.1.2 Entidad `CaseActivity` (Timeline de Actuaciones)

**Tipo:** ContentEntity (append-only)
**ID:** `case_activity`
**Base table:** `case_activity`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `case_id` | entity_reference (client_case) | Si | Expediente asociado |
| `activity_type` | list_string | Si | Tipos: case_opened, note_added, document_uploaded, document_requested, document_approved, document_rejected, document_delivered, deadline_created, deadline_completed, hearing_scheduled, citation_attached, invoice_created, status_changed, lexnet_notification, lexnet_submission |
| `actor_id` | entity_reference (user) | No | Quien realizo la accion (NULL = sistema) |
| `actor_role` | list_string | Si | provider, client, system |
| `description` | string(255) | Si | Descripcion legible de la accion |
| `details` | map | No | JSON con datos adicionales contextuales |
| `is_visible_to_client` | boolean | Si | Si se muestra en portal cliente. Default: TRUE |
| `created` | created | Si | Timestamp |

**Indices DB:** `case_id`, `activity_type`, `created`.

#### 6.1.3 Entidad `ClientInquiry` (Consulta Entrante)

**Tipo:** ContentEntity
**ID:** `client_inquiry`
**Base table:** `client_inquiry`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `tenant_id` | entity_reference (group) | Si | Tenant |
| `source` | list_string | Si | web_form, email, whatsapp, phone, chat |
| `source_reference` | string(255) | No | ID del mensaje original (email ID, WA msg ID) |
| `client_name` | string(255) | Si | Nombre del consultante |
| `client_email` | string(255) | No | Email |
| `client_phone` | string(24) | No | Telefono |
| `existing_client_id` | entity_reference (user) | No | Cliente existente (si aplica) |
| `subject` | string(255) | No | Asunto |
| `message` | text_long | Si | Texto de la consulta |
| `attachments` | map | No | JSON array [{name, mime_type, size, path}] |
| `status` | list_string | Si | new, triaged, assigned, in_progress, converted, closed |
| `assigned_to` | entity_reference (user) | No | Profesional asignado |
| `converted_to_case_id` | entity_reference (client_case) | No | Expediente creado (si se convirtio) |
| `received_at` | datetime | Si | Fecha de recepcion |
| `created` | created | Si | Timestamp |

#### 6.1.4 Entidad `InquiryTriage` (Triaje IA)

**Tipo:** ContentEntity
**ID:** `inquiry_triage`
**Base table:** `inquiry_triage`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `inquiry_id` | entity_reference (client_inquiry) | Si | Consulta triada (1:1) |
| `category_tid` | entity_reference (taxonomy_term) | No | Categoria detectada |
| `category_confidence` | list_string | Si | high, medium, low |
| `urgency_score` | integer | Si | 1-5 (1=minimo, 5=critico) |
| `urgency_reasons` | map | No | JSON array de razones |
| `extracted_entities` | map | No | JSON {dates, amounts, parties, documents, locations} |
| `suggested_provider_id` | entity_reference (user) | No | Profesional sugerido por IA |
| `summary` | string(500) | Si | Resumen 1-2 frases generado por IA |
| `clarification_questions` | map | No | JSON array de preguntas sugeridas |
| `needs_review` | boolean | Si | Requiere revision manual. Default: FALSE |
| `model_version` | string(32) | Si | Version del modelo IA usado |
| `processing_time_ms` | integer | Si | Tiempo de procesamiento en ms |
| `created` | created | Si | Timestamp |

#### 6.1.5 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_legal_cases.case_manager` | `CaseManagerService` | `createCase()`, `updateStatus()`, `getCaseProgress()`, `generateCaseNumber()` | CRUD y lifecycle de expedientes |
| `jaraba_legal_cases.activity_logger` | `CaseActivityLoggerService` | `log()`, `getTimeline()`, `getClientVisibleTimeline()` | Timeline append-only de actuaciones |
| `jaraba_legal_cases.triage` | `CaseTriageService` | `processInquiry()`, `buildContext()`, `buildPrompt()` | Triaje IA de consultas entrantes (Gemini 2.0 Flash, temperature=0.1) |
| `jaraba_legal_cases.inquiry_manager` | `InquiryManagerService` | `createFromWebhook()`, `assignToProvider()`, `convertToCase()` | Gestion de consultas entrantes |

**Inyeccion de dependencias (`jaraba_legal_cases.services.yml`):**

```yaml
services:
  logger.channel.jaraba_legal_cases:
    parent: logger.channel_base
    arguments: ['jaraba_legal_cases']

  jaraba_legal_cases.case_manager:
    class: Drupal\jaraba_legal_cases\Service\CaseManagerService
    arguments:
      - '@entity_type.manager'
      - '@ecosistema_jaraba_core.tenant_context'
      - '@jaraba_legal_cases.activity_logger'
      - '@logger.channel.jaraba_legal_cases'

  jaraba_legal_cases.activity_logger:
    class: Drupal\jaraba_legal_cases\Service\CaseActivityLoggerService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_legal_cases'

  jaraba_legal_cases.triage:
    class: Drupal\jaraba_legal_cases\Service\CaseTriageService
    arguments:
      - '@ai.provider'
      - '@entity_type.manager'
      - '@config.factory'
      - '@logger.channel.jaraba_legal_cases'

  jaraba_legal_cases.inquiry_manager:
    class: Drupal\jaraba_legal_cases\Service\InquiryManagerService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_legal_cases.case_manager'
      - '@jaraba_legal_cases.triage'
      - '@logger.channel.jaraba_legal_cases'
```

#### 6.1.6 Controllers y API Endpoints

**Rutas principales (`jaraba_legal_cases.routing.yml`):**

```yaml
# Frontend zero-region
jaraba_legal_cases.dashboard:
  path: '/legal/cases'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesDashboardController::dashboard'
    _title: 'Mis Expedientes'
  requirements:
    _permission: 'access legal cases'

jaraba_legal_cases.case_detail:
  path: '/legal/cases/{client_case}'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CaseDetailController::view'
    _title_callback: '\Drupal\jaraba_legal_cases\Controller\CaseDetailController::title'
  requirements:
    _permission: 'access legal cases'

# REST API
jaraba_legal_cases.api.list:
  path: '/api/v1/legal/cases'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::list'
  requirements:
    _permission: 'access legal cases api'
  methods: [GET]

jaraba_legal_cases.api.create:
  path: '/api/v1/legal/cases'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::create'
  requirements:
    _permission: 'manage legal cases'
  methods: [POST]

jaraba_legal_cases.api.detail:
  path: '/api/v1/legal/cases/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::detail'
  requirements:
    _permission: 'access legal cases api'
  methods: [GET]

jaraba_legal_cases.api.update:
  path: '/api/v1/legal/cases/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::update'
  requirements:
    _permission: 'manage legal cases'
  methods: [PATCH]

jaraba_legal_cases.api.delete:
  path: '/api/v1/legal/cases/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::delete'
  requirements:
    _permission: 'manage legal cases'
  methods: [DELETE]

jaraba_legal_cases.api.activity:
  path: '/api/v1/legal/cases/{uuid}/activity'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\CasesApiController::activity'
  requirements:
    _permission: 'access legal cases api'
  methods: [GET]

# Inquiries API
jaraba_legal_cases.api.inquiries.create:
  path: '/api/v1/legal/inquiries'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\InquiryApiController::create'
  requirements:
    _permission: 'manage legal inquiries'
  methods: [POST]

jaraba_legal_cases.api.inquiries.list:
  path: '/api/v1/legal/inquiries'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\InquiryApiController::list'
  requirements:
    _permission: 'manage legal inquiries'
  methods: [GET]

jaraba_legal_cases.api.inquiries.triage:
  path: '/api/v1/legal/inquiries/{uuid}/triage'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\InquiryApiController::triage'
  requirements:
    _permission: 'manage legal inquiries'
  methods: [POST]

jaraba_legal_cases.api.inquiries.convert:
  path: '/api/v1/legal/inquiries/{uuid}/convert/case'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\InquiryApiController::convertToCase'
  requirements:
    _permission: 'manage legal cases'
  methods: [POST]

jaraba_legal_cases.api.inquiries.assign:
  path: '/api/v1/legal/inquiries/{uuid}/assign'
  defaults:
    _controller: '\Drupal\jaraba_legal_cases\Controller\InquiryApiController::assign'
  requirements:
    _permission: 'manage legal inquiries'
  methods: [PATCH]
```

**Resumen de endpoints:** 11 endpoints (5 cases CRUD+delete + 5 inquiries + 1 activity).

#### 6.1.7 Templates y Parciales Twig

| Template | Archivo | Proposito |
|----------|---------|-----------|
| Pagina dashboard | `page--legal-cases.html.twig` | Zero-region, grid expedientes activos + plazos proximos |
| Pagina detalle | `page--legal-case-detail.html.twig` | Zero-region, timeline + documentos + plazos + citas |
| Parcial: case card | `partials/_case-card.html.twig` | Tarjeta de expediente con status badge + deadline |
| Parcial: activity entry | `partials/_activity-entry.html.twig` | Entrada de timeline con icono + actor + timestamp |
| Parcial: inquiry card | `partials/_inquiry-card.html.twig` | Tarjeta de consulta con urgency badge + triage |
| Parcial: empty state | `partials/_cases-empty-state.html.twig` | Estado vacio con CTA "Crear primer expediente" |

#### 6.1.8 Hooks

```php
/**
 * Implements hook_preprocess_html().
 * Añade body classes para paginas de expedientes.
 */
function jaraba_legal_cases_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route, 'jaraba_legal_cases.')) {
    $variables['attributes']['class'][] = 'vertical-jarabalex';
    $variables['attributes']['class'][] = 'page--legal-cases';
    if (str_contains($route, 'case_detail')) {
      $variables['attributes']['class'][] = 'page--legal-case-detail';
    }
  }
}

/**
 * Implements hook_entity_insert().
 * Registra actividad al crear entidades relacionadas.
 */
function jaraba_legal_cases_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'client_case') {
    \Drupal::service('jaraba_legal_cases.activity_logger')->log(
      (int) $entity->id(), 'case_opened', NULL, 'provider',
      (string) new TranslatableMarkup('Expediente @number abierto', [
        '@number' => $entity->get('case_number')->value,
      ])
    );
  }
}

/**
 * Implements hook_cron().
 * Auto-reminders para consultas sin responder >24h.
 */
function jaraba_legal_cases_cron(): void {
  $lastRun = \Drupal::state()->get('jaraba_legal_cases.cron_stale_inquiries', 0);
  if (\Drupal::time()->getRequestTime() - $lastRun < 86400) {
    return;
  }
  // Buscar inquiries en status 'triaged' o 'assigned' con received_at > 24h.
  // Enviar alerta al profesional asignado.
  \Drupal::state()->set('jaraba_legal_cases.cron_stale_inquiries', \Drupal::time()->getRequestTime());
}
```

#### 6.1.9 Permisos

```yaml
access legal cases:
  title: 'Acceder a expedientes legales'
  description: 'Ver lista y detalle de expedientes propios.'
access legal cases api:
  title: 'Acceso API expedientes'
  description: 'Acceso programatico a endpoints REST de expedientes.'
manage legal cases:
  title: 'Gestionar expedientes legales'
  description: 'Crear, editar y cambiar estado de expedientes.'
manage legal inquiries:
  title: 'Gestionar consultas entrantes'
  description: 'Ver, asignar y convertir consultas a expedientes.'
administer legal cases:
  title: 'Administrar modulo de expedientes'
  description: 'Configuracion avanzada del modulo.'
  restrict access: true
```

---

### 6.2 FASE A2: jaraba_legal_calendar — Agenda Juridica y Tributaria

**Justificacion:** Los plazos procesales son criticos para un abogado — un plazo perdido genera responsabilidad profesional. La agenda tributaria es igualmente critica para asesores fiscales. Ningun competidor espanol ofrece computo automatico de plazos procesales (LEC Art. 130-136) integrado con IA.

#### 6.2.1 Entidad `LegalDeadline` (Plazo Legal)

**Tipo:** ContentEntity
**ID:** `legal_deadline`
**Base table:** `legal_deadline`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `tenant_id` | entity_reference (group) | Si | Tenant |
| `case_id` | entity_reference (client_case) | Si | Expediente asociado |
| `title` | string(255) | Si | Descripcion del plazo |
| `deadline_type` | list_string | Si | procesal, tributario, contractual, administrativo, custom |
| `legal_basis` | string(255) | No | Base legal (ej: "LEC Art. 405", "LGT Art. 48") |
| `due_date` | datetime | Si | Fecha de vencimiento calculada |
| `is_computed` | boolean | Si | Si se calculo automaticamente. Default: FALSE |
| `base_date` | datetime | No | Fecha base para computo (ej: fecha notificacion) |
| `computation_rule` | string(128) | No | Regla de computo (ej: "20_dias_habiles", "30_dias_naturales") |
| `alert_days_before` | integer | Si | Dias de antelacion para alerta. Default: 3 |
| `assigned_to` | entity_reference (user) | No | Profesional responsable |
| `status` | list_string | Si | pending, in_progress, completed, overdue, cancelled |
| `completed_at` | datetime | No | Cuando se completo |
| `notes` | text_long | No | Notas internas |
| `created` | created | Si | Timestamp |
| `changed` | changed | Si | Timestamp |

**Indices DB:** `tenant_id`, `case_id`, `due_date`, `status`, `deadline_type`.

#### 6.2.2 Entidad `CourtHearing` (Senalamiento Judicial)

**Tipo:** ContentEntity
**ID:** `court_hearing`
**Base table:** `court_hearing`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `tenant_id` | entity_reference (group) | Si | Tenant |
| `case_id` | entity_reference (client_case) | Si | Expediente |
| `hearing_type` | list_string | Si | vista_oral, audiencia_previa, juicio, comparecencia, declaracion, reconocimiento, medidas_cautelares, ejecucion |
| `court` | string(255) | Si | Organo judicial |
| `courtroom` | string(64) | No | Sala |
| `scheduled_at` | datetime | Si | Fecha y hora del senalamiento |
| `estimated_duration_minutes` | integer | No | Duracion estimada |
| `address` | text_long | No | Direccion del juzgado |
| `is_virtual` | boolean | Si | Si es telematico. Default: FALSE |
| `virtual_url` | string(512) | No | URL de videoconferencia |
| `status` | list_string | Si | scheduled, confirmed, postponed, completed, cancelled |
| `outcome` | text_long | No | Resultado de la vista |
| `notes` | text_long | No | Notas de preparacion |
| `created` | created | Si | Timestamp |
| `changed` | changed | Si | Timestamp |

#### 6.2.3 Entidad `CalendarConnection` (Conexion Google/Outlook)

**Tipo:** ContentEntity
**ID:** `calendar_connection`
**Base table:** `calendar_connection`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `provider_id` | entity_reference (user) | Si | Profesional |
| `platform` | list_string | Si | google, microsoft |
| `account_email` | string(255) | Si | Email de la cuenta vinculada |
| `access_token` | text_long | Si | Token cifrado (libsodium secretbox) |
| `refresh_token` | text_long | No | Refresh token cifrado |
| `token_expires_at` | datetime | Si | Expiracion del access token |
| `scopes` | map | Si | JSON array de scopes OAuth concedidos |
| `status` | list_string | Si | active, expired, revoked, error |
| `last_sync_at` | datetime | No | Ultima sincronizacion exitosa |
| `sync_errors` | integer | Si | Errores consecutivos. Default: 0 |
| `created` | created | Si | Timestamp |

#### 6.2.4 Entidades `SyncedCalendar` y `ExternalEventCache`

**`SyncedCalendar`** — Calendario vinculado para sincronizacion (base table: `synced_calendar`):

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `connection_id`, `external_calendar_id`, `calendar_name` | — | Identificacion |
| `sync_direction` | list_string | read, write, both |
| `is_primary` | boolean | Calendario principal para escritura |
| `webhook_subscription_id` | string(255) | ID suscripcion webhook externo |
| `sync_token` | string(255) | Token para sync incremental |
| `is_enabled` | boolean | Activo/inactivo |

**`ExternalEventCache`** — Cache de eventos externos (base table: `external_event_cache`):

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `synced_calendar_id`, `external_event_id` | — | Identificacion |
| `start_datetime`, `end_datetime` | datetime | Solo tiempo (sin titulo/descripcion por privacidad) |
| `is_all_day` | boolean | Evento de dia completo |
| `status` | list_string | confirmed, tentative, cancelled |
| `transparency` | list_string | opaque (busy), transparent (free) |
| `last_synced_at` | datetime | Ultima actualizacion |

#### 6.2.5 Services

| Service ID | Clase | Metodos Clave | Descripcion |
|-----------|-------|---------------|-------------|
| `jaraba_legal_calendar.deadline_calculator` | `DeadlineCalculatorService` | `computeDeadline()`, `getBusinessDays()`, `isBusinessDay()` | Computo de plazos procesales (LEC 130-136) y tributarios (LGT 48) con calendario laboral |
| `jaraba_legal_calendar.calendar_sync` | `CalendarSyncService` | `syncFromExternal()`, `pushToExternal()`, `deleteExternalEvent()` | Sincronizacion bidireccional Google Calendar / Outlook |
| `jaraba_legal_calendar.agenda` | `LegalAgendaService` | `getUpcomingDeadlines()`, `getHearings()`, `getDayView()`, `getWeekView()` | Agenda unificada: plazos + vistas + eventos externos |
| `jaraba_legal_calendar.alerts` | `DeadlineAlertService` | `checkUpcomingDeadlines()`, `sendAlert()` | Alertas de plazos proximos via email/push |

**Computo de plazos procesales — `DeadlineCalculatorService`:**

```php
/**
 * Calcula fecha de vencimiento segun regla de computo.
 *
 * Reglas implementadas:
 * - dias_habiles: Excluye sabados, domingos, festivos nacionales/CCAA
 * - dias_naturales: Incluye todos los dias
 * - meses: Fecha equivalente del mes siguiente(s)
 * - agosto_inhabil: Agosto es inhabil para plazos procesales (LEC 130.2)
 *
 * @param \DateTimeInterface $baseDate Fecha base (notificacion, publicacion...)
 * @param string $rule Regla: "20_dias_habiles", "30_dias_naturales", "1_mes"
 * @param string $jurisdiction Jurisdiccion para calendario laboral
 */
public function computeDeadline(\DateTimeInterface $baseDate, string $rule, string $jurisdiction = 'ES'): \DateTimeImmutable {
  // Parse rule: extract count + unit (habiles/naturales/meses)
  // Apply exclusions: weekends, national holidays, CCAA holidays
  // August is non-working for court deadlines (LEC 130.2)
  // Return computed date
}
```

**Reglas de computo precargadas (config entity):**

| ID | Nombre | Regla | Base Legal |
|----|--------|-------|-----------|
| `contestacion_demanda` | Contestacion a la demanda | 20 dias habiles | LEC Art. 405 |
| `recurso_apelacion` | Recurso de apelacion | 20 dias habiles | LEC Art. 458 |
| `recurso_casacion` | Recurso de casacion | 20 dias habiles | LEC Art. 479 |
| `recurso_reposicion` | Recurso de reposicion | 5 dias habiles | LEC Art. 452 |
| `modelo_303` | Modelo 303 IVA trimestral | 20 dias naturales (mes siguiente) | LGT |
| `modelo_200` | Impuesto Sociedades | 25 dias naturales (julio) | LIS Art. 124 |
| `modelo_100` | IRPF declaracion anual | 30 junio | LIRPF |
| `recurso_economico_admin` | Recurso economico-administrativo | 1 mes natural | LGT Art. 235 |
| `recurso_contencioso` | Recurso contencioso-administrativo | 2 meses naturales | LJCA Art. 46 |

#### 6.2.6 API Endpoints (resumen)

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/api/v1/legal/calendar/deadlines` | Listar plazos con filtros |
| POST | `/api/v1/legal/calendar/deadlines` | Crear plazo manual |
| POST | `/api/v1/legal/calendar/deadlines/compute` | Calcular plazo automaticamente |
| PATCH | `/api/v1/legal/calendar/deadlines/{uuid}` | Actualizar plazo |
| POST | `/api/v1/legal/calendar/deadlines/{uuid}/complete` | Marcar completado |
| GET | `/api/v1/legal/calendar/hearings` | Listar senalados |
| POST | `/api/v1/legal/calendar/hearings` | Crear senalamiento |
| PATCH | `/api/v1/legal/calendar/hearings/{uuid}` | Actualizar |
| GET | `/api/v1/legal/calendar/agenda` | Vista unificada (plazos + vistas + externos) |
| GET | `/api/v1/legal/calendar/agenda/{year}/{month}` | Vista mensual |
| GET | `/api/v1/legal/calendar/connections` | Conexiones calendario |
| GET | `/api/v1/legal/calendar/google/auth` | Iniciar OAuth Google |
| GET | `/api/v1/legal/calendar/google/callback` | Callback OAuth Google |
| GET | `/api/v1/legal/calendar/microsoft/auth` | Iniciar OAuth Microsoft |
| GET | `/api/v1/legal/calendar/microsoft/callback` | Callback OAuth Microsoft |
| DELETE | `/api/v1/legal/calendar/connections/{id}` | Desconectar cuenta |
| POST | `/api/v1/legal/calendar/sync/{calendarId}/refresh` | Forzar sync manual |

**Total: 17 endpoints.**

---

### 6.3 FASE A3: Integracion Expedientes ↔ Legal Intelligence

**Justificacion:** Conectar el modulo de expedientes con la busqueda semantica existente, convirtiendo `LegalCitation.expediente_id` de integer FK a entity_reference real, y enriqueciendo el copilot con contexto del expediente.

**Cambios en modulos existentes:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_legal_intelligence/src/Entity/LegalCitation.php` | Migrar `expediente_id` de `integer` a `entity_reference` apuntando a `client_case` |
| `jaraba_legal_intelligence/jaraba_legal_intelligence.install` | `hook_update_10004()` para migrar campo integer → entity_reference |
| `jaraba_legal_intelligence/src/Service/LegalCitationService.php` | Actualizar `attachToExpediente()` para usar entity_reference |
| `jaraba_legal_intelligence/src/Agent/LegalCopilotAgent.php` | Añadir modo `case_assistant` (ver Fase C3) |
| `ecosistema_jaraba_core/src/Service/AvatarNavigationService.php` | Añadir item "Expedientes" al avatar `legal_professional` |

---

## 7. MACRO-FASE B: Productividad Completa — Documentos + Facturacion

### 7.1 FASE B1: jaraba_legal_vault — Buzon de Confianza

**Justificacion:** Diferenciador competitivo. Cifrado zero-knowledge AES-256-GCM donde el servidor nunca ve los datos en claro. Ningun competidor espanol ofrece esto. Cumple RGPD, LOPD-GDD, y secreto profesional (Art. 542.3 LOPJ).

#### 7.1.1 Entidad `SecureDocument` (Documento Cifrado)

**Tipo:** ContentEntity
**ID:** `secure_document`
**Base table:** `secure_document`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Si | PK |
| `uuid` | uuid | Si | UUID |
| `owner_id` | entity_reference (user) | Si | Profesional propietario |
| `tenant_id` | entity_reference (group) | Si | Tenant |
| `case_id` | entity_reference (client_case) | No | Expediente vinculado |
| `title` | string(255) | Si | Titulo del documento |
| `description` | text_long | No | Descripcion |
| `original_filename` | string(255) | Si | Nombre original del archivo |
| `mime_type` | string(128) | Si | Tipo MIME |
| `file_size` | integer | Si | Tamano en bytes (bigint) |
| `storage_path` | string(512) | Si | Ruta de almacenamiento cifrado |
| `content_hash` | string(64) | Si | SHA-256 del contenido original (deduplicacion) |
| `encrypted_dek` | text_long | Si | DEK cifrado con AES-256-KW (wrapped) |
| `encryption_iv` | string(32) | Si | IV de 12 bytes en hexadecimal |
| `encryption_tag` | string(32) | Si | GCM auth tag de 16 bytes en hex |
| `category_tid` | entity_reference (taxonomy_term) | No | Categoria documental |
| `version` | integer | Si | Numero de version. Default: 1 |
| `parent_version_id` | entity_reference (secure_document) | No | Version anterior |
| `is_signed` | boolean | Si | Firmado digitalmente. Default: FALSE |
| `expires_at` | datetime | No | Fecha de expiracion |
| `status` | list_string | Si | draft, active, archived, deleted |
| `created` | created | Si | Timestamp |
| `changed` | changed | Si | Timestamp |

**Indices DB:** `tenant_id`, `owner_id`, `case_id`, `content_hash`, `status`.

#### 7.1.2 Entidad `DocumentAccess` (Control de Acceso Granular)

**Base table:** `document_access`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `document_id` (ref: secure_document), `grantee_id` (ref: user), `grantee_email` | — | Identificacion |
| `access_token` | string(64) | Token unico para acceso via enlace (UNIQUE, INDEX) |
| `encrypted_dek` | text_long | DEK re-cifrado para el destinatario (RSA-OAEP) |
| `permissions` | map | JSON: ["view", "download", "sign"] |
| `max_downloads` | integer | Limite de descargas (NULL = ilimitado) |
| `download_count` | integer | Contador actual. Default: 0 |
| `expires_at` | datetime | Expiracion del acceso |
| `requires_auth` | boolean | Requiere autenticacion. Default: TRUE |
| `is_revoked` | boolean | Revocado. Default: FALSE |
| `granted_by` | entity_reference (user) | Quien concedio el acceso |
| `created` | created | Timestamp |

#### 7.1.3 Entidad `DocumentAuditLog` (Log Inmutable)

**Base table:** `document_audit_log`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` (bigserial), `document_id`, `action` | — | created, viewed, downloaded, shared, signed, revoked, deleted |
| `actor_id`, `actor_ip` | — | Quien y desde donde |
| `details` | map | JSON contextual |
| `hash_chain` | string(64) | SHA-256(prev_hash + current_record) — cadena de integridad |
| `created` | created (microsegundos) | Timestamp de alta precision |

**Regla critica:** Append-only. Sin UPDATE ni DELETE. Cada `hash_chain` = SHA-256 del hash anterior + datos del registro actual. Cualquier modificacion rompe la cadena y es detectable.

#### 7.1.4 Services

| Service ID | Clase | Metodos Clave |
|-----------|-------|---------------|
| `jaraba_legal_vault.document_vault` | `DocumentVaultService` | `store()`, `retrieve()`, `createVersion()`, `softDelete()` |
| `jaraba_legal_vault.document_access` | `DocumentAccessService` | `shareDocument()`, `revokeAccess()`, `revokeAll()`, `validateToken()` |
| `jaraba_legal_vault.audit_log` | `VaultAuditLogService` | `log()`, `verifyIntegrity()`, `getAuditTrail()` |
| `jaraba_legal_vault.encryption` | `VaultEncryptionService` | `generateDek()`, `wrapDek()`, `unwrapDek()`, `reEncryptForRecipient()` |

**API Endpoints (15):**

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| POST | `/api/v1/vault/documents` | Subir documento cifrado |
| GET | `/api/v1/vault/documents` | Listar documentos propios |
| GET | `/api/v1/vault/documents/{uuid}` | Obtener documento cifrado + DEK |
| DELETE | `/api/v1/vault/documents/{uuid}` | Soft delete |
| POST | `/api/v1/vault/documents/{uuid}/versions` | Subir nueva version |
| GET | `/api/v1/vault/documents/{uuid}/versions` | Listar versiones |
| POST | `/api/v1/vault/documents/{uuid}/share` | Compartir con usuario |
| GET | `/api/v1/vault/documents/{uuid}/access` | Listar accesos compartidos |
| DELETE | `/api/v1/vault/access/{id}` | Revocar acceso |
| GET | `/api/v1/vault/documents/{uuid}/audit` | Ver audit log |
| GET | `/api/v1/vault/shared` | Documentos compartidos conmigo |
| GET | `/api/v1/vault/access/token/{token}` | Acceso via enlace (publico + token) |
| GET | `/api/v1/vault/export` | Exportar todos (portabilidad RGPD) |
| GET | `/api/v1/vault/export?format=json` | Exportar con metadata |
| DELETE | `/api/v1/vault/documents/{id}/access/all` | Revocar todos los accesos (oposicion RGPD) |

---

### 7.2 FASE B2: Portal Cliente Documental

**Extension de `jaraba_legal_vault` con workflow de solicitud/entrega de documentos.**

#### 7.2.1 Entidad `DocumentRequest` (Solicitud de Documento)

**Base table:** `document_request`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `case_id` (ref: client_case) | — | Identificacion |
| `document_type_tid` | entity_reference (taxonomy_term) | Tipo de documento solicitado |
| `title` | string(255) | Nombre del documento pedido |
| `instructions` | text_long | Instrucciones para el cliente |
| `is_required` | boolean | Obligatorio. Default: TRUE |
| `deadline` | datetime | Fecha limite de entrega |
| `status` | list_string | pending, uploaded, reviewing, approved, rejected |
| `uploaded_document_id` | entity_reference (secure_document) | Documento subido por el cliente |
| `reviewed_by` | entity_reference (user) | Quien reviso |
| `rejection_reason` | text_long | Motivo de rechazo |
| `reminder_count` | integer | Recordatorios enviados. Default: 0 |
| `created` | created | Timestamp |

#### 7.2.2 Entidad `DocumentDelivery` (Puesta a Disposicion)

**Base table:** `document_delivery`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `case_id`, `document_id` (ref: secure_document) | — | Identificacion |
| `delivered_by`, `recipient_id` | entity_reference (user) | Emisor y receptor |
| `message` | text_long | Mensaje adjunto |
| `notification_channels` | map | JSON: ["email", "whatsapp", "push"] |
| `requires_acknowledgment` | boolean | Requiere acuse de recibo |
| `requires_signature` | boolean | Requiere firma digital |
| `status` | list_string | sent, notified, viewed, downloaded, acknowledged, signed |
| `viewed_at`, `downloaded_at`, `acknowledged_at` | datetime | Timestamps de tracking |
| `download_count` | integer | Descargas. Default: 0 |
| `created` | created | Timestamp |

**API Endpoints Portal Cliente (token-based, 7):**

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/api/v1/portal/{token}` | Datos del expediente para portal cliente |
| GET | `/api/v1/portal/{token}/requests` | Documentos pendientes de subir |
| POST | `/api/v1/portal/{token}/requests/{id}/upload` | Subir documento solicitado |
| GET | `/api/v1/portal/{token}/deliveries` | Documentos disponibles para descargar |
| GET | `/api/v1/portal/{token}/deliveries/{id}/download` | Descargar documento entregado |
| POST | `/api/v1/portal/{token}/deliveries/{id}/acknowledge` | Confirmar recepcion |
| GET | `/api/v1/portal/{token}/activity` | Historial (solo visible para cliente) |

---

### 7.3 FASE B3: jaraba_legal_billing — Facturacion Legal + Time Tracking

**Justificacion:** Sin facturacion, el profesional necesita otra herramienta para cobrar. Soporta 6 modelos de facturacion legal. Integra con `jaraba_facturae` existente para XML Facturae 3.2.2 y VeriFactu.

#### 7.3.1 Entidad `TimeEntry` (Registro de Horas)

**Base table:** `time_entry`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id` | — | Identificacion |
| `case_id` | entity_reference (client_case) | Expediente |
| `user_id` | entity_reference (user) | Profesional |
| `description` | string(255) | Descripcion del trabajo |
| `date` | datetime | Fecha del trabajo |
| `duration_minutes` | integer | Duracion en minutos |
| `billing_rate` | decimal(12,2) | Tarifa horaria aplicada |
| `is_billable` | boolean | Facturable. Default: TRUE |
| `invoice_id` | entity_reference (legal_invoice) | Factura vinculada (si ya se facturo) |
| `created` | created | Timestamp |

#### 7.3.2 Entidad `LegalInvoice` (Factura de Honorarios)

**Base table:** `legal_invoice`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id`, `provider_id` | — | Identificacion |
| `invoice_number` | string(32) | Auto: FAC-YYYY-NNNN (unico por tenant, inmutable tras emision) |
| `series` | list_string | FAC, REC, PRO |
| `case_id` | entity_reference (client_case) | Expediente vinculado |
| `client_name`, `client_nif`, `client_address`, `client_email` | string | Datos fiscales del cliente |
| `issue_date`, `due_date` | datetime | Fechas de emision y vencimiento |
| `subtotal` | decimal(12,2) | Base imponible |
| `tax_rate` | decimal(5,2) | IVA (default 21%) |
| `tax_amount` | decimal(12,2) | Cuota IVA |
| `irpf_rate` | decimal(5,2) | Retencion IRPF (default 0 o 15% profesionales) |
| `irpf_amount` | decimal(12,2) | Cuota IRPF |
| `total` | decimal(12,2) | Total factura |
| `status` | list_string | draft, issued, sent, viewed, paid, partial, overdue, refunded, cancelled, written_off |
| `payment_method` | list_string | stripe, transfer, cash, check |
| `stripe_invoice_id`, `stripe_payment_url` | string | Integracion Stripe |
| `paid_at`, `paid_amount` | datetime, decimal | Datos de cobro |
| `notes` | text_long | Observaciones |
| `created`, `changed` | created, changed | Timestamps |

#### 7.3.3 Entidades `InvoiceLine`, `CreditNote`, `ServiceCatalogItem`, `Quote`, `QuoteLineItem`

Estas 5 entidades siguen los schemas definidos en docs 92 y 96 con campos completos:

- **`InvoiceLine`**: `invoice_id`, `line_order`, `description`, `quantity`, `unit` (unit/hour/session/month), `unit_price`, `discount_percent`, `line_total`, `tax_rate`
- **`CreditNote`**: `credit_note_number` (NC-YYYY-NNNN), `invoice_id`, `reason`, `amount`, `refund_status`, `stripe_refund_id`
- **`ServiceCatalogItem`**: Catalogo de servicios del profesional con `pricing_model` (fixed/hourly/range/success_fee/subscription), `base_price`, `complexity_factors` (JSON)
- **`Quote`**: Presupuesto con `quote_number` (PRES-YYYY-NNNN), lineas, descuentos, IVA, token de acceso publico, status (draft→sent→viewed→accepted→rejected→expired)
- **`QuoteLineItem`**: Lineas del presupuesto con `catalog_item_id`, `complexity_multiplier`, `complexity_factors_applied` (JSON)

#### 7.3.4 Services

| Service ID | Clase | Metodos Clave |
|-----------|-------|---------------|
| `jaraba_legal_billing.time_tracker` | `TimeTrackingService` | `logTime()`, `getTimeByCase()`, `getUnbilledTime()`, `calculateTotal()` |
| `jaraba_legal_billing.invoice_manager` | `InvoiceManagerService` | `createFromCase()`, `createFromQuote()`, `issue()`, `send()`, `markPaid()` |
| `jaraba_legal_billing.stripe_invoice` | `StripeInvoiceService` | `createStripeInvoice()`, `handleWebhook()` |
| `jaraba_legal_billing.quote_manager` | `QuoteManagerService` | `create()`, `send()`, `convertToCase()`, `convertToInvoice()` |
| `jaraba_legal_billing.quote_estimator` | `QuoteEstimatorService` | `generateEstimate()` — IA estima presupuesto desde triaje (Gemini 2.0 Flash, temperature=0.2, strict grounding al catalogo) |

**API Endpoints (26):**

Time Tracking (5): CRUD + listar por caso + resumen horas no facturadas.
Invoices (11): CRUD + issue + send + mark-paid + PDF + credit-note + webhook Stripe.
Quotes (10): CRUD + generate AI + send + duplicate + PDF + portal (view/accept/reject/negotiate/pdf).

---

## 8. MACRO-FASE C: Diferenciacion Competitiva — LexNET + Plantillas IA

### 8.1 FASE C1: jaraba_legal_lexnet — Integracion LexNET

**Justificacion:** LexNET es el sistema de comunicacion judicial obligatorio en Espana. Sin LexNET, los abogados litigantes no pueden usar JarabaLex como herramienta principal. Es un **moat estructural**: herramientas internacionales (Clio, vLex) no pueden competir en Espana sin esta integracion.

**Nota:** La integracion con LexNET requiere certificacion del Ministerio de Justicia. El modulo se implementa con la API definida por el CGPJ y se prepara para el proceso de certificacion.

#### 8.1.1 Entidad `LexnetNotification` (Notificacion Judicial)

**Base table:** `lexnet_notification`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id` | — | Identificacion |
| `case_id` | entity_reference (client_case) | Expediente asociado (puede vincularse manualmente) |
| `external_id` | string(64) | ID en el sistema LexNET |
| `notification_type` | list_string | resolucion, comunicacion, requerimiento, citacion, emplazamiento, notificacion_electronica |
| `court` | string(255) | Organo judicial emisor |
| `procedure_number` | string(64) | Numero de procedimiento |
| `subject` | string(500) | Asunto |
| `received_at` | datetime | Fecha de recepcion en LexNET |
| `acknowledged_at` | datetime | Fecha de lectura/acuse |
| `deadline_days` | integer | Dias de plazo asociados (si procede) |
| `computed_deadline` | datetime | Plazo calculado automaticamente |
| `attachments` | map | JSON array de adjuntos [{name, size, mime_type, lexnet_file_id}] |
| `status` | list_string | pending, read, linked, archived |
| `raw_data` | text_long | JSON completo de la respuesta LexNET (preservacion) |
| `created` | created | Timestamp |

#### 8.1.2 Entidad `LexnetSubmission` (Presentacion Judicial)

**Base table:** `lexnet_submission`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id`, `case_id` | — | Identificacion |
| `submission_type` | list_string | demanda, contestacion, recurso, escrito, solicitud, subsanacion |
| `court` | string(255) | Organo judicial destino |
| `procedure_number` | string(64) | Numero de procedimiento |
| `subject` | string(500) | Asunto |
| `document_ids` | map | JSON array de secure_document UUIDs adjuntos |
| `submitted_at` | datetime | Fecha de envio |
| `confirmation_id` | string(128) | ID de confirmacion de LexNET |
| `status` | list_string | draft, submitting, submitted, confirmed, rejected, error |
| `error_message` | text_long | Mensaje de error (si fallo) |
| `raw_response` | text_long | JSON respuesta LexNET |
| `created` | created | Timestamp |

#### 8.1.3 Services

| Service ID | Clase | Metodos Clave |
|-----------|-------|---------------|
| `jaraba_legal_lexnet.sync` | `LexnetSyncService` | `fetchNotifications()`, `acknowledgeNotification()`, `downloadAttachments()` |
| `jaraba_legal_lexnet.submission` | `LexnetSubmissionService` | `submit()`, `checkStatus()`, `attachDocuments()` |
| `jaraba_legal_lexnet.client` | `LexnetApiClient` | `authenticate()`, `request()`, `refreshCertificate()` — cliente HTTP con certificado QES |

**API Endpoints (8):**

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/api/v1/legal/lexnet/notifications` | Listar notificaciones |
| POST | `/api/v1/legal/lexnet/sync` | Forzar sincronizacion |
| POST | `/api/v1/legal/lexnet/notifications/{id}/link/{case_uuid}` | Vincular a expediente |
| POST | `/api/v1/legal/lexnet/notifications/{id}/acknowledge` | Acusar recibo |
| POST | `/api/v1/legal/lexnet/submissions` | Crear presentacion |
| POST | `/api/v1/legal/lexnet/submissions/{id}/submit` | Enviar a LexNET |
| GET | `/api/v1/legal/lexnet/submissions/{id}/status` | Verificar estado |
| GET | `/api/v1/legal/lexnet/submissions` | Listar presentaciones |

---

### 8.2 FASE C2: jaraba_legal_templates — Plantillas de Escritos con IA

**Justificacion:** Diferenciador IA. Generacion de borradores de escritos judiciales y tributarios desde el contexto del expediente + jurisprudencia encontrada + plantilla procesal. Ningun competidor integra busqueda semantica + generacion de escritos en un flujo unico.

#### 8.2.1 Entidad `LegalTemplate` (Plantilla Parametrica)

**Base table:** `legal_template`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id` | — | Identificacion |
| `name` | string(255) | Nombre de la plantilla |
| `template_type` | list_string | demanda, contestacion, recurso, escrito, contrato, dictamen, informe, consulta, recurso_tributario |
| `practice_area_tid` | entity_reference (taxonomy_term) | Area de practica |
| `jurisdiction_tid` | entity_reference (taxonomy_term) | Jurisdiccion aplicable |
| `template_body` | text_long | Cuerpo con merge fields `{{ case.title }}`, `{{ case.court }}`, `{{ citations }}` |
| `merge_fields` | map | JSON definicion de campos: [{key, label, type, required, default}] |
| `ai_instructions` | text_long | Instrucciones para generacion IA (prompt adicional) |
| `is_system` | boolean | Plantilla del sistema (no editable). Default: FALSE |
| `is_active` | boolean | Activa. Default: TRUE |
| `usage_count` | integer | Veces utilizada. Default: 0 |
| `created` | created | Timestamp |
| `changed` | changed | Timestamp |

#### 8.2.2 Entidad `GeneratedDocument` (Documento Generado)

**Base table:** `generated_document`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid`, `tenant_id` | — | Identificacion |
| `case_id` | entity_reference (client_case) | Expediente |
| `template_id` | entity_reference (legal_template) | Plantilla usada |
| `generated_by` | entity_reference (user) | Quien genero |
| `title` | string(255) | Titulo del escrito |
| `content_html` | text_long | Contenido HTML generado |
| `merge_data` | map | JSON con valores de merge fields usados |
| `citations_used` | map | JSON array de legal_citation IDs incluidas |
| `ai_model_version` | string(32) | Version del modelo IA |
| `generation_mode` | list_string | template_only, ai_assisted, ai_full |
| `status` | list_string | draft, reviewing, approved, finalized |
| `vault_document_id` | entity_reference (secure_document) | Si se guardo en vault |
| `created` | created | Timestamp |
| `changed` | changed | Timestamp |

#### 8.2.3 Services

| Service ID | Clase | Metodos Clave |
|-----------|-------|---------------|
| `jaraba_legal_templates.template_manager` | `TemplateManagerService` | `listByType()`, `getSystemTemplates()`, `renderTemplate()` |
| `jaraba_legal_templates.document_generator` | `DocumentGeneratorService` | `generateFromTemplate()`, `generateWithAi()`, `mergeFields()` |
| `jaraba_legal_templates.ai_drafter` | `AiDraftingService` | `draftDocument()` — Genera borrador con Gemini usando contexto del expediente + citas + plantilla |

**`AiDraftingService::draftDocument()` — flujo:**

1. Cargar expediente completo (hechos, partes, tribunal, jurisdiccion)
2. Cargar citas vinculadas al expediente (LegalCitation → LegalResolution)
3. Buscar jurisprudencia adicional relevante via LegalSearchService
4. Cargar plantilla con merge fields
5. Construir prompt: plantilla + datos expediente + jurisprudencia + instrucciones IA
6. Llamar a `@ai.provider` (Gemini 2.0 Flash, temperature=0.3)
7. Post-procesar: insertar citas formateadas, aplicar formato legal
8. Guardar como GeneratedDocument en status `draft`

**API Endpoints (5):**

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/api/v1/legal/templates` | Listar plantillas |
| POST | `/api/v1/legal/templates` | Crear plantilla |
| POST | `/api/v1/legal/documents/generate` | Generar desde plantilla (merge fields) |
| POST | `/api/v1/legal/documents/generate-ai` | Generar con IA (expediente + jurisprudencia) |
| GET | `/api/v1/legal/documents/{uuid}` | Obtener documento generado |

---

### 8.3 FASE C3: Copilot Legal v2 — 2 Nuevos Modos Contextuales

**Extension de `LegalCopilotAgent` con 2 modos que requieren contexto del expediente:**

#### Modo `case_assistant` (Asistente del Expediente)

- **Temperature:** 0.4
- **Keywords trigger:** expediente, caso, estado, resumen del caso, que falta
- **Contexto:** Lee el expediente completo (hechos, partes, plazos, documentos, citas, actividad)
- **Capacidades:** Resumir estado del caso, sugerir proximos pasos, identificar documentos faltantes, detectar plazos proximos, sugerir jurisprudencia relevante

#### Modo `document_drafter` (Redactor de Escritos)

- **Temperature:** 0.3
- **Keywords trigger:** redactar, escrito, demanda, contestacion, recurso, borrador
- **Contexto:** Expediente + citas vinculadas + plantillas disponibles + jurisprudencia RAG
- **Capacidades:** Generar borrador de escrito procesal, insertar citas formateadas, aplicar formato legal del tipo de procedimiento

**Cambios en LegalCopilotAgent:**

```php
// Añadir a MODES constant:
'case_assistant' => [
  'label' => 'Asistente del expediente',
  'description' => 'Analiza el estado del caso y sugiere acciones.',
  'temperature' => 0.4,
],
'document_drafter' => [
  'label' => 'Redactor de escritos',
  'description' => 'Genera borradores de escritos procesales.',
  'temperature' => 0.3,
],
```

---

## 9. Inventario Consolidado de Entidades

| # | Entidad | Modulo | Macro-Fase | Campos | Inmutable |
|---|---------|--------|------------|--------|-----------|
| 1 | `client_case` | jaraba_legal_cases | A1 | 22 | No |
| 2 | `case_activity` | jaraba_legal_cases | A1 | 11 | Si (append-only) |
| 3 | `client_inquiry` | jaraba_legal_cases | A1 | 17 | No |
| 4 | `inquiry_triage` | jaraba_legal_cases | A1 | 13 | No |
| 5 | `legal_deadline` | jaraba_legal_calendar | A2 | 17 | No |
| 6 | `court_hearing` | jaraba_legal_calendar | A2 | 17 | No |
| 7 | `calendar_connection` | jaraba_legal_calendar | A2 | 12 | No |
| 8 | `synced_calendar` | jaraba_legal_calendar | A2 | 8 | No |
| 9 | `external_event_cache` | jaraba_legal_calendar | A2 | 8 | No |
| 10 | `secure_document` | jaraba_legal_vault | B1 | 22 | No |
| 11 | `document_access` | jaraba_legal_vault | B1 | 14 | No |
| 12 | `document_audit_log` | jaraba_legal_vault | B1 | 8 | Si (append-only + hash chain) |
| 13 | `document_request` | jaraba_legal_vault | B2 | 14 | No |
| 14 | `document_delivery` | jaraba_legal_vault | B2 | 14 | No |
| 15 | `time_entry` | jaraba_legal_billing | B3 | 10 | No |
| 16 | `legal_invoice` | jaraba_legal_billing | B3 | 24 | No (inmutable tras emision) |
| 17 | `invoice_line` | jaraba_legal_billing | B3 | 10 | No |
| 18 | `credit_note` | jaraba_legal_billing | B3 | 9 | No |
| 19 | `service_catalog_item` | jaraba_legal_billing | B3 | 16 | No |
| 20 | `quote` | jaraba_legal_billing | B3 | 22 | No |
| 21 | `quote_line_item` | jaraba_legal_billing | B3 | 12 | No |
| 22 | `lexnet_notification` | jaraba_legal_lexnet | C1 | 15 | No |
| 23 | `lexnet_submission` | jaraba_legal_lexnet | C1 | 14 | No |
| 24 | `legal_template` | jaraba_legal_templates | C2 | 13 | No |
| 25 | `generated_document` | jaraba_legal_templates | C2 | 13 | No |

**Total: 25 Content Entities, ~340 campos.**

---

## 10. Inventario Consolidado de Services

| # | Service ID | Modulo | Metodos Principales |
|---|-----------|--------|-------------------|
| 1 | `jaraba_legal_cases.case_manager` | Cases | createCase, updateStatus, getCaseProgress, generateCaseNumber |
| 2 | `jaraba_legal_cases.activity_logger` | Cases | log, getTimeline, getClientVisibleTimeline |
| 3 | `jaraba_legal_cases.triage` | Cases | processInquiry, buildContext, buildPrompt |
| 4 | `jaraba_legal_cases.inquiry_manager` | Cases | createFromWebhook, assignToProvider, convertToCase |
| 5 | `jaraba_legal_calendar.deadline_calculator` | Calendar | computeDeadline, getBusinessDays, isBusinessDay |
| 6 | `jaraba_legal_calendar.calendar_sync` | Calendar | syncFromExternal, pushToExternal, deleteExternalEvent |
| 7 | `jaraba_legal_calendar.agenda` | Calendar | getUpcomingDeadlines, getHearings, getDayView, getWeekView |
| 8 | `jaraba_legal_calendar.alerts` | Calendar | checkUpcomingDeadlines, sendAlert |
| 9 | `jaraba_legal_vault.document_vault` | Vault | store, retrieve, createVersion, softDelete |
| 10 | `jaraba_legal_vault.document_access` | Vault | shareDocument, revokeAccess, revokeAll, validateToken |
| 11 | `jaraba_legal_vault.audit_log` | Vault | log, verifyIntegrity, getAuditTrail |
| 12 | `jaraba_legal_vault.encryption` | Vault | generateDek, wrapDek, unwrapDek, reEncryptForRecipient |
| 13 | `jaraba_legal_billing.time_tracker` | Billing | logTime, getTimeByCase, getUnbilledTime, calculateTotal |
| 14 | `jaraba_legal_billing.invoice_manager` | Billing | createFromCase, createFromQuote, issue, send, markPaid |
| 15 | `jaraba_legal_billing.stripe_invoice` | Billing | createStripeInvoice, handleWebhook |
| 16 | `jaraba_legal_billing.quote_manager` | Billing | create, send, convertToCase, convertToInvoice |
| 17 | `jaraba_legal_billing.quote_estimator` | Billing | generateEstimate (IA, Gemini, strict grounding) |
| 18 | `jaraba_legal_lexnet.sync` | LexNET | fetchNotifications, acknowledgeNotification, downloadAttachments |
| 19 | `jaraba_legal_lexnet.submission` | LexNET | submit, checkStatus, attachDocuments |
| 20 | `jaraba_legal_lexnet.client` | LexNET | authenticate, request, refreshCertificate |
| 21 | `jaraba_legal_templates.template_manager` | Templates | listByType, getSystemTemplates, renderTemplate |
| 22 | `jaraba_legal_templates.document_generator` | Templates | generateFromTemplate, generateWithAi, mergeFields |
| 23 | `jaraba_legal_templates.ai_drafter` | Templates | draftDocument (Gemini, expediente + citas + plantilla) |

**Total: 23 nuevos services + extension de LegalCopilotAgent (2 modos).**

---

## 11. Inventario Consolidado de Endpoints REST API

| Modulo | Endpoints | Descripcion |
|--------|-----------|-------------|
| jaraba_legal_cases | 11 | CRUD cases (5) + inquiries (5) + activity (1) |
| jaraba_legal_calendar | 17 | Deadlines (5) + hearings (3) + agenda (2) + connections (3) + OAuth (4) |
| jaraba_legal_vault | 22 | Vault docs (15) + portal cliente (7) |
| jaraba_legal_billing | 26 | Time (5) + invoices (11) + quotes (10) |
| jaraba_legal_lexnet | 8 | Notifications (4) + submissions (4) |
| jaraba_legal_templates | 5 | Templates (2) + generation (2) + detail (1) |
| **TOTAL** | **89** | + 31 endpoints existentes jaraba_legal_intelligence |

**Total ecosistema JarabaLex post-implementacion: ~120 endpoints REST API.**

---

## 12. Paleta de Colores y Design Tokens

Reutiliza la paleta existente del vertical JarabaLex:

| Contexto | Token CSS | Valor | Uso |
|----------|-----------|-------|-----|
| Primary | `--ej-legal-primary` | #1E3A5F | Titulos, headers, navegacion |
| Secondary | `--ej-legal-secondary` | #8B7355 | Acentos, iconos |
| Accent | `--ej-legal-accent` | #B8860B | CTAs, badges premium |
| Success | `--ej-color-success` | #43A047 | Plazo completado, documento aprobado |
| Danger | `--ej-color-danger` | #E53935 | Plazo vencido, urgente, error |
| Warning | `--ej-color-warning` | #FB8C00 | Plazo proximo, revision pendiente |
| Info | `--ej-color-info` | #1976D2 | LexNET, calendar sync |
| Vault | `--ej-vault-primary` | #2E7D32 | Documentos cifrados, seguridad |

```scss
.legal-deadline-badge {
  &--urgent { background: color-mix(in srgb, var(--ej-color-danger) 15%, transparent); }
  &--upcoming { background: color-mix(in srgb, var(--ej-color-warning) 15%, transparent); }
  &--completed { background: color-mix(in srgb, var(--ej-color-success) 15%, transparent); }
}
```

---

## 13. Patron de Iconos SVG

| Modulo | Categoria | Iconos Necesarios |
|--------|-----------|------------------|
| Cases | `legal/` | `briefcase.svg`, `briefcase-duotone.svg`, `gavel.svg`, `gavel-duotone.svg`, `user-tie.svg`, `user-tie-duotone.svg` |
| Calendar | `legal/` | `calendar-legal.svg`, `calendar-legal-duotone.svg`, `clock-deadline.svg`, `clock-deadline-duotone.svg`, `courthouse.svg`, `courthouse-duotone.svg` |
| Vault | `security/` | `lock-shield.svg`, `lock-shield-duotone.svg`, `document-encrypted.svg`, `document-encrypted-duotone.svg`, `share-secure.svg`, `share-secure-duotone.svg` |
| Billing | `finance/` | `invoice.svg`, `invoice-duotone.svg`, `timer.svg`, `timer-duotone.svg`, `receipt.svg`, `receipt-duotone.svg` |
| LexNET | `legal/` | `lexnet.svg`, `lexnet-duotone.svg`, `notification-court.svg`, `notification-court-duotone.svg` |
| Templates | `legal/` | `template-legal.svg`, `template-legal-duotone.svg`, `ai-draft.svg`, `ai-draft-duotone.svg` |

**Total: 24 iconos (12 pares outline + duotone).**

---

## 14. Orden Global de Implementacion

```
MACRO    SUB     MES       MODULO                      DEPS                    PRIORIDAD
──────────────────────────────────────────────────────────────────────────────────────────
  A      A1     M1-M2     Cases (Expedientes)          legal_intelligence      P0 Critico
  A      A2     M2-M3     Calendar (Agenda+Plazos)     A1                      P0 Critico
  A      A3     M3        Integracion ↔ Legal Intel    A1                      P0 Critico
──────────────────────────────────────────────────────────────────────────────────────────
  B      B1     M4-M5     Vault (Buzon Confianza)      A1                      P1 Alto
  B      B2     M5        Portal Cliente               B1                      P1 Alto
  B      B3     M5-M7     Billing (Facturas+Horas)     A1, jaraba_facturae     P1 Alto
──────────────────────────────────────────────────────────────────────────────────────────
  C      C1     M8-M9     LexNET Integration           A1, A2                  P1 Alto
  C      C2     M9-M10    Templates Escritos IA        A1, C1                  P2 Medio
  C      C3     M10       Copilot v2 (2 modos)         A1, C2                  P2 Medio
──────────────────────────────────────────────────────────────────────────────────────────
```

**Paralelizacion posible:**
- B1/B2 y B3 pueden ejecutarse en paralelo (ambos dependen solo de A1)
- C1 y C2 son secuenciales (C2 necesita LexNET para presentaciones)
- A1 es prerequisito de TODO — debe completarse primero

---

## 15. Estimacion de Esfuerzo

| Sub-Fase | Modulo | Entidades | Services | Endpoints | Horas (min) | Horas (max) |
|----------|--------|-----------|----------|-----------|-------------|-------------|
| A1 | jaraba_legal_cases | 4 | 4 | 11 | 120 | 160 |
| A2 | jaraba_legal_calendar | 5 | 4 | 17 | 150 | 200 |
| A3 | Integracion (mod existentes) | 0 | 0 | 0 | 50 | 60 |
| B1 | jaraba_legal_vault | 3 | 4 | 15 | 130 | 170 |
| B2 | Portal Cliente (ext vault) | 2 | 0 | 7 | 60 | 80 |
| B3 | jaraba_legal_billing | 7 | 5 | 26 | 120 | 160 |
| C1 | jaraba_legal_lexnet | 2 | 3 | 8 | 130 | 170 |
| C2 | jaraba_legal_templates | 2 | 3 | 5 | 80 | 110 |
| C3 | Copilot v2 (mod existente) | 0 | 0 | 0 | 50 | 70 |
| **TOTAL** | **6 modulos nuevos** | **25** | **23** | **89** | **890** | **1,180** |

**Inversion:** 890–1,180 horas × EUR 45/hora = **EUR 40,050–53,100**
**Timeline:** ~10 meses (con 1 desarrollador senior), ~5 meses (con 2 en paralelo)

---

## 16. Registro de Cambios

### v1.0.0 (2026-02-16)
- Creacion inicial del plan de implementacion
- Analisis de mercado completo (competidores, TAM, mega-operaciones 2024-2025)
- Analisis multidisciplinar desde 10 roles senior
- 8 ventajas competitivas documentadas (VC-1 a VC-8)
- 6 necesidades fundamentales organizadas en 3 macro-fases (A/B/C)
- 6 modulos nuevos detallados: jaraba_legal_cases, jaraba_legal_calendar, jaraba_legal_vault, jaraba_legal_billing, jaraba_legal_lexnet, jaraba_legal_templates
- 25 Content Entities con ~340 campos
- 23 nuevos services + 2 modos copilot
- ~89 nuevos endpoints REST API (~120 total ecosistema)
- Cumplimiento verificado de 15 directrices + 3 reglas JarabaLex
- Tabla de correspondencia con 10 especificaciones tecnicas (docs 85-96)
- Estimacion total: 890–1,180 horas / EUR 40,050–53,100 / 3 macro-fases
- Inventarios consolidados: entidades, services, endpoints, tokens, iconos

### v1.0.1 (2026-02-16)
- **Auditoria de integridad:** Corregidas 13 inconsistencias detectadas por 3 agentes de auditoria paralelos
- Seccion 1.6: Entidades 22→25, Services 24→23, Endpoints A=42→28/C=30→13/total ~120→89 nuevos
- Metadata: Sub-Fases 18→9 (conteo real de sub-fases definidas)
- Metadata Docs: Eliminados 178/178A/178B (no referenciados en body), anadido doc 89 (PAdES)
- Seccion 1.5: Clarificado CryptographyService (Ed25519 signing, no AES-256-GCM; vault encryption es servicio nuevo)
- Seccion 1.6 Timeline: Alineado con Seccion 15 (~10 meses 1 dev, ~5 meses 2 devs), eliminada estimacion inconsistente 12-16 semanas
- Seccion 3.7: Entidades 22→25
- Seccion 5: B3 dependencia anadida jaraba_facturae (ya estaba en Seccion 14)
- Seccion 6.1.6: Anadida ruta DELETE faltante en Cases YAML (5 CRUD completo)
- Seccion 16 changelog: Roles 15→10 (conteo real de roles detallados en Seccion 1.3)

---

*Fin del documento.*
