<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio de metricas PIIL.
 *
 * Calcula metricas de insercion laboral, competencias digitales,
 * certificaciones y genera informes para PIIL/FUNDAE/FSE+.
 */
class PiilMetricsService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  /**
   * Obtiene tasas de insercion laboral por programa.
   */
  public function getEmploymentOutcomes(int $programId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId);
      $totalIds = $query->execute();
      $total = count($totalIds);

      if ($total === 0) {
        return ['total' => 0, 'employed' => 0, 'rate' => 0.0];
      }

      $employedQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId)
        ->condition('employment_sector', '', '<>');
      $employedIds = $employedQuery->execute();
      $employed = count($employedIds);

      return [
        'total' => $total,
        'employed' => $employed,
        'rate' => round($employed / $total, 4),
      ];
    }
    catch (\Throwable) {
      return ['total' => 0, 'employed' => 0, 'rate' => 0.0];
    }
  }

  /**
   * Distribucion por nivel DigComp.
   */
  public function getDigitalSkillsDistribution(int $programId): array {
    $distribution = ['A1' => 0, 'A2' => 0, 'B1' => 0, 'B2' => 0, 'C1' => 0, 'C2' => 0];

    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId)
        ->condition('digital_skills_level', '', '<>')
        ->execute();

      foreach ($storage->loadMultiple($ids) as $participant) {
        if (!($participant instanceof ContentEntityInterface)) {
          continue;
        }
        $level = $participant->get('digital_skills_level')->value;
        if ($level !== NULL && $level !== '' && isset($distribution[$level])) {
          $distribution[$level]++;
        }
      }
    }
    catch (\Throwable) {
      // Return empty distribution on error.
    }

    return $distribution;
  }

  /**
   * Tasas de certificacion por programa.
   */
  public function getCertificationRates(int $programId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('program_participant');
      $totalQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId);
      $total = $totalQuery->count()->execute();

      if ($total === 0) {
        return ['total' => 0, 'certified' => 0, 'rate' => 0.0, 'by_type' => []];
      }

      $certifiedQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId)
        ->condition('certification_date', NULL, 'IS NOT NULL');
      $certified = $certifiedQuery->count()->execute();

      $byType = ['attendance' => 0, 'competency' => 0, 'accredited' => 0];
      $certIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('program_id', $programId)
        ->condition('certification_type', '', '<>')
        ->execute();

      foreach ($storage->loadMultiple($certIds) as $participant) {
        if (!($participant instanceof ContentEntityInterface)) {
          continue;
        }
        $type = $participant->get('certification_type')->value;
        if ($type !== NULL && $type !== '' && isset($byType[$type])) {
          $byType[$type]++;
        }
      }

      return [
        'total' => $total,
        'certified' => $certified,
        'rate' => round($certified / $total, 4),
        'by_type' => $byType,
      ];
    }
    catch (\Throwable) {
      return ['total' => 0, 'certified' => 0, 'rate' => 0.0, 'by_type' => []];
    }
  }

  /**
   * Genera informe completo para PIIL.
   */
  public function generatePiilReport(int $programId): array {
    return [
      'program_id' => $programId,
      'generated_at' => date('Y-m-d\TH:i:s'),
      'employment_outcomes' => $this->getEmploymentOutcomes($programId),
      'digital_skills' => $this->getDigitalSkillsDistribution($programId),
      'certifications' => $this->getCertificationRates($programId),
    ];
  }

}
