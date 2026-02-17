<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar resenas de servicio.
 *
 * Estructura: Extiende ContentEntityForm con fieldsets tematicos.
 *
 * Logica: Agrupa campos por: datos de la resena (valoracion, titulo,
 *   comentario) y estado/respuesta del profesional.
 */
class ReviewServiciosForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['resena'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de la Resena'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    // Reemplazar el campo rating por un select 1-5.
    if (isset($form['rating'])) {
      $form['rating']['widget'][0]['value']['#type'] = 'select';
      $form['rating']['widget'][0]['value']['#options'] = [
        1 => $this->t('1 - Muy malo'),
        2 => $this->t('2 - Malo'),
        3 => $this->t('3 - Regular'),
        4 => $this->t('4 - Bueno'),
        5 => $this->t('5 - Excelente'),
      ];
      unset($form['rating']['widget'][0]['value']['#min']);
      unset($form['rating']['widget'][0]['value']['#max']);
    }

    foreach (['rating', 'title', 'comment'] as $field) {
      if (isset($form[$field])) {
        $form['resena'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['referencias'] = [
      '#type' => 'details',
      '#title' => $this->t('Referencias'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['provider_id', 'offering_id', 'booking_id', 'reviewer_uid'] as $field) {
      if (isset($form[$field])) {
        $form['referencias'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['moderacion'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderacion'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['status'] as $field) {
      if (isset($form[$field])) {
        $form['moderacion'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['respuesta'] = [
      '#type' => 'details',
      '#title' => $this->t('Respuesta del Profesional'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['provider_response', 'response_date'] as $field) {
      if (isset($form[$field])) {
        $form['respuesta'][$field] = $form[$field];
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
      $this->messenger()->addStatus($this->t('Resena #@id creada.', ['@id' => $entity->id()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Resena #@id actualizada.', ['@id' => $entity->id()]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
