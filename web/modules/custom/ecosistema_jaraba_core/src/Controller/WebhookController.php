<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para webhooks de integraciones externas.
 *
 * Este controlador gestiona los webhooks entrantes de servicios externos,
 * principalmente Stripe para eventos de suscripción y pagos. También
 * proporciona un endpoint genérico para webhooks personalizados por tenant.
 *
 * Seguridad:
 * - Los webhooks de Stripe se validan mediante firma criptográfica
 * - Los webhooks personalizados requieren token de autenticación
 * - Todos los eventos se registran en el log para auditoría
 *
 * Eventos de Stripe manejados:
 * - customer.subscription.created: Nueva suscripción
 * - customer.subscription.updated: Cambio de plan
 * - customer.subscription.deleted: Cancelación
 * - invoice.payment_succeeded: Pago exitoso
 * - invoice.payment_failed: Pago fallido
 *
 * @see https://stripe.com/docs/webhooks
 */
class WebhookController extends ControllerBase
{

    /**
     * El gestor de tenants.
     *
     * Se usa para actualizar estados de suscripción.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantManager
     */
    protected TenantManager $tenantManager;

    /**
     * Canal de log para webhooks.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->tenantManager = $container->get('ecosistema_jaraba_core.tenant_manager');
        $instance->logger = $container->get('logger.channel.ecosistema_jaraba_core');
        return $instance;
    }

    /**
     * Procesa webhooks entrantes de Stripe.
     *
     * Este endpoint recibe eventos de Stripe y los procesa según su tipo.
     * La firma del webhook se valida para garantizar su autenticidad.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con el evento de Stripe.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta 200 si se procesó correctamente, o código de error.
     */
    public function stripeWebhook(Request $request): Response
    {
        // Obtener el cuerpo de la petición
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        // Obtener el secreto del webhook desde configuración
        $webhookSecret = $this->config('ecosistema_jaraba_core.stripe')->get('webhook_secret');

        if (!$webhookSecret) {
            $this->logger->error('🚫 Webhook Stripe: Secreto no configurado');
            return new Response('Webhook secret not configured', 500);
        }

        // Validar la firma del webhook
        try {
            $event = $this->verifyStripeSignature($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            $this->logger->warning(
                '🚫 Webhook Stripe: Firma inválida - @error',
                ['@error' => $e->getMessage()]
            );
            return new Response('Invalid signature', 400);
        }

        // Log del evento recibido
        $this->logger->info(
            '📥 Webhook Stripe recibido: @type (ID: @id)',
            [
                '@type' => $event['type'],
                '@id' => $event['id'],
            ]
        );

        // Procesar según el tipo de evento
        try {
            $result = $this->handleStripeEvent($event);

            if ($result['success']) {
                return new Response('OK', 200);
            } else {
                $this->logger->error(
                    '🚫 Webhook Stripe: Error procesando @type - @error',
                    [
                        '@type' => $event['type'],
                        '@error' => $result['error'] ?? 'Unknown error',
                    ]
                );
                return new Response($result['error'] ?? 'Processing error', 500);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                '🚫 Webhook Stripe: Excepción en @type - @error',
                [
                    '@type' => $event['type'],
                    '@error' => $e->getMessage(),
                ]
            );
            return new Response('Internal error', 500);
        }
    }

    /**
     * Verifica la firma criptográfica del webhook de Stripe.
     *
     * @param string $payload
     *   El cuerpo de la petición en crudo.
     * @param string $sigHeader
     *   El header Stripe-Signature.
     * @param string $secret
     *   El secreto del webhook.
     *
     * @return array
     *   El evento decodificado como array.
     *
     * @throws \Exception
     *   Si la firma no es válida.
     */
    protected function verifyStripeSignature(string $payload, string $sigHeader, string $secret): array
    {
        // Parsear el header de firma
        $elements = explode(',', $sigHeader);
        $timestamp = NULL;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;

            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (!$timestamp) {
            throw new \Exception('No timestamp in signature');
        }

        if (empty($signatures)) {
            throw new \Exception('No valid signature found');
        }

        // Verificar que el timestamp no es muy antiguo (5 minutos)
        $tolerance = 300;
        if (abs(time() - $timestamp) > $tolerance) {
            throw new \Exception('Timestamp outside tolerance');
        }

        // Calcular la firma esperada
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Verificar que alguna de las firmas coincide
        $valid = FALSE;
        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                $valid = TRUE;
                break;
            }
        }

