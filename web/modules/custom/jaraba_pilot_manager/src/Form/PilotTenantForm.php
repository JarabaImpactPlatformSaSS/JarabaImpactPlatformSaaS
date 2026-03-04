<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad Pilot Tenant.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PilotTenantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'enrollment' => [
        'label' => $this->t('Inscripcion'),
        'icon' => ['category' => 'ui', 'name' => 'user-plus'],
        'description' => $this->t('Datos de inscripcion del tenant en el programa piloto.'),
        'fields' => ['pilot_program', 'tenant_id', 'enrollment_date', 'status'],
      ],
      'metrics' => [
        'label' => $this->t('Metricas'),
        'icon' => ['category' => 'analytics', 'name' => 'chart-bar'],
        'description' => $this->t('Metricas de activacion, retencion y engagement.'),
        'fields' => ['activation_score', 'retention_d30', 'engagement_score', 'churn_risk'],
      ],
      'conversion' => [
        'label' => $this->t('Conversion'),
        'icon' => ['category' => 'business', 'name' => 'trending-up'],
        'description' => $this->t('Datos de conversion a plan de pago.'),
        'fields' => ['conversion_date', 'converted_plan', 'onboarding_completed'],
      ],
      'activity' => [
        'label' => $this->t('Actividad'),
        'icon' => ['category' => 'ui', 'name' => 'clock'],
        'description' => $this->t('Actividad y notas del tenant.'),
        'fields' => ['last_activity', 'feedback_count', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'users'];
  }

}
