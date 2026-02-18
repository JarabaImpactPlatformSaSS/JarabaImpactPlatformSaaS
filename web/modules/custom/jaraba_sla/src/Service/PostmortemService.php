<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sla\Entity\SlaIncidentInterface;

/**
 * Service for incident postmortem management.
 *
 * Structure: Stateless service with DI for entity manager, tenant context,
 *   and current user.
 * Logic: Updates resolved incidents with postmortem data (root_cause, timeline,
 *   preventive_actions). Provides severity-based postmortem templates to ensure
 *   consistent documentation. Lists incidents that have completed postmortems.
 */
class PostmortemService {

  /**
   * Constructs a PostmortemService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Creates/updates a postmortem for an incident.
   *
   * Updates the incident entity with postmortem data and sets
   * status to 'postmortem'.
   *
   * @param int $incidentId
   *   The incident entity ID.
   * @param array $data
   *   Postmortem data with optional keys:
   *   - root_cause: string
   *   - timeline: array (will be JSON-encoded)
   *   - preventive_actions: string
   *
   * @return \Drupal\jaraba_sla\Entity\SlaIncidentInterface|null
   *   The updated incident, or NULL on failure.
   */
  public function create(int $incidentId, array $data): ?SlaIncidentInterface {
    try {
      /** @var \Drupal\jaraba_sla\Entity\SlaIncidentInterface|null $incident */
      $incident = $this->entityTypeManager->getStorage('sla_incident')
        ->load($incidentId);

      if (!$incident) {
        return NULL;
      }

      if (!empty($data['root_cause'])) {
        $incident->set('root_cause', $data['root_cause']);
      }

      if (!empty($data['timeline'])) {
        $timeline = is_array($data['timeline']) ? json_encode($data['timeline']) : $data['timeline'];
        $incident->set('timeline', $timeline);
      }

      if (!empty($data['preventive_actions'])) {
        $incident->set('preventive_actions', $data['preventive_actions']);
      }

      $incident->set('status', 'postmortem');
      $incident->save();

      return $incident;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Returns an auto-generated postmortem template based on severity.
   *
   * @param string $severity
   *   The severity level (sev1, sev2, sev3, sev4).
   *
   * @return array
   *   Template structure with suggested sections:
   *   - summary: Template text.
   *   - impact: Template text.
   *   - root_cause: Template text.
   *   - timeline: Array of suggested timeline entries.
   *   - preventive_actions: Template text.
   *   - severity_label: Human-readable severity.
   */
  public function getTemplate(string $severity): array {
    $templates = [
      'sev1' => [
        'summary' => 'Critical incident affecting all users. Complete service outage detected.',
        'impact' => 'All users affected. Service completely unavailable.',
        'root_cause' => '[REQUIRED] Provide detailed root cause analysis.',
        'timeline' => [
          ['time' => '', 'event' => 'Incident detected by monitoring system'],
          ['time' => '', 'event' => 'On-call engineer paged'],
          ['time' => '', 'event' => 'Root cause identified'],
          ['time' => '', 'event' => 'Fix deployed'],
          ['time' => '', 'event' => 'Service restored'],
          ['time' => '', 'event' => 'Monitoring confirmed recovery'],
        ],
        'preventive_actions' => '[REQUIRED] List at least 3 preventive actions with owners and deadlines.',
        'severity_label' => 'SEV1 - Critical',
      ],
      'sev2' => [
        'summary' => 'Major incident affecting significant portion of users.',
        'impact' => 'Significant number of users affected. Core functionality degraded.',
        'root_cause' => '[REQUIRED] Provide root cause analysis.',
        'timeline' => [
          ['time' => '', 'event' => 'Incident detected'],
          ['time' => '', 'event' => 'Investigation started'],
          ['time' => '', 'event' => 'Root cause identified'],
          ['time' => '', 'event' => 'Fix applied'],
          ['time' => '', 'event' => 'Service restored'],
        ],
        'preventive_actions' => '[REQUIRED] List at least 2 preventive actions.',
        'severity_label' => 'SEV2 - Major',
      ],
      'sev3' => [
        'summary' => 'Minor incident with limited user impact.',
        'impact' => 'Limited number of users affected. Non-critical functionality impacted.',
        'root_cause' => 'Provide root cause analysis.',
        'timeline' => [
          ['time' => '', 'event' => 'Incident detected'],
          ['time' => '', 'event' => 'Fix applied'],
          ['time' => '', 'event' => 'Service restored'],
        ],
        'preventive_actions' => 'List preventive actions if applicable.',
        'severity_label' => 'SEV3 - Minor',
      ],
      'sev4' => [
        'summary' => 'Low severity incident with minimal impact.',
        'impact' => 'Minimal user impact. Cosmetic or non-functional issue.',
        'root_cause' => 'Describe the issue.',
        'timeline' => [
          ['time' => '', 'event' => 'Issue identified'],
          ['time' => '', 'event' => 'Fix applied'],
        ],
        'preventive_actions' => 'Optional preventive actions.',
        'severity_label' => 'SEV4 - Low',
      ],
    ];

    return $templates[$severity] ?? $templates['sev4'];
  }

  /**
   * Lists incidents that have completed postmortems.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param int $page
   *   Page number (0-indexed).
   * @param int $limit
   *   Items per page.
   *
   * @return array
   *   Array with keys:
   *   - items: Array of postmortem incident data.
   *   - total: Total count of postmortem incidents.
   *   - page: Current page.
   *   - limit: Items per page.
   */
  public function list(int $tenantId, int $page = 0, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('sla_incident');

      // Count total.
      $countQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'postmortem')
        ->count();
      $total = (int) $countQuery->execute();

      // Load page.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'postmortem')
        ->sort('created', 'DESC')
        ->range($page * $limit, $limit);

      $ids = $query->execute();
      $items = [];

      if (!empty($ids)) {
        $incidents = $storage->loadMultiple($ids);
        foreach ($incidents as $incident) {
          $timelineRaw = $incident->get('timeline')->value ?? '[]';
          $items[] = [
            'id' => (int) $incident->id(),
            'uuid' => $incident->uuid(),
            'title' => $incident->get('title')->value ?? '',
            'component' => $incident->get('component')->value ?? '',
            'severity' => $incident->get('severity')->value ?? '',
            'started_at' => $incident->get('started_at')->value ?? '',
            'resolved_at' => $incident->get('resolved_at')->value,
            'duration_minutes' => $incident->get('duration_minutes')->value ? (float) $incident->get('duration_minutes')->value : NULL,
            'root_cause' => $incident->get('root_cause')->value ?? '',
            'preventive_actions' => $incident->get('preventive_actions')->value ?? '',
            'timeline' => json_decode($timelineRaw, TRUE) ?? [],
            'created' => $incident->get('created')->value ?? '',
          ];
        }
      }

      return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
      ];
    }
    catch (\Exception $e) {
      return [
        'items' => [],
        'total' => 0,
        'page' => $page,
        'limit' => $limit,
      ];
    }
  }

}
