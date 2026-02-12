<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar CrossVerticalRule.
 */
class CrossVerticalRuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $messageArgs = ['%name' => $this->entity->label()];
    $message = $result === SAVED_NEW
      ? $this->t('Regla cross-vertical %name creada.', $messageArgs)
      : $this->t('Regla cross-vertical %name actualizada.', $messageArgs);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
