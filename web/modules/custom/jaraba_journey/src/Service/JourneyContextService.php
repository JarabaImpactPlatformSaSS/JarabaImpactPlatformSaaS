<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de contexto para el Journey Engine.
 *
 * Analiza el contexto del usuario en tiempo real:
 * - Identidad: rol, permisos, tenant, historial
 * - Temporal: hora del día, día de semana, estacionalidad
 * - Comportamental: páginas visitadas, tiempo en pantalla
 * - Transaccional: compras previas, carritos abandonados
 */
class JourneyContextService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Obtiene el contexto completo del usuario.
     */
    public function getFullContext(?int $userId = NULL): array
    {
        $userId = $userId ?? (int) $this->currentUser->id();

        return [
            'identity' => $this->getIdentityContext($userId),
            'temporal' => $this->getTemporalContext(),
            'behavioral' => $this->getBehavioralContext($userId),
            'transactional' => $this->getTransactionalContext($userId),
        ];
    }

    /**
     * Obtiene contexto de identidad.
     */
    protected function getIdentityContext(int $userId): array
    {
        try {
            $user = $this->entityTypeManager->getStorage('user')->load($userId);

            if (!$user) {
                return [];
            }

            return [
                'user_id' => $userId,
                'roles' => $user->getRoles(),
                'created' => $user->getCreatedTime(),
                'last_login' => $user->getLastLoginTime(),
                'is_authenticated' => $userId > 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene contexto temporal.
     */
    protected function getTemporalContext(): array
    {
        $now = new \DateTime();

        return [
            'hour' => (int) $now->format('G'),
            'day_of_week' => (int) $now->format('N'),
            'is_weekend' => in_array($now->format('N'), [6, 7]),
            'is_business_hours' => $now->format('G') >= 9 && $now->format('G') <= 18,
            'month' => (int) $now->format('n'),
            'quarter' => ceil((int) $now->format('n') / 3),
        ];
    }

    /**
     * Obtiene contexto comportamental.
     */
    protected function getBehavioralContext(int $userId): array
    {
        // En una implementación real, esto vendría de analytics
        return [
            'sessions_this_week' => 0,
            'pages_viewed_today' => 0,
            'time_on_site_today' => 0,
            'last_page_viewed' => '',
            'scroll_depth_avg' => 0,
        ];
    }

    /**
     * Obtiene contexto transaccional.
     */
    protected function getTransactionalContext(int $userId): array
    {
        // En una implementación real, esto vendría de commerce
        return [
            'total_purchases' => 0,
            'total_spent' => 0,
            'last_purchase_date' => NULL,
            'cart_items' => 0,
            'abandoned_carts' => 0,
        ];
    }

    /**
     * Calcula el risk score basado en el contexto.
     */
    public function calculateRiskScore(int $userId): float
    {
        $context = $this->getFullContext($userId);
        $riskScore = 0.0;

        // Factores de riesgo
        $identity = $context['identity'];

        // Inactividad
        if ($identity['last_login'] ?? 0) {
            $daysSinceLogin = (time() - $identity['last_login']) / 86400;
            if ($daysSinceLogin > 7) {
                $riskScore += min(0.3, $daysSinceLogin * 0.03);
            }
        }

        // Bajo engagement
        $behavioral = $context['behavioral'];
        if (($behavioral['sessions_this_week'] ?? 0) < 2) {
            $riskScore += 0.2;
        }

        // Sin transacciones
        $transactional = $context['transactional'];
        if (($transactional['total_purchases'] ?? 0) === 0) {
            $riskScore += 0.15;
        }

        return min(1.0, $riskScore);
    }

}
