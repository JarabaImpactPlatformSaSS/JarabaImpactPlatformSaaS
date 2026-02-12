<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Psr\Log\LoggerInterface;

/**
 * Scoring engine that matches funding opportunities to tenant profiles.
 *
 * Evaluates funding calls against a tenant's profile across multiple
 * dimensions (region, beneficiary type, sector, company size) and produces
 * a weighted overall score with a per-criterion breakdown.
 *
 * SCORING WEIGHTS:
 * - Region: 30% — geographic eligibility is a hard filter.
 * - Beneficiary type: 30% — legal form / entity type match.
 * - Sector: 25% — industry alignment.
 * - Company size: 15% — employee-count range fit.
 *
 * ARQUITECTURA:
 * - Cada dimension se calcula de forma independiente (0-100).
 * - El score global es la media ponderada de las dimensiones.
 * - Scores altos (>=70) indican alta probabilidad de elegibilidad.
 *
 * RELACIONES:
 * - FundingMatchingEngine -> LoggerInterface (logging)
 * - FundingMatchingEngine <- Controllers / CronJobs (consumido por)
 */
class FundingMatchingEngine {

  /**
   * Scoring weights for each criterion (must sum to 1.0).
   */
  protected const WEIGHTS = [
    'region' => 0.30,
    'beneficiary' => 0.30,
    'sector' => 0.25,
    'size' => 0.15,
  ];

