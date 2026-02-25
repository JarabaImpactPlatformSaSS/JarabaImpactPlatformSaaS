<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar recompensas de referido.
 */
class ReferralRewardForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Tenant, referral, code and beneficiary user.'),
        'fields' => ['tenant_id', 'referral_id', 'code_id', 'user_id'],
      ],
      'reward' => [
        'label' => $this->t('Reward'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Reward type, value and currency.'),
        'fields' => ['reward_type', 'reward_value', 'currency', 'status'],
      ],
      'payment' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Stripe payout details and dates.'),
        'fields' => ['stripe_payout_id', 'paid_at', 'expires_at'],
      ],
      'notes' => [
        'label' => $this->t('Notes'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Internal notes about the reward.'),
        'fields' => ['notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'coins'];
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
