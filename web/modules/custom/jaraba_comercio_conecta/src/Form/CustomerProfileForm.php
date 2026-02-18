<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar perfiles de cliente.
 *
 * Estructura: Extiende ContentEntityForm. Los campos se organizan
 *   en fieldsets temáticos para facilitar la edición.
 *
 * Lógica: Agrupa campos por categoría funcional: datos personales,
 *   direcciones y preferencias.
 */
class CustomerProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['datos_personales'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos Personales'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['display_name', 'phone', 'avatar_url'] as $field) {
      if (isset($form[$field])) {
        $form['datos_personales'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['direcciones'] = [
      '#type' => 'details',
      '#title' => $this->t('Direcciones'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['shipping_address', 'billing_address'] as $field) {
      if (isset($form[$field])) {
        $form['direcciones'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['preferencias'] = [
      '#type' => 'details',
      '#title' => $this->t('Preferencias'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['preferences', 'favorite_merchants'] as $field) {
      if (isset($form[$field])) {
        $form['preferencias'][$field] = $form[$field];
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

    $this->messenger()->addStatus($this->t('Perfil actualizado correctamente.'));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
