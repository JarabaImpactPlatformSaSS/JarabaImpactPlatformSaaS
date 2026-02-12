<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad ReferralProgram.
 *
 * ESTRUCTURA: FormBase simple que sirve como ruta base para Field UI.
 *
 * LÓGICA: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestañas "Manage fields" y "Manage display".
 *
 * RELACIONES:
 * - ReferralProgramSettingsForm <- ReferralProgram entity (field_ui_base_route apunta aquí)
 * - ReferralProgramSettingsForm <- jaraba_referral.routing.yml (ruta definida)
 */
class ReferralProgramSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'referral_program_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Programa de Referidos. Usa las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
