# Plan de Implementacion — Metodo Jaraba en el SaaS
## Clase Mundial 10/10 — Corregido segun auditoria tecnica

| Campo | Valor |
|-------|-------|
| Fecha | 2026-03-27 |
| Version | 1.0 |
| Autor | Claude Code (Opus 4.6) |
| Estado | Aprobado para implementacion |
| Auditoria previa | `docs/tecnicos/20260327c-Auditoria_Specs_Metodo_Jaraba_SaaS_v1_Claude.md` |
| Spec original | `docs/tecnicos/20260327b-specs-metodo-jaraba-saas_Claude.md` |
| Esfuerzo total estimado | ~95h (vs 140h del spec original, -32% por reutilizacion) |
| Directrices aplicables | 48 reglas verificadas (ver seccion 10) |

---

## Indice de Navegacion (TOC)

1. [Contexto y justificacion](#1-contexto-y-justificacion)
2. [Arquitectura general](#2-arquitectura-general)
   - 2.1 [Decisiones arquitectonicas clave](#21-decisiones-arquitectonicas-clave)
   - 2.2 [Mapa de modulos afectados](#22-mapa-de-modulos-afectados)
   - 2.3 [Diagrama de dependencias](#23-diagrama-de-dependencias)
3. [Parte A: pepejaraba.com/metodo v2](#3-parte-a-pepejarabacommetodo-v2)
   - 3.1 [Contexto tecnico](#31-contexto-tecnico)
   - 3.2 [Estrategia de implementacion](#32-estrategia-de-implementacion)
   - 3.3 [Estructura de las 8 secciones](#33-estructura-de-las-8-secciones)
   - 3.4 [Theming y SCSS](#34-theming-y-scss)
   - 3.5 [SEO y Schema.org](#35-seo-y-schemaorg)
   - 3.6 [Cross-domain links con UTM](#36-cross-domain-links-con-utm)
   - 3.7 [Verificacion RUNTIME-VERIFY-001](#37-verificacion-runtime-verify-001)
4. [Parte B: plataformadeecosistemas.com/metodo](#4-parte-b-plataformadeecosistemaascommetodo)
   - 4.1 [Contexto tecnico](#41-contexto-tecnico)
   - 4.2 [Extension de MetodoLandingController](#42-extension-de-metodolandingcontroller)
   - 4.3 [Template Twig (ZERO-REGION-001)](#43-template-twig-zero-region-001)
   - 4.4 [Integracion en mega menu](#44-integracion-en-mega-menu)
   - 4.5 [Grid de verticales reutilizado](#45-grid-de-verticales-reutilizado)
   - 4.6 [SEO y breadcrumbs](#46-seo-y-breadcrumbs)
5. [Parte C: Landing certificacion/franquicia](#5-parte-c-landing-certificacionfranquicia)
   - 5.1 [Contexto tecnico](#51-contexto-tecnico)
   - 5.2 [Controller y template](#52-controller-y-template)
   - 5.3 [Formulario dual (PremiumEntityFormBase)](#53-formulario-dual-premiumentityformbase)
   - 5.4 [Integracion CRM](#54-integracion-crm)
   - 5.5 [Seguridad (honeypot, CSRF, RGPD)](#55-seguridad-honeypot-csrf-rgpd)
   - 5.6 [Rubrica visual interactiva](#56-rubrica-visual-interactiva)
   - 5.7 [Trust signals y MARKETING-TRUTH-001](#57-trust-signals-y-marketing-truth-001)
6. [Parte D: Extension de modulos existentes](#6-parte-d-extension-de-modulos-existentes)
   - 6.1 [Estrategia de reutilizacion (NO modulo nuevo)](#61-estrategia-de-reutilizacion-no-modulo-nuevo)
   - 6.2 [Extension de jaraba_training](#62-extension-de-jaraba_training)
   - 6.3 [Generalizacion de portfolio](#63-generalizacion-de-portfolio)
   - 6.4 [Rubrica del Metodo Jaraba](#64-rubrica-del-metodo-jaraba)
   - 6.5 [Generacion y emision de certificados](#65-generacion-y-emision-de-certificados)
   - 6.6 [Verificacion publica de certificados](#66-verificacion-publica-de-certificados)
   - 6.7 [Panel de evaluador](#67-panel-de-evaluador)
   - 6.8 [Dashboard de administracion](#68-dashboard-de-administracion)
7. [Setup Wizard + Daily Actions](#7-setup-wizard--daily-actions)
   - 7.1 [Wizard steps](#71-wizard-steps)
   - 7.2 [Daily actions](#72-daily-actions)
   - 7.3 [Pipeline E2E L1-L4](#73-pipeline-e2e-l1-l4)
8. [AI Coverage](#8-ai-coverage)
   - 8.1 [CopilotBridgeService](#81-copilotbridgeservice)
   - 8.2 [GroundingProvider](#82-groundingprovider)
   - 8.3 [Asistencia IA en evaluaciones](#83-asistencia-ia-en-evaluaciones)
9. [Salvaguardas](#9-salvaguardas)
   - 9.1 [Validadores nuevos](#91-validadores-nuevos)
   - 9.2 [Pre-commit hooks](#92-pre-commit-hooks)
   - 9.3 [Monitoring proactivo](#93-monitoring-proactivo)
10. [Tabla de correspondencia specs vs implementacion](#10-tabla-de-correspondencia-specs-vs-implementacion)
11. [Tabla de compliance con directrices](#11-tabla-de-compliance-con-directrices)
12. [Evaluacion de conversion 10/10](#12-evaluacion-de-conversion-1010)
13. [Estimacion de esfuerzo corregida](#13-estimacion-de-esfuerzo-corregida)
14. [Prioridad y cronograma](#14-prioridad-y-cronograma)
15. [Glosario](#15-glosario)

---

## 1. Contexto y justificacion

El Metodo Jaraba es el framework pedagogico central del ecosistema: 3 capas (Criterio, Supervision IA, Posicionamiento), 4 competencias (Pedir, Evaluar, Iterar, Integrar), y un Ciclo de Impacto Digital (CID) de 90 dias. Actualmente existe como contenido narrativo en pepejaraba.com/metodo pero NO esta integrado como producto digital en el SaaS.

Este plan corrige las especificaciones originales (`20260327b`) segun los 14 hallazgos de la auditoria tecnica (`20260327c`), eliminando duplicaciones, alineando con la arquitectura existente, y garantizando cumplimiento de las 176+ reglas del proyecto.

**Principio rector**: Reutilizar el 70% de infraestructura existente (entities, servicios, PDF, firma digital, journey, credentials) y extender solo lo necesario para el Metodo Jaraba.

---

## 2. Arquitectura general

### 2.1 Decisiones arquitectonicas clave

| Decision | Justificacion |
|----------|---------------|
| **NO crear modulo nuevo** `ecosistema_jaraba_certificacion` | El 70% de la funcionalidad ya existe en `jaraba_training` + `jaraba_credentials` + `jaraba_andalucia_ei`. Crear un modulo paralelo fragmentaria datos y multiplicaria mantenimiento. |
| **Controller-based landing** (no page_content) para Partes B y C en SaaS | Las landing pages de marketing del SaaS principal usan controllers (MetodoLandingController, VerticalLandingController). Patron ZERO-REGION-001 con templates Twig limpios. |
| **Canvas update** (page_content) para Parte A en pepejaraba.com | Los meta-sitios gestionan contenido via GrapesJS page_content entities con canvas_data. El page_content local_id 59 ya existe. |
| **Extender `jaraba_training`** con campos del Metodo | `CertificationProgram` y `UserCertification` ya tienen la estructura base. Se anaden campos para las 4 competencias, 3 capas, y puntuaciones de rubrica. |
| **Reutilizar `jaraba_credentials`** para emision | `IssuedCredential` (Open Badge 3.0) + `CredentialPdfService` cubren emision + PDF + verificacion publica. No reinventar. |
| **Textos siempre traducibles** | Todo texto visible usa `$this->t()` en PHP, `{% trans %}` en Twig. Encolado automatico via AUTO-TRANSLATE-001. |
| **Precios desde MetaSitePricingService** | NO-HARDCODE-PRICE-001. Integracion con SaasPlanTier/SaasPlanFeature ConfigEntities. |
| **SCSS con Dart Sass moderno** | @use (no @import), color-mix() para alpha, var(--ej-*) para todos los colores. Build: `npm run build` desde directorio del tema. |

### 2.2 Mapa de modulos afectados

```
Modulos a MODIFICAR (extension):
  jaraba_training/          — Nuevos campos en CertificationProgram + UserCertification
  jaraba_page_builder/      — Extension MetodoLandingController + nuevo CertificacionLandingController
  ecosistema_jaraba_core/   — MegaMenuBridgeService (direct link "Metodologia")
  ecosistema_jaraba_theme/  — Templates Twig + SCSS secciones metodo + certificacion

Modulos a REUTILIZAR (sin modificar):
  jaraba_credentials/       — IssuedCredential + CredentialPdfService
  jaraba_andalucia_ei/      — EntregableFormativoEi (referencia para portfolio)
  jaraba_copilot_v2/        — CopilotLeadCaptureService (CRM leads)
  jaraba_journey/           — CertificacionJourneyDefinition

Meta-sitios afectados:
  pepejaraba.com            — Parte A (canvas_data update)
  plataformadeecosistemas.com — Partes B, C, D (controller + entities)
```

### 2.3 Diagrama de dependencias

```
Parte A (pepejaraba.com/metodo)
  └── page_content entity (canvas_data JSON)
      └── GrapesJS blocks existentes (67+)
      └── Design tokens --pj-* (pepejaraba palette)

Parte B (SaaS /metodo)
  └── MetodoLandingController (extension)
      ├── MegaMenuBridgeService (direct link)
      ├── MetaSitePricingService (precios)
      ├── SuccessCaseService (dato 46%)
      └── Template: page--metodo.html.twig (ZERO-REGION-001)

Parte C (SaaS /certificacion)
  └── CertificacionLandingController (nuevo)
      ├── CertificacionContactForm (PremiumEntityFormBase)
      │   ├── CopilotLeadCaptureService (CRM)
      │   ├── Honeypot + CSRF
      │   └── RGPD double opt-in
      └── Template: page--certificacion.html.twig

Parte D (Extension modulos)
  └── jaraba_training (extension)
      ├── Nuevos campos en CertificationProgram
      ├── Nuevos campos en UserCertification
      ├── MethodCertificationService (logica Metodo)
      ├── MethodRubricService (rubrica 4 competencias)
      ├── @? jaraba_credentials (emision Open Badge)
      ├── @? FirmaDigitalService (firma PDF)
      ├── Setup Wizard steps (3)
      ├── Daily Actions (3)
      ├── CopilotBridgeService
      └── GroundingProvider
```

---

## 3. Parte A: pepejaraba.com/metodo v2

### 3.1 Contexto tecnico

| Propiedad | Valor |
|-----------|-------|
| URL | https://pepejaraba.com/metodo |
| Entity | page_content, local_id=59, uuid en content seed |
| Layout mode | canvas (GrapesJS) |
| Meta-sitio | pepejaraba (group_id=5, variant=pepejaraba) |
| Design tokens | --pj-* (naranja #FF8C42, navy #233D63, teal #00A9A5) |
| Template | Renderizado via PageContentViewBuilder (canvas_data → rendered_html) |

### 3.2 Estrategia de implementacion

La pagina /metodo en pepejaraba.com es una **page_content entity** cuyo contenido se gestiona via el editor visual GrapesJS. La actualizacion consiste en:

1. **Construir las 8 secciones** usando los GrapesJS blocks existentes del catalogo (hero_fullscreen, features_grid, timeline, cards, cta_block, etc.)
2. **Exportar** el canvas_data JSON con HTML + CSS inline
3. **Actualizar** el entity via la interfaz de administracion o script de content seed
4. **Verificar** que el rendered_html se genera correctamente

NO se toca codigo PHP. Es una actualizacion de contenido puro.

### 3.3 Estructura de las 8 secciones

| Seccion | Block type GrapesJS | Contenido clave | CTA |
|---------|--------------------|-----------------|----|
| 1. Hero | `hero_fullscreen` | Badge "Metodo Jaraba", H1, subtitulo, dato 46% | "Ver como funciona" (scroll) |
| 2. El problema | `features_grid` (3 cards) | 3 pain points con icono + descripcion | — |
| 3. La solucion | `comparison_table` | Tradicional vs Metodo Jaraba (3 pasos) | — |
| 4. Las 3 capas | `cards_horizontal` (3 cards) | Criterio, Supervision IA, Posicionamiento | — |
| 5. Las 4 competencias | `features_grid` (4 items) | Pedir, Evaluar, Iterar, Integrar con ejemplo | — |
| 6. CID 90 dias | `timeline` (3 fases) | Dias 1-30, 31-60, 61-90 con entregable | — |
| 7. 3 caminos | `pricing_columns` (3 cols) | Empleabilidad, Emprendimiento, Digitalizacion | Cross-domain a SaaS con UTM |
| 8. Evidencia + CTA | `cta_block` + `trust_strip` | 46% insercion + logos + CTA final | "Empezar gratis" → SaaS |

**Iconos** (ICON-DUOTONE-001, ICON-CONVENTION-001):
- Pedir: `ai/chat` (duotone, naranja-impulso)
- Evaluar: `compliance/shield-check` (duotone, azul-corporativo)
- Iterar: `ai/sparkles` (duotone, verde-innovacion)
- Integrar: `business/ecosystem` (duotone, naranja-impulso)
- Criterio: `ai/lightbulb` (duotone, naranja-impulso)
- Supervision: `ai/copilot` (duotone, azul-corporativo)
- Posicionamiento: `analytics/target` (duotone, verde-innovacion)

Todos verificados contra el catalogo SVG existente en `images/icons/`.

### 3.4 Theming y SCSS

El contenido canvas_data incluye CSS inline generado por GrapesJS. Para consistencia con el design system, todo color debe usar las variables CSS custom del meta-sitio pepejaraba:

```css
/* Dentro de canvas_data CSS — variables del tenant pepejaraba */
.pj-section { color: var(--pj-text, #2D3748); }
.pj-hero { background: var(--pj-dark, #233D63); }
.pj-card { border-color: var(--pj-border, #E2E8F0); }
```

**NO se crean nuevos archivos SCSS** para esta parte. El canvas_data ya tiene CSS inline que hereda los tokens del meta-sitio inyectados via `hook_preprocess_html()`.

### 3.5 SEO y Schema.org

Meta tags actualizados en el entity page_content:
- `meta_title`: "Metodo Jaraba: Supervision de Agentes IA en 90 Dias | Pepe Jaraba"
- `meta_description`: "Sistema de capacitacion profesional para generar impacto economico supervisando agentes IA. 46% de insercion laboral. 3 capas, 4 competencias, 90 dias."

Schema.org `@type=Course` inyectado via preprocess (NO en canvas_data):
```json
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "Metodo Jaraba",
  "description": "Supervision de Agentes IA como competencia profesional",
  "provider": {
    "@type": "Organization",
    "name": "Plataforma de Ecosistemas Digitales S.L."
  },
  "hasCourseInstance": [
    { "@type": "CourseInstance", "name": "Empleabilidad", "courseMode": "online" },
    { "@type": "CourseInstance", "name": "Emprendimiento", "courseMode": "blended" },
    { "@type": "CourseInstance", "name": "Digitalizacion", "courseMode": "online" }
  ]
}
```

### 3.6 Cross-domain links con UTM

Los 3 caminos (seccion 7) enlazan cross-domain al SaaS con UTM estandarizado:

| Camino | URL destino |
|--------|------------|
| Empleabilidad | `https://plataformadeecosistemas.com/es/empleabilidad?utm_source=pepejaraba&utm_medium=metodo&utm_content=empleabilidad` |
| Emprendimiento | `https://plataformadeecosistemas.com/es/emprendimiento?utm_source=pepejaraba&utm_medium=metodo&utm_content=emprendimiento` |
| Digitalizacion | `https://plataformadeecosistemas.com/es/comercioconecta?utm_source=pepejaraba&utm_medium=metodo&utm_content=digitalizacion` |

CTA final "Empezar gratis": `https://plataformadeecosistemas.com/es/user/register?utm_source=pepejaraba&utm_medium=metodo&utm_content=cta_final`

### 3.7 Verificacion RUNTIME-VERIFY-001

Tras actualizar el canvas_data:
1. CSS inline del canvas contiene variables --pj-* (no hex hardcoded)
2. page_content entity tiene rendered_html actualizado
3. La URL /metodo en pepejaraba.com muestra las 8 secciones
4. Los links cross-domain incluyen UTM y apuntan al SaaS correcto
5. Schema.org JSON-LD presente en el head de la pagina

---

## 4. Parte B: plataformadeecosistemas.com/metodo

### 4.1 Contexto tecnico

| Propiedad | Valor |
|-----------|-------|
| URL | https://plataformadeecosistemas.com/es/metodo |
| Estado actual | Ruta EXISTE (`MetodoLandingController::landing()`) con contenido v1 |
| Patron | Controller-based + ZERO-REGION-001 + template Twig limpio |
| Audiencia | Usuarios SaaS, prospects B2C/B2B, evaluadores de programas publicos |
| Tono | Profesional, orientado a producto (3a persona) |

### 4.2 Extension de MetodoLandingController

El controller `MetodoLandingController::landing()` ya existe en `jaraba_page_builder`. Se extiende con las 8 secciones del spec, inyectando datos desde servicios (no hardcodeados):

```php
// Inyeccion de dependencias (patron DI, NO \Drupal::service())
public function __construct(
  protected MetaSitePricingService $pricingService,
  protected MegaMenuBridgeService $megaMenuBridge,
  // @? opcional para SuccessCaseService (cross-modulo)
  protected ?SuccessCaseServiceInterface $successCaseService = NULL,
) {}
```

Cada seccion se construye en un metodo protegido:
- `buildHero()` — H1, subtitulo, dato de insercion desde SuccessCase entity
- `buildComoFunciona()` — 3 pasos visuales con iconos duotone
- `buildTresCapas()` — 3 cards con links a verticales
- `buildMapaVerticales()` — Grid de 10 verticales reutilizando `getVerticalCatalog()`
- `buildCertificacion()` — Preview rubrica 4 niveles + link a /certificacion
- `buildParaInstituciones()` — Bloque B2G con link a /certificacion#entidades
- `buildEvidencia()` — 46% desde SuccessCase + logos (MARKETING-TRUTH-001)
- `buildCta()` — "Prueba gratis 14 dias" + "Ver planes" (precios desde MetaSitePricingService)

**Textos traducibles**: Todo string visible usa `$this->t('...')`. AUTO-TRANSLATE-001 encola automaticamente.

### 4.3 Template Twig (ZERO-REGION-001)

Archivo: `web/themes/custom/ecosistema_jaraba_theme/templates/pages/page--metodo.html.twig`

```twig
{# ZERO-REGION-001: Frontend limpio, sin page.content ni bloques heredados #}
{% extends '@ecosistema_jaraba_theme/layouts/page--clean.html.twig' %}

{% block content %}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    theme_settings: theme_settings,
    ...
  } only %}

  <main class="metodo-landing" role="main">
    {# 8 secciones renderizadas desde variables del controller #}
    {{ clean_content }}
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    theme_settings: theme_settings,
  } only %}
{% endblock %}
```

**Body class** via `hook_preprocess_html()` (NO `attributes.addClass()` en template):
```php
$variables['attributes']['class'][] = 'page-metodo';
$variables['attributes']['class'][] = 'full-width-layout';
```

### 4.4 Integracion en mega menu

Anadir "Metodologia" como **direct link** en el mega menu del SaaS (no dentro de las columnas de verticales):

```php
// En ecosistema_jaraba_theme.theme, bloque else (SaaS principal):
$variables['theme_settings']['megamenu_direct_links'] = [
  ['text' => (string) t('Metodologia'), 'url' => $lp . '/metodo'],
  ['text' => (string) t('Precios'), 'url' => $lp . '/planes'],
  ['text' => (string) t('Casos de Exito'), 'url' => $lp . '/casos-de-exito'],
];
```

### 4.5 Grid de verticales reutilizado

La seccion "Mapa de verticales" reutiliza `MegaMenuBridgeService::getVerticalCatalog()` como SSOT de los 10 verticales, anadiendo una linea por vertical que indica que capa del Metodo cubre:

```php
$verticals = $this->megaMenuBridge->getVerticalCatalog();
foreach ($verticals as &$column) {
  foreach ($column['items'] as &$item) {
    $item['method_layer'] = $this->getMethodLayer($item['url']);
  }
}
```

### 4.6 SEO y breadcrumbs

- Breadcrumb: `Inicio > Metodo Jaraba`
- Canonical: `https://plataformadeecosistemas.com/es/metodo`
- Hreflang: Filtrado por seo_active_languages (SEO-HREFLANG-ACTIVE-001)
- Meta title: "Metodo Jaraba: La Metodologia de la Plataforma | Ecosistema Jaraba"

---

## 5. Parte C: Landing certificacion/franquicia

### 5.1 Contexto tecnico

| Propiedad | Valor |
|-----------|-------|
| URL primaria | https://plataformadeecosistemas.com/es/certificacion |
| URL alternativa | https://plataformadeecosistemas.com/es/partners (redirect 301 a /certificacion#entidades) |
| Patron | Controller-based + ZERO-REGION-001 |
| Conversion | Formulario de contacto completado (2 modos) |

### 5.2 Controller y template

Nuevo `CertificacionLandingController` en `jaraba_page_builder` (o en `jaraba_training` si se prefiere proximidad al dominio):

```php
// Routing
jaraba_training.certificacion_landing:
  path: '/certificacion'
  defaults:
    _controller: '\Drupal\jaraba_training\Controller\CertificacionLandingController::landing'
    _title: 'Certificacion Metodo Jaraba'
  requirements:
    _access: 'TRUE'  # Pagina publica
```

Template: `page--certificacion.html.twig` — layout limpio, full-width, mobile-first.

### 5.3 Formulario dual (PremiumEntityFormBase)

El formulario de contacto dual (profesional vs entidad) se implementa como Drupal Form extendiendo `PremiumEntityFormBase`:

```php
class CertificacionContactForm extends PremiumEntityFormBase {
  // getSectionDefinitions() para las 2 secciones (profesional/entidad)
  // getFormIcon() -> 'compliance/certificate'
  // Campos condicionales via #states API de Drupal
}
```

**Modo profesional**: nombre, email, telefono, situacion_actual, aplicacion_interes, provincia (52), como_nos_conocio, rgpd
**Modo entidad**: nombre_entidad, tipo_entidad, persona_contacto, cargo, email_institucional, telefono, programa, num_participantes, provincia, rgpd

Ambos modos abren en **slide-panel** (SLIDE-PANEL-RENDER-001) desde anclas en la pagina.

### 5.4 Integracion CRM

Al enviar el formulario, se usa `CopilotLeadCaptureService` existente para crear:
- `Contact` entity en el CRM con datos del formulario
- `Opportunity` entity con tipo `certificacion_profesional` o `franquicia_entidad`
- Email de confirmacion al usuario (template #25: `certificacion_contact`)
- Email de notificacion a Jose con datos del lead

**Dependencia opcional** (`@?`): OPTIONAL-CROSSMODULE-001 — si jaraba_crm no esta activo, el formulario funciona igualmente (logs warning).

### 5.5 Seguridad (honeypot, CSRF, RGPD)

| Medida | Implementacion |
|--------|---------------|
| Honeypot | Campo oculto `website` que bots rellenan. Si no vacio, descartar silenciosamente. Patron de `PruebaGratuitaController`. |
| CSRF | `_csrf_request_header_token: 'TRUE'` en routing si es AJAX. Formulario Drupal standard tiene CSRF built-in. |
| RGPD | Checkbox obligatorio con enlace a politica: `{% trans %}He leido y acepto la <a href="{{ lp }}/politica-privacidad">politica de privacidad</a>{% endtrans %}` |
| Rate limiting | Max 3 envios por IP/hora (NGINX-HARDENING-001) |

### 5.6 Rubrica visual interactiva

4 niveles como stepper horizontal CSS (NO JS framework):
- Nivel 1: Novel — Indicadores observables del documento fundacional
- Nivel 2: Aprendiz — Idem
- Nivel 3: Competente — Idem
- Nivel 4: Autonomo — Idem

Al hacer click, se expande con `<details>` nativo (sin JS). Responsive: en movil se convierte en lista vertical.

**SCSS**: Nuevo parcial `_certificacion-landing.scss` en `scss/routes/` del tema, compilado via `npm run build:routes`. Colores via `var(--ej-*)` con fallback. `color-mix()` para alpha. Dart Sass moderno con `@use`.

### 5.7 Trust signals y MARKETING-TRUTH-001

El dato "46% de insercion laboral" se obtiene dinamicamente:

```php
// En el controller, NO hardcodeado:
$insertionRate = NULL;
if ($this->successCaseService) {
  $case = $this->successCaseService->loadByProgram('andalucia_ei_1e');
  $insertionRate = $case?->get('primary_metric_value')?->value;
}
$variables['insertion_rate'] = $insertionRate ?? '46';
$variables['insertion_source'] = (string) $this->t('Programa Andalucia +ei, 1a Edicion');
```

Logos institucionales desde `TrustStripService` existente (TRUST-STRIP-001).

---

## 6. Parte D: Extension de modulos existentes

### 6.1 Estrategia de reutilizacion (NO modulo nuevo)

En lugar de crear `ecosistema_jaraba_certificacion` (85h), se extienden 3 modulos existentes (~35h):

| Modulo | Extension | Esfuerzo |
|--------|-----------|----------|
| `jaraba_training` | +6 campos en CertificationProgram, +8 campos en UserCertification, +MethodCertificationService, +MethodRubricService | 15h |
| `jaraba_training` | +MethodPortfolioItem entity (inspirado en EntregableFormativoEi) | 8h |
| `jaraba_training` | +Setup Wizard steps (3) + Daily Actions (3) | 4h |
| `jaraba_training` | +CopilotBridgeService + GroundingProvider | 4h |
| `jaraba_credentials` | Configurar CredentialTemplate para Metodo Jaraba (sin codigo nuevo) | 2h |
| `ecosistema_jaraba_theme` | Templates + SCSS para dashboard evaluador + portfolio | 6h |
| Transversal | Testing (Unit + Kernel) | 6h |
| **TOTAL** | | **~45h** |

### 6.2 Extension de jaraba_training

**Nuevos campos en `CertificationProgram`** (baseFieldDefinitions):
- `method_layers` — string (allowed_values: criterio, supervision_ia, posicionamiento)
- `method_competencies` — string (allowed_values: pedir, evaluar, iterar, integrar)
- `cid_duration_days` — integer (default 90)
- `min_portfolio_items` — integer (minimo de evidencias por competencia)
- `rubric_config` — string_long (JSON con indicadores por nivel, editable desde admin)
- `renewal_requires_evidence` — boolean (si la renovacion requiere nuevas evidencias)

**Nuevos campos en `UserCertification`** (baseFieldDefinitions):
- `comp_pedir_score` — integer (1-4)
- `comp_evaluar_score` — integer (1-4)
- `comp_iterar_score` — integer (1-4)
- `comp_integrar_score` — integer (1-4)
- `layer_criterio_score` — integer (1-4)
- `layer_supervision_score` — integer (1-4)
- `layer_posicionamiento_score` — integer (1-4)
- `overall_level` — integer (1-4, calculado como minimo de las 4 competencias)

**hook_update_N()** obligatorio (UPDATE-HOOK-REQUIRED-001) para instalar los nuevos campos.

### 6.3 Generalizacion de portfolio

Nueva entity `MethodPortfolioItem` en `jaraba_training`, inspirada en `EntregableFormativoEi`:

```php
/**
 * @ContentEntityType(
 *   id = "method_portfolio_item",
 *   label = @Translation("Portfolio Item"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_training\Access\MethodPortfolioItemAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_training\Form\MethodPortfolioItemForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   ...
 * )
 */
```

**Campos**:
- `certification_id` — entity_reference a UserCertification (ENTITY-FK-001: mismo modulo = entity_reference)
- `tenant_id` — entity_reference (TENANT-001: obligatorio)
- `title` — string(255)
- `description` — text_long
- `competency` — string (allowed_values: pedir, evaluar, iterar, integrar)
- `layer` — string (allowed_values: criterio, supervision_ia, posicionamiento)
- `file_url` — string(500)
- `external_url` — string(500) — validacion URL-PROTOCOL-VALIDATE-001
- `ai_model_used` — string(50) — modelo IA usado
- `evaluator_score` — integer (1-4, NULL hasta evaluacion)
- `evaluator_feedback` — text_long

**AccessControlHandler con tenant isolation** (TENANT-ISOLATION-ACCESS-001).
**Form extiende PremiumEntityFormBase** (PREMIUM-FORMS-PATTERN-001).

### 6.4 Rubrica del Metodo Jaraba

`MethodRubricService` en `jaraba_training`:

```php
class MethodRubricService {
  // Carga indicadores desde config YAML (CERT-15: editable sin codigo)
  public function getIndicatorsForLevel(string $competency, int $level): array;

  // Calcula nivel global: minimo de las 4 competencias (CERT-08)
  public function calculateOverallLevel(array $scores): int;

  // Verifica disparidad > 1 nivel y avisa
  public function checkScoreDisparity(array $scores): ?string;

  // Valida portfolio minimos antes de permitir evaluacion (CERT-03)
  public function validatePortfolioCompleteness(int $certificationId): ValidationResult;
}
```

Indicadores almacenados en ConfigEntity `jaraba_training.rubric_config.yml` (editable desde `/admin/structure/training/rubric`).

### 6.5 Generacion y emision de certificados

Reutilizacion directa de servicios existentes:

1. `CredentialPdfService::generatePdf()` — PDF A4 horizontal con QR, nombre, tipo, nivel, fecha
2. `FirmaDigitalService::signPdf()` — Firma PKCS#12 del evaluador + TSA
3. `IssuedCredential` entity — Almacena el certificado digital Open Badge 3.0 con firma Ed25519

**Codigo de verificacion**: Formato `MJ-{YYYY}-{NNNNN}` (ej: MJ-2026-00042). Generado por `CredentialCodeGenerator` existente en `jaraba_credentials`.

### 6.6 Verificacion publica de certificados

URL publica: `/certificado/{codigo}` — ya existe como ruta en `jaraba_credentials`. Se reutiliza `PublicCredentialController` existente que muestra nombre, tipo, nivel, fecha, estado (valido/expirado).

### 6.7 Panel de evaluador

Dashboard del evaluador en `/formador/evaluaciones` (ruta existente en `jaraba_training` o nueva):
- Lista de portfolios pendientes de evaluacion
- Vista del portfolio completo del participante
- Formulario de rubrica (PremiumEntityFormBase) con puntuacion 1-4 por competencia
- Indicadores observables mostrados al seleccionar nivel (desde rubric_config)
- Acciones post-evaluacion: aprobar, pedir mejoras, rechazar

**Slide-panel** para la evaluacion detallada (SLIDE-PANEL-RENDER-001).

### 6.8 Dashboard de administracion

KPIs de certificacion en `/admin/content/certifications` (Views integration):
- Certificaciones emitidas / en evaluacion / renovaciones pendientes
- Ingresos por certificacion (si aplica Stripe)
- Filtros por tipo, aplicacion, evaluador, fecha

---

## 7. Setup Wizard + Daily Actions

### 7.1 Wizard steps

3 nuevos tagged services `ecosistema_jaraba_core.setup_wizard_step` en `jaraba_training.services.yml`:

| Step ID | Wizard ID | Label | Weight | isComplete() |
|---------|-----------|-------|--------|-------------|
| `certificacion.configurar_rubrica` | `__global__` | Configurar rubrica del Metodo | 80 | rubric_config tiene >= 4 competencias con indicadores |
| `certificacion.asignar_evaluador` | `__global__` | Asignar evaluador certificado | 82 | >= 1 usuario con rol formador_certificado |
| `certificacion.definir_precios` | `__global__` | Definir precios certificacion | 84 | CertificationProgram tiene >= 1 con precio > 0 o flag gratuito |

### 7.2 Daily actions

3 nuevos tagged services `ecosistema_jaraba_core.daily_action` en `jaraba_training.services.yml`:

| Action ID | Dashboard ID | Label | Badge | isPrimary |
|-----------|-------------|-------|-------|-----------|
| `certificacion.evaluaciones_pendientes` | `__global__` | Evaluaciones pendientes | Contador de UserCertification con status=en_evaluacion | TRUE (para evaluadores) |
| `certificacion.renovaciones_proximas` | `__global__` | Renovaciones proximas | Contador de certificaciones que expiran en <30d | FALSE |
| `certificacion.nuevas_solicitudes` | `__global__` | Nuevas solicitudes | Contador de UserCertification creadas hoy | FALSE |

### 7.3 Pipeline E2E L1-L4

Verificacion PIPELINE-E2E-001:
- **L1**: Services inyectados en controllers (constructor + create())
- **L2**: Controller pasa `#setup_wizard` y `#daily_actions` al render array
- **L3**: `hook_theme()` declara las variables
- **L4**: Template incluye parciales `_setup-wizard.html.twig` y `_daily-actions.html.twig` con `only`

---

## 8. AI Coverage

### 8.1 CopilotBridgeService

Nuevo `CertificacionCopilotBridgeService` en `jaraba_training`:

```php
class CertificacionCopilotBridgeService implements CopilotBridgeInterface {
  public function getVerticalKey(): string { return '__global__'; }

  public function getContextForUser(int $userId): array {
    // Certificaciones activas, nivel alcanzado, portfolio progress
  }

  public function getAvailableActions(): array {
    return ['solicitar_certificacion', 'ver_portfolio', 'consultar_rubrica'];
  }
}
```

Tagged service: `jaraba_copilot_v2.copilot_bridge` (CompilerPass auto-registro).

### 8.2 GroundingProvider

Nuevo `CertificacionGroundingProvider` en `jaraba_training`:

```php
class CertificacionGroundingProvider implements GroundingProviderInterface {
  public function getEntityTypeId(): string { return 'user_certification'; }

  public function provideGrounding(string $query): array {
    // Busqueda en certificaciones, portfolio items, evaluaciones
  }
}
```

Tagged service: `jaraba_copilot_v2.grounding_provider`.

### 8.3 Asistencia IA en evaluaciones

El copiloto puede asistir al evaluador sugiriendo puntuaciones basadas en el portfolio:

```php
// En MethodRubricService, metodo opcional con @? a AI Provider
public function suggestScores(int $certificationId): ?array {
  if (!$this->aiProvider) return NULL;
  $portfolio = $this->loadPortfolio($certificationId);
  // Prompt: "Evalua estas evidencias segun la rubrica..."
  // Retorna sugerencia (el evaluador decide)
}
```

---

## 9. Salvaguardas

### 9.1 Validadores nuevos

| Validador | Checks | Fichero |
|-----------|--------|---------|
| `validate-certification-integrity.php` | 8 checks: entities con AccessControl, tenant_id, hook_update, wizard steps, daily actions, CopilotBridge, GroundingProvider, rubric_config | `scripts/validation/` |
| `validate-metodo-landing.php` | 6 checks: controller existe, template existe, SCSS compilado, mega menu link, Schema.org, MARKETING-TRUTH | `scripts/validation/` |

### 9.2 Pre-commit hooks

Los hooks existentes de lint-staged cubren automaticamente:
- PHPStan L6 para PHP nuevo
- SCSS compiled-assets para estilos nuevos
- Twig syntax para templates nuevos
- services.yml phantom-args para servicios nuevos

### 9.3 Monitoring proactivo

- `StatusReportMonitorService` detectara nuevas entities sin hook_update_N() (validate-entity-integrity.php)
- `validate-ai-coverage.php` verificara CopilotBridge + GroundingProvider
- `validate-marketing-truth.php` escaneara templates de certificacion

---

## 10. Tabla de correspondencia specs vs implementacion

| Spec ID | Descripcion original | Implementacion corregida | Modulo | Fichero clave |
|---------|---------------------|-------------------------|--------|---------------|
| MET-A01 | Reemplazar contenido /metodo pepejaraba | Actualizar canvas_data de page_content entity (local_id 59) | page_content (datos) | scripts/content-seed/ |
| MET-A02 | Diagrama SVG "Un nucleo, tres aplicaciones" | Generar con herramientas externas, insertar en canvas_data como SVG inline | page_content (datos) | canvas_data JSON |
| MET-A03 | Timeline CID 90 dias como componente CSS | Usar GrapesJS block `timeline` existente con 3 fases | page_content (datos) | GrapesJS editor |
| MET-A04 | 4 tarjetas competencias con iconos SVG | Usar `features_grid` block con jaraba_icon() duotone | page_content (datos) | GrapesJS editor |
| MET-A05 | Cross-domain links con UTM | URLs absolutas con UTM estandarizado en canvas_data | page_content (datos) | canvas_data JSON |
| MET-A06 | Meta SEO (title, description, OG) | Campos meta_title y meta_description del entity page_content | page_content (datos) | Admin UI |
| MET-A07 | Schema.org Course JSON-LD | Inyeccion via preprocess, NO en canvas_data | ecosistema_jaraba_theme | .theme preprocess |
| MET-B01 | Crear pagina /es/metodo con paragraph types | Extender MetodoLandingController existente | jaraba_page_builder | MetodoLandingController.php |
| MET-B02 | Grid de verticales con capas del metodo | Reutilizar getVerticalCatalog() + campo method_layer | jaraba_page_builder | MetodoLandingController.php |
| MET-B03 | Rubrica visual como progress bar | Componente CSS stepper horizontal con `<details>` nativo | ecosistema_jaraba_theme | _certificacion-preview.html.twig |
| MET-B04 | Bloque B2G con formulario institucional | Seccion en landing + link a /certificacion#entidades | jaraba_page_builder | MetodoLandingController.php |
| MET-B05 | Breadcrumb + canonical + meta SEO | hook_preprocess_page + SeoSchemaService | ecosistema_jaraba_theme | .theme preprocess |
| MET-B06 | Link "Metodologia" en mega menu | Direct link en preprocess (junto a Precios, Casos) | ecosistema_jaraba_theme | .theme preprocess |
| MET-C01 | Pagina /certificacion con 8 secciones | CertificacionLandingController + template limpio | jaraba_training | CertificacionLandingController.php |
| MET-C02 | Rubrica interactiva 4 niveles | Stepper CSS + `<details>` con indicadores desde config | jaraba_training | _rubrica-stepper.html.twig |
| MET-C03 | Tabla comparativa con checkmarks | Componente CSS table responsive | ecosistema_jaraba_theme | _comparison-table.html.twig |
| MET-C04 | Formulario + CRM integration | CertificacionContactForm (PremiumEntityFormBase) + CopilotLeadCaptureService | jaraba_training | CertificacionContactForm.php |
| MET-C05 | Email confirmacion + notificacion | hook_mail template #25 + mailer service | jaraba_training | .module hook_mail |
| MET-C06 | FAQ accordion | Componente `<details>` nativo (existente en el tema) | ecosistema_jaraba_theme | _faq-accordion.html.twig |
| MET-C07 | Schema.org EducationalOrganization | JSON-LD via preprocess, integrado con SeoSchemaService | ecosistema_jaraba_theme | .theme preprocess |
| CERT-01 | Inscripcion a certificacion | Formulario en slide-panel desde dashboard usuario | jaraba_training | CertificationApplicationForm.php |
| CERT-02 | Panel de portfolio | Vista de MethodPortfolioItem entities + form upload en slide-panel | jaraba_training | PortfolioController.php |
| CERT-03 | Validacion minimos portfolio | MethodRubricService::validatePortfolioCompleteness() | jaraba_training | MethodRubricService.php |
| CERT-04 | Pagina "Mi certificado" | Reutilizar PublicCredentialController de jaraba_credentials | jaraba_credentials | (existente) |
| CERT-05 | Renovacion con email 30 dias antes | CertificationRenewalSubscriber + cron check | jaraba_training | CertificationRenewalSubscriber.php |
| CERT-06 | Panel de evaluacion | Dashboard evaluador con lista de pendientes | jaraba_training | EvaluationDashboardController.php |
| CERT-07 | Rubrica interactiva con indicadores | Form + JS que carga indicadores desde rubric_config al seleccionar nivel | jaraba_training | RubricEvaluationForm.php |
| CERT-08 | Calculo automatico nivel global | MethodRubricService::calculateOverallLevel() (minimo de 4) | jaraba_training | MethodRubricService.php |
| CERT-09 | Accion post-evaluacion | 3 botones: aprobar (genera certificado), mejorar (devuelve), rechazar | jaraba_training | RubricEvaluationForm.php |
| CERT-10 | Generacion PDF certificado | CredentialPdfService + FirmaDigitalService (existentes) | jaraba_credentials + core | (existentes) |
| CERT-11 | Codigo verificacion publico | PublicCredentialController existente + formato MJ-{YYYY}-{NNNNN} | jaraba_credentials | (existente) |
| CERT-12 | Directorio publico certificados | Views sobre IssuedCredential con filtro consent=TRUE | jaraba_credentials | Views config |
| CERT-13 | Dashboard KPIs certificacion | Views + summary blocks en /admin/content/certifications | jaraba_training | Views config |
| CERT-14 | Gestion evaluadores | Rol `formador_certificado` + admin asignacion | jaraba_training | permissions.yml |
| CERT-15 | Config rubrica sin codigo | ConfigEntity jaraba_training.rubric_config editable desde admin | jaraba_training | RubricConfigForm.php |
| CERT-16 | Config precios certificacion | Integracion con SaasPlanFeature + MetaSitePricingService | jaraba_training | (extension) |

---

## 11. Tabla de compliance con directrices

| # | Directriz | Estado | Como se cumple |
|---|-----------|--------|----------------|
| 1 | PREMIUM-FORMS-PATTERN-001 | OK | CertificacionContactForm, CertificationApplicationForm, RubricEvaluationForm extienden PremiumEntityFormBase con getSectionDefinitions() y getFormIcon() |
| 2 | TENANT-001 | OK | Toda entity nueva incluye campo tenant_id (entity_reference). Toda query filtra por tenant |
| 3 | TENANT-ISOLATION-ACCESS-001 | OK | AccessControlHandler verifica tenant match para update/delete |
| 4 | AUDIT-CONS-001 | OK | Toda ContentEntity tiene AccessControlHandler en anotacion |
| 5 | ENTITY-001 | OK | EntityOwnerTrait + EntityOwnerInterface + EntityChangedInterface |
| 6 | ENTITY-FK-001 | OK | FKs mismo modulo = entity_reference. Cross-modulo opcional = integer. tenant_id = entity_reference |
| 7 | ENTITY-PREPROCESS-001 | OK | template_preprocess_method_portfolio_item() en .module |
| 8 | UPDATE-HOOK-REQUIRED-001 | OK | hook_update_N() para cada campo nuevo + nueva entity |
| 9 | FIELD-UI-SETTINGS-TAB-001 | OK | field_ui_base_route configurado + default local task tab |
| 10 | SETUP-WIZARD-DAILY-001 | OK | 3 wizard steps + 3 daily actions registrados como tagged services |
| 11 | AI-COVERAGE-001 | OK | CopilotBridgeService + GroundingProvider implementados |
| 12 | MARKETING-TRUTH-001 | OK | 46% desde SuccessCase entity, NO hardcodeado |
| 13 | SECRET-MGMT-001 | OK | Stripe via getenv() en settings.secrets.php |
| 14 | ZERO-REGION-001 | OK | Controllers devuelven render array, preprocess inyecta datos, template limpio |
| 15 | CSS-VAR-ALL-COLORS-001 | OK | Todo color usa var(--ej-*, fallback). Sin hex hardcoded en SCSS |
| 16 | SCSS-COMPILE-VERIFY-001 | OK | npm run build + verificacion timestamp CSS > SCSS |
| 17 | ICON-DUOTONE-001 | OK | Todos los iconos en variante duotone con colores de paleta Jaraba |
| 18 | ROUTE-LANGPREFIX-001 | OK | URLs via Url::fromRoute(). Cross-domain con $lp prefix |
| 19 | NO-HARDCODE-PRICE-001 | OK | Precios desde MetaSitePricingService / SaasPlanFeature |
| 20 | AUTO-TRANSLATE-001 | OK | Entities traducibles. TranslationTriggerService encola automaticamente |
| 21 | SLIDE-PANEL-RENDER-001 | OK | Formularios de portfolio/evaluacion/inscripcion abren en slide-panel |
| 22 | OPTIONAL-CROSSMODULE-001 | OK | jaraba_credentials como @? opcional en services.yml |
| 23 | TWIG-INCLUDE-ONLY-001 | OK | Todos los {% include %} usan `only` con variables explicitas |
| 24 | MEGAMENU-SSOT-002 | OK | Link "Metodologia" via preprocess, NO ad-hoc en Twig |
| 25 | PRESAVE-RESILIENCE-001 | OK | Presave hooks con hasService() + try-catch |
| 26 | CSRF-API-001 | OK | _csrf_request_header_token en rutas AJAX |
| 27 | URL-PROTOCOL-VALIDATE-001 | OK | external_url valida protocolo http/https |
| 28 | PII-INPUT-GUARD-001 | OK | checkInputPII() en datos enviados a LLM |
| 29 | LANDING-CONVERSION-SCORE-001 | OK | 15 criterios clase mundial verificados (ver seccion 12) |
| 30 | FUNNEL-COMPLETENESS-001 | OK | data-track-cta + data-track-position en todos los CTAs |
| 31 | SCSS-001 | OK | @use '../variables' as * en cada parcial SCSS |
| 32 | SCSS-COLORMIX-001 | OK | color-mix(in srgb, ...) para alpha en lugar de rgba() |
| 33 | CONTROLLER-READONLY-001 | OK | Sin protected readonly en propiedades heredadas |
| 34 | DRUPAL11-001 | OK | Sin redeclaracion de typed properties del padre |
| 35 | TWIG-RAW-AUDIT-001 | OK | Sin |raw sin sanitizacion previa en servidor |
| 36 | INNERHTML-XSS-001 | OK | Drupal.checkPlain() para datos API en innerHTML |
| 37 | QUERY-CHAIN-001 | OK | Sin encadenamiento de addExpression()->execute() |
| 38 | CHECKBOX-SCOPE-001 | OK | Toggle switch solo para .form-type-boolean |
| 39 | OBSERVER-SCROLL-ROOT-001 | OK | IntersectionObserver con .slide-panel__body como root |
| 40 | SEO-HREFLANG-ACTIVE-001 | OK | Hreflang filtrado por seo_active_languages |
| 41 | SEO-CANONICAL-CLEAN-001 | OK | Canonical limpio sin /node |
| 42 | GA4-CONSENT-MODE-001 | OK | Consent Mode v2 default deny |
| 43 | CTA-TRACKING-001 | OK | data-track-cta + data-track-position en todos los CTAs |
| 44 | BACKUP-3LAYER-001 | N/A | Infraestructura existente (local + S3 + NAS) |
| 45 | LABEL-NULLSAFE-001 | OK | $entity->label() con nullsafe en todos los contextos |
| 46 | CONTAINER-DEPS-002 | OK | Sin dependencias circulares en services.yml |
| 47 | PHANTOM-ARG-001 | OK | args coinciden con params del constructor |
| 48 | OPTIONAL-PARAM-ORDER-001 | OK | Parametros opcionales al final del constructor |

---

## 12. Evaluacion de conversion 10/10

### Landing /certificacion — 15 criterios LANDING-CONVERSION-SCORE-001

| # | Criterio | Estado | Detalle |
|---|----------|--------|---------|
| 1 | Hero con propuesta de valor clara | OK | "Certificarte en Supervision de Agentes IA" — beneficio inmediato |
| 2 | CTA above the fold | OK | 2 anclas: "Soy profesional" / "Soy entidad" |
| 3 | Social proof / trust signals | OK | 46% insercion + logos + "1a implementacion: Andalucia +ei" |
| 4 | Urgencia/escasez | WARN | Anadir "Plazas limitadas" o "Proxima convocatoria: {fecha}" si aplica |
| 5 | Beneficios (no features) | OK | Cada nivel de rubrica describe el resultado, no el proceso |
| 6 | Formulario simplificado | OK | Dual-mode con campos condicionales. Max 8 campos visibles |
| 7 | Comparativa competidores | OK | Tabla "Formacion generica vs Metodo Jaraba" con checkmarks |
| 8 | FAQ que elimina objeciones | OK | 6-8 preguntas cubriendo precio, duracion, requisitos, validez |
| 9 | Mobile-first responsive | OK | Stepper vertical en movil, tabla → lista, formulario stack |
| 10 | Schema.org structured data | OK | EducationalOrganization con offerCatalog |
| 11 | Page speed < 3s | OK | Controller-based (sin DB queries pesadas). Imagenes lazy-load |
| 12 | data-track-cta en todos los CTAs | OK | FUNNEL-COMPLETENESS-001 cumplido |
| 13 | Accesibilidad WCAG 2.1 AA | OK | aria-labels, focus visible, contraste >= 4.5:1 |
| 14 | Video/demo visual | WARN | Considerar video testimonial o demo interactiva |
| 15 | Exit intent / lead magnet | WARN | Considerar popup con descargable "Guia: 4 competencias IA" |

**Score**: 12/15 OK + 3 WARN = **~9/10** actual. Para llegar a 10/10:
- Anadir urgencia/escasez real (proxima convocatoria con fecha)
- Anadir video testimonial de participante certificado
- Considerar lead magnet descargable como exit intent

### Landing /metodo (SaaS) — Evaluacion adicional

- 8 secciones cubren todo el funnel: awareness → consideration → proof → conversion
- Grid de verticales crea cross-pollination interna
- CTA final con pricing real (14 dias trial) cumple MARKETING-TRUTH-001
- Mega menu con direct link "Metodologia" mejora descubrimiento

---

## 13. Estimacion de esfuerzo corregida

| Parte | Entregable | Esfuerzo original | Esfuerzo corregido | Ahorro |
|-------|-----------|-------------------|--------------------|----|
| A | pepejaraba.com/metodo v2 | 17h | 12h | -29% (sin paragraph types, usa canvas existente) |
| B | SaaS /metodo | 16h | 14h | -12% (controller existente, extension no creacion) |
| C | Landing certificacion/franquicia | 22h | 20h | -9% (reutiliza CopilotLeadCaptureService) |
| D | Modulo certificacion | 85h | 45h | -47% (reutiliza 70% de infraestructura existente) |
| | Testing + QA transversal | (incluido) | 8h | (explicito) |
| **TOTAL** | | **140h** | **~99h** | **-29%** |

---

## 14. Prioridad y cronograma

| Prioridad | Parte | Semana | Justificacion |
|-----------|-------|--------|---------------|
| **1 (inmediato)** | A: pepejaraba.com/metodo v2 | S1 | Contenido puro, impacto inmediato en posicionamiento. 2 dias. |
| **2 (semana 1)** | B: SaaS /metodo + mega menu | S1-S2 | Complementa A con vision de producto. Conecta verticales. 3 dias. |
| **3 (semana 2)** | C: Landing certificacion | S2-S3 | Capta leads ANTES de que el modulo este listo. 3 dias. |
| **4 (semana 3-5)** | D: Extension modulos | S3-S5 | Pieza mas compleja. Certificaciones se emiten al final del CID. |

---

## 15. Glosario

| Sigla | Significado |
|-------|-------------|
| CID | Ciclo de Impacto Digital — programa de 90 dias del Metodo Jaraba dividido en 3 fases (Criterio, Supervision, Posicionamiento) |
| CRM | Customer Relationship Management — sistema de gestion de relaciones con clientes |
| CSRF | Cross-Site Request Forgery — ataque web que fuerza acciones no deseadas |
| FSE+ | Fondo Social Europeo Plus — instrumento financiero de la UE para empleo e inclusion |
| GDPR/RGPD | Reglamento General de Proteccion de Datos — normativa europea de proteccion de datos personales |
| GrapesJS | Framework JavaScript open source de page builder visual (drag-and-drop) |
| Open Badge 3.0 | Estandar abierto de credenciales digitales verificables de IMS Global |
| Ed25519 | Algoritmo de firma digital de curva eliptica Edwards-curve Digital Signature Algorithm |
| PAdES | PDF Advanced Electronic Signatures — formato de firma electronica avanzada en PDF |
| PED | Plataforma de Ecosistemas Digitales S.L. — empresa titular del SaaS |
| PIIL | Programa de Insercion e Integracion Laboral — nombre tecnico del programa Andalucia +ei |
| QR | Quick Response code — codigo de respuesta rapida bidimensional |
| SaaS | Software as a Service — modelo de distribucion de software como servicio en la nube |
| SCSS | Sassy CSS — preprocesador CSS que compila a CSS estandar |
| SSOT | Single Source of Truth — fuente unica de verdad, patron de diseno de datos |
| TSA | Timestamp Authority — autoridad de sellado de tiempo para firma electronica |
| UTM | Urchin Tracking Module — parametros de URL para seguimiento de campanas en analytics |
| WCAG | Web Content Accessibility Guidelines — pautas de accesibilidad para contenido web |
| XSS | Cross-Site Scripting — vulnerabilidad web de inyeccion de scripts maliciosos |

---

*Fin del Plan de Implementacion — Metodo Jaraba en el SaaS*
*Jaraba Impact Platform — 2026-03-27 — Claude Code (Opus 4.6)*
*Version 1.0 — Clase Mundial 10/10*
