<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Drupal\jaraba_job_board\Service\JobSearchService;
use Drupal\jaraba_job_board\Service\MatchingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for job search and listing.
 */
class JobSearchController extends ControllerBase
{

    /**
     * The job search service.
     */
    protected JobSearchService $searchService;

    /**
     * The application service.
     */
    protected ApplicationService $applicationService;

    /**
     * The matching service.
     */
    protected MatchingService $matchingService;

    /**
     * Constructor.
     */
    public function __construct(
        JobSearchService $search_service,
        ApplicationService $application_service,
        MatchingService $matching_service
    ) {
        $this->searchService = $search_service;
        $this->applicationService = $application_service;
        $this->matchingService = $matching_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_job_board.search'),
            $container->get('jaraba_job_board.application'),
            $container->get('jaraba_job_board.matching')
        );
    }

    /**
     * Displays the job search page.
     */
    public function index(Request $request): array
    {
        $filters = [
            'q' => $request->query->get('q'),
            'location' => $request->query->get('location'),
            'job_type' => $request->query->get('job_type'),
            'remote' => $request->query->get('remote'),
            'experience' => $request->query->get('experience'),
            'salary_min' => $request->query->get('salary_min'),
            'posted_within' => $request->query->get('posted_within', '30'),
        ];

        $page = (int) $request->query->get('page', 0);
        $limit = 20;

        // Get jobs with filters
        $result = $this->getFilteredJobs($filters, $page, $limit);

        // Get user match scores if authenticated
        $user_id = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;

        return [
            '#theme' => 'job_search_results',
            '#jobs' => $this->formatJobsForDisplay($result['jobs'], $user_id),
            '#total' => $result['total'],
            '#page' => $page,
            '#per_page' => $limit,
            '#filters' => $filters,
            '#facets' => $this->buildFacets(),
            '#attached' => [
                'library' => ['jaraba_job_board/search'],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.query_args'],
                'tags' => ['job_posting_list'],
                'max-age' => 900, // 15 minutes
            ],
        ];
    }

    /**
     * Displays a single job posting.
     */
    public function detail(JobPostingInterface $job_posting): array
    {
        $user_id = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;

        // Check if user has already applied
        $has_applied = FALSE;
        $application = NULL;
        $match_score = NULL;

        if ($user_id) {
            $application = $this->applicationService->getApplication($user_id, (int) $job_posting->id());
            $has_applied = $application !== NULL;

            if (!$has_applied) {
                $match_score = $this->matchingService->calculateCandidateJobScore($user_id, $job_posting);
            }
        }

        // Get employer info
        $employer = $this->getEmployerInfo($job_posting);

        // Get similar jobs
        $similar_jobs = $this->getSimilarJobs($job_posting);

        // Increment view count
        $views = (int) $job_posting->get('views_count')->value;
        $job_posting->set('views_count', $views + 1);
        $job_posting->save();

        // Build JSON-LD JobPosting schema for Google Job Search & GEO.
        $schema_org_json_ld = $this->buildJobPostingJsonLd($job_posting, $employer);

        return [
            '#theme' => 'job_posting_detail',
            '#schema_org_json_ld' => Markup::create($schema_org_json_ld),
            '#job' => [
                'id' => $job_posting->id(),
                'title' => $job_posting->getTitle(),
                'reference' => $job_posting->getReferenceCode(),
                'description' => $job_posting->get('description')->value,
                'requirements' => $job_posting->get('requirements')->value,
                'responsibilities' => $job_posting->get('responsibilities')->value,
                'benefits' => $job_posting->get('benefits')->value,
                'job_type' => $job_posting->getJobType(),
                'job_type_label' => $this->getJobTypeLabel($job_posting->getJobType()),
                'remote_type' => $job_posting->getRemoteType(),
                'remote_type_label' => $this->getRemoteTypeLabel($job_posting->getRemoteType()),
                'location' => $job_posting->getLocationCity(),
                'salary' => $job_posting->getSalaryRange(),
                'salary_visible' => (bool) $job_posting->get('salary_visible')->value,
                'experience_level' => $job_posting->get('experience_level')->value,
                'skills' => $job_posting->getSkillsRequired(),
                'is_featured' => $job_posting->isFeatured(),
                'published_at' => $job_posting->get('published_at')->value,
                'applications_count' => $job_posting->getApplicationsCount(),
                'application_method' => $job_posting->get('application_method')->value,
                'external_url' => $job_posting->get('external_url')->value,
            ],
            '#employer' => $employer,
            '#has_applied' => $has_applied,
            '#application' => $application ? [
                'id' => $application->id(),
                'status' => $application->getStatus(),
                'applied_at' => $application->getAppliedAt(),
            ] : NULL,
            '#match_score' => $match_score,
            '#similar_jobs' => $similar_jobs,
            '#can_apply' => !$has_applied && $job_posting->isPublished(),
            '#attached' => [
                'library' => ['jaraba_job_board/job_detail'],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.query_args'],
                'tags' => ['job_posting:' . $job_posting->id()],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Title callback for job detail page.
     */
    public function jobTitle(JobPostingInterface $job_posting): string
    {
        return $job_posting->getTitle() . ' - ' . $job_posting->getLocationCity();
    }

    /**
     * Gets filtered jobs.
     */
    protected function getFilteredJobs(array $filters, int $page, int $limit): array
    {
        $query = $this->entityTypeManager()
            ->getStorage('job_posting')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'published')
            ->sort('is_featured', 'DESC')
            ->sort('published_at', 'DESC')
            ->range($page * $limit, $limit);

        // Apply text search
        if (!empty($filters['q'])) {
            $query->condition('title', '%' . $filters['q'] . '%', 'LIKE');
        }

        // Apply location filter
        if (!empty($filters['location'])) {
            $query->condition('location_city', '%' . $filters['location'] . '%', 'LIKE');
        }

        // Apply job type filter
        if (!empty($filters['job_type'])) {
            $query->condition('job_type', $filters['job_type']);
        }

        // Apply remote filter
        if (!empty($filters['remote'])) {
            $query->condition('remote_type', $filters['remote']);
        }

        // Apply experience filter
        if (!empty($filters['experience'])) {
            $query->condition('experience_level', $filters['experience']);
        }

        // Apply posted within filter
        if (!empty($filters['posted_within'])) {
            $threshold = time() - ((int) $filters['posted_within'] * 24 * 60 * 60);
            $query->condition('published_at', $threshold, '>=');
        }

        $ids = $query->execute();
        $jobs = $this->entityTypeManager()->getStorage('job_posting')->loadMultiple($ids);

        // Get total count
        $count_query = clone $query;
        $total = $count_query->count()->execute();

        return [
            'jobs' => $jobs,
            'total' => (int) $total,
        ];
    }

    /**
     * Formats jobs for display.
     */
    protected function formatJobsForDisplay(array $jobs, ?int $user_id): array
    {
        $formatted = [];
        foreach ($jobs as $job) {
            $match_score = NULL;
            if ($user_id) {
                $match_score = $this->matchingService->calculateCandidateJobScore($user_id, $job);
            }

            $formatted[] = [
                'id' => $job->id(),
                'title' => $job->getTitle(),
                'location' => $job->getLocationCity(),
                'job_type' => $job->getJobType(),
                'job_type_label' => $this->getJobTypeLabel($job->getJobType()),
                'remote_type' => $job->getRemoteType(),
                'salary' => $job->getSalaryRange(),
                'salary_visible' => (bool) $job->get('salary_visible')->value,
                'is_featured' => $job->isFeatured(),
                'published_at' => $job->get('published_at')->value,
                'published_ago' => $this->formatTimeAgo($job->get('published_at')->value),
                'match_score' => $match_score,
                'url' => '/jobs/' . $job->id(),
            ];
        }
        return $formatted;
    }

    /**
     * Builds facets for filtering.
     */
    protected function buildFacets(): array
    {
        return [
            'job_type' => [
                'label' => $this->t('Job Type'),
                'options' => [
                    'full_time' => $this->t('Full-time'),
                    'part_time' => $this->t('Part-time'),
                    'contract' => $this->t('Contract'),
                    'internship' => $this->t('Internship'),
                    'freelance' => $this->t('Freelance'),
                ],
            ],
            'remote' => [
                'label' => $this->t('Remote'),
                'options' => [
                    'onsite' => $this->t('On-site'),
                    'hybrid' => $this->t('Hybrid'),
                    'remote' => $this->t('Full remote'),
                    'flexible' => $this->t('Flexible'),
                ],
            ],
            'experience' => [
                'label' => $this->t('Experience'),
                'options' => [
                    'entry' => $this->t('Entry level'),
                    'junior' => $this->t('Junior'),
                    'mid' => $this->t('Mid-level'),
                    'senior' => $this->t('Senior'),
                    'executive' => $this->t('Executive'),
                ],
            ],
            'posted_within' => [
                'label' => $this->t('Posted'),
                'options' => [
                    '1' => $this->t('Last 24 hours'),
                    '7' => $this->t('Last 7 days'),
                    '30' => $this->t('Last 30 days'),
                ],
            ],
        ];
    }

    /**
     * Gets employer information.
     */
    protected function getEmployerInfo(JobPostingInterface $job): array
    {
        // Cargar employer_profile por user_id del autor del JobPosting.
        $employerUid = $job->getOwnerId();
        try {
            $profiles = $this->entityTypeManager()
                ->getStorage('employer_profile')
                ->loadByProperties(['user_id' => $employerUid]);

            if (!empty($profiles)) {
                $employer = reset($profiles);

                // Obtener URL del logo.
                $logoUrl = NULL;
                if ($employer->hasField('logo') && !$employer->get('logo')->isEmpty()) {
                    $file = $this->entityTypeManager()
                        ->getStorage('file')
                        ->load($employer->get('logo')->target_id);
                    if ($file) {
                        $logoUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                    }
                }

                return [
                    'name' => $employer->getCompanyName(),
                    'logo_url' => $logoUrl,
                    'description' => $employer->hasField('description') ? ($employer->get('description')->value ?? '') : '',
                    'location' => $employer->hasField('city') ? ($employer->get('city')->value ?? $job->getLocationCity()) : $job->getLocationCity(),
                    'employees' => $employer->hasField('company_size') ? ($employer->get('company_size')->value ?? '') : '',
                    'industry' => '',
                    'is_verified' => $employer->isVerified(),
                ];
            }
        } catch (\Exception $e) {
            // employer_profile puede no estar instalado.
        }

        return [
            'name' => '',
            'logo_url' => NULL,
            'description' => '',
            'location' => $job->getLocationCity(),
            'employees' => '',
            'industry' => '',
        ];
    }

    /**
     * Gets similar jobs.
     */
    protected function getSimilarJobs(JobPostingInterface $job): array
    {
        try {
            $query = $this->entityTypeManager()
                ->getStorage('job_posting')
                ->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 'published')
                ->condition('id', $job->id(), '<>')
                ->range(0, 4)
                ->sort('published_at', 'DESC');

            // Priorizar por mismo tipo de trabajo.
            $jobType = $job->getJobType();
            if ($jobType) {
                $query->condition('job_type', $jobType);
            }

            $ids = $query->execute();

            // Si no hay suficientes del mismo tipo, buscar por ubicación.
            if (count($ids) < 4) {
                $fallbackQuery = $this->entityTypeManager()
                    ->getStorage('job_posting')
                    ->getQuery()
                    ->accessCheck(TRUE)
                    ->condition('status', 'published')
                    ->condition('id', array_merge([$job->id()], $ids ?: []), 'NOT IN')
                    ->range(0, 4 - count($ids))
                    ->sort('published_at', 'DESC');

                $location = $job->getLocationCity();
                if ($location) {
                    $fallbackQuery->condition('location_city', '%' . $location . '%', 'LIKE');
                }

                $extraIds = $fallbackQuery->execute();
                $ids = array_merge($ids, $extraIds);
            }

            if (empty($ids)) {
                return [];
            }

            $jobs = $this->entityTypeManager()->getStorage('job_posting')->loadMultiple($ids);
            $similar = [];
            foreach ($jobs as $similarJob) {
                $similar[] = [
                    'id' => $similarJob->id(),
                    'title' => $similarJob->getTitle(),
                    'location' => $similarJob->getLocationCity(),
                    'job_type' => $similarJob->getJobType(),
                    'job_type_label' => $this->getJobTypeLabel($similarJob->getJobType()),
                    'published_at' => $similarJob->get('published_at')->value,
                    'url' => '/jobs/' . $similarJob->id(),
                ];
            }
            return $similar;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Gets job type label.
     */
    protected function getJobTypeLabel(string $type): string
    {
        $labels = [
            'full_time' => $this->t('Full-time'),
            'part_time' => $this->t('Part-time'),
            'contract' => $this->t('Contract'),
            'internship' => $this->t('Internship'),
            'freelance' => $this->t('Freelance'),
        ];
        return (string) ($labels[$type] ?? $type);
    }

    /**
     * Gets remote type label.
     */
    protected function getRemoteTypeLabel(string $type): string
    {
        $labels = [
            'onsite' => $this->t('On-site'),
            'hybrid' => $this->t('Hybrid'),
            'remote' => $this->t('Full remote'),
            'flexible' => $this->t('Flexible'),
        ];
        return (string) ($labels[$type] ?? $type);
    }

    /**
     * Formats timestamp as "time ago".
     */
    protected function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        if ($diff < 3600) {
            return $this->t('Just now');
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $this->t('@count hour ago|@count hours ago', ['@count' => $hours]);
        }
        $days = floor($diff / 86400);
        return $this->t('@count day ago|@count days ago', ['@count' => $days]);
    }

    /**
     * Builds JSON-LD JobPosting schema for SEO and GEO.
     *
     * @param \Drupal\jaraba_job_board\Entity\JobPostingInterface $job
     *   The job posting entity.
     * @param array $employer
     *   Employer information array.
     *
     * @return string
     *   JSON-LD script tag.
     */
    protected function buildJobPostingJsonLd(JobPostingInterface $job, array $employer): string
    {
        $published = $job->get('published_at')->value ?? $job->get('created')->value;
        $json_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->getTitle(),
            'description' => strip_tags($job->get('description')->value ?? ''),
            'datePosted' => date('Y-m-d', (int) $published),
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->getLocationCity() ?: '',
                    'addressCountry' => 'ES',
                ],
            ],
        ];

        // Employment type mapping per schema.org.
        $type_map = [
            'full_time' => 'FULL_TIME',
            'part_time' => 'PART_TIME',
            'contract' => 'CONTRACTOR',
            'internship' => 'INTERN',
            'freelance' => 'CONTRACTOR',
        ];
        $job_type = $job->getJobType();
        if (isset($type_map[$job_type])) {
            $json_ld['employmentType'] = $type_map[$job_type];
        }

        // Remote type.
        $remote = $job->getRemoteType();
        if ($remote === 'remote') {
            $json_ld['jobLocationType'] = 'TELECOMMUTE';
        }

        // Hiring organization.
        if (!empty($employer['name'])) {
            $json_ld['hiringOrganization'] = [
                '@type' => 'Organization',
                'name' => $employer['name'],
            ];
            if (!empty($employer['logo_url'])) {
                $json_ld['hiringOrganization']['logo'] = $employer['logo_url'];
            }
        }

        // Salary.
        $salary = $job->getSalaryRange();
        if ($salary && $job->get('salary_visible')->value) {
            $json_ld['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => $salary['min'] ?? 0,
                    'maxValue' => $salary['max'] ?? 0,
                    'unitText' => strtoupper($salary['period'] ?? 'YEAR'),
                ],
            ];
        }

        return '<script type="application/ld+json">' . json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

}
