<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;

/**
 * Controller for the entrepreneur copilot dashboard.
 */
class CopilotDashboardController extends ControllerBase
{

    /**
     * The feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Constructs a CopilotDashboardController object.
     */
    public function __construct(FeatureUnlockService $featureUnlock)
    {
        $this->featureUnlock = $featureUnlock;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_copilot_v2.feature_unlock')
        );
    }

    /**
     * Displays the entrepreneur dashboard.
     *
     * @return array
     *   A render array.
     */
    public function dashboard(): array
    {
        $unlockStatus = $this->featureUnlock->getUnlockStatus();
        $availableModes = $this->featureUnlock->getAvailableCopilotModes();

        return [
            '#theme' => 'copilot_dashboard',
            '#unlock_status' => $unlockStatus,
            '#available_modes' => $availableModes,
            '#attached' => [
                'library' => ['jaraba_copilot_v2/dashboard'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['entrepreneur_profile'],
            ],
        ];
    }

}
