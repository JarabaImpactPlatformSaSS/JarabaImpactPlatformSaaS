<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de programas institucionales.
 *
 * ESTRUCTURA: ContentEntityForm con 4 fieldsets organizando los campos:
 *   identificacion, financiacion, participantes y estado.
 *
 * LOGICA: Los campos se agrupan logicamente. El campo budget_executed
 *   solo se muestra en edicion (no en creacion).
 */
class InstitutionalProgramForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $isNew = $this->entity->isNew();

    // --- Fieldset 1: Identificacion del programa ---
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion del programa'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $identificationFields = ['name', 'program_type', 'program_code', 'funding_entity'];
    foreach ($identificationFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'identification';
      }
    }

    // --- Fieldset 2: Fechas y financiacion ---
    $form['financing'] = [
      '#type' => 'details',
      '#title' => $this->t('Fechas y financiacion'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    $financingFields = ['start_date', 'end_date', 'budget_total', 'budget_executed'];
    foreach ($financingFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'financing';
      }
    }

    // Ocultar budget_executed en creacion.
    if ($isNew && isset($form['budget_executed'])) {
      $form['budget_executed']['#access'] = FALSE;
    }

    // --- Fieldset 3: Participantes ---
    $form['participants_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Participantes'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    $participantFields = ['participants_target', 'participants_actual'];
    foreach ($participantFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'participants_group';
      }
    }

    // --- Fieldset 4: Estado y notas ---
    $form['status_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado y notas'),
      '#open' => TRUE,
      '#weight' => 30,
    ];

    $statusFields = ['status', 'reporting_deadlines', 'notes'];
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
    $name = $entity->get('name')->value ?? '';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(
        $this->t('Programa «@name» creado correctamente.', ['@name' => $name]),
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t('Programa «@name» actualizado correctamente.', ['@name' => $name]),
      );
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
