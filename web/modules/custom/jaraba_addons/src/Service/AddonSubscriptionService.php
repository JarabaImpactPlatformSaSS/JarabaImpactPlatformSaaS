<?php

namespace Drupal\jaraba_addons\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_addons\Entity\AddonSubscription;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de suscripciones a add-ons.
 *
 * ESTRUCTURA:
 * Servicio que orquesta el ciclo de vida completo de suscripciones
 * a add-ons: suscripción, cancelación, renovación, consulta y
 * verificación de actividad. Depende de EntityTypeManager para
 * CRUD de entidades, TenantContextService para aislamiento
 * multi-tenant, y del canal de log dedicado.
 *
 * LÓGICA:
 * El flujo de suscripción sigue estas reglas de negocio:
 * 1. Un tenant puede suscribirse a múltiples add-ons simultáneamente.
 * 2. No se permiten suscripciones duplicadas al mismo add-on.
 * 3. La cancelación cambia el estado pero no elimina el registro.
 * 4. La renovación extiende el periodo según el billing_cycle.
 * 5. Las suscripciones expiradas se detectan comparando end_date.
 *
 * RELACIONES:
 * - AddonSubscriptionService -> EntityTypeManager (dependencia)
 * - AddonSubscriptionService -> TenantContextService (dependencia)
 * - AddonSubscriptionService -> AddonSubscription entity (gestiona)
 * - AddonSubscriptionService -> Addon entity (consulta precios)
 * - AddonSubscriptionService <- AddonApiController (consumido por)
 * - AddonSubscriptionService <- AddonCatalogController (consumido por)
 *
 * @package Drupal\jaraba_addons\Service
 */
