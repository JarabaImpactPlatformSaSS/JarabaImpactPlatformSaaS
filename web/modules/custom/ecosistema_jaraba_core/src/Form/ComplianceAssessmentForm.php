<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing ComplianceAssessment entities.
 */
class ComplianceAssessmentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('Información General'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Tenant, marco, fecha, evaluador y estado.'),
        'fields' => ['tenant_id', 'framework', 'assessment_date', 'assessor', 'status'],
      ],
      'results' => [
        'label' => $this->t('Resultados'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Puntuación y hallazgos de la evaluación.'),
        'fields' => ['overall_score', 'findings'],
      ],
      'remediation' => [
        'label' => $this->t('Remediación'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Plan de remediación y próxima revisión.'),
        'fields' => ['remediation_plan', 'next_review_date'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // JSON field descriptions.
    $section = 'premium_section_results';
    if (isset($form[$section]['findings']['widget'][0]['value'])) {
      $form[$section]['findings']['widget'][0]['value']['#description'] = $this->t('Introduzca los hallazgos en formato JSON. Ejemplo: [{"control":"AC-1","status":"fail","detail":"Falta política de acceso"}]');
    }

    $section = 'premium_section_remediation';
    if (isset($form[$section]['remediation_plan']['widget'][0]['value'])) {
      $form[$section]['remediation_plan']['widget'][0]['value']['#description'] = $this->t('Introduzca el plan de acciones en formato JSON. Ejemplo: [{"action":"Implementar MFA","responsible":"CTO","deadline":"2025-06-01","priority":"high"}]');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
