<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\jaraba_ai_agents\DependencyInjection\Compiler\AgentToolPass;

/**
 * ServiceProvider para jaraba_ai_agents.
 */
class JarabaAiAgentsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new AgentToolPass());
  }

}
