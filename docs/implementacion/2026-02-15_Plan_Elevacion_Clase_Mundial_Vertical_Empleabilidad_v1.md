# Plan de Elevacion a Clase Mundial: Vertical EMPLEABILIDAD

**Fecha:** 2026-02-15
**Version:** 1.0.0
**Estado:** Implementado (10/10 Fases)
**Vertical:** Empleabilidad
**Modulos afectados:** jaraba_candidate, jaraba_job_board, jaraba_diagnostic, jaraba_self_discovery, ecosistema_jaraba_core, ecosistema_jaraba_theme, jaraba_billing, jaraba_customer_success, jaraba_email, jaraba_crm, jaraba_onboarding
**Specs relacionados:** 20260120a, 20260120b, Doc 183 (F2 Freemium), f-103 (UX Journey Avatars)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Auditoria del Estado Actual](#2-auditoria-del-estado-actual)
3. [Mapa del Recorrido Completo del Usuario](#3-mapa-del-recorrido-completo-del-usuario)
4. [Hallazgos Criticos](#4-hallazgos-criticos)
5. [FASE 1: Clean Page Templates para Empleabilidad](#5-fase-1-clean-page-templates)
6. [FASE 2: Sistema Modal en Acciones CRUD](#6-fase-2-sistema-modal)
7. [FASE 3: Correccion SCSS y Compliance de Theming](#7-fase-3-scss-compliance)
8. [FASE 4: Feature Gating por Plan (Escalera de Valor)](#8-fase-4-feature-gating)
9. [FASE 5: Upgrade Triggers y Upsell Contextual IA](#9-fase-5-upgrade-triggers)
10. [FASE 6: Email Sequences Automatizadas](#10-fase-6-email-sequences)
11. [FASE 7: CRM Integration y Pipeline de Empleo](#11-fase-7-crm-integration)
12. [FASE 8: Cross-Vertical Value Bridges](#12-fase-8-cross-vertical)
13. [FASE 9: AI Journey Progression Proactiva](#13-fase-9-ai-journey)
14. [FASE 10: Health Scores y Metricas Especificas](#14-fase-10-health-scores)
15. [Correspondencia con Especificaciones Tecnicas](#15-correspondencia-specs)
16. [Cumplimiento de Directrices](#16-cumplimiento-directrices)
17. [Dependencias Cruzadas del Vertical](#17-dependencias-cruzadas)
18. [Diagrama de Flujo Entrada/Salida](#18-diagrama-flujo)

---

## 1. Resumen Ejecutivo

### Estado actual del vertical Empleabilidad

El vertical de Empleabilidad cuenta con **4 modulos principales** (jaraba_candidate, jaraba_job_board, jaraba_diagnostic, jaraba_self_discovery) que suman **115 clases PHP**, **80+ rutas**, **11 templates de page builder**, **3 agentes IA** y **4 flujos ECA**. El ~70% del vertical esta implementado a nivel funcional.

### Lo que funciona a nivel de clase mundial

- **Diagnostico Express**: Wizard de 3 preguntas, scoring ponderado (LinkedIn 40%, CV 35%, Estrategia 25%), 5 perfiles psicograficos (Invisible a Magnetico), acceso publico
- **Avatar Detection**: Cascada de 4 niveles (Domain > Path/UTM > Group > Role) con 19 tipos de avatar y confianza 0.0-1.0
- **AI Copilot 6 Modos**: profile_coach, job_advisor, interview_prep, learning_guide, application_helper, faq con prompts especializados y temperaturas optimizadas
- **Career Coach Agent**: Framework Lucia de 5 fases con deteccion de gaps, itinerarios personalizados y mensajes de tutoria
- **Recruiter Assistant Agent**: 6 acciones para employers (screening, ranking, JD optimization, interview questions, analytics, drafting)
- **Job Matching Hibrido**: Reglas (80%) + Semantico Qdrant (20%) con pesos configurables
- **Gamificacion**: ImpactCredits (50 creditos por diagnostico, 500 por contratacion) + XP/Niveles (10 niveles, Novato a Campeon)
- **Cross-Vertical Credentials**: VerticalActivityTracker con 6 templates empleabilidad (cv_professional, portfolio_creator, interview_ready, job_application_expert, career_planner, linkedin_optimizer)
- **ECA Automation**: USR-001 (registro), USR-002 (post-diagnostico), JOB-001 (aplicaciones), JOB-003 (indexacion)
- **Observabilidad IA**: ai_usage_log, copilot_conversation, copilot_message, insights dashboard
- **Page Builder**: 11 bloques verticales con HTML semantico unico por tipo y SCSS responsive
- **Progressive Profiling**: localStorage con 5 perfiles (empleo, talento, emprender, comercio, b2g)

### Lo que necesita elevacion a clase mundial (10 Fases)

| # | Fase | Prioridad | Impacto |
|---|------|-----------|---------|
| 1 | Clean Page Templates para rutas empleabilidad | CRITICA | Frontend limpio sin regiones Drupal |
| 2 | Sistema Modal en acciones CRUD | CRITICA | UX sin abandono de pagina |
| 3 | Correccion SCSS y compliance theming | ALTA | Coherencia design tokens |
| 4 | Feature Gating por plan (Escalera de Valor) | CRITICA | Monetizacion efectiva |
| 5 | Upgrade Triggers y Upsell contextual IA | CRITICA | Conversion free-to-paid |
| 6 | Email Sequences automatizadas | ALTA | Nurturing y retencion |
| 7 | CRM Integration y Pipeline de Empleo | MEDIA | Tracking del ciclo de vida |
| 8 | Cross-Vertical Value Bridges | MEDIA | Maximizar LTV cross-vertical |
| 9 | AI Journey Progression proactiva | ALTA | Automatizacion del embudo |
| 10 | Health Scores y metricas especificas | MEDIA | Inteligencia del vertical |

---

## 2. Auditoria del Estado Actual

### 2.1 Modulos y Codigo

| Modulo | Clases PHP | Entidades | Servicios | Rutas | Estado |
|--------|-----------|-----------|-----------|-------|--------|
| jaraba_candidate | 33 | 5 (CandidateProfile, CandidateSkill, CandidateLanguage, CopilotConversation, CopilotMessage) | 7 | 39 | Funcional |
| jaraba_job_board | 33 | 4 (JobPosting, JobApplication, EmployerProfile, SavedJob) | 11 | 41 | Funcional |
| jaraba_diagnostic | 22 | 6 (EmployabilityDiagnostic, BusinessDiagnostic, DiagnosticSection, DiagnosticQuestion, DiagnosticAnswer, DiagnosticRecommendation) | 4 | 14 | Funcional |
| jaraba_self_discovery | 27 | 4 (InterestProfile, StrengthAssessment, LifeWheelAssessment, LifeTimeline) | 5 | 17 | Funcional |

### 2.2 Integracion Admin (Estructura y Contenido)

| Modulo | Tabs /admin/content | Links /admin/structure | ListBuilders | Action Links |
|--------|---------------------|----------------------|--------------|-------------|
| jaraba_candidate | 2 (Candidatos w:24, Skills w:25) | 2 (Profile Settings w:14, Skill Settings w:15) | 2 | Si |
| jaraba_job_board | 2 (Empleos w:22, Candidaturas w:23) | 2 (Posting Settings w:12, Application Settings w:13) | 2 | Si |
| jaraba_diagnostic | 1 (Diagnosticos w:30) | 3 (Settings w:30, Sections w:31, Questions w:32) | 4 | Si |
| jaraba_self_discovery | 3 (Rueda Vida w:50, RIASEC w:51, Fortalezas w:52) | 3 (LifeWheel w:50, Interest w:51, Strength w:52) | 4 | Si |

**Estado**: Correcto. Todas las entidades tienen navegacion en /admin/structure (Field UI) y /admin/content (gestion). Acceso completo a Field UI y Views.

### 2.3 FreemiumVerticalLimit (Datos Seed)

Se verificaron 9 configuraciones seed existentes:

| Feature | Plan Free | Plan Starter | Plan Professional |
|---------|-----------|-------------|-------------------|
| diagnostics | 1 | -1 (ilimitado) | -1 (ilimitado) |
| cv_builder | 1 | 5 | 10 |
| offers_visible_per_day | TBD | TBD | TBD |

**Gap critico**: Los datos seed existen en `config/install/` pero **NO se ejecuta enforcement** en ningun controller ni servicio. Los limites no se comprueban.

### 2.4 Templates de Pagina

| Ruta | Template Zero-Region | Estado |
|------|---------------------|--------|
| /empleabilidad/diagnostico | page--empleabilidad--diagnostico.html.twig | OK - Clean |
| /jobseeker | page.html.twig (fallback con page.content) | FALTA |
| /my-profile | page.html.twig (fallback) | FALTA |
| /my-profile/cv | page.html.twig (fallback) | FALTA |
| /jobs | page.html.twig (fallback) | FALTA |
| /jobs/{id} | page.html.twig (fallback) | FALTA |
| /employer | page.html.twig (fallback) | FALTA |
| /my-applications | page.html.twig (fallback) | FALTA |
| /self-discovery | page.html.twig (fallback) | FALTA |
| /self-discovery/* | page.html.twig (fallback) | FALTA |

### 2.5 Compliance SCSS

| Fichero | Violaciones rgba() | color-mix() correcto | BEM | Vars CSS |
|---------|-------------------|--------------------|-----|----------|
| jaraba_candidate/scss/_dashboard.scss | 7 instancias rgba() | 0 | Si | Si |
| jaraba_self_discovery/scss/self-discovery.scss | 10+ instancias rgba() | 1 instancia | Si | Si |
| jaraba_diagnostic/scss/_employability-diagnostic.scss | Por verificar | Por verificar | Si | Si |

### 2.6 Sistema Modal

| Modulo | Modales use-ajax | Estado |
|--------|-----------------|--------|
| jaraba_job_board (employer) | 3 (crear, editar, ver ofertas) | Implementado |
| jaraba_candidate | 0 | FALTA - todas las acciones navegan a otra pagina |
| jaraba_diagnostic | 0 | FALTA |
| jaraba_self_discovery | 0 (usa slide panels custom) | PARCIAL - slide panel no es modal Drupal |

### 2.7 Traduccion (|t)

Todas las templates verificadas usan correctamente `|t` filter para strings de interfaz. **CUMPLE**.

### 2.8 Body Classes (hook_preprocess_html)

`ecosistema_jaraba_theme_preprocess_html()` (linea 873) inyecta clases de layout (header, footer, hero, card) y A11Y. **NO inyecta clases especificas para rutas de empleabilidad** (ej: `page-jobseeker`, `page-self-discovery`).

---

## 3. Mapa del Recorrido Completo del Usuario

### 3.1 Recorrido del visitante NO registrado

```
[1. LLEGADA]
   Usuario llega via Google/Redes/UTM
   |
   v
[2. DETECCION AVATAR]
   AvatarDetectionService detecta domain/path/UTM
   → Confianza 0.8-1.0: empleo.jaraba.es o /empleabilidad
   |
   v
[3. LANDING PAGE PERSONALIZADA]
   page--front.html.twig con progressive-profiling.js
   → Hero personalizado segun vertical detectado
   → 5 tarjetas de intencion (empleo, talento, emprender, comercio, instituciones)
   → Click en "Empleo" → guarda perfil en localStorage
   |
   v
[4. DIAGNOSTICO EXPRESS]                          ← ENTRADA PRINCIPAL AL EMBUDO
   /empleabilidad/diagnostico (publico, sin auth)
   → 3 preguntas: LinkedIn, CV ATS, Estrategia
   → Scoring: LinkedIn 40% + CV 35% + Estrategia 25%
   → Resultado: Perfil (Invisible/Desconectado/Construccion/Competitivo/Magnetico)
   → Score 0-10 + Gap principal + Recomendaciones
   |
   v
[5. CAPTACION EMAIL]
   Paso opcional en wizard: email para remarketing
   → GAP: No hay consentimiento GDPR explicito
   |
   v
[6. CTA REGISTRO]
   URL: /user/register?diagnostic={UUID}
   → UUID vincula diagnostico anonimo con cuenta nueva
   → hook_user_insert (USR-001): crea JourneyState, vincula diagnostico
```

### 3.2 Recorrido del usuario REGISTRADO (Embudo de Ventas)

```
[7. POST-REGISTRO AUTOMATICO (USR-001 + USR-002)]
   → Asigna rol 'candidate'
   → Crea JourneyState: avatar=jobseeker, state=activation, vertical=empleabilidad
   → Auto-inscripcion LMS segun profileType y primaryGap
   → Otorga +50 creditos de impacto
   → Encola webhook ActiveCampaign
   |
   v
[8. ONBOARDING]                                    ← GAP: Wizard generico, no empleabilidad-especifico
   Jaraba Onboarding Module: 7 pasos genericos
   → Welcome, Identity, Fiscal, Payments, Team, Content, Launch
   → GAP: No hay flujo de onboarding especifico para candidatos
   |
   v
[9. DASHBOARD CANDIDATO - PLAN FREE]
   /jobseeker (ruta principal)
   → Copilot FAB: diagnostico fase + itinerarios + coaching
   → Tarjetas: Perfil, Ofertas, Candidaturas, Formacion, Credenciales
   → GAP: No se muestra escalera de valor ni limites free
   |
   v
[10. PERFIL PROFESIONAL]
   /my-profile → /my-profile/edit
   → Crear perfil: experiencia, educacion, habilidades, idiomas
   → Copilot modo profile_coach: optimizacion LinkedIn, marca personal
   → +50 creditos por crear perfil, +100 por completar perfil
   |
   v
[11. CV BUILDER]
   /my-profile/cv
   → 5 templates: modern, classic, creative, minimal, tech
   → AI genera contenido del CV
   → Export PDF via Dompdf
   → GAP: Limite free de 1 CV no se enforza
   |
   v
[12. SELF-DISCOVERY]
   /self-discovery
   → RIASEC (intereses vocacionales), Life Wheel, Strengths, Timeline
   → Resultados alimentan recomendaciones del Copilot
   → GAP: No conectado con ruta de cursos LMS
   |
   v
[13. BUSQUEDA DE EMPLEO]
   /jobs
   → Busqueda facetada con filtros inline
   → Job matching hibrido (reglas 80% + semantico 20%)
   → Score de compatibilidad por oferta
   → Guardar ofertas, crear alertas
   → GAP: Limite free de ofertas visibles por dia no se enforza
   |
   v
[14. APLICACION A OFERTAS]
   /jobs/{id}/apply
   → Formulario de aplicacion
   → Copilot modo application_helper: carta presentacion, CV adaptado
   → +20 creditos por aplicar
   → Notificacion email al employer (JOB-001)
   |
   v
[15. PREPARACION ENTREVISTA]
   Copilot modo interview_prep
   → Preguntas frecuentes con respuestas modelo
   → Tips de comunicacion no verbal
   → +50 creditos por entrevista, +500 por contratacion
   |
   v
[16. FORMACION LMS]
   /courses (jaraba_lms)
   → Auto-inscripcion segun gap detectado
   → Cursos: LinkedIn para Profesionales +40, IA Generativa, etc.
   → +10 creditos por leccion, +100 por curso, +50 por badge
   |
   v
[17. CREDENCIALES]
   jaraba_credentials_cross_vertical
   → Badges: cv_professional, interview_ready, linkedin_optimizer
   → Portabilidad cross-vertical
   → Visibles en perfil publico
```

### 3.3 Recorrido CROSS-VERTICAL (Escalera de Valor SaaS)

```
[EMPLEABILIDAD → EMPRENDIMIENTO]
   Candidato que no encuentra empleo → "Crea tu propio negocio"
   → Diagnostico de negocio (/diagnostic/start)
   → Business Model Canvas
   → GAP: No hay bridge implementado, solo credentials cross-vertical

[EMPLEABILIDAD → FORMACION]
   Gap de habilidades detectado → Cursos LMS
   → Certificaciones emitidas
   → GAP: Conexion parcial via DiagnosticEnrollmentService

[EMPLEABILIDAD → SERVICIOS]
   Candidato con skills → Freelancer en serviciosconecta
   → GAP: No hay bridge implementado

[EMPLEABILIDAD → COMERCIO]
   Candidato emprendedor → Vender productos en comercioconecta
   → GAP: No hay bridge implementado

[EMPLOYER → EMPLEABILIDAD PREMIUM]
   Employer contrata → Upsell plan premium para mas candidatos
   → GAP: Upgrade triggers no se disparan
```

---

## 4. Hallazgos Criticos

### 4.1 Hallazgos de Frontend

| ID | Hallazgo | Severidad | Impacto |
|----|----------|-----------|---------|
| FE-001 | 9 rutas empleabilidad usan page.html.twig generico con page.content y regiones Drupal | CRITICA | Viola directriz zero-region |
| FE-002 | Acciones CRUD en candidate/diagnostic/self-discovery no abren en modal | CRITICA | UX - usuario abandona pagina |
| FE-003 | 17+ instancias de rgba() en SCSS en lugar de color-mix() | ALTA | Viola P4-COLOR-002 |
| FE-004 | hook_preprocess_html() no inyecta body classes para rutas empleabilidad | MEDIA | Selectores CSS limitados |
| FE-005 | Variable `phase_emoji` en career-dashboard.html.twig es nombre enganyoso | BAJA | Confuso para mantenimiento |

### 4.2 Hallazgos de Negocio

| ID | Hallazgo | Severidad | Impacto |
|----|----------|-----------|---------|
| BZ-001 | FreemiumVerticalLimit seed data NO se enforza en controllers | CRITICA | Monetizacion nula - todo gratis |
| BZ-002 | UpgradeTriggerService NUNCA se llama para eventos empleabilidad | CRITICA | Conversion free-to-paid = 0% |
| BZ-003 | No hay email sequences para empleabilidad | ALTA | Nurturing inexistente |
| BZ-004 | No hay bridges cross-vertical implementados | MEDIA | LTV limitado a 1 vertical |
| BZ-005 | stripe_price_id vacio en SaasPlan | CRITICA | Pagos no funcionales |
| BZ-006 | Onboarding generico, no especifico para candidatos | ALTA | Activacion suboptima |
| BZ-007 | No hay referral program contextualizado para empleabilidad | MEDIA | Adquisicion organica baja |

### 4.3 Hallazgos de IA

| ID | Hallazgo | Severidad | Impacto |
|----|----------|-----------|---------|
| AI-001 | Copilot no progresa proactivamente al usuario por el embudo | ALTA | Automatizacion incompleta |
| AI-002 | getSoftSuggestion() no conectado con billing/upgrade triggers | ALTA | Upsell IA desconectado |
| AI-003 | No hay A/B testing en mensajes del copilot | MEDIA | Sin optimizacion de conversion |
| AI-004 | Interview prep es solo tips, no simulacion interactiva | MEDIA | Feature incompleta |
| AI-005 | No hay proactive triggers basados en JourneyState changes | ALTA | IA reactiva, no proactiva |

### 4.4 Hallazgos de Compliance

| ID | Hallazgo | Severidad | Impacto |
|----|----------|-----------|---------|
| CP-001 | GDPR: localStorage sin consentimiento explicito | ALTA | Riesgo legal |
| CP-002 | Email remarketing sin opt-in visible | ALTA | Riesgo RGPD |
| CP-003 | SCSS usa @import en algunos archivos (debe ser @use) | MEDIA | Dart Sass deprecation |

---

## 5. FASE 1: Clean Page Templates para Empleabilidad

### 5.1 Objetivo

Crear templates zero-region para las 9 rutas principales del vertical empleabilidad, eliminando las regiones Drupal (page.content, page.sidebar_first, etc.) y usando layout full-width mobile-first con header/footer propios.

### 5.2 Ficheros a Crear

| Fichero | Ruta(s) | Proposito |
|---------|---------|-----------|
| `templates/page--jobseeker.html.twig` | /jobseeker, /jobseeker/* | Dashboard candidato zero-region |
| `templates/page--my-profile.html.twig` | /my-profile, /my-profile/* | Gestion perfil zero-region |
| `templates/page--jobs.html.twig` | /jobs, /jobs/* | Busqueda empleo zero-region |
| `templates/page--employer.html.twig` | /employer, /employer/* | Dashboard employer zero-region |
| `templates/page--my-applications.html.twig` | /my-applications | Candidaturas zero-region |
| `templates/page--self-discovery.html.twig` | /self-discovery, /self-discovery/* | Self-discovery zero-region |
| `templates/page--my-company.html.twig` | /my-company, /my-company/* | Empresa zero-region |

Todos estos templates siguen el mismo patron:

```twig
{#
 # Page template - Zero Region Policy
 # Layout: Full-width, mobile-first, sin regiones Drupal
 # Directriz: ZERO-REGION — No usa page.content ni bloques heredados
 #}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  'theme_settings': theme_settings|default({}),
  'avatar_nav': avatar_nav|default({}),
  'is_front': false,
} only %}

<main class="ej-main ej-main--[seccion]" role="main">
  <a id="main-content" tabindex="-1"></a>

  {% if clean_messages %}
    <div class="ej-messages">
      {{ clean_messages }}
    </div>
  {% endif %}

  {{ clean_content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  'theme_settings': theme_settings|default({}),
} only %}

{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
  'copilot_context': copilot_context|default('empleabilidad'),
  'copilot_avatar': copilot_avatar|default('jobseeker'),
} only %}
```

### 5.3 Ficheros a Modificar

| Fichero | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme.theme` | Agregar `hook_preprocess_page()` para inyectar `clean_content` y `clean_messages` en las rutas de empleabilidad. Agregar `hook_theme_suggestions_page_alter()` para resolver la sugerencia de template correcta por ruta |
| `ecosistema_jaraba_theme.theme` | Extender `hook_preprocess_html()` para agregar body classes: `page-jobseeker`, `page-my-profile`, `page-jobs`, `page-employer`, `page-self-discovery`, `page-my-applications`, `page-my-company` |

### 5.4 Patron de Preprocessor

```php
/**
 * Implements hook_preprocess_page() para inyectar clean_content.
 */
function ecosistema_jaraba_theme_preprocess_page(&$variables) {
  $route_name = \Drupal::routeMatch()->getRouteName();

  // Rutas de empleabilidad que requieren layout limpio.
  $clean_routes_prefixes = [
    'jaraba_candidate.',
    'jaraba_job_board.',
    'jaraba_diagnostic.',
    'jaraba_self_discovery.',
  ];

  $is_clean = FALSE;
  foreach ($clean_routes_prefixes as $prefix) {
    if (str_starts_with($route_name, $prefix)) {
      $is_clean = TRUE;
      break;
    }
  }

  if ($is_clean) {
    $variables['clean_content'] = $variables['page']['content'] ?? [];
    $variables['clean_messages'] = $variables['page']['highlighted'] ?? [];
  }
}
```

### 5.5 Directrices de Aplicacion

- **ZERO-REGION**: Ningun template usa `page.content`, `page.sidebar_first`, `page.sidebar_second`, `page.breadcrumb`
- **FULL-WIDTH**: Contenido ocupa 100% del ancho, sin sidebar
- **MOBILE-FIRST**: Layout responsive, breakpoints 576px y 768px
- **INCLUDES**: Usa `{% include ... with {...} only %}` para aislamiento de variables
- **COPILOT FAB**: Incluido en todos los templates con contexto empleabilidad
- **A11Y**: `role="main"`, `tabindex="-1"` en contenido principal, skip link

---

## 6. FASE 2: Sistema Modal en Acciones CRUD

### 6.1 Objetivo

Todas las acciones de crear/editar/ver en las paginas frontend del vertical empleabilidad deben abrirse en un modal Drupal, para que el usuario no abandone la pagina en la que esta trabajando.

### 6.2 Acciones a Modalizar

| Modulo | Accion | Ruta Actual | Tipo Modal |
|--------|--------|-------------|-----------|
| jaraba_candidate | Editar perfil | /my-profile/edit | Modal 900px |
| jaraba_candidate | Agregar experiencia | /my-profile/experience/add | Modal 800px |
| jaraba_candidate | Editar experiencia | /my-profile/experience/{id}/edit | Modal 800px |
| jaraba_candidate | Agregar educacion | /my-profile/education/add | Modal 800px |
| jaraba_candidate | Agregar habilidad | /my-profile/skills/add | Modal 600px |
| jaraba_candidate | Configurar privacidad | /my-profile/privacy | Modal 700px |
| jaraba_candidate | Preview CV | /my-profile/cv/preview/{template} | Modal 1000px (off-canvas) |
| jaraba_job_board | Aplicar a oferta | /jobs/{id}/apply | Modal 800px |
| jaraba_job_board | Crear alerta | /my-jobs/alerts/add | Modal 600px |
| jaraba_job_board | Ver detalle oferta | /jobs/{id} | Modal 1000px |
| jaraba_self_discovery | Iniciar RIASEC | /self-discovery/interests | Modal 900px |
| jaraba_self_discovery | Iniciar Life Wheel | /self-discovery/life-wheel | Modal 900px |
| jaraba_self_discovery | Ver resultados assessment | /self-discovery/{tool}/results | Modal 900px |

### 6.3 Implementacion Tecnica

**Patron en Twig (en las templates de modulo)**:
```twig
<a href="{{ path('jaraba_candidate.profile_edit') }}"
   class="use-ajax btn btn--primary"
   data-dialog-type="modal"
   data-dialog-options='{"width": 900, "title": "{{ 'Editar perfil'|t }}", "dialogClass": "ej-modal ej-modal--profile-edit"}'
   aria-label="{{ 'Editar mi perfil profesional'|t }}">
  {{ jaraba_icon('actions', 'edit', { size: '16px' }) }}
  {{ 'Editar perfil'|t }}
</a>
```

**Library requerida** en cada `.libraries.yml`:
```yaml
modal-actions:
  version: VERSION
  dependencies:
    - core/drupal.dialog.ajax
    - ecosistema_jaraba_core/modal-system
```

**Formularios** deben implementar `FormInterface::submitForm()` con `AjaxResponse` para cerrar el modal y refrescar el contenido:

```php
public function submitForm(array &$form, FormStateInterface $form_state): void {
  // ... save entity ...

  // Si es peticion AJAX (modal), no redirigir.
  if ($this->getRequest()->isXmlHttpRequest()) {
    $form_state->disableRedirect();
  }
}
```

### 6.4 Directrices de Aplicacion

- **MODAL-001**: Toda accion CRUD en frontend abre en modal, nunca navega a otra pagina
- **MODAL-002**: Width del modal segun complejidad: 600px (simple), 800px (form media), 900px (form compleja), 1000px (preview/detalle)
- **MODAL-003**: Tras submit exitoso, el modal se cierra y el contenido de la pagina se refresca via AJAX
- **MODAL-004**: `dialogClass` sigue convencion BEM: `ej-modal ej-modal--{contexto}`
- **MODAL-005**: Todos los modales llevan `aria-label` descriptivo y son accesibles por teclado (ESC cierra)

---

## 7. FASE 3: Correccion SCSS y Compliance de Theming

### 7.1 Objetivo

Corregir todas las violaciones de la directriz P4-COLOR-002 (usar `color-mix()` en lugar de `rgba()`) y cualquier uso de `@import` por `@use` en los SCSS del vertical.

### 7.2 Ficheros a Corregir

**jaraba_candidate/scss/_dashboard.scss**:

| Linea (aprox) | Actual | Correcto |
|------|--------|----------|
| 14 | `rgba(0, 0, 0, 0.08)` | `color-mix(in srgb, var(--ej-color-corporate, #233D63) 8%, transparent)` |
| 15 | `rgba(0, 0, 0, 0.12)` | `color-mix(in srgb, var(--ej-color-corporate, #233D63) 12%, transparent)` |
| 60 | `rgba(16, 185, 129, 0.15)` | `color-mix(in srgb, var(--ej-color-success, #10B981) 15%, transparent)` |
| 97 | `rgba(99, 102, 241, 0.4)` | `color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 40%, transparent)` |
| 102 | `rgba(99, 102, 241, 0.1)` | `color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 10%, transparent)` |
| 106 | `rgba(99, 102, 241, 0.2)` | `color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 20%, transparent)` |
| 588 | `rgba(99, 102, 241, 0.4)` | `color-mix(in srgb, var(--ej-color-innovation, #00A9A5) 40%, transparent)` |

**jaraba_self_discovery/scss/self-discovery.scss**:

- Todas las instancias `rgba()` para sombras, backgrounds y gradientes deben migrarse a `color-mix()`
- La instancia existente de `color-mix()` en linea 396 se mantiene como referencia correcta

### 7.3 Verificacion @use vs @import

Todos los archivos SCSS deben usar `@use` (Dart Sass moderno):
```scss
// CORRECTO
@use 'variables' as *;
@use 'sass:color';
@use 'sass:math';

// INCORRECTO — Debe eliminarse
@import 'variables';
```

### 7.4 Patron de Sombras con Design Tokens

```scss
// Sombras: usar tokens CSS con fallback color-mix
$ej-shadow-sm: var(--ej-shadow-sm, 0 2px 8px color-mix(in srgb, var(--ej-color-corporate, #233D63) 8%, transparent));
$ej-shadow-md: var(--ej-shadow-md, 0 4px 16px color-mix(in srgb, var(--ej-color-corporate, #233D63) 12%, transparent));
$ej-shadow-lg: var(--ej-shadow-lg, 0 8px 32px color-mix(in srgb, var(--ej-color-corporate, #233D63) 16%, transparent));
```

### 7.5 Compilacion

Todos los cambios SCSS se compilan desde Docker:
```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_candidate && npx sass scss/main.scss:css/candidate.css --style=compressed"

docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_self_discovery && npx sass scss/main.scss:css/self-discovery.css --style=compressed"
```

### 7.6 Body Classes para Empleabilidad

Extender `ecosistema_jaraba_theme_preprocess_html()`:

```php
// Deteccion de rutas de empleabilidad para body classes.
$route_name = \Drupal::routeMatch()->getRouteName() ?: '';

$route_class_map = [
  'jaraba_candidate.dashboard' => 'page-jobseeker',
  'jaraba_candidate.my_profile' => 'page-my-profile',
  'jaraba_candidate.cv_builder' => 'page-cv-builder',
  'jaraba_job_board.search' => 'page-jobs',
  'jaraba_job_board.detail' => 'page-job-detail',
  'jaraba_job_board.employer_dashboard' => 'page-employer',
  'jaraba_job_board.my_applications' => 'page-my-applications',
  'jaraba_self_discovery.dashboard' => 'page-self-discovery',
];

foreach ($route_class_map as $route => $class) {
  if ($route_name === $route || str_starts_with($route_name, str_replace('.dashboard', '.', $route))) {
    $variables['attributes']['class'][] = $class;
    $variables['attributes']['class'][] = 'page-empleabilidad';
    break;
  }
}
```

---

## 8. FASE 4: Feature Gating por Plan (Escalera de Valor)

### 8.1 Objetivo

Implementar enforcement real de los FreemiumVerticalLimit existentes en los controllers y servicios del vertical empleabilidad, creando una escalera de valor clara: Free → Starter → Professional.

### 8.2 Escalera de Valor Empleabilidad

| Feature | Free | Starter (19 EUR/mes) | Professional (39 EUR/mes) |
|---------|------|---------------------|---------------------------|
| Diagnostico Express | 1/mes | Ilimitado | Ilimitado |
| CV Builder (IA) | 1 CV | 5 versiones | 10 versiones |
| Ofertas visibles/dia | 5 | 25 | Ilimitado |
| Aplicaciones/dia | 1 | 10 | Ilimitado |
| Copilot mensajes/dia | 5 | 25 | Ilimitado |
| Job alerts | 1 activa | 5 activas | Ilimitado |
| Interview Prep | Tips basicos | Simulacion guiada | Simulacion IA interactiva |
| Self-Discovery | RIASEC basico | Todas las herramientas | + Informe PDF IA |
| Profile visibility | Basica | Destacado | Prioridad en matching |
| LinkedIn optimization | No | Si | + IA avanzada |
| Push notifications | No | Si | Si |
| Credenciales | Badge basico | Stacks | + Portabilidad |

### 8.3 Servicio de Gating: EmployabilityFeatureGateService

Nuevo servicio en `ecosistema_jaraba_core`:

```php
namespace Drupal\ecosistema_jaraba_core\Service;

class EmployabilityFeatureGateService {

  /**
   * Verifica si el usuario puede acceder a una feature del vertical empleabilidad.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $featureKey
   *   La feature a verificar (ej: 'cv_builder', 'diagnostics', 'job_applications').
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
   *   Resultado con allowed (bool), remaining (int), limit (int), upgradeMessage (string).
   */
  public function check(int $userId, string $featureKey): FeatureGateResult;

  /**
   * Registra un uso de la feature (incrementa contador).
   */
  public function recordUsage(int $userId, string $featureKey): void;

  /**
   * Obtiene el plan actual del usuario para el vertical empleabilidad.
   */
  public function getUserPlan(int $userId): string;
}
```

### 8.4 Integracion en Controllers

Cada controller/servicio que gestiona una feature limitada debe verificar antes de ejecutar:

```php
// En CvBuilderService::generateCv()
$gate = $this->featureGate->check($userId, 'cv_builder');
if (!$gate->isAllowed()) {
  return [
    'error' => 'feature_limit_reached',
    'message' => $gate->getUpgradeMessage(),
    'current_plan' => $gate->getCurrentPlan(),
    'upgrade_url' => Url::fromRoute('jaraba_billing.upgrade')->toString(),
  ];
}
// ... generar CV ...
$this->featureGate->recordUsage($userId, 'cv_builder');
```

### 8.5 FreemiumVerticalLimit: Datos Seed Adicionales

Crear en `config/install/` las configuraciones que faltan:

```yaml
# empleabilidad_free_job_applications.yml
id: empleabilidad_free_job_applications
label: 'Empleabilidad Free: Aplicaciones diarias'
vertical: empleabilidad
plan: free
feature_key: job_applications_per_day
limit_value: 1
upgrade_message: 'Ya has enviado tu candidatura diaria gratuita. Actualiza a Starter para hasta 10 aplicaciones al dia.'
expected_conversion: 0.35

# empleabilidad_free_copilot_messages.yml
id: empleabilidad_free_copilot_messages
label: 'Empleabilidad Free: Mensajes Copilot'
vertical: empleabilidad
plan: free
feature_key: copilot_messages_per_day
limit_value: 5
upgrade_message: 'Has agotado tus 5 consultas diarias al copilot. Actualiza a Starter para 25 consultas diarias.'
expected_conversion: 0.28

# empleabilidad_free_job_alerts.yml
id: empleabilidad_free_job_alerts
label: 'Empleabilidad Free: Alertas de empleo'
vertical: empleabilidad
plan: free
feature_key: job_alerts
limit_value: 1
upgrade_message: 'Solo puedes tener 1 alerta activa en el plan gratuito. Actualiza a Starter para hasta 5 alertas.'
expected_conversion: 0.22
```

### 8.6 UI de Limite Alcanzado

Cuando el usuario alcanza un limite, mostrar un modal de upgrade contextual:

```twig
{# Parcial: partials/_upgrade-prompt.html.twig #}
<div class="ej-upgrade-prompt" role="alert">
  <div class="ej-upgrade-prompt__icon">
    {{ jaraba_icon('ui', 'lock', { color: 'impulse', size: '48px' }) }}
  </div>
  <h3 class="ej-upgrade-prompt__title">{{ upgrade_title|t }}</h3>
  <p class="ej-upgrade-prompt__message">{{ upgrade_message|t }}</p>
  <div class="ej-upgrade-prompt__benefits">
    {% for benefit in upgrade_benefits %}
      <div class="ej-upgrade-prompt__benefit">
        {{ jaraba_icon('actions', 'check', { color: 'success', size: '16px' }) }}
        <span>{{ benefit|t }}</span>
      </div>
    {% endfor %}
  </div>
  <div class="ej-upgrade-prompt__actions">
    <a href="{{ upgrade_url }}" class="btn btn--primary btn--lg">
      {{ 'Actualizar a @plan'|t({'@plan': next_plan}) }}
    </a>
    <button class="btn btn--text" data-dismiss="modal">
      {{ 'Ahora no'|t }}
    </button>
  </div>
</div>
```

---

## 9. FASE 5: Upgrade Triggers y Upsell Contextual IA

### 9.1 Objetivo

Conectar el UpgradeTriggerService existente con los eventos del vertical empleabilidad, y hacer que el copilot IA sugiera upgrades de forma contextual y no intrusiva.

### 9.2 Puntos de Disparo

| Evento | Trigger Type | Conversion Esperada | Punto de Integracion |
|--------|-------------|--------------------|-----------------------|
| Limite diario de aplicaciones alcanzado | limit_reached | 35% | ApplicationService::applyToJob() |
| Limite de CV builder alcanzado | limit_reached | 28% | CvBuilderService::generateCv() |
| Limite de copilot alcanzado | limit_reached | 28% | EmployabilityCopilotAgent::chat() |
| Intento de feature bloqueada (interview sim) | feature_blocked | 28% | InterviewPrepService |
| Primera entrevista programada | first_milestone | 42% | JourneyState transition |
| 5+ aplicaciones enviadas | engagement_high | 40% | ApplicationService (counter) |
| Primer mensaje de reclutador recibido | external_validation | 45% | ApplicationNotificationService |
| Candidato preseleccionado | status_change | 50% | JobApplication status update |
| 30+ dias en plataforma sin upgrade | time_on_platform | 18% | Cron job semanal |

### 9.3 Implementacion en ApplicationService

```php
// En jaraba_job_board/src/Service/ApplicationService.php
public function applyToJob(int $candidateId, int $jobId, array $data): array {
  // 1. Verificar limite
  $gate = $this->featureGate->check($candidateId, 'job_applications_per_day');
  if (!$gate->isAllowed()) {
    // 2. Disparar upgrade trigger
    $this->upgradeTrigger->fire('limit_reached', $this->tenantContext->getCurrentTenantId(), [
      'feature' => 'job_applications',
      'user_id' => $candidateId,
      'plan' => $gate->getCurrentPlan(),
      'vertical' => 'empleabilidad',
    ]);

    return [
      'success' => FALSE,
      'upgrade_required' => TRUE,
      'message' => $gate->getUpgradeMessage(),
    ];
  }

  // 3. Procesar aplicacion...
  // 4. Registrar uso
  $this->featureGate->recordUsage($candidateId, 'job_applications_per_day');

  // 5. Verificar milestone de engagement
  $totalApps = $this->countUserApplications($candidateId);
  if ($totalApps === 5) {
    $this->upgradeTrigger->fire('engagement_high', $this->tenantContext->getCurrentTenantId(), [
      'user_id' => $candidateId,
      'milestone' => '5_applications',
      'vertical' => 'empleabilidad',
    ]);
  }

  return ['success' => TRUE, 'application_id' => $application->id()];
}
```

### 9.4 Copilot Upsell Contextual

Modificar `EmployabilityCopilotAgent::getSoftSuggestion()` para conectar con billing:

```php
public function getSoftSuggestion(array $context = []): ?array {
  $diagnosis = $this->diagnoseCareerStage();
  $phase = $diagnosis['phase'];
  $plan = $this->featureGate->getUserPlan($this->currentUser->id());

  // Solo sugerir upgrade si esta en plan free y fase >= 3
  if ($plan !== 'free' || $phase < 3) {
    return NULL;
  }

  // Sugerencia contextual segun fase
  $suggestions = [
    3 => [
      'type' => 'upgrade',
      'message' => $this->t('Tu perfil esta listo para competir. Con el plan Starter podrias aplicar a 10 ofertas al dia y recibir alertas prioritarias.'),
      'cta' => ['label' => $this->t('Ver plan Starter'), 'url' => '/upgrade?vertical=empleabilidad&source=copilot'],
      'trigger' => 'copilot_soft_upsell',
    ],
    4 => [
      'type' => 'upgrade',
      'message' => $this->t('Estas compitiendo a alto nivel. El plan Professional te daria simulacion de entrevistas con IA y prioridad en el matching.'),
      'cta' => ['label' => $this->t('Ver plan Professional'), 'url' => '/upgrade?vertical=empleabilidad&source=copilot'],
      'trigger' => 'copilot_premium_upsell',
    ],
  ];

  return $suggestions[$phase] ?? NULL;
}
```

---

## 10. FASE 6: Email Sequences Automatizadas

### 10.1 Objetivo

Crear 5 email sequences automatizadas para el ciclo de vida del candidato en empleabilidad, usando el modulo jaraba_email existente.

### 10.2 Sequences a Implementar

**SEQ-EMP-001: Onboarding Candidato (7 dias)**

| Dia | Asunto | Contenido | CTA |
|-----|--------|-----------|-----|
| 0 | Bienvenido a Jaraba Empleabilidad | Resultado diagnostico + primeros pasos | Completar perfil |
| 1 | Tu perfil profesional te espera | Tips para foto y headline LinkedIn | Editar perfil |
| 3 | 3 ofertas que encajan contigo | Primeras recomendaciones de empleo | Ver ofertas |
| 5 | Tu CV puede ser mejor con IA | Funcionalidad CV Builder | Crear CV |
| 7 | Tu primera semana: resumen | Estadisticas de actividad + siguiente paso | Dashboard |

**SEQ-EMP-002: Engagement (cuando 0 aplicaciones en 7 dias)**

| Dia | Asunto | Contenido | CTA |
|-----|--------|-----------|-----|
| 0 | No te rindas, hay ofertas para ti | 3 ofertas personalizadas segun perfil | Aplicar ahora |
| 3 | Tip: Asi destacan los candidatos exitosos | Estadisticas + consejos del copilot | Hablar con copilot |
| 7 | Ultima oportunidad esta semana | Urgencia + escasez (X candidatos ya aplicaron) | Ver ofertas |

**SEQ-EMP-003: Upsell Free-to-Starter (tras 3 aplicaciones)**

| Dia | Asunto | Contenido | CTA |
|-----|--------|-----------|-----|
| 0 | Tu actividad demuestra que vas en serio | Resumen de actividad + beneficios Starter | Ver planes |
| 2 | Los candidatos Starter aplican 10x mas rapido | Comparativa Free vs Starter | Probar Starter |
| 5 | Oferta especial: 30% descuento primer mes | Descuento limitado + testimonio | Activar descuento |

**SEQ-EMP-004: Post-Entrevista**

| Dia | Asunto | Contenido | CTA |
|-----|--------|-----------|-----|
| 0 | Preparate para tu entrevista | Tips del copilot interview_prep | Practicar preguntas |
| D+1 | Como fue tu entrevista? | Feedback + proximos pasos | Responder encuesta |
| D+3 | Mientras esperas respuesta... | Seguir aplicando + cursos recomendados | Ver mas ofertas |

**SEQ-EMP-005: Retention Post-Empleo**

| Dia | Asunto | Contenido | CTA |
|-----|--------|-----------|-----|
| 0 | Felicidades por tu nuevo empleo! | Celebracion + credencial obtenida | Compartir logro |
| 7 | Recomienda Jaraba a un amigo | Referral program: 1 mes gratis por referido | Invitar amigo |
| 30 | Emprende tu proximo reto | Cross-sell: emprendimiento, formacion avanzada | Explorar vertical |

### 10.3 Triggers de Activacion

```php
// En jaraba_diagnostic.module - post diagnostico completado
$this->emailSequenceService->enroll($userId, 'SEQ-EMP-001');

// En cron job semanal - detecta inactividad
if ($daysSinceLastApplication > 7) {
  $this->emailSequenceService->enroll($userId, 'SEQ-EMP-002');
}

// En ApplicationService - tras 3ra aplicacion en plan free
if ($totalApps === 3 && $plan === 'free') {
  $this->emailSequenceService->enroll($userId, 'SEQ-EMP-003');
}

// En ApplicationNotificationService - status = 'interview_scheduled'
$this->emailSequenceService->enroll($userId, 'SEQ-EMP-004');

// En ImpactCreditService - reason = 'get_hired'
$this->emailSequenceService->enroll($userId, 'SEQ-EMP-005');
```

---

## 11. FASE 7: CRM Integration y Pipeline de Empleo

### 11.1 Objetivo

Sincronizar el ciclo de vida de candidaturas del job board con el pipeline del CRM, creando oportunidades y actividades automaticamente.

### 11.2 Mapping Job Application → CRM Opportunity

| Job Application Status | CRM Pipeline Stage | Accion |
|-----------------------|-------------------|--------|
| applied | Awareness | Crear oportunidad |
| reviewed | Lead | Actualizar stage |
| shortlisted | Qualification | Actualizar + notificar |
| interview_scheduled | Proposal | Actualizar + actividad |
| offered | Negotiation | Actualizar + actividad |
| hired | Won | Cerrar + creditos + celebracion |
| rejected | Lost | Cerrar + nurturing sequence |

### 11.3 Integracion en jaraba_job_board.module

```php
/**
 * Sync job application status changes to CRM pipeline.
 */
function jaraba_job_board_sync_application_to_crm(JobApplicationInterface $application, string $oldStatus, string $newStatus): void {
  $crmService = \Drupal::service('jaraba_crm.opportunity_service');

  $statusToStage = [
    'applied' => 'awareness',
    'reviewed' => 'lead',
    'shortlisted' => 'qualification',
    'interview_scheduled' => 'proposal',
    'offered' => 'negotiation',
    'hired' => 'won',
    'rejected' => 'lost',
  ];

  $stage = $statusToStage[$newStatus] ?? NULL;
  if (!$stage) {
    return;
  }

  // Buscar o crear oportunidad CRM.
  $opportunity = $crmService->findByReference('job_application', $application->id());
  if (!$opportunity) {
    $opportunity = $crmService->createOpportunity([
      'title' => $application->getJobPosting()->getTitle() . ' - ' . $application->getOwner()->getDisplayName(),
      'stage' => $stage,
      'source' => 'job_board',
      'reference_type' => 'job_application',
      'reference_id' => $application->id(),
      'vertical' => 'empleabilidad',
    ]);
  }
  else {
    $crmService->updateStage($opportunity->id(), $stage);
  }
}
```

---

## 12. FASE 8: Cross-Vertical Value Bridges

### 12.1 Objetivo

Implementar bridges de valor entre empleabilidad y otros verticales del SaaS para maximizar el LTV del cliente.

### 12.2 Bridges a Implementar

| Desde | Hacia | Trigger | Mensaje | CTA |
|-------|-------|---------|---------|-----|
| Empleabilidad (candidato desempleado +90 dias) | Emprendimiento | JourneyState time_in_state > 90 | "Has considerado crear tu propio negocio? El 23% de nuestros emprendedores exitosos comenzaron aqui." | Diagnostico de Negocio |
| Empleabilidad (candidato con skills tecnicas) | Servicios | SkillsService detecta skills freelance | "Tus habilidades en {skill} tienen alta demanda como freelance. Mira estas oportunidades." | Perfil Freelancer |
| Empleabilidad (candidato contratado) | Formacion avanzada | ImpactCredit reason=get_hired | "Felicidades! Ahora que estas en tu nuevo puesto, potencia tu carrera con formacion avanzada." | Catalogo LMS Premium |
| Empleabilidad (employer activo) | Comercio | EmployerProfile verified=true | "Conecta tu negocio con comercioconecta para vender tus productos." | Landing ComercioConecta |

### 12.3 Servicio CrossVerticalBridgeService

```php
namespace Drupal\ecosistema_jaraba_core\Service;

class CrossVerticalBridgeService {

  /**
   * Evalua bridges disponibles para un usuario dado su estado.
   *
   * @return array
   *   Lista de bridges con: vertical_destino, mensaje, cta_url, prioridad.
   */
  public function evaluateBridges(int $userId): array;

  /**
   * Presenta un bridge al usuario (soft suggestion en copilot o card en dashboard).
   */
  public function presentBridge(int $userId, string $bridgeId): array;

  /**
   * Registra si el usuario acepto o descarto el bridge.
   */
  public function trackBridgeResponse(int $userId, string $bridgeId, string $response): void;
}
```

### 12.4 Integracion en Dashboard Candidato

En `jobseeker-dashboard.html.twig`, agregar seccion condicional:

```twig
{% if cross_vertical_bridges is not empty %}
  <section class="ej-dashboard__bridges">
    <h3>{{ 'Explora mas oportunidades'|t }}</h3>
    {% for bridge in cross_vertical_bridges %}
      <div class="ej-bridge-card ej-bridge-card--{{ bridge.vertical }}">
        {{ jaraba_icon('verticals', bridge.icon, { size: '32px', color: bridge.color }) }}
        <p>{{ bridge.message|t }}</p>
        <a href="{{ bridge.cta_url }}" class="btn btn--outline btn--sm">
          {{ bridge.cta_label|t }}
        </a>
      </div>
    {% endfor %}
  </section>
{% endif %}
```

---

## 13. FASE 9: AI Journey Progression Proactiva

### 13.1 Objetivo

Hacer que la IA del copilot progrese proactivamente al usuario a traves del embudo de empleabilidad, en lugar de solo responder reactivamente a preguntas.

### 13.2 Proactive Triggers

| Journey State | Trigger | Accion IA | Canal |
|--------------|---------|-----------|-------|
| discovery → 3 dias sin actividad | Inactividad detectada | Copilot muestra mensaje motivacional + CTA completar perfil | FAB notification dot |
| activation → perfil < 50% completado | Perfil incompleto | Copilot sugiere siguiente paso especifico (foto, experiencia, skills) | FAB auto-expand |
| activation → perfil completo pero 0 aplicaciones | Listo pero inactivo | Copilot presenta 3 ofertas con mejor match score | FAB + badge |
| engagement → 5+ aplicaciones sin respuesta | Frustacion potencial | Copilot ofrece coaching de CV + nuevas estrategias | FAB + email |
| engagement → entrevista programada | Momento alto interes | Copilot activa modo interview_prep automaticamente | FAB + push |
| conversion → oferta recibida | Exito inminente | Copilot ofrece negociacion salarial + celebracion | FAB + confetti |
| retention → 30 dias post-empleo | Oportunidad expansion | Copilot sugiere bridge cross-vertical | Email + FAB |

### 13.3 JourneyProgressionService

Nuevo servicio que evalua el estado del journey y dispara acciones proactivas:

```php
namespace Drupal\ecosistema_jaraba_core\Service;

class JourneyProgressionService {

  /**
   * Evalua el journey state de un usuario y determina la siguiente accion proactiva.
   * Se ejecuta en cron (cada hora) o en hook_entity_update (journey_state).
   */
  public function evaluate(int $userId): ?ProactiveAction;

  /**
   * Ejecuta la accion proactiva determinada.
   */
  public function executeAction(ProactiveAction $action): void;
}
```

### 13.4 FAB Notification System

El FAB debe mostrar un indicador visual cuando hay una accion proactiva pendiente:

```javascript
// En agent-fab.js - Verificar acciones proactivas
function checkProactiveActions() {
  fetch('/api/v1/copilot/employability/proactive')
    .then(res => res.json())
    .then(data => {
      if (data.has_action) {
        // Mostrar dot de notificacion en FAB
        document.querySelector('.fab-pulse').classList.add('has-notification');
        // Guardar accion para mostrar al abrir
        window.pendingProactiveAction = data.action;
      }
    });
}

// Verificar cada 5 minutos
setInterval(checkProactiveActions, 300000);
```

---

## 14. FASE 10: Health Scores y Metricas Especificas

### 14.1 Objetivo

Implementar metricas especificas de empleabilidad en el HealthScoreCalculatorService para medir la efectividad del vertical.

### 14.2 KPIs del Vertical Empleabilidad

| KPI | Definicion | Target | Fuente |
|-----|-----------|--------|--------|
| Tasa de Insercion | % candidatos que consiguen empleo | 40% | ImpactCredit reason=get_hired / total candidatos |
| Tiempo a Empleo | Dias desde registro hasta contratacion | < 90 dias | JourneyState created → get_hired timestamp |
| Tasa de Activacion | % registrados que completan perfil al 70%+ | 60% | ProfileCompletionService |
| Tasa de Engagement | % activados que aplican a 3+ ofertas | 45% | ApplicationService counter |
| NPS Candidato | Net Promoter Score del vertical | > 50 | Encuesta post-empleo |
| ARPU Empleabilidad | Ingreso promedio por usuario del vertical | > 15 EUR/mes | Billing per vertical |
| Conversion Free→Paid | % free que upgradean | > 8% | UpgradeTrigger conversions |
| Churn Rate | % pagados que cancelan en 30 dias | < 5% | Subscription status changes |

### 14.3 Empleabilidad Health Dimensions

Extender `HealthScoreCalculatorService`:

```php
protected function calculateEmpleabilidadScore(int $userId): array {
  return [
    'profile_completeness' => [
      'weight' => 0.25,
      'score' => $this->profileCompletion->getCompleteness($userId),
    ],
    'application_activity' => [
      'weight' => 0.30,
      'score' => $this->calculateApplicationActivity($userId),
    ],
    'copilot_engagement' => [
      'weight' => 0.15,
      'score' => $this->calculateCopilotUsage($userId),
    ],
    'training_progress' => [
      'weight' => 0.15,
      'score' => $this->calculateLmsProgress($userId),
    ],
    'credential_advancement' => [
      'weight' => 0.15,
      'score' => $this->calculateCredentialProgress($userId),
    ],
  ];
}
```

---

## 15. Correspondencia con Especificaciones Tecnicas

### 15.1 Especificaciones del Vertical (docs/tecnicos/20260115g-*)

| Codigo Spec | Fichero | Descripcion | Fases Plan | Estado Implementacion |
|-------------|---------|-------------|-----------|----------------------|
| 20260120a | 20260120a-avatar_detection_flow.html | Flujo Deteccion Avatar: cascada Domain>Path/UTM>Group>Role, 19 avatares, 3 fases pre/durante/post-registro | PREVIO | Completo |
| 20260120b | 20260120b-Recorrido_Interfaz_Empleabilidad_v1_Claude.md | Recorrido completo UI: diagnostico express, dashboard, LMS, job board, CV builder, copilot 6 modos, employer portal | Fases 1,2,4,5,9 | ~70% implementado |
| 08_LMS_Core | 20260115g-08_Empleabilidad_LMS_Core_v1_Claude.md | Entidades: course, lesson, activity, enrollment, progress_record, learning_path. ECA-LMS-001/002/003. H5P + xAPI | PREVIO + Fase 9 | Funcional, ECA parcial |
| 09_Learning_Paths | 20260115g-09_Learning_Paths_v1 | Rutas formativas secuenciales por perfil (invisible, desconectado, construccion, competitivo, magnetico) y gap (linkedin, cv, estrategia) | Fase 9 | Definicion lista, binding parcial |
| 10_Progress_Tracking | 20260115g-10_Progress_Tracking_v1 | xAPI tracking, progress_record entity, completion criteria, scoring por actividad | PREVIO | Funcional |
| 11_Job_Board_Core | 20260115g-11_Empleabilidad_Job_Board_Core_v1_Claude.md | job_posting (45+ campos), job_application pipeline (8 estados), employer_profile, saved_job, job_alert. ECA-JOB-001/002/003/004. Impact Metrics (Placement >25%, TTH <30d) | Fases 2,4,5,7 | Funcional, feature gating falta |
| 12_Application_System | 20260115g-12_Application_System_v1 | Pipeline de candidaturas: applied>screening>shortlisted>interviewed>offered>hired/rejected. Status workflow, rejection reasons | Fases 5,7 | Funcional, CRM sync falta |
| 13_Employer_Portal | 20260115g-13_Employer_Portal_v1 | Dashboard employer, wizard publicacion 8 pasos, ATS Kanban lite, scoring candidatos | Fase 2 | Funcional, modales parcial |
| 14_Job_Alerts | 20260115g-14_Job_Alerts_v1 | Alertas configurables: keywords, categorias, experiencia, remote, ubicacion, frecuencia (instant/daily/weekly) | Fase 4 | Funcional, gating falta |
| 15_Candidate_Profile | 20260115g-15_Empleabilidad_Candidate_Profile_v1_Claude.md | candidate_profile, experience, education, skill (4 niveles), language (A1-C2). Completeness scoring: 100 puntos en 6 dimensiones | Fases 1,2,3 | Funcional, templates/modales falta |
| 16_CV_Builder | 20260115g-16_CV_Builder_v1 | 5 templates (Classic ATS, Modern, Executive, Creative, Jaraba Method). AI generation. PDF(mpdf)/DOCX(PHPWord). ATS Validation score | Fases 2,4 | Funcional, gating falta |
| 17_Credentials_System | 20260115g-17_Credentials_System_v1 | Open Badge 3.0, verificacion, portabilidad cross-vertical, 6 templates empleabilidad | Fase 8 | Funcional |
| 18_Certification_Workflow | 20260115g-18_Certification_Workflow_v1 | Auto-issuance on course completion, email delivery, template rendering | PREVIO | Funcional |
| 19_Matching_Engine | 20260115g-19_Matching_Engine_v1 | Matching hibrido v1: skills 35% + experience 20% + education 10% + location 15% + semantic 20% | PREVIO | Funcional |
| CP_MatchingEngine_VideoLMS | 20260115g-CP_Empleabilidad_MatchingEngine_VideoLMS_v1_Claude.md | Contrapropuesta tecnica: hybrid rule(80%)+semantic(20%), Qdrant collections (job_vectors, candidate_vectors), ECA-MATCH-001/002/003, feedback loop | Fases 5,10 | Parcial (Qdrant OK, feedback loop falta) |
| 20_AI_Copilot | 20260115g-20_Empleabilidad_AI_Copilot_v1_Claude.md | 6 modos, RAG pipeline 9 pasos, Claude 3.5 Sonnet + Gemini fallback, intent catalog (8 intents), entities copilot_conversation/message, strict grounding | Fases 5,9 | Funcional, proactive triggers falta |
| 21_Recommendation | 20260115g-21_Recommendation_System_v1 | Sistema de recomendaciones personalizadas por perfil, historial, preferencias | Fase 9 | Parcial |
| 22_Dashboard_JobSeeker | 20260115g-22_Dashboard_JobSeeker_v1 | Widgets: Profile Completeness, Learning Progress, Application Status, Jobs For You, Gamification Stats. Nav: Home, Formacion, Empleos, CV, Perfil, Logros, Ajustes | Fases 1,8 | Funcional, bridges cross-vertical falta |
| 23_Dashboard_Employer | 20260115g-23_Dashboard_Employer_v1 | KPIs employer: ofertas activas, candidaturas, conversion, time-to-fill | Fase 2 | Funcional |
| 24_Impact_Metrics | 20260115g-24_Impact_Metrics_v1 | Credits: create_profile(50), complete_profile(100), apply_job(20), get_hired(500). XP/Levels: 10 niveles (Novato a Campeon) | Fase 10 | Funcional |
| Gap_Analysis | 20260115g-Gap_Analysis | Analisis de componentes faltantes vs especificados | Todas | Referencia |
| Auditoria_Gap | 20260115g-Auditoria_Gap | Auditoria de arquitectura: implementation vs spec gaps | Todas | Referencia |

### 15.2 Especificaciones Transversales

| Spec | Descripcion | Fases que lo implementan | Estado |
|------|-------------|------------------------|--------|
| Doc 183 / F2 | Modelo Freemium Trial: FreemiumVerticalLimit entity, seed data, upgrade triggers, conversion tracking | Fase 4, 5 | Estructura lista, enforcement pendiente |
| f-103 | UX Journey Avatars (3 fases): Fase 1 nav contextual, Fase 2 AI Decision Engine, Fase 3 Journey Progression | Fases 1, 9 | Fase 1 completa, fase 2-3 pendiente |
| 20260115e | SaaS Verticales Empleabilidad/Emprendimiento: Pricing models, competitive analysis, market positioning | Fase 4 | Referencia de mercado |
| P4-COLOR-001/002 | Design tokens y color-mix() obligatorio | Fase 3 | Violaciones encontradas (17+) |
| P4-EMOJI-001/002 | Sistema de iconos jaraba_icon() sin emojis | Fase 3 | Mayormente cumple |
| MODAL-* | Sistema modal para CRUD | Fase 2 | Solo employer dashboard |
| ZERO-REGION | Templates sin regiones Drupal | Fase 1 | Solo diagnostico cumple |
| NAV-001..005 | Navegacion contextual avatar | PREVIO | Completo |
| ENTITY-001 | EntityOwnerInterface requerido | PREVIO | Completo |
| TENANT-001 | Filtrado tenant obligatorio en queries | PREVIO | Completo |
| AUDIT-SEC-001..003 | Seguridad endpoints (HMAC, permissions, sanitizacion) | PREVIO | Completo |
| PB-VERTICAL-001 | HTML semantico unico por tipo de bloque | PREVIO | Completo (11 bloques) |
| PB-VERTICAL-002 | Color schemes via --pb-accent + color-mix() | PREVIO | Completo |

### 15.3 Tabla de Correspondencia: Spec → Codigo → Estado

| Spec | Entidad/Servicio Implementado | Fichero Principal | Cumple Spec? |
|------|------------------------------|-------------------|-------------|
| 08 (LMS) | Course, Lesson, Activity, Enrollment | jaraba_lms/src/Entity/*.php | Si |
| 08 (LMS) | EnrollmentService | jaraba_lms/src/Service/EnrollmentService.php | Si |
| 08 (LMS) | ECA-LMS-001 auto-enrollment | jaraba_diagnostic.module L56-143 | Si |
| 11 (Job Board) | JobPosting, JobApplication | jaraba_job_board/src/Entity/*.php | Si |
| 11 (Job Board) | JobPostingService, ApplicationService | jaraba_job_board/src/Service/*.php | Si, gating falta |
| 11 (Job Board) | ECA-JOB-001/002/003 | jaraba_job_board.module L508+ | Si |
| 11 (Job Board) | Impact Metrics (Placement >25%) | NO IMPLEMENTADO | No - Fase 10 |
| 15 (Candidate) | CandidateProfile, Skill, Language | jaraba_candidate/src/Entity/*.php | Si |
| 15 (Candidate) | ProfileCompletionService | jaraba_candidate/src/Service/ProfileCompletionService.php | Si |
| 16 (CV Builder) | CvBuilderService | jaraba_candidate/src/Service/CvBuilderService.php | Si, gating falta |
| 16 (CV Builder) | 5 templates (modern, classic, creative, minimal, tech) | jaraba_candidate/templates/cv/ | Si |
| 19/CP (Matching) | MatchingService | jaraba_job_board/src/Service/MatchingService.php | Si |
| 19/CP (Matching) | Qdrant vectorization | jaraba_rag + jaraba_matching | Si |
| 19/CP (Matching) | Feedback loop retraining | NO IMPLEMENTADO | No - Fase 10 |
| 20 (Copilot) | EmployabilityCopilotAgent | jaraba_candidate/src/Agent/EmployabilityCopilotAgent.php | Si |
| 20 (Copilot) | 6 modos con prompts especializados | EmployabilityCopilotAgent::MODE_PROMPTS | Si |
| 20 (Copilot) | CareerCoachAgent (Framework Lucia) | jaraba_candidate/src/Agent/CareerCoachAgent.php | Si |
| 20 (Copilot) | RecruiterAssistantAgent | jaraba_job_board/src/Agent/RecruiterAssistantAgent.php | Si |
| 20 (Copilot) | copilot_conversation + message entities | jaraba_candidate/src/Entity/Copilot*.php | Si |
| 20 (Copilot) | Proactive journey triggers | NO IMPLEMENTADO | No - Fase 9 |
| 22 (Dashboard) | JobSeeker Dashboard | jaraba_candidate/templates/jobseeker-dashboard.html.twig | Si, bridges falta |
| 22 (Dashboard) | Cross-vertical cards | NO IMPLEMENTADO | No - Fase 8 |
| 24 (Impact) | ImpactCreditService | jaraba_billing/src/Service/ImpactCreditService.php | Si |
| 24 (Impact) | GamificationService | jaraba_lms/src/Service/GamificationService.php | Si |
| F2 (Freemium) | FreemiumVerticalLimit entity + 9 seeds | ecosistema_jaraba_core/config/install/...empleabilidad*.yml | Parcial - sin enforcement |
| F2 (Freemium) | EmployabilityFeatureGateService | NO IMPLEMENTADO | No - Fase 4 |
| F2 (Freemium) | UpgradeTrigger para empleabilidad | NO IMPLEMENTADO | No - Fase 5 |

---

## 16. Cumplimiento de Directrices

### 16.1 Checklist de Directrices por Fase

| Directriz | Descripcion | Fases | Estado |
|-----------|-------------|-------|--------|
| Textos interfaz traducibles (`\|t`) | Todos los strings con `\|t` o `$this->t()` | Todas | CUMPLE |
| Modelo SASS con SCSS + variables inyectables | `_injectable.scss` + `var(--ej-*)` con fallback | Fase 3 | PARCIAL - rgba() |
| Dart Sass moderno (`@use` no `@import`) | Todos los SCSS usan `@use` | Fase 3 | VERIFICAR |
| Templates Twig limpias (zero-region) | Sin `page.content`, sin bloques Drupal | Fase 1 | FALTA en 9 rutas |
| Parciales reutilizables (`{% include %}`) | Templates parciales con `only` | Fases 1, 2, 4 | CUMPLE parcialmente |
| Variables configurables desde UI Drupal | Theme settings para header, footer, nav | Fase 1 | CUMPLE (header/footer) |
| Control absoluto frontend limpio | Sin page.content ni bloques heredados | Fase 1 | FALTA |
| Layout full-width mobile-first | Sin sidebar, breakpoints 576/768 | Fase 1 | PARCIAL |
| Acciones CRUD en modal | `use-ajax` + `data-dialog-type="modal"` | Fase 2 | FALTA en 3 modulos |
| `hook_preprocess_html()` para body classes | No `attributes.addClass()` en template | Fase 3 | CUMPLE pero incompleto |
| Tenant sin acceso tema admin | Roles y permisos correctos | N/A | CUMPLE |
| Entidades con Field UI y Views | `/admin/structure` + `/admin/content` | N/A | CUMPLE |
| Iconos con `jaraba_icon()` | Sin emojis en templates ni SCSS | Fase 3 | CUMPLE (excepto naming) |
| `color-mix()` para variantes color | No rgba() ni hex hardcoded | Fase 3 | FALTA - 17+ violaciones |

### 16.2 Reglas Nuevas Propuestas

| Regla | Descripcion |
|-------|-------------|
| EMPL-GATE-001 | Toda feature limitada DEBE verificar EmployabilityFeatureGateService antes de ejecutar |
| EMPL-JOURNEY-001 | Todo cambio de estado en el journey DEBE evaluarse por JourneyProgressionService |
| EMPL-TRIGGER-001 | Todo evento de negocio empleabilidad DEBE considerar fire() de UpgradeTriggerService |
| EMPL-CRM-001 | Todo cambio de status en JobApplication DEBE sincronizar con CRM pipeline |
| EMPL-SEQ-001 | Todo milestone del candidato DEBE evaluarse para enrollment en email sequence |

---

## 17. Dependencias Cruzadas del Vertical

### 17.1 Mapa de Dependencias

```
jaraba_candidate
├── DEPENDE DE: ecosistema_jaraba_core (avatar, copilot context, design tokens)
├── DEPENDE DE: jaraba_lms (enrollment, courses)
├── DEPENDE DE: jaraba_ai_agents (base agent, observability)
├── DEPENDE DE: jaraba_job_board (job matching, recommendations)
├── USADO POR: jaraba_job_board (candidate profiles for matching)
├── USADO POR: jaraba_credentials (badges for milestones)
└── USADO POR: jaraba_billing (feature gating, metering)

jaraba_job_board
├── DEPENDE DE: ecosistema_jaraba_core (avatar, tenant context)
├── DEPENDE DE: jaraba_candidate (candidate profiles)
├── DEPENDE DE: jaraba_matching (matching engine)
├── DEPENDE DE: jaraba_rag (semantic embeddings)
├── DEPENDE DE: jaraba_page_builder (landing templates)
├── USADO POR: jaraba_crm (opportunity sync) [NUEVO - Fase 7]
├── USADO POR: jaraba_email (sequences) [NUEVO - Fase 6]
└── USADO POR: jaraba_billing (upgrade triggers) [NUEVO - Fase 5]

jaraba_diagnostic
├── DEPENDE DE: ecosistema_jaraba_core (scoring, credits)
├── DEPENDE DE: jaraba_lms (auto-enrollment)
├── DEPENDE DE: jaraba_journey (journey state)
├── USADO POR: jaraba_candidate (diagnostic results in profile)
└── USADO POR: jaraba_email (onboarding sequence) [NUEVO - Fase 6]

jaraba_self_discovery
├── DEPENDE DE: jaraba_candidate (candidate context)
├── DEPENDE DE: ecosistema_jaraba_core (design tokens)
├── USADO POR: jaraba_candidate (copilot context enrichment)
└── USADO POR: jaraba_lms (learning path recommendations)
```

### 17.2 Flujos de Entrada/Salida

```
ENTRADAS AL VERTICAL:
├── UTM/SEO → Landing page → Diagnostico Express → Registro → Dashboard
├── Referral link → Landing personalizada → Registro → Dashboard
├── Google Jobs → /jobs/{id} → Aplicar → Registro → Dashboard
├── Admin onboarding → Asignar rol candidate → Dashboard
├── Cross-vertical bridge → Self-Discovery → Perfil → Dashboard
└── Email remarketing → /empleabilidad/diagnostico/{uuid}/resultados → Registro

SALIDAS DEL VERTICAL:
├── Candidato contratado → Celebracion → Referral → Email retention
├── Candidato frustrado → Bridge emprendimiento → Diagnostico negocio
├── Candidato freelancer → Bridge servicios → Perfil profesional servicios
├── Candidato formado → LMS → Credenciales → Bridge formacion avanzada
├── Employer satisfecho → Bridge comercioconecta → Marketplace
└── Churn → Dunning sequence → Win-back email → Desactivacion
```

---

## 18. Diagrama de Flujo Entrada/Salida

```
                    ┌─────────────────────────────────────────────┐
                    │           VISITANTE NO REGISTRADO            │
                    │  (Google, Redes, UTM, Referral, Direct)      │
                    └──────────────────┬──────────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────────┐
                    │         AVATAR DETECTION (4 niveles)         │
                    │  Domain > Path/UTM > Group > Role > Default  │
                    └──────────────────┬──────────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────────┐
                    │      LANDING PERSONALIZADA (Front Page)      │
                    │  Progressive Profiling → localStorage         │
                    │  Hero dinámico → 5 tarjetas intención         │
                    └─────────┬────────────────────┬──────────────┘
                              │                    │
               ┌──────────────▼───────┐ ┌─────────▼──────────────┐
               │ DIAGNOSTICO EXPRESS  │ │   BUSQUEDA DIRECTA     │
               │ /empleabilidad/      │ │   /jobs (público)      │
               │ diagnostico          │ │   Google Jobs embed    │
               │ 3 preguntas → Score  │ └─────────┬──────────────┘
               │ Perfil + Gap + Recs  │           │
               └──────────┬───────────┘           │
                          │                       │
               ┌──────────▼───────────────────────▼──────────────┐
               │              REGISTRO + USR-001                  │
               │  → JourneyState (jobseeker, discovery)           │
               │  → Vincular diagnostico previo (?diagnostic=UUID)│
               │  → Webhook ActiveCampaign                        │
               └──────────────────┬──────────────────────────────┘
                                  │
               ┌──────────────────▼──────────────────────────────┐
               │           USR-002: POST-DIAGNOSTICO              │
               │  → Rol 'candidate'                               │
               │  → JourneyState: activation                      │
               │  → Auto-inscripcion LMS                          │
               │  → +50 creditos impacto                          │
               │  → [NUEVO] Email SEQ-EMP-001                     │
               └──────────────────┬──────────────────────────────┘
                                  │
     ┌────────────────────────────▼────────────────────────────┐
     │                  DASHBOARD CANDIDATO                     │
     │                  (PLAN FREE)                             │
     │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────┐ │
     │  │ Perfil   │  │ Ofertas  │  │ CV       │  │ Self-  │ │
     │  │ (modal)  │  │ (modal)  │  │ Builder  │  │ Disc.  │ │
     │  │          │  │          │  │ (modal)  │  │(modal) │ │
     │  └────┬─────┘  └────┬─────┘  └────┬─────┘  └───┬────┘ │
     │       │              │              │             │      │
     │  ┌────▼──────────────▼──────────────▼─────────────▼────┐│
     │  │            COPILOT FAB (6 modos)                    ││
     │  │  Proactivo: detecta estado → sugiere accion         ││
     │  │  [NUEVO] Feature gate → upgrade prompt              ││
     │  │  [NUEVO] Soft upsell contextual                     ││
     │  └─────────────────────────────────────────────────────┘│
     │                                                         │
     │  ┌─────────────────────────────────────────────────────┐│
     │  │          ESCALERA DE VALOR [NUEVO]                  ││
     │  │  Free: 1 app/dia, 1 CV, 5 ofertas/dia              ││
     │  │  Starter: 10 apps, 5 CVs, 25 ofertas               ││
     │  │  Professional: ilimitado + interview sim + priority  ││
     │  └─────────────────────────┬───────────────────────────┘│
     └────────────────────────────┼────────────────────────────┘
                                  │
               ┌──────────────────▼──────────────────────────────┐
               │         EMBUDO DE CONVERSION                     │
               │                                                  │
               │  ┌── Limite alcanzado → Upgrade Trigger ──────┐ │
               │  │   → Modal upgrade prompt                    │ │
               │  │   → Email SEQ-EMP-003 (upsell)             │ │
               │  │   → Copilot soft suggestion                │ │
               │  └────────────────────────────────────────────┘ │
               │                                                  │
               │  ┌── Entrevista programada ───────────────────┐ │
               │  │   → Email SEQ-EMP-004                      │ │
               │  │   → Copilot interview_prep auto            │ │
               │  │   → Push notification                      │ │
               │  └────────────────────────────────────────────┘ │
               │                                                  │
               │  ┌── Contratado ──────────────────────────────┐ │
               │  │   → +500 creditos                          │ │
               │  │   → Credencial cross-vertical              │ │
               │  │   → Email SEQ-EMP-005 (retention)          │ │
               │  │   → CRM: Oportunidad → Won                │ │
               │  └────────────────────────────────────────────┘ │
               └──────────────────┬──────────────────────────────┘
                                  │
               ┌──────────────────▼──────────────────────────────┐
               │       BRIDGES CROSS-VERTICAL [NUEVO]             │
               │                                                  │
               │  +90 dias desempleado → Emprendimiento           │
               │  Skills freelance    → ServiciosConecta          │
               │  Contratado          → Formacion avanzada (LMS)  │
               │  Employer activo     → ComercioConecta           │
               └─────────────────────────────────────────────────┘
```

---

## Apendice A: Orden de Implementacion Recomendado

| Orden | Fase | Dependencia | Estimacion |
|-------|------|-------------|-----------|
| 1 | Fase 1: Clean Page Templates | Ninguna | - |
| 2 | Fase 3: SCSS Compliance | Ninguna (paralelo con Fase 1) | - |
| 3 | Fase 2: Sistema Modal | Fase 1 (templates deben existir) | - |
| 4 | Fase 4: Feature Gating | Fase 1 (UI de upgrade prompt) | - |
| 5 | Fase 5: Upgrade Triggers | Fase 4 (gating debe existir) | - |
| 6 | Fase 6: Email Sequences | Fase 4, 5 (triggers disparan emails) | - |
| 7 | Fase 9: AI Journey Progression | Fase 5 (triggers como entrada) | - |
| 8 | Fase 7: CRM Integration | Independiente | - |
| 9 | Fase 8: Cross-Vertical Bridges | Fase 9 (journey state como trigger) | - |
| 10 | Fase 10: Health Scores | Fases 4-9 (necesita datos) | - |

---

## Apendice B: Ficheros Verificados en esta Auditoria

| Fichero | Tipo | Estado |
|---------|------|--------|
| web/modules/custom/jaraba_candidate/src/Agent/EmployabilityCopilotAgent.php | Agente IA | Completo |
| web/modules/custom/jaraba_candidate/src/Agent/CareerCoachAgent.php | Agente IA | Completo |
| web/modules/custom/jaraba_job_board/src/Agent/RecruiterAssistantAgent.php | Agente IA | Completo |
| web/modules/custom/jaraba_diagnostic/src/Controller/EmployabilityDiagnosticController.php | Controller | Completo |
| web/modules/custom/jaraba_diagnostic/src/Service/EmployabilityScoringService.php | Servicio | Completo |
| web/modules/custom/ecosistema_jaraba_core/src/Service/AvatarDetectionService.php | Servicio | Completo |
| web/modules/custom/ecosistema_jaraba_core/src/Service/AvatarNavigationService.php | Servicio | Completo |
| web/modules/custom/jaraba_billing/src/Service/FeatureAccessService.php | Servicio | Sin enforcement empleabilidad |
| web/modules/custom/jaraba_billing/src/Service/ImpactCreditService.php | Servicio | Completo |
| web/modules/custom/jaraba_lms/src/Service/GamificationService.php | Servicio | Completo |
| web/modules/custom/jaraba_credentials/modules/jaraba_credentials_cross_vertical/src/Service/VerticalActivityTracker.php | Servicio | Completo |
| web/modules/custom/jaraba_journey/src/Entity/JourneyState.php | Entidad | Completo |
| web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme | Theme hooks | Parcial |
| web/themes/custom/ecosistema_jaraba_theme/templates/page--empleabilidad--diagnostico.html.twig | Template | Completo (zero-region) |
| web/themes/custom/ecosistema_jaraba_theme/templates/page.html.twig | Template | NO zero-region |
| web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig | Template | Zero-region |
| web/themes/custom/ecosistema_jaraba_theme/js/progressive-profiling.js | JS | Completo |
| web/modules/custom/jaraba_job_board/js/agent-fab.js | JS | Completo |
| web/modules/custom/jaraba_candidate/scss/_dashboard.scss | SCSS | 7 violaciones rgba() |
| web/modules/custom/jaraba_self_discovery/scss/self-discovery.scss | SCSS | 10+ violaciones rgba() |
| web/modules/custom/jaraba_diagnostic/jaraba_diagnostic.module | Module hooks | Completo |
| web/modules/custom/jaraba_job_board/jaraba_job_board.module | Module hooks | Completo |
| web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module | Module hooks | Completo |
| config/install/ecosistema_jaraba_core.freemium_vertical_limit.empleabilidad_*.yml | Config | 9 archivos seed |
| config/install/ecosistema_jaraba_core.eca_flow_definition.eca_usr_001.yml | ECA Flow | Implementado |
| config/install/ecosistema_jaraba_core.eca_flow_definition.eca_usr_002.yml | ECA Flow | Implementado |
| config/install/ecosistema_jaraba_core.eca_flow_definition.eca_job_001.yml | ECA Flow | Implementado |
| docs/00_DIRECTRICES_PROYECTO.md | Directrices | v25.0.0 |
| docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md | Arquitectura | Completo |
| docs/implementacion/2026-02-12_plan_cierre_gaps_avatar_empleabilidad.md | Plan anterior | Implementado |
