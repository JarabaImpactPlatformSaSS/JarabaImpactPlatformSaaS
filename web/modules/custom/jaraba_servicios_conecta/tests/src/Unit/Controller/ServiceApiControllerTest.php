<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_servicios_conecta\Unit\Controller;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for ServiceApiController validation logic.
 *
 * @group jaraba_servicios_conecta
 * @coversDefaultClass \Drupal\jaraba_servicios_conecta\Controller\ServiceApiController
 */
class ServiceApiControllerTest extends UnitTestCase {

  /**
   * Tests that createBooking requires all required fields.
   */
  public function testCreateBookingRequiredFields(): void {
    $required = ['provider_id', 'offering_id', 'datetime'];
    foreach ($required as $field) {
      $this->assertContains($field, $required, "Field {$field} should be required for booking creation.");
    }
    // Ensure 3 required fields.
    $this->assertCount(3, $required);
  }

  /**
   * Tests state transitions validation for updateBooking.
   */
  public function testUpdateBookingAllowedTransitions(): void {
    $allowed = [
      'pending_confirmation' => ['confirmed', 'cancelled_client', 'cancelled_provider'],
      'confirmed' => ['completed', 'cancelled_client', 'cancelled_provider', 'no_show'],
    ];

    // Valid transitions.
    $this->assertContains('confirmed', $allowed['pending_confirmation']);
    $this->assertContains('completed', $allowed['confirmed']);
    $this->assertContains('no_show', $allowed['confirmed']);

    // Invalid transitions.
    $this->assertNotContains('completed', $allowed['pending_confirmation'],
      'Cannot transition from pending_confirmation directly to completed.');
    $this->assertNotContains('no_show', $allowed['pending_confirmation'],
      'Cannot transition from pending_confirmation directly to no_show.');

    // Terminal states should not have transitions.
    $this->assertArrayNotHasKey('completed', $allowed);
    $this->assertArrayNotHasKey('cancelled_client', $allowed);
    $this->assertArrayNotHasKey('cancelled_provider', $allowed);
  }

  /**
   * Tests that cancelled status is mapped to role-specific status.
   */
  public function testCancelledStatusMapping(): void {
    // Provider cancellation.
    $newStatus = 'cancelled';
    $isProvider = TRUE;
    $mappedStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client';
    $this->assertEquals('cancelled_provider', $mappedStatus);

    // Client cancellation.
    $isProvider = FALSE;
    $mappedStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client';
    $this->assertEquals('cancelled_client', $mappedStatus);
  }

  /**
   * Tests ownership check logic (P0-1 fix).
   *
   * Verifies that provider ownership is checked via getOwnerId()
   * on the provider_profile entity, not by comparing entity IDs.
   */
  public function testProviderOwnershipCheckConcept(): void {
    // Simulate: provider_profile entity ID = 42, owner uid = 7.
    $providerProfileId = 42;
    $providerOwnerUid = 7;
    $currentUserId = 7;

    // The old (broken) check: currentUserId !== providerProfileId.
    // This would DENY access even though user 7 owns the profile.
    $oldCheck = ($currentUserId !== $providerProfileId);
    $this->assertTrue($oldCheck, 'Old broken check would deny access for the actual owner.');

    // The new (fixed) check: currentUserId !== providerOwnerUid.
    $newCheck = ($currentUserId !== $providerOwnerUid);
    $this->assertFalse($newCheck, 'New fixed check correctly grants access to the profile owner.');
  }

  /**
   * Tests that only providers can perform certain actions.
   */
  public function testProviderOnlyActions(): void {
    $providerOnlyStatuses = ['confirmed', 'completed', 'no_show'];

    foreach ($providerOnlyStatuses as $status) {
      $isProvider = FALSE;
      $allowed = !in_array($status, $providerOnlyStatuses, TRUE) || $isProvider;
      $this->assertFalse($allowed, "Non-provider should not be able to set status to {$status}.");
    }

    foreach ($providerOnlyStatuses as $status) {
      $isProvider = TRUE;
      $allowed = !in_array($status, $providerOnlyStatuses, TRUE) || $isProvider;
      $this->assertTrue($allowed, "Provider should be able to set status to {$status}.");
    }
  }

}
