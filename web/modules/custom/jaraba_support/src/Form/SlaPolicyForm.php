<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for SLA Policy ConfigEntity add/edit.
 */
class SlaPolicyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\jaraba_support\Entity\SlaPolicyInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#maxlength' => 128,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\jaraba_support\Entity\SlaPolicy::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['plan_tier'] = [
      '#type' => 'select',
      '#title' => $this->t('Plan Tier'),
      '#options' => [
        'starter' => $this->t('Starter'),
        'professional' => $this->t('Professional'),
        'enterprise' => $this->t('Enterprise'),
        'institutional' => $this->t('Institutional'),
      ],
      '#default_value' => $entity->getPlanTier(),
      '#required' => TRUE,
    ];

    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => [
        'critical' => $this->t('Critical'),
        'high' => $this->t('High'),
        'medium' => $this->t('Medium'),
        'low' => $this->t('Low'),
      ],
      '#default_value' => $entity->getPriority(),
      '#required' => TRUE,
    ];

    $form['first_response_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('First Response (hours)'),
      '#default_value' => $entity->getFirstResponseHours(),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['resolution_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Resolution (hours)'),
      '#default_value' => $entity->getResolutionHours(),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['business_hours_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Business hours only'),
      '#default_value' => $entity->isBusinessHoursOnly(),
    ];

    $form['business_hours_schedule_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Business Hours Schedule ID'),
      '#default_value' => $entity->get('business_hours_schedule_id') ?? 'spain_standard',
      '#maxlength' => 128,
    ];

    $form['escalation_after_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-escalation after (hours)'),
      '#default_value' => $entity->get('escalation_after_hours') ?? 0,
      '#min' => 0,
    ];

    $form['includes_phone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Includes phone support'),
      '#default_value' => $entity->get('includes_phone') ?? FALSE,
    ];

    $form['includes_priority_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Includes priority queue'),
      '#default_value' => $entity->get('includes_priority_queue') ?? FALSE,
    ];

    $form['idle_reminder_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Idle reminder after (hours)'),
      '#default_value' => $entity->get('idle_reminder_hours') ?? 48,
      '#min' => 0,
    ];

    $form['auto_close_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-close after (hours)'),
      '#description' => $this->t('Hours of inactivity after reminder to auto-close. GAP-SUP-07.'),
      '#default_value' => $entity->get('auto_close_hours') ?? 168,
      '#min' => 0,
    ];

    $form['pause_on_pending'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause SLA on pending customer'),
      '#description' => $this->t('GAP-SUP-15: Stop SLA clock when waiting for customer response.'),
      '#default_value' => $entity->isPauseOnPending(),
    ];

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $entity->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('SLA Policy %label has been created.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('SLA Policy %label has been updated.', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
