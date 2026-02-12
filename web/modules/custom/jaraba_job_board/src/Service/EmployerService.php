<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service for managing employer profiles and operations.
 *
 * Gestiona perfiles de empleadores, verificación,
 * estadísticas y acceso a ofertas publicadas.
 */
class EmployerService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Creates an employer profile.
   *
   * @param int $userId
   *   The user ID.
   * @param array $data
   *   Employer data: 'company_name', 'sector', 'size', 'description', 'website', 'location'.
   *
   * @return array
   *   Created employer profile data.
   */
  public function createEmployerProfile(int $userId, array $data): array {
    // Check for existing profile.
    $existing = $this->getEmployerProfile($userId);
    if ($existing) {
      return ['error' => 'Employer profile already exists', 'employer_id' => $existing['employer_id']];
    }

    $storage = $this->entityTypeManager->getStorage('employer_profile');
    $employer = $storage->create([
      'user_id' => $userId,
      'company_name' => $data['company_name'] ?? '',
      'sector' => $data['sector'] ?? '',
      'company_size' => $data['size'] ?? $data['company_size'] ?? '',
      'description' => $data['description'] ?? '',
      'website' => $data['website'] ?? '',
      'location' => $data['location'] ?? '',
      'verified' => FALSE,
      'status' => 'active',
    ]);

    try {
      $employer->save();
    }
    catch (\Exception $e) {
      return ['error' => 'Failed to create employer profile: ' . $e->getMessage()];
    }

    return [
      'employer_id' => (int) $employer->id(),
      'user_id' => $userId,
      'company_name' => $data['company_name'] ?? '',
      'sector' => $data['sector'] ?? '',
      'verified' => FALSE,
      'status' => 'active',
      'created' => TRUE,
    ];
  }

  /**
   * Gets an employer profile by user ID.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array|null
   *   Employer profile data or NULL.
   */
  public function getEmployerProfile(int $userId): ?array {
    $storage = $this->entityTypeManager->getStorage('employer_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $employer = $storage->load(reset($ids));
    if (!$employer) {
      return NULL;
    }

    return [
      'employer_id' => (int) $employer->id(),
      'user_id' => $userId,
      'company_name' => $employer->get('company_name')->value ?? '',
      'sector' => $employer->get('sector')->value ?? '',
      'company_size' => $employer->get('company_size')->value ?? '',
      'description' => $employer->get('description')->value ?? '',
      'website' => $employer->get('website')->value ?? '',
      'location' => $employer->get('location')->value ?? '',
      'verified' => (bool) ($employer->get('verified')->value ?? FALSE),
      'status' => $employer->get('status')->value ?? 'active',
    ];
  }

  /**
   * Updates an employer profile.
   *
   * @param int $userId
   *   The user ID.
   * @param array $data
   *   Fields to update.
   *
   * @return array
   *   Updated employer data.
   */
  public function updateEmployerProfile(int $userId, array $data): array {
    $storage = $this->entityTypeManager->getStorage('employer_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return ['error' => 'Employer profile not found'];
    }

    $employer = $storage->load(reset($ids));
    if (!$employer) {
      return ['error' => 'Failed to load employer profile'];
    }

    $allowedFields = ['company_name', 'sector', 'company_size', 'description', 'website', 'location'];

    foreach ($allowedFields as $field) {
      if (isset($data[$field]) && $employer->hasField($field)) {
        $employer->set($field, $data[$field]);
      }
    }

    try {
      $employer->save();
    }
    catch (\Exception $e) {
      return ['error' => 'Failed to update: ' . $e->getMessage()];
    }

    return $this->getEmployerProfile($userId) ?? ['error' => 'Update succeeded but profile not found'];
  }

  /**
   * Gets employer's job postings filtered by status.
   *
   * @param int $userId
   *   The employer user ID.
   * @param string $status
   *   Filter: 'published', 'draft', 'closed', 'all'.
   *
   * @return array
   *   List of job posting summaries.
   */
  public function getEmployerJobs(int $userId, string $status = 'all'): array {
    $storage = $this->entityTypeManager->getStorage('job_posting');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('employer_id', $userId)
      ->sort('created', 'DESC');

    if ($status !== 'all') {
      $query->condition('status', $status);
    }

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $jobs = $storage->loadMultiple($ids);
    $result = [];

    foreach ($jobs as $job) {
      $result[] = [
        'job_id' => (int) $job->id(),
        'title' => $job->label() ?? $job->get('title')->value ?? '',
        'status' => $job->get('status')->value ?? 'draft',
        'location' => $job->get('location')->value ?? '',
        'applications_count' => $this->countApplications((int) $job->id()),
        'created' => $job->get('created')->value ?? '',
      ];
    }

    return $result;
  }

  /**
   * Gets employer statistics.
   *
   * @param int $userId
   *   The employer user ID.
   *
   * @return array
   *   Stats: posted_jobs, active_jobs, total_applications, hires.
   */
  public function getEmployerStats(int $userId): array {
    $jobStorage = $this->entityTypeManager->getStorage('job_posting');

    try {
      $totalJobs = (int) $jobStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('employer_id', $userId)
        ->count()
        ->execute();

      $activeJobs = (int) $jobStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('employer_id', $userId)
        ->condition('status', 'published')
        ->count()
        ->execute();

      $jobIds = $jobStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('employer_id', $userId)
        ->execute();

      $totalApplications = 0;
      $hires = 0;

      if (!empty($jobIds)) {
        $appStorage = $this->entityTypeManager->getStorage('job_application');

        $totalApplications = (int) $appStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('job_id', array_values($jobIds), 'IN')
          ->count()
          ->execute();

        $hires = (int) $appStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('job_id', array_values($jobIds), 'IN')
          ->condition('status', 'hired')
          ->count()
          ->execute();
      }
    }
    catch (\Exception) {
      return [
        'posted_jobs' => 0,
        'active_jobs' => 0,
        'total_applications' => 0,
        'hires' => 0,
      ];
    }

    return [
      'posted_jobs' => $totalJobs,
      'active_jobs' => $activeJobs,
      'total_applications' => $totalApplications,
      'hires' => $hires,
      'avg_applications_per_job' => $totalJobs > 0 ? round($totalApplications / $totalJobs, 1) : 0.0,
    ];
  }

  /**
   * Marks an employer as verified.
   *
   * @param int $userId
   *   The employer user ID.
   *
   * @return bool
   *   TRUE if successfully verified.
   */
  public function verifyEmployer(int $userId): bool {
    $storage = $this->entityTypeManager->getStorage('employer_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return FALSE;
    }

    $employer = $storage->load(reset($ids));
    if (!$employer) {
      return FALSE;
    }

    try {
      $employer->set('verified', TRUE);
      $employer->save();
      return TRUE;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Gets applications for a specific job posting.
   *
   * @param int $jobId
   *   The job posting ID.
   *
   * @return array
   *   List of application summaries.
   */
  public function getApplicationsForJob(int $jobId): array {
    $storage = $this->entityTypeManager->getStorage('job_application');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('job_id', $jobId)
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $applications = $storage->loadMultiple($ids);
    $result = [];

    foreach ($applications as $app) {
      $result[] = [
        'application_id' => (int) $app->id(),
        'candidate_id' => (int) ($app->get('candidate_id')->target_id ?? $app->get('candidate_id')->value ?? 0),
        'status' => $app->get('status')->value ?? 'pending',
        'applied_at' => $app->get('created')->value ?? '',
        'cover_letter' => !empty($app->get('cover_letter')->value ?? ''),
      ];
    }

    return $result;
  }

  /**
   * Counts applications for a job.
   */
  protected function countApplications(int $jobId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('job_application');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('job_id', $jobId)
        ->count()
        ->execute();
    }
    catch (\Exception) {
      return 0;
    }
  }

}
