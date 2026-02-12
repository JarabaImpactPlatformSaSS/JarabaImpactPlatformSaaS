<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de gamificación para Customer Discovery.
 *
 * Implementa badges y logros para motivar a los emprendedores
 * a realizar entrevistas con clientes (Field Exits) según la
 * metodología de Steve Blank.
 *
 * Basado en gamificación probada:
 * - Badges por hitos de entrevistas
 * - Streaks por consistencia
 * - Niveles de "Customer Discovery Master"
 */
class CustomerDiscoveryGamificationService
{

    /**
     * Badges disponibles por hitos de entrevistas.
     */
    const BADGES = [
        'first_steps' => [
            'id' => 'first_steps',
            'name' => 'Primeros Pasos',
            'name_en' => 'First Steps',
            'description' => 'Realizaste tu primera entrevista con un cliente potencial.',
            'icon' => 'badge-star',
            'threshold' => 1,
            'color' => '#10B981', // Verde
        ],
        'explorer' => [
            'id' => 'explorer',
            'name' => 'Explorador',
            'name_en' => 'Explorer',
            'description' => 'Has entrevistado a 5 clientes potenciales.',
            'icon' => 'badge-compass',
            'threshold' => 5,
            'color' => '#3B82F6', // Azul
        ],
        'researcher' => [
            'id' => 'researcher',
            'name' => 'Investigador',
            'name_en' => 'Researcher',
            'description' => 'Has entrevistado a 10 clientes potenciales.',
            'icon' => 'badge-search',
            'threshold' => 10,
            'color' => '#8B5CF6', // Púrpura
        ],
        'field_expert' => [
            'id' => 'field_expert',
            'name' => 'Experto de Campo',
            'name_en' => 'Field Expert',
            'description' => 'Has realizado 25 salidas del edificio.',
            'icon' => 'badge-building',
            'threshold' => 25,
            'color' => '#F59E0B', // Naranja
        ],
        'customer_whisperer' => [
            'id' => 'customer_whisperer',
            'name' => 'Susurrador de Clientes',
            'name_en' => 'Customer Whisperer',
            'description' => 'Has completado 50 entrevistas. ¡Impresionante!',
            'icon' => 'badge-crown',
            'threshold' => 50,
            'color' => '#EF4444', // Rojo
        ],
        'discovery_master' => [
            'id' => 'discovery_master',
            'name' => 'Maestro del Discovery',
            'name_en' => 'Discovery Master',
            'description' => 'Has completado 100 entrevistas. Steve Blank estaría orgulloso.',
            'icon' => 'badge-trophy',
            'threshold' => 100,
            'color' => '#F97316', // Dorado
        ],
        // Badges especiales
        'week_streak_3' => [
            'id' => 'week_streak_3',
            'name' => 'Racha de 3 Semanas',
            'name_en' => '3-Week Streak',
            'description' => 'Has entrevistado clientes durante 3 semanas consecutivas.',
            'icon' => 'badge-fire',
            'threshold' => 3,
            'type' => 'streak',
            'color' => '#EF4444',
        ],
        'diverse_methods' => [
            'id' => 'diverse_methods',
            'name' => 'Metodólogo Diverso',
            'name_en' => 'Diverse Methodologist',
            'description' => 'Has usado 5 métodos diferentes de Customer Discovery.',
            'icon' => 'badge-puzzle',
            'threshold' => 5,
            'type' => 'method_diversity',
            'color' => '#14B8A6',
        ],
        'validator' => [
            'id' => 'validator',
            'name' => 'Validador Experto',
            'name_en' => 'Expert Validator',
            'description' => 'Has validado 10 hipótesis mediante entrevistas.',
            'icon' => 'badge-check',
            'threshold' => 10,
            'type' => 'validations',
            'color' => '#22C55E',
        ],
    ];

