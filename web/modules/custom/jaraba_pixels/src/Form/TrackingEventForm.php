<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing tracking events.
 */
class TrackingEventForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'event' => [
        'label' => $this->t('Event'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Event details and conversion settings.'),
        'fields' => ['pixel_id', 'event_name', 'event_category', 'event_data', 'is_conversion', 'conversion_value'],
      ],
      'visitor' => [
        'label' => $this->t('Visitor'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Visitor and session information.'),
        'fields' => ['visitor_id', 'session_id', 'page_url', 'referrer', 'user_agent', 'ip_hash'],
      ],
      'delivery' => [
        'label' => $this->t('Delivery'),
        'icon' => ['category' => 'ui', 'name' => 'send'],
        'description' => $this->t('Delivery and deduplication settings.'),
        'fields' => ['dedup_key', 'sent_server_side', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
