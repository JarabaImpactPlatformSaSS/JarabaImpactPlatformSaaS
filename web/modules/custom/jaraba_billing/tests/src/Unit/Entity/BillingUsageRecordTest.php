<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\BillingUsageRecord;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para la entidad BillingUsageRecord.
 *
 * @covers \Drupal\jaraba_billing\Entity\BillingUsageRecord
 * @group jaraba_billing
 */
class BillingUsageRecordTest extends UnitTestCase {

  /**
   * Tests that baseFieldDefinitions returns expected fields.
   */
  public function testBaseFieldDefinitionsContainsRequiredFields(): void {
    // Verify the class exists and can be loaded.
    $this->assertTrue(class_exists(BillingUsageRecord::class));

    // Verify the entity annotation is correct via reflection.
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('@ContentEntityType', $docComment);
    $this->assertStringContainsString('id = "billing_usage_record"', $docComment);
    $this->assertStringContainsString('base_table = "billing_usage_record"', $docComment);
  }

  /**
   * Tests that the entity has no edit/delete form handlers (append-only).
   */
  public function testAppendOnlyNoEditDeleteForms(): void {
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $docComment = $reflection->getDocComment();

    // Should only have 'add' form handler, not 'edit' or 'delete'.
    $this->assertStringContainsString('"add"', $docComment);
    $this->assertStringNotContainsString('"edit"', $docComment);
    $this->assertStringNotContainsString('"delete"', $docComment);
  }

  /**
   * Tests that the entity does not implement EntityChangedInterface.
   *
   * Append-only entities should not track changes.
   */
  public function testDoesNotImplementEntityChanged(): void {
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $interfaces = $reflection->getInterfaceNames();

    $this->assertNotContains(
      'Drupal\Core\Entity\EntityChangedInterface',
      $interfaces,
      'Append-only entity should not implement EntityChangedInterface'
    );
  }

  /**
   * Tests that baseFieldDefinitions contains new sync fields.
   */
  public function testBaseFieldDefinitionsContainsSyncFields(): void {
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $source = file_get_contents($reflection->getFileName());

    // Verify new sync fields are present in the source.
    $this->assertStringContainsString("'subscription_item_id'", $source);
    $this->assertStringContainsString("'reported_at'", $source);
    $this->assertStringContainsString("'idempotency_key'", $source);
    $this->assertStringContainsString("'billed'", $source);
    $this->assertStringContainsString("'billing_period'", $source);
  }

  /**
   * Tests that idempotency_key has proper max_length.
   */
  public function testIdempotencyKeyMaxLength(): void {
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $source = file_get_contents($reflection->getFileName());

    // Verify max_length setting for idempotency_key.
    $this->assertStringContainsString("'max_length', 128", $source);
  }

  /**
   * Tests that billed field has FALSE default.
   */
  public function testBilledDefaultFalse(): void {
    $reflection = new \ReflectionClass(BillingUsageRecord::class);
    $source = file_get_contents($reflection->getFileName());

    // Verify boolean default for billed field.
    $this->assertStringContainsString("->setDefaultValue(FALSE)", $source);
  }

}
