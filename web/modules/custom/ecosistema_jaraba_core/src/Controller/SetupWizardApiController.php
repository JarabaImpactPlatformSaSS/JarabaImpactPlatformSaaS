<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for Setup Wizard status — SETUP-WIZARD-DAILY-001.
 *
 * Provides JSON endpoint for JS to refresh wizard state
 * after slide-panel form saves.
 */
class SetupWizardApiController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The setup wizard registry.
   */
  protected SetupWizardRegistry $wizardRegistry;

  /**
   * The tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructs a SetupWizardApiController.
   */
  public function __construct(
    SetupWizardRegistry $wizardRegistry,
    TenantContextService $tenantContext,
  ) {
    $this->wizardRegistry = $wizardRegistry;
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.setup_wizard_registry'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Returns wizard completion status as JSON.
   *
   * @param string $wizard_id
   *   The wizard identifier.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with completion_percentage, is_complete, steps[].
   */
  public function getStatus(string $wizard_id): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();

    if (!$tenantId || !$this->wizardRegistry->hasWizard($wizard_id)) {
      return new JsonResponse(['error' => 'Not found'], 404);
    }

    $data = $this->wizardRegistry->getStepsForWizard($wizard_id, $tenantId);

    // Serialize TranslatableMarkup to strings for JSON.
    foreach ($data['steps'] as &$step) {
      $step['label'] = (string) $step['label'];
      $step['description'] = (string) $step['description'];
      if (isset($step['completion_data']['label'])) {
        $step['completion_data']['label'] = (string) $step['completion_data']['label'];
      }
      if (isset($step['completion_data']['warning'])) {
        $step['completion_data']['warning'] = (string) $step['completion_data']['warning'];
      }
    }

    return new JsonResponse($data);
  }

}
