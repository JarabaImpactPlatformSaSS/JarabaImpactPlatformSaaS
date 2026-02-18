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
     * AUDIT-SEC-N01: Verificación HMAC obligatoria antes de procesar.
     * Sigue el patrón de WebhookDispatcherService (X-Jaraba-Signature: sha256=<hex>).
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
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Empty payload']], 400);
        }

        // AUDIT-SEC-N01: Verificar firma HMAC antes de procesar el payload.
        $signatureVerification = $this->verifyHmacSignature($webhook_id, $body, $request);
        if ($signatureVerification !== TRUE) {
            return $signatureVerification;
        }

        $payload = json_decode($body, TRUE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid JSON']], 400);
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
     * Verifica la firma HMAC del payload del webhook.
     *
     * AUDIT-SEC-001: Implementación obligatoria de HMAC en todos los webhooks.
     *
     * Acepta dos formatos de firma:
     * - X-Jaraba-Signature: sha256=<hex> (formato propio de la plataforma)
     * - X-Webhook-Signature: <hex> (formato genérico)
     *
     * @param string $webhookId
     *   ID del webhook para cargar el secret.
     * @param string $body
     *   Payload raw del request.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP para extraer headers.
     *
     * @return true|\Symfony\Component\HttpFoundation\JsonResponse
     *   TRUE si la firma es válida, o JsonResponse con error.
     */
    protected function verifyHmacSignature(string $webhookId, string $body, Request $request): true|JsonResponse
    {
        // Cargar la suscripción de webhook para obtener el secret.
        $storage = $this->entityTypeManager()->getStorage('webhook_subscription');
        $subscriptions = $storage->loadByProperties(['webhook_id' => $webhookId]);

        if (empty($subscriptions)) {
            $this->logger->warning('Webhook rechazado: endpoint @id no encontrado', [
                '@id' => $webhookId,
            ]);
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Webhook endpoint not found']], 404);
        }

        $subscription = reset($subscriptions);
        $secret = $subscription->getSecret();

        if (empty($secret)) {
            $this->logger->error('Webhook rechazado: endpoint @id sin secret configurado', [
                '@id' => $webhookId,
            ]);
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Webhook secret not configured']], 500);
        }

        // Intentar extraer la firma de los headers soportados.
        $signature = $request->headers->get('X-Jaraba-Signature')
            ?? $request->headers->get('X-Webhook-Signature');

        if (empty($signature)) {
            $this->logger->warning('Webhook rechazado: firma ausente para endpoint @id (IP: @ip)', [
                '@id' => $webhookId,
                '@ip' => $request->getClientIp(),
            ]);
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Missing signature']], 403);
        }

        // Extraer el hash de la firma (soportar formato "sha256=<hex>" o "<hex>").
        $receivedHash = $signature;
        if (str_starts_with($signature, 'sha256=')) {
            $receivedHash = substr($signature, 7);
        }

        // Calcular la firma esperada.
        $expectedHash = hash_hmac('sha256', $body, $secret);

        // Comparación timing-safe para prevenir timing attacks.
        if (!hash_equals($expectedHash, $receivedHash)) {
            $this->logger->warning('Webhook rechazado: firma inválida para endpoint @id (IP: @ip)', [
                '@id' => $webhookId,
                '@ip' => $request->getClientIp(),
            ]);
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid signature']], 403);
        }

        return TRUE;
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
