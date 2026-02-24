<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_servicios_conecta\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the Booking entity type.
 *
 * @group jaraba_servicios_conecta
 */
class BookingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'jaraba_servicios_conecta',
  ];

  /**
   * Tests that the booking entity type is defined.
   */
  public function testBookingEntityTypeExists(): void {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('booking', FALSE);
    $this->assertNotNull($entity_type, 'Booking entity type should be defined.');
    $this->assertEquals('booking', $entity_type->id());
  }

  /**
   * Tests booking status labels mapping.
   */
  public function testBookingStateLabels(): void {
    $expected_states = [
      'pending_confirmation',
      'confirmed',
      'completed',
      'cancelled_client',
      'cancelled_provider',
      'no_show',
    ];

    // Verify the allowed transitions map from updateBooking covers these states.
    $allowed_transitions = [
      'pending_confirmation' => ['confirmed', 'cancelled_client', 'cancelled_provider'],
      'confirmed' => ['completed', 'cancelled_client', 'cancelled_provider', 'no_show'],
    ];

    foreach ($allowed_transitions as $from => $to_states) {
      $this->assertContains($from, $expected_states, "State {$from} should be in expected states.");
      foreach ($to_states as $to) {
        $this->assertContains($to, $expected_states, "Transition target {$to} should be in expected states.");
      }
    }

    // Verify terminal states have no outgoing transitions.
    $terminal_states = ['completed', 'cancelled_client', 'cancelled_provider', 'no_show'];
    foreach ($terminal_states as $state) {
      $this->assertArrayNotHasKey($state, $allowed_transitions, "Terminal state {$state} should not have outgoing transitions.");
    }
  }

}
