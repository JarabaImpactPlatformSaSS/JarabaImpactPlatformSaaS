<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing e-invoice documents.
 */
class EInvoiceDocumentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'invoice' => [
        'label' => $this->t('Invoice'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Invoice number, date, and direction.'),
        'fields' => ['direction', 'invoice_number', 'invoice_date', 'due_date', 'format', 'currency_code', 'invoice_id', 'facturae_document_id'],
      ],
      'parties' => [
        'label' => $this->t('Parties'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Seller and buyer information.'),
        'fields' => ['seller_nif', 'seller_name', 'buyer_nif', 'buyer_name'],
      ],
      'amounts' => [
        'label' => $this->t('Amounts'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Totals and tax breakdown.'),
        'fields' => ['total_without_tax', 'total_tax', 'total_amount', 'tax_breakdown_json', 'line_items_json', 'payment_terms_json'],
      ],
      'xml' => [
        'label' => $this->t('XML'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('XML content and signed version.'),
        'fields' => ['xml_content', 'xml_signed', 'file_id'],
      ],
      'delivery' => [
        'label' => $this->t('Delivery'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('Delivery method and SPFE status.'),
        'fields' => ['delivery_status', 'delivery_method', 'delivery_timestamp', 'delivery_response_json', 'spfe_submission_id', 'spfe_status', 'spfe_response_json'],
      ],
      'payment_status' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Payment status tracking.'),
        'fields' => ['payment_status', 'payment_status_date', 'payment_status_communicated'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Document status and validation.'),
        'fields' => ['status', 'validation_status', 'validation_errors_json', 'tenant_id'],
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
