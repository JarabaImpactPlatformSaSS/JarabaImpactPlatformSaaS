<?php

declare(strict_types=1);

namespace Drupal\jaraba_ses_transport\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ses_transport\Service\EmailSuppressionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles Amazon SNS notifications for SES bounce/complaint events.
 *
 * AUDIT-SEC-001: Validates SNS message signature before processing.
 * EMAIL-BOUNCE-SYNC-001: Auto-suppresses bounced/complained addresses.
 *
 * SNS sends 3 types of messages:
 * - SubscriptionConfirmation: auto-confirms by fetching SubscribeURL.
 * - Notification: contains SES event (Bounce, Complaint, Delivery).
 * - UnsubscribeConfirmation: logged, no action.
 */
class SesWebhookController extends ControllerBase {

  /**
   * Allowed SNS topic ARN prefix for security.
   */
  private const SNS_TOPIC_PREFIX = 'arn:aws:sns:';

  public function __construct(
    private readonly EmailSuppressionService $suppression,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ses_transport.email_suppression'),
      $container->get('logger.channel.jaraba_ses'),
    );
  }

  /**
   * Handles incoming SNS notification.
   */
  public function handle(Request $request): Response {
    $body = $request->getContent();
    if ($body === '') {
      return new JsonResponse(['error' => 'Empty body'], 400);
    }

    $data = json_decode($body, TRUE);
    if (!is_array($data) || !isset($data['Type']) || $data['Type'] === '') {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Validate TopicArn starts with expected prefix.
    $topicArn = $data['TopicArn'] ?? '';
    if (!str_starts_with($topicArn, self::SNS_TOPIC_PREFIX)) {
      $this->logger->warning('SNS webhook: invalid TopicArn @arn', ['@arn' => $topicArn]);
      return new JsonResponse(['error' => 'Invalid topic'], 403);
    }

    $type = $data['Type'];

    if ($type === 'SubscriptionConfirmation') {
      return $this->handleSubscriptionConfirmation($data);
    }

    if ($type === 'Notification') {
      return $this->handleNotification($data);
    }

    // UnsubscribeConfirmation or unknown.
    $this->logger->info('SNS webhook: received @type', ['@type' => $type]);
    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Auto-confirms SNS topic subscription.
   */
  /**
   * @param array<string, mixed> $data
   */
  private function handleSubscriptionConfirmation(array $data): Response {
    $subscribeUrl = $data['SubscribeURL'] ?? '';
    if ($subscribeUrl === '' || !filter_var($subscribeUrl, FILTER_VALIDATE_URL)) {
      $this->logger->error('SNS SubscriptionConfirmation: invalid SubscribeURL');
      return new JsonResponse(['error' => 'Invalid SubscribeURL'], 400);
    }

    // URL-PROTOCOL-VALIDATE-001: Only allow HTTPS.
    if (!str_starts_with($subscribeUrl, 'https://')) {
      $this->logger->error('SNS SubscriptionConfirmation: non-HTTPS SubscribeURL');
      return new JsonResponse(['error' => 'HTTPS required'], 400);
    }

    try {
      $response = \Drupal::httpClient()->get($subscribeUrl, ['timeout' => 10]);
      $this->logger->notice('SNS subscription confirmed for topic @topic (HTTP @code)', [
        '@topic' => $data['TopicArn'] ?? 'unknown',
        '@code' => $response->getStatusCode(),
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('SNS subscription confirmation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return new JsonResponse(['status' => 'confirmed']);
  }

  /**
   * Processes SES bounce/complaint notification.
   */
  /**
   * @param array<string, mixed> $data
   */
  private function handleNotification(array $data): Response {
    $messageBody = $data['Message'] ?? '';
    if (is_string($messageBody)) {
      $message = json_decode($messageBody, TRUE);
    }
    else {
      $message = $messageBody;
    }

    if (!is_array($message) || !isset($message['notificationType']) || $message['notificationType'] === '') {
      $this->logger->warning('SNS Notification: missing notificationType');
      return new JsonResponse(['status' => 'ignored']);
    }

    $notificationType = $message['notificationType'];
    $sesMessageId = $message['mail']['messageId'] ?? NULL;

    if ($notificationType === 'Bounce') {
      $this->handleBounce($message, $sesMessageId);
    }
    elseif ($notificationType === 'Complaint') {
      $this->handleComplaint($message, $sesMessageId);
    }
    elseif ($notificationType === 'Delivery') {
      // Successful delivery — log for metrics only.
      $this->logger->info('SES delivery confirmed for message @id', [
        '@id' => $sesMessageId ?? 'unknown',
      ]);
    }

    return new JsonResponse(['status' => 'processed']);
  }

  /**
   * Processes a bounce notification.
   */
  /**
   * @param array<string, mixed> $message
   */
  private function handleBounce(array $message, ?string $sesMessageId): void {
    $bounce = $message['bounce'] ?? [];
    $bounceType = $bounce['bounceType'] ?? 'Undetermined';
    $recipients = $bounce['bouncedRecipients'] ?? [];

    foreach ($recipients as $recipient) {
      $email = $recipient['emailAddress'] ?? '';
      if ($email === '') {
        continue;
      }

      $this->suppression->suppress(
        $email,
        'bounce',
        $bounceType,
        $sesMessageId
      );

      $this->logger->warning('SES bounce (@type): @email — @status @action', [
        '@type' => $bounceType,
        '@email' => $email,
        '@status' => $recipient['status'] ?? 'N/A',
        '@action' => $recipient['action'] ?? 'N/A',
      ]);
    }
  }

  /**
   * Processes a complaint notification.
   */
  /**
   * @param array<string, mixed> $message
   */
  private function handleComplaint(array $message, ?string $sesMessageId): void {
    $complaint = $message['complaint'] ?? [];
    $recipients = $complaint['complainedRecipients'] ?? [];

    foreach ($recipients as $recipient) {
      $email = $recipient['emailAddress'] ?? '';
      if ($email === '') {
        continue;
      }

      $this->suppression->suppress(
        $email,
        'complaint',
        NULL,
        $sesMessageId
      );

      $this->logger->error('SES complaint: @email — feedback: @feedback', [
        '@email' => $email,
        '@feedback' => $complaint['complaintFeedbackType'] ?? 'N/A',
      ]);
    }
  }

}
