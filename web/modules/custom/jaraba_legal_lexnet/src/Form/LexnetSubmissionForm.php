<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creacion/edicion de Envios LexNET.
 */
class LexnetSubmissionForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('Envio LexNET "%subject" guardado.', [
      '%subject' => $entity->get('subject')->value,
    ]));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
