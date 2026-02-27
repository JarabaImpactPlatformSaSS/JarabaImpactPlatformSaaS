<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\AutonomousAgentService;
use Drupal\jaraba_ai_agents\Service\AutoDiagnosticService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GAP-L5-F/G: Autonomous Agents & Self-Healing Dashboard.
 *
 * Admin page showing active autonomous sessions, health indicators,
 * and recent remediation actions.
 */
class AutonomousAgentDashboardController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected AutonomousAgentService $autonomousAgent,
    protected AutoDiagnosticService $autoDiagnostic,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.autonomous_agent'),
      $container->get('jaraba_ai_agents.auto_diagnostic'),
    );
  }

  /**
   * Renders the autonomous agents dashboard.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $stats = $this->autonomousAgent->getStats();
    $activeSessions = $this->autonomousAgent->getSessions('', 'active');
    $escalatedSessions = $this->autonomousAgent->getSessions('', 'escalated');
    $recentRemediations = $this->autoDiagnostic->getRecentRemediations('', 10);

    // Build session summaries for the template.
    $sessionData = [];
    foreach ($activeSessions as $session) {
      $sessionData[] = [
        'id' => $session->id(),
        'agent_type' => $session->getAgentType(),
        'status' => $session->getSessionStatus(),
        'execution_count' => $session->getExecutionCount(),
        'total_cost' => round($session->getTotalCost(), 4),
        'cost_ceiling' => $session->getCostCeiling(),
        'consecutive_failures' => $session->getConsecutiveFailures(),
        'tenant_id' => $session->get('tenant_id')->value ?? '',
      ];
    }

    $escalatedData = [];
    foreach ($escalatedSessions as $session) {
      $escalatedData[] = [
        'id' => $session->id(),
        'agent_type' => $session->getAgentType(),
        'escalation_reason' => $session->get('escalation_reason')->value ?? '',
        'total_cost' => round($session->getTotalCost(), 4),
        'tenant_id' => $session->get('tenant_id')->value ?? '',
      ];
    }

    $remediationData = [];
    foreach ($recentRemediations as $entry) {
      $remediationData[] = [
        'id' => $entry->id(),
        'anomaly_type' => $entry->getAnomalyType(),
        'action' => $entry->getRemediationAction(),
        'outcome' => $entry->getOutcome(),
        'severity' => $entry->get('severity')->value ?? 'warning',
        'detected_value' => $entry->get('detected_value')->value ?? 0,
        'threshold_value' => $entry->get('threshold_value')->value ?? 0,
      ];
    }

    return [
      '#theme' => 'autonomous_agents_dashboard',
      '#stats' => $stats,
      '#active_sessions' => $sessionData,
      '#escalated_sessions' => $escalatedData,
      '#recent_remediations' => $remediationData,
      '#agent_types' => [
        'reputation_monitor' => $this->t('Reputation Monitor'),
        'content_curator' => $this->t('Content Curator'),
        'kb_maintainer' => $this->t('Knowledge Base Maintainer'),
        'churn_prevention' => $this->t('Churn Prevention'),
      ],
    ];
  }

}
