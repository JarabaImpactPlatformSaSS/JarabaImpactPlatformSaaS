<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de inscripcion/edicion de participantes en programas.
 *
 * ESTRUCTURA: ContentEntityForm con 4 fieldsets organizando los campos:
 *   inscripcion, seguimiento, resultado y estado.
 *
 * LOGICA: Los campos de seguimiento y resultado se agrupan para
 *   facilitar la actualizacion progresiva del expediente del
 *   participante a lo largo del programa.
 */
class ProgramParticipantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Inscripcion ---
    $form['enrollment_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Inscripcion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $enrollmentFields = ['program_id', 'user_id', 'enrollment_date'];
    foreach ($enrollmentFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'enrollment_group';
      }
    }

    // --- Fieldset 2: Seguimiento ---
    $form['tracking_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Seguimiento'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    $trackingFields = ['hours_orientation', 'hours_training', 'certifications_obtained'];
    foreach ($trackingFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'tracking_group';
      }
    }

    // --- Fieldset 3: Resultado ---
    $form['outcome_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Resultado'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    $outcomeFields = ['employment_outcome', 'employment_date', 'exit_date', 'exit_reason'];
    foreach ($outcomeFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'outcome_group';
      }
    }

    // --- Fieldset 4: Estado ---
    $form['status_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado y notas'),
      '#open' => TRUE,
      '#weight' => 30,
    ];

    $statusFields = ['status', 'notes'];
    foreach ($statusFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'status_group';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(
        $this->t('Participante inscrito correctamente.'),
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t('Participante actualizado correctamente.'),
      );
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
