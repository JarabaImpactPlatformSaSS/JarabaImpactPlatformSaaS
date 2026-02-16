<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for FacturaeDocument create/edit.
 */
class FacturaeDocumentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Facturae document @number has been created.', [
        '@number' => $entity->get('facturae_number')->value,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Facturae document @number has been updated.', [
        '@number' => $entity->get('facturae_number')->value,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
