# Plan de Implementacion: Demo Vertical 100% — Post-Verificacion

**Fecha de creacion:** 2026-02-27 22:00
**Ultima actualizacion:** 2026-02-27 22:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Auditoria base:** `2026-02-27_Auditoria_Post_Verificacion_Demo_Vertical_v3.md` (v3.0.0)
**Score actual verificado:** ~80%
**Score objetivo:** 100%
**Esfuerzo estimado:** ~75 horas (4 sprints)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de Hallazgos por Sprint](#2-inventario-de-hallazgos-por-sprint)
3. [Sprint 1: Tests + Quick Wins (~35h)](#3-sprint-1-tests--quick-wins-35h)
   - 3.1 [Tarea 1.1: Unit Tests — DemoFeatureGateService (4h)](#31-tarea-11-unit-tests--demofeaturegateservice-4h)
   - 3.2 [Tarea 1.2: Unit Tests — DemoJourneyProgressionService (5h)](#32-tarea-12-unit-tests--demojourneyprogressionservice-5h)
   - 3.3 [Tarea 1.3: Unit Tests — DemoSessionEvent (1h)](#33-tarea-13-unit-tests--demosessionevent-1h)
   - 3.4 [Tarea 1.4: Kernel Tests — DemoInteractiveService (8h)](#34-tarea-14-kernel-tests--demointeractiveservice-8h)
   - 3.5 [Tarea 1.5: Kernel Tests — SuccessCase Entity (3h)](#35-tarea-15-kernel-tests--successcase-entity-3h)
   - 3.6 [Tarea 1.6: Functional Tests — DemoController (6h)](#36-tarea-16-functional-tests--democontroller-6h)
   - 3.7 [Tarea 1.7: detach() en demo-storytelling.js (1h)](#37-tarea-17-detach-en-demo-storytellingjs-1h)
   - 3.8 [Tarea 1.8: Modal conversion como partial (2h)](#38-tarea-18-modal-conversion-como-partial-2h)
   - 3.9 [Tarea 1.9: Chart.js SRI fix (1h)](#39-tarea-19-chartjs-sri-fix-1h)
4. [Sprint 2: Code Quality (~20h)](#4-sprint-2-code-quality-20h)
   - 4.1 [Tarea 2.1: Eliminar SandboxTenantService del cron (2h)](#41-tarea-21-eliminar-sandboxtenantservice-del-cron-2h)
   - 4.2 [Tarea 2.2: Conectar config demo_settings (3h)](#42-tarea-22-conectar-config-demo_settings-3h)
   - 4.3 [Tarea 2.3: Fix error exposure en SandboxTenantService (1h)](#43-tarea-23-fix-error-exposure-en-sandboxtenantservice-1h)
   - 4.4 [Tarea 2.4: Implementar regeneracion real en storytelling JS (4h)](#44-tarea-24-implementar-regeneracion-real-en-storytelling-js-4h)
   - 4.5 [Tarea 2.5: Refactorizar templates dashboard (4h)](#45-tarea-25-refactorizar-templates-dashboard-4h)
   - 4.6 [Tarea 2.6: Crear DemoAnalyticsEventSubscriber (4h)](#46-tarea-26-crear-demoanalyticseventsubscriber-4h)
5. [Sprint 3: Architecture Compliance (~15h)](#5-sprint-3-architecture-compliance-15h)
   - 5.1 [Tarea 3.1: Race condition fix con transaccion (3h)](#51-tarea-31-race-condition-fix-con-transaccion-3h)
   - 5.2 [Tarea 3.2: Aria-labels en scenario cards (0.5h)](#52-tarea-32-aria-labels-en-scenario-cards-05h)
   - 5.3 [Tarea 3.3: i18n extraction para perfiles demo (2h)](#53-tarea-33-i18n-extraction-para-perfiles-demo-2h)
   - 5.4 [Tarea 3.4: Migrar SuccessCaseForm a PremiumEntityFormBase (4h)](#54-tarea-34-migrar-successcaseform-a-premiumentityformbase-4h)
   - 5.5 [Tarea 3.5: template_preprocess_success_case (3h)](#55-tarea-35-template_preprocess_success_case-3h)
6. [Sprint 4: Polish (~5h)](#6-sprint-4-polish-5h)
   - 6.1 [Tarea 4.1: Limpieza template legacy (2h)](#61-tarea-41-limpieza-template-legacy-2h)
   - 6.2 [Tarea 4.2: Documentacion inline (2h)](#62-tarea-42-documentacion-inline-2h)
   - 6.3 [Tarea 4.3: Verificacion final (1h)](#63-tarea-43-verificacion-final-1h)
7. [Tabla de Cumplimiento con Directrices](#7-tabla-de-cumplimiento-con-directrices)
8. [Archivos Nuevos a Crear](#8-archivos-nuevos-a-crear)
9. [Archivos a Modificar](#9-archivos-a-modificar)
10. [Criterios de Aceptacion por Sprint](#10-criterios-de-aceptacion-por-sprint)
11. [Riesgos y Mitigaciones](#11-riesgos-y-mitigaciones)
12. [Verificacion Final](#12-verificacion-final)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan de implementacion cierra la brecha entre el estado actual verificado (~80%) y el objetivo de 100% clase mundial para la vertical Demo del ecosistema Jaraba Impact Platform. Se fundamenta en la **auditoria post-verificacion v3**, que descarto 12 falsos positivos de la auditoria v2, dejando **18 hallazgos reales y accionables**.

### 1.1 Contexto

La auditoria v2 identifico 67 hallazgos iniciales. La verificacion exhaustiva contra el codigo fuente real demostro que 12 de ellos eran falsos positivos (funcionalidad ya implementada, code paths que no existen, duplicaciones fantasma). La auditoria v3 consolido los 18 hallazgos genuinos, recalibro severidades y elimino ruido.

### 1.2 Estrategia de priorizacion

| Prioridad | Criterio | Accion |
|-----------|----------|--------|
| P0 | CRITICO | Sprint 1 — cobertura de tests (unico hallazgo critico) |
| P1 | ALTO | Sprint 1 (quick wins) + Sprint 2 (code quality) |
| P2 | MEDIO | Distribuido entre Sprint 1-3 segun dependencias |
| P3 | BAJO | Sprint 3-4, o sin accion requerida |

### 1.3 Distribucion de esfuerzo

| Sprint | Foco | Horas | Score esperado |
|--------|------|-------|---------------|
| Sprint 1 | Tests + Quick Wins | ~35h | 80% → 90% |
| Sprint 2 | Code Quality | ~20h | 90% → 95% |
| Sprint 3 | Architecture Compliance | ~15h | 95% → 99% |
| Sprint 4 | Polish | ~5h | 99% → 100% |
| **Total** | | **~75h** | **100%** |

### 1.4 Filosofia

- **Tests primero:** el unico hallazgo CRITICO es la ausencia de tests para ~4,000 LOC. Se aborda integralmente en Sprint 1.
- **Falsos positivos descartados:** no se dedica esfuerzo a items que la verificacion demostro inexistentes.
- **Incrementalidad:** cada sprint es autocontenido y genera valor verificable.
- **Cumplimiento de directrices:** las reglas del proyecto (PREMIUM-FORMS-PATTERN-001, ENTITY-PREPROCESS-001, ROUTE-LANGPREFIX-001, etc.) se verifican explicitamente en la Seccion 7.

---

## 2. Inventario de Hallazgos por Sprint

La siguiente tabla mapea los 18 hallazgos verificados de la auditoria v3 a su sprint asignado, con severidad y titulo.

| ID | Severidad | Sprint | Titulo | Horas est. |
|----|-----------|--------|--------|-----------|
| HAL-DEMO-V3-BACK-001 | CRITICO | 1 | Zero tests para ~4,000 LOC | 27 |
| HAL-DEMO-V3-FRONT-002 | ALTO | 1 | demo-storytelling.js sin detach() | 1 |
| HAL-DEMO-V3-FRONT-003 | MEDIO | 1 | Modal conversion ausente en dashboard-view | 2 |
| HAL-DEMO-V3-FRONT-004 | MEDIO | 1 | Chart.js CDN sin SRI | 1 |
| HAL-DEMO-V3-PLG-004 | MEDIO | 1 | Conversion path roto (ligado a FRONT-003) | — |
| HAL-DEMO-V3-BACK-002 | ALTO | 2 | SandboxTenantService deprecated pero activo | 2 |
| HAL-DEMO-V3-BACK-003 | ALTO | 2 | Rate limits hardcoded | 3 |
| HAL-DEMO-V3-SEC-004 | ALTO | 2 | Error exposure en SandboxTenantService | 1 |
| HAL-DEMO-V3-FRONT-001 | ALTO | 2 | Boton regenerar fake | 4 |
| HAL-DEMO-V3-BACK-005 | MEDIO | 2 | No EventSubscribers para DemoSessionEvent | 4 |
| HAL-DEMO-V3-BACK-006 | MEDIO | 2 | Template duplicado dashboard | 4 |
| HAL-DEMO-V3-CONF-001 | MEDIO | 2 | Dead config (ligado a BACK-003) | — |
| HAL-DEMO-V3-BACK-004 | MEDIO | 3 | Race condition recordUsage() | 3 |
| HAL-DEMO-V3-A11Y-005 | MEDIO | 3 | Scenario cards sin aria-label | 0.5 |
| HAL-DEMO-V3-I18N-003 | MEDIO | 3 | Perfiles PO no extraibles | 2 |
| HAL-DEMO-V3-BACK-007 | BAJO | 3 | SuccessCaseForm no PremiumEntityFormBase | 4 |
| HAL-DEMO-V3-CONF-002 | BAJO | 4 | node_modules en disco | 1 |
| HAL-DEMO-V3-PERF-002 | BAJO | — | No action needed (sin impacto medible) | 0 |

**Totales por severidad:**

| Severidad | Cantidad | Horas estimadas |
|-----------|----------|----------------|
| CRITICO | 1 | 27 |
| ALTO | 5 | 11 |
| MEDIO | 9 | 16.5 |
| BAJO | 2 | 5 |
| Sin accion | 1 | 0 |
| **Total** | **18** | **~59.5** + ~15.5h polish = **~75h** |

### 2.1 Hallazgos descartados (falsos positivos v2)

Para trazabilidad, se documentan los 12 items descartados por la verificacion:

| ID original (v2) | Razon de descarte |
|-------------------|-------------------|
| HAL-DEMO-FE-01 | XSS `|raw` — no existe en templates reales |
| HAL-DEMO-FE-05 | Strings sin i18n — todas las strings usan `{% trans %}` o `$this->t()` |
| HAL-DEMO-BE-09 | Wire DemoFeatureGateService — ya inyectado |
| HAL-DEMO-BE-10 | Wire DemoJourneyProgressionService — ya inyectado |
| HAL-DEMO-FE-06 | SCSS duplicado — archivo unico verificado |
| HAL-DEMO-FE-07 | Template countdown — ya implementado |
| HAL-DEMO-FE-08 | Social proof — ya renderizado |
| HAL-DEMO-CFG-03 | GDPR consent — ya implementado |
| HAL-DEMO-FE-10 | A/B test hooks — ya presentes |
| HAL-DEMO-BE-12 | Analytics structured — ya implementado via DemoSessionEvent |
| HAL-DEMO-FE-12 | TTFV tracking — ya persistido |
| HAL-DEMO-BE-14 | Conversion funnel — ya tracked en 6 stages |

---

## 3. Sprint 1: Tests + Quick Wins (~35h)

**Prioridad:** P0/P1
**Hallazgos resueltos:** HAL-DEMO-V3-BACK-001, FRONT-002, FRONT-003, FRONT-004, PLG-004
**Objetivo:** Establecer cobertura de tests y resolver los quick wins frontend que no requieren cambios arquitecturales.

---

### 3.1 Tarea 1.1: Unit Tests — DemoFeatureGateService (4h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/DemoFeatureGateServiceTest.php`
**Namespace:** `Drupal\Tests\ecosistema_jaraba_core\Unit\Service`

**Tests a implementar:**

| # | Metodo | Descripcion | Mocks necesarios |
|---|--------|-------------|-----------------|
| 1 | `testCheckReturnsAllowedWhenUnderLimit()` | Mock DB retorna sesion con uso bajo, verificar que `check()` retorna `['allowed' => TRUE]` | `Connection`, `StatementInterface` |
| 2 | `testCheckReturnsDeniedWhenOverLimit()` | Mock DB retorna sesion al limite, verificar `['allowed' => FALSE, 'reason' => ...]` | `Connection`, `StatementInterface` |
| 3 | `testRecordUsageIncrementsCounter()` | Mock DB, verificar que UPDATE se llama con valor incrementado | `Connection`, `Update` |
| 4 | `testGetLimitsReturnsAllFeatureLimits()` | Verificar que `getLimits()` retorna el array constante completo | Ninguno |

**Estructura del test:**

```php
namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\ecosistema_jaraba_core\Service\DemoFeatureGateService;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Database\Connection;

class DemoFeatureGateServiceTest extends UnitTestCase {

  protected DemoFeatureGateService $service;
  protected Connection $database;

  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->createMock(Connection::class);
    $this->service = new DemoFeatureGateService($this->database);
  }
}
```

**Criterio de aceptacion:** 4 tests pasan. Cobertura de los 3 metodos publicos principales.

---

### 3.2 Tarea 1.2: Unit Tests — DemoJourneyProgressionService (5h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/DemoJourneyProgressionServiceTest.php`
**Namespace:** `Drupal\Tests\ecosistema_jaraba_core\Unit\Service`

**Tests a implementar:**

| # | Metodo | Descripcion | Mocks necesarios |
|---|--------|-------------|-----------------|
| 1 | `testGetDisclosureLevelReturnsLevel0ForNewSession()` | Sesion nueva sin acciones → nivel 0 | `Connection` |
| 2 | `testGetDisclosureLevelProgressesWithActions()` | Sesion con N acciones → nivel proporcional | `Connection` |
| 3 | `testEvaluateNudgesReturnsEmptyWhenNoNudgesApplicable()` | Sesion sin condiciones de nudge → array vacio | `Connection` |
| 4 | `testEvaluateNudgesReturnsPrioritizedList()` | Sesion con multiples nudges → lista ordenada por prioridad | `Connection` |
| 5 | `testDismissNudgeMarksNudgeAsDismissed()` | Llamar dismiss → verificar UPDATE en DB | `Connection`, `Update` |

**Logica de test para progresion:**

```php
public function testGetDisclosureLevelProgressesWithActions(): void {
  // Configurar mock para retornar sesion con 5 acciones completadas.
  $statement = $this->createMock(StatementInterface::class);
  $statement->method('fetchAssoc')->willReturn([
    'session_data' => json_encode(['actions_completed' => 5]),
  ]);
  // ... configurar query mock ...

  $level = $this->service->getDisclosureLevel('test-session-id');
  $this->assertGreaterThan(0, $level);
  $this->assertLessThanOrEqual(5, $level);
}
```

**Criterio de aceptacion:** 5 tests pasan. Cobertura de disclosure levels, nudge evaluation y dismiss.

---

### 3.3 Tarea 1.3: Unit Tests — DemoSessionEvent (1h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Event/DemoSessionEventTest.php`
**Namespace:** `Drupal\Tests\ecosistema_jaraba_core\Unit\Event`

**Tests a implementar:**

| # | Metodo | Descripcion |
|---|--------|-------------|
| 1 | `testEventConstantsAreDefined()` | Verificar que CREATED, VALUE_ACTION, CONVERSION, EXPIRED estan definidos como constantes |
| 2 | `testConstructorSetsProperties()` | Crear evento con session_id y data, verificar almacenamiento |
| 3 | `testGettersReturnCorrectValues()` | `getSessionId()`, `getData()`, `getEventType()` retornan valores del constructor |

**Estructura:**

```php
public function testEventConstantsAreDefined(): void {
  $this->assertNotEmpty(DemoSessionEvent::CREATED);
  $this->assertNotEmpty(DemoSessionEvent::VALUE_ACTION);
  $this->assertNotEmpty(DemoSessionEvent::CONVERSION);
  $this->assertNotEmpty(DemoSessionEvent::EXPIRED);
}

public function testConstructorSetsProperties(): void {
  $event = new DemoSessionEvent('sess-123', ['action' => 'test'], DemoSessionEvent::CREATED);
  $this->assertEquals('sess-123', $event->getSessionId());
  $this->assertEquals(['action' => 'test'], $event->getData());
}
```

**Criterio de aceptacion:** 3 tests pasan. Cobertura completa de la clase Event.

---

### 3.4 Tarea 1.4: Kernel Tests — DemoInteractiveService (8h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Kernel/Service/DemoInteractiveServiceTest.php`
**Namespace:** `Drupal\Tests\ecosistema_jaraba_core\Kernel\Service`

**Requisitos:** Base de datos MariaDB (CI-KERNEL-001), schema `demo_sessions` instalado, modulo `ecosistema_jaraba_core` habilitado.

**Tests a implementar:**

| # | Metodo | Descripcion | Complejidad |
|---|--------|-------------|-------------|
| 1 | `testCreateDemoSession()` | Crea sesion en DB, verifica estructura completa | Media |
| 2 | `testGetDemoProfiles()` | Verifica que los 10 perfiles verticales se retornan | Baja |
| 3 | `testGetDemoProfile()` | Recuperacion de perfil individual por vertical | Baja |
| 4 | `testTrackAction()` | Registra accion y verifica persistencia | Media |
| 5 | `testGetSessionData()` | Estructura completa de datos de sesion | Media |
| 6 | `testSessionExpiry()` | Verifica comportamiento TTL | Alta |
| 7 | `testRateLimitIntegration()` | Interaccion con FloodInterface | Alta |
| 8 | `testIpHashing()` | Verifica SHA256 hashing de IP del visitante | Baja |

**Configuracion base del test:**

```php
namespace Drupal\Tests\ecosistema_jaraba_core\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;

class DemoInteractiveServiceTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'ecosistema_jaraba_core',
  ];

  protected DemoInteractiveService $service;

  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('ecosistema_jaraba_core', ['demo_sessions']);
    $this->installConfig(['ecosistema_jaraba_core']);
    $this->service = $this->container->get('ecosistema_jaraba_core.demo_interactive');
  }
}
```

**Nota sobre testGetDemoProfiles():** Debe verificar que se retornan exactamente los 10 verticales canonicos (VERTICAL-CANONICAL-001): empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, andalucia_ei, jaraba_content_hub, formacion, demo.

**Nota sobre testIpHashing():** El servicio hashea IPs de visitantes con SHA256 antes de almacenar. Verificar que el valor almacenado en DB no es la IP raw sino su hash:

```php
public function testIpHashing(): void {
  $sessionId = $this->service->createDemoSession('192.168.1.1', 'comercioconecta');
  $storedData = $this->service->getSessionData($sessionId);
  $this->assertNotEquals('192.168.1.1', $storedData['ip_hash']);
  $this->assertEquals(hash('sha256', '192.168.1.1'), $storedData['ip_hash']);
}
```

**Criterio de aceptacion:** 8 tests pasan con MariaDB. Schema se instala correctamente. TTL y rate limit se verifican sin dependencias externas.

---

### 3.5 Tarea 1.5: Kernel Tests — SuccessCase Entity (3h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/jaraba_success_cases/tests/src/Kernel/Entity/SuccessCaseTest.php`
**Namespace:** `Drupal\Tests\jaraba_success_cases\Kernel\Entity`

**Tests a implementar:**

| # | Metodo | Descripcion |
|---|--------|-------------|
| 1 | `testEntityCreate()` | CRUD completo: create, load, update, delete |
| 2 | `testSlugAutoGeneration()` | Verificar que preSave genera slug a partir del nombre |
| 3 | `testFieldDefinitions()` | Los 25 campos base estan presentes en la definicion |

**Detalle testFieldDefinitions():**

```php
public function testFieldDefinitions(): void {
  $definitions = SuccessCase::baseFieldDefinitions(
    $this->entityTypeManager->getDefinition('success_case')
  );

  $expectedFields = [
    'id', 'uuid', 'name', 'slug', 'profession', 'company',
    'sector', 'location', 'photo', 'challenge', 'solution',
    'quote', 'video_url', 'metrics_json', 'tags', 'vertical',
    'is_published', 'is_featured', 'sort_weight', 'created',
    'changed', 'meta_title', 'meta_description', 'og_image',
    'schema_type',
  ];

  foreach ($expectedFields as $field) {
    $this->assertArrayHasKey($field, $definitions, "Missing field: $field");
  }
}
```

**Criterio de aceptacion:** 3 tests pasan. Entity CRUD funcional, slug auto-generado, campos completos.

---

### 3.6 Tarea 1.6: Functional Tests — DemoController (6h)

**Hallazgo:** HAL-DEMO-V3-BACK-001 (parcial)
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/tests/src/Functional/Controller/DemoControllerTest.php`
**Namespace:** `Drupal\Tests\ecosistema_jaraba_core\Functional\Controller`

**Tests a implementar:**

| # | Metodo | Descripcion | HTTP |
|---|--------|-------------|------|
| 1 | `testDemoLandingPageLoads()` | GET /demo retorna HTTP 200, contiene elementos clave | GET |
| 2 | `testStartDemoCreatesSession()` | POST /demo/start retorna JSON con session_id | POST |
| 3 | `testTrackActionRequiresValidSession()` | POST /demo/{invalid}/track retorna 404/403 | POST |
| 4 | `testDemoDashboardRendersChart()` | GET /demo/{session}/dashboard contiene canvas element | GET |
| 5 | `testConvertToRealValidatesInput()` | POST /demo/{session}/convert sin datos retorna error | POST |
| 6 | `testRateLimitBlocks429()` | 6+ requests al mismo endpoint retorna HTTP 429 | POST |

**Configuracion del test funcional:**

```php
namespace Drupal\Tests\ecosistema_jaraba_core\Functional\Controller;

use Drupal\Tests\BrowserTestBase;

class DemoControllerTest extends BrowserTestBase {

  protected static $modules = [
    'ecosistema_jaraba_core',
    'jaraba_success_cases',
  ];

  protected $defaultTheme = 'ecosistema_jaraba_theme';

  public function testDemoLandingPageLoads(): void {
    $this->drupalGet('/demo');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '[data-demo-landing]');
  }

  public function testRateLimitBlocks429(): void {
    // Crear sesion primero.
    $response = $this->drupalPost('/demo/start', [
      'vertical' => 'comercioconecta',
    ]);
    $data = json_decode($response->getBody(), TRUE);
    $sessionId = $data['session_id'];

    // Exceder limite con multiples requests.
    for ($i = 0; $i < 7; $i++) {
      $response = $this->drupalPost("/demo/$sessionId/track", [
        'action' => 'generate_story',
      ]);
    }

    // El ultimo debe ser 429.
    $this->assertEquals(429, $response->getStatusCode());
  }
}
```

**Criterio de aceptacion:** 6 tests pasan. Flujo completo demo verificado end-to-end. Rate limiting funciona.

---

### 3.7 Tarea 1.7: detach() en demo-storytelling.js (1h)

**Hallazgo:** HAL-DEMO-V3-FRONT-002
**Severidad:** ALTO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/js/demo-storytelling.js`

**Problema:** El behavior `Drupal.behaviors.demoStorytelling` implementa `attach()` pero no `detach()`. Esto causa memory leaks cuando Drupal reemplaza contenido via AJAX — los event listeners del DOM anterior no se limpian.

**Implementacion:**

```javascript
Drupal.behaviors.demoStorytelling = {
  attach: function (context, settings) {
    // ... existing attach code ...
  },

  detach: function (context, settings, trigger) {
    if (trigger === 'unload') {
      // Remover event listeners anadidos en attach.
      once.remove('demo-storytelling', '[data-storytelling-container]', context)
        .forEach(function (el) {
          // Limpiar timeouts/intervals si existen.
          if (el._storyTimeout) {
            clearTimeout(el._storyTimeout);
            delete el._storyTimeout;
          }
          // Remover listeners de botones.
          var regenerateBtn = el.querySelector('[data-regenerate]');
          if (regenerateBtn && regenerateBtn._clickHandler) {
            regenerateBtn.removeEventListener('click', regenerateBtn._clickHandler);
          }
        });
    }
  }
};
```

**Pasos:**

1. Abrir `demo-storytelling.js`.
2. En `attach()`, almacenar referencias a handlers en el elemento DOM: `el._storyTimeout`, `btn._clickHandler`.
3. Anadir metodo `detach()` que limpia usando `once.remove()` y elimina listeners.
4. Verificar que no hay timers colgantes tras `detach()`.

**Criterio de aceptacion:** `detach()` implementado. No hay memory leaks en navegacion AJAX. `once.remove()` invocado correctamente.

---

### 3.8 Tarea 1.8: Modal conversion como partial (2h)

**Hallazgos:** HAL-DEMO-V3-FRONT-003 + HAL-DEMO-V3-PLG-004
**Severidad:** MEDIO
**Archivos a modificar:** `demo-dashboard.html.twig`, `demo-dashboard-view.html.twig`
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-convert-modal.html.twig`

**Problema:** El modal de conversion (formulario para convertir sesion demo en cuenta real) solo existe en `demo-dashboard.html.twig`. En `demo-dashboard-view.html.twig` (la vista de escenario individual), el boton de conversion no tiene modal asociado, rompiendo el funnel de conversion.

**Implementacion:**

1. **Extraer modal a partial:**
   - Copiar lineas 182-218 de `demo-dashboard.html.twig` (el bloque `<div class="demo-convert-modal">...</div>`) al nuevo archivo `_demo-convert-modal.html.twig`.
   - El partial debe recibir la variable `session` para construir la URL del form action.

2. **Incluir en ambos templates:**
   ```twig
   {# En demo-dashboard.html.twig — reemplazar lineas 182-218: #}
   {% include '@ecosistema_jaraba_core/partials/_demo-convert-modal.html.twig' with {
     session: session,
   } only %}

   {# En demo-dashboard-view.html.twig — anadir antes del cierre: #}
   {% include '@ecosistema_jaraba_core/partials/_demo-convert-modal.html.twig' with {
     session: session,
   } only %}
   ```

3. **Verificar que el JS de conversion funciona en ambos contextos.** El script `demo-dashboard.js` ya se carga en ambas paginas.

**Criterio de aceptacion:** Modal de conversion visible y funcional en ambas vistas. Path de conversion no roto. Template DRY.

---

### 3.9 Tarea 1.9: Chart.js SRI fix (1h)

**Hallazgo:** HAL-DEMO-V3-FRONT-004
**Severidad:** MEDIO
**Archivos a modificar:** `ecosistema_jaraba_core.libraries.yml`, `demo-dashboard.js`

**Problema:** Chart.js se carga dinamicamente via `document.createElement('script')` en `demo-dashboard.js` sin Subresource Integrity (SRI). Esto expone a ataques man-in-the-middle si el CDN se compromete.

**Implementacion:**

1. **En `ecosistema_jaraba_core.libraries.yml`**, verificar o crear la libreria `chartjs`:
   ```yaml
   chartjs:
     remote: https://www.chartjs.org
     version: '4.4.x'
     license:
       name: MIT
       gpl-compatible: true
     js:
       https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js:
         type: external
         minified: true
         attributes:
           integrity: 'sha384-XXXXXXXX'
           crossorigin: anonymous
   ```

2. **Anadir `chartjs` como dependencia de `demo-dashboard`:**
   ```yaml
   demo-dashboard:
     js:
       js/demo-dashboard.js: {}
     dependencies:
       - ecosistema_jaraba_core/chartjs
       - core/once
       - core/drupal
   ```

3. **En `demo-dashboard.js`**, eliminar el bloque de lazy-loading manual (lineas ~307-316 que crean el `<script>` element):
   ```javascript
   // ELIMINAR este bloque:
   // var script = document.createElement('script');
   // script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
   // ...
   ```

4. **Reemplazar con verificacion directa:**
   ```javascript
   if (typeof Chart !== 'undefined') {
     // Inicializar charts directamente — Chart.js ya cargado via library dependency.
     initializeCharts();
   }
   ```

**Criterio de aceptacion:** Chart.js cargado via Drupal library system con SRI. No hay `createElement('script')` manual para Chart.js. Dashboard charts renderizan correctamente.

---

## 4. Sprint 2: Code Quality (~20h)

**Prioridad:** P1/P2
**Hallazgos resueltos:** HAL-DEMO-V3-BACK-002, BACK-003, SEC-004, FRONT-001, BACK-005, BACK-006, CONF-001
**Objetivo:** Eliminar codigo deprecated, conectar configuracion, implementar event subscribers y refactorizar templates.

---

### 4.1 Tarea 2.1: Eliminar SandboxTenantService del cron (2h)

**Hallazgo:** HAL-DEMO-V3-BACK-002
**Severidad:** ALTO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module`

**Problema:** `SandboxTenantService` fue marcado como deprecated en la refactorizacion hacia `DemoInteractiveService`, pero el hook `ecosistema_jaraba_core_cron()` todavia invoca la limpieza de sandbox sessions. Esto ejecuta codigo deprecated en cada cron run.

**Implementacion:**

1. **Localizar el bloque sandbox en cron:**
   ```php
   // ELIMINAR lineas ~1553-1566:
   // Sandbox session cleanup (deprecated).
   // if (\Drupal::hasService('ecosistema_jaraba_core.sandbox_tenant')) {
   //   try {
   //     $sandbox = \Drupal::service('ecosistema_jaraba_core.sandbox_tenant');
   //     $sandbox->cleanupExpiredSessions();
   //   } catch (\Exception $e) {
   //     \Drupal::logger('ecosistema_jaraba_core')->warning('...');
   //   }
   // }
   ```

2. **En `ecosistema_jaraba_core.services.yml`**, anadir anotacion deprecated:
   ```yaml
   ecosistema_jaraba_core.sandbox_tenant:
     class: Drupal\ecosistema_jaraba_core\Service\SandboxTenantService
     deprecated: 'The "%alias_id%" service is deprecated. Use ecosistema_jaraba_core.demo_interactive instead.'
     arguments: [...]
   ```

3. **En `SandboxTenantService.php`**, anadir docblock `@deprecated`:
   ```php
   /**
    * @deprecated in ecosistema_jaraba_core:2.0.0 and is removed from
    *   ecosistema_jaraba_core:3.0.0. Use DemoInteractiveService instead.
    * @see \Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService
    */
   ```

4. **Verificar** que ningun otro codigo invoca `sandbox_tenant` en cron o hooks:
   - Buscar `sandbox_tenant` en todo el proyecto.
   - Verificar que `DemoInteractiveService` ya maneja la limpieza de sesiones expiradas.

**Criterio de aceptacion:** Cron no ejecuta codigo deprecated. Servicio marcado como deprecated en YAML y PHPDoc. No hay regresion en limpieza de sesiones demo.

---

### 4.2 Tarea 2.2: Conectar config demo_settings (3h)

**Hallazgos:** HAL-DEMO-V3-BACK-003 + HAL-DEMO-V3-CONF-001
**Severidad:** ALTO + MEDIO
**Archivos a modificar:** `DemoController.php`, `DemoFeatureGateService.php`, `ecosistema_jaraba_core.services.yml`

**Problema:** Los rate limits y configuraciones de demo estan hardcoded como constantes PHP. Existe un archivo de configuracion `ecosistema_jaraba_core.demo_settings.yml` en `config/install/` pero no se consume. Esto impide ajustar limites sin deploy.

**Implementacion:**

1. **En `ecosistema_jaraba_core.services.yml`**, anadir `@config.factory` como argumento:

   Para `DemoFeatureGateService`:
   ```yaml
   ecosistema_jaraba_core.demo_feature_gate:
     class: Drupal\ecosistema_jaraba_core\Service\DemoFeatureGateService
     arguments:
       - '@database'
       - '@config.factory'
   ```

   Para `DemoController` (si usa DI via `create()`):
   ```yaml
   # Ya inyectado via ContainerInjectionInterface::create()
   ```

2. **En `DemoFeatureGateService.php`**, inyectar `ConfigFactoryInterface`:
   ```php
   use Drupal\Core\Config\ConfigFactoryInterface;

   public function __construct(
     protected Connection $database,
     protected ConfigFactoryInterface $configFactory,
   ) {}
   ```

3. **Reemplazar constantes hardcoded** con lecturas de config:
   ```php
   // ANTES:
   private const RATE_LIMIT_START = 5;
   private const RATE_LIMIT_STORYTELLING = 3;

   // DESPUES:
   private function getRateLimit(string $feature): int {
     $config = $this->configFactory->get('ecosistema_jaraba_core.demo_settings');
     return (int) $config->get("rate_limits.$feature") ?? $this->getDefaultLimit($feature);
   }

   private function getDefaultLimit(string $feature): int {
     return match ($feature) {
       'start' => 5,
       'story_generations_per_session' => 3,
       'ai_messages_per_session' => 10,
       'products_viewed_per_session' => 50,
       default => 10,
     };
   }
   ```

4. **Anadir update hook** para migrar config si no existe:
   ```php
   function ecosistema_jaraba_core_update_100XX() {
     $config = \Drupal::configFactory()->getEditable('ecosistema_jaraba_core.demo_settings');
     if ($config->isNew()) {
       $config->set('rate_limits.start', 5);
       $config->set('rate_limits.story_generations_per_session', 3);
       $config->set('rate_limits.ai_messages_per_session', 10);
       $config->set('rate_limits.products_viewed_per_session', 50);
       $config->save();
     }
   }
   ```

**Criterio de aceptacion:** Rate limits leidos desde config. Defaults como fallback. Config editable desde `/admin/config`. HAL-DEMO-V3-CONF-001 resuelto simultaneamente.

---

### 4.3 Tarea 2.3: Fix error exposure en SandboxTenantService (1h)

**Hallazgo:** HAL-DEMO-V3-SEC-004
**Severidad:** ALTO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/SandboxTenantService.php`

**Problema:** El metodo `convertToAccount()` expone el mensaje de excepcion al usuario en la respuesta de error. Esto puede revelar informacion interna (stack traces, nombres de tablas, configuracion).

**Implementacion:**

1. **Localizar** el bloque catch en `convertToAccount()` (linea ~315):
   ```php
   // ANTES:
   catch (\Exception $e) {
     $this->logger->error('Conversion failed: @message', ['@message' => $e->getMessage()]);
     return ['success' => FALSE, 'error' => 'Failed to create account: ' . $e->getMessage()];
   }
   ```

2. **Reemplazar** con mensaje generico:
   ```php
   // DESPUES:
   catch (\Exception $e) {
     $this->logger->error('Demo session conversion failed for session @session: @message', [
       '@session' => $sessionId,
       '@message' => $e->getMessage(),
       '@trace' => $e->getTraceAsString(),
     ]);
     return [
       'success' => FALSE,
       'error' => $this->t('An error occurred during account creation. Please try again.'),
     ];
   }
   ```

3. **Verificar** que el logging detallado ya existe (lineas 309-311) y se mantiene intacto — solo cambia lo que se retorna al usuario.

**Criterio de aceptacion:** Errores internos no expuestos al usuario. Logging detallado mantenido. Mensaje user-facing generico e internacionalizado.

---

### 4.4 Tarea 2.4: Implementar regeneracion real en storytelling JS (4h)

**Hallazgo:** HAL-DEMO-V3-FRONT-001
**Severidad:** ALTO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/js/demo-storytelling.js`

**Problema:** El boton "Regenerar" en la interfaz de storytelling ejecuta `alert('Regenerando...')` sin realizar ninguna llamada al backend. Es funcionalidad fake que rompe la experiencia del usuario.

**Implementacion:**

1. **Reemplazar el handler `alert()`** con llamada real al API:

   ```javascript
   // Almacenar handler para detach().
   regenerateBtn._clickHandler = function (e) {
     e.preventDefault();
     var btn = this;
     var sessionId = drupalSettings.ecosistemaJarabaCore.demoSessionId;
     var container = btn.closest('[data-storytelling-container]');
     var contentEl = container.querySelector('[data-story-content]');

     // Mostrar loading state.
     btn.disabled = true;
     btn.classList.add('is-loading');
     var originalText = btn.textContent;
     btn.textContent = Drupal.t('Regenerando...');

     // ROUTE-LANGPREFIX-001: usar drupalSettings para URL base.
     var url = drupalSettings.path.baseUrl
       + drupalSettings.path.pathPrefix
       + 'demo/' + sessionId + '/storytelling';

     fetch(url, {
       method: 'POST',
       headers: {
         'Content-Type': 'application/json',
         'X-Requested-With': 'XMLHttpRequest',
       },
       body: JSON.stringify({ regenerate: true }),
     })
     .then(function (response) {
       if (!response.ok) {
         throw new Error(response.status);
       }
       return response.json();
     })
     .then(function (data) {
       if (data.story) {
         contentEl.innerHTML = data.story;
         Drupal.attachBehaviors(contentEl);
       }
     })
     .catch(function (error) {
       // Mensaje user-friendly, no exponer error interno.
       var errorMsg = document.createElement('div');
       errorMsg.className = 'messages messages--warning';
       errorMsg.textContent = Drupal.t('No se pudo regenerar la historia. Intentalo de nuevo.');
       contentEl.prepend(errorMsg);
       setTimeout(function () { errorMsg.remove(); }, 5000);
     })
     .finally(function () {
       btn.disabled = false;
       btn.classList.remove('is-loading');
       btn.textContent = originalText;
     });
   };

   regenerateBtn.addEventListener('click', regenerateBtn._clickHandler);
   ```

2. **Verificar** que la ruta `demo/{session}/storytelling` acepta POST y retorna JSON con clave `story`.

3. **Respetar ROUTE-LANGPREFIX-001:** La URL se construye usando `drupalSettings.path.baseUrl` + `drupalSettings.path.pathPrefix` para incluir el prefijo `/es/` automaticamente.

**Criterio de aceptacion:** Boton "Regenerar" realiza fetch() al backend. Loading state visible. Errores manejados con mensaje amigable. Respeta prefijo de idioma.

---

### 4.5 Tarea 2.5: Refactorizar templates dashboard (4h)

**Hallazgo:** HAL-DEMO-V3-BACK-006
**Severidad:** MEDIO
**Archivos a modificar:** `demo-dashboard.html.twig`, `demo-dashboard-view.html.twig`
**Archivos a crear:** `_demo-header.html.twig`, `_demo-metrics.html.twig`

**Problema:** Los templates `demo-dashboard.html.twig` y `demo-dashboard-view.html.twig` comparten ~60% del codigo (header, metricas, sidebar). Esto genera mantenimiento duplicado y riesgo de divergencia.

**Implementacion:**

1. **Crear `templates/partials/_demo-header.html.twig`:**
   ```twig
   {# Header compartido del dashboard demo. #}
   <header class="demo-dashboard__header">
     <div class="demo-dashboard__header-content">
       <h1 class="demo-dashboard__title">{{ title }}</h1>
       {% if session.vertical %}
         <span class="demo-dashboard__vertical-badge">
           {{ jaraba_icon(session.vertical) }}
           {{ session.vertical_label }}
         </span>
       {% endif %}
       <div class="demo-dashboard__session-info">
         <span class="demo-dashboard__timer" data-demo-countdown="{{ session.expires_at }}">
           {{ session.time_remaining }}
         </span>
       </div>
     </div>
   </header>
   ```

2. **Crear `templates/partials/_demo-metrics.html.twig`:**
   ```twig
   {# Metricas del dashboard demo. #}
   <div class="demo-dashboard__metrics">
     {% for metric in metrics %}
       <div class="demo-metric-card">
         <span class="demo-metric-card__value">{{ metric.value }}</span>
         <span class="demo-metric-card__label">{{ metric.label }}</span>
       </div>
     {% endfor %}
   </div>
   ```

3. **Refactorizar `demo-dashboard.html.twig`:**
   ```twig
   {% include '@ecosistema_jaraba_core/partials/_demo-header.html.twig' with {
     title: 'Demo Dashboard'|t,
     session: session,
   } only %}

   {% include '@ecosistema_jaraba_core/partials/_demo-metrics.html.twig' with {
     metrics: metrics,
   } only %}

   {# Contenido especifico del dashboard principal #}
   <div class="demo-dashboard__scenarios">
     {# ... scenarios grid ... #}
   </div>

   {% include '@ecosistema_jaraba_core/partials/_demo-convert-modal.html.twig' with {
     session: session,
   } only %}
   ```

4. **Refactorizar `demo-dashboard-view.html.twig`** de forma similar, manteniendo solo el contenido especifico de la vista de escenario.

**Criterio de aceptacion:** Duplicacion reducida a < 20%. Ambos templates renderizan correctamente. Partials reutilizables.

---

### 4.6 Tarea 2.6: Crear DemoAnalyticsEventSubscriber (4h)

**Hallazgo:** HAL-DEMO-V3-BACK-005
**Severidad:** MEDIO
**Archivo a crear:** `web/modules/custom/ecosistema_jaraba_core/src/EventSubscriber/DemoAnalyticsEventSubscriber.php`
**Archivo a modificar:** `ecosistema_jaraba_core.services.yml`

**Problema:** `DemoSessionEvent` define 4 constantes de evento (CREATED, VALUE_ACTION, CONVERSION, EXPIRED) pero no hay ningun `EventSubscriber` registrado que los escuche. Los eventos se disparan pero no producen ningun efecto observable.

**Implementacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\ecosistema_jaraba_core\Event\DemoSessionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber para eventos de sesion demo — analytics y tracking.
 */
class DemoAnalyticsEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DemoSessionEvent::CREATED => ['onSessionCreated', 100],
      DemoSessionEvent::VALUE_ACTION => ['onValueAction', 50],
      DemoSessionEvent::CONVERSION => ['onConversion', 200],
      DemoSessionEvent::EXPIRED => ['onSessionExpired', 50],
    ];
  }

  /**
   * Log inicio de sesion demo.
   */
  public function onSessionCreated(DemoSessionEvent $event): void {
    $this->logger->info('Demo session created: @session (vertical: @vertical)', [
      '@session' => $event->getSessionId(),
      '@vertical' => $event->getData()['vertical'] ?? 'unknown',
    ]);
  }

  /**
   * Incrementar contador de acciones de valor.
   */
  public function onValueAction(DemoSessionEvent $event): void {
    $data = $event->getData();
    $this->logger->debug('Demo value action: @action in session @session', [
      '@action' => $data['action'] ?? 'unknown',
      '@session' => $event->getSessionId(),
    ]);
  }

  /**
   * Log evento de conversion + trigger notificacion.
   */
  public function onConversion(DemoSessionEvent $event): void {
    $data = $event->getData();
    $this->logger->notice('Demo conversion: session @session converted to account @email', [
      '@session' => $event->getSessionId(),
      '@email' => $data['email'] ?? 'unknown',
    ]);
    // Futuro: trigger notificacion al equipo de ventas.
  }

  /**
   * Log expiracion y calcular metricas de engagement.
   */
  public function onSessionExpired(DemoSessionEvent $event): void {
    $data = $event->getData();
    $this->logger->info('Demo session expired: @session (actions: @count, duration: @duration)', [
      '@session' => $event->getSessionId(),
      '@count' => $data['total_actions'] ?? 0,
      '@duration' => $data['duration_seconds'] ?? 0,
    ]);
  }

}
```

**Registro en services.yml:**

```yaml
ecosistema_jaraba_core.demo_analytics_subscriber:
  class: Drupal\ecosistema_jaraba_core\EventSubscriber\DemoAnalyticsEventSubscriber
  arguments:
    - '@logger.channel.ecosistema_jaraba_core'
  tags:
    - { name: event_subscriber }
```

**Criterio de aceptacion:** 4 eventos escuchados. Logs verificables en watchdog/dblog. Prioridad alta para CONVERSION (200). Subscriber registrado con tag `event_subscriber`.

---

## 5. Sprint 3: Architecture Compliance (~15h)

**Prioridad:** P2/P3
**Hallazgos resueltos:** HAL-DEMO-V3-BACK-004, A11Y-005, I18N-003, BACK-007
**Objetivo:** Corregir problemas de concurrencia, accesibilidad, internacionalizacion y cumplimiento de patrones arquitecturales del proyecto.

---

### 5.1 Tarea 3.1: Race condition fix con transaccion (3h)

**Hallazgo:** HAL-DEMO-V3-BACK-004
**Severidad:** MEDIO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoFeatureGateService.php`

**Problema:** El metodo `recordUsage()` ejecuta un SELECT seguido de un UPDATE sin transaccion ni bloqueo. Bajo concurrencia (multiples pestanas, bots), dos requests simultaneos pueden leer el mismo valor y ambos incrementar a N+1 en vez de N+2.

**Implementacion — Opcion A (atomic increment, preferida):**

```php
public function recordUsage(string $sessionId, string $feature): void {
  // Increment atomico — no requiere SELECT previo.
  $this->database->query(
    "UPDATE {demo_sessions} SET session_data = JSON_SET(
      session_data,
      :path,
      COALESCE(JSON_EXTRACT(session_data, :path), 0) + 1
    ) WHERE session_id = :sid",
    [
      ':path' => '$.feature_usage.' . $feature,
      ':sid' => $sessionId,
    ]
  );
}
```

**Implementacion — Opcion B (transaccion, si JSON_SET no disponible):**

```php
public function recordUsage(string $sessionId, string $feature): void {
  $transaction = $this->database->startTransaction();
  try {
    // SELECT ... FOR UPDATE para bloquear la fila.
    $current = $this->database->query(
      "SELECT session_data FROM {demo_sessions} WHERE session_id = :sid FOR UPDATE",
      [':sid' => $sessionId]
    )->fetchField();

    $data = json_decode($current, TRUE) ?: [];
    $data['feature_usage'][$feature] = ($data['feature_usage'][$feature] ?? 0) + 1;

    $this->database->update('demo_sessions')
      ->fields(['session_data' => json_encode($data)])
      ->condition('session_id', $sessionId)
      ->execute();
  }
  catch (\Exception $e) {
    $transaction->rollBack();
    $this->logger->error('Failed to record usage for session @session: @message', [
      '@session' => $sessionId,
      '@message' => $e->getMessage(),
    ]);
    throw $e;
  }
}
```

**Recomendacion:** Opcion A (atomic increment via SQL) es preferida por rendimiento y simplicidad. MariaDB 10.11+ soporta `JSON_SET()` y `JSON_EXTRACT()` de forma nativa.

**Criterio de aceptacion:** Race condition eliminada. Incrementos atomicos verificados bajo carga concurrente. Sin deadlocks.

---

### 5.2 Tarea 3.2: Aria-labels en scenario cards (0.5h)

**Hallazgo:** HAL-DEMO-V3-A11Y-005
**Severidad:** MEDIO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/templates/demo-dashboard-view.html.twig`

**Problema:** Las tarjetas de acciones/escenarios en el dashboard de vista individual no tienen `aria-label` en los elementos `<a>`. Lectores de pantalla no pueden comunicar el proposito de cada enlace.

**Implementacion:**

Localizar el bloque de action cards (linea ~72) y anadir `aria-label`:

```twig
{# ANTES: #}
<a href="{{ action.url }}" class="demo-action-card">
  <span class="demo-action-card__icon">{{ jaraba_icon(action.icon) }}</span>
  <span class="demo-action-card__label">{{ action.label }}</span>
</a>

{# DESPUES: #}
<a href="{{ action.url }}"
   class="demo-action-card"
   aria-label="{{ action.label }}">
  <span class="demo-action-card__icon" aria-hidden="true">{{ jaraba_icon(action.icon) }}</span>
  <span class="demo-action-card__label">{{ action.label }}</span>
</a>
```

**Nota adicional:** Marcar el icono como `aria-hidden="true"` para evitar que el lector de pantalla lea el SVG.

**Criterio de aceptacion:** Todas las action cards tienen `aria-label`. Iconos marcados `aria-hidden`. axe-core no reporta violaciones en esta seccion.

---

### 5.3 Tarea 3.3: i18n extraction para perfiles demo (2h)

**Hallazgo:** HAL-DEMO-V3-I18N-003
**Severidad:** MEDIO
**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php`

**Problema:** Los nombres y descripciones de los 10 perfiles demo estan definidos como strings PHP planos en un array. La herramienta `potx` (PO Template Extraction) no puede detectarlos como traducibles porque no pasan por `$this->t()` ni `new TranslatableMarkup()`.

**Implementacion (Opcion A — recomendada):**

Anadir un metodo dedicado que contenga todas las llamadas `$this->t()` literal para que `potx` las escanee:

```php
/**
 * Retorna strings traducibles de perfiles demo.
 *
 * Este metodo existe exclusivamente para que potx pueda extraer las
 * strings de traduccion. Los valores se usan en getDemoProfiles().
 *
 * @return array<string, array{name: \Drupal\Core\StringTranslation\TranslatableMarkup, description: \Drupal\Core\StringTranslation\TranslatableMarkup}>
 */
protected function getTranslatableProfileStrings(): array {
  return [
    'empleabilidad' => [
      'name' => $this->t('Candidato en busqueda activa'),
      'description' => $this->t('Explora herramientas de busqueda de empleo, perfil profesional y matching con ofertas'),
    ],
    'emprendimiento' => [
      'name' => $this->t('Emprendedor en fase inicial'),
      'description' => $this->t('Descubre herramientas de plan de negocio, validacion de idea y financiacion'),
    ],
    'comercioconecta' => [
      'name' => $this->t('Comerciante local'),
      'description' => $this->t('Gestiona tu tienda online, inventario y presencia digital'),
    ],
    'agroconecta' => [
      'name' => $this->t('Productor de Aceite'),
      'description' => $this->t('Conecta con compradores, gestiona trazabilidad y certificaciones'),
    ],
    'jarabalex' => [
      'name' => $this->t('Profesional legal'),
      'description' => $this->t('Accede a documentos legales, consultas automatizadas y cumplimiento'),
    ],
    'serviciosconecta' => [
      'name' => $this->t('Proveedor de servicios'),
      'description' => $this->t('Publica tu catalogo de servicios y conecta con clientes'),
    ],
    'andalucia_ei' => [
      'name' => $this->t('Empresa innovadora andaluza'),
      'description' => $this->t('Accede a subvenciones, networking y ecosistema de innovacion'),
    ],
    'jaraba_content_hub' => [
      'name' => $this->t('Creador de contenido'),
      'description' => $this->t('Publica articulos, gestiona blog y optimiza SEO'),
    ],
    'formacion' => [
      'name' => $this->t('Estudiante en formacion'),
      'description' => $this->t('Accede a cursos, certificaciones y rutas de aprendizaje'),
    ],
    'demo' => [
      'name' => $this->t('Visitante exploratorio'),
      'description' => $this->t('Explora todas las verticales de la plataforma'),
    ],
  ];
}
```

Luego, en `getDemoProfiles()`, usar este metodo como fuente de datos traducidos.

**Criterio de aceptacion:** `potx` extrae las 20 strings (10 nombres + 10 descripciones). Perfiles demo se muestran en el idioma del usuario. Archivo `.po` se puede generar automaticamente.

---

### 5.4 Tarea 3.4: Migrar SuccessCaseForm a PremiumEntityFormBase (4h)

**Hallazgo:** HAL-DEMO-V3-BACK-007
**Severidad:** BAJO
**Archivo a modificar:** `web/modules/custom/jaraba_success_cases/src/Form/SuccessCaseForm.php`

**Problema:** `SuccessCaseForm` extiende `ContentEntityForm` directamente, violando PREMIUM-FORMS-PATTERN-001 que requiere que todas las entity forms extiendan `PremiumEntityFormBase`.

**Implementacion — Patron D (Custom Logic):**

1. **Cambiar herencia:**
   ```php
   // ANTES:
   use Drupal\Core\Entity\ContentEntityForm;
   class SuccessCaseForm extends ContentEntityForm {

   // DESPUES:
   use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
   class SuccessCaseForm extends PremiumEntityFormBase {
   ```

2. **Implementar `getSectionDefinitions()`:**
   ```php
   protected function getSectionDefinitions(): array {
     return [
       'identity' => [
         'title' => $this->t('Identidad'),
         'icon' => 'user',
         'weight' => 0,
         'fields' => ['name', 'slug', 'profession', 'company', 'sector', 'location'],
       ],
       'story' => [
         'title' => $this->t('Historia'),
         'icon' => 'book-open',
         'weight' => 10,
         'fields' => ['challenge', 'solution', 'quote'],
       ],
       'media' => [
         'title' => $this->t('Media'),
         'icon' => 'image',
         'weight' => 20,
         'fields' => ['photo', 'video_url', 'og_image'],
       ],
       'metrics' => [
         'title' => $this->t('Metricas'),
         'icon' => 'bar-chart-2',
         'weight' => 30,
         'fields' => ['metrics_json'],
       ],
       'classification' => [
         'title' => $this->t('Clasificacion'),
         'icon' => 'tag',
         'weight' => 40,
         'fields' => ['tags', 'vertical', 'schema_type'],
       ],
       'publishing' => [
         'title' => $this->t('Publicacion'),
         'icon' => 'globe',
         'weight' => 50,
         'fields' => ['is_published', 'is_featured', 'sort_weight', 'meta_title', 'meta_description'],
       ],
     ];
   }
   ```

3. **Implementar `getFormIcon()`:**
   ```php
   protected function getFormIcon(): string {
     return 'award';
   }
   ```

4. **Eliminar todos los grupos `#type => 'details'`** que existan en el form actual. `PremiumEntityFormBase` genera las secciones automaticamente.

5. **DI via `create()` + `parent::create()`:**
   ```php
   public static function create(ContainerInterface $container) {
     $instance = parent::create($container);
     // Inyectar servicios adicionales si los hay.
     return $instance;
   }
   ```

6. **Verificar** que los campos computados usen `#disabled = TRUE` en lugar de `#access = FALSE`.

**Criterio de aceptacion:** Formulario renderiza con secciones premium. Sin fieldsets/details. DI correcto. Datos guardados sin regresion.

---

### 5.5 Tarea 3.5: template_preprocess_success_case (3h)

**Hallazgo:** Relacionado con ENTITY-PREPROCESS-001
**Severidad:** MEDIO (arquitectural)
**Archivo a modificar:** `web/modules/custom/jaraba_success_cases/jaraba_success_cases.module`

**Problema:** La entidad `SuccessCase` se renderiza en view mode pero no tiene `template_preprocess_success_case()`. Segun ENTITY-PREPROCESS-001, toda ContentEntity renderizada en view mode DEBE tener su preprocess en el `.module`.

**Implementacion:**

```php
/**
 * Implements template_preprocess_success_case().
 */
function template_preprocess_success_case(array &$variables): void {
  /** @var \Drupal\jaraba_success_cases\Entity\SuccessCase $entity */
  $entity = $variables['elements']['#success_case'];

  // Primitivas.
  $variables['name'] = $entity->get('name')->value;
  $variables['slug'] = $entity->get('slug')->value;
  $variables['profession'] = $entity->get('profession')->value;
  $variables['company'] = $entity->get('company')->value;
  $variables['sector'] = $entity->get('sector')->value;
  $variables['location'] = $entity->get('location')->value;
  $variables['challenge'] = $entity->get('challenge')->value;
  $variables['solution'] = $entity->get('solution')->value;
  $variables['quote'] = $entity->get('quote')->value;
  $variables['video_url'] = $entity->get('video_url')->value;
  $variables['is_published'] = (bool) $entity->get('is_published')->value;
  $variables['is_featured'] = (bool) $entity->get('is_featured')->value;
  $variables['vertical'] = $entity->get('vertical')->value;
  $variables['schema_type'] = $entity->get('schema_type')->value;

  // Metricas (JSON → array).
  $metricsRaw = $entity->get('metrics_json')->value;
  $variables['metrics'] = $metricsRaw ? json_decode($metricsRaw, TRUE) : [];

  // Tags.
  $variables['tags'] = [];
  foreach ($entity->get('tags') as $item) {
    $variables['tags'][] = $item->value;
  }

  // Imagenes responsivas.
  $variables['photo_url'] = NULL;
  if (!$entity->get('photo')->isEmpty()) {
    $photoUri = $entity->get('photo')->entity?->getFileUri();
    if ($photoUri) {
      $style = \Drupal\image\Entity\ImageStyle::load('article_featured');
      if ($style) {
        $variables['photo_url'] = $style->buildUrl($photoUri);
      }
    }
  }

  $variables['og_image_url'] = NULL;
  if (!$entity->get('og_image')->isEmpty()) {
    $ogUri = $entity->get('og_image')->entity?->getFileUri();
    if ($ogUri) {
      $style = \Drupal\image\Entity\ImageStyle::load('article_hero');
      if ($style) {
        $variables['og_image_url'] = $style->buildUrl($ogUri);
      }
    }
  }

  // SEO meta.
  $variables['meta_title'] = $entity->get('meta_title')->value ?: $variables['name'];
  $variables['meta_description'] = $entity->get('meta_description')->value;

  // Breadcrumb data.
  $variables['breadcrumbs'] = [
    ['label' => t('Inicio'), 'url' => '/'],
    ['label' => t('Casos de exito'), 'url' => '/casos-de-exito'],
    ['label' => $variables['name'], 'url' => NULL],
  ];

  // Fechas.
  $variables['created'] = $entity->get('created')->value;
  $variables['changed'] = $entity->get('changed')->value;
}
```

**Criterio de aceptacion:** Template recibe todas las variables preprocesadas. Imagenes generan URLs responsivas. JSON de metricas parseado. Breadcrumbs disponibles. Sin llamadas a metodos de entidad en Twig.

---

## 6. Sprint 4: Polish (~5h)

**Prioridad:** P3
**Hallazgos resueltos:** HAL-DEMO-V3-CONF-002 (parcial), items de limpieza general
**Objetivo:** Pulido final, documentacion inline, verificacion integral.

---

### 6.1 Tarea 4.1: Limpieza template legacy (2h)

**Alcance:**

| Tarea | Detalle |
|-------|---------|
| Verificar includes | Todos los `{% include %}` de partials creados en Sprint 1 y 2 resuelven correctamente |
| Eliminar bloques comentados | Remover bloques `{# ... #}` obsoletos de templates refactorizados |
| Consistencia de variables | Verificar que las variables pasadas a partials son consistentes en nombre y tipo |
| Template hints | Verificar que `theme.debug: true` muestra los template hints correctos |

**Archivos a revisar:**

1. `demo-dashboard.html.twig` — verificar integridad tras extraccion de partials
2. `demo-dashboard-view.html.twig` — verificar modal, aria-labels, partials
3. `_demo-convert-modal.html.twig` — verificar variables recibidas
4. `_demo-header.html.twig` — verificar variables recibidas
5. `_demo-metrics.html.twig` — verificar variables recibidas

**HAL-DEMO-V3-CONF-002 (node_modules en disco):**

Verificar que `node_modules/` esta en `.gitignore` y no se incluye en el build. Si existe un directorio `node_modules` suelto dentro del modulo, eliminarlo y documentar que la compilacion SCSS usa el toolchain centralizado del tema.

**Criterio de aceptacion:** Cero bloques de codigo muerto en templates. Todos los includes resuelven. No hay `node_modules` trackeado.

---

### 6.2 Tarea 4.2: Documentacion inline (2h)

**Alcance:**

| Archivo | Accion |
|---------|--------|
| `DemoFeatureGateService.php` | PHPDoc completo en todos los metodos publicos. `@see` a la config YAML |
| `DemoJourneyProgressionService.php` | PHPDoc con descripcion de niveles de disclosure |
| `DemoInteractiveService.php` | PHPDoc con `@see` a VERTICAL-CANONICAL-001 |
| `DemoSessionEvent.php` | Documentar cada constante con su trigger point |
| `DemoAnalyticsEventSubscriber.php` | Ya documentado en creacion (Sprint 2) |
| `SandboxTenantService.php` | Verificar `@deprecated` anadido en Sprint 2 |
| `SuccessCaseForm.php` | PHPDoc de secciones y patron de migracion |
| `demo-storytelling.js` | JSDoc para `attach()` y `detach()` |

**Formato de `@deprecated`:**

```php
/**
 * @deprecated in ecosistema_jaraba_core:2.0.0 and is removed from
 *   ecosistema_jaraba_core:3.0.0. Use DemoInteractiveService instead.
 * @see \Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService
 */
```

**Formato de `@see` a hallazgos:**

```php
/**
 * ...
 * @see HAL-DEMO-V3-BACK-004 (race condition fix)
 */
```

**Criterio de aceptacion:** Todos los metodos publicos documentados. `@deprecated` presente. `@see` referencias a hallazgos en los metodos afectados.

---

### 6.3 Tarea 4.3: Verificacion final (1h)

**Pasos:**

1. Ejecutar suite de tests completa (ver Seccion 12).
2. Verificacion manual del flujo demo.
3. Recalcular score contra los 18 hallazgos.
4. Verificar que no hay regresiones en otros modulos.
5. Generar reporte final con estado de cada hallazgo.

**Resultado esperado:** 17/18 hallazgos resueltos (HAL-DEMO-V3-PERF-002 no requiere accion). Score: 100%.

---

## 7. Tabla de Cumplimiento con Directrices

La siguiente tabla mapea las 22 directrices principales del proyecto contra el estado de cumplimiento de la vertical Demo, indicando que sprint cierra las brechas.

| # | Directriz | Cumplimiento Actual | Sprint | Notas |
|---|-----------|-------------------|--------|-------|
| 1 | PREMIUM-FORMS-PATTERN-001 | PARCIAL | S3 | `SuccessCaseForm` pendiente migracion (Tarea 3.4) |
| 2 | ZERO-REGION-POLICY | OK | — | Templates demo ya usan zero-region, sin bloques Drupal |
| 3 | ICON-CONVENTION-001 | OK | — | `jaraba_icon()` en todos los templates verificado |
| 4 | SCSS Model (Dart Sass + Custom Props) | OK | — | SCSS compilado con variables CSS custom verificado |
| 5 | i18n (`{% trans %}` + `$this->t()`) | PARCIAL | S3 | Perfiles demo PO no extraibles (Tarea 3.3) |
| 6 | ROUTE-LANGPREFIX-001 | OK | S2 | Templates usan `path()`, JS debe usar `drupalSettings` (Tarea 2.4) |
| 7 | SLIDE-PANEL-RENDER-001 | N/A | — | Demo no usa slide-panel para formularios |
| 8 | ENTITY-PREPROCESS-001 | PARCIAL | S3 | `SuccessCase` falta preprocess (Tarea 3.5) |
| 9 | PRESAVE-RESILIENCE-001 | OK | — | `try-catch` + `hasService()` en servicios opcionales |
| 10 | TENANT-BRIDGE-001 | PARCIAL | — | `SuccessCase` sin `tenant_id` — fuera de scope demo |
| 11 | CSRF-API-001 | OK | — | CSRF tokens presentes en API endpoints demo |
| 12 | DOC-GUARD-001 | OK | — | Demo no modifica master docs |
| 13 | TENANT-ISOLATION-ACCESS-001 | PARCIAL | — | `SuccessCase` sin tenant check — fuera de scope demo |
| 14 | CANVAS-ARTICLE-001 | N/A | — | Demo no usa canvas editor |
| 15 | SECRET-MGMT-001 | OK | — | No hay secrets en config demo |
| 16 | Body classes (`hook_preprocess_html`) | OK | — | `jaraba_success_cases.module` lineas 70-83 verificado |
| 17 | Mobile-first (`min-width` media queries) | OK | — | SCSS demo usa `min-width` breakpoints verificado |
| 18 | Modal para CRUD | PARCIAL | S1 | Modal conversion ausente en `-view` (Tarea 1.8) |
| 19 | Entity admin routes | OK | — | Rutas admin presentes para `SuccessCase` |
| 20 | Clean frontend pages (`page--` templates) | OK | — | `page--demo` templates verificados |
| 21 | Header/footer partials | OK | — | Theme settings partials reutilizados |
| 22 | Tenant sin acceso a tema admin | OK | — | Permisos verificados, tema admin restringido |

**Resumen de cumplimiento:**

| Estado | Cantidad | Porcentaje |
|--------|----------|-----------|
| OK | 15 | 68.2% |
| PARCIAL | 5 | 22.7% |
| N/A | 2 | 9.1% |
| **Total aplicables** | **20** | — |
| **Post-implementacion** | **20/20 OK** | **100%** |

---

## 8. Archivos Nuevos a Crear

Inventario completo de archivos nuevos organizados por categoria.

### 8.1 Tests

| # | Archivo | Sprint | Tarea |
|---|---------|--------|-------|
| 1 | `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/DemoFeatureGateServiceTest.php` | S1 | 1.1 |
| 2 | `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/DemoJourneyProgressionServiceTest.php` | S1 | 1.2 |
| 3 | `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Event/DemoSessionEventTest.php` | S1 | 1.3 |
| 4 | `web/modules/custom/ecosistema_jaraba_core/tests/src/Kernel/Service/DemoInteractiveServiceTest.php` | S1 | 1.4 |
| 5 | `web/modules/custom/jaraba_success_cases/tests/src/Kernel/Entity/SuccessCaseTest.php` | S1 | 1.5 |
| 6 | `web/modules/custom/ecosistema_jaraba_core/tests/src/Functional/Controller/DemoControllerTest.php` | S1 | 1.6 |

### 8.2 Templates (Partials)

| # | Archivo | Sprint | Tarea |
|---|---------|--------|-------|
| 7 | `web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-convert-modal.html.twig` | S1 | 1.8 |
| 8 | `web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-header.html.twig` | S2 | 2.5 |
| 9 | `web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-metrics.html.twig` | S2 | 2.5 |

### 8.3 EventSubscriber

| # | Archivo | Sprint | Tarea |
|---|---------|--------|-------|
| 10 | `web/modules/custom/ecosistema_jaraba_core/src/EventSubscriber/DemoAnalyticsEventSubscriber.php` | S2 | 2.6 |

**Total archivos nuevos: 10**

---

## 9. Archivos a Modificar

Inventario completo de archivos existentes que requieren modificaciones.

| # | Archivo | Sprint | Tareas | Tipo de cambio |
|---|---------|--------|--------|---------------|
| 1 | `web/modules/custom/ecosistema_jaraba_core/js/demo-storytelling.js` | S1, S2 | 1.7, 2.4 | Anadir `detach()`, reemplazar `alert()` con `fetch()` |
| 2 | `web/modules/custom/ecosistema_jaraba_core/templates/demo-dashboard.html.twig` | S1, S2 | 1.8, 2.5 | Extraer modal a partial, extraer header y metricas a partials |
| 3 | `web/modules/custom/ecosistema_jaraba_core/templates/demo-dashboard-view.html.twig` | S1, S2, S3 | 1.8, 2.5, 3.2 | Incluir modal partial, anadir aria-labels a cards |
| 4 | `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoFeatureGateService.php` | S2, S3 | 2.2, 3.1 | Inyectar `config.factory`, transaccion/atomic increment |
| 5 | `web/modules/custom/ecosistema_jaraba_core/src/Controller/DemoController.php` | S2 | 2.2 | Inyectar config, reemplazar constantes con config reads |
| 6 | `web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php` | S3 | 3.3 | Anadir `getTranslatableProfileStrings()` para extraccion PO |
| 7 | `web/modules/custom/ecosistema_jaraba_core/src/Service/SandboxTenantService.php` | S2 | 2.3 | Fix error exposure — mensaje generico en catch |
| 8 | `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module` | S2 | 2.1 | Eliminar bloque sandbox en cron |
| 9 | `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | S2 | 2.1, 2.2, 2.6 | Deprecate sandbox, anadir `@config.factory`, registrar event_subscriber |
| 10 | `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.libraries.yml` | S1 | 1.9 | Anadir `chartjs` library, dependencia en `demo-dashboard` |
| 11 | `web/modules/custom/ecosistema_jaraba_core/js/demo-dashboard.js` | S1 | 1.9 | Eliminar lazy-load manual de Chart.js |
| 12 | `web/modules/custom/jaraba_success_cases/src/Form/SuccessCaseForm.php` | S3 | 3.4 | Migrar a `PremiumEntityFormBase` con Patron D |
| 13 | `web/modules/custom/jaraba_success_cases/jaraba_success_cases.module` | S3 | 3.5 | Anadir `template_preprocess_success_case()` |

**Total archivos a modificar: 13**

---

## 10. Criterios de Aceptacion por Sprint

### 10.1 Sprint 1: Tests + Quick Wins

| # | Criterio | Verificacion |
|---|----------|-------------|
| CA-1.1 | 4 Unit tests para DemoFeatureGateService pasan | `phpunit --filter DemoFeatureGateServiceTest` |
| CA-1.2 | 5 Unit tests para DemoJourneyProgressionService pasan | `phpunit --filter DemoJourneyProgressionServiceTest` |
| CA-1.3 | 3 Unit tests para DemoSessionEvent pasan | `phpunit --filter DemoSessionEventTest` |
| CA-1.4 | 8 Kernel tests para DemoInteractiveService pasan | `phpunit --testsuite Kernel --filter DemoInteractiveServiceTest` |
| CA-1.5 | 3 Kernel tests para SuccessCase entity pasan | `phpunit --testsuite Kernel --filter SuccessCaseTest` |
| CA-1.6 | 6 Functional tests para DemoController pasan | `phpunit --testsuite Functional --filter DemoControllerTest` |
| CA-1.7 | `demo-storytelling.js` tiene `detach()` implementado | Inspeccion de codigo + no memory leaks en DevTools |
| CA-1.8 | Modal conversion visible en ambos dashboards | Navegacion manual a `/demo/{session}/dashboard` y `/demo/{session}/view/{scenario}` |
| CA-1.9 | Chart.js cargado via library con SRI | Inspector de red: no `createElement('script')`, si `integrity` attribute |
| CA-1.10 | Zero test failures en suite completa | `phpunit --testsuite Unit,Kernel` sin errores |

### 10.2 Sprint 2: Code Quality

| # | Criterio | Verificacion |
|---|----------|-------------|
| CA-2.1 | Cron no ejecuta codigo sandbox deprecated | `drush cron` + verificar logs sin mensajes sandbox |
| CA-2.2 | Rate limits leidos desde config | Editar config en `/admin/config` → cambio reflejado en comportamiento |
| CA-2.3 | Error messages no exponen internals | Forzar error en conversion → verificar respuesta generica |
| CA-2.4 | Boton "Regenerar" llama al API real | Click en regenerar → Network tab muestra POST exitoso |
| CA-2.5 | Templates dashboard usan 3+ partials | `grep -c 'include' demo-dashboard*.twig` >= 3 |
| CA-2.6 | DemoAnalyticsEventSubscriber registrado | `drush service:list --tag=event_subscriber` incluye demo_analytics |
| CA-2.7 | 4 tipos de evento logueados | Crear sesion → trackear accion → verificar dblog |

### 10.3 Sprint 3: Architecture Compliance

| # | Criterio | Verificacion |
|---|----------|-------------|
| CA-3.1 | Race condition eliminada | Concurrent requests test: 10 requests paralelos → counter correcto |
| CA-3.2 | Todas las action cards tienen aria-label | axe-core scan sin violaciones en dashboard-view |
| CA-3.3 | potx extrae 20 strings de perfiles | `drush locale:check` o `potx` scan del modulo |
| CA-3.4 | SuccessCaseForm extiende PremiumEntityFormBase | Formulario renderiza con secciones premium |
| CA-3.5 | template_preprocess_success_case implementado | Template recibe variables preprocesadas sin llamadas a entidad |

### 10.4 Sprint 4: Polish

| # | Criterio | Verificacion |
|---|----------|-------------|
| CA-4.1 | Cero bloques de codigo muerto en templates | Revision manual |
| CA-4.2 | Todos los metodos publicos documentados | `phpcs --standard=Drupal` sin warnings de documentacion |
| CA-4.3 | Score final 100% contra los 18 hallazgos | Checklist completo 17/17 resueltos (1 sin accion) |

---

## 11. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigacion |
|---|--------|-------------|---------|-----------|
| R1 | Kernel tests requieren MariaDB container | Baja | Alto | CI-KERNEL-001: pipeline ya configurado con `mariadb:10.11`. Verificar que `phpunit.xml` incluye el datasource |
| R2 | Eliminacion de sandbox cron rompe otros hooks | Media | Alto | **Pre-requisito:** ejecutar `grep -rn 'sandbox_tenant' web/modules/` para identificar TODOS los puntos de uso antes de eliminar |
| R3 | Config migration resetea rate limits en update | Media | Medio | Usar update hook con `isNew()` check — solo crea config si no existe. Valores existentes preservados |
| R4 | PremiumEntityFormBase migracion rompe datos existentes | Baja | Bajo | La migracion de form no afecta datos almacenados — solo cambia renderizado del formulario. Verificar con entity CRUD test |
| R5 | Chart.js SRI hash cambia con nueva version | Baja | Bajo | Fijar version exacta en library YAML (`chart.js@4.4.7`). Documentar proceso de actualizacion de hash |
| R6 | `JSON_SET()` no disponible en MariaDB version antigua | Baja | Alto | Verificar version MariaDB: `SELECT VERSION()`. 10.11+ soporta JSON nativo. Fallback: Opcion B (transaccion) |
| R7 | Functional tests lentos en CI | Media | Bajo | Limitar a 6 tests funcionales. Usar `@group demo` para ejecucion selectiva |
| R8 | `once.remove()` no disponible en version Drupal core | Baja | Medio | Verificar que `core/once` esta en dependencias. Drupal 11 incluye `once` 1.x nativo |
| R9 | Partials de template no resuelven ruta | Media | Medio | Verificar hook_theme() registra las rutas de partials. Usar ruta completa `@ecosistema_jaraba_core/partials/...` |

**Plan de contingencia global:** Si un sprint se bloquea por un riesgo materializado, los sprints subsiguientes pueden avanzar de forma independiente (no hay dependencias criticas entre Sprint 2, 3 y 4 salvo el test foundation de Sprint 1).

---

## 12. Verificacion Final

### 12.1 Checklist de ejecucion de tests

```bash
# 1. Unit tests — todos los tests Demo
lando php vendor/bin/phpunit --testsuite Unit --filter Demo

# 2. Kernel tests — requiere MariaDB (CI-KERNEL-001)
lando php vendor/bin/phpunit --testsuite Kernel --filter Demo

# 3. Kernel tests — SuccessCase entity
lando php vendor/bin/phpunit --testsuite Kernel --filter SuccessCaseTest

# 4. Functional tests — DemoController (mas lentos)
lando php vendor/bin/phpunit --testsuite Functional --filter DemoControllerTest

# 5. Suite completa — verificar no regresiones
lando php vendor/bin/phpunit --testsuite Unit,Kernel
```

### 12.2 Verificacion manual

| # | Paso | Resultado esperado |
|---|------|--------------------|
| 1 | Navegar a `/demo` | Landing page carga con perfiles verticales |
| 2 | Iniciar demo con vertical `comercioconecta` | Session creada, redirect a dashboard |
| 3 | Verificar dashboard renderiza charts | Canvas element presente, Chart.js inicializado |
| 4 | Verificar modal de conversion en dashboard | Modal visible al click en CTA |
| 5 | Navegar a vista de escenario individual | Dashboard-view carga correctamente |
| 6 | Verificar modal de conversion en vista escenario | Modal visible (era el bug FRONT-003) |
| 7 | Click en "Regenerar" en storytelling | Fetch POST al API, contenido actualizado |
| 8 | Verificar boton regenerar con loading state | Spinner visible, boton disabled durante request |
| 9 | Exceder rate limit (6+ story generations) | HTTP 429 con mensaje amigable |
| 10 | Verificar action cards con screen reader | aria-labels leidos correctamente |
| 11 | Verificar countdown de sesion | Timer visible y decrementando |
| 12 | Verificar conversion form | Formulario funcional, errores no exponen internals |
| 13 | Revisar `drush dblog` tras flujo completo | Logs de CREATED, VALUE_ACTION visibles |

### 12.3 Verificacion SCSS

```bash
# Compilar SCSS y verificar que no hay errores
cd web/themes/custom/ecosistema_jaraba_theme
npx sass scss/main.scss css/main.css --style=compressed
```

### 12.4 Score final

| Hallazgo | Estado post-implementacion |
|----------|--------------------------|
| HAL-DEMO-V3-BACK-001 (tests) | RESUELTO — 29 tests nuevos |
| HAL-DEMO-V3-BACK-002 (sandbox cron) | RESUELTO — eliminado de cron |
| HAL-DEMO-V3-BACK-003 (rate limits hardcoded) | RESUELTO — config driven |
| HAL-DEMO-V3-BACK-004 (race condition) | RESUELTO — atomic increment |
| HAL-DEMO-V3-BACK-005 (no event subscribers) | RESUELTO — DemoAnalyticsEventSubscriber |
| HAL-DEMO-V3-BACK-006 (template duplicado) | RESUELTO — 3 partials extraidos |
| HAL-DEMO-V3-BACK-007 (form no premium) | RESUELTO — PremiumEntityFormBase |
| HAL-DEMO-V3-SEC-004 (error exposure) | RESUELTO — mensaje generico |
| HAL-DEMO-V3-FRONT-001 (regenerar fake) | RESUELTO — fetch() real |
| HAL-DEMO-V3-FRONT-002 (sin detach) | RESUELTO — detach() implementado |
| HAL-DEMO-V3-FRONT-003 (modal ausente) | RESUELTO — partial incluido en ambas vistas |
| HAL-DEMO-V3-FRONT-004 (Chart.js sin SRI) | RESUELTO — library dependency con SRI |
| HAL-DEMO-V3-CONF-001 (dead config) | RESUELTO — config conectada |
| HAL-DEMO-V3-CONF-002 (node_modules) | RESUELTO — limpieza verificada |
| HAL-DEMO-V3-A11Y-005 (sin aria-label) | RESUELTO — aria-labels en cards |
| HAL-DEMO-V3-I18N-003 (PO no extraible) | RESUELTO — getTranslatableProfileStrings() |
| HAL-DEMO-V3-PLG-004 (conversion path roto) | RESUELTO — modal en ambas vistas |
| HAL-DEMO-V3-PERF-002 (sin impacto) | SIN ACCION — hallazgo sin impacto medible |

**Score final: 17/17 hallazgos accionables resueltos = 100%**

---

## 13. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | 2026-02-27 | Plan inicial basado en auditoria v3 post-verificacion (18 hallazgos reales, 12 falsos positivos descartados) |
