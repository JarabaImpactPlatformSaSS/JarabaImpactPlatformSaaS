<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a pilot tenant abandons the program.
 *
 * @EcaEvent(
 *   id = "jaraba_pilot_manager_pilot_abandoned",
 *   label = @Translation("Pilot tenant abandoned"),
 *   description = @Translation("Fires when a pilot tenant status changes to abandoned."),
 *   event_name = "jaraba_pilot_manager.pilot_abandoned",
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'pilot_tenant_id', description: 'The pilot tenant ID.')]
#[Token(name: 'churn_risk', description: 'The churn risk level at time of abandonment.')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class PilotAbandonedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_pilot_manager_pilot_abandoned' => [
        'label' => 'Pilot tenant abandoned',
        'event_name' => 'jaraba_pilot_manager.pilot_abandoned',
        'event_class' => \Drupal\Component\EventDispatcher\Event::class,
      ],
    ];
  }

}
