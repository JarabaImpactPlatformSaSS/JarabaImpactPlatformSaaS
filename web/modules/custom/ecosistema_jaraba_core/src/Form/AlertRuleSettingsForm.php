<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Regla de Alerta entity type settings.
 */
final class AlertRuleSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'alert_rule_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Regla de Alerta.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
