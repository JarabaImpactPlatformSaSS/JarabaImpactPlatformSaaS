<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_email\Service\SendGridClientService;
use Drupal\jaraba_email\Service\SubscriberService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para webhooks de SendGrid.
 *
 * Recibe notificaciones de eventos: delivered, open, click,
 * bounce, spam_report, unsubscribe.
 */
class EmailWebhookController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected SendGridClientService $sendGridClient,
    protected SubscriberService $subscriberService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_email.sendgrid_client'),
      $container->get('jaraba_email.subscriber_service'),
      $container->get('logger.channel.jaraba_email'),
    );
  }

  /**
   * POST /api/v1/webhooks/sendgrid — Recibe eventos de SendGrid.
   */
  public function handleSendGridWebhook(Request $request): JsonResponse {
    // Validar firma HMAC.
    $signature = $request->headers->get('X-Twilio-Email-Event-Webhook-Signature', '');
    $timestamp = $request->headers->get('X-Twilio-Email-Event-Webhook-Timestamp', '');
    $payload = $request->getContent();

    if ($signature && !$this->sendGridClient->validateWebhookSignature($signature, $timestamp, $payload)) {
      $this->logger->warning('Firma de webhook SendGrid invalida.');
      return new JsonResponse(['success' => FALSE, 'error' => 'Invalid signature'], 403);
    }

    $events = json_decode($payload, TRUE);
    if (!is_array($events)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Invalid payload'], 400);
    }

    $processed = 0;
    $errors = 0;

    foreach ($events as $event) {
      try {
        $this->processEvent($event);
        $processed++;
      }
      catch (\Exception $e) {
        $errors++;
        $this->logger->error('Error procesando evento webhook: @error', ['@error' => $e->getMessage()]);
      }
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'processed' => $processed,
        'errors' => $errors,
      ],
    ]);
  }

  /**
   * Procesa un evento individual de SendGrid.
   */
  protected function processEvent(array $event): void {
    $eventType = $event['event'] ?? '';
    $email = $event['email'] ?? '';

    if (!$email) {
      return;
    }

    switch ($eventType) {
      case 'delivered':
        $this->logger->info('Email entregado a @email.', ['@email' => $email]);
        break;

      case 'open':
        $this->logger->info('Email abierto por @email.', ['@email' => $email]);
        break;

      case 'click':
        $url = $event['url'] ?? '';
        $this->logger->info('Click en @url por @email.', ['@url' => $url, '@email' => $email]);
        break;

      case 'bounce':
        $this->logger->warning('Bounce para @email: @reason', [
          '@email' => $email,
          '@reason' => $event['reason'] ?? 'unknown',
        ]);
        break;

      case 'spamreport':
        $this->logger->warning('Spam report de @email.', ['@email' => $email]);
        break;

      case 'unsubscribe':
        $this->logger->info('Desuscripcion de @email via SendGrid.', ['@email' => $email]);
        break;

      default:
        $this->logger->info('Evento SendGrid no manejado: @type para @email.', [
          '@type' => $eventType,
          '@email' => $email,
        ]);
    }

    // Procesar en SendGridClientService para logica adicional.
    $this->sendGridClient->processWebhookEvent($event);
  }

}
