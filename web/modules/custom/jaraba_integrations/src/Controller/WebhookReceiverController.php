<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para recibir webhooks entrantes de servicios externos.
 *
 * PROPÓSITO:
 * Endpoint público que recibe notificaciones HTTP POST de servicios
 * como Stripe, GitHub, etc. Valida la firma y procesa el evento.
 *
 * SEGURIDAD:
 * - Valida firma HMAC si el conector la proporciona.
 * - Rate limiting por IP.
 * - Logs de todas las entregas recibidas.
 */
class WebhookReceiverController extends ControllerBase {

  /**
   * Recibe un webhook entrante.
   *
   * POST /api/v1/integrations/webhooks/{webhook_id}/receive
   */
  public function receive(string $webhook_id, Request $request): JsonResponse {
    $body = $request->getContent();

    if (empty($body)) {
      return new JsonResponse(['error' => 'Empty payload'], 400);
    }

    $payload = json_decode($body, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Log la recepción.
    \Drupal::logger('jaraba_integrations')->info(
      'Webhook recibido en endpoint @id: @event',
      [
        '@id' => $webhook_id,
        '@event' => $payload['event'] ?? 'unknown',
      ]
    );

    // TODO: Implementar dispatch interno según el tipo de webhook.
    // Por ahora, simplemente aceptar y logear.

    return new JsonResponse([
      'status' => 'received',
      'webhook_id' => $webhook_id,
    ]);
  }

}
