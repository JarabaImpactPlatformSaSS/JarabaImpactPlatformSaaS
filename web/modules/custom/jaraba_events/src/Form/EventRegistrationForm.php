<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing event registrations.
 */
class EventRegistrationForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'registration' => [
        'label' => $this->t('Registration'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Attendee registration details.'),
        'fields' => ['event_id', 'attendee_name', 'attendee_email', 'attendee_phone', 'registration_status', 'ticket_code'],
      ],
      'payment' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Payment status and transaction details.'),
        'fields' => ['payment_status', 'amount_paid', 'stripe_payment_id'],
      ],
      'attendance' => [
        'label' => $this->t('Attendance'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Check-in and attendance tracking.'),
        'fields' => ['checked_in', 'checkin_time', 'attendance_duration', 'rating', 'feedback'],
      ],
      'certificate' => [
        'label' => $this->t('Certificate'),
        'icon' => ['category' => 'ui', 'name' => 'award'],
        'description' => $this->t('Certificate issuance and tracking source.'),
        'fields' => ['certificate_issued', 'certificate_url', 'source', 'utm_source', 'tenant_id'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'check'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