        if (!$valid) {
            throw new \Exception('Signature verification failed');
        }

        // Decodificar el evento
        $event = json_decode($payload, TRUE);

        if (!$event) {
            throw new \Exception('Invalid JSON payload');
        }

        return $event;
    }

    /**
     * Procesa un evento de Stripe según su tipo.
     *
     * @param array $event
     *   El evento de Stripe decodificado.
     *
     * @return array
     *   Array con 'success' boolean y opcionalmente 'error'.
     */
    protected function handleStripeEvent(array $event): array
    {
        $type = $event['type'];
        $data = $event['data']['object'];

        switch ($type) {
            // Nueva suscripción creada
            case 'customer.subscription.created':
                return $this->handleSubscriptionCreated($data);

            // Suscripción actualizada (cambio de plan, etc.)
            case 'customer.subscription.updated':
                return $this->handleSubscriptionUpdated($data);

            // Suscripción cancelada o eliminada
            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($data);

            // Pago de factura exitoso
            case 'invoice.payment_succeeded':
                return $this->handlePaymentSucceeded($data);

            // Pago de factura fallido
            case 'invoice.payment_failed':
                return $this->handlePaymentFailed($data);

            // Periodo de prueba a punto de terminar
            case 'customer.subscription.trial_will_end':
                return $this->handleTrialWillEnd($data);

            // Eventos no manejados - simplemente aceptar
            default:
                $this->logger->info(
                    '📝 Webhook Stripe: Evento ignorado @type',
                    ['@type' => $type]
                );
                return ['success' => TRUE];
        }
    }

    /**
     * Maneja el evento de nueva suscripción creada.
     *
     * @param array $subscription
     *   Datos de la suscripción de Stripe.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handleSubscriptionCreated(array $subscription): array
    {
        $customerId = $subscription['customer'];
        $subscriptionId = $subscription['id'];
        $status = $subscription['status'];

        // Buscar el tenant por customer ID de Stripe
        $tenant = $this->tenantManager->findTenantByStripeCustomer($customerId);

        if (!$tenant) {
            $this->logger->warning(
                '⚠️ Webhook: No se encontró tenant para customer @customer',
                ['@customer' => $customerId]
            );
            return ['success' => TRUE];  // Aceptamos pero logueamos
        }

        // Actualizar el tenant con el ID de suscripción
        $tenant->set('stripe_subscription_id', $subscriptionId);

        // Actualizar estado según el estado de Stripe
        $newStatus = $this->mapStripeStatusToTenantStatus($status);
        if ($newStatus) {
            $this->tenantManager->updateSubscriptionStatus($tenant, $newStatus);
        }

        $this->logger->info(
            '✅ Webhook: Suscripción creada para tenant @tenant',
            ['@tenant' => $tenant->getName()]
        );

        return ['success' => TRUE];
    }

    /**
     * Maneja el evento de suscripción actualizada.
     *
     * @param array $subscription
     *   Datos actualizados de la suscripción.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handleSubscriptionUpdated(array $subscription): array
    {
        $subscriptionId = $subscription['id'];
        $status = $subscription['status'];

        // Buscar tenant por subscription ID
        $tenant = $this->tenantManager->findTenantByStripeSubscription($subscriptionId);

        if (!$tenant) {
            return ['success' => TRUE];
        }

        // Actualizar estado
        $newStatus = $this->mapStripeStatusToTenantStatus($status);
        if ($newStatus && $newStatus !== $tenant->getSubscriptionStatus()) {
            $this->tenantManager->updateSubscriptionStatus($tenant, $newStatus);

            $this->logger->info(
                '📝 Webhook: Suscripción actualizada para tenant @tenant - nuevo estado: @status',
                [
                    '@tenant' => $tenant->getName(),
                    '@status' => $newStatus,
                ]
            );
        }

        // Verificar si cambió el plan
        if (isset($subscription['items']['data'][0]['price']['id'])) {
            $newPriceId = $subscription['items']['data'][0]['price']['id'];
            $this->checkAndUpdatePlan($tenant, $newPriceId);
        }

        return ['success' => TRUE];
    }

    /**
     * Maneja el evento de suscripción eliminada/cancelada.
     *
     * @param array $subscription
     *   Datos de la suscripción cancelada.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handleSubscriptionDeleted(array $subscription): array
    {
        $subscriptionId = $subscription['id'];

        $tenant = $this->tenantManager->findTenantByStripeSubscription($subscriptionId);

        if (!$tenant) {
            return ['success' => TRUE];
        }

        // Marcar suscripción como cancelada
        $this->tenantManager->cancelSubscription($tenant, 'cancelled_by_stripe');

        $this->logger->info(
            '❌ Webhook: Suscripción cancelada para tenant @tenant',
            ['@tenant' => $tenant->getName()]
        );

        // Enviar notificación al administrador del tenant
        // TODO: Implementar notificaciones por email

        return ['success' => TRUE];
    }

    /**
     * Maneja el evento de pago exitoso.
     *
     * @param array $invoice
     *   Datos de la factura pagada.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handlePaymentSucceeded(array $invoice): array
    {
        $customerId = $invoice['customer'];
        $subscriptionId = $invoice['subscription'] ?? NULL;

        if (!$subscriptionId) {
            return ['success' => TRUE];  // Factura sin suscripción (one-time)
        }

        $tenant = $this->tenantManager->findTenantByStripeSubscription($subscriptionId);

        if (!$tenant) {
            return ['success' => TRUE];
        }

        // Si estaba en past_due, reactivar
        if ($tenant->getSubscriptionStatus() === 'past_due') {
            $this->tenantManager->updateSubscriptionStatus($tenant, 'active');

            $this->logger->info(
                '✅ Webhook: Pago recibido, tenant @tenant reactivado',
                ['@tenant' => $tenant->getName()]
            );
        }

        // Registrar el pago para auditoría
        $this->logger->info(
            '💰 Webhook: Pago de @amount recibido para tenant @tenant',
            [
                '@amount' => $invoice['amount_paid'] / 100 . ' ' . strtoupper($invoice['currency']),
                '@tenant' => $tenant->getName(),
            ]
        );

        return ['success' => TRUE];
    }

    /**
     * Maneja el evento de pago fallido.
     *
     * @param array $invoice
     *   Datos de la factura con pago fallido.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handlePaymentFailed(array $invoice): array
    {
        $subscriptionId = $invoice['subscription'] ?? NULL;

        if (!$subscriptionId) {
            return ['success' => TRUE];
        }

        $tenant = $this->tenantManager->findTenantByStripeSubscription($subscriptionId);

        if (!$tenant) {
            return ['success' => TRUE];
        }

        // Marcar como past_due
        $this->tenantManager->updateSubscriptionStatus($tenant, 'past_due');

        $this->logger->warning(
            '⚠️ Webhook: Pago fallido para tenant @tenant',
            ['@tenant' => $tenant->getName()]
        );

        // TODO: Enviar notificación al administrador del tenant

        return ['success' => TRUE];
    }

    /**
     * Maneja el evento de trial próximo a terminar.
     *
     * @param array $subscription
     *   Datos de la suscripción.
     *
     * @return array
     *   Resultado del procesamiento.
     */
    protected function handleTrialWillEnd(array $subscription): array
    {
        $subscriptionId = $subscription['id'];
        $trialEnd = $subscription['trial_end'];

        $tenant = $this->tenantManager->findTenantByStripeSubscription($subscriptionId);

        if (!$tenant) {
            return ['success' => TRUE];
        }

        $this->logger->info(
            '⏰ Webhook: Trial termina pronto para tenant @tenant',
            ['@tenant' => $tenant->getName()]
        );

        // TODO: Enviar notificación de recordatorio

        return ['success' => TRUE];
    }

    /**
     * Mapea estados de Stripe a estados de tenant.
     *
     * @param string $stripeStatus
     *   Estado de suscripción en Stripe.
     *
     * @return string|null
     *   Estado correspondiente del tenant, o NULL si no aplica.
     */
    protected function mapStripeStatusToTenantStatus(string $stripeStatus): ?string
    {
        $mapping = [
            'trialing' => 'trial',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'cancelled',
            'unpaid' => 'suspended',
            'incomplete' => 'pending',
            'incomplete_expired' => 'cancelled',
        ];

        return $mapping[$stripeStatus] ?? NULL;
    }

    /**
     * Verifica y actualiza el plan si cambió.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a verificar.
     * @param string $newPriceId
     *   ID del nuevo precio en Stripe.
     */
    protected function checkAndUpdatePlan($tenant, string $newPriceId): void
    {
        // Buscar el plan que corresponde a este price ID
        $plans = $this->entityTypeManager()
            ->getStorage('saas_plan')
            ->loadByProperties(['stripe_price_id' => $newPriceId]);

        if (!empty($plans)) {
            $newPlan = reset($plans);
            $currentPlan = $tenant->getSubscriptionPlan();

            if ($currentPlan && $currentPlan->id() !== $newPlan->id()) {
                try {
                    $this->tenantManager->changePlan($tenant, $newPlan);

                    $this->logger->info(
                        '📝 Webhook: Plan cambiado para tenant @tenant: @old → @new',
                        [
                            '@tenant' => $tenant->getName(),
                            '@old' => $currentPlan->getName(),
                            '@new' => $newPlan->getName(),
                        ]
                    );
                }
                catch (\InvalidArgumentException $e) {
                    $this->logger->error(
                        'Webhook: Error cambiando plan para tenant @tenant: @error',
                        [
                            '@tenant' => $tenant->getName(),
                            '@error' => $e->getMessage(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Procesa webhooks personalizados de integraciones de tenants.
     *
     * Este endpoint permite a los tenants configurar webhooks entrantes
     * de sus propias integraciones (Zapier, n8n, etc.).
     *
     * @param string $integration_id
     *   ID de la integración configurada.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con el resultado.
     */
    public function customWebhook(string $integration_id, Request $request): JsonResponse
    {
        // SEC-02: Validar token de autenticación via header HMAC o token.
        // El token via query param se mantiene solo por compatibilidad.
        $token = $request->headers->get('X-Webhook-Token');
        $hmacSignature = $request->headers->get('X-Webhook-Signature');

        if (!$token && !$hmacSignature) {
            $this->logger->warning(
                'Webhook @id rechazado: sin token ni firma HMAC',
                ['@id' => $integration_id]
            );
            return new JsonResponse(['error' => 'Missing authentication'], 401);
        }

        // SEC-02: Verificar firma HMAC si está presente (preferido sobre token simple).
        if ($hmacSignature) {
            $webhookSecret = getenv('WEBHOOK_SECRET_' . strtoupper($integration_id))
                ?: $this->config('ecosistema_jaraba_core.webhooks')->get("integrations.{$integration_id}.secret");

            if (!$webhookSecret) {
                $this->logger->error(
                    'Webhook @id: secreto HMAC no configurado',
                    ['@id' => $integration_id]
                );
                return new JsonResponse(['error' => 'Webhook not configured'], 500);
            }

            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            if (!hash_equals($expectedSignature, $hmacSignature)) {
                $this->logger->warning(
                    'Webhook @id rechazado: firma HMAC inválida',
                    ['@id' => $integration_id]
                );
                return new JsonResponse(['error' => 'Invalid signature'], 403);
            }
        }

        // Validar token simple como fallback (deprecated, migrar a HMAC).
        if ($token && !$hmacSignature) {
            $expectedToken = getenv('WEBHOOK_TOKEN_' . strtoupper($integration_id))
                ?: $this->config('ecosistema_jaraba_core.webhooks')->get("integrations.{$integration_id}.token");

            if (!$expectedToken || !hash_equals($expectedToken, $token)) {
                $this->logger->warning(
                    'Webhook @id rechazado: token inválido',
                    ['@id' => $integration_id]
                );
                return new JsonResponse(['error' => 'Invalid token'], 403);
            }
        }

        $this->logger->info(
            'Webhook personalizado recibido: @id',
            ['@id' => $integration_id]
        );

        // TODO: Implementar entidad WebhookIntegration para procesamiento específico.
        $payload = json_decode($request->getContent(), TRUE);

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Webhook received',
            'integration_id' => $integration_id,
        ]);
    }

}
