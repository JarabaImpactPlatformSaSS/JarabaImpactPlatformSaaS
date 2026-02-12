# Lecciones Aprendidas — 2026-02-11

## 1.558 Lesson: hero--split Class Unconditionally Breaking Admin Pages

- **Problem**: El contenido de `/admin/reports/dblog` y otras páginas admin no ocupaba el ancho completo. El layout se comprimía a un grid 50/50.
- **Root Cause**: `jaraba_theming_preprocess_html()` añadía la clase `hero--split` al `<body>` en TODAS las rutas, incluyendo páginas administrativas. La regla CSS `.hero--split { display: grid; grid-template-columns: 1fr 1fr; }` en `_hero.scss` creaba un grid de dos columnas.
- **Fix Multi-Capa**:
  1. **Capa PHP (Primaria)**: Guard en `jaraba_theming.module` que comprueba `isAdminRoute()` + `str_starts_with($route, 'system.')` + `str_starts_with($route, 'dblog.')` y retorna antes de inyectar clases de variante.
  2. **Capa Service**: `CriticalCssService::getCriticalCssFile()` retorna NULL para rutas admin, evitando cargar `homepage.css`.
  3. **Capa CSS (Respaldo)**: `_site-builder.scss` mantiene override defensivo para `.hero--split`.
- **Key Insight**: Las clases de tema visual (hero variants, header variants) **nunca** deben aplicarse a rutas administrativas. El guard `isAdminRoute()` en preprocess hooks debería ser la primera línea de defensa.

## 1.559 Lesson: Namespace Type-Hints After Service Extraction

- **Problem**: Tras la extracción de 8 servicios billing de `ecosistema_jaraba_core` a `jaraba_billing`, controllers y services consumidores seguían importando clases del namespace antiguo, causando `TypeError` por strict type checking.
- **Archivos afectados**: `TenantManager.php`, `UsageDashboardController.php`, `UsageApiController.php`.
- **Root Cause**: Los `use` statements apuntaban a `Drupal\ecosistema_jaraba_core\Service\PlanValidator` pero la clase real ahora reside en `Drupal\jaraba_billing\Service\PlanValidator`. Aunque existían aliases backward-compatible en `services.yml`, PHP strict type checking valida el **fully qualified class name**, no el service ID.
- **Fix**: Actualizar los `use` statements en cada consumidor para apuntar al namespace correcto.
- **Pattern**: **Post-Extraction Namespace Audit**. Tras extraer servicios a un nuevo módulo:
  1. Buscar `use Drupal\modulo_origen\Service\ClaseExtraida` en todo el codebase.
  2. Actualizar cada `use` statement al nuevo namespace.
  3. Verificar que los aliases en `services.yml` cubren la inyección de dependencias (DI container).
  4. Los aliases DI resuelven la **inyección por service ID** pero NO resuelven **type hints** en constructores PHP.
- **Key Insight**: El refactoring no está completo hasta que todos los consumidores del namespace original han sido actualizados. `grep` por el namespace antiguo es obligatorio.

## 1.560 Lesson: Missing Method After Extraction (getAvailableFeatures)

- **Problem**: `EngagementScoringService` llamaba a `PlanValidator::getAvailableFeatures()`, un método que no existía tras la extracción.
- **Root Cause**: Durante la extracción de servicios billing, el método `getAvailableFeatures()` se asumió existente but no fue incluido en la clase migrada.
- **Fix**: Añadir el método `getAvailableFeatures(string $tenant_id): array` a `PlanValidator.php`, con lógica de carga de tenant, validación de estado activo, y recuperación de features del plan.
- **Pattern**: **Method Completeness Audit**. Tras extraer una clase:
  1. Buscar todas las invocaciones del método en el codebase (`grep -r "->methodName("`)
  2. Verificar que cada método existe en la clase destino
  3. Ejecutar tests para detectar `Call to undefined method` errors
- **Key Insight**: Las interfaces implícitas (métodos llamados pero no definidos en una interfaz formal) son el eslabón más débil en extracciones de módulos. Si no hay una `Interface` explícita, usar `grep` para auditar todos los callsites.
