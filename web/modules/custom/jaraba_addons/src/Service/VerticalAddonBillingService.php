<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquesta la activacion/desactivacion de verticales addon con facturacion.
 *
 * ESTRUCTURA:
 * Servicio que coordina entre AddonSubscriptionService (logica local de
 * suscripciones) y la integracion con Stripe (subscription items). Cuando
 * un tenant activa un vertical addon, este servicio:
 * 1. Crea la AddonSubscription local
 * 2. (Futuro) Añade un Stripe Subscription Item a la suscripcion existente
 * 3. Invalida la cache de TenantVerticalService.
 *
 * LOGICA:
 * La integracion con Stripe se hará via StripeConnectService cuando se
 * configure stripe_price_id en los Addon entities de tipo vertical.
 * De momento el servicio opera solo en modo local (creacion de
 * AddonSubscription + invalidacion de cache).
 *
 * RELACIONES:
 * - VerticalAddonBillingService -> AddonSubscriptionService (suscripcion local)
 * - VerticalAddonBillingService -> TenantVerticalService (invalidacion cache)
 * - VerticalAddonBillingService -> EntityTypeManager (validacion)
 * - VerticalAddonBillingService <- AddonApiController (consumido por)
 * - VerticalAddonBillingService <- AddonCatalogController (consumido por futuro)
 */
class VerticalAddonBillingService {

  public function __construct(
    protected AddonSubscriptionService $subscriptionService,
    protected TenantVerticalService $tenantVerticalService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Activa un vertical addon para un tenant.
   *
   * Crea la suscripcion local e invalida la cache de verticales.
   *
   * @param int $addonId
   *   ID del Addon entity (debe ser addon_type = 'vertical').
   * @param int $tenantId
   *   ID del tenant que activa el vertical.
   * @param string $billingCycle
   *   Ciclo de facturacion: 'monthly' o 'yearly'.
   *
   * @return array{subscription_id: int, vertical_ref: string, status: string}
   *   Datos de la activacion.
   *
   * @throws \RuntimeException
   *   Si el addon no es tipo vertical, no existe, o ya esta activo.
   */
  public function activateVerticalAddon(int $addonId, int $tenantId, string $billingCycle = 'monthly'): array {
    // Validar que el addon es tipo vertical.
    $addon = $this->entityTypeManager->getStorage('addon')->load($addonId);
    if (!$addon) {
      throw new \RuntimeException('El add-on solicitado no existe.');
    }

    $addonType = $addon->get('addon_type')->value ?? '';
    if ($addonType !== 'vertical') {
      throw new \RuntimeException(
        sprintf('El add-on "%s" no es de tipo vertical. Use subscribe() para add-ons regulares.', $addon->label())
      );
    }

    $verticalRef = $addon->get('vertical_ref')->value ?? '';
    if (empty($verticalRef)) {
      throw new \RuntimeException(
        sprintf('El add-on vertical "%s" no tiene un vertical_ref configurado.', $addon->label())
      );
    }

    // Verificar que el tenant no tiene ya este vertical activo.
    if ($this->tenantVerticalService->hasVertical($tenantId, $verticalRef)) {
      throw new \RuntimeException(
        sprintf('El tenant ya tiene el vertical "%s" activo.', $verticalRef)
      );
    }

    // Crear suscripcion local.
    $subscription = $this->subscriptionService->subscribe($addonId, $tenantId, $billingCycle);

    // Invalidar cache de verticales para que se resuelva inmediatamente.
    $this->tenantVerticalService->invalidateCache($tenantId);

    $this->logger->info('Vertical addon "@vertical" activado para tenant #@tenant (suscripcion #@sub)', [
      '@vertical' => $verticalRef,
      '@tenant' => $tenantId,
      '@sub' => $subscription->id(),
    ]);

    return [
      'subscription_id' => (int) $subscription->id(),
      'vertical_ref' => $verticalRef,
      'status' => 'active',
    ];
  }

  /**
   * Desactiva un vertical addon para un tenant.
   *
   * Cancela la suscripcion local e invalida la cache.
   *
   * @param int $subscriptionId
   *   ID de la AddonSubscription a cancelar.
   * @param int $tenantId
   *   ID del tenant (para verificacion de ownership).
   *
   * @return array{subscription_id: int, status: string}
   *   Datos de la cancelacion.
   *
   * @throws \RuntimeException
   *   Si la suscripcion no existe o no pertenece al tenant.
   */
  public function deactivateVerticalAddon(int $subscriptionId, int $tenantId): array {
    // Verificar ownership.
    $subscription = $this->entityTypeManager->getStorage('addon_subscription')->load($subscriptionId);
    if (!$subscription) {
      throw new \RuntimeException('La suscripcion no existe.');
    }

    $subTenantId = (int) ($subscription->get('tenant_id')->target_id ?? 0);
    if ($subTenantId !== $tenantId) {
      throw new \RuntimeException('La suscripcion no pertenece a este tenant.');
    }

    // Cancelar suscripcion.
    $this->subscriptionService->cancel($subscriptionId);

    // Invalidar cache.
    $this->tenantVerticalService->invalidateCache($tenantId);

    $this->logger->info('Vertical addon desactivado: suscripcion #@sub para tenant #@tenant', [
      '@sub' => $subscriptionId,
      '@tenant' => $tenantId,
    ]);

    return [
      'subscription_id' => $subscriptionId,
      'status' => 'cancelled',
    ];
  }

}
