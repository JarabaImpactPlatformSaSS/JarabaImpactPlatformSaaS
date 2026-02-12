<?php

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar slots de disponibilidad.
 */
class AvailabilitySlotForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['slot'] = [
      '#type' => 'details',
      '#title' => $this->t('Slot de Disponibilidad'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['provider_id', 'day_of_week', 'start_time', 'end_time'] as $field) {
      if (isset($form[$field])) {
        $form['slot'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['vigencia'] = [
      '#type' => 'details',
      '#title' => $this->t('Vigencia'),
      '#open' => FALSE,
      '#weight' => 10,
    ];
    foreach (['is_active', 'valid_from', 'valid_until'] as $field) {
      if (isset($form[$field])) {
        $form['vigencia'][$field] = $form[$field];
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
    $entity = $this->entity;

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Slot de disponibilidad creado.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Slot de disponibilidad actualizado.'));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
