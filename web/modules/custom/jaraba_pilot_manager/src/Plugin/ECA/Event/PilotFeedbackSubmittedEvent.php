<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Plugin\ECA\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when pilot feedback is submitted.
 *
 * @EcaEvent(
 *   id = "jaraba_pilot_manager_pilot_feedback_submitted",
 *   label = @Translation("Pilot feedback submitted"),
 *   description = @Translation("Fires when a new pilot feedback entity is created."),
 *   event_name = "jaraba_pilot_manager.pilot_feedback_submitted",
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'feedback_id', description: 'The pilot feedback ID.')]
#[Token(name: 'feedback_type', description: 'The type of feedback (nps, csat, etc.).')]
#[Token(name: 'score', description: 'The feedback score (0-10).')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class PilotFeedbackSubmittedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_pilot_manager_pilot_feedback_submitted' => [
        'label' => 'Pilot feedback submitted',
        'event_name' => 'jaraba_pilot_manager.pilot_feedback_submitted',
        'event_class' => Event::class,
      ],
    ];
  }

}
