<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de visualizacion/edicion de validaciones VIES.
 */
class ViesValidationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'result' => [
        'label' => $this->t('Result'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('VAT number, country and validation result.'),
        'fields' => ['tenant_id', 'vat_number', 'country_code', 'is_valid'],
      ],
      'company_data' => [
        'label' => $this->t('Company Data'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Company name and address returned by VIES.'),
        'fields' => ['company_name', 'company_address'],
      ],
      'traceability' => [
        'label' => $this->t('Traceability'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('VIES request identifier and validation timestamp.'),
        'fields' => ['request_identifier', 'validated_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
