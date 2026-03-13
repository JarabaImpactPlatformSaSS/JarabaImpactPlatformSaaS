<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

/**
 * Collects and organizes SetupWizardStep tagged services.
 *
 * Pattern: identical to TenantSettingsRegistry (TENANT-SETTINGS-HUB-001).
 * Steps are collected via SetupWizardCompilerPass and grouped by wizardId.
 *
 * Usage in controllers:
 *   $steps = $this->wizardRegistry->getStepsForWizard('coordinador_ei', $tenantId);
 *
 * Usage in Twig (via preprocess):
 *   {% include '.../_setup-wizard.html.twig' with { wizard: setup_wizard } only %}
 *
 * @see \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface
 * @see \Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler\SetupWizardCompilerPass
 */
class SetupWizardRegistry {

  /**
   * Collected wizard steps, grouped by wizard ID.
   *
   * @var \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface[][]
   */
  protected array $steps = [];

  /**
   * Registers a step into the registry.
   *
   * Called by SetupWizardCompilerPass for each tagged service.
   */
  public function addStep(SetupWizardStepInterface $step): void {
    $this->steps[$step->getWizardId()][] = $step;
  }

  /**
   * Returns all steps for a wizard, sorted by weight, with completion data.
   *
   * @param string $wizardId
   *   The wizard identifier (e.g., 'coordinador_ei').
   * @param int $tenantId
   *   The tenant ID for computing isComplete() and getCompletionData().
   *
   * @return array{wizard_id: string, is_complete: bool, completion_percentage: int, steps: array}
   *   Structured array ready for Twig rendering.
   */
  public function getStepsForWizard(string $wizardId, int $tenantId): array {
    $wizardSteps = $this->steps[$wizardId] ?? [];

    usort($wizardSteps, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    $steps = [];
    $completedCount = 0;
    $requiredCount = 0;
    $firstIncomplete = TRUE;

    foreach ($wizardSteps as $index => $step) {
      $isComplete = $step->isComplete($tenantId);
      $isOptional = $step->isOptional();

      if (!$isOptional) {
        $requiredCount++;
        if ($isComplete) {
          $completedCount++;
        }
      }

      // The "active" step is the first non-optional incomplete step.
      $isActive = FALSE;
      if (!$isComplete && !$isOptional && $firstIncomplete) {
        $isActive = TRUE;
        $firstIncomplete = FALSE;
      }

      $steps[] = [
        'id' => $step->getId(),
        'label' => $step->getLabel(),
        'description' => $step->getDescription(),
        'icon' => $step->getIcon(),
        'route' => $step->getRoute(),
        'route_params' => $step->getRouteParameters(),
        'use_slide_panel' => $step->useSlidePanel(),
        'slide_panel_size' => $step->getSlidePanelSize(),
        'is_complete' => $isComplete,
        'is_optional' => $isOptional,
        'is_active' => $isActive,
        'completion_data' => $step->getCompletionData($tenantId),
        'step_number' => $index + 1,
      ];
    }

    $percentage = $requiredCount > 0
      ? (int) round(($completedCount / $requiredCount) * 100)
      : 100;

    return [
      'wizard_id' => $wizardId,
      'is_complete' => $percentage === 100,
      'completion_percentage' => $percentage,
      'steps' => $steps,
    ];
  }

  /**
   * Quick check if a wizard has any registered steps.
   */
  public function hasWizard(string $wizardId): bool {
    return !empty($this->steps[$wizardId]);
  }

}
