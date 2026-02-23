<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the SecureConversation entity add/edit forms.
 */
class SecureConversationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Conversation %title has been created.', [
        '%title' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Conversation %title has been updated.', [
        '%title' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
