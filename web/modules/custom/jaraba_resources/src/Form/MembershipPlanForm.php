<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for Membership Plan entities.
 */
class MembershipPlanForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'plan' => [
        'label' => $this->t('Plan'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'fields' => ['name', 'description', 'plan_type', 'display_order'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'fields' => ['price', 'billing_interval', 'stripe_price_id'],
      ],
      'features' => [
        'label' => $this->t('Features'),
        'icon' => ['category' => 'ui', 'name' => 'list'],
        'fields' => ['features', 'kit_access_level', 'max_mentoring_sessions', 'max_groups', 'has_ai_features', 'has_priority_support'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['is_featured', 'status'],
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
