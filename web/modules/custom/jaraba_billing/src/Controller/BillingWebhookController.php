<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\jaraba_billing\Service\DunningService;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoint dedicado para webhooks de billing desde Stripe.
 *
 * Separado del StripeWebhookController de FOC para manejar
 * eventos específicos de billing y suscripciones.
 *
 * EVENTOS MANEJADOS:
 * - invoice.paid: Factura pagada → sincronizar BillingInvoice
 * - invoice.payment_failed: Fallo de pago → marcar tenant past_due
 * - invoice.finalized: Factura finalizada → sincronizar
 * - customer.subscription.updated: Cambio de suscripción
 * - customer.subscription.deleted: Cancelación de suscripción
 * - customer.subscription.trial_will_end: Aviso fin de trial
 * - payment_method.attached: Nuevo método de pago
 * - payment_method.detached: Método de pago eliminado
 *
 * SEGURIDAD: HMAC-SHA256 via StripeConnectService::verifyWebhookSignature().
 *
 * AUDIT-PERF-002: Usa LockBackendInterface para prevenir lost-updates
 * cuando webhooks concurrentes modifican el estado del mismo tenant.
 */
class BillingWebhookController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected StripeConnectService $stripeConnect,
    protected StripeInvoiceService $invoiceService,
    protected TenantSubscriptionService $tenantSubscription,
    protected LoggerInterface $billingLogger,
    protected LockBackendInterface $lock,
    protected ?DunningService $dunningService = NULL,
    protected ?MailManagerInterface $mailManager = NULL,
    protected ?PlanResolverService $planResolver = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_foc.stripe_connect'),
      $container->get('jaraba_billing.stripe_invoice'),
      $container->get('jaraba_billing.tenant_subscription'),
      $container->get('logger.channel.jaraba_billing'),
      $container->get('lock'),
      $container->get('jaraba_billing.dunning'),
      $container->get('plugin.manager.mail'),
      $container->has('ecosistema_jaraba_core.plan_resolver')
        ? $container->get('ecosistema_jaraba_core.plan_resolver')
        : NULL,
    );
  }

  /**
   * Procesa webhooks entrantes de Stripe para billing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP entrante.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta HTTP.
   */
  public function handle(Request $request): Response {
    $payload = $request->getContent();
    $sigHeader = $request->headers->get('Stripe-Signature', '');

    // Verificación de firma HMAC-SHA256.
    if (!$this->stripeConnect->verifyWebhookSignature($payload, $sigHeader)) {
      $this->billingLogger->warning('Billing webhook rechazado: firma inválida.');
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid signature']], 400);
    }

    $event = json_decode($payload, TRUE);
    if (!$event || !isset($event['type'])) {
      $this->billingLogger->warning('Billing webhook rechazado: payload inválido.');
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid payload']], 400);
    }

    $eventType = $event['type'];
    $eventData = $event['data']['object'] ?? [];

    $this->billingLogger->info('Billing webhook recibido: @type', ['@type' => $eventType]);

    try {
      return match ($eventType) {
        'invoice.paid' => $this->handleInvoicePaid($eventData),
        'invoice.payment_failed' => $this->handleInvoicePaymentFailed($eventData),
        'invoice.finalized' => $this->handleInvoiceFinalized($eventData),
        'customer.subscription.updated' => $this->handleSubscriptionUpdated($eventData),
        'customer.subscription.deleted' => $this->handleSubscriptionDeleted($eventData),
        'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($eventData),
        'payment_method.attached' => $this->handlePaymentMethodAttached($eventData),
        'payment_method.detached' => $this->handlePaymentMethodDetached($eventData),
        default => $this->handleUnknownEvent($eventType),
      };
    }
    catch (\Exception $e) {
      $this->billingLogger->error('Error procesando billing webhook @type: @error', [
        '@type' => $eventType,
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Processing failed']], 500);
    }
  }

  /**
   * Factura pagada: sincronizar BillingInvoice local.
   */
  protected function handleInvoicePaid(array $data): JsonResponse {
    $this->invoiceService->syncInvoice($data);

    $this->billingLogger->info('Invoice pagada sincronizada: @id', [
      '@id' => $data['id'] ?? 'unknown',
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Fallo de pago: marcar tenant como past_due.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates.
   */
  protected function handleInvoicePaymentFailed(array $data): JsonResponse {
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in payment_failed webhook', ['@id' => $tenantId]);
        }
        elseif ($tenant) {
          $this->tenantSubscription->markPastDue($tenant);
        }
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error marcando tenant @id past_due: @error', [
          '@id' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    // Sincronizar la factura con estado fallido.
    $this->invoiceService->syncInvoice($data, $tenantId ?: NULL);

    $this->billingLogger->warning('Pago fallido en invoice @id, tenant @tenant', [
      '@id' => $data['id'] ?? 'unknown',
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Factura finalizada: sincronizar.
   */
  protected function handleInvoiceFinalized(array $data): JsonResponse {
    $this->invoiceService->syncInvoice($data);

    $this->billingLogger->info('Invoice finalizada sincronizada: @id', [
      '@id' => $data['id'] ?? 'unknown',
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Suscripción actualizada: sincroniza estado del tenant local.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates cuando
   * múltiples webhooks de Stripe modifican el estado del mismo tenant.
   */
  protected function handleSubscriptionUpdated(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $status = $data['status'] ?? 'unknown';
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in subscription_updated webhook', ['@id' => $tenantId]);
          $tenant = NULL;
        }
        if ($tenant) {
          switch ($status) {
            case 'active':
              $this->tenantSubscription->activateSubscription($tenant);
              // Stop dunning if payment recovered.
              if ($this->dunningService && $this->dunningService->isInDunning($tenantId)) {
                $this->dunningService->stopDunning($tenantId);
              }
              break;

            case 'past_due':
              $this->tenantSubscription->markPastDue($tenant);
              // Start dunning process.
              if ($this->dunningService) {
                $this->dunningService->startDunning($tenantId);
              }
              break;

            case 'canceled':
              $this->tenantSubscription->cancelSubscription($tenant, TRUE);
              break;

            case 'trialing':
              // No change for trialing status.
              break;
          }

          // If plan changed (items differ), update subscription_plan.
          // Use PlanResolver to normalize tier from Stripe Price ID when available.
          $priceId = $data['items']['data'][0]['price']['id'] ?? NULL;
          $planId = $data['items']['data'][0]['price']['product'] ?? NULL;
          if ($planId) {
            $resolvedTier = NULL;
            if ($this->planResolver && $priceId) {
              $resolvedTier = $this->planResolver->resolveFromStripePriceId($priceId);
            }
            $tenant->set('subscription_plan', $resolvedTier ?? $planId);
            $tenant->save();
          }
        }
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error sincronizando suscripción @id para tenant @tenant: @error', [
          '@id' => $subscriptionId,
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    $this->billingLogger->info('Suscripción actualizada: @id, estado: @status, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@status' => $status,
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Suscripción eliminada/cancelada.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates.
   */
  protected function handleSubscriptionDeleted(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in subscription_deleted webhook', ['@id' => $tenantId]);
        }
        elseif ($tenant) {
          $this->tenantSubscription->cancelSubscription($tenant, TRUE);
        }
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error cancelando tenant @id: @error', [
          '@id' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    $this->billingLogger->info('Suscripción eliminada: @id, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Trial a punto de terminar: notifica al tenant.
   */
  protected function handleTrialWillEnd(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $trialEnd = $data['trial_end'] ?? NULL;
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in trial_will_end webhook', ['@id' => $tenantId]);
          $tenant = NULL;
        }

        if ($tenant && $this->mailManager) {
          // Look up billing email for the tenant.
          $customerStorage = $this->entityTypeManager()->getStorage('billing_customer');
          $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);
          $customer = !empty($customers) ? reset($customers) : NULL;

          $to = $customer ? $customer->get('billing_email')->value : NULL;
          if ($to) {
            $params = [
              'tenant_label' => $tenant->label(),
              'trial_end' => $trialEnd ? date('d/m/Y', (int) $trialEnd) : 'pronto',
              'subscription_id' => $subscriptionId,
            ];
            $this->mailManager->mail('jaraba_billing', 'trial_will_end', $to, 'es', $params);
          }
        }
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error notificando fin de trial para tenant @tenant: @error', [
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Trial terminará pronto: suscripción @id, fin: @end, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@end' => $trialEnd ? date('Y-m-d', (int) $trialEnd) : 'unknown',
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Método de pago vinculado.
   */
  protected function handlePaymentMethodAttached(array $data): JsonResponse {
    $pmId = $data['id'] ?? '';
    $customerId = $data['customer'] ?? '';

    if ($pmId && $customerId) {
      try {
        $storage = $this->entityTypeManager()->getStorage('billing_payment_method');

        // Buscar tenant_id a partir del customer.
        $tenantId = NULL;
        $existingByCustomer = $storage->loadByProperties([
          'stripe_customer_id' => $customerId,
        ]);
        if (!empty($existingByCustomer)) {
          $sample = reset($existingByCustomer);
          $tenantId = $sample->get('tenant_id')->target_id;
        }

        $values = [
          'tenant_id' => $tenantId,
          'stripe_payment_method_id' => $pmId,
          'stripe_customer_id' => $customerId,
          'type' => $data['type'] ?? 'card',
          'card_brand' => $data['card']['brand'] ?? NULL,
          'card_last4' => $data['card']['last4'] ?? NULL,
          'card_exp_month' => $data['card']['exp_month'] ?? NULL,
          'card_exp_year' => $data['card']['exp_year'] ?? NULL,
          'status' => 'active',
        ];

        $entity = $storage->create($values);
        $entity->save();
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error creando payment method @pm: @error', [
          '@pm' => $pmId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Payment method vinculado: @pm', ['@pm' => $pmId]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Método de pago desvinculado.
   */
  protected function handlePaymentMethodDetached(array $data): JsonResponse {
    $pmId = $data['id'] ?? '';

    if ($pmId) {
      try {
        $storage = $this->entityTypeManager()->getStorage('billing_payment_method');
        $existing = $storage->loadByProperties([
          'stripe_payment_method_id' => $pmId,
        ]);
        if (!empty($existing)) {
          $entity = reset($existing);
          $entity->set('status', 'detached');
          $entity->save();
        }
      }
      catch (\Exception $e) {
        $this->billingLogger->error('Error marcando payment method @pm como detached: @error', [
          '@pm' => $pmId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Payment method desvinculado: @pm', ['@pm' => $pmId]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Evento no reconocido.
   */
  protected function handleUnknownEvent(string $eventType): JsonResponse {
    $this->billingLogger->debug('Billing webhook ignorado: @type', ['@type' => $eventType]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'ignored'], 'meta' => ['timestamp' => time()]]);
  }

}
