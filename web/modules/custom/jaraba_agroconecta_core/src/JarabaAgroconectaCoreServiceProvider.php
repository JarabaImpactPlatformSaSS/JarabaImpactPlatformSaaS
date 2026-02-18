<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\jaraba_agroconecta_core\DependencyInjection\Compiler\CarrierPass;

/**
 * Service provider for AgroConecta Core.
 */
class JarabaAgroconectaCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new CarrierPass());
  }

}
