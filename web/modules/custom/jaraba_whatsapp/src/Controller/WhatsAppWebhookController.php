<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_whatsapp\Service\WhatsAppApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * WhatsApp webhook controller.
 *
 * GET: Verification (Meta setup).
 * POST: Receive messages (HMAC-SHA256 validated).
 *
 * AUDIT-SEC-001: HMAC + hash_equals().
 * Response 200 immediately, process async via queue.
 */
class WhatsAppWebhookController extends ControllerBase {

  protected WhatsAppApiService $apiService;
  protected QueueFactory $queueFactory;
  protected LoggerInterface $waLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->apiService = $container->get('jaraba_whatsapp.api_service');
    $instance->queueFactory = $container->get('queue');
    $instance->waLogger = $container->get('logger.channel.jaraba_whatsapp');
    return $instance;
  }

  /**
   * GET /api/v1/whatsapp/webhook — Meta verification.
   */
  public function verify(Request $request): Response {
    $mode = $request->query->get('hub_mode', '');
    $token = $request->query->get('hub_verify_token', '');
    $challenge = $request->query->get('hub_challenge', '');

    $verifyToken = getenv('WHATSAPP_VERIFY_TOKEN');

    if ($mode === 'subscribe' && $verifyToken !== false && hash_equals($verifyToken, $token)) {
      $this->waLogger->info('Webhook verified successfully.');
      return new Response($challenge, 200, ['Content-Type' => 'text/plain']);
    }

    $this->waLogger->warning('Webhook verification failed (mode: @m).', ['@m' => $mode]);
    return new JsonResponse(['error' => 'Forbidden'], 403);
  }

  /**
   * POST /api/v1/whatsapp/webhook — Receive messages.
   */
  public function handle(Request $request): JsonResponse {
    $payload = $request->getContent();
    $signature = $request->headers->get('X-Hub-Signature-256', '');

    // Validate HMAC signature.
    if ($signature === '' || !$this->apiService->verifyWebhookSignature($payload, $signature)) {
      $this->waLogger->warning('Invalid webhook signature.');
      return new JsonResponse(['error' => 'Invalid signature'], 403);
    }

    $data = json_decode($payload, TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Parse and enqueue.
    $parsed = $this->apiService->parseIncomingPayload($data);
    if ($parsed !== [] && isset($parsed['phone']) && $parsed['phone'] !== '') {
      $queue = $this->queueFactory->get('whatsapp_process_message');
      $queue->createItem($parsed);
    }

    // Always respond 200 immediately (Meta requirement: <5s).
    return new JsonResponse(['status' => 'ok'], 200);
  }

}
