<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Service\AttachmentService;
use Drupal\jaraba_support\Service\AttachmentUrlService;
use Drupal\jaraba_support\Service\CsatSurveyService;
use Drupal\jaraba_support\Service\SupportAnalyticsService;
use Drupal\jaraba_support\Service\TicketAiClassificationService;
use Drupal\jaraba_support\Service\TicketAiResolutionService;
use Drupal\jaraba_support\Service\TicketDeflectionService;
use Drupal\jaraba_support\Service\TicketMergeService;
use Drupal\jaraba_support\Service\TicketService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for support tickets.
 *
 * API-WHITELIST-001: All request fields are filtered through ALLOWED_FIELDS.
 * CSRF: All POST/PATCH routes require _csrf_request_header_token.
 * ROUTE-LANGPREFIX-001: URLs generated via Url::fromRoute(), never hardcoded.
 */
class SupportApiController extends ControllerBase {

  /**
   * Allowed fields for ticket creation/update (API-WHITELIST-001).
   */
  private const ALLOWED_CREATE_FIELDS = [
    'subject', 'description', 'vertical', 'category', 'subcategory',
    'priority', 'severity', 'channel', 'related_entity_type', 'related_entity_id',
  ];

  private const ALLOWED_UPDATE_FIELDS = [
    'status', 'priority', 'severity', 'assignee_uid', 'tags',
    'category', 'subcategory',
  ];

