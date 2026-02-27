<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Feature gate service para la experiencia demo.
 *
 * S4-03: Implementa límites ligeros de funcionalidades para sesiones
 * demo anónimas. Controla: sesiones por hora, mensajes IA por sesión,
 * generaciones de historias por sesión.
 *
 * No es un FeatureGateService completo como los de verticales elevados
 * (no tiene config entities ni reglas complejas). Es una implementación
 * ligera diseñada para PLG.
 *
 * Q1 2027 - Sprint 4: Elevación y Page Builder
 */
class DemoFeatureGateService
{

    /**
     * Límites específicos de la experiencia demo.
     */
    protected const DEMO_LIMITS = [
        'demo_sessions_per_hour' => 5,
        'ai_messages_per_session' => 10,
        'story_generations_per_session' => 3,
        'products_viewed_per_session' => 50,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected ?LoggerChannelFactoryInterface $loggerFactory = NULL,
    ) {
    }

    /**
     * Comprueba si una funcionalidad está disponible para la sesión.
     *
     * @param string $sessionId
     *   ID de la sesión demo.
     * @param string $feature
     *   ID de la funcionalidad (key de DEMO_LIMITS).
     *
     * @return array
     *   Array con: allowed (bool), remaining (int), limit (int), current (int).
     */
    public function check(string $sessionId, string $feature): array
    {
        $limit = self::DEMO_LIMITS[$feature] ?? NULL;
        if ($limit === NULL) {
            // S8-02: Log warning para features desconocidas.
            $this->loggerFactory?->get('demo_feature_gate')->warning(
                'Unknown demo feature gate: @feature',
                ['@feature' => $feature]
            );
            return ['allowed' => TRUE, 'remaining' => PHP_INT_MAX, 'limit' => PHP_INT_MAX, 'current' => 0];
        }

        $currentUsage = $this->getUsage($sessionId, $feature);
        $allowed = $currentUsage < $limit;

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $limit - $currentUsage),
            'limit' => $limit,
            'current' => $currentUsage,
        ];
    }

    /**
     * Registra el uso de una funcionalidad en la sesión.
     *
     * @param string $sessionId
     *   ID de la sesión demo.
     * @param string $feature
     *   ID de la funcionalidad.
     */
    public function recordUsage(string $sessionId, string $feature): void
    {
        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data'])
                ->condition('session_id', $sessionId)
                ->execute()
                ->fetchField();

            if (!$row) {
                return;
            }

            $data = json_decode($row, TRUE) ?: [];
            $data['feature_usage'][$feature] = ($data['feature_usage'][$feature] ?? 0) + 1;

            $this->database->update('demo_sessions')
                ->fields(['session_data' => json_encode($data, JSON_UNESCAPED_UNICODE)])
                ->condition('session_id', $sessionId)
                ->execute();
        }
        catch (\Exception $e) {
            // Silencioso — el feature gate no debe bloquear la UX.
        }
    }

    /**
     * Obtiene el uso actual de una funcionalidad en una sesión.
     */
    protected function getUsage(string $sessionId, string $feature): int
    {
        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data'])
                ->condition('session_id', $sessionId)
                ->execute()
                ->fetchField();

            if (!$row) {
                return 0;
            }

            $data = json_decode($row, TRUE) ?: [];
            return (int) ($data['feature_usage'][$feature] ?? 0);
        }
        catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene todos los límites configurados.
     *
     * @return array
     *   Array asociativo feature_id => limit.
     */
    public function getLimits(): array
    {
        return self::DEMO_LIMITS;
    }

}
