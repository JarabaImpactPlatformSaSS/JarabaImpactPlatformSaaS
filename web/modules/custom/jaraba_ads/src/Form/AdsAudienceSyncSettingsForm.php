<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad AdsAudienceSync.
 *
 * ESTRUCTURA: FormBase simple que sirve como ruta base para Field UI.
 *
 * LÓGICA: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestañas "Manage fields" y "Manage display".
 *
 * RELACIONES:
 * - AdsAudienceSyncSettingsForm <- AdsAudienceSync entity (field_ui_base_route apunta aquí)
 * - AdsAudienceSyncSettingsForm <- jaraba_ads.routing.yml (ruta definida)
 */
class AdsAudienceSyncSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ads_audience_sync_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Sincronización de Audiencia. Usa las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
