<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Plan Formativo — Coordinador Setup Wizard.
 *
 * Checks if the coordinator has created at least one active PlanFormativoEi.
 * Hybrid approach: isComplete() uses count, getCompletionData() adds warnings
 * if the plan exists but lacks required configuration (hours, carril).
 */
class CoordinadorPlanFormativoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.plan_formativo';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'coordinador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Plan Formativo');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Define el plan formativo con carril, horas y fechas');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'clipboard-list',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.hub.plan_formativo.add';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getActivePlanCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getActivePlanCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin plan formativo'),
      ];
    }

    // Hybrid check: plan exists but may lack critical fields.
    $warning = $this->checkPlanQuality($tenantId);

    $data = [
      'count' => $count,
      'label' => $this->t('@count plan(es) activo(s)', ['@count' => $count]),
    ];

    if ($warning !== NULL) {
      $data['warning'] = $warning;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of active plans for the tenant.
   */
  protected function getActivePlanCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('plan_formativo_ei');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'borrador', '<>')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Checks plan quality and returns warning if incomplete.
   */
  protected function checkPlanQuality(int $tenantId): ?TranslatableMarkup {
    try {
      $storage = $this->entityTypeManager->getStorage('plan_formativo_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'borrador', '<>')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $plan = $storage->load(reset($ids));
      if (!$plan) {
        return NULL;
      }

      // Check critical fields.
      $carril = $plan->get('carril')->value ?? '';
      $horasTotal = (float) ($plan->get('horas_totales_previstas')->value ?? 0);

      if (empty($carril)) {
        return $this->t('Plan sin carril asignado');
      }

      if ($horasTotal <= 0) {
        return $this->t('Plan sin horas configuradas');
      }

      return NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
