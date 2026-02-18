<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\jaraba_predictive\DependencyInjection\Compiler\FraudRulePass;

/**
 * ServiceProvider para jaraba_predictive.
 */
class JarabaPredictiveServiceProvider extends ServiceProviderBase {

  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new FraudRulePass());
  }

}
