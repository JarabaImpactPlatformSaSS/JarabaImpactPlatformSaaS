<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gamificación para el vertical Empleabilidad.
 *
 * PROPÓSITO:
 * Implementa un sistema de XP, niveles, rachas y logros para
 * motivar a los usuarios a completar su formación y perfil.
 *
 * MECÁNICAS:
 * - XP: Puntos de experiencia por cada acción
 * - Niveles: Progresión basada en XP total
 * - Rachas: Días consecutivos de actividad
 * - Logros: Badges especiales por hitos
 * - Leaderboard: Ranking semanal por tenant
 */
class GamificationService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * XP por tipo de acción.
     */
    const XP_REWARDS = [
        'complete_lesson' => 50,
        'complete_course' => 200,
        'pass_quiz' => 75,
        'apply_job' => 30,
        'profile_complete' => 100,
        'upload_cv' => 40,
        'get_interview' => 150,
        'get_hired' => 500,
        'daily_login' => 10,
        'streak_bonus_7' => 100,
        'streak_bonus_30' => 500,
        'first_application' => 50,
        'first_badge' => 75,
    ];

    /**
     * Niveles con XP requerido.
     */
    const LEVELS = [
        1 => 0,
        2 => 100,
        3 => 300,
        4 => 600,
        5 => 1000,
        6 => 1500,
        7 => 2200,
        8 => 3000,
        9 => 4000,
        10 => 5500,
    ];

    /**
     * Nombres de niveles.
     */
    const LEVEL_NAMES = [
        1 => 'Novato',
        2 => 'Aprendiz',
        3 => 'Iniciado',
        4 => 'Competente',
        5 => 'Profesional',
        6 => 'Experto',
        7 => 'Maestro',
        8 => 'Leyenda',
        9 => 'Élite',
        10 => 'Campeón',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->logger = $loggerFactory->get('jaraba_lms');
    }

    /**
     * Otorga XP a un usuario por una acción.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $action
     *   Tipo de acción (ver XP_REWARDS).
     * @param array $context
     *   Contexto adicional opcional.
     *
     * @return array
     *   Resultado con XP ganado, nivel nuevo, etc.
     */
    public function awardXP(int $userId, string $action, array $context = []): array
    {
        $xp = self::XP_REWARDS[$action] ?? 0;

        if ($xp <= 0) {
            return ['success' => FALSE, 'error' => 'Unknown action'];
        }

        // Aplicar multiplicadores si existen
        if (!empty($context['multiplier'])) {
            $xp = (int) ($xp * $context['multiplier']);
        }

        // Obtener estado actual del usuario
        $currentStats = $this->getUserStats($userId);
        $previousLevel = $currentStats['level'];

        // Actualizar XP total
        $newTotalXP = $currentStats['total_xp'] + $xp;
        $newLevel = $this->calculateLevel($newTotalXP);
        $leveledUp = $newLevel > $previousLevel;

        // Insertar transacción de XP
        $this->database->insert('gamification_xp_log')
            ->fields([
                'user_id' => $userId,
                'action' => $action,
                'xp_amount' => $xp,
                'context_data' => json_encode($context),
                'created' => time(),
            ])
            ->execute();

        // Actualizar stats del usuario
        $this->updateUserStats($userId, [
            'total_xp' => $newTotalXP,
            'current_level' => $newLevel,
        ]);

        $result = [
            'success' => TRUE,
            'xp_awarded' => $xp,
            'total_xp' => $newTotalXP,
            'current_level' => $newLevel,
            'level_name' => self::LEVEL_NAMES[$newLevel] ?? 'Unknown',
            'leveled_up' => $leveledUp,
        ];

        if ($leveledUp) {
            $result['new_level'] = $newLevel;
            $result['new_level_name'] = self::LEVEL_NAMES[$newLevel] ?? 'Unknown';
            $this->logger->info('User @user leveled up to @level', [
                '@user' => $userId,
                '@level' => $newLevel,
            ]);
        }

        return $result;
    }

    /**
     * Registra actividad diaria y actualiza racha.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return array
     *   Estado de la racha.
     */
    public function recordStreak(int $userId): array
    {
        $stats = $this->getUserStats($userId);
        $lastActivity = $stats['last_activity_date'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Si ya registró hoy, retornar stats actuales
        if ($lastActivity === $today) {
            return [
                'streak_days' => $stats['streak_days'],
                'already_recorded' => TRUE,
            ];
        }

        $newStreak = ($lastActivity === $yesterday)
            ? $stats['streak_days'] + 1
            : 1; // Reset si no fue ayer

        // Actualizar stats
        $this->updateUserStats($userId, [
            'streak_days' => $newStreak,
            'last_activity_date' => $today,
            'longest_streak' => max($stats['longest_streak'] ?? 0, $newStreak),
        ]);

        // Dar XP por login diario
        $this->awardXP($userId, 'daily_login');

        // Bonus de racha
        if ($newStreak === 7) {
            $this->awardXP($userId, 'streak_bonus_7');
        } elseif ($newStreak === 30) {
            $this->awardXP($userId, 'streak_bonus_30');
        }

        return [
            'streak_days' => $newStreak,
            'is_new_streak' => $lastActivity !== $yesterday,
        ];
    }

    /**
     * Verifica si el usuario subió de nivel.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return array|null
     *   Datos del nuevo nivel o null si no subió.
     */
    public function checkLevelUp(int $userId): ?array
    {
        $stats = $this->getUserStats($userId);
        $calculatedLevel = $this->calculateLevel($stats['total_xp']);

        if ($calculatedLevel > $stats['current_level']) {
            $this->updateUserStats($userId, ['current_level' => $calculatedLevel]);

            return [
                'new_level' => $calculatedLevel,
                'level_name' => self::LEVEL_NAMES[$calculatedLevel] ?? 'Unknown',
                'xp_for_next' => self::LEVELS[$calculatedLevel + 1] ?? NULL,
            ];
        }

        return NULL;
    }

    /**
     * Obtiene el leaderboard semanal.
     *
     * @param int|null $tenantId
     *   ID del tenant (null = global).
     * @param int $limit
     *   Número de posiciones.
     *
     * @return array
     *   Array de posiciones del leaderboard.
     */
    public function getLeaderboard(?int $tenantId = NULL, int $limit = 10): array
    {
        $weekStart = date('Y-m-d', strtotime('monday this week'));

        $query = $this->database->select('gamification_xp_log', 'x');
        $query->fields('x', ['user_id']);
        $query->addExpression('SUM(x.xp_amount)', 'weekly_xp');
        $query->condition('x.created', strtotime($weekStart), '>=');
        $query->groupBy('x.user_id');
        $query->orderBy('weekly_xp', 'DESC');
        $query->range(0, $limit);

        $results = $query->execute()->fetchAll();

        $leaderboard = [];
        $position = 1;
        $userStorage = $this->entityTypeManager->getStorage('user');

        foreach ($results as $row) {
            $userId = (int) $row->user_id;
            $user = $userStorage->load($userId);
            if (!$user) {
                continue;
            }

            $stats = $this->getUserStats($userId);

            $leaderboard[] = [
                'position' => $position++,
                'user_id' => $userId,
                'username' => $user->getDisplayName(),
                'weekly_xp' => (int) $row->weekly_xp,
                'total_xp' => $stats['total_xp'],
                'level' => $stats['current_level'],
                'level_name' => self::LEVEL_NAMES[$stats['current_level']] ?? 'Unknown',
            ];
        }

        return $leaderboard;
    }

    /**
     * Obtiene las estadísticas de gamificación de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return array
     *   Stats del usuario.
     */
    public function getUserStats(int $userId): array
    {
        // Buscar en tabla de stats
        $result = $this->database->select('gamification_user_stats', 's')
            ->fields('s')
            ->condition('s.user_id', $userId)
            ->execute()
            ->fetchAssoc();

        if ($result) {
            return $result;
        }

        // Crear stats iniciales si no existen
        $defaults = [
            'user_id' => $userId,
            'total_xp' => 0,
            'current_level' => 1,
            'streak_days' => 0,
            'longest_streak' => 0,
            'last_activity_date' => NULL,
        ];

        $this->database->insert('gamification_user_stats')
            ->fields($defaults)
            ->execute();

        return $defaults;
    }

    /**
     * Actualiza stats del usuario.
     */
    protected function updateUserStats(int $userId, array $updates): void
    {
        $updates['updated'] = time();

        $this->database->update('gamification_user_stats')
            ->fields($updates)
            ->condition('user_id', $userId)
            ->execute();
    }

    /**
     * Calcula el nivel basado en XP total.
     */
    protected function calculateLevel(int $totalXP): int
    {
        $level = 1;
        foreach (self::LEVELS as $lvl => $requiredXP) {
            if ($totalXP >= $requiredXP) {
                $level = $lvl;
            }
        }
        return min($level, 10);
    }

    /**
     * Obtiene el progreso hacia el siguiente nivel.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return array
     *   Progreso con porcentaje, XP actual y requerido.
     */
    public function getLevelProgress(int $userId): array
    {
        $stats = $this->getUserStats($userId);
        $currentLevel = $stats['current_level'];
        $totalXP = $stats['total_xp'];

        $currentLevelXP = self::LEVELS[$currentLevel] ?? 0;
        $nextLevelXP = self::LEVELS[$currentLevel + 1] ?? $currentLevelXP + 1000;

        $xpInCurrentLevel = $totalXP - $currentLevelXP;
        $xpNeededForNext = $nextLevelXP - $currentLevelXP;
        $progress = ($xpNeededForNext > 0) ? ($xpInCurrentLevel / $xpNeededForNext) * 100 : 100;

        return [
            'current_level' => $currentLevel,
            'level_name' => self::LEVEL_NAMES[$currentLevel] ?? 'Unknown',
            'total_xp' => $totalXP,
            'xp_in_level' => $xpInCurrentLevel,
            'xp_for_next' => $xpNeededForNext,
            'progress_percent' => min(100, round($progress, 1)),
            'next_level_name' => self::LEVEL_NAMES[$currentLevel + 1] ?? 'Max Level',
        ];
    }

}
