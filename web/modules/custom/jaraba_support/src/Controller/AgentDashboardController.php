<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Service\TicketService;
use Drupal\jaraba_support\Service\SupportAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the support agent dashboard.
 *
 * Frontend route with _admin_route: FALSE — agents use the frontend theme.
 */
class AgentDashboardController extends ControllerBase {

  public function __construct(
    protected TicketService $ticketService,
    protected SupportAnalyticsService $analytics,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.analytics'),
    );
  }

  /**
   * Agent dashboard — GET /soporte/agente.
   */
  public function dashboard(): array {
    $agentUid = (int) $this->currentUser()->id();
    $tickets = $this->ticketService->getTicketsForAgent($agentUid);
    $dateFormatter = \Drupal::service('date.formatter');

    $formattedTickets = [];
    foreach ($tickets as $ticket) {
      $reporterName = '';
      $reporterEmail = '';
      $reporter = $ticket->getOwner();
      if ($reporter) {
        $reporterName = $reporter->getDisplayName();
        $reporterEmail = $reporter->getEmail();
      }

      $assigneeName = '';
      if (!$ticket->get('assignee_uid')->isEmpty()) {
        $assignee = $ticket->get('assignee_uid')->entity;
        $assigneeName = $assignee ? $assignee->getDisplayName() : '';
      }

      $formattedTickets[] = [
        'id' => $ticket->id(),
        'ticket_number' => $ticket->getTicketNumber(),
        'subject' => $ticket->label(),
        'status' => $ticket->getStatus(),
        'priority' => $ticket->getPriority(),
        'category' => $ticket->get('category')->value ?? '',
        'vertical' => $ticket->get('vertical')->value ?? '',
        'created' => $dateFormatter->format((int) $ticket->get('created')->value, 'medium'),
        'updated' => $ticket->get('changed')->value
          ? $dateFormatter->format((int) $ticket->get('changed')->value, 'medium')
          : '',
        'sla_breached' => $ticket->isSlaBreached(),
        'assigned_agent' => $assigneeName,
        'requester' => [
          'name' => $reporterName,
          'email' => $reporterEmail,
        ],
      ];
    }

    $apiBase = \Drupal\Core\Url::fromRoute('jaraba_support.api.tickets.list')->toString();

    return [
      '#theme' => 'support_agent_dashboard',
      '#tickets' => $formattedTickets,
      '#stats' => $this->analytics->getOverviewStats(),
      '#saved_views' => [],
      '#agent_metrics' => $this->analytics->getAgentPerformance($agentUid),
      '#attached' => [
        'library' => [
          'jaraba_support/agent-dashboard',
          'ecosistema_jaraba_theme/bundle-support',
        ],
        'drupalSettings' => [
          'jarabaSupport' => [
            'agentUid' => $agentUid,
            'apiBaseUrl' => $apiBase,
            'agentSseUrl' => \Drupal\Core\Url::fromRoute('jaraba_support.stream')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['support_ticket_list'],
        'contexts' => ['user', 'languages'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Agent ticket view — GET /soporte/agente/ticket/{support_ticket}.
   */
  public function ticketView(SupportTicketInterface $support_ticket): array {
    $isAgent = TRUE;
    $messages = $this->ticketService->getMessages($support_ticket, $isAgent);

    $formattedMessages = [];
    foreach ($messages as $message) {
      $authorName = '';
      if (!$message->get('author_uid')->isEmpty()) {
        $author = $message->get('author_uid')->entity;
        $authorName = $author ? $author->getDisplayName() : '';
      }

      $formattedMessages[] = [
        'id' => $message->id(),
        'body' => $message->get('body')->value ?? '',
        'author_name' => $authorName ?: (string) $this->t('System'),
        'author_type' => $message->getAuthorType(),
        'is_internal_note' => $message->isInternalNote(),
        'is_ai_generated' => $message->isAiGenerated(),
        'created' => $message->get('created')->value,
      ];
    }

    return [
      '#theme' => 'support_ticket_detail',
      '#ticket' => $support_ticket,
      '#messages' => $formattedMessages,
      '#attachments' => [],
      '#sla_status' => 'ok',
      '#can_respond' => TRUE,
      '#can_close' => !$support_ticket->isResolved(),
      '#is_agent' => TRUE,
      '#attached' => [
        'library' => [
          'jaraba_support/ticket-detail',
          'ecosistema_jaraba_theme/bundle-support',
        ],
      ],
      '#cache' => [
        'tags' => ['support_ticket:' . $support_ticket->id()],
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Title callback for agent ticket view.
   */
  public function ticketTitle(SupportTicketInterface $support_ticket): TranslatableMarkup {
    return $this->t('Agent: @number — @subject', [
      '@number' => $support_ticket->getTicketNumber(),
      '@subject' => $support_ticket->label(),
    ]);
  }

}
