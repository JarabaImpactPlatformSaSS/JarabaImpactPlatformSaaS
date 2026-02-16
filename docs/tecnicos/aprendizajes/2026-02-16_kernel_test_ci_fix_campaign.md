# Aprendizajes: Kernel Test CI Fix Campaign — 61→0 Errors

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

Campana de 7 commits iterativos para corregir 61 errores de Kernel tests en CI (workflow "Jaraba SaaS - Deploy to IONOS"). Los tests de los modulos fiscales (`jaraba_verifactu`, `jaraba_einvoice_b2b`) y compliance (`ecosistema_jaraba_core`) fallaban porque KernelTestBase NO resuelve dependencias de info.yml — todas las dependencias de field types, entity references y servicios deben declararse manualmente. El proceso revelo ademas un bug de produccion en `jaraba_verifactu.module`.

---

## Aprendizajes Clave

### 1. KernelTestBase no resuelve dependencias de info.yml — todas deben ser explicitas

**Situacion:** Los 18 tests de los modulos fiscales (9 verifactu + 9 einvoice_b2b) fallaban con `PluginNotFoundException: "field_item:list_string"` y `FieldException: tenant_id references target entity type 'group'`. Los modulos tenian dependencias correctas en sus info.yml, pero KernelTestBase las ignora.

**Aprendizaje:** `KernelTestBase::$modules` es la UNICA fuente de verdad para modulos cargados en Kernel tests. Cada field type plugin necesita su modulo proveedor: `options` para `list_string`, `datetime` para campos datetime, `file` para entity_reference a archivos. Los entity_reference a tipos de entidades contrib (`group`) requieren cargar el modulo contrib y sus dependencias (`flexible_permissions`).

**Regla:** KERNEL-DEP-001: Kernel tests `$modules` DEBEN incluir TODOS los modulos que proveen field types usados por las entidades bajo test. Patron minimo para entidades con tenant_id→group: `['system', 'user', 'field', 'options', 'datetime', 'flexible_permissions', 'group', '<module_under_test>']`.

### 2. Synthetic services para dependencias de modulos no cargados

**Situacion:** `jaraba_verifactu` depende de `jaraba_billing` que depende de `jaraba_foc` que depende de `commerce`. Cargar toda la cadena es impracticable. Pero `jaraba_billing.services.yml` tiene hard dependency `@jaraba_foc.stripe_connect` que causa `ServiceNotFoundException` al compilar el container.

**Aprendizaje:** El patron synthetic services permite registrar placeholders que satisfacen la compilacion del container DI sin cargar modulos completos. Se implementa en dos fases: (1) `register(ContainerBuilder $container)` antes del boot — `$container->register('service_id')->setSynthetic(TRUE)` y (2) `setUp()` despues del boot — `$this->container->set('service_id', $this->createMock(Interface::class))`. Los mocks tipados satisfacen type-hints en los constructores de los servicios reales.

**Regla:** KERNEL-SYNTH-001: Para dependencias hard (`@service_name`) de modulos no cargables, usar el patron synthetic en `register()` + typed mock en `setUp()`. Para dependencias opcionales (`@?service_name`), no se necesita accion.

### 3. Entity references a entidades inexistentes se descartan silenciosamente

**Situacion:** `VeriFactuEventLogEntityTest::testCreateEventLog` asignaba `'record_id' => 42` a un campo entity_reference, pero entity ID 42 no existia. Tras save() + load(), el campo retornaba string vacia en lugar de `'42'`.

**Aprendizaje:** Drupal valida entity_reference fields durante preSave(). Si la entidad target no existe, el valor se descarta silenciosamente — no hay excepcion ni warning. Esto es por diseno para evitar foreign key violations con entidades eliminadas, pero causa fallos confusos en tests.

**Regla:** KERNEL-EREF-001: Tests que verifican entity_reference fields DEBEN crear primero la entidad target real y usar `$entity->id()` como valor, NUNCA un ID arbitrario.

### 4. Timestamps `time()` causan flaky tests por race conditions

