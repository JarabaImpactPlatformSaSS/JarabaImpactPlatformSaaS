<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing Vertical entities.
 */
class VerticalForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información Básica'),
        'icon' => ['category' => 'ui', 'name' => 'layers'],
        'description' => $this->t('Nombre, machine name, descripción y estado.'),
        'fields' => ['name', 'machine_name', 'description', 'status'],
      ],
      'features' => [
        'label' => $this->t('Features y Agentes'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Funcionalidades habilitadas y agentes IA.'),
        'fields' => ['enabled_features', 'ai_agents'],
      ],
      'theming' => [
        'label' => $this->t('Configuración de Tema'),
        'icon' => ['category' => 'ui', 'name' => 'palette'],
        'description' => $this->t('JSON con colores, tipografía y logo.'),
        'fields' => ['theme_settings'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layers'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add description for theme_settings JSON field.
    $section = 'premium_section_theming';
    if (isset($form[$section]['theme_settings'])) {
      $form[$section]['theme_settings']['#description'] = $this->t('JSON con colores, tipografía y logo. Ejemplo: {"color_primario": "#FF8C42", "color_secundario": "#2D3436"}');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate machine_name is alphanumeric.
    $machine_name = $form_state->getValue(['machine_name', 0, 'value']);
    if ($machine_name && !preg_match('/^[a-z0-9_]+$/', $machine_name)) {
      $form_state->setErrorByName('machine_name', $this->t('El machine name solo puede contener letras minúsculas, números y guiones bajos.'));
    }

    // Validate theme_settings JSON.
    $theme_settings = $form_state->getValue(['theme_settings', 0, 'value']);
    if ($theme_settings && json_decode($theme_settings) === NULL) {
      $form_state->setErrorByName('theme_settings', $this->t('La configuración de tema debe ser un JSON válido.'));
    }
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
