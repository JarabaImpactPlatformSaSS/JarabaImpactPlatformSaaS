<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for VeriFactu invoice records.
 */
class VeriFactuInvoiceRecordForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'invoice' => [
        'label' => $this->t('Invoice'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Invoice identification and type.'),
        'fields' => ['record_type', 'numero_factura', 'fecha_expedicion', 'tipo_factura', 'clave_regimen'],
      ],
      'issuer' => [
        'label' => $this->t('Issuer'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Issuer NIF and name.'),
        'fields' => ['nif_emisor', 'nombre_emisor'],
      ],
      'amounts' => [
        'label' => $this->t('Amounts'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Tax base, rate, and totals.'),
        'fields' => ['base_imponible', 'tipo_impositivo', 'cuota_tributaria', 'importe_total'],
      ],
      'chain' => [
        'label' => $this->t('Chain'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Hash chain and QR code.'),
        'fields' => ['hash_record', 'hash_previous', 'qr_url', 'qr_image'],
      ],
      'aeat' => [
        'label' => $this->t('AEAT'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('AEAT submission status and response.'),
        'fields' => ['aeat_status', 'aeat_response_code', 'aeat_response_message', 'xml_registro'],
      ],
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Related entities and software info.'),
        'fields' => ['remision_batch_id', 'billing_invoice_id', 'software_id', 'software_version', 'tenant_id'],
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
