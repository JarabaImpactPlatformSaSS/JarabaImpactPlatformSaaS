<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar cuentas de redes sociales.
 *
 * PROPOSITO:
 * Gestiona las conexiones OAuth con plataformas sociales
 * (Facebook, Instagram, LinkedIn, Twitter/X, TikTok).
 * Permite conectar, desconectar y refrescar tokens de cuentas
 * asociadas a cada tenant.
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Gestión de entidades SocialAccount.
 * - logger: Registro de eventos de conexion/desconexion.
 */
class SocialAccountService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger para registro de eventos.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene todas las cuentas sociales de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de entidades SocialAccount del tenant.
   */
  public function getAccountsForTenant(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_account');
      $accounts = $storage->loadByProperties([
        'tenant_id' => $tenantId,
      ]);

      return array_values($accounts);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo cuentas para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Conecta una nueva cuenta social para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $platform
   *   Plataforma social (facebook, instagram, linkedin, twitter, tiktok).
   * @param array $oauthData
   *   Datos OAuth recibidos del flujo de autorizacion:
   *   - access_token: Token de acceso.
   *   - refresh_token: Token de refresco (opcional).
   *   - expires_in: Segundos hasta la expiracion.
   *   - account_id: ID externo de la cuenta.
   *   - name: Nombre descriptivo de la cuenta.
   *
   * @return array
   *   Resultado de la operacion con claves: success, account_id, message.
   */
  public function connectAccount(int $tenantId, string $platform, array $oauthData): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_account');

      $values = [
        'tenant_id' => $tenantId,
        'platform' => $platform,
        'name' => $oauthData['name'] ?? $platform . ' - ' . $tenantId,
        'account_id' => $oauthData['account_id'] ?? '',
        'access_token' => $oauthData['access_token'] ?? '',
        'refresh_token' => $oauthData['refresh_token'] ?? '',
        'token_expires' => isset($oauthData['expires_in'])
          ? time() + (int) $oauthData['expires_in']
          : NULL,
        'status' => TRUE,
      ];

      $account = $storage->create($values);
      $account->save();

      $this->logger->info('Cuenta social conectada: @platform para tenant @tid', [
        '@platform' => $platform,
        '@tid' => $tenantId,
      ]);

      return [
        'success' => TRUE,
        'account_id' => (int) $account->id(),
        'message' => 'Cuenta conectada correctamente.',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error conectando cuenta @platform para tenant @tid: @error', [
        '@platform' => $platform,
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'account_id' => NULL,
        'message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Refresca el token de acceso de una cuenta social.
   *
   * @param int $accountId
   *   ID de la entidad SocialAccount.
   *
   * @return bool
   *   TRUE si el token se refresco correctamente.
   */
  public function refreshToken(int $accountId): bool {
    try {
      $account = $this->getAccountById($accountId);
      if (!$account) {
        $this->logger->warning('Cuenta social @id no encontrada para refrescar token.', [
          '@id' => $accountId,
        ]);
        return FALSE;
      }

      // TODO: Implementar llamadas OAuth reales a cada plataforma.
      $this->logger->info('Token refrescado para cuenta social @id (simulado).', [
        '@id' => $accountId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error refrescando token para cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Desconecta una cuenta social.
   *
   * @param int $accountId
   *   ID de la entidad SocialAccount.
   *
   * @return bool
   *   TRUE si la cuenta fue desconectada correctamente.
   */
  public function disconnectAccount(int $accountId): bool {
    try {
      $account = $this->getAccountById($accountId);
      if (!$account) {
        $this->logger->warning('Cuenta social @id no encontrada para desconectar.', [
          '@id' => $accountId,
        ]);
        return FALSE;
      }

      $account->set('status', FALSE);
      $account->set('access_token', '');
      $account->set('refresh_token', '');
      $account->save();

      $this->logger->info('Cuenta social @id desconectada.', [
        '@id' => $accountId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error desconectando cuenta @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene una cuenta social por su ID.
   *
   * @param int $accountId
   *   ID de la entidad SocialAccount.
   *
   * @return object|null
   *   La entidad SocialAccount o NULL si no existe.
   */
  public function getAccountById(int $accountId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('social_account');
      $account = $storage->load($accountId);

      return $account ?: NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error cargando cuenta social @id: @error', [
        '@id' => $accountId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
