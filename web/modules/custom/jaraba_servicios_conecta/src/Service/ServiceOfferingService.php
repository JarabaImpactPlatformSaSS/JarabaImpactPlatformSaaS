<?php

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de lógica de servicios ofertados.
 *
 * Estructura: Gestiona el CRUD y consulta de servicios ofertados
 *   por los profesionales.
 *
 * Lógica: Centraliza queries de servicios publicados, filtrado
 *   por profesional, categoría y modalidad.
 */
class ServiceOfferingService {

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * Obtiene servicios publicados de un profesional.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   *
   * @return array
   *   Array de entidades ServiceOffering.
   */
  public function getProviderOfferings(int $provider_id): array {
    $storage = $this->entityTypeManager->getStorage('service_offering');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('is_published', TRUE)
      ->sort('sort_weight', 'ASC')
      ->sort('title', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene paquetes publicados de un profesional.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   *
   * @return array
   *   Array de entidades ServicePackage.
   */
  public function getProviderPackages(int $provider_id): array {
    $storage = $this->entityTypeManager->getStorage('service_package');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('is_published', TRUE)
      ->sort('title', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene servicios destacados del marketplace.
   *
   * @param int $limit
   *   Límite de resultados.
   *
   * @return array
   *   Array de entidades ServiceOffering destacadas.
   */
  public function getFeaturedOfferings(int $limit = 8): array {
    $storage = $this->entityTypeManager->getStorage('service_offering');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_published', TRUE)
      ->condition('is_featured', TRUE)
      ->sort('title', 'ASC')
      ->range(0, $limit)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

}
