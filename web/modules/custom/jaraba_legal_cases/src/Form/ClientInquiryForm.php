<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar consultas juridicas.
 *
 * Estructura: Extiende ContentEntityForm con campos agrupados.
 *
 * Logica: El inquiry_number se auto-genera en preSave().
 */
class ClientInquiryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $message_args = ['%label' => $entity->toLink()->toString()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Consulta %label creada correctamente.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Consulta %label actualizada correctamente.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
