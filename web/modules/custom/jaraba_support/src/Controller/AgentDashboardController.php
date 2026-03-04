<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Service\TicketRoutingService;
use Drupal\jaraba_support\Service\TicketService;
use Drupal\jaraba_support\Service\SupportAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the support agent dashboard.
 *
 * Frontend route with _admin_route: FALSE — agents use the frontend theme.
 */
class AgentDashboardController extends ControllerBase {

  /**
   * HTML tags allowed in message body output.
   */
  private const ALLOWED_TAGS = [
    'b', 'i', 'strong', 'em', 'a', 'br', 'p',
    'ul', 'ol', 'li', 'code', 'pre', 'blockquote',
    'h2', 'h3', 'h4',
  ];

  public function __construct(
    protected TicketService $ticketService,
    protected SupportAnalyticsService $analytics,
    protected DateFormatterInterface $dateFormatter,
    protected ?TicketRoutingService $ticketRouting = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.analytics'),
      $container->get('date.formatter'),
      $container->get('jaraba_support.routing'),
    );
  }

  /**
   * Agent dashboard — GET /soporte/agente.
   */
  public function dashboard(): array {
    $agentUid = (int) $this->currentUser()->id();
    $tickets = $this->ticketService->getTicketsForAgent($agentUid);
    $dateFormatter = $this->dateFormatter;

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
        'subject' => Xss::filter($ticket->label() ?? '', []),
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

    $apiBase = Url::fromRoute('jaraba_support.api.tickets.list')->toString();

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
            'agentSseUrl' => Url::fromRoute('jaraba_support.stream')->toString(),
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
    $dateFormatter = $this->dateFormatter;

    $formattedMessages = [];
    foreach ($messages as $message) {
      $authorName = '';
      if (!$message->get('author_uid')->isEmpty()) {
        $author = $message->get('author_uid')->entity;
        $authorName = $author ? $author->getDisplayName() : '';
      }
      $type = $message->getAuthorType();
      if (empty($authorName)) {
        $authorName = match ($type) {
          'ai' => (string) $this->t('AI Assistant'),
          'system' => (string) $this->t('System'),
          default => (string) $this->t('Unknown'),
        };
      }

      $formattedMessages[] = [
        'id' => $message->id(),
        'body' => Xss::filter($message->get('body')->value ?? '', self::ALLOWED_TAGS),
        'author' => Xss::filter($authorName, []),
        'author_role' => $type,
        'is_internal_note' => $message->isInternalNote(),
        'is_ai_generated' => $message->isAiGenerated(),
        'created' => $dateFormatter->format((int) $message->get('created')->value, 'medium'),
        'attachments' => [],
      ];
    }

    // SEC-001: Format ticket as sanitized array, NEVER pass entity object.
    $ticketData = $this->formatTicketForTemplate($support_ticket, $dateFormatter);

    $apiBase = Url::fromRoute('jaraba_support.api.tickets.list')->toString();

    return [
      '#theme' => 'support_ticket_detail',
      '#ticket' => $ticketData,
      '#messages' => $formattedMessages,
      '#attachments' => [],
      '#sla_status' => [],
      '#can_respond' => TRUE,
      '#can_close' => !$support_ticket->isResolved(),
      '#is_agent' => TRUE,
      '#attached' => [
        'library' => [
          'jaraba_support/ticket-detail',
          'ecosistema_jaraba_theme/bundle-support',
        ],
        'drupalSettings' => [
          'jarabaSupport' => [
            'ticketId' => $support_ticket->id(),
            'apiBaseUrl' => $apiBase,
            'agentSseUrl' => Url::fromRoute('jaraba_support.stream')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['support_ticket:' . $support_ticket->id()],
        'contexts' => ['user', 'languages'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Formats a ticket entity into a template-ready sanitized array.
   */
  private function formatTicketForTemplate(SupportTicketInterface $ticket, $dateFormatter): array {
    $reporter = $ticket->getOwner();
    $assigneeName = '';
    if (!$ticket->get('assignee_uid')->isEmpty()) {
      $assignee = $ticket->get('assignee_uid')->entity;
      $assigneeName = $assignee ? $assignee->getDisplayName() : '';
    }

    return [
      'id' => $ticket->id(),
      'ticket_number' => $ticket->getTicketNumber(),
      'subject' => Xss::filter($ticket->label() ?? '', []),
      'description' => Xss::filter($ticket->get('description')->value ?? '', self::ALLOWED_TAGS),
      'status' => $ticket->getStatus(),
      'priority' => $ticket->getPriority(),
      'category' => $ticket->get('category')->value ?? '',
      'vertical' => $ticket->get('vertical')->value ?? '',
      'channel' => $ticket->get('channel')->value ?? '',
      'created' => $dateFormatter->format((int) $ticket->get('created')->value, 'medium'),
      'updated' => $ticket->get('changed')->value
        ? $dateFormatter->format((int) $ticket->get('changed')->value, 'medium')
        : '',
      'assigned_agent' => $assigneeName,
      'requester' => [
        'name' => $reporter ? $reporter->getDisplayName() : '',
        'email' => $reporter ? $reporter->getEmail() : '',
      ],
      'tags' => $ticket->getTags(),
      'sla_breached' => $ticket->isSlaBreached(),
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
