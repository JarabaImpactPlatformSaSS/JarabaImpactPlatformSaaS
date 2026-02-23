<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jaraba_legal_calendar\Service\CalendarSyncService;
use Drupal\jaraba_legal_calendar\Service\DeadlineCalculatorService;
use Drupal\jaraba_legal_calendar\Service\LegalAgendaService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para agenda juridica.
 *
 * Endpoints para plazos, senalados, agenda unificada y conexiones.
 * Respuestas con API envelope: {data} / {data, meta} / {error}.
 */
class CalendarApiController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected DeadlineCalculatorService $deadlineCalculator,
    protected LegalAgendaService $agenda,
    protected ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    protected CalendarSyncService $calendarSync,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_legal_calendar.deadline_calculator'),
      $container->get('jaraba_legal_calendar.agenda'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('jaraba_legal_calendar.calendar_sync'),
      $container->get('logger.channel.jaraba_legal_calendar'),
    );
  }

  /**
   * GET /api/v1/legal/calendar/deadlines — Listar plazos.
   */
  public function listDeadlines(Request $request): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $query = $storage->getQuery()->accessCheck(TRUE)->sort('due_date', 'ASC');

    $status = $request->query->get('status');
    if ($status) {
      $query->condition('status', $status);
    }

    $case_id = $request->query->get('case_id');
    if ($case_id) {
      $query->condition('case_id', $case_id);
    }

    $limit = min((int) ($request->query->get('limit') ?: 50), 100);
    $offset = (int) ($request->query->get('offset') ?: 0);
    $count = (clone $query)->count()->execute();
    $ids = $query->range($offset, $limit)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = $this->serializeDeadline($entity);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['total' => $count, 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines — Crear plazo.
   */
  public function storeDeadline(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['title']) || empty($content['case_id']) || empty($content['due_date'])) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos obligatorios: title, case_id, due_date.']], 400);
    }

    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $entity = $storage->create($content);
    $entity->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeDeadline($entity)], 201);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines/compute — Calcular plazo.
   */
  public function computeDeadline(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['base_date']) || empty($content['rule'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos obligatorios: base_date, rule.']], 400);
    }

    $baseDate = new \DateTimeImmutable($content['base_date']);
    $jurisdiction = $content['jurisdiction'] ?? 'ES';
    $computed = $this->deadlineCalculator->computeDeadline($baseDate, $content['rule'], $jurisdiction);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'base_date' => $baseDate->format('Y-m-d\TH:i:s'),
        'rule' => $content['rule'],
        'jurisdiction' => $jurisdiction,
        'computed_date' => $computed->format('Y-m-d\TH:i:s'),
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * PATCH /api/v1/legal/calendar/deadlines/{uuid} — Actualizar plazo.
   */
  public function updateDeadline(string $uuid, Request $request): JsonResponse {
    $entity = $this->loadByUuid('legal_deadline', $uuid);
    if (!$entity) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Plazo no encontrado.']], 404);
    }

    $content = json_decode($request->getContent(), TRUE);
    foreach ($content as $field => $value) {
      if ($entity->hasField($field)) {
        $entity->set($field, $value);
      }
    }
    $entity->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeDeadline($entity), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/calendar/deadlines/{uuid}/complete — Completar.
   */
  public function completeDeadline(string $uuid): JsonResponse {
    $entity = $this->loadByUuid('legal_deadline', $uuid);
    if (!$entity) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Plazo no encontrado.']], 404);
    }

    $entity->set('status', 'completed');
    $entity->set('completed_at', date('Y-m-d\TH:i:s'));
    $entity->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeDeadline($entity), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/calendar/hearings — Listar senalados.
   */
  public function listHearings(Request $request): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $query = $storage->getQuery()->accessCheck(TRUE)->sort('scheduled_at', 'ASC');

    $case_id = $request->query->get('case_id');
    if ($case_id) {
      $query->condition('case_id', $case_id);
    }

    $limit = min((int) ($request->query->get('limit') ?: 50), 100);
    $offset = (int) ($request->query->get('offset') ?: 0);
    $count = (clone $query)->count()->execute();
    $ids = $query->range($offset, $limit)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = $this->serializeHearing($entity);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['total' => $count, 'limit' => $limit, 'offset' => $offset, 'timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/calendar/hearings — Crear senalado.
   */
  public function storeHearing(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content['title']) || empty($content['case_id']) || empty($content['scheduled_at']) || empty($content['court'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos obligatorios: title, case_id, scheduled_at, court.']], 400);
    }

    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $entity = $storage->create($content);
    $entity->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeHearing($entity), 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * PATCH /api/v1/legal/calendar/hearings/{uuid} — Actualizar senalado.
   */
  public function updateHearing(string $uuid, Request $request): JsonResponse {
    $entity = $this->loadByUuid('court_hearing', $uuid);
    if (!$entity) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Senalado no encontrado.']], 404);
    }

    $content = json_decode($request->getContent(), TRUE);
    foreach ($content as $field => $value) {
      if ($entity->hasField($field)) {
        $entity->set($field, $value);
      }
    }
    $entity->save();

    return new JsonResponse(['success' => TRUE, 'data' => $this->serializeHearing($entity), 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/calendar/agenda — Vista unificada.
   */
  public function agendaView(Request $request): JsonResponse {
    $days = (int) ($request->query->get('days') ?: 30);
    $deadlines = $this->agenda->getUpcomingDeadlines($days);
    $hearings = $this->agenda->getHearings($days);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'deadlines' => array_map([$this, 'serializeDeadline'], $deadlines),
        'hearings' => array_map([$this, 'serializeHearing'], $hearings),
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/agenda/{year}/{month} — Vista mensual.
   */
  public function monthView(int $year, int $month): JsonResponse {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = (new \DateTime($startDate))->modify('last day of this month')->format('Y-m-d');

    $dayView = $this->agenda->getDayView($startDate);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'year' => $year,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'deadlines' => array_map([$this, 'serializeDeadline'], $dayView['deadlines']),
        'hearings' => array_map([$this, 'serializeHearing'], $dayView['hearings']),
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/connections — Listar conexiones.
   */
  public function listConnections(): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $ids = $storage->getQuery()->accessCheck(TRUE)->execute();
    $entities = $ids ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($entities as $entity) {
      $data[] = [
        'id' => (int) $entity->id(),
        'uuid' => $entity->uuid(),
        'platform' => $entity->get('platform')->value,
        'account_email' => $entity->get('account_email')->value,
        'status' => $entity->get('status')->value,
        'last_sync_at' => $entity->get('last_sync_at')->value,
      ];
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GET /api/v1/legal/calendar/google/auth — OAuth Google redirect.
   */
  public function googleAuth(): JsonResponse {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $config = $this->configFactory->get('jaraba_legal_calendar.settings');
    $clientId = $config->get('google_client_id');
    if (empty($clientId)) {
      $this->logger->error('Google OAuth client_id not configured.');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_CONFIG_ERROR', 'message' => (string) $this->t('Google OAuth is not configured.')],
      ], 500);
    }

    $redirectUri = Url::fromRoute('jaraba_legal_calendar.api.google.callback', [], ['absolute' => TRUE])->toString();
    $state = bin2hex(random_bytes(16));
    $session = \Drupal::request()->getSession();
    $session->set('jaraba_legal_calendar_oauth_state', $state);

    $params = [
      'client_id' => $clientId,
      'redirect_uri' => $redirectUri,
      'response_type' => 'code',
      'scope' => 'https://www.googleapis.com/auth/calendar',
      'access_type' => 'offline',
      'prompt' => 'consent',
      'state' => $state,
    ];
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['redirect_url' => $authUrl],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/google/callback — OAuth Google callback.
   */
  public function googleCallback(Request $request): JsonResponse {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $code = $request->query->get('code');
    $state = $request->query->get('state');
    $error = $request->query->get('error');

    if ($error) {
      $this->logger->warning('Google OAuth denied: @error', ['@error' => $error]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_DENIED', 'message' => (string) $this->t('Google authorization was denied.')],
      ], 403);
    }

    if (empty($code) || empty($state)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_INVALID', 'message' => (string) $this->t('Missing authorization code or state parameter.')],
      ], 400);
    }

    // Validate CSRF state token.
    $session = $request->getSession();
    $storedState = $session->get('jaraba_legal_calendar_oauth_state');
    $session->remove('jaraba_legal_calendar_oauth_state');
    if (!hash_equals((string) $storedState, $state)) {
      $this->logger->warning('Google OAuth state mismatch (CSRF protection).');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_STATE_MISMATCH', 'message' => (string) $this->t('Invalid OAuth state. Please retry the authorization.')],
      ], 403);
    }

    $config = $this->configFactory->get('jaraba_legal_calendar.settings');
    $redirectUri = Url::fromRoute('jaraba_legal_calendar.api.google.callback', [], ['absolute' => TRUE])->toString();

    try {
      $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
        'form_params' => [
          'code' => $code,
          'client_id' => $config->get('google_client_id'),
          'client_secret' => $config->get('google_client_secret'),
          'redirect_uri' => $redirectUri,
          'grant_type' => 'authorization_code',
        ],
      ]);
      $tokens = json_decode((string) $response->getBody(), TRUE);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Google token exchange failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_TOKEN_ERROR', 'message' => (string) $this->t('Failed to exchange authorization code for tokens.')],
      ], 502);
    }

    if (empty($tokens['access_token'])) {
      $this->logger->error('Google token response missing access_token.');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_TOKEN_ERROR', 'message' => (string) $this->t('Invalid token response from Google.')],
      ], 502);
    }

    // Fetch user email from Google userinfo for the connection record.
    $accountEmail = '';
    try {
      $userinfoResp = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
        'headers' => ['Authorization' => 'Bearer ' . $tokens['access_token']],
      ]);
      $userinfo = json_decode((string) $userinfoResp->getBody(), TRUE);
      $accountEmail = $userinfo['email'] ?? '';
    }
    catch (GuzzleException $e) {
      $this->logger->warning('Could not fetch Google userinfo: @msg', ['@msg' => $e->getMessage()]);
    }

    // Calculate token expiry.
    $expiresAt = new \DateTime();
    $expiresAt->modify('+' . ((int) ($tokens['expires_in'] ?? 3600)) . ' seconds');

    // Create or update CalendarConnection entity.
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $currentUser = \Drupal::currentUser();
    $connection = $storage->create([
      'provider_id' => $currentUser->id(),
      'platform' => 'google',
      'account_email' => $accountEmail,
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'] ?? '',
      'token_expires_at' => $expiresAt->format('Y-m-d\TH:i:s'),
      'scopes' => ['https://www.googleapis.com/auth/calendar'],
      'status' => 'active',
    ]);
    $connection->save();

    $this->logger->info('Google Calendar connected for user @uid (@email).', [
      '@uid' => $currentUser->id(),
      '@email' => $accountEmail,
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'status' => 'connected',
        'platform' => 'google',
        'account_email' => $accountEmail,
        'connection_id' => (int) $connection->id(),
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/microsoft/auth — OAuth Microsoft redirect.
   */
  public function microsoftAuth(): JsonResponse {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $config = $this->configFactory->get('jaraba_legal_calendar.settings');
    $clientId = $config->get('microsoft_client_id');
    if (empty($clientId)) {
      $this->logger->error('Microsoft OAuth client_id not configured.');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_CONFIG_ERROR', 'message' => (string) $this->t('Microsoft OAuth is not configured.')],
      ], 500);
    }

    $redirectUri = Url::fromRoute('jaraba_legal_calendar.api.microsoft.callback', [], ['absolute' => TRUE])->toString();
    $state = bin2hex(random_bytes(16));
    $session = \Drupal::request()->getSession();
    $session->set('jaraba_legal_calendar_ms_oauth_state', $state);

    $params = [
      'client_id' => $clientId,
      'redirect_uri' => $redirectUri,
      'response_type' => 'code',
      'scope' => 'Calendars.ReadWrite offline_access',
      'state' => $state,
    ];
    $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query($params);

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['redirect_url' => $authUrl],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * GET /api/v1/legal/calendar/microsoft/callback — OAuth Microsoft callback.
   */
  public function microsoftCallback(Request $request): JsonResponse {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    $code = $request->query->get('code');
    $state = $request->query->get('state');
    $error = $request->query->get('error');

    if ($error) {
      $errorDesc = $request->query->get('error_description', '');
      $this->logger->warning('Microsoft OAuth denied: @error - @desc', ['@error' => $error, '@desc' => $errorDesc]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_DENIED', 'message' => (string) $this->t('Microsoft authorization was denied.')],
      ], 403);
    }

    if (empty($code) || empty($state)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_INVALID', 'message' => (string) $this->t('Missing authorization code or state parameter.')],
      ], 400);
    }

    // Validate CSRF state token.
    $session = $request->getSession();
    $storedState = $session->get('jaraba_legal_calendar_ms_oauth_state');
    $session->remove('jaraba_legal_calendar_ms_oauth_state');
    if (!hash_equals((string) $storedState, $state)) {
      $this->logger->warning('Microsoft OAuth state mismatch (CSRF protection).');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_STATE_MISMATCH', 'message' => (string) $this->t('Invalid OAuth state. Please retry the authorization.')],
      ], 403);
    }

    $config = $this->configFactory->get('jaraba_legal_calendar.settings');
    $redirectUri = Url::fromRoute('jaraba_legal_calendar.api.microsoft.callback', [], ['absolute' => TRUE])->toString();

    try {
      $response = $this->httpClient->request('POST', 'https://login.microsoftonline.com/common/oauth2/v2.0/token', [
        'form_params' => [
          'code' => $code,
          'client_id' => $config->get('microsoft_client_id'),
          'client_secret' => $config->get('microsoft_client_secret'),
          'redirect_uri' => $redirectUri,
          'grant_type' => 'authorization_code',
          'scope' => 'Calendars.ReadWrite offline_access',
        ],
      ]);
      $tokens = json_decode((string) $response->getBody(), TRUE);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Microsoft token exchange failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_TOKEN_ERROR', 'message' => (string) $this->t('Failed to exchange authorization code for tokens.')],
      ], 502);
    }

    if (empty($tokens['access_token'])) {
      $this->logger->error('Microsoft token response missing access_token.');
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'OAUTH_TOKEN_ERROR', 'message' => (string) $this->t('Invalid token response from Microsoft.')],
      ], 502);
    }

    // Fetch user profile from Microsoft Graph for the connection record.
    $accountEmail = '';
    try {
      $profileResp = $this->httpClient->request('GET', 'https://graph.microsoft.com/v1.0/me', [
        'headers' => ['Authorization' => 'Bearer ' . $tokens['access_token']],
      ]);
      $profile = json_decode((string) $profileResp->getBody(), TRUE);
      $accountEmail = $profile['mail'] ?? $profile['userPrincipalName'] ?? '';
    }
    catch (GuzzleException $e) {
      $this->logger->warning('Could not fetch Microsoft profile: @msg', ['@msg' => $e->getMessage()]);
    }

    // Calculate token expiry.
    $expiresAt = new \DateTime();
    $expiresAt->modify('+' . ((int) ($tokens['expires_in'] ?? 3600)) . ' seconds');

    // Create CalendarConnection entity.
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $currentUser = \Drupal::currentUser();
    $connection = $storage->create([
      'provider_id' => $currentUser->id(),
      'platform' => 'microsoft',
      'account_email' => $accountEmail,
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'] ?? '',
      'token_expires_at' => $expiresAt->format('Y-m-d\TH:i:s'),
      'scopes' => ['Calendars.ReadWrite', 'offline_access'],
      'status' => 'active',
    ]);
    $connection->save();

    $this->logger->info('Microsoft Calendar connected for user @uid (@email).', [
      '@uid' => $currentUser->id(),
      '@email' => $accountEmail,
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'status' => 'connected',
        'platform' => 'microsoft',
        'account_email' => $accountEmail,
        'connection_id' => (int) $connection->id(),
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * DELETE /api/v1/legal/calendar/connections/{id} — Desconectar.
   */
  public function deleteConnection(int $id): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('calendar_connection');
    $entity = $storage->load($id);
    if (!$entity) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Conexion no encontrada.']], 404);
    }
    $entity->delete();
    return new JsonResponse(['success' => TRUE, 'data' => ['deleted' => TRUE], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * POST /api/v1/legal/calendar/sync/{calendarId}/refresh — Forzar sync.
   */
  public function forceSync(int $calendarId): JsonResponse {
    // AUDIT-TODO-RESOLVED: Calendar OAuth integration.
    try {
      $syncedCount = $this->calendarSync->syncFromExternal($calendarId);
    }
    catch (\Exception $e) {
      $this->logger->error('Force sync failed for calendar @id: @msg', [
        '@id' => $calendarId,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'SYNC_ERROR', 'message' => (string) $this->t('Synchronization failed. Please try again later.')],
      ], 500);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'synced' => TRUE,
        'calendar_id' => $calendarId,
        'events_synced' => $syncedCount,
      ],
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * Carga entidad por UUID.
   */
  protected function loadByUuid(string $entityType, string $uuid): mixed {
    $storage = $this->entityTypeManager->getStorage($entityType);
    $ids = $storage->getQuery()
      ->condition('uuid', $uuid)
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();
    return $ids ? $storage->load(reset($ids)) : NULL;
  }

  /**
   * Serializa LegalDeadline para API response.
   */
  protected function serializeDeadline($entity): array {
    return [
      'id' => (int) $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'deadline_type' => $entity->get('deadline_type')->value,
      'legal_basis' => $entity->get('legal_basis')->value,
      'due_date' => $entity->get('due_date')->value,
      'status' => $entity->get('status')->value,
      'is_computed' => (bool) $entity->get('is_computed')->value,
      'case_id' => $entity->get('case_id')->target_id,
      'assigned_to' => $entity->get('assigned_to')->target_id,
      'alert_days_before' => (int) $entity->get('alert_days_before')->value,
      'created' => date('c', (int) $entity->get('created')->value),
    ];
  }

  /**
   * Serializa CourtHearing para API response.
   */
  protected function serializeHearing($entity): array {
    return [
      'id' => (int) $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'hearing_type' => $entity->get('hearing_type')->value,
      'court' => $entity->get('court')->value,
      'courtroom' => $entity->get('courtroom')->value,
      'scheduled_at' => $entity->get('scheduled_at')->value,
      'estimated_duration_minutes' => $entity->get('estimated_duration_minutes')->value,
      'is_virtual' => (bool) $entity->get('is_virtual')->value,
      'virtual_url' => $entity->get('virtual_url')->value,
      'status' => $entity->get('status')->value,
      'case_id' => $entity->get('case_id')->target_id,
      'created' => date('c', (int) $entity->get('created')->value),
    ];
  }

}
