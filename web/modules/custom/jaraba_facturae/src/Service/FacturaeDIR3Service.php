<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_facturae\ValueObject\DIR3Unit;
use Psr\Log\LoggerInterface;

/**
 * Servicio de consulta del Directorio Comun DIR3.
 *
 * Provee busqueda de unidades organizativas (Oficina Contable,
 * Organo Gestor, Unidad Tramitadora) del directorio DIR3 para
 * facturacion B2G a Administraciones Publicas.
 *
 * Cache de 24h para evitar consultas repetidas al directorio
 * que contiene 60.000+ unidades.
 *
 * Spec: Doc 180, Seccion 3.6.
 * Plan: FASE 7, entregable F7-3.
 */
class FacturaeDIR3Service {

  /**
   * Cache TTL: 24 hours.
   */
  private const CACHE_TTL = 86400;

  /**
   * Cache bin prefix.
   */
  private const CACHE_PREFIX = 'jaraba_facturae:dir3:';

  /**
   * DIR3 centre role types.
   */
  public const ROLE_OFICINA_CONTABLE = '01';
  public const ROLE_ORGANO_GESTOR = '02';
  public const ROLE_UNIDAD_TRAMITADORA = '03';
  public const ROLE_ORGANO_PROPONENTE = '04';

  public function __construct(
    protected readonly CacheBackendInterface $cache,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Searches DIR3 units by name or code.
   *
   * @param string $query
   *   The search query (min 3 characters).
   * @param string $type
   *   Optional filter by role type: '01', '02', '03', or 'all'.
   *
   * @return array
   *   Array of DIR3Unit objects matching the query.
   */
  public function search(string $query, string $type = 'all'): array {
    $query = trim($query);
    if (strlen($query) < 3) {
      return [];
    }

    // Check cache first.
    $cacheKey = self::CACHE_PREFIX . 'search:' . md5($query . ':' . $type);
    $cached = $this->cache->get($cacheKey);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    // Search in local storage if available (synced from FACe).
    $results = $this->searchLocal($query, $type);

    // Cache results.
    $this->cache->set($cacheKey, $results, time() + self::CACHE_TTL);

    return $results;
  }

  /**
   * Gets a specific DIR3 unit by code.
   *
   * @param string $code
   *   The DIR3 unit code.
   *
   * @return \Drupal\jaraba_facturae\ValueObject\DIR3Unit|null
   *   The DIR3 unit, or NULL if not found.
   */
  public function getUnit(string $code): ?DIR3Unit {
    $code = strtoupper(trim($code));
    if (empty($code)) {
      return NULL;
    }

    $cacheKey = self::CACHE_PREFIX . 'unit:' . $code;
    $cached = $this->cache->get($cacheKey);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    // Search local storage.
    $results = $this->searchLocal($code, 'all');
    foreach ($results as $unit) {
      if ($unit->code === $code) {
        $this->cache->set($cacheKey, $unit, time() + self::CACHE_TTL);
        return $unit;
      }
    }

    return NULL;
  }

  /**
   * Validates a DIR3 centre code against a specific role type.
   *
   * @param string $code
   *   The DIR3 code.
   * @param string $type
   *   The expected role type ('01', '02', '03').
   *
   * @return bool
   *   TRUE if the code is valid for the given type.
   */
  public function validateCentre(string $code, string $type): bool {
    $unit = $this->getUnit($code);
    if ($unit === NULL) {
      return FALSE;
    }

    if ($type !== 'all' && $unit->type !== $type) {
      return FALSE;
    }

    return $unit->active;
  }

  /**
   * Gets the three required DIR3 centres for a B2G invoice.
   *
   * @param string $oficinaContable
   *   DIR3 code for Oficina Contable (role 01).
   * @param string $organoGestor
   *   DIR3 code for Organo Gestor (role 02).
   * @param string $unidadTramitadora
   *   DIR3 code for Unidad Tramitadora (role 03).
   *
   * @return array
   *   Array with keys: valid, centres, errors.
   */
  public function validateB2GCentres(string $oficinaContable, string $organoGestor, string $unidadTramitadora): array {
    $errors = [];
    $centres = [];

    if (empty($oficinaContable)) {
      $errors[] = 'Oficina Contable (DIR3 role 01) is required for B2G invoices.';
    }
    else {
      $unit = $this->getUnit($oficinaContable);
      if ($unit !== NULL) {
        $centres[] = ['code' => $oficinaContable, 'role' => self::ROLE_OFICINA_CONTABLE, 'name' => $unit->name];
      }
      else {
        $errors[] = sprintf('Oficina Contable code %s not found in DIR3 directory.', $oficinaContable);
      }
    }

    if (empty($organoGestor)) {
      $errors[] = 'Organo Gestor (DIR3 role 02) is required for B2G invoices.';
    }
    else {
      $unit = $this->getUnit($organoGestor);
      if ($unit !== NULL) {
        $centres[] = ['code' => $organoGestor, 'role' => self::ROLE_ORGANO_GESTOR, 'name' => $unit->name];
      }
      else {
        $errors[] = sprintf('Organo Gestor code %s not found in DIR3 directory.', $organoGestor);
      }
    }

    if (empty($unidadTramitadora)) {
      $errors[] = 'Unidad Tramitadora (DIR3 role 03) is required for B2G invoices.';
    }
    else {
      $unit = $this->getUnit($unidadTramitadora);
      if ($unit !== NULL) {
        $centres[] = ['code' => $unidadTramitadora, 'role' => self::ROLE_UNIDAD_TRAMITADORA, 'name' => $unit->name];
      }
      else {
        $errors[] = sprintf('Unidad Tramitadora code %s not found in DIR3 directory.', $unidadTramitadora);
      }
    }

    return [
      'valid' => empty($errors),
      'centres' => $centres,
      'errors' => $errors,
    ];
  }

  /**
   * Searches for DIR3 units in local storage.
   *
   * In a full implementation, this would query a local database table
   * that is synced from FACe periodically. For now, it provides a
   * framework for the search interface.
   *
   * @param string $query
   *   Search query.
   * @param string $type
   *   Role type filter.
   *
   * @return array
   *   Array of DIR3Unit objects.
   */
  protected function searchLocal(string $query, string $type): array {
    // This is a placeholder for the DIR3 search.
    // In production, this queries a local cache table synced from FACe.
    // The FACe SOAP method consultarUnidades provides the data source.
    $this->logger->debug('DIR3 search for query "@query" type "@type".', [
      '@query' => $query,
      '@type' => $type,
    ]);

    return [];
  }

  /**
   * Clears the DIR3 cache.
   */
  public function clearCache(): void {
    $this->cache->deleteAll();
    $this->logger->info('DIR3 cache cleared.');
  }

}
