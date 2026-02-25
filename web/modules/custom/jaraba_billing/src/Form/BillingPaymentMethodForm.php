<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar metodos de pago.
 */
class BillingPaymentMethodForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Tenant and Stripe payment method references.'),
        'fields' => ['tenant_id', 'stripe_payment_method_id', 'stripe_customer_id'],
      ],
      'type_details' => [
        'label' => $this->t('Type & Card Details'),
        'icon' => ['category' => 'commerce', 'name' => 'receipt'],
        'description' => $this->t('Payment type and card information.'),
        'fields' => ['type', 'card_brand', 'card_last4', 'card_exp_month', 'card_exp_year'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Default flag and active status.'),
        'fields' => ['is_default', 'status'],
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
