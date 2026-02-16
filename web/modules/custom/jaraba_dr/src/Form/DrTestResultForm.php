<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para DrTestResult.
 *
 * Los resultados de test DR se generan normalmente via DrTestRunnerService.
 * Este formulario permite la creacion y edicion manual desde admin.
 */
class DrTestResultForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->get('test_name')->value];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Test DR %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Test DR %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
