<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
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

    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $platform = $connection->get('platform')->value;
    $accessToken = $this->getValidAccessToken($connection);
    if (!$accessToken) {
      $this->logger->error('No valid access token available for connection @id (@platform).', [
        '@id' => $connection->id(),
        '@platform' => $platform,
      ]);
      $connection->set('status', 'expired');
      $connection->save();
      return 0;
    }

    $externalCalendarId = $calendar->get('external_calendar_id')->value;
    $now = new \DateTime();
    $timeMin = (clone $now)->modify('-30 days')->format(\DateTimeInterface::RFC3339);
    $timeMax = (clone $now)->modify('+90 days')->format(\DateTimeInterface::RFC3339);

    try {
      if ($platform === 'google') {
        $calId = $externalCalendarId ?: 'primary';
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calId) . '/events';
        $response = $this->httpClient->request('GET', $url, [
          'headers' => ['Authorization' => 'Bearer ' . $accessToken],
          'query' => [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'maxResults' => 250,
          ],
        ]);
        $body = json_decode((string) $response->getBody(), TRUE);
        $events = $body['items'] ?? [];
      }
      elseif ($platform === 'microsoft') {
        $url = 'https://graph.microsoft.com/v1.0/me/calendarView';
        $response = $this->httpClient->request('GET', $url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Prefer' => 'outlook.timezone="UTC"',
          ],
          'query' => [
            'startDateTime' => $timeMin,
            'endDateTime' => $timeMax,
            '$top' => 250,
            '$orderby' => 'start/dateTime',
            '$select' => 'id,start,end,isAllDay,showAs,isCancelled',
          ],
        ]);
        $body = json_decode((string) $response->getBody(), TRUE);
        $events = $body['value'] ?? [];
      }
      else {
        $this->logger->error('Unsupported platform: @platform', ['@platform' => $platform]);
        return 0;
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch events from @platform for calendar @id: @msg', [
        '@platform' => $platform,
        '@id' => $syncedCalendarId,
        '@msg' => $e->getMessage(),
      ]);
      $syncErrors = (int) $connection->get('sync_errors')->value;
      $connection->set('sync_errors', $syncErrors + 1);
      $connection->save();
      return 0;
    }

    // Upsert events into ExternalEventCache.
    $cacheStorage = $this->entityTypeManager->getStorage('external_event_cache');
    $synced = 0;

    foreach ($events as $event) {
      $parsed = $this->parseExternalEvent($event, $platform);
      if (!$parsed) {
        continue;
      }

      // Check if event already cached.
      $existingIds = $cacheStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('synced_calendar_id', $syncedCalendarId)
        ->condition('external_event_id', $parsed['external_event_id'])
        ->range(0, 1)
        ->execute();

      if ($existingIds) {
        $existing = $cacheStorage->load(reset($existingIds));
        $existing->set('start_datetime', $parsed['start_datetime']);
        $existing->set('end_datetime', $parsed['end_datetime']);
        $existing->set('is_all_day', $parsed['is_all_day']);
        $existing->set('status', $parsed['status']);
        $existing->set('transparency', $parsed['transparency']);
        $existing->set('last_synced_at', $now->format('Y-m-d\TH:i:s'));
        $existing->save();
      }
      else {
        $cacheEntity = $cacheStorage->create([
          'synced_calendar_id' => $syncedCalendarId,
          'external_event_id' => $parsed['external_event_id'],
          'start_datetime' => $parsed['start_datetime'],
          'end_datetime' => $parsed['end_datetime'],
          'is_all_day' => $parsed['is_all_day'],
          'status' => $parsed['status'],
          'transparency' => $parsed['transparency'],
          'last_synced_at' => $now->format('Y-m-d\TH:i:s'),
        ]);
        $cacheEntity->save();
      }
      $synced++;
    }

    // Update connection last_sync_at and reset error count.
    $connection->set('last_sync_at', $now->format('Y-m-d\TH:i:s'));
    $connection->set('sync_errors', 0);
    $connection->save();

    $this->logger->info('Synced @count events from @platform for calendar @name.', [
      '@count' => $synced,
      '@platform' => $platform,
      '@name' => $calendar->label(),
    ]);

    return $synced;
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
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $storage = $this->entityTypeManager->getStorage('synced_calendar');
    $calendar = $storage->load($syncedCalendarId);
    if (!$calendar) {
      $this->logger->error('SyncedCalendar @id no encontrado para push.', ['@id' => $syncedCalendarId]);
      return NULL;
    }

    $connection = $calendar->get('connection_id')->entity;
    if (!$connection) {
      $this->logger->error('CalendarConnection no encontrada para push en SyncedCalendar @id.', ['@id' => $syncedCalendarId]);
      return NULL;
    }

    $platform = $connection->get('platform')->value;
    $accessToken = $this->getValidAccessToken($connection);
    if (!$accessToken) {
      $this->logger->error('No valid access token for push to @platform (connection @id).', [
        '@platform' => $platform,
        '@id' => $connection->id(),
      ]);
      return NULL;
    }

    $externalCalendarId = $calendar->get('external_calendar_id')->value;

    try {
      if ($platform === 'google') {
        $calId = $externalCalendarId ?: 'primary';
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calId) . '/events';
        $response = $this->httpClient->request('POST', $url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
          ],
          'json' => [
            'summary' => $title,
            'start' => ['dateTime' => $start->format(\DateTimeInterface::RFC3339)],
            'end' => ['dateTime' => $end->format(\DateTimeInterface::RFC3339)],
          ],
        ]);
        $body = json_decode((string) $response->getBody(), TRUE);
        $externalEventId = $body['id'] ?? NULL;
      }
      elseif ($platform === 'microsoft') {
        $url = 'https://graph.microsoft.com/v1.0/me/events';
        $response = $this->httpClient->request('POST', $url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
          ],
          'json' => [
            'subject' => $title,
            'start' => [
              'dateTime' => $start->format('Y-m-d\TH:i:s'),
              'timeZone' => 'UTC',
            ],
            'end' => [
              'dateTime' => $end->format('Y-m-d\TH:i:s'),
              'timeZone' => 'UTC',
            ],
          ],
        ]);
        $body = json_decode((string) $response->getBody(), TRUE);
        $externalEventId = $body['id'] ?? NULL;
      }
      else {
        $this->logger->error('Unsupported platform for push: @platform', ['@platform' => $platform]);
        return NULL;
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to push event "@title" to @platform: @msg', [
        '@title' => $title,
        '@platform' => $platform,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }

    if ($externalEventId) {
      $this->logger->info('Pushed event "@title" to @platform, external ID: @eid.', [
        '@title' => $title,
        '@platform' => $platform,
        '@eid' => $externalEventId,
      ]);
    }

    return $externalEventId;
  }

  /**
   * Elimina un evento del calendario externo.
   */
  public function deleteExternalEvent(int $syncedCalendarId, string $externalEventId): bool {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $storage = $this->entityTypeManager->getStorage('synced_calendar');
    $calendar = $storage->load($syncedCalendarId);
    if (!$calendar) {
      $this->logger->error('SyncedCalendar @id no encontrado para delete.', ['@id' => $syncedCalendarId]);
      return FALSE;
    }

    $connection = $calendar->get('connection_id')->entity;
    if (!$connection) {
      $this->logger->error('CalendarConnection no encontrada para delete en SyncedCalendar @id.', ['@id' => $syncedCalendarId]);
      return FALSE;
    }

    $platform = $connection->get('platform')->value;
    $accessToken = $this->getValidAccessToken($connection);
    if (!$accessToken) {
      $this->logger->error('No valid access token for delete on @platform (connection @id).', [
        '@platform' => $platform,
        '@id' => $connection->id(),
      ]);
      return FALSE;
    }

    $externalCalendarId = $calendar->get('external_calendar_id')->value;

    try {
      if ($platform === 'google') {
        $calId = $externalCalendarId ?: 'primary';
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calId) . '/events/' . urlencode($externalEventId);
        $this->httpClient->request('DELETE', $url, [
          'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
      }
      elseif ($platform === 'microsoft') {
        $url = 'https://graph.microsoft.com/v1.0/me/events/' . urlencode($externalEventId);
        $this->httpClient->request('DELETE', $url, [
          'headers' => ['Authorization' => 'Bearer ' . $accessToken],
        ]);
      }
      else {
        $this->logger->error('Unsupported platform for delete: @platform', ['@platform' => $platform]);
        return FALSE;
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to delete event @eid from @platform: @msg', [
        '@eid' => $externalEventId,
        '@platform' => $platform,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }

    // Remove from local cache as well.
    $cacheStorage = $this->entityTypeManager->getStorage('external_event_cache');
    $cacheIds = $cacheStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('synced_calendar_id', $syncedCalendarId)
      ->condition('external_event_id', $externalEventId)
      ->execute();
    if ($cacheIds) {
      $cacheEntities = $cacheStorage->loadMultiple($cacheIds);
      $cacheStorage->delete($cacheEntities);
    }

    $this->logger->info('Deleted event @eid from @platform calendar @id.', [
      '@eid' => $externalEventId,
      '@platform' => $platform,
      '@id' => $syncedCalendarId,
    ]);

    return TRUE;
  }

  /**
   * Gets a valid access token, refreshing if expired.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $connection
   *   The CalendarConnection entity.
   *
   * @return string|null
   *   A valid access token, or NULL if refresh failed.
   */
  protected function getValidAccessToken($connection): ?string {
    $accessToken = $connection->get('access_token')->value;
    $expiresAt = $connection->get('token_expires_at')->value;

    // Check if token is still valid (with 5-minute buffer).
    if ($expiresAt) {
      $expiryTime = new \DateTime($expiresAt);
      $now = new \DateTime();
      $now->modify('+5 minutes');
      if ($expiryTime > $now) {
        return $accessToken;
      }
    }

    // Token expired: attempt refresh.
    $refreshToken = $connection->get('refresh_token')->value;
    if (empty($refreshToken)) {
      $this->logger->warning('No refresh token available for connection @id.', ['@id' => $connection->id()]);
      return NULL;
    }

    $platform = $connection->get('platform')->value;
    $config = $this->configFactory->get('jaraba_legal_calendar.settings');

    try {
      if ($platform === 'google') {
        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
          'form_params' => [
            'client_id' => $config->get('google_client_id'),
            'client_secret' => $config->get('google_client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
          ],
        ]);
      }
      elseif ($platform === 'microsoft') {
        $response = $this->httpClient->request('POST', 'https://login.microsoftonline.com/common/oauth2/v2.0/token', [
          'form_params' => [
            'client_id' => $config->get('microsoft_client_id'),
            'client_secret' => $config->get('microsoft_client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => 'Calendars.ReadWrite offline_access',
          ],
        ]);
      }
      else {
        $this->logger->error('Cannot refresh token for unsupported platform: @platform', ['@platform' => $platform]);
        return NULL;
      }

      $tokens = json_decode((string) $response->getBody(), TRUE);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Token refresh failed for @platform connection @id: @msg', [
        '@platform' => $platform,
        '@id' => $connection->id(),
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }

    if (empty($tokens['access_token'])) {
      $this->logger->error('Token refresh response missing access_token for connection @id.', ['@id' => $connection->id()]);
      return NULL;
    }

    // Persist refreshed tokens.
    $newExpiry = new \DateTime();
    $newExpiry->modify('+' . ((int) ($tokens['expires_in'] ?? 3600)) . ' seconds');

    $connection->set('access_token', $tokens['access_token']);
    $connection->set('token_expires_at', $newExpiry->format('Y-m-d\TH:i:s'));
    // Google sometimes returns a new refresh_token; Microsoft may as well.
    if (!empty($tokens['refresh_token'])) {
      $connection->set('refresh_token', $tokens['refresh_token']);
    }
    $connection->set('status', 'active');
    $connection->save();

    $this->logger->info('Refreshed access token for @platform connection @id.', [
      '@platform' => $platform,
      '@id' => $connection->id(),
    ]);

    return $tokens['access_token'];
  }

  /**
   * Parses an external event into a normalized array for ExternalEventCache.
   *
   * @param array $event
   *   Raw event from Google Calendar API or Microsoft Graph API.
   * @param string $platform
   *   Either 'google' or 'microsoft'.
   *
   * @return array|null
   *   Normalized event data, or NULL if unparseable.
   */
  protected function parseExternalEvent(array $event, string $platform): ?array {
    if ($platform === 'google') {
      $eventId = $event['id'] ?? NULL;
      if (!$eventId) {
        return NULL;
      }

      $isAllDay = isset($event['start']['date']);
      if ($isAllDay) {
        $startDatetime = $event['start']['date'] . 'T00:00:00';
        $endDatetime = $event['end']['date'] . 'T00:00:00';
      }
      else {
        $startDatetime = $event['start']['dateTime'] ?? NULL;
        $endDatetime = $event['end']['dateTime'] ?? NULL;
        if (!$startDatetime || !$endDatetime) {
          return NULL;
        }
        // Normalize to Y-m-d\TH:i:s format.
        $startDatetime = (new \DateTime($startDatetime))->format('Y-m-d\TH:i:s');
        $endDatetime = (new \DateTime($endDatetime))->format('Y-m-d\TH:i:s');
      }

      $googleStatus = $event['status'] ?? 'confirmed';
      $statusMap = [
        'confirmed' => 'confirmed',
        'tentative' => 'tentative',
        'cancelled' => 'cancelled',
      ];
      $status = $statusMap[$googleStatus] ?? 'confirmed';

      $transparency = ($event['transparency'] ?? 'opaque') === 'transparent' ? 'transparent' : 'opaque';

      return [
        'external_event_id' => $eventId,
        'start_datetime' => $startDatetime,
        'end_datetime' => $endDatetime,
        'is_all_day' => $isAllDay,
        'status' => $status,
        'transparency' => $transparency,
      ];
    }

    if ($platform === 'microsoft') {
      $eventId = $event['id'] ?? NULL;
      if (!$eventId) {
        return NULL;
      }

      $isAllDay = !empty($event['isAllDay']);
      $startDatetime = $event['start']['dateTime'] ?? NULL;
      $endDatetime = $event['end']['dateTime'] ?? NULL;
      if (!$startDatetime || !$endDatetime) {
        return NULL;
      }
      $startDatetime = (new \DateTime($startDatetime))->format('Y-m-d\TH:i:s');
      $endDatetime = (new \DateTime($endDatetime))->format('Y-m-d\TH:i:s');

      $status = !empty($event['isCancelled']) ? 'cancelled' : 'confirmed';

      $showAs = $event['showAs'] ?? 'busy';
      $transparency = ($showAs === 'free' || $showAs === 'unknown') ? 'transparent' : 'opaque';

      return [
        'external_event_id' => $eventId,
        'start_datetime' => $startDatetime,
        'end_datetime' => $endDatetime,
        'is_all_day' => $isAllDay,
        'status' => $status,
        'transparency' => $transparency,
      ];
    }

    return NULL;
  }

}
