<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Business Hours Schedule ConfigEntity add/edit.
 */
class BusinessHoursScheduleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\jaraba_support\Entity\BusinessHoursSchedule $entity */
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
        'exists' => '\Drupal\jaraba_support\Entity\BusinessHoursSchedule::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#options' => system_time_zones(TRUE, TRUE),
      '#default_value' => $entity->getTimezone(),
      '#required' => TRUE,
    ];

    $schedule = $entity->getSchedule();
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $day_labels = [
      'monday' => $this->t('Monday'),
      'tuesday' => $this->t('Tuesday'),
      'wednesday' => $this->t('Wednesday'),
      'thursday' => $this->t('Thursday'),
      'friday' => $this->t('Friday'),
      'saturday' => $this->t('Saturday'),
      'sunday' => $this->t('Sunday'),
    ];

    $form['schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Weekly Schedule'),
      '#tree' => TRUE,
    ];

    foreach ($days as $day) {
      $form['schedule'][$day] = [
        '#type' => 'fieldset',
        '#title' => $day_labels[$day],
        '#attributes' => ['class' => ['container-inline']],
      ];
      $form['schedule'][$day]['start'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Start'),
        '#default_value' => $schedule[$day]['start'] ?? '',
        '#size' => 5,
        '#placeholder' => '09:00',
      ];
      $form['schedule'][$day]['end'] = [
        '#type' => 'textfield',
        '#title' => $this->t('End'),
        '#default_value' => $schedule[$day]['end'] ?? '',
        '#size' => 5,
        '#placeholder' => '18:00',
      ];
    }

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
      $this->messenger()->addStatus($this->t('Business Hours Schedule %label has been created.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Business Hours Schedule %label has been updated.', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
