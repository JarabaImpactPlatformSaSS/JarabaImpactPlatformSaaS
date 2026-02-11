<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para gestionar impersonación de usuarios (Login as).
 *
 * Permite a administradores autenticarse temporalmente como
 * usuarios de tenants para diagnóstico y soporte.
 *
 * SEGURIDAD:
 * - Requiere permiso 'impersonate tenants'
 * - Todos los eventos se registran en ImpersonationAuditLog
 * - Sesión máxima de 30 minutos
 * - No permite impersonar a superadmins
 */
class ImpersonationService
{

    /**
     * Duración máxima de sesión de impersonación (segundos).
     */
    protected const MAX_SESSION_DURATION = 1800; // 30 minutos

    /**
     * Clave para almacenar datos de sesión original.
     */
    protected const SESSION_KEY = 'impersonation_original_session';

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected SessionManagerInterface $sessionManager,
        protected PrivateTempStoreFactory $tempStoreFactory,
        protected RequestStack $requestStack,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Inicia sesión de impersonación.
     *
     * @param int $targetUid
     *   ID del usuario objetivo.
     * @param string $reason
     *   Razón de la impersonación (opcional).
     *
     * @return array
     *   Resultado con success, message, redirect_url.
     */
    public function startSession(int $targetUid, string $reason = ''): array
    {
        $adminUid = (int) $this->currentUser->id();

        // Validar que no estamos ya en una sesión de impersonación.
        if ($this->isImpersonating()) {
            return [
                'success' => FALSE,
                'error' => t('Ya existe una sesión de impersonación activa. Termínala antes de iniciar otra.'),
            ];
        }

        // Cargar usuario objetivo.
        $targetUser = $this->entityTypeManager->getStorage('user')->load($targetUid);
        if (!$targetUser) {
            return [
                'success' => FALSE,
                'error' => t('Usuario no encontrado.'),
            ];
        }

        // No permitir impersonar a superadmins (uid 1).
        if ($targetUid === 1) {
            return [
                'success' => FALSE,
                'error' => t('No se puede impersonar al superadministrador.'),
            ];
        }

        // Guardar sesión original.
        $tempStore = $this->tempStoreFactory->get('impersonation');
        $tempStore->set(self::SESSION_KEY, [
            'admin_uid' => $adminUid,
            'started_at' => time(),
            'target_uid' => $targetUid,
        ]);

        // Registrar evento de inicio.
        $this->logEvent($adminUid, $targetUid, 'start', $reason);

        // Cambiar a usuario objetivo.
        $this->switchToUser($targetUid);

        $this->logger->notice('Impersonation started: Admin @admin → User @target', [
            '@admin' => $adminUid,
            '@target' => $targetUid,
        ]);

        return [
            'success' => TRUE,
            'message' => t('Sesión de impersonación iniciada. Recuerda salir cuando termines.'),
            'target_name' => $targetUser->getDisplayName(),
            'expires_in' => self::MAX_SESSION_DURATION,
        ];
    }

    /**
     * Termina sesión de impersonación.
     *
     * @return array
     *   Resultado con success y message.
     */
    public function endSession(): array
    {
        if (!$this->isImpersonating()) {
            return [
                'success' => FALSE,
                'error' => t('No hay sesión de impersonación activa.'),
            ];
        }

        $tempStore = $this->tempStoreFactory->get('impersonation');
        $sessionData = $tempStore->get(self::SESSION_KEY);

        $adminUid = $sessionData['admin_uid'];
        $targetUid = $sessionData['target_uid'];
        $startedAt = $sessionData['started_at'];
        $duration = time() - $startedAt;

        // Registrar evento de fin.
        $this->logEvent($adminUid, $targetUid, 'end', '', $duration);

        // Limpiar datos de sesión.
        $tempStore->delete(self::SESSION_KEY);

        // Restaurar sesión original.
        $this->switchToUser($adminUid);

        $this->logger->notice('Impersonation ended: Admin @admin (duration: @duration seconds)', [
            '@admin' => $adminUid,
            '@duration' => $duration,
        ]);

        return [
            'success' => TRUE,
            'message' => t('Sesión de impersonación terminada. Has vuelto a tu cuenta de administrador.'),
            'duration' => $duration,
        ];
    }

