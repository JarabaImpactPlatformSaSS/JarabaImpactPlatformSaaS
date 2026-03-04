<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Service\TicketService;
use Drupal\jaraba_support\Service\SlaEngineService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for ticket detail view (conversational timeline).
 *
 * Frontend route with _admin_route: FALSE.
 */
class TicketDetailController extends ControllerBase {

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
    protected SlaEngineService $slaEngine,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.sla_engine'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Ticket detail view — GET /soporte/ticket/{support_ticket}.
   */
  public function view(SupportTicketInterface $support_ticket): array {
    $isAgent = $this->currentUser()->hasPermission('use support agent dashboard');
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
        'author' => $authorName,
        'author_role' => $type,
        'is_internal_note' => $message->isInternalNote(),
        'is_ai_generated' => $message->isAiGenerated(),
        'created' => $dateFormatter->format((int) $message->get('created')->value, 'medium'),
        'attachments' => [],
      ];
    }

    $canRespond = $this->currentUser()->hasPermission('edit own support ticket')
      || $this->currentUser()->hasPermission('edit any support ticket');

    $canClose = !$support_ticket->isResolved() && $canRespond;

    $slaStatus = $this->slaEngine->checkSlaStatus($support_ticket);

    // Format ticket as array for template.
    $ticketData = $this->formatTicketForTemplate($support_ticket, $dateFormatter);

    $apiBase = Url::fromRoute('jaraba_support.api.tickets.list')->toString();

    return [
      '#theme' => 'support_ticket_detail',
      '#ticket' => $ticketData,
      '#messages' => $formattedMessages,
      '#attachments' => [],
      '#sla_status' => is_array($slaStatus) ? $slaStatus : [],
      '#can_respond' => $canRespond,
      '#can_close' => $canClose,
      '#is_agent' => $isAgent,
      '#attached' => [
        'library' => [
          'jaraba_support/ticket-detail',
          'ecosistema_jaraba_theme/bundle-support',
        ],
        'drupalSettings' => [
          'jarabaSupport' => [
            'ticketId' => $support_ticket->id(),
            'apiBaseUrl' => $apiBase,
            'sseUrl' => Url::fromRoute('jaraba_support.stream')->toString(),
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
   * Formats a ticket entity into a template-ready array.
   */
  private function formatTicketForTemplate(SupportTicketInterface $ticket, $dateFormatter): array {
    $reporter = $ticket->getOwner();
    $assigneeName = '';
    if (!$ticket->get('assignee_uid')->isEmpty()) {
      $assignee = $ticket->get('assignee_uid')->entity;
      $assigneeName = $assignee ? $assignee->getDisplayName() : '';
    }

    $tags = $ticket->getTags();

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
      'tags' => $tags,
      'sla_breached' => $ticket->isSlaBreached(),
    ];
  }

  /**
   * Title callback for ticket detail.
   */
  public function title(SupportTicketInterface $support_ticket): TranslatableMarkup {
    return $this->t('Ticket @number — @subject', [
      '@number' => $support_ticket->getTicketNumber(),
      '@subject' => $support_ticket->label(),
    ]);
  }

}
