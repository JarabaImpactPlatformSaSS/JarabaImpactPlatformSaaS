<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar referidos.
 */
class ReferralForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'users' => [
        'label' => $this->t('Users'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Referrer and referred user assignment.'),
        'fields' => ['referrer_uid', 'referred_uid', 'tenant_id'],
      ],
      'referral' => [
        'label' => $this->t('Referral'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Referral code and current status.'),
        'fields' => ['referral_code', 'status'],
      ],
      'reward' => [
        'label' => $this->t('Reward'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Reward type and value for this referral.'),
        'fields' => ['reward_type', 'reward_value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
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
