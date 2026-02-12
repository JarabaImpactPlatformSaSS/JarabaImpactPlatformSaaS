<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\BillingCustomer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para la entidad BillingCustomer.
 *
 * @covers \Drupal\jaraba_billing\Entity\BillingCustomer
 * @group jaraba_billing
 */
class BillingCustomerTest extends UnitTestCase {

  /**
   * Tests that the class exists and has correct annotation.
   */
  public function testEntityAnnotation(): void {
    $this->assertTrue(class_exists(BillingCustomer::class));

    $reflection = new \ReflectionClass(BillingCustomer::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('@ContentEntityType', $docComment);
    $this->assertStringContainsString('id = "billing_customer"', $docComment);
    $this->assertStringContainsString('base_table = "billing_customer"', $docComment);
  }

  /**
   * Tests that the entity implements EntityChangedInterface.
   */
  public function testImplementsEntityChanged(): void {
    $reflection = new \ReflectionClass(BillingCustomer::class);
    $interfaces = $reflection->getInterfaceNames();

    $this->assertContains(
      'Drupal\Core\Entity\EntityChangedInterface',
      $interfaces,
      'BillingCustomer should implement EntityChangedInterface'
    );
  }

  /**
   * Tests that the entity has correct handlers.
   */
  public function testEntityHandlers(): void {
    $reflection = new \ReflectionClass(BillingCustomer::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('BillingCustomerListBuilder', $docComment);
    $this->assertStringContainsString('BillingCustomerForm', $docComment);
    $this->assertStringContainsString('BillingCustomerAccessControlHandler', $docComment);
  }

  /**
   * Tests entity keys.
   */
  public function testEntityKeys(): void {
    $reflection = new \ReflectionClass(BillingCustomer::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('"label" = "billing_name"', $docComment);
  }

  /**
   * Tests that field_ui_base_route is set.
   */
  public function testFieldUiBaseRoute(): void {
    $reflection = new \ReflectionClass(BillingCustomer::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('field_ui_base_route = "jaraba_billing.billing_customer.settings"', $docComment);
  }

}
