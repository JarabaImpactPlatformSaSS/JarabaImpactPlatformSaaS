<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for job alerts.
 */
class JobAlertService
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
     * The mail manager.
     */
    protected MailManagerInterface $mailManager;

    /**
     * The queue factory.
     */
    protected QueueFactory $queueFactory;

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
        MailManagerInterface $mail_manager,
        QueueFactory $queue_factory,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->mailManager = $mail_manager;
        $this->queueFactory = $queue_factory;
        $this->logger = $logger_factory->get('jaraba_job_board');
    }

    /**
     * Processes scheduled alerts.
     */
    public function processScheduledAlerts(): int
    {
        try {
            $alertIds = $this->entityTypeManager
                ->getStorage('job_alert')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('status', 'active')
                ->execute();

            if (empty($alertIds)) {
                return 0;
            }

            $alerts = $this->entityTypeManager->getStorage('job_alert')->loadMultiple($alertIds);
            $processed = 0;

            foreach ($alerts as $alert) {
                // Build query from alert criteria.
                $query = $this->entityTypeManager
                    ->getStorage('job_posting')
                    ->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('status', 'published');

                // Filter by alert criteria.
                $keywords = $alert->get('keywords')->value ?? NULL;
                if ($keywords) {
                    $query->condition('title', '%' . $keywords . '%', 'LIKE');
                }
                $location = $alert->get('location')->value ?? NULL;
                if ($location) {
                    $query->condition('city', $location);
                }

                // Only jobs created after last notification.
                $lastSent = $alert->get('last_sent_at')->value ?? NULL;
                if ($lastSent) {
                    $query->condition('created', $lastSent, '>');
                }

                $query->range(0, 10);
                $jobIds = $query->execute();

                if (!empty($jobIds)) {
                    $user = \Drupal::entityTypeManager()->getStorage('user')->load($alert->get('user_id')->target_id);
                    if ($user && $user->getEmail()) {
                        $jobs = $this->entityTypeManager->getStorage('job_posting')->loadMultiple($jobIds);
                        $jobTitles = array_map(fn($j) => $j->label(), $jobs);
                        $this->mailManager->mail('jaraba_job_board', 'high_match_alert', $user->getEmail(), $user->getPreferredLangcode(), [
                            'employer_name' => $user->getDisplayName(),
                            'job_title' => implode(', ', $jobTitles),
                        ]);
                    }

                    $alert->set('last_sent_at', \Drupal::time()->getRequestTime());
                    $alert->save();
                    $processed++;
                }
            }

            return $processed;
        } catch (\Exception $e) {
            $this->logger->error('Error processing job alerts: @error', ['@error' => $e->getMessage()]);
            return 0;
        }
    }

}
