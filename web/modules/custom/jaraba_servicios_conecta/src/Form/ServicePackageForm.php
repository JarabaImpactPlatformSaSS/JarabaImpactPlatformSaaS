<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar paquetes de servicios.
 */
class ServicePackageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['paquete'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos del Paquete'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'provider_id', 'offering_id', 'description'] as $field) {
      if (isset($form[$field])) {
        $form['paquete'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['precio'] = [
      '#type' => 'details',
      '#title' => $this->t('Precio y Sesiones'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['total_sessions', 'price', 'discount_percent', 'validity_days'] as $field) {
      if (isset($form[$field])) {
        $form['precio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['estado'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['is_published'] as $field) {
      if (isset($form[$field])) {
        $form['estado'][$field] = $form[$field];
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
    $message_args = ['%label' => $entity->toLink()->toString()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Paquete %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Paquete %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
