<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing business diagnostics.
 */
class BusinessDiagnosticForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'business_info' => [
        'label' => $this->t('Business Information'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Basic business data for the diagnostic context.'),
        'fields' => ['business_name', 'business_sector', 'business_size', 'business_age_years', 'annual_revenue'],
      ],
      'responses' => [
        'label' => $this->t('Diagnostic Responses'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Responses collected during the wizard.'),
        'fields' => ['responses'],
      ],
      'program_context' => [
        'label' => $this->t('Program Context'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant association and Time-to-Value metrics.'),
        'fields' => ['tenant_id', 'maturity_ttv_score'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('Calculated score and recommendations.'),
        'fields' => ['overall_score', 'maturity_level', 'estimated_loss_annual', 'priority_gaps', 'recommended_path_id'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Diagnostic status and user assignment.'),
        'fields' => ['status', 'completed_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'gauge'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add recalculate button in results section for existing diagnostics.
    if (!$this->entity->isNew() && isset($form['premium_section_results'])) {
      $form['premium_section_results']['recalculate'] = [
        '#type' => 'button',
        '#value' => $this->t('Recalculate score'),
        '#attributes' => ['class' => ['btn-recalculate']],
        '#ajax' => [
          'callback' => '::recalculateScore',
          'wrapper' => 'premium-section-results',
        ],
        '#weight' => 100,
      ];
    }

    return $form;
  }

  /**
   * AJAX callback to recalculate the score.
   */
  public function recalculateScore(array &$form, FormStateInterface $form_state): array {
    $this->messenger()->addStatus($this->t('Score recalculated.'));
    return $form['premium_section_results'];
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
