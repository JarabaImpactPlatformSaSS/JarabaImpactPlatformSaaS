<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class FocMetricSnapshotSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'foc_metric_snapshot_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Snapshot de Metricas FOC.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
