<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de edicion para LegalTemplate.
 */
class LegalTemplateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Plantilla juridica guardada.'));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

}
