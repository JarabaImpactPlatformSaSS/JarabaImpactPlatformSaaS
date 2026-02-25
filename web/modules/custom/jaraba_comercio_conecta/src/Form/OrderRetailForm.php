<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing retail orders.
 */
class OrderRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_pedido' => [
        'label' => $this->t('Informacion del Pedido'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Datos principales del pedido.'),
        'fields' => ['order_number', 'customer_uid', 'merchant_id', 'status'],
      ],
      'importes' => [
        'label' => $this->t('Importes'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Desglose economico del pedido.'),
        'fields' => ['subtotal', 'tax_amount', 'shipping_cost', 'discount_amount', 'total'],
      ],
      'pago' => [
        'label' => $this->t('Pago'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Metodo y estado del pago.'),
        'fields' => ['payment_method', 'payment_status', 'payment_intent_id'],
      ],
      'envio' => [
        'label' => $this->t('Envio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Direcciones y seguimiento del envio.'),
        'fields' => ['shipping_address', 'billing_address', 'shipping_method', 'tracking_number'],
      ],
      'notas' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Notas internas del pedido.'),
        'fields' => ['notes'],
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
