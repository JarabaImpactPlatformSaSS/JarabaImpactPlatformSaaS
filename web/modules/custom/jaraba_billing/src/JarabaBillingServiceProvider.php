<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Conditional service injection for jaraba_billing.
 *
 * Injects optional fiscal module services into FiscalInvoiceDelegationService
 * when the corresponding modules are installed.
 *
 * FASE 11, entregable F11-5.
 */
class JarabaBillingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');

    // Fiscal delegation: inject optional fiscal module services.
    if ($container->hasDefinition('jaraba_billing.fiscal_delegation')) {
      $delegation = $container->getDefinition('jaraba_billing.fiscal_delegation');
      $args = $delegation->getArguments();

      // arg[2]: jaraba_verifactu.record_service.
      if (isset($modules['jaraba_verifactu'])) {
        $args[2] = new Reference('jaraba_verifactu.record_service');
      }

      // arg[3]: jaraba_facturae.xml_service.
      if (isset($modules['jaraba_facturae'])) {
        $args[3] = new Reference('jaraba_facturae.xml_service');
      }

      // arg[4]: jaraba_einvoice_b2b.delivery_service.
      if (isset($modules['jaraba_einvoice_b2b'])) {
        $args[4] = new Reference('jaraba_einvoice_b2b.delivery_service');
      }

      $delegation->setArguments($args);
    }
  }

}
