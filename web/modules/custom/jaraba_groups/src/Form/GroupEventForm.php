<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form controller for Group Event forms.
 */
class GroupEventForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'event' => [
        'label' => $this->t('Event'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Event title, description, type, and format.'),
        'fields' => ['group_id', 'title', 'description', 'event_type', 'format'],
      ],
      'schedule' => [
        'label' => $this->t('Schedule'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Start and end times, timezone.'),
        'fields' => ['start_datetime', 'end_datetime', 'timezone'],
      ],
      'location' => [
        'label' => $this->t('Location'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Physical location or online meeting URL.'),
        'fields' => ['location', 'meeting_url'],
      ],
      'capacity' => [
        'label' => $this->t('Capacity & Pricing'),
        'icon' => ['category' => 'commerce', 'name' => 'price'],
        'description' => $this->t('Attendee limits and pricing.'),
        'fields' => ['max_attendees', 'is_free', 'price'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Event publication status.'),
        'fields' => ['status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'actions', 'name' => 'calendar'];
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
