<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing mentoring engagements.
 */
class MentoringEngagementForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'engagement' => [
        'label' => $this->t('Engagement'),
        'icon' => ['category' => 'education', 'name' => 'book'],
        'description' => $this->t('Mentor-mentee pairing and package details.'),
        'fields' => ['mentor_id', 'mentee_id', 'package_id', 'business_diagnostic_id'],
      ],
      'payment' => [
        'label' => $this->t('Payment'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Payment and transaction details.'),
        'fields' => ['order_id', 'payment_intent_id', 'amount_paid'],
      ],
      'sessions' => [
        'label' => $this->t('Sessions'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Session tracking and schedule.'),
        'fields' => ['sessions_total', 'sessions_used', 'sessions_remaining', 'start_date', 'expiry_date'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Engagement status and goals.'),
        'fields' => ['status', 'goals'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'book'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
