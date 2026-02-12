<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_integrations\Entity\Connector;

/**
 * Servicio de registro y descubrimiento de conectores.
 *
 * PROPÓSITO:
 * Catálogo central de conectores disponibles en el marketplace.
 * Proporciona búsqueda por categoría, filtrado por plan de tenant
 * y caché de resultados para rendimiento.
 *
 * LÓGICA:
 * - getPublishedConnectors(): conectores publicados (con caché).
 * - getByCategory(): filtrado por categoría.
 * - getForTenantPlan(): filtrado por plan requerido del conector.
 * - searchConnectors(): búsqueda por nombre/descripción.
 */
class ConnectorRegistryService {

  /**
   * Cache ID para conectores publicados.
   */
  protected const CACHE_ID = 'jaraba_integrations:published_connectors';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todos los conectores publicados.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector[]
   *   Array de conectores publicados.
   */
  public function getPublishedConnectors(): array {
    $cached = $this->cache->get(self::CACHE_ID);
    if ($cached) {
      return $cached->data;
    }

    $storage = $this->entityTypeManager->getStorage('connector');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('publish_status', Connector::STATUS_PUBLISHED)
      ->sort('name', 'ASC')
      ->execute();

    $connectors = $ids ? $storage->loadMultiple($ids) : [];

    $this->cache->set(self::CACHE_ID, $connectors, CacheBackendInterface::CACHE_PERMANENT, [
      'connector_list',
    ]);

    return $connectors;
  }

  /**
   * Obtiene conectores por categoría.
   *
   * @param string $category
   *   Categoría del conector (ej: 'analytics', 'marketing').
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector[]
   *   Array de conectores filtrados.
   */
  public function getByCategory(string $category): array {
    $connectors = $this->getPublishedConnectors();
    return array_filter($connectors, fn(Connector $c) => $c->getCategory() === $category);
  }

  /**
   * Obtiene conectores disponibles para un plan de tenant.
   *
   * @param string $plan_id
   *   ID del plan del tenant (ej: 'starter', 'pro', 'enterprise').
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector[]
   *   Conectores accesibles para el plan.
   */
  public function getForTenantPlan(string $plan_id): array {
    $connectors = $this->getPublishedConnectors();

    return array_filter($connectors, function (Connector $connector) use ($plan_id) {
      $required = $connector->get('required_plans')->value;
      if (empty($required)) {
        // Sin restricción de plan: disponible para todos.
        return TRUE;
      }
      $plans = json_decode($required, TRUE) ?? [];
      return empty($plans) || in_array($plan_id, $plans, TRUE);
    });
  }

  /**
   * Busca conectores por texto en nombre o descripción.
   *
   * @param string $query
   *   Texto de búsqueda.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector[]
   *   Conectores que coinciden.
   */
  public function searchConnectors(string $query): array {
    $query_lower = mb_strtolower($query);
    $connectors = $this->getPublishedConnectors();

    return array_filter($connectors, function (Connector $connector) use ($query_lower) {
      $name = mb_strtolower($connector->getName());
      $desc = mb_strtolower($connector->get('description')->value ?? '');
      return str_contains($name, $query_lower) || str_contains($desc, $query_lower);
    });
  }

  /**
   * Obtiene todas las categorías con conteo.
   *
   * @return array<string, int>
   *   Mapa categoría => conteo.
   */
  public function getCategoryCounts(): array {
    $counts = [];
    foreach ($this->getPublishedConnectors() as $connector) {
      $cat = $connector->getCategory();
      $counts[$cat] = ($counts[$cat] ?? 0) + 1;
    }
    arsort($counts);
    return $counts;
  }

  /**
   * Invalida la caché de conectores (llamar tras CRUD).
   */
  public function invalidateCache(): void {
    $this->cache->invalidate(self::CACHE_ID);
  }

}
