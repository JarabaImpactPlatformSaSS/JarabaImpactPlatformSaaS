<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de priorizacion de hipotesis usando algoritmo ICE.
 *
 * ICE = Importance x Confidence x Evidence
 * Permite ordenar hipotesis por prioridad de validacion.
 */
class HypothesisPrioritizationService {

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Calcula el ICE score de una hipotesis.
   *
   * @param int $importance
   *   Importancia (1-5): criticidad para el modelo de negocio.
   * @param int $confidence
   *   Confianza (1-5): nivel de certeza actual (invertido: menor = mas urgente).
   * @param int $evidence
   *   Evidencia (1-5): datos actuales que soportan la hipotesis.
   *
   * @return float
   *   ICE score (0-125).
   */
  public function calculateIceScore(int $importance, int $confidence, int $evidence): float {
    $importance = max(1, min(5, $importance));
    $confidence = max(1, min(5, $confidence));
    $evidence = max(1, min(5, $evidence));

    // Invertimos confidence: baja confianza = mayor urgencia de validar
    $urgency = 6 - $confidence;

    return (float) ($importance * $urgency * $evidence);
  }

  /**
   * Prioriza un conjunto de hipotesis por ICE score.
   *
   * @param array $hypothesisIds
   *   IDs de hipotesis a priorizar.
   *
   * @return array
   *   Hipotesis ordenadas por ICE score descendente.
   */
  public function prioritize(array $hypothesisIds): array {
    $storage = $this->entityTypeManager->getStorage('hypothesis');
    $hypotheses = $storage->loadMultiple($hypothesisIds);

    $scored = [];
    foreach ($hypotheses as $hypothesis) {
      $importance = (int) ($hypothesis->get('importance_score')->value ?? 3);
      $evidence = (int) ($hypothesis->get('evidence_score')->value ?? 1);
      $status = $hypothesis->get('validation_status')->value ?? 'PENDING';

      // Confidence basado en el estado de validacion
      $confidence = match ($status) {
        'VALIDATED' => 5,
        'IN_PROGRESS' => 3,
        'INCONCLUSIVE' => 2,
        'INVALIDATED' => 4,
        default => 1, // PENDING = menor confianza
      };

      $iceScore = $this->calculateIceScore($importance, $confidence, $evidence);

      $scored[] = [
        'id' => (int) $hypothesis->id(),
        'statement' => $hypothesis->get('statement')->value,
        'hypothesis_type' => $hypothesis->get('hypothesis_type')->value,
        'bmc_block' => $hypothesis->get('bmc_block')->value,
        'importance_score' => $importance,
        'evidence_score' => $evidence,
        'confidence_score' => $confidence,
        'validation_status' => $status,
        'ice_score' => $iceScore,
      ];
    }

    // Ordenar por ICE score descendente
    usort($scored, fn($a, $b) => $b['ice_score'] <=> $a['ice_score']);

    return $scored;
  }

  /**
   * Prioriza hipotesis de un perfil de emprendedor.
   *
   * @param int $profileId
   *   ID del perfil de emprendedor.
   * @param string|null $bmcBlock
   *   Filtrar por bloque BMC opcional.
   *
   * @return array
   *   Hipotesis priorizadas.
   */
  public function prioritizeByProfile(int $profileId, ?string $bmcBlock = NULL): array {
    $storage = $this->entityTypeManager->getStorage('hypothesis');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('entrepreneur_profile', $profileId);

    if ($bmcBlock) {
      $query->condition('bmc_block', $bmcBlock);
    }

    // Solo hipotesis pendientes o en progreso
    $query->condition('validation_status', ['PENDING', 'IN_PROGRESS'], 'IN');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->prioritize(array_values($ids));
  }

}
