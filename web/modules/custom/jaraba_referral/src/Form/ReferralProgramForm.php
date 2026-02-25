<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar programas de referidos.
 */
class ReferralProgramForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('General'),
        'icon' => ['category' => 'ui', 'name' => 'gift'],
        'description' => $this->t('Program identity and tenant assignment.'),
        'fields' => ['tenant_id', 'name', 'description', 'is_active'],
      ],
      'referrer_reward' => [
        'label' => $this->t('Referrer Reward'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Reward configuration for the referring user.'),
        'fields' => ['reward_type', 'reward_value', 'reward_currency'],
      ],
      'referee_reward' => [
        'label' => $this->t('Referee Reward'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Reward configuration for the referred user.'),
        'fields' => ['referee_reward_type', 'referee_reward_value'],
      ],
      'limits' => [
        'label' => $this->t('Limits & Tiers'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Tier thresholds and reward limits per user.'),
        'fields' => ['min_referrals_for_tier', 'max_rewards_per_user'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Program validity period and terms.'),
        'fields' => ['starts_at', 'ends_at', 'terms_conditions'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'gift'];
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
