<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar cuentas de ads.
 */
class AdsAccountForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'account' => [
        'label' => $this->t('Account'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Account name, platform and external identifier.'),
        'fields' => ['account_name', 'platform', 'external_account_id', 'status'],
      ],
      'authentication' => [
        'label' => $this->t('Authentication'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('OAuth tokens and scopes for API access.'),
        'fields' => ['access_token', 'refresh_token', 'token_expires_at', 'oauth_scopes'],
      ],
      'sync' => [
        'label' => $this->t('Synchronization'),
        'icon' => ['category' => 'ui', 'name' => 'refresh'],
        'description' => $this->t('Last sync timestamp and error details.'),
        'fields' => ['last_synced_at', 'sync_error'],
      ],
      'ownership' => [
        'label' => $this->t('Ownership'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Tenant assignment.'),
        'fields' => ['tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
