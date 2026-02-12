<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar variantes de posts sociales.
 *
 * PROPOSITO:
 * Permite a los gestores de redes sociales crear y editar variantes
 * de contenido para testing A/B de publicaciones.
 */
class SocialPostVariantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Variante de post %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Variante de post %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
