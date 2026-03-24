<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * SSOT for program role detection and management in Andalucía +ei.
 *
 * Replaces the fragmented detection in AccesoProgramaService (Drupal roles
 * that didn't exist) and AndaluciaEiUserProfileSection (entity-based).
 *
 * Detection cascade:
 * 1. Drupal role coordinador_ei → 'coordinador'
 * 2. Drupal role orientador_ei → 'orientador'
 * 3. Drupal role formador_ei → 'formador'
 * 4. Permission 'administer andalucia ei' → 'coordinador' (backward compat)
 * 5. Entity programa_participante_ei (alumni or active) → 'alumni' or 'participante'
 * 6. None → 'none'
 *
 * TENANT-001: All entity queries filter by tenant_id.
 */
class RolProgramaService implements RolProgramaServiceInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getRolProgramaUsuario(AccountInterface $account): string {
    try {
      $roles = $account->getRoles();

      // 1-3. Drupal role detection (priority order).
      if (in_array('coordinador_ei', $roles, TRUE)) {
        return self::ROL_COORDINADOR;
      }
      if (in_array('orientador_ei', $roles, TRUE)) {
        return self::ROL_ORIENTADOR;
      }
      if (in_array('formador_ei', $roles, TRUE)) {
        return self::ROL_FORMADOR;
      }

      // 4. Legacy backward compatibility: users with admin permission
      // but without the coordinador_ei role (pre-migration).
      if ($account->hasPermission('administer andalucia ei')) {
        return self::ROL_COORDINADOR;
      }

      // 5. Entity-based detection for participante/alumni.
      return $this->detectarRolParticipante($account);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error detecting program role for uid @uid: @msg', [
        '@uid' => $account->id(),
        '@msg' => $e->getMessage(),
      ]);
      return self::ROL_NONE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tieneRol(AccountInterface $account, string $rol): bool {
    return $this->getRolProgramaUsuario($account) === $rol;
  }

  /**
   * {@inheritdoc}
   */
  public function esStaff(AccountInterface $account): bool {
    return in_array($this->getRolProgramaUsuario($account), self::ROLES_STAFF, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function asignarRol(int $uid, string $rol, string $motivo = ''): bool {
    if (!in_array($rol, self::ROLES_STAFF, TRUE)) {
      throw new \InvalidArgumentException("Invalid staff role: $rol. Must be one of: " . implode(', ', self::ROLES_STAFF));
    }

    try {
      $drupalRoleId = self::ROL_DRUPAL_MAP[$rol];

      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user === NULL) {
        $this->logger->error('Cannot assign role @rol: user @uid not found.', [
          '@rol' => $rol,
          '@uid' => $uid,
        ]);
        return FALSE;
      }

      // Assign the Drupal role.
      if (!$user->hasRole($drupalRoleId)) {
        $user->addRole($drupalRoleId);
        $user->save();
      }

      // Log the assignment for FSE+ audit.
      $this->logRolChange($uid, $rol, 'asignar', $motivo);

      $this->logger->info('Program role @rol assigned to user @uid by @by. Reason: @motivo', [
        '@rol' => $rol,
        '@uid' => $uid,
        '@by' => $this->currentUser->id(),
        '@motivo' => $motivo !== '' ? $motivo : '(none)',
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error assigning role @rol to user @uid: @msg', [
        '@rol' => $rol,
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function revocarRol(int $uid, string $rol, string $motivo = ''): bool {
    if (!in_array($rol, self::ROLES_STAFF, TRUE)) {
      throw new \InvalidArgumentException("Invalid staff role: $rol");
    }

    try {
      $drupalRoleId = self::ROL_DRUPAL_MAP[$rol];

      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user === NULL) {
        return FALSE;
      }

      if ($user->hasRole($drupalRoleId)) {
        $user->removeRole($drupalRoleId);
        $user->save();
      }

      $this->logRolChange($uid, $rol, 'revocar', $motivo);

      $this->logger->info('Program role @rol revoked from user @uid by @by. Reason: @motivo', [
        '@rol' => $rol,
        '@uid' => $uid,
        '@by' => $this->currentUser->id(),
        '@motivo' => $motivo !== '' ? $motivo : '(none)',
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error revoking role @rol from user @uid: @msg', [
        '@rol' => $rol,
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUsuariosPorRol(string $rol): array {
    try {
      if (!isset(self::ROL_DRUPAL_MAP[$rol])) {
        return [];
      }

      $drupalRoleId = self::ROL_DRUPAL_MAP[$rol];
      $userIds = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', $drupalRoleId)
        ->condition('status', 1)
        ->execute();

      if ($userIds === []) {
        return [];
      }

      return $this->entityTypeManager->getStorage('user')->loadMultiple($userIds);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading users by role @rol: @msg', [
        '@rol' => $rol,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Detects participante or alumni role from entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return string
   *   ROL_PARTICIPANTE, ROL_ALUMNI, or ROL_NONE.
   */
  protected function detectarRolParticipante(AccountInterface $account): string {
    if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
      return self::ROL_NONE;
    }

    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $account->id())
      ->range(0, 1);

    // TENANT-001: Filter by tenant if available.
    $tenantId = $this->resolverTenantId();
    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    $ids = $query->execute();
    if ($ids === []) {
      return self::ROL_NONE;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $participante */
    $participante = $storage->load(reset($ids));
    if ($participante === NULL) {
      return self::ROL_NONE;
    }

    $fase = $participante->get('fase_actual')->value ?? 'acogida';

    if ($fase === 'alumni') {
      return self::ROL_ALUMNI;
    }
    if ($fase === 'baja') {
      return self::ROL_NONE;
    }

    return self::ROL_PARTICIPANTE;
  }

  /**
   * Resolves the current tenant ID.
   *
   * @return int|null
   *   The tenant ID or NULL if unavailable.
   */
  protected function resolverTenantId(): ?int {
    if ($this->tenantContext !== NULL && method_exists($this->tenantContext, 'getCurrentTenantId')) {
      $tenantId = $this->tenantContext->getCurrentTenantId();
      return $tenantId !== NULL && $tenantId !== 0 ? (int) $tenantId : NULL;
    }
    return NULL;
  }

  /**
   * Logs a role change to the rol_programa_log entity (if available).
   *
   * PRESAVE-RESILIENCE-001: Uses hasDefinition + try-catch for optional entity.
   *
   * @param int $uid
   *   User affected.
   * @param string $rol
   *   Program role.
   * @param string $accion
   *   'asignar' or 'revocar'.
   * @param string $motivo
   *   Reason.
   */
  protected function logRolChange(int $uid, string $rol, string $accion, string $motivo): void {
    try {
      if (!$this->entityTypeManager->hasDefinition('rol_programa_log')) {
        // Entity not yet installed — log to watchdog instead.
        $this->logger->info('Role change (entity not available): @accion @rol for uid @uid', [
          '@accion' => $accion,
          '@rol' => $rol,
          '@uid' => $uid,
        ]);
        return;
      }

      $logStorage = $this->entityTypeManager->getStorage('rol_programa_log');
      $log = $logStorage->create([
        'user_id' => $uid,
        'assigned_by' => $this->currentUser->id(),
        'rol_programa' => $rol,
        'accion' => $accion,
        'motivo' => $motivo,
        'tenant_id' => $this->resolverTenantId(),
      ]);
      $log->save();
    }
    catch (\Throwable $e) {
      // Non-blocking: audit failure should not prevent role assignment.
      $this->logger->warning('Failed to log role change: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
