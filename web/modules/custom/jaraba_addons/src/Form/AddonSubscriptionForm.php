<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing add-on subscriptions.
 */
class AddonSubscriptionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'subscription' => [
        'label' => $this->t('Subscription'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('Add-on and tenant.'),
        'fields' => ['addon_id', 'tenant_id', 'billing_cycle', 'price_paid'],
      ],
      'dates' => [
        'label' => $this->t('Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Subscription period.'),
        'fields' => ['start_date', 'end_date'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Subscription status.'),
        'fields' => ['status'],
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
    $entity = $this->getEntity();

    // Auto-set start_date for new subscriptions.
    if ($entity->isNew() && empty($entity->get('start_date')->value)) {
      $entity->set('start_date', date('Y-m-d\TH:i:s'));
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
