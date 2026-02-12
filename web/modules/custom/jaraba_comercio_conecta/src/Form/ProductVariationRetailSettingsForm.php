<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad ProductVariationRetail.
 *
 * Estructura: Formulario simple requerido para que field_ui_base_route
 *   funcione correctamente y permita añadir campos personalizados
 *   desde la interfaz de Drupal.
 *
 * Lógica: No tiene configuración real; su existencia permite que
 *   Field UI muestre las pestañas de "Gestionar campos" y
 *   "Gestionar presentación" en /admin/structure/comercio-variations.
 */
class ProductVariationRetailSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'product_variation_retail_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Variación de Producto de ComercioConecta. Utilice las pestañas superiores para gestionar campos y presentación.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Sin configuración que guardar.
  }

}
