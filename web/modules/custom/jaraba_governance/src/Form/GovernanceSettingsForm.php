<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Data Governance settings.
 *
 * Allows administrators to configure:
 * - Retention policies per entity type (days, action, grace period).
 * - Data masking rules for dev/staging environments.
 * - Classification level definitions (encryption, masking, cross-border).
 */
class GovernanceSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'jaraba_governance.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_governance_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    // =========================================================================
    // RETENTION POLICIES
    // =========================================================================
    $form['retention'] = [
      '#type' => 'details',
      '#title' => $this->t('Retention Policies'),
      '#open' => TRUE,
      '#description' => $this->t('Configure data retention periods and actions per entity type. Actions: delete (permanent removal), anonymize (replace PII), archive (soft-retain), keep (no action).'),
    ];

    $policies = $config->get('retention_policies') ?? [];
    foreach ($policies as $key => $policy) {
      $form['retention'][$key] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Policy: @key', ['@key' => $key]),
      ];

      $form['retention'][$key]['entity_type_' . $key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity Type'),
        '#default_value' => $policy['entity_type'] ?? '',
        '#size' => 40,
      ];

      $form['retention'][$key]['retention_days_' . $key] = [
        '#type' => 'number',
        '#title' => $this->t('Retention Days'),
        '#default_value' => $policy['retention_days'] ?? 0,
        '#min' => 0,
      ];

      $form['retention'][$key]['action_' . $key] = [
        '#type' => 'select',
        '#title' => $this->t('Action'),
        '#options' => [
          'delete' => $this->t('Delete'),
          'anonymize' => $this->t('Anonymize'),
          'archive' => $this->t('Archive'),
          'keep' => $this->t('Keep (no action)'),
        ],
        '#default_value' => $policy['action'] ?? 'keep',
      ];

      if (isset($policy['grace_days'])) {
        $form['retention'][$key]['grace_days_' . $key] = [
          '#type' => 'number',
          '#title' => $this->t('Grace Days'),
          '#default_value' => $policy['grace_days'] ?? 0,
          '#min' => 0,
        ];
      }

      if (isset($policy['legal_basis'])) {
        $form['retention'][$key]['legal_basis_' . $key] = [
          '#type' => 'textfield',
          '#title' => $this->t('Legal Basis'),
          '#default_value' => $policy['legal_basis'] ?? '',
          '#size' => 60,
        ];
      }
    }

    // =========================================================================
    // MASKING RULES
    // =========================================================================
    $form['masking'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Masking Rules'),
      '#description' => $this->t('Configure masking patterns for PII fields. Used when creating dev/staging database copies.'),
    ];

    $maskingRules = $config->get('masking_rules') ?? [];
    foreach ($maskingRules as $field => $rule) {
      $form['masking']['masking_' . $field] = [
        '#type' => 'textfield',
        '#title' => $this->t('Rule for @field', ['@field' => $field]),
        '#default_value' => $rule,
        '#size' => 60,
        '#description' => $this->t('Template with {id} placeholder, or faker type: faker_name, faker_nif, faker_iban, faker_phone, faker_address.'),
      ];
    }

    // =========================================================================
    // CLASSIFICATION LEVELS
    // =========================================================================
    $form['classification'] = [
      '#type' => 'details',
      '#title' => $this->t('Classification Level Definitions'),
      '#description' => $this->t('Define security requirements per classification level.'),
    ];

    $levels = $config->get('classification_levels') ?? [];
    foreach ($levels as $levelKey => $levelDef) {
      $form['classification'][$levelKey] = [
        '#type' => 'fieldset',
        '#title' => $levelDef['label'] ?? $levelKey,
      ];

      $form['classification'][$levelKey]['label_' . $levelKey] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $levelDef['label'] ?? '',
        '#size' => 30,
      ];

      $form['classification'][$levelKey]['encryption_' . $levelKey] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Encryption Required'),
        '#default_value' => $levelDef['encryption_required'] ?? FALSE,
      ];

      $form['classification'][$levelKey]['masking_' . $levelKey] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Masking Required'),
        '#default_value' => $levelDef['masking_required'] ?? FALSE,
      ];

      if (isset($levelDef['cross_border_restricted'])) {
        $form['classification'][$levelKey]['cross_border_' . $levelKey] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Cross-Border Restricted'),
          '#default_value' => $levelDef['cross_border_restricted'] ?? FALSE,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(self::CONFIG_NAME);

    // Save retention policies.
    $existingPolicies = $config->get('retention_policies') ?? [];
    foreach ($existingPolicies as $key => $policy) {
      $policy['entity_type'] = $form_state->getValue('entity_type_' . $key) ?? $policy['entity_type'] ?? '';
      $policy['retention_days'] = (int) ($form_state->getValue('retention_days_' . $key) ?? $policy['retention_days'] ?? 0);
      $policy['action'] = $form_state->getValue('action_' . $key) ?? $policy['action'] ?? 'keep';

      $graceValue = $form_state->getValue('grace_days_' . $key);
      if ($graceValue !== NULL) {
        $policy['grace_days'] = (int) $graceValue;
      }

      $legalValue = $form_state->getValue('legal_basis_' . $key);
      if ($legalValue !== NULL) {
        $policy['legal_basis'] = $legalValue;
      }

      $existingPolicies[$key] = $policy;
    }
    $config->set('retention_policies', $existingPolicies);

    // Save masking rules.
    $existingMasking = $config->get('masking_rules') ?? [];
    foreach ($existingMasking as $field => $rule) {
      $newValue = $form_state->getValue('masking_' . $field);
      if ($newValue !== NULL) {
        $existingMasking[$field] = $newValue;
      }
    }
    $config->set('masking_rules', $existingMasking);

    // Save classification levels.
    $existingLevels = $config->get('classification_levels') ?? [];
    foreach ($existingLevels as $levelKey => $levelDef) {
      $labelValue = $form_state->getValue('label_' . $levelKey);
      if ($labelValue !== NULL) {
        $existingLevels[$levelKey]['label'] = $labelValue;
      }
      $encValue = $form_state->getValue('encryption_' . $levelKey);
      if ($encValue !== NULL) {
        $existingLevels[$levelKey]['encryption_required'] = (bool) $encValue;
      }
      $maskValue = $form_state->getValue('masking_' . $levelKey);
      if ($maskValue !== NULL) {
        $existingLevels[$levelKey]['masking_required'] = (bool) $maskValue;
      }
      $crossValue = $form_state->getValue('cross_border_' . $levelKey);
      if ($crossValue !== NULL) {
        $existingLevels[$levelKey]['cross_border_restricted'] = (bool) $crossValue;
      }
    }
    $config->set('classification_levels', $existingLevels);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
