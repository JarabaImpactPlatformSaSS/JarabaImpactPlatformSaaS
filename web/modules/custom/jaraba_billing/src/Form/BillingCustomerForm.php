<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar clientes de billing.
 */
class BillingCustomerForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'tenant_stripe' => [
        'label' => $this->t('Tenant & Stripe'),
        'icon' => ['category' => 'commerce', 'name' => 'receipt'],
        'description' => $this->t('Tenant assignment and Stripe integration IDs.'),
        'fields' => ['tenant_id', 'stripe_customer_id', 'stripe_connect_id'],
      ],
      'billing_info' => [
        'label' => $this->t('Billing Information'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Billing name, email and contact details.'),
        'fields' => ['billing_email', 'billing_name', 'billing_address'],
      ],
      'fiscal' => [
        'label' => $this->t('Fiscal Data'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Tax identification and fiscal type.'),
        'fields' => ['tax_id', 'tax_id_type'],
      ],
      'payment' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Default payment method configuration.'),
        'fields' => ['default_payment_method'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
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
