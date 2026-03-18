<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that collects daily action tagged services.
 *
 * Pattern: identical to SetupWizardCompilerPass.
 * Collects services tagged 'ecosistema_jaraba_core.daily_action'
 * and injects them into DailyActionsRegistry via addAction().
 *
 * @see \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry
 */
class DailyActionsCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('ecosistema_jaraba_core.daily_actions_registry')) {
      return;
    }

    $registry = $container->getDefinition('ecosistema_jaraba_core.daily_actions_registry');

    foreach ($container->findTaggedServiceIds('ecosistema_jaraba_core.daily_action') as $id => $tags) {
      $registry->addMethodCall('addAction', [new Reference($id)]);
    }
  }

}
