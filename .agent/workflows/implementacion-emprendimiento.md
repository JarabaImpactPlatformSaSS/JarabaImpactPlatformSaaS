---
description: Implementación del vertical Emprendimiento con Copiloto v2 y Desbloqueo Progresivo UX
---

# Workflow: Implementación Vertical Emprendimiento

Este workflow documenta el proceso para implementar el vertical Emprendimiento con el Copiloto v2 y el patrón de Desbloqueo Progresivo UX.

> **Estado**: ✅ IMPLEMENTADO — Clase Mundial (Specs 20260121a-e + 20260122-25 100% cerradas + Gaps cerrados, 2026-02-12)
> - 22 API endpoints REST + **Chat SSE Stream**, 14+ servicios produccion, 5 Content Entities con Access Handlers + ListBuilders
> - 3 paginas frontend (BMC Dashboard, Hypothesis Manager, Experiment Lifecycle) + **widget chat SSE** (Alpine.js streaming)
> - Impact Points gamification + ICE Score prioritization + BMC Semaforos + **milestones persistentes** (`entrepreneur_milestone`)
> - **7 suites unit tests** (64 tests, 184 assertions, PHPUnit 11) — incluye 3 nuevos: ModeDetectorDbTest, ExperimentApiReflectionTest, HypothesisApiReflectionTest
> - **Triggers BD configurables**: 175 triggers en tabla `copilot_mode_triggers` con cache 1h + fallback constante PHP + admin UI
> - **Multi-proveedor optimizado**: consultor/landing→Gemini Flash (ahorro ~55%), coach/sparring→Claude, cfo→GPT-4o. Modelos vigentes: claude-sonnet-4-5, gemini-2.5-flash, claude-haiku-4-5
> - **Metricas avanzadas**: P50/P99 latencia, fallback rate, costes diarios por proveedor (`getMetricsSummary()`)
> - **Self-Discovery Integration**: SelfDiscoveryContextService inyectado en CopilotOrchestratorService (10o arg nullable)
> - **Self-Discovery Entities**: InterestProfile (RIASEC) + StrengthAssessment (VIA) como Content Entities
> - **Self-Discovery Services**: LifeWheelService, TimelineAnalysisService, RiasecService, StrengthAnalysisService
> - **Correcciones PHP 8.4**: `create()`→`store()` (API-NAMING-001), property redeclaration (DRUPAL11-001), Kernel→Unit tests (KERNEL-TEST-001)

---

## Principio Rector: Desbloqueo Progresivo UX

> **El emprendedor (Javier) ve exactamente lo que necesita cuando lo necesita.**
> La plataforma "crece" con él a lo largo de las 12 semanas del programa.

---

## Pasos de Implementación

### 1. Crear FeatureUnlockService

Implementar el servicio que controla qué features están disponibles por semana:

```php
// Ubicación: web/modules/custom/jaraba_copilot_v2/src/Service/FeatureUnlockService.php

namespace Drupal\jaraba_copilot_v2\Service;

class FeatureUnlockService {
    
    const UNLOCK_MAP = [
        0 => ['dime_test', 'profile_basic'],
        1 => ['copilot_coach', 'pills_1_3', 'kit_emocional'],
        4 => ['canvas_vpc', 'canvas_bmc', 'experiments_discovery'],
        7 => ['copilot_cfo', 'calculadora_precio', 'test_card'],
        10 => ['mentoring_marketplace', 'calendar_sessions'],
        12 => ['experiments_commitment', 'demo_day', 'certificado']
    ];
    
    public function isFeatureUnlocked(string $feature, $profile): bool {
        $weekNumber = $profile->getCurrentProgramWeek();
        return in_array($feature, $this->getUnlockedFeatures($profile));
    }
}
```

### 2. Crear UI de Feature Bloqueada

// turbo
Implementar template Twig para funcionalidades bloqueadas:

```twig
{# Ubicación: web/modules/custom/jaraba_copilot_v2/templates/feature-locked.html.twig #}

{% if not is_unlocked %}
<div class="feature-locked">
    <div class="feature-locked__icon">🔒</div>
    <div class="feature-locked__message">
        {{ 'Esta funcionalidad estará disponible en la Semana @week'|t({'@week': unlock_week}) }}
    </div>
    <div class="feature-locked__preview">
        {{ feature_preview }}
    </div>
</div>
{% endif %}
```

### 3. Integrar Entregables Copiloto v2

Los siguientes archivos están listos en `docs/tecnicos/20260121a-*`:

