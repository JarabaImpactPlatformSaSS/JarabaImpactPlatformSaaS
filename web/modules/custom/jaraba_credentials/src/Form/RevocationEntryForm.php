<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar RevocationEntry.
 */
class RevocationEntryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $messageArgs = ['@id' => $this->entity->id()];
    $message = $result === SAVED_NEW
      ? $this->t('Entrada de revocación #@id creada.', $messageArgs)
      : $this->t('Entrada de revocación #@id actualizada.', $messageArgs);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
