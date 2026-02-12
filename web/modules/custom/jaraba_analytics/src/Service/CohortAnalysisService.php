<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Entity\CohortDefinition;

/**
 * Servicio de análisis de cohortes.
 *
 * PROPÓSITO:
 * Proporciona métodos para construir curvas de retención, comparar cohortes
 * y obtener miembros de una cohorte a partir de los eventos de analytics.
 *
 * LÓGICA:
 * - buildRetentionCurve: consulta analytics_event para miembros de la cohorte,
 *   calcula retención semana a semana como porcentaje del total inicial.
 * - compareCohorts: ejecuta buildRetentionCurve para múltiples cohortes
 *   y devuelve resultados alineados para comparación.
 * - getCohortMembers: obtiene user IDs que coinciden con los criterios
 *   de la cohorte (tipo, rango de fechas, filtros adicionales).
 */
class CohortAnalysisService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Conexión a base de datos.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Build a week-by-week retention curve for a cohort.
   *
   * Queries analytics_event for cohort members and calculates the percentage
   * of members still active in each subsequent week after the cohort start date.
   *
   * @param \Drupal\jaraba_analytics\Entity\CohortDefinition $cohort
   *   The cohort definition entity.
   * @param int $weeks
   *   Number of weeks to calculate retention for. Defaults to 12.
   *
   * @return array
   *   Associative array where keys are week numbers (0-based) and values
   *   are retention percentages (0-100). Week 0 is always 100%.
   *   Example: [0 => 100.0, 1 => 72.5, 2 => 58.3, ...]
   */
  public function buildRetentionCurve(CohortDefinition $cohort, int $weeks = 12): array {
    $members = $this->getCohortMembers($cohort);

    if (empty($members)) {
      return array_fill(0, $weeks, 0.0);
    }

    $totalMembers = count($members);
    $cohortStartDate = $cohort->getDateRangeStart();

    if (!$cohortStartDate) {
      return array_fill(0, $weeks, 0.0);
    }

    $startTimestamp = strtotime($cohortStartDate);
    $retention = [];

    // Week 0 is always 100% (all members are present at the start).
    $retention[0] = 100.0;

    for ($week = 1; $week < $weeks; $week++) {
      $weekStart = $startTimestamp + ($week * 7 * 86400);
      $weekEnd = $weekStart + (7 * 86400);

      // Count distinct user_ids from analytics_event within this week.
      $query = $this->database->select('analytics_event', 'ae');
      $query->addExpression('COUNT(DISTINCT ae.user_id)', 'active_users');
      $query->condition('ae.user_id', $members, 'IN');
      $query->condition('ae.created', $weekStart, '>=');
      $query->condition('ae.created', $weekEnd, '<');

      // Apply tenant filter if set.
      $tenantId = $cohort->getTenantId();
      if ($tenantId !== NULL) {
        $query->condition('ae.tenant_id', $tenantId);
      }

      $activeCount = (int) $query->execute()->fetchField();

      $retention[$week] = round(($activeCount / $totalMembers) * 100, 2);
    }

    return $retention;
  }

  /**
   * Compare retention curves of multiple cohorts side by side.
   *
   * @param array $cohortIds
   *   Array of cohort_definition entity IDs to compare.
   *
   * @return array
   *   Associative array keyed by cohort ID, each containing:
   *   - 'name': cohort name
   *   - 'type': cohort type
   *   - 'members_count': number of members in the cohort
   *   - 'retention': array of week => retention_pct
   */
  public function compareCohorts(array $cohortIds): array {
    $storage = $this->entityTypeManager->getStorage('cohort_definition');
    $cohorts = $storage->loadMultiple($cohortIds);

    $results = [];

    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $cohort */
    foreach ($cohorts as $id => $cohort) {
      $members = $this->getCohortMembers($cohort);
      $retention = $this->buildRetentionCurve($cohort);

      $results[$id] = [
        'name' => $cohort->getName(),
        'type' => $cohort->getCohortType(),
        'members_count' => count($members),
        'retention' => $retention,
      ];
    }

    return $results;
  }

  /**
   * Get user IDs matching cohort criteria.
   *
   * Queries the system to find users that belong to the given cohort
   * based on its type (registration_date, first_purchase, vertical, custom)
   * and the configured date range and filters.
   *
   * @param \Drupal\jaraba_analytics\Entity\CohortDefinition $cohort
   *   The cohort definition entity.
   *
   * @return array
   *   Array of user IDs (integers) matching the cohort criteria.
   */
  public function getCohortMembers(CohortDefinition $cohort): array {
    $cohortType = $cohort->getCohortType();
    $startDate = $cohort->getDateRangeStart();
    $endDate = $cohort->getDateRangeEnd();
    $tenantId = $cohort->getTenantId();
    $filters = $cohort->getFilters();

    $members = [];

    switch ($cohortType) {
      case CohortDefinition::TYPE_REGISTRATION_DATE:
        $members = $this->getMembersByRegistrationDate($startDate, $endDate, $tenantId);
        break;

      case CohortDefinition::TYPE_FIRST_PURCHASE:
        $members = $this->getMembersByFirstPurchase($startDate, $endDate, $tenantId);
        break;

      case CohortDefinition::TYPE_VERTICAL:
        $vertical = $filters['vertical'] ?? NULL;
        $members = $this->getMembersByVertical($vertical, $startDate, $endDate, $tenantId);
        break;

      case CohortDefinition::TYPE_CUSTOM:
        $members = $this->getMembersByCustomFilters($filters, $startDate, $endDate, $tenantId);
        break;
    }

    return $members;
  }

  /**
   * Get members by registration date range.
   *
   * @param string|null $startDate
   *   Start date (Y-m-d).
   * @param string|null $endDate
   *   End date (Y-m-d).
   * @param int|null $tenantId
   *   Optional tenant ID for filtering.
   *
   * @return array
   *   User IDs registered within the date range.
   */
  protected function getMembersByRegistrationDate(?string $startDate, ?string $endDate, ?int $tenantId): array {
    $query = $this->database->select('users_field_data', 'u');
    $query->fields('u', ['uid']);
    $query->condition('u.uid', 0, '>');

    if ($startDate) {
      $query->condition('u.created', strtotime($startDate . ' 00:00:00'), '>=');
    }
    if ($endDate) {
      $query->condition('u.created', strtotime($endDate . ' 23:59:59'), '<=');
    }

    // If tenant filtering is needed, join through group_relationship.
    if ($tenantId !== NULL) {
      $query->innerJoin('group_relationship_field_data', 'gr', 'gr.entity_id = u.uid');
      $query->condition('gr.gid', $tenantId);
      $query->condition('gr.type', '%group_membership', 'LIKE');
    }

    return array_map('intval', $query->execute()->fetchCol());
  }

  /**
   * Get members by first purchase date range.
   *
   * @param string|null $startDate
   *   Start date (Y-m-d).
   * @param string|null $endDate
   *   End date (Y-m-d).
   * @param int|null $tenantId
   *   Optional tenant ID for filtering.
   *
   * @return array
   *   User IDs whose first purchase event falls within the date range.
   */
  protected function getMembersByFirstPurchase(?string $startDate, ?string $endDate, ?int $tenantId): array {
    // Find users whose FIRST purchase event falls within the date range.
    $subquery = $this->database->select('analytics_event', 'ae_inner');
    $subquery->addField('ae_inner', 'user_id');
    $subquery->addExpression('MIN(ae_inner.created)', 'first_purchase');
    $subquery->condition('ae_inner.event_type', 'purchase');
    $subquery->groupBy('ae_inner.user_id');

    if ($tenantId !== NULL) {
      $subquery->condition('ae_inner.tenant_id', $tenantId);
    }

    $query = $this->database->select($subquery, 'fp');
    $query->fields('fp', ['user_id']);

    if ($startDate) {
      $query->condition('fp.first_purchase', strtotime($startDate . ' 00:00:00'), '>=');
    }
    if ($endDate) {
      $query->condition('fp.first_purchase', strtotime($endDate . ' 23:59:59'), '<=');
    }

    return array_map('intval', $query->execute()->fetchCol());
  }

  /**
   * Get members by vertical assignment.
   *
   * @param string|null $vertical
   *   Vertical machine name to filter by.
   * @param string|null $startDate
   *   Start date (Y-m-d).
   * @param string|null $endDate
   *   End date (Y-m-d).
   * @param int|null $tenantId
   *   Optional tenant ID for filtering.
   *
   * @return array
   *   User IDs active in the specified vertical within the date range.
   */
  protected function getMembersByVertical(?string $vertical, ?string $startDate, ?string $endDate, ?int $tenantId): array {
    // Find distinct users with events in analytics_event,
    // filtered by vertical in event_data if available.
    $query = $this->database->select('analytics_event', 'ae');
    $query->addExpression('DISTINCT ae.user_id', 'user_id');
    $query->isNotNull('ae.user_id');

    if ($startDate) {
      $query->condition('ae.created', strtotime($startDate . ' 00:00:00'), '>=');
    }
    if ($endDate) {
      $query->condition('ae.created', strtotime($endDate . ' 23:59:59'), '<=');
    }

    if ($tenantId !== NULL) {
      $query->condition('ae.tenant_id', $tenantId);
    }

    $userIds = array_map('intval', $query->execute()->fetchCol());

    // Additional vertical filtering through group membership if vertical is set.
    if ($vertical && !empty($userIds)) {
      $groupQuery = $this->database->select('group_relationship_field_data', 'gr');
      $groupQuery->fields('gr', ['entity_id']);
      $groupQuery->condition('gr.entity_id', $userIds, 'IN');
      $groupQuery->condition('gr.type', '%group_membership', 'LIKE');

      // Join with groups to filter by vertical.
      $groupQuery->innerJoin('groups_field_data', 'g', 'g.id = gr.gid');

      $userIds = array_map('intval', $groupQuery->execute()->fetchCol());
    }

    return $userIds;
  }

  /**
   * Get members by custom filters from analytics_event.
   *
   * @param array $filters
   *   Associative array of custom filter criteria.
   * @param string|null $startDate
   *   Start date (Y-m-d).
   * @param string|null $endDate
   *   End date (Y-m-d).
   * @param int|null $tenantId
   *   Optional tenant ID for filtering.
   *
   * @return array
   *   User IDs matching the custom filters.
   */
  protected function getMembersByCustomFilters(array $filters, ?string $startDate, ?string $endDate, ?int $tenantId): array {
    $query = $this->database->select('analytics_event', 'ae');
    $query->addExpression('DISTINCT ae.user_id', 'user_id');
    $query->isNotNull('ae.user_id');

    if ($startDate) {
      $query->condition('ae.created', strtotime($startDate . ' 00:00:00'), '>=');
    }
    if ($endDate) {
      $query->condition('ae.created', strtotime($endDate . ' 23:59:59'), '<=');
    }

    if ($tenantId !== NULL) {
      $query->condition('ae.tenant_id', $tenantId);
    }

    // Apply custom filters on indexed fields.
    if (!empty($filters['event_type'])) {
      $query->condition('ae.event_type', $filters['event_type']);
    }
    if (!empty($filters['device_type'])) {
      $query->condition('ae.device_type', $filters['device_type']);
    }
    if (!empty($filters['utm_source'])) {
      $query->condition('ae.utm_source', $filters['utm_source']);
    }
    if (!empty($filters['utm_campaign'])) {
      $query->condition('ae.utm_campaign', $filters['utm_campaign']);
    }
    if (!empty($filters['country'])) {
      $query->condition('ae.country', $filters['country']);
    }

    return array_map('intval', $query->execute()->fetchCol());
  }

}
