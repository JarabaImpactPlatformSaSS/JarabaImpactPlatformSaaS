<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_job_board\Entity\JobApplication;
use Drupal\jaraba_job_board\Entity\JobApplicationInterface;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Service for managing job applications.
 *
 * Gestiona las candidaturas de usuarios a ofertas de empleo.
 */
class ApplicationService
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
     * The job posting service.
     */
    protected JobPostingService $jobPostingService;

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
        JobPostingService $job_posting_service,
        LoggerChannelFactoryInterface $logger_factory,
        EventDispatcherInterface $event_dispatcher,
        ?TenantContextService $tenantContext = NULL, // AUDIT-CONS-N10: Proper DI for tenant context.
    ) {
        $this->tenantContext = $tenantContext;
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->jobPostingService = $job_posting_service;
        $this->logger = $logger_factory->get('jaraba_job_board');
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * Creates a new job application.
     *
     * @param int $job_id
     *   The job posting ID.
     * @param int $candidate_id
     *   The candidate user ID.
     * @param array $data
     *   Additional application data (cover_letter, cv_file_id, etc.).
     *
     * @return \Drupal\jaraba_job_board\Entity\JobApplicationInterface|null
     *   The created application or NULL if already applied.
     */
    public function apply(int $job_id, int $candidate_id, array $data = []): ?JobApplicationInterface
    {
        // Feature gate: job_applications_per_day (Plan Elevación Empleabilidad v1 — Fase 4).
        try {
            /** @var \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService $featureGate */
            $featureGate = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate');
            $gateResult = $featureGate->check($candidate_id, 'job_applications_per_day');
            if (!$gateResult->isAllowed()) {
                $this->logger->notice('Feature gate denied job application for user @user: @msg', [
                    '@user' => $candidate_id,
                    '@msg' => $gateResult->getUpgradeMessage(),
                ]);

                // Fire upgrade trigger (Plan Elevación Empleabilidad v1 — Fase 5).
                try {
                    if ($this->tenantContext) {
                        $tenant = $this->tenantContext->getCurrentTenant();
                        if ($tenant) {
                            /** @var \Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService $upgradeTrigger */
                            $upgradeTrigger = \Drupal::service('ecosistema_jaraba_core.upgrade_trigger');
                            $upgradeTrigger->fire('limit_reached', $tenant, [
                                'feature_key' => 'job_applications_per_day',
                                'current_usage' => $gateResult->used,
                                'vertical' => 'empleabilidad',
                            ]);
                        }
                    }
                }
                catch (\Exception $e) {
                    // Non-critical — fail silently.
                }

                return NULL;
            }
        }
        catch (\Exception $e) {
            // Service not available — allow application (fail-open).
        }

        // Check if already applied
        if ($this->hasApplied($candidate_id, $job_id)) {
            $this->logger->notice('User @user already applied to job @job', [
                '@user' => $candidate_id,
                '@job' => $job_id,
            ]);
            return $this->getApplication($candidate_id, $job_id);
        }

        // Check if job is open for applications
        $job = $this->entityTypeManager->getStorage('job_posting')->load($job_id);
        if (!$job || !$job->isPublished()) {
            $this->logger->warning('Cannot apply to unpublished job @job', ['@job' => $job_id]);
            return NULL;
        }

        // Create application
        $application = $this->entityTypeManager
            ->getStorage('job_application')
            ->create([
                'job_id' => $job_id,
                'candidate_id' => $candidate_id,
                'status' => JobApplication::STATUS_APPLIED,
                'cover_letter' => $data['cover_letter'] ?? NULL,
                'cv_file_id' => $data['cv_file_id'] ?? NULL,
                'portfolio_url' => $data['portfolio_url'] ?? NULL,
                'source' => $data['source'] ?? 'organic',
                'referral_code' => $data['referral_code'] ?? NULL,
            ]);

        $application->save();

        // Record feature usage (Plan Elevación Empleabilidad v1 — Fase 4).
        try {
            /** @var \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService $featureGate */
            $featureGate = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate');
            $featureGate->recordUsage($candidate_id, 'job_applications_per_day');
        }
        catch (\Exception $e) {
            // Service not available — skip recording.
        }

        // Milestone triggers + email sequences (Plan Elevación Empleabilidad v1 — Fase 5/6).
        try {
            $totalApps = count($this->getCandidateApplications($candidate_id));

            // 5-application engagement milestone → upgrade trigger.
            if ($totalApps === 5 && $this->tenantContext) {
                $tenant = $this->tenantContext->getCurrentTenant();
                if ($tenant) {
                    \Drupal::service('ecosistema_jaraba_core.upgrade_trigger')
                        ->fire('engagement_high', $tenant, [
                            'feature_key' => 'job_applications',
                            'milestone' => '5_applications',
                            'vertical' => 'empleabilidad',
                        ]);
                }
            }

            // 3rd application on free plan → upsell email sequence.
            if ($totalApps === 3 && \Drupal::hasService('ecosistema_jaraba_core.employability_feature_gate')) {
                $plan = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate')
                    ->getUserPlan($candidate_id);
                if ($plan === 'free' && \Drupal::hasService('ecosistema_jaraba_core.employability_email_sequence')) {
                    \Drupal::service('ecosistema_jaraba_core.employability_email_sequence')
                        ->enroll($candidate_id, 'SEQ_EMP_003');
                }
            }
        }
        catch (\Exception $e) {
            // Non-critical milestone tracking — fail silently.
        }

        // Increment job applications count
        $job->incrementApplicationsCount();
        $job->save();

        $this->logger->info('User @user applied to job @job', [
            '@user' => $candidate_id,
            '@job' => $job_id,
        ]);

        // Dispatch application event for ECA (notifications, etc.).
        $event = new \Symfony\Component\EventDispatcher\GenericEvent($application, [
            'type' => 'application_created',
            'job_id' => $job_id,
            'candidate_id' => $candidate_id,
            'application_id' => $application->id(),
        ]);
        $this->eventDispatcher->dispatch($event, 'jaraba_job_board.application.created');

        // Send notification emails.
        try {
            $mailManager = \Drupal::service('plugin.manager.mail');
            $candidate = $this->entityTypeManager->getStorage('user')->load($candidate_id);

            // Confirmation to candidate.
            if ($candidate && $candidate->getEmail()) {
                $mailManager->mail('jaraba_job_board', 'application_submitted', $candidate->getEmail(), $candidate->getPreferredLangcode(), [
                    'candidate_name' => $candidate->getDisplayName(),
                    'job_title' => $job->label(),
                ]);
            }

            // Notification to employer.
            $employerId = $job->get('employer_id')->target_id ?? NULL;
            if ($employerId) {
                $employer = $this->entityTypeManager->getStorage('user')->load($employerId);
                if ($employer && $employer->getEmail()) {
                    $mailManager->mail('jaraba_job_board', 'new_application', $employer->getEmail(), $employer->getPreferredLangcode(), [
                        'employer_name' => $employer->getDisplayName(),
                        'job_title' => $job->label(),
                        'candidate_name' => $candidate ? $candidate->getDisplayName() : 'Candidato',
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Failed to send application notifications: @error', ['@error' => $e->getMessage()]);
        }

        return $application;
    }

    /**
     * Checks if a user has already applied to a job.
     */
    public function hasApplied(int $candidate_id, int $job_id): bool
    {
        $application = $this->getApplication($candidate_id, $job_id);
        return $application !== NULL;
    }

    /**
     * Gets an application by candidate and job.
     */
    public function getApplication(int $candidate_id, int $job_id): ?JobApplicationInterface
    {
        $applications = $this->entityTypeManager
            ->getStorage('job_application')
            ->loadByProperties([
                'candidate_id' => $candidate_id,
                'job_id' => $job_id,
            ]);

        return !empty($applications) ? reset($applications) : NULL;
    }

    /**
     * Gets all applications for a candidate.
     *
     * @param int $candidate_id
     *   The candidate user ID.
     * @param string|null $status
     *   Optional status filter.
     *
     * @return \Drupal\jaraba_job_board\Entity\JobApplicationInterface[]
     *   Array of applications.
     */
    public function getCandidateApplications(int $candidate_id, ?string $status = NULL): array
    {
        $properties = ['candidate_id' => $candidate_id];
        if ($status !== NULL) {
            $properties['status'] = $status;
        }

        return $this->entityTypeManager
            ->getStorage('job_application')
            ->loadByProperties($properties);
    }

    /**
     * Gets all applications for a job.
     *
     * @param int $job_id
     *   The job posting ID.
     * @param string|null $status
     *   Optional status filter.
     *
     * @return \Drupal\jaraba_job_board\Entity\JobApplicationInterface[]
     *   Array of applications sorted by match_score descending.
     */
    public function getJobApplications(int $job_id, ?string $status = NULL): array
    {
        $query = $this->entityTypeManager
            ->getStorage('job_application')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('job_id', $job_id)
            ->sort('match_score', 'DESC')
            ->sort('applied_at', 'DESC');

        if ($status !== NULL) {
            $query->condition('status', $status);
        }

        $ids = $query->execute();
        return $this->entityTypeManager->getStorage('job_application')->loadMultiple($ids);
    }

    /**
     * Updates application status.
     *
     * @param int $application_id
     *   The application ID.
     * @param string $status
     *   New status.
     * @param array $options
     *   Additional options (rejection_reason, rejection_feedback, etc.).
     *
     * @return \Drupal\jaraba_job_board\Entity\JobApplicationInterface|null
     *   The updated application or NULL.
     */
    public function updateStatus(int $application_id, string $status, array $options = []): ?JobApplicationInterface
    {
        $application = $this->entityTypeManager
            ->getStorage('job_application')
            ->load($application_id);

        if (!$application) {
            return NULL;
        }

        $old_status = $application->getStatus();
        $application->setStatus($status);

        // Handle special cases
        if ($status === JobApplication::STATUS_HIRED) {
            $application->hire($options['salary'] ?? NULL);

            // Close the job if all vacancies filled
            $job = $application->getJob();
            if ($job) {
                $hired_count = count($this->getJobApplications($job->id(), JobApplication::STATUS_HIRED));
                if ($hired_count >= $job->get('vacancies')->value) {
                    $job->setStatus('filled');
                    $job->save();
                }
            }
        } elseif ($status === JobApplication::STATUS_REJECTED) {
            $application->reject(
                $options['rejection_reason'] ?? NULL,
                $options['rejection_feedback'] ?? NULL
            );
        } elseif ($status === JobApplication::STATUS_INTERVIEWED && isset($options['interview_date'])) {
            $application->set('interview_scheduled_at', $options['interview_date']);
        } elseif ($status === JobApplication::STATUS_OFFERED && isset($options['salary'])) {
            $application->set('offered_salary', $options['salary']);
            $application->set('offer_expires_at', $options['offer_expires'] ?? NULL);
        }

        $application->save();

        $this->logger->info('Application @id status changed: @old -> @new', [
            '@id' => $application_id,
            '@old' => $old_status,
            '@new' => $status,
        ]);

        // Dispatch status change event for notifications.
        $statusEvent = new \Symfony\Component\EventDispatcher\GenericEvent($application, [
            'type' => 'application_status_changed',
            'application_id' => $application_id,
            'old_status' => $old_status,
            'new_status' => $status,
        ]);
        $this->eventDispatcher->dispatch($statusEvent, 'jaraba_job_board.application.status_changed');

        // Send status notification email to candidate.
        try {
            $candidate = $this->entityTypeManager->getStorage('user')->load($application->get('candidate_id')->target_id);
            $job = $application->getJob();
            if ($candidate && $candidate->getEmail() && $job) {
                $statusMailKey = match ($status) {
                    JobApplication::STATUS_SHORTLISTED => 'application_shortlisted',
                    JobApplication::STATUS_INTERVIEWED => 'interview_scheduled',
                    JobApplication::STATUS_OFFERED => 'offer_received',
                    JobApplication::STATUS_HIRED => 'application_hired',
                    JobApplication::STATUS_REJECTED => 'application_rejected',
                    default => NULL,
                };
                if ($statusMailKey) {
                    \Drupal::service('plugin.manager.mail')->mail('jaraba_job_board', $statusMailKey, $candidate->getEmail(), $candidate->getPreferredLangcode(), [
                        'candidate_name' => $candidate->getDisplayName(),
                        'job_title' => $job->label(),
                        'status' => $status,
                        'interview_date' => $options['interview_date'] ?? NULL,
                        'salary' => $options['salary'] ?? NULL,
                        'rejection_feedback' => $options['rejection_feedback'] ?? NULL,
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Failed to send status change notification: @error', ['@error' => $e->getMessage()]);
        }

        return $application;
    }

    /**
     * Gets application statistics for an employer.
     *
     * @param int $employer_id
     *   The employer profile ID.
     *
     * @return array
     *   Statistics array.
     */
    public function getEmployerStats(int $employer_id): array
    {
        $jobs = $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties(['employer_id' => $employer_id]);

        $job_ids = array_keys($jobs);

        if (empty($job_ids)) {
            return [
                'total_applications' => 0,
                'pending_review' => 0,
                'shortlisted' => 0,
                'interviewed' => 0,
                'hired' => 0,
                'avg_time_to_hire' => 0,
            ];
        }

        $storage = $this->entityTypeManager->getStorage('job_application');

        $total = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->count()
            ->execute();

        $pending = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->condition('status', JobApplication::STATUS_APPLIED)
            ->count()
            ->execute();

        $shortlisted = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->condition('status', JobApplication::STATUS_SHORTLISTED)
            ->count()
            ->execute();

        $interviewed = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->condition('status', JobApplication::STATUS_INTERVIEWED)
            ->count()
            ->execute();

        $hired = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->condition('status', JobApplication::STATUS_HIRED)
            ->count()
            ->execute();

        return [
            'total_applications' => (int) $total,
            'pending_review' => (int) $pending,
            'shortlisted' => (int) $shortlisted,
            'interviewed' => (int) $interviewed,
            'hired' => (int) $hired,
            'conversion_rate' => $total > 0 ? round(($hired / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Counts pending applications for an employer.
     *
     * @param int $employer_id
     *   The employer user ID.
     *
     * @return int
     *   Number of pending applications.
     */
    public function countPendingApplications(int $employer_id): int
    {
        $jobs = $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties(['employer_id' => $employer_id]);

        $job_ids = array_keys($jobs);

        if (empty($job_ids)) {
            return 0;
        }

        $storage = $this->entityTypeManager->getStorage('job_application');

        return (int) $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('job_id', $job_ids, 'IN')
            ->condition('status', JobApplication::STATUS_APPLIED)
            ->count()
            ->execute();
    }

}

