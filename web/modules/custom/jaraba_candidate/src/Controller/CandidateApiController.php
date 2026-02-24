<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_candidate\Service\CandidateProfileService;
use Drupal\jaraba_candidate\Service\CvBuilderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller for candidate operations.
 */
class CandidateApiController extends ControllerBase
{

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * The CV builder service.
     */
    protected CvBuilderService $cvBuilder;

    /**
     * Constructor.
     */
    public function __construct(CandidateProfileService $profile_service, CvBuilderService $cv_builder)
    {
        $this->profileService = $profile_service;
        $this->cvBuilder = $cv_builder;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_candidate.profile'),
            $container->get('jaraba_candidate.cv_builder')
        );
    }

    /**
     * Gets the current user's profile.
     */
    public function getProfile(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], 404);
        }

        return new JsonResponse([
            'id' => $profile->id(),
            'full_name' => $profile->getFullName(),
            'headline' => $profile->getHeadline(),
            'summary' => $profile->getSummary(),
            'city' => $profile->getCity(),
            'experience_years' => $profile->getExperienceYears(),
            'completion' => $profile->getCompletionPercent(),
            'availability' => $profile->getAvailability(),
        ]);
    }

    /**
     * Fields allowed to be updated via the public API.
     */
    private const ALLOWED_PROFILE_FIELDS = [
        'first_name',
        'last_name',
        'headline',
        'summary',
        'city',
        'country',
        'phone',
        'experience_years',
        'desired_salary_min',
        'desired_salary_max',
        'availability',
        'job_search_status',
        'remote_preference',
        'relocation_willing',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'website_url',
    ];

    /**
     * Updates the current user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid request body'], 400);
        }

        $updated = [];
        foreach ($data as $field => $value) {
            if (in_array($field, self::ALLOWED_PROFILE_FIELDS, TRUE) && $profile->hasField($field)) {
                $profile->set($field, $value);
                $updated[] = $field;
            }
        }

        if (empty($updated)) {
            return new JsonResponse(['error' => 'No valid fields to update'], 400);
        }

        $profile->save();

        return new JsonResponse(['success' => TRUE, 'message' => 'Profile updated', 'updated_fields' => $updated]);
    }

    /**
     * Fields allowed for experience creation/update.
     */
    private const ALLOWED_EXPERIENCE_FIELDS = [
        'company_name',
        'job_title',
        'description',
        'location',
        'start_date',
        'end_date',
        'is_current',
    ];

    /**
     * Gets experiences for the current user's profile.
     */
    public function getExperiences(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();

        try {
            $experiences = $this->entityTypeManager()
                ->getStorage('candidate_experience')
                ->loadByProperties(['user_id' => $user_id]);
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
            return new JsonResponse(['experiences' => []]);
        }

        $formatted = [];
        foreach ($experiences as $exp) {
            $formatted[] = [
                'id' => (int) $exp->id(),
                'company_name' => $exp->getCompanyName(),
                'job_title' => $exp->getJobTitle(),
                'description' => $exp->getDescription(),
                'location' => $exp->getLocation(),
                'start_date' => $exp->getStartDate(),
                'end_date' => $exp->getEndDate(),
                'is_current' => $exp->isCurrent(),
            ];
        }

        // Sort by start_date descending (most recent first).
        usort($formatted, fn($a, $b) => ($b['start_date'] ?? 0) <=> ($a['start_date'] ?? 0));

        return new JsonResponse(['experiences' => $formatted]);
    }

    /**
     * Adds an experience to the current user's profile.
     */
    public function addExperience(Request $request): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $data = json_decode($request->getContent(), TRUE);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid request body'], 400);
        }

        // Validate required fields.
        if (empty($data['company_name']) || empty($data['job_title'])) {
            return new JsonResponse(['error' => 'company_name and job_title are required'], 400);
        }

        if (empty($data['start_date'])) {
            return new JsonResponse(['error' => 'start_date is required'], 400);
        }

        // Build entity values from allowed fields only.
        $values = ['user_id' => $user_id];
        foreach ($data as $field => $value) {
            if (in_array($field, self::ALLOWED_EXPERIENCE_FIELDS, TRUE)) {
                $values[$field] = $value;
            }
        }

        // Link to profile if available.
        $profile = $this->profileService->getProfileByUserId($user_id);
        if ($profile) {
            $values['profile_id'] = (int) $profile->id();
        }

        try {
            $experience = $this->entityTypeManager()
                ->getStorage('candidate_experience')
                ->create($values);
            $experience->save();
        }
        catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create experience'], 500);
        }

        return new JsonResponse([
            'success' => TRUE,
            'id' => (int) $experience->id(),
        ]);
    }

    /**
     * Gets skills for the profile.
     */
    public function getSkills(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], 404);
        }

        $skills = $this->profileService->getSkills((int) $profile->id());
        return new JsonResponse(['skills' => $skills]);
    }

    /**
     * Adds a skill to the current user's profile.
     */
    public function addSkill(Request $request): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();

        $data = json_decode($request->getContent(), TRUE);
        $skill_id = $data['skill_id'] ?? NULL;
        $level = $data['level'] ?? 'intermediate';
        $years_experience = (int) ($data['years_experience'] ?? 0);

        if (!$skill_id) {
            return new JsonResponse(['error' => 'skill_id is required'], 400);
        }

        // Check if skill already exists for this user
        $existing = $this->entityTypeManager()
            ->getStorage('candidate_skill')
            ->loadByProperties([
                'user_id' => $user_id,
                'skill_id' => $skill_id,
            ]);

        if (!empty($existing)) {
            return new JsonResponse(['error' => 'Skill already added'], 409);
        }

        // Create new candidate_skill entity
        $skill_entity = $this->entityTypeManager()
            ->getStorage('candidate_skill')
            ->create([
                'user_id' => $user_id,
                'skill_id' => $skill_id,
                'level' => $level,
                'years_experience' => $years_experience,
                'source' => 'manual',
            ]);

        $skill_entity->save();

        return new JsonResponse([
            'success' => TRUE,
            'id' => $skill_entity->id(),
        ]);
    }

    /**
     * Deletes a skill from the current user's profile.
     */
    public function deleteSkill(int $skill_entity_id): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();

        $skill_entity = $this->entityTypeManager()
            ->getStorage('candidate_skill')
            ->load($skill_entity_id);

        if (!$skill_entity) {
            return new JsonResponse(['error' => 'Skill not found'], 404);
        }

        // Check ownership
        if ($skill_entity->get('user_id')->target_id != $user_id) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $skill_entity->delete();

        return new JsonResponse(['success' => TRUE]);
    }

    /**
     * Gets CV data.
     */
    public function getCv(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], 404);
        }

        $data = $this->cvBuilder->collectCvData($profile);
        return new JsonResponse($data);
    }

    /**
     * Generates CV.
     */
    public function generateCv(Request $request): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['error' => 'Profile not found'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);
        $template = $data['template'] ?? 'modern';
        $format = $data['format'] ?? 'html';

        $result = $this->cvBuilder->generateCv($profile, $template, $format);

        return new JsonResponse([
            'success' => TRUE,
            'filename' => $result['filename'],
        ]);
    }

    /**
     * Gets profile completion data.
     */
    public function getCompletion(): JsonResponse
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new JsonResponse(['completion' => 0, 'missing' => []]);
        }

        return new JsonResponse([
            'completion' => $profile->getCompletionPercent(),
            'missing' => [],
        ]);
    }

    /**
     * Imports profile from LinkedIn.
     */
    public function importLinkedIn(Request $request): JsonResponse
    {
        return new JsonResponse([
            'success' => FALSE,
            'message' => 'LinkedIn import not yet implemented',
        ]);
    }

}
