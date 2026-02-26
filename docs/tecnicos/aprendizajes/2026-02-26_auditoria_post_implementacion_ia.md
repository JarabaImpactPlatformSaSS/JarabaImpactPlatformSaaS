# Aprendizaje #130 — Auditoria Post-Implementacion IA: Service Call Contracts y Config Schemas

**Fecha:** 2026-02-26
**Contexto:** Auditoria sistematica de los 23 FIX items (FIX-029 a FIX-051) implementados para elevar el stack IA a nivel 5/5. Todos los ficheros PHP existian con codigo de produccion real, pero 3 bugs criticos de wiring y 3 schemas faltantes impedian el correcto funcionamiento.

---

## 1. Hallazgos Criticos

### 1.1 SemanticCacheService No Registrado (CRITICO)
- **Problema:** `SemanticCacheService.php` (237 LOC) existia con implementacion completa pero NO tenia entrada en `jaraba_copilot_v2.services.yml`.
- **Impacto:** `\Drupal::hasService('jaraba_copilot_v2.semantic_cache')` siempre retornaba `FALSE`, deshabilitando silenciosamente la capa 2 de cache semantica (Qdrant, threshold 0.92).
- **Root cause:** El fichero PHP se creo pero el registro en services.yml se omitio.
- **Fix:** Registrar servicio con 3 args opcionales: `@logger.channel.jaraba_copilot_v2`, `@?jaraba_rag.qdrant_client`, `@?jaraba_rag.rag_service`.

### 1.2 Call Signature Mismatch en `get()` (CRITICO)
- **Problema:** `CopilotCacheService` llamaba `$semanticCache->get($message, (string) $tenantId)` con 2 args.
- **Firma real:** `SemanticCacheService::get(string $query, string $mode, ?string $tenantId)` — 3 args.
- **Consecuencia:** `$tenantId` se pasaba en posicion de `$mode`, y `$tenantId` quedaba como NULL. TypeError en runtime.
- **Fix:** Anadir `$mode` como segundo argumento.

### 1.3 Call Signature Mismatch en `set()` (CRITICO)
- **Problema:** `CopilotCacheService` llamaba `$semanticCache->set($message, (string) $tenantId, $response, $ttl)`.
- **Firma real:** `SemanticCacheService::set(string $query, string $response, string $mode, ?string $tenantId, int $ttl)`.
- **Consecuencia:** `$tenantId` en posicion 2 (esperaba `$response` string), array `$response` en posicion 3 (esperaba `$mode` string). TypeError en runtime.
- **Fix:** Extraer `$responseText = $response['text'] ?? json_encode($response)` y reordenar args.

### 1.4 Config Reranking Faltante (ALTO)
- **Problema:** `JarabaRagService::reRankResults()` lee `$config->get('reranking.strategy')` pero `jaraba_rag.settings.yml` no tenia seccion `reranking`.
- **Impacto:** La estrategia siempre defaulteaba a `'keyword'`, haciendo que `LlmReRankerService` (FIX-037) fuera inaccesible desde configuracion.
- **Fix:** Anadir seccion `reranking: { strategy: 'hybrid', llm_weight: 0.6, vector_weight: 0.4 }` al YAML + schema.

### 1.5 Tres Config Schemas Faltantes (MEDIO)
- `jaraba_ai_agents.provider_fallback` — cadenas de fallback por tier
- `jaraba_agents.agent_type_mapping` — mapeo agent_type a service_id
- `jaraba_matching.settings` — configuracion completa de matching
- **Impacto:** `SchemaIncompleteException` en Kernel tests per CONFIG-SCHEMA-001.
- **Fix:** Schemas anadidos a ficheros existentes + 1 fichero nuevo (`jaraba_matching.schema.yml`).

---

## 2. Patrones Aprendidos

### 2.1 SERVICE-CALL-CONTRACT-001 (Nueva Regla P0)
Cuando un servicio se registra en `services.yml` y se consume desde otro servicio, las llamadas DEBEN coincidir con la firma del metodo. Un `hasService()` + `try-catch` NO protege contra errores de firma. Verificar con:
```bash
grep -rn 'serviceName->methodName' web/modules/custom/
```
Comparar args en cada punto de llamada con la firma del metodo.

### 2.2 Auditoria de 4 Dimensiones (Regla de Oro #50)
Despues de implementar cambios batch, auditar:
1. **Registro** — todo PHP service tiene entrada en services.yml
2. **Firmas** — callers coinciden con firma del metodo
3. **Schemas** — todo config YAML tiene schema correspondiente
4. **Completitud** — las secciones que se leen con `$config->get()` existen en el YAML

### 2.3 hasService() + try-catch es Necesario pero Insuficiente
- `hasService()` protege contra servicios no instalados → retorna FALSE, skip.
- `try-catch(\Throwable)` protege contra excepciones de ejecucion.
- Pero si el servicio EXISTE y se llama con firma incorrecta, el TypeError se lanza DENTRO de la llamada. El try-catch lo captura, pero el servicio queda efectivamente roto.
- La unica proteccion real es verificar la firma en tiempo de desarrollo.

---

## 3. Ficheros Modificados

| Fichero | Accion | Descripcion |
|---------|--------|-------------|
| `jaraba_copilot_v2/jaraba_copilot_v2.services.yml` | Modificado | SemanticCacheService registrado |
| `jaraba_copilot_v2/src/Service/CopilotCacheService.php` | Modificado | 2 call signatures corregidas (get + set) |
| `jaraba_rag/config/install/jaraba_rag.settings.yml` | Modificado | Seccion reranking anadida |
| `jaraba_rag/config/schema/jaraba_rag.schema.yml` | Modificado | Schema reranking anadido |
| `jaraba_ai_agents/config/schema/jaraba_ai_agents.schema.yml` | Modificado | Schema provider_fallback anadido |
| `jaraba_agents/config/schema/jaraba_agents.schema.yml` | Modificado | Schema agent_type_mapping anadido |
| `jaraba_matching/config/schema/jaraba_matching.schema.yml` | **Creado** | Schema completo jaraba_matching.settings |

---

## 4. Reglas Derivadas

| ID | Prioridad | Resumen |
|----|-----------|---------|
| SERVICE-CALL-CONTRACT-001 | P0 | Firmas de llamada DEBEN coincidir exactamente con firmas de metodo |
| Regla de Oro #50 | — | Auditoria post-implementacion obligatoria de 4 dimensiones |

---

## 5. Verificacion

- 35 ficheros PHP validados con `php -l` → 0 errores de sintaxis
- 14 ficheros YAML validados con `yaml.safe_load()` → 0 errores de formato
- hook_cron verificado en `jaraba_agents.module` → correcto (lineas 212-259)
- AiFeedback entity verificada → 4 ficheros correctos (Entity, Controller, AccessControlHandler, ListBuilder)

---

**Cross-refs:** Directrices v78.0.0, Flujo v33.0.0, Indice v103.0.0, Arquitectura: `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md`
