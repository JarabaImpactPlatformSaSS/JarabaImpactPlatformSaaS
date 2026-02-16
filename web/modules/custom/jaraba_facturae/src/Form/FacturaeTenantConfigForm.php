<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for FacturaeTenantConfig create/edit.
 */
class FacturaeTenantConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Facturae config for @name has been created.', [
        '@name' => $entity->get('nombre_razon')->value,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Facturae config for @name has been updated.', [
        '@name' => $entity->get('nombre_razon')->value,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
