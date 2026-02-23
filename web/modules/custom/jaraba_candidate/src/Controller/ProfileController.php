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

        return [
            '#theme' => 'candidate_profile_view',
            '#profile' => [
                'full_name' => $candidate_profile->getFullName(),
                'headline' => $candidate_profile->getHeadline(),
                'summary' => $candidate_profile->getSummary(),
                'city' => $candidate_profile->getCity(),
                'experience_years' => $candidate_profile->getExperienceYears(),
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
                    'library' => ['jaraba_candidate/profile'],
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
     */
    public function experienceSection(): array
    {
        return [
            '#markup' => $this->t('Work Experience section - Coming soon'),
        ];
    }

    /**
     * Displays education section.
     */
    public function educationSection(): array
    {
        return [
            '#markup' => $this->t('Education section - Coming soon'),
        ];
    }

    /**
     * Displays and manages user's skills section.
     */
    public function skillsSection(): array
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

        return [
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
                // Fallback: just show a message with link to create
                return [
                    '#markup' => '<div class="empty-state-premium" style="text-align: center; padding: 3rem;">
                        <h3>' . $this->t('No tienes un perfil todavía') . '</h3>
                        <p>' . $this->t('Crea tu perfil profesional para empezar a buscar oportunidades.') . '</p>
                        <a href="/admin/content/candidates/add" class="btn btn-primary">' . $this->t('Crear mi perfil') . '</a>
                    </div>',
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

