<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service for managing job postings.
 */
class JobPostingService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The database.
     */
    protected Connection $database;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * The event dispatcher.
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_job_board');
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * Gets published job postings.
     */
    public function getPublishedJobs(int $limit = 50): array
    {
        return $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties(['status' => 'published']);
    }

    /**
     * Gets a job by ID.
     */
    public function getJob(int $id): mixed
    {
        return $this->entityTypeManager
            ->getStorage('job_posting')
            ->load($id);
    }

    /**
     * Gets jobs by employer.
     */
    public function getEmployerJobs(int $employer_id): array
    {
        return $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties(['employer_id' => $employer_id]);
    }

    /**
     * Closes expired jobs.
     */
    public function closeExpiredJobs(): int
    {
        $now = date('Y-m-d\TH:i:s');
        try {
            $ids = $this->entityTypeManager
                ->getStorage('job_posting')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', 'published')
                ->condition('expiration_date', $now, '<')
                ->execute();

            $count = 0;
            foreach ($this->entityTypeManager->getStorage('job_posting')->loadMultiple($ids) as $job) {
                $job->setStatus('expired');
                $job->save();
                $count++;

                // Dispatch expiration event.
                $this->eventDispatcher->dispatch(
                    new \Symfony\Component\EventDispatcher\GenericEvent($job, [
                        'type' => 'job_posting_expired',
                        'job_id' => $job->id(),
                    ]),
                    'jaraba_job_board.job_posting.expired'
                );
            }

            if ($count > 0) {
                $this->logger->info('Closed @count expired job postings', ['@count' => $count]);
            }

            return $count;
        } catch (\Exception $e) {
            $this->logger->error('Error closing expired jobs: @error', ['@error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Counts active jobs for an employer.
     *
     * @param int $employer_id
     *   The employer user ID.
     *
     * @return int
     *   Number of active jobs.
     */
    public function countActiveJobsByEmployer(int $employer_id): int
    {
        $jobs = $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties([
                'employer_id' => $employer_id,
                'status' => 'published',
            ]);
        return count($jobs);
    }

}

