<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;

/**
 * Controller for the experiment library pages.
 */
class ExperimentController extends ControllerBase
{

    /**
     * The experiment library service.
     */
    protected ExperimentLibraryService $experimentLibrary;

    /**
     * The feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Constructs an ExperimentController object.
     */
    public function __construct(
        ExperimentLibraryService $experimentLibrary,
        FeatureUnlockService $featureUnlock
    ) {
        $this->experimentLibrary = $experimentLibrary;
        $this->featureUnlock = $featureUnlock;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_copilot_v2.experiment_library'),
            $container->get('jaraba_copilot_v2.feature_unlock')
        );
    }

    /**
     * Displays the experiment library.
     *
     * @return array
     *   A render array.
     */
    public function library(): array
    {
        $experiments = $this->experimentLibrary->getAvailableExperiments();
        $categories = $this->experimentLibrary->getCategories();

        return [
            '#theme' => 'experiment_library',
            '#experiments' => $experiments,
            '#categories' => $categories,
            '#attached' => [
                'library' => ['jaraba_copilot_v2/experiment_library'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Displays experiment detail.
     *
     * @param string $experiment_id
     *   The experiment ID.
     *
     * @return array
     *   A render array.
     */
    public function detail(string $experiment_id): array
    {
        $experiment = $this->experimentLibrary->getExperimentById($experiment_id);

        if (!$experiment) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        return [
            '#theme' => 'experiment_detail',
            '#experiment' => $experiment,
            '#cache' => [
                'contexts' => ['url'],
                'max-age' => 3600,
            ],
        ];
    }

}
