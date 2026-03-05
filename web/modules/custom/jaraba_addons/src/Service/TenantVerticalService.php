<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resuelve todos los verticales activos de un tenant.
 *
 * ESTRUCTURA:
 * Servicio central que determina los verticales disponibles para un tenant,
 * combinando el vertical primario (campo Tenant.vertical) con los verticales
 * secundarios contratados como add-ons (AddonSubscription activas de tipo
 * 'vertical'). Esto implementa el modelo "Verticales Componibles" donde
 * 1 tenant = 1 vertical primario + N verticales addon.
 *
 * LOGICA:
 * 1. Obtiene el vertical primario desde Tenant.vertical entity_reference
 * 2. Busca AddonSubscriptions activas del tenant donde el Addon tiene
 *    addon_type = 'vertical' y vertical_ref no vacio
 * 3. Devuelve array unificado con flag is_primary para cada vertical
 * 4. Cache por request (misma instancia de servicio)
 *
 * RELACIONES:
 * - TenantVerticalService -> EntityTypeManager (dependencia)
 * - TenantVerticalService -> Addon entity (consulta vertical addons)
 * - TenantVerticalService -> AddonSubscription entity (consulta suscripciones)
 * - TenantVerticalService -> Tenant entity (consulta vertical primario)
 * - TenantVerticalService <- TenantSelfServiceController (consumido por)
 * - TenantVerticalService <- FeatureAccessService (consumido por)
 */
class TenantVerticalService {

  /**
   * Cache de verticales por tenant para evitar queries repetidas.
   *
   * @var array<int, array<string, array{machine_name: string, label: string, is_primary: bool}>>
   */
  protected array $cache = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todos los verticales activos de un tenant.
   *
   * Combina el vertical primario (Tenant.vertical) con verticales
   * contratados como add-ons (AddonSubscription activas tipo 'vertical').
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array<string, array{machine_name: string, label: string, is_primary: bool}>
   *   Array indexado por machine_name con datos de cada vertical activo.
   */
  public function getActiveVerticals(int $tenantId): array {
    if (isset($this->cache[$tenantId])) {
      return $this->cache[$tenantId];
    }

    $verticals = [];

    // 1. Vertical primario desde Tenant.vertical.
    $verticals = $this->resolvePrimaryVertical($tenantId, $verticals);

    // 2. Verticales addon desde suscripciones activas.
    $verticals = $this->resolveAddonVerticals($tenantId, $verticals);

    $this->cache[$tenantId] = $verticals;

    return $verticals;
  }

  /**
   * Comprueba si un tenant tiene acceso a un vertical especifico.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $verticalKey
   *   Machine name del vertical (ej: 'agroconecta', 'formacion').
   *
   * @return bool
   *   TRUE si el tenant tiene el vertical activo (primario o addon).
   */
  public function hasVertical(int $tenantId, string $verticalKey): bool {
    $verticals = $this->getActiveVerticals($tenantId);
    return isset($verticals[$verticalKey]);
  }

  /**
   * Obtiene solo el vertical primario de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return string|null
   *   Machine name del vertical primario o NULL.
   */
  public function getPrimaryVertical(int $tenantId): ?string {
    $verticals = $this->getActiveVerticals($tenantId);

    foreach ($verticals as $key => $data) {
      if ($data['is_primary']) {
        return $key;
      }
    }

    return NULL;
  }

  /**
   * Obtiene solo los verticales addon (no primario) de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array<string, array{machine_name: string, label: string, is_primary: bool}>
   *   Verticales addon activos.
   */
  public function getAddonVerticals(int $tenantId): array {
    $verticals = $this->getActiveVerticals($tenantId);

    return array_filter($verticals, fn(array $v) => !$v['is_primary']);
  }

  /**
   * Obtiene verticales disponibles para contratar (no activos aun).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array<string, array{machine_name: string, label: string, addon_id: int, price_monthly: float, price_yearly: float}>
   *   Verticales disponibles para suscripcion.
   */
  public function getAvailableVerticals(int $tenantId): array {
    $activeKeys = array_keys($this->getActiveVerticals($tenantId));
    $available = [];

    try {
      $addonStorage = $this->entityTypeManager->getStorage('addon');
      $ids = $addonStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('addon_type', 'vertical')
        ->condition('is_active', TRUE)
        ->sort('label')
        ->execute();

      if (!empty($ids)) {
        $addons = $addonStorage->loadMultiple($ids);
        foreach ($addons as $addon) {
          $verticalRef = $addon->get('vertical_ref')->value ?? '';
          if ($verticalRef && !in_array($verticalRef, $activeKeys)) {
            $available[$verticalRef] = [
              'machine_name' => $verticalRef,
              'label' => $addon->label(),
              'addon_id' => (int) $addon->id(),
              'price_monthly' => (float) ($addon->get('price_monthly')->value ?? 0),
              'price_yearly' => (float) ($addon->get('price_yearly')->value ?? 0),
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error loading available verticals for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $available;
  }

  /**
   * Resuelve el vertical primario del tenant.
   */
  protected function resolvePrimaryVertical(int $tenantId, array $verticals): array {
    try {
      $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
      if (!$tenant) {
        return $verticals;
      }

      if ($tenant->hasField('vertical') && !$tenant->get('vertical')->isEmpty()) {
        $vertical = $tenant->get('vertical')->entity;
        if ($vertical && method_exists($vertical, 'getMachineName')) {
          $machineName = $vertical->getMachineName();
          if ($machineName) {
            $verticals[$machineName] = [
              'machine_name' => $machineName,
              'label' => $vertical->label() ?? $machineName,
              'is_primary' => TRUE,
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error resolving primary vertical for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $verticals;
  }

  /**
   * Resuelve los verticales contratados como add-ons.
   */
  protected function resolveAddonVerticals(int $tenantId, array $verticals): array {
    try {
      $subStorage = $this->entityTypeManager->getStorage('addon_subscription');
      $subIds = $subStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', ['active', 'trial'], 'IN')
        ->execute();

      if (empty($subIds)) {
        return $verticals;
      }

      $subscriptions = $subStorage->loadMultiple($subIds);
      foreach ($subscriptions as $subscription) {
        $addon = $subscription->get('addon_id')->entity;
        if (!$addon) {
          continue;
        }

        $addonType = $addon->get('addon_type')->value ?? '';
        if ($addonType !== 'vertical') {
          continue;
        }

        $verticalRef = $addon->get('vertical_ref')->value ?? '';
        if ($verticalRef && !isset($verticals[$verticalRef])) {
          $verticals[$verticalRef] = [
            'machine_name' => $verticalRef,
            'label' => $addon->label(),
            'is_primary' => FALSE,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error resolving addon verticals for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $verticals;
  }

  /**
   * Invalida la cache interna para un tenant.
   *
   * Llamar cuando cambie la suscripcion o el vertical primario.
   *
   * @param int $tenantId
   *   ID del tenant.
   */
  public function invalidateCache(int $tenantId): void {
    unset($this->cache[$tenantId]);
  }

}
