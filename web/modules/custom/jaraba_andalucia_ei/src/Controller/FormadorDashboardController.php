<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard for formadores in Andalucía +ei.
 *
 * ZERO-REGION-001: Controller returns minimal markup. All data flows via
 * hook_preprocess_page() → drupalSettings (ZERO-REGION-003).
 *
 * CONTROLLER-READONLY-001: Does NOT use protected readonly for inherited
 * $entityTypeManager property.
 */
class FormadorDashboardController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?TenantContextService $tenantContext,
    protected LoggerInterface $logger,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
      $container->has('ecosistema_jaraba_core.daily_actions_registry')
        ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
    );
  }

  /**
   * Renders the formador dashboard.
   *
   * ZERO-REGION-001: Returns only minimal markup. Real data is injected
   * via hook_preprocess_page() → $variables['#attached']['drupalSettings'].
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
