<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jaraba_job_board\Service\EmployabilityMenuService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contextual menu block for Employability vertical.
 *
 * @Block(
 *   id = "employability_contextual_menu",
 *   admin_label = @Translation("Menú Contextual Empleabilidad"),
 *   category = @Translation("Empleabilidad")
 * )
 */
class EmployabilityContextualMenuBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The employability menu service.
     */
    protected EmployabilityMenuService $menuService;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EmployabilityMenuService $menu_service
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->menuService = $menu_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('jaraba_job_board.employability_menu')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        $items = $this->menuService->getMenuItems();
        $role = $this->menuService->detectUserRole();
        $roleLabel = $this->menuService->getRoleLabel();

        return [
            '#theme' => 'employability_contextual_menu',
            '#items' => $items,
            '#role' => $role,
            '#role_label' => $roleLabel,
            '#cache' => [
                'contexts' => ['user.roles', 'user.permissions'],
                'tags' => ['employability_menu'],
            ],
            '#attached' => [
                'library' => ['jaraba_job_board/contextual_menu'],
            ],
        ];
    }

}
