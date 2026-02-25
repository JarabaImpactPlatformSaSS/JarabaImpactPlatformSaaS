<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing retail products.
 */
class ProductRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_basica' => [
        'label' => $this->t('Informacion Basica'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Datos principales del producto.'),
        'fields' => ['title', 'sku', 'merchant_id', 'category_id', 'brand_id', 'description', 'short_description'],
      ],
      'precio' => [
        'label' => $this->t('Precio'),
        'icon' => ['category' => 'commerce', 'name' => 'wallet'],
        'description' => $this->t('Precio de venta, coste e impuestos.'),
        'fields' => ['price', 'compare_at_price', 'cost_price', 'tax_rate'],
      ],
      'inventario' => [
        'label' => $this->t('Inventario'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Control de stock y variaciones.'),
        'fields' => ['stock_quantity', 'low_stock_threshold', 'has_variations'],
      ],
      'dimensiones' => [
        'label' => $this->t('Dimensiones y Peso'),
        'icon' => ['category' => 'ui', 'name' => 'ruler'],
        'description' => $this->t('Medidas fisicas del producto para envio.'),
        'fields' => ['weight', 'dimensions_length', 'dimensions_width', 'dimensions_height'],
      ],
      'codigo_barras' => [
        'label' => $this->t('Codigo de Barras'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Codigo de barras para identificacion.'),
        'fields' => ['barcode_type', 'barcode_value'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Optimizacion para motores de busqueda.'),
        'fields' => ['seo_title', 'seo_description'],
      ],
      'estado' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Estado del producto en el catalogo.'),
        'fields' => ['status'],
      ],
      'media' => [
        'label' => $this->t('Imagenes'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Galeria de imagenes del producto.'),
        'fields' => ['images'],
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
  protected function getCharacterLimits(): array {
    return [
      'seo_title' => 70,
      'seo_description' => 160,
    ];
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