**Situacion:** `VeriFactuInvoiceRecordEntityTest::testCreatedTimestampAutoPopulated` capturaba `$before = time()`, creaba una entidad, y assertaba `$created >= $before`. Intermitentemente fallaba porque `time()` se ejecutaba en un tick de segundo anterior al momento en que Drupal asignaba el timestamp.

**Aprendizaje:** La granularidad de `time()` es 1 segundo. Si la llamada a `time()` ocurre justo antes de un cambio de segundo, y la entidad se crea justo despues, el created timestamp puede ser 1 segundo mayor o menor. En CI con recursos compartidos, esto es mas frecuente.

**Regla:** KERNEL-TIME-001: Assertions de timestamps DEBEN usar tolerancia `time() - 1` para el lower bound y `time() + 1` para el upper bound. NUNCA `time()` exacto.

### 5. SQLite retorna decimales sin trailing zeros

**Situacion:** Assertions como `assertSame('1210.00', $entity->get('importe_total')->value)` fallaban en Kernel tests (SQLite) porque el valor retornado era `'1210'` sin decimales.

**Aprendizaje:** SQLite tiene tipado dinamico y no preserva trailing zeros en valores numericos. A diferencia de MySQL/MariaDB que almacena DECIMAL(12,2) como `'1210.00'`, SQLite retorna `'1210'` o `'1210.5'`. Las assertions de campos numericos en Kernel tests deben usar comparacion numerica con tolerancia.

**Regla:** KERNEL-DECIMAL-001: Para campos decimal/float en Kernel tests, usar `assertEqualsWithDelta($expected, (float) $value, 0.001)` en lugar de `assertSame('value', $value)`.

### 6. Drupal UID 1 bypasses ALL access control checks

**Situacion:** `VeriFactuPermissionsTest::testAccessDeniedWithoutPermission` creaba un usuario sin permisos y verificaba que el acceso era denegado. Pero el primer usuario creado obtenia uid=1 (super admin) y siempre tenia acceso.

**Aprendizaje:** Drupal otorga privilegios de super admin al usuario con uid=1, bypassing completamente el sistema de permisos. En tests de permisos, el primer `User::create()` obtiene uid=1. La solucion es crear un usuario placeholder uid=1 en `setUp()` antes de crear usuarios de test.

**Regla:** KERNEL-UID1-001: Tests de AccessControlHandler DEBEN crear un usuario placeholder uid=1 en `setUp()` con `User::create(['name' => 'admin', 'status' => 1])->save()`. Los usuarios de test obtienen uid>=2 con permisos reales.

### 7. Tests de CI revelan bugs de produccion que los Unit tests no detectan

**Situacion:** `jaraba_verifactu.module` referenciaba `modo_verifactu` en dos funciones helper, pero el campo real en `VeriFactuTenantConfig` es `is_active`. Los Unit tests (que no instancian entidades reales) no detectaron esto.

**Aprendizaje:** Los Kernel tests que instalan entity schemas y crean entidades reales detectan bugs que los Unit tests con mocks no pueden encontrar: field names incorrectos, entity_reference targets invalidos, y field type mismatches. La campana de correccion CI revelo y corrigio este bug de produccion antes de que causara problemas en runtime.

**Regla:** KERNEL-PROD-001: Los Kernel tests son la linea de defensa contra bugs de campo/schema. Cada Content Entity con AccessControlHandler DEBE tener al menos 1 Kernel test que cree, guarde y recargue la entidad verificando campos criticos.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Commits de correccion | 7 (iterativos) |
| Errores iniciales | 61 |
| Errores finales | 0 |
| Tests pasando | 2,039 (1,895 Unit + 144 Kernel) |
| Archivos test modificados | 20 (18 fiscales + 1 ecosistema_jaraba_core + 1 production .module) |
| Bug de produccion corregido | 1 (modo_verifactu → is_active en jaraba_verifactu.module) |
| Reglas nuevas | 7 (KERNEL-DEP-001, KERNEL-SYNTH-001, KERNEL-EREF-001, KERNEL-TIME-001, KERNEL-DECIMAL-001, KERNEL-UID1-001, KERNEL-PROD-001) |
| Archivos stub eliminados | 11 (JS/SCSS/Twig placeholders de jaraba_legal + jaraba_dr) |
