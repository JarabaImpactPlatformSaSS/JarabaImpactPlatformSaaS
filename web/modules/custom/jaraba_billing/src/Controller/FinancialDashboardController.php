<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_billing\Service\WalletService;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el Dashboard Financiero del Tenant.
 *
 * Visualiza el saldo del Wallet, historial de transacciones y consumo.
 */
class FinancialDashboardController extends ControllerBase {

  public function __construct(
    protected WalletService $walletService,
    protected TenantMeteringService $meteringService,
    protected TenantContextService $tenantContext,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.wallet'),
      $container->get('jaraba_billing.tenant_metering'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Renderiza el dashboard financiero.
   */
  public function build(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return ['#markup' => $this->t('Acceso denegado.')];
    }

    $tenantId = (int) $tenant->id();
    $balance = $this->walletService->getBalance($tenantId);
    $usage = $this->meteringService->getUsage((string) $tenantId);
    $forecast = $this->meteringService->getForecast((string) $tenantId);

    return [
      '#theme' => 'financial_dashboard',
      '#balance' => $balance,
      '#usage' => $usage,
      '#forecast' => $forecast,
      '#attached' => [
        'library' => ['jaraba_billing/financial-dashboard'],
      ],
    ];
  }

}
