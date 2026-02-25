<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_candidate\Entity\CandidateProfileInterface;
use Drupal\jaraba_candidate\Service\CandidateProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for candidate profile pages.
 */
class ProfileController extends ControllerBase
{

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * Constructor.
     */
    public function __construct(CandidateProfileService $profile_service)
    {
        $this->profileService = $profile_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_candidate.profile')
        );
    }

    /**
     * Displays a public candidate profile.
     */
    public function view(CandidateProfileInterface $candidate_profile): array
    {
        if (!$candidate_profile->isPublic() && $candidate_profile->getOwnerId() != $this->currentUser()->id()) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }

        $user_id = (int) $candidate_profile->getOwnerId();

        // Build photo URL if available.
        $photo_url = NULL;
        try {
            $photo_ref = $candidate_profile->get('photo')->target_id;
            if ($photo_ref) {
                $file = $this->entityTypeManager()->getStorage('file')->load($photo_ref);
                if ($file) {
                    $photo_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                }
            }
        }
        catch (\Exception $e) {
            // Photo not available.
        }

        // Availability label map.
        $availability_labels = [
            'active' => $this->t('Actively looking'),
            'passive' => $this->t('Open to opportunities'),
            'not_looking' => $this->t('Not looking'),
            'employed' => $this->t('Employed'),
        ];
        $availability_raw = $candidate_profile->getAvailability();

        // Experience level label map.
        $level_labels = [
            'entry' => $this->t('Entry level'),
            'junior' => $this->t('Junior'),
            'mid' => $this->t('Mid-level'),
            'senior' => $this->t('Senior'),
            'executive' => $this->t('Executive'),
        ];
        $exp_level_raw = $candidate_profile->get('experience_level')->value ?? '';

        // Education level label map.
        $edu_labels = [
            'secondary' => $this->t('Secondary'),
            'vocational' => $this->t('Vocational'),
            'bachelor' => $this->t('Bachelor'),
            'master' => $this->t('Master'),
            'phd' => $this->t('PhD'),
        ];
        $edu_level_raw = $candidate_profile->getEducationLevel();

        // Load skills (resilient).
        $skills = [];
        try {
            $skill_entities = $this->entityTypeManager()
                ->getStorage('candidate_skill')
                ->loadByProperties(['user_id' => $user_id]);
            foreach ($skill_entities as $skill_entity) {
                $skill_term_id = $skill_entity->get('skill_id')->target_id;
                if ($skill_term_id) {
                    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($skill_term_id);
                    if ($term) {
                        $skills[] = [
                            'name' => $term->label(),
                            'level' => $skill_entity->get('level')->value ?? 'intermediate',
                            'verified' => (bool) $skill_entity->get('is_verified')->value,
                        ];
                    }
                }
            }
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
        }

        // Load experiences (resilient).
        $experiences = [];
        try {
            $loaded = $this->entityTypeManager()
                ->getStorage('candidate_experience')
                ->loadByProperties(['user_id' => $user_id]);
            foreach ($loaded as $exp) {
                $experiences[] = [
                    'company_name' => $exp->getCompanyName(),
                    'job_title' => $exp->getJobTitle(),
                    'description' => $exp->getDescription(),
                    'location' => $exp->getLocation(),
                    'start_date' => $exp->getStartDate(),
                    'end_date' => $exp->getEndDate(),
                    'is_current' => $exp->isCurrent(),
                ];
            }
            usort($experiences, fn($a, $b) => ($b['start_date'] ?? 0) <=> ($a['start_date'] ?? 0));
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
        }

        // Load education (resilient).
        $educations = [];
        try {
            $loaded = $this->entityTypeManager()
                ->getStorage('candidate_education')
                ->loadByProperties(['user_id' => $user_id]);
            foreach ($loaded as $edu) {
                $educations[] = [
                    'institution' => $edu->get('institution')->value ?? '',
                    'degree' => $edu->get('degree')->value ?? '',
                    'field_of_study' => $edu->get('field_of_study')->value ?? '',
                    'start_date' => $edu->get('start_date')->value ?? NULL,
                    'end_date' => $edu->get('end_date')->value ?? NULL,
                ];
            }
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
        }

        $is_own_profile = ($user_id == $this->currentUser()->id());

        return [
            '#theme' => 'candidate_profile_view',
            '#profile' => [
                'full_name' => $candidate_profile->getFullName(),
                'headline' => $candidate_profile->getHeadline(),
                'summary' => $candidate_profile->getSummary(),
                'city' => $candidate_profile->getCity(),
                'province' => $candidate_profile->get('province')->value ?? '',
                'experience_years' => $candidate_profile->getExperienceYears(),
                'experience_level' => $level_labels[$exp_level_raw] ?? '',
                'education_level' => $edu_labels[$edu_level_raw] ?? '',
                'availability' => $availability_labels[$availability_raw] ?? '',
                'availability_raw' => $availability_raw,
                'completion_percent' => $candidate_profile->getCompletionPercent(),
                'photo_url' => $photo_url,
                'linkedin_url' => $candidate_profile->get('linkedin_url')->value ?? '',
                'github_url' => $candidate_profile->get('github_url')->value ?? '',
                'portfolio_url' => $candidate_profile->get('portfolio_url')->value ?? '',
                'website_url' => $candidate_profile->get('website_url')->value ?? '',
                'skills' => $skills,
                'experiences' => $experiences,
                'educations' => $educations,
            ],
            '#is_own_profile' => $is_own_profile,
            '#attached' => [
                'library' => ['jaraba_candidate/profile_view'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['candidate_profile:' . $candidate_profile->id()],
            ],
        ];
    }

    /**
     * Displays the current user's profile.
     */
    public function myProfile(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return [
                '#theme' => 'my_profile_empty',
                '#attached' => [
                    'library' => ['jaraba_candidate/profile_view'],
                ],
            ];
        }

        return $this->view($profile);
    }

    /**
     * Title callback for profile page.
     */
    public function profileTitle(CandidateProfileInterface $candidate_profile): string
    {
        return $candidate_profile->getFullName();
    }

    /**
     * Displays experience section.
     *
     * Supports slide-panel AJAX: returns bare HTML if XMLHttpRequest.
     */
    public function experienceSection(Request $request): array|Response
    {
        $user_id = (int) $this->currentUser()->id();

        // Load experiences (resilient to missing entity type).
        $experiences = [];
        try {
            $loaded = $this->entityTypeManager()
                ->getStorage('candidate_experience')
                ->loadByProperties(['user_id' => $user_id]);
            foreach ($loaded as $exp) {
                $experiences[] = [
                    'id' => $exp->id(),
                    'company_name' => $exp->getCompanyName(),
                    'job_title' => $exp->getJobTitle(),
                    'description' => $exp->getDescription(),
                    'location' => $exp->getLocation(),
                    'start_date' => $exp->getStartDate(),
                    'end_date' => $exp->getEndDate(),
                    'is_current' => $exp->isCurrent(),
                ];
            }
            // Sort by start_date descending.
            usort($experiences, fn($a, $b) => ($b['start_date'] ?? 0) <=> ($a['start_date'] ?? 0));
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
        }

        $build = [
            '#theme' => 'my_profile_experience',
            '#experiences' => $experiences,
            '#attached' => [
                'library' => ['jaraba_candidate/profile_experience'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            $html = (string) \Drupal::service('renderer')->render($build);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return $build;
    }

    /**
     * Displays education section.
     *
     * Supports slide-panel AJAX: returns bare HTML if XMLHttpRequest.
     */
    public function educationSection(Request $request): array|Response
    {
        $user_id = (int) $this->currentUser()->id();

        // Load education records (resilient to missing entity type).
        $educations = [];
        try {
            $loaded = $this->entityTypeManager()
                ->getStorage('candidate_education')
                ->loadByProperties(['user_id' => $user_id]);
            foreach ($loaded as $edu) {
                $educations[] = [
                    'id' => $edu->id(),
                    'institution' => $edu->get('institution')->value ?? '',
                    'degree' => $edu->get('degree')->value ?? '',
                    'field_of_study' => $edu->get('field_of_study')->value ?? '',
                    'start_date' => $edu->get('start_date')->value ?? NULL,
                    'end_date' => $edu->get('end_date')->value ?? NULL,
                ];
            }
        }
        catch (\Exception $e) {
            // Entity type may not be installed yet.
        }

        $build = [
            '#theme' => 'my_profile_education',
            '#educations' => $educations,
            '#attached' => [
                'library' => ['jaraba_candidate/profile_education'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 300,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            $html = (string) \Drupal::service('renderer')->render($build);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return $build;
    }

    /**
     * Displays and manages user's skills section.
     *
     * Supports slide-panel AJAX: returns bare HTML if XMLHttpRequest.
     */
    public function skillsSection(Request $request): array|Response
    {
        $user_id = (int) $this->currentUser()->id();

        // Get user's current skills
        $skill_entities = $this->entityTypeManager()
            ->getStorage('candidate_skill')
            ->loadByProperties(['user_id' => $user_id]);

        $user_skills = [];
        foreach ($skill_entities as $skill_entity) {
            $skill_term_id = $skill_entity->get('skill_id')->target_id;
            if ($skill_term_id) {
                $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($skill_term_id);
                if ($term) {
                    $user_skills[] = [
                        'id' => $skill_entity->id(),
                        'skill_id' => $skill_term_id,
                        'name' => $term->label(),
                        'level' => $skill_entity->get('level')->value ?? 'intermediate',
                        'years' => $skill_entity->get('years_experience')->value ?? 0,
                        'verified' => (bool) $skill_entity->get('is_verified')->value,
                    ];
                }
            }
        }

        // Get all available skills grouped by category
        $terms = $this->entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['vid' => 'skills']);

        $categories = [];
        $all_skills = [];
        foreach ($terms as $term) {
            $parent = $term->get('parent')->target_id;
            if (empty($parent) || $parent == 0) {
                // This is a category
                $categories[$term->id()] = [
                    'id' => $term->id(),
                    'name' => $term->label(),
                    'skills' => [],
                ];
            } else {
                // This is a skill
                $all_skills[] = [
                    'id' => $term->id(),
                    'name' => $term->label(),
                    'parent' => $parent,
                ];
            }
        }

        // Assign skills to categories
        foreach ($all_skills as $skill) {
            if (isset($categories[$skill['parent']])) {
                $categories[$skill['parent']]['skills'][] = $skill;
            }
        }

        $build = [
            '#theme' => 'my_profile_skills',
            '#user_skills' => $user_skills,
            '#categories' => array_values($categories),
            '#attached' => [
                'library' => ['jaraba_candidate/skills_manager'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['user:' . $user_id, 'taxonomy_term_list:skills'],
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            $html = (string) \Drupal::service('renderer')->render($build);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return $build;
    }

    /**
     * Edit profile form for current user.
     *
     * Supports slide-panel AJAX: if the request is XMLHttpRequest,
     * returns only the rendered form HTML (no page chrome).
     */
    public function editProfile(Request $request): array|Response
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            // Create a new profile for this user
            $profile = $this->profileService->createProfile($user_id);
            if (!$profile) {
                // Fallback: show premium empty state template.
                return [
                    '#theme' => 'my_profile_empty',
                    '#attached' => [
                        'library' => ['jaraba_candidate/profile_view'],
                    ],
                ];
            }
        }

        // Get the entity form and render it
        $form = \Drupal::service('entity.form_builder')->getForm($profile, 'default');

        // Slide-panel / AJAX → return only the form HTML
        if ($request->isXmlHttpRequest()) {
            $html = (string) \Drupal::service('renderer')->render($form);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return $form;
    }

}

