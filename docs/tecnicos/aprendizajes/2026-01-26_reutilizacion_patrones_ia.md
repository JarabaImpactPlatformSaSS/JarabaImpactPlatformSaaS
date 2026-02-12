# Aprendizaje: Reutilización Prioritaria de Patrones Existentes

**Fecha:** 2026-01-26
**Módulo:** Arquitectura IA / Copilotos
**Impacto:** Alta - Evita duplicación de código y mantiene consistencia

---

## Resumen

Al implementar nuevas funcionalidades, **siempre verificar primero qué patrones y servicios ya existen** antes de diseñar desde cero. La arquitectura modular del SaaS permite reutilizar servicios como `CopilotOrchestratorService` en múltiples contextos.

---

## Ejemplo: Copiloto Público

### ❌ Lo que se creía necesario
- Crear un nuevo servicio completo para el copiloto público
- Implementar detección de intenciones desde cero
- Diseñar sistema de sugerencias independiente

### ✅ Lo que ya existía y se reutilizó

| Componente | Ya existía en | Reutilizado en |
|------------|---------------|----------------|
| `CopilotOrchestratorService` | `jaraba_copilot_v2` | `PublicCopilotController` |
| Rate Limiting con Flood | Core Drupal | Copiloto público |
| Detección de modo | `ModeDetectorService` | Modo `landing_copilot` |
| Suggestions contextuales | Empleabilidad | Botones quick actions |
| Feedback loop | RAG Analytics | Tabla `jaraba_copilot_feedback` |

### Código del Copiloto Público

```php
// ✅ PublicCopilotController.php - REUTILIZA el orchestrator existente
$response = $this->copilotOrchestrator->chat(
    $this->buildPublicEnrichedMessage($message, $context),
    $publicContext,
    'landing_copilot'  // Modo específico, mismo servicio
);
```

---

## Regla: Checklist Pre-Implementación

Antes de implementar una nueva funcionalidad:

1. **Buscar servicios existentes** en `web/modules/custom/*/src/Service/`
2. **Revisar documentación técnica** en `docs/tecnicos/` por keywords
3. **Verificar copilotos por vertical** - pueden tener patrones reutilizables
4. **Consultar índice general** `docs/00_INDICE_GENERAL.md`
5. **Revisar aprendizajes** en `docs/tecnicos/aprendizajes/`

---

## Arquitectura IA Reutilizable

### Servicios Core IA (15+ servicios en `jaraba_copilot_v2`)

```
jaraba_copilot_v2/src/Service/
├── CopilotOrchestratorService.php  # ✨ Orquestador multi-modo
├── ModeDetectorService.php         # Detección automática de modo
├── NormativeRAGService.php         # RAG semántico
├── EntrepreneurContextService.php  # Contexto dinámico
└── FeatureUnlockService.php        # Desbloqueo progresivo
```

### Servicios RAG Multi-Tenant (`jaraba_rag`)

```
jaraba_rag/src/Service/
├── JarabaRagService.php           # Orquestador RAG
├── GroundingValidator.php         # Anti-alucinaciones
├── QueryAnalyticsService.php      # Detección de gaps
└── TenantContextService.php       # Aislamiento por tenant
```

### Servicios Matching IA (`jaraba_matching`)

```
jaraba_matching/src/Service/
├── EmbeddingService.php           # Vectorización
├── MatchingService.php            # Job-Candidate match
└── RecommendationService.php      # Recomendaciones
```

---

## Impacto en Tiempo

| Enfoque | Tiempo estimado |
|---------|-----------------|
| Implementar desde cero | 40-60h |
| Reutilizar y extender | 8-16h |
| **Ahorro** | **70-75%** |

---

## Referencias

- [PublicCopilotController.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Controller/PublicCopilotController.php)
- [CopilotOrchestratorService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php)
- [Auditoría Frontend Hallazgos](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/2026-01-26_auditoria_frontend_hallazgos.md)
