<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Facturae tenant configuration.
 */
class FacturaeTenantConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'fiscal' => [
        'label' => $this->t('Fiscal Data'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('NIF, name, address, and contact.'),
        'fields' => ['tenant_id', 'nif_emisor', 'nombre_razon', 'person_type', 'residence_type', 'address_json', 'contact_json'],
      ],
      'numbering' => [
        'label' => $this->t('Numbering'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Invoice series and numbering pattern.'),
        'fields' => ['default_series', 'next_number', 'numbering_pattern'],
      ],
      'certificate' => [
        'label' => $this->t('Certificate'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Digital certificate configuration.'),
        'fields' => ['certificate_file_id', 'certificate_password_encrypted', 'certificate_nif_titular', 'certificate_subject', 'certificate_expiry', 'certificate_issuer'],
      ],
      'face' => [
        'label' => $this->t('FACe'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('FACe integration settings.'),
        'fields' => ['face_enabled', 'face_environment', 'face_email_notification'],
      ],
      'defaults' => [
        'label' => $this->t('Defaults'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Default payment, tax, and DIR3 settings.'),
        'fields' => ['default_payment_method', 'default_payment_iban', 'tax_regime', 'retention_rate', 'invoice_description_template', 'legal_literals_default_json', 'default_dir3_oficina_contable', 'default_dir3_organo_gestor', 'default_dir3_unidad_tramitadora'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Configuration activation.'),
        'fields' => ['active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'settings'];
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
