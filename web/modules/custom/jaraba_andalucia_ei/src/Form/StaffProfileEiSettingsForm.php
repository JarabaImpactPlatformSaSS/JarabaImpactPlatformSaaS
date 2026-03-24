<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for StaffProfileEi entity (Field UI base route target).
 *
 * FIELD-UI-SETTINGS-TAB-001: Provides the settings page so Field UI
 * tabs (Manage fields, Manage display) can attach to this entity type.
 */
class StaffProfileEiSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'staff_profile_ei_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de los perfiles profesionales del programa Andalucía +ei. Utiliza las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op: settings form is a placeholder for Field UI tabs.
  }

}
