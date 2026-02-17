<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class LocalSeoService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera Schema.org JSON-LD para un LocalBusiness.
   *
   * @param int $localBusinessId
   *   ID del perfil de negocio local.
   *
   * @return array
   *   Estructura Schema.org como array asociativo.
   */
  public function generateSchemaOrg(int $localBusinessId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('local_business_profile');
      $profile = $storage->load($localBusinessId);

      if (!$profile) {
        return [];
      }

      $schema_type = $profile->get('schema_type')->value ?: 'LocalBusiness';

      $schema = [
        '@context' => 'https://schema.org',
        '@type' => $schema_type,
        'name' => $profile->get('business_name')->value,
        'description' => $profile->get('description_seo')->value,
        'address' => [
          '@type' => 'PostalAddress',
          'streetAddress' => $profile->get('address_street')->value,
          'addressLocality' => $profile->get('city')->value,
          'postalCode' => $profile->get('postal_code')->value,
          'addressRegion' => $profile->get('province')->value,
          'addressCountry' => $profile->get('country')->value,
        ],
      ];

      // Telefono.
      $phone = $profile->get('phone')->value;
      if ($phone) {
        $schema['telephone'] = $phone;
      }

      // Email.
      $email = $profile->get('email')->value;
      if ($email) {
        $schema['email'] = $email;
      }

      // Website.
      $website = $profile->get('website_url')->value;
      if ($website) {
        $schema['url'] = $website;
      }

      // Geolocalizacion.
      $lat = $profile->get('latitude')->value;
      $lng = $profile->get('longitude')->value;
      if ($lat && $lng) {
        $schema['geo'] = [
          '@type' => 'GeoCoordinates',
          'latitude' => (float) $lat,
          'longitude' => (float) $lng,
        ];
      }

      // Google Maps.
      $google_url = $profile->get('google_business_url')->value;
      if ($google_url) {
        $schema['hasMap'] = $google_url;
      }

      // Horarios.
      $opening_hours = $profile->get('opening_hours')->value;
      if ($opening_hours) {
        $hours = json_decode($opening_hours, TRUE);
        if (is_array($hours)) {
          $schema['openingHoursSpecification'] = [];
          foreach ($hours as $entry) {
            $schema['openingHoursSpecification'][] = [
              '@type' => 'OpeningHoursSpecification',
              'dayOfWeek' => $entry['day'] ?? '',
              'opens' => $entry['opens'] ?? '',
              'closes' => $entry['closes'] ?? '',
            ];
          }
        }
      }

      return $schema;
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando Schema.org para negocio @id: @e', [
        '@id' => $localBusinessId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene o crea un perfil de negocio local para un comerciante.
   *
   * @param int $merchantId
   *   ID del perfil de comerciante.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   El perfil de negocio local o NULL si falla.
   */
  public function getOrCreateProfile(int $merchantId): ?ContentEntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('local_business_profile');

      // Check existing.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('merchant_id', $merchantId)
        ->execute();

      if (!empty($ids)) {
        return $storage->load(reset($ids));
      }

      // Load merchant profile to pre-fill data.
      $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');
      $merchant = $merchant_storage->load($merchantId);

      if (!$merchant) {
        $this->logger->warning('Perfil de comerciante @id no encontrado para crear perfil local.', [
          '@id' => $merchantId,
        ]);
        return NULL;
      }

      // Create new local business profile from merchant data.
      $profile = $storage->create([
        'merchant_id' => $merchantId,
        'business_name' => $merchant->get('business_name')->value ?? '',
        'phone' => $merchant->hasField('phone') ? $merchant->get('phone')->value : '',
        'email' => $merchant->hasField('email') ? $merchant->get('email')->value : '',
        'schema_type' => 'LocalBusiness',
      ]);
      $profile->save();

      $this->logger->info('Perfil de negocio local creado para comerciante @id.', [
        '@id' => $merchantId,
      ]);

      return $profile;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo/creando perfil local para comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Verifica la consistencia NAP de todas las entradas y actualiza la puntuacion.
   *
   * @param int $localBusinessId
   *   ID del perfil de negocio local.
   *
   * @return int
   *   Puntuacion de consistencia (0-100).
   */
  public function updateNapConsistency(int $localBusinessId): int {
    try {
      $profile_storage = $this->entityTypeManager->getStorage('local_business_profile');
      $profile = $profile_storage->load($localBusinessId);

      if (!$profile) {
        return 0;
      }

      // Get all NAP entries for this business.
      $nap_storage = $this->entityTypeManager->getStorage('nap_entry');
      $nap_ids = $nap_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('local_business_id', $localBusinessId)
        ->execute();

      if (empty($nap_ids)) {
        $profile->set('nap_consistency_score', 100);
        $profile->save();
        return 100;
      }

      $nap_entries = $nap_storage->loadMultiple($nap_ids);
      $canonical_name = mb_strtolower(trim($profile->get('business_name')->value));
      $canonical_phone = preg_replace('/[^0-9+]/', '', $profile->get('phone')->value ?? '');
      $canonical_address = mb_strtolower(trim($profile->get('address_street')->value ?? ''));

      $total = count($nap_entries);
      $consistent = 0;

      foreach ($nap_entries as $nap) {
        $is_consistent = TRUE;

        $nap_name = mb_strtolower(trim($nap->get('business_name')->value ?? ''));
        if ($nap_name && $nap_name !== $canonical_name) {
          $is_consistent = FALSE;
        }

        $nap_phone = preg_replace('/[^0-9+]/', '', $nap->get('phone')->value ?? '');
        if ($nap_phone && $nap_phone !== $canonical_phone) {
          $is_consistent = FALSE;
        }

        $nap_address = mb_strtolower(trim($nap->get('address')->value ?? ''));
        if ($nap_address && $nap_address !== $canonical_address) {
          $is_consistent = FALSE;
        }

        $nap->set('is_consistent', $is_consistent ? 1 : 0);
        $nap->save();

        if ($is_consistent) {
          $consistent++;
        }
      }

      $score = (int) round(($consistent / $total) * 100);
      $profile->set('nap_consistency_score', $score);
      $profile->save();

      $this->logger->info('Consistencia NAP actualizada para negocio @id: @score%', [
        '@id' => $localBusinessId,
        '@score' => $score,
      ]);

      return $score;
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando consistencia NAP para @id: @e', [
        '@id' => $localBusinessId,
        '@e' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Verifica una entrada NAP individual contra el perfil canonico.
   *
   * @param int $napEntryId
   *   ID de la entrada NAP.
   *
   * @return bool
   *   TRUE si la entrada es consistente.
   */
  public function checkNapEntry(int $napEntryId): bool {
    try {
      $nap_storage = $this->entityTypeManager->getStorage('nap_entry');
      $nap = $nap_storage->load($napEntryId);

      if (!$nap) {
        return FALSE;
      }

      $local_business_id = $nap->get('local_business_id')->target_id;
      if (!$local_business_id) {
        return FALSE;
      }

      $profile_storage = $this->entityTypeManager->getStorage('local_business_profile');
      $profile = $profile_storage->load($local_business_id);

      if (!$profile) {
        return FALSE;
      }

      $is_consistent = TRUE;

      $canonical_name = mb_strtolower(trim($profile->get('business_name')->value));
      $nap_name = mb_strtolower(trim($nap->get('business_name')->value ?? ''));
      if ($nap_name && $nap_name !== $canonical_name) {
        $is_consistent = FALSE;
      }

      $canonical_phone = preg_replace('/[^0-9+]/', '', $profile->get('phone')->value ?? '');
      $nap_phone = preg_replace('/[^0-9+]/', '', $nap->get('phone')->value ?? '');
      if ($nap_phone && $nap_phone !== $canonical_phone) {
        $is_consistent = FALSE;
      }

      $canonical_address = mb_strtolower(trim($profile->get('address_street')->value ?? ''));
      $nap_address = mb_strtolower(trim($nap->get('address')->value ?? ''));
      if ($nap_address && $nap_address !== $canonical_address) {
        $is_consistent = FALSE;
      }

      $nap->set('is_consistent', $is_consistent ? 1 : 0);
      $nap->save();

      return $is_consistent;
    }
    catch (\Exception $e) {
      $this->logger->error('Error verificando entrada NAP @id: @e', [
        '@id' => $napEntryId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera un sitemap local con las URLs de los negocios.
   *
   * @return array
   *   Array de URLs de paginas de negocios locales.
   */
  public function generateLocalSitemap(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('local_business_profile');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('business_name', 'ASC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $urls = [];

      foreach ($entities as $entity) {
        $urls[] = [
          'url' => '/comercio/negocio/' . $entity->id(),
          'business_name' => $entity->get('business_name')->value,
          'city' => $entity->get('city')->value,
          'lastmod' => date('Y-m-d', $entity->get('changed')->value ?? $entity->get('created')->value),
        ];
      }

      return $urls;
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando sitemap local: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene negocios cercanos a una ubicacion, ordenados por distancia.
   *
   * @param float $lat
   *   Latitud del punto de referencia.
   * @param float $lng
   *   Longitud del punto de referencia.
   * @param float $radiusKm
   *   Radio de busqueda en kilometros.
   *
   * @return array
   *   Array de negocios con distancia.
   */
  public function getLocalBusinesses(float $lat, float $lng, float $radiusKm = 10): array {
    try {
      $storage = $this->entityTypeManager->getStorage('local_business_profile');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->exists('latitude')
        ->exists('longitude')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $entity_lat = (float) $entity->get('latitude')->value;
        $entity_lng = (float) $entity->get('longitude')->value;

        if (!$entity_lat || !$entity_lng) {
          continue;
        }

        $distance = $this->calculateDistance($lat, $lng, $entity_lat, $entity_lng);

        if ($distance <= $radiusKm) {
          $results[] = [
            'id' => $entity->id(),
            'business_name' => $entity->get('business_name')->value,
            'city' => $entity->get('city')->value,
            'phone' => $entity->get('phone')->value,
            'latitude' => $entity_lat,
            'longitude' => $entity_lng,
            'distance' => $distance,
          ];
        }
      }

      // Sort by distance ascending.
      usort($results, fn($a, $b) => $a['distance'] <=> $b['distance']);

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo negocios cercanos: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Calcula la distancia en kilometros entre dos puntos usando Haversine.
   *
   * @param float $lat1
   *   Latitud punto 1.
   * @param float $lng1
   *   Longitud punto 1.
   * @param float $lat2
   *   Latitud punto 2.
   * @param float $lng2
   *   Longitud punto 2.
   *
   * @return float
   *   Distancia en kilometros.
   */
  protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earth_radius = 6371;

    $lat_diff = deg2rad($lat2 - $lat1);
    $lng_diff = deg2rad($lng2 - $lng1);

    $a = sin($lat_diff / 2) * sin($lat_diff / 2)
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
      * sin($lng_diff / 2) * sin($lng_diff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($earth_radius * $c, 2);
  }

}
