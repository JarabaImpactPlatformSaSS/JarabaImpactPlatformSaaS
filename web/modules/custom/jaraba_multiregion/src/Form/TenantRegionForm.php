<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario de creacion/edicion de configuracion regional por tenant.
 */
class TenantRegionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'regional' => [
        'label' => $this->t('Regional Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'globe'],
        'description' => $this->t('Legal jurisdiction, data region and datacenter.'),
        'fields' => ['tenant_id', 'legal_jurisdiction', 'data_region', 'primary_dc'],
      ],
      'currency' => [
        'label' => $this->t('Currency'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Base currency, display currencies and Stripe account country.'),
        'fields' => ['base_currency', 'display_currencies', 'stripe_account_country'],
      ],
      'fiscal' => [
        'label' => $this->t('Fiscal'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('VAT number and VIES validation status.'),
        'fields' => ['vat_number', 'vies_validated', 'vies_validated_at'],
      ],
      'gdpr' => [
        'label' => $this->t('GDPR'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('GDPR representative for this jurisdiction.'),
        'fields' => ['gdpr_representative'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'globe'];
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
