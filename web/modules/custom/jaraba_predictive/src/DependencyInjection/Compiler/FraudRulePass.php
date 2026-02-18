<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registra servicios tagueados como reglas de fraude en el FraudEngine.
 */
class FraudRulePass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    if (!$container->has('jaraba_predictive.fraud_engine')) {
      return;
    }

    $definition = $container->findDefinition('jaraba_predictive.fraud_engine');
    $taggedServices = $container->findTaggedServiceIds('jaraba_predictive.fraud_rule');

    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addRule', [new Reference($id)]);
    }
  }

}
