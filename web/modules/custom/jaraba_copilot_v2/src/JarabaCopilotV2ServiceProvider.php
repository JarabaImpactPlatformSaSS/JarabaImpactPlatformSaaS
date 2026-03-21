<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\jaraba_copilot_v2\DependencyInjection\Compiler\GroundingProviderCompilerPass;

/**
 * Service provider para jaraba_copilot_v2.
 *
 * Registra el CompilerPass que recolecta GroundingProviders tagged.
 */
class JarabaCopilotV2ServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new GroundingProviderCompilerPass());
  }

}
