<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para UsageLimitRecord.
 *
 * En producción, los registros de límites se generan automáticamente
 * vía AupEnforcerService. Este formulario permite la gestión manual.
 */
class UsageLimitRecordForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Tenant this usage limit record belongs to.'),
        'fields' => ['tenant_id'],
      ],
      'limit_config' => [
        'label' => $this->t('Limit Configuration'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Resource type, limit value, and current usage.'),
        'fields' => ['limit_type', 'limit_value', 'current_usage'],
      ],
      'period' => [
        'label' => $this->t('Measurement Period'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Period of measurement for this usage limit.'),
        'fields' => ['period'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
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
