<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_integrations\Entity\WebhookSubscription;

/**
 * Servicio de despacho de webhooks con retry y firma HMAC.
 *
 * PROPÓSITO:
 * Cuando ocurre un evento en la plataforma, este servicio localiza
 * las suscripciones activas y envía el payload a cada URL de destino.
 *
 * SEGURIDAD:
 * - Payload firmado con HMAC-SHA256 usando el secret de la suscripción.
 * - Header X-Jaraba-Signature: sha256=<hex_digest>.
 * - Header X-Jaraba-Event: <event_name>.
 * - Header X-Jaraba-Delivery: <uuid>.
 *
 * RETRY:
 * - 3 intentos con backoff exponencial (1s, 4s, 16s).
 * - Tras N fallos consecutivos, la suscripción pasa a estado 'failing'.
 */
class WebhookDispatcherService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Despacha un evento a todas las suscripciones activas.
   *
   * @param string $event
   *   Nombre del evento (ej: 'order.created', 'tenant.updated').
   * @param array $payload
   *   Datos del evento.
   * @param string|null $tenant_id
   *   ID del tenant (para filtrar suscripciones). NULL = todas.
   *
   * @return int
   *   Número de entregas exitosas.
   */
  public function dispatch(string $event, array $payload, ?string $tenant_id = NULL): int {
    $subscriptions = $this->getActiveSubscriptions($event, $tenant_id);
    $success_count = 0;

    foreach ($subscriptions as $subscription) {
      if ($this->deliver($subscription, $event, $payload)) {
        $success_count++;
      }
    }

    $this->logger->info('Evento @event despachado: @success/@total entregas exitosas', [
      '@event' => $event,
      '@success' => $success_count,
      '@total' => count($subscriptions),
    ]);

    return $success_count;
  }

  /**
   * Entrega un webhook a una suscripción con retry.
   *
   * @param \Drupal\jaraba_integrations\Entity\WebhookSubscription $subscription
   *   La suscripción.
   * @param string $event
   *   Nombre del evento.
   * @param array $payload
   *   Datos del evento.
   *
   * @return bool
   *   TRUE si la entrega fue exitosa.
   */
  protected function deliver(WebhookSubscription $subscription, string $event, array $payload): bool {
    $config = \Drupal::config('jaraba_integrations.settings');
    $max_retries = $config->get('webhook_max_retries') ?? 3;
    $timeout = $config->get('webhook_timeout') ?? 30;

    $json_payload = json_encode([
      'event' => $event,
      'data' => $payload,
      'timestamp' => time(),
      'delivery_id' => \Drupal::service('uuid')->generate(),
    ], JSON_UNESCAPED_UNICODE);

    // Firma HMAC-SHA256.
    $signature = hash_hmac('sha256', $json_payload, $subscription->getSecret());

    $headers = [
      'Content-Type' => 'application/json',
      'X-Jaraba-Event' => $event,
      'X-Jaraba-Signature' => 'sha256=' . $signature,
      'X-Jaraba-Delivery' => \Drupal::service('uuid')->generate(),
      'User-Agent' => 'Jaraba-Webhooks/1.0',
    ];

    for ($attempt = 0; $attempt < $max_retries; $attempt++) {
      try {
        $response = $this->httpClient->request('POST', $subscription->getTargetUrl(), [
          'headers' => $headers,
          'body' => $json_payload,
          'timeout' => $timeout,
          'http_errors' => TRUE,
        ]);

        $status_code = $response->getStatusCode();

        // Éxito: 2xx.
        if ($status_code >= 200 && $status_code < 300) {
          $this->recordSuccess($subscription, $status_code);
          return TRUE;
        }
      }
      catch (RequestException $e) {
        $status_code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
        $this->logger->warning('Webhook @label fallo intento @attempt/@max (HTTP @code): @msg', [
          '@label' => $subscription->getLabel(),
          '@attempt' => $attempt + 1,
          '@max' => $max_retries,
          '@code' => $status_code,
          '@msg' => $e->getMessage(),
        ]);

        // Backoff exponencial: 1s, 4s, 16s.
        if ($attempt < $max_retries - 1) {
          usleep((int) (pow(4, $attempt) * 1000000));
        }
      }
    }

    // Todos los intentos fallaron.
    $this->recordFailure($subscription, $status_code ?? 0);
    return FALSE;
  }

  /**
   * Registra una entrega exitosa.
   */
  protected function recordSuccess(WebhookSubscription $subscription, int $status_code): void {
    $subscription->set('consecutive_failures', 0);
    $subscription->set('last_triggered', date('Y-m-d\TH:i:s'));
    $subscription->set('last_response_code', $status_code);
    $subscription->set('total_deliveries', ($subscription->get('total_deliveries')->value ?? 0) + 1);

    // Si estaba en estado 'failing', restaurar a 'active'.
    if ($subscription->getSubscriptionStatus() === WebhookSubscription::STATUS_FAILING) {
      $subscription->set('status', WebhookSubscription::STATUS_ACTIVE);
    }

    $subscription->save();
  }

  /**
   * Registra un fallo de entrega.
   */
  protected function recordFailure(WebhookSubscription $subscription, int $status_code): void {
    $failures = ($subscription->get('consecutive_failures')->value ?? 0) + 1;
    $subscription->set('consecutive_failures', $failures);
    $subscription->set('last_triggered', date('Y-m-d\TH:i:s'));
    $subscription->set('last_response_code', $status_code);

    // Desactivar tras N fallos consecutivos.
    $config = \Drupal::config('jaraba_integrations.settings');
    $max_failures = $config->get('webhook_disable_after_failures') ?? 10;

    if ($failures >= $max_failures) {
      $subscription->set('status', WebhookSubscription::STATUS_INACTIVE);
      $this->logger->error('Webhook @label desactivado tras @n fallos consecutivos', [
        '@label' => $subscription->getLabel(),
        '@n' => $failures,
      ]);
    }
    elseif ($failures >= 3) {
      $subscription->set('status', WebhookSubscription::STATUS_FAILING);
    }

    $subscription->save();
  }

  /**
   * Obtiene suscripciones activas para un evento.
   *
   * @param string $event
   *   Nombre del evento.
   * @param string|null $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_integrations\Entity\WebhookSubscription[]
   *   Suscripciones activas.
   */
  protected function getActiveSubscriptions(string $event, ?string $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('webhook_subscription');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', [
        WebhookSubscription::STATUS_ACTIVE,
        WebhookSubscription::STATUS_FAILING,
      ], 'IN');

    if ($tenant_id) {
      $query->condition('tenant_id', $tenant_id);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $subscriptions = $storage->loadMultiple($ids);

    // Filtrar por evento suscrito.
    return array_filter($subscriptions, fn(WebhookSubscription $s) => $s->isSubscribedTo($event));
  }

}
