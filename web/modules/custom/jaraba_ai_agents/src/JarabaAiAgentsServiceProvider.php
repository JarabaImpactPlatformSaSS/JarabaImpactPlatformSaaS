<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * ServiceProvider para jaraba_ai_agents.
 *
 * Registra automáticamente herramientas tagueadas en el ToolRegistry.
 */
class JarabaAiAgentsServiceProvider extends ServiceProviderBase
{

    /**
     * {@inheritdoc}
     */
    public function alter(ContainerBuilder $container): void
    {
        // Encontrar ToolRegistry.
        if (!$container->hasDefinition('jaraba_ai_agents.tool_registry')) {
            return;
        }

        $registryDefinition = $container->getDefinition('jaraba_ai_agents.tool_registry');

        // Encontrar todos los servicios tagueados como 'jaraba_ai_agents.tool'.
        $taggedServices = $container->findTaggedServiceIds('jaraba_ai_agents.tool');

        foreach (array_keys($taggedServices) as $serviceId) {
            // Añadir llamada a register() para cada herramienta.
            $registryDefinition->addMethodCall('register', [new Reference($serviceId)]);
        }
    }

}
