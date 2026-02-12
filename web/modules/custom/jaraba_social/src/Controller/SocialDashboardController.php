<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_social\Service\SocialPostService;

/**
 * Controller para el dashboard de Social Media.
 */
class SocialDashboardController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SocialPostService $postService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_social.post_service'),
        );
    }

    /**
     * Página principal del dashboard.
     */
    public function dashboard(): array
    {
        $stats = $this->postService->getStats();

        return [
            '#theme' => 'social_dashboard',
            '#stats' => $stats,
            '#attached' => [
                'library' => ['jaraba_social/dashboard'],
            ],
            '#cache' => ['max-age' => 0],
        ];
    }

}