class AddonSubscriptionService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Canal de log dedicado para el módulo de addons.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de suscripciones a add-ons.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
  }

  /**
   * Suscribe un tenant a un add-on.
   *
   * ESTRUCTURA: Método público para crear suscripciones.
   *
   * LÓGICA: Verifica que el add-on existe y está activo, que no hay
   *   una suscripción duplicada, calcula el precio y periodo según
   *   el billing_cycle, y crea la entidad AddonSubscription.
   *
   * RELACIONES: Consume Addon y AddonSubscription storage.
   *
   * @param int $addon_id
   *   ID del add-on al que suscribirse.
   * @param int $tenant_id
   *   ID del tenant que se suscribe.
   * @param string $billing_cycle
   *   Ciclo de facturación: 'monthly' o 'yearly'.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonSubscription
   *   La suscripción creada.
   *
   * @throws \RuntimeException
   *   Si el add-on no existe, no está activo, o ya hay suscripción activa.
   */
  public function subscribe(int $addon_id, int $tenant_id, string $billing_cycle = 'monthly'): AddonSubscription {
    // Verificar que el add-on existe y está activo.
    $addon_storage = $this->entityTypeManager->getStorage('addon');
    /** @var \Drupal\jaraba_addons\Entity\Addon|null $addon */
    $addon = $addon_storage->load($addon_id);

    if (!$addon) {
      throw new \RuntimeException('El add-on solicitado no existe.');
    }

    if (!$addon->isActive()) {
      throw new \RuntimeException(
        sprintf('El add-on "%s" no está disponible actualmente.', $addon->label())
      );
    }

    // Verificar duplicados: mismo add-on y tenant con suscripción activa.
    $sub_storage = $this->entityTypeManager->getStorage('addon_subscription');
    $existing = $sub_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('addon_id', $addon_id)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', ['active', 'trial'], 'IN')
      ->count()
      ->execute();

    if ((int) $existing > 0) {
      throw new \RuntimeException(
        sprintf('El tenant ya tiene una suscripción activa al add-on "%s".', $addon->label())
      );
    }

    // Calcular precio y periodo.
    $price = $addon->getPrice($billing_cycle);
    $start = new \DateTime();
    $end = clone $start;

    if ($billing_cycle === 'yearly') {
      $end->modify('+1 year');
    }
    else {
      $end->modify('+1 month');
    }

    /** @var \Drupal\jaraba_addons\Entity\AddonSubscription $subscription */
    $subscription = $sub_storage->create([
      'addon_id' => $addon_id,
      'tenant_id' => $tenant_id,
      'status' => 'active',
      'billing_cycle' => $billing_cycle,
      'start_date' => $start->format('Y-m-d\TH:i:s'),
      'end_date' => $end->format('Y-m-d\TH:i:s'),
      'price_paid' => $price,
    ]);

    $subscription->save();

    $this->logger->info('Suscripción creada: tenant #@tenant al add-on "@addon" (@cycle, @price EUR)', [
      '@tenant' => $tenant_id,
      '@addon' => $addon->label(),
      '@cycle' => $billing_cycle,
      '@price' => $price,
    ]);

    return $subscription;
  }

  /**
   * Cancela una suscripción a un add-on.
   *
   * ESTRUCTURA: Método público para cancelar suscripciones.
   *
   * LÓGICA: Cambia el estado de la suscripción a 'cancelled'.
   *   No elimina el registro para mantener historial.
   *
   * RELACIONES: Consume AddonSubscription storage.
   *
   * @param int $subscription_id
   *   ID de la suscripción a cancelar.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonSubscription|null
   *   La suscripción cancelada o NULL si no existe.
   */
  public function cancel(int $subscription_id): ?AddonSubscription {
    $storage = $this->entityTypeManager->getStorage('addon_subscription');
    /** @var \Drupal\jaraba_addons\Entity\AddonSubscription|null $subscription */
    $subscription = $storage->load($subscription_id);

    if (!$subscription) {
      return NULL;
    }

    $subscription->set('status', 'cancelled');
    $subscription->save();

    $addon = $subscription->get('addon_id')->entity;
    $addon_label = $addon ? $addon->label() : 'Desconocido';

    $this->logger->info('Suscripción #@id cancelada: add-on "@addon" para tenant #@tenant', [
      '@id' => $subscription_id,
      '@addon' => $addon_label,
      '@tenant' => $subscription->get('tenant_id')->target_id,
    ]);

    return $subscription;
  }

  /**
   * Renueva una suscripción existente.
   *
   * ESTRUCTURA: Método público para renovar suscripciones.
   *
   * LÓGICA: Extiende el periodo de vigencia según el billing_cycle.
   *   Si la suscripción estaba expirada, la reactiva. Si estaba
   *   cancelada, la reactiva con nuevo periodo desde hoy.
   *
   * RELACIONES: Consume AddonSubscription y Addon storage.
   *
   * @param int $subscription_id
   *   ID de la suscripción a renovar.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonSubscription|null
   *   La suscripción renovada o NULL si no existe.
   */
  public function renew(int $subscription_id): ?AddonSubscription {
    $storage = $this->entityTypeManager->getStorage('addon_subscription');
    /** @var \Drupal\jaraba_addons\Entity\AddonSubscription|null $subscription */
    $subscription = $storage->load($subscription_id);

    if (!$subscription) {
      return NULL;
    }

    $billing_cycle = $subscription->get('billing_cycle')->value;

    // Calcular nueva fecha de fin.
    $now = new \DateTime();
    $end_date_str = $subscription->get('end_date')->value;

    // Si la suscripción no ha expirado, extender desde la fecha de fin actual.
    // Si ha expirado, extender desde hoy.
    if ($end_date_str && strtotime($end_date_str) > time()) {
      $new_end = new \DateTime($end_date_str);
    }
    else {
      $new_end = clone $now;
      $subscription->set('start_date', $now->format('Y-m-d\TH:i:s'));
    }

    if ($billing_cycle === 'yearly') {
      $new_end->modify('+1 year');
    }
    else {
      $new_end->modify('+1 month');
    }

    // Recalcular precio.
    $addon = $subscription->get('addon_id')->entity;
    $price = $addon ? $addon->getPrice($billing_cycle) : 0;

    $subscription->set('end_date', $new_end->format('Y-m-d\TH:i:s'));
    $subscription->set('status', 'active');
    $subscription->set('price_paid', $price);
    $subscription->save();

    $this->logger->info('Suscripción #@id renovada hasta @end (@cycle)', [
      '@id' => $subscription_id,
      '@end' => $new_end->format('Y-m-d'),
      '@cycle' => $billing_cycle,
    ]);

    return $subscription;
  }

  /**
   * Obtiene todas las suscripciones de un tenant.
   *
   * ESTRUCTURA: Método público de consulta por tenant.
   *
   * LÓGICA: Consulta todas las suscripciones del tenant especificado,
   *   ordenadas por estado (activas primero) y fecha de creación.
   *
   * RELACIONES: Consume AddonSubscription storage.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return array
   *   Array de entidades AddonSubscription del tenant.
   */
  public function getTenantSubscriptions(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('addon_subscription');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->sort('status')
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return array_values($storage->loadMultiple($ids));
  }

  /**
   * Verifica si un add-on está activo para un tenant.
   *
   * ESTRUCTURA: Método público de verificación booleana.
   *
   * LÓGICA: Consulta si existe una suscripción activa (status = 'active'
   *   o 'trial') del tenant al add-on especificado. Verificación
   *   rápida usada por lógica de negocio para feature gates.
   *
   * RELACIONES: Consume AddonSubscription storage.
   *
   * @param int $addon_id
   *   ID del add-on a verificar.
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return bool
   *   TRUE si el tenant tiene una suscripción activa al add-on.
   */
  public function isAddonActive(int $addon_id, int $tenant_id): bool {
    $storage = $this->entityTypeManager->getStorage('addon_subscription');

    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('addon_id', $addon_id)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', ['active', 'trial'], 'IN')
      ->count()
      ->execute();

    return (int) $count > 0;
  }

}
