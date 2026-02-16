<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad LegalDeadline.
 */
class LegalDeadlineForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Plazo "%label" creado.', ['%label' => $entity->label()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Plazo "%label" actualizado.', ['%label' => $entity->label()]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