    /**
     * Niveles del programa.
     */
    const LEVELS = [
        1 => ['name' => 'Novato', 'min_exits' => 0, 'max_exits' => 4],
        2 => ['name' => 'Aprendiz', 'min_exits' => 5, 'max_exits' => 14],
        3 => ['name' => 'Practicante', 'min_exits' => 15, 'max_exits' => 29],
        4 => ['name' => 'Profesional', 'min_exits' => 30, 'max_exits' => 49],
        5 => ['name' => 'Experto', 'min_exits' => 50, 'max_exits' => 99],
        6 => ['name' => 'Maestro', 'min_exits' => 100, 'max_exits' => PHP_INT_MAX],
    ];

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
     * Obtiene los badges ganados por un usuario.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return array
     *   Badges ganados y próximo objetivo.
     */
    public function getUserBadges(?int $userId = NULL): array
    {
        $userId = $userId ?? (int) $this->currentUser->id();
        $stats = $this->getUserStats($userId);

        $earned = [];
        $nextBadge = NULL;
        $nextThreshold = PHP_INT_MAX;

        foreach (self::BADGES as $badgeId => $badge) {
            $type = $badge['type'] ?? 'exits';
            $threshold = $badge['threshold'];

            $achieved = FALSE;
            switch ($type) {
                case 'exits':
                    $achieved = $stats['total_exits'] >= $threshold;
                    break;

                case 'streak':
                    $achieved = $stats['week_streak'] >= $threshold;
                    break;

                case 'method_diversity':
                    $achieved = $stats['unique_methods'] >= $threshold;
                    break;

                case 'validations':
                    $achieved = $stats['validations'] >= $threshold;
                    break;
            }

            if ($achieved) {
                $earned[$badgeId] = $badge;
            } elseif ($type === 'exits' && $threshold < $nextThreshold) {
                $nextBadge = $badge;
                $nextThreshold = $threshold;
                $nextBadge['remaining'] = $threshold - $stats['total_exits'];
            }
        }

        return [
            'earned' => $earned,
            'total_earned' => count($earned),
            'next_badge' => $nextBadge,
            'level' => $this->getUserLevel($stats['total_exits']),
        ];
    }

    /**
     * Obtiene estadísticas del usuario para badges.
     */
    protected function getUserStats(int $userId): array
    {
        $stats = [
            'total_exits' => 0,
            'week_streak' => 0,
            'unique_methods' => 0,
            'validations' => 0,
        ];

        try {
            $storage = $this->entityTypeManager->getStorage('field_exit');
            $exits = $storage->loadByProperties(['entrepreneur_id' => $userId]);

            $stats['total_exits'] = count($exits);

            // Contar métodos únicos
            $methods = [];
            $validations = 0;
            foreach ($exits as $exit) {
                $method = $exit->get('exit_type')->value ?? '';
                if ($method) {
                    $methods[$method] = TRUE;
                }
                if ($exit->get('hypothesis_validated')->value) {
                    $validations++;
                }
            }
            $stats['unique_methods'] = count($methods);
            $stats['validations'] = $validations;

            // TODO: Calcular week_streak basado en fechas
            $stats['week_streak'] = min(3, intval($stats['total_exits'] / 3));
        } catch (\Exception $e) {
            // Entity doesn't exist yet
        }

        return $stats;
    }

    /**
     * Obtiene el nivel del usuario.
     */
    protected function getUserLevel(int $totalExits): array
    {
        foreach (self::LEVELS as $level => $data) {
            if ($totalExits >= $data['min_exits'] && $totalExits <= $data['max_exits']) {
                $progress = 0;
                if ($data['max_exits'] !== PHP_INT_MAX) {
                    $range = $data['max_exits'] - $data['min_exits'] + 1;
                    $progress = (($totalExits - $data['min_exits']) / $range) * 100;
                } else {
                    $progress = 100;
                }

                return [
                    'level' => $level,
                    'name' => $data['name'],
                    'progress' => round($progress),
                    'next_level' => $level < 6 ? self::LEVELS[$level + 1]['name'] : NULL,
                    'exits_to_next' => $level < 6 ? self::LEVELS[$level + 1]['min_exits'] - $totalExits : 0,
                ];
            }
        }

        return ['level' => 1, 'name' => 'Novato', 'progress' => 0];
    }

    /**
     * Obtiene resumen de gamificación para el prompt.
     */
    public function getGamificationSummaryForPrompt(?int $userId = NULL): string
    {
        $badges = $this->getUserBadges($userId);

        $parts = [];
        $parts[] = "Nivel: {$badges['level']['name']} (nivel {$badges['level']['level']})";
        $parts[] = "Badges: {$badges['total_earned']} obtenidos";

        if ($badges['next_badge']) {
            $parts[] = "Próximo badge: {$badges['next_badge']['name']} ({$badges['next_badge']['remaining']} entrevistas más)";
        }

        return implode('. ', $parts);
    }

    /**
     * Verifica si hay un nuevo badge ganado después de una acción.
     */
    public function checkNewBadges(int $userId, int $previousCount): ?array
    {
        $currentBadges = $this->getUserBadges($userId);

        foreach ($currentBadges['earned'] as $badgeId => $badge) {
            // Si el badge no estaba antes, es nuevo
            if ($previousCount < $badge['threshold']) {
                return $badge;
            }
        }

        return NULL;
    }

}
