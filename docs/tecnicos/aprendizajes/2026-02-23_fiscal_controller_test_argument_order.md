# Fix Orden de Argumentos en FiscalDashboardControllerTest

**Fecha:** 2026-02-23
**Sesion:** Correccion de 3 tests unitarios fallando en CI por orden incorrecto de argumentos del constructor
**Regla nueva:** CTRL-ARGS-001
**Aprendizaje #104**

---

## Contexto

El pipeline CI (Jaraba SaaS - Deploy to IONOS) fallaba con 3 errores en `FiscalDashboardControllerTest`. Los 2762 tests restantes pasaban correctamente. Los 3 errores eran `TypeError` en el constructor de `FiscalDashboardController`.

---

## Lecciones Aprendidas

### 1. Al refactorizar el orden de parametros de un constructor, TODOS los tests deben actualizarse

**Situacion:** El constructor de `FiscalDashboardController` fue refactorizado para mover `$tenantContext` de la 7ma posicion a la 3ra (obligatorio, no nullable):

```php
// ANTES (orden antiguo):
public function __construct(
  FiscalComplianceService $complianceService,  // #1
  LoggerInterface $logger,                      // #2
  ?object $hashService = NULL,                  // #3
  ?object $faceClient = NULL,                   // #4
  ?object $paymentStatusService = NULL,          // #5
  ?object $certificateManager = NULL,            // #6
  ?TenantContextService $tenantContext = NULL,    // #7
)

// DESPUES (orden actual):
public function __construct(
  FiscalComplianceService $complianceService,  // #1
  LoggerInterface $logger,                      // #2
  TenantContextService $tenantContext,           // #3 (obligatorio)
  ?object $hashService = NULL,                  // #4
  ?object $faceClient = NULL,                   // #5
  ?object $paymentStatusService = NULL,          // #6
  ?object $certificateManager = NULL,            // #7
)
```

Los tests no fueron actualizados y seguian pasando `$tenantContext` en la 7ma posicion, con `NULL` en la 3ra:

```php
// INCORRECTO — test desactualizado:
$controller = new FiscalDashboardController(
  $complianceService,  // #1
  $logger,             // #2
  NULL,                // #3 — DEBERIA ser $tenantContext!
  NULL,                // #4
  NULL,                // #5
  NULL,                // #6
  $tenantContext,      // #7 — posicion incorrecta
);
```

Esto causaba:
- Tests 1 y 2: `TypeError: Argument #3 ($tenantContext) must be of type TenantContextService, null given`
- Test 3: `TypeError: Argument #3 ($tenantContext) must be of type TenantContextService, MockObject_stdClass given` (el mock de `$hashService` se pasaba en posicion #3)

**Regla CTRL-ARGS-001:** Al refactorizar el orden de parametros de un constructor, buscar TODOS los `new ClassName(` en tests y codigo para actualizar el orden de argumentos. Usar `grep -rn 'new FiscalDashboardController(' web/` para encontrar todas las instanciaciones.

**Patron de verificacion:**
```bash
# Tras refactorizar un constructor, buscar todas las instanciaciones:
grep -rn 'new NombreClase(' web/modules/ web/themes/
# Incluir tests:
grep -rn 'new NombreClase(' web/modules/*/tests/
```

### 2. Los parametros obligatorios deben ir antes que los opcionales

**Aprendizaje:** PHP exige que los parametros sin valor por defecto vayan antes que los que tienen `= NULL`. El refactor fue correcto al mover `$tenantContext` (obligatorio) antes de los opcionales `$hashService`, `$faceClient`, etc. Pero el test no se actualizo.

**Buena practica:** Al promover un parametro de opcional a obligatorio:
1. Moverlo antes de todos los opcionales en el constructor
2. Actualizar `create()` en ContainerInjectionInterface
3. Actualizar TODOS los tests que instancian la clase
4. Ejecutar los tests localmente antes de hacer push

---

## Archivos Modificados

- `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/FiscalDashboardControllerTest.php` — Corregido orden de argumentos en las 3 instanciaciones de `FiscalDashboardController`

---

## Verificacion

```bash
# Tests locales — 3 tests, 16 assertions, OK:
lando php vendor/bin/phpunit --filter FiscalDashboardControllerTest --no-progress

# CI — Deploy to IONOS: success (2762 tests, 10254 assertions, 0 errors)
```