| Archivo | Destino |
|---------|---------|
| `copilot_integration.module` | `web/modules/custom/jaraba_copilot_v2/` |
| `experiment_library_*.json` | `web/modules/custom/jaraba_copilot_v2/data/` |
| `CopilotChatWidget.jsx` | `web/themes/custom/ecosistema_jaraba/js/components/` |
| `BMCValidationDashboard.jsx` | `web/themes/custom/ecosistema_jaraba/js/components/` |
| `migraciones_sql_copiloto_v2.sql` | Ejecutar en base de datos |

### 4. Crear Content Entities

Crear las entidades base siguiendo el patrón de Content Entities:

- **`entrepreneur_profile`**: Perfil emprendedor + DIME + carril
- **`hypothesis`**: Hipótesis de negocio + bloque BMC
- **`experiment`**: Experimento de validación + Test/Learning Cards
- **`copilot_session`**: Sesión de conversación con el copiloto

Ver workflow `/drupal-custom-modules` para detalles de implementación.

### 5. Mapa de Desbloqueo por Semana

| Semana | Funcionalidades |
|--------|-----------------|
| **0** | DIME + Clasificación Carril + Perfil Básico |
| **1-3** | Copiloto Coach + Píldoras 1-3 + Kit Emocional |
| **4-6** | +Canvas VPC/BMC + Experimentos DISCOVERY |
| **7-9** | +Copiloto CFO/Devil + Calculadora + Dashboard Validación |
| **10-11** | +Mentores + Calendario + Círculos Responsabilidad |
| **12** | +Demo Day + Certificado + Club Alumni |

---

## 5 Modos del Copiloto

| Modo | Trigger | Semana Disponible |
|------|---------|-------------------|
| 🧠 Coach Emocional | miedo, bloqueo, impostor | 1 |
| 🔧 Consultor Táctico | cómo hago, paso a paso | 4 |
| 🥊 Sparring Partner | qué te parece, feedback | 4 |
| 💰 CFO Sintético | precio, cobrar, rentable | 7 |
| 😈 Abogado del Diablo | estoy seguro, funcionará | 7 |

---

## Verificación

1. **Verificar desbloqueo progresivo**: Crear usuario de prueba en Semana 0 y confirmar que solo ve DIME
2. **Verificar transiciones**: Avanzar semana y confirmar que nuevas features se desbloquean
3. **Verificar UI bloqueada**: Confirmar que features futuras muestran mensaje "Disponible en Semana X"
4. **Verificar modos copiloto**: Confirmar que solo modos desbloqueados responden

---

---

## API Endpoints Implementados (22 totales)

| Grupo | Endpoints | Método |
|-------|-----------|--------|
| **Hypothesis** | `/api/v1/hypotheses` | GET, POST |
| | `/api/v1/hypotheses/{id}` | GET, PATCH |
| | `/api/v1/hypotheses/prioritize` | POST (ICE Score) |
| **Experiment** | `/api/v1/experiments` | GET, POST |
| | `/api/v1/experiments/{id}` | GET |
| | `/api/v1/experiments/{id}/start` | POST |
| | `/api/v1/experiments/{id}/result` | PATCH (Learning Card) |
| **BMC** | `/api/v1/bmc/validation/{userId}` | GET (9 bloques, semáforos) |
| | `/api/v1/bmc/pivot-log/{userId}` | GET |
| **Entrepreneur** | `/api/v1/entrepreneurs` | GET, POST, PATCH |
| | `/api/v1/entrepreneurs/dime` | POST (DIME scores) |
| **History** | `/api/v1/copilot/history/{sessionId}` | GET |
| **Knowledge** | `/api/v1/knowledge/search` | GET |

## Servicios Produccion (14+ totales)

| Servicio | Responsabilidad |
|----------|-----------------|
| HypothesisPrioritizationService | ICE = Importance x Confidence x Evidence |
| BmcValidationService | Semaforos: RED <33%, YELLOW 33-66%, GREEN >66%, GRAY sin datos |
| LearningCardService | Genera Learning Card desde resultado experimento |
| TestCardGeneratorService | Genera Test Card desde hipotesis |
| ModeDetectorService | **175 triggers BD** + fallback const PHP + cache 1h + analisis emocional, 7 modos |
| CopilotOrchestratorService | **Multi-proveedor optimizado** Gemini/Claude/GPT-4o + **metricas P50/P99** + fallback rate + costes |
| PivotDetectorService | 3+ hipotesis invalidadas → senal de pivot |
| ContentGroundingService | Enriquece respuestas con contenido Drupal real |
| ValuePropositionCanvasService | Jobs, Pains, Gains vs Features, Relievers, Creators |
| BusinessPatternDetectorService | 10 patrones BMG (Long Tail, Freemium, Multi-Sided) |
| CustomerDiscoveryGamificationService | Badges: First Interview, 10 Contacts, BMC Complete |
| CopilotCacheService | TTL + cache tags por tenant |
| ClaudeApiService | HTTP wrapper Anthropic API con retry (model actualizado a claude-sonnet-4-5) |
| FaqGeneratorService | Agrupa preguntas frecuentes del query log |
| NormativeKnowledgeService | Full-text search en base normativa |

