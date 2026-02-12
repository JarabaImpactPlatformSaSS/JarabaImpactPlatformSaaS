<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Job Alerts.
 *
 * Implementa el sistema de alertas de empleo (doc 14_Job_Alerts):
 * - Custom alerts con filtros
 * - Smart match basado en perfil
 * - Company follow
 * - Digest diario/semanal
 */
class JobAlertMatchingService
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
     * Queue factory.
     */
    protected QueueFactory $queueFactory;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        QueueFactory $queueFactory,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->queueFactory = $queueFactory;
        $this->logger = $logger;
    }

    /**
     * Procesa una nueva oferta contra todas las alertas activas.
     *
     * @param object $job
     *   Entidad job_posting.
     */
    public function processNewJob(object $job): void
    {
        // Verificar tabla de alertas
        if (!$this->database->schema()->tableExists('job_alert')) {
            return;
        }

        $jobId = (int) $job->id();

        // Cargar alertas activas
        $alerts = $this->database->select('job_alert', 'a')
            ->fields('a')
            ->condition('is_active', 1)
            ->execute()
            ->fetchAll();

        $matchedUserIds = [];

        foreach ($alerts as $alert) {
            if ($this->evaluateAlert($job, $alert)) {
                $matchedUserIds[$alert->user_id][] = [
                    'alert_id' => $alert->id,
                    'job_id' => $jobId,
                    'alert_type' => $alert->alert_type,
                ];
            }
        }

        // Procesar company follows
        $this->processCompanyFollows($job, $matchedUserIds);

        // Queue notifications por usuario
        foreach ($matchedUserIds as $userId => $matches) {
            $this->queueAlertNotification($userId, $matches);
        }

        $this->logger->info('Processed @count alert matches for job @id', [
            '@count' => count($matchedUserIds),
            '@id' => $jobId,
        ]);
    }

    /**
     * Evalúa si un job cumple los criterios de una alerta.
     *
     * @return bool
     *   TRUE si hay match.
     */
    protected function evaluateAlert(object $job, object $alert): bool
    {
        // Keywords match (fulltext)
        if (!empty($alert->keywords)) {
            $searchText = strtolower($job->label() . ' ' . ($job->get('description')->value ?? ''));
            $keywords = strtolower($alert->keywords);

            if (strpos($searchText, $keywords) === FALSE) {
                return FALSE;
            }
        }

        // Location match
        if (!empty($alert->locations)) {
            $locations = json_decode($alert->locations, TRUE) ?: [];
            if (!empty($locations)) {
                $jobLocation = $job->get('location')->value ?? '';
                $matched = FALSE;

                foreach ($locations as $loc) {
                    if (
                        stripos($jobLocation, $loc['city'] ?? '') !== FALSE ||
                        stripos($jobLocation, $loc['province'] ?? '') !== FALSE
                    ) {
                        $matched = TRUE;
                        break;
                    }
                }

                if (!$matched) {
                    return FALSE;
                }
            }
        }

        // Remote type match
        if (!empty($alert->remote_types)) {
            $remoteTypes = json_decode($alert->remote_types, TRUE) ?: [];
            if (!empty($remoteTypes)) {
                $jobRemote = $job->get('remote_type')->value ?? 'onsite';
                if (!in_array($jobRemote, $remoteTypes)) {
                    return FALSE;
                }
            }
        }

        // Job type match
        if (!empty($alert->job_types)) {
            $jobTypes = json_decode($alert->job_types, TRUE) ?: [];
            if (!empty($jobTypes)) {
                $jobType = $job->get('job_type')->value ?? 'full_time';
                if (!in_array($jobType, $jobTypes)) {
                    return FALSE;
                }
            }
        }

        // Experience level match
        if (!empty($alert->experience_levels)) {
            $expLevels = json_decode($alert->experience_levels, TRUE) ?: [];
            if (!empty($expLevels)) {
                $jobExp = $job->get('experience_level')->value ?? 'mid';
                if (!in_array($jobExp, $expLevels)) {
                    return FALSE;
                }
            }
        }

        // Salary range match
        if (!empty($alert->salary_min)) {
            $jobSalaryMax = (float) ($job->get('salary_max')->value ?? 0);
            if ($jobSalaryMax > 0 && $jobSalaryMax < (float) $alert->salary_min) {
                return FALSE;
            }
        }

        // Exclude companies
        if (!empty($alert->exclude_company_ids)) {
            $excluded = json_decode($alert->exclude_company_ids, TRUE) ?: [];
            $jobEmployer = $job->get('employer_id')->target_id ?? NULL;
            if ($jobEmployer && in_array($jobEmployer, $excluded)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Procesa company follows para una oferta.
     */
    protected function processCompanyFollows(object $job, array &$matchedUserIds): void
    {
        if (!$this->database->schema()->tableExists('company_follow')) {
            return;
        }

        $employerId = $job->get('employer_id')->target_id ?? NULL;
        if (!$employerId) {
            return;
        }

        $follows = $this->database->select('company_follow', 'f')
            ->fields('f', ['user_id'])
            ->condition('employer_id', $employerId)
            ->condition('notify_new_jobs', 1)
            ->execute()
            ->fetchCol();

        foreach ($follows as $userId) {
            $matchedUserIds[$userId][] = [
                'alert_id' => NULL,
                'job_id' => (int) $job->id(),
                'alert_type' => 'company_follow',
            ];
        }
    }

    /**
     * Encola notificación de alerta.
     */
    protected function queueAlertNotification(int $userId, array $matches): void
    {
        $queue = $this->queueFactory->get('job_alert_notification');
        $queue->createItem([
            'user_id' => $userId,
            'matches' => $matches,
            'queued_at' => time(),
        ]);
    }

    /**
     * Crea una alerta desde una búsqueda guardada.
     *
     * @param int $userId
     *   ID del usuario.
     * @param array $filters
     *   Filtros de la búsqueda.
     * @param string $frequency
     *   Frecuencia: instant, daily, weekly.
     *
     * @return int|null
     *   ID de la alerta creada o NULL.
     */
    public function createAlertFromSearch(int $userId, array $filters, string $frequency = 'daily'): ?int
    {
        $this->ensureTablesExist();

        $alertData = [
            'user_id' => $userId,
            'name' => $filters['name'] ?? 'Alerta de búsqueda',
            'alert_type' => 'saved_search',
            'keywords' => $filters['keywords'] ?? NULL,
            'categories' => !empty($filters['categories']) ? json_encode($filters['categories']) : NULL,
            'locations' => !empty($filters['locations']) ? json_encode($filters['locations']) : NULL,
            'remote_types' => !empty($filters['remote_types']) ? json_encode($filters['remote_types']) : NULL,
            'job_types' => !empty($filters['job_types']) ? json_encode($filters['job_types']) : NULL,
            'experience_levels' => !empty($filters['experience_levels']) ? json_encode($filters['experience_levels']) : NULL,
            'salary_min' => $filters['salary_min'] ?? NULL,
            'frequency' => $frequency,
            'channels' => json_encode(['email', 'in_app']),
            'is_active' => 1,
            'created' => time(),
            'changed' => time(),
        ];

        try {
            return (int) $this->database->insert('job_alert')
                ->fields($alertData)
                ->execute();
        } catch (\Exception $e) {
            $this->logger->error('Error creating alert: @error', ['@error' => $e->getMessage()]);
            return NULL;
        }
    }

    /**
     * Sigue una empresa.
     */
    public function followCompany(int $userId, int $employerId): bool
    {
        $this->ensureTablesExist();

        try {
            $this->database->merge('company_follow')
                ->keys([
                    'user_id' => $userId,
                    'employer_id' => $employerId,
                ])
                ->fields([
                    'notify_new_jobs' => 1,
                    'created_at' => time(),
                ])
                ->execute();

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error following company: @error', ['@error' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Deja de seguir una empresa.
     */
    public function unfollowCompany(int $userId, int $employerId): bool
    {
        try {
            $this->database->delete('company_follow')
                ->condition('user_id', $userId)
                ->condition('employer_id', $employerId)
                ->execute();

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Obtiene alertas de un usuario.
     */
    public function getUserAlerts(int $userId): array
    {
        if (!$this->database->schema()->tableExists('job_alert')) {
            return [];
        }

        return $this->database->select('job_alert', 'a')
            ->fields('a')
            ->condition('user_id', $userId)
            ->orderBy('created', 'DESC')
            ->execute()
            ->fetchAll();
    }

    /**
     * Asegura que existan las tablas necesarias.
     */
    protected function ensureTablesExist(): void
    {
        $schema = $this->database->schema();

        if (!$schema->tableExists('job_alert')) {
            $schema->createTable('job_alert', [
                'fields' => [
                    'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
                    'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'name' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE],
                    'alert_type' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
                    'keywords' => ['type' => 'varchar', 'length' => 500],
                    'categories' => ['type' => 'text'],
                    'skills' => ['type' => 'text'],
                    'locations' => ['type' => 'text'],
                    'remote_types' => ['type' => 'text'],
                    'job_types' => ['type' => 'text'],
                    'experience_levels' => ['type' => 'text'],
                    'salary_min' => ['type' => 'numeric', 'precision' => 10, 'scale' => 2],
                    'salary_max' => ['type' => 'numeric', 'precision' => 10, 'scale' => 2],
                    'company_ids' => ['type' => 'text'],
                    'exclude_company_ids' => ['type' => 'text'],
                    'match_threshold' => ['type' => 'int', 'default' => 70],
                    'frequency' => ['type' => 'varchar', 'length' => 16, 'default' => 'daily'],
                    'channels' => ['type' => 'text'],
                    'is_active' => ['type' => 'int', 'size' => 'tiny', 'default' => 1],
                    'last_sent_at' => ['type' => 'int', 'unsigned' => TRUE],
                    'total_matches' => ['type' => 'int', 'default' => 0],
                    'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'changed' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                ],
                'primary key' => ['id'],
                'indexes' => [
                    'user_id' => ['user_id'],
                    'is_active' => ['is_active'],
                ],
            ]);
        }

        if (!$schema->tableExists('company_follow')) {
            $schema->createTable('company_follow', [
                'fields' => [
                    'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
                    'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'employer_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'notify_new_jobs' => ['type' => 'int', 'size' => 'tiny', 'default' => 1],
                    'notify_updates' => ['type' => 'int', 'size' => 'tiny', 'default' => 0],
                    'created_at' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                ],
                'primary key' => ['id'],
                'unique keys' => [
                    'user_employer' => ['user_id', 'employer_id'],
                ],
            ]);
        }
    }

}
