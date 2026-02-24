<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for candidate-facing job board pages.
 */
class CandidateController extends ControllerBase
{

    /**
     * The application service.
     */
    protected ApplicationService $applicationService;

    /**
     * Constructor.
     */
    public function __construct(ApplicationService $application_service)
    {
        $this->applicationService = $application_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_job_board.application')
        );
    }

    /**
     * Displays user's job applications.
     */
    public function applications(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $applications = $this->applicationService->getCandidateApplications($user_id);

        $formatted = [];
        foreach ($applications as $app) {
            $job = $app->getJob();
            $formatted[] = [
                'id' => $app->id(),
                'job_title' => $job ? $job->getTitle() : $this->t('(Job removed)'),
                'company' => $job ? $job->getLocationCity() : '',
                'status' => $app->getStatus(),
                'applied_at' => $app->getAppliedAt(),
            ];
        }

        return [
            '#theme' => 'my_applications',
            '#applications' => $formatted,
            '#attached' => [
                'library' => ['jaraba_job_board/applications'],
            ],
        ];
    }

    /**
     * Displays saved jobs.
     */
    public function savedJobs(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Load saved jobs from user data storage.
        $saved = [];
        try {
            $userData = \Drupal::service('user.data');
            $saved_ids = $userData->get('jaraba_job_board', $user_id, 'saved_jobs') ?: [];

            if (!empty($saved_ids)) {
                $jobs = $this->entityTypeManager()
                    ->getStorage('job_posting')
                    ->loadMultiple($saved_ids);

                foreach ($jobs as $job) {
                    $saved[] = [
                        'id' => $job->id(),
                        'title' => $job->getTitle(),
                        'location' => $job->getLocationCity(),
                        'job_type' => $job->getJobType(),
                        'status' => $job->getStatus(),
                        'published_at' => $job->get('published_at')->value,
                    ];
                }
            }
        }
        catch (\Exception $e) {
            // Fail gracefully.
        }

        return [
            '#theme' => 'saved_jobs',
            '#jobs' => $saved,
            '#attached' => [
                'library' => ['jaraba_job_board/saved_jobs'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Displays job alerts.
     */
    public function alerts(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Load configured job alerts for this user.
        $alerts = [];
        try {
            $userData = \Drupal::service('user.data');
            $alertData = $userData->get('jaraba_job_board', $user_id, 'job_alerts') ?: [];

            foreach ($alertData as $alert) {
                $alerts[] = [
                    'id' => $alert['id'] ?? '',
                    'keywords' => $alert['keywords'] ?? '',
                    'location' => $alert['location'] ?? '',
                    'job_type' => $alert['job_type'] ?? '',
                    'frequency' => $alert['frequency'] ?? 'daily',
                    'created' => $alert['created'] ?? time(),
                    'active' => $alert['active'] ?? TRUE,
                ];
            }
        }
        catch (\Exception $e) {
            // Fail gracefully.
        }

        return [
            '#theme' => 'job_alerts',
            '#alerts' => $alerts,
            '#attached' => [
                'library' => ['jaraba_job_board/job_alerts'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];
    }

}
