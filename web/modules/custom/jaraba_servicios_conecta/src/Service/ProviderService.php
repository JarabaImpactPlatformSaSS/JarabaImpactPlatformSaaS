<?php

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de lógica de profesionales y marketplace público.
 *
 * Estructura: Gestiona la consulta y filtrado de perfiles profesionales,
 *   así como la lógica del marketplace de servicios.
 *
 * Lógica: Centraliza queries de profesionales activos y aprobados
 *   para el marketplace. Implementa filtros por categoría, modalidad,
 *   ciudad y búsqueda textual.
 */
class ProviderService {

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * La conexion a base de datos.
   */
  protected Connection $database;

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
    Connection $database,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * Obtiene profesionales activos y aprobados para el marketplace.
   *
   * @param array $filters
   *   Filtros opcionales: category, city, modality, search, tenant_id.
   * @param int $limit
   *   Límite de resultados.
   * @param int $offset
   *   Desplazamiento para paginación.
   *
   * @return array
   *   Array con 'providers' (entidades) y 'total' (count).
   */
  public function getMarketplaceProviders(array $filters = [], int $limit = 12, int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('provider_profile');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_active', TRUE)
      ->condition('verification_status', 'approved');

    if (!empty($filters['tenant_id'])) {
      $query->condition('tenant_id', $filters['tenant_id']);
    }

    if (!empty($filters['category'])) {
      $query->condition('service_category', $filters['category']);
    }

    if (!empty($filters['city'])) {
      $query->condition('address_city', $filters['city']);
    }

    if (!empty($filters['search'])) {
      $group = $query->orConditionGroup()
        ->condition('display_name', '%' . $filters['search'] . '%', 'LIKE')
        ->condition('professional_title', '%' . $filters['search'] . '%', 'LIKE');
      $query->condition($group);
    }

    // Contar total antes de aplicar paginación
    $count_query = clone $query;
    $total = $count_query->count()->execute();

    // Aplicar paginación y ordenar por rating
    $ids = $query
      ->sort('average_rating', 'DESC')
      ->sort('display_name', 'ASC')
      ->range($offset, $limit)
      ->execute();

    $providers = $ids ? $storage->loadMultiple($ids) : [];

    return [
      'providers' => $providers,
      'total' => (int) $total,
    ];
  }

  /**
   * Obtiene un profesional por su slug.
   *
   * @param string $slug
   *   El slug URL-friendly del profesional.
   * @param int|null $tenant_id
   *   ID del tenant (opcional).
   *
   * @return \Drupal\jaraba_servicios_conecta\Entity\ProviderProfile|null
   *   El perfil profesional o NULL si no existe.
   */
  public function getProviderBySlug(string $slug, ?int $tenant_id = NULL): ?object {
    $storage = $this->entityTypeManager->getStorage('provider_profile');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('slug', $slug)
      ->condition('is_active', TRUE)
      ->condition('verification_status', 'approved');

    if ($tenant_id) {
      $query->condition('tenant_id', $tenant_id);
    }

    $ids = $query->range(0, 1)->execute();
    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene el perfil del profesional del usuario actual.
   *
   * @return \Drupal\jaraba_servicios_conecta\Entity\ProviderProfile|null
   *   El perfil del usuario actual o NULL.
   */
  public function getCurrentUserProvider(): ?object {
    $storage = $this->entityTypeManager->getStorage('provider_profile');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $this->currentUser->id())
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene ciudades únicas con profesionales activos.
   *
   * @return array
   *   Lista de ciudades únicas.
   */
  public function getActiveCities(): array {
    try {
      $results = $this->database->select('provider_profile', 'p')
        ->fields('p', ['address_city'])
        ->distinct()
        ->condition('p.is_active', 1)
        ->condition('p.verification_status', 'approved')
        ->isNotNull('p.address_city')
        ->condition('p.address_city', '', '<>')
        ->orderBy('p.address_city', 'ASC')
        ->execute()
        ->fetchCol();

      return $results ?: [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching active cities: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
