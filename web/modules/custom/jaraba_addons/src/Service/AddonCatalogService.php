<?php

namespace Drupal\jaraba_addons\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_addons\Entity\Addon;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión del catálogo de add-ons.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el catálogo público de add-ons disponibles
 * para suscripción por los tenants. Proporciona métodos para listar
 * add-ons activos, filtrar por tipo y obtener precios según ciclo
 * de facturación. Depende de EntityTypeManager para consulta de
 * entidades, TenantContextService para aislamiento multi-tenant,
 * y del canal de log dedicado.
 *
 * LÓGICA:
 * El catálogo se construye dinámicamente consultando las entidades Addon
 * con is_active = TRUE. Los add-ons se pueden filtrar por tipo (feature,
 * storage, api_calls, support, custom). Los precios se devuelven
 * según el ciclo de facturación solicitado (mensual o anual).
 *
 * RELACIONES:
 * - AddonCatalogService -> EntityTypeManager (dependencia)
 * - AddonCatalogService -> TenantContextService (dependencia)
 * - AddonCatalogService -> Addon entity (consulta)
 * - AddonCatalogService <- AddonCatalogController (consumido por)
 * - AddonCatalogService <- AddonApiController (consumido por)
 *
 * @package Drupal\jaraba_addons\Service
 */
class AddonCatalogService {

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
   * Constructor del servicio de catálogo de add-ons.
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
   * Obtiene todos los add-ons activos del catálogo.
   *
   * ESTRUCTURA: Método público principal del catálogo.
   *
   * LÓGICA: Consulta todas las entidades Addon con is_active = TRUE,
   *   ordenadas por tipo y nombre. Devuelve array de entidades.
   *
   * RELACIONES: Consume Addon storage.
   *
   * @return array
   *   Array de entidades Addon activas.
   */
  public function getAvailableAddons(): array {
    $storage = $this->entityTypeManager->getStorage('addon');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_active', TRUE)
      ->sort('addon_type')
      ->sort('label')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return array_values($storage->loadMultiple($ids));
  }

  /**
   * Obtiene add-ons filtrados por tipo.
   *
   * ESTRUCTURA: Método público de filtrado por categoría.
   *
   * LÓGICA: Consulta add-ons activos del tipo especificado.
   *   Tipos válidos: feature, storage, api_calls, support, custom.
   *
   * RELACIONES: Consume Addon storage.
   *
   * @param string $type
   *   Tipo de add-on a filtrar.
   *
   * @return array
   *   Array de entidades Addon del tipo especificado.
   */
  public function getAddonsByType(string $type): array {
    $storage = $this->entityTypeManager->getStorage('addon');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_active', TRUE)
      ->condition('addon_type', $type)
      ->sort('label')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return array_values($storage->loadMultiple($ids));
  }

  /**
   * Obtiene el precio de un add-on según el ciclo de facturación.
   *
   * ESTRUCTURA: Método público de consulta de precios.
   *
   * LÓGICA: Carga el add-on y devuelve el precio correspondiente
   *   al ciclo de facturación (mensual o anual). Si el add-on no
   *   existe o no está activo, devuelve NULL.
   *
   * RELACIONES: Consume Addon storage, invoca Addon::getPrice().
   *
   * @param int $addon_id
   *   ID del add-on.
   * @param string $billing_cycle
   *   Ciclo de facturación: 'monthly' o 'yearly'.
   *
   * @return float|null
   *   Precio del add-on o NULL si no existe/no está activo.
   */
  public function getAddonPrice(int $addon_id, string $billing_cycle = 'monthly'): ?float {
    $storage = $this->entityTypeManager->getStorage('addon');
    /** @var \Drupal\jaraba_addons\Entity\Addon|null $addon */
    $addon = $storage->load($addon_id);

    if (!$addon || !$addon->isActive()) {
      return NULL;
    }

    return $addon->getPrice($billing_cycle);
  }

}
