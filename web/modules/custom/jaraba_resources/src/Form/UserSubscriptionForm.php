<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for User Subscription entities.
 */
class UserSubscriptionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'subscription' => [
        'label' => $this->t('Subscription'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'fields' => ['user_id', 'plan_id', 'subscription_status'],
      ],
      'stripe' => [
        'label' => $this->t('Stripe'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['stripe_subscription_id', 'stripe_customer_id'],
      ],
      'period' => [
        'label' => $this->t('Period'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['current_period_start', 'current_period_end', 'trial_end', 'cancelled_at'],
      ],
      'usage' => [
        'label' => $this->t('Usage'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['mentoring_sessions_used', 'usage_reset_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'package'];
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
