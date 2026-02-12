# F11 — Elevacion IA Clase Mundial — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F11 de 12
**Modulo:** `jaraba_ai_agents` (extension)
**Estimacion:** 40-60h
**Dependencias:** jaraba_ai_agents, jaraba_ab_testing, jaraba_tenant_knowledge (Qdrant)

---

## 1. Objetivo

Completar capacidades de IA clase mundial: Brand Voice Trainer con
pipeline de re-entrenamiento Qdrant, sistema formal de A/B testing
de prompts integrado con jaraba_ab_testing, y preparacion multi-modal.

## 2. Estado Actual

| Componente | Estado |
|------------|--------|
| TenantBrandVoiceService | Existe (archetype, personality, examples) |
| QualityEvaluatorService (LLM-as-Judge) | Existe (5 criterios ponderados) |
| AB Testing module | Completo (ABExperiment, StatisticalEngine, VariantAssignment) |
| Qdrant integration (jaraba_tenant_knowledge) | Existe (KnowledgeIndexer, embeddings) |
| Brand Voice Trainer | NO existe |
| Prompt A/B Testing | NO existe |
| Multi-modal interfaces | NO existe |

## 3. Arquitectura

### 3.1 BrandVoiceTrainerService

Pipeline de re-entrenamiento:
1. Indexar ejemplos de brand voice como embeddings en Qdrant
2. Al ejecutar un agente, comparar output con brand voice embeddings
3. Recoger feedback humano (approve/reject/edit)
4. Re-entrenar: usar ejemplos aprobados para refinar brand voice
5. Calcular brand_alignment_score via similitud coseno

Coleccion Qdrant: `jaraba_brand_voice` (1536 dimensiones)

### 3.2 PromptExperimentService

Integracion formal con jaraba_ab_testing:
- experiment_type: `prompt_variant`
- Cada variante almacena: system_prompt, temperature, model_tier
- Al ejecutar agente → asignar variante via VariantAssignmentService
- Conversion = quality_score >= umbral (configurable, default 0.7)
- Auto-evaluacion via QualityEvaluatorService

### 3.3 Multi-modal Preparation

Interfaces PHP para futuras capacidades:
- MultiModalInputInterface: transcribeAudio(), analyzeImage()
- MultiModalOutputInterface: synthesizeSpeech(), generateImage()
- MultiModalBridgeService: stub con excepciones informativas

## 4. Archivos Creados/Modificados

| Archivo | Accion |
|---------|--------|
| src/Service/BrandVoiceTrainerService.php | Nuevo |
| src/Service/PromptExperimentService.php | Nuevo |
| src/Contract/MultiModalInputInterface.php | Nuevo |
| src/Contract/MultiModalOutputInterface.php | Nuevo |
| src/Service/MultiModalBridgeService.php | Nuevo |
| jaraba_ai_agents.services.yml | Modificado (+3 services) |
| jaraba_ai_agents.routing.yml | Modificado (+4 routes) |
| jaraba_ai_agents.permissions.yml | Modificado (+2 permissions) |

## 5. Verificacion

- [ ] `drush cr` exitoso
- [ ] Service `brand_voice_trainer` registrado
- [ ] Service `prompt_experiment` registrado
- [ ] Service `multimodal_bridge` registrado
- [ ] 4 rutas API nuevas activas
- [ ] MultiModalBridgeService implementa ambas interfaces
