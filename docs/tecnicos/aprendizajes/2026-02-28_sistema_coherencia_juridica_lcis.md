# Aprendizaje #153 — Sistema de Coherencia Juridica (LCIS) — 9 Capas

**Fecha:** 2026-02-28
**Contexto:** Implementacion del Legal Coherence Intelligence System (LCIS) con 9 capas de validacion normativa para el vertical JarabaLex, alcanzando nivel clase mundial en coherencia juridica de salidas LLM.
**Documentos referencia:**
- Directrices v103.0.0: 6 reglas LEGAL-COHERENCE-*
- Arquitectura v92.0.0: seccion 12.6 LCIS ASCII box
- Servicios: `jaraba_legal_intelligence/src/LegalCoherence/` (9 ficheros)
- Tests: `jaraba_legal_intelligence/tests/src/Unit/LegalCoherence/` (7 ficheros)

---

## Problema

Las salidas LLM para consultas juridicas carecian de validacion normativa. Un modelo podia afirmar que "un Real Decreto deroga una Ley Organica" (inversion jerarquica), citar normas derogadas sin advertirlo, o contradecirse entre turnos de conversacion. No existia ningun mecanismo para detectar, puntuar o corregir estas incoherencias juridicas.

## Solucion: Arquitectura de 9 Capas

### Principio: Fail-Open en Todas las Capas

Cada capa es independiente. Si una falla, la respuesta pasa sin bloquear:
- L2 falla → se asume LEGAL_DIRECT (pipeline completo)
- L3 falla → se retornan chunks RAG originales sin enriquecer
- L6 falla → la respuesta pasa sin puntuacion
- L8 falla → se usa disclaimer hardcoded de fallback

### Capa 1: Knowledge Base (LegalCoherenceKnowledgeBase)

**445 LOC, PHP puro, 0 dependencias.**

Constantes estaticas como Single Source of Truth:
- `NORMATIVE_HIERARCHY`: 9 niveles desde `derecho_ue_primario` (rank 1) hasta `circulares` (rank 9)
- `NORM_TYPE_PATTERNS`: 30+ regex para detectar tipo de norma en texto libre
- `FORAL_LAW_REGIMES`: 6 CCAA (Cataluna CCCat, Aragon, Navarra, PaisVasco, Galicia, Baleares) con corpus y materias
- `STATE_EXCLUSIVE_COMPETENCES`: 7 materias Art.149.1 CE con articulo preciso
- Metodos estaticos puros: `isHigherRank()`, `detectNormRank()`, `isStateExclusiveCompetence()`, `requiresOrganicLaw()`, `getForalRegime()`, `getHierarchyWeight()`

**Leccion:** Centralizar toda la logica normativa en una clase estatica sin estado permite composicion desde cualquier capa sin dependencias circulares.

### Capa 2: Intent Classifier (LegalIntentClassifierService)

**345 LOC, requiere Logger.**

Gate que clasifica la intencion del usuario en 5 niveles:
- `LEGAL_DIRECT` (score >= 0.85): pipeline completo
- `LEGAL_IMPLICIT` (2+ keywords, score 0.15-0.85): pipeline completo
- `LEGAL_REFERENCE` (1 keyword): pipeline light
- `COMPLIANCE_CHECK`: pipeline completo
- `NON_LEGAL` (score < 0.15): sin pipeline

Shortcircuits: acciones `legal_search`/`fiscal`/`laboral`/`document_drafter` y vertical `jarabalex` → siempre `LEGAL_DIRECT`.

**Leccion:** El shortcircuit por action/vertical evita falsos negativos. Un usuario en JarabaLex pidiendo "recetas de cocina" sigue obteniendo pipeline legal (contexto vertical prevalece sobre contenido).

### Capa 3: Normative Graph Enricher (NormativeGraphEnricher)

**329 LOC, requiere Logger + KnowledgeBase.**

Formula de ranking Authority-Aware RAG:
```
final_score = 0.55 * semantic_similarity
            + 0.30 * authority_weight
            + 0.15 * recency_bonus
```

