<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class WebVitalsMetricSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'web_vitals_metric_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Web Vitals Metric.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
