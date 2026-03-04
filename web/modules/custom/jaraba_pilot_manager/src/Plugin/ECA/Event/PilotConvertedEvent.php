<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a pilot tenant converts to a paid plan.
 *
 * @EcaEvent(
 *   id = "jaraba_pilot_manager_pilot_converted",
 *   label = @Translation("Pilot tenant converted"),
 *   description = @Translation("Fires when a pilot tenant status changes to converted."),
 *   event_name = "jaraba_pilot_manager.pilot_converted",
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'pilot_tenant_id', description: 'The pilot tenant ID.')]
#[Token(name: 'converted_plan', description: 'The plan the tenant converted to.')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class PilotConvertedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_pilot_manager_pilot_converted' => [
        'label' => 'Pilot tenant converted',
        'event_name' => 'jaraba_pilot_manager.pilot_converted',
        'event_class' => \Drupal\Component\EventDispatcher\Event::class,
      ],
    ];
  }

}
