# 🎯 Avatar Detection + Empleabilidad UI — Activación y Verificación

**Fecha:** 2026-02-12
**Autor:** IA Asistente
**Versión:** 1.0.0
**Sesión:** Activación de 7 fases implementadas del plan Avatar Detection (20260120a) + Empleabilidad UI (20260120b)

---

## 📑 Tabla de Contenidos

1. [Contexto](#1-contexto)
2. [Fases Implementadas](#2-fases-implementadas)
3. [Hallazgos y Correcciones](#3-hallazgos-y-correcciones)
4. [Reglas Nuevas](#4-reglas-nuevas)
5. [Resultados de Testing](#5-resultados-de-testing)
6. [Lecciones Aprendidas](#6-lecciones-aprendidas)

---

## 1. Contexto

Las especificaciones `20260120a` (Flujo de Detección de Avatar) y `20260120b` (Recorrido Interfaz Empleabilidad v1) definen el flujo completo end-to-end del vertical de Empleabilidad. Tras la implementación de las 7 fases, se realizó una activación sistemática verificando:

- Entorno Docker/Lando (PHP 8.4.15, Drupal 11.3.2)
- Integridad de 25+ ficheros (13 PHP, 12 YAML)
- Instalación de dependencias (dompdf v2.0.8)
- Registro de 16 entidades en base de datos
- Cache rebuild + verificación de servicios y rutas
- Compilación SCSS (Dart Sass)
- Ejecución de suite de tests unitarios

---

## 2. Fases Implementadas

### Fase 1: AvatarDetectionService (Fundación)

| Componente | Descripción |
|------------|-------------|
| **AvatarDetectionResult** | ValueObject inmutable: avatarType, vertical, detectionSource, programaOrigen, confidence |
| **AvatarDetectionService** | Cascada 4 niveles: Domain → Path/UTM → Group → Rol |
| **DashboardRedirectController** | `/dashboard` redirige según avatar detectado |
| **Tests** | 7 casos, 32 assertions (AvatarDetectionServiceTest) |

### Fase 2: Diagnóstico Express Empleabilidad

| Componente | Descripción |
|------------|-------------|
| **EmployabilityDiagnostic** | ContentEntityType con 14 campos (q_linkedin, q_cv_ats, q_estrategia, score, profile_type, primary_gap, anonymous_token, email_remarketing, avatar_confirmed) |
| **EmployabilityScoringService** | Pesos: LinkedIn 40%, CV ATS 35%, Estrategia 25%. Umbrales: <2=Invisible, <4=Desconectado, <6=En Construcción, <8=Competitivo, ≥8=Magnético |
| **EmployabilityDiagnosticController** | 3 rutas bajo `/empleabilidad/diagnostico` (landing, processAndShowResults, showResults) |
| **Frontend** | Template Twig hero+wizard 3 pasos, JS wizard, SCSS compilado (9,662 bytes CSS) |

### Fase 3: Hooks ECA (USR-001 + USR-002)

| Hook | Efecto |
|------|--------|
| **hook_user_insert()** | Crea JourneyState(avatar=pending, state=discovery), detecta vertical vía AvatarDetectionService |
| **hook_entity_insert(employability_diagnostic)** | Asigna rol 'candidate', actualiza JourneyState, inscripción LMS vía DiagnosticEnrollmentService, +50 créditos |
| **Tests** | UserInsertHookTest (3 casos, 15 assertions) + EmployabilityDiagnosticInsertTest (3 casos, 14 assertions) |

### Fase 4: AI Copilot para Empleabilidad

| Componente | Descripción |
|------------|-------------|
| **EmployabilityCopilotAgent** | Extiende BaseAgent de jaraba_ai_agents. 6 modos: Profile Coach, Job Advisor, Interview Prep, Learning Guide, Application Helper, FAQ |
| **CopilotApiController** | POST /api/v1/copilot/employability/chat + GET /suggestions |
| **DI** | @ai.provider, @config.factory, @logger.channel.jaraba_ai_agents, @jaraba_ai_agents.tenant_brand_voice, @jaraba_ai_agents.observability, @ecosistema_jaraba_core.unified_prompt_builder |

### Fase 5: CV PDF Export

| Componente | Descripción |
|------------|-------------|
| **dompdf v2.0.8** | Instalado vía `lando composer require dompdf/dompdf:^2.0` |
| **CvBuilderService::convertHtmlToPdf()** | Instancia Dompdf, inyecta CSS Design Tokens, render A4 portrait |
| **Nota** | Deprecation warnings PHP 8.4 (nullable params) — no bloqueante |

### Fase 6: Sistema de Modales

| Componente | Descripción |
|------------|-------------|
| **modal-system.js** | Behavior Drupal que detecta links `class="use-ajax"` + `data-dialog-type="modal"` |
| **Library** | `ecosistema_jaraba_core/modal-system` con deps: core/drupal.dialog, core/drupal.dialog.ajax |

### Fase 7: Partials Frontend

| Partial | Descripción |
|---------|-------------|
| `_application-pipeline.html.twig` | Mini-pipeline horizontal de candidaturas |
| `_job-card.html.twig` | Card reutilizable con match score |
| `_gamification-stats.html.twig` | Barra compacta: racha + nivel + logros |
| `_profile-completeness.html.twig` | Ring SVG de completitud |

---

## 3. Hallazgos y Correcciones

### 3.1 Logger Channels Faltantes

**Problema:** El contenedor de Drupal no compilaba porque `logger.channel.jaraba_analytics` y `logger.channel.jaraba_pixels` no estaban declarados en sus respectivos services.yml.

**Fix:**
```yaml
# En jaraba_analytics.services.yml y jaraba_pixels.services.yml
logger.channel.{module}:
  class: Drupal\Core\Logger\LoggerChannel
  factory: logger.factory:get
  arguments: ['{module}']
```

**Regla derivada:** SERVICE-001

### 3.2 Servicio Brand Voice Incorrecto

**Problema:** `jaraba_candidate.services.yml` referenciaba `@jaraba_ai_agents.brand_voice` (no existe) en lugar de `@jaraba_ai_agents.tenant_brand_voice`.

**Fix:** Corregido a `@jaraba_ai_agents.tenant_brand_voice` + `@logger.channel.jaraba_ai_agents` + `@ecosistema_jaraba_core.unified_prompt_builder`.

### 3.3 EntityOwnerInterface Faltante

**Problema:** `EmployabilityDiagnostic` usaba `EntityOwnerTrait` pero no implementaba `EntityOwnerInterface`, lo que causaba error al instalar la entidad.

**Fix:**
```php
class EmployabilityDiagnostic extends ContentEntityBase
  implements EntityChangedInterface, EntityOwnerInterface {
  use EntityChangedTrait;
  use EntityOwnerTrait;
}
```

**Regla derivada:** ENTITY-001

### 3.4 PHP 8.4 Property Type Redeclaration (16 Controllers)

**Problema:** PHP 8.4 prohíbe que una clase hija redeclare propiedades tipadas de la clase padre. `ControllerBase` ya declara `protected EntityTypeManagerInterface $entityTypeManager` y `protected AccountInterface $currentUser`. Los controllers que usaban promoted constructor params para estas propiedades causaban fatal error.

**Controllers afectados (16):**
- ComplianceDashboardController, PushApiController (ecosistema_jaraba_core)
- MarketplaceController, DeveloperPortalController (jaraba_integrations)
- AgentFlowDashboardController, AgentFlowApiController (jaraba_agent_flows)
- NpsSurveyController, NpsApiController (jaraba_customer_success)
- ReferralApiController (jaraba_referral)
- ThemePreviewController (jaraba_theming)
- AdsOAuthController (jaraba_ads)
- FunnelApiController, CohortApiController, ReportApiController (jaraba_analytics)
- PwaApiController (jaraba_pwa)
- OnboardingDashboardController, OnboardingApiController (jaraba_onboarding)

**Fix:** Eliminar `protected` de los promoted constructor params para propiedades heredadas y asignar manualmente:
```php
// ANTES (fatal error en PHP 8.4)
public function __construct(
  protected EntityTypeManagerInterface $entityTypeManager,
  protected MyService $myService,
) {}

// DESPUÉS (correcto)
protected MyService $myService;
public function __construct(
  EntityTypeManagerInterface $entityTypeManager,
  MyService $myService,
) {
  $this->entityTypeManager = $entityTypeManager;
  $this->myService = $myService;
}
```

**Regla derivada:** DRUPAL11-001

### 3.5 Métodos Faltantes en Controllers

**ABTestingApiController:** Añadidos 5 métodos: `recordExposure()`, `listExposures()`, `calculateResults()`, `checkAutoStop()`, `declareWinner()`.

**ReferralApiController:** Añadidos 3 métodos: `listReferrals()`, `processReferral()`, `stats()`.

### 3.6 Drupal 11 applyUpdates() Eliminado

**Problema:** `jaraba_billing.install` usaba `EntityDefinitionUpdateManager::applyUpdates()` que fue eliminado en Drupal 11.

**Fix:**
```php
function jaraba_billing_update_10001(): void {
  $updateManager = \Drupal::entityDefinitionUpdateManager();
  $entityTypeManager = \Drupal::entityTypeManager();
  $newEntityTypes = ['billing_invoice', 'billing_payment_method', ...];
  foreach ($newEntityTypes as $entityTypeId) {
    if (!$updateManager->getEntityType($entityTypeId)) {
      $entityType = $entityTypeManager->getDefinition($entityTypeId, FALSE);
      if ($entityType) {
        $updateManager->installEntityType($entityType);
      }
    }
  }
}
```

**Regla derivada:** DRUPAL11-002

### 3.7 Dart Sass @use Scoping

**Problema:** `_mobile-components.scss` no compilaba porque usaba variables `$ej-bg-surface` sin importarlas. En Dart Sass, `@use` crea scope aislado.

**Fix:** Añadir `@use '../variables' as *;` al inicio del parcial.

**Regla derivada:** SCSS-001

### 3.8 Test Mocks Incorrectos

**ResellerCommissionServiceTest:** `FieldItemListInterface` no tiene método `referencedEntities()`. Fix: cambiar a `EntityReferenceFieldItemListInterface`.

**ExposureTrackingServiceTest:** `\Drupal::time()` no disponible en unit test. Fix: mock de container con `TimeInterface`.

---

## 4. Reglas Nuevas

| ID | Nombre | Descripción | Impacto |
|----|--------|-------------|---------|
| **DRUPAL11-001** | PHP 8.4 Property Redeclaration | Clases hijas NO pueden redeclarar propiedades tipadas heredadas de padre. Eliminar `protected` de promoted params y asignar manualmente | 16 controllers afectados |
| **DRUPAL11-002** | applyUpdates() Removal | `EntityDefinitionUpdateManager::applyUpdates()` eliminado en Drupal 11. Usar `installEntityType()` por entidad | Update hooks |
| **SERVICE-001** | Logger Channel Factory | Declarar logger channel en services.yml con factory `logger.factory:get` | Todo módulo con logging |
| **ENTITY-001** | EntityOwnerInterface | Entity con `EntityOwnerTrait` DEBE implementar `EntityOwnerInterface` + `EntityChangedInterface` | Nuevas entidades |
| **SCSS-001** | Dart Sass @use Scoping | Cada parcial SCSS debe importar sus propias variables con `@use '../variables' as *;` | Todos los .scss |

---

## 5. Resultados de Testing

### Suite Completa

| Métrica | Valor |
|---------|-------|
| **Tests ejecutados** | 789 |
| **Tests exitosos** | 730 (92.5%) |
| **Fallos** | 59 (preexistentes en módulos no relacionados) |
| **Módulos nuevos testeados** | ecosistema_jaraba_core (AvatarDetection, UserInsert, EmployabilityDiagnosticInsert), jaraba_ab_testing (ExposureTracking), ResellerCommission |

### Tests Unitarios Nuevos

| Test | Casos | Assertions | Resultado |
|------|-------|------------|-----------|
| AvatarDetectionServiceTest | 7 | 32 | ✅ PASS |
| UserInsertHookTest | 3 | 15 | ✅ PASS |
| EmployabilityDiagnosticInsertTest | 3 | 14 | ✅ PASS |

### Compilación SCSS

| Fichero | Tamaño | Resultado |
|---------|--------|-----------|
| employability-diagnostic.css | 9,662 bytes | ✅ Compilado |
| main.css (ecosistema_jaraba_theme) | 544,199 bytes | ✅ Compilado |

---

## 6. Lecciones Aprendidas

### 6.1 PHP 8.4 Cambia las Reglas de Herencia
PHP 8.4 refuerza la protección de propiedades tipadas en clases padre. En Drupal, donde `ControllerBase` ya declara propiedades como `$entityTypeManager`, esto afecta a TODO controller que las inyecte vía promoted constructor params. **Acción preventiva:** Auditar todos los controllers nuevos antes de activar.

### 6.2 Drupal 11 Elimina APIs de Conveniencia
`applyUpdates()` era un atajo cómodo pero Drupal 11 lo eliminó. La alternativa (`installEntityType()` por entidad) es más verbosa pero más controlada. **Verificar siempre** las APIs deprecated antes de usar update hooks.

### 6.3 Dart Sass Module System es Estricto
A diferencia de node-sass, Dart Sass NO propaga variables entre ficheros importados con `@use`. Cada parcial necesita sus propios imports. Esto es más seguro pero requiere disciplina.

### 6.4 Los Mocks Deben Usar Interfaces Correctas
PHPUnit mocks solo exponen métodos de la interfaz mockeada. `FieldItemListInterface` no tiene `referencedEntities()` — solo `EntityReferenceFieldItemListInterface` lo tiene. **Verificar la interfaz correcta** antes de crear mocks.

### 6.5 Contenedor de Drupal en Unit Tests
Cuando el servicio bajo test usa `\Drupal::time()` u otros servicios estáticos, el test debe crear un `ContainerBuilder` mock y registrarlo con `\Drupal::setContainer()`.

### 6.6 Activación Sistemática = Calidad de Clase Mundial
La activación paso a paso (integridad → dependencias → entidades → caché → SCSS → tests) detectó 12 errores que habrían sido invisibles en producción. **Siempre** activar nuevo código de forma metódica antes de desplegarlo.

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-12 | 1.0.0 | Documento inicial — activación 7 fases Avatar Detection + Empleabilidad UI |
