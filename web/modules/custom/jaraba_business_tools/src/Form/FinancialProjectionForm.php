<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Financial Projection entities.
 */
class FinancialProjectionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'projection' => [
        'label' => $this->t('Projection'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['title', 'canvas_id', 'scenario', 'period_months', 'initial_investment'],
      ],
      'revenue' => [
        'label' => $this->t('Revenue'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['revenue_projections', 'cost_projections', 'fixed_costs', 'variable_cost_percentage'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'fields' => ['break_even_month', 'assumptions', 'notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
