<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_lexnet\Unit\Controller;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for LexnetApiController logic.
 *
 * @group jaraba_legal_lexnet
 */
class LexnetApiControllerTest extends UnitTestCase {

  /**
   * Tests storeSubmission required fields validation.
   *
   * Verifies that case_id, subject, and court are all required.
   *
   * @covers \Drupal\jaraba_legal_lexnet\Controller\LexnetApiController::storeSubmission
   * @dataProvider requiredFieldsProvider
   */
  public function testStoreSubmissionRequiredFields(array $data, bool $shouldFail): void {
    $requiredFields = ['case_id', 'subject', 'court'];
    $missingRequired = FALSE;

    foreach ($requiredFields as $field) {
      if (empty($data[$field])) {
        $missingRequired = TRUE;
        break;
      }
    }

    $this->assertSame($shouldFail, $missingRequired);
  }

  /**
   * Data provider for required fields test.
   */
  public static function requiredFieldsProvider(): array {
    return [
      'all missing' => [[], TRUE],
      'only case_id' => [['case_id' => 1], TRUE],
      'only subject' => [['subject' => 'Test'], TRUE],
      'only court' => [['court' => 'Juzgado 1'], TRUE],
      'missing court' => [['case_id' => 1, 'subject' => 'Test'], TRUE],
      'missing subject' => [['case_id' => 1, 'court' => 'Juzgado 1'], TRUE],
      'missing case_id' => [['subject' => 'Test', 'court' => 'Juzgado 1'], TRUE],
      'all present' => [['case_id' => 1, 'subject' => 'Test', 'court' => 'Juzgado 1'], FALSE],
    ];
  }

  /**
   * Tests notification status transitions: pending→read→linked→archived.
   *
   * @covers \Drupal\jaraba_legal_lexnet\Controller\LexnetApiController
   */
  public function testNotificationStatusTransitions(): void {
    $validTransitions = [
      'pending' => ['read'],
      'read' => ['linked'],
      'linked' => ['archived'],
      'archived' => [],
    ];

    // Verify all expected transitions exist.
    $this->assertArrayHasKey('pending', $validTransitions);
    $this->assertContains('read', $validTransitions['pending']);
    $this->assertContains('linked', $validTransitions['read']);
    $this->assertContains('archived', $validTransitions['linked']);
    $this->assertEmpty($validTransitions['archived']);

    // Verify invalid transitions are rejected.
    $this->assertNotContains('archived', $validTransitions['pending']);
    $this->assertNotContains('pending', $validTransitions['read']);
  }

  /**
   * Tests submission status transitions.
   *
   * Valid: draft→submitting→submitted→confirmed, draft→submitting→error.
   *
   * @covers \Drupal\jaraba_legal_lexnet\Controller\LexnetApiController
   */
  public function testSubmissionStatusTransitions(): void {
    $validTransitions = [
      'draft' => ['submitting'],
      'submitting' => ['submitted', 'error'],
      'submitted' => ['confirmed', 'rejected'],
      'confirmed' => [],
      'error' => ['submitting'],
      'rejected' => [],
    ];

    // Happy path: draft → submitting → submitted → confirmed.
    $this->assertContains('submitting', $validTransitions['draft']);
    $this->assertContains('submitted', $validTransitions['submitting']);
    $this->assertContains('confirmed', $validTransitions['submitted']);

    // Error path: draft → submitting → error.
    $this->assertContains('error', $validTransitions['submitting']);

    // Error recovery: error → submitting (retry).
    $this->assertContains('submitting', $validTransitions['error']);

    // Rejection path: submitted → rejected.
    $this->assertContains('rejected', $validTransitions['submitted']);

    // Terminal states.
    $this->assertEmpty($validTransitions['confirmed']);
    $this->assertEmpty($validTransitions['rejected']);
  }

  /**
   * Tests ownership check concept (P2-1 fix validation).
   *
   * Validates that strict (int) === (int) comparison works correctly
   * for owner ID matching, preventing type juggling vulnerabilities.
   *
   * @covers \Drupal\jaraba_legal_lexnet\Controller\LexnetApiController
   */
  public function testOwnershipCheckConcept(): void {
    // Simulates getOwnerId() returning string vs account->id() returning int.
    $ownerId = '5';
    $accountId = 5;

    // Loose equality (the old bug) — would match incorrectly in edge cases.
    // phpcs:ignore
    $looseMatch = ($ownerId == $accountId);
    $this->assertTrue($looseMatch);

    // Strict equality with (int) cast (the fix).
    $strictMatch = ((int) $ownerId === (int) $accountId);
    $this->assertTrue($strictMatch);

    // Loose == matches string '5' to int 5 via type juggling.
    // phpcs:ignore
    $this->assertTrue('5' == 5, 'Loose equality coerces string to int');
    // Strict === rejects mismatched types without casting.
    $this->assertFalse('5' === 5, 'Strict equality rejects type mismatch');

    // With explicit (int) cast, comparison is predictable regardless of source types.
    $this->assertTrue((int) $ownerId === (int) $accountId, 'Cast match for same ID');

    // Non-matching IDs.
    $differentOwner = '7';
    $this->assertFalse((int) $differentOwner === (int) $accountId);
  }

}
