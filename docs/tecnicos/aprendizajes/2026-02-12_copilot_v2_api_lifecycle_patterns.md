# Aprendizaje: Patrones API, Entity Hooks y Modal System — Copiloto v2

**Fecha:** 2026-02-12
**Contexto:** Cierre completo de gaps del modulo jaraba_copilot_v2 contra especificaciones 20260121

---

## Resumen

Durante la implementacion de 22 API endpoints, 5 Access Handlers, 5 ListBuilders, 3 paginas frontend con SCSS/JS y la integracion completa con el tema, se extrajeron patrones reutilizables para futuros modulos del ecosistema Jaraba.

---

## 1. Patron API Controller con Lifecycle

### Aprendizaje: Separar estados de entidad en endpoints dedicados

En lugar de un unico PATCH generico para cambiar estado, los cambios de estado criticos (start, cancel, complete) merecen su propio endpoint POST dedicado. Esto facilita:

- Validacion especifica por transicion
- Logging granular por accion
- Permisos diferenciados por operacion

```php
// Bueno: Endpoints dedicados por transicion
POST /api/v1/experiments/{id}/start    → solo cambia PLANNED → IN_PROGRESS
PATCH /api/v1/experiments/{id}/result  → solo cambia IN_PROGRESS → COMPLETED

// Evitar: PATCH generico para todo
PATCH /api/v1/experiments/{id}         → status=IN_PROGRESS|COMPLETED|CANCELLED
```

**Regla LIFECYCLE-001**: Cada transicion de estado critica debe tener su propio endpoint con validacion de pre-condiciones.

---

## 2. Patron ICE Score para Priorizacion

### Aprendizaje: Calcular scores derivados en servicio dedicado

El ICE Score (Importance x Confidence x Evidence) debe calcularse en un servicio independiente del controller para reutilizacion:

```php
// HypothesisPrioritizationService.php
public function calculateIceScore(array $scores): int {
    return ($scores['importance'] ?? 0) * ($scores['confidence'] ?? 0) * ($scores['evidence'] ?? 0);
}
```

**Regla ICE-001**: Los scores derivados (ICE, NPS, health) siempre se calculan en un servicio, nunca en el controller ni en la entidad.

---

## 3. Patron Semaforo BMC

### Aprendizaje: Umbrales claros con cobertura completa de edge cases

Los semaforos del BMC usan umbrales fijos que cubren todos los casos posibles:

```php
public function getSemaphoreColor(float $ratio): string {
    if ($ratio >= 0.66) return 'GREEN';
    if ($ratio >= 0.33) return 'YELLOW';
    return 'RED';
}
```

El caso GRAY (sin hipotesis) se maneja en el nivel superior, no en getSemaphoreColor(), evitando division por cero:

```php
$total = count($hypotheses);
if ($total === 0) {
    return ['color' => 'GRAY', 'percentage' => 0];
}
$validated = count(array_filter($hypotheses, fn($h) => $h['status'] === 'VALIDATED'));
$ratio = $validated / $total;
return ['color' => $this->getSemaphoreColor($ratio), 'percentage' => round($ratio * 100)];
```

**Regla SEMAPHORE-001**: Siempre manejar el caso "sin datos" (GRAY) antes de calcular ratios para evitar division por cero.

---

## 4. Patron Access Control Handler con Cache

### Aprendizaje: Cachear decisiones de acceso por usuario y entidad

Los Access Handlers de Drupal deben incluir cache contexts y tags para evitar recalculos innecesarios:

```php
protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return match ($operation) {
        'view' => AccessResult::allowedIfHasPermission($account, 'administer entrepreneur profiles')
            ->orIf(AccessResult::allowedIf(
                $account->hasPermission('view own entrepreneur profile') &&
                (int) $entity->get('user_id')->value === (int) $account->id()
            )->addCacheableDependency($entity)->cachePerUser()),
        default => AccessResult::neutral(),
    };
}
```

**Regla ACCESS-001**: Todo AccessResult debe incluir `cachePerUser()` cuando depende del usuario y `addCacheableDependency($entity)` cuando depende de la entidad.

---

## 5. Patron Frontend Page Template

### Aprendizaje: Tres capas para paginas frontend limpias

Cada pagina frontend requiere sincronizar tres capas:

1. **Route + Controller**: Render array con `#theme` y `#attached`
2. **Theme preprocess_html**: Body classes para CSS targeting
3. **Page template Twig**: Layout full-width sin sidebars

```php
// 1. Controller
return [
    '#theme' => 'bmc_dashboard',
    '#blocks' => $validation['blocks'],
    '#attached' => ['library' => ['jaraba_copilot_v2/bmc-dashboard']],
];
```

```php
// 2. ecosistema_jaraba_theme.theme
$copilot_v2_routes = [
    'jaraba_copilot_v2.bmc_dashboard' => 'page-bmc-dashboard',
];
if (isset($copilot_v2_routes[$route])) {
    $variables['attributes']['class'][] = 'dashboard-page';
    $variables['attributes']['class'][] = $copilot_v2_routes[$route];
}
```

```twig
{# 3. page--emprendimiento--bmc.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {...} only %}
<main class="main-content main-content--full" role="main">
    {{ page.content }}
</main>
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {...} only %}
```

**Regla FRONTEND-001**: Toda pagina frontend nueva requiere: ruta, controller con render array, body class en preprocess_html, y page template Twig limpio.

---

## 6. Patron Libraries con Design Tokens

### Aprendizaje: Una library por pagina, CSS compartido

Cada pagina frontend tiene su propia library en .libraries.yml referenciando el mismo CSS compilado pero JS separado:

```yaml
bmc-dashboard:
  version: VERSION
  css:
    component:
      css/copilot-v2.css: {}
  js:
    js/bmc-dashboard.js: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupal.dialog.ajax
```

**Regla LIBRARY-001**: Siempre incluir `core/once` y `core/drupal.dialog.ajax` en libraries que usen behaviors o modales AJAX.

---

## 7. Patron Impact Points con Niveles

### Aprendizaje: Gamificacion con thresholds fijos

Los puntos de impacto usan thresholds fijos para calcular niveles sin base de datos adicional:

```php
protected function calculateLevel(int $points): int {
    if ($points >= 2000) return 5; // Maestro
    if ($points >= 1000) return 4; // Experto
    if ($points >= 500) return 3;  // Avanzado
    if ($points >= 100) return 2;  // Aprendiz
    return 1;                       // Novato
}
```

**Regla GAMIFICATION-001**: Los thresholds de nivel deben definirse en un solo lugar (controller o servicio) y no duplicarse entre frontend y backend.

---

## Lecciones Aprendidas

### 1. Los servicios "stub" no siempre lo son
Al investigar los 9 servicios catalogados como stubs, todos resultaron tener implementacion sustancial (250-400+ lineas). Leccion: siempre leer el codigo antes de asumir que esta incompleto.

### 2. El patron route → class → body-class es fragil
Si se omite cualquiera de las tres capas (ruta, preprocess_html, page template), la pagina no renderiza correctamente. Conviene crear las tres a la vez.

### 3. Los modales AJAX requieren library especifica
Sin `core/drupal.dialog.ajax` como dependencia, los links con `class="use-ajax"` y `data-dialog-type="modal"` no funcionan. Esta dependencia es facil de olvidar.

### 4. EntityQuery siempre necesita accessCheck
Desde Drupal 10, `accessCheck(TRUE)` o `accessCheck(FALSE)` es obligatorio en todas las entity queries. Sin el, Drupal lanza un error.
