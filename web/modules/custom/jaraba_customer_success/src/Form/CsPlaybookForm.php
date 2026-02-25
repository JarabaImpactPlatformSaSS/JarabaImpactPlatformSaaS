<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing Customer Success playbooks.
 */
class CsPlaybookForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'business', 'name' => 'roadmap'],
        'description' => $this->t('Playbook name, trigger, and priority.'),
        'fields' => ['name', 'trigger_type', 'priority', 'status'],
      ],
      'trigger_settings' => [
        'label' => $this->t('Trigger Conditions'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('JSON conditions that activate this playbook.'),
        'fields' => ['trigger_conditions'],
      ],
      'execution_steps' => [
        'label' => $this->t('Execution Steps'),
        'icon' => ['category' => 'ui', 'name' => 'list'],
        'description' => $this->t('Sequential steps for playbook execution.'),
        'fields' => ['steps'],
      ],
      'execution_config' => [
        'label' => $this->t('Execution'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Auto-execution and statistics.'),
        'fields' => ['auto_execute'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'roadmap'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // execution_count and success_rate are computed — show as read-only.
    foreach (['execution_count', 'success_rate'] as $field) {
      if (isset($form['premium_section_other'][$field])) {
        $form['premium_section_other'][$field]['#disabled'] = TRUE;
      }
      elseif (isset($form[$field])) {
        $form[$field]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate JSON for trigger_conditions.
    $conditions = $form_state->getValue(['trigger_conditions', 0, 'value']);
    if ($conditions && json_decode($conditions) === NULL && json_last_error() !== JSON_ERROR_NONE) {
      $form_state->setErrorByName('trigger_conditions', $this->t('Trigger conditions must be valid JSON.'));
    }

    // Validate JSON array for steps.
    $steps = $form_state->getValue(['steps', 0, 'value']);
    if ($steps) {
      $decoded = json_decode($steps, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName('steps', $this->t('Steps must be valid JSON.'));
      }
      elseif (!is_array($decoded)) {
        $form_state->setErrorByName('steps', $this->t('Steps must be a JSON array.'));
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