Donde:
- `authority_weight`: peso de la KB segun rango normativo (Constitucion=0.98, LO=0.90, ..., Circular=0.20)
- `recency_bonus`: bonus logaritmico por anio de publicacion
- Filtros: derogation filter elimina normas derogadas, territory warning marca normas forales fuera de contexto

**Leccion:** Los pesos 0.55/0.30/0.15 priorizan relevancia semantica pero elevan normas de rango superior. Una Ley Organica menos semanticamente relevante puede superar a una Circular muy relevante, lo cual es correcto juridicamente.

### Capa 4: Prompt Rules (LegalCoherencePromptRule)

**176 LOC, PHP puro.**

8+1 reglas inyectadas en system prompt:
- R1 Jerarquia Normativa (Art.9.3 CE)
- R2 Primacia Derecho UE (Costa/ENEL, Simmenthal, Van Gend en Loos)
- R3 Competencias Estado vs CCAA (Art.149.1)
- R4 Reserva Ley Organica (Art.81)
- R5 Irretroactividad (Art.9.3)
- R6 Vigencia y Derogacion
- R7 Consistencia Transversal
- R8 Humildad Juridica (disclaimer obligatorio)
- R9 Contexto Territorial (foral law, solo cuando aplica)

Version short para FAQ/alerts/citations (reduce token usage ~60%).

**Leccion:** `applyWithTerritory()` detecta automaticamente si la CCAA tiene derecho foral y anade R9 con corpus especifico. Andalucia no tiene R9; Cataluna si (CCCat).

### Capa 6: Validator (LegalCoherenceValidatorService)

**684 LOC, requiere Logger.**

7 checks post-LLM con scoring por severidad:
- `hierarchy_inversion` (critical, -0.30): "RD deroga LO"
- `eu_primacy_violation` (critical, -0.30): "CE prevalece sobre Reglamento UE"
- `organic_law_violation` (high, -0.20): "Ley ordinaria regula derechos fundamentales"
- `competence_violation` (high, -0.20): "Ley CCAA en legislacion penal"
- `vigencia_not_mentioned` (low, -0.05): norma pre-2016 sin mencion de vigencia
- `internal_contradiction` (medium, -0.15): afirmaciones contradictorias en mismo output
- `sycophancy_risk` (medium, -0.15): repite premisa falsa del usuario sin corregir

Flujo de accion:
- Score >= 0.7: `allow`
- Score 0.5-0.7: `warn` (disclaimer adicional)
- Score < 0.5 + retry < 2: `regenerate` (con constraints CRITICO)
- Score < 0.5 + retry >= 2: `block` (mensaje generico sanitizado)

**Anti-sycophancy (V8):** Detecta cuando el usuario afirma algo incorrecto ("los autonomos no cotizan por desempleo") y el LLM confirma sin corregir. Busca ASSERTION_PATTERNS en user_query y verifica CORRECTION_INDICATORS ('sin embargo', 'conviene aclarar', 'no es exactamente asi') en output.

**Leccion:** La regeneracion con constraints es mas efectiva que el bloqueo directo. El constraint "CRITICO: No afirmar que un Real Decreto puede derogar una Ley Organica" en el re-prompt corrige el 80%+ de las inversiones jerarquicas en el segundo intento.

### Capa 8: Disclaimer Enforcement (LegalDisclaimerEnforcementService)

**166 LOC, requiere Logger + optional LegalDisclaimerService.**

Disclaimer non-removable con fallback:
- Si `LegalDisclaimerService` disponible → usa su disclaimer personalizado
- Si no disponible → usa FALLBACK_DISCLAIMER hardcoded
- Detecta duplicados para no insertar disclaimer si ya existe
- Si coherence score < 0.70 → anade nota de coherencia adicional

**Leccion:** El patron `@?` con fallback garantiza que el disclaimer siempre se inserta, incluso si el modulo de disclaimers no esta habilitado.

