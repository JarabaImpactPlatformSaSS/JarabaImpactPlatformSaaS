<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\jaraba_support\Form\SupportTicketForm;
use Drupal\jaraba_support\Service\TicketDeflectionService;
use Drupal\jaraba_support\Service\TicketService;
use Drupal\jaraba_support\Service\SlaEngineService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the tenant-facing support portal.
 *
 * Frontend routes with _admin_route: FALSE — tenant never sees admin theme.
 */
class SupportPortalController extends ControllerBase {

  public function __construct(
    protected TicketService $ticketService,
    protected TicketDeflectionService $deflectionService,
    protected SlaEngineService $slaEngine,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.deflection'),
      $container->get('jaraba_support.sla_engine'),
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  /**
   * My Tickets list — GET /soporte.
   */
  public function index(): array {
    $tenant = $this->tenantContext?->getCurrentTenant();
    $userId = (int) $this->currentUser()->id();

    $tickets = $this->ticketService->getTicketsForTenant(
      $tenant ? (int) $tenant->id() : NULL,
      ['reporter_uid' => $userId]
    );

    $formattedTickets = [];
    foreach ($tickets as $ticket) {
      $formattedTickets[] = [
        'id' => $ticket->id(),
        'ticket_number' => $ticket->getTicketNumber(),
        'subject' => $ticket->label(),
        'status' => $ticket->getStatus(),
        'priority' => $ticket->getPriority(),
        'vertical' => $ticket->get('vertical')->value ?? '',
        'created' => $ticket->get('created')->value,
        'sla_breached' => $ticket->isSlaBreached(),
        'assignee_name' => '',
        'url' => $ticket->toUrl('canonical')->toString(),
      ];

      if (!$ticket->get('assignee_uid')->isEmpty()) {
        $assignee = $ticket->get('assignee_uid')->entity;
        $formattedTickets[array_key_last($formattedTickets)]['assignee_name'] = $assignee ? $assignee->getDisplayName() : '';
      }
    }

    return [
      '#theme' => 'support_portal',
      '#tickets' => $formattedTickets,
      '#stats' => $this->getQuickStats($userId, $tenant ? (int) $tenant->id() : NULL),
      '#can_create' => $this->currentUser()->hasPermission('create support ticket'),
      '#attached' => [
        'library' => [
          'jaraba_support/support-portal',
          'ecosistema_jaraba_theme/bundle-support',
        ],
        'drupalSettings' => [
          'jarabaSupport' => [
            'apiBaseUrl' => \Drupal\Core\Url::fromRoute('jaraba_support.api.tickets.create')->toString(),
            'createUrl' => \Drupal\Core\Url::fromRoute('jaraba_support.portal.create')->toString(),
            'portalUrl' => \Drupal\Core\Url::fromRoute('jaraba_support.portal')->toString(),
            'deflectionUrl' => \Drupal\Core\Url::fromRoute('jaraba_support.api.deflection')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['support_ticket_list'],
        'contexts' => ['user', 'languages'],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Create ticket form — GET /soporte/crear.
   *
   * SLIDE-PANEL-RENDER-001: Uses renderPlain() for slide-panel requests.
   */
  public function createForm(Request $request): Response|array {
    $form = $this->formBuilder()->getForm(SupportTicketForm::class);

    // SLIDE-PANEL-RENDER-001: Check if slide-panel request.
    if ($this->isSlidePanelRequest($request)) {
      $form['#action'] = $request->getRequestUri();
      $html = (string) \Drupal::service('renderer')->renderPlain($form);
      return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return $form;
  }

  /**
   * Quick stats for the portal header.
   */
  private function getQuickStats(int $userId, ?int $tenantId): array {
    $tickets = $this->ticketService->getTicketsForTenant($tenantId, ['reporter_uid' => $userId], 100);

    $open = 0;
    $resolved = 0;
    $pending = 0;
    foreach ($tickets as $ticket) {
      $status = $ticket->getStatus();
      if (in_array($status, ['new', 'open', 'ai_handling', 'escalated', 'reopened'], TRUE)) {
        $open++;
      }
      elseif (in_array($status, ['resolved', 'closed'], TRUE)) {
        $resolved++;
      }
      elseif (str_starts_with($status, 'pending')) {
        $pending++;
      }
    }

    return [
      'total' => count($tickets),
      'open' => $open,
      'resolved' => $resolved,
      'pending' => $pending,
    ];
  }

  /**
   * Detects slide-panel (AJAX) requests.
   *
   * SLIDE-PANEL-RENDER-001: isXmlHttpRequest() AND NOT _wrapper_format.
   */
  private function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
  }

}
