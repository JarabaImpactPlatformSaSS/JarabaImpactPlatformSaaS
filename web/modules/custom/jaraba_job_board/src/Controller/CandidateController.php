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
        return [
            '#markup' => $this->t('Saved jobs - Coming soon'),
        ];
    }

    /**
     * Displays job alerts.
     */
    public function alerts(): array
    {
        return [
            '#markup' => $this->t('Job alerts - Coming soon'),
        ];
    }

}
