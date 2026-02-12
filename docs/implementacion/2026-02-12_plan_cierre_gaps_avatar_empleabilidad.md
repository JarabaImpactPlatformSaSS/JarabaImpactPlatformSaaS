# Plan de Cierre de Gaps: Avatar Detection + Empleabilidad UI

**Fecha:** 2026-02-12
**Specs:** 20260120a (Flujo de Deteccion de Avatar), 20260120b (Recorrido Interfaz Empleabilidad v1)
**Estado:** Implementado
**Modulos afectados:** ecosistema_jaraba_core, jaraba_diagnostic, jaraba_candidate, ecosistema_jaraba_theme

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Fase 1: AvatarDetectionService](#fase-1-avatardetectionservice)
3. [Fase 2: Diagnostico Express de Empleabilidad](#fase-2-diagnostico-express)
4. [Fase 3: Hooks ECA (USR-001 + USR-002)](#fase-3-hooks-eca)
5. [Fase 4: AI Copilot para Empleabilidad](#fase-4-ai-copilot)
6. [Fase 5: CV PDF Export](#fase-5-cv-pdf-export)
7. [Fase 6: Sistema de Modales](#fase-6-sistema-de-modales)
8. [Fase 7: Partials Frontend](#fase-7-partials-frontend)
9. [Correspondencia con Specs](#correspondencia-con-specs)
10. [Cumplimiento de Directrices](#cumplimiento-de-directrices)

---

## Resumen Ejecutivo

Este plan cierra los 5 gaps criticos que bloqueaban el flujo end-to-end del vertical de Empleabilidad. El ~70% del vertical ya estaba implementado (Job Board, Matching, LMS, Gamificacion, Dashboard). Las piezas implementadas son:

- **AvatarDetectionService**: Cascada unificada Domain > Path/UTM > Group > Rol
- **Diagnostico Express**: Wizard de 3 preguntas con scoring y perfiles
- **Hooks ECA**: Automatizacion de registro y post-diagnostico
- **AI Copilot 6 Modos**: Profile Coach, Job Advisor, Interview Prep, Learning Guide, Application Helper, FAQ
- **CV PDF Export**: Dompdf real sustituyendo el placeholder TODO

---

## Fase 1: AvatarDetectionService

### Ficheros Creados

| Fichero | Proposito |
|---------|-----------|
| `ecosistema_jaraba_core/src/ValueObject/AvatarDetectionResult.php` | Value Object inmutable (avatarType, vertical, detectionSource, programaOrigen, confidence) |
| `ecosistema_jaraba_core/src/Service/AvatarDetectionService.php` | Servicio con cascada de 4 niveles y constantes DOMAIN_MAP, PATH_MAP, UTM_MAP, ROLE_TO_AVATAR |
| `ecosistema_jaraba_core/tests/src/Unit/Service/AvatarDetectionServiceTest.php` | 7 casos de test cubriendo todos los niveles de cascada |

### Ficheros Modificados

| Fichero | Cambio |
|---------|--------|
| `ecosistema_jaraba_core.services.yml` | Registro del servicio `avatar_detection` con DI |
| `DashboardRedirectController.php` | Nuevo metodo `redirectByAvatar()` con DI via `create()` |
| `ecosistema_jaraba_core.routing.yml` | Ruta `/dashboard` que redirige segun avatar detectado |

### Patron Arquitectonico

Sigue la estructura de `CopilotContextService`:
- Constantes estaticas para mapeos
- DI explicita: entity_type.manager, current_user, current_route_match, request_stack, logger
- Metodo principal `detect()` con fallback a `createDefault()`
- Value Object inmutable para el resultado

### Cascada de Deteccion

```
1. Domain (confianza: 1.0) → empleo.jaraba.es → jobseeker
2. Path/UTM (confianza: 0.8-0.9) → /empleabilidad/* → jobseeker
3. Group (confianza: 0.7) → Tenant admin → admin
4. Rol (confianza: 0.6) → candidate → jobseeker
5. Default (confianza: 0.1) → general
```

---

## Fase 2: Diagnostico Express

### Ficheros Creados

| Fichero | Proposito |
|---------|-----------|
| `jaraba_diagnostic/src/Entity/EmployabilityDiagnostic.php` | ContentEntityType con q_linkedin, q_cv_ats, q_estrategia, score, profile_type, primary_gap, anonymous_token |
| `jaraba_diagnostic/src/Service/EmployabilityScoringService.php` | Pesos: LinkedIn 40%, CV 35%, Estrategia 25%. Umbrales de 5 perfiles |
| `jaraba_diagnostic/src/Controller/EmployabilityDiagnosticController.php` | 3 rutas: landing (GET), processAndShowResults (POST JSON), showResults (GET por UUID) |
| `jaraba_diagnostic/src/EmployabilityDiagnosticListBuilder.php` | Lista admin con columnas: usuario, perfil, score, fecha |
| `jaraba_diagnostic/src/EmployabilityDiagnosticAccessControlHandler.php` | view own, edit own, administer |
| `jaraba_diagnostic/templates/employability-diagnostic-landing.html.twig` | Hero + wizard 3 pasos con textos `\|t` |
| `jaraba_diagnostic/templates/employability-diagnostic-results.html.twig` | Score ring SVG + perfil + gap + CTAs |
| `jaraba_diagnostic/js/employability-diagnostic.js` | Wizard steps, AJAX submit, score animation |
| `jaraba_diagnostic/scss/_employability-diagnostic.scss` | Mobile-first con var(--ej-*) tokens |
| `ecosistema_jaraba_theme/templates/page--empleabilidad--diagnostico.html.twig` | Zero-region, full-width, includes _header y _footer |

### Ficheros Modificados

| Fichero | Cambio |
|---------|--------|
| `jaraba_diagnostic.routing.yml` | 3 rutas bajo `/empleabilidad/diagnostico` |
| `jaraba_diagnostic.libraries.yml` | Library `employability-diagnostic` con CSS/JS |
| `jaraba_diagnostic.module` | hook_theme() con 2 templates nuevos |
| `jaraba_diagnostic.services.yml` | Registro de EmployabilityScoringService |
| `ecosistema_jaraba_theme.theme` | hook_preprocess_html: clase `page-employability-diagnostic` |

### Umbrales de Perfil

| Score | Perfil | Descripcion |
|-------|--------|-------------|
| <2 | Invisible | No tiene presencia profesional |
| <4 | Desconectado | Existe pero sin estrategia |
| <6 | En Construccion | Ha empezado a trabajar su marca |
| <8 | Competitivo | Perfil solido con areas de mejora |
| >=8 | Magnetico | Perfil optimizado que atrae oportunidades |

---

## Fase 3: Hooks ECA

### USR-001: hook_user_insert (ecosistema_jaraba_core.module)

Acciones automaticas post-registro:
1. Detectar avatar via AvatarDetectionService
2. Crear JourneyState (avatar=pending/detected, state=discovery)
3. Vincular diagnostico previo si `?diagnostic=UUID` en query
4. Encolar webhook ActiveCampaign para CRM

### USR-002: hook_entity_insert para employability_diagnostic (jaraba_diagnostic.module)

Acciones automaticas post-diagnostico:
1. Asignar rol `candidate` al usuario
2. Actualizar JourneyState (avatar=jobseeker, state=activation)
3. Invocar `DiagnosticEnrollmentService` para inscripcion LMS automatica
4. Otorgar creditos de impacto via `ImpactCreditService`

### Tests Creados

| Fichero | Casos |
|---------|-------|
| `UserInsertHookTest.php` | 3 tests: default result, detected result, toArray serialization |
| `EmployabilityDiagnosticInsertTest.php` | 3 tests: score minimo=invisible, score maximo=magnetico, gap detection |

---

## Fase 4: AI Copilot

### Ficheros Creados

| Fichero | Proposito |
|---------|-----------|
| `jaraba_candidate/src/Agent/EmployabilityCopilotAgent.php` | Extiende BaseAgent con 6 modos, system prompts, deteccion automatica por keywords |
| `jaraba_candidate/src/Controller/CopilotApiController.php` | POST /chat, GET /suggestions |
| `jaraba_candidate/js/employability-copilot.js` | Conecta FAB con API, quick action chips |

### 6 Modos del Copilot

| Modo | Proposito | Temperature |
|------|-----------|-------------|
| profile_coach | Optimizacion de perfil y LinkedIn | 0.7 |
| job_advisor | Recomendaciones de ofertas y estrategia | 0.6 |
| interview_prep | Simulacion y preparacion de entrevistas | 0.8 |
| learning_guide | Orientacion formativa y rutas LMS | 0.5 |
| application_helper | Asistencia con candidaturas y CVs | 0.4 |
| faq | Preguntas frecuentes sobre la plataforma | 0.3 |

### Deteccion Automatica de Modo

Keywords del mensaje del usuario se analizan contra las listas de keywords de cada modo. El modo con mas matches gana. Si no hay matches, se usa `faq` como fallback.

---

## Fase 5: CV PDF Export

### Cambio

Reemplazo del placeholder TODO en `CvBuilderService::convertHtmlToPdf()` por implementacion real con Dompdf:
- Configuracion segura: `isRemoteEnabled=false`, `isHtml5ParserEnabled=true`
- Formato A4 portrait, DPI 150
- Guardado en `private://cv_exports/`
- Design Tokens inyectados en el CSS del PDF

---

## Fase 6: Sistema de Modales

### Ficheros Creados

| Fichero | Proposito |
|---------|-----------|
| `ecosistema_jaraba_core/js/modal-system.js` | Behavior que detecta `data-dialog-type="modal"`, configura Drupal.dialog, callback on close para refrescar |

### Uso en Templates

```twig
<a href="/entity/add"
   class="use-ajax"
   data-dialog-type="modal"
   data-dialog-options='{"width": 700, "dialogClass": "ej-modal"}'>
  {{ 'Crear nuevo'|t }}
</a>
```

---

## Fase 7: Partials Frontend

### Partials Creados

| Fichero | Proposito |
|---------|-----------|
| `_application-pipeline.html.twig` | Mini-pipeline horizontal: Aplicado > Visto > Entrevista > Oferta |
| `_job-card.html.twig` | Card reutilizable con match score, tags y CTA modal |
| `_gamification-stats.html.twig` | Barra compacta: racha + nivel + creditos + logros |
| `_profile-completeness.html.twig` | Ring SVG de completitud con checklist de secciones |

Todos usan `jaraba_icon()` para iconos SVG, variables `var(--ej-*)` para tokens, y filtro `|t` para traducciones.

---

## Correspondencia con Specs

| Spec | Seccion | Implementacion |
|------|---------|----------------|
| 20260120a Fase 1 | Registro con deteccion | hook_user_insert (USR-001) |
| 20260120a Fase 2 | Post-diagnostico | hook_entity_insert (USR-002) |
| 20260120a Fase 3 | Cascada de avatar | AvatarDetectionService |
| 20260120b S3 | Diagnostico express | EmployabilityDiagnostic + EmployabilityScoringService |
| 20260120b S8.2 | CV PDF Export | CvBuilderService con Dompdf |
| 20260120b S10 | Copilot 6 modos | EmployabilityCopilotAgent |

---

## Cumplimiento de Directrices

| Directriz | Implementacion |
|-----------|----------------|
| Textos traducibles | `$this->t()` en PHP, `\|t` en Twig |
| SCSS con Dart Sass | `scss/_employability-diagnostic.scss` con `var(--ej-*)` |
| Templates zero-region | `page--empleabilidad--diagnostico.html.twig` sin bloques heredados |
| Partials con include | `{% include '@ecosistema_jaraba_theme/partials/_name.html.twig' %}` |
| Variables UI Drupal | TenantThemeConfig + ThemeTokenService inyectan CSS |
| Full-width mobile-first | Layout sin sidebar, media queries mobile-first |
| Modales CRUD | `data-dialog-type="modal"` + `core/drupal.dialog.ajax` |
| hook_preprocess_html | Clases body via hook: `page-employability-diagnostic` |
| Entidades Field UI + Views | AdminHtmlRouteProvider, fieldable=TRUE, views_data handler |
| Admin navigation | `/admin/content/employability-diagnostics` |
| Iconos SVG | `jaraba_icon()` en Twig |
| AI via @ai.provider | BaseAgent abstraction via DI |
| Comentarios 3D | Estructura + Logica + Sintaxis en espanol |
| Docker | Todos los comandos con `lando` prefix |
