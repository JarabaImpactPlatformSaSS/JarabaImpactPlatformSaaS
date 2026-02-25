<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing pricing rules.
 */
class PricingRuleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'tag'],
        'description' => $this->t('Rule name and metric.'),
        'fields' => ['name', 'metric_name'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Pricing model and rates.'),
        'fields' => ['pricing_model', 'unit_price', 'tiers_config', 'currency'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Rule status and tenant.'),
        'fields' => ['status', 'tenant_id'],
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
