<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de Notificaciones LexNET.
 */
class LexnetNotificationForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('Notificacion LexNET "%subject" guardada.', [
      '%subject' => $entity->get('subject')->value,
    ]));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
