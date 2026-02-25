<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar facturas de billing.
 */
class BillingInvoiceForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Tenant, invoice number and Stripe references.'),
        'fields' => ['tenant_id', 'invoice_number', 'stripe_invoice_id', 'stripe_customer_id'],
      ],
      'status_reason' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Invoice status and billing reason.'),
        'fields' => ['status', 'billing_reason', 'currency'],
      ],
      'amounts' => [
        'label' => $this->t('Amounts'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Subtotal, taxes, total and payment amounts.'),
        'fields' => ['subtotal', 'tax', 'total', 'amount_due', 'amount_paid'],
      ],
      'dates' => [
        'label' => $this->t('Dates & Period'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Billing period, due date and payment date.'),
        'fields' => ['period_start', 'period_end', 'due_date', 'paid_at'],
      ],
      'links' => [
        'label' => $this->t('External Links'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('PDF and hosted invoice URLs.'),
        'fields' => ['pdf_url', 'hosted_invoice_url'],
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
