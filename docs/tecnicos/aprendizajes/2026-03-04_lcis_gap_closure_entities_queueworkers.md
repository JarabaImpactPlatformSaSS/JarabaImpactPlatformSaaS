# Aprendizaje #157 — LCIS Gap Closure: 2 Entities + 2 QueueWorkers + EU AI Act Audit Trail

**Fecha:** 2026-03-04
**Contexto:** Cierre de 4 gaps de complitud entre spec 179 y la implementacion real del Legal Coherence Intelligence System (LCIS)
**Directrices:** v107.0.0 | **Arquitectura:** v96.0.0 | **Indice:** v136.0.0 | **Flujo:** v60.0.0

---

## Problema

El LCIS (9 capas, ~95% implementado) tenia 4 gaps respecto a la spec 179:

| Gap | Severidad | Descripcion |
|-----|-----------|-------------|
| G1 | ALTA | `legal_norm_relation` entity faltante — NormativeGraphEnricher usaba heuristicas v1 basadas en payload.status |
| G2 | CRITICA | `legal_coherence_log` entity faltante — sin audit trail, incumplimiento EU AI Act Art. 12 |
| G3 | MEDIA | `NormativeUpdateWorker` queue worker faltante — actualizaciones de vigencia solo manuales |
| G4 | MEDIA | `NormVectorizationWorker` queue worker faltante — re-vectorizacion solo manual |

## Decision Arquitectonica: NO crear jaraba_lcis

La spec 179 proponia `jaraba_lcis` como modulo standalone. La implementacion real distribuye LCIS entre:
- `jaraba_legal_intelligence` — pipeline LCIS (9 capas), servicios, queue workers
- `jaraba_legal_knowledge` — entidades de datos (LegalNorm, LegalChunk, LegalNormRelation)

**Ventajas de la distribucion actual:**
1. Evita duplicar LegalNorm/LegalChunk entities
2. Evita circular dependencies (jaraba_lcis necesitaria jaraba_legal_knowledge Y viceversa)
3. 9 clases LegalCoherence ya namespace-aisladas en `src/LegalCoherence/`
4. Coherencia con patron del proyecto: otros subsistemas tampoco son modulos separados

## Implementacion

### G1: `legal_norm_relation` (jaraba_legal_knowledge)

- ContentEntity con 10 `relation_type`: deroga_total, deroga_parcial, modifica, desarrolla, transpone, cita, complementa, prevalece_sobre, es_especial_de, sustituye
- `source_norm_id` + `target_norm_id`: entity_reference a `legal_norm` (ENTITY-FK-001 mismo modulo)
- `affected_articles`: string_long (JSON array de articulos afectados)
- PremiumEntityFormBase con 3 secciones (relation, details, admin)
- AccessControlHandler con tenant isolation (TENANT-ISOLATION-ACCESS-001)
- hook_update_10001 para instalar entity type

### G2: `legal_coherence_log` (jaraba_legal_intelligence)

- 15 campos audit trail: query_text, intent_type, coherence_score (float), validator_results (JSON), verifier_results (JSON), norm_citations (JSON), hierarchy_chain (JSON), disclaimer_appended, retries_needed, blocked, block_reason, response_snippet, vertical, tenant_id, trace_id
- Read-only: AccessControlHandler deniega `update` (logs inmutables)
- NO form handlers (creacion programatica desde Validator/Verifier)
- hook_update_10005 para instalar entity type

### G3: `NormativeUpdateWorker`

- Queue: `jaraba_legal_intelligence_normative_update`, cron 60s
- processItem: carga norm afectada, actualiza status, crea LegalNormRelation (con duplicate check)
- Maneja `superseded_by` para deroga_total/sustituye

### G4: `NormVectorizationWorker`

- Queue: `jaraba_legal_intelligence_vectorization`, cron 120s
- Servicios opcionales: chunkingService, embeddingService (from jaraba_legal_knowledge)
- Defensivo: marca `embedding_status = 'pending'` si servicios no disponibles

### Servicios Modificados

- **NormativeGraphEnricher**: +`checkNormRelationStatus()` consulta legal_norm_relation para detectar normas derogadas/modificadas, +`getRelationsForNorms()` carga relaciones por source norm IDs
- **LegalCoherenceValidatorService**: +`createCoherenceLog()` crea LegalCoherenceLog tras validate()
- **LegalCoherenceVerifierService**: +`createVerificationLog()` crea LegalCoherenceLog tras verify()

## Patrones Aplicados

| Patron | Aplicacion |
|--------|------------|
| ENTITY-FK-001 | source/target_norm_id = entity_reference (mismo modulo) |
| ENTITY-001 | EntityOwnerInterface + EntityChangedInterface en ambas entities |
| TENANT-ISOLATION-ACCESS-001 | tenant match check en update/delete |
| PREMIUM-FORMS-PATTERN-001 | LegalNormRelationForm extiende PremiumEntityFormBase |
| UPDATE-HOOK-REQUIRED-001 | hook_update_10001 y hook_update_10005 |
| UPDATE-HOOK-CATCH-001 | catch(\Throwable) en ambos hooks |
| MOCK-DYNPROP-001 | Tests usan anonymous classes con typed properties (PHP 8.4) |
| KERNEL-SYNTH-001 | Synthetic services para dependencias de modulos no cargados |

## Tests

| Test | Tipo | Assertions |
|------|------|------------|
| NormativeUpdateWorkerTest | Unit | 3 tests: missing IDs, non-existent norm, status update |
| NormVectorizationWorkerTest | Unit | 4 tests: missing norm_id, missing services, non-existent norm, successful vectorization |
| LegalNormRelationEntityTest | Kernel | CRUD, 10 relation types, deletion, interfaces |
| LegalCoherenceLogEntityTest | Kernel | CRUD, JSON fields, verifier results, interfaces |
| NormativeGraphEnricherIntegrationTest | Kernel | Derogated norm filtered, modified norm warning, getRelationsForNorms |

## Ficheros

**10 nuevos:**
1. `jaraba_legal_knowledge/src/Entity/LegalNormRelation.php`
2. `jaraba_legal_knowledge/src/Access/LegalNormRelationAccessControlHandler.php`
3. `jaraba_legal_knowledge/src/Form/LegalNormRelationForm.php`
4. `jaraba_legal_knowledge/src/ListBuilder/LegalNormRelationListBuilder.php`
5. `jaraba_legal_intelligence/src/Entity/LegalCoherenceLog.php`
6. `jaraba_legal_intelligence/src/Access/LegalCoherenceLogAccessControlHandler.php`
7. `jaraba_legal_intelligence/src/ListBuilder/LegalCoherenceLogListBuilder.php`
8. `jaraba_legal_intelligence/src/Plugin/QueueWorker/NormativeUpdateWorker.php`
9. `jaraba_legal_intelligence/src/Plugin/QueueWorker/NormVectorizationWorker.php`
10. 5 test files

**9 modificados:**
- jaraba_legal_knowledge: .install, .module, .routing.yml
- jaraba_legal_intelligence: .install, .routing.yml, .services.yml, NormativeGraphEnricher.php, LegalCoherenceValidatorService.php, LegalCoherenceVerifierService.php

## Reglas Nuevas

- **LCIS-AUDIT-001** (P0): Toda validacion LCIS crea legal_coherence_log — EU AI Act Art. 12
- **LCIS-GRAPH-001** (P1): NormativeGraphEnricher consulta legal_norm_relation, no heuristicas
- **Regla de oro #97**: LCIS entities en modulos correctos (datos normativos en legal_knowledge, audit IA en legal_intelligence)
