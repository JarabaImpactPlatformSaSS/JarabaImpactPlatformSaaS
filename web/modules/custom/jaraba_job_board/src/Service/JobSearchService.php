<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for job search functionality.
 */
class JobSearchService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The database.
     */
    protected Connection $database;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_job_board');
    }

    /**
     * Searches jobs.
     */
    public function search(array $filters, int $page = 0, int $limit = 20): array
    {
        $query = $this->entityTypeManager
            ->getStorage('job_posting')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'published')
            ->sort('is_featured', 'DESC')
            ->sort('published_at', 'DESC')
            ->range($page * $limit, $limit);

        if (!empty($filters['q'])) {
            $query->condition('title', '%' . $filters['q'] . '%', 'LIKE');
        }

        if (!empty($filters['location'])) {
            $query->condition('location_city', '%' . $filters['location'] . '%', 'LIKE');
        }

        if (!empty($filters['job_type'])) {
            $query->condition('job_type', $filters['job_type']);
        }

        $ids = $query->execute();
        $jobs = $this->entityTypeManager->getStorage('job_posting')->loadMultiple($ids);

        return [
            'jobs' => $jobs,
            'total' => count($ids),
        ];
    }

}
