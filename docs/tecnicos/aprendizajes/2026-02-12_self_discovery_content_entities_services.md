# Self-Discovery Content Entities + Services + Copilot v2 Integration

**Fecha:** 2026-02-12
**Contexto:** Cierre de gaps Specs 20260122-20260125 (Docs 159-161) para el modulo `jaraba_self_discovery`
**Modulo:** `web/modules/custom/jaraba_self_discovery/`

---

## Problema

La auditoria de los documentos tecnicos 159-165 (specs 20260122-20260125) revelo 14 gaps en 3 areas:
1. **Infraestructura Lando** (Doc 159): Faltaban `redis.conf`, `.env.example`, `setup-dev.sh`, y `settings.local.php` estaba incompleto
2. **Self-Discovery Module** (Docs 160-161): RIASEC e InterestProfile/StrengthAssessment no eran Content Entities (datos en `user.data` volatil), no habia servicios dedicados, faltaban forms Phase 2/3
3. **Copilot v2 Integration** (Doc 161): No habia inyeccion automatica del contexto Self-Discovery al Copiloto

---

## Solucion Implementada

### 1. Content Entities (Patron replicado de LifeWheelAssessment)

**Regla ENTITY-SD-001**: Para migrar datos de `user.data` a Content Entities, siempre implementar **dual storage** temporal:
- Guardar en entity Y en `user.data` simultaneamente
- Los servicios nuevos leen de entity con fallback a `user.data`
- Eliminar `user.data` solo cuando todas las migraciones esten completas

```php
// Patron en InterestsAssessmentForm::saveResults()
// 1. Guardar en user.data (retrocompatibilidad)
$this->userData->set('jaraba_self_discovery', $uid, 'riasec_data', $riasec_data);
// 2. Guardar en Content Entity (nuevo)
InterestProfile::create([...campos...])->save();
```

**InterestProfile** (entity RIASEC):
- 6 campos enteros score_realistic..score_conventional (0-100)
- Campo riasec_code (string, 3 chars)
- Campos JSON: dominant_types, answers, suggested_careers

**StrengthAssessment** (entity Fortalezas):
- Campos JSON: top_strengths (top 5), all_scores (24 fortalezas), answers (20 selecciones)

### 2. Servicios Especializados (Patron Delegacion)

**Regla SERVICE-SD-001**: Cuando un servicio agregador (SelfDiscoveryContextService) crece demasiado, **refactorizar extrayendo servicios dedicados** e inyectarlos como dependencias opcionales:

```php
// SelfDiscoveryContextService refactorizado
public function __construct(
  // ... args originales ...
  ?LifeWheelService $lifeWheelService = NULL,
  ?TimelineAnalysisService $timelineAnalysisService = NULL,
  ?RiasecService $riasecService = NULL,
  ?StrengthAnalysisService $strengthAnalysisService = NULL,
) {
```

4 servicios creados:
- **LifeWheelService**: getLatestAssessment, getAverageScore, getLowestAreas, getHighestAreas, getTrend, getHistorical
- **TimelineAnalysisService**: getAllEvents, getIdentifiedPatterns, getTopSatisfactionFactors, getTopSkills, getTopValues
- **RiasecService**: getLatestProfile, getCode, getScores, getProfileDescription (con fallback user.data)
- **StrengthAnalysisService**: getLatestAssessment, getTop5, getAllScores, getStrengthDescription (con fallback user.data)

### 3. Copilot v2 Integration (Patron Nullable Service)

**Regla COPILOT-SD-001**: Para inyectar contexto cross-modulo en el CopilotOrchestratorService, seguir el patron nullable:

```php
// CopilotOrchestratorService constructor (10o argumento)
?SelfDiscoveryContextService $selfDiscoveryContext = NULL

// En buildSystemPrompt(), con try/catch defensivo
if ($this->selfDiscoveryContext) {
  try {
    $sdPrompt = $this->selfDiscoveryContext->getCopilotContextPrompt();
    if ($sdPrompt) {
      $systemPrompt .= "\n\n" . $sdPrompt;
    }
  } catch (\Exception $e) {
    $this->logger->warning('Self-Discovery context unavailable');
  }
}
```

### 4. Infraestructura Lando

- `.lando/redis.conf`: maxmemory 1gb, allkeys-lru, persistencia RDB
- `.env.example`: Template con placeholders (DB, Redis, Qdrant, Tika, AI providers, Stripe)
- `scripts/setup-dev.sh`: Onboarding script ejecutable con verificaciones
- `settings.local.php` completado: +Qdrant, +Tika, +AI providers, +Xdebug, +trusted hosts, +dev cache, +file paths

### 5. Timeline Phase 2/3 Forms

- **TimelinePhase2Form**: Describe evento con satisfaction_factors, skills, values, learnings
- **TimelinePhase3Form**: Identificacion de patrones con insights auto-generados por TimelineAnalysisService

---

## Reglas Documentadas

| Regla | Descripcion |
|-------|-------------|
| **ENTITY-SD-001** | Dual storage (entity + user.data) para migracion gradual |
| **SERVICE-SD-001** | Extraer servicios dedicados de agregadores grandes con DI nullable |
| **COPILOT-SD-001** | Inyeccion nullable + try/catch para contexto cross-modulo |
| **INFRA-SD-001** | getenv() para todas las variables de entorno en settings.local.php |

---

## Archivos Creados/Modificados

**23 archivos nuevos:**
- 2 Content Entities (InterestProfile, StrengthAssessment)
- 2 ListBuilders, 2 AccessControlHandlers, 2 SettingsForms
- 4 Services (LifeWheel, TimelineAnalysis, Riasec, StrengthAnalysis)
- 2 Forms (TimelinePhase2Form, TimelinePhase3Form)
- 5 Unit Tests
- 4 Infraestructura (.lando/redis.conf, .env.example, scripts/setup-dev.sh, doc implementacion)

**11 archivos modificados:**
- services.yml, routing.yml, links.menu.yml, links.task.yml, links.action.yml
- InterestsAssessmentForm.php, StrengthsAssessmentForm.php
- SelfDiscoveryContextService.php
- CopilotOrchestratorService.php, jaraba_copilot_v2.services.yml
- settings.local.php

---

## Lecciones Aprendidas

1. **user.data es volatil**: No tiene Field UI, no aparece en Views, no tiene entrypoint admin. Para datos persistentes siempre usar Content Entities
2. **Patron nullable robusto**: El constructor nullable + try/catch en runtime evita que un modulo deshabilitado rompa otro
3. **Tests puros**: PHPUnit TestCase puro (sin UnitTestCase de Drupal) permite testear logica replicada sin bootstrap
4. **PHP 8.4 en Lando**: Los tests deben ejecutarse via `lando php vendor/bin/phpunit`, no en el host
5. **Dual storage transitorio**: Guardar en ambos lados permite migracion sin downtime, pero debe tener fecha de sunset

---

## Verificacion

```bash
# Ejecutar tests
lando php vendor/bin/phpunit web/modules/custom/jaraba_self_discovery/tests/

# Crear tablas nuevas
lando drush entity:updates -y && lando drush cr

# Verificar admin
# /admin/content → tabs InterestProfile, StrengthAssessment
# /admin/structure → enlaces configuracion
```
