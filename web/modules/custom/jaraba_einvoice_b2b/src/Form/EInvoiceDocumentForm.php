<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for EInvoice Document entities.
 *
 * Handles add/edit operations for e-invoice documents. In production,
 * documents are typically created programmatically from billing invoices;
 * this form serves for manual creation and administrative corrections
 * on draft documents.
 *
 * Spec: Doc 181, Section 2.1.
 */
class EInvoiceDocumentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $invoiceNumber = $entity->get('invoice_number')->value ?? '';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('E-Invoice document %label created.', [
        '%label' => $invoiceNumber,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('E-Invoice document %label updated.', [
        '%label' => $invoiceNumber,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
