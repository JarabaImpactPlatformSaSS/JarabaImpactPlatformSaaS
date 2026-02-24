<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Contenido Generado IA entity type settings.
 */
final class CopilotGeneratedContentAgroSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'copilot_generated_content_agro_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Contenido Generado IA.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
