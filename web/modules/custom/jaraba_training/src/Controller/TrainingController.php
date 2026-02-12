<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_training\Service\LadderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para páginas frontend del módulo Training.
 */
class TrainingController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected LadderService $ladderService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_training.ladder_service'),
        );
    }

    /**
     * Muestra la Escalera de Valor.
     *
     * @return array
     *   Render array.
     */
    public function ladder(): array
    {
        $products = $this->ladderService->getFullLadder();
        $progress = $this->ladderService->getUserProgress();

        return [
            '#theme' => 'training_ladder',
            '#products' => $products,
            '#current_level' => $progress['current_level'],
            '#attached' => [
                'library' => ['jaraba_training/ladder'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['training_product_list'],
            ],
        ];
    }

}
