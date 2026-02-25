<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for creating/editing WhitelabelReseller entities.
 */
class WhitelabelResellerForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general_info' => [
        'label' => $this->t('General Information'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Reseller identity and contact details.'),
        'fields' => ['name', 'company_name', 'contact_email'],
      ],
      'commercial_config' => [
        'label' => $this->t('Commercial Configuration'),
        'icon' => ['category' => 'commerce', 'name' => 'cart'],
        'description' => $this->t('Commission rates, revenue model and territory.'),
        'fields' => ['commission_rate', 'revenue_share_model', 'territory'],
      ],
      'tenants' => [
        'label' => $this->t('Tenants'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Tenants managed by this reseller.'),
        'fields' => ['managed_tenant_ids'],
      ],
      'contract' => [
        'label' => $this->t('Contract'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Contract dates and reseller status.'),
        'fields' => ['contract_start', 'contract_end', 'reseller_status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'building'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $territoryRaw = $form_state->getValue('territory')[0]['value'] ?? '';
    if (!empty($territoryRaw)) {
      $decoded = json_decode($territoryRaw, TRUE);
      if (!is_array($decoded)) {
        $form_state->setErrorByName('territory', $this->t('Territory must be valid JSON.'));
      }
    }
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
