<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;

/**
 * API controller for experiments.
 */
class ExperimentApiController extends ControllerBase
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
     * Constructs an ExperimentApiController object.
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
     * Returns list of available experiments.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with experiments.
     */
    public function list(Request $request): JsonResponse
    {
        $category = $request->query->get('category');
        $experiments = $this->experimentLibrary->getAvailableExperiments($category);

        return new JsonResponse([
            'success' => TRUE,
            'experiments' => $experiments,
            'count' => count($experiments),
        ]);
    }

    /**
     * Suggests experiments for a hypothesis.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with suggestions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $hypothesisType = $data['hypothesis_type'] ?? 'DESIRABILITY';
        $bmcBlock = $data['bmc_block'] ?? 'VP';

        $suggestions = $this->experimentLibrary->suggestExperiments($hypothesisType, $bmcBlock);

        return new JsonResponse([
            'success' => TRUE,
            'suggestions' => $suggestions,
            'count' => count($suggestions),
        ]);
    }

}
