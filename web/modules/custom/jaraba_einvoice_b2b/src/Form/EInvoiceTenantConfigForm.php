<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for e-invoice B2B tenant configuration.
 */
class EInvoiceTenantConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'fiscal' => [
        'label' => $this->t('Fiscal Data'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('NIF, name, and address.'),
        'fields' => ['tenant_id', 'nif_emisor', 'nombre_razon', 'address_json', 'preferred_format'],
      ],
      'spfe' => [
        'label' => $this->t('SPFE'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('SPFE integration settings.'),
        'fields' => ['spfe_enabled', 'spfe_environment', 'spfe_credentials_json'],
      ],
      'peppol' => [
        'label' => $this->t('Peppol'),
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'description' => $this->t('Peppol network configuration.'),
        'fields' => ['peppol_enabled', 'peppol_participant_id'],
      ],
      'inbound' => [
        'label' => $this->t('Inbound'),
        'icon' => ['category' => 'ui', 'name' => 'download'],
        'description' => $this->t('Inbound invoice reception.'),
        'fields' => ['inbound_email', 'inbound_webhook_url', 'auto_send_on_paid', 'payment_status_tracking', 'default_payment_terms_days'],
      ],
      'certificate' => [
        'label' => $this->t('Certificate'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Digital certificate.'),
        'fields' => ['certificate_file_id', 'certificate_password_encrypted'],
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
