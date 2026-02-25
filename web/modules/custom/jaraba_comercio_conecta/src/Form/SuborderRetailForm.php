<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for retail suborders.
 */
class SuborderRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'pedido' => [
        'label' => $this->t('Datos del Sub-pedido'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Pedido principal, comercio y estado del sub-pedido.'),
        'fields' => ['order_id', 'merchant_id', 'status'],
      ],
      'financiero' => [
        'label' => $this->t('Datos Financieros'),
        'icon' => ['category' => 'fiscal', 'name' => 'calculator'],
        'description' => $this->t('Subtotal, comisiones y pago al comerciante.'),
        'fields' => ['subtotal', 'commission_rate', 'commission_amount', 'merchant_payout'],
      ],
      'pago' => [
        'label' => $this->t('Estado del Pago'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Estado de la transferencia y referencia de Stripe.'),
        'fields' => ['payout_status', 'stripe_transfer_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'cart'];
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
