<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services tagged with 'jaraba_ai_agents.tool' into the registry.
 */
class AgentToolPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->has('jaraba_ai_agents.tool_registry')) {
      return;
    }

    $definition = $container->findDefinition('jaraba_ai_agents.tool_registry');
    $taggedServices = $container->findTaggedServiceIds('jaraba_ai_agents.tool');

    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addToolService', [new Reference($id), $id]);
    }
  }

}
