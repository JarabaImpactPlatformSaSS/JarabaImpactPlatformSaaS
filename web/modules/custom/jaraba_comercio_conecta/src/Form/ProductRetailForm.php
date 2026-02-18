<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar productos retail.
 *
 * Estructura: Extiende ContentEntityForm. Los campos se organizan
 *   en fieldsets temáticos para facilitar la edición.
 *
 * Lógica: Agrupa campos por categoría funcional: información básica,
 *   precios, inventario, dimensiones, código de barras, SEO e imágenes.
 */
class ProductRetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['info_basica'] = [
      '#type' => 'details',
      '#title' => $this->t('Información Básica'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    foreach (['title', 'sku', 'merchant_id', 'category_id', 'brand_id', 'description', 'short_description'] as $field) {
      if (isset($form[$field])) {
        $form['info_basica'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['precio'] = [
      '#type' => 'details',
      '#title' => $this->t('Precio'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    foreach (['price', 'compare_at_price', 'cost_price', 'tax_rate'] as $field) {
      if (isset($form[$field])) {
        $form['precio'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['inventario'] = [
      '#type' => 'details',
      '#title' => $this->t('Inventario'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    foreach (['stock_quantity', 'low_stock_threshold', 'has_variations'] as $field) {
      if (isset($form[$field])) {
        $form['inventario'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['dimensiones'] = [
      '#type' => 'details',
      '#title' => $this->t('Dimensiones y Peso'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    foreach (['weight', 'dimensions_length', 'dimensions_width', 'dimensions_height'] as $field) {
      if (isset($form[$field])) {
        $form['dimensiones'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['codigo_barras'] = [
      '#type' => 'details',
      '#title' => $this->t('Código de Barras'),
      '#open' => FALSE,
      '#weight' => 40,
    ];
    foreach (['barcode_type', 'barcode_value'] as $field) {
      if (isset($form[$field])) {
        $form['codigo_barras'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['seo'] = [
      '#type' => 'details',
      '#title' => $this->t('SEO'),
      '#open' => FALSE,
      '#weight' => 50,
    ];
    foreach (['seo_title', 'seo_description'] as $field) {
      if (isset($form[$field])) {
        $form['seo'][$field] = $form[$field];
        unset($form[$field]);
      }
    }

    $form['estado_producto'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 60,
    ];
    if (isset($form['status'])) {
      $form['estado_producto']['status'] = $form['status'];
      unset($form['status']);
    }

    $form['media'] = [
      '#type' => 'details',
      '#title' => $this->t('Imágenes'),
      '#open' => TRUE,
      '#weight' => 70,
    ];
    if (isset($form['images'])) {
      $form['media']['images'] = $form['images'];
      unset($form['images']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->entity;
    $message_args = ['%label' => $entity->toLink()->toString()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Producto %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Producto %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
