<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for WorkflowRule config entities.
 */
class WorkflowRuleForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);
        /** @var \Drupal\jaraba_workflows\Entity\WorkflowRuleInterface $rule */
        $rule = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Rule name'),
            '#maxlength' => 255,
            '#default_value' => $rule->label(),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $rule->id(),
            '#machine_name' => [
                'exists' => '\Drupal\jaraba_workflows\Entity\WorkflowRule::load',
            ],
            '#disabled' => !$rule->isNew(),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#default_value' => $rule->getDescription(),
            '#rows' => 3,
        ];

        $form['trigger_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Trigger'),
            '#options' => [
                'entity_created' => $this->t('Entity created'),
                'entity_updated' => $this->t('Entity updated'),
                'cron_schedule' => $this->t('Scheduled (cron)'),
                'threshold_reached' => $this->t('Threshold reached'),
                'ai_insight' => $this->t('AI insight generated'),
            ],
            '#default_value' => $rule->getTriggerType(),
            '#required' => TRUE,
        ];

        $form['trigger_config'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Trigger configuration (JSON)'),
            '#default_value' => !empty($rule->getTriggerConfig()) ? json_encode($rule->getTriggerConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
            '#description' => $this->t('JSON configuration for the trigger type.'),
            '#rows' => 4,
        ];

        $form['actions_config'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Actions (JSON array)'),
            '#default_value' => !empty($rule->getActions()) ? json_encode($rule->getActions(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
            '#description' => $this->t('JSON array of actions: [{type: "send_email", config: {...}}, ...]'),
            '#rows' => 6,
        ];

        $form['tenant_id'] = [
            '#type' => 'number',
            '#title' => $this->t('Tenant ID'),
            '#default_value' => $rule->getTenantId(),
            '#description' => $this->t('0 = global rule, otherwise tenant-scoped.'),
            '#min' => 0,
        ];

        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Weight'),
            '#default_value' => $rule->getWeight(),
            '#description' => $this->t('Lower weight = executed first.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Validate trigger_config JSON.
        $triggerConfigRaw = $form_state->getValue('trigger_config');
        if (!empty($triggerConfigRaw)) {
            $decoded = json_decode($triggerConfigRaw, TRUE);
            if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
                $form_state->setErrorByName('trigger_config', $this->t('Invalid JSON in trigger configuration.'));
            }
        }

        // Validate actions JSON.
        $actionsRaw = $form_state->getValue('actions_config');
        if (!empty($actionsRaw)) {
            $decoded = json_decode($actionsRaw, TRUE);
            if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
                $form_state->setErrorByName('actions_config', $this->t('Invalid JSON in actions configuration.'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var \Drupal\jaraba_workflows\Entity\WorkflowRule $rule */
        $rule = $this->entity;

        // Decode JSON fields.
        $triggerConfigRaw = $form_state->getValue('trigger_config');
        if (!empty($triggerConfigRaw)) {
            $rule->set('trigger_config', json_decode($triggerConfigRaw, TRUE) ?? []);
        }

        $actionsRaw = $form_state->getValue('actions_config');
        if (!empty($actionsRaw)) {
            $rule->set('actions', json_decode($actionsRaw, TRUE) ?? []);
        }

        $status = $rule->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Workflow rule %label created.', ['%label' => $rule->label()]));
        }
        else {
            $this->messenger()->addStatus($this->t('Workflow rule %label updated.', ['%label' => $rule->label()]));
        }

        $form_state->setRedirectUrl($rule->toUrl('collection'));
        return $status;
    }

}
