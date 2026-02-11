<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_mentoring\Entity\MentorProfile;

/**
 * Controller for mentor profile pages.
 */
class MentorProfileController extends ControllerBase
{

    /**
     * Displays the public mentor profile.
     *
     * @param \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor_profile
     *   The mentor profile entity.
     *
     * @return array
     *   Render array.
     */
    public function publicView(MentorProfile $mentor_profile): array
    {
        // Get packages for this mentor.
        $package_storage = $this->entityTypeManager()->getStorage('mentoring_package');
        $package_ids = $package_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $mentor_profile->id())
            ->condition('is_published', TRUE)
            ->sort('price', 'ASC')
            ->execute();

        $packages = $package_storage->loadMultiple($package_ids);

        $package_data = [];
        foreach ($packages as $package) {
            $package_data[] = [
                'id' => $package->id(),
                'title' => $package->get('title')->value,
                'description' => $package->get('description')->value,
                'package_type' => $package->get('package_type')->value,
                'sessions_included' => $package->get('sessions_included')->value,
                'session_duration' => $package->get('session_duration_minutes')->value,
                'price' => number_format((float) $package->get('price')->value, 0),
                'is_featured' => (bool) $package->get('is_featured')->value,
            ];
        }

        // Get reviews.
        $review_storage = $this->entityTypeManager()->getStorage('session_review');
        $review_ids = $review_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('reviewee_id', $mentor_profile->getOwnerId())
            ->condition('review_type', 'mentee_to_mentor')
            ->sort('created', 'DESC')
            ->range(0, 5)
            ->execute();

        $reviews = $review_storage->loadMultiple($review_ids);

        $review_data = [];
        foreach ($reviews as $review) {
            $review_data[] = [
                'rating' => $review->get('overall_rating')->value,
                'comment' => $review->get('comment')->value,
                'created' => $review->get('created')->value,
            ];
        }

        $sectors = array_column($mentor_profile->get('sectors')->getValue(), 'value');
        $stages = array_column($mentor_profile->get('business_stages')->getValue(), 'value');

        return [
            '#theme' => 'mentor_profile_public',
            '#mentor' => [
                'id' => $mentor_profile->id(),
                'display_name' => $mentor_profile->getDisplayName(),
                'headline' => $mentor_profile->getHeadline(),
                'bio' => $mentor_profile->get('bio')->value,
                'sectors' => $sectors,
                'stages' => $stages,
                'certification_level' => $mentor_profile->getCertificationLevel(),
                'average_rating' => number_format($mentor_profile->getAverageRating(), 1),
                'total_sessions' => $mentor_profile->get('total_sessions')->value ?? 0,
                'total_reviews' => $mentor_profile->get('total_reviews')->value ?? 0,
                'hourly_rate' => number_format($mentor_profile->getHourlyRate(), 0),
                'is_available' => $mentor_profile->isAvailable(),
            ],
            '#packages' => $package_data,
            '#reviews' => $review_data,
            '#attached' => [
                'library' => [
                    'jaraba_mentoring/mentor_catalog',
                ],
            ],
            '#cache' => [
                'tags' => ['mentor_profile:' . $mentor_profile->id()],
            ],
        ];
    }

    /**
     * Title callback for mentor profile.
     *
     * @param \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor_profile
     *   The mentor profile entity.
     *
     * @return string
     *   The page title.
     */
    public function title(MentorProfile $mentor_profile): string
    {
        return $mentor_profile->getDisplayName();
    }

    /**
     * Page to become a mentor.
     *
     * @return array
     *   Render array with become mentor form.
     */
    public function becomeMentor(): array
    {
        // Check if user already has a mentor profile.
        $current_user = $this->currentUser();

        $storage = $this->entityTypeManager()->getStorage('mentor_profile');
        $existing = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('user_id', $current_user->id())
            ->execute();

        if (!empty($existing)) {
            $profile_id = reset($existing);
            return $this->redirect('entity.mentor_profile.edit_form', ['mentor_profile' => $profile_id]);
        }

        // Create new profile entity and show form.
        $mentor_profile = $storage->create([
            'user_id' => $current_user->id(),
            'display_name' => $current_user->getDisplayName(),
        ]);

        $form = $this->entityFormBuilder()->getForm($mentor_profile, 'default');

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['become-mentor-page']],
            'intro' => [
                '#markup' => '<div class="become-mentor-intro"><h2>' . $this->t('Únete a nuestra red de mentores') . '</h2><p>' . $this->t('Comparte tu experiencia y ayuda a emprendedores a alcanzar su potencial.') . '</p></div>',
            ],
            'form' => $form,
        ];
    }

}