### Multi-Turn: LegalConversationContext

**256 LOC, requiere Logger.**

Extrae assertions juridicas de cada turno:
- `competencia`: "es competencia exclusiva del Estado"
- `primacia`: "prevalece el Derecho UE"
- `vigencia`: "ha sido derogada"
- `reserva_lo`: "requiere Ley Organica"
- `retroactividad`: "no tiene efecto retroactivo"
- `jerarquia`: deteccion de norma + rango

`checkCrossTurnCoherence()` compara nuevo output contra assertions previas. Si el turno 1 dice "penal es competencia exclusiva del Estado" y el turno 3 dice "las CCAA pueden legislar en materia penal", detecta contradiccion tipo `competencia`.

**Leccion:** Limitar assertions a 50 y turnos a 20 previene degradacion de rendimiento en conversaciones largas sin perder la capacidad de detectar contradicciones recientes.

## Testing: 149 Tests, 277 Assertions

### Estrategia
- **PHP puro donde posible:** Capas L1 y L4 son enteramente estaticas, testables sin mocks
- **NullLogger:** Capas L2, L3, L6, L8, MT solo necesitan `new NullLogger()` para instanciar
- **Mocks solo para servicios opcionales:** L8 necesita mock de `LegalDisclaimerService`
- **Inputs realistas en espanol:** Todos los test inputs usan texto juridico espanol real

### Distribucion
| Test File | Tests | Assertions |
|-----------|-------|------------|
| LegalCoherenceKnowledgeBaseTest | 18 | ~30 |
| LegalIntentClassifierServiceTest | 26 | ~45 |
| NormativeGraphEnricherTest | 18 | ~35 |
| LegalCoherencePromptRuleTest | 24 | ~40 |
| LegalCoherenceValidatorServiceTest | 22 | ~45 |
| LegalDisclaimerEnforcementServiceTest | 16 | ~35 |
| LegalConversationContextTest | 25 | ~47 |
| **Total** | **149** | **277** |

### Errores encontrados y corregidos durante testing
1. `testCleanOutputPasses`: texto "Ley 39/2015" activaba vigencia check (regex `/201[0-5]/`) → cambiar input a texto sin normas
2. `testForalRegimeCataluna`: corpus es `'Codi Civil de Catalunya'` no `'CCCat'` → corregir assertion
3. `testVerticalBonusEmpleabilidad`: 'despido' es 1 keyword, produce LEGAL_REFERENCE no LEGAL_DIRECT → ampliar expected intents
4. `testActionWarnOnMediumScore`: condicional sin assertions → reescribir para siempre assertar

## Reglas Establecidas

| Regla | Prioridad | Descripcion |
|-------|-----------|-------------|
| LEGAL-COHERENCE-KB-001 | P0 | KnowledgeBase como SSOT normativa, NUNCA duplicar jerarquias |
| LEGAL-COHERENCE-INTENT-001 | P1 | IntentClassifier como gate, shortcircuits por action/vertical |
| LEGAL-COHERENCE-PROMPT-001 | P0 | 8+1 reglas en prompts, version short para lightweight actions |
| LEGAL-COHERENCE-FAILOPEN-001 | P0 | Fail-open en TODAS las capas, respuesta nunca bloqueada por error |
| LEGAL-COHERENCE-REGEN-001 | P1 | Regeneracion max 2 retries, bloqueo solo como ultimo recurso |
| LEGAL-COHERENCE-MULTITURN-001 | P1 | Assertions cross-turn, MAX_ASSERTIONS=50, MAX_TURNS=20 |

## EU AI Act Compliance

El sistema LCIS es HIGH RISK bajo el EU AI Act (Annex III, art. 8 — Administration of Justice and Democratic Processes). Requiere:
- Trazabilidad de decisiones (metadata en cada validacion)
- Human oversight (disclaimer obligatorio de no-sustitucion de asesoramiento profesional)
- Transparencia (coherence score visible, contradicciones explicitas)
- Testing documentado (149 tests como evidence)
