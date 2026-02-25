<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for order item retail entities.
 */
class OrderItemRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'producto' => [
        'label' => $this->t('Producto'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Producto y variacion de la linea de pedido.'),
        'fields' => ['order_id', 'product_id', 'variation_id', 'product_title', 'product_sku'],
      ],
      'precio' => [
        'label' => $this->t('Precio y Cantidad'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Cantidad y precios de la linea.'),
        'fields' => ['quantity', 'unit_price', 'total_price'],
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