  /**
   * Beneficiary type inclusion map.
   *
   * Maps broad beneficiary categories to the specific types they include.
   */
  protected const BENEFICIARY_INCLUSIONS = [
    'empresas' => ['pymes', 'grandes_empresas', 'microempresas', 'autonomos'],
    'entidades' => ['asociaciones', 'fundaciones', 'ong'],
    'pymes' => ['microempresas'],
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calculates region score between a call's region and a profile's region.
   *
   * - Exact match: 100.0
   * - National call: 90.0 (matches any region)
   * - European call: 85.0 (matches any region)
   * - No match: 0.0
   *
   * @param string $callRegion
   *   The funding call's target region (lowercase).
   * @param string $profileRegion
   *   The tenant's region (lowercase).
   *
   * @return float
   *   Score between 0.0 and 100.0.
   */
  public function calculateRegionScore(string $callRegion, string $profileRegion): float {
    $callRegion = mb_strtolower(trim($callRegion));
    $profileRegion = mb_strtolower(trim($profileRegion));

    // Exact match.
    if ($callRegion === $profileRegion) {
      return 100.0;
    }

    // National scope applies to all regions.
    if ($callRegion === 'nacional') {
      return 90.0;
    }

    // European scope applies to all Spanish regions.
    if ($callRegion === 'europeo') {
      return 85.0;
    }

    // No match.
    return 0.0;
  }

  /**
   * Calculates beneficiary type score.
   *
   * Checks whether the profile's type appears directly in the call's
   * beneficiary list or is implicitly included via the inclusion map.
   *
   * @param array $callBeneficiaries
   *   List of beneficiary types accepted by the call (lowercase).
   * @param string $profileType
   *   The tenant's beneficiary type (lowercase).
   *
   * @return float
   *   Score between 0.0 and 100.0.
   */
  public function calculateBeneficiaryScore(array $callBeneficiaries, string $profileType): float {
    $profileType = mb_strtolower(trim($profileType));
    $callBeneficiaries = array_map(fn(string $b): string => mb_strtolower(trim($b)), $callBeneficiaries);

    // Direct match.
    if (in_array($profileType, $callBeneficiaries, TRUE)) {
      return 100.0;
    }

    // Check inclusion: if a call accepts a broad category that includes profileType.
    foreach ($callBeneficiaries as $callType) {
      if (isset(self::BENEFICIARY_INCLUSIONS[$callType])) {
        if (in_array($profileType, self::BENEFICIARY_INCLUSIONS[$callType], TRUE)) {
          return 75.0;
        }
      }
    }

    // Check reverse inclusion: if profileType is a broad category that includes a call type.
    if (isset(self::BENEFICIARY_INCLUSIONS[$profileType])) {
      foreach ($callBeneficiaries as $callType) {
        if (in_array($callType, self::BENEFICIARY_INCLUSIONS[$profileType], TRUE)) {
          return 50.0;
        }
      }
    }

    // No match.
    return 0.0;
  }

  /**
   * Calculates sector alignment score.
   *
   * Empty call sectors means the call is open to all sectors (high score).
   * Otherwise, the score is proportional to sector overlap.
   *
   * @param array $callSectors
   *   Sectors the call targets (lowercase). Empty = unrestricted.
   * @param array $profileSectors
   *   The tenant's sectors (lowercase).
   *
   * @return float
   *   Score between 0.0 and 100.0.
   */
  public function calculateSectorScore(array $callSectors, array $profileSectors): float {
    $callSectors = array_map(fn(string $s): string => mb_strtolower(trim($s)), $callSectors);
    $profileSectors = array_map(fn(string $s): string => mb_strtolower(trim($s)), $profileSectors);

    // Unrestricted call matches all sectors.
    if (empty($callSectors)) {
      return 90.0;
    }

    if (empty($profileSectors)) {
      return 0.0;
    }

    // Calculate overlap ratio.
    $overlap = array_intersect($callSectors, $profileSectors);
    $overlapCount = count($overlap);

    if ($overlapCount === 0) {
      return 0.0;
    }

    // Score based on what percentage of profile sectors match.
    $ratio = $overlapCount / count($profileSectors);
    return round(min($ratio * 100.0, 100.0), 2);
  }

  /**
   * Calculates company size score based on employee count range.
   *
   * Returns 100.0 if the profile's employee count falls within the call's
   * min/max range, or a reduced score based on distance from the range.
   *
   * @param int $callMinEmployees
   *   Minimum employees accepted by the call.
   * @param int $callMaxEmployees
   *   Maximum employees accepted by the call.
   * @param int $profileEmployees
   *   The tenant's number of employees.
   *
   * @return float
   *   Score between 0.0 and 100.0.
   */
  public function calculateSizeScore(int $callMinEmployees, int $callMaxEmployees, int $profileEmployees): float {
    // Within range: perfect score.
    if ($profileEmployees >= $callMinEmployees && $profileEmployees <= $callMaxEmployees) {
      return 100.0;
    }

    // Calculate distance from the nearest boundary.
    $rangeSize = max($callMaxEmployees - $callMinEmployees, 1);

    if ($profileEmployees < $callMinEmployees) {
      $distance = $callMinEmployees - $profileEmployees;
    }
    else {
      $distance = $profileEmployees - $callMaxEmployees;
    }

    // Penalize proportionally to how far outside the range.
    $penalty = ($distance / $rangeSize) * 100.0;
    return max(0.0, round(100.0 - $penalty, 2));
  }

  /**
   * Calculates the weighted overall matching score.
   *
   * Combines all individual criterion scores using the predefined weights
   * and returns both the overall score and a per-criterion breakdown.
   *
   * @param array $call
   *   Normalized funding call data with keys:
   *   - region: (string) Target region.
   *   - beneficiary_types: (array) Accepted beneficiary types.
   *   - sectors: (array) Target sectors (empty = unrestricted).
   *   - min_employees: (int) Minimum employees.
   *   - max_employees: (int) Maximum employees.
   * @param array $profile
   *   Tenant profile data with keys:
   *   - region: (string) Tenant's region.
   *   - beneficiary_type: (string) Tenant's beneficiary type.
   *   - sectors: (array) Tenant's sectors.
   *   - employees: (int) Number of employees.
   *
   * @return array
   *   Associative array with:
   *   - score: (float) Weighted overall score (0-100).
   *   - breakdown: (array) Per-criterion scores.
   */
  public function calculateOverallScore(array $call, array $profile): array {
    $regionScore = $this->calculateRegionScore(
      (string) ($call['region'] ?? ''),
      (string) ($profile['region'] ?? ''),
    );

    $beneficiaryScore = $this->calculateBeneficiaryScore(
      (array) ($call['beneficiary_types'] ?? []),
      (string) ($profile['beneficiary_type'] ?? ''),
    );

    $sectorScore = $this->calculateSectorScore(
      (array) ($call['sectors'] ?? []),
      (array) ($profile['sectors'] ?? []),
    );

    $sizeScore = $this->calculateSizeScore(
      (int) ($call['min_employees'] ?? 0),
      (int) ($call['max_employees'] ?? 99999),
      (int) ($profile['employees'] ?? 0),
    );

    $breakdown = [
      'region' => $regionScore,
      'beneficiary' => $beneficiaryScore,
      'sector' => $sectorScore,
      'size' => $sizeScore,
    ];

    $overallScore = 0.0;
    foreach (self::WEIGHTS as $criterion => $weight) {
      $overallScore += $breakdown[$criterion] * $weight;
    }

    return [
      'score' => round($overallScore, 2),
      'breakdown' => $breakdown,
    ];
  }

}
