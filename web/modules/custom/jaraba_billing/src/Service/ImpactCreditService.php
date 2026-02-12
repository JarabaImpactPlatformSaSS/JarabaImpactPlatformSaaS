<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Créditos de Impacto.
 *
 * Gestiona el sistema de créditos de impacto para gamificación
 * y reconocimiento de logros en la plataforma.
 *
 * Los créditos se usan para:
 * - Ranking de candidatos comprometidos
 * - Desbloqueo de features premium
 * - Métricas de engagement
 */
class ImpactCreditService
{

    /**
     * Database connection.
     */
    protected Connection $database;

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Créditos por tipo de acción.
     */
    const CREDIT_VALUES = [
        // LMS
        'complete_lesson' => 10,
        'complete_course' => 100,
        'earn_badge' => 50,
        'first_course_complete' => 150,

        // Job Board
        'create_profile' => 50,
        'complete_profile' => 100,
        'apply_job' => 20,
        'get_shortlisted' => 30,
        'get_interviewed' => 50,
        'get_hired' => 500,

        // Engagement
        'daily_login' => 5,
        'weekly_streak' => 25,
        'refer_friend' => 100,
        'leave_review' => 15,

        // Diagnóstico
        'complete_diagnostic' => 50,
        'lms_enrolled' => 50,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Otorga créditos a un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $reason
     *   Razón/tipo de créditos.
     * @param int|null $customAmount
     *   Cantidad custom (si NULL, usa CREDIT_VALUES).
     * @param array $metadata
     *   Datos adicionales.
     *
     * @return int
     *   Créditos otorgados.
     */
    public function awardCredits(int $userId, string $reason, ?int $customAmount = NULL, array $metadata = []): int
    {
        $amount = $customAmount ?? (self::CREDIT_VALUES[$reason] ?? 0);

        if ($amount <= 0) {
            return 0;
        }

        // Verificar si existe la tabla
        if (!$this->database->schema()->tableExists('impact_credits_log')) {
            $this->createTables();
        }

        // Insertar log de créditos
        $this->database->insert('impact_credits_log')
            ->fields([
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason,
                'metadata' => json_encode($metadata),
                'created' => time(),
            ])
            ->execute();

        // Actualizar balance del usuario
        $this->database->merge('impact_credits_balance')
            ->keys(['user_id' => $userId])
            ->fields([
                'user_id' => $userId,
                'total_credits' => $amount,
                'updated' => time(),
            ])
            ->expression('total_credits', 'total_credits + :amount', [':amount' => $amount])
            ->execute();

        $this->logger->info('Awarded @amount credits to user @user for @reason', [
            '@amount' => $amount,
            '@user' => $userId,
            '@reason' => $reason,
        ]);

        return $amount;
    }

    /**
     * Obtiene el balance de créditos de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return int
     *   Total de créditos.
     */
    public function getBalance(int $userId): int
    {
        if (!$this->database->schema()->tableExists('impact_credits_balance')) {
            return 0;
        }

        $result = $this->database->select('impact_credits_balance', 'b')
            ->fields('b', ['total_credits'])
            ->condition('user_id', $userId)
            ->execute()
            ->fetchField();

        return (int) ($result ?: 0);
    }

    /**
     * Obtiene el historial de créditos de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $limit
     *   Límite de resultados.
     *
     * @return array
     *   Historial de créditos.
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        if (!$this->database->schema()->tableExists('impact_credits_log')) {
            return [];
        }

        return $this->database->select('impact_credits_log', 'l')
            ->fields('l')
            ->condition('user_id', $userId)
            ->orderBy('created', 'DESC')
            ->range(0, $limit)
            ->execute()
            ->fetchAll();
    }

    /**
     * Obtiene el ranking de usuarios por créditos.
     *
     * @param int $limit
     *   Cantidad de usuarios.
     * @param int|null $tenantId
     *   Filtrar por tenant.
     *
     * @return array
     *   Ranking de usuarios.
     */
    public function getLeaderboard(int $limit = 10, ?int $tenantId = NULL): array
    {
        if (!$this->database->schema()->tableExists('impact_credits_balance')) {
            return [];
        }

        $query = $this->database->select('impact_credits_balance', 'b');
        $query->join('users_field_data', 'u', 'b.user_id = u.uid');
        $query->fields('b', ['user_id', 'total_credits']);
        $query->fields('u', ['name']);
        $query->orderBy('total_credits', 'DESC');
        $query->range(0, $limit);

        // TODO: Filtrar por tenant cuando esté integrado

        $results = $query->execute()->fetchAll();

        $leaderboard = [];
        $position = 1;
        foreach ($results as $row) {
            $leaderboard[] = [
                'position' => $position++,
                'user_id' => $row->user_id,
                'name' => $row->name,
                'credits' => $row->total_credits,
            ];
        }

        return $leaderboard;
    }

    /**
     * Crea las tablas necesarias.
     */
    protected function createTables(): void
    {
        $schema = $this->database->schema();

        if (!$schema->tableExists('impact_credits_log')) {
            $schema->createTable('impact_credits_log', [
                'fields' => [
                    'id' => [
                        'type' => 'serial',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                    ],
                    'user_id' => [
                        'type' => 'int',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                    ],
                    'amount' => [
                        'type' => 'int',
                        'not null' => TRUE,
                    ],
                    'reason' => [
                        'type' => 'varchar',
                        'length' => 64,
                        'not null' => TRUE,
                    ],
                    'metadata' => [
                        'type' => 'text',
                        'size' => 'normal',
                    ],
                    'created' => [
                        'type' => 'int',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                    ],
                ],
                'primary key' => ['id'],
                'indexes' => [
                    'user_id' => ['user_id'],
                    'reason' => ['reason'],
                    'created' => ['created'],
                ],
            ]);
        }

        if (!$schema->tableExists('impact_credits_balance')) {
            $schema->createTable('impact_credits_balance', [
                'fields' => [
                    'user_id' => [
                        'type' => 'int',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                    ],
                    'total_credits' => [
                        'type' => 'int',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                        'default' => 0,
                    ],
                    'updated' => [
                        'type' => 'int',
                        'unsigned' => TRUE,
                        'not null' => TRUE,
                    ],
                ],
                'primary key' => ['user_id'],
            ]);
        }
    }

}
