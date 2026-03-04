# Aprendizaje #158: 7 Items SaaS Post-Evaluacion — VerticalBrand + Pre-PMF + PilotManager + PIIL + OpenAPI + ConfigEntity Safeguards

| Metadata | Valor |
|----------|-------|
| Fecha | 2026-03-04 |
| Sesion | Items 1-7 Post-Evaluacion Documentos 20260303 |
| Ficheros nuevos | ~35 |
| Ficheros modificados | ~25 |
| Tests | 40 tests, 294 assertions, 0 errores |
| PHPStan L6 errores corregidos | 95+ |
| Regla reforzada | UPDATE-HOOK-REQUIRED-001 |
| Regla de oro | #98 |

---

## Patron Principal

**Estrategia "Submarinas con Periscopio"**: Cada vertical presentado como producto independiente
con sub-marca, revelacion progresiva en 4 niveles (landing → trial → expansion → enterprise).
**Principio rector**: Extender modulos existentes sobre crear nuevos. De 5 GAPs propuestos como
modulos nuevos, solo 1 (jaraba_pilot_manager) fue genuinamente necesario. Los otros 4 se
resolvieron extendiendo modulos existentes, reduciendo el esfuerzo estimado de 440-600h a 250-350h (~45% menos).

---

## Aprendizajes Clave

### 1. ConfigEntities tambien necesitan hook_update_N()

**Situacion:** Tras implementar VerticalBrandConfig y ActivationCriteriaConfig (ambas ConfigEntities),
el status report de Drupal mostraba "Vertical Brand needs to be installed" y "Activation Criteria
needs to be installed". El sitio funcionaba pero con warnings.

**Aprendizaje:** Drupal trackea definiciones de entity types para AMBOS backends de storage
(ContentEntity con tablas SQL y ConfigEntity con archivos YAML). Sin un hook_update_N() que
llame a `installEntityType()`, el EntityDefinitionUpdateManager no registra el entity type.

**Regla:** UPDATE-HOOK-REQUIRED-001 reforzado: "Content O Config" — ConfigEntities NO estan exentas.

**Salvaguarda:** CHECK 7 en `scripts/validation/validate-entity-integrity.php` escanea las 446
entities del proyecto y detecta cualquier entity_type_id no referenciado en el .install del modulo.

### 2. PHPStan Level 6 — Patrones de correccion sistematica

**Situacion:** 95+ errores PHPStan al aplicar nivel 6 a los ~35 ficheros nuevos.

**Aprendizaje:** Los patrones mas frecuentes son:

| Patron erroneo | Correccion | Frecuencia |
|----------------|-----------|------------|
| `empty($var)` | `$var === []` o `$var === ''` o `$var === NULL` | ~30 |
| `assert($x instanceof Y)` | `/** @var Y $x */` (assert prohibido en config) | ~15 |
| `$val ?: 'default'` | `$val ?? 'default'` o `$val !== '' ? $val : 'default'` | ~12 |
| `EntityInterface::get()` | `ContentEntityInterface` type narrowing | ~10 |
| Implicit `$header = ...` | Inicializar `$header = []` antes | ~8 |
| Missing `save()` return | Anadir `: int` y `return $status` | ~4 |

**Regla:** PHPStan config del proyecto prohibe `assert()` y `empty()` — siempre usar comparaciones estrictas.

### 3. Kernel tests — Dependencias criticas

**Situacion:** 16 errores en kernel tests por modulos faltantes.

**Aprendizaje:**
- `flexible_permissions` DEBE listarse ANTES de `group` en `$modules` (group depende de el)
- `ecosistema_jaraba_core` necesario para entity type `tenant` (entity_reference target)
- Servicios cross-module necesitan `register(ContainerBuilder)` con `setSynthetic(TRUE)`
- `$strictConfigSchema = FALSE` necesario cuando el modulo tiene configs con dot-keys problematicos

**Regla:** KERNEL-TEST-DEPS-001 + KERNEL-SYNTH-001 ya existian pero la combinacion con
`flexible_permissions` no estaba documentada.

### 4. FeatureGateRouter como ServiceLocator

**Situacion:** Item 3 requeria un test Twig `feature_allowed` que pudiera despachar a 10
FeatureGateService verticales distintos.

**Aprendizaje:** El patron ServiceLocator con mapa constante `VERTICAL_SERVICES` es mas
explicito y debuggeable que tagged services para este caso (10 servicios fijos, no dinamicos).
La deteccion automatica del vertical se delega a `AvatarDetectionService::detect()` que
retorna un `AvatarDetectionResult` con propiedad publica `vertical`.

**Regla:** Para colecciones pequenas y fijas de servicios (<=15), ServiceLocator con mapa
constante es preferible a tagged services.

### 5. ECA Events — Patron identico a existentes

**Situacion:** Item 4 requeria 5 ECA Events para el ciclo de vida de pilotos.

**Aprendizaje:** El patron exacto esta en `jaraba_messaging/src/Plugin/ECA/Event/`:
- Clase Symfony base en `src/Event/` que extiende `Event`
- Plugin ECA adapter en `src/Plugin/ECA/Event/` que extiende `EventBase`
- `@EcaEvent` annotation con `event_name` OBLIGATORIO (ECA-EVENT-001)
- `getEntity()` retorna la entidad que disparo el evento

### 6. Modulo nuevo vs extension — Criterio de decision

**Situacion:** De 5 GAPs propuestos como modulos nuevos, solo 1 fue genuinamente necesario.

**Aprendizaje:** Criterio para modulo nuevo:
1. La funcionalidad NO existe en ningun modulo existente
2. Las entities NO tienen FK naturales a entities de modulos existentes
3. El dominio es independiente y no solapa con modulos activos

Criterio para extension:
1. El modulo existente ya tiene entities del mismo dominio
2. Los servicios nuevos consumen entities existentes del modulo
3. Las rutas y permisos son coherentes con el namespace existente

**Resultado aplicado:**
- GAP-1 VerticalBrand → Extension ecosistema_jaraba_core (ya tiene config entities verticales)
- GAP-2 ProductAnalytics → Extension jaraba_analytics (ya tiene analytics entities)
- GAP-3 FeatureGates → Router + Twig test (ya existen 10 services + 217 configs)
- GAP-4 PilotManager → NUEVO (funcionalidad genuinamente faltante)
- GAP-5 PIIL Bridge → Extension jaraba_institutional (ya tiene PIIL/STO entities)

---

## Cross-refs

- Directrices: v108.0.0
- Arquitectura: v97.0.0
- Indice General: v137.0.0
- Flujo de Trabajo: v61.0.0
- Plan original: `docs/implementacion/2026-03-03_Plan_Implementacion_Gaps_Clase_Mundial_100_v1.md`
- Regla de oro: #98
