<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a pilot program is completed.
 *
 * @EcaEvent(
 *   id = "jaraba_pilot_manager_pilot_completed",
 *   label = @Translation("Pilot program completed"),
 *   description = @Translation("Fires when a pilot program status changes to completed."),
 *   event_name = "jaraba_pilot_manager.pilot_completed",
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'program_id', description: 'The pilot program ID.')]
#[Token(name: 'vertical', description: 'The vertical of the pilot program.')]
#[Token(name: 'conversion_rate', description: 'The final conversion rate.')]
class PilotCompletedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_pilot_manager_pilot_completed' => [
        'label' => 'Pilot program completed',
        'event_name' => 'jaraba_pilot_manager.pilot_completed',
        'event_class' => \Drupal\Component\EventDispatcher\Event::class,
      ],
    ];
  }

}
