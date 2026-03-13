<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that collects setup wizard step tagged services.
 *
 * Pattern: identical to TenantSettingsSectionPass.
 * Collects services tagged 'ecosistema_jaraba_core.setup_wizard_step'
 * and injects them into SetupWizardRegistry via addStep().
 *
 * @see \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry
 */
class SetupWizardCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('ecosistema_jaraba_core.setup_wizard_registry')) {
      return;
    }

    $registry = $container->getDefinition('ecosistema_jaraba_core.setup_wizard_registry');

    foreach ($container->findTaggedServiceIds('ecosistema_jaraba_core.setup_wizard_step') as $id => $tags) {
      $registry->addMethodCall('addStep', [new Reference($id)]);
    }
  }

}
