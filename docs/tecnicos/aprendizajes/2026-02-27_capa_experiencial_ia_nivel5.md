# Aprendizaje #147 — Capa Experiencial IA Nivel 5: Self-Healing Real + Autonomous Tasks + SCSS Dashboards

**Fecha:** 2026-02-27
**Contexto:** Auditoria revelo que 7/9 GAPs de IA Nivel 5 tenian backend scaffolding sin experiencia de usuario real.
**Directrices:** v97.0.0 | **Flujo:** v50.0.0 | **Indice:** v126.0.0 | **Arquitectura IA L5:** v3.0.0

---

## Problema

Tras implementar 9 GAPs de IA Nivel 5 (Constitutional Safety, Self-Improving Prompts, EU AI Act, Voice, Browser, Autonomous, Self-Healing, Federated, Causal), una auditoria critica revelo:

- **78% scaffold sin experiencia**: metodos `executeAutoDowngrade()`, `taskContentCurator()` etc. solo logeaban o retornaban arrays vacios
- **0 SCSS compilados** para los 3 dashboards (compliance, autonomous, causal)
- **0 libraries declaradas** en `.libraries.yml` para los nuevos dashboards
- **0 body classes** en `hook_preprocess_html()` para las 3 rutas
- `bundle-ai-compliance` referenciada en controller pero **inexistente** en `.libraries.yml`
- Controllers sin `#attached['library']` (2 de 3)

## Solucion

### 1. Self-Healing via State API (SELF-HEALING-STATE-001)

**Patron clave:** Remediaciones automaticas NUNCA modifican config persistente. Usan `\Drupal::state()` con flags efimeros:

```php
// AutoDiagnosticService::executeAutoDowngrade()
$state->set('jaraba_ai_agents.tier_override.' . $tenantId, [
    'tier' => 'fast',
    'reason' => 'auto_downgrade_high_latency',
    'expires' => time() + 3600, // Auto-expiry 1h
]);
```

**Consumer pattern:** Servicios verifican flag y lo limpian si expirado:

```php
// AutoDiagnosticService::getTierOverride()
$data = $state->get(self::STATE_TIER_OVERRIDE . $tenantId);
if (isset($data['expires']) && time() > $data['expires']) {
    $state->delete(self::STATE_TIER_OVERRIDE . $tenantId);
    return NULL;
}
return $data['tier'] ?? NULL;
```

**Por que State API y no Config:** `\Drupal::state()` es almacenamiento key-value en BD, no requiere `drush config:import`, no genera conflictos entre entornos. Config es para settings permanentes que se despliegan.

### 2. Autonomous Tasks con Entity Queries (AUTONOMOUS-TASK-ENTITY-001)

**Patron clave:** Task methods consultan entidades reales, NUNCA retornan arrays vacios stub:

```php
// taskContentCurator — busca articulos reales con bajo rendimiento
$lowViewIds = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('status', 1)
    ->condition('created', $thirtyDaysAgo, '>=')
    ->condition('views_count', 10, '<')
    ->sort('created', 'DESC')
    ->range(0, 5)
    ->execute();
```

**Entity types opcionales cross-module:** Verificar con `hasDefinition()`:

```php
if (!$this->entityTypeManager->hasDefinition('tenant_knowledge_config')) {
    return ['success' => TRUE, 'data' => ['note' => 'Module not installed.']];
}
```

### 3. SCSS Entry Point sin Partial (Dart Sass Ambiguity)

**Problema descubierto:** Cuando `ai-compliance.scss` (entry point) y `_ai-compliance.scss` (partial) estan en el mismo directorio, `@use 'ai-compliance'` genera error de ambiguedad en Dart Sass.

**Solucion:** Consolidar todo el CSS directamente en el entry point sin partial separado. El patron `@use` solo funciona sin ambiguedad cuando el partial esta en un directorio diferente (como `@use '../_variables'`).

### 4. Route-Specific Dashboard CSS Pattern

**Flujo completo de un dashboard SCSS:**

1. SCSS en `scss/routes/{name}.scss` con `@use '../variables' as *`
2. Compilar: `npx sass scss/routes/{name}.scss css/routes/{name}.css --style=compressed --no-source-map`
3. Library en `.libraries.yml`: `route-{name}` con dependency `global-styling`
4. `hook_page_attachments_alter()`: Ruta exacta ANTES del catch-all prefijo modulo
5. `hook_preprocess_html()`: Body class exacta ANTES del catch-all
6. Controller: `#attached['library']` como redundancia

**Orden critico:** Las rutas exactas DEBEN ir ANTES del catch-all `jaraba_ai_agents.` porque el `foreach` hace `break` en primer match.

## Regla de Oro #79

> Self-healing efimero via State API, NUNCA config persistente. Autonomous tasks con entity queries reales, NUNCA stubs. Route CSS con entry point consolidado para evitar ambiguedad Dart Sass.

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `AutoDiagnosticService.php` | 5 metodos executeAuto* con logica real + 3 metodos publicos get* |
| `AutonomousAgentService.php` | 3 task methods con entity queries reales |
| `AutonomousAgentDashboardController.php` | Añadido `#attached['library']` |
| `CausalAnalyticsDashboardController.php` | Añadido `#attached['library']` |
| `ecosistema_jaraba_theme.libraries.yml` | 4 libraries nuevas |
| `ecosistema_jaraba_theme.theme` | 3 body classes + 3 rutas en page_attachments_alter |
| `scss/routes/ai-compliance.scss` | Nuevo (4.8KB compilado) |
| `scss/routes/autonomous-agents.scss` | Nuevo (4.2KB compilado) |
| `scss/routes/causal-analytics.scss` | Nuevo (4.5KB compilado) |
| `templates/partials/_ai-health-indicator.html.twig` | Nuevo partial |
| `templates/partials/_causal-analysis-widget.html.twig` | Nuevo partial |

## Metricas

- 47 tests passing, 181 assertions
- 0 errores PHP lint
- 3 CSS compilados (13.5KB total)
- 5 self-healing remediations implementadas
- 3 autonomous tasks con entity queries
- 2 nuevas directrices: SELF-HEALING-STATE-001, AUTONOMOUS-TASK-ENTITY-001
