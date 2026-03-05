<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registra servicios taggeados como secciones de tenant settings.
 *
 * Patron identico a AgentToolPass (jaraba_ai_agents).
 *
 * @see \Drupal\jaraba_ai_agents\DependencyInjection\Compiler\AgentToolPass
 */
class TenantSettingsSectionPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->has('ecosistema_jaraba_core.tenant_settings_registry')) {
      return;
    }

    $definition = $container->findDefinition('ecosistema_jaraba_core.tenant_settings_registry');
    $taggedServices = $container->findTaggedServiceIds('ecosistema_jaraba_core.tenant_settings_section');

    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addSection', [new Reference($id)]);
    }
  }

}
