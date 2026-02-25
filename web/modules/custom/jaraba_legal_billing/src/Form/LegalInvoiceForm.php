<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de Facturas Legales.
 */
class LegalInvoiceForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Invoice identification and tenant information.'),
        'fields' => ['tenant_id', 'provider_id', 'series', 'case_id'],
      ],
      'client' => [
        'label' => $this->t('Client Data'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Client contact and billing information.'),
        'fields' => ['client_name', 'client_nif', 'client_address', 'client_email'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Issue and due dates for the invoice.'),
        'fields' => ['issue_date', 'due_date'],
      ],
      'amounts' => [
        'label' => $this->t('Amounts'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Invoice totals, taxes and withholdings.'),
        'fields' => ['subtotal', 'tax_rate', 'tax_amount', 'irpf_rate', 'irpf_amount', 'total'],
      ],
      'status_payment' => [
        'label' => $this->t('Status & Payment'),
        'icon' => ['category' => 'commerce', 'name' => 'receipt'],
        'description' => $this->t('Invoice status and payment tracking.'),
        'fields' => ['status', 'payment_method', 'paid_at', 'paid_amount'],
      ],
      'notes' => [
        'label' => $this->t('Notes'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Internal notes about the invoice.'),
        'fields' => ['notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'receipt'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
