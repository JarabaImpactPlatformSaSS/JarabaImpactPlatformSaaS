<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_sla\Service\UptimeMonitorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the public status page at /status-page.
 *
 * Structure: Extends ControllerBase with DI for UptimeMonitorService.
 * Logic: Renders a themed status page showing component statuses, overall
 *   system health, and recent incidents. Publicly accessible (no auth required).
 */
class StatusPageController extends ControllerBase {

  /**
   * Constructs a StatusPageController.
   */
  public function __construct(
    protected readonly UptimeMonitorService $uptimeMonitor,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_sla.uptime_monitor'),
    );
  }

  /**
   * Renders the public status page.
   *
   * @return array
   *   A render array for the status page.
   */
  public function page(): array {
    $statusData = $this->uptimeMonitor->getStatusPageData();

    return [
      '#theme' => 'sla_status_page',
      '#overall_status' => $statusData['overall_status'],
      '#components' => $statusData['components'],
      '#recent_incidents' => $statusData['recent_incidents'],
      '#uptime_history' => [],
      '#attached' => [
        'library' => ['jaraba_sla/status-page'],
      ],
      '#cache' => [
        'max-age' => 30,
      ],
    ];
  }

}
