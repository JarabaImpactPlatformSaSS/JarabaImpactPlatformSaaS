<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona acceso y elegibilidad de usuarios al programa PIIL CV 2025.
 *
 * Determina permisos de acceso a portales (participante, coordinador,
 * orientador) y resuelve el rol activo dentro del programa.
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class AccesoProgramaService {

  /**
   * Roles del programa.
   */
  private const ROLES_PROGRAMA = ['participante', 'coordinador', 'orientador', 'formador', 'alumni', 'none'];

  /**
   * Constructs an AccesoProgramaService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Determina si el usuario puede acceder al portal de participante.
   *
   * Requisito: tener un registro activo (no baja) como participante.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario a evaluar.
   *
   * @return bool
   *   TRUE si puede acceder al portal de participante.
   */
  public function puedeAccederPortalParticipante(AccountInterface $account): bool {
    try {
      $participante = $this->getParticipanteActivo($account);
      return $participante !== NULL;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error verificando acceso portal participante uid @uid: @msg', [
        '@uid' => $account->id(),
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Determina si el usuario puede acceder al dashboard de coordinador.
   *
   * Requisito: permiso 'administer andalucia ei' o 'view programa participante ei'.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario a evaluar.
   *
   * @return bool
   *   TRUE si puede acceder al dashboard de coordinador.
   */
  public function puedeAccederDashboardCoordinador(AccountInterface $account): bool {
    return $account->hasPermission('administer andalucia ei')
      || $account->hasPermission('view programa participante ei');
  }

  /**
   * Determina si el usuario puede acceder al dashboard de orientador.
   *
   * Requisito: rol 'orientador_ei' o permiso 'view programa participante ei'.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario a evaluar.
   *
   * @return bool
   *   TRUE si puede acceder al dashboard de orientador.
   */
  public function puedeAccederDashboardOrientador(AccountInterface $account): bool {
    try {
      $roles = $account->getRoles();
      if (in_array('orientador_ei', $roles, TRUE)) {
        return TRUE;
      }

      return $account->hasPermission('view programa participante ei');
    }
    catch (\Throwable $e) {
      $this->logger->error('Error verificando acceso orientador uid @uid: @msg', [
        '@uid' => $account->id(),
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Carga el participante activo asociado a una cuenta de usuario.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario.
   * @param int|null $tenantId
   *   ID del tenant, o NULL para resolver automaticamente.
   *
   * @return object|null
   *   La entidad programa_participante_ei activa, o NULL si no existe.
   */
  public function getParticipanteActivo(AccountInterface $account, ?int $tenantId = NULL): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', (int) $account->id())
        ->condition('fase_actual', 'baja', '!=')
        ->range(0, 1);

      // Resolver tenant: parametro explicito > servicio de contexto.
      $resolvedTenant = $tenantId;
      if (!$resolvedTenant && $this->tenantContext && method_exists($this->tenantContext, 'getCurrentTenantId')) {
        $resolvedTenant = $this->tenantContext->getCurrentTenantId();
      }

      if ($resolvedTenant) {
        $query->condition('tenant_id', $resolvedTenant);
      }

      $ids = $query->execute();
      if (empty($ids)) {
        return NULL;
      }

      return $storage->load(reset($ids));
    }
    catch (\Throwable $e) {
      $this->logger->error('Error cargando participante activo uid @uid: @msg', [
        '@uid' => $account->id(),
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Determina el rol del usuario dentro del programa.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   La cuenta de usuario a evaluar.
   *
   * @return string
   *   Uno de: 'participante', 'coordinador', 'orientador', 'formador', 'alumni', 'none'.
   */
  public function getRolProgramaUsuario(AccountInterface $account): string {
    try {
      // Coordinador: permiso administrativo.
      if ($account->hasPermission('administer andalucia ei')) {
        return 'coordinador';
      }

      // Orientador: rol especifico.
      $roles = $account->getRoles();
      if (in_array('orientador_ei', $roles, TRUE)) {
        return 'orientador';
      }

      // Formador: rol especifico.
      if (in_array('formador_ei', $roles, TRUE)) {
        return 'formador';
      }

      // Participante o alumni: buscar registro.
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', (int) $account->id())
        ->range(0, 1);

      // Filtrar por tenant si disponible.
      if ($this->tenantContext && method_exists($this->tenantContext, 'getCurrentTenantId')) {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId) {
          $query->condition('tenant_id', $tenantId);
        }
      }

      $ids = $query->execute();
      if (empty($ids)) {
        return 'none';
      }

      $participante = $storage->load(reset($ids));
      if (!$participante) {
        return 'none';
      }

      $fase = $participante->get('fase_actual')->value ?? 'acogida';

      // Alumni: fase completada o seguimiento finalizado.
      if ($fase === 'alumni') {
        return 'alumni';
      }

      // Baja: no tiene rol activo.
      if ($fase === 'baja') {
        return 'none';
      }

      return 'participante';
    }
    catch (\Throwable $e) {
      $this->logger->error('Error resolviendo rol programa uid @uid: @msg', [
        '@uid' => $account->id(),
        '@msg' => $e->getMessage(),
      ]);
      return 'none';
    }
  }

}
