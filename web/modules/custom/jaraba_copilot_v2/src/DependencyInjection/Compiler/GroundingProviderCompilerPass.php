<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass que recolecta grounding providers para ContentGroundingService.
 *
 * Sigue el patron identico a SetupWizardCompilerPass y DailyActionsCompilerPass
 * en ecosistema_jaraba_core.
 */
class GroundingProviderCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('jaraba_copilot_v2.content_grounding')) {
      return;
    }

    $registry = $container->getDefinition('jaraba_copilot_v2.content_grounding');

    foreach ($container->findTaggedServiceIds('jaraba_copilot_v2.grounding_provider') as $id => $tags) {
      $registry->addMethodCall('addProvider', [new Reference($id)]);
    }
  }

}
