<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a pilot program is started.
 *
 * @EcaEvent(
 *   id = "jaraba_pilot_manager_pilot_started",
 *   label = @Translation("Pilot program started"),
 *   description = @Translation("Fires when a pilot program status changes to active."),
 *   event_name = "jaraba_pilot_manager.pilot_started",
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'program_id', description: 'The pilot program ID.')]
#[Token(name: 'vertical', description: 'The vertical of the pilot program.')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class PilotStartedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_pilot_manager_pilot_started' => [
        'label' => 'Pilot program started',
        'event_name' => 'jaraba_pilot_manager.pilot_started',
        'event_class' => \Drupal\Component\EventDispatcher\Event::class,
      ],
    ];
  }

}
