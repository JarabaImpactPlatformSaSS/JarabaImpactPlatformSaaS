<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing marketing events.
 */
class MarketingEventForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'event' => [
        'label' => $this->t('Event'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Event details and content.'),
        'fields' => ['title', 'slug', 'event_type', 'format', 'description', 'short_desc', 'image', 'speakers'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Date, time, and location settings.'),
        'fields' => ['start_date', 'end_date', 'timezone', 'meeting_url', 'location'],
      ],
      'capacity' => [
        'label' => $this->t('Capacity & Pricing'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Attendee limits and pricing options.'),
        'fields' => ['max_attendees', 'is_free', 'price', 'early_bird_price', 'early_bird_deadline'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication status and SEO.'),
        'fields' => ['status_event', 'featured', 'meta_description', 'schema_type', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && empty($entity->get('slug')->value)) {
      $title = $entity->get('title')->value ?? '';
      $slug = mb_strtolower(trim($title));
      $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
      $slug = preg_replace('/[éèëê]/u', 'e', $slug);
      $slug = preg_replace('/[íìïî]/u', 'i', $slug);
      $slug = preg_replace('/[óòöô]/u', 'o', $slug);
      $slug = preg_replace('/[úùüû]/u', 'u', $slug);
      $slug = preg_replace('/ñ/u', 'n', $slug);
      $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
      $slug = trim($slug, '-');
      $entity->set('slug', $slug);
    }
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
