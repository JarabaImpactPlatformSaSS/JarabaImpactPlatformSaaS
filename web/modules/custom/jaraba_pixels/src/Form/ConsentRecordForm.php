<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing consent records.
 */
class ConsentRecordForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'consent' => [
        'label' => $this->t('Consent'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Consent details and version.'),
        'fields' => ['visitor_id', 'consent_type', 'status', 'consent_version'],
      ],
      'context' => [
        'label' => $this->t('Context'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Technical context of the consent record.'),
        'fields' => ['ip_address', 'user_agent', 'revoked_at', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
