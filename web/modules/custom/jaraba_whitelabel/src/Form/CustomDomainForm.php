<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for creating/editing CustomDomain entities.
 */
class CustomDomainForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'domain_info' => [
        'label' => $this->t('Domain Information'),
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'description' => $this->t('Domain name and tenant assignment.'),
        'fields' => ['domain', 'tenant_id'],
      ],
      'verification' => [
        'label' => $this->t('Verification & SSL'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('SSL status, DNS verification and domain status.'),
        'fields' => ['ssl_status', 'dns_verified', 'dns_verification_token', 'domain_status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'globe'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $domain = $form_state->getValue('domain')[0]['value'] ?? '';
    if (!empty($domain) && !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/', $domain)) {
      $form_state->setErrorByName('domain', $this->t('Please enter a valid domain name (e.g. app.example.com).'));
    }
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
