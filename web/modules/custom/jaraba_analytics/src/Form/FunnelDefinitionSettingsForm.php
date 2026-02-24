<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Funnel Definition entity type settings.
 */
final class FunnelDefinitionSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'funnel_definition_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Funnel Definition.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
