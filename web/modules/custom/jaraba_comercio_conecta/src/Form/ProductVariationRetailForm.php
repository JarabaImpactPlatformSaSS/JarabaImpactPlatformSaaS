<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar variaciones de producto.
 *
 * Estructura: Extiende ContentEntityForm con campos organizados
 *   en fieldsets temáticos para facilitar la edición.
 *
 * Lógica: Los atributos (color, talla, etc.) se introducen como JSON
 *   para máxima flexibilidad. El precio de la variación permite
 *   tener precios diferentes por combinación de atributos.
 */
class ProductVariationRetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo: Información básica
    $form['info_basica'] = [
      '#type' => 'details',
      '#title' => $this->t('Información Básica'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    // Mover campos al grupo de información básica
    $basic_fields = ['product_id', 'title', 'sku', 'attributes', 'status', 'sort_order'];
    foreach ($basic_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['info_basica'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Precio e Inventario
    $form['precio_inventario'] = [
      '#type' => 'details',
      '#title' => $this->t('Precio e Inventario'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    $price_fields = ['price', 'compare_at_price', 'stock_quantity', 'weight', 'barcode_value'];
    foreach ($price_fields as $field_name) {
      if (isset($form[$field_name])) {
        $form['precio_inventario'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    // Grupo: Imagen
    $form['media'] = [
      '#type' => 'details',
      '#title' => $this->t('Imagen'),
      '#open' => FALSE,
      '#weight' => 2,
    ];

    if (isset($form['image'])) {
      $form['media']['image'] = $form['image'];
      unset($form['image']);
    }

    // Grupo: Configuración técnica (tenant)
    $form['configuracion'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    if (isset($form['tenant_id'])) {
      $form['configuracion']['tenant_id'] = $form['tenant_id'];
      unset($form['tenant_id']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Lógica: Muestra mensaje de confirmación con el nombre de la variación
   *   y redirige a la colección de variaciones.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    $label = $entity->get('title')->value;

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('La variación %label ha sido creada.', [
        '%label' => $label,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('La variación %label ha sido actualizada.', [
        '%label' => $label,
      ]));
    }

    $form_state->setRedirect('entity.product_variation_retail.collection');
    return $status;
  }

}
