<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST APIs para Admin Center — Gestión de Tenants.
 *
 * Endpoints:
 *   GET  /api/v1/admin/tenants          — Lista paginada con filtros.
 *   GET  /api/v1/admin/tenants/{id}     — Detalle 360 de un tenant.
 *   POST /api/v1/admin/tenants/{id}/impersonate — Iniciar impersonación.
 *   GET  /api/v1/admin/tenants/export   — Exportar CSV.
 *
 * F6 — Doc 181 / Spec f104 §FASE 2.
 */
class AdminCenterApiController extends ControllerBase {

  use ApiResponseTrait;

  /**
   * The admin center settings service.
   *
   * @var object
   */
  protected $adminCenterSettings;

  /**
   * The admin center alerts service.
   *
   * @var object
   */
  protected $adminCenterAlerts;

  /**
   * The admin center analytics service.
   *
   * @var object
   */
  protected $adminCenterAnalytics;

  /**
   * The admin center finance service.
   *
   * @var object
   */
  protected $adminCenterFinance;

  /**
   * The masquerade service.
   *
   * @var object|null
   */
  protected $masquerade;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandlerService;

  public function __construct(
    protected LoggerInterface $logger,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    protected RendererInterface $renderer,
    protected Connection $database,
    object $adminCenterSettings,
    object $adminCenterAlerts,
    object $adminCenterAnalytics,
    object $adminCenterFinance,
    ModuleHandlerInterface $moduleHandler,
    ?object $masquerade,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->adminCenterSettings = $adminCenterSettings;
    $this->adminCenterAlerts = $adminCenterAlerts;
    $this->adminCenterAnalytics = $adminCenterAnalytics;
    $this->adminCenterFinance = $adminCenterFinance;
    $this->moduleHandlerService = $moduleHandler;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.ecosistema_jaraba_core'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('database'),
      $container->get('ecosistema_jaraba_core.admin_center_settings'),
      $container->get('ecosistema_jaraba_core.admin_center_alerts'),
      $container->get('ecosistema_jaraba_core.admin_center_analytics'),
      $container->get('ecosistema_jaraba_core.admin_center_finance'),
      $container->get('module_handler'),
      $container->has('masquerade') ? $container->get('masquerade') : NULL,
    );
  }

  /**
   * GET /api/v1/admin/tenants
   *
   * Parámetros query:
   *   - q: búsqueda por nombre (LIKE).
   *   - status: filtro por field_subscription_status (active|trial|suspended).
   *   - plan: filtro por field_saas_plan (entity reference label).
   *   - sort: campo de ordenación (label|created|field_subscription_status).
   *   - dir: dirección (ASC|DESC).
   *   - limit: tamaño de página (default 20, max 100).
   *   - offset: desplazamiento.
   */
  public function listTenants(Request $request): JsonResponse {
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');

      // Parse query params.
      $q = trim($request->query->get('q', ''));
      $status = $request->query->get('status', '');
      $plan = $request->query->get('plan', '');
      $sort = $request->query->get('sort', 'label');
      $dir = strtoupper($request->query->get('dir', 'ASC'));
      $limit = min((int) $request->query->get('limit', 20), 100);
      $offset = max((int) $request->query->get('offset', 0), 0);

      if (!in_array($dir, ['ASC', 'DESC'], TRUE)) {
        $dir = 'ASC';
      }

      // Allowed sort fields.
      $allowedSorts = ['label', 'created', 'field_subscription_status'];
      if (!in_array($sort, $allowedSorts, TRUE)) {
        $sort = 'label';
      }

      // Build count query first.
      $countQuery = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant');

      if ($q !== '') {
        $countQuery->condition('label', '%' . $q . '%', 'LIKE');
      }
      if ($status !== '') {
        $countQuery->condition('field_subscription_status', $status);
      }

      $total = (int) $countQuery->count()->execute();

      // Build data query.
      $dataQuery = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant');

      if ($q !== '') {
        $dataQuery->condition('label', '%' . $q . '%', 'LIKE');
      }
      if ($status !== '') {
        $dataQuery->condition('field_subscription_status', $status);
      }

      $dataQuery->sort($sort, $dir)
        ->range($offset, $limit);

      $ids = $dataQuery->execute();
      $groups = $groupStorage->loadMultiple($ids);

      $items = [];
      foreach ($groups as $group) {
        $items[] = $this->serializeTenantSummary($group);
      }

      return $this->apiPaginated($items, $total, $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('Admin tenants list error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al listar tenants.', 'LIST_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/tenants/{group}
   *
   * Detalle 360 de un tenant específico.
   */
  public function tenantDetail(string $group): JsonResponse {
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $entity = $groupStorage->load($group);

      if (!$entity || $entity->bundle() !== 'tenant') {
        return $this->apiError('Tenant no encontrado.', 'NOT_FOUND', 404);
      }

      $detail = $this->serializeTenantDetail($entity);

      return $this->apiSuccess($detail);
    }
    catch (\Exception $e) {
      $this->logger->error('Admin tenant detail error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener detalle del tenant.', 'DETAIL_ERROR', 500);
    }
  }

  /**
   * GET /admin/jaraba/center/tenants/{group}/panel
   *
   * Detalle 360 renderizado como HTML para slide-panel.
   */
  public function tenantDetailPanel(string $group): Response {
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $entity = $groupStorage->load($group);

      if (!$entity || $entity->bundle() !== 'tenant') {
        return new Response('<p>' . $this->t('Tenant no encontrado.') . '</p>', 404);
      }

      $detail = $this->serializeTenantDetail($entity);

      $build = [
        '#theme' => 'admin_center_tenant_detail',
        '#tenant' => $detail,
      ];

      $html = (string) $this->renderer->render($build);
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Tenant panel error: @error', ['@error' => $e->getMessage()]);
      return new Response('<p>' . $this->t('Error al cargar el detalle.') . '</p>', 500);
    }
  }

  /**
   * POST /api/v1/admin/tenants/{group}/impersonate
   *
   * Inicia impersonación como admin del tenant.
   */
  public function impersonate(string $group): JsonResponse {
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $entity = $groupStorage->load($group);

      if (!$entity || $entity->bundle() !== 'tenant') {
        return $this->apiError('Tenant no encontrado.', 'NOT_FOUND', 404);
      }

      // Buscar el tenant_admin del grupo.
      $membershipStorage = $this->entityTypeManager->getStorage('group_relationship');
      $memberships = $membershipStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('gid', $entity->id())
        ->condition('group_roles', 'tenant-tenant_admin', 'CONTAINS')
        ->range(0, 1)
        ->execute();

      if (empty($memberships)) {
        return $this->apiError(
          'No se encontró un administrador para este tenant.',
          'NO_ADMIN',
          422,
        );
      }

      $membership = $membershipStorage->load(reset($memberships));
      $targetUid = $membership->get('entity_id')->target_id ?? NULL;

      if (!$targetUid) {
        return $this->apiError('UID del admin no válido.', 'INVALID_UID', 422);
      }

      // Log the impersonation attempt.
      $this->logger->notice('Impersonation: @admin impersonating tenant @tenant (uid @uid)', [
        '@admin' => $this->currentUser->getDisplayName(),
        '@tenant' => $entity->label(),
        '@uid' => $targetUid,
      ]);

      // Impersonation via masquerade module if available.
      if ($this->moduleHandlerService->moduleExists('masquerade') && $this->masquerade) {
        $targetUser = $this->entityTypeManager->getStorage('user')->load($targetUid);
        if ($targetUser && $this->masquerade->switchTo($targetUser)) {
          return $this->apiSuccess([
            'redirect' => '/tenant/dashboard',
            'target_uid' => (int) $targetUid,
            'tenant' => $entity->label(),
          ]);
        }
      }

      // Fallback: return the target URL for manual masquerade.
      return $this->apiSuccess([
        'redirect' => '/admin/people/masquerade/' . $targetUid,
        'target_uid' => (int) $targetUid,
        'tenant' => $entity->label(),
        'manual' => TRUE,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Impersonation error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al iniciar impersonación.', 'IMPERSONATE_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/tenants/export
   *
   * Exporta tenants en formato CSV.
   */
  public function exportTenants(Request $request): JsonResponse {
    try {
      $groupStorage = $this->entityTypeManager->getStorage('group');

      $query = $groupStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'tenant')
        ->sort('label', 'ASC');

      // Apply same filters as list.
      $status = $request->query->get('status', '');
      if ($status !== '') {
        $query->condition('field_subscription_status', $status);
      }

      $ids = $query->execute();
      $groups = $groupStorage->loadMultiple($ids);

      $rows = [];
      foreach ($groups as $group) {
        $rows[] = $this->serializeTenantSummary($group);
      }

      return $this->apiSuccess([
        'format' => 'json',
        'count' => count($rows),
        'rows' => $rows,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Admin tenants export error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al exportar tenants.', 'EXPORT_ERROR', 500);
    }
  }

  // =========================================================================
  // USER MANAGEMENT APIS (FASE 3)
  // =========================================================================

  /**
   * GET /api/v1/admin/users
   *
   * Parámetros query:
   *   - q: búsqueda por nombre o email (LIKE).
   *   - status: filtro por estado (1=active, 0=blocked).
   *   - role: filtro por rol.
   *   - sort: campo (name|created|access|status).
   *   - dir: dirección (ASC|DESC).
   *   - limit/offset: paginación.
   */
  public function listUsers(Request $request): JsonResponse {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');

      $q = trim($request->query->get('q', ''));
      $status = $request->query->get('status', '');
      $role = $request->query->get('role', '');
      $sort = $request->query->get('sort', 'name');
      $dir = strtoupper($request->query->get('dir', 'ASC'));
      $limit = min((int) $request->query->get('limit', 20), 100);
      $offset = max((int) $request->query->get('offset', 0), 0);

      if (!in_array($dir, ['ASC', 'DESC'], TRUE)) {
        $dir = 'ASC';
      }
      $allowedSorts = ['name', 'created', 'access', 'status'];
      if (!in_array($sort, $allowedSorts, TRUE)) {
        $sort = 'name';
      }

      // Count query.
      $countQuery = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', 0, '>');
      $this->applyUserFilters($countQuery, $q, $status, $role);
      $total = (int) $countQuery->count()->execute();

      // Data query.
      $dataQuery = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', 0, '>');
      $this->applyUserFilters($dataQuery, $q, $status, $role);
      $dataQuery->sort($sort, $dir)->range($offset, $limit);
      $ids = $dataQuery->execute();

      $users = $userStorage->loadMultiple($ids);
      $items = [];
      foreach ($users as $user) {
        $items[] = $this->serializeUserSummary($user);
      }

      return $this->apiPaginated($items, $total, $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('Admin users list error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al listar usuarios.', 'LIST_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/users/{uid}
   *
   * Detalle de un usuario.
   */
  public function userDetail(string $uid): JsonResponse {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!$user || (int) $user->id() === 0) {
        return $this->apiError('Usuario no encontrado.', 'NOT_FOUND', 404);
      }

      return $this->apiSuccess($this->serializeUserDetail($user));
    }
    catch (\Exception $e) {
      $this->logger->error('Admin user detail error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener detalle del usuario.', 'DETAIL_ERROR', 500);
    }
  }

  /**
   * GET /admin/jaraba/center/users/{uid}/panel
   *
   * Detalle de usuario renderizado como HTML para slide-panel.
   */
  public function userDetailPanel(string $uid): Response {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!$user || (int) $user->id() === 0) {
        return new Response('<p>' . $this->t('Usuario no encontrado.') . '</p>', 404);
      }

      $detail = $this->serializeUserDetail($user);

      $build = [
        '#theme' => 'admin_center_user_detail',
        '#user_data' => $detail,
      ];

      $html = (string) $this->renderer->render($build);
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('User panel error: @error', ['@error' => $e->getMessage()]);
      return new Response('<p>' . $this->t('Error al cargar el detalle.') . '</p>', 500);
    }
  }

  /**
   * DELETE /api/v1/admin/users/{uid}/sessions
   *
   * Fuerza cierre de sesiones del usuario.
   */
  public function forceLogout(string $uid): JsonResponse {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!$user || (int) $user->id() === 0) {
        return $this->apiError('Usuario no encontrado.', 'NOT_FOUND', 404);
      }

      // Delete all sessions for this user from the sessions table.
      $deleted = $this->database->delete('sessions')
        ->condition('uid', $user->id())
        ->execute();

      $this->logger->notice('Force logout: @admin forced logout of @user (@uid). @count sessions deleted.', [
        '@admin' => $this->currentUser->getDisplayName(),
        '@user' => $user->getDisplayName(),
        '@uid' => $user->id(),
        '@count' => $deleted,
      ]);

      return $this->apiSuccess([
        'uid' => (int) $user->id(),
        'sessions_deleted' => (int) $deleted,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Force logout error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al cerrar sesiones.', 'LOGOUT_ERROR', 500);
    }
  }

  /**
   * Aplica filtros comunes a queries de usuarios.
   */
  protected function applyUserFilters(object $query, string $q, string $status, string $role): void {
    if ($q !== '') {
      $orGroup = $query->orConditionGroup()
        ->condition('name', '%' . $q . '%', 'LIKE')
        ->condition('mail', '%' . $q . '%', 'LIKE');
      $query->condition($orGroup);
    }
    if ($status !== '') {
      $query->condition('status', (int) $status);
    }
    if ($role !== '') {
      $query->condition('roles', $role);
    }
  }

  /**
   * Serializa un usuario para la lista (resumen).
   */
  protected function serializeUserSummary(object $user): array {
    $tenantInfo = $this->getUserTenantInfo($user);

    return [
      'id' => (int) $user->id(),
      'name' => $user->getDisplayName(),
      'email' => $user->getEmail() ?? '',
      'status' => (int) $user->get('status')->value ? 'active' : 'blocked',
      'roles' => array_values(array_diff($user->getRoles(), ['authenticated'])),
      'role_label' => $this->getPrimaryRoleLabel($user),
      'tenant' => $tenantInfo['label'] ?? '',
      'tenant_id' => $tenantInfo['id'] ?? NULL,
      'last_access' => $this->formatTimestamp((int) $user->get('access')->value),
      'created' => date('Y-m-d', (int) $user->get('created')->value),
      'url' => $user->toUrl('canonical')->toString(),
    ];
  }

  /**
   * Serializa un usuario para el detalle.
   */
  protected function serializeUserDetail(object $user): array {
    $summary = $this->serializeUserSummary($user);

    // All roles.
    $summary['all_roles'] = $user->getRoles();

    // Activity log (last 10 watchdog entries for this user).
    $summary['activity'] = $this->getUserActivity((int) $user->id());

    // Session count.
    $summary['sessions'] = $this->getUserSessionCount((int) $user->id());

    return $summary;
  }

  /**
   * Obtiene el tenant (group) principal de un usuario.
   */
  protected function getUserTenantInfo(object $user): array {
    try {
      $membershipStorage = $this->entityTypeManager->getStorage('group_relationship');
      $ids = $membershipStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_id', $user->id())
        ->condition('plugin_id', 'group_membership')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $membership = $membershipStorage->load(reset($ids));
        $group = $membership->getGroup();
        if ($group) {
          return [
            'id' => (int) $group->id(),
            'label' => $group->label(),
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }
    return [];
  }

  /**
   * Obtiene la etiqueta del rol principal del usuario.
   */
  protected function getPrimaryRoleLabel(object $user): string {
    $roles = array_diff($user->getRoles(), ['authenticated', 'anonymous']);
    if (empty($roles)) {
      return 'authenticated';
    }
    // Priority: administrator > tenant_admin > rest.
    if (in_array('administrator', $roles, TRUE)) {
      return 'administrator';
    }
    if (in_array('tenant_admin', $roles, TRUE)) {
      return 'tenant_admin';
    }
    return reset($roles);
  }

  /**
   * Obtiene las últimas entradas de actividad de un usuario.
   */
  protected function getUserActivity(int $uid): array {
    $activity = [];
    try {
      $result = $this->database->select('watchdog', 'w')
        ->fields('w', ['wid', 'type', 'message', 'variables', 'severity', 'timestamp'])
        ->condition('w.uid', $uid)
        ->orderBy('w.timestamp', 'DESC')
        ->range(0, 10)
        ->execute();

      foreach ($result as $row) {
        $activity[] = [
          'id' => $row->wid,
          'type' => $row->type,
          'message' => $this->formatLogMessage($row->message, $row->variables),
          'severity' => (int) $row->severity,
          'timestamp' => (int) $row->timestamp,
          'time_ago' => $this->formatTimestamp((int) $row->timestamp),
        ];
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }
    return $activity;
  }

  /**
   * Obtiene el conteo de sesiones activas de un usuario.
   */
  protected function getUserSessionCount(int $uid): int {
    try {
      return (int) $this->database->select('sessions', 's')
        ->condition('s.uid', $uid)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Formatea un timestamp como texto relativo o fecha.
   */
  protected function formatTimestamp(int $timestamp): string {
    if ($timestamp === 0) {
      return '-';
    }
    $diff = time() - $timestamp;
    if ($diff < 3600) {
      return (int) ($diff / 60) . 'm';
    }
    if ($diff < 86400) {
      return (int) ($diff / 3600) . 'h';
    }
    if ($diff < 604800) {
      return (int) ($diff / 86400) . 'd';
    }
    return date('Y-m-d', $timestamp);
  }

  // =========================================================================
  // FINANCE DASHBOARD APIS (FASE 4)
  // =========================================================================

  /**
   * GET /api/v1/admin/finance/metrics
   *
   * Métricas financieras completas.
   */
  public function financeMetrics(): JsonResponse {
    try {
      $data = $this->adminCenterFinance->getFinanceDashboardData();

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      $this->logger->error('Finance metrics error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener métricas financieras.', 'FINANCE_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/finance/tenants
   *
   * Analytics por tenant para DataTable financiero.
   */
  public function financeTenantAnalytics(): JsonResponse {
    try {
      $tenants = $this->adminCenterFinance->getTenantAnalytics();

      return $this->apiSuccess($tenants);
    }
    catch (\Exception $e) {
      $this->logger->error('Finance tenant analytics error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener analytics de tenants.', 'FINANCE_TENANTS_ERROR', 500);
    }
  }

  // =========================================================================
  // ALERTS & PLAYBOOKS APIS (FASE 5)
  // =========================================================================

  /**
   * GET /api/v1/admin/alerts
   *
   * Parámetros query:
   *   - severity: critical|warning|info.
   *   - status: open|acknowledged|resolved|dismissed.
   *   - type: alert_type filter.
   *   - q: búsqueda por título.
   *   - limit/offset: paginación.
   */
  public function listAlerts(Request $request): JsonResponse {
    try {
      $alertService = $this->adminCenterAlerts;

      $filters = [
        'severity' => $request->query->get('severity', ''),
        'status' => $request->query->get('status', ''),
        'alert_type' => $request->query->get('type', ''),
        'q' => trim($request->query->get('q', '')),
      ];
      $limit = min((int) $request->query->get('limit', 20), 100);
      $offset = max((int) $request->query->get('offset', 0), 0);

      $result = $alertService->listAlerts($filters, $limit, $offset);

      return $this->apiPaginated($result['items'], $result['total'], $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('Alerts list error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al listar alertas.', 'ALERTS_LIST_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/alerts/summary
   *
   * Resumen de alertas para scorecards.
   */
  public function alertsSummary(): JsonResponse {
    try {
      $alertService = $this->adminCenterAlerts;
      $summary = $alertService->getDashboardSummary();

      return $this->apiSuccess($summary);
    }
    catch (\Exception $e) {
      $this->logger->error('Alerts summary error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener resumen de alertas.', 'ALERTS_SUMMARY_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/admin/alerts/{foc_alert}/state
   *
   * Actualiza el estado de una alerta.
   * Body JSON: { "state": "acknowledged|resolved|dismissed" }
   */
  public function updateAlertState(Request $request, string $foc_alert): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      $newState = $body['state'] ?? '';

      if (!in_array($newState, ['acknowledged', 'resolved', 'dismissed'], TRUE)) {
        return $this->apiError(
          'Estado inválido. Valores permitidos: acknowledged, resolved, dismissed.',
          'INVALID_STATE',
          422,
        );
      }

      $alertService = $this->adminCenterAlerts;
      $success = $alertService->updateAlertState((int) $foc_alert, $newState);

      if (!$success) {
        return $this->apiError('No se pudo actualizar la alerta.', 'UPDATE_FAILED', 404);
      }

      return $this->apiSuccess([
        'id' => (int) $foc_alert,
        'state' => $newState,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Alert state update error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al actualizar estado de alerta.', 'STATE_ERROR', 500);
    }
  }

  /**
   * GET /admin/jaraba/center/alerts/{foc_alert}/panel
   *
   * Detalle de alerta renderizado como HTML para slide-panel.
   */
  public function alertDetailPanel(string $foc_alert): Response {
    try {
      $alertService = $this->adminCenterAlerts;
      $detail = $alertService->getAlertDetail((int) $foc_alert);

      if (!$detail) {
        return new Response('<p>' . $this->t('Alerta no encontrada.') . '</p>', 404);
      }

      $build = [
        '#theme' => 'admin_center_alert_detail',
        '#alert' => $detail,
      ];

      $html = (string) $this->renderer->render($build);
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Alert panel error: @error', ['@error' => $e->getMessage()]);
      return new Response('<p>' . $this->t('Error al cargar el detalle.') . '</p>', 500);
    }
  }

  /**
   * GET /api/v1/admin/playbooks
   *
   * Lista todos los playbooks.
   */
  public function listPlaybooks(): JsonResponse {
    try {
      $alertService = $this->adminCenterAlerts;
      $playbooks = $alertService->listPlaybooks();

      return $this->apiSuccess($playbooks);
    }
    catch (\Exception $e) {
      $this->logger->error('Playbooks list error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al listar playbooks.', 'PLAYBOOKS_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/admin/playbooks/{cs_playbook}/execute
   *
   * Ejecuta un playbook manualmente.
   * Body JSON: { "tenant_id": 123 }
   */
  public function executePlaybook(Request $request, string $cs_playbook): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      $tenantId = isset($body['tenant_id']) ? (int) $body['tenant_id'] : NULL;

      $alertService = $this->adminCenterAlerts;
      $result = $alertService->executePlaybook((int) $cs_playbook, $tenantId);

      if (!$result['success']) {
        return $this->apiError($result['message'], 'EXECUTE_FAILED', 422);
      }

      return $this->apiSuccess($result);
    }
    catch (\Exception $e) {
      $this->logger->error('Playbook execution error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al ejecutar el playbook.', 'EXECUTE_ERROR', 500);
    }
  }

  // =========================================================================
  // ANALYTICS & LOGS APIS (FASE 6)
  // =========================================================================

  /**
   * GET /api/v1/admin/analytics/overview
   *
   * Datos generales de analytics: scorecards, tendencias para Chart.js.
   */
  public function analyticsOverview(): JsonResponse {
    try {
      $analyticsService = $this->adminCenterAnalytics;
      $data = $analyticsService->getAnalyticsOverview();

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      $this->logger->error('Analytics overview error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener analytics.', 'ANALYTICS_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/analytics/ai
   *
   * Resumen de telemetría AI (costes, invocaciones, latencia).
   */
  public function analyticsAi(): JsonResponse {
    try {
      $analyticsService = $this->adminCenterAnalytics;
      $data = $analyticsService->getAiTelemetrySummary();

      return $this->apiSuccess($data);
    }
    catch (\Exception $e) {
      $this->logger->error('AI analytics error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener analytics AI.', 'AI_ANALYTICS_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/logs
   *
   * Logs combinados (audit + system) con filtros y paginación.
   * Parámetros query: source (audit|system|all), severity, q, limit, offset.
   */
  public function listLogs(Request $request): JsonResponse {
    try {
      $analyticsService = $this->adminCenterAnalytics;

      $filters = [
        'source' => $request->query->get('source', 'all'),
        'severity' => $request->query->get('severity', ''),
        'q' => trim($request->query->get('q', '')),
      ];
      $limit = min((int) $request->query->get('limit', 30), 100);
      $offset = max((int) $request->query->get('offset', 0), 0);

      $result = $analyticsService->listLogs($filters, $limit, $offset);

      return $this->apiPaginated($result['items'], $result['total'], $limit, $offset);
    }
    catch (\Exception $e) {
      $this->logger->error('Logs list error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al listar logs.', 'LOGS_ERROR', 500);
    }
  }

  // =========================================================================
  // TENANT SERIALIZATION HELPERS
  // =========================================================================

  /**
   * Serializa un tenant para la lista (resumen).
   */
  protected function serializeTenantSummary(object $group): array {
    $created = $group->get('created')->value ?? 0;

    // Member count.
    $memberCount = 0;
    try {
      $memberCount = (int) $this->entityTypeManager
        ->getStorage('group_relationship')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('gid', $group->id())
        ->condition('plugin_id', 'group_membership')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    return [
      'id' => (int) $group->id(),
      'label' => $group->label(),
      'status' => $group->get('field_subscription_status')->value ?? 'unknown',
      'plan' => $this->getTenantPlanLabel($group),
      'members' => $memberCount,
      'created' => date('Y-m-d', (int) $created),
      'url' => $group->toUrl('canonical')->toString(),
    ];
  }

  /**
   * Serializa un tenant para el detalle 360.
   */
  protected function serializeTenantDetail(object $group): array {
    $summary = $this->serializeTenantSummary($group);

    // Enrich with additional fields.
    $summary['description'] = $group->get('field_description')->value ?? '';
    $summary['vertical'] = $group->get('field_vertical')->entity?->label() ?? '';
    $summary['domain'] = $group->get('field_custom_domain')->value ?? '';
    $summary['created_full'] = date('Y-m-d H:i:s', (int) ($group->get('created')->value ?? 0));

    // Recent members (last 5).
    $summary['recent_members'] = $this->getRecentMembers($group);

    return $summary;
  }

  /**
   * Obtiene el label del plan SaaS del tenant.
   */
  protected function getTenantPlanLabel(object $group): string {
    try {
      $planRef = $group->get('field_saas_plan');
      if (!$planRef->isEmpty()) {
        $plan = $planRef->entity;
        return $plan ? $plan->label() : '';
      }
    }
    catch (\Exception $e) {
      // Field may not exist.
    }
    return '';
  }

  // ===========================================================================
  // SETTINGS APIs (F6 Doc 181 / Spec f104 §FASE 7)
  // ===========================================================================

  /**
   * GET /api/v1/admin/settings
   *
   * Overview completo de configuración para la página Settings.
   */
  public function settingsOverview(): JsonResponse {
    try {
      $settingsService = $this->adminCenterSettings;
      return $this->apiSuccess($settingsService->getSettingsOverview());
    }
    catch (\Exception $e) {
      $this->logger->error('Settings overview error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener configuración.', 'SETTINGS_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/admin/settings/general
   *
   * Guardar configuración general de plataforma.
   * Body JSON: { "platform_name": "...", "support_email": "...", ... }
   */
  public function saveGeneralSettings(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      if (empty($body)) {
        return $this->apiError('Body vacío.', 'EMPTY_BODY', 422);
      }

      $settingsService = $this->adminCenterSettings;
      $settingsService->saveGeneralSettings($body);

      return $this->apiSuccess([
        'saved' => TRUE,
        'settings' => $settingsService->getGeneralSettings(),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Save general settings error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al guardar configuración.', 'SETTINGS_SAVE_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/settings/plans
   *
   * Lista de planes SaaS.
   */
  public function listPlans(): JsonResponse {
    try {
      $settingsService = $this->adminCenterSettings;
      return $this->apiSuccess($settingsService->listPlans());
    }
    catch (\Exception $e) {
      $this->logger->error('List plans error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener planes.', 'PLANS_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/settings/integrations
   *
   * Estado de integraciones externas.
   */
  public function listIntegrations(): JsonResponse {
    try {
      $settingsService = $this->adminCenterSettings;
      return $this->apiSuccess($settingsService->getIntegrationsStatus());
    }
    catch (\Exception $e) {
      $this->logger->error('List integrations error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener integraciones.', 'INTEGRATIONS_ERROR', 500);
    }
  }

  /**
   * GET /api/v1/admin/settings/api-keys
   *
   * Lista de API keys (sin hash expuesto).
   */
  public function listApiKeys(): JsonResponse {
    try {
      $settingsService = $this->adminCenterSettings;
      $keys = $settingsService->listApiKeys();

      // Strip sensitive fields.
      $safe = array_map(function ($k) {
        unset($k['key_hash']);
        return $k;
      }, $keys);

      return $this->apiSuccess($safe);
    }
    catch (\Exception $e) {
      $this->logger->error('List API keys error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al obtener API keys.', 'APIKEYS_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/admin/settings/api-keys
   *
   * Crear nueva API key.
   * Body JSON: { "label": "...", "scope": "read|write|admin" }
   */
  public function createApiKey(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      $label = trim($body['label'] ?? '');
      $scope = $body['scope'] ?? 'read';

      if (empty($label)) {
        return $this->apiError('El label es obligatorio.', 'MISSING_LABEL', 422);
      }

      $settingsService = $this->adminCenterSettings;
      $record = $settingsService->createApiKey($label, $scope);

      return $this->apiSuccess($record);
    }
    catch (\Exception $e) {
      $this->logger->error('Create API key error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al crear API key.', 'APIKEY_CREATE_ERROR', 500);
    }
  }

  /**
   * POST /api/v1/admin/settings/api-keys/{key_id}/revoke
   *
   * Revocar una API key.
   */
  public function revokeApiKey(string $key_id): JsonResponse {
    try {
      $settingsService = $this->adminCenterSettings;
      $revoked = $settingsService->revokeApiKey($key_id);

      if (!$revoked) {
        return $this->apiError('API key no encontrada o ya revocada.', 'APIKEY_NOT_FOUND', 404);
      }

      return $this->apiSuccess(['revoked' => TRUE]);
    }
    catch (\Exception $e) {
      $this->logger->error('Revoke API key error: @error', ['@error' => $e->getMessage()]);
      return $this->apiError('Error al revocar API key.', 'APIKEY_REVOKE_ERROR', 500);
    }
  }

  /**
   * Obtiene los últimos 5 miembros del tenant.
   */
  protected function getRecentMembers(object $group): array {
    $members = [];
    try {
      $membershipStorage = $this->entityTypeManager->getStorage('group_relationship');
      $ids = $membershipStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('gid', $group->id())
        ->condition('plugin_id', 'group_membership')
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      foreach ($membershipStorage->loadMultiple($ids) as $membership) {
        $user = $membership->get('entity_id')->entity;
        if ($user) {
          $members[] = [
            'uid' => (int) $user->id(),
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }
    return $members;
  }

}
