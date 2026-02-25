<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para CookieConsent.
 *
 * Los registros de consentimiento son de solo lectura (audit trail).
 * Este formulario existe para la interfaz admin y desarrollo.
 */
class CookieConsentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'user' => [
        'label' => $this->t('User'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('User and session information.'),
        'fields' => ['tenant_id', 'user_id', 'session_id'],
      ],
      'consent' => [
        'label' => $this->t('Consent'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Granular cookie consent categories.'),
        'fields' => ['consent_analytics', 'consent_marketing', 'consent_functional', 'consent_thirdparty'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
