<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing vertical retention profiles.
 */
class VerticalRetentionProfileForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Vertical identification and status.'),
        'fields' => ['vertical_id', 'label', 'status', 'max_inactivity_days'],
      ],
      'scoring' => [
        'label' => $this->t('Health Score Weights'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('JSON weights that must sum to 100.'),
        'fields' => ['health_score_weights'],
      ],
      'seasonality' => [
        'label' => $this->t('Seasonality'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Calendar, usage patterns, and seasonal offers.'),
        'fields' => ['seasonality_calendar', 'expected_usage_pattern', 'seasonal_offers'],
      ],
      'churn_config' => [
        'label' => $this->t('Churn Detection'),
        'icon' => ['category' => 'ui', 'name' => 'alert'],
        'description' => $this->t('Churn signals and critical features.'),
        'fields' => ['churn_risk_signals', 'critical_features'],
      ],
      'engagement' => [
        'label' => $this->t('Re-engagement'),
        'icon' => ['category' => 'ui', 'name' => 'refresh'],
        'description' => $this->t('Triggers, upsell signals, and playbook overrides.'),
        'fields' => ['reengagement_triggers', 'upsell_signals', 'playbook_overrides'],
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate health_score_weights sums to 100.
    $weightsRaw = $form_state->getValue(['health_score_weights', 0, 'value']);
    if ($weightsRaw) {
      $weights = json_decode($weightsRaw, TRUE);
      if (!is_array($weights)) {
        $form_state->setErrorByName('health_score_weights', $this->t('Health Score Weights must be valid JSON.'));
      }
      elseif (array_sum($weights) !== 100) {
        $form_state->setErrorByName('health_score_weights', $this->t('Health Score Weights must sum to exactly 100. Current sum: @sum.', [
          '@sum' => array_sum($weights),
        ]));
      }
    }

    // Validate seasonality_calendar has 12 entries.
    $calendarRaw = $form_state->getValue(['seasonality_calendar', 0, 'value']);
    if ($calendarRaw) {
      $calendar = json_decode($calendarRaw, TRUE);
      if (!is_array($calendar)) {
        $form_state->setErrorByName('seasonality_calendar', $this->t('Seasonality Calendar must be valid JSON.'));
      }
      elseif (count($calendar) !== 12) {
        $form_state->setErrorByName('seasonality_calendar', $this->t('Seasonality Calendar must have exactly 12 entries (one per month).'));
      }
    }

    // Validate churn_risk_signals is valid JSON array.
    $signalsRaw = $form_state->getValue(['churn_risk_signals', 0, 'value']);
    if ($signalsRaw) {
      $signals = json_decode($signalsRaw, TRUE);
      if (!is_array($signals)) {
        $form_state->setErrorByName('churn_risk_signals', $this->t('Churn Risk Signals must be a valid JSON array.'));
      }
    }
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
