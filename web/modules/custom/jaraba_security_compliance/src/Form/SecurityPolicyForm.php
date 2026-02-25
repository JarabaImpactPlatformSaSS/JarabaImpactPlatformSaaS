<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing security policies.
 */
class SecurityPolicyForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Policy name, type, and tenant.'),
        'fields' => ['name', 'policy_type', 'tenant_id'],
      ],
      'content_version' => [
        'label' => $this->t('Content & Version'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Policy content, version, and dates.'),
        'fields' => ['content', 'version', 'effective_date', 'review_date'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Policy status.'),
        'fields' => ['policy_status'],
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
