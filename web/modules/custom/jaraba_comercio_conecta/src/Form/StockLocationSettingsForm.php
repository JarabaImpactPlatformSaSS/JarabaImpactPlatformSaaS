<?php

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad StockLocation.
 *
 * Estructura: Formulario requerido por field_ui_base_route para habilitar
 *   Field UI en la entidad de ubicaciones de stock.
 *
 * Lógica: Permite que los administradores accedan a las pestañas
 *   "Gestionar campos" y "Gestionar presentación" desde
 *   /admin/structure/comercio-stock-locations.
 */
class StockLocationSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'stock_location_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Ubicación de Stock de ComercioConecta. Utilice las pestañas superiores para gestionar campos y presentación.') . '</p>',
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
