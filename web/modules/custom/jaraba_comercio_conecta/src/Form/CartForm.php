<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing carts.
 */
class CartForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'carrito' => [
        'label' => $this->t('Carrito'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Datos del carrito de compra.'),
        'fields' => ['session_id', 'status', 'coupon_id'],
      ],
      'importes' => [
        'label' => $this->t('Importes'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Desglose economico del carrito.'),
        'fields' => ['subtotal', 'discount_amount', 'shipping_method', 'shipping_cost', 'total'],
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
