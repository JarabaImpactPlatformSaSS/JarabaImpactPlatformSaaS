<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing compliance assessments.
 */
class ComplianceAssessmentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'control_info' => [
        'label' => $this->t('Control Information'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Framework, control ID, and tenant.'),
        'fields' => ['framework', 'control_id', 'control_name', 'tenant_id'],
      ],
      'evaluation' => [
        'label' => $this->t('Evaluation'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Assessment status and evidence.'),
        'fields' => ['assessment_status', 'evidence_notes', 'assessed_by', 'assessed_at'],
      ],
      'scheduling' => [
        'label' => $this->t('Review Schedule'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Next review date.'),
        'fields' => ['next_review'],
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
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
