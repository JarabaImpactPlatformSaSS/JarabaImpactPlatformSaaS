<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Vertical Retention Profile add/edit.
 */
class VerticalRetentionProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['vertical_id']['#group'] = 'basic_info';
    $form['label']['#group'] = 'basic_info';
    $form['status']['#group'] = 'basic_info';
    $form['max_inactivity_days']['#group'] = 'basic_info';

    $form['scoring'] = [
      '#type' => 'details',
      '#title' => $this->t('Health Score Weights'),
      '#description' => $this->t('JSON object with keys: engagement, adoption, satisfaction, support, growth. Values must sum to 100. Example: {"engagement": 25, "adoption": 35, "satisfaction": 15, "support": 10, "growth": 15}'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    $form['health_score_weights']['#group'] = 'scoring';

    $form['seasonality'] = [
      '#type' => 'details',
      '#title' => $this->t('Seasonality Configuration'),
      '#description' => $this->t('JSON arrays for calendar, usage patterns, and seasonal offers.'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    $form['seasonality_calendar']['#group'] = 'seasonality';
    $form['expected_usage_pattern']['#group'] = 'seasonality';
    $form['seasonal_offers']['#group'] = 'seasonality';

    $form['churn_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Churn Detection'),
      '#description' => $this->t('Vertical-specific churn signals and features.'),
      '#open' => TRUE,
      '#weight' => 30,
    ];
    $form['churn_risk_signals']['#group'] = 'churn_config';
    $form['critical_features']['#group'] = 'churn_config';

    $form['engagement'] = [
      '#type' => 'details',
      '#title' => $this->t('Re-engagement & Expansion'),
      '#open' => FALSE,
      '#weight' => 40,
    ];
    $form['reengagement_triggers']['#group'] = 'engagement';
    $form['upsell_signals']['#group'] = 'engagement';
    $form['playbook_overrides']['#group'] = 'engagement';

    return $form;
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

    // Validate seasonality_calendar is valid JSON array with 12 entries.
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

    $label = $this->entity->label();
    $messageArgs = ['%label' => $label];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Vertical Retention Profile %label has been created.', $messageArgs));
    }
    else {
      $this->messenger()->addStatus($this->t('Vertical Retention Profile %label has been updated.', $messageArgs));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
