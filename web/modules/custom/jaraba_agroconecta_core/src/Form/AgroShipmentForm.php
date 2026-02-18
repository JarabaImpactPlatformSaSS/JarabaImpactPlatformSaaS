<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad AgroShipment.
 */
class AgroShipmentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface $entity */
    $entity = $this->entity;

    // Si es nuevo, el número de envío será generado automáticamente.
    if ($entity->isNew()) {
      $form['shipment_number']['widget'][0]['value']['#placeholder'] = $this->t('Auto-generado al guardar');
    }

    // Estilizar estados.
    if (isset($form['state'])) {
      $form['state']['widget']['#attributes']['class'][] = 'agro-shipment-state-select';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Envío %label registrado correctamente.', [
          '%label' => $entity->getShipmentNumber(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Envío %label actualizado correctamente.', [
          '%label' => $entity->getShipmentNumber(),
        ]));
    }

    $form_state->setRedirect('entity.agro_shipment.collection');
    return $status;
  }

}
