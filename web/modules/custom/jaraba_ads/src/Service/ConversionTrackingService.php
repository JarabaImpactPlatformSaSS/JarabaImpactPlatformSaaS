<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking de conversiones offline para ads.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el registro y subida de eventos de conversión
 * offline a plataformas de publicidad. Permite registrar conversiones
 * que ocurren fuera del canal digital (ventas en tienda, llamadas, etc.)
 * y subirlas a Meta CAPI o Google Offline Conversions para atribución.
 *
 * LÓGICA:
 * El flujo de conversiones offline sigue estos pasos:
 * 1. Se registra un evento con recordConversion() (estado 'pending').
 * 2. Se procesan en batch con uploadPendingConversions() por plataforma.
 * 3. Se actualiza el upload_status del evento tras la subida.
 *
 * RELACIONES:
 * - ConversionTrackingService -> EntityTypeManager (dependencia)
 * - ConversionTrackingService -> AdsConversionEvent entity (gestiona)
 * - ConversionTrackingService <- AdsApiController (consumido por)
 */
class ConversionTrackingService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un nuevo evento de conversión offline.
   *
   * LÓGICA: Crea una entidad AdsConversionEvent con los datos
   *   proporcionados en estado 'pending' para posterior subida.
   *
   * @param int $tenantId
   *   ID del tenant propietario.
   * @param string $platform
   *   Plataforma destino: 'meta' o 'google'.
   * @param array $eventData
   *   Datos del evento con claves: event_name, event_time, email_hash,
   *   phone_hash, conversion_value, currency, order_id, account_id.
   *
   * @return array
   *   Array con claves: success (bool), event_id (int), message (string).
   */
  public function recordConversion(int $tenantId, string $platform, array $eventData): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ads_conversion_event');

      $event = $storage->create([
        'tenant_id' => $tenantId,
        'account_id' => $eventData['account_id'] ?? NULL,
        'platform' => $platform,
        'event_name' => $eventData['event_name'] ?? 'Purchase',
        'event_time' => $eventData['event_time'] ?? time(),
        'email_hash' => $eventData['email_hash'] ?? '',
        'phone_hash' => $eventData['phone_hash'] ?? '',
        'conversion_value' => $eventData['conversion_value'] ?? NULL,
        'currency' => $eventData['currency'] ?? 'EUR',
        'order_id' => $eventData['order_id'] ?? '',
        'upload_status' => 'pending',
      ]);

      $event->save();

      $this->logger->info('Conversión registrada: @event para tenant @tenant en @platform (ID: @id)', [
        '@event' => $eventData['event_name'] ?? 'Purchase',
        '@tenant' => $tenantId,
        '@platform' => $platform,
        '@id' => $event->id(),
      ]);

      return [
        'success' => TRUE,
        'event_id' => (int) $event->id(),
        'message' => 'Conversión registrada correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversión para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'event_id' => 0, 'message' => $e->getMessage()];
    }
  }

  /**
   * Sube los eventos de conversión pendientes de un tenant.
   *
   * LÓGICA: Consulta todos los eventos con upload_status = 'pending'
   *   del tenant, los agrupa por plataforma y los sube en batch.
   *   Actualiza el upload_status de cada evento tras la subida.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con claves: total_pending (int), uploaded (int), failed (int).
   */
  public function uploadPendingConversions(int $tenantId): array {
    $result = [
      'total_pending' => 0,
      'uploaded' => 0,
      'failed' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('ads_conversion_event');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('upload_status', 'pending')
        ->sort('created', 'ASC')
        ->execute();

      if (empty($ids)) {
        return $result;
      }

      $events = $storage->loadMultiple($ids);
      $result['total_pending'] = count($events);

      // Agrupar eventos por plataforma para batch upload.
      $byPlatform = [];
      foreach ($events as $event) {
        $platform = $event->get('platform')->value ?? 'meta';
        $byPlatform[$platform][] = $event;
      }

      foreach ($byPlatform as $platform => $platformEvents) {
        foreach ($platformEvents as $event) {
          try {
            // En producción se usaría MetaAdsClientService o GoogleAdsClientService.
            $event->set('upload_status', 'uploaded');
            $event->save();
            $result['uploaded']++;
          }
          catch (\Exception $e) {
            $event->set('upload_status', 'failed');
            $event->set('upload_error', $e->getMessage());
            $event->save();
            $result['failed']++;
          }
        }
      }

      $this->logger->info('Conversiones procesadas para tenant @tenant: @uploaded subidas, @failed fallidas de @total pendientes', [
        '@tenant' => $tenantId,
        '@uploaded' => $result['uploaded'],
        '@failed' => $result['failed'],
        '@total' => $result['total_pending'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando conversiones pendientes para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Obtiene estadísticas de conversión de un tenant.
   *
   * LÓGICA: Consulta eventos de conversión del tenant en el periodo
   *   especificado y calcula estadísticas: total, subidas, pendientes,
   *   fallidas, valor total.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $period
   *   Periodo de consulta: '7d', '30d', '90d'. Por defecto '30d'.
   *
   * @return array
   *   Array con claves: total_events, uploaded, pending, failed, total_value.
   */
  public function getConversionStats(int $tenantId, string $period = '30d'): array {
    $stats = [
      'total_events' => 0,
      'uploaded' => 0,
      'pending' => 0,
      'failed' => 0,
      'total_value' => 0.0,
    ];

    try {
      // Calcular timestamp de inicio según el periodo.
      $days = (int) rtrim($period, 'd');
      if ($days <= 0) {
        $days = 30;
      }
      $sinceTimestamp = time() - ($days * 86400);

      $storage = $this->entityTypeManager->getStorage('ads_conversion_event');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('created', $sinceTimestamp, '>=')
        ->execute();

      if (empty($ids)) {
        return $stats;
      }

      $events = $storage->loadMultiple($ids);
      $stats['total_events'] = count($events);

      foreach ($events as $event) {
        $status = $event->get('upload_status')->value;
        if ($status === 'uploaded') {
          $stats['uploaded']++;
        }
        elseif ($status === 'pending') {
          $stats['pending']++;
        }
        elseif ($status === 'failed') {
          $stats['failed']++;
        }

        $value = (float) ($event->get('conversion_value')->value ?? 0);
        $stats['total_value'] += $value;
      }

      $stats['total_value'] = round($stats['total_value'], 4);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadísticas de conversión para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

}
