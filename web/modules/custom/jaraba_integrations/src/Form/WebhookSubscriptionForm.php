<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing webhook subscriptions.
 */
class WebhookSubscriptionForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'webhook' => [
        'label' => $this->t('Webhook'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Webhook endpoint and event configuration.'),
        'fields' => ['label', 'target_url', 'events', 'secret'],
      ],
      'stats' => [
        'label' => $this->t('Statistics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Delivery statistics and health metrics.'),
        'fields' => ['consecutive_failures', 'last_triggered', 'last_response_code', 'total_deliveries'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant settings.'),
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    if ($this->getEntity()->isNew()) {
      $secretField = $form['premium_section_webhook']['secret'] ?? $form['secret'] ?? NULL;
      if ($secretField && is_array($secretField)) {
        $form['premium_section_webhook']['secret']['widget'][0]['value']['#default_value'] = hash_hmac('sha256', random_bytes(32), 'jaraba-webhook');
      }
    }
    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
