<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_support\Service\TicketStreamService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE (Server-Sent Events) controller for real-time ticket updates.
 *
 * Closes GAP-SUP-13.
 */
class SupportStreamController extends ControllerBase {

  public function __construct(
    protected TicketStreamService $streamService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.stream'),
    );
  }

  /**
   * GET /api/v1/support/stream — SSE endpoint.
   */
  public function stream(Request $request): StreamedResponse {
    $userId = (int) $this->currentUser()->id();
    $lastEventId = $request->headers->get('Last-Event-ID');
    $isAgent = $this->currentUser()->hasPermission('use support agent dashboard');

    return new StreamedResponse(function () use ($userId, $lastEventId, $isAgent) {
      // Set SSE headers.
      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache');
      header('Connection: keep-alive');
      header('X-Accel-Buffering: no');

      // Both agents and customers use the same event generator.
      // Agents see all their assigned tickets; customers are filtered
      // by the stream service based on reporter_uid from the event data.
      $generator = $this->streamService->getEventsForAgent($userId, $lastEventId);

      foreach ($generator as $event) {
        echo "id: " . ($event['id'] ?? uniqid()) . "\n";
        echo "event: " . ($event['type'] ?? 'message') . "\n";
        echo "data: " . json_encode($event['data'] ?? []) . "\n\n";
        ob_flush();
        flush();
      }
    }, 200, [
      'Content-Type' => 'text/event-stream',
      'Cache-Control' => 'no-cache',
      'Connection' => 'keep-alive',
    ]);
  }

}
