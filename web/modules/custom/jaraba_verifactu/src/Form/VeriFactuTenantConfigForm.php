<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for VeriFactu tenant configuration.
 */
class VeriFactuTenantConfigForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'fiscal' => [
        'label' => $this->t('Fiscal Data'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('NIF, fiscal name, and invoicing series.'),
        'fields' => ['tenant_id', 'nif', 'nombre_fiscal', 'serie_facturacion'],
      ],
      'certificate' => [
        'label' => $this->t('Certificate'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Digital certificate configuration.'),
        'fields' => ['certificate_password_encrypted', 'certificate_valid_until', 'certificate_subject'],
      ],
      'aeat' => [
        'label' => $this->t('AEAT'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('AEAT environment and chain data.'),
        'fields' => ['aeat_environment', 'last_chain_hash', 'last_record_id'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Configuration activation.'),
        'fields' => ['is_active'],
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
