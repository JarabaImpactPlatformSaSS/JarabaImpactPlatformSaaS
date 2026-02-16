<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para PrivacyPolicy.
 *
 * Permite crear y editar políticas de privacidad con contenido HTML
 * parametrizable por vertical y tenant.
 */
class PrivacyPolicyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('version')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Política de privacidad v%label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Política de privacidad v%label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
