<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registra servicios taggeados como secciones de perfil de usuario.
 *
 * Patron identico a TenantSettingsSectionPass.
 *
 * @see \Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler\TenantSettingsSectionPass
 */
class UserProfileSectionPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->has('ecosistema_jaraba_core.user_profile_section_registry')) {
      return;
    }

    $definition = $container->findDefinition('ecosistema_jaraba_core.user_profile_section_registry');
    $taggedServices = $container->findTaggedServiceIds('ecosistema_jaraba_core.user_profile_section');

    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('addSection', [new Reference($id)]);
    }
  }

}
