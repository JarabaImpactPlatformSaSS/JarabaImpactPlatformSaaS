<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a floating action button for the Canvas AI Assistant.
 *
 * @Block(
 *   id = "entrepreneur_agent_fab",
 *   admin_label = @Translation("FAB Asistente IA Canvas"),
 *   category = @Translation("Emprendimiento")
 * )
 */
class EntrepreneurAgentFabBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The current route match.
     */
    protected RouteMatchInterface $routeMatch;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RouteMatchInterface $route_match
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->routeMatch = $route_match;
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
            $container->get('current_route_match')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        $currentUser = \Drupal::currentUser();

        if ($currentUser->isAnonymous()) {
            return [];
        }

        // Check if user can access canvas
        if (
            !$currentUser->hasPermission('create business model canvas') &&
            !$currentUser->hasPermission('edit own business model canvas')
        ) {
            return [];
        }

        $userName = $currentUser->getDisplayName();
        $nameParts = explode(' ', $userName);
        $firstName = $nameParts[0] ?? $userName;

        // Check if we're on a canvas page to get canvas context
        $canvasId = NULL;
        $routeName = $this->routeMatch->getRouteName();
        if (
            $routeName === 'entity.business_model_canvas.canonical' ||
            $routeName === 'entity.business_model_canvas.edit_form'
        ) {
            $canvas = $this->routeMatch->getParameter('business_model_canvas');
            if ($canvas) {
                $canvasId = $canvas->id();
            }
        }

        return [
            '#theme' => 'entrepreneur_agent_fab',
            '#agent' => $this->getAgentConfig($firstName),
            '#user_name' => $firstName,
            '#cache' => [
                'contexts' => ['user.roles', 'user.permissions', 'route'],
                'tags' => ['entrepreneur_agent'],
            ],
            '#attached' => [
                'library' => ['jaraba_business_tools/entrepreneur_agent_fab'],
                'drupalSettings' => [
                    'entrepreneurAgent' => [
                        'canvasId' => $canvasId,
                        'userName' => $firstName,
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets agent configuration.
     */
    protected function getAgentConfig(string $userName = ''): array
    {
        $greeting_suffix = $userName ? ", {$userName}" : '';

        return [
            'id' => 'entrepreneur_assistant',
            'name' => $this->t('Copiloto Canvas'),
            'icon_category' => 'ai',
            'icon_name' => 'copilot',
            'color' => '#2e7d32', // Green for entrepreneurship
            'greeting' => $this->t('¡Hola@suffix! Soy tu Copiloto de Canvas. ¿En qué puedo ayudarte?', ['@suffix' => $greeting_suffix]),
            'actions' => [
                ['id' => 'generate_full_canvas', 'label' => $this->t('Generar canvas completo'), 'icon_category' => 'ai', 'icon_name' => 'sparkle'],
                ['id' => 'analyze_canvas', 'label' => $this->t('Analizar mi Canvas'), 'icon_category' => 'analytics', 'icon_name' => 'gauge'],
                ['id' => 'suggest_improvements', 'label' => $this->t('Mejorar propuesta'), 'icon_category' => 'ai', 'icon_name' => 'lightbulb'],
                ['id' => 'validate_model', 'label' => $this->t('Validar modelo'), 'icon_category' => 'ui', 'icon_name' => 'check-circle'],
            ],
        ];
    }

}
