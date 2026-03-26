<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * Support Copilot Bridge — contextualiza el copilot con datos de soporte.
 */
class SupportCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?SupportAnalyticsService $analytics = NULL,
    protected ?SupportHealthScoreService $healthScore = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'support',
      'has_support_data' => FALSE,
      'open_tickets_count' => 0,
      'sla_breached_count' => 0,
      'avg_resolution_hours' => 0,
      'recent_tickets' => [],
      'category_distribution' => [],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('support_ticket');

      // Open tickets count.
      $openStatuses = ['new', 'ai_handling', 'open', 'pending_customer', 'pending_internal', 'escalated', 'reopened'];
      $openCount = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', $openStatuses, 'IN')
        ->count()
        ->execute();
      $context['open_tickets_count'] = $openCount;

      // SLA breached count.
      $breachedCount = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('sla_breached', TRUE)
        ->condition('status', $openStatuses, 'IN')
        ->count()
        ->execute();
      $context['sla_breached_count'] = $breachedCount;

      // Recent tickets (last 5 open).
      $recentIds = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', $openStatuses, 'IN')
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      if ($recentIds !== []) {
        $tickets = $storage->loadMultiple($recentIds);
        foreach ($tickets as $ticket) {
          if (!$ticket instanceof SupportTicketInterface) {
            continue;
          }
          $context['recent_tickets'][] = [
            'number' => $ticket->getTicketNumber(),
            'subject' => (string) ($ticket->get('subject')->value ?? ''),
            'status' => $ticket->getStatus(),
            'priority' => $ticket->getPriority(),
            'category' => (string) ($ticket->get('category')->value ?? ''),
          ];
        }
      }

      // Category distribution.
      $allOpenIds = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', $openStatuses, 'IN')
        ->execute();

      if ($allOpenIds !== []) {
        $allOpen = $storage->loadMultiple($allOpenIds);
        $cats = [];
        foreach ($allOpen as $t) {
          if (!$t instanceof SupportTicketInterface) {
            continue;
          }
          $cat = (string) ($t->get('category')->value ?? 'sin_categoria');
          $cats[$cat] = ($cats[$cat] ?? 0) + 1;
        }
        arsort($cats);
        $context['category_distribution'] = $cats;
      }

      $context['has_support_data'] = $context['open_tickets_count'] > 0;

      // Health score if available (requires tenant context).
      if ($this->healthScore !== NULL) {
        try {
          // @todo Resolve tenant ID from user context for accurate score.
          $context['health_score'] = $this->healthScore->calculateSupportScore(0);
        }
        catch (\Throwable $e) {
          // Health score not critical.
        }
      }

      $context['_system_prompt_addition'] = $this->buildSystemPrompt($context);

    }
    catch (\Exception $e) {
      $this->logger->warning('Support CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('support_ticket');
      $breachedCount = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('sla_breached', TRUE)
        ->condition('status', ['new', 'open', 'escalated', 'reopened'], 'IN')
        ->count()
        ->execute();

      if ($breachedCount > 0) {
        return [
          'message' => sprintf(
            'Hay %d tickets con SLA incumplido. Requieren atencion inmediata.',
            $breachedCount,
          ),
          'cta' => [
            'label' => 'Ver tickets urgentes',
            'route' => 'jaraba_support.agent.dashboard',
          ],
          'trigger' => 'sla_breached',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Support soft suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Builds support-specific system prompt.
   *
   * @param array<string, mixed> $context
   *   The support context data.
   */
  protected function buildSystemPrompt(array $context): string {
    $prompt = "Tienes acceso a datos del sistema de soporte. ";

    $prompt .= sprintf(
      "Tickets abiertos: %d. SLA incumplido: %d. ",
      $context['open_tickets_count'],
      $context['sla_breached_count'],
    );

    if ($context['category_distribution'] !== []) {
      $prompt .= "Distribucion por categoria: ";
      foreach (array_slice($context['category_distribution'], 0, 5, TRUE) as $cat => $count) {
        $prompt .= sprintf("%s=%d ", $cat, $count);
      }
      $prompt .= ". ";
    }

    if ($context['recent_tickets'] !== []) {
      $prompt .= "Tickets recientes: ";
      foreach ($context['recent_tickets'] as $t) {
        $prompt .= sprintf("[%s: %s (%s, %s)] ", $t['number'], $t['subject'], $t['priority'], $t['status']);
      }
    }

    $prompt .= "Ayuda a priorizar tickets, detectar patrones de incidencias y mejorar tiempos de resolucion. ";
    $prompt .= "NUNCA inventes numeros de ticket ni datos de clientes.";

    return $prompt;
  }

}
