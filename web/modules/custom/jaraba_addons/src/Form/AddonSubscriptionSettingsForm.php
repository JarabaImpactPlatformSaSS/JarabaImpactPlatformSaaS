<?php

namespace Drupal\jaraba_addons\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad AddonSubscription.
 *
 * ESTRUCTURA: FormBase simple que sirve como ruta base para Field UI.
 *
 * LÓGICA: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestañas "Manage fields" y "Manage display".
 *
 * RELACIONES:
 * - AddonSubscriptionSettingsForm <- AddonSubscription entity (field_ui_base_route)
 * - AddonSubscriptionSettingsForm <- jaraba_addons.routing.yml (ruta definida)
 */
class AddonSubscriptionSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'addon_subscription_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Suscripción a Add-on. Usa las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
