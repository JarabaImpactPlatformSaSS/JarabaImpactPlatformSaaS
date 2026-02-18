<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar listas de deseos.
 *
 * Estructura: Extiende ContentEntityForm con campos
 *   de nombre y visibilidad.
 *
 * Lógica: Formulario simple con nombre de la lista y campo
 *   de visibilidad (pública/privada).
 */
class WishlistForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;

    $this->messenger()->addStatus($this->t('Lista guardada correctamente.'));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
