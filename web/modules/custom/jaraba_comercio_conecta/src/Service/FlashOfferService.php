<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class FlashOfferService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene ofertas flash activas, opcionalmente filtradas por radio geografico.
   *
   * Logica: Busca entidades comercio_flash_offer con status=active y
   *   end_time futuro. Si se proporcionan coordenadas, filtra por distancia
   *   usando formula Haversine simplificada en post-proceso.
   *
   * @param float|null $lat
   *   Latitud del usuario.
   * @param float|null $lng
   *   Longitud del usuario.
   * @param float $radiusKm
   *   Radio maximo en kilometros.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Lista de entidades de ofertas activas.
   */
  public function getActiveOffers(float $lat = NULL, float $lng = NULL, float $radiusKm = 10, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('comercio_flash_offer');
    $now = \Drupal::time()->getRequestTime();

    try {
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->condition('end_time', $now, '>')
        ->sort('end_time', 'ASC')
        ->range(0, $limit);

      $ids = $query->execute();
      if (!$ids) {
        return [];
      }

      $offers = array_values($storage->loadMultiple($ids));

      if ($lat !== NULL && $lng !== NULL) {
        $offers = array_filter($offers, function ($offer) use ($lat, $lng, $radiusKm) {
          $offer_lat = (float) $offer->get('location_lat')->value;
          $offer_lng = (float) $offer->get('location_lng')->value;
          if ($offer_lat === 0.0 && $offer_lng === 0.0) {
            return TRUE;
          }
          return $this->calculateDistance($lat, $lng, $offer_lat, $offer_lng) <= $radiusKm;
        });
        $offers = array_values($offers);
      }

      return $offers;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo ofertas activas: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Canjea una oferta flash para un usuario.
   *
   * Logica: Verifica que la oferta este activa, no expirada, y no haya
   *   alcanzado max_claims. Crea entidad comercio_flash_offer_claim con
   *   codigo unico, incrementa current_claims en la oferta.
   *
   * @param int $offerId
   *   ID de la oferta.
   * @param int $userId
   *   ID del usuario.
   * @param float $lat
   *   Latitud del usuario al canjear.
   * @param float $lng
   *   Longitud del usuario al canjear.
   *
   * @return array|null
   *   Datos del canje con codigo, o null si no se pudo canjear.
   */
  public function claimOffer(int $offerId, int $userId, float $lat = 0, float $lng = 0): ?array {
    $offer_storage = $this->entityTypeManager->getStorage('comercio_flash_offer');
    $claim_storage = $this->entityTypeManager->getStorage('comercio_flash_offer_claim');
    $now = \Drupal::time()->getRequestTime();

    try {
      $offer = $offer_storage->load($offerId);
      if (!$offer) {
        return NULL;
      }

      if ($offer->get('status')->value !== 'active') {
        return NULL;
      }

      $end_time = (int) $offer->get('end_time')->value;
      if ($end_time > 0 && $end_time <= $now) {
        return NULL;
      }

      $max_claims = (int) $offer->get('max_claims')->value;
      $current_claims = (int) $offer->get('current_claims')->value;
      if ($max_claims > 0 && $current_claims >= $max_claims) {
        return NULL;
      }

      $existing = $claim_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('offer_id', $offerId)
        ->condition('user_id', $userId)
        ->count()
        ->execute();

      if ((int) $existing > 0) {
        $this->logger->info('Usuario @uid ya canjeo oferta @oid', [
          '@uid' => $userId,
          '@oid' => $offerId,
        ]);
        return NULL;
      }

      $claim_code = strtoupper(bin2hex(random_bytes(4)));

      $claim = $claim_storage->create([
        'offer_id' => $offerId,
        'user_id' => $userId,
        'claim_code' => $claim_code,
        'status' => 'claimed',
        'claimed_at' => $now,
        'claim_lat' => $lat,
        'claim_lng' => $lng,
      ]);
      $claim->save();

      $offer->set('current_claims', $current_claims + 1);
      $offer->save();

      $this->logger->info('Oferta @oid canjeada por usuario @uid con codigo @code', [
        '@oid' => $offerId,
        '@uid' => $userId,
        '@code' => $claim_code,
      ]);

      return [
        'claim_id' => (int) $claim->id(),
        'claim_code' => $claim_code,
        'offer_id' => $offerId,
        'user_id' => $userId,
        'status' => 'claimed',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error canjeando oferta @oid: @e', [
        '@oid' => $offerId,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Marca un canje como redimido usando el codigo de canje.
   *
   * Logica: Busca el canje por claim_code, verifica que este en status
   *   claimed, lo actualiza a redeemed con timestamp.
   *
   * @param string $claimCode
   *   Codigo unico del canje.
   *
   * @return bool
   *   TRUE si se redimio correctamente.
   */
  public function redeemClaim(string $claimCode): bool {
    $storage = $this->entityTypeManager->getStorage('comercio_flash_offer_claim');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('claim_code', $claimCode)
        ->range(0, 1)
        ->execute();

      if (!$ids) {
        return FALSE;
      }

      $claim = $storage->load(reset($ids));
      if (!$claim || $claim->get('status')->value !== 'claimed') {
        return FALSE;
      }

      $claim->set('status', 'redeemed');
      $claim->set('redeemed_at', \Drupal::time()->getRequestTime());
      $claim->save();

      $this->logger->info('Canje @code redimido', ['@code' => $claimCode]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error redimiendo canje @code: @e', [
        '@code' => $claimCode,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Cron: Activa ofertas cuyo start_time ha llegado.
   *
   * Logica: Busca ofertas con status=scheduled y start_time <= ahora,
   *   las actualiza a status=active.
   *
   * @return int
   *   Numero de ofertas activadas.
   */
  public function activateScheduledOffers(): int {
    $storage = $this->entityTypeManager->getStorage('comercio_flash_offer');
    $now = \Drupal::time()->getRequestTime();
    $count = 0;

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'scheduled')
        ->condition('start_time', $now, '<=')
        ->execute();

      if (!$ids) {
        return 0;
      }

      $offers = $storage->loadMultiple($ids);
      foreach ($offers as $offer) {
        $offer->set('status', 'active');
        $offer->save();
        $count++;
      }

      if ($count > 0) {
        $this->logger->info('Activadas @count ofertas programadas', ['@count' => $count]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error activando ofertas programadas: @e', ['@e' => $e->getMessage()]);
    }

    return $count;
  }

  /**
   * Cron: Expira ofertas cuyo end_time ha pasado.
   *
   * Logica: Busca ofertas con status=active y end_time <= ahora,
   *   las actualiza a status=expired.
   *
   * @return int
   *   Numero de ofertas expiradas.
   */
  public function expireEndedOffers(): int {
    $storage = $this->entityTypeManager->getStorage('comercio_flash_offer');
    $now = \Drupal::time()->getRequestTime();
    $count = 0;

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->condition('end_time', $now, '<=')
        ->condition('end_time', 0, '>')
        ->execute();

      if (!$ids) {
        return 0;
      }

      $offers = $storage->loadMultiple($ids);
      foreach ($offers as $offer) {
        $offer->set('status', 'expired');
        $offer->save();
        $count++;
      }

      if ($count > 0) {
        $this->logger->info('Expiradas @count ofertas', ['@count' => $count]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error expirando ofertas: @e', ['@e' => $e->getMessage()]);
    }

    return $count;
  }

  /**
   * Obtiene estadisticas de una oferta: canjes, redenciones, conversion.
   *
   * @param int $offerId
   *   ID de la oferta.
   *
   * @return array
   *   Array con total_claims, total_redeemed, conversion_rate.
   */
  public function getOfferStats(int $offerId): array {
    $claim_storage = $this->entityTypeManager->getStorage('comercio_flash_offer_claim');

    try {
      $total_claims = (int) $claim_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('offer_id', $offerId)
        ->count()
        ->execute();

      $total_redeemed = (int) $claim_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('offer_id', $offerId)
        ->condition('status', 'redeemed')
        ->count()
        ->execute();

      $conversion_rate = $total_claims > 0 ? round(($total_redeemed / $total_claims) * 100, 2) : 0;

      return [
        'total_claims' => $total_claims,
        'total_redeemed' => $total_redeemed,
        'conversion_rate' => $conversion_rate,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo stats de oferta @id: @e', [
        '@id' => $offerId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'total_claims' => 0,
        'total_redeemed' => 0,
        'conversion_rate' => 0,
      ];
    }
  }

  /**
   * Calcula la distancia entre dos puntos en kilometros usando Haversine.
   */
  protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earth_radius = 6371;
    $d_lat = deg2rad($lat2 - $lat1);
    $d_lng = deg2rad($lng2 - $lng1);
    $a = sin($d_lat / 2) * sin($d_lat / 2)
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
      * sin($d_lng / 2) * sin($d_lng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
  }

}
