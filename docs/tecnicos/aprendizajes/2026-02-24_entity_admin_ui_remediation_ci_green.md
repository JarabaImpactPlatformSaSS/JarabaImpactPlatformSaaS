# Aprendizaje #116 — Entity Admin UI Remediation: CI Green + Field UI Settings Tabs

**Fecha:** 2026-02-24
**Contexto:** Remediacion completa de errores de CI (Unit + Kernel) y adicion de tabs de configuracion Field UI a 175 entidades en 46 modulos
**Impacto:** CI 100% verde (Unit + Kernel) + Administracion de campos habilitada para todas las Content Entities de la plataforma

---

## 1. Problema

Tres categorias de problemas bloqueaban el pipeline CI y la administracion de campos en produccion:

| # | Categoria | Errores | Descripcion |
|---|-----------|---------|-------------|
| P1 | Unit tests | 12 | CopilotApiControllerTest (4) + MatchingServiceTest (8) con mocks incorrectos |
| P2 | Kernel tests | 18 | ServiceNotFoundException por referencias cross-modulo + dependencias faltantes |
| P3 | Field UI | 175 entidades | Tabs "Administrar campos" / "Administrar visualizacion" invisibles en la UI de admin |

---

## 2. Diagnostico y Solucion

### P1 — 12 Errores de Unit Tests (CopilotApiControllerTest + MatchingServiceTest)

**CopilotApiControllerTest (4 errores):**

**Causa raiz:** El mock de `AccountProxyInterface` se creaba pero nunca se inyectaba en el controlador. `ControllerBase` almacena `currentUser` en una propiedad `protected` que no es accesible por setter.

**Solucion:** Inyeccion via `ReflectionProperty::setValue()`:
```php
$reflection = new \ReflectionProperty(ControllerBase::class, 'currentUser');
$reflection->setAccessible(true);
$reflection->setValue($controller, $mockCurrentUser);
```

**MatchingServiceTest (8 errores):**

**Causa raiz:** Los tests usaban `stdClass` en lugar de mocks tipados con la interfaz correcta. Los metodos `hasField()`, `get()`, `isEmpty()` no existian en `stdClass`, causando errores fatales.

**Solucion:** Reemplazo de `stdClass` por mocks de `ContentEntityInterface`:
```php
$mockEntity = $this->createMock(ContentEntityInterface::class);
$mockEntity->method('hasField')->willReturn(true);
$mockEntity->method('get')->willReturnCallback(fn($field) => new class($field) {
    public $value;
    public $target_id;
    public function isEmpty() { return false; }
});
```

Uso de `EntityStorageInterface` mocks separados por tipo de entidad con `willReturnCallback` y expresion `match`:
```php
$entityTypeManager->method('getStorage')
    ->willReturnCallback(fn($type) => match($type) {
        'candidato' => $candidatoStorage,
        'oferta_empleo' => $ofertaStorage,
        default => throw new \Exception("Unexpected: $type"),
    });
```

---

### P2 — 18 Errores de Kernel Tests (ServiceNotFoundException)

#### 2a. 15 errores: Referencias `@service` hard a servicios cross-modulo

**Causa raiz:** Los ficheros `services.yml` contenian referencias directas (`@`) a servicios de otros modulos. Cuando los Kernel tests habilitaban solo el modulo bajo test, los servicios de los modulos dependientes no existian y el container DI fallaba en compilacion.

**Solucion:** Uso del prefijo `@?` (inyeccion opcional de Symfony) — pasa `NULL` si el servicio no existe:

```yaml
# ANTES (rompe en Kernel tests)
arguments: ['@jaraba_tenant.context']

# DESPUES (funciona en aislamiento)
arguments: ['@?jaraba_tenant.context']
```

**Ficheros modificados:**
- `jaraba_agroconecta_core.services.yml` — 7 referencias convertidas a opcionales
- `jaraba_job_board.services.yml` — 1 referencia convertida a opcional

