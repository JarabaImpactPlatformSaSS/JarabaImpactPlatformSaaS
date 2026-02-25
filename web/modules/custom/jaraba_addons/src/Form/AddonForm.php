<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing add-ons.
 */
class AddonForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'addon' => [
        'label' => $this->t('Add-on'),
        'icon' => ['category' => 'ui', 'name' => 'package'],
        'description' => $this->t('Name, type, and description.'),
        'fields' => ['label', 'machine_name', 'description', 'addon_type'],
      ],
      'pricing' => [
        'label' => $this->t('Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Monthly and yearly pricing.'),
        'fields' => ['price_monthly', 'price_yearly'],
      ],
      'features' => [
        'label' => $this->t('Features'),
        'icon' => ['category' => 'ui', 'name' => 'list'],
        'description' => $this->t('Included features and limits.'),
        'fields' => ['features_included', 'limits'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'package'];
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
