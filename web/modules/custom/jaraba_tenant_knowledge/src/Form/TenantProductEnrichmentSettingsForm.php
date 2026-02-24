<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class TenantProductEnrichmentSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'tenant_product_enrichment_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Enriquecimiento de Producto.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
