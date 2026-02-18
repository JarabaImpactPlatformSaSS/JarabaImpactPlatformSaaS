<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad ProductRetail.
 *
 * Estructura: FormBase simple como ruta base para Field UI.
 *
 * Lógica: Proporciona el field_ui_base_route necesario para
 *   "Manage fields" y "Manage display".
 */
class ProductRetailSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'product_retail_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Producto Retail. Use las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