**Constructores actualizados (6 clases PHP):**
```php
// ANTES
public function __construct(TenantContextInterface $tenantContext) {

// DESPUES
public function __construct(?TenantContextInterface $tenantContext = NULL) {
```

**Null guards anadidos donde necesario:**
```php
if ($this->tenantContext) {
    $tenantId = $this->tenantContext->getCurrentTenantId();
}
// O con nullsafe operator:
$tenantId = $this->tenantContext?->getCurrentTenantId();
```

#### 2b. 2 errores: OrderAgroTest con modulos faltantes

**Causa raiz:** La entidad `order_agro` usa campos `datetime` y referencia `taxonomy_term`, pero el test solo declaraba `system`, `user`, `taxonomy` en `$modules`.

**Solucion:** Anadir modulos faltantes y esquemas previos:
```php
protected static $modules = [
    'system', 'user', 'taxonomy',
    'field',      // NUEVO — field types base
    'text',       // NUEVO — text_long fields
    'datetime',   // NUEVO — datetime fields
    'jaraba_agroconecta_core',
];

protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term'); // ANTES de order_agro
    $this->installEntitySchema('order_agro');
}
```

#### 2c. 1 error: Metodo estatico inexistente `OrderAgro::getStateLabels()`

**Causa raiz:** El test llamaba `OrderAgro::getStateLabels()` (metodo estatico) pero solo existia `getStateLabel()` (metodo de instancia).

**Solucion:** Agregar el metodo estatico y refactorizar el metodo de instancia para reutilizarlo:
```php
public static function getStateLabels(): array {
    return [
        'draft' => t('Borrador'),
        'confirmed' => t('Confirmado'),
        'shipped' => t('Enviado'),
        'delivered' => t('Entregado'),
        'cancelled' => t('Cancelado'),
    ];
}

public function getStateLabel(): string {
    $labels = static::getStateLabels();
    return $labels[$this->get('state')->value] ?? $this->get('state')->value;
}
```

---

### P3 — 175 Entidades sin Tabs de Configuracion Field UI

**Causa raiz:** Todas las 175 entidades tenian `field_ui_base_route` configurado en su anotacion `@ContentEntityType`, pero les faltaba la entrada de local task por defecto obligatoria en `*.links.task.yml`.

Sin el tab por defecto (donde `route_name == base_route`), Drupal Field UI no puede anclar las pestanas derivadas "Administrar campos" y "Administrar visualizacion de formulario".

**Flujo roto:**
1. `@ContentEntityType(... field_ui_base_route = "entity.ENTITY_ID.settings" ...)`
2. Field UI busca un local task con `base_route: entity.ENTITY_ID.settings`
3. Si no existe un tab por defecto con `route_name: entity.ENTITY_ID.settings`, Field UI no registra las pestanas derivadas
4. Resultado: las pestanas "Administrar campos" / "Administrar visualizacion de formulario" / "Administrar visualizacion" no aparecen

**Solucion:** Script Python genero entradas de local task para las 175 entidades en los 46 modulos afectados:

```yaml
# Patron aplicado a cada entidad en su links.task.yml
entity.ENTITY_ID.settings_tab:
  title: 'Configuracion'
  route_name: entity.ENTITY_ID.settings
  base_route: entity.ENTITY_ID.settings
```

**Verificacion local:**
```bash
lando drush cr
# Consulta Drush confirmo que los 4 tabs aparecen para cada entidad:
# - Configuracion (default tab)
# - Administrar campos
# - Administrar visualizacion de formulario
# - Administrar visualizacion
```

---

## 3. Commits

| Commit | Mensaje | Errores resueltos |
|--------|---------|-------------------|
| `d946f982` | fix(tests): Make cross-module service refs optional | 15 Kernel errors |
| `e86edd09` | fix(tests): Add missing module deps to OrderAgroTest | 2 Kernel errors |
| `4b39cc91` | fix(tests): Add getStateLabels() static method to OrderAgro | 1 Kernel error |
| `40e4b22c` | fix(tests): Resolve 12 PHPUnit errors in CopilotApiControllerTest + MatchingServiceTest | 12 Unit errors |
| `8dd4db09` | fix(field-ui): Add default settings tabs to 175 entities across 46 modules | 175 entidades sin tabs |

