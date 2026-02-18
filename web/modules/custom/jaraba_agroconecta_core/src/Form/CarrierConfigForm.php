<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad CarrierConfig.
 */
class CarrierConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['api_key']['#type'] = 'password';
    $form['api_key']['#attributes']['autocomplete'] = 'off';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Configuración para %carrier creada.', ['%label' => $entity->label()]));
    } else {
      $this->messenger()->addMessage($this->t('Configuración actualizada.'));
    }

    $form_state->setRedirect('entity.agro_carrier_config.collection');
    return $status;
  }

}
