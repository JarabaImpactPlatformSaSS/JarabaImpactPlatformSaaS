<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_job_board\Entity\JobApplicationInterface;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for employer-facing job board pages.
 */
class EmployerController extends ControllerBase
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
     * Displays employer dashboard.
     */
    public function dashboard(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Get job postings for this employer
        $jobs = $this->entityTypeManager()
            ->getStorage('job_posting')
            ->loadByProperties(['employer_id' => $user_id]);

        // Calculate stats
        $totalJobs = count($jobs);
        $activeJobs = 0;
        $totalApplications = 0;
        $pendingApplications = 0;
        $totalViews = 0;
        $recentJobs = [];

        foreach ($jobs as $job) {
            $status = $job->get('status')->value ?? 'draft';
            if ($status === 'published') {
                $activeJobs++;
            }

            $appCount = $job->getApplicationsCount();
            $totalApplications += $appCount;
            $totalViews += (int) ($job->get('views_count')->value ?? 0);

            // Count pending (not reviewed)
            $applications = $this->applicationService->getJobApplications((int) $job->id());
            foreach ($applications as $app) {
                if ($app->getStatus() === 'pending' || $app->getStatus() === 'applied') {
                    $pendingApplications++;
                }
            }

            // Recent jobs for display
            $recentJobs[] = [
                'id' => $job->id(),
                'title' => $job->getTitle(),
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'applications_count' => $appCount,
                'views' => (int) ($job->get('views_count')->value ?? 0),
            ];
        }

        // Limit recent jobs to 5
        $recentJobs = array_slice($recentJobs, 0, 5);

        // Calculate conversion rate
        $conversionRate = $totalViews > 0 ? round(($totalApplications / $totalViews) * 100, 1) : 0;

        return [
            '#theme' => 'employer_dashboard',
            '#jobs_count' => $totalJobs,
            '#active_jobs' => $activeJobs,
            '#pending_applications' => $pendingApplications,
            '#total_applications' => $totalApplications,
            '#total_views' => $totalViews,
            '#conversion_rate' => $conversionRate,
            '#recent_jobs' => $recentJobs,
            '#user' => $this->entityTypeManager()->getStorage('user')->load($user_id),
            '#attached' => [
                'library' => ['jaraba_job_board/employer_dashboard'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Gets human-readable status label.
     *
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
     *   Translated status label.
     */
    protected function getStatusLabel(string $status): \Drupal\Core\StringTranslation\TranslatableMarkup|string
    {
        $labels = [
            'draft' => $this->t('Borrador'),
            'published' => $this->t('Publicada'),
            'paused' => $this->t('Pausada'),
            'closed' => $this->t('Cerrada'),
            'expired' => $this->t('Expirada'),
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Lists employer's job postings with filters.
     */
    public function jobs(Request $request): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Get filter parameters
        $status_filter = $request->query->get('status', '');
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'newest');

        // Build query with conditions
        $storage = $this->entityTypeManager()->getStorage('job_posting');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('employer_id', $user_id);

        // Apply status filter
        if ($status_filter && in_array($status_filter, ['draft', 'published', 'paused', 'closed'])) {
            $query->condition('status', $status_filter);
        }

        // Apply search filter (title)
        if ($search) {
            $query->condition('title', '%' . $search . '%', 'LIKE');
        }

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                $query->sort('created', 'ASC');
                break;
            case 'title':
                $query->sort('title', 'ASC');
                break;
            case 'applications':
                // Note: This requires post-load sorting as applications_count is computed
                $query->sort('created', 'DESC');
                break;
            case 'newest':
            default:
                $query->sort('created', 'DESC');
                break;
        }

        $job_ids = $query->execute();
        $jobs = $storage->loadMultiple($job_ids);

        $formatted = [];
        foreach ($jobs as $job) {
            $formatted[] = [
                'id' => $job->id(),
                'title' => $job->getTitle(),
                'status' => $job->getStatus(),
                'status_label' => $this->getStatusLabel($job->getStatus()),
                'applications_count' => $job->getApplicationsCount(),
                'published_at' => $job->get('published_at')->value,
                'created' => $job->get('created')->value,
            ];
        }

        // Post-sort by applications if needed
        if ($sort === 'applications') {
            usort($formatted, fn($a, $b) => $b['applications_count'] <=> $a['applications_count']);
        }

        // Count by status for filter chips
        $status_counts = ['all' => 0, 'draft' => 0, 'published' => 0, 'paused' => 0, 'closed' => 0];
        $all_jobs = $storage->loadByProperties(['employer_id' => $user_id]);
        foreach ($all_jobs as $job) {
            $status_counts['all']++;
            $s = $job->getStatus();
            if (isset($status_counts[$s])) {
                $status_counts[$s]++;
            }
        }

        return [
            '#theme' => 'employer_jobs',
            '#jobs' => $formatted,
            '#filters' => [
                'status' => $status_filter,
                'search' => $search,
                'sort' => $sort,
            ],
            '#status_counts' => $status_counts,
            '#attached' => [
                'library' => [
                    'jaraba_job_board/employer_jobs',
                    'core/drupal.dialog.ajax',
                ],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.query_args'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Lists all applications received.
     */
    public function applications(): array
    {
        return [
            '#markup' => $this->t('All received applications - Coming soon'),
        ];
    }

    /**
     * Displays analytics dashboard for employer.
     */
    public function analytics(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Get job postings for this employer
        $jobs = $this->entityTypeManager()
            ->getStorage('job_posting')
            ->loadByProperties(['employer_id' => $user_id]);

        $totalJobs = count($jobs);
        $activeJobs = 0;
        $totalApplications = 0;
        $totalViews = 0;

        foreach ($jobs as $job) {
            if ($job->get('status')->value === 'published') {
                $activeJobs++;
            }
            $totalApplications += $job->getApplicationsCount();
            $totalViews += (int) ($job->get('views_count')->value ?? 0);
        }

        // Calculate conversion rate
        $conversionRate = $totalViews > 0 ? round(($totalApplications / $totalViews) * 100, 1) : 0;

        return [
            '#theme' => 'my_company_analytics',
            '#stats' => [
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'total_applications' => $totalApplications,
                'total_views' => $totalViews,
                'conversion_rate' => $conversionRate,
            ],
            '#jobs' => array_map(function ($job) {
                return [
                    'id' => $job->id(),
                    'title' => $job->getTitle(),
                    'status' => $job->get('status')->value,
                    'applications' => $job->getApplicationsCount(),
                    'views' => (int) ($job->get('views_count')->value ?? 0),
                ];
            }, $jobs),
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/premium-components'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Lists applications for a specific job.
     */
    public function jobApplications(JobPostingInterface $job_posting): array
    {
        $applications = $this->applicationService->getJobApplications((int) $job_posting->id());

        $formatted = [];
        foreach ($applications as $app) {
            $formatted[] = [
                'id' => $app->id(),
                'candidate_id' => $app->getCandidateId(),
                'status' => $app->getStatus(),
                'match_score' => $app->getMatchScore(),
                'applied_at' => $app->getAppliedAt(),
            ];
        }

        return [
            '#theme' => 'job_applications',
            '#job' => [
                'id' => $job_posting->id(),
                'title' => $job_posting->getTitle(),
            ],
            '#applications' => $formatted,
            '#attached' => [
                'library' => ['jaraba_job_board/job_applications'],
            ],
        ];
    }

    /**
     * Shows application details.
     */
    public function applicationDetail(JobApplicationInterface $job_application): array
    {
        $job_application->markAsViewed();
        $job_application->save();

        return [
            '#theme' => 'application_detail',
            '#application' => [
                'id' => $job_application->id(),
                'status' => $job_application->getStatus(),
                'cover_letter' => $job_application->getCoverLetter(),
                'match_score' => $job_application->getMatchScore(),
                'applied_at' => $job_application->getAppliedAt(),
            ],
        ];
    }

    /**
     * Updates application status.
     */
    public function updateStatus(JobApplicationInterface $job_application, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $new_status = $data['status'] ?? NULL;

        if (!$new_status) {
            return new JsonResponse(['error' => 'Status required'], 400);
        }

        $this->applicationService->updateStatus(
            (int) $job_application->id(),
            $new_status,
            $data
        );

        return new JsonResponse(['success' => TRUE]);
    }

}
