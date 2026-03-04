<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for FairUsePolicy ConfigEntity (add/edit).
 */
class FairUsePolicyForm extends EntityForm {

  /**
   * Valid enforcement actions.
   */
  protected const VALID_ACTIONS = ['warn', 'throttle', 'soft_block', 'hard_block'];

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['tier'] = [
      '#type' => 'select',
      '#title' => $this->t('Tier'),
      '#options' => [
        '_global' => $this->t('Global (fallback)'),
        'starter' => $this->t('Starter'),
        'professional' => $this->t('Professional'),
        'enterprise' => $this->t('Enterprise'),
      ],
      '#default_value' => $entity->getTier(),
      '#required' => TRUE,
    ];

    // Warning thresholds.
    $form['thresholds_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Warning Thresholds'),
      '#open' => TRUE,
    ];

    $form['thresholds_section']['warning_thresholds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Threshold percentages'),
      '#description' => $this->t('Comma-separated integers (e.g. 70,85,95).'),
      '#default_value' => implode(',', $entity->getWarningThresholds()),
    ];

    // Enforcement actions.
    $form['enforcement_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Enforcement Actions'),
      '#open' => TRUE,
    ];

    $form['enforcement_section']['enforcement_actions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enforcement actions'),
      '#description' => $this->t('One per line: resource|level|action. Levels: warning, critical, exceeded. Actions: warn, throttle, soft_block, hard_block. Example: ai_queries|critical|throttle'),
      '#default_value' => $this->flattenEnforcementActions($entity->getEnforcementActions()),
      '#rows' => 15,
    ];

    // Overage unit prices.
    $form['pricing_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Overage Unit Prices (EUR)'),
      '#open' => TRUE,
    ];

    $form['pricing_section']['overage_unit_prices'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Unit prices'),
      '#description' => $this->t('One per line: metric|price. Example: ai_tokens|0.00002'),
      '#default_value' => $this->flattenKeyValue($entity->getOverageUnitPrices()),
      '#rows' => 10,
    ];

    // Burst and grace.
    $form['limits_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Burst Tolerance & Grace Period'),
      '#open' => TRUE,
    ];

    $form['limits_section']['burst_tolerance_pct'] = [
      '#type' => 'number',
      '#title' => $this->t('Burst tolerance (%)'),
      '#description' => $this->t('Percentage over the limit allowed before enforcement. Enterprise: 15%, Professional: 5%, Starter: 0%.'),
      '#default_value' => $entity->getBurstTolerancePct(),
      '#min' => 0,
      '#max' => 100,
    ];

    $form['limits_section']['grace_period_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Grace period (hours)'),
      '#description' => $this->t('Hours of grace after first breach before enforcement applies.'),
      '#default_value' => $entity->getGracePeriodHours(),
      '#min' => 0,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
      '#rows' => 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy $entity */
    $entity = $this->entity;

    // Process warning thresholds.
    $thresholdsRaw = $form_state->getValue('warning_thresholds') ?? '';
    $thresholds = array_filter(
      array_map('intval', explode(',', $thresholdsRaw)),
      fn(int $v) => $v > 0 && $v <= 100
    );
    sort($thresholds);
    $entity->setWarningThresholds(array_values($thresholds));

    // Process enforcement actions.
    $actionsRaw = $form_state->getValue('enforcement_actions') ?? '';
    $entity->setEnforcementActions($this->parseEnforcementActions($actionsRaw));

    // Process overage prices.
    $pricesRaw = $form_state->getValue('overage_unit_prices') ?? '';
    $entity->setOverageUnitPrices($this->parseKeyValueFloat($pricesRaw));

    // Scalar values.
    $entity->setBurstTolerancePct((int) $form_state->getValue('burst_tolerance_pct'));
    $entity->setGracePeriodHours((int) $form_state->getValue('grace_period_hours'));
    $entity->setDescription((string) $form_state->getValue('description'));

    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Fair Use Policy %label created.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Fair Use Policy %label updated.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

  /**
   * Flatten enforcement_actions map to textarea format.
   */
  protected function flattenEnforcementActions(array $actions): string {
    $lines = [];
    foreach ($actions as $resource => $levels) {
      if (!is_array($levels)) {
        continue;
      }
      foreach ($levels as $level => $action) {
        $lines[] = "{$resource}|{$level}|{$action}";
      }
    }
    return implode("\n", $lines);
  }

  /**
   * Parse textarea lines into enforcement_actions map.
   */
  protected function parseEnforcementActions(string $raw): array {
    $actions = [];
    $lines = array_filter(array_map('trim', explode("\n", $raw)));

    foreach ($lines as $line) {
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) !== 3) {
        continue;
      }
      [$resource, $level, $action] = $parts;
      if (!in_array($action, self::VALID_ACTIONS, TRUE)) {
        continue;
      }
      $actions[$resource][$level] = $action;
    }

    return $actions;
  }

  /**
   * Flatten key=>value map to textarea format.
   */
  protected function flattenKeyValue(array $map): string {
    $lines = [];
    foreach ($map as $key => $value) {
      $lines[] = "{$key}|{$value}";
    }
    return implode("\n", $lines);
  }

  /**
   * Parse textarea key|value lines into map with float values.
   */
  protected function parseKeyValueFloat(string $raw): array {
    $map = [];
    $lines = array_filter(array_map('trim', explode("\n", $raw)));

    foreach ($lines as $line) {
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) !== 2) {
        continue;
      }
      $map[$parts[0]] = (float) $parts[1];
    }

    return $map;
  }

}
