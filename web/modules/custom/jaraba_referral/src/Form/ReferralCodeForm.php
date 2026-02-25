<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar codigos de referido.
 */
class ReferralCodeForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Code, owner and program assignment.'),
        'fields' => ['tenant_id', 'program_id', 'user_id', 'code', 'custom_url'],
      ],
      'tracking' => [
        'label' => $this->t('Tracking'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Click, signup and conversion counters.'),
        'fields' => ['total_clicks', 'total_signups', 'total_conversions', 'total_revenue'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Activation state and expiration.'),
        'fields' => ['is_active', 'expires_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'tag'];
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
