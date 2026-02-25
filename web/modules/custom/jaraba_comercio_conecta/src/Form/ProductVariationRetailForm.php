<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing product variations.
 */
class ProductVariationRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_basica' => [
        'label' => $this->t('Informacion Basica'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Datos principales de la variacion.'),
        'fields' => ['product_id', 'title', 'sku', 'attributes', 'status', 'sort_order'],
      ],
      'precio_inventario' => [
        'label' => $this->t('Precio e Inventario'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Precio, stock y peso de la variacion.'),
        'fields' => ['price', 'compare_at_price', 'stock_quantity', 'weight', 'barcode_value'],
      ],
      'media' => [
        'label' => $this->t('Imagen'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Imagen especifica de esta variacion.'),
        'fields' => ['image'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'tag'];
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
