<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para DpaAgreement.
 *
 * En produccion, los DPA se generan y firman via DpaManagerService.
 * Este formulario permite la creacion y edicion manual desde admin.
 */
class DpaAgreementForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Tenant and DPA version.'),
        'fields' => ['tenant_id', 'version'],
      ],
      'signature' => [
        'label' => $this->t('Signature'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Signer details and signature reference.'),
        'fields' => ['signed_by', 'signer_name', 'signer_role'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('DPA status.'),
        'fields' => ['status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
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