    /**
     * Verifica si hay una sesión de impersonación activa.
     */
    public function isImpersonating(): bool
    {
        $tempStore = $this->tempStoreFactory->get('impersonation');
        $sessionData = $tempStore->get(self::SESSION_KEY);

        if (!$sessionData) {
            return FALSE;
        }

        // Verificar timeout automático.
        $elapsed = time() - $sessionData['started_at'];
        if ($elapsed > self::MAX_SESSION_DURATION) {
            $this->handleTimeout($sessionData);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Obtiene datos de la sesión de impersonación actual.
     */
    public function getCurrentSessionData(): ?array
    {
        if (!$this->isImpersonating()) {
            return NULL;
        }

        $tempStore = $this->tempStoreFactory->get('impersonation');
        $sessionData = $tempStore->get(self::SESSION_KEY);

        $elapsed = time() - $sessionData['started_at'];
        $remaining = self::MAX_SESSION_DURATION - $elapsed;

        $adminUser = $this->entityTypeManager->getStorage('user')->load($sessionData['admin_uid']);

        return [
            'admin_uid' => $sessionData['admin_uid'],
            'admin_name' => $adminUser ? $adminUser->getDisplayName() : 'Admin',
            'target_uid' => $sessionData['target_uid'],
            'started_at' => $sessionData['started_at'],
            'elapsed' => $elapsed,
            'remaining' => max(0, $remaining),
        ];
    }

    /**
     * Maneja el timeout automático de sesión.
     */
    protected function handleTimeout(array $sessionData): void
    {
        $duration = time() - $sessionData['started_at'];

        // Registrar evento de timeout.
        $this->logEvent(
            $sessionData['admin_uid'],
            $sessionData['target_uid'],
            'timeout',
            '',
            $duration
        );

        // Limpiar tempstore.
        $tempStore = $this->tempStoreFactory->get('impersonation');
        $tempStore->delete(self::SESSION_KEY);

        // Restaurar usuario admin.
        $this->switchToUser($sessionData['admin_uid']);

        $this->logger->warning('Impersonation timeout: Admin @admin after @duration seconds', [
            '@admin' => $sessionData['admin_uid'],
            '@duration' => $duration,
        ]);
    }

    /**
     * Cambia al usuario especificado.
     */
    protected function switchToUser(int $uid): void
    {
        $user = $this->entityTypeManager->getStorage('user')->load($uid);
        if ($user) {
            $this->currentUser->setAccount($user);
        }
    }

    /**
     * Registra un evento de impersonación en el audit log.
     */
    protected function logEvent(
        int $adminUid,
        int $targetUid,
        string $eventType,
        string $reason = '',
        int $duration = 0
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        // Obtener tenant del usuario objetivo.
        $tenantId = $this->getTenantForUser($targetUid);

        $logEntry = $this->entityTypeManager->getStorage('impersonation_audit_log')->create([
            'admin_uid' => $adminUid,
            'target_uid' => $targetUid,
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'session_duration' => $duration,
            'admin_ip' => $request ? $request->getClientIp() : '',
            'user_agent' => $request ? $request->headers->get('User-Agent', '') : '',
            'reason' => $reason,
        ]);

        $logEntry->save();
    }

    /**
     * Obtiene el tenant asociado a un usuario.
     */
    protected function getTenantForUser(int $uid): ?int
    {
        // Buscar tenant donde el usuario es miembro.
        $query = $this->entityTypeManager->getStorage('tenant')->getQuery()
            ->accessCheck(FALSE)
            ->condition('user_id', $uid)
            ->range(0, 1);

        $ids = $query->execute();
        return $ids ? (int) reset($ids) : NULL;
    }

}
