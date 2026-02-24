<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class TenantThemeConfigSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'tenant_theme_config_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Configuración de Tema.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
