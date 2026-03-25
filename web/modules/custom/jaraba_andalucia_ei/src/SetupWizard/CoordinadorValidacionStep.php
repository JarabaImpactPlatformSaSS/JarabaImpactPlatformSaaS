<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 4: Validación Normativa — Coordinador Setup Wizard.
 *
 * Checks PIIL CV 2025 normative requirements:
 * - At least 50h of formación previstas
 * - At least 10h of orientación previstas
 * - No VoBo pending.
 *
 * This is an optional step: the program can operate without
 * full normative compliance, but the wizard warns about gaps.
 */
class CoordinadorValidacionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Minimum formación hours per PIIL normative.
   */
  protected const MIN_HORAS_FORMACION = 50.0;

  /**
   * Minimum orientación hours per PIIL normative.
   */
  protected const MIN_HORAS_ORIENTACION = 10.0;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.validacion';
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
    return $this->t('Validación normativa');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Verifica los requisitos PIIL: horas mínimas y VoBo');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 40;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'compliance',
      'name' => 'shield-check',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    // Points to the PIIL tab of the dashboard — not a form.
    return 'jaraba_andalucia_ei.coordinador_dashboard';
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
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    $data = $this->validateNormative($tenantId);
    return $data['cumple_formacion'] && $data['cumple_orientacion'] && $data['vobo_pendiente'] === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $data = $this->validateNormative($tenantId);

    $warnings = [];
    if (!$data['cumple_formacion']) {
      $warnings[] = (string) $this->t('Faltan @h horas de formación', [
        '@h' => number_format(self::MIN_HORAS_FORMACION - $data['horas_formacion'], 1),
      ]);
    }
    if (!$data['cumple_orientacion']) {
      $warnings[] = (string) $this->t('Faltan @h horas de orientación', [
        '@h' => number_format(self::MIN_HORAS_ORIENTACION - $data['horas_orientacion'], 1),
      ]);
    }
    if ($data['vobo_pendiente'] > 0) {
      $warnings[] = (string) $this->t('@count VoBo pendiente(s)', ['@count' => $data['vobo_pendiente']]);
    }

    $result = [
      'count' => 0,
    ];

    if (empty($warnings)) {
      $result['label'] = $this->t('Requisitos PIIL cumplidos');
    }
    else {
      $result['label'] = $this->t('Requisitos pendientes');
      $result['warning'] = implode(' · ', $warnings);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

  /**
   * Validates PIIL normative requirements from PlanFormativoEi data.
   *
   * @return array{horas_formacion: float, horas_orientacion: float, cumple_formacion: bool, cumple_orientacion: bool, vobo_pendiente: int}
   */
  protected function validateNormative(int $tenantId): array {
    $result = [
      'horas_formacion' => 0.0,
      'horas_orientacion' => 0.0,
      'cumple_formacion' => FALSE,
      'cumple_orientacion' => FALSE,
      'vobo_pendiente' => 0,
    ];

    try {
      // Get hours from active PlanFormativoEi.
      $planStorage = $this->entityTypeManager->getStorage('plan_formativo_ei');
      $planIds = $planStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'borrador', '<>')
        ->execute();

      if (!empty($planIds)) {
        $plans = $planStorage->loadMultiple($planIds);
        foreach ($plans as $plan) {
          $result['horas_formacion'] += (float) ($plan->get('horas_formacion_previstas')->value ?? 0);
          $result['horas_orientacion'] += (float) ($plan->get('horas_orientacion_previstas')->value ?? 0);
        }
      }

      $result['cumple_formacion'] = $result['horas_formacion'] >= self::MIN_HORAS_FORMACION;
      $result['cumple_orientacion'] = $result['horas_orientacion'] >= self::MIN_HORAS_ORIENTACION;

      // Check VoBo pending.
      $accionStorage = $this->entityTypeManager->getStorage('accion_formativa_ei');
      $result['vobo_pendiente'] = (int) $accionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', ['pendiente_vobo', 'vobo_enviado'], 'IN')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      // Fail gracefully — return defaults.
    }

    return $result;
  }

}
