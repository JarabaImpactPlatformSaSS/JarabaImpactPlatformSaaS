<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar expedientes juridicos.
 *
 * Estructura: Extiende ContentEntityForm con campos agrupados
 *   por seccion logica.
 *
 * Logica: Agrupa los campos en fieldsets para una mejor experiencia
 *   de usuario. El case_number se auto-genera en preSave() por lo
 *   que no aparece en el formulario de creacion.
 */
class ClientCaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['case_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Informacion del Expediente'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    $move_to_case = ['title', 'status', 'priority', 'case_type', 'description'];
    foreach ($move_to_case as $field) {
      if (isset($form[$field])) {
        $form['case_info'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['client_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Cliente'),
      '#open' => TRUE,
      '#weight' => -5,
    ];

    $move_to_client = ['client_name', 'client_email', 'client_phone', 'client_nif'];
    foreach ($move_to_client as $field) {
      if (isset($form[$field])) {
        $form['client_info'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['judicial_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos Judiciales'),
      '#open' => FALSE,
      '#weight' => 0,
    ];

    $move_to_judicial = [
      'court_name', 'court_number', 'filing_date',
      'next_deadline', 'opposing_party', 'estimated_value',
    ];
    foreach ($move_to_judicial as $field) {
      if (isset($form[$field])) {
        $form['judicial_info'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['assignment'] = [
      '#type' => 'details',
      '#title' => $this->t('Asignacion y Tenant'),
      '#open' => FALSE,
      '#weight' => 5,
    ];

    $move_to_assignment = ['assigned_to', 'tenant_id', 'legal_area'];
    foreach ($move_to_assignment as $field) {
      if (isset($form[$field])) {
        $form['assignment'][$field] = $form[$field];
        unset($form[$field]);
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
    $message_args = ['%label' => $entity->toLink()->toString()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Expediente %label creado correctamente.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Expediente %label actualizado correctamente.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