---

## 4. Modulos Afectados

46+ modulos, incluyendo:

- `jaraba_agroconecta_core`
- `jaraba_job_board`
- `jaraba_andalucia_ei`
- `ecosistema_jaraba_core`
- Y 42 modulos adicionales con entidades que requerian tabs de Field UI

**Ficheros modificados:** ~55+

---

## 5. Reglas Derivadas

### KERNEL-TEST-DEPS-001 — Dependencias Explicitas de Modulos en Kernel Tests

Drupal `KernelTestBase::$modules` NO resuelve automaticamente dependencias de modulos — TODOS los modulos requeridos deben listarse explicitamente.

| Si la entidad usa... | Agregar modulo |
|----------------------|----------------|
| Campos `datetime` | `datetime` |
| Referencia a `taxonomy_term` | `taxonomy`, `text`, `field` + `installEntitySchema('taxonomy_term')` antes del schema de la entidad |
| Campos `list_string` | `options` |
| Campos `text_long` | `text` |

### OPTIONAL-SERVICE-DI-001 — Referencias Opcionales de Servicios Cross-Modulo

Servicios que dependen de otros modulos (que pueden no estar instalados) DEBEN usar el prefijo `@?` en services.yml.

**Requisitos obligatorios:**
1. El parametro del constructor DEBE ser nullable: `?ServiceInterface $param = NULL`
2. El codigo que usa el servicio DEBE tener null-guard: `if ($this->service) { ... }` o `$this->service?->method()`
3. Esto es critico para Kernel tests que habilitan solo el modulo bajo test

```yaml
# services.yml
arguments: ['@?otro_modulo.servicio']
```
```php
// Constructor
public function __construct(?OtroServicioInterface $servicio = NULL) {
    $this->servicio = $servicio;
}

// Uso
if ($this->servicio) {
    $this->servicio->doSomething();
}
```

### FIELD-UI-SETTINGS-TAB-001 — Tab por Defecto Obligatorio para Field UI

Toda entidad con `field_ui_base_route` DEBE tener una entrada de local task por defecto correspondiente en `links.task.yml`.

**Sin esta entrada, Field UI NO puede renderizar las pestanas "Administrar campos", "Administrar visualizacion de formulario" y "Administrar visualizacion".**

La entrada del tab DEBE tener `route_name` y `base_route` apuntando a la misma ruta de settings:

```yaml
entity.ENTITY_ID.settings_tab:
  title: 'Configuracion'
  route_name: entity.ENTITY_ID.settings
  base_route: entity.ENTITY_ID.settings
```

---

## 6. Resultado CI

| Suite | Tests | Assertions | Resultado |
|-------|-------|------------|-----------|
| Unit | 2,859 | 10,594 | OK |
| Kernel | 211 | 1,190 | OK |
| Deploy | — | — | Success |

---

## 7. Leccion Clave

**Los Kernel tests de Drupal operan en aislamiento estricto** — no heredan dependencias de `info.yml`, no cargan servicios de modulos no declarados en `$modules`, y no toleran referencias hard a servicios inexistentes. Cada modulo que se prueba en Kernel tests debe ser completamente autosuficiente en su declaracion de dependencias de test, o usar inyeccion opcional (`@?`) para servicios que pueden no existir.

**Field UI de Drupal tiene un contrato silencioso:** tener `field_ui_base_route` en la anotacion de la entidad es condicion necesaria pero NO suficiente. Sin el local task por defecto en `links.task.yml`, las pestanas derivadas simplemente no se registran, sin error ni warning en logs. Esta fue la causa de que 175 entidades en 46 modulos no tuvieran administracion de campos accesible desde la UI.
