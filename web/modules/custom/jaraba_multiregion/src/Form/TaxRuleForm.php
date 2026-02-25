<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de reglas fiscales.
 */
class TaxRuleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'description' => $this->t('Country code, tax name and EU membership.'),
        'fields' => ['country_code', 'tax_name', 'eu_member'],
      ],
      'tax_rates' => [
        'label' => $this->t('Tax Rates'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Standard, reduced, super-reduced and digital services rates.'),
        'fields' => ['standard_rate', 'reduced_rate', 'super_reduced_rate', 'digital_services_rate'],
      ],
      'configuration' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('OSS threshold and reverse charge settings.'),
        'fields' => ['oss_threshold', 'reverse_charge_enabled'],
      ],
      'validity' => [
        'label' => $this->t('Validity'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Effective date range for this tax rule.'),
        'fields' => ['effective_from', 'effective_to'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'coins'];
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
