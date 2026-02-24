<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class QuoteLineItemSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'quote_line_item_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Linea de Presupuesto.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