## Tablas Custom

| Tabla | Proposito |
|-------|-----------|
| `copilot_mode_triggers` | 175 triggers configurables desde admin UI, cache 1h, fallback const PHP |
| `entrepreneur_milestone` | Hitos append-only con tipo, descripcion, puntos, entidad relacionada |

## Frontend Pages

| Ruta | Controller | Template |
|------|-----------|----------|
| `/emprendimiento/bmc` | `CopilotDashboardController::bmcDashboard()` | `bmc-dashboard.html.twig` |
| `/emprendimiento/hipotesis` | `CopilotDashboardController::hypothesisManager()` | `hypothesis-manager.html.twig` |
| `/emprendimiento/experimentos/gestion` | `CopilotDashboardController::experimentLifecycle()` | `experiment-lifecycle.html.twig` |
| Widget Chat SSE | `CopilotStreamController::stream()` | `copilot-chat-widget.html.twig` (Alpine.js + ReadableStream) |

## Admin Pages

| Ruta | Controller | Proposito |
|------|-----------|-----------|
| `/admin/config/jaraba/copilot-v2/triggers` | `ModeTriggersAdminForm` | Gestion CRUD triggers de modos (crear, editar, peso, restaurar) |
| `/admin/copilot/analytics` | `CopilotAnalyticsController` | Metricas P50/P99, fallback rate, costes por proveedor |

## Reglas Tecnicas del Modulo

| Regla | ID | Descripcion |
|-------|----|-------------|
| API POST naming | API-NAMING-001 | Usar `store()` para POST de creacion, NUNCA `create()` (colisiona con DI factory) |
| Triggers BD fallback | COPILOT-DB-001 | Mantener const PHP como fallback al migrar config a BD |
| Unit vs Kernel tests | KERNEL-TEST-001 | Reflection/constantes → TestCase. BD/entidades → KernelTestBase |
| SSE con POST | SSE-001 | `fetch()` + `ReadableStream`, no `EventSource` (solo soporta GET) |
| Logs append-only | MILESTONE-001 | Tabla custom via hook_update_N(), no Content Entity |
| Metricas temporales | METRICS-001 | State API con claves fechadas, max 1000 muestras/dia |
| Multi-proveedor | PROVIDER-001 | Gemini Flash para alto volumen, Claude/GPT-4o para calidad |

---

## Referencias

- Plan de Implementacion v3.1: `brain/*/implementation_plan.md`
- **Plan Cierre Gaps Copilot v2**: `docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Copiloto_v2_Specs_20260121.md`
- **Plan Cierre Gaps Self-Discovery**: `docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260122_20260125.md`
- Especificaciones Copiloto v2: `docs/tecnicos/20260121a-Especificaciones_Tecnicas_Copiloto_v2_Claude.md`
- OpenAPI: `docs/tecnicos/20260121a-openapi_copiloto_v2.yaml`
- Programa Andalucia +ei: `docs/tecnicos/20260115c-Programa Maestro Andalucía +ei V2.0_Gemini.md`
- Aprendizaje Desbloqueo: `docs/tecnicos/aprendizajes/2026-01-21_desbloqueo_progresivo_ux.md`
- **Aprendizaje API Patterns**: `docs/tecnicos/aprendizajes/2026-02-12_copilot_v2_api_lifecycle_patterns.md`
- **Aprendizaje Gaps Closure (BD/SSE/Metrics)**: `docs/tecnicos/aprendizajes/2026-02-12_copilot_v2_gaps_closure_db_streaming_metrics.md`
- **Aprendizaje Self-Discovery Entities + Services**: `docs/tecnicos/aprendizajes/2026-02-12_self_discovery_content_entities_services.md`
- **Aprendizaje Heatmaps + Tracking Fases 1-5**: `docs/tecnicos/aprendizajes/2026-02-12_heatmaps_tracking_phases_1_5.md`
