<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_candidate\Service\CandidateProfileService;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Drupal\jaraba_job_board\Service\MatchingService;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for JobSeeker Dashboard.
 *
 * Dashboard unificado para candidatos con:
 * - Resumen de perfil y completitud
 * - Aplicaciones activas
 * - Cursos en progreso
 * - Recomendaciones de empleo
 */
class DashboardController extends ControllerBase
{

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * The application service.
     */
    protected ApplicationService $applicationService;

    /**
     * The enrollment service.
     */
    protected EnrollmentService $enrollmentService;

    /**
     * The matching service.
     */
    protected MatchingService $matchingService;

    /**
     * Constructor.
     */
    public function __construct(
        CandidateProfileService $profile_service,
        ApplicationService $application_service,
        EnrollmentService $enrollment_service,
        MatchingService $matching_service
    ) {
        $this->profileService = $profile_service;
        $this->applicationService = $application_service;
        $this->enrollmentService = $enrollment_service;
        $this->matchingService = $matching_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_candidate.profile'),
            $container->get('jaraba_job_board.application'),
            $container->get('jaraba_lms.enrollment'),
            $container->get('jaraba_job_board.matching')
        );
    }

    /**
     * Displays the main JobSeeker dashboard.
     */
    public function index(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Get profile with completion
        $profile = $this->profileService->getProfileByUserId($user_id);
        $profile_data = $profile ? $this->formatProfileSummary($profile) : NULL;

        // Get active applications
        $applications = $this->applicationService->getCandidateApplications($user_id);
        $active_applications = $this->formatApplications(
            array_filter($applications, fn($a) => $a->isActive())
        );

        // Get learning progress
        $enrollments = $this->enrollmentService->getUserEnrollments($user_id, 'active');
        $learning_progress = $this->formatEnrollments($enrollments);

        // Get job recommendations
        $recommendations = $this->matchingService->getRecommendedJobs($user_id, 5);

        // Calculate overall stats
        $stats = $this->calculateStats($user_id, $applications, $enrollments);

        // Cross-vertical bridges (Plan Elevación Empleabilidad v1 — Fase 8).
        $cross_vertical_bridges = [];
        if (\Drupal::hasService('ecosistema_jaraba_core.employability_cross_vertical_bridge')) {
            try {
                $cross_vertical_bridges = \Drupal::service('ecosistema_jaraba_core.employability_cross_vertical_bridge')
                    ->evaluateBridges($user_id);
            }
            catch (\Exception $e) {
                // Non-critical — bridges are optional.
            }
        }

        return [
            '#theme' => 'jobseeker_dashboard',
            '#profile' => $profile_data,
            '#applications' => $active_applications,
            '#applications_count' => count($applications),
            '#learning' => $learning_progress,
            '#recommendations' => $recommendations,
            '#stats' => $stats,
            '#quick_actions' => $this->getQuickActions($profile),
            '#cross_vertical_bridges' => $cross_vertical_bridges,
            '#attached' => [
                'library' => ['jaraba_candidate/dashboard'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['candidate_profile:' . ($profile ? $profile->id() : 0)],
                'max-age' => 300,
            ],
        ];
    }

    /**
     * Displays job recommendations.
     */
    public function recommendations(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $recommendations = $this->matchingService->getRecommendedJobs($user_id, 20);

        return [
            '#theme' => 'jobseeker_recommendations',
            '#jobs' => $recommendations,
            '#attached' => [
                'library' => ['jaraba_candidate/recommendations'],
            ],
        ];
    }

    /**
     * Displays user statistics.
     */
    public function stats(): array
    {
        $user_id = (int) $this->currentUser()->id();

        // Get all applications
        $applications = $this->applicationService->getCandidateApplications($user_id);

        // Get all enrollments
        $enrollments = $this->enrollmentService->getUserEnrollments($user_id);

        // Calculate detailed stats
        $stats = [
            'applications' => $this->getApplicationStats($applications),
            'learning' => $this->getLearningStats($user_id),
            'profile_views' => $this->getProfileViews($user_id),
            'timeline' => $this->getActivityTimeline($user_id),
        ];

        return [
            '#theme' => 'jobseeker_stats',
            '#stats' => $stats,
            '#attached' => [
                'library' => ['jaraba_candidate/stats'],
            ],
        ];
    }

    /**
     * Formats profile summary for dashboard.
     */
    protected function formatProfileSummary($profile): array
    {
        return [
            'id' => $profile->id(),
            'full_name' => $profile->getFullName(),
            'headline' => $profile->getHeadline(),
            'completion' => $profile->getCompletionPercent(),
            'availability' => $profile->getAvailability(),
            'is_public' => $profile->isPublic(),
            'photo_url' => $this->getPhotoUrl($profile),
            'missing_sections' => $this->getMissingSections($profile),
        ];
    }

    /**
     * Gets missing profile sections.
     */
    protected function getMissingSections($profile): array
    {
        $missing = [];

        if (empty($profile->getHeadline())) {
            $missing[] = ['section' => 'headline', 'label' => $this->t('Professional headline')];
        }
        if (empty($profile->getSummary())) {
            $missing[] = ['section' => 'summary', 'label' => $this->t('Professional summary')];
        }
        // Verificar completitud de skills.
        try {
            $skillCount = (int) $this->entityTypeManager()
                ->getStorage('candidate_skill')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $profile->getOwnerId())
                ->count()
                ->execute();
            if ($skillCount === 0) {
                $missing[] = ['section' => 'skills', 'label' => $this->t('Skills')];
            }
        } catch (\Exception $e) {
            // Entidad puede no estar instalada.
        }

        // Verificar idiomas.
        try {
            $langCount = (int) $this->entityTypeManager()
                ->getStorage('candidate_language')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $profile->getOwnerId())
                ->count()
                ->execute();
            if ($langCount === 0) {
                $missing[] = ['section' => 'languages', 'label' => $this->t('Languages')];
            }
        } catch (\Exception $e) {
            // Entidad puede no estar instalada.
        }

        return $missing;
    }

    /**
     * Formats applications for display.
     */
    protected function formatApplications(array $applications): array
    {
        $formatted = [];
        foreach ($applications as $app) {
            $job = $app->getJob();
            $formatted[] = [
                'id' => $app->id(),
                'job_title' => $job ? $job->getTitle() : $this->t('(Job removed)'),
                'job_id' => $job ? $job->id() : NULL,
                'company' => $job ? $job->getLocationCity() : '', // Placeholder
                'status' => $app->getStatus(),
                'status_label' => $this->getStatusLabel($app->getStatus()),
                'applied_at' => $app->getAppliedAt(),
                'applied_ago' => $this->formatTimeAgo($app->getAppliedAt()),
                'match_score' => $app->getMatchScore(),
            ];
        }
        return $formatted;
    }

    /**
     * Formats enrollments for display.
     */
    protected function formatEnrollments(array $enrollments): array
    {
        $formatted = [];
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            $formatted[] = [
                'id' => $enrollment->id(),
                'course_title' => $course ? $course->getTitle() : $this->t('(Course removed)'),
                'course_id' => $course ? $course->id() : NULL,
                'progress' => $enrollment->getProgressPercent(),
                'status' => $enrollment->getStatus(),
                'enrolled_at' => $enrollment->getEnrolledAt(),
            ];
        }
        return $formatted;
    }

    /**
     * Calculates overall statistics.
     */
    protected function calculateStats(int $user_id, array $applications, array $enrollments): array
    {
        $total_apps = count($applications);
        $interviews = count(array_filter($applications, fn($a) => $a->getStatus() === 'interviewed'));
        $offers = count(array_filter($applications, fn($a) => $a->getStatus() === 'offered'));

        $completed_courses = count(array_filter($enrollments, fn($e) => $e->isCompleted()));
        $in_progress = count(array_filter($enrollments, fn($e) => $e->isActive()));

        return [
            'total_applications' => $total_apps,
            'interviews' => $interviews,
            'offers' => $offers,
            'response_rate' => $total_apps > 0 ? round(($interviews / $total_apps) * 100) : 0,
            'courses_completed' => $completed_courses,
            'courses_in_progress' => $in_progress,
        ];
    }

    /**
     * Gets quick actions based on profile state.
     */
    protected function getQuickActions($profile): array
    {
        $actions = [];

        if (!$profile || $profile->getCompletionPercent() < 80) {
            $actions[] = [
                'type' => 'complete_profile',
                'label' => $this->t('Complete your profile'),
                'description' => $this->t('A complete profile gets 3x more views'),
                'url' => '/my-profile/edit',
                'priority' => 'high',
            ];
        }

        if (!$profile || empty($profile->get('cv_file_id')->target_id)) {
            $actions[] = [
                'type' => 'build_cv',
                'label' => $this->t('Build your CV'),
                'description' => $this->t('Create a professional CV in minutes'),
                'url' => '/my-profile/cv',
                'priority' => 'medium',
            ];
        }

        $actions[] = [
            'type' => 'search_jobs',
            'label' => $this->t('Search jobs'),
            'url' => '/jobs',
            'priority' => 'normal',
        ];

        return $actions;
    }

    /**
     * Gets application stats.
     */
    protected function getApplicationStats(array $applications): array
    {
        $by_status = [];
        foreach ($applications as $app) {
            $status = $app->getStatus();
            $by_status[$status] = ($by_status[$status] ?? 0) + 1;
        }
        return $by_status;
    }

    /**
     * Gets learning stats from LMS.
     */
    protected function getLearningStats(int $user_id): array
    {
        $stats = [
            'total_hours' => 0,
            'certificates' => 0,
            'courses_started' => 0,
        ];

        try {
            // Consultar enrollments del usuario.
            $enrollmentStorage = $this->entityTypeManager()->getStorage('lms_enrollment');
            $enrollments = $enrollmentStorage->loadByProperties(['user_id' => $user_id]);

            $stats['courses_started'] = count($enrollments);

            foreach ($enrollments as $enrollment) {
                if ($enrollment->hasField('progress') && $enrollment->get('progress')->value == 100) {
                    $stats['certificates']++;
                }
                if ($enrollment->hasField('time_spent')) {
                    $stats['total_hours'] += (int) ($enrollment->get('time_spent')->value ?? 0);
                }
            }

            // Convertir minutos a horas.
            $stats['total_hours'] = round($stats['total_hours'] / 60, 1);
        } catch (\Exception $e) {
            // LMS puede no estar instalado.
        }

        return $stats;
    }

    /**
     * Gets profile view count.
     */
    protected function getProfileViews(int $user_id): int
    {
        // Consultar vistas al perfil desde el campo profile_views de CandidateProfile.
        try {
            $profiles = $this->entityTypeManager()
                ->getStorage('candidate_profile')
                ->loadByProperties(['user_id' => $user_id]);

            if (!empty($profiles)) {
                $profile = reset($profiles);
                if ($profile->hasField('profile_views')) {
                    return (int) ($profile->get('profile_views')->value ?? 0);
                }
            }
        } catch (\Exception $e) {
            // Campo puede no existir.
        }

        return 0;
    }

    /**
     * Gets recent activity timeline.
     */
    protected function getActivityTimeline(int $user_id): array
    {
        $activities = [];

        // 1. Actividades de solicitudes de empleo.
        try {
            $applications = $this->entityTypeManager()
                ->getStorage('job_application')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $user_id)
                ->sort('created', 'DESC')
                ->range(0, 5)
                ->execute();

            if (!empty($applications)) {
                $appEntities = $this->entityTypeManager()
                    ->getStorage('job_application')
                    ->loadMultiple($applications);
                foreach ($appEntities as $app) {
                    $activities[] = [
                        'type' => 'application',
                        'label' => $this->t('Applied to a job'),
                        'timestamp' => (int) $app->get('created')->value,
                    ];
                }
            }
        } catch (\Exception $e) {
            // job_application puede no estar instalado.
        }

        // 2. Actividades de cursos.
        try {
            $enrollments = $this->entityTypeManager()
                ->getStorage('lms_enrollment')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $user_id)
                ->sort('created', 'DESC')
                ->range(0, 5)
                ->execute();

            if (!empty($enrollments)) {
                $enrollmentEntities = $this->entityTypeManager()
                    ->getStorage('lms_enrollment')
                    ->loadMultiple($enrollments);
                foreach ($enrollmentEntities as $enrollment) {
                    $activities[] = [
                        'type' => 'learning',
                        'label' => $this->t('Enrolled in a course'),
                        'timestamp' => (int) $enrollment->get('created')->value,
                    ];
                }
            }
        } catch (\Exception $e) {
            // lms_enrollment puede no estar instalado.
        }

        // Ordenar por timestamp descendente.
        usort($activities, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, 10);
    }

    /**
     * Gets status label.
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'applied' => $this->t('Applied'),
            'screening' => $this->t('In screening'),
            'shortlisted' => $this->t('Shortlisted'),
            'interviewed' => $this->t('Interviewed'),
            'offered' => $this->t('Offer received'),
            'hired' => $this->t('Hired'),
            'rejected' => $this->t('Not selected'),
            'withdrawn' => $this->t('Withdrawn'),
        ];
        return (string) ($labels[$status] ?? $status);
    }

    /**
     * Gets photo URL.
     */
    protected function getPhotoUrl($profile): ?string
    {
        $file_id = $profile->get('photo')->target_id;
        if (!$file_id) {
            return NULL;
        }
        $file = $this->entityTypeManager()->getStorage('file')->load($file_id);
        return $file ? \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()) : NULL;
    }

    /**
     * Formats timestamp as time ago.
     */
    protected function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        if ($diff < 86400) {
            return $this->t('Today');
        }
        $days = floor($diff / 86400);
        if ($days == 1) {
            return $this->t('Yesterday');
        }
        if ($days < 7) {
            return $this->t('@days days ago', ['@days' => $days]);
        }
        return date('d M Y', $timestamp);
    }

}
