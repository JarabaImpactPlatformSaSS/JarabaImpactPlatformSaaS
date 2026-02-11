<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_mentoring\Service\MentorMatchingService;

/**
 * Controller for the mentor catalog frontend.
 */
class MentorCatalogController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected MentorMatchingService $matchingService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_mentoring.mentor_matching'),
        );
    }

    /**
     * Displays the mentor catalog.
     *
     * @return array
     *   Render array.
     */
    public function catalog(): array
    {
        $storage = $this->entityTypeManager()->getStorage('mentor_profile');

        // Get active mentors.
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'active')
            ->condition('is_available', TRUE)
            ->sort('average_rating', 'DESC')
            ->range(0, 20);

        $mentor_ids = $query->execute();
        $mentors = $storage->loadMultiple($mentor_ids);

        $mentor_cards = [];
        foreach ($mentors as $mentor) {
            /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor */
            $sectors = array_column($mentor->get('sectors')->getValue(), 'value');

            $mentor_cards[] = [
                'id' => $mentor->id(),
                'display_name' => $mentor->getDisplayName(),
                'headline' => $mentor->getHeadline(),
                'sectors' => $sectors,
                'certification_level' => $mentor->getCertificationLevel(),
                'average_rating' => number_format($mentor->getAverageRating(), 1),
                'total_sessions' => $mentor->get('total_sessions')->value ?? 0,
                'hourly_rate' => number_format($mentor->getHourlyRate(), 0),
                'is_available' => $mentor->isAvailable(),
                'url' => Url::fromRoute('jaraba_mentoring.mentor_public_profile', ['mentor_profile' => $mentor->id()])->toString(),
            ];
        }

        // Get filter options.
        $sector_options = [
            'comercio' => $this->t('Comercio Local'),
            'servicios' => $this->t('Servicios Profesionales'),
            'agro' => $this->t('Agroalimentario'),
            'hosteleria' => $this->t('Hostelería y Turismo'),
            'industria' => $this->t('Industria'),
            'tech' => $this->t('Tecnología'),
        ];

        $stage_options = [
            'idea' => $this->t('Idea / Validación'),
            'lanzamiento' => $this->t('Lanzamiento'),
            'crecimiento' => $this->t('Crecimiento'),
            'escalado' => $this->t('Escalado'),
            'consolidacion' => $this->t('Consolidación'),
        ];

        return [
            '#theme' => 'mentor_catalog',
            '#mentors' => $mentor_cards,
            '#filters' => [
                'sectors' => $sector_options,
                'stages' => $stage_options,
            ],
            '#empty_message' => $this->t('No hay mentores disponibles en este momento.'),
            '#attached' => [
                'library' => [
                    'jaraba_mentoring/mentor_catalog',
                ],
            ],
            '#cache' => [
                'tags' => ['mentor_profile_list'],
                'max-age' => 300,
            ],
        ];
    }

}
