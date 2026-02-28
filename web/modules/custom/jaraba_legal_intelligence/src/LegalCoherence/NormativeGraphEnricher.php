<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enriquecedor de resultados RAG con consciencia jerarquica.
 *
 * Capa 3 del LCIS (Legal Coherence Intelligence System).
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
 *
 * @see LegalCoherenceKnowledgeBase::HIERARCHY_WEIGHTS
 * @see LegalCoherenceKnowledgeBase::detectNormRank()
 */
final class NormativeGraphEnricher {

  /**
   * Pesos para Authority-Aware Ranking.
   *
   * Fuente: doc 179 LCIS, seccion 3.3.
   * final_score = semantic * 0.55 + authority * 0.30 + recency * 0.15
   */
  private const WEIGHT_SEMANTIC = 0.55;
  private const WEIGHT_AUTHORITY = 0.30;
  private const WEIGHT_RECENCY = 0.15;

  /**
   * Bonus de peso para ley autonomica en competencia exclusiva CCAA.
   */
  private const CCAA_EXCLUSIVE_BONUS = 0.12;

  /**
   * Materias de competencia exclusiva autonomica (Art. 148 CE).
   *
   * Complementa STATE_EXCLUSIVE_COMPETENCES del KB (Art. 149).
   * Si la query toca una de estas materias Y la norma es autonomica,
   * recibe bonus de peso jerarquico.
   */
  private const CCAA_EXCLUSIVE_COMPETENCES = [
    'urbanismo', 'vivienda', 'comercio_interior', 'artesania',
    'turismo', 'deporte', 'asistencia_social', 'sanidad',
    'cultura', 'patrimonio_historico', 'medio_ambiente',
    'agricultura', 'ganaderia', 'pesca_interior',
    'montes', 'aprovechamientos_forestales',
    'caza', 'pesca_fluvial', 'ferias_interiores',
  ];

  /**
   * Estados de vigencia que implican norma derogada totalmente.
   */
  private const DEROGATED_STATUSES = [
    'derogada_total', 'derogada', 'anulada', 'nula',
  ];

  /**
   * Estados de vigencia con derogacion parcial (warning, no exclusion).
   */
  private const PARTIAL_DEROGATION_STATUSES = [
    'derogada_parcial', 'parcialmente_derogada', 'modificada',
  ];

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
   *   Contexto de la consulta:
   *   - territory: CCAA del tenant (ej: 'Andalucia').
   *   - subject_areas: Areas tematicas detectadas (ej: ['urbanismo']).
   *   - query_date: Fecha de la consulta (Y-m-d).
   *
   * @return array
   *   Resultados reordenados con metadata enriquecida:
   *   - final_score: Puntuacion combinada (semantic + authority + recency).
   *   - authority_weight: Peso jerarquico de la norma.
   *   - norm_type_detected: Tipo de norma detectado.
   *   - recency_bonus: Bonus por recencia temporal.
   *   - vigencia_status: Estado de vigencia.
   *   - derogation_warning: (opcional) Advertencia de derogacion parcial.
   *   - territory_warning: (opcional) Advertencia territorial.
   */
  public function enrichRetrieval(array $ragResults, array $context = []): array {
    $territory = $context['territory'] ?? '';
    $subjectAreas = $context['subject_areas'] ?? [];
    $queryDate = $context['query_date'] ?? date('Y-m-d');

    $enriched = [];
    $derogatedCount = 0;

    foreach ($ragResults as $result) {
      $payload = $result['payload'] ?? [];

      // 1. Filtro de vigencia: eliminar normas derogadas totalmente.
      $status = $payload['status_legal'] ?? $payload['status'] ?? 'vigente';
      if (in_array($status, self::DEROGATED_STATUSES, TRUE)) {
        $derogatedCount++;
        continue;
      }

      // 2. Detectar rango jerarquico.
      $normType = $payload['norm_type'] ?? NULL;
      $normTitle = $payload['norm_title'] ?? $payload['title'] ?? '';
      if (!$normType) {
        $normType = LegalCoherenceKnowledgeBase::detectNormRank($normTitle);
      }

      // 3. Calcular authority weight con resolucion dinamica de competencia.
      $authorityWeight = $this->calculateAuthorityWeight(
        $normType,
        $territory,
        $subjectAreas,
      );

      // 4. Calcular recency bonus.
      $publicationDate = $payload['publication_date'] ?? $payload['date'] ?? '';
      $recencyBonus = $this->calculateRecencyBonus($publicationDate, $queryDate);

      // 5. Authority-Aware final score.
      $semanticScore = (float) ($result['score'] ?? 0.0);
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
      if (in_array($status, self::PARTIAL_DEROGATION_STATUSES, TRUE)) {
        $result['derogation_warning'] = 'Esta norma tiene articulos derogados o modificados. Verificar vigencia del articulo concreto.';
      }

      // 7. Anotar si es norma de otra CCAA (warning territorial).
      $normTerritory = $payload['autonomous_community'] ?? $payload['territory'] ?? '';
      if ($normTerritory && $territory
        && mb_strtolower($normTerritory) !== mb_strtolower($territory)) {
        $result['territory_warning'] = sprintf(
          'Norma de %s aplicada a consulta de %s. Verificar aplicabilidad territorial.',
          $normTerritory,
          $territory,
        );
      }

      $enriched[] = $result;
    }

    // Reordenar por final_score descendente.
    usort($enriched, static fn(array $a, array $b): int =>
      ($b['final_score'] ?? 0) <=> ($a['final_score'] ?? 0)
    );

    // Log si se filtraron normas derogadas.
    if ($derogatedCount > 0) {
      $this->logger->info('NormativeGraphEnricher: filtradas @count normas derogadas de @total resultados RAG.', [
        '@count' => $derogatedCount,
        '@total' => count($ragResults),
      ]);
    }

    return $enriched;
  }

