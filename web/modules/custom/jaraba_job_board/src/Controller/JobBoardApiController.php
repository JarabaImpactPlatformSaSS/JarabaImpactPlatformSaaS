<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Drupal\jaraba_job_board\Service\MatchingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller for job board operations.
 */
class JobBoardApiController extends ControllerBase
{

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
    public function __construct(ApplicationService $application_service, MatchingService $matching_service)
    {
        $this->applicationService = $application_service;
        $this->matchingService = $matching_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_job_board.application'),
            $container->get('jaraba_job_board.matching')
        );
    }

    /**
     * Lists published jobs.
     */
    public function listJobs(Request $request): JsonResponse
    {
        $jobs = $this->entityTypeManager()
            ->getStorage('job_posting')
            ->loadByProperties(['status' => 'published']);

        $result = [];
        foreach ($jobs as $job) {
            $result[] = [
                'id' => $job->id(),
                'title' => $job->getTitle(),
                'location' => $job->getLocationCity(),
                'job_type' => $job->getJobType(),
                'published_at' => $job->get('published_at')->value,
            ];
        }

        return new JsonResponse(['jobs' => $result, 'total' => count($result)]);
    }

    /**
     * Gets a single job.
     */
    public function getJob(int $job_id): JsonResponse
    {
        $job = $this->entityTypeManager()->getStorage('job_posting')->load($job_id);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        return new JsonResponse([
            'id' => $job->id(),
            'title' => $job->getTitle(),
            'description' => $job->get('description')->value,
            'requirements' => $job->get('requirements')->value,
            'location' => $job->getLocationCity(),
            'job_type' => $job->getJobType(),
            'salary' => $job->getSalaryRange(),
        ]);
    }

    /**
     * Applies to a job.
     */
    public function apply(int $job_id, Request $request): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $data = json_decode($request->getContent(), TRUE);

        $application = $this->applicationService->apply($job_id, $user_id, $data);

        if (!$application) {
            return new JsonResponse(['error' => 'Could not apply'], 400);
        }

        return new JsonResponse([
            'success' => TRUE,
            'application_id' => $application->id(),
        ], 201);
    }

    /**
     * Gets user's applications.
     */
    public function getUserApplications(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $applications = $this->applicationService->getCandidateApplications($user_id);

        $result = [];
        foreach ($applications as $app) {
            $result[] = [
                'id' => $app->id(),
                'job_id' => $app->getJobId(),
                'status' => $app->getStatus(),
                'applied_at' => $app->getAppliedAt(),
            ];
        }

        return new JsonResponse(['applications' => $result]);
    }

    /**
     * Gets employer's applications.
     */
    public function getEmployerApplications(): JsonResponse
    {
        $userId = (int) $this->currentUser()->id();

        try {
            // Obtener los job_posting IDs del employer actual.
            $jobIds = $this->entityTypeManager()
                ->getStorage('job_posting')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('uid', $userId)
                ->execute();

            if (empty($jobIds)) {
                return new JsonResponse(['applications' => []]);
            }

            // Obtener aplicaciones para esos jobs.
            $applicationIds = $this->entityTypeManager()
                ->getStorage('job_application')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('job_id', $jobIds, 'IN')
                ->sort('created', 'DESC')
                ->range(0, 50)
                ->execute();

            $applications = $this->entityTypeManager()
                ->getStorage('job_application')
                ->loadMultiple($applicationIds);

            $result = [];
            foreach ($applications as $app) {
                $job = $app->getJob();
                $result[] = [
                    'id' => (int) $app->id(),
                    'job_id' => $job ? (int) $job->id() : NULL,
                    'job_title' => $job ? $job->getTitle() : '',
                    'candidate_id' => (int) $app->getOwnerId(),
                    'status' => $app->getStatus(),
                    'match_score' => $app->getMatchScore(),
                    'applied_at' => $app->getAppliedAt(),
                ];
            }

            return new JsonResponse(['applications' => $result]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_job_board')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse(['applications' => [], 'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']);
        }
    }

    /**
     * Gets match score for a job.
     */
    public function getMatchScore(int $job_id): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $job = $this->entityTypeManager()->getStorage('job_posting')->load($job_id);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        $score = $this->matchingService->calculateCandidateJobScore($user_id, $job);

        return new JsonResponse(['match_score' => $score]);
    }

    /**
     * Gets job recommendations.
     */
    public function getRecommendations(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $recommendations = $this->matchingService->getRecommendedJobs($user_id, 10);

        return new JsonResponse(['recommendations' => $recommendations]);
    }

    /**
     * Receives agent rating feedback.
     */
    public function submitAgentRating(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['rating'])) {
            return new JsonResponse(['error' => 'Rating is required'], 400);
        }

        $userId = (int) $this->currentUser()->id();

        \Drupal::logger('jaraba_job_board')->info('Agent rating: @rating from user @user (session: @session)', [
            '@rating' => $data['rating'],
            '@user' => $userId,
            '@session' => $data['session_id'] ?? 'unknown',
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

}
