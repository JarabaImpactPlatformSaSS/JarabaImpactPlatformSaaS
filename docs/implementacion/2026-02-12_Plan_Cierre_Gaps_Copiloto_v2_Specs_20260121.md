# Plan de Cierre de Gaps Copiloto v2 — Specs 20260121

**Fecha:** 2026-02-12
**Estado:** Completado

---

## Indice

1. [Contexto y Alcance](#1-contexto-y-alcance)
2. [Diagnostico Inicial](#2-diagnostico-inicial)
3. [Tabla de Correspondencia con Specs](#3-tabla-de-correspondencia-con-specs)
4. [Fase 1: Infraestructura de Entidades](#4-fase-1-infraestructura-de-entidades)
5. [Fase 2: API de Hipotesis](#5-fase-2-api-de-hipotesis)
6. [Fase 3: API de Ciclo de Vida de Experimentos](#6-fase-3-api-de-ciclo-de-vida-de-experimentos)
7. [Fase 4: API BMC Validation + Entrepreneur](#7-fase-4-api-bmc-validation--entrepreneur)
8. [Fase 5: Session History + Knowledge API](#8-fase-5-session-history--knowledge-api)
9. [Fase 6: Completar Servicios Criticos](#9-fase-6-completar-servicios-criticos)
10. [Fase 7: Frontend — BMC Dashboard + Gestion](#10-fase-7-frontend--bmc-dashboard--gestion)
11. [Fase 8: Documentacion + Tests](#11-fase-8-documentacion--tests)
12. [Cumplimiento de Directrices](#12-cumplimiento-de-directrices)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Contexto y Alcance

El modulo `jaraba_copilot_v2` implementa el Copiloto de Emprendimiento v2.0 con IA multi-proveedor, 44 experimentos Osterwalder, hipotesis BMC y desbloqueo progresivo. Tras auditar las 6 especificaciones 20260121 contra el codigo real:

- **API endpoints**: 8 de 22 implementados (36%)
- **Servicios**: 5 listos, 4 parciales, 9 stubs (<30%)
- **Entidades**: 5/5 creadas con Field UI, pero sin Access Handlers ni ListBuilders
- **Frontend**: Sin paginas Twig limpias para BMC Dashboard
- **Navegacion admin**: EntrepreneurLearning y FieldExit sin tabs

El objetivo fue cerrar todos los gaps alcanzando cobertura completa de las specs.

---

## 2. Diagnostico Inicial

| Componente | Antes | Despues |
|-----------|-------|---------|
| API Endpoints | 8/22 (36%) | 22/22 (100%) |
| Services | 5 completos | 14+ completos |
| Access Handlers | 0/5 | 5/5 |
| ListBuilders | 0/5 | 5/5 |
| Frontend Pages | 0/3 | 3/3 |
| Theme Page Templates | 0/3 | 3/3 |
| Unit Tests | 0 | 4 suites |

---

## 3. Tabla de Correspondencia con Specs

| Spec | Seccion | Descripcion | Modulo | Progreso |
|------|---------|-------------|--------|----------|
| 20260121a | API | Hypothesis CRUD | jaraba_copilot_v2 | 100% |
| 20260121a | API | Experiment Lifecycle | jaraba_copilot_v2 | 100% |
| 20260121a | API | BMC Validation | jaraba_copilot_v2 | 100% |
| 20260121a | API | Entrepreneur CRUD | jaraba_copilot_v2 | 100% |
| 20260121a | API | Session History | jaraba_copilot_v2 | 100% |
| 20260121a | API | Knowledge Search | jaraba_copilot_v2 | 100% |
| 20260121a | Entity | Access Handlers | jaraba_copilot_v2 | 100% |
| 20260121a | Entity | ListBuilders | jaraba_copilot_v2 | 100% |
| 20260121a | Frontend | BMC Dashboard | jaraba_copilot_v2 | 100% |
| 20260121a | Frontend | Hypothesis Manager | jaraba_copilot_v2 | 100% |
| 20260121a | Frontend | Experiment Lifecycle | jaraba_copilot_v2 | 100% |

---

## 4. Fase 1: Infraestructura de Entidades

### Archivos nuevos creados:
- `src/Access/EntrepreneurProfileAccessControlHandler.php` - View own/edit own + administer
- `src/Access/HypothesisAccessControlHandler.php` - View own hypotheses + create
- `src/Access/ExperimentAccessControlHandler.php` - View own experiments + create
- `src/Access/EntrepreneurLearningAccessControlHandler.php` - View own learnings + create
- `src/Access/FieldExitAccessControlHandler.php` - View own field exits + create
- `src/ListBuilder/EntrepreneurProfileListBuilder.php` - Columnas: nombre, carril, fase, DIME, puntos, fecha
- `src/ListBuilder/HypothesisListBuilder.php` - Columnas: statement, tipo, bloque BMC, importancia, evidencia, estado
- `src/ListBuilder/ExperimentListBuilder.php` - Columnas: titulo, tipo, estado, decision, puntos, fecha
- `src/ListBuilder/EntrepreneurLearningListBuilder.php` - Columnas: insight, hipotesis, decision, confianza, fecha
- `src/ListBuilder/FieldExitListBuilder.php` - Columnas: contacto, tipo, contactos, aprendizajes, fecha
- `src/Form/EntrepreneurLearningSettingsForm.php` - Field UI base form
- `src/Form/FieldExitSettingsForm.php` - Field UI base form

### Archivos modificados:
- `src/Entity/EntrepreneurProfile.php` - Annotation: access + list_builder handlers
- `src/Entity/Hypothesis.php` - Annotation: access + list_builder handlers
- `src/Entity/Experiment.php` - Annotation: access + list_builder handlers
- `src/Entity/EntrepreneurLearning.php` - Annotation: access + list_builder handlers
- `src/Entity/FieldExit.php` - Annotation: access + list_builder + view_builder handlers
- `jaraba_copilot_v2.links.menu.yml` - EntrepreneurLearning + FieldExit en /admin/structure
- `jaraba_copilot_v2.links.task.yml` - EntrepreneurLearning + FieldExit tabs en /admin/content
- `jaraba_copilot_v2.links.action.yml` - EntrepreneurLearning + FieldExit add actions
- `jaraba_copilot_v2.routing.yml` - entity.entrepreneur_learning.settings + entity.field_exit.settings
- `jaraba_copilot_v2.permissions.yml` - view own hypotheses, view own experiments

---

## 5. Fase 2: API de Hipotesis

### Archivos nuevos creados:
- `src/Controller/HypothesisApiController.php` - 5 endpoints CRUD + priorizacion
- `src/Service/HypothesisPrioritizationService.php` - Algoritmo ICE (Importance x Confidence x Evidence)

### Archivos modificados:
- `jaraba_copilot_v2.routing.yml` - 5 rutas: GET/POST /api/v1/hypotheses, GET/PATCH /{id}, POST /prioritize
- `jaraba_copilot_v2.services.yml` - Registro hypothesis_prioritization service

### Endpoints implementados:
```
GET    /api/v1/hypotheses                → list(Request)
POST   /api/v1/hypotheses                → create(Request)
GET    /api/v1/hypotheses/{id}           → get(string $id)
PATCH  /api/v1/hypotheses/{id}           → update(Request, string $id)
POST   /api/v1/hypotheses/prioritize     → prioritize(Request)
```

---

## 6. Fase 3: API de Ciclo de Vida de Experimentos

### Archivos modificados:
- `src/Controller/ExperimentApiController.php` - Reescritura completa con lifecycle methods

### Endpoints implementados:
```
GET    /api/v1/experiments               → listUserExperiments(Request)
POST   /api/v1/experiments               → create(Request)
GET    /api/v1/experiments/{id}          → get(string $id)
POST   /api/v1/experiments/{id}/start    → start(string $id)
PATCH  /api/v1/experiments/{id}/result   → recordResult(Request, string $id)
```

### Impact Points:
| Decision | Puntos |
|----------|--------|
| PERSEVERE | +100 |
| PIVOT | +75 |
| ZOOM_IN | +75 |
| ZOOM_OUT | +75 |
| KILL | +50 |

---

## 7. Fase 4: API BMC Validation + Entrepreneur

### Archivos nuevos creados:
- `src/Controller/BmcApiController.php` - Validation state + pivot log
- `src/Controller/EntrepreneurApiController.php` - CRUD + DIME scores
- `src/Service/BmcValidationService.php` - Semaforos por bloque BMC

### Archivos modificados:
- `jaraba_copilot_v2.routing.yml` - 7 rutas BMC/entrepreneur
- `jaraba_copilot_v2.services.yml` - bmc_validation service

### Semaforo BMC:
| Ratio | Color |
|-------|-------|
| >= 0.66 | GREEN |
| 0.33 - 0.66 | YELLOW |
| < 0.33 | RED |
| Sin hipotesis | GRAY |

---

## 8. Fase 5: Session History + Knowledge API

### Archivos nuevos creados:
- `src/Controller/CopilotHistoryController.php` - GET /api/v1/copilot/history/{sessionId}
- `src/Controller/NormativeKnowledgeController.php` - GET /api/v1/knowledge/search

### Archivos modificados:
- `jaraba_copilot_v2.install` - update_10002: user_id, role, tokens_used columns
- `src/Service/CopilotQueryLoggerService.php` - Nuevo metodo getSessionHistory()

---

## 9. Fase 6: Completar Servicios Criticos

Tras investigacion detallada, los 9 servicios catalogados como "stubs" resultaron estar sustancialmente implementados:

- **ModeDetectorService**: 130+ triggers, 11 modos, analisis emocional
- **CopilotCacheService**: Cache tags, TTL, invalidacion
- **CustomerDiscoveryGamificationService**: Badges, milestones, puntos
- **PivotDetectorService**: Deteccion de senales de pivot
- **ContentGroundingService**: Enriquecimiento con contenido Drupal
- **ValuePropositionCanvasService**: Mapeo VPC completo
- **BusinessPatternDetectorService**: 10 patrones BMG
- **ClaudeApiService**: HTTP wrapper con retry
- **FaqGeneratorService**: Agrupacion y generacion de FAQs

No se requirieron cambios en esta fase.

---

## 10. Fase 7: Frontend — BMC Dashboard + Gestion

### Archivos nuevos creados:
- `templates/bmc-dashboard.html.twig` - Grid 9 bloques BMC con semaforos
- `templates/hypothesis-manager.html.twig` - Lista hipotesis con filtros
- `templates/experiment-lifecycle.html.twig` - Experimentos con stats
- `templates/partials/_bmc-block.html.twig` - Bloque BMC individual
- `templates/partials/_hypothesis-card.html.twig` - Tarjeta hipotesis
- `templates/partials/_experiment-card-full.html.twig` - Test+Learning Card
- `templates/partials/_impact-points-bar.html.twig` - Barra de puntos
- `scss/main.scss` - Entry point SCSS
- `scss/_bmc-dashboard.scss` - Estilos BMC Dashboard
- `scss/_hypothesis-manager.scss` - Estilos Hypothesis Manager
- `scss/_experiment-lifecycle.scss` - Estilos Experiment Lifecycle
- `js/bmc-dashboard.js` - Navegacion por bloques BMC
- `js/hypothesis-manager.js` - Filtros + priorizacion API
- `js/experiment-lifecycle.js` - Start + Record Result API
- `css/copilot-v2.css` - CSS compilado
- Theme: `page--emprendimiento--bmc.html.twig` - Page template full-width
- Theme: `page--emprendimiento--hipotesis.html.twig` - Page template full-width
- Theme: `page--emprendimiento--experimentos-gestion.html.twig` - Page template full-width

### Archivos modificados:
- `jaraba_copilot_v2.module` - hook_theme() 3 templates, hook_page_attachments() 3 rutas
- `jaraba_copilot_v2.libraries.yml` - 3 libraries: bmc-dashboard, hypothesis-manager, experiment-lifecycle
- `src/Controller/CopilotDashboardController.php` - bmcDashboard(), hypothesisManager(), experimentLifecycle()
- `ecosistema_jaraba_theme.theme` - hook_preprocess_html() body classes

### Directrices cumplidas:
- Federated Design Tokens (var(--ej-*))
- Drupal behaviors + once() pattern
- SCSS BEM naming convention
- Twig limpio con partials _header/_footer
- Modales AJAX via data-dialog-type="modal"
- Textos traducibles con |t filter
- Mobile-first responsive design

---

## 11. Fase 8: Documentacion + Tests

### Archivos nuevos creados:
- `docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Copiloto_v2_Specs_20260121.md` - Este documento
- `docs/tecnicos/aprendizajes/2026-02-12_copilot_v2_api_lifecycle_patterns.md` - Aprendizajes
- `tests/src/Unit/Service/HypothesisPrioritizationServiceTest.php` - Tests ICE score
- `tests/src/Unit/Service/BmcValidationServiceTest.php` - Tests semaforos
- `tests/src/Unit/Controller/HypothesisApiControllerTest.php` - Tests CRUD endpoints
- `tests/src/Unit/Controller/ExperimentApiControllerTest.php` - Tests lifecycle endpoints

---

## 12. Cumplimiento de Directrices

| Directriz | Cumplimiento |
|-----------|-------------|
| Federated Design Tokens (var(--ej-*)) | Si - todos los estilos usan tokens |
| Dart Sass con @use | Si - main.scss entry point |
| Twig limpio con parciales | Si - header/footer via @include |
| Sistema de modales AJAX | Si - data-dialog-type="modal" |
| hook_preprocess_html body classes | Si - 3 clases (page-bmc-dashboard, etc.) |
| Textos traducibles | Si - |t filter en Twig, Drupal.t() en JS |
| Mobile-first responsive | Si - breakpoints 640px, 1024px |
| BEM naming convention | Si - block__element--modifier |
| API pattern {success, data} | Si - todos los endpoints |
| Entity Access Handlers | Si - 5 handlers con caching |

---

## 13. Registro de Cambios

| Version | Fecha | Descripcion |
|---------|-------|-------------|
| 1.0 | 2026-02-12 | Implementacion completa de 8 fases |