  /**
   * Calcula el peso jerarquico con resolucion dinamica de competencia.
   *
   * @param string|null $normType
   *   Tipo de norma detectado (ej: 'ley_organica', 'ley_autonomica').
   * @param string $territory
   *   CCAA del tenant.
   * @param array $subjectAreas
   *   Areas tematicas de la consulta.
   *
   * @return float
   *   Peso jerarquico normalizado [0.0, 1.0].
   */
  protected function calculateAuthorityWeight(
    ?string $normType,
    string $territory,
    array $subjectAreas,
  ): float {
    if (!$normType) {
      return 0.50;
    }

    // Usar pesos del KB centralizado.
    $weight = LegalCoherenceKnowledgeBase::getHierarchyWeight($normType);

    // Resolucion dinamica: ley autonomica gana bonus en competencia
    // exclusiva CCAA, haciendo que pese mas que reglamento estatal.
    if ($normType === 'ley_autonomica' && !empty($subjectAreas)) {
      if ($this->isExclusiveCcaaCompetence($subjectAreas)) {
        $weight += self::CCAA_EXCLUSIVE_BONUS;
      }
    }

    return min(1.0, $weight);
  }

  /**
   * Determina si las areas tematicas son competencia exclusiva CCAA.
   *
   * @param array $subjectAreas
   *   Areas tematicas de la consulta.
   *
   * @return bool
   *   TRUE si al menos una area es competencia exclusiva CCAA.
   */
  protected function isExclusiveCcaaCompetence(array $subjectAreas): bool {
    foreach ($subjectAreas as $area) {
      if (in_array(mb_strtolower($area), self::CCAA_EXCLUSIVE_COMPETENCES, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Calcula bonus de recencia (norma mas reciente = mas relevante).
   *
   * Escala logaritmica: normas de ultimo ano reciben 1.0,
   * decrementando progresivamente hasta 0.15 para normas > 20 anos.
   *
   * @param string $date
   *   Fecha de publicacion de la norma (Y-m-d).
   * @param string $queryDate
   *   Fecha de la consulta (Y-m-d).
   *
   * @return float
   *   Bonus de recencia [0.15, 1.0].
   */
  protected function calculateRecencyBonus(string $date, string $queryDate): float {
    if (empty($date)) {
      return 0.3;
    }

    try {
      $pub = new \DateTime($date);
      $query = new \DateTime($queryDate);
      $diff = $query->diff($pub);
      $years = $diff->y + ($diff->m / 12);

      return match (TRUE) {
        $years <= 1 => 1.0,
        $years <= 3 => 0.85,
        $years <= 5 => 0.70,
        $years <= 10 => 0.50,
        $years <= 20 => 0.30,
        default => 0.15,
      };
    }
    catch (\Exception) {
      return 0.3;
    }
  }

  /**
   * Genera anotaciones textuales para el prompt sobre los resultados.
   *
   * Incluye warnings de derogacion parcial, territorio y jerarquia
   * para que el LLM los tenga en cuenta al generar la respuesta.
   *
   * @param array $enrichedResults
   *   Resultados de enrichRetrieval().
   *
   * @return string
   *   Anotaciones textuales para inyectar en el prompt.
   */
  public function generatePromptAnnotations(array $enrichedResults): string {
    $annotations = [];

    $hasDerogationWarning = FALSE;
    $hasTerritoryWarning = FALSE;

    foreach ($enrichedResults as $result) {
      if (!empty($result['derogation_warning'])) {
        $hasDerogationWarning = TRUE;
      }
      if (!empty($result['territory_warning'])) {
        $hasTerritoryWarning = TRUE;
      }
    }

    if ($hasDerogationWarning) {
      $annotations[] = 'ATENCION: Algunas de las normas en el contexto tienen articulos derogados o modificados. Verifica la vigencia del articulo concreto antes de citarlo.';
    }

    if ($hasTerritoryWarning) {
      $annotations[] = 'ATENCION: El contexto incluye normas de otras Comunidades Autonomas. Prioriza las del territorio del usuario y senala explicitamente si citas normativa de otra CCAA.';
    }

    if (!empty($enrichedResults)) {
      $annotations[] = 'Las fuentes normativas estan ordenadas por autoridad jerarquica (peso normativo) y vigencia temporal.';
    }

    return implode("\n", $annotations);
  }

}