  public function __construct(
    protected TicketService $ticketService,
    protected CsatSurveyService $csatService,
    protected TicketMergeService $mergeService,
    protected SupportAnalyticsService $analytics,
    protected TicketDeflectionService $deflectionService,
    protected ?TicketAiClassificationService $aiClassification,
    protected ?TicketAiResolutionService $aiResolution,
    protected ?AttachmentService $attachmentService,
    protected ?AttachmentUrlService $attachmentUrlService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.csat'),
      $container->get('jaraba_support.merge'),
      $container->get('jaraba_support.analytics'),
      $container->get('jaraba_support.deflection'),
      $container->has('jaraba_support.ai_classification') ? $container->get('jaraba_support.ai_classification') : NULL,
      $container->has('jaraba_support.ai_resolution') ? $container->get('jaraba_support.ai_resolution') : NULL,
      $container->has('jaraba_support.attachment') ? $container->get('jaraba_support.attachment') : NULL,
      $container->has('jaraba_support.attachment_url') ? $container->get('jaraba_support.attachment_url') : NULL,
    );
  }

  /**
   * POST /api/v1/support/tickets — Create a new ticket.
   */
  public function createTicket(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    // API-WHITELIST-001: Filter input.
    $filtered = array_intersect_key($data, array_flip(self::ALLOWED_CREATE_FIELDS));

    if (empty($filtered['subject']) || empty($filtered['description'])) {
      return new JsonResponse(['error' => 'Subject and description are required.'], 400);
    }

    try {
      $ticket = $this->ticketService->createTicket($filtered);
      return new JsonResponse([
        'id' => $ticket->id(),
        'ticket_number' => $ticket->getTicketNumber(),
        'status' => $ticket->getStatus(),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/support/tickets — List tickets with filters.
   */
  public function listTickets(Request $request): JsonResponse {
    $filters = [];
    if ($status = $request->query->get('status')) {
      $filters['status'] = explode(',', $status);
    }
    if ($priority = $request->query->get('priority')) {
      $filters['priority'] = explode(',', $priority);
    }
    if ($vertical = $request->query->get('vertical')) {
      $filters['vertical'] = $vertical;
    }

    $limit = min((int) ($request->query->get('limit', 50)), 100);
    $offset = max((int) ($request->query->get('offset', 0)), 0);

    $tickets = $this->ticketService->getTicketsForTenant(NULL, $filters, $limit, $offset);

    $items = [];
    foreach ($tickets as $ticket) {
      $items[] = $this->serializeTicket($ticket);
    }

    return new JsonResponse(['data' => $items, 'count' => count($items)]);
  }

  /**
   * GET /api/v1/support/tickets/{support_ticket} — Get ticket detail.
   */
  public function getTicket(SupportTicketInterface $support_ticket): JsonResponse {
    return new JsonResponse($this->serializeTicket($support_ticket, TRUE));
  }

  /**
   * PATCH /api/v1/support/tickets/{support_ticket} — Update ticket.
   */
  public function updateTicket(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $filtered = array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS));

    try {
      foreach ($filtered as $field => $value) {
        if ($field === 'status') {
          $this->ticketService->transitionStatus($support_ticket, $value, (int) $this->currentUser()->id(), 'agent');
          continue;
        }
        if ($field === 'tags') {
          $support_ticket->set('tags', json_encode($value));
          continue;
        }
        $support_ticket->set($field, $value);
      }
      $support_ticket->save();

      return new JsonResponse($this->serializeTicket($support_ticket));
    }
    catch (\InvalidArgumentException $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/messages — Add message.
   */
  public function addMessage(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    if (empty($data['body'])) {
      return new JsonResponse(['error' => 'Message body is required.'], 400);
    }

    $authorType = $this->currentUser()->hasPermission('use support agent dashboard') ? 'agent' : 'customer';

    try {
      $message = $this->ticketService->addMessage(
        $support_ticket,
        $data['body'],
        $authorType,
        [
          'is_internal_note' => (bool) ($data['is_internal_note'] ?? FALSE),
        ]
      );

      return new JsonResponse([
        'id' => $message->id(),
        'author_type' => $message->getAuthorType(),
        'created' => $message->get('created')->value,
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/resolve.
   */
  public function resolveTicket(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $this->ticketService->resolveTicket(
        $support_ticket,
        $data['notes'] ?? '',
        (int) $this->currentUser()->id()
      );

      return new JsonResponse(['status' => 'resolved']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/reopen.
   */
  public function reopenTicket(SupportTicketInterface $support_ticket): JsonResponse {
    try {
      $this->ticketService->reopenTicket($support_ticket, (int) $this->currentUser()->id());
      return new JsonResponse(['status' => 'reopened']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/escalate.
   */
  public function escalateTicket(SupportTicketInterface $support_ticket): JsonResponse {
    try {
      $this->ticketService->transitionStatus(
        $support_ticket,
        'escalated',
        (int) $this->currentUser()->id(),
        'agent'
      );
      return new JsonResponse(['status' => 'escalated']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/satisfaction.
   */
  public function submitSatisfaction(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    if (empty($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
      return new JsonResponse(['error' => 'Rating must be between 1 and 5.'], 400);
    }

    try {
      $this->csatService->submitSatisfaction(
        $support_ticket,
        (int) $data['rating'],
        isset($data['effort_score']) ? (int) $data['effort_score'] : NULL,
        $data['comment'] ?? NULL
      );

      return new JsonResponse(['status' => 'submitted']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/merge.
   */
  public function mergeTicket(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    if (empty($data['duplicate_id'])) {
      return new JsonResponse(['error' => 'duplicate_id is required.'], 400);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('support_ticket');
      $duplicate = $storage->load($data['duplicate_id']);

      if (!$duplicate) {
        return new JsonResponse(['error' => 'Duplicate ticket not found.'], 404);
      }

      $this->mergeService->merge($support_ticket, $duplicate);
      return new JsonResponse(['status' => 'merged']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/support/stats.
   */
  public function getStats(): JsonResponse {
    return new JsonResponse($this->analytics->getOverviewStats());
  }

  /**
   * GET /api/v1/support/sla-policies.
   */
  public function listSlaPolicies(): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('sla_policy');
    $policies = $storage->loadMultiple();

    $items = [];
    foreach ($policies as $policy) {
      $items[] = [
        'id' => $policy->id(),
        'label' => $policy->label(),
        'plan_tier' => $policy->getPlanTier(),
        'priority' => $policy->getPriority(),
        'first_response_hours' => $policy->getFirstResponseHours(),
        'resolution_hours' => $policy->getResolutionHours(),
      ];
    }

    return new JsonResponse(['data' => $items]);
  }

  /**
   * POST /api/v1/support/ai/classify — Classify an existing ticket via AI.
   */
  public function classifyText(Request $request): JsonResponse {
    if ($this->aiClassification === NULL) {
      return new JsonResponse(['error' => 'AI classification service unavailable.'], 503);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $ticketId = $data['ticket_id'] ?? NULL;

    if (!$ticketId) {
      return new JsonResponse(['error' => 'ticket_id is required.'], 400);
    }

    try {
      $ticket = $this->entityTypeManager()->getStorage('support_ticket')->load($ticketId);
      if (!$ticket) {
        return new JsonResponse(['error' => 'Ticket not found.'], 404);
      }

      $result = $this->aiClassification->classify($ticket);
      return new JsonResponse($result);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/support/ai/suggest — Suggest resolution via AI.
   */
  public function suggestSolution(Request $request): JsonResponse {
    if ($this->aiResolution === NULL) {
      return new JsonResponse(['error' => 'AI resolution service unavailable.'], 503);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $ticketId = $data['ticket_id'] ?? NULL;

    if (!$ticketId) {
      return new JsonResponse(['error' => 'ticket_id is required.'], 400);
    }

    try {
      $ticket = $this->entityTypeManager()->getStorage('support_ticket')->load($ticketId);
      if (!$ticket) {
        return new JsonResponse(['error' => 'Ticket not found.'], 404);
      }

      $kbResults = $data['kb_results'] ?? [];
      $similarTickets = $data['similar_tickets'] ?? [];
      $result = $this->aiResolution->attemptResolution($ticket, $kbResults, $similarTickets);

      return new JsonResponse($result);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/support/search — Full-text search tickets.
   */
  public function searchTickets(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));
    if (mb_strlen($query) < 2) {
      return new JsonResponse(['data' => [], 'count' => 0]);
    }

    $limit = min((int) ($request->query->get('limit', 20)), 50);

    try {
      $storage = $this->entityTypeManager()->getStorage('support_ticket');
      $entityQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      // Search in subject field.
      $orGroup = $entityQuery->orConditionGroup()
        ->condition('subject', '%' . $query . '%', 'LIKE')
        ->condition('description', '%' . $query . '%', 'LIKE');
      $entityQuery->condition($orGroup);

      $ids = $entityQuery->execute();
      $tickets = $ids ? $storage->loadMultiple($ids) : [];

      $items = [];
      foreach ($tickets as $ticket) {
        $items[] = $this->serializeTicket($ticket);
      }

      return new JsonResponse(['data' => $items, 'count' => count($items)]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/support/deflection.
   */
  public function deflectionSearch(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $query = $data['query'] ?? '';

    if (empty($query)) {
      return new JsonResponse(['results' => []]);
    }

    $results = $this->deflectionService->searchDeflection($query);
    return new JsonResponse(['results' => $results]);
  }

  /**
   * POST /api/v1/support/inbound/email — Process inbound email webhook.
   */
  public function inboundEmail(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];

    $from = $data['from'] ?? '';
    $subject = $data['subject'] ?? '';
    $body = $data['body'] ?? $data['text'] ?? '';
    $inReplyTo = $data['in_reply_to'] ?? $data['headers']['In-Reply-To'] ?? '';

    if (empty($from) || empty($body)) {
      return new JsonResponse(['error' => 'From and body are required.'], 400);
    }

    try {
      // Check if this is a reply to an existing ticket (by ticket number in subject or In-Reply-To header).
      $ticketNumber = NULL;
      if (preg_match('/\[#(TK-\d+)\]/', $subject, $m)) {
        $ticketNumber = $m[1];
      }

      if ($ticketNumber) {
        // Find existing ticket.
        $storage = $this->entityTypeManager()->getStorage('support_ticket');
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('ticket_number', $ticketNumber)
          ->range(0, 1)
          ->execute();

        if (!empty($ids)) {
          $ticket = $storage->load(reset($ids));
          if ($ticket) {
            $this->ticketService->addMessage($ticket, $body, 'customer', [
              'is_internal_note' => FALSE,
            ]);
            return new JsonResponse(['action' => 'reply_added', 'ticket_number' => $ticketNumber]);
          }
        }
      }

      // Create new ticket from email.
      $ticket = $this->ticketService->createTicket([
        'subject' => $subject ?: 'Email support request',
        'description' => $body,
        'channel' => 'email',
      ]);

      return new JsonResponse([
        'action' => 'ticket_created',
        'ticket_number' => $ticket->getTicketNumber(),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /support/attachments/{attachment_id}/download — Download via signed URL.
   */
  public function downloadAttachment(Request $request, string $attachment_id): Response {
    if ($this->attachmentUrlService === NULL) {
      return new JsonResponse(['error' => 'Attachment service unavailable.'], 503);
    }

    $token = $request->query->get('token', '');
    if (empty($token)) {
      return new JsonResponse(['error' => 'Token required.'], 400);
    }

    $binaryResponse = $this->attachmentUrlService->validateAndServe($token, $this->currentUser());
    if ($binaryResponse === NULL) {
      return new JsonResponse(['error' => 'Invalid or expired download link.'], 403);
    }

    return $binaryResponse;
  }

  /**
   * POST /api/v1/support/tickets/{support_ticket}/attachments — Upload file.
   */
  public function uploadAttachment(Request $request, SupportTicketInterface $support_ticket): JsonResponse {
    if ($this->attachmentService === NULL) {
      return new JsonResponse(['error' => 'Attachment service unavailable.'], 503);
    }

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
    $file = $request->files->get('file');
    if (!$file instanceof UploadedFile || !$file->isValid()) {
      return new JsonResponse(['error' => 'Valid file upload required.'], 400);
    }

    $messageId = $request->request->get('message_id') ? (int) $request->request->get('message_id') : NULL;

    try {
      $attachment = $this->attachmentService->uploadAttachment(
        $support_ticket,
        $file,
        (int) $this->currentUser()->id(),
        $messageId
      );

      if ($attachment === NULL) {
        return new JsonResponse(['error' => 'File upload failed validation.'], 422);
      }

      $downloadUrl = $this->attachmentUrlService?->generateSignedUrl($attachment, $this->currentUser()) ?? '#';

      return new JsonResponse([
        'id' => $attachment->id(),
        'filename' => $attachment->get('filename')->value,
        'file_size' => $attachment->get('file_size')->value,
        'mime_type' => $attachment->get('mime_type')->value,
        'download_url' => $downloadUrl,
        'scan_status' => $attachment->get('scan_status')->value,
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/support/tickets/{support_ticket}/attachments — List attachments.
   */
  public function listAttachments(SupportTicketInterface $support_ticket): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('ticket_attachment');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('ticket_id', $support_ticket->id())
        ->sort('created', 'ASC')
        ->execute();

      $attachments = $ids ? $storage->loadMultiple($ids) : [];
      $items = [];

      foreach ($attachments as $attachment) {
        $downloadUrl = '#';
        if ($this->attachmentUrlService && $attachment->get('scan_status')->value === 'clean') {
          $downloadUrl = $this->attachmentUrlService->generateSignedUrl($attachment, $this->currentUser());
        }

        $items[] = [
          'id' => (int) $attachment->id(),
          'filename' => $attachment->get('filename')->value,
          'file_size' => (int) $attachment->get('file_size')->value,
          'mime_type' => $attachment->get('mime_type')->value,
          'scan_status' => $attachment->get('scan_status')->value,
          'download_url' => $downloadUrl,
          'created' => $attachment->get('created')->value,
        ];
      }

      return new JsonResponse(['data' => $items, 'count' => count($items)]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Serializes a ticket to array for JSON output.
   */
  private function serializeTicket(SupportTicketInterface $ticket, bool $detailed = FALSE): array {
    $data = [
      'id' => (int) $ticket->id(),
      'ticket_number' => $ticket->getTicketNumber(),
      'subject' => $ticket->label(),
      'status' => $ticket->getStatus(),
      'priority' => $ticket->getPriority(),
      'vertical' => $ticket->get('vertical')->value ?? '',
      'category' => $ticket->get('category')->value ?? '',
      'channel' => $ticket->get('channel')->value ?? '',
      'sla_breached' => $ticket->isSlaBreached(),
      'created' => $ticket->get('created')->value,
      'changed' => $ticket->get('changed')->value,
    ];

    if ($detailed) {
      $data['description'] = $ticket->get('description')->value ?? '';
      $data['subcategory'] = $ticket->get('subcategory')->value ?? '';
      $data['severity'] = $ticket->get('severity')->value ?? NULL;
      $data['ai_classification'] = $ticket->getAiClassification();
      $data['ai_resolution_attempted'] = (bool) $ticket->get('ai_resolution_attempted')->value;
      $data['tags'] = $ticket->getTags();
      $data['satisfaction_rating'] = $ticket->get('satisfaction_rating')->value;
      $data['resolved_at'] = $ticket->get('resolved_at')->value;
      $data['closed_at'] = $ticket->get('closed_at')->value;
    }

    return $data;
  }

}
