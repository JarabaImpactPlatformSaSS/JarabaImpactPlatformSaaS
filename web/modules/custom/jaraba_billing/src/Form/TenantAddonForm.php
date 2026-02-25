<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar add-ons de tenant.
 */
class TenantAddonForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('Tenant and add-on code selection.'),
        'fields' => ['tenant_id', 'addon_code'],
      ],
      'subscription' => [
        'label' => $this->t('Subscription'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Stripe subscription item and pricing.'),
        'fields' => ['stripe_subscription_item_id', 'price'],
      ],
      'status_dates' => [
        'label' => $this->t('Status & Dates'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Add-on status and activation/cancellation dates.'),
        'fields' => ['status', 'activated_at', 'canceled_at'],
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
