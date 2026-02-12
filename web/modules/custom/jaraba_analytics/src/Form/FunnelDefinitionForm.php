<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de Funnel Definitions.
 *
 * PROPÓSITO:
 * Permite crear y editar definiciones de funnels de conversión
 * con pasos dinámicos gestionados vía AJAX.
 *
 * LÓGICA:
 * - Grupo 1: Información básica (nombre, tenant, ventana de conversión).
 * - Grupo 2: Pasos del funnel (añadir/eliminar dinámicamente con AJAX).
 *   Cada paso tiene event_type (textfield) y label (textfield).
 */
class FunnelDefinitionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\jaraba_analytics\Entity\FunnelDefinition $entity */
    $entity = $this->entity;

    // Grupo: Información básica.
    $form['basic_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic Information'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['name'])) {
      $form['name']['#group'] = 'basic_info';
    }
    if (isset($form['tenant_id'])) {
      $form['tenant_id']['#group'] = 'basic_info';
    }
    if (isset($form['conversion_window_hours'])) {
      $form['conversion_window_hours']['#group'] = 'basic_info';
    }

    // Grupo: Pasos del funnel (AJAX).
    $form['steps_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Funnel Steps'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    // Determine steps from form_state or entity.
    $existingSteps = $entity->getSteps();
    $numSteps = $form_state->get('num_steps');
    if ($numSteps === NULL) {
      $numSteps = count($existingSteps) > 0 ? count($existingSteps) : 1;
      $form_state->set('num_steps', $numSteps);
    }

    $form['steps_wrapper']['steps_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'funnel-steps-wrapper'],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $numSteps; $i++) {
      $form['steps_wrapper']['steps_container'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Step @number', ['@number' => $i + 1]),
        '#attributes' => ['class' => ['funnel-step-item']],
      ];

      $form['steps_wrapper']['steps_container'][$i]['event_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Event Type'),
        '#description' => $this->t('The analytics event type (e.g., page_view, add_to_cart, purchase).'),
        '#default_value' => $existingSteps[$i]['event_type'] ?? '',
        '#required' => TRUE,
        '#maxlength' => 100,
      ];

      $form['steps_wrapper']['steps_container'][$i]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#description' => $this->t('Display label for this step.'),
        '#default_value' => $existingSteps[$i]['label'] ?? '',
        '#required' => TRUE,
        '#maxlength' => 255,
      ];

      // Remove button for each step (except if only one).
      if ($numSteps > 1) {
        $form['steps_wrapper']['steps_container'][$i]['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove step @number', ['@number' => $i + 1]),
          '#name' => 'remove_step_' . $i,
          '#submit' => ['::removeStepCallback'],
          '#ajax' => [
            'callback' => '::stepsAjaxCallback',
            'wrapper' => 'funnel-steps-wrapper',
          ],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--danger']],
        ];
      }
    }

    // Add step button.
    $form['steps_wrapper']['add_step'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add step'),
      '#name' => 'add_step',
      '#submit' => ['::addStepCallback'],
      '#ajax' => [
        'callback' => '::stepsAjaxCallback',
        'wrapper' => 'funnel-steps-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * AJAX callback to return the steps container.
   */
  public function stepsAjaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['steps_wrapper']['steps_container'];
  }

  /**
   * Submit handler for adding a step.
   */
  public function addStepCallback(array &$form, FormStateInterface $form_state): void {
    $numSteps = $form_state->get('num_steps');
    $form_state->set('num_steps', $numSteps + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for removing a step.
   */
  public function removeStepCallback(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name'] ?? '';

    if (preg_match('/remove_step_(\d+)/', $name, $matches)) {
      $indexToRemove = (int) $matches[1];
      $numSteps = $form_state->get('num_steps');

      // Collect current step values and reindex without the removed one.
      $currentValues = $form_state->getValue('steps_container') ?? [];
      unset($currentValues[$indexToRemove]);
      $currentValues = array_values($currentValues);

      $form_state->setValue('steps_container', $currentValues);
      $form_state->set('num_steps', max(1, $numSteps - 1));
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $stepsValues = $form_state->getValue('steps_container') ?? [];
    foreach ($stepsValues as $index => $step) {
      if (empty($step['event_type'])) {
        $form_state->setErrorByName(
          "steps_container][$index][event_type",
          $this->t('Step @num: Event type is required.', ['@num' => $index + 1])
        );
      }
      if (empty($step['label'])) {
        $form_state->setErrorByName(
          "steps_container][$index][label",
          $this->t('Step @num: Label is required.', ['@num' => $index + 1])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_analytics\Entity\FunnelDefinition $entity */
    $entity = $this->getEntity();

    // Build steps array from form values.
    $stepsValues = $form_state->getValue('steps_container') ?? [];
    $steps = [];
    foreach ($stepsValues as $step) {
      if (!empty($step['event_type']) && !empty($step['label'])) {
        $steps[] = [
          'event_type' => $step['event_type'],
          'label' => $step['label'],
          'filters' => [],
        ];
      }
    }

    $entity->set('steps', [$steps]);

    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Funnel %name created.', [
        '%name' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Funnel %name updated.', [
        '%name' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
