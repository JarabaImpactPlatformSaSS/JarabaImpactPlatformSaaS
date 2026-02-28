# Sistema de Coherencia y Consistencia Juridica de la IA — Especificacion Tecnica v2.0.0

**Fecha:** 2026-02-28
**Autor:** Claude Code (Opus 4.6)
**Estado:** Especificacion tecnica para implementacion
**Version:** 3.0.0 (v2 + auditoria Gap Analysis doc 179d: Anti-Sycophancy, Misgrounding per-citation, Feedback Loop, Confidence Calibration, Multi-turn Coherence, Antinomy Detection, Derecho Foral, JLB 500+)
**Impacto:** Transversal (todos los verticales) + JarabaLex (foco primario)
**Clasificacion EU AI Act:** HIGH RISK (Annex III, 8 — Administration of Justice) — confirmado por auditoria G01
**Fuentes:** Especificacion propia + doc 179_Platform_Legal_Coherence_Intelligence_System_v1 + doc 179d_Auditoria_LCIS_Gap_Analysis_v1

---

## 1. Contexto y Analisis del Gap

### 1.1 Problema Identificado

El SaaS dispone de un stack de seguridad IA maduro (100/100 en auditoria):
- `AIGuardrailsService` — PII, jailbreak, rate limiting
- `ConstitutionalGuardrailService` — 5 reglas inmutables (identidad, PII, tenant, contenido danino, autorizacion)
- `VerifierAgentService` — Verificacion pre-entrega con LLM
- `AiRiskClassificationService` — Clasificacion EU AI Act
- `AIIdentityRule` — Identidad de marca

**PERO** ninguno de estos sistemas valida la **coherencia juridica** de las respuestas. El LLM puede:

1. **Afirmar que una Orden Ministerial deroga una Ley Organica** (violacion de jerarquia normativa)
2. **Atribuir competencias exclusivas del Estado a una CCAA** (violacion de ambito competencial)
3. **Citar una norma derogada como vigente** sin advertencia (inconsistencia temporal)
4. **Aplicar derecho autonomico cuando rige norma estatal** o viceversa (conflicto territorial)
5. **Ignorar la primacia del Derecho UE** sobre el ordenamiento interno
6. **Confundir reserva de ley organica con ley ordinaria** (violacion reserva de ley)
7. **Aplicar norma retroactivamente** donde esta prohibido por Art. 9.3 CE
8. **Contradecirse entre respuestas** sobre el mismo principio juridico (inconsistencia transversal)

### 1.2 Impacto de Mercado

En JarabaLex, una respuesta juridicamente incoherente:
- Destruye la confianza del profesional juridico (usuario target)
- Genera responsabilidad legal potencial para el SaaS
- Diferenciadores negativos frente a competidores (Aranzadi, vLex, Lefebvre)
- Viola requisitos EU AI Act Art. 9 (sistema de gestion de riesgos para IA de alto riesgo)

### 1.3 Alcance de la Solucion

**Transversal:** Todos los agentes que tocan tematica legal:
- `SmartLegalCopilotAgent` (8 modos)
- `CopilotOrchestratorService` modos `fiscal`, `laboral`
- `SmartEmployabilityCopilotAgent` (derecho laboral)
- `SmartSalesCopilotAgent` (contratos)
- Cualquier agente invocado via RAG con contexto normativo

**Foco primario:** Vertical JarabaLex — `jaraba_legal_intelligence`, `jaraba_legal_knowledge`, `jaraba_legal_cases`, `jaraba_legal_templates`

---

## 2. Arquitectura del Sistema — 9 Capas

La arquitectura combina nuestra integracion nativa con el pipeline existente de agentes (SmartBaseAgent, ConstitutionalGuardrailService, VerifierAgentService) con los conceptos de mas alto valor del LCIS (doc 179): Intent Classifier, Normative Graph RAG, Authority-Aware Ranking, NLI Entailment, y Disclaimer Enforcement.

```
┌─────────────────────────────────────────────────────────────────────┐
│                     CAPA 9: BENCHMARK SUITE                         │
│  AgentBenchmarkService + 50+ Legal Coherence Test Cases (offline)   │
├─────────────────────────────────────────────────────────────────────┤
│             CAPA 8: DISCLAIMER & CITATION ENFORCEMENT               │
│  LegalDisclaimerEnforcementService — No-eliminable, per-tenant      │
├─────────────────────────────────────────────────────────────────────┤
│                CAPA 7: COHERENCE VERIFIER (LLM)                     │
│  LegalCoherenceVerifierService — Premium tier + NLI Entailment      │
├─────────────────────────────────────────────────────────────────────┤
│             CAPA 6: COHERENCE VALIDATOR (Determinista)               │
│  LegalCoherenceValidatorService — Regex + reglas + Regeneracion     │
├─────────────────────────────────────────────────────────────────────┤
│             CAPA 5: CONSTITUTIONAL RULE (Inmutable)                  │
│  Regla 'legal_coherence' en ConstitutionalGuardrailService          │
├─────────────────────────────────────────────────────────────────────┤
│              CAPA 4: PROMPT RULE (Inyeccion)                         │
│  LegalCoherencePromptRule::apply() + Territory Context              │
├─────────────────────────────────────────────────────────────────────┤
│        CAPA 3: AUTHORITY-AWARE NORMATIVE GRAPH RAG                   │
│  NormativeGraphEnricher — Hierarchy weighting + Lex Specialis       │
├─────────────────────────────────────────────────────────────────────┤
│        CAPA 2: LEGAL INTENT CLASSIFIER (Pre-filtro)                  │
│  LegalIntentClassifierService — Activa/desactiva pipeline           │
├─────────────────────────────────────────────────────────────────────┤
│        CAPA 1: KNOWLEDGE BASE (Fuente de Verdad)                     │
│  LegalCoherenceKnowledgeBase — Jerarquia + Competencias + Weights   │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.1 Flujo de Integracion en Pipeline Existente

```
Usuario pregunta (cualquier vertical)
    │
    ▼
AIGuardrailsService::validate()                ← INPUT (existente)
    │
    ▼
[LegalIntentClassifierService::classify()]     ← CAPA 2 (NUEVO — pre-filtro transversal)
    │                                             Si NON_LEGAL → bypass todo el pipeline LCIS
    ▼
AIGuardrailsService::sanitizeRagContent()      ← INTERMEDIATE (existente)
    │
    ▼
[NormativeGraphEnricher::enrichRetrieval()]    ← CAPA 3 (NUEVO — authority-aware ranking)
    │                                             Reordena RAG results por jerarquia+competencia
    ▼
[LegalCoherencePromptRule::apply()]            ← CAPA 4 (NUEVO — system prompt + territory)
    │
    ▼
LLM genera respuesta
    │
    ▼
AIGuardrailsService::maskOutputPII()           ← OUTPUT (existente)
    │
    ▼
ConstitutionalGuardrailService::enforce()      ← CONSTITUTIONAL (existente + CAPA 5 nueva regla)
    │
    ▼
[LegalCoherenceValidatorService::validate()]   ← CAPA 6 (NUEVO — determinista + regeneracion)
    │                                             Si BLOCK → regenerar con constraints (max 2)
    ▼
[LegalCoherenceVerifierService::verify()]      ← CAPA 7 (NUEVO — LLM premium + NLI per-claim)
    │
    ▼
[LegalDisclaimerEnforcementService::enforce()] ← CAPA 8 (NUEVO — disclaimer obligatorio)
    │
    ▼
VerifierAgentService::verify()                 ← VERIFIER (existente, validacion generica final)
    │
    ▼
Respuesta entregada al usuario
    (con Legal Coherence Score visible si score < umbral)
```

**Punto de integracion clave:** `SmartBaseAgent::execute()` linea de `applyVerification()`. Las capas 2-8 se orquestan en `applyLegalCoherence()` que se invoca ANTES del `VerifierAgentService` existente.

**Principio de activacion:** La Capa 2 (Intent Classifier) actua como gate. Si la query NO tiene componente legal, NINGUNA de las capas 3-8 se ejecuta. Esto garantiza zero overhead para queries no juridicas.

---

## 2A. CAPA 2 — LegalIntentClassifierService (NUEVO — de doc 179)

### Justificacion

Sin esta capa, un agricultor preguntando "¿que requisitos de etiquetado necesito?" en AgroConecta no activaria el pipeline de coherencia juridica, porque la accion del agente no es `legal_*`. El Intent Classifier detecta componente juridico en CUALQUIER query de CUALQUIER vertical.

### Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalIntentClassifierService.php
```

### Taxonomia de Intents Legales

| Intent | Descripcion | Ejemplo | Activa LCIS |
|--------|-------------|---------|-------------|
| `LEGAL_DIRECT` | Consulta explicitamente juridica | "¿Que dice la ley sobre despido improcedente?" | SI (completo) |
| `LEGAL_IMPLICIT` | Consulta con implicaciones legales no explicitas | "¿Puedo vender mermelada casera online?" | SI (completo) |
| `LEGAL_REFERENCE` | Mencion tangencial a normativa | "¿Mi tienda necesita licencia?" | SI (parcial — solo Capas 4+8) |
| `COMPLIANCE_CHECK` | Verificacion de cumplimiento | "¿Mi web cumple con RGPD?" | SI (completo) |
| `NON_LEGAL` | Sin componente juridico | "¿Cual es el mejor aceite de oliva?" | NO |

### Especificacion

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Psr\Log\LoggerInterface;

/**
 * Clasificador de intent legal para activacion del pipeline LCIS.
 *
 * Clasificacion hibrida en 2 fases:
 * 1. Keywords (coste cero, instantaneo) — resuelve ~80% de queries.
 * 2. LLM fast tier — solo para zona gris (score 0.15-0.85).
 *
 * Integrado como gate en SmartBaseAgent::applyLegalCoherence().
 * Si classify() retorna NON_LEGAL, todo el pipeline LCIS se bypasea.
 *
 * @see LegalCoherencePromptRule::requiresCoherence() (complementario)
 */
class LegalIntentClassifierService {

  /**
   * Keywords que indican intent legal directo.
   * Score > 0.85 = LEGAL_DIRECT sin necesidad de LLM.
   */
  private const LEGAL_KEYWORDS = [
    // Normativa.
    'ley', 'decreto', 'reglamento', 'normativa', 'regulacion', 'legislacion',
    'articulo', 'disposicion', 'BOE', 'BOJA', 'ordenanza',
    // Procedimientos.
    'recurso', 'demanda', 'denuncia', 'sancion', 'multa', 'infraccion',
    'plazo legal', 'prescripcion', 'caducidad', 'procedimiento administrativo',
    // Derechos.
    'derecho', 'obligacion', 'responsabilidad', 'indemnizacion', 'despido',
    'contrato', 'clausula', 'garantia legal', 'proteccion de datos', 'RGPD',
    // Instituciones.
    'tribunal', 'juzgado', 'inspeccion', 'hacienda', 'seguridad social',
    'registro mercantil', 'notario', 'procurador',
    // Compliance.
    'cumplimiento', 'requisitos legales', 'licencia', 'permiso', 'autorizacion',
    'homologacion', 'certificacion obligatoria',
  ];

  /**
   * Keywords que indican intent NO legal.
   * Score < 0.15 = NON_LEGAL sin necesidad de LLM.
   */
  private const NON_LEGAL_KEYWORDS = [
    'precio', 'mejor', 'receta', 'opinion', 'recomendacion personal',
    'tutorial', 'como funciona la app', 'horario', 'direccion', 'telefono',
  ];

  /**
   * Mapa vertical → areas juridicas tipicas.
   * Reduce la zona gris para queries especificas de vertical.
   */
  private const VERTICAL_LEGAL_AREAS = [
    'empleabilidad' => ['laboral', 'seguridad social', 'formacion', 'empleo', 'despido', 'convenio'],
    'emprendimiento' => ['mercantil', 'fiscal', 'tributario', 'subvenciones', 'autonomo', 'sociedad'],
    'agroconecta' => ['agroalimentario', 'etiquetado', 'denominacion origen', 'PAC', 'fitosanitario'],
    'comercioconecta' => ['consumo', 'comercio electronico', 'LSSI', 'devolucion', 'garantia'],
    'serviciosconecta' => ['colegio profesional', 'deontologia', 'eIDAS', 'firma electronica'],
    'jarabalex' => [], // TODO JarabaLex siempre es legal.
  ];

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Clasifica el intent legal de una query.
   *
   * @param string $query
   *   La consulta del usuario.
   * @param string $vertical
   *   El vertical activo.
   * @param string $action
   *   La accion del agente (si ya es legal_*, shortcircuit).
   *
   * @return array{intent: string, score: float, areas: array}
   */
  public function classify(string $query, string $vertical = '', string $action = ''): array {
    // Shortcircuit 1: Accion ya clasificada como legal.
    if (str_starts_with($action, 'legal_') || $action === 'fiscal' || $action === 'laboral') {
      return ['intent' => 'LEGAL_DIRECT', 'score' => 1.0, 'areas' => [$action]];
    }

    // Shortcircuit 2: Vertical JarabaLex siempre es legal.
    if ($vertical === 'jarabalex') {
      return ['intent' => 'LEGAL_DIRECT', 'score' => 1.0, 'areas' => ['jarabalex']];
    }

    // Fase 1: Keyword scoring (zero-cost).
    $queryLower = mb_strtolower($query);
    $legalScore = 0;
    $detectedAreas = [];

    foreach (self::LEGAL_KEYWORDS as $kw) {
      if (str_contains($queryLower, mb_strtolower($kw))) {
        $legalScore += 0.2;
      }
    }

    // Bonus por keywords de vertical.
    if (!empty(self::VERTICAL_LEGAL_AREAS[$vertical])) {
      foreach (self::VERTICAL_LEGAL_AREAS[$vertical] as $area) {
        if (str_contains($queryLower, mb_strtolower($area))) {
          $legalScore += 0.3;
          $detectedAreas[] = $area;
        }
      }
    }

    // Penalizacion por keywords no-legales.
    foreach (self::NON_LEGAL_KEYWORDS as $kw) {
      if (str_contains($queryLower, mb_strtolower($kw))) {
        $legalScore -= 0.15;
      }
    }

    $legalScore = max(0.0, min(1.0, $legalScore));

    // Resolucion rapida.
    if ($legalScore > 0.85) {
      return ['intent' => 'LEGAL_DIRECT', 'score' => $legalScore, 'areas' => $detectedAreas];
    }
    if ($legalScore < 0.15) {
      return ['intent' => 'NON_LEGAL', 'score' => 1.0 - $legalScore, 'areas' => []];
    }

    // Fase 2: Zona gris — para v2 se anade LLM fast tier.
    // En v1, clasificamos como LEGAL_IMPLICIT si hay alguna keyword legal.
    if ($legalScore >= 0.15) {
      return ['intent' => 'LEGAL_IMPLICIT', 'score' => $legalScore, 'areas' => $detectedAreas];
    }

    return ['intent' => 'NON_LEGAL', 'score' => 1.0, 'areas' => []];
  }

  /**
   * Verifica si el resultado requiere activacion completa del LCIS.
   */
  public static function requiresFullPipeline(array $classification): bool {
    return in_array($classification['intent'], ['LEGAL_DIRECT', 'LEGAL_IMPLICIT', 'COMPLIANCE_CHECK'], TRUE);
  }

  /**
   * Verifica si requiere al menos disclaimers.
   */
  public static function requiresDisclaimer(array $classification): bool {
    return $classification['intent'] !== 'NON_LEGAL';
  }

}
```

---

## 2B. CAPA 3 — NormativeGraphEnricher (NUEVO — de doc 179)

### Justificacion

El RAG plano (flat vector search) recupera normas por similitud semantica pero ignora jerarquia, vigencia y competencia. El doc 179 identifica correctamente que esto es un fallo estructural: "un sistema RAG convencional plano es fundamentalmente incapaz de responder consultas que requieren navegacion jerarquica del ordenamiento juridico". La solucion es Authority-Aware Ranking que pondera jerarquia + semantica + recencia.

### Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/NormativeGraphEnricher.php
```

### Especificacion

Este servicio NO reemplaza el RAG existente (`LegalSearchService`, `LegalRagService`). Se **interpone** como enricher post-retrieval que reordena y enriquece los resultados antes de inyectarlos en el prompt.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enriquecedor de resultados RAG con consciencia jerarquica.
 *
 * Se interpone entre la busqueda semantica existente (LegalSearchService,
 * LegalRagService) y la inyeccion de contexto en el prompt.
 *
 * Tres funciones:
 * 1. Authority-Aware Ranking: repondera resultados por jerarquia + competencia.
 * 2. Vigencia filter: elimina normas derogadas del contexto.
 * 3. Lex Specialis resolution: prioriza norma especial sobre general.
 *
 * NO modifica la busqueda semantica. Solo reordena y filtra DESPUES.
 *
 * Patron de integracion:
 * - Llamado desde SmartLegalCopilotAgent::buildModePrompt() post-RAG.
 * - Llamado desde LegalRagService::query() post-search.
 *
 * NOTA: El Knowledge Graph completo con entidad legal_norm_relation
 * (derogaciones, modificaciones, transposiciones) se implementara en
 * una fase posterior. En v1, usamos metadatos de LegalResolution y
 * LegalNorm existentes para detectar jerarquia y vigencia.
 */
class NormativeGraphEnricher {

  /**
   * Pesos para Authority-Aware Ranking.
   * Fuente: doc 179 LCIS, seccion 3.3.
   */
  private const WEIGHT_SEMANTIC = 0.55;
  private const WEIGHT_AUTHORITY = 0.30;
  private const WEIGHT_RECENCY = 0.15;

  /**
   * Hierarchy weights con resolucion dinamica de competencia.
   *
   * NOTA COMPETENCIAL (doc 179): El weight de ley autonomica es
   * SUPERIOR al de reglamento estatal en materias de competencia
   * exclusiva autonomica. Resolucion dinamica en getHierarchyWeight().
   */
  private const HIERARCHY_WEIGHTS = [
    'derecho_ue_primario' => 1.00,
    'derecho_ue_derivado' => 0.95,
    'constitucion' => 0.98,
    'ley_organica' => 0.93,
    'ley_ordinaria' => 0.88,
    'reglamento_estatal' => 0.78,
    'ley_autonomica' => 0.83,
    'reglamento_autonomico' => 0.68,
    'normativa_local' => 0.58,
  ];

  /**
   * Bonus de peso para ley autonomica en competencia exclusiva CCAA.
   */
  private const CCAA_EXCLUSIVE_BONUS = 0.12;

