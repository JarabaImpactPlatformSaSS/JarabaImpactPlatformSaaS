<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * SSOT interface for program role detection and management.
 *
 * Centralizes all role detection, assignment, and revocation for the
 * Andalucía +ei program. Replaces the fragmented logic that was spread
 * across AccesoProgramaService and AndaluciaEiUserProfileSection.
 *
 * Detection cascade:
 * 1. Drupal role (coordinador_ei > orientador_ei > formador_ei)
 * 2. Legacy permission fallback (administer andalucia ei → coordinador)
 * 3. Entity-based (programa_participante_ei → participante/alumni)
 * 4. None → 'none'
 *
 * TENANT-001: All queries filter by tenant_id.
 */
interface RolProgramaServiceInterface {

  /**
   * Program role constants.
   */
  public const ROL_COORDINADOR = 'coordinador';
  public const ROL_ORIENTADOR = 'orientador';
  public const ROL_FORMADOR = 'formador';
  public const ROL_PARTICIPANTE = 'participante';
  public const ROL_ALUMNI = 'alumni';
  public const ROL_NONE = 'none';

  /**
   * Staff (professional) roles.
   */
  public const ROLES_STAFF = [
    self::ROL_COORDINADOR,
    self::ROL_ORIENTADOR,
    self::ROL_FORMADOR,
  ];

  /**
   * All valid program roles.
   */
  public const ROLES_ALL = [
    self::ROL_COORDINADOR,
    self::ROL_ORIENTADOR,
    self::ROL_FORMADOR,
    self::ROL_PARTICIPANTE,
    self::ROL_ALUMNI,
    self::ROL_NONE,
  ];

  /**
   * Mapping: program role → Drupal role machine name.
   */
  public const ROL_DRUPAL_MAP = [
    self::ROL_COORDINADOR => 'coordinador_ei',
    self::ROL_ORIENTADOR => 'orientador_ei',
    self::ROL_FORMADOR => 'formador_ei',
  ];

  /**
   * Determines the user's primary role in the program.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to evaluate.
   *
   * @return string
   *   One of the ROL_* constants.
   */
  public function getRolProgramaUsuario(AccountInterface $account): string;

  /**
   * Checks if the user has a specific program role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $rol
   *   ROL_* constant to check.
   *
   * @return bool
   *   TRUE if the user has the indicated role.
   */
  public function tieneRol(AccountInterface $account, string $rol): bool;

  /**
   * Checks if the user is staff (coordinador, orientador, or formador).
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if user has any professional role.
   */
  public function esStaff(AccountInterface $account): bool;

  /**
   * Assigns a program role to a user.
   *
   * Creates the Drupal role assignment and logs the action for audit.
   * Only staff roles can be assigned (coordinador, orientador, formador).
   *
   * @param int $uid
   *   User ID.
   * @param string $rol
   *   ROL_* constant (must be in ROLES_STAFF).
   * @param string $motivo
   *   Reason for assignment (for FSE+ audit trail).
   *
   * @return bool
   *   TRUE if assigned successfully.
   *
   * @throws \InvalidArgumentException
   *   If the role is not a valid staff role.
   */
  public function asignarRol(int $uid, string $rol, string $motivo = ''): bool;

  /**
   * Revokes a program role from a user.
   *
   * @param int $uid
   *   User ID.
   * @param string $rol
   *   ROL_* constant (must be in ROLES_STAFF).
   * @param string $motivo
   *   Reason for revocation (for FSE+ audit trail).
   *
   * @return bool
   *   TRUE if revoked successfully.
   */
  public function revocarRol(int $uid, string $rol, string $motivo = ''): bool;

  /**
   * Gets all users with a specific role in the current tenant.
   *
   * @param string $rol
   *   ROL_* constant to search for.
   *
   * @return \Drupal\user\UserInterface[]
   *   Array of user entities with the indicated role.
   */
  public function getUsuariosPorRol(string $rol): array;

}
