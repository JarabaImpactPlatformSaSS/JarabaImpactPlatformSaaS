<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class SitePageTreeSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'site_page_tree_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Nodo del Arbol de Paginas.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
