<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers carrier adapters with the CarrierManager.
 */
class CarrierPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->has('jaraba_agroconecta_core.carrier_manager')) {
      return;
    }

    $definition = $container->findDefinition('jaraba_agroconecta_core.carrier_manager');
    $taggedServices = $container->findTaggedServiceIds('jaraba_agroconecta_core_carrier');

    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addCarrier', [new Reference($id)]);
    }
  }

}
