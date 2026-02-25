<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing Facturae documents.
 */
class FacturaeDocumentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'invoice_id' => [
        'label' => $this->t('Invoice'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Invoice number, type, and dates.'),
        'fields' => ['facturae_number', 'facturae_series', 'invoice_class', 'invoice_type', 'issuer_type', 'schema_version', 'currency_code', 'language_code', 'issue_date', 'operation_date', 'tax_point_date', 'invoice_id'],
      ],
      'seller' => [
        'label' => $this->t('Seller'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Seller identification and address.'),
        'fields' => ['seller_nif', 'seller_name', 'seller_person_type', 'seller_residence_type', 'seller_address_json'],
      ],
      'buyer' => [
        'label' => $this->t('Buyer'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Buyer identification and address.'),
        'fields' => ['buyer_nif', 'buyer_name', 'buyer_person_type', 'buyer_residence_type', 'buyer_address_json', 'buyer_admin_centres_json'],
      ],
      'lines_taxes' => [
        'label' => $this->t('Lines & Taxes'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Invoice lines and tax breakdown.'),
        'fields' => ['invoice_lines_json', 'taxes_outputs_json', 'taxes_withheld_json'],
      ],
      'totals' => [
        'label' => $this->t('Totals'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('All total amounts.'),
        'fields' => ['total_gross_amount', 'total_general_discounts', 'total_general_surcharges', 'total_gross_amount_before_taxes', 'total_tax_outputs', 'total_tax_withheld', 'total_invoice_amount', 'total_outstanding', 'total_executable'],
      ],
      'payment' => [
        'label' => $this->t('Payment & Legal'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Payment details and legal literals.'),
        'fields' => ['payment_details_json', 'legal_literals_json', 'additional_data_json', 'corrective_json'],
      ],
      'signature' => [
        'label' => $this->t('Signature'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('XML documents and digital signature.'),
        'fields' => ['xml_unsigned', 'xml_signed', 'xsig_file_id', 'pdf_representation_id', 'signature_status', 'signature_timestamp', 'signature_certificate_nif'],
      ],
      'face' => [
        'label' => $this->t('FACe'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('FACe submission status.'),
        'fields' => ['face_status', 'face_registry_number', 'face_csv', 'face_tramitacion_status', 'face_tramitacion_date', 'face_anulacion_status', 'face_response_json'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Document status and validation.'),
        'fields' => ['status', 'validation_errors_json', 'tenant_id'],
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
