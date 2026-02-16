<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for EInvoice Tenant Config entities.
 *
 * Manages per-tenant B2B e-invoicing configuration: SPFE credentials,
 * Peppol participant ID, inbound reception settings, payment terms,
 * and certificate configuration.
 *
 * Spec: Doc 181, Section 2.2.
 */
class EInvoiceTenantConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $label = $entity->get('nombre_razon')->value ?? '';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('E-Invoice B2B configuration for %label created.', [
        '%label' => $label,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('E-Invoice B2B configuration for %label updated.', [
        '%label' => $label,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