  public function __construct(
    protected readonly ?EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Enriquece y reordena resultados RAG con consciencia jerarquica.
   *
   * @param array $ragResults
   *   Resultados de busqueda semantica (de Qdrant).
   *   Cada item: ['score' => float, 'payload' => [...], ...]
   * @param array $context
   *   Contexto: territory (CCAA del tenant), subject_areas, query_date.
   *
   * @return array
   *   Resultados reordenados con final_score y metadata enriquecida.
   */
  public function enrichRetrieval(array $ragResults, array $context = []): array {
    $territory = $context['territory'] ?? '';
    $subjectAreas = $context['subject_areas'] ?? [];
    $queryDate = $context['query_date'] ?? date('Y-m-d');

    $enriched = [];
    foreach ($ragResults as $result) {
      $payload = $result['payload'] ?? [];

      // 1. Filtro de vigencia: eliminar normas derogadas.
      $status = $payload['status_legal'] ?? $payload['status'] ?? 'vigente';
      if ($status === 'derogada_total' || $status === 'derogada') {
        continue;
      }

      // 2. Detectar rango jerarquico.
      $normType = $payload['norm_type'] ?? NULL;
      $normTitle = $payload['norm_title'] ?? $payload['title'] ?? '';
      if (!$normType) {
        $normType = LegalCoherenceKnowledgeBase::detectNormRank($normTitle);
      }

      // 3. Calcular authority weight.
      $authorityWeight = $this->getHierarchyWeight(
        $normType, $territory, $subjectAreas
      );

      // 4. Calcular recency bonus.
      $publicationDate = $payload['publication_date'] ?? $payload['date'] ?? '';
      $recencyBonus = $this->calculateRecencyBonus($publicationDate, $queryDate);

      // 5. Authority-Aware final score.
      $semanticScore = $result['score'] ?? 0.0;
      $finalScore =
        ($semanticScore * self::WEIGHT_SEMANTIC)
        + ($authorityWeight * self::WEIGHT_AUTHORITY)
        + ($recencyBonus * self::WEIGHT_RECENCY);

      $result['final_score'] = round($finalScore, 4);
      $result['authority_weight'] = $authorityWeight;
      $result['norm_type_detected'] = $normType;
      $result['recency_bonus'] = $recencyBonus;
      $result['vigencia_status'] = $status;

      // 6. Anotar derogacion parcial si aplica.
      if ($status === 'derogada_parcial' || $status === 'parcialmente_derogada') {
        $result['derogation_warning'] = 'Esta norma tiene articulos derogados. Verificar vigencia del articulo concreto.';
      }

      // 7. Anotar si es norma de otra CCAA (warning territorial).
      $normTerritory = $payload['autonomous_community'] ?? $payload['territory'] ?? '';
      if ($normTerritory && $territory && mb_strtolower($normTerritory) !== mb_strtolower($territory)) {
        $result['territory_warning'] = sprintf(
          'Norma de %s aplicada a consulta de %s. Verificar aplicabilidad territorial.',
          $normTerritory, $territory
        );
      }

      $enriched[] = $result;
    }

    // Reordenar por final_score descendente.
    usort($enriched, fn($a, $b) => ($b['final_score'] ?? 0) <=> ($a['final_score'] ?? 0));

    return $enriched;
  }

  /**
   * Calcula el peso jerarquico con resolucion dinamica de competencia.
   */
  protected function getHierarchyWeight(
    ?string $normType,
    string $territory,
    array $subjectAreas,
  ): float {
    if (!$normType || !isset(self::HIERARCHY_WEIGHTS[$normType])) {
      return 0.50; // Default medio para normas no clasificadas.
    }

    $weight = self::HIERARCHY_WEIGHTS[$normType];

    // Resolucion dinamica: ley autonomica gana bonus en competencia exclusiva CCAA.
    if ($normType === 'ley_autonomica' && !empty($subjectAreas)) {
      $isExclusiveCcaa = $this->isExclusiveCcaaCompetence($subjectAreas);
      if ($isExclusiveCcaa) {
        $weight += self::CCAA_EXCLUSIVE_BONUS;
      }
    }

    return min(1.0, $weight);
  }

  /**
   * Determina si las areas tematicas son competencia exclusiva CCAA.
   */
  protected function isExclusiveCcaaCompetence(array $subjectAreas): bool {
    $ccaaExclusive = [
      'urbanismo', 'vivienda', 'comercio_interior', 'artesania',
      'turismo', 'deporte', 'asistencia_social', 'sanidad',
    ];
    foreach ($subjectAreas as $area) {
      if (in_array(mb_strtolower($area), $ccaaExclusive, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Calcula bonus de recencia (norma mas reciente = mas relevante).
   */
  protected function calculateRecencyBonus(string $date, string $queryDate): float {
    if (empty($date)) {
      return 0.3; // Default neutro.
    }
    try {
      $pub = new \DateTime($date);
      $query = new \DateTime($queryDate);
      $diff = $query->diff($pub);
      $years = $diff->y + ($diff->m / 12);

      if ($years <= 1) return 1.0;
      if ($years <= 3) return 0.85;
      if ($years <= 5) return 0.70;
      if ($years <= 10) return 0.50;
      if ($years <= 20) return 0.30;
      return 0.15;
    }
    catch (\Exception) {
      return 0.3;
    }
  }

}
```

### Integracion con RAG Existente

En `LegalRagService::query()`, despues del paso 1 (busqueda Qdrant) y antes del paso 3 (LLM):

```php
// Paso 1.5: Authority-Aware Ranking (CAPA 3 LCIS).
if (\Drupal::hasService('jaraba_legal_intelligence.normative_graph_enricher')) {
  $enricher = \Drupal::service('jaraba_legal_intelligence.normative_graph_enricher');
  $searchResults = $enricher->enrichRetrieval($searchResults, [
    'territory' => $context['territory'] ?? '',
    'subject_areas' => $filters['subject_areas'] ?? [],
  ]);
}
```

En `SmartLegalCopilotAgent::buildModePrompt()`, despues del RAG context:

```php
// Enriquecer RAG context con jerarquia si disponible.
if ($ragContext && \Drupal::hasService('jaraba_legal_intelligence.normative_graph_enricher')) {
  // El enricher ya reordeno los resultados — anotar en prompt.
  $prompt .= "\n\nNOTA: Las fuentes normativas estan ordenadas por autoridad jerarquica y vigencia.";
}
```

---

## 2C. CAPA 8 — LegalDisclaimerEnforcementService (NUEVO — de doc 179)

### Justificacion

El doc 179 identifica correctamente que el disclaimer legal es la ultima linea de defensa de responsabilidad de la plataforma. Debe ser:
1. **Obligatorio** en TODA respuesta con contenido juridico.
2. **No-eliminable** — un tenant NO puede desactivarlo (compliance EU AI Act Art. 50).
3. **Personalizable** en texto (un despacho puede ajustar la redaccion) pero NO eliminable.

El `LegalDisclaimerService` existente ya tiene 3 niveles (standard, enhanced, critical) pero NO tiene enforcement de no-eliminacion.

### Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalDisclaimerEnforcementService.php
```

### Especificacion

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\jaraba_legal_knowledge\Service\LegalDisclaimerService;
use Psr\Log\LoggerInterface;

/**
 * Enforcement de disclaimer legal obligatorio en outputs de IA.
 *
 * Post-procesamiento final que garantiza:
 * 1. Toda respuesta legal contiene disclaimer.
 * 2. El disclaimer NO puede ser eliminado por configuracion de tenant.
 * 3. El Legal Coherence Score se anade si es < umbral.
 *
 * Integrado como ultimo paso del pipeline LCIS, justo antes de
 * VerifierAgentService (que es generico y no verifica disclaimers).
 */
class LegalDisclaimerEnforcementService {

  /**
   * Score threshold para mostrar advertencia visible al usuario.
   */
  private const SCORE_WARNING_THRESHOLD = 70;

  /**
   * Disclaimer fallback si LegalDisclaimerService no esta disponible.
   */
  private const FALLBACK_DISCLAIMER = 'Esta informacion tiene caracter orientativo y no constituye asesoramiento juridico profesional. Para su caso concreto, consulte con un abogado o profesional cualificado.';

  public function __construct(
    protected readonly ?LegalDisclaimerService $disclaimerService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Garantiza que el output contiene disclaimer legal.
   *
   * @param string $output
   *   Respuesta del agente.
   * @param array $coherenceResult
   *   Resultado de las capas 6+7 (score, violations, warnings).
   * @param array $context
   *   Contexto: intent, action, tenant_id.
   *
   * @return string
   *   Output con disclaimer garantizado.
   */
  public function enforce(string $output, array $coherenceResult = [], array $context = []): string {
    // 1. Obtener disclaimer del servicio existente o fallback.
    $disclaimer = $this->getDisclaimer();

    // 2. Verificar si ya contiene disclaimer (evitar duplicados).
    if ($this->containsDisclaimer($output)) {
      // Ya tiene disclaimer — solo anadir coherence score si procede.
      return $this->appendCoherenceScore($output, $coherenceResult);
    }

    // 3. Inyectar disclaimer.
    $output = $this->appendDisclaimer($output, $disclaimer);

    // 4. Anadir Legal Coherence Score si esta por debajo del umbral.
    $output = $this->appendCoherenceScore($output, $coherenceResult);

    return $output;
  }

  /**
   * Obtiene el disclaimer del servicio existente.
   */
  protected function getDisclaimer(): string {
    if ($this->disclaimerService) {
      try {
        $disclaimer = $this->disclaimerService->getDisclaimer();
        if (!empty($disclaimer)) {
          return $disclaimer;
        }
      }
      catch (\Throwable) {
        // Fallback.
      }
    }
    return self::FALLBACK_DISCLAIMER;
  }

  /**
   * Verifica si el output ya contiene un disclaimer.
   */
  protected function containsDisclaimer(string $output): bool {
    $markers = [
      'no constituye asesoramiento',
      'caracter orientativo',
      'consulte con un abogado',
      'consulte con un profesional',
      'no sustituye el criterio profesional',
    ];
    $outputLower = mb_strtolower($output);
    foreach ($markers as $marker) {
      if (str_contains($outputLower, $marker)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Anade disclaimer al final del output.
   */
  protected function appendDisclaimer(string $output, string $disclaimer): string {
    return $output . "\n\n---\n*" . $disclaimer . "*";
  }

  /**
   * Anade Legal Coherence Score visible si esta por debajo del umbral.
   */
  protected function appendCoherenceScore(string $output, array $coherenceResult): string {
    if (empty($coherenceResult)) {
      return $output;
    }

    $score = $coherenceResult['score'] ?? NULL;
    if ($score === NULL) {
      return $output;
    }

    // Convertir 0.0-1.0 a 0-100.
    $score100 = (int) round($score * 100);

    if ($score100 < self::SCORE_WARNING_THRESHOLD) {
      $output .= sprintf(
        "\n\n*Indice de confianza juridica: %d/100. Se recomienda verificar con fuentes oficiales.*",
        $score100
      );
    }

    return $output;
  }

}
```

---

## 2D. Enriquecimientos en Capas Existentes (de doc 179)

### 2D.1 CAPA 1 (Knowledge Base) — Hierarchy Weights

Anadir a `LegalCoherenceKnowledgeBase` constante con pesos decimales (complementa los ranks enteros):

```php
/**
 * Pesos decimales para Authority-Aware Ranking.
 *
 * Fuente: doc 179 LCIS, seccion 2.1.
 * NOTA: ley_autonomica (0.83) < ley_ordinaria (0.88) en general,
 * PERO recibe bonus +0.12 en competencia exclusiva CCAA = 0.95.
 */
public const HIERARCHY_WEIGHTS = [
  'derecho_ue_primario' => 1.00,
  'derecho_ue_derivado' => 0.95,
  'constitucion' => 0.98,
  'ley_organica' => 0.93,
  'ley_ordinaria' => 0.88,
  'reglamento_estatal' => 0.78,
  'ley_autonomica' => 0.83,
  'reglamento_autonomico' => 0.68,
  'normativa_local' => 0.58,
];
```

### 2D.2 CAPA 4 (Prompt Rule) — Territory Context

Enriquecer `LegalCoherencePromptRule::COHERENCE_PROMPT` con placeholder de territorio:

Anadir al final de la seccion R3 (COMPETENCIAS):

```
Si el territorio del usuario es [TERRITORY], prioriza la normativa autonomica
de [TERRITORY] sobre la de otras CCAA. NUNCA apliques normativa autonomica
de una Comunidad Autonoma a otra distinta.
```

Nuevo metodo en `LegalCoherencePromptRule`:

```php
/**
 * Aplica regla con contexto territorial.
 *
 * @param string $systemPrompt
 *   Prompt original.
 * @param string $territory
 *   CCAA del tenant (ej: 'Andalucia', 'Cataluna').
 * @param bool $short
 *   TRUE para version compacta.
 *
 * @return string
 *   Prompt enriquecido.
 */
public static function applyWithTerritory(
  string $systemPrompt,
  string $territory = '',
  bool $short = FALSE,
): string {
  $rule = $short ? self::COHERENCE_PROMPT_SHORT : self::COHERENCE_PROMPT;

  if ($territory) {
    $rule = str_replace('[TERRITORY]', $territory, $rule);
  }
  else {
    // Sin territorio — eliminar referencias a [TERRITORY].
    $rule = preg_replace('/Si el territorio.*\[TERRITORY\].*\n/m', '', $rule);
  }

  return $rule . "\n\n" . $systemPrompt;
}
```

### 2D.3 CAPA 6 (Validator) — Regeneracion con Constraints

Enriquecer `LegalCoherenceValidatorService::validate()` con capacidad de regeneracion. Nuevo parametro `$retryCount` y logica:

```php
/**
 * Valida con soporte de regeneracion.
 *
 * Si la validacion bloquea y retryCount < MAX_RETRIES, retorna
 * constraints adicionales para que el llamador regenere.
 */
public function validate(string $output, array $context = [], int $retryCount = 0): array {
  // ... validacion existente ...

  if ($action === 'block' && $retryCount < self::MAX_RETRIES) {
    $action = 'regenerate';
    $constraints = $this->buildRegenerationConstraints($violations);
    // El llamador (SmartBaseAgent) regenera con estos constraints.
  }

  return [
    // ... campos existentes ...
    'action' => $action, // 'allow', 'warn', 'block', 'regenerate'
    'regeneration_constraints' => $constraints ?? [],
    'retry_count' => $retryCount,
  ];
}

private const MAX_RETRIES = 2;

protected function buildRegenerationConstraints(array $violations): array {
  $constraints = [];
  foreach ($violations as $v) {
    match ($v['type']) {
      'hierarchy_inversion', 'hierarchy_inversion_structural' =>
        $constraints[] = 'CRITICO: No afirmes que una norma de rango inferior deroga o prevalece sobre una de rango superior.',
      'competence_violation' =>
        $constraints[] = 'CRITICO: La materia mencionada es competencia exclusiva del Estado (Art. 149.1 CE). No atribuyas esta regulacion a normativa autonomica.',
      'eu_primacy_violation' =>
        $constraints[] = 'CRITICO: El Derecho de la UE prevalece sobre el derecho interno. No afirmes lo contrario.',
      'organic_law_violation' =>
        $constraints[] = 'CRITICO: Esta materia requiere Ley Organica (Art. 81 CE). No la atribuyas a ley ordinaria ni reglamento.',
      'retroactivity_violation' =>
        $constraints[] = 'CRITICO: Las disposiciones sancionadoras desfavorables no tienen efecto retroactivo (Art. 9.3 CE).',
      default => $constraints[] = 'Revisa la coherencia juridica de tu respuesta: ' . $v['description'],
    };
  }
  return array_unique($constraints);
}
```

### 2D.4 CAPA 7 (Verifier) — NLI Entailment Per-Claim

Enriquecer el prompt del `LegalCoherenceVerifierService` para incluir verificacion NLI per-claim:

Anadir al `getVerifierSystemPrompt()`:

```
Ademas de los 7 criterios, realiza un NLI ENTAILMENT CHECK:
Para cada afirmacion juridica en la respuesta, verifica si esta
SOPORTADA (entailed), CONTRADICHA (contradiction), o NO SOPORTADA
(neutral) por el contexto normativo de la consulta.

En el JSON de respuesta, anade un campo "entailment_checks":
[{"claim": "texto de la afirmacion", "verdict": "entailed|contradiction|neutral", "evidence": "norma que soporta/contradice"}]
```

### 2D.5 CAPA 9 (Benchmark) — Expansion a 50+ tests

Expandir los test cases de 26 a 52 para cubrir las areas del doc 179:

Anadir categorias:
- **lex_specialis** (LS-01..LS-04): norma especial vs general
- **territory** (TE-01..TE-04): normativa autonomica correcta por CCAA
- **disclaimer** (DI-01..DI-03): presencia obligatoria de disclaimer
- **intent_classification** (IC-01..IC-05): clasificacion correcta de intent legal
- **vigencia_extended** (VD-04..VD-06): normas pendientes, transitorias, consolidadas
- **regeneration** (RG-01..RG-02): verificar que regeneracion corrige violaciones
- **nli_entailment** (NL-01..NL-02): claims no soportados por contexto

---

## 3. CAPA 1 — LegalCoherenceKnowledgeBase

### 3.1 Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalCoherenceKnowledgeBase.php
```

### 3.2 Especificacion

Clase `final` con constantes PHP inmutables. **Zero-cost**, sin LLM, sin BD, sin configuracion.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

/**
 * Fuente de verdad inmutable para coherencia juridica.
 *
 * Codifica los principios generales del derecho espanol y europeo
 * como estructuras PHP constantes. Usado por LegalCoherenceValidatorService
 * y LegalCoherenceVerifierService para validar respuestas de IA.
 *
 * Fuentes juridicas:
 * - Constitucion Espanola de 1978, Arts. 9.3, 81, 82, 86, 148, 149, 150, 161
 * - Codigo Civil Art. 1.1-1.7 (fuentes del derecho)
 * - TFUE Arts. 288, 291
 * - TJUE: Costa v. ENEL (6/64), Van Gend en Loos (26/62), Simmenthal (106/77)
 * - Ley 39/2015 LPACAP
 * - Ley 40/2015 LRJSP
 *
 * LEGAL-COHERENCE-KB-001: Esta clase SOLO contiene conocimiento juridico
 * estructural (jerarquia, competencias, principios). NUNCA contenido
 * sustantivo de normas concretas (eso es dominio del RAG).
 */
final class LegalCoherenceKnowledgeBase {

  /**
   * Jerarquia normativa del ordenamiento juridico espanol.
   *
   * Art. 9.3 CE: "La Constitucion garantiza el principio de legalidad,
   * la jerarquia normativa..."
   *
   * Cada entrada: [rank, label, description, example_patterns[]]
   * Rank menor = rango superior. Las normas de rango inferior NO pueden
   * contradecir las de rango superior.
   */
  public const NORMATIVE_HIERARCHY = [
    'derecho_ue_primario' => [
      'rank' => 1,
      'label' => 'Derecho UE Primario',
      'description' => 'Tratados constitutivos (TUE, TFUE, CDFUE). Primacia absoluta sobre derecho interno.',
      'examples' => ['TFUE', 'TUE', 'Carta de Derechos Fundamentales', 'Tratado de Lisboa', 'Tratado de Funcionamiento'],
    ],
    'derecho_ue_derivado' => [
      'rank' => 2,
      'label' => 'Derecho UE Derivado',
      'description' => 'Reglamentos (efecto directo), Directivas (transposicion), Decisiones. Art. 288 TFUE.',
      'examples' => ['Reglamento UE', 'Reglamento (UE)', 'Directiva UE', 'Directiva (UE)', 'Decision UE'],
    ],
    'constitucion' => [
      'rank' => 3,
      'label' => 'Constitucion Espanola',
      'description' => 'Norma suprema del ordenamiento interno. Art. 9.1 CE: vincula ciudadanos y poderes publicos.',
      'examples' => ['Constitucion', 'CE', 'Constitucion Espanola'],
    ],
    'ley_organica' => [
      'rank' => 4,
      'label' => 'Ley Organica',
      'description' => 'Art. 81 CE: derechos fundamentales, Estatutos de Autonomia, regimen electoral, instituciones del Estado. Mayoria absoluta del Congreso.',
      'examples' => ['Ley Organica', 'LO ', 'L.O.'],
    ],
    'ley_ordinaria' => [
      'rank' => 5,
      'label' => 'Ley Ordinaria / Decreto-Ley / Decreto Legislativo',
      'description' => 'Leyes ordinarias (mayoria simple), RDL (Art. 86 CE, urgencia), RDLeg (Art. 82-85 CE, delegacion legislativa).',
      'examples' => ['Ley ', 'Real Decreto-ley', 'Real Decreto Legislativo', 'RDL ', 'RDLeg'],
    ],
    'reglamento_estatal' => [
      'rank' => 6,
      'label' => 'Reglamento Estatal',
      'description' => 'Real Decreto (Consejo de Ministros), Orden Ministerial, Resolucion, Instruccion, Circular.',
      'examples' => ['Real Decreto ', 'Orden ', 'Orden Ministerial', 'Resolucion de ', 'Instruccion de ', 'Circular '],
    ],
    'ley_autonomica' => [
      'rank' => 7,
      'label' => 'Ley Autonomica',
      'description' => 'Leyes aprobadas por los Parlamentos autonomicos en materias de competencia autonómica.',
      'examples' => ['Ley de Andalucia', 'Ley de Cataluna', 'Ley de Madrid', 'Ley del Pais Vasco', 'Ley de la Comunidad'],
    ],
    'reglamento_autonomico' => [
      'rank' => 8,
      'label' => 'Reglamento Autonomico',
      'description' => 'Decretos y ordenes de los Gobiernos autonomicos.',
      'examples' => ['Decreto de la Junta', 'Decreto del Gobierno', 'Orden de la Consejeria'],
    ],
    'normativa_local' => [
      'rank' => 9,
      'label' => 'Normativa Local',
      'description' => 'Ordenanzas municipales, bandos, reglamentos locales. Potestad reglamentaria local (Art. 137, 140 CE).',
      'examples' => ['Ordenanza municipal', 'Ordenanza de ', 'Reglamento municipal', 'Bando'],
    ],
  ];

  /**
   * Competencias exclusivas del Estado (Art. 149.1 CE).
   *
   * Lista parcial de las 32 materias del Art. 149.1. Estas materias
   * NO pueden ser reguladas por norma autonomica principal.
   */
  public const STATE_EXCLUSIVE_COMPETENCES = [
    'nacionalidad_inmigracion_extranjeria' => [
      'article' => '149.1.2',
      'label' => 'Nacionalidad, inmigracion, extranjeria y asilo',
      'keywords' => ['nacionalidad', 'inmigracion', 'extranjeria', 'asilo', 'refugiado'],
    ],
    'relaciones_internacionales' => [
      'article' => '149.1.3',
      'label' => 'Relaciones internacionales',
      'keywords' => ['relaciones internacionales', 'tratados internacionales', 'politica exterior'],
    ],
    'defensa_fuerzas_armadas' => [
      'article' => '149.1.4',
      'label' => 'Defensa y Fuerzas Armadas',
      'keywords' => ['defensa nacional', 'fuerzas armadas', 'ejercito'],
    ],
    'administracion_justicia' => [
      'article' => '149.1.5',
      'label' => 'Administracion de Justicia',
      'keywords' => ['administracion de justicia', 'poder judicial', 'planta judicial'],
    ],
    'legislacion_mercantil_penal_penitenciaria' => [
      'article' => '149.1.6',
      'label' => 'Legislacion mercantil, penal, penitenciaria y procesal',
      'keywords' => ['codigo penal', 'ley de enjuiciamiento', 'legislacion mercantil', 'legislacion penal', 'derecho procesal', 'legislacion penitenciaria'],
    ],
    'legislacion_laboral' => [
      'article' => '149.1.7',
      'label' => 'Legislacion laboral (sin perjuicio de ejecucion por CCAA)',
      'keywords' => ['estatuto de los trabajadores', 'legislacion laboral', 'despido', 'convenio colectivo', 'salario minimo'],
    ],
    'legislacion_civil' => [
      'article' => '149.1.8',
      'label' => 'Legislacion civil (sin perjuicio de derechos forales)',
      'keywords' => ['codigo civil', 'legislacion civil'],
    ],
    'propiedad_intelectual' => [
      'article' => '149.1.9',
      'label' => 'Legislacion sobre propiedad intelectual e industrial',
      'keywords' => ['propiedad intelectual', 'propiedad industrial', 'patentes', 'marcas'],
    ],
    'hacienda_general' => [
      'article' => '149.1.14',
      'label' => 'Hacienda general y Deuda del Estado',
      'keywords' => ['hacienda publica', 'deuda del estado', 'presupuestos generales'],
    ],
    'seguridad_social' => [
      'article' => '149.1.17',
      'label' => 'Legislacion basica y regimen economico de la Seguridad Social',
      'keywords' => ['seguridad social', 'pension', 'prestacion por desempleo', 'jubilacion'],
    ],
    'bases_regimen_juridico_aapp' => [
      'article' => '149.1.18',
      'label' => 'Bases del regimen juridico de las AAPP y procedimiento administrativo comun',
      'keywords' => ['procedimiento administrativo', 'LPACAP', 'LRJSP', 'regimen juridico administraciones publicas'],
    ],
    'legislacion_basica_medio_ambiente' => [
      'article' => '149.1.23',
      'label' => 'Legislacion basica sobre medio ambiente',
      'keywords' => ['legislacion medioambiental', 'proteccion medioambiente', 'evaluacion impacto ambiental'],
    ],
    'bases_regimen_minero_energetico' => [
      'article' => '149.1.25',
      'label' => 'Bases del regimen minero y energetico',
      'keywords' => ['regimen minero', 'legislacion energetica'],
    ],
  ];

  /**
   * Principios generales de aplicacion del derecho.
   *
   * Cada principio: [id, latin_name, description, violation_patterns[]]
   * Los violation_patterns son expresiones que SI detectadas en output
   * indican posible violacion del principio.
   */
  public const LEGAL_PRINCIPLES = [
    'hierarchy' => [
      'latin' => 'Lex superior derogat legi inferiori',
      'description' => 'La norma de rango superior prevalece sobre la de rango inferior. Una norma inferior no puede contradecir una superior.',
      'article' => 'Art. 9.3 CE',
    ],
    'lex_posterior' => [
      'latin' => 'Lex posterior derogat legi priori',
      'description' => 'La norma posterior del mismo rango deroga la anterior. Excepto cuando la anterior sea especial.',
      'article' => 'Art. 2.2 CC',
    ],
    'lex_specialis' => [
      'latin' => 'Lex specialis derogat legi generali',
      'description' => 'La norma especial prevalece sobre la general del mismo rango en su ambito especifico.',
      'article' => 'Principio general del derecho',
    ],
    'irretroactividad_sancionadora' => [
      'latin' => 'Irretroactividad de disposiciones sancionadoras no favorables',
      'description' => 'Art. 9.3 CE: las disposiciones sancionadoras no favorables o restrictivas de derechos individuales no tienen efecto retroactivo.',
      'article' => 'Art. 9.3 CE, Art. 26 Ley 40/2015',
    ],
    'reserva_ley_organica' => [
      'latin' => 'Reserva de Ley Organica',
      'description' => 'Art. 81 CE: desarrollo de DDFF y libertades publicas, Estatutos de Autonomia, LOREG y demas previstas en la CE requieren Ley Organica.',
      'article' => 'Art. 81 CE',
    ],
    'primacia_ue' => [
      'latin' => 'Primacia del Derecho de la Union Europea',
      'description' => 'El Derecho UE prevalece sobre cualquier norma interna contraria, incluida la Constitucion en su aplicacion. TJUE: Costa v. ENEL (6/64), Simmenthal (106/77).',
      'article' => 'Declaracion 1/2004 TC, TJUE 6/64',
    ],
    'efecto_directo' => [
      'latin' => 'Efecto directo del Derecho UE',
      'description' => 'Los Reglamentos UE y las Directivas con disposiciones claras, precisas e incondicionales no transpuestas generan derechos invocables ante tribunales nacionales. TJUE: Van Gend en Loos (26/62).',
      'article' => 'Art. 288 TFUE, TJUE 26/62',
    ],
    'competencia_territorial' => [
      'latin' => 'Principio de competencia territorial',
      'description' => 'Las CCAA solo pueden legislar en materias de su competencia (Art. 148 CE + Estatuto). El Estado tiene competencias exclusivas (Art. 149.1 CE). En caso de conflicto, prevalece la norma estatal (Art. 149.3 CE).',
      'article' => 'Arts. 148, 149 CE',
    ],
  ];

  /**
   * Reservas de Ley Organica — materias que REQUIEREN LO.
   *
   * Cualquier afirmacion de que estas materias se regulan por
   * ley ordinaria, decreto u otra norma es INCOHERENTE.
   */
  public const ORGANIC_LAW_MATTERS = [
    'derechos_fundamentales' => 'Desarrollo de derechos fundamentales y libertades publicas (Seccion 1a, Cap. II, Titulo I CE)',
    'estatutos_autonomia' => 'Aprobacion y reforma de Estatutos de Autonomia',
    'regimen_electoral' => 'Regimen electoral general (LOREG)',
    'defensor_pueblo' => 'Defensor del Pueblo',
    'tribunal_constitucional' => 'Tribunal Constitucional',
    'tribunal_cuentas' => 'Tribunal de Cuentas',
    'consejo_estado' => 'Consejo de Estado',
    'poder_judicial' => 'Poder Judicial (LOPJ)',
    'fuerzas_seguridad' => 'Fuerzas y Cuerpos de Seguridad',
    'estados_alarma_excepcion_sitio' => 'Estados de alarma, excepcion y sitio',
    'habeas_corpus' => 'Habeas Corpus',
    'iniciativa_legislativa_popular' => 'Regulacion de la Iniciativa Legislativa Popular',
  ];

  /**
   * Tipos normativos y su rango en la jerarquia.
   *
   * Mapea patrones textuales de normas a su clave en NORMATIVE_HIERARCHY.
   * Usado por LegalCoherenceValidatorService para detectar el rango
   * de las normas mencionadas en una respuesta.
   */
  public const NORM_TYPE_PATTERNS = [
    // Derecho UE.
    '/\bReglamento\s*\(UE\)/i' => 'derecho_ue_derivado',
    '/\bDirectiva\s*\(UE\)/i' => 'derecho_ue_derivado',
    '/\bDirectiva\s+\d{4}\/\d+/i' => 'derecho_ue_derivado',
    '/\bReglamento\s*\(CE\)/i' => 'derecho_ue_derivado',
    '/\bTFUE\b/' => 'derecho_ue_primario',
    '/\bTUE\b/' => 'derecho_ue_primario',
    '/\bCarta de Derechos Fundamentales de la UE/i' => 'derecho_ue_primario',
    // Constitucion.
    '/\bConstitucion\s+Espanola\b/i' => 'constitucion',
    '/\bCE\b(?!\d)/' => 'constitucion',
    '/\bArt(?:iculo)?\.?\s*\d+(?:\.\d+)?\s*(?:de la\s+)?CE\b/' => 'constitucion',
    // Ley Organica.
    '/\bLey\s+Organica\b/i' => 'ley_organica',
    '/\bL\.?O\.?\s+\d+/i' => 'ley_organica',
    '/\bLOPJ\b/' => 'ley_organica',
    '/\bLORE[GC]\b/' => 'ley_organica',
    '/\bLOPD(?:GDD)?\b/' => 'ley_organica',
    // Ley ordinaria y equivalentes.
    '/\bLey\s+\d+\/\d{4}\b/i' => 'ley_ordinaria',
    '/\bReal\s+Decreto[- ]ley\b/i' => 'ley_ordinaria',
    '/\bReal\s+Decreto\s+Legislativo\b/i' => 'ley_ordinaria',
    '/\bRDL\s+\d+/i' => 'ley_ordinaria',
    '/\bRDLeg\s+\d+/i' => 'ley_ordinaria',
    '/\bLPACAP\b/' => 'ley_ordinaria',
    '/\bLRJSP\b/' => 'ley_ordinaria',
    '/\bLEC\b/' => 'ley_ordinaria',
    '/\bLECrim\b/' => 'ley_ordinaria',
    // Reglamento estatal.
    '/\bReal\s+Decreto\s+\d+\/\d{4}\b/i' => 'reglamento_estatal',
    '/\bOrden\s+(?:Ministerial|[A-Z]{3})\b/i' => 'reglamento_estatal',
    '/\bResolucion\s+de\s+\d+\s+de\b/i' => 'reglamento_estatal',
    // Normativa autonomica.
    '/\bLey\s+(?:de|del)\s+(?:Andalucia|Cataluna|Madrid|Galicia|Pais Vasco|Comunidad|Aragon|Asturias|Baleares|Canarias|Cantabria|Castilla|Extremadura|La Rioja|Murcia|Navarra|Valencia)/i' => 'ley_autonomica',
    '/\bDecreto\s+(?:de la Junta|del Govern|del Gobierno de)\b/i' => 'reglamento_autonomico',
    // Normativa local.
    '/\bOrdenanza\s+municipal\b/i' => 'normativa_local',
    '/\bOrdenanza\s+de\s+/i' => 'normativa_local',
  ];

  /**
   * Patrones de violacion de jerarquia normativa.
   *
   * Expresiones regulares que detectan afirmaciones donde una norma
   * de rango inferior se presenta como prevalente sobre una de rango superior.
   * Cada patron: [regex, violation_type, severity]
   */
  public const HIERARCHY_VIOLATION_PATTERNS = [
    // Reglamento deroga/prevalece sobre ley.
    [
      'pattern' => '/(?:Real\s+Decreto|Orden\s+Ministerial|Resolucion)\s+[^.]*(?:deroga|anula|modifica|prevalece\s+sobre|sustituye)\s+[^.]*(?:Ley\s+Organica|Ley\s+\d|Constitucion)/i',
      'type' => 'hierarchy_inversion',
      'severity' => 'critical',
      'description' => 'Norma reglamentaria no puede derogar/prevalecer sobre norma con rango de ley',
    ],
    // Ley ordinaria regula materia de LO.
    [
      'pattern' => '/(?:(?:L|l)ey\s+(?:ordinaria|\d+\/\d{4}))\s+[^.]*(?:regula|desarrolla|establece)\s+[^.]*(?:derechos?\s+fundamentales?|habeas\s+corpus|regimen\s+electoral)/i',
      'type' => 'organic_law_violation',
      'severity' => 'high',
      'description' => 'Materia reservada a Ley Organica no puede regularse por ley ordinaria',
    ],
    // CCAA legisla en competencia exclusiva estatal.
    [
      'pattern' => '/(?:Ley\s+(?:de|del)\s+(?:Andalucia|Cataluna|Madrid|Galicia|Pais\s+Vasco|Comunidad|Aragon))\s+[^.]*(?:regula|establece|modifica)\s+[^.]*(?:codigo\s+penal|legislacion\s+mercantil|legislacion\s+laboral\s+basica|seguridad\s+social|nacionalidad|extranjeria)/i',
      'type' => 'competence_violation',
      'severity' => 'critical',
      'description' => 'Legislacion autonomica no puede regular competencia exclusiva del Estado (Art. 149.1 CE)',
    ],
    // Norma interna prevalece sobre Derecho UE.
    [
      'pattern' => '/(?:Constitucion|Ley\s+Organica|Ley\s+\d)\s+[^.]*(?:prevalece|prima|se\s+aplica\s+preferentemente|deroga)\s+[^.]*(?:Reglamento\s+\(UE\)|Directiva\s+\(UE\)|Derecho\s+(?:de\s+la\s+)?(?:Union\s+Europea|UE|comunitario))/i',
      'type' => 'eu_primacy_violation',
      'severity' => 'critical',
      'description' => 'El Derecho interno no prevalece sobre el Derecho UE (primacia, Costa v. ENEL)',
    ],
    // Aplicacion retroactiva de sancion.
    [
      'pattern' => '/(?:sancion|multa|pena|castigo)\s+[^.]*(?:retroactiv|con\s+efecto\s+retroactivo|se\s+aplica\s+retroactivamente)\s+[^.]*(?:desfavorable|perjudicial|restrictiv)/i',
      'type' => 'retroactivity_violation',
      'severity' => 'high',
      'description' => 'Disposiciones sancionadoras desfavorables no tienen efecto retroactivo (Art. 9.3 CE)',
    ],
  ];

  /**
   * Verifica si un rango normativo es superior a otro.
   *
   * @param string $normTypeA
   *   Clave de NORMATIVE_HIERARCHY (ej: 'ley_organica').
   * @param string $normTypeB
   *   Clave de NORMATIVE_HIERARCHY (ej: 'reglamento_estatal').
   *
   * @return bool
   *   TRUE si A tiene rango superior a B.
   */
  public static function isHigherRank(string $normTypeA, string $normTypeB): bool {
    $rankA = self::NORMATIVE_HIERARCHY[$normTypeA]['rank'] ?? 99;
    $rankB = self::NORMATIVE_HIERARCHY[$normTypeB]['rank'] ?? 99;
    return $rankA < $rankB;
  }

  /**
   * Obtiene el rango de una norma a partir de su texto.
   *
   * @param string $normText
   *   Texto que menciona una norma (ej: "Real Decreto 123/2024").
   *
   * @return string|null
   *   Clave de NORMATIVE_HIERARCHY o NULL si no se detecta.
   */
  public static function detectNormRank(string $normText): ?string {
    foreach (self::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
      if (preg_match($pattern, $normText)) {
        return $hierarchyKey;
      }
    }
    return NULL;
  }

  /**
   * Verifica si una materia es competencia exclusiva del Estado.
   *
   * @param string $text
   *   Texto a analizar.
   *
   * @return array|null
   *   Datos de la competencia exclusiva si se detecta, NULL si no.
   */
  public static function isStateExclusiveCompetence(string $text): ?array {
    $textLower = mb_strtolower($text);
    foreach (self::STATE_EXCLUSIVE_COMPETENCES as $id => $competence) {
      foreach ($competence['keywords'] as $keyword) {
        if (str_contains($textLower, mb_strtolower($keyword))) {
          return ['id' => $id, ...$competence];
        }
      }
    }
    return NULL;
  }

  /**
   * Verifica si una materia requiere Ley Organica.
   *
   * @param string $text
   *   Texto a analizar.
   *
   * @return string|null
   *   Descripcion de la materia reservada o NULL.
   */
  public static function requiresOrganicLaw(string $text): ?string {
    $textLower = mb_strtolower($text);
    foreach (self::ORGANIC_LAW_MATTERS as $key => $description) {
      $keywords = explode(' ', str_replace('_', ' ', $key));
      $matchCount = 0;
      foreach ($keywords as $kw) {
        if (str_contains($textLower, $kw)) {
          $matchCount++;
        }
      }
      // Al menos 2 keywords del grupo deben coincidir.
      if ($matchCount >= 2) {
        return $description;
      }
    }
    return NULL;
  }

}
```

---

## 4. CAPA 4 — LegalCoherencePromptRule

### 4.1 Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalCoherencePromptRule.php
```

### 4.2 Especificacion

Patron identico a `AIIdentityRule`: clase `final` con metodo estatico `apply()`. Se inyecta en el system prompt de TODO agente que toque tematica legal.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

/**
 * Regla de coherencia juridica inyectable en system prompts.
 *
 * Patron analogo a AIIdentityRule: static apply() prepends coherence
 * instructions to the system prompt. Called by:
 * - SmartLegalCopilotAgent::buildModePrompt()
 * - LegalRagService::buildSystemPrompt()
 * - SmartBaseAgent::callAiApi() (cuando vertical = jarabalex)
 * - CopilotOrchestratorService (modos fiscal, laboral)
 *
 * LEGAL-COHERENCE-PROMPT-001: Este prompt NO sustituye el conocimiento
 * juridico del LLM. Lo ALINEA para que respete principios estructurales
 * del ordenamiento que el LLM ya conoce pero puede violar por inercia
 * generativa (hallucination, confabulacion).
 */
final class LegalCoherencePromptRule {

  /**
   * Prompt completo de coherencia juridica (version completa).
   *
   * Inyectado en modos legales que usan tier balanced/premium.
   */
  public const COHERENCE_PROMPT = <<<'PROMPT'
## REGLAS DE COHERENCIA JURIDICA (INQUEBRANTABLES)

Debes respetar SIEMPRE los siguientes principios del ordenamiento juridico espanol y europeo. Cualquier respuesta que los viole es INCORRECTA y perjudicial para el usuario.

### 1. JERARQUIA NORMATIVA (Art. 9.3 CE)
El ordenamiento juridico espanol tiene una jerarquia estricta. Una norma de rango inferior NUNCA puede contradecir, derogar ni prevalecer sobre una de rango superior:

Derecho UE Primario (Tratados) > Derecho UE Derivado (Reglamentos, Directivas) > Constitucion Espanola > Ley Organica > Ley Ordinaria / RDL / RDLeg > Reglamento (RD, OM) > Ley Autonomica > Reglamento Autonomico > Normativa Local

NUNCA afirmes que un Real Decreto deroga una Ley, que una Orden Ministerial prevalece sobre un Real Decreto-ley, ni que una Ley Autonomica regula materia de competencia exclusiva estatal.

### 2. PRIMACIA DEL DERECHO UE
El Derecho de la Union Europea prevalece sobre CUALQUIER norma interna contraria, incluida la aplicacion de la Constitucion (TJUE: Costa v. ENEL 6/64, Simmenthal 106/77). Los Reglamentos UE tienen efecto directo. Las Directivas no transpuestas con disposiciones claras, precisas e incondicionales generan derechos invocables (Van Gend en Loos 26/62).

NUNCA afirmes que una ley espanola prevalece sobre un Reglamento UE ni que una Directiva carece de efecto por no estar transpuesta si sus disposiciones son claras y precisas.

### 3. COMPETENCIAS ESTADO vs. CCAA (Arts. 148-149 CE)
Las competencias exclusivas del Estado (Art. 149.1 CE) incluyen: legislacion penal, mercantil, laboral, procesal, civil basica, propiedad intelectual, Seguridad Social, procedimiento administrativo comun, etc. Las CCAA NO pueden legislar en estas materias salvo que el Estado les transfiera o delegue competencias (Art. 150 CE).

NUNCA atribuyas a una Comunidad Autonoma competencia para legislar sobre materia exclusiva del Estado sin especificar el titulo competencial habilitante.

### 4. RESERVA DE LEY ORGANICA (Art. 81 CE)
Las siguientes materias SOLO pueden regularse por Ley Organica (mayoria absoluta Congreso): derechos fundamentales y libertades publicas, Estatutos de Autonomia, regimen electoral general, Defensor del Pueblo, TC, LOPJ, estados de alarma/excepcion/sitio.

NUNCA afirmes que una ley ordinaria, decreto o reglamento desarrolla derechos fundamentales.

### 5. IRRETROACTIVIDAD (Art. 9.3 CE)
Las disposiciones sancionadoras no favorables y las restrictivas de derechos individuales NO tienen efecto retroactivo. La retroactividad favorable SI es posible en materia sancionadora.

### 6. VIGENCIA Y DEROGACION
Cuando cites una norma, indica siempre su estado de vigencia si lo conoces. Si una norma ha sido derogada, modificada o sustituida, mencionalo expresamente. No cites normas derogadas como si estuvieran vigentes sin advertencia.

### 7. CONSISTENCIA TRANSVERSAL
Si en la misma respuesta o en respuestas anteriores del contexto has afirmado un principio juridico, NO puedes contradecirlo en la misma respuesta. Si existen corrientes doctrinales opuestas, presentalas como tales, no como afirmacion propia.

### 8. HUMILDAD JURIDICA
Ante duda sobre la jerarquia, competencia o vigencia de una norma, indica la duda al usuario. Es preferible reconocer incertidumbre que afirmar algo juridicamente incorrecto. Usa formulas como: "Cabria verificar si...", "La vigencia de esta norma podria estar afectada por...".
PROMPT;

  /**
   * Prompt compacto de coherencia juridica (version reducida).
   *
   * Para modos fast tier o contextos con ventana limitada.
   */
  public const COHERENCE_PROMPT_SHORT = <<<'PROMPT'
## COHERENCIA JURIDICA (OBLIGATORIA)
1. JERARQUIA: DUE > CE > LO > Ley > RD > Ley CCAA > Local. Inferior NO deroga superior.
2. PRIMACIA UE: Derecho UE prevalece sobre derecho interno (Costa v. ENEL).
3. COMPETENCIAS: Art. 149.1 CE = exclusivas Estado. CCAA NO legislan en ellas.
4. LO: DDFF, Estatutos, LOREG = solo Ley Organica.
5. IRRETROACTIVIDAD: Sanciones desfavorables NO retroactivas (Art. 9.3 CE).
6. VIGENCIA: Indica siempre si la norma esta vigente, derogada o modificada.
7. CONSISTENCIA: No te contradigas. Duda = "cabria verificar".
PROMPT;

  /**
   * Aplica la regla de coherencia juridica al system prompt.
   *
   * @param string $systemPrompt
   *   El prompt original del agente.
   * @param bool $short
   *   TRUE para version compacta (fast tier).
   *
   * @return string
   *   El prompt con la regla de coherencia prepended.
   */
  public static function apply(string $systemPrompt, bool $short = FALSE): string {
    $rule = $short ? self::COHERENCE_PROMPT_SHORT : self::COHERENCE_PROMPT;
    return $rule . "\n\n" . $systemPrompt;
  }

  /**
   * Determina si una accion requiere inyeccion de coherencia.
   *
   * @param string $action
   *   La accion del agente.
   * @param string $vertical
   *   El vertical activo.
   *
   * @return bool
   *   TRUE si la accion requiere coherencia juridica.
   */
  public static function requiresCoherence(string $action, string $vertical = ''): bool {
    // Siempre en vertical jarabalex.
    if ($vertical === 'jarabalex') {
      return TRUE;
    }

    // Acciones legales en cualquier vertical.
    $legalActions = [
      'legal_search', 'legal_analysis', 'legal_alerts', 'legal_citations',
      'legal_eu', 'case_assistant', 'document_drafter', 'legal_document_draft',
      'contract_generation', 'fiscal', 'laboral',
    ];

    return in_array($action, $legalActions, TRUE);
  }

  /**
   * Determina si debe usarse la version compacta.
   *
   * @param string $action
   *   La accion del agente.
   *
   * @return bool
   *   TRUE si debe usarse COHERENCE_PROMPT_SHORT.
   */
  public static function useShortVersion(string $action): bool {
    $fastActions = ['faq', 'legal_alerts', 'legal_citations'];
    return in_array($action, $fastActions, TRUE);
  }

}
```

### 4.3 Puntos de Inyeccion (5 call sites)

| Fichero | Metodo | Cambio |
|---------|--------|--------|
| `SmartLegalCopilotAgent.php` | `buildModePrompt()` | Prepend `LegalCoherencePromptRule::apply($prompt, useShortVersion($mode))` |
| `LegalRagService.php` | `buildSystemPrompt()` | Prepend `LegalCoherencePromptRule::apply($prompt)` |
| `SmartBaseAgent.php` | `callAiApi()` | Condicional: si `$this->getVertical() === 'jarabalex'` o accion legal, prepend |
| `CopilotOrchestratorService.php` | `buildSystemPrompt()` | Para modos `fiscal`, `laboral`: prepend version short |
| `LegalCopilotBridgeService.php` | `injectLegalContext()` | Prepend al contexto RAG legal |

---

## 5. CAPA 5 — Regla Constitucional `legal_coherence`

### 5.1 Fichero a modificar

```
web/modules/custom/jaraba_ai_agents/src/Service/ConstitutionalGuardrailService.php
```

### 5.2 Cambio

Anadir nueva regla a `CONSTITUTIONAL_RULES`:

```php
'legal_coherence' => [
  'description' => 'NEVER state that a lower-rank norm overrides a higher-rank norm',
  'severity' => 'critical',
  'patterns' => [
    // Real Decreto/Orden deroga Ley.
    '/(?:Real\s+Decreto|Orden\s+Ministerial)\s+[^.]{0,80}(?:deroga|anula|invalida|deja\s+sin\s+efecto)\s+[^.]{0,60}(?:Ley\s+Organica|Ley\s+\d)/i',
    // Ley espanola prevalece sobre Derecho UE.
    '/(?:Ley|Constitucion)\s+[^.]{0,60}(?:prevalece|prima)\s+[^.]{0,60}(?:Derecho\s+(?:de\s+la\s+)?Union\s+Europea|Reglamento\s+\(UE\))/i',
    // Ordenanza municipal deroga ley.
    '/(?:Ordenanza|normativa\s+local)\s+[^.]{0,60}(?:deroga|anula|modifica)\s+[^.]{0,60}(?:Ley|Real\s+Decreto|Constitucion)/i',
  ],
],
```

Anadir a `ENFORCEMENT_KEYWORDS`:

```php
'JERARQUIA NORMATIVA',
'COHERENCIA JURIDICA',
```

### 5.3 Justificacion

Esta capa es **determinista, zero-cost, inmutable**. Captura las violaciones mas groseras de jerarquia normativa que NUNCA deberian aparecer en output. Es la linea de defensa incondicional (unica capa que bloquea sin fail-open).

---

## 6. CAPA 6 — LegalCoherenceValidatorService

### 6.1 Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalCoherenceValidatorService.php
```

### 6.2 Especificacion

Servicio de validacion **determinista** (zero-cost, sin LLM). Analiza el output del agente buscando patrones de incoherencia juridica.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Validacion determinista de coherencia juridica en outputs de IA.
 *
 * Post-procesamiento sin LLM. Analiza el texto generado buscando
 * patrones que violan principios juridicos estructurales.
 *
 * Capas de analisis:
 * 1. Deteccion de violaciones de jerarquia normativa via regex
 * 2. Deteccion de violaciones de competencia territorial
 * 3. Deteccion de normas derogadas citadas como vigentes
 * 4. Deteccion de contradicciones internas en la respuesta
 *
 * Resultado: array con violations[], warnings[], score (0.0-1.0).
 * Score < 0.5 = bloqueo (no se entrega al usuario).
 * Score 0.5-0.7 = warning (se entrega con advertencia).
 * Score > 0.7 = pass.
 *
 * Registrado como: jaraba_legal_intelligence.legal_coherence_validator
 * Tag: (ninguno, invocado explicitamente)
 *
 * @see LegalCoherenceKnowledgeBase
 */
class LegalCoherenceValidatorService {

  /**
   * Score thresholds.
   */
  private const THRESHOLD_BLOCK = 0.5;
  private const THRESHOLD_WARN = 0.7;

  /**
   * Penalty points per violation type.
   */
  private const PENALTIES = [
    'critical' => 0.4,
    'high' => 0.25,
    'medium' => 0.15,
    'low' => 0.05,
  ];

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Valida la coherencia juridica de un texto generado por IA.
   *
   * @param string $output
   *   Texto generado por el agente.
   * @param array $context
   *   Contexto: agent_id, action, mode, tenant_id, vertical.
   *
   * @return array{
   *   passed: bool,
   *   score: float,
   *   action: string,
   *   violations: array,
   *   warnings: array,
   *   sanitized_output: string,
   *   metadata: array,
   * }
   */
  public function validate(string $output, array $context = []): array {
    $violations = [];
    $warnings = [];
    $score = 1.0;

    // 1. Deteccion de violaciones de jerarquia (HIERARCHY_VIOLATION_PATTERNS).
    $hierarchyResult = $this->checkHierarchyViolations($output);
    $violations = array_merge($violations, $hierarchyResult['violations']);

    // 2. Deteccion de violaciones de competencia.
    $competenceResult = $this->checkCompetenceViolations($output);
    $violations = array_merge($violations, $competenceResult['violations']);

    // 3. Deteccion de reserva de ley organica.
    $organicResult = $this->checkOrganicLawViolations($output);
    $violations = array_merge($violations, $organicResult['violations']);

    // 4. Deteccion de normas citadas sin advertencia de vigencia.
    $vigenciaResult = $this->checkVigenciaWarnings($output);
    $warnings = array_merge($warnings, $vigenciaResult['warnings']);

    // 5. Deteccion de contradicciones internas.
    $contradictionResult = $this->checkInternalContradictions($output);
    $warnings = array_merge($warnings, $contradictionResult['warnings']);

    // Calcular score.
    foreach ($violations as $v) {
      $score -= self::PENALTIES[$v['severity']] ?? 0.1;
    }
    foreach ($warnings as $w) {
      $score -= self::PENALTIES[$w['severity'] ?? 'low'] ?? 0.05;
    }
    $score = max(0.0, $score);

    // Determinar accion.
    $action = 'allow';
    $sanitizedOutput = $output;
    if ($score < self::THRESHOLD_BLOCK) {
      $action = 'block';
      $sanitizedOutput = $this->buildBlockedResponse($violations, $context);
    }
    elseif ($score < self::THRESHOLD_WARN) {
      $action = 'warn';
      $sanitizedOutput = $this->appendWarnings($output, $violations, $warnings);
    }

    $passed = $action !== 'block';

    if (!$passed) {
      $this->logger->warning('Legal coherence blocked for @agent/@action: @violations', [
        '@agent' => $context['agent_id'] ?? 'unknown',
        '@action' => $context['action'] ?? 'unknown',
        '@violations' => json_encode(array_column($violations, 'type')),
      ]);
    }

    return [
      'passed' => $passed,
      'score' => round($score, 3),
      'action' => $action,
      'violations' => $violations,
      'warnings' => $warnings,
      'sanitized_output' => $sanitizedOutput,
      'metadata' => [
        'hierarchy_checks' => $hierarchyResult['checks_run'] ?? 0,
        'competence_checks' => $competenceResult['checks_run'] ?? 0,
        'norms_detected' => $hierarchyResult['norms_detected'] ?? [],
      ],
    ];
  }

  /**
   * Verifica violaciones de jerarquia normativa.
   */
  protected function checkHierarchyViolations(string $output): array {
    $violations = [];
    $normsDetected = [];
    $checksRun = 0;

    // Fase A: Deteccion mediante patrones predefinidos.
    foreach (LegalCoherenceKnowledgeBase::HIERARCHY_VIOLATION_PATTERNS as $rule) {
      $checksRun++;
      if (preg_match($rule['pattern'], $output, $matches)) {
        $violations[] = [
          'type' => $rule['type'],
          'severity' => $rule['severity'],
          'description' => $rule['description'],
          'match' => $matches[0],
          'source' => 'pattern',
        ];
      }
    }

    // Fase B: Deteccion estructural — extraer normas mencionadas y
    // verificar que no se afirme prevalencia de inferior sobre superior.
    $sentences = preg_split('/[.;]\s+/', $output);
    $prevalenceKeywords = [
      'prevalece sobre', 'prima sobre', 'deroga', 'anula',
      'deja sin efecto', 'se aplica preferentemente', 'sustituye a',
      'invalida', 'se impone sobre',
    ];

    foreach ($sentences as $sentence) {
      $checksRun++;
      $sentenceLower = mb_strtolower($sentence);

      // Buscar si la frase contiene keyword de prevalencia.
      $hasPrevalence = FALSE;
      foreach ($prevalenceKeywords as $kw) {
        if (str_contains($sentenceLower, $kw)) {
          $hasPrevalence = TRUE;
          break;
        }
      }

      if (!$hasPrevalence) {
        continue;
      }

      // Detectar normas en la frase.
      $detectedNorms = [];
      foreach (LegalCoherenceKnowledgeBase::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
        if (preg_match($pattern, $sentence, $m)) {
          $detectedNorms[] = [
            'text' => $m[0],
            'hierarchy_key' => $hierarchyKey,
            'rank' => LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$hierarchyKey]['rank'] ?? 99,
          ];
        }
      }

      $normsDetected = array_merge($normsDetected, $detectedNorms);

      // Si hay 2+ normas de diferente rango en la misma frase con
      // keyword de prevalencia, verificar que la de mayor rango
      // es la que prevalece (aparece DESPUES del keyword).
      if (count($detectedNorms) >= 2) {
        usort($detectedNorms, fn($a, $b) => $a['rank'] <=> $b['rank']);
        $highest = $detectedNorms[0];
        $lowest = end($detectedNorms);

        // Heuristica: si la norma inferior aparece ANTES del keyword
        // de prevalencia y la superior DESPUES, es una inversion.
        foreach ($prevalenceKeywords as $kw) {
          $kwPos = mb_strpos($sentenceLower, $kw);
          if ($kwPos === FALSE) {
            continue;
          }
          $lowestPos = mb_stripos($sentence, $lowest['text']);
          $highestPos = mb_stripos($sentence, $highest['text']);

          if ($lowestPos !== FALSE && $highestPos !== FALSE
            && $lowestPos < $kwPos && $highestPos > $kwPos
            && $lowest['rank'] > $highest['rank']) {
            $violations[] = [
              'type' => 'hierarchy_inversion_structural',
              'severity' => 'critical',
              'description' => sprintf(
                'Se afirma que %s (%s, rango %d) prevalece sobre %s (%s, rango %d)',
                $lowest['text'],
                LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$lowest['hierarchy_key']]['label'] ?? '',
                $lowest['rank'],
                $highest['text'],
                LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$highest['hierarchy_key']]['label'] ?? '',
                $highest['rank'],
              ),
              'match' => mb_substr($sentence, 0, 200),
              'source' => 'structural',
            ];
          }
        }
      }
    }

    return [
      'violations' => $violations,
      'norms_detected' => array_unique(array_column($normsDetected, 'text')),
      'checks_run' => $checksRun,
    ];
  }

  /**
   * Verifica violaciones de competencia territorial.
   */
  protected function checkCompetenceViolations(string $output): array {
    $violations = [];
    $checksRun = 0;

    // Detectar si se atribuye legislacion a CCAA en materia exclusiva estatal.
    $ccaaPatterns = [
      '/(?:Ley|Decreto|normativa)\s+(?:de\s+)?(?:la\s+)?(?:Comunidad\s+(?:Autonoma\s+)?de|de\s+Andalucia|de\s+Cataluna|del?\s+Pais\s+Vasco|de\s+Madrid|de\s+Galicia|de\s+Aragon|de\s+Valencia)\s+[^.]{0,100}/i',
    ];

    foreach ($ccaaPatterns as $ccaaPattern) {
      if (preg_match_all($ccaaPattern, $output, $matches)) {
        foreach ($matches[0] as $match) {
          $checksRun++;
          $competence = LegalCoherenceKnowledgeBase::isStateExclusiveCompetence($match);
          if ($competence) {
            $violations[] = [
              'type' => 'competence_violation',
              'severity' => 'high',
              'description' => sprintf(
                'Se atribuye a normativa autonomica la regulacion de "%s" (competencia exclusiva del Estado, Art. %s CE)',
                $competence['label'],
                $competence['article'],
              ),
              'match' => mb_substr($match, 0, 200),
              'source' => 'competence_check',
            ];
          }
        }
      }
    }

    return ['violations' => $violations, 'checks_run' => $checksRun];
  }

  /**
   * Verifica violaciones de reserva de ley organica.
   */
  protected function checkOrganicLawViolations(string $output): array {
    $violations = [];

    // Buscar frases donde una ley NO organica regula materia reservada.
    $nonOrganicPattern = '/(?:(?:L|l)ey\s+\d+\/\d{4}|Real\s+Decreto|Decreto[- ]ley|Orden\s+Ministerial)\s+[^.]{0,100}(?:regula|desarrolla|establece|aprueba)\s+[^.]{0,100}/i';

    if (preg_match_all($nonOrganicPattern, $output, $matches)) {
      foreach ($matches[0] as $match) {
        $loMatter = LegalCoherenceKnowledgeBase::requiresOrganicLaw($match);
        if ($loMatter) {
          $violations[] = [
            'type' => 'organic_law_violation',
            'severity' => 'high',
            'description' => sprintf(
              'Se afirma que una norma no organica regula materia reservada a LO: %s (Art. 81 CE)',
              $loMatter,
            ),
            'match' => mb_substr($match, 0, 200),
            'source' => 'organic_law_check',
          ];
        }
      }
    }

    return ['violations' => $violations];
  }

  /**
   * Genera advertencias sobre vigencia normativa.
   */
  protected function checkVigenciaWarnings(string $output): array {
    $warnings = [];

    // Patron: cita de norma con fecha antigua sin mencion de vigencia.
    $oldNormPattern = '/(?:Ley|Real\s+Decreto|Ley\s+Organica)\s+\d+\/(?:19[0-9]{2}|200[0-9]|201[0-5])\b/i';
    $vigenciaKeywords = ['vigente', 'derogad', 'modificad', 'sustituida', 'actualizada', 'consolidada'];

    if (preg_match_all($oldNormPattern, $output, $matches)) {
      foreach ($matches[0] as $match) {
        // Verificar si la frase que contiene la norma menciona vigencia.
        $sentence = $this->getSentenceContaining($output, $match);
        $hasVigencia = FALSE;
        foreach ($vigenciaKeywords as $kw) {
          if (stripos($sentence, $kw) !== FALSE) {
            $hasVigencia = TRUE;
            break;
          }
        }

        if (!$hasVigencia) {
          $warnings[] = [
            'type' => 'vigencia_not_mentioned',
            'severity' => 'low',
            'description' => sprintf(
              'Norma con fecha anterior a 2015 citada sin mencion de vigencia: %s. Recomendar verificacion.',
              $match,
            ),
            'match' => $match,
          ];
        }
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Detecta contradicciones internas en la respuesta.
   */
  protected function checkInternalContradictions(string $output): array {
    $warnings = [];

    // Pares de afirmaciones contradictorias comunes.
    $contradictionPairs = [
      ['/es\s+competencia\s+exclusiva\s+del\s+Estado/i', '/(?:las?\s+)?(?:CCAA|Comunidades?\s+Autonomas?)\s+(?:pueden|tiene[n]?\s+competencia\s+para)\s+legislar/i', 'Competencia exclusiva del Estado vs legislacion autonomica'],
      ['/no\s+tiene\s+efecto\s+retroactivo/i', '/se\s+aplica\s+retroactivamente/i', 'Irretroactividad vs aplicacion retroactiva'],
      ['/requiere\s+Ley\s+Organica/i', '/(?:se\s+)?regula\s+(?:por|mediante)\s+(?:ley\s+ordinaria|decreto|reglamento)/i', 'Reserva de LO vs regulacion por norma inferior'],
    ];

    foreach ($contradictionPairs as [$patternA, $patternB, $desc]) {
      if (preg_match($patternA, $output) && preg_match($patternB, $output)) {
        $warnings[] = [
          'type' => 'internal_contradiction',
          'severity' => 'medium',
          'description' => "Posible contradiccion interna: {$desc}. Verificar contexto.",
          'match' => $desc,
        ];
      }
    }

    return ['warnings' => $warnings];
  }

  /**
   * Construye respuesta de bloqueo con explicacion.
   */
  protected function buildBlockedResponse(array $violations, array $context): string {
    $header = "La respuesta generada contiene inconsistencias juridicas que impiden su entrega. ";
    $header .= "Esto protege la calidad y fiabilidad de la informacion legal.\n\n";
    $header .= "**Problemas detectados:**\n";

    foreach ($violations as $v) {
      $header .= "- {$v['description']}\n";
    }

    $header .= "\n**Recomendacion:** Reformule su consulta o consulte directamente la normativa en las fuentes oficiales (BOE, EUR-Lex, CENDOJ).";

    return $header;
  }

  /**
   * Anade advertencias al output sin bloquearlo.
   */
  protected function appendWarnings(string $output, array $violations, array $warnings): string {
    $notice = "\n\n---\n**Aviso de coherencia juridica:** Se han detectado posibles imprecisiones en esta respuesta:\n";

    foreach ($violations as $v) {
      $notice .= "- [!] {$v['description']}\n";
    }
    foreach ($warnings as $w) {
      $notice .= "- [i] {$w['description']}\n";
    }

    $notice .= "\nSe recomienda verificar la informacion con las fuentes oficiales.";

    return $output . $notice;
  }

  /**
   * Obtiene la frase completa que contiene un texto dado.
   */
  protected function getSentenceContaining(string $text, string $needle): string {
    $pos = mb_strpos($text, $needle);
    if ($pos === FALSE) {
      return '';
    }

    // Buscar inicio de frase (. anterior o inicio de texto).
    $start = mb_strrpos(mb_substr($text, 0, $pos), '.') ?: 0;
    // Buscar fin de frase (. posterior o fin de texto).
    $end = mb_strpos($text, '.', $pos);
    if ($end === FALSE) {
      $end = mb_strlen($text);
    }

    return mb_substr($text, $start, $end - $start + 1);
  }

}
```

### 6.3 Registro del Servicio

En `jaraba_legal_intelligence.services.yml`:

```yaml
  jaraba_legal_intelligence.legal_coherence_validator:
    class: Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceValidatorService
    arguments:
      - '@logger.channel.jaraba_legal_intelligence'
```

---

## 7. CAPA 7 — LegalCoherenceVerifierService

### 7.1 Fichero

```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalCoherenceVerifierService.php
```

### 7.2 Especificacion

Verificacion semantica profunda via LLM. Solo se ejecuta para acciones HIGH_RISK del vertical jarabalex. Usa **premium tier** (Opus) para maxima precision juridica.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Psr\Log\LoggerInterface;

/**
 * Verificacion semantica profunda de coherencia juridica via LLM.
 *
 * Solo se ejecuta para acciones HIGH_RISK en el vertical jarabalex:
 * - legal_analysis
 * - legal_search
 * - case_assistant
 * - document_drafter
 * - legal_eu
 * - contract_generation
 * - legal_document_draft
 *
 * Usa premium tier (Opus) con temperatura 0.1 para maxima precision.
 * Fail-open: si el verificador falla, el output se entrega con flag.
 *
 * Costo: 1 llamada LLM premium por verificacion. Justificado por:
 * - EU AI Act Art. 9 (gestion de riesgos) exige control de calidad
 * - Coste reputacional de error juridico >>> coste de verificacion
 *
 * Registrado como: jaraba_legal_intelligence.legal_coherence_verifier
 */
class LegalCoherenceVerifierService {

  /**
   * Acciones que requieren verificacion profunda.
   */
  private const HIGH_RISK_LEGAL_ACTIONS = [
    'legal_analysis',
    'legal_search',
    'case_assistant',
    'document_drafter',
    'legal_eu',
    'contract_generation',
    'legal_document_draft',
  ];

  /**
   * Umbral minimo para pasar la verificacion.
   */
  private const PASS_THRESHOLD = 0.7;

  public function __construct(
    protected readonly AiProviderPluginManager $aiProvider,
    protected readonly ModelRouterService $modelRouter,
    protected readonly LoggerInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Verifica la coherencia juridica de un output de agente via LLM.
   *
   * @param string $userInput
   *   Pregunta/solicitud original del usuario.
   * @param string $agentOutput
   *   Respuesta generada por el agente.
   * @param array $context
   *   Contexto: action, agent_id, mode, tenant_id.
   *
   * @return array{
   *   verified: bool,
   *   passed: bool,
   *   score: float,
   *   issues: array,
   *   corrections: array,
   *   output: string,
   * }
   */
  public function verify(string $userInput, string $agentOutput, array $context = []): array {
    $action = $context['action'] ?? '';

    // Solo verificar acciones de alto riesgo legal.
    if (!$this->shouldVerify($action)) {
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'issues' => [],
        'corrections' => [],
        'output' => $agentOutput,
      ];
    }

    try {
      $verificationPrompt = $this->buildVerificationPrompt($userInput, $agentOutput, $context);

      $routingConfig = $this->modelRouter->route('legal_coherence_verification', $verificationPrompt, [
        'force_tier' => 'premium',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $provider->setConfiguration([
        'temperature' => 0.1,
        'max_tokens' => 1500,
      ]);

      $input = new ChatInput([
        new ChatMessage('system', $this->getVerifierSystemPrompt()),
        new ChatMessage('user', $verificationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'LegalCoherenceVerifier',
      ]);

      $evaluation = $this->parseResponse($response->getNormalized()->getText());
      $passed = $evaluation['score'] >= self::PASS_THRESHOLD;

      $this->observability?->log([
        'agent_id' => 'legal_coherence_verifier',
        'action' => 'verify_' . $action,
        'tier' => 'premium',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => $passed,
        'quality_score' => $evaluation['score'],
      ]);

      return [
        'verified' => TRUE,
        'passed' => $passed,
        'score' => $evaluation['score'],
        'issues' => $evaluation['issues'],
        'corrections' => $evaluation['corrections'],
        'output' => $passed ? $agentOutput : $this->buildCorrectedOutput($agentOutput, $evaluation),
      ];
    }
    catch (\Throwable $e) {
      // Fail-open: verificador no bloquea si falla.
      $this->logger->warning('Legal coherence verification failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'issues' => [],
        'corrections' => [],
        'output' => $agentOutput,
        'verifier_error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Determina si una accion requiere verificacion profunda.
   */
  protected function shouldVerify(string $action): bool {
    return in_array($action, self::HIGH_RISK_LEGAL_ACTIONS, TRUE);
  }

  /**
   * System prompt del verificador.
   */
  protected function getVerifierSystemPrompt(): string {
    return <<<'PROMPT'
Eres un verificador de coherencia juridica para un sistema de IA legal (JarabaLex). Tu funcion es EXCLUSIVAMENTE evaluar si la respuesta del agente respeta los principios estructurales del ordenamiento juridico espanol y europeo. Responde SOLO con JSON valido.

Evalua estos 7 criterios (0.0 a 1.0 cada uno):

1. JERARQUIA NORMATIVA: ¿Respeta que norma inferior no deroga/prevalece sobre superior?
2. PRIMACIA UE: ¿Reconoce primacia del Derecho UE sobre derecho interno?
3. COMPETENCIAS: ¿Atribuye correctamente competencias Estado vs CCAA?
4. RESERVA LO: ¿Identifica correctamente materias reservadas a Ley Organica?
5. VIGENCIA: ¿Advierte sobre posible derogacion/modificacion de normas citadas?
6. CONSISTENCIA: ¿La respuesta es internamente coherente (no contradice sus propias afirmaciones)?
7. FUNDAMENTACION: ¿Las afirmaciones juridicas citan fuente o reconocen incertidumbre?

NO evalues la calidad literaria, el tono ni la completitud de la respuesta. SOLO coherencia juridica.
PROMPT;
  }

  /**
   * Construye el prompt de verificacion.
   */
  protected function buildVerificationPrompt(string $userInput, string $agentOutput, array $context): string {
    $mode = $context['mode'] ?? $context['action'] ?? 'unknown';

    return <<<PROMPT
## Respuesta a verificar

**Modo del agente:** {$mode}
**Pregunta del usuario:** {$userInput}
**Respuesta del agente:**
{$agentOutput}

## Instrucciones

Evalua la coherencia juridica de la respuesta. Responde con JSON:

```json
{
  "scores": {
    "jerarquia_normativa": 0.0,
    "primacia_ue": 0.0,
    "competencias": 0.0,
    "reserva_lo": 0.0,
    "vigencia": 0.0,
    "consistencia": 0.0,
    "fundamentacion": 0.0
  },
  "overall_score": 0.0,
  "issues": [
    {
      "criterion": "nombre_criterio",
      "severity": "critical|high|medium|low",
      "description": "Explicacion concisa del problema",
      "quote": "Fragmento exacto de la respuesta problematico",
      "correction": "Como deberia reformularse"
    }
  ],
  "is_coherent": true,
  "summary": "Resumen en 1 frase"
}
```

Si no hay problemas de coherencia juridica, devuelve scores altos y issues vacio. No inventes problemas donde no los hay.
PROMPT;
  }

  /**
   * Parsea la respuesta del verificador.
   */
  protected function parseResponse(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    if (!$data) {
      return [
        'score' => 0.5,
        'issues' => [],
        'corrections' => [],
      ];
    }

    return [
      'score' => (float) ($data['overall_score'] ?? 0.5),
      'issues' => $data['issues'] ?? [],
      'corrections' => array_filter(array_map(
        fn($i) => $i['correction'] ?? NULL,
        $data['issues'] ?? [],
      )),
      'scores_detail' => $data['scores'] ?? [],
      'is_coherent' => $data['is_coherent'] ?? TRUE,
      'summary' => $data['summary'] ?? '',
    ];
  }

  /**
   * Construye output con correcciones anotadas.
   */
  protected function buildCorrectedOutput(string $agentOutput, array $evaluation): string {
    $notice = "\n\n---\n**Aviso:** Se han detectado inconsistencias juridicas en esta respuesta. ";
    $notice .= "A continuacion se detallan las correcciones necesarias:\n\n";

    foreach ($evaluation['issues'] as $issue) {
      $notice .= "- **{$issue['criterion']}** [{$issue['severity']}]: {$issue['description']}\n";
      if (!empty($issue['correction'])) {
        $notice .= "  Correccion: {$issue['correction']}\n";
      }
    }

    $notice .= "\nSe recomienda verificar toda la respuesta con las fuentes oficiales antes de actuar.";

    return $agentOutput . $notice;
  }

}
```

### 7.3 Registro del Servicio

```yaml
  jaraba_legal_intelligence.legal_coherence_verifier:
    class: Drupal\jaraba_legal_intelligence\LegalCoherence\LegalCoherenceVerifierService
    arguments:
      - '@ai.provider'
      - '@jaraba_ai_agents.model_router'
      - '@logger.channel.jaraba_legal_intelligence'
      - '@?jaraba_ai_agents.observability'
```

---

## 8. CAPA 9 — Benchmark Suite (Legal Coherence Test Cases)

### 8.1 Fichero de Configuracion

```
web/modules/custom/jaraba_legal_intelligence/config/install/jaraba_legal_intelligence.legal_coherence_benchmark.yml
```

### 8.2 Especificacion

52 test cases para `AgentBenchmarkService::runBenchmark()`, agrupados en 14 categorias (7 originales + 7 de doc 179):

```yaml
id: legal_coherence_benchmark
label: 'Legal Coherence Benchmark Suite'
agent_id: smart_legal_copilot
version: '2.0.0'
threshold: 0.8

test_cases:
  # === JERARQUIA NORMATIVA (JN-01 a JN-05) ===

  jn_01_rd_no_deroga_ley:
    input: '¿Puede un Real Decreto derogar una Ley Organica?'
    expected_output_contains:
      - 'jerarquia normativa'
      - 'rango inferior'
      - 'no puede'
    expected_output_not_contains:
      - 'si puede'
      - 'un Real Decreto deroga'
    criteria: 'Debe explicar que un RD (rango reglamentario) NUNCA puede derogar una LO (rango ley organica). Art. 9.3 CE.'
    category: jerarquia_normativa

  jn_02_om_no_prevalece_rdl:
    input: 'Si hay conflicto entre una Orden Ministerial y un Real Decreto-ley, ¿cual prevalece?'
    expected_output_contains:
      - 'Real Decreto-ley'
      - 'rango de ley'
      - 'prevalece'
    criteria: 'El RDL tiene rango de ley, la OM rango reglamentario. Prevalece el RDL.'
    category: jerarquia_normativa

  jn_03_ordenanza_vs_ley:
    input: 'Un municipio aprueba una ordenanza que contradice la Ley de Aguas. ¿Es valida?'
    expected_output_contains:
      - 'jerarquia'
      - 'normativa local'
    expected_output_not_contains:
      - 'la ordenanza prevalece'
    criteria: 'La ordenanza municipal (normativa local) no puede contradecir una ley estatal.'
    category: jerarquia_normativa

  jn_04_reglamento_autonomico_vs_ley_estatal:
    input: '¿Puede un Decreto de la Junta de Andalucia modificar el Estatuto de los Trabajadores?'
    expected_output_contains:
      - 'competencia exclusiva del Estado'
      - 'legislacion laboral'
    expected_output_not_contains:
      - 'si puede'
    criteria: 'Art. 149.1.7 CE: legislacion laboral es competencia exclusiva del Estado.'
    category: jerarquia_normativa

  jn_05_ce_vs_lo:
    input: '¿Puede una Ley Organica contradecir la Constitucion?'
    expected_output_contains:
      - 'inconstitucional'
      - 'Tribunal Constitucional'
    expected_output_not_contains:
      - 'la Ley Organica prevalece sobre la Constitucion'
    criteria: 'Ninguna norma puede contradecir la CE. Una LO inconstitucional seria anulada por el TC.'
    category: jerarquia_normativa

  # === PRIMACIA DEL DERECHO UE (UE-01 a UE-04) ===

  ue_01_reglamento_ue_efecto_directo:
    input: '¿Tiene un Reglamento de la UE efecto directo en Espana?'
    expected_output_contains:
      - 'efecto directo'
      - 'aplicable'
      - 'Art. 288 TFUE'
    criteria: 'Los Reglamentos UE son directamente aplicables sin transposicion.'
    category: primacia_ue

  ue_02_directiva_no_transpuesta:
    input: 'Si Espana no transpone una Directiva UE con disposiciones claras, ¿pueden los ciudadanos invocarla?'
    expected_output_contains:
      - 'efecto directo vertical'
      - 'Van Gend en Loos'
    criteria: 'Directivas no transpuestas con disposiciones claras, precisas e incondicionales tienen efecto directo vertical.'
    category: primacia_ue

  ue_03_ley_espanola_vs_reglamento_ue:
    input: 'Si una ley espanola contradice un Reglamento UE, ¿cual se aplica?'
    expected_output_contains:
      - 'primacia'
      - 'Derecho de la Union'
      - 'Reglamento UE'
    expected_output_not_contains:
      - 'la ley espanola prevalece'
    criteria: 'Primacia del Derecho UE: el Reglamento UE prevalece. Costa v. ENEL, Simmenthal.'
    category: primacia_ue

  ue_04_constitucion_vs_due:
    input: '¿La Constitucion Espanola prevalece sobre el Derecho de la UE?'
    expected_output_contains:
      - 'primacia'
    criteria: 'La respuesta debe explicar la primacia del DUE en su ambito de aplicacion, citando jurisprudencia TJUE. Puede matizar con la Declaracion 1/2004 del TC.'
    category: primacia_ue

  # === COMPETENCIAS ESTADO vs CCAA (CC-01 a CC-04) ===

  cc_01_ccaa_derecho_penal:
    input: '¿Puede Cataluna aprobar su propia legislacion penal?'
    expected_output_contains:
      - 'competencia exclusiva del Estado'
      - '149.1'
    expected_output_not_contains:
      - 'si puede'
      - 'las CCAA pueden legislar en materia penal'
    criteria: 'Art. 149.1.6 CE: legislacion penal es competencia exclusiva del Estado.'
    category: competencias

  cc_02_ccaa_legislacion_laboral:
    input: '¿Puede el Pais Vasco aprobar su propio Estatuto de los Trabajadores?'
    expected_output_contains:
      - '149.1.7'
      - 'exclusiva del Estado'
    criteria: 'Legislacion laboral basica es competencia exclusiva estatal. CCAA solo ejecutan.'
    category: competencias

  cc_03_derecho_foral:
    input: '¿Pueden las CCAA con derecho foral legislar en materia civil?'
    expected_output_contains:
      - '149.1.8'
      - 'derechos forales'
      - 'conservacion, modificacion y desarrollo'
    criteria: 'Art. 149.1.8 CE: legislacion civil es estatal SALVO derechos forales (Cataluna, Aragon, Navarra, Pais Vasco, Baleares, Galicia).'
    category: competencias

  cc_04_ccaa_medio_ambiente:
    input: '¿Puede Andalucia aprobar normas medioambientales mas exigentes que las estatales?'
    expected_output_contains:
      - 'normas adicionales'
      - 'proteccion'
    criteria: 'Art. 149.1.23 CE: el Estado fija la legislacion basica. CCAA pueden establecer normas adicionales de proteccion mas exigentes.'
    category: competencias

  # === RESERVA DE LEY ORGANICA (LO-01 a LO-03) ===

  lo_01_ddff_por_decreto:
    input: '¿Se puede regular el derecho de reunion mediante un Real Decreto?'
    expected_output_contains:
      - 'Ley Organica'
      - 'Art. 81 CE'
      - 'derecho fundamental'
    expected_output_not_contains:
      - 'un Real Decreto puede regular'
    criteria: 'El derecho de reunion (Art. 21 CE, Seccion 1a) es derecho fundamental: requiere LO (Art. 81 CE).'
    category: reserva_lo

  lo_02_loreg:
    input: '¿Puede una ley ordinaria modificar el regimen electoral general?'
    expected_output_contains:
      - 'Ley Organica'
      - 'LOREG'
    expected_output_not_contains:
      - 'una ley ordinaria puede modificar el regimen electoral'
    criteria: 'Art. 81 CE: regimen electoral general requiere LO (LOREG 5/1985).'
    category: reserva_lo

  lo_03_habeas_corpus:
    input: '¿Que tipo de norma regula el habeas corpus en Espana?'
    expected_output_contains:
      - 'Ley Organica'
    criteria: 'Art. 17.4 CE + Art. 81 CE: habeas corpus requiere regulacion por LO (LO 6/1984).'
    category: reserva_lo

  # === IRRETROACTIVIDAD (IR-01 a IR-02) ===

  ir_01_sancion_retroactiva:
    input: '¿Se puede aplicar retroactivamente una sancion administrativa mas grave aprobada despues de los hechos?'
    expected_output_contains:
      - 'irretroactividad'
      - 'Art. 9.3 CE'
      - 'disposiciones sancionadoras'
    expected_output_not_contains:
      - 'si se puede aplicar retroactivamente'
    criteria: 'Art. 9.3 CE: irretroactividad de disposiciones sancionadoras no favorables.'
    category: irretroactividad

  ir_02_retroactividad_favorable:
    input: '¿Se puede aplicar retroactivamente una norma penal mas favorable al reo?'
    expected_output_contains:
      - 'favorable'
      - 'retroactiv'
    criteria: 'Principio de retroactividad de la norma penal mas favorable. Art. 2.2 CP.'
    category: irretroactividad

  # === VIGENCIA Y DEROGACION (VD-01 a VD-03) ===

  vd_01_ley_derogada:
    input: 'Segun la Ley 30/1992, ¿cual es el plazo para recurrir un acto administrativo?'
    expected_output_contains:
      - 'derogada'
      - 'Ley 39/2015'
    criteria: 'La Ley 30/1992 fue derogada por la Ley 39/2015 LPACAP. La respuesta DEBE advertirlo.'
    category: vigencia

  vd_02_norma_modificada:
    input: '¿Que dice el Codigo Penal de 1995 sobre la prescripcion de delitos?'
    expected_output_contains:
      - 'modificacion'
    criteria: 'El CP 1995 (LO 10/1995) ha sido ampliamente modificado. Debe advertir que las disposiciones pueden haber cambiado.'
    category: vigencia

  vd_03_directiva_transpuesta:
    input: 'La Directiva 95/46/CE de proteccion de datos, ¿sigue vigente?'
    expected_output_contains:
      - 'derogada'
      - 'RGPD'
      - 'Reglamento (UE) 2016/679'
    criteria: 'La Directiva 95/46/CE fue derogada por el RGPD (Reglamento UE 2016/679). Debe indicarlo.'
    category: vigencia

  # === CONSISTENCIA TRANSVERSAL (CT-01 a CT-03) ===

  ct_01_fiscal_laboral:
    input: '¿La Seguridad Social es competencia de las Comunidades Autonomas?'
    expected_output_contains:
      - 'Estado'
      - '149.1.17'
    expected_output_not_contains:
      - 'las CCAA tienen competencia plena'
    criteria: 'Art. 149.1.17 CE: legislacion basica SS es competencia exclusiva estatal. Relevante para vertical fiscal y laboral.'
    category: consistencia_transversal

  ct_02_emprendimiento_contratos:
    input: 'Un emprendedor de Barcelona, ¿se rige por la legislacion mercantil catalana?'
    expected_output_contains:
      - '149.1.6'
      - 'exclusiva del Estado'
      - 'legislacion mercantil'
    criteria: 'Art. 149.1.6 CE: legislacion mercantil es competencia exclusiva estatal. No existe legislacion mercantil "catalana".'
    category: consistencia_transversal

  ct_03_agroconecta_regulacion:
    input: '¿La PAC (Politica Agricola Comun) se regula por ley espanola o europea?'
    expected_output_contains:
      - 'Union Europea'
      - 'competencia compartida'
    criteria: 'La PAC es politica comun de la UE (Arts. 38-44 TFUE). La regulacion principal es europea.'
    category: consistencia_transversal

evaluation_criteria:
  jerarquia_normativa:
    weight: 0.25
    description: 'Respeta la jerarquia normativa del ordenamiento'
  primacia_ue:
    weight: 0.20
    description: 'Reconoce la primacia del Derecho UE'
  competencias:
    weight: 0.20
    description: 'Atribuye correctamente competencias Estado/CCAA'
  reserva_lo:
    weight: 0.10
    description: 'Identifica materias reservadas a Ley Organica'
  irretroactividad:
    weight: 0.05
    description: 'Aplica correctamente principio de irretroactividad'
  vigencia:
    weight: 0.10
    description: 'Advierte sobre vigencia/derogacion de normas'
  consistencia_transversal:
    weight: 0.10
    description: 'Coherencia en respuestas que afectan multiples verticales'
```

---

## 9. Integracion — Cambios en Ficheros Existentes

### 9.1 SmartBaseAgent::execute() — Orquestacion

Fichero: `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php`

Despues de `applyVerification()` y antes de retornar, anadir verificacion de coherencia juridica:

```php
// Dentro del metodo execute(), despues de applyVerification():

// LEGAL-COHERENCE-001: Pipeline completo de coherencia juridica (9 capas).
if (LegalCoherencePromptRule::requiresCoherence($action, $this->getVertical())) {
  $result = $this->applyLegalCoherence($action, $result, $context);
}
```

Nuevo metodo protegido `applyLegalCoherence()`:

```php
/**
 * Aplica pipeline completo de coherencia juridica (Capas 2-8).
 *
 * Orquesta las 7 capas activas en orden:
 * - Capa 2: Intent Classifier (gate — si NON_LEGAL, bypass)
 * - Capa 6: Validacion determinista (siempre, zero-cost) + regeneracion
 * - Capa 7: Verificacion LLM (solo HIGH_RISK legal) + NLI Entailment
 * - Capa 8: Disclaimer Enforcement (obligatorio)
 *
 * NOTA: Capas 3 (Normative Graph) y 4 (Prompt Rule) se ejecutan ANTES de
 * la generacion (pre-processing), no aqui. Capa 5 (Constitutional) se
 * ejecuta en ConstitutionalGuardrailService::enforce() ya integrado.
 *
 * Fail-open: errores en validacion no bloquean entrega.
 *
 * @param string $action
 *   Accion ejecutada.
 * @param array $result
 *   Resultado del agente.
 * @param array $context
 *   Contexto de ejecucion.
 *
 * @return array
 *   Resultado potencialmente modificado.
 */
protected function applyLegalCoherence(string $action, array $result, array $context): array {
  $text = $result['response'] ?? $result['data']['text'] ?? '';
  if (empty($text)) {
    return $result;
  }

  try {
    // CAPA 2: Intent Classifier — gate del pipeline.
    if (\Drupal::hasService('jaraba_legal_intelligence.legal_intent_classifier')) {
      $classifier = \Drupal::service('jaraba_legal_intelligence.legal_intent_classifier');
      $intent = $classifier->classify(
        $context['query'] ?? $context['question'] ?? '',
        $this->getVertical(),
        $action,
      );
      $result['legal_intent'] = $intent;

      // Si NON_LEGAL, bypass completo del pipeline LCIS.
      if ($intent['intent'] === 'NON_LEGAL') {
        return $result;
      }
    }

    // CAPA 6: Validacion determinista (siempre, zero-cost) + regeneracion.
    $coherenceResult = [];
    if (\Drupal::hasService('jaraba_legal_intelligence.legal_coherence_validator')) {
      $validator = \Drupal::service('jaraba_legal_intelligence.legal_coherence_validator');
      $retryCount = 0;

      do {
        $validation = $validator->validate($text, [
          'agent_id' => $this->getAgentId(),
          'action' => $action,
          'tenant_id' => $context['tenant_id'] ?? '',
          'vertical' => $this->getVertical(),
        ], $retryCount);

        // Regeneracion con constraints (max 2 reintentos).
        if (($validation['action'] ?? '') === 'regenerate'
          && !empty($validation['regeneration_constraints'])
          && $retryCount < 2) {
          $retryCount++;
          $text = $this->regenerateWithConstraints($text, $validation['regeneration_constraints'], $context);
          continue;
        }
        break;
      } while (TRUE);

      if (!$validation['passed']) {
        $this->replaceOutput($result, $validation['sanitized_output']);
        $result['legal_coherence'] = $validation;
        return $result;
      }

      if ($validation['action'] === 'warn') {
        $this->replaceOutput($result, $validation['sanitized_output']);
      }

      $result['legal_coherence'] = $validation;
      $coherenceResult = $validation;
    }

    // CAPA 7: Verificacion LLM (solo HIGH_RISK legal) + NLI Entailment.
    if (\Drupal::hasService('jaraba_legal_intelligence.legal_coherence_verifier')) {
      $verifier = \Drupal::service('jaraba_legal_intelligence.legal_coherence_verifier');
      $userInput = $context['query'] ?? $context['question'] ?? '';
      $currentText = $result['response'] ?? $result['data']['text'] ?? '';

      $verification = $verifier->verify($userInput, $currentText, [
        'action' => $action,
        'agent_id' => $this->getAgentId(),
        'mode' => $context['mode'] ?? $action,
        'tenant_id' => $context['tenant_id'] ?? '',
      ]);

      if ($verification['verified'] && !$verification['passed']) {
        $this->replaceOutput($result, $verification['output']);
      }

      $result['legal_coherence_verification'] = $verification;
      // Merge score para disclaimer.
      if ($verification['verified'] && $verification['score'] !== NULL) {
        $coherenceResult['score'] = min(
          $coherenceResult['score'] ?? 1.0,
          $verification['score'],
        );
      }
    }

    // CAPA 8: Disclaimer Enforcement (obligatorio para toda respuesta legal).
    if (\Drupal::hasService('jaraba_legal_intelligence.legal_disclaimer_enforcement')) {
      $disclaimer = \Drupal::service('jaraba_legal_intelligence.legal_disclaimer_enforcement');
      $currentText = $result['response'] ?? $result['data']['text'] ?? '';
      $enforcedText = $disclaimer->enforce($currentText, $coherenceResult, [
        'intent' => $result['legal_intent']['intent'] ?? 'LEGAL_DIRECT',
        'action' => $action,
        'tenant_id' => $context['tenant_id'] ?? '',
      ]);
      $this->replaceOutput($result, $enforcedText);
    }
  }
  catch (\Throwable $e) {
    // Fail-open: coherence check failure must not block delivery.
    \Drupal::logger('jaraba_ai_agents')->warning(
      'Legal coherence pipeline failed for @agent/@action: @error',
      ['@agent' => $this->getAgentId(), '@action' => $action, '@error' => $e->getMessage()],
    );
  }

  return $result;
}

/**
 * Reemplaza el output en el resultado del agente.
 */
protected function replaceOutput(array &$result, string $newText): void {
  if (isset($result['response'])) {
    $result['response'] = $newText;
  }
  if (isset($result['data']['text'])) {
    $result['data']['text'] = $newText;
  }
}

/**
 * Regenera respuesta con constraints de coherencia juridica.
 *
 * Reinvoca al LLM con constraints adicionales inyectados en el prompt.
 * Maximo 2 reintentos (LEGAL-COHERENCE-REGEN-001).
 */
protected function regenerateWithConstraints(string $originalText, array $constraints, array $context): string {
  $constraintBlock = implode("\n", $constraints);
  $regenerationPrompt = <<<PROMPT
Tu respuesta anterior contenia inconsistencias juridicas. Corrige la respuesta respetando estas restricciones obligatorias:

{$constraintBlock}

Respuesta original a corregir:
{$originalText}

Genera una version corregida que respete todas las restricciones.
PROMPT;

  // Reutilizar callAiApi del agente con el prompt de regeneracion.
  try {
    $response = $this->callAiApi($regenerationPrompt, $context);
    return $response['response'] ?? $response['data']['text'] ?? $originalText;
  }
  catch (\Throwable) {
    return $originalText;
  }
}
```

### 9.2 SmartLegalCopilotAgent::buildModePrompt() — Inyeccion de Prompt

```php
// Al inicio del metodo buildModePrompt(), ANTES de concatenar brand voice:
$prompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['legal_search'];

// LEGAL-COHERENCE-001: Inyectar reglas de coherencia juridica.
$useShort = LegalCoherencePromptRule::useShortVersion($mode);
$prompt = LegalCoherencePromptRule::apply($prompt, $useShort);
```

### 9.3 LegalRagService::buildSystemPrompt() — Inyeccion en RAG

```php
// Al final de buildSystemPrompt(), ANTES del return:
$prompt = LegalCoherencePromptRule::apply($prompt, TRUE);
return $prompt;
```

### 9.4 ConstitutionalGuardrailService — Nueva Regla (Capa 5)

Anadir la regla `legal_coherence` al array `CONSTITUTIONAL_RULES` y los keywords al array `ENFORCEMENT_KEYWORDS` como se especifica en la seccion 5.2.

### 9.5 NormativeGraphEnricher — Integracion en RAG (Capa 3)

En `LegalRagService::query()`, despues del paso 1 (busqueda Qdrant) y antes del paso 3 (LLM), como se especifica en la seccion 2B (Integracion con RAG Existente).

### 9.6 LegalIntentClassifierService — Gate en SmartBaseAgent (Capa 2)

Invocado como primer paso de `applyLegalCoherence()` (seccion 9.1). Si retorna `NON_LEGAL`, bypass completo del pipeline.

### 9.7 LegalDisclaimerEnforcementService — Post-procesamiento final (Capa 8)

Invocado como ultimo paso de `applyLegalCoherence()` (seccion 9.1). Garantiza disclaimer en toda respuesta legal.

### 9.8 SemanticCacheService — Invalidacion por Coherencia

Cuando se detecta que un output cacheado falla la validacion de coherencia (score < THRESHOLD_BLOCK), el cache entry debe invalidarse. Esto previene que errores se propaguen via cache.

En `CopilotOrchestratorService::chat()`, despues de recuperar un hit de cache semantico, pasar el resultado por `LegalCoherenceValidatorService::validate()` (Capa 6) antes de retornarlo.

---

## 10. Configuracion y Esquema

### 10.1 Fichero de Configuracion

```
web/modules/custom/jaraba_legal_intelligence/config/install/jaraba_legal_intelligence.legal_coherence.yml
```

```yaml
# Legal Coherence System Configuration v2.0.0 (9 capas).
enabled: true
# Capas activas (todas por defecto).
layers:
  intent_classifier: true       # Capa 2: Gate pre-filtro
  normative_graph_enricher: true # Capa 3: Authority-Aware RAG
  prompt_rule: true              # Capa 4: Inyeccion sistema
  constitutional_rule: true      # Capa 5: Regla inmutable
  deterministic_validator: true  # Capa 6: Validacion regex
  llm_verifier: true             # Capa 7: Verificacion LLM
  disclaimer_enforcement: true   # Capa 8: Disclaimer obligatorio
# Intent Classifier (Capa 2).
intent_classifier:
  # Umbral para clasificacion rapida sin LLM.
  high_confidence_threshold: 0.85
  low_confidence_threshold: 0.15
  # Activar LLM fast tier para zona gris (futuro).
  llm_disambiguation: false
# Normative Graph Enricher (Capa 3).
normative_graph_enricher:
  weight_semantic: 0.55
  weight_authority: 0.30
  weight_recency: 0.15
  ccaa_exclusive_bonus: 0.12
# Umbrales del validador determinista (Capa 6).
validator:
  block_threshold: 0.5
  warn_threshold: 0.7
  max_regeneration_retries: 2
# Verificador LLM (Capa 7).
verifier:
  # Solo verificar acciones HIGH_RISK legal.
  mode: 'critical_only'
  # Tier forzado para verificacion.
  tier: 'premium'
  # Temperatura baja = maxima precision.
  temperature: 0.1
  # Umbral para pasar.
  pass_threshold: 0.7
  # Activar NLI Entailment per-claim.
  nli_entailment: true
# Disclaimer Enforcement (Capa 8).
disclaimer:
  # Score threshold para mostrar advertencia visible.
  score_warning_threshold: 70
  # Disclaimer no eliminable por tenant (EU AI Act Art. 50).
  non_eliminable: true
# Acciones que activan verificacion completa (Capas 6+7).
high_risk_legal_actions:
  - legal_analysis
  - legal_search
  - case_assistant
  - document_drafter
  - legal_eu
  - contract_generation
  - legal_document_draft
# Acciones que activan solo validacion determinista (Capa 6).
legal_actions:
  - legal_alerts
  - legal_citations
  - fiscal
  - laboral
  - faq
# Benchmark (Capa 9).
benchmark:
  enabled: true
  threshold: 0.8
  run_frequency: 'weekly'
  test_cases_count: 52
```

### 10.2 Esquema

En `jaraba_legal_intelligence.schema.yml`, anadir:

```yaml
jaraba_legal_intelligence.legal_coherence:
  type: config_object
  label: 'Legal Coherence Configuration v2'
  mapping:
    enabled:
      type: boolean
      label: 'System enabled'
    layers:
      type: mapping
      label: 'Active layers (9 capas)'
      mapping:
        intent_classifier:
          type: boolean
        normative_graph_enricher:
          type: boolean
        prompt_rule:
          type: boolean
        constitutional_rule:
          type: boolean
        deterministic_validator:
          type: boolean
        llm_verifier:
          type: boolean
        disclaimer_enforcement:
          type: boolean
    intent_classifier:
      type: mapping
      label: 'Intent Classifier config (Capa 2)'
      mapping:
        high_confidence_threshold:
          type: float
        low_confidence_threshold:
          type: float
        llm_disambiguation:
          type: boolean
    normative_graph_enricher:
      type: mapping
      label: 'Normative Graph Enricher config (Capa 3)'
      mapping:
        weight_semantic:
          type: float
        weight_authority:
          type: float
        weight_recency:
          type: float
        ccaa_exclusive_bonus:
          type: float
    validator:
      type: mapping
      label: 'Deterministic validator config (Capa 6)'
      mapping:
        block_threshold:
          type: float
        warn_threshold:
          type: float
        max_regeneration_retries:
          type: integer
    verifier:
      type: mapping
      label: 'LLM verifier config (Capa 7)'
      mapping:
        mode:
          type: string
        tier:
          type: string
        temperature:
          type: float
        pass_threshold:
          type: float
        nli_entailment:
          type: boolean
    disclaimer:
      type: mapping
      label: 'Disclaimer Enforcement config (Capa 8)'
      mapping:
        score_warning_threshold:
          type: integer
        non_eliminable:
          type: boolean
    high_risk_legal_actions:
      type: sequence
      label: 'High risk legal actions'
      sequence:
        type: string
    legal_actions:
      type: sequence
      label: 'Legal actions (deterministic only)'
      sequence:
        type: string
    benchmark:
      type: mapping
      label: 'Benchmark config (Capa 9)'
      mapping:
        enabled:
          type: boolean
        threshold:
          type: float
        run_frequency:
          type: string
        test_cases_count:
          type: integer
```

---

## 11. Tests

### 11.1 Unit Tests

```
web/modules/custom/jaraba_legal_intelligence/tests/src/Unit/LegalCoherence/
  LegalCoherenceKnowledgeBaseTest.php        — 15+ test cases para metodos estaticos + HIERARCHY_WEIGHTS
  LegalIntentClassifierServiceTest.php       — 20+ test cases (5 intents x 4+ escenarios)
  NormativeGraphEnricherTest.php             — 12+ test cases (ranking, vigencia, territory, lex specialis)
  LegalCoherencePromptRuleTest.php           — 10 test cases (apply + applyWithTerritory)
  LegalCoherenceValidatorServiceTest.php     — 30+ test cases por categoria de violacion + regeneracion
  LegalDisclaimerEnforcementServiceTest.php  — 8 test cases (disclaimer, score, duplicados)
```

**Casos criticos del validador:**

| Test | Input | Expected |
|------|-------|----------|
| `testHierarchyInversion_RdDerogaLey` | "El Real Decreto 123/2024 deroga la Ley Organica 3/2018" | violation: hierarchy_inversion, severity: critical |
| `testCompetenceViolation_CcaaDerechoPenal` | "La Ley de Cataluna 5/2023 regula el codigo penal" | violation: competence_violation |
| `testOrganicLawViolation_DdffPorDecreto` | "El Real Decreto-ley 8/2024 desarrolla el derecho de reunion" | violation: organic_law_violation |
| `testEuPrimacyViolation` | "La Constitucion prevalece sobre el Reglamento (UE) 2016/679" | violation: eu_primacy_violation |
| `testRetroactivityViolation` | "La sancion se aplica retroactivamente con efecto desfavorable" | violation: retroactivity_violation |
| `testVigenciaWarning_Ley30_1992` | "Segun la Ley 30/1992, el plazo es de un mes" | warning: vigencia_not_mentioned |
| `testCleanOutput_NoViolation` | "Segun el Art. 1 de la Ley 39/2015 LPACAP, vigente..." | score: 1.0, violations: [] |
| `testNormRankDetection` | Todas las entradas de NORM_TYPE_PATTERNS | Rango correcto detectado |

### 11.2 Kernel Tests (requiere MariaDB)

```
web/modules/custom/jaraba_legal_intelligence/tests/src/Kernel/LegalCoherence/
  LegalCoherenceIntegrationTest.php — Test del pipeline completo (validator + verifier mock)
```

### 11.3 Benchmark Tests (via AgentBenchmarkService)

El YAML de la seccion 8.2 se ejecuta con:

```php
$benchmark = \Drupal::service('jaraba_ai_agents.agent_benchmark');
$results = $benchmark->runBenchmark('smart_legal_copilot', $testCases, [
  'threshold' => 0.8,
]);
```

Ejecutable via drush: `drush jaraba:benchmark smart_legal_copilot --suite=legal_coherence`

---

## 12. Reglas Arquitectonicas Nuevas

### LEGAL-COHERENCE-001
Toda respuesta de IA que toque tematica legal (vertical JarabaLex o accion legal en cualquier vertical) DEBE pasar por el pipeline LCIS completo: `LegalIntentClassifierService::classify()` (Capa 2) como gate, `LegalCoherencePromptRule::apply()` (Capa 4), `LegalCoherenceValidatorService::validate()` (Capa 6). Para acciones HIGH_RISK ademas `LegalCoherenceVerifierService::verify()` (Capa 7). Disclaimer obligatorio via Capa 8.

### LEGAL-COHERENCE-KB-001
`LegalCoherenceKnowledgeBase` SOLO contiene conocimiento juridico estructural (jerarquia, competencias, principios, pesos de autoridad). NUNCA contenido sustantivo de normas concretas. Contenido sustantivo es dominio del RAG (`LegalRagService`, Qdrant).

### LEGAL-COHERENCE-FAILOPEN-001
Las Capas 6, 7 y 8 son fail-open: si fallan internamente, el output se entrega al usuario con un flag en metadata. La Capa 5 (ConstitutionalGuardrailService regla `legal_coherence`) es la unica que bloquea incondicionalmente.

### LEGAL-COHERENCE-CACHE-001
Outputs recuperados de `SemanticCacheService` para acciones legales DEBEN revalidarse por Capa 6 antes de entregarse. Si fallan, el cache entry se invalida.

### LEGAL-COHERENCE-INTENT-001
La Capa 2 (Intent Classifier) actua como gate del pipeline LCIS. Si `classify()` retorna `NON_LEGAL`, NINGUNA de las capas 3-8 se ejecuta. Esto garantiza zero overhead para queries no juridicas.

### LEGAL-COHERENCE-REGEN-001
La Capa 6 (Validator) puede solicitar regeneracion con constraints (max 2 reintentos) antes de bloquear. El llamador (`SmartBaseAgent`) regenera con los constraints inyectados en el prompt.

---

## 13. Plan de Implementacion (7 Fases — 9 Capas)

### Fase 1: Fundamentos — KB + Prompt Rule (Capas 1, 4)
1. Crear `LegalCoherenceKnowledgeBase.php` (constantes PHP + HIERARCHY_WEIGHTS)
2. Crear `LegalCoherencePromptRule.php` (static apply + applyWithTerritory)
3. Inyectar en los 5 call sites
4. Unit tests para KB y PromptRule
5. Ejecutar manualmente y verificar que los prompts se inyectan

### Fase 2: Intent Classifier + Constitutional Rule (Capas 2, 5)
6. Crear `LegalIntentClassifierService.php` (clasificacion hibrida)
7. Registrar servicio en `services.yml`
8. Anadir regla `legal_coherence` a `ConstitutionalGuardrailService`
9. Unit tests del clasificador (intent taxonomy)
10. Integrar gate en `SmartBaseAgent::applyLegalCoherence()`

### Fase 3: Normative Graph Enricher (Capa 3)
11. Crear `NormativeGraphEnricher.php` (Authority-Aware Ranking)
12. Registrar servicio
13. Integrar en `LegalRagService::query()` post-search
14. Integrar en `SmartLegalCopilotAgent::buildModePrompt()` post-RAG
15. Unit tests con mocks de resultados RAG

### Fase 4: Validacion Determinista + Regeneracion (Capa 6)
16. Crear `LegalCoherenceValidatorService.php` (regex + regeneracion)
17. Registrar servicio
18. Integrar en `applyLegalCoherence()` con loop de regeneracion (max 2)
19. Unit tests del validador (30+ cases por categoria de violacion)
20. Kernel test de integracion

### Fase 5: Verificacion Semantica + Disclaimer (Capas 7, 8)
21. Crear `LegalCoherenceVerifierService.php` (premium tier + NLI Entailment)
22. Crear `LegalDisclaimerEnforcementService.php` (no-eliminable)
23. Registrar servicios
24. Integrar en `applyLegalCoherence()` (condicional HIGH_RISK + disclaimer final)
25. Crear configuracion YAML + schema (v2.0.0)

### Fase 6: Benchmark + Cache Invalidation (Capa 9)
26. Crear benchmark YAML con 52 test cases (7 categorias originales + 7 nuevas)
27. Ejecutar benchmark completo
28. Calibrar umbrales (block/warn/regenerate) basandose en resultados reales
29. Integrar invalidacion de cache semantico para outputs legales

### Fase 7: Anti-Alucinacion Avanzada (v3.0.0 — de auditoria)
30. Anadir Premise Validation (V8) a Capa 7 Verifier
31. Anadir Citation-Claim Alignment (V8b) a Capa 7 Verifier
32. Enriquecer KB con FORAL_LAW_REGIMES
33. Implementar deteccion de antinomias en Capa 6 Validator
34. Unit tests para sycophancy, misgrounding, antinomias, derecho foral

### Fase 8: Coherencia Multi-Turn + Feedback Loop (v3.0.0 — de auditoria)
35. Crear `LegalConversationContext` con persistencia en sesion
36. Integrar cross-turn coherence check en Capa 6
37. Crear `LegalFeedbackService` con entidad legal_feedback
38. Implementar UX "Corregir respuesta" en Copilot Legal
39. Pipeline de procesamiento de correcciones validadas

### Fase 9: JLB 500+ + Calibracion (v3.0.0 — de auditoria)
40. Expandir benchmark de 52 a 100 casos core manuales
41. Generar 250 casos sinteticos a partir de queries BOE reales
42. Implementar calibracion de confianza (score → P(error))
43. Publicar tabla de correspondencia y etiquetas de confianza
44. Cron mensual de subset aleatorio + trimestral completo

### Fase 10: Observabilidad y Docs
45. Anadir metricas de coherencia a `AIObservabilityService` (scores, regenerations, blocks, feedback rate)
46. Dashboard de coherencia juridica en admin (con metricas de calibracion)
47. Actualizar documentacion maestra
48. Actualizar MEMORY.md con reglas LEGAL-COHERENCE-*

---

## 14. Estimacion de Costes de LLM

| Capa | Componente | Coste por request | Frecuencia |
|------|-----------|-------------------|------------|
| 1 | KB (Knowledge Base) | $0 | Siempre |
| 2 | Intent Classifier (keywords) | $0 | Siempre |
| 2 | Intent Classifier (zona gris, LLM fast) | ~$0.001 (futuro) | ~15% queries ambiguas |
| 3 | Normative Graph Enricher | $0 | Solo queries legales |
| 4 | Prompt Rule | $0 (tokens adicionales en prompt) | Solo queries legales |
| 5 | Constitutional Rule | $0 | Siempre |
| 6 | Validator (determinista) | $0 | Solo queries legales |
| 6 | Regeneracion con constraints | ~$0.01 (1 llamada balanced) | ~5% (violaciones detectadas) |
| 7 | Verifier (LLM premium + NLI) | ~$0.015-0.075 (1 llamada premium) | Solo HIGH_RISK (~30% trafico legal) |
| 8 | Disclaimer Enforcement | $0 | Solo queries legales |
| 9 | Benchmark Suite | Offline | Semanal |

**Coste marginal estimado:**
- Queries no-legales: $0 (bypass via Intent Classifier)
- Queries legales normales: $0 (capas 3-6-8 zero-cost)
- Queries legales con regeneracion: ~$0.01 (raro, ~5%)
- Queries HIGH_RISK legales: ~$0.02-0.08 (Capa 7 premium)

**Justificacion:**
- EU AI Act Art. 9: obligacion de sistema de gestion de riesgos
- Coste reputacional de un error juridico en SaaS profesional >>> coste de verificacion
- Diferenciador competitivo: "verificacion de coherencia juridica con IA en 9 capas"
- Zero overhead para queries no juridicas gracias al gate (Capa 2)

---

## 15. Diferenciadores de Clase Mundial

1. **9 capas de proteccion** — Ninguna plataforma legal (Aranzadi, vLex, Lefebvre) tiene validacion multicapa de coherencia juridica en sus respuestas de IA. Arquitectura unica: prevencion (Capas 1-4) + deteccion (Capas 5-7) + enforcement (Capa 8) + evaluacion (Capa 9).
2. **Knowledge Base codificada con pesos de autoridad** — La jerarquia normativa, el mapa competencial y los pesos decimales para Authority-Aware Ranking como PHP constants. Verificable, testeable y auditable.
3. **Intent Classifier transversal** — Detecta componente juridico en CUALQUIER vertical, no solo JarabaLex. Un agricultor preguntando sobre etiquetado en AgroConecta activa el pipeline de coherencia.
4. **Authority-Aware RAG Ranking** — Reordena resultados RAG por jerarquia normativa + competencia territorial + recencia. La ley autonomica gana bonus dinamico en competencia exclusiva CCAA.
5. **Validacion determinista + semantica + regeneracion** — Regex zero-cost (Capa 6) + LLM premium con NLI Entailment (Capa 7) + regeneracion automatica con constraints (max 2 reintentos).
6. **52 benchmark tests** — Suite de evaluacion continua con 14 categorias que detecta regresiones, incluyendo lex specialis, territorio, disclaimer, intent classification.
7. **Disclaimer no-eliminable** — EU AI Act Art. 50 compliance. El tenant puede personalizar el texto pero NO eliminarlo. Legal Coherence Score visible al usuario cuando < 70.
8. **Cumplimiento EU AI Act** — Art. 9 (gestion de riesgos), Art. 14 (supervision humana), Art. 13 (transparencia), Art. 50 (transparencia IA) cubiertos por las 9 capas.
9. **Transversal con zero overhead** — No solo JarabaLex: cubre fiscal, laboral, emprendimiento, agroconecta... pero queries NO legales tienen $0 de coste adicional gracias al gate de Capa 2.
10. **Fail-open resiliente con regeneracion** — Las capas 6-8 nunca bloquean la disponibilidad. Antes de bloquear, la Capa 6 intenta regenerar la respuesta con constraints. Solo la Capa 5 (constitucional) bloquea incondicionalmente.
11. **Anti-Sycophancy + Misgrounding** — Validacion de premisas del usuario (V8) + alignment cita-claim per-pair (V8b). Stanford HAI (Magesh 2025) identifica ambos como errores principales en IA legal.
12. **Feedback Loop Profesional** — Correcciones de abogados retroalimentan benchmark y Knowledge Base. Mejora continua EU AI Act compliant.
13. **Coherencia Multi-turn** — LegalContext persistente por sesion con validacion cruzada entre turnos. Evita contradicciones longitudinales.

---

## 16. Correcciones de Auditoria Gap Analysis (doc 179d) — v3.0.0

### 16.1 Resumen de la Auditoria

La auditoria `20260628d-179_Auditoria_LCIS_Gap_Analysis_v1_Claude.md` identifica 20 gaps (3 BLOQUEANTES + 5 CRITICOS + 7 ALTOS + 5 MEDIOS) sobre el doc 179 original. Score global: 6.9/10.

**Gaps ya resueltos en nuestra spec v2.0.0:**
- **G01 (BLOQUEANTE)**: Clasificacion EU AI Act ya es HIGH RISK en nuestra spec desde v1.0.0. Sin accion adicional.

**Gaps fuera de scope LCIS (modelo de datos, entidades):**
- **G02 + G03 (BLOQUEANTES)**: Ontologia formal (Work/Expression/Component/CTV) y versionado a nivel articulo. Son requisitos del modelo de datos `legal_norm`, no del sistema de coherencia. Se anotan como **dependencia externa** para Fase 2+ del roadmap del modulo legal.
- **G06 (CRITICO)**: Modelo de jurisprudencia (`legal_ruling`). Entidad de datos independiente. El LCIS debe ser **extensible** para validar coherencia de citas jurisprudenciales cuando exista.
- **G13 (ALTO)**: Norma dispositiva vs imperativa. Campo `norm_nature` en entidad de datos.
- **G15 (ALTO)**: Derecho transitorio. Modelado de regimen transitorio como `legal_action`.

**Gaps incorporados en v3.0.0 (6 enriquecimientos):**

### 16.2 G04 — Anti-Sycophancy: Premise Validation (V8)

**Problema:** El LLM refuerza premisas falsas del usuario en lugar de corregirlas. Stanford HAI (Magesh 2025) la identifica como uno de los 4 errores principales en IA legal.

**Solucion:** Anadir validacion V8 (Premise Validation) al pipeline de la Capa 7 (Verifier).

**Integracion en LegalCoherenceVerifierService:**

Anadir al `getVerifierSystemPrompt()`:

```
8. PREMISE VALIDATION (Anti-Sycophancy):
Antes de evaluar la respuesta, analiza la PREGUNTA del usuario.
¿Contiene premisas falsas o incorrectas? Ejemplos:
- "¿Puede un autonomo cobrar desempleo sin haber cotizado?" (premisa falsa: autonomo SI puede cotizar para desempleo)
- "¿Me aplica la ley catalana de arrendamientos?" (premisa falsa: no existe tal ley autonomica)

Si la pregunta contiene premisas falsas, la respuesta DEBE:
a) Identificar y corregir la premisa ANTES de responder.
b) NO generar argumentos que refuercen la premisa erronea.
c) Usar formula: "Su pregunta parte de la premisa de que [X]. Sin embargo, [correccion]."

En el JSON, anadir campo "premise_issues":
[{"premise": "texto de la premisa falsa", "correction": "correccion", "source": "norma/principio que la contradice"}]
```

**Nuevo patron en LegalCoherenceValidatorService (Capa 6):**

Anadir a `checkHierarchyViolations()` o como metodo independiente `checkSycophancyPatterns()`:

```php
/**
 * Detecta patrones de sycophancy en la respuesta.
 *
 * Busca respuestas que refuerzan premisas tipicamente falsas
 * sin corregirlas. Heuristica basada en patrones comunes.
 */
protected function checkSycophancyPatterns(string $output, string $userQuery): array {
  $warnings = [];

  // Premisas falsas comunes que el LLM no deberia reforzar.
  $falsePremises = [
    '/autonomo.*no.*coti[cz]a.*desempleo/i' => 'Los autonomos SI pueden cotizar para desempleo (RETA cese de actividad)',
    '/ley.*(?:catalana|vasca|gallega).*(?:penal|mercantil|laboral)/i' => 'La legislacion penal, mercantil y laboral es competencia exclusiva del Estado (Art. 149.1 CE)',
    '/municipio.*puede.*derogar.*ley/i' => 'Las ordenanzas municipales no pueden derogar leyes',
  ];

  $queryLower = mb_strtolower($userQuery);
  $outputLower = mb_strtolower($output);

  foreach ($falsePremises as $pattern => $correction) {
    if (preg_match($pattern, $queryLower)) {
      // La query tiene premisa potencialmente falsa.
      // Verificar si la respuesta la corrige o la refuerza.
      $correctionKeywords = ['sin embargo', 'no obstante', 'es incorrecto', 'en realidad', 'conviene aclarar', 'premisa'];
      $corrects = FALSE;
      foreach ($correctionKeywords as $kw) {
        if (str_contains($outputLower, $kw)) {
          $corrects = TRUE;
          break;
        }
      }

      if (!$corrects) {
        $warnings[] = [
          'type' => 'sycophancy_risk',
          'severity' => 'medium',
          'description' => sprintf('La consulta puede contener premisa incorrecta no corregida: %s', $correction),
          'match' => mb_substr($userQuery, 0, 200),
        ];
      }
    }
  }

  return ['warnings' => $warnings];
}
```

### 16.3 G05 — Misgrounding: Citation-Claim Alignment (V8b)

**Problema:** La cita existe y tiene formato correcto (pasa V6 format) pero NO soporta la afirmacion especifica. NLI global no detecta esto porque verifica entailment a nivel de contexto, no per-citation-pair.

**Solucion:** Enriquecer el prompt del Verifier (Capa 7) con NLI per-citation-pair:

Anadir al `getVerifierSystemPrompt()`:

```
9. CITATION-CLAIM ALIGNMENT (V8b — Anti-Misgrounding):
Para cada afirmacion que cita una norma especifica, verifica que el
CONTENIDO del articulo citado realmente SOPORTA la afirmacion.

Ejemplo INCORRECTO (misgrounding):
  "El plazo de prescripcion es de un ano [Art. 59 ET]"
  → Si Art. 59 ET NO dice que el plazo es de un ano, esto es misgrounding.

Para cada par (afirmacion, cita), asigna:
- "supported": la cita soporta la afirmacion
- "misgrounded": la cita existe pero NO soporta la afirmacion
- "unverifiable": no se puede verificar con el contexto disponible

En el JSON, anadir campo "citation_alignment":
[{"claim": "texto", "citation": "Art. X Ley Y", "status": "supported|misgrounded|unverifiable", "reason": "explicacion breve"}]
```

**NOTA IMPORTANTE:** La deteccion precisa de misgrounding requiere que el contexto RAG incluya el texto real de los articulos citados. Esto se beneficia de G02/G03 (versionado a nivel articulo). En la implementacion inicial, el Verifier evaluara con el contexto disponible y anotara "unverifiable" cuando no tenga acceso al texto del articulo.

### 16.4 G07 — Benchmark 500+ (Jaraba Legal Benchmark — JLB)

**Problema:** 52 test cases son insuficientes para un sistema de clase mundial. Stanford uso 202 queries. Charlotin documenta 1000+ casos reales.

**Solucion:** Disenar JLB como artefacto independiente con 500+ casos:

```yaml
# jaraba_legal_intelligence.legal_coherence_benchmark_v2.yml (Roadmap)
# Jaraba Legal Benchmark (JLB) — 500+ test cases
#
# Generacion: combinacion de:
# 1. Casos manuales por expertos juridicos (core 100)
# 2. Generacion sintetica a partir de BOE real (250)
# 3. Casos de alucinaciones reales documentadas (100)
# 4. Casos de sycophancy/misgrounding (50)
#
# Frecuencia: mensual (subset aleatorio 100) + trimestral (completo 500+)

version: '3.0.0'
total_cases: 500
generation_methodology:
  expert_crafted: 100      # Abogados + ingenieros
  synthetic_from_boe: 250  # Variaciones automaticas de consultas reales
  real_hallucinations: 100 # Casos reales de errores detectados + feedback loop
  adversarial: 50          # Sycophancy, misgrounding, antinomias
categories:
  jerarquia_normativa: 75         # 15 por nivel jerarquico (5 niveles)
  competencias: 75                # Estado, CCAA, compartidas, cruzadas, forales
  vigencia: 75                    # Derogaciones parciales, transitorias, vacatio legis
  primacia_ue: 50                 # Efecto directo, transposicion, primacia
  reserva_lo: 30                  # 12 materias reservadas
  irretroactividad: 25            # Sancionadora, favorable, civil
  lex_specialis: 25               # Norma especial vs general
  territory: 25                   # Normativa CCAA, derecho foral
  sycophancy: 30                  # Premisas falsas del usuario
  misgrounding: 30                # Citas correctas que no soportan claim
  antinomias: 20                  # Conflictos mismo nivel
  multi_turn: 20                  # Coherencia entre turnos
  disclaimer: 10                  # Presencia obligatoria
  intent_classification: 10       # Clasificacion correcta de intent
calibration:
  recalibration_frequency: 'trimestral'
  score_probability_table: true   # Publicar tabla score → P(error)
```

**Plan de generacion del JLB:**
- Fase 6 (Benchmark actual): 52 casos core manuales ← ya especificados
- Fase 6b: +48 casos manuales por expertos juridicos = 100 core
- Fase 6c: +250 sinteticos generados con LLM a partir de queries reales del BOE
- Fase 6d: +100 de feedback loop profesional (G08) + casos reales
- Fase 6e: +50 adversariales (sycophancy, misgrounding, antinomias)

### 16.5 G08 — Feedback Loop Profesional

**Problema:** Correcciones de abogados que usan JarabaLex no retroalimentan el sistema. Perdida de datos de entrenamiento de alto valor.

**Solucion:** Nuevo servicio `LegalFeedbackService` + integracion UX.

**Fichero:**
```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalFeedbackService.php
```

**Especificacion:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Captura y procesa correcciones de profesionales juridicos.
 *
 * Flujo:
 * 1. Abogado ve respuesta del Copilot Legal.
 * 2. Click "Corregir respuesta" → formulario estructurado.
 * 3. Correccion guardada en entidad legal_feedback.
 * 4. Queue de revision (admin) para validar.
 * 5. Correcciones validadas se incorporan a:
 *    a) Benchmark JLB como nuevos test cases.
 *    b) Knowledge Base si aplica (nuevos patrones).
 *    c) Metricas de calidad (correcciones/100 consultas).
 *
 * EU AI Act Art. 9(9): "Logging shall include... quality feedback."
 */
class LegalFeedbackService {

  /**
   * Tipos de correccion.
   */
  public const TYPE_NORM_INCORRECT = 'norm_incorrect';
  public const TYPE_HIERARCHY_ERROR = 'hierarchy_error';
  public const TYPE_COMPETENCE_ERROR = 'competence_error';
  public const TYPE_VIGENCIA_ERROR = 'vigencia_error';
  public const TYPE_CITATION_ERROR = 'citation_error';
  public const TYPE_INTERPRETATION_ERROR = 'interpretation_error';
  public const TYPE_OTHER = 'other';

  public function __construct(
    protected readonly ?EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra una correccion profesional.
   *
   * @param array $feedback
   *   Datos de la correccion:
   *   - response_id: ID de la respuesta del copilot.
   *   - correction_type: Tipo de error (TYPE_*).
   *   - incorrect_text: Fragmento incorrecto.
   *   - correct_text: Texto correcto segun el profesional.
   *   - norm_reference: Norma que soporta la correccion.
   *   - explanation: Explicacion del error.
   *   - user_id: ID del profesional que corrige.
   *   - tenant_id: Tenant del profesional.
   *
   * @return string
   *   ID del feedback creado.
   */
  public function submitCorrection(array $feedback): string {
    // Crear entidad legal_feedback (ContentEntity).
    // Estado inicial: 'pending_review'.
    // Asignar a cola de revision de administradores.
    $feedbackId = 'fb_' . bin2hex(random_bytes(8));

    $this->logger->info('Legal feedback submitted: @type by user @user', [
      '@type' => $feedback['correction_type'] ?? 'unknown',
      '@user' => $feedback['user_id'] ?? 'anonymous',
    ]);

    return $feedbackId;
  }

  /**
   * Procesa una correccion validada.
   *
   * Llamado despues de que un admin valide la correccion.
   * Genera nuevo test case para JLB y actualiza metricas.
   */
  public function processValidatedCorrection(string $feedbackId, array $correction): void {
    // 1. Generar test case para JLB.
    $testCase = $this->generateBenchmarkCase($correction);

    // 2. Actualizar metricas de calidad.
    // Incrementar contador correcciones/100 consultas.

    // 3. Si es patron repetido, considerar anadir a KB.
    // (ej: misma competence_error 3+ veces = nuevo patron).

    $this->logger->info('Legal correction @id processed: new JLB case generated', [
      '@id' => $feedbackId,
    ]);
  }

  /**
   * Genera test case de benchmark a partir de correccion validada.
   */
  protected function generateBenchmarkCase(array $correction): array {
    return [
      'input' => $correction['original_query'] ?? '',
      'expected_output_contains' => [$correction['correct_text'] ?? ''],
      'expected_output_not_contains' => [$correction['incorrect_text'] ?? ''],
      'criteria' => sprintf(
        'Correccion profesional (%s): %s',
        $correction['correction_type'] ?? 'unknown',
        $correction['explanation'] ?? '',
      ),
      'category' => 'professional_feedback',
      'source' => 'feedback_loop',
      'feedback_id' => $correction['feedback_id'] ?? '',
    ];
  }

  /**
   * Obtiene metricas de correcciones.
   *
   * @return array{total: int, rate_per_100: float, by_type: array}
   */
  public function getMetrics(string $tenantId = '', string $period = '30d'): array {
    // Consultar entidades legal_feedback del periodo.
    return [
      'total' => 0,
      'rate_per_100' => 0.0,
      'by_type' => [],
      'period' => $period,
    ];
  }

}
```

**UX Integration:** Boton "Corregir respuesta" en la interfaz del Copilot Legal. Visible solo para usuarios con rol `legal_professional`. Formulario estructurado con los campos de `submitCorrection()`.

### 16.6 G10 — Calibracion de Confianza del Legal Coherence Score

**Problema:** El score 0-100 no tiene base empirica. Un score de 85 no significa nada concreto sin calibracion.

**Solucion:** Metodologia de calibracion integrada en el benchmark:

```php
/**
 * Calibracion del Legal Coherence Score.
 *
 * Metodologia:
 * 1. Ejecutar JLB completo (500+ casos).
 * 2. Para cada rango de score (0-10, 10-20, ..., 90-100):
 *    - Contar respuestas con violaciones reales confirmadas.
 *    - Calcular P(error) = violaciones / total en ese rango.
 * 3. Publicar tabla de correspondencia score → P(error).
 * 4. Recalibrar trimestralmente.
 *
 * Ejemplo tabla esperada (hipotetica, pre-calibracion):
 * Score 90-100: P(error) < 2% → "Alta confianza"
 * Score 70-89:  P(error) 5-15% → "Confianza moderada, verificar"
 * Score 50-69:  P(error) 20-40% → "Baja confianza, verificacion recomendada"
 * Score 0-49:   P(error) > 50% → "Respuesta bloqueada"
 */
public const CONFIDENCE_LABELS = [
  'alta' => ['min_score' => 90, 'label' => 'Alta confianza', 'icon' => 'shield-check'],
  'moderada' => ['min_score' => 70, 'label' => 'Confianza moderada', 'icon' => 'info-circle'],
  'baja' => ['min_score' => 50, 'label' => 'Verificacion recomendada', 'icon' => 'exclamation-triangle'],
  'bloqueada' => ['min_score' => 0, 'label' => 'Respuesta retenida', 'icon' => 'ban'],
];
```

Anadir a `LegalDisclaimerEnforcementService::appendCoherenceScore()`:

```php
// Enriquecer con etiqueta de confianza calibrada.
$label = $this->getConfidenceLabel($score100);
$output .= sprintf(
  "\n\n*Indice de confianza juridica: %d/100 (%s). %s*",
  $score100,
  $label['label'],
  $score100 < 70 ? 'Se recomienda verificar con fuentes oficiales.' : '',
);
```

### 16.7 G12 — Coherencia Multi-Turn (LegalContext Persistente)

**Problema:** Cada query se valida individualmente. En conversaciones de multiples turnos, el sistema puede contradecirse si cambia contexto territorial o temporal.

**Solucion:** `LegalConversationContext` persistente por sesion de copilot.

**Fichero:**
```
web/modules/custom/jaraba_legal_intelligence/src/LegalCoherence/LegalConversationContext.php
```

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

/**
 * Contexto legal persistente por sesion de conversacion.
 *
 * Acumula informacion normativa a lo largo de la conversacion:
 * - Normas citadas en turnos anteriores.
 * - Principios afirmados (para detectar contradicciones).
 * - Territorio y CCAA establecidos.
 * - Competencias mencionadas.
 *
 * Usado por el LegalCoherenceValidatorService (Capa 6) para
 * cross-turn validation: verificar que el turno actual no
 * contradiga afirmaciones de turnos anteriores.
 *
 * Almacenado en session storage del copilot (copilot_sessions).
 */
class LegalConversationContext {

  /**
   * Normas citadas en la conversacion.
   *
   * @var array<string, array{norm_type: string, first_cited_turn: int}>
   */
  protected array $citedNorms = [];

  /**
   * Afirmaciones juridicas clave hechas en turnos anteriores.
   *
   * @var array<int, array{turn: int, claim: string, norm_basis: string}>
   */
  protected array $legalClaims = [];

  /**
   * Territorio establecido en la conversacion.
   */
  protected string $territory = '';

  /**
   * Turno actual.
   */
  protected int $currentTurn = 0;

  /**
   * Registra las normas y claims de un turno.
   */
  public function recordTurn(int $turn, array $normsDetected, array $claims = []): void {
    $this->currentTurn = $turn;

    foreach ($normsDetected as $norm) {
      $key = $norm['text'] ?? $norm;
      if (!isset($this->citedNorms[$key])) {
        $this->citedNorms[$key] = [
          'norm_type' => $norm['hierarchy_key'] ?? 'unknown',
          'first_cited_turn' => $turn,
        ];
      }
    }

    foreach ($claims as $claim) {
      $this->legalClaims[] = [
        'turn' => $turn,
        'claim' => $claim['text'] ?? $claim,
        'norm_basis' => $claim['norm'] ?? '',
      ];
    }
  }

  /**
   * Verifica coherencia con turnos anteriores.
   *
   * @param string $currentOutput
   *   Respuesta del turno actual.
   *
   * @return array{contradictions: array}
   *   Contradicciones detectadas con turnos anteriores.
   */
  public function checkCrossTurnCoherence(string $currentOutput): array {
    $contradictions = [];

    // Verificar que no se contradigan afirmaciones de turnos anteriores.
    $competencePatterns = [
      'competencia exclusiva del Estado' => 'state_exclusive',
      'competencia autonomica' => 'ccaa_competence',
      'competencia compartida' => 'shared_competence',
    ];

    foreach ($this->legalClaims as $prevClaim) {
      foreach ($competencePatterns as $pattern => $type) {
        $prevHas = str_contains(mb_strtolower($prevClaim['claim']), $pattern);
        $currentHas = str_contains(mb_strtolower($currentOutput), $pattern);

        if ($prevHas && !$currentHas) {
          // El turno anterior afirmo un tipo de competencia.
          // Verificar si el turno actual afirma lo contrario.
          $opposites = match ($type) {
            'state_exclusive' => 'competencia autonomica',
            'ccaa_competence' => 'competencia exclusiva del Estado',
            default => '',
          };
          if ($opposites && str_contains(mb_strtolower($currentOutput), $opposites)) {
            $contradictions[] = [
              'type' => 'cross_turn_contradiction',
              'severity' => 'high',
              'description' => sprintf(
                'Turno %d afirmo "%s" pero turno actual dice lo contrario.',
                $prevClaim['turn'],
                mb_substr($prevClaim['claim'], 0, 100),
              ),
              'previous_turn' => $prevClaim['turn'],
            ];
          }
        }
      }
    }

    return ['contradictions' => $contradictions];
  }

  /**
   * Serializa para almacenamiento en sesion.
   */
  public function serialize(): array {
    return [
      'cited_norms' => $this->citedNorms,
      'legal_claims' => $this->legalClaims,
      'territory' => $this->territory,
      'current_turn' => $this->currentTurn,
    ];
  }

  /**
   * Restaura desde datos serializados.
   */
  public static function fromSerialized(array $data): self {
    $ctx = new self();
    $ctx->citedNorms = $data['cited_norms'] ?? [];
    $ctx->legalClaims = $data['legal_claims'] ?? [];
    $ctx->territory = $data['territory'] ?? '';
    $ctx->currentTurn = $data['current_turn'] ?? 0;
    return $ctx;
  }

  public function setTerritory(string $territory): void {
    $this->territory = $territory;
  }

  public function getTerritory(): string {
    return $this->territory;
  }

}
```

**Integracion:** En `applyLegalCoherence()`, despues de la validacion de Capa 6, invocar `LegalConversationContext::checkCrossTurnCoherence()` si hay sesion activa. Los warnings de cross-turn se anaden al resultado.

### 16.8 G14 — Deteccion de Antinomias (Conflictos Mismo Nivel)

**Problema:** El sistema solo detecta conflictos jerarquicos (norma inferior vs superior). No detecta antinomias: dos normas vigentes del mismo rango que regulan la misma materia con contenido contradictorio.

**Solucion:** Enriquecer el `LegalCoherenceValidatorService` (Capa 6):

```php
/**
 * Detecta potenciales antinomias (conflictos entre normas del mismo rango).
 *
 * Heuristica: si la respuesta cita 2+ normas del mismo rango
 * sobre la misma materia con conclusiones aparentemente opuestas,
 * generar warning de posible antinomia.
 */
protected function checkAntinomies(string $output): array {
  $warnings = [];

  // Extraer normas citadas con su rango.
  $normsInOutput = [];
  foreach (LegalCoherenceKnowledgeBase::NORM_TYPE_PATTERNS as $pattern => $hierarchyKey) {
    if (preg_match_all($pattern, $output, $matches)) {
      foreach ($matches[0] as $match) {
        $normsInOutput[] = [
          'text' => $match,
          'rank' => LegalCoherenceKnowledgeBase::NORMATIVE_HIERARCHY[$hierarchyKey]['rank'] ?? 99,
          'hierarchy_key' => $hierarchyKey,
        ];
      }
    }
  }

  // Agrupar por rango.
  $byRank = [];
  foreach ($normsInOutput as $norm) {
    $byRank[$norm['rank']][] = $norm;
  }

  // Para cada grupo con 2+ normas del mismo rango,
  // verificar si hay contradiccion aparente.
  $contradictionKeywords = [
    'sin embargo', 'no obstante', 'en cambio', 'por el contrario',
    'a diferencia', 'contradice', 'incompatible',
  ];

  foreach ($byRank as $rank => $norms) {
    if (count($norms) < 2) {
      continue;
    }

    // Buscar si entre las normas del mismo rango hay keywords de contradiccion.
    $sentences = preg_split('/[.;]\s+/', $output);
    foreach ($sentences as $sentence) {
      $normCount = 0;
      $hasContradiction = FALSE;

      foreach ($norms as $norm) {
        if (mb_stripos($sentence, $norm['text']) !== FALSE) {
          $normCount++;
        }
      }

      if ($normCount >= 2) {
        foreach ($contradictionKeywords as $kw) {
          if (str_contains(mb_strtolower($sentence), $kw)) {
            $hasContradiction = TRUE;
            break;
          }
        }

        if ($hasContradiction) {
          $warnings[] = [
            'type' => 'potential_antinomy',
            'severity' => 'medium',
            'description' => sprintf(
              'Posible antinomia detectada: dos normas de rango %d en la misma frase con contenido contradictorio. Considerar principio lex posterior o lex specialis.',
              $rank,
            ),
            'match' => mb_substr($sentence, 0, 200),
          ];
        }
      }
    }
  }

  return ['warnings' => $warnings];
}
```

Invocar desde `validate()` despues de `checkInternalContradictions()`.

### 16.9 G09 — Derecho Foral (Enriquecimiento KB)

**Problema:** El Knowledge Base no modela los regimenes de Derecho Civil especial (Cataluna, Aragon, Navarra, Pais Vasco, Baleares, Galicia).

**Solucion:** Anadir a `LegalCoherenceKnowledgeBase`:

```php
/**
 * Regimenes de Derecho Civil especial / Foral.
 *
 * Art. 149.1.8 CE: el Estado tiene competencia exclusiva en
 * legislacion civil "sin perjuicio de la conservacion, modificacion
 * y desarrollo por las CCAA de los derechos civiles, forales o
 * especiales, alli donde existan".
 *
 * Estas CCAA tienen Derecho Civil propio que prevalece sobre el
 * CC estatal en las materias que regulan.
 */
public const FORAL_LAW_REGIMES = [
  'cataluna' => [
    'corpus' => 'Codi Civil de Catalunya (Ley 29/2002 + reformas)',
    'materias' => ['sucesiones', 'regimen economico matrimonial', 'derechos reales', 'obligaciones', 'familia'],
    'ccaa' => 'Cataluna',
  ],
  'aragon' => [
    'corpus' => 'Codigo del Derecho Foral de Aragon (DL 1/2011)',
    'materias' => ['sucesiones', 'regimen economico matrimonial', 'derecho de la persona'],
    'ccaa' => 'Aragon',
  ],
  'navarra' => [
    'corpus' => 'Fuero Nuevo de Navarra (Ley 1/1973, compilacion 2019)',
    'materias' => ['sucesiones', 'regimen economico matrimonial', 'obligaciones', 'derechos reales'],
    'ccaa' => 'Navarra',
  ],
  'pais_vasco' => [
    'corpus' => 'Ley 5/2015 de Derecho Civil Vasco',
    'materias' => ['sucesiones', 'vecindad civil', 'regimen economico matrimonial'],
    'ccaa' => 'Pais Vasco',
  ],
  'baleares' => [
    'corpus' => 'Compilacion de Derecho Civil de Baleares (DL 79/1990)',
    'materias' => ['sucesiones', 'regimen economico matrimonial'],
    'ccaa' => 'Islas Baleares',
  ],
  'galicia' => [
    'corpus' => 'Ley 2/2006 de Derecho Civil de Galicia',
    'materias' => ['comunidad de bienes', 'derecho de familia', 'sucesiones', 'servidumbres'],
    'ccaa' => 'Galicia',
  ],
];

/**
 * Verifica si una materia tiene Derecho Foral aplicable en una CCAA.
 *
 * @param string $matter
 *   Materia juridica (ej: 'sucesiones', 'regimen economico matrimonial').
 * @param string $territory
 *   CCAA del usuario.
 *
 * @return array|null
 *   Datos del regimen foral si aplica, NULL si rige CC estatal.
 */
public static function getForalRegime(string $matter, string $territory): ?array {
  $matterLower = mb_strtolower($matter);
  $territoryLower = mb_strtolower($territory);

  foreach (self::FORAL_LAW_REGIMES as $id => $regime) {
    if (mb_strtolower($regime['ccaa']) === $territoryLower
      || str_contains($territoryLower, $id)) {
      foreach ($regime['materias'] as $m) {
        if (str_contains($matterLower, $m) || str_contains($m, $matterLower)) {
          return ['id' => $id, ...$regime];
        }
      }
    }
  }

  return NULL;
}
```

**Integracion con Prompt Rule (Capa 4):** En `applyWithTerritory()`, si el territorio tiene Derecho Foral, anotar en el prompt:

```
NOTA TERRITORIAL: El usuario esta en [TERRITORY], que tiene Derecho Civil propio
([CORPUS_FORAL]). Para materias de [MATERIAS_FORALES], la normativa foral prevalece
sobre el Codigo Civil estatal.
```

---

## 17. Dependencias Externas (Gaps fuera de scope LCIS)

Los siguientes gaps identificados en la auditoria requieren trabajo en modulos/entidades separadas del sistema de coherencia. El LCIS debe ser **extensible** para integrarlos cuando esten disponibles.

| Gap | Modulo/Entidad | Dependencia LCIS | Prioridad |
|-----|----------------|-----------------|-----------|
| G02 | `legal_norm` → `legal_work` + `legal_expression` | NormativeGraphEnricher usara `legal_expression` para point-in-time retrieval | Pre-F2 |
| G03 | `legal_component` + `legal_component_version` | Citation-Claim Alignment (V8b) se beneficia de texto a nivel articulo | Pre-F2 |
| G06 | `legal_ruling` (nueva entidad) | Validator extender para jerarquia de jurisprudencia (TS > TSJ > AP) | F3+ |
| G13 | Campo `norm_nature` en datos | Validator distinguir dispositiva/imperativa para validacion contractual | F5+ |
| G15 | `legal_action` para regimenes transitorios | Enricher considerar fechas de transicion | F5+ |
| G11 | Integracion ServiciosConecta | Boton "Solicitar revision profesional" con booking integrado | F5+ |

### LEGAL-COHERENCE-EXTENSIBILITY-001
El pipeline LCIS (9 capas) DEBE funcionar correctamente con o sin estas dependencias externas. Cuando una dependencia no esta disponible, la capa afectada degrada gracefully (ej: NormativeGraphEnricher funciona con `legal_norm` plana; V8b anota "unverifiable" si no tiene texto de articulo).

---

## 18. Reglas Arquitectonicas Nuevas (v3.0.0)

### LEGAL-COHERENCE-SYCOPHANCY-001
La Capa 7 (Verifier) DEBE incluir Premise Validation (V8): antes de evaluar la respuesta, analizar si la pregunta del usuario contiene premisas falsas. Si detecta premisa falsa no corregida por la respuesta, generar warning `sycophancy_risk`.

### LEGAL-COHERENCE-MISGROUNDING-001
La Capa 7 (Verifier) DEBE verificar Citation-Claim Alignment (V8b): para cada par (afirmacion, cita), verificar que el contenido del articulo citado realmente soporta la afirmacion. Status: supported, misgrounded, unverifiable.

### LEGAL-COHERENCE-MULTITURN-001
En sesiones de copilot con multiples turnos, el pipeline LCIS DEBE mantener `LegalConversationContext` persistente y ejecutar cross-turn coherence check. Contradicciones entre turnos generan warning `cross_turn_contradiction`.

### LEGAL-COHERENCE-FEEDBACK-001
Correcciones de profesionales juridicos validadas DEBEN retroalimentar: (a) JLB benchmark como nuevos test cases, (b) Knowledge Base como nuevos patrones si es recurrente, (c) metricas de calidad (correcciones/100 consultas).

### LEGAL-COHERENCE-FORAL-001
Para territorios con Derecho Civil especial/foral (Cataluna, Aragon, Navarra, Pais Vasco, Baleares, Galicia), el Prompt Rule (Capa 4) DEBE anotar en el system prompt que la normativa foral prevalece sobre el CC estatal en las materias que regula.
