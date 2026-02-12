<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\Badge;
use Drupal\ecosistema_jaraba_core\Entity\BadgeAward;
use Psr\Log\LoggerInterface;

/**
 * Servicio de otorgamiento de insignias (gamificación).
 *
 * Gestiona la asignación, revocación y consulta de insignias de usuarios.
 * Soporta otorgamiento automático basado en criterios configurados en
 * cada Badge (event_count, first_action, streak) y otorgamiento manual.
 *
 * Previene duplicados: un usuario no puede recibir la misma insignia dos veces
 * (excepto si se revoca y se vuelve a otorgar).
 */
class BadgeAwardService
{

    /**
     * Gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Canal de logging.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   Gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   Usuario actual.
     * @param \Psr\Log\LoggerInterface $logger
     *   Canal de logging.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerInterface $logger,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Otorga una insignia a un usuario.
     *
     * Verifica que no exista un otorgamiento previo de la misma insignia
     * al mismo usuario antes de crear uno nuevo.
     *
     * @param int $badgeId
     *   ID de la insignia a otorgar.
     * @param int $userId
     *   ID del usuario que recibe la insignia.
     * @param string $reason
     *   Motivo del otorgamiento (opcional).
     * @param int|null $tenantId
     *   ID del tenant en cuyo contexto se otorga (opcional).
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\BadgeAward|null
     *   La entidad BadgeAward creada, o NULL si ya existe o hay error.
     */
    public function awardBadge(int $badgeId, int $userId, string $reason = '', ?int $tenantId = NULL): ?BadgeAward
    {
        // Verificar que la insignia existe y está activa.
        $badge = $this->loadBadge($badgeId);
        if (!$badge || !$badge->isActive()) {
            $this->logger->warning('Intento de otorgar insignia inexistente o inactiva: @id', [
                '@id' => $badgeId,
            ]);
            return NULL;
        }

        // Verificar duplicados.
        if ($this->hasUserBadge($badgeId, $userId)) {
            $this->logger->info('El usuario @uid ya tiene la insignia @badge. No se otorga duplicado.', [
                '@uid' => $userId,
                '@badge' => $badge->getName(),
            ]);
            return NULL;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('badge_award');
            $values = [
                'badge_id' => $badgeId,
                'user_id' => $userId,
                'awarded_reason' => $reason,
            ];

            if ($tenantId !== NULL) {
                $values['tenant_id'] = $tenantId;
            }

            /** @var \Drupal\ecosistema_jaraba_core\Entity\BadgeAward $award */
            $award = $storage->create($values);
            $award->save();

            $this->logger->info('Insignia "@badge" otorgada al usuario @uid. Motivo: @reason', [
                '@badge' => $badge->getName(),
                '@uid' => $userId,
                '@reason' => $reason ?: '(sin motivo)',
            ]);

            return $award;
        }
        catch (\Exception $e) {
            $this->logger->error('Error al otorgar insignia @badge al usuario @uid: @error', [
                '@badge' => $badgeId,
                '@uid' => $userId,
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Revoca una insignia de un usuario.
     *
     * Elimina el registro de otorgamiento de la insignia al usuario.
     *
     * @param int $badgeId
     *   ID de la insignia a revocar.
     * @param int $userId
     *   ID del usuario al que se revoca.
     *
     * @return bool
     *   TRUE si se revocó correctamente, FALSE si no existía o hubo error.
     */
    public function revokeBadge(int $badgeId, int $userId): bool
    {
        try {
            $storage = $this->entityTypeManager->getStorage('badge_award');
            $awards = $storage->loadByProperties([
                'badge_id' => $badgeId,
                'user_id' => $userId,
            ]);

            if (empty($awards)) {
                return FALSE;
            }

            $storage->delete($awards);

            $this->logger->info('Insignia @badge revocada al usuario @uid.', [
                '@badge' => $badgeId,
                '@uid' => $userId,
            ]);

            return TRUE;
        }
        catch (\Exception $e) {
            $this->logger->error('Error al revocar insignia @badge del usuario @uid: @error', [
                '@badge' => $badgeId,
                '@uid' => $userId,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Obtiene todas las insignias otorgadas a un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int|null $tenantId
     *   Filtrar por tenant (opcional).
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\BadgeAward[]
     *   Array de entidades BadgeAward.
     */
    public function getUserBadges(int $userId, ?int $tenantId = NULL): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('badge_award');
            $properties = ['user_id' => $userId];

            if ($tenantId !== NULL) {
                $properties['tenant_id'] = $tenantId;
            }

            return $storage->loadByProperties($properties);
        }
        catch (\Exception $e) {
            $this->logger->error('Error al obtener insignias del usuario @uid: @error', [
                '@uid' => $userId,
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calcula la suma total de puntos de insignias de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int|null $tenantId
     *   Filtrar por tenant (opcional).
     *
     * @return int
     *   Total de puntos acumulados.
     */
    public function getUserPoints(int $userId, ?int $tenantId = NULL): int
    {
        $awards = $this->getUserBadges($userId, $tenantId);
        $totalPoints = 0;

        foreach ($awards as $award) {
            $badge = $award->getBadge();
            if ($badge) {
                $totalPoints += $badge->getPoints();
            }
        }

        return $totalPoints;
    }

    /**
     * Evalúa y otorga automáticamente insignias según un evento.
     *
     * Recorre todas las insignias activas, compara sus criterios con el
     * evento recibido y otorga las que correspondan.
     *
     * @param int $userId
     *   ID del usuario que disparó el evento.
     * @param string $eventType
     *   Tipo de evento (ej: 'login', 'profile_complete', 'first_sale').
     * @param array $eventData
     *   Datos adicionales del evento (ej: ['count' => 10]).
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\BadgeAward[]
     *   Array de BadgeAwards recién otorgados.
     */
    public function checkAndAwardBadges(int $userId, string $eventType, array $eventData = []): array
    {
        $awarded = [];

        try {
            $badgeStorage = $this->entityTypeManager->getStorage('badge');
            $badges = $badgeStorage->loadByProperties(['active' => TRUE]);

            foreach ($badges as $badge) {
                /** @var \Drupal\ecosistema_jaraba_core\Entity\Badge $badge */
                if ($this->matchesCriteria($badge, $eventType, $eventData)) {
                    $tenantId = $eventData['tenant_id'] ?? NULL;
                    $reason = sprintf('Auto-otorgada por evento: %s', $eventType);
                    $award = $this->awardBadge(
                        (int) $badge->id(),
                        $userId,
                        $reason,
                        $tenantId,
                    );

                    if ($award !== NULL) {
                        $awarded[] = $award;
                    }
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Error al evaluar insignias para usuario @uid, evento @event: @error', [
                '@uid' => $userId,
                '@event' => $eventType,
                '@error' => $e->getMessage(),
            ]);
        }

        return $awarded;
    }

    /**
     * Verifica si un usuario ya tiene una insignia específica.
     *
     * @param int $badgeId
     *   ID de la insignia.
     * @param int $userId
     *   ID del usuario.
     *
     * @return bool
     *   TRUE si el usuario ya tiene la insignia.
     */
    protected function hasUserBadge(int $badgeId, int $userId): bool
    {
        try {
            $storage = $this->entityTypeManager->getStorage('badge_award');
            $existing = $storage->loadByProperties([
                'badge_id' => $badgeId,
                'user_id' => $userId,
            ]);
            return !empty($existing);
        }
        catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Carga una entidad Badge por ID.
     *
     * @param int $badgeId
     *   ID de la insignia.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\Badge|null
     *   La entidad Badge o NULL si no existe.
     */
    protected function loadBadge(int $badgeId): ?Badge
    {
        try {
            $entity = $this->entityTypeManager->getStorage('badge')->load($badgeId);
            return $entity instanceof Badge ? $entity : NULL;
        }
        catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Evalúa si un evento coincide con los criterios de una insignia.
     *
     * Soporta los siguientes tipos de criterio:
     * - event_count: El evento coincide y el conteo supera el umbral.
     * - first_action: El evento coincide (se otorga la primera vez).
     * - streak: El evento coincide y la racha alcanza el mínimo.
     * - manual: Nunca se otorga automáticamente.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\Badge $badge
     *   La insignia a evaluar.
     * @param string $eventType
     *   Tipo de evento recibido.
     * @param array $eventData
     *   Datos del evento.
     *
     * @return bool
     *   TRUE si los criterios se cumplen.
     */
    protected function matchesCriteria(Badge $badge, string $eventType, array $eventData): bool
    {
        $criteriaType = $badge->getCriteriaType();
        $config = $badge->getCriteriaConfig();

        // Los badges manuales nunca se otorgan automáticamente.
        if ($criteriaType === 'manual') {
            return FALSE;
        }

        // El evento configurado debe coincidir con el evento recibido.
        $configuredEvent = $config['event'] ?? '';
        if (empty($configuredEvent) || $configuredEvent !== $eventType) {
            return FALSE;
        }

        return match ($criteriaType) {
            'event_count' => $this->matchesEventCount($config, $eventData),
            'first_action' => TRUE,
            'streak' => $this->matchesStreak($config, $eventData),
            default => FALSE,
        };
    }

    /**
     * Evalúa el criterio de conteo de eventos.
     *
     * @param array $config
     *   Configuración del criterio (debe incluir 'count').
     * @param array $eventData
     *   Datos del evento (debe incluir 'count').
     *
     * @return bool
     *   TRUE si el conteo del evento alcanza el umbral configurado.
     */
    protected function matchesEventCount(array $config, array $eventData): bool
    {
        $requiredCount = (int) ($config['count'] ?? 0);
        $actualCount = (int) ($eventData['count'] ?? 0);

        return $requiredCount > 0 && $actualCount >= $requiredCount;
    }

    /**
     * Evalúa el criterio de racha consecutiva.
     *
     * @param array $config
     *   Configuración del criterio (debe incluir 'streak_days').
     * @param array $eventData
     *   Datos del evento (debe incluir 'streak_days').
     *
     * @return bool
     *   TRUE si la racha alcanza el mínimo configurado.
     */
    protected function matchesStreak(array $config, array $eventData): bool
    {
        $requiredStreak = (int) ($config['streak_days'] ?? 0);
        $actualStreak = (int) ($eventData['streak_days'] ?? 0);

        return $requiredStreak > 0 && $actualStreak >= $requiredStreak;
    }

}
