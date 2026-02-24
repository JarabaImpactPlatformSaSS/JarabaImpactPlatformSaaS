<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class ExternalEventCacheSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'external_event_cache_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Evento Externo (Cache).') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
