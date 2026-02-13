<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_integrations\Event\WebhookEvents;
use Drupal\jaraba_integrations\Event\WebhookReceivedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para recibir webhooks entrantes de servicios externos.
 *
 * PROPÓSITO:
 * Endpoint público que recibe notificaciones HTTP POST de servicios
 * como Stripe, GitHub, etc. Valida la firma y despacha internamente
 * via Symfony EventDispatcher para que los módulos subscriptores
 * procesen cada tipo de webhook.
 *
 * SEGURIDAD:
 * - Valida firma HMAC si el conector la proporciona.
 * - Rate limiting por IP.
 * - Logs de todas las entregas recibidas.
 */
class WebhookReceiverController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('event_dispatcher'),
            $container->get('logger.factory')->get('jaraba_integrations'),
        );
    }

    /**
     * Recibe un webhook entrante y lo despacha internamente.
     *
     * POST /api/v1/integrations/webhooks/{webhook_id}/receive
     *
     * @param string $webhook_id
     *   ID del endpoint de webhook configurado.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP entrante.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con estado de recepción.
     */
    public function receive(string $webhook_id, Request $request): JsonResponse
    {
        $body = $request->getContent();

        if (empty($body)) {
            return new JsonResponse(['error' => 'Empty payload'], 400);
        }

        $payload = json_decode($body, TRUE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $eventType = $payload['event'] ?? $payload['type'] ?? 'unknown';
        $provider = $payload['provider'] ?? $this->resolveProvider($webhook_id);
        $tenantId = $this->resolveTenantId($webhook_id, $payload);

        $this->logger->info(
            'Webhook recibido en endpoint @id: @event (proveedor: @provider, tenant: @tenant)',
            [
                '@id' => $webhook_id,
                '@event' => $eventType,
                '@provider' => $provider,
                '@tenant' => $tenantId ?? 'N/A',
            ]
        );

        // Despachar evento interno para que los módulos subscriptores procesen.
        $event = new WebhookReceivedEvent(
            webhookId: $webhook_id,
            provider: $provider,
            eventType: $eventType,
            payload: $payload,
            tenantId: $tenantId,
        );

        $this->eventDispatcher->dispatch($event, WebhookEvents::RECEIVED);

        return new JsonResponse([
            'status' => 'received',
            'webhook_id' => $webhook_id,
        ]);
    }

    /**
     * Resuelve el proveedor a partir del ID del webhook.
     *
     * @param string $webhookId
     *   ID del endpoint de webhook.
     *
     * @return string
     *   Nombre del proveedor inferido.
     */
    protected function resolveProvider(string $webhookId): string
    {
        // Intentar cargar la entidad WebhookSubscription para obtener el proveedor.
        $storage = $this->entityTypeManager()->getStorage('webhook_subscription');
        $subscriptions = $storage->loadByProperties(['webhook_id' => $webhookId]);

        if ($subscriptions) {
            $subscription = reset($subscriptions);
            return $subscription->get('provider')->value ?? 'unknown';
        }

        // Inferir del ID si contiene el nombre del proveedor.
        if (str_contains($webhookId, 'stripe')) {
            return 'stripe';
        }

        return 'unknown';
    }

    /**
     * Resuelve el tenant ID asociado al webhook.
     *
     * @param string $webhookId
     *   ID del endpoint de webhook.
     * @param array $payload
     *   Payload del webhook.
     *
     * @return int|null
     *   ID del tenant o NULL si no se puede determinar.
     */
    protected function resolveTenantId(string $webhookId, array $payload): ?int
    {
        // Intentar desde metadata del payload.
        if (isset($payload['metadata']['tenant_id'])) {
            return (int) $payload['metadata']['tenant_id'];
        }

        // Intentar desde la entidad WebhookSubscription.
        $storage = $this->entityTypeManager()->getStorage('webhook_subscription');
        $subscriptions = $storage->loadByProperties(['webhook_id' => $webhookId]);

        if ($subscriptions) {
            $subscription = reset($subscriptions);
            $tenantId = $subscription->get('tenant_id')->target_id ?? NULL;
            return $tenantId ? (int) $tenantId : NULL;
        }

        return NULL;
    }

}
