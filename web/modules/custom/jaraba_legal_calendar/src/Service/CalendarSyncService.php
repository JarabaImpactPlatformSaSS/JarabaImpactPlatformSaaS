<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sincronizacion bidireccional Google Calendar / Outlook.
 *
 * Lee eventos externos y los almacena como ExternalEventCache (solo fechas,
 * sin datos privados). Push de plazos y senalados al calendario externo.
 */
class CalendarSyncService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza eventos desde un calendario externo.
   *
   * @param int $syncedCalendarId
   *   ID del SyncedCalendar.
   *
   * @return int
   *   Numero de eventos sincronizados.
   */
  public function syncFromExternal(int $syncedCalendarId): int {
    $storage = $this->entityTypeManager->getStorage('synced_calendar');
    $calendar = $storage->load($syncedCalendarId);
    if (!$calendar) {
      $this->logger->error('SyncedCalendar @id no encontrado.', ['@id' => $syncedCalendarId]);
      return 0;
    }

    $connection = $calendar->get('connection_id')->entity;
    if (!$connection) {
      $this->logger->error('CalendarConnection no encontrada para SyncedCalendar @id.', ['@id' => $syncedCalendarId]);
      return 0;
    }

    // TODO: Implementar llamada a Google Calendar API / Microsoft Graph API.
    $this->logger->info('Sync desde @platform para calendario @name.', [
      '@platform' => $connection->get('platform')->value,
      '@name' => $calendar->label(),
    ]);

    return 0;
  }

  /**
   * Empuja un evento al calendario externo.
   *
   * @param int $syncedCalendarId
   *   ID del SyncedCalendar.
   * @param string $title
   *   Titulo del evento.
   * @param \DateTimeInterface $start
   *   Fecha/hora de inicio.
   * @param \DateTimeInterface $end
   *   Fecha/hora de fin.
   *
   * @return string|null
   *   ID del evento externo creado, o NULL si fallo.
   */
  public function pushToExternal(int $syncedCalendarId, string $title, \DateTimeInterface $start, \DateTimeInterface $end): ?string {
    // TODO: Implementar push a Google Calendar API / Microsoft Graph API.
    $this->logger->info('Push evento "@title" a calendario @id.', [
      '@title' => $title,
      '@id' => $syncedCalendarId,
    ]);
    return NULL;
  }

  /**
   * Elimina un evento del calendario externo.
   */
  public function deleteExternalEvent(int $syncedCalendarId, string $externalEventId): bool {
    // TODO: Implementar DELETE a Google Calendar API / Microsoft Graph API.
    $this->logger->info('Delete evento externo @eid de calendario @id.', [
      '@eid' => $externalEventId,
      '@id' => $syncedCalendarId,
    ]);
    return TRUE;
  }

}
